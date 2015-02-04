<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Tickets
 *
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Import_Operation' ) ) {

	/**
	 * Class Rt_HD_Tickets
	 * HD : Help Desk
	 * This class is for tickets related functions
	 * todo:what this function does ?
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Import_Operation {

		/**
		 * set hooks
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static $FOLLOWUP_PUBLIC = 10;
		public static $FOLLOWUP_SENSITIVE = 20;
		public static $FOLLOWUP_STAFF = 30;

		public function __construct() {
			$this->hooks();
		}

		/**
		 * hook function
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function hooks() {
			add_filter( 'comments_clauses', array( $this, 'filter_comment_from_admin' ), 100, 1 );

			add_action( 'comment_post', array( $this, 'mail_new_comment_data' ), 10, 2 );

			add_action( 'wp_ajax_rthd_add_new_followup_ajax', array( $this, 'add_new_followup_ajax' ) );
			add_action( 'wp_ajax_rthd_add_new_followup_front', array( $this, 'add_new_followup_front' ) );
			add_action( 'wp_ajax_nopriv_rthd_add_new_followup_front', array( $this, 'add_new_followup_front' ) );
			add_action( 'wp_ajax_helpdesk_delete_followup', array( $this, 'delete_followup_ajax' ) );
			add_action( 'wp_ajax_load_more_followup', array( $this, 'load_more_followup' ) );
			add_action( 'wp_ajax_nopriv_load_more_followup', array( $this, 'load_more_followup' ) );
			add_action( 'wp_ajax_rthd_add_new_ticket_ajax', array( $this, 'add_new_ticket_ajax' ) );
			add_action( 'wp_ajax_nopriv_rthd_add_new_ticket_ajax', array( $this, 'add_new_ticket_ajax' ) );
			add_action( 'read_rt_mailbox_email_'.RT_HD_TEXT_DOMAIN, array( $this, 'process_email_to_ticket' ), 10, 16 );
			add_action( 'wp_ajax_ticket_bulk_edit', array( $this, 'ticket_bulk_edit' ) );
			add_action( 'wp_ajax_front_end_status_change', array( $this, 'front_end_status_change' ) );
		}

		function ticket_bulk_edit(){
			$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
			$status = ( isset( $_POST[ 'ticket_status' ] ) && ! empty( $_POST[ 'ticket_status' ] ) ) ? $_POST[ 'ticket_status' ] : NULL;
			if ( ! empty( $post_ids ) && is_array( $post_ids ) && ! empty( $status ) ) {
				global $rt_hd_module;
				$labels = $rt_hd_module->labels;
				foreach( $post_ids as $post_id ) {
					rthd_update_ticket_updated_by_user( $post_id, get_current_user_id() );
					$old = get_post_status( $post_id );
					if ( $old == $status ){
						die();
					}
					global $rt_hd_email_notification;
					$body = $labels['name'].' Status '.rthd_status_markup( $old ).' changed to '.rthd_status_markup( $status );
					$rt_hd_email_notification->notification_ticket_updated( $post_id, $labels['name'], $body, array() );
				}
			}
		}

		function add_new_ticket_ajax(){
			$result = array();

			if ( !isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'rt_hd_ticket_edit' ) ){
				$result['status'] = false;
				$result['msg'] = "Incorrect nonce";
				echo json_encode( $result );
				die();
			}
			if ( ! isset( $_POST['post_id'] ) && ! isset( $_POST['body'] ) ){
				$result['status'] = false;
				$result['msg'] = "Incorrect Param";
				echo json_encode( $result );
				die();
			}
			wp_update_post( array (
				                'ID'           => $_POST['post_id'],
				                'post_content' => rthd_content_filter( $_POST['body'] ),
			                ) );
			$subject = rthd_create_new_ticket_title( 'rthd_update_ticket_email_title', $_POST['post_id'] );
			$body = "Ticket content updated : ". rthd_content_filter( $_POST['body'] );
			$flag = false;
			$redux = rthd_get_redux_settings();
			if ( 1 != $redux['rthd_notification_events']['status_metadata_changed'] ){
				$flag = false;
			}
			else{
				$flag = true;
			}
			$this->notify_subscriber_via_email( $_POST['post_id'], $subject, rthd_get_general_body_template( $body ), array(), $_POST['post_id'], $flag, false );
			$result['status'] = true;
			echo json_encode( $result );
			die();
		}

		/**
		 * filer comment from admin
		 *
		 * @param $_comment1
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function filter_comment_from_admin( $_comment1 ) {
			global $hook_suffix;
			if ( in_array( $hook_suffix, array( 'edit-comments.php', 'index.php' ) ) ) {
				global $wpdb;
				if ( false === strpos( $_comment1['join'], $wpdb->posts ) ) {
					$_comment1['join'] .= " left join $wpdb->posts on {$wpdb->comments}.comment_post_id = {$wpdb->posts}.id ";
				}
				$_comment1['where'] .= " and $wpdb->posts.post_type NOT IN ( '" . Rt_HD_Module::$post_type . "' ) ";
			}

			return $_comment1;
		}

		/**
		 * add new ticket
		 *
		 * @param        $title
		 * @param        $body
		 * @param        $mailtime
		 * @param        $allemail
		 * @param        $uploaded
		 * @param        $senderEmail
		 * @param string $messageid
		 * @param string $inreplyto
		 * @param string $references
		 * @param array  $subscriber
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $senderEmail, $messageid = '', $inreplyto = '', $references = '', $subscriber = array(), $originalBody = '' ) {
			global $rt_hd_module, $rt_hd_tickets_operation, $rt_hd_ticket_history_model, $rt_hd_contacts;
			$d             = new DateTime( $mailtime );
			$timeStamp     = $d->getTimestamp();
			$post_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
			$post_type     = Rt_HD_Module::$post_type;
			$labels        = $rt_hd_module->labels;
			$settings      = rthd_get_redux_settings();

			$userid = $rt_hd_contacts->get_user_from_email( $senderEmail );

			$postArray = array(
				'post_author'   => $settings['rthd_default_user'],
				'post_content'  => rthd_content_filter( $body ),
				'post_date'     => $post_date,
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_type'     => $post_type,
				'post_date_gmt' => $post_date_gmt,
			);

			$dataArray = array(
				'assignee'     => $postArray['post_author'],
				'post_content' => rthd_content_filter( $postArray['post_content'] ),
				'post_status'  => $postArray['post_status'],
				'post_title'   => $postArray['post_title'],
			);

			$post_id = $rt_hd_tickets_operation->ticket_default_field_update( $postArray, $dataArray, $post_type, '', $userid, $userid );
			if ( '' != $originalBody ) {
				add_post_meta( $post_id, '_rt_hd_original_email_body', $originalBody );
			}
			// Updating Post Status from publish to unanswered
			$rt_hd_tickets_operation->ticket_default_field_update( array( 'post_status' => 'hd-unanswered', 'post_name' => $post_id ), array( 'post_status' => 'hd-unanswered' ), $post_type, $post_id, $userid, $userid );

			$rt_hd_ticket_history_model->insert( array(
				'ticket_id'   => $post_id,
				'type'        => 'post_status',
				'old_value'   => 'auto-draft',
				'new_value'   => 'hd-unanswered',
				'update_time' => current_time( 'mysql' ),
				'updated_by'  => get_current_user_id(),
			) );

			$rt_hd_tickets_operation->ticket_subscribe_update( $subscriber, $postArray['post_author'], $post_id );

			$this->add_contacts_to_post( $allemail, $post_id );

			$this->add_attachment_to_post( $uploaded, $post_id );

			update_post_meta( $post_id, '_rtbiz_hd_email', $senderEmail );
			update_post_meta( $post_id, '_rtbiz_hd_email', $senderEmail );

			global $transaction_id;
			if ( isset( $transaction_id ) && $transaction_id > 0 ) {
				update_post_meta( $post_id, '_rtbiz_hd_transaction_id', $transaction_id );
			}
			if ( $messageid != '' ) {
				update_post_meta( $post_id, '_rtbiz_hd_messageid', $messageid );
			}
			if ( $inreplyto != '' ) {
				update_post_meta( $post_id, '_rtbiz_hd_inreplyto', $inreplyto );
			}
			if ( $references != '' ) {
				update_post_meta( $post_id, '_rtbiz_hd_references', $references );
			}

			//send Notification
			global $bulkimport, $gravity_auto_import, $rt_hd_email_notification, $helpdesk_import_ticket_id;
			if ( isset( $gravity_auto_import ) && $gravity_auto_import ) {
				$helpdesk_import_ticket_id = $post_id;
				add_filter( 'gform_pre_send_email', array( &$this, 'hijack_mail_subject' ), 999, 2 );
			} else {
				/**
				 * $orignalBody will be empty in case of gravity form imports
				 * But it is taken care of by above if condition.
				 * $originalBody will also be empty if a ticket is created from default support form
				 * Hence pass $email_parse flag based on that.
				 * $email_parse = ! empty( $originalBody )
				 */
				//$rt_hd_email_notification->notification_new_ticket_assigned( $post_id, $settings['rthd_default_user'], $labels['name'], $allemail, $uploaded, $email_parse = ! empty( $originalBody ) );
			}

			$rt_hd_email_notification->notification_new_ticket_created( $post_id,$labels['name'], $body, $allemail, $uploaded );

			return $post_id;
		}

		/**
		 * check email subject for existence of ticket related conversation
		 *
		 * @param $data
		 * @param $message_format
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function hijack_mail_subject( $data, $message_format ) {
			global $helpdesk_import_ticket_id;
			$data['subject'] = '[' . ucfirst( Rt_HD_Module::$name ) . ' #' . $helpdesk_import_ticket_id . ']' . $data['subject'];
			$hd_url          = admin_url( "post.php?post={$helpdesk_import_ticket_id}action=edit" );
			$data['message'] = str_replace( '--rtcamp_hd_link--', $hd_url, $data['message'] );

			return $data;
		}

		/**
		 * add attachment to post from mail
		 *
		 * @since 0.1
		 *
		 * @param      $uploaded
		 * @param      $post_id
		 * @param int  $comment_id
		 */
		function add_attachment_to_post( $uploaded, $post_id, $comment_id = 0 ) {
			global $rt_hd_admin;
			if ( isset( $uploaded ) && is_array( $uploaded ) ) {

				foreach ( $uploaded as $upload ) {

					$post_attachment_hashes = get_post_meta( $post_id, '_rtbiz_hd_attachment_hash' );
					if ( empty( $post_attachment_hashes ) || ! in_array( md5_file( $upload['file'] ), $post_attachment_hashes ) ) {
						//$uploaded["filename"]
						$attachment = array(
							'post_title'     => $upload['filename'],
							'image_alt'      => $upload['filename'],
							'post_content'   => '',
							'post_excerpt'   => '',
							'post_parent'    => $post_id,
							'post_mime_type' => $this->get_mime_type_from_extn( $upload['extn'] ),
							'guid'           => $upload['url'],
						);
						add_filter( 'upload_dir', array( $rt_hd_admin, 'custom_upload_dir' ) );//added hook for add addon specific folder for attachment
						$attach_id  = wp_insert_attachment( $attachment );
						remove_filter( 'upload_dir', array( $rt_hd_admin, 'custom_upload_dir' ) );//remove hook for add addon specific folder for attachment

						add_post_meta( $attach_id, '_wp_attached_file', $upload['file'] );
						add_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $upload['file'] ) );
					}

					if ( $comment_id > 0 ) {
						add_comment_meta( $comment_id, 'attachment', $upload['url'] );
					}
				}
			}
		}

		/**
		 * attach_contacts with post
		 *
		 * @since 0.1
		 *
		 * @param $allemail
		 * @param $post_id
		 */
		function add_contacts_to_post( $allemail, $post_id ) {
			global $rt_hd_contacts;
			$postterms = array();
			foreach ( $allemail as $email ) {
				$term = rt_biz_get_contact_by_email( $email['address'] );
				if ( ! empty( $term ) ) {
					foreach ( $term as $tm ) {
						$postterms[] = $tm->ID;
					}
				} else {
					$term        = $rt_hd_contacts->insert_new_contact( $email['address'], ( isset( $email['name'] ) ) ? $email['name'] : $email['address'] );
					$postterms[] = $term->ID;
				}

				$postterms = array_unique( $postterms );

				if ( ! empty( $postterms ) ) {

					$post_type = get_post_type( $post_id );
					foreach ( $postterms as $term ) {
						rt_biz_connect_post_to_contact( $post_type, $post_id, $term );
					}

					// Update Index
					$ticketModel = new Rt_HD_Ticket_Model();
					$where       = array( 'post_id' => $post_id );
					$attr_name   = rt_biz_get_contact_post_type();
					$data        = array(
						$attr_name        => implode( ',', $postterms ),
						'date_update'     => current_time( 'mysql' ),
						'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
						'user_updated_by' => get_current_user_id(),
					);
					$ticketModel->update_ticket( $data, $where );
					// System Notification -- Contact added
				}
			}
		}

		/**
		 * get post id from subject
		 *
		 * @param $subject
		 *
		 * @return int
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_post_id_from_subject( $subject ) {
			//\[([A-Za-z]+)\ \#([1-9]+)\]
			$pattern     = '/\[([A-Za-z]+)\ \#([0-9]+)\]/im';
			$intMatch    = preg_match_all( $pattern, $subject, $matches );
			$module_name = strtolower( Rt_HD_Module::$name );
			$post_type   = Rt_HD_Module::$post_type;
			if ( count( $matches ) > 0 ) {
				if ( isset( $matches[2][0] ) && isset( $matches[1][0] ) ) {
					if ( strtolower( $matches[1][0] ) == $module_name && get_post_type( intval( $matches[2][0] ) ) == $post_type ) {
						return intval( $matches[2][0] );
					}
				}
			}

			return 0;
		}

		/**
		 * Process email as ticket if it is related to ticket conversation and add ticket for that email.
		 *
		 * @param        $title
		 * @param        $body
		 * @param        $fromemail
		 * @param        $mailtime
		 * @param        $allemails
		 * @param        $uploaded
		 * @param        $mailBodyText
		 * @param bool   $check_duplicate
		 * @param bool   $userid
		 * @param string $messageid
		 * @param string $inreplyto
		 * @param string $references
		 * @param array  $rt_all_emails
		 * @param bool   $systemEmail
		 *
		 * @param string $from_email
		 *
		 * @return bool
		 * @internal param $allemail
		 * @internal param array $subscriber
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function process_email_to_ticket(
			$title,
			$body,
			$fromemail,
			$mailtime,
			$allemails,
			$uploaded,
			$mailBodyText,
			$check_duplicate = true,
			$userid = false,
			$messageid = '',
			$inreplyto = '',
			$references = '',
			$rt_all_emails  = array(),
			$systemEmail = false,
			$from_email = '',
			$originalBody = ''
		) {
			//subscriber diff
			$rtCampUser = Rt_HD_Utils::get_hd_rtcamp_user();
			$hdUser     = array();
			foreach ( $rtCampUser as $rUser ) {
				$hdUser[ $rUser->user_email ] = $rUser->ID;
			}
			$subscriber = array();
			$allemail = array();
			foreach ( $allemails as $mail ) {
				if ( ! array_key_exists( $mail['address'], $hdUser ) ) {
					$allemail[]= $mail;
				} else {
					$subscriber[]= $hdUser[$mail['address']];
				}
			}

			global $rt_hd_contacts;

			if ( empty( $userid ) ) {
				$userid = $rt_hd_contacts->get_user_from_email( $fromemail['address'] );
			}
			//always true in mail cron  is use for importer
			if ( ! $check_duplicate ) {
				$success_flag = $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'] );

				error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

				if ( ! $success_flag ) {
					foreach ( $uploaded as $u ) {
						unlink( $u['file'] );
					}
				}

				return $success_flag;
			}
			//-----------------------------------------------------------------------------//
			global $threadPostId;
			$forwardFlag = false;
			if ( ! isset( $threadPostId ) ) {
				$forwardFlag = strpos( strtolower( $title ), 'fwd:' );
				if ( false === $forwardFlag ) {
					$forwardFlag = false;
				} else {
					$forwardFlag = true;
				}

				if ( false === strpos( strtolower( $title ), 're:' ) ) {
					$replyFlag = false;
				} else {
					$replyFlag = true;
				}

				$postid = $this->get_post_id_from_subject( $title );

				error_log("POST ID FOUND FROM MAIL SUBJECT\n\r");

				if ( ! $postid ) {
					//get postID from inreply to and refrence meta
					$postid = $this->get_post_id_from_mail_meta( $inreplyto, $references );
					error_log("POST ID FOUND FROM MAIL META\n\r");
				}

				//if we got post id from subject
			} else {
				$postid = $threadPostId;
			}
			error_log( "POST ID : ". var_export( $postid, true ) . "\r\n" );
			$dndEmails = array();

			if ( $postid && get_post( $postid ) != null ) {
				if ( $forwardFlag ) {
					$this->process_forward_email_data( $title, $body, $mailtime, $allemail, $mailBodyText, $dndEmails );
				}

				$success_flag = $this->insert_post_comment( $postid, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $rt_all_emails , $subscriber, $originalBody);

				error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

				if ( ! $success_flag ) {
					foreach ( $uploaded as $u ) {
						unlink( $u['file'] );
					}
				}

				return $success_flag;
			}
			//if subject is re to post title

			if ( $replyFlag ) {
				error_log("MAIL IS A REPLY / FORWARD OF PREVIOUS TICKET\n\r");
				$title       = str_replace( 'Re:', '', $title );
				$title       = str_replace( 're:', '', $title );
				$title       = trim( $title );
				$existPostId = $this->post_exists( $title );
				if ( ! isset( $fromemail['name'] ) ) {
					$fromemail['name'] = $fromemail['address'];
				}
				//if given post title exits then it will be add as comment other wise as post
				if ( $existPostId ) {
					$success_flag = $this->insert_post_comment( $existPostId, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber, $originalBody );
					error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

					if ( ! $success_flag ) {
						foreach ( $uploaded as $u ) {
							unlink( $u['file'] );
						}
					}
					return $success_flag;
				} else {
					if ( $systemEmail ) {
						$success_flag = $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber, $originalBody );
						error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

						if ( ! $success_flag ) {
							foreach ( $uploaded as $u ) {
								unlink( $u['file'] );
							}
						}
						return $success_flag;
					}
				}
			} else {
				$existPostId = $this->post_exists( $title, $mailtime );
				//if given post title exits then it will be add as comment other wise as post
				error_log( "Post Exists : ". var_export( $existPostId, true ) . "\r\n" );
				if ( ! $existPostId ) {
					if ( $systemEmail ) {
						$success_flag = $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber, $originalBody );
						error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

						if ( ! $success_flag ) {
							foreach ( $uploaded as $u ) {
								unlink( $u['file'] );
							}
						}
						return $success_flag;
					}
				} else {
					$success_flag = $this->insert_post_comment( $existPostId, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber, $originalBody );
					error_log( 'Mail Parse Status : ' . var_export( $success_flag, true ) . "\n\r" );

					if ( ! $success_flag ) {
						foreach ( $uploaded as $u ) {
							unlink( $u['file'] );
						}
					}
					return $success_flag;
				}
			}

			return false;
		}

		/**
		 * Get post id from email meta
		 *
		 * @param $inreplyto
		 * @param $refrences
		 *
		 * @return bool / id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_post_id_from_mail_meta( $inreplyto, $refrences ) {
			if ( $inreplyto == '' && $refrences == '' ) {
				return false;
			}
			global $wpdb;
			$sql1       = "select post_id as id ,'post' as type from {$wpdb->postmeta} where ";
			$sql2       = "select comment_id as id, 'comment' as type from {$wpdb->commentmeta} where ";
			$operatorOr = '';
			if ( $inreplyto != '' ) {
				$sql1 .= " ({$wpdb->postmeta}.meta_key in('_messageid','_references','_inreplyto') and {$wpdb->postmeta}.meta_value like '%{$inreplyto}%' ) ";
				$sql2 .= " ({$wpdb->commentmeta}.meta_key in('_messageid','_references','_inreplyto') and {$wpdb->commentmeta}.meta_value like '%{$inreplyto}%' ) ";
				$operatorOr = ' or ';
			}

			$sql = $sql1 . ' union ' . $sql2;
			// echo '\r\n' . $sql . '\r\n';
			$result = $wpdb->get_results( $sql );
			if ( ! empty( $result ) ) {
				$row = $result[0];
				if ( 'post' == $row->type ) {
					return $row->id;
				} else {
					$comment = get_comment( $row->id );

					return $comment->comment_post_ID;
				}
			} else {
				return false;
			}
		}

		/**
		 * process forward email data
		 *
		 * @param $title
		 * @param $body
		 * @param $mailtime
		 * @param $allemail
		 * @param $mailBodyText
		 * @param $dndEmails
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function process_forward_email_data( &$title, &$body, &$mailtime, &$allemail, &$mailBodyText, &$dndEmails ) {
			$forwardArray = explode( "\r", $mailBodyText );
			$dndEmails    = array();
			foreach ( $forwardArray as $forwardline ) {
				$forwardline = strtolower( $forwardline );
				if ( ! ( false === strpos( $forwardline, 'to:' ) ) ) {
					$dndEmails = array_merge( $dndEmails, $this->extract_all_email_from_string( $forwardline ) );
				}
				if ( ! ( false === strpos( $forwardline, 'cc:' ) ) ) {
					$dndEmails = array_merge( $dndEmails, $this->extract_all_email_from_string( $forwardline ) );
				}
				if ( ! ( false === strpos( $forwardline, 'data:' ) ) ) {
					$mailtime = trim( str_replace( 'date:', '', $forwardline ) );
				}
				if ( ! ( false === strpos( $forwardline, 'title:' ) ) ) {
					$title = trim( str_replace( 'title:', '', $forwardline ) );
				}
			}

			foreach ( $dndEmails as $dndEmail ) {
				$allemail[] = array( 'address' => $dndEmail, 'name' => $dndEmail );
			}
		}

		/**
		 * Get all email from string
		 *
		 * @param $string
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function extract_all_email_from_string( $string ) {
			$pattern  = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
			$intmatch = preg_match_all( $pattern, $string, $matches );
			if ( ! empty( $matches[0] ) ) {
				return $matches[0];
			}
		}

		/**
		 * check for post existence
		 *
		 * @param        $title
		 * @param string $date
		 *
		 * @return int
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function post_exists( $title, $date = '' ) {
			global $wpdb;
			if ( trim( $title ) == '' ) {
				return 0;
			}
			$post_title = stripslashes( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
			$post_date  = stripslashes( sanitize_post_field( 'post_date', $date, 0, 'db' ) );

			$query = "SELECT ID FROM $wpdb->posts WHERE 1=1 and post_type IN ('" . Rt_HD_Module::$post_type . "') ";
			$args  = array();

			if ( ! empty( $date ) && $date != '' ) {
				$d   = new DateTime( $date );
				$UTC = new DateTimeZone( 'UTC' );
				$d->setTimezone( $UTC );
				$timeStamp = $d->getTimestamp();
				$postDate  = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
				$sql       = "select * from $wpdb->comments where  comment_post_id=%d  and comment_date between subtime(%s,'00:10:00') and addtime(%s,'00:10:00')";
				$query .= " AND post_date between subtime(%s,'00:10:00') and addtime(%s,'00:10:00')";
				$args[] = $postDate;
				$args[] = $postDate;
			}

			if ( ! empty( $title ) && trim( $title ) != '' ) {
				$query .= ' AND post_title = %s';
				$args[] = $post_title;
			}

			if ( ! empty( $args ) ) {
				return $wpdb->get_var( $wpdb->prepare( $query, $args ) );
			}

			return 0;
		}

		/**
		 * Add comment to post
		 *
		 * @param        $comment_post_ID
		 * @param        $userid
		 * @param        $comment_content
		 * @param        $comment_author
		 * @param        $comment_author_email
		 * @param        $commenttime
		 * @param        $uploaded
		 * @param        $allemails
		 * @param        $dndEmails
		 * @param string $messageid
		 * @param string $inreplyto
		 * @param string $references
		 * @param array  $subscriber
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function insert_post_comment( $comment_post_ID, $userid, $comment_content, $comment_author, $comment_author_email, $commenttime, $uploaded, $allemails, $dndEmails, $messageid = '', $inreplyto = '', $references = '', $rt_all_emails = array(), $subscriber = array(), $originalBody = '' ) {

			$post_type       = get_post_type( $comment_post_ID );
			$ticketModel     = new Rt_HD_Ticket_Model();
			$d               = new DateTime( $commenttime );
			$UTC             = new DateTimeZone( 'UTC' );
			$d->setTimezone( $UTC );
			$timeStamp      = $d->getTimestamp();
			$commentDate    = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
			$commentDateGmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
			global $signature;
			$comment_content_old = $comment_content;
			$comment_content     = str_replace( $signature, '', $comment_content );
			$data                = array(
				'comment_post_ID'      => $comment_post_ID,
				'comment_author'       => $comment_author,
				'comment_author_email' => $comment_author_email,
				'comment_author_url'   => 'http://',
				'comment_content'      => $comment_content,
				'comment_type'         => 'mail',
				'comment_parent'       => 0,
				'user_id'              => $userid,
				'comment_author_IP'    => '127.0.0.1',
				'comment_agent'        => ' ',
				'comment_date'         => $commentDate,
				'comment_date_gmt'     => $commentDateGmt,
				'comment_approved'     => 1,
			);
			if ( $this->check_duplicate_from_message_id( $messageid ) ) {
				return false;
			}
			$checkDupli = $this->check_duplicate_comment( $comment_post_ID, $commentDate, $comment_content, $comment_content_old );
			if ( false !== $checkDupli ) {
				if ( '' != $messageid ) {
					add_comment_meta( $checkDupli, '_messageid', $messageid );
				}
				if ( '' != $inreplyto ) {
					add_comment_meta( $checkDupli, '_inreplyto', $inreplyto );
				}
				if ( '' != $references ) {
					add_comment_meta( $checkDupli, '_references', $references );
				}

				return false;
			}

			$comment_id = wp_insert_comment( $data );
			if ( '' != $originalBody ) {
				add_comment_meta( $comment_id, 'rt_hd_original_email', $originalBody );
			}
			if ( '' != $messageid ) {
				add_comment_meta( $comment_id, '_messageid', $messageid );
			}
			if ( '' != $inreplyto ) {
				add_comment_meta( $comment_id, '_inreplyto', $inreplyto );
			}
			if ( '' != $references ) {
				add_comment_meta( $comment_id, '_references', $references );
			}

			$data  = array(
				'date_update'     => current_time( 'mysql' ),
				'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'user_updated_by' => $userid,
				'last_comment_id' => $comment_id,
			);
			$where = array(
				'post_id' => $comment_post_ID,
			);
			$ticketModel->update_ticket( $data, $where );
			/* System Notification -- Followup Added to the ticket */
			
			/* Toggle Ticket Status */
			global $rt_hd_email_notification;
			$post = get_post( $comment_post_ID );
			
			if ( $rt_hd_email_notification->is_internal_user( $comment_author_email ) ) {
				
				if ( $post->post_status != 'hd-answered' ){
					wp_update_post( array( 'ID'=>$comment_post_ID ,'post_status'=>'hd-answered') );
				}
			}
			else {
				if ( $post->post_status != 'hd-unanswered' ){
					wp_update_post( array( 'ID'=>$comment_post_ID ,'post_status'=>'hd-unanswered') );
				}
			}
			/* end of status toogle code */

			if ( isset( $rt_all_emails  ) ) {
				foreach ( $rt_all_emails  as $email ) {
					if ( isset( $email['key'] ) ) {
						$meta = get_comment_meta( $comment_id, '_email_' . $email['key'], true );
						if ( empty( $meta ) ) {
							update_comment_meta( $comment_id, '_email_' . $email['key'], $email['address'] );
						} else {
							update_comment_meta( $comment_id, '_email_' . $email['key'], $meta . ',' . $email['address'] );
						}
					}
				}
			}

			$subscribe_to = get_post_meta( $comment_post_ID, '_rtbiz_hd_subscribe_to', true );
			if ( $subscribe_to && is_array( $subscribe_to ) && sizeof( $subscribe_to ) > 0 ) {
				$subscriber = array_merge( $subscribe_to, $subscriber );
			}
			update_post_meta( $comment_post_ID, '_rtbiz_hd_subscribe_to', $subscriber );

			$this->add_attachment_to_post( $uploaded, $comment_post_ID, $comment_id );

			$this->add_contacts_to_post( $allemails, $comment_post_ID );

			global $threadPostId;
			if ( ! isset( $threadPostId ) ) {
				$title = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );;
				//				$body = '<span style="color:#777">< ! ------------------ REPLY ABOVE THIS LINE ------------------ ! ></span><br />';
				//				$body  = '<br/><strong>New Followup Added by ' . $comment_author . ' - ' . $comment_author_email . ':</strong>';
				$user = get_user_by( 'email', $comment_author_email );
				$contactFlag = true;
				if ( $user->ID == get_post_meta( $comment_post_ID, '_rtbiz_hd_created_by', true ) ){
					$creatorBody = '<br/>New Followup Added by <strong>You</strong>';
					$creatorBody .=  rthd_content_filter( $comment_content );
					$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $creatorBody ), wp_list_pluck( $uploaded, 'url' ), $comment_id, false, true, false, false );
					$contactFlag = false;
				}
				$body  = '<br/>New Followup Added by <strong>' . $comment_author .'</strong>';
				$body .=  rthd_content_filter( $comment_content );
				$body .= '<br/> ';
				$notificationFlag = $this->check_setting_for_new_followup_email();
				$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $body ), wp_list_pluck( $uploaded, 'url' ), $comment_id, $notificationFlag, $contactFlag );
			}

			return true;
		}

		/**
		 * Check for duplicate comment
		 *
		 * @param $comment_post_ID
		 * @param $comment_date
		 * @param $comment_content
		 * @param $comment_content_old
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function check_duplicate_comment( $comment_post_ID, $comment_date, $comment_content, $comment_content_old ) {
			global $wpdb;
			$sql    = "select * from $wpdb->comments where  comment_post_id=%d  and comment_date between subtime(%s,'00:10:00') and addtime(%s,'00:10:00')";
			$sql    = $wpdb->prepare( $sql, $comment_post_ID, $comment_date, $comment_date );
			$result = $wpdb->get_results( $sql );
			global $signature;
			foreach ( $result as $row ) {
				if ( trim( strip_tags( $comment_content ) ) == trim( strip_tags( $row->comment_content ) ) ) {
					return $row->comment_ID;
				} else {
					$tempStr = str_replace( trim( strip_tags( $signature ) ), '', trim( strip_tags( $comment_content_old ) ) );
					if ( trim( strip_tags( $tempStr ) ) == trim( strip_tags( $row->comment_content ) ) ) {
						return $row->comment_ID;
					}
				}
			}

			return false;
		}

		/**
		 * Check duplicate message from message ID
		 *
		 * @param $messageid
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function check_duplicate_from_message_id( $messageid ) {
			global $wpdb;
			if ( $messageid && trim( $messageid ) == '' ) {
				return false;
			}

			$sql    = $wpdb->prepare( "select meta_value from $wpdb->commentmeta where $wpdb->commentmeta.meta_key = '_messageid' and $wpdb->commentmeta.meta_value = %s", $messageid );
			$result = $wpdb->get_results( $sql );
			if ( empty( $result ) ) {

				$sql    = $wpdb->prepare( "select meta_value from $wpdb->postmeta where $wpdb->postmeta.meta_key = '_messageid' and $wpdb->postmeta.meta_value = %s", $messageid );
				$result = $wpdb->get_results( $sql );

				return ! empty( $result );
			} else {
				return ! empty( $result );
			}
		}

		/**
		 * Send new mail as someone post new comment
		 *
		 * @param $comment_id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function mail_new_comment_data( $comment_id ) {
			if ( ! $this->check_setting_for_new_followup_email( ) ) {
				return false;
			}
			if ( isset( $_REQUEST['commentSendAttachment'] ) && $_REQUEST['commentSendAttachment'] != '' ) {
				$arrAttache = explode( ',', $_REQUEST['commentSendAttachment'] );
				foreach ( $arrAttache as $strAttach ) {
					add_comment_meta( $comment_id, 'attachment', wp_get_attachment_url( intval( $strAttach ) ) );
				}
			}
			$this->mail_comment_data( $comment_id, array(), '', array() );
		}

		/**
		 *  mail comment data
		 *
		 * @param       $comment_id
		 * @param       $uploaded
		 * @param       $senderemail
		 * @param       $allemails
		 * @param array $dndEmails
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function mail_comment_data( $comment_id, $uploaded, $senderemail, $allemails, $dndEmails = array() ) {

			$comment         = get_comment( $comment_id );
			$comment_post_ID = $comment->comment_post_ID;

			if ( get_post_type( $comment_post_ID ) != Rt_HD_Module::$post_type ) {
				return true;
			}

			$subject = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );

			$mailbody = rthd_content_filter( $comment->comment_content );

			//commentSendAttachment
			$attachment = array();
			if ( isset( $_REQUEST['commentSendAttachment'] ) && $_REQUEST['commentSendAttachment'] != '' ) {
				$arrAttache = explode( ',', $_REQUEST['commentSendAttachment'] );
				foreach ( $arrAttache as $strAttach ) {
					$attachfile = get_attached_file( intval( $strAttach ) );
					if ( $attachfile ) {
						$attachment[] = $attachfile;
					}
				}
			} else {
				$_REQUEST['commentSendAttachment'] = '';
			}

			if ( isset( $uploaded ) && is_array( $uploaded ) && ! empty( $uploaded ) ) {
				foreach ( $uploaded as $upload ) {
					$attachment[] = $upload['file'];
				}
			}

			$toEmail = array();
			if ( isset( $_POST['helpdesk-email-to'] ) ) {
				foreach ( $_POST['helpdesk-email-to'] as $key => $email ) {
					$toEmail[] = array( 'email' => $email, 'name' => '' );
				}
			}
			$ccEmail = array();
			if ( isset( $_POST['helpdesk-email-cc'] ) ) {
				foreach ( $_POST['helpdesk-email-cc'] as $key => $email ) {
					$ccEmail[] = array( 'email' => $email, 'name' => '' );
				}
			}
			$bccEmail = array();
			if ( isset( $_POST['helpdesk-email-bcc'] ) ) {
				foreach ( $_POST['helpdesk-email-bcc'] as $key => $email ) {
					$bccEmail[] = array( 'email' => $email, 'name' => '' );
				}
			}

			if ( isset( $_POST['comment-reply-from'] ) ) {
				$fromemail = $_POST['comment-reply-from'];
				//to set default email
			}
			if ( empty( $toEmail ) && empty( $ccEmail ) && empty( $bccEmail ) ) {
				return false;
			}

			global $rt_hd_email_notification;
			return $rt_hd_email_notification->insert_new_send_email( $subject, '', rthd_get_general_body_template( $mailbody ), $toEmail, $ccEmail, $bccEmail, $attachment, $comment_id, 'comment' );
		}

		/**
		 * Get mine type from extension of file
		 *
		 * @param $strFileType
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_mime_type_from_extn( $strFileType ) {
			$ContentType = 'application/octet-stream';
			$strFileType = strtolower( '.' . $strFileType );
			if ( '.asf' == $strFileType ) {
				$ContentType = 'video/x-ms-asf';
			}
			if ( '.avi' == $strFileType ) {
				$ContentType = 'video/avi';
			}
			if ( '.doc' == $strFileType ) {
				$ContentType = 'application/msword';
			}
			if ( '.zip' == $strFileType ) {
				$ContentType = 'application/zip';
			}
			if ( '.xls' == $strFileType ) {
				$ContentType = 'application/vnd.ms-excel';
			}
			if ( '.gif' == $strFileType ) {
				$ContentType = 'image/gif';
			}
			if ( '.jpg' == $strFileType || 'jpeg' == $strFileType ) {
				$ContentType = 'image/jpeg';
			}
			if ( '.wav' == $strFileType ) {
				$ContentType = 'audio/wav';
			}
			if ( '.mp3' == $strFileType ) {
				$ContentType = 'audio/mpeg3';
			}
			if ( '.mpg' == $strFileType || 'mpeg' == $strFileType ) {
				$ContentType = 'video/mpeg';
			}
			if ( '.rtf' == $strFileType ) {
				$ContentType = 'application/rtf';
			}
			if ( '.htm' == $strFileType || 'html' == $strFileType ) {
				$ContentType = 'text/html';
			}
			if ( '.xml' == $strFileType ) {
				$ContentType = 'text/xml';
			}
			if ( '.xsl' == $strFileType ) {
				$ContentType = 'text/xsl';
			}
			if ( '.css' == $strFileType ) {
				$ContentType = 'text/css';
			}
			if ( '.php' == $strFileType ) {
				$ContentType = 'text/php';
			}
			if ( '.asp' == $strFileType ) {
				$ContentType = 'text/asp';
			}
			if ( '.pdf' == $strFileType ) {
				$ContentType = 'application/pdf';
			}

			return $ContentType;
		}


        function process_file_attachment($file){
            if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) __return_false();
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
            $attachment_id = media_handle_upload($file, "");
            return $attachment_id;
        }

		/**
		 * Add follow up of tickets on front end
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function add_new_followup_front() {
			if ( ! isset( $_POST['followuptype'] ) ) {
				wp_die( 'Invalid Request' );
			}
			if ( ! isset( $_POST['followup_ticket_unique_id'] ) ) {
				wp_die( 'Invalid Ticket' );
			}
			$returnArray     = array();
			if ( ! is_user_logged_in() ){
				$returnArray['status']  = false;
				$returnArray['message'] = 'ERROR: please login to continue.';
				echo json_encode( $returnArray );
				die( 0 );
			}
			$ticket_unique_id = $_POST['followup_ticket_unique_id'];
			$args             = array(
				'meta_key'    => '_rtbiz_hd_unique_id',
				'meta_value'  => $ticket_unique_id,
				'post_status' => 'any',
				'post_type'   => Rt_HD_Module::$post_type,
			);
			$ticketpost       = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				wp_die( 'Invalid Ticket' );
			}
			$rthd_ticket = $ticketpost[0];

			$followuptype    = $_POST['followuptype'];
			$comment_post_ID = $rthd_ticket->ID;
			$post_type       = $rthd_ticket->post_type;

			$comment_content = rthd_content_filter( $_POST['followup_content'] );
			$comment_privacy = $_POST['private_comment'];
			global $wpdb;
			$user = wp_get_current_user();
			if ( $user->exists() ) {
				$user_id              = $user->ID;
				$comment_author       = esc_sql( $user->display_name );
				$comment_author_email = esc_sql( $user->user_email );
				$comment_author_url   = esc_sql( $user->user_url );
			}
			if ( '' == $comment_content ) {
				$returnArray['status']  = false;
				$returnArray['message'] = 'ERROR: please type a comment.';
				echo json_encode( $returnArray );
				die( 0 );
			}

			$comment_type                   = $followuptype;
			$comment_parent                 = 0; //absint($_POST['comment_ID']);
			$comment_auto_approved          = true;
			$commentdata                    = compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_id' );
			$commentdata                    = apply_filters( 'preprocess_comment', $commentdata );
			$commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];
			if ( isset( $commentdata['user_ID'] ) ) {
				$commentdata['user_id'] = $commentdata['user_ID'] = (int) $commentdata['user_ID'];
			} elseif ( isset( $commentdata['user_id'] ) ) {
				$commentdata['user_id'] = (int) $commentdata['user_id'];
			}
			$commentdata['comment_parent']    = isset( $commentdata['comment_parent'] ) ? absint( $commentdata['comment_parent'] ) : 0;
			$parent_status                    = ( 0 < $commentdata['comment_parent'] ) ? wp_get_comment_status( $commentdata['comment_parent'] ) : '';
			$commentdata['comment_parent']    = ( 'approved' == $parent_status || 'unapproved' == $parent_status ) ? $commentdata['comment_parent'] : 0;
			$commentdata['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$commentdata['comment_agent']     = substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 );
			if ( isset( $_REQUEST['follwoup-time'] ) && $_REQUEST['follwoup-time'] != '' ) {
				$d                           = new DateTime( $_REQUEST['follwoup-time'] );
				$commentdata['comment_date'] = $d->format( 'Y-m-d H:i:s' );
				$commentdata['comment_date_gmt'] = $d->format( 'Y-m-d H:i:s' );
			} else {
				$commentdata['comment_date']     = current_time( 'mysql' );
				$commentdata['comment_date_gmt'] = current_time( 'mysql', 1 );
			}
			$commentdata['comment_approved'] = 1;
            $commentdata['comment_type']=$comment_privacy;
			$comment_ID                      = wp_insert_comment( $commentdata );

//			update_comment_meta( $comment_ID, '_rthd_privacy', $comment_privacy );

			$comment    = get_comment( $comment_ID );
			$attachment = array();
			$uploaded   = array();

			//then loop over the files that were sent and store them using  media_handle_upload();
			//			var_dump($_FILES);
			$attachment_IDs=array();
            if ( ! empty($_FILES['attachemntlist']['name'])) {
                $files = $_FILES['attachemntlist'];
                foreach ($files['name'] as $key => $value) {
                    if ($files['name'][$key]) {
                        $file = array(
                            'name' => $files['name'][$key],
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key]
                        );
                        $_FILES = array("attachemntlist" => $file);
                        foreach ($_FILES as $file => $array) {
                            $attach_id = $this->process_file_attachment($file);
                            array_push($attachment_IDs, $attach_id);
                        }
                    }
                }
            }
            if ( isset( $attachment_IDs ) && ! empty( $attachment_IDs ) ) {
				foreach ( $attachment_IDs as $strAttach ) {
					$attachfile = get_attached_file( intval( $strAttach ) );
					if ( $attachfile ) {
						$attachment[] = wp_get_attachment_url( intval( $strAttach ) );
						$extn_array = explode( '.', $attachfile );
						$extn       = $extn_array[ count( $extn_array ) - 1 ];
						$file_array = explode( '/', $attachfile );
						$fileName   = $file_array[ count( $file_array ) - 1 ];
						$uploaded[] = array(
							'filename'      => $fileName,
							'extn'          => $extn,
							'url'           => wp_get_attachment_url( intval( $strAttach ) ),
							'file'          => $attachfile,
						);
					}
				}

				$this->add_attachment_to_post( $uploaded, $comment_post_ID, $comment_ID );
			}
            /*
             *This code is for issue Issue #43
             * Toggle Ticket Status
             *
             */
            if ( isset($_POST['rthd_keep_status']) && !empty($_POST['rthd_keep_status'])){
                if ($_POST['rthd_keep_status']=='false'){
                    $returnArray['ticket_status']= rthd_toggle_status($comment_post_ID);
                }
            }
            else{
                $returnArray['ticket_status']= rthd_toggle_status($comment_post_ID);
            }
            /* end of status toogle code */

			$currentUser = get_user_by( 'id', get_current_user_id() );

			if ( ! empty( $currentUser->user_email ) ) {
				update_comment_meta( $comment_ID, '_email_from', $currentUser->user_email );
			}

			$title = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );;
			$contactFlag = ( $comment_privacy  == Rt_HD_Import_Operation::$FOLLOWUP_STAFF ) ? false : true;
			$hideAttachmentFlag = false;
			$creatorPrivate_flag = false;
			if ( isset( $comment_privacy ) && ! empty( $comment_privacy ) && intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ){
				$body = '<br /> A private followup has been added ' . ( ( ! empty( $currentUser->display_name ) ) ? 'by <strong>' . $currentUser->display_name : 'annonymously' ) .'<strong>. Please go to ticket to view content.';
				$hideAttachmentFlag = true;
				$creatorPrivate_flag = true;
			}else {
				$body = '<strong>New Followup Added ' . ( ( ! empty( $currentUser->display_name ) ) ? 'by ' . $currentUser->display_name : 'annonymously' ) . ':</strong>';
				$body .= rthd_content_filter( $comment->comment_content );
			}
			if ( get_current_user_id() == get_post_meta( $comment_post_ID, '_rtbiz_hd_created_by', true ) ) {
				$creatorbody = '';
				if ( ! $creatorPrivate_flag ){
					$creatorbody = '<br /> New follow up is added by <strong>you</strong>.';
					$creatorbody .= rthd_content_filter( $comment->comment_content );
				} else {
					$creatorbody = '<br /> A private followup has been added by <strong> you<strong>. Please go to ticket to view content.';
					$uploaded = array();
				}
				$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $creatorbody ), $uploaded, $comment_ID, false, true, false, false );
				$contactFlag = false;
			}
			$notificationFlag = $this->check_setting_for_new_followup_email();
			if ( $hideAttachmentFlag ){
				$uploaded = array();
			}
			$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $body ), $uploaded, $comment_ID, $notificationFlag, $contactFlag );

			$returnArray['status']        = true;

			$returnArray['comment_count'] = get_comments(
				array(
					'order'     => 'DESC',
					'post_id'   => $comment_post_ID,
					'post_type' => $post_type,
					'count'     => true,
				) );
			$returnArray['comment_id'] = $comment_ID;
			$returnArray['private']       = $comment_privacy;
			$comment_user  = get_user_by( 'id', $comment->user_id );
			$comment_render_type = 'left';
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			if ( ! empty( $comment_user ) ) {
				if ( $comment_user->has_cap( $cap ) ) {
					$comment_render_type = 'right';
				}
			}
			$user_edit = current_user_can( $cap ) || ( get_current_user_id() == $comment->user_id ) || ( get_current_user_id() == get_post_meta( $comment_post_ID, '_rtbiz_hd_created_by' ,true ) );
			$returnArray['comment_content'] = rthd_render_comment( get_comment( $comment_ID ), $user_edit, $comment_render_type, false );
			echo json_encode( $returnArray );
			ob_end_flush();
			die( 0 );
		}

		/**
		 * add new followup on AJAX
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function add_new_followup_ajax() {
			if ( ! isset( $_POST['followuptype'] ) ) {
				wp_die( 'Invalid Request' );
			}
			if ( ! ( isset( $_POST['followup_post_id'] ) && get_post_type( intval( $_POST['followup_post_id'] ) ) == $_POST['post_type'] ) ) {
				wp_die( 'Invalid Post ID' );
			}
			$returnArray     = array();
			if ( ! is_user_logged_in() ){
				$returnArray['status']  = false;
				$returnArray['message'] = 'ERROR: please login to continue.';
				echo json_encode( $returnArray );
				die( 0 );
			}
			$followuptype    = $_POST['followuptype'];
			$comment_post_ID = $_POST['followup_post_id'];
			$post_type       = get_post_type( $comment_post_ID );
			$comment_content = rthd_content_filter( $_POST['followup_content'] );
			$comment_privacy = $_POST['followup_private'];
			global $wpdb;
			$user = wp_get_current_user();
			if ( $user->exists() ) {
				$user_id              = $user->ID;
				$comment_author       = esc_sql( $user->display_name );
				$comment_author_email = esc_sql( $user->user_email );
				$comment_author_url   = esc_sql( $user->user_url );
			}
			if ( '' == $comment_content ) {
				$returnArray['status']  = false;
				$returnArray['message'] = 'ERROR: please type a comment.';
				echo json_encode( $returnArray );
				die( 0 );
			}

			if ( isset( $_POST['comment_id'] ) && intval( $_POST['comment_id'] ) > 0 ) {
				$commentdata = get_comment( $_POST['comment_id'], ARRAY_A );
				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				$user_edit = current_user_can( $cap ) || (get_current_user_id() == $commentdata['user_id'] );
				if ( !$user_edit ) {
					$returnArray['status']  = false;
					$returnArray['message'] = 'Unauthorized Access';
					echo json_encode( $returnArray );
					die( 0 );
				}
				$oldCommentBody = $commentdata['comment_content'];

				$commentdata['comment_content'] = $comment_content;
				$oldDate                        = $commentdata['comment_date'];
				$newDate                        = '';
				if ( isset( $_REQUEST['follwoup-time'] ) && $_REQUEST['follwoup-time'] != '' ) {
					$d                           = new DateTime( $_REQUEST['follwoup-time'] );
					$UTC                         = new DateTimeZone( 'UTC' );
					$commentdata['comment_date'] = $d->format( 'Y-m-d H:i:s' );
					$newDate                     = $commentdata['comment_date'];
					$d->setTimezone( $UTC );
					$commentdata['comment_date_gmt'] = $d->format( 'Y-m-d H:i:s' );
				}
				else {
					$newDate = current_time( 'mysql', 1 );
				}
                $old_privacy=  $commentdata['comment_type'];
                $commentdata['comment_type']= $comment_privacy;
                // update_comment_meta( $_POST['comment_id'], '_rthd_privacy', $comment_privacy );

				wp_update_comment( $commentdata );

				//todo: remove below line when comment wordpress start supporting comment_type edit
				rthd_edit_comment_type( $commentdata['comment_ID'], $comment_privacy );
				$uploaded = array();
				$attachment = array();
				$comment = get_comment( $_POST['comment_id'] );
				if ( isset( $_REQUEST['attachemntlist'] ) && ! empty( $_REQUEST['attachemntlist'] ) ) {
					delete_comment_meta( $_POST['comment_id'], 'attachment' );
					foreach ( $_REQUEST['attachemntlist'] as $strAttach ) {
						$attachfile = get_attached_file( intval( $strAttach ) );
						if ( $attachfile ) {
							$attachment[] = $attachfile;
							$extn_array   = explode( '.', $attachfile );
							$extn         = $extn_array[ count( $extn_array ) - 1 ];
							$file_array   = explode( '/', $attachfile );
							$fileName     = $file_array[ count( $file_array ) - 1 ];
							$uploaded[]   = array(
								'filename'      => $fileName,
								'extn'          => $extn,
								'url'           => wp_get_attachment_url( intval( $strAttach ) ),
								'file'          => $attachfile,
							);
						}
					}
					$this->add_attachment_to_post( $uploaded, $comment_post_ID, $_POST['comment_id'] );
				}

				$currentUser = get_user_by( 'id', get_current_user_id() );
				$subject       = rthd_create_new_ticket_title( 'rthd_update_followup_email_title', $comment_post_ID );

				$updatedbybody = '<div> A Follwup Updated by ' . $currentUser->display_name. '</div> <br/>';
				$creatorbody = '<div> A follow is updated by you</div> <br />';
				$body = '<div> The changes are as follows: </div><br/>';

				$flag = false;

				$old_privacy_text = rthd_get_comment_type($old_privacy);
				$new_privacy = rthd_get_comment_type($comment_privacy);
				if ( intval( $old_privacy ) && $old_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ){
					$old_privacy = 'true';
				}
				if ( intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ){
					$comment_privacy = 'true';
				}
				$body_template = '';
				$diff = rthd_text_diff( $old_privacy_text, $new_privacy );
				$diff_content = rthd_text_diff( trim( html_entity_decode( strip_tags( $oldCommentBody ) ) ), trim( html_entity_decode( strip_tags( $commentdata[ 'comment_content' ] ) ) ) );
				if ( $diff || $diff_content ) {
					if ( $diff ) {
						$body_template .= '<br/><b>Visibility : </b>' . $diff;
					}
					$flag = true;
				}
				if ( 'true' == $old_privacy || 'true' == $comment_privacy ){
					$body_template .= '<br /> A <strong>private</strong> followup has been edited. Please go to link and login to view the message.';
				}
				else {
					if ( $diff || $diff_content ) {
						$flag = true;
						$body_template .= '<br/><b>Followup Content : </b>' . $diff_content;
					} else {
						$body_template .= '<br/><b>Followup Content : </b>' . rthd_content_filter( $comment->comment_content );
					}
					$body_template .= '<br/> ';
				}
				if ( $flag ) {
					if ( get_current_user_id() == get_post_meta( $comment_post_ID, '_rtbiz_hd_created_by', true ) ) {
						$contactbody = $creatorbody . $body. $body_template;
						$this->notify_subscriber_via_email( $comment_post_ID, $subject, rthd_get_general_body_template( $contactbody ), $attachment, $_POST['comment_id'],false, true, false, false );
					}
					$redux = rthd_get_redux_settings();
					$notificationFlag = ( $redux['rthd_notification_events']['followup_edited'] == 1 );
					$body = $updatedbybody . $body. rthd_get_general_body_template( $body_template );
					$this->notify_subscriber_via_email( $comment_post_ID, $subject, $body, $attachment, $_POST['comment_id'],$notificationFlag, false );
				}

				$returnArray['status']        = true;
				//				$returnArray['comment_count'] = get_comments(
				//					array(
				//						'order'     => 'DESC',
				//						'post_id'   => $comment_post_ID,
				//						'post_type' => $post_type,
				//						'count'     => true,
				//					) );
				$returnArray['private']       = $comment->comment_type;
				//				$returnArray['comment_content'] = wpautop( make_clickable( $comment->comment_content ) );
				$comment_render_type = 'left';
				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				if ( ! empty( $comment_user ) ) {
					if ( $comment_user->has_cap( $cap ) ) {
						$comment_render_type = 'right';
					}
				}
				clean_comment_cache( $comment->comment_ID  );
				$user_edit = current_user_can( $cap ) || ( get_current_user_id() == $comment->user_id ) || ( get_current_user_id() == get_post_meta( $comment_post_ID, '_rtbiz_hd_created_by' ,true ) );
				$returnArray['comment_content'] = rthd_render_comment( get_comment( $comment->comment_ID ), $user_edit, $comment_render_type, false );
				echo json_encode( $returnArray );
				die( 0 );
			}

			$comment_type                   = $followuptype;
			$comment_parent                 = 0; //absint($_POST['comment_ID']);
			$comment_auto_approved          = true;
			$commentdata                    = compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_id' );
			$commentdata                    = apply_filters( 'preprocess_comment', $commentdata );
			$commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];
			if ( isset( $commentdata['user_ID'] ) ) {
				$commentdata['user_id'] = $commentdata['user_ID'] = (int) $commentdata['user_ID'];
			} elseif ( isset( $commentdata['user_id'] ) ) {
				$commentdata['user_id'] = (int) $commentdata['user_id'];
			}
			$commentdata['comment_parent']    = isset( $commentdata['comment_parent'] ) ? absint( $commentdata['comment_parent'] ) : 0;
			$parent_status                    = ( 0 < $commentdata['comment_parent'] ) ? wp_get_comment_status( $commentdata['comment_parent'] ) : '';
			$commentdata['comment_parent']    = ( 'approved' == $parent_status || 'unapproved' == $parent_status ) ? $commentdata['comment_parent'] : 0;
			$commentdata['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$commentdata['comment_agent']     = substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 );
			if ( isset( $_REQUEST['follwoup-time'] ) && $_REQUEST['follwoup-time'] != '' ) {
				$d                           = new DateTime( $_REQUEST['follwoup-time'] );
				$UTC                         = new DateTimeZone( 'UTC' );
				$commentdata['comment_date'] = $d->format( 'Y-m-d H:i:s' );
				$d->setTimezone( $UTC );
				$commentdata['comment_date_gmt'] = $d->format( 'Y-m-d H:i:s' );
			} else {
				$commentdata['comment_date']     = current_time( 'mysql' );
				$commentdata['comment_date_gmt'] = current_time( 'mysql', 1 );
			}
			$commentdata['comment_approved'] = 1;
            $commentdata['comment_type']= $comment_privacy;
            $comment_ID                      = wp_insert_comment( $commentdata );

			//			update_comment_meta( $comment_ID, '_rthd_privacy', $comment_privacy );

			$comment = get_comment( $comment_ID );

			$attachment = array();
			$uploaded   = array();
			if ( isset( $_REQUEST['attachemntlist'] ) && ! empty( $_REQUEST['attachemntlist'] ) ) {
				foreach ( $_REQUEST['attachemntlist'] as $strAttach ) {
					$attachfile = get_attached_file( intval( $strAttach ) );
					if ( $attachfile ) {
						$attachment[] = $attachfile;
						$extn_array = explode( '.', $attachfile );
						$extn       = $extn_array[ count( $extn_array ) - 1 ];
						$file_array = explode( '/', $attachfile );
						$fileName   = $file_array[ count( $file_array ) - 1 ];
						$uploaded[] = array(
							'filename'      => $fileName,
							'extn'          => $extn,
							'url'           => wp_get_attachment_url( intval( $strAttach ) ),
							'file'          => $attachfile,
						);
					}
				}
				$this->add_attachment_to_post( $uploaded, $comment_post_ID, $comment_ID );
			}
			$to  = '';
			$bcc = '';
			$cc  = '';

			if ( 'mail' == $followuptype ) {

				$subject = $this->create_title_for_mail( $comment_post_ID );

				$mailbody = str_replace( '&nbsp;', ' ', $comment->comment_content );

				//commentSendAttachment

				$toEmail = array();
				if ( isset( $_POST['comment-reply-to'] ) ) {
					$sep = '';
					foreach ( $_POST['comment-reply-to'] as $email ) {
						$toEmail[] = array( 'email' => $email, 'name' => '' );
						$to .= $sep . $email;
						$sep = ',';
					}
				}
				$ccEmail = array();
				if ( isset( $_POST['comment-reply-cc'] ) ) {
					$sep = '';
					foreach ( $_POST['comment-reply-cc'] as $email ) {
						$ccEmail[] = array( 'email' => $email, 'name' => '' );
						$cc .= $sep . $email;
						$sep = ',';
					}
				}
				$bccEmail = array();
				if ( isset( $_POST['comment-reply-bcc'] ) ) {
					$sep = '';
					foreach ( $_POST['comment-reply-bcc'] as $email ) {
						$bccEmail[] = array( 'email' => $email, 'name' => '' );
						$bcc .= $sep . $email;
						$sep = ',';
					}
				}

				if ( isset( $_POST['comment-reply-from'] ) ) {
					$fromemail = $_POST['comment-reply-from'];
					//to set default email
				}
				if ( empty( $toEmail ) && empty( $ccEmail ) && empty( $bccEmail ) ) {
					return true;
				}

				if ( ! isset( $_REQUEST['commentSendAttachment'] ) ) {
					$_REQUEST['commentSendAttachment'] = array();
				}

				if ( ! $this->is_allow_to_sendemail_fromemail( $fromemail ) ) {
					return false;
				}
				$signature   = '';
				$email_type  = '';
				$imap_server = '';
				global $rt_hd_email_notification;
				if ( ! $this->check_setting_for_new_followup_email( ) ) {
					return false;
				}

				if ( false == strpos( $signature, '</' ) ) {
					$signature = htmlentities( $signature );
					$signature = preg_replace( '/(\n|\r|\r\n)/i', '<br />\n', $signature );
					$signature = preg_replace( '/  /i', '  ', $signature );
				}
				$mailbody .= '<br />' . $signature;
				$rt_hd_email_notification->insert_new_send_email( $subject, '', rthd_get_general_body_template( $mailbody ), $toEmail, $ccEmail, $bccEmail, $attachment, $comment_ID, 'comment' );
			}

			update_comment_meta( $comment_ID, '_email_from', $_POST['comment-reply-from'] );
			update_comment_meta( $comment_ID, '_email_to', $to );
			update_comment_meta( $comment_ID, '_email_cc', $cc );
			update_comment_meta( $comment_ID, '_email_bcc', $bcc );

			$currentUser = get_user_by( 'id', get_current_user_id() );
			$title       = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );

			//			$body = '<span style="color:#777">< ! ------------------ REPLY ABOVE THIS LINE ------------------ ! ></span><br />';
			$body = '<br/><strong>New Followup Added by ' . $currentUser->display_name.':</strong><br />';
			if ( 'mail' == $followuptype ) {
				$body .= '<br/><b>From : </b>' . $_POST['comment-reply-from'];
				$body .= '<br/><b>To : </b>' . $to;
				$body .= '<br/><b>CC : </b>' . $cc;
				$body .= '<br/><b>BCC : </b>' . $bcc;
			}
			$contactFlag = (intval( $comment_privacy ) == Rt_HD_Import_Operation::$FOLLOWUP_STAFF)? false : true;

			if ( isset( $comment_privacy ) && ! empty( $comment_privacy ) && intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ){
				$body .= '<br /> A private followup has been added ' . ( ( ! empty( $currentUser->display_name ) ) ? 'by ' . $currentUser->display_name : 'annonymously' ) .'. Please go to link and login to view the message.';
			}
			else{
				$body .= '<br/>' . rthd_content_filter( $comment->comment_content );
			}

			$body .= '<br/> ';
			$notificationFlag = $this->check_setting_for_new_followup_email();
			$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $body ), $attachment, $comment_ID, $notificationFlag, $contactFlag );
			$returnArray['status'] = true;
			$returnArray['comment_id'] = $comment_ID;
			$returnArray['comment_count'] = get_comments( array(
															'order'     => 'DESC',
															'post_id'   => $comment_post_ID,
															'post_type' => $post_type,
															'count'     => true,
															) );
			$returnArray['private']       = $comment_privacy;

			echo json_encode( $returnArray );

			ob_end_flush();
			die( 0 );
		}

		/**
		 * check for if sending of email is Allowed from sendmail
		 *
		 * @param $email
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function is_allow_to_sendemail_fromemail( $email ) {
			$user_id = get_current_user_id();
			global $wpdb, $rt_mail_accounts_model, $rt_hd_mail_acl_model;
			$sql    = $wpdb->prepare( '(select * from {$rt_mail_accounts_model->table_name} where user_id=%d and email = %s)
                                union (select a.* from {$rt_hd_mail_acl_model->table_name} b inner join
                                {$rt_mail_accounts_model->table_name} a on a.email=b.email where b.allow_user=%d and a.email=%s)', $user_id, $email, $user_id, $email );
			$result = $wpdb->get_results( $sql );
			if ( $result && ! empty( $result ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * notify subscriber via email
		 *
		 * @param       $post_id
		 * @param       $subject
		 * @param       $body
		 * @param array $attachment
		 * @param       $comment_id
		 * @param       $notificationFlag
		 * @param       $contactFlag
		 *
		 * @param bool  $subscriberFlag
		 *
		 * @internal param $title
		 * @since rt-Helpdesk 0.1
		 */
		function notify_subscriber_via_email( $post_id, $subject, $body, $attachment = array(), $comment_id, $notificationFlag, $contactFlag, $subscriberFlag = true, $assigneeFlag = true ) {
			$bccemails     = array();
			$sendEmailFlag = false;
			if ( $subscriberFlag ) {
				$oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );
				if ( $oldSubscriberArr && is_array( $oldSubscriberArr ) && ! empty( $oldSubscriberArr ) ) {
					foreach ( $oldSubscriberArr as $emailsubscriber ) {
						$userSub = get_user_by( 'id', intval( $emailsubscriber ) );
						if ( ! empty( $userSub ) ) {
							$bccemails[ ]  = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
							$sendEmailFlag = true;
						}
					}
				}
			}
			global $redux_helpdesk_settings, $rt_hd_email_notification;

			if ( $contactFlag ) {
				$tocontact      = array();
				$contacts = rt_biz_get_post_for_contact_connection( $post_id, Rt_HD_Module::$post_type );
				foreach ( $contacts as $contact ) {
					global $rt_contact;
					$emails = get_post_meta( $contact->ID, Rt_Entity::$meta_key_prefix.$rt_contact->primary_email_key );
					foreach ( $emails as $email ) {
						array_push( $tocontact, array( 'email' => $email ) );
					}
				}
				global $rt_hd_module;
				$labels = $rt_hd_module->labels;

				$title     = $rt_hd_email_notification->get_email_title( $post_id, $labels['name'] );
				$rt_hd_email_notification->insert_new_send_email( $subject, $title, $body, $tocontact, array(), array(), $attachment, $comment_id, 'comment', true );
			}

			//			$cc = array();
			if ( $notificationFlag ) {
				if ( isset( $redux_helpdesk_settings['rthd_notification_emails'] ) ) {
					foreach ( $redux_helpdesk_settings['rthd_notification_emails'] as $email ) {
						array_push( $bccemails, array( 'email' => $email ) );
						$sendEmailFlag = true;
					}
				}
			}
			if ( $assigneeFlag ){
				$post_author_id = get_post_field( 'post_author', $post_id );
				$userSub     = get_user_by( 'id', intval( $post_author_id ) );
				$to[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
				$sendEmailFlag = true;
			}
			if ( $sendEmailFlag ){
				global $rt_hd_module;
				$labels = $rt_hd_module->labels;
				$title = $rt_hd_email_notification->get_email_title( $post_id, $labels['name'] );
				$rt_hd_email_notification->insert_new_send_email( $subject, $title, $body, $to, array(), $bccemails, $attachment, $comment_id, 'comment', true );
			}
		}

		/**
		 * remove follow up AJAX
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function delete_followup_ajax() {
			$response = array();
			if ( ! isset( $_POST['comment_id'] ) ) {
				die( 0 );
			}
			$comment          = get_comment( $_POST['comment_id'] );
			$attachments_urls = get_comment_meta( $_POST['comment_id'], 'attachment' );
			$attachments = get_children( array( 'post_parent' => $comment->comment_post_ID, 'post_type' => 'attachment', ) );
			if ( ! empty( $attachments ) && ! empty( $attachments_urls ) ){
				foreach ( $attachments as $att ){
					if ( in_array( wp_get_attachment_url( $att->ID ),$attachments_urls ) ){
						wp_delete_attachment( $att->ID, true );
					}
				}
			}
			$response['status'] = wp_delete_comment( $_POST['comment_id'], true );
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( $redux['rthd_notification_events']['followup_deleted'] == 1 );
			$currentUser      = get_user_by( 'id', get_current_user_id() );
			$body             = 'A Follwup is deleted by <b>' . $currentUser->display_name .'</b>';
			$body .= '<br/>'. $comment->comment_content ;
			$body .= '<br/> ';
			$comment_post_ID = $_POST['post_id'];

			$title           = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title', $comment_post_ID );
			$this->notify_subscriber_via_email( $comment_post_ID, $title, rthd_get_general_body_template( $body ), array(), $_POST['comment_id'], $notificationFlag, false, true, true );
			echo json_encode( $response );
			die( 0 );
		}

		/**
		 * Request import for gmail thread
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function gmail_thread_import_request() {
			$response = array();
			if ( ! isset( $_POST['post_id'] ) ) {
				$response['false']   = true;
				$response['message'] = 'Invalid Post ID';
				echo json_encode( $response );
				die( 0 );
			}

			if ( get_post_type( $_POST['post_id'] ) != Rt_HD_Module::$post_type ) {
				$response['false']   = true;
				$response['message'] = 'Invalid Post Type';
				echo json_encode( $response );
				die( 0 );
			}

			global $rt_mail_settings;
			$signature    = '';
			$email_type   = '';
			$imap_server  = '';
			$access_token = $rt_mail_settings->get_accesstoken_from_email( $_POST['email'], $signature, $email_type, $imap_server );

			if ( 'goauth' != $email_type ) {
				$response['false']   = true;
				$response['message'] = 'Email is registered with invalid type. Thread Importing is only allowed via Google Oauth Emails.';
				echo json_encode( $response );
				die( 0 );
			}

			$result = $this->insert_import_mail_thread_request( $_POST['email'], $_POST['thread_id'], $_POST['post_id'] );
			if ( ! empty( $result ) ) {
				$response['status'] = true;
			} else {
				$response['false']   = true;
				$response['message'] = 'Error in inserting request, Please try again';
			}
			echo json_encode( $response );
			die( 0 );
		}

		/**
		 * insert import mail thread request
		 *
		 * @param     $email
		 * @param     $threaid
		 * @param     $post_id
		 * @param int $userid
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function insert_import_mail_thread_request( $email, $threaid, $post_id, $userid = 0 ) {
			if ( 0 == $userid ) {
				$userid = get_current_user_id();
			}
			$args = array(
				'email'    => $email,
				'threadid' => $threaid,
				'post_id'  => $post_id,
				'user_id'  => $userid,
				'status'   => 'r',
			);
			global $rt_mail_thread_importer_model;
			$rows_affected = $rt_mail_thread_importer_model->add_thread( $args );

			return $rows_affected;
		}
		public function check_setting_for_new_followup_email(){
			$redux = rthd_get_redux_settings();
			if ( 1 != $redux['rthd_notification_events']['new_comment_added'] ){
				return false;
			}
			return true;
		}

		function load_more_followup(){
			$response = array();
			$response['status']= false;
			if (isset($_REQUEST['post_id']) && isset($_REQUEST['offset']) && isset($_REQUEST['limit'])  ){
				$postid=  $_REQUEST['post_id'];
				$offset=  $_REQUEST['offset'];
				$Limit=  $_REQUEST['limit'];
				//				if ( ! isset($_POST['getall']) && $_POST['all']!='true') {
				//					$offset = $offset + $Limit;
				//				}
			}
			else{
				echo json_encode($response);
				die();
			}

			$comments = get_comments( array(
	              'post_id' => $postid,
	              'status'  => 'approve',
	              'order'   => 'ASC',
	              'number' => $Limit,
	              'offset' => $offset,
	          ) );
//			$user_edit = current_user_can( rt_biz_get_access_role_cap( RT_BIZ_TEXT_DOMAIN, 'editor' ) );
			$commenthtml='';
			$count = 0;
			foreach ( $comments as $comment ) {
				$comment_user  = get_user_by( 'id', $comment->user_id );
				$comment_render_type = 'left';
				if ( ! empty( $comment_user ) ) {
					if ( $comment_user->has_cap( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ) {
						$comment_render_type = 'right';
					}
				}
				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				$user_edit = current_user_can( $cap ) || ( get_current_user_id() == $comment->user_id ) || ( get_current_user_id() == get_post_meta( $postid, '_rtbiz_hd_created_by' ,true ) );
				$commenthtml .= rthd_render_comment( $comment, $user_edit, $comment_render_type, false );
				$count++;
			}

			$placeholder = '';
			if ( ! ( $count < $Limit ) ) {
				$placeholder = '<div class="content-stream stream-loading js-loading-placeholder"><img id="load-more-hdspinner" class="js-loading-placeholder" src="' . admin_url() . 'images/spinner.gif' . '" /></div>';
			}

			$response['offset']= $offset;
			$response['comments']= $commenthtml;
			$response['placeholder'] = $placeholder;
			$response['status']= true;
			echo json_encode($response);
			die();
		}

		function front_end_status_change(){
			$response = array();
			$response['status']= false;
			$post_id = $_POST['post_id'];
			$old = get_post_status( $post_id );
			$post_status = $_POST['post_status'];
			if ( $post_id ){
				$ticket = array( 'ID' => $post_id,
				                      'post_status' => $post_status,);
				wp_update_post( $ticket );
			}
			$response['stauts_markup']= rthd_status_markup( $post_status );
			$response['status']= true;
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			rthd_update_ticket_updated_by_user( $post_id, get_current_user_id() );
			global $rt_hd_email_notification;
			$body = $labels['name'].' Status '.rthd_status_markup( $old ).' changed to '.rthd_status_markup( $post_status );
			$rt_hd_email_notification->notification_ticket_updated( $post_id, $labels['name'], $body, array() );
			echo json_encode($response);
			die();
		}

	}

}
