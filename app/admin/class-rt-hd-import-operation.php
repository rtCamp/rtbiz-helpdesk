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
		public function insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $senderEmail, $messageid = '', $inreplyto = '', $references = '', $subscriber = array() ) {
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
				'post_content'  => $body,
				'post_date'     => $post_date,
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_type'     => $post_type,
				'post_date_gmt' => $post_date_gmt,
			);

			$dataArray = array(
				'assignee'     => $postArray['post_author'],
				'post_content' => $postArray['post_content'],
				'post_status'  => $postArray['post_status'],
				'post_title'   => $postArray['post_title'],
			);

			$post_id = $rt_hd_tickets_operation->ticket_default_field_update( $postArray, $dataArray, $post_type, '', $userid, $userid );

			// Updating Post Status from publish to unanswered
			$rt_hd_tickets_operation->ticket_default_field_update( array( 'post_status' => 'hd-unanswered' ), array( 'post_status' => 'hd-unanswered' ), $post_type, $post_id, $userid, $userid );

			$rt_hd_ticket_history_model->insert(
				array(
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
				$rt_hd_email_notification->notification_new_ticket_assigned( $post_id, $settings['rthd_default_user'], $labels['name'], $allemail, $uploaded, $mail_parse = true );
			}

			$rt_hd_email_notification->ticket_created_notification( $post_id,$labels['name'], $body, $allemail, $uploaded );

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
						$attach_id  = wp_insert_attachment( $attachment );

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
				$term = rt_biz_get_person_by_email( $email['address'] );
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
						rt_biz_connect_post_to_person( $post_type, $post_id, $term );
					}

					// Update Index
					$ticketModel = new Rt_HD_Ticket_Model();
					$where       = array( 'post_id' => $post_id );
					$attr_name   = rt_biz_get_person_post_type();
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
		 * @param        $allemail
		 * @param        $uploaded
		 * @param        $mailBodyText
		 * @param bool   $check_duplicate
		 * @param bool   $userid
		 * @param string $messageid
		 * @param string $inreplyto
		 * @param string $references
		 * @param bool   $systemEmail
		 * @param array  $subscriber
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function process_email_to_ticket(
			$title,
			$body,
			$fromemail,
			$mailtime,
			$allemail,
			$uploaded,
			$mailBodyText,
			$check_duplicate = true,
			$userid = false,
			$messageid = '',
			$inreplyto = '',
			$references = '',
			$systemEmail = false,
			$subscriber = array()
		) {
			global $rt_hd_contacts;

			if ( empty( $userid ) ) {
				$userid = $rt_hd_contacts->get_user_from_email( $fromemail['address'] );
			}
			//always true in mail cron  is use for importer
			if ( ! $check_duplicate ) {
				return $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'] );
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

				return $this->insert_post_comment( $postid, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber );
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
					return $this->insert_post_comment( $existPostId, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber );
				} else {
					if ( $systemEmail ) {
						return $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber );
					}
				}
			} else {
				$existPostId = $this->post_exists( $title, $mailtime );
				//if given post title exits then it will be add as comment other wise as post
				error_log( "Post Exists : ". var_export( $existPostId, true ) . "\r\n" );
				if ( ! $existPostId ) {
					if ( $systemEmail ) {
						return $this->insert_new_ticket( $title, $body, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber );
					}
				} else {
					return $this->insert_post_comment( $existPostId, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber );
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
		public function insert_post_comment( $comment_post_ID, $userid, $comment_content, $comment_author, $comment_author_email, $commenttime, $uploaded, $allemails, $dndEmails, $messageid = '', $inreplyto = '', $references = '', $subscriber = array() ) {

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

			global $rthd_all_emails;
			if ( isset( $rthd_all_emails ) ) {
				foreach ( $rthd_all_emails as $email ) {
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
				$body = '<span style="color:#777">< ! ------------------ REPLY ABOVE THIS LINE ------------------ ! ></span><br />';
				$body  .= '<br/><strong>New Followup Added by ' . $comment_author . ' - ' . $comment_author_email . ':</strong>';
				$body .= '<br/><b>Type : </b>' . 'Mail';
				$body .= '<br/><b>From : </b>' . $comment_author_email;
				$body .= '<br/><b>Body : </b>' . $comment_content;
				$body .= '<br/> ';
				$notificationFlag = $this->check_setting_for_new_followup_email();
				$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, wp_list_pluck( $uploaded, 'url' ), $comment_id, $notificationFlag, true );
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

			$title = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );

			$mailbody = apply_filters( 'the_content', balanceTags( $comment->comment_content, true ) );

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
			return $rt_hd_email_notification->insert_new_send_email( $title, $mailbody, $toEmail, $ccEmail, $bccEmail, $attachment, $comment_id, 'comment' );
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

			$returnArray     = array();
			$followuptype    = $_POST['followuptype'];
			$comment_post_ID = $rthd_ticket->ID;
			$post_type       = $rthd_ticket->post_type;

			$comment_content = Rt_HD_Utils::force_utf_8( $_POST['followup_content'] );
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
			$comment_ID                      = wp_insert_comment( $commentdata );

			update_comment_meta( $comment_ID, '_rthd_privacy', $comment_privacy );

			$comment    = get_comment( $comment_ID );
			$attachment = array();
			$uploaded   = array();

			//then loop over the files that were sent and store them using  media_handle_upload();
			//			var_dump($_FILES);
			require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
			$attachment_IDs=array();
			if ( ! empty($_FILES['attachemntlist']['name'])) {
				foreach ( $_FILES as $file => $array ) {
					if ( $_FILES[ $file ]['error'] !== UPLOAD_ERR_OK ) {
						$returnArray['error']    = 'upload error : ' . $_FILES[ $file ]['error'];
						$returnArray['status'] = false;
						echo json_encode( $returnArray );
						ob_end_flush();
						die();
					}
					$attach_id = media_handle_upload( $file, "" );;
					array_push($attachment_IDs,$attach_id);
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

			$currentUser = get_user_by( 'id', get_current_user_id() );

			if ( ! empty( $currentUser->user_email ) ) {
				update_comment_meta( $comment_ID, '_email_from', $currentUser->user_email );
			}

			$title = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );;

			$body = '<span style="color:#777">< ! ------------------ REPLY ABOVE THIS LINE ------------------ ! ></span><br />';
			$body .= '<br /><strong>New Followup Added ' . ( ( ! empty( $currentUser->display_name ) ) ? 'by ' . $currentUser->display_name : 'annonymously' ) . ':</strong><br />';
			$body .= '<br/>' . apply_filters( 'the_content', $comment->comment_content );
			$body .= '<br/>';
			$notificationFlag = $this->check_setting_for_new_followup_email();
			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $attachment, $comment_ID, $notificationFlag, true );

			$returnArray['status']        = true;

			$returnArray['comment_count'] = get_comments(
				array(
					'order'     => 'DESC',
					'post_id'   => $comment_post_ID,
					'post_type' => $post_type,
					'count'     => true,
				) );
			$returnArray['comment_id'] = $comment_ID;
			$returnArray['private']       = get_comment_meta( $comment_ID, '_rthd_privacy', true );
			$comment_user  = get_user_by( 'id', $comment->user_id );
			$comment_render_type = 'left';
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			if ( ! empty( $comment_user ) ) {
				if ( $comment_user->has_cap( $cap ) ) {
					$comment_render_type = 'right';
				}
			}
			$user_edit = current_user_can( $cap ) || (get_current_user_id() == $comment->user_id );
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
			$followuptype    = $_POST['followuptype'];
			$comment_post_ID = $_POST['followup_post_id'];
			$post_type       = get_post_type( $comment_post_ID );
			$comment_content = Rt_HD_Utils::force_utf_8( $_POST['followup_content'] );
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
				wp_update_comment( $commentdata );
				$old_privacy = get_comment_meta( $_POST['comment_id'], '_rthd_privacy' ,true );
				update_comment_meta( $_POST['comment_id'], '_rthd_privacy', $comment_privacy );
				$comment = get_comment( $_POST['comment_id'] );
				$uploaded = array();
				$attachment = array();
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
				$title       = rthd_create_new_ticket_title( 'rthd_update_followup_email_title', $comment_post_ID );

				$body = ' Follwup Updated by ' . $currentUser->display_name;
				$body .= '<br/><b>Type : </b> <br/>' . $followuptype;

				$flag = false;

				$old_privacy_text = ( $old_privacy == 'true' )?'yes':'no';
				$new_privacy = ( $comment_privacy == 'true' )?'yes':'no';

				$diff = rthd_text_diff( $old_privacy_text, $new_privacy );
				if ( $diff ) {
					$body .= '<br/><b>Private : </b>' . $diff;
					$flag = true;
				}

				$diff = rthd_text_diff( $oldDate, $newDate );
				if ( $diff ) {
					$body .= '<br/><b>Date : </b>' . $diff;
					$flag = true;
				}

				$diff = rthd_text_diff( trim( html_entity_decode( strip_tags( $oldCommentBody ) ) ), trim( html_entity_decode( strip_tags( $commentdata['comment_content'] ) ) ) );
				if ( $diff ) {
					$flag = true;
					$body .= '<br/><b>Body : </b>' . $diff;
				}
				else {
					$body .= '<br/><b>Body : </b>' . wpautop( make_clickable( $comment->comment_content ) );
				}

				$body .= '<br/> ';
				if ( $flag ) {
					$redux = rthd_get_redux_settings();
					$notificationFlag = ( $redux['rthd_notification_events']['followup_edited'] == 1 );
					$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $attachment, $_POST['comment_id'],$notificationFlag, false );
				}

				$returnArray['status']        = true;
				$returnArray['comment_count'] = get_comments(
					array(
						'order'     => 'DESC',
						'post_id'   => $comment_post_ID,
						'post_type' => $post_type,
						'count'     => true,
					) );
				$returnArray['private']       = get_comment_meta( $_POST['comment_id'], '_rthd_privacy', true );
				$returnArray['comment_content'] = wpautop( make_clickable( $comment->comment_content ) );
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
			$comment_ID                      = wp_insert_comment( $commentdata );

			update_comment_meta( $comment_ID, '_rthd_privacy', $comment_privacy );

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

				$title = $this->create_title_for_mail( $comment_post_ID );

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
				$rt_hd_email_notification->insert_new_send_email( $title, $mailbody, $toEmail, $ccEmail, $bccEmail, $attachment, $comment_ID, 'comment' );
			}

			update_comment_meta( $comment_ID, '_email_from', $_POST['comment-reply-from'] );
			update_comment_meta( $comment_ID, '_email_to', $to );
			update_comment_meta( $comment_ID, '_email_cc', $cc );
			update_comment_meta( $comment_ID, '_email_bcc', $bcc );

			$currentUser = get_user_by( 'id', get_current_user_id() );
			$title       = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment_post_ID );

			$body = '<span style="color:#777">< ! ------------------ REPLY ABOVE THIS LINE ------------------ ! ></span><br />';
			$body .= '<br/><strong>New Followup Added by ' . $currentUser->display_name.':</strong><br />';
			if ( 'mail' == $followuptype ) {
				$body .= '<br/><b>From : </b>' . $_POST['comment-reply-from'];
				$body .= '<br/><b>To : </b>' . $to;
				$body .= '<br/><b>CC : </b>' . $cc;
				$body .= '<br/><b>BCC : </b>' . $bcc;
			}

			$body .= '<br/>' . apply_filters( 'the_content', $comment->comment_content );

			$body .= '<br/> ';
			$notificationFlag = $this->check_setting_for_new_followup_email();
			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $attachment, $comment_ID, $notificationFlag, true );
			$returnArray['status'] = true;
			$returnArray['comment_id'] = $comment_ID;
			$returnArray['comment_count'] = get_comments( array(
															'order'     => 'DESC',
															'post_id'   => $comment_post_ID,
															'post_type' => $post_type,
															'count'     => true,
															) );
			$returnArray['private']       = get_comment_meta( $comment_ID, '_rthd_privacy', true );

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
			global $wpdb, $rt_hd_mail_accounts_model, $rt_hd_mail_acl_model;
			$sql    = $wpdb->prepare( '(select * from {$rt_hd_mail_accounts_model->table_name} where user_id=%d and email = %s)
                                union (select a.* from {$rt_hd_mail_acl_model->table_name} b inner join
                                {$rt_hd_mail_accounts_model->table_name} a on a.email=b.email where b.allow_user=%d and a.email=%s)', $user_id, $email, $user_id, $email );
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
		 * @param $post_id
		 * @param $title
		 * @param $body
		 * @param $attachment
		 * @param $comment_id
		 * @param $notificationFlag
		 * @param $contactFlag
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function notify_subscriber_via_email( $post_id, $title, $body, $attachment = array(), $comment_id, $notificationFlag, $contactFlag ) {
			$oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );
			$bccemails        = array();
			if ( $oldSubscriberArr && is_array( $oldSubscriberArr ) && ! empty( $oldSubscriberArr ) ) {
				foreach ( $oldSubscriberArr as $emailsubscriber ) {
					$userSub     = get_user_by( 'id', intval( $emailsubscriber ) );
					if ( ! empty( $userSub ) ) {
						$bccemails[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
					}
				}
			}

			global $redux_helpdesk_settings, $rt_hd_email_notification;

			if ( $contactFlag ) {
				$tocontact      = array();
				$persons = rt_biz_get_post_for_person_connection( $post_id, Rt_HD_Module::$post_type );
				foreach ( $persons as $person ) {
					global $rt_person;
					$emails = get_post_meta( $person->ID, Rt_Entity::$meta_key_prefix.$rt_person->email_key );
					foreach ( $emails as $email ) {
						array_push( $tocontact, array( 'email' => $email ) );
					}
				}
				$rthd_unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );

				global $rt_hd_module;
				$labels = $rt_hd_module->labels;
				$bd     = $body . " Click <a href='" . esc_url( trailingslashit( site_url() ) . strtolower( $labels['name'] ) . '/' . $rthd_unique_id ) . "'> here </a> to view ticket";
				$rt_hd_email_notification->insert_new_send_email( $title, $bd, $tocontact, array(), array(), $attachment, $comment_id, 'comment' );

			}

			$cc = array();
			if ( $notificationFlag ) {
				if ( isset( $redux_helpdesk_settings['rthd_notification_emails'] ) ) {
					foreach ( $redux_helpdesk_settings['rthd_notification_emails'] as $email ) {
						array_push( $cc, array( 'email' => $email ) );
					}
				}
			}
			$post_author_id = get_post_field( 'post_author', $post_id );
			$userSub     = get_user_by( 'id', intval( $post_author_id ) );
			$to[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			//			$emailHTML = $body . "</br> To View Follwup Click <a href='". admin_url().'post.php?post='.$post_id.'&action=edit'."'>here</a>.<br/>";
			$rthd_unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$emailHTML     = $body . " Click <a href='" . esc_url( trailingslashit( site_url() ) . strtolower( $labels['name'] ) . '/' . $rthd_unique_id ) . "'> here </a> to view ticket";

			$rt_hd_email_notification->insert_new_send_email( $title, $emailHTML, $to, $cc, $bccemails, $attachment, $comment_id, 'comment' );
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
			$response['status'] = wp_delete_comment( $_POST['comment_id'], true );
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( $redux['rthd_notification_events']['followup_deleted'] == 1 );
			$currentUser      = get_user_by( 'id', get_current_user_id() );
			$body             = ' Follwup Deleted by ' . $currentUser->display_name;
			$body .= '<br/><b>Body : </b>' . $comment->comment_content;
			$body .= '<br/> ';
			$comment_post_ID = $_POST['post_id'];

			$title           = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title', $comment_post_ID );
			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, array(), $_POST['comment_id'], $notificationFlag, false );
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

			global $rt_hd_settings;
			$signature    = '';
			$email_type   = '';
			$imap_server  = '';
			$access_token = $rt_hd_settings->get_accesstoken_from_email( $_POST['email'], $signature, $email_type, $imap_server );

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
			global $rt_hd_mail_thread_importer_model;
			$rows_affected = $rt_hd_mail_thread_importer_model->add_thread( $args );

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
			if (isset($_POST['post_id']) && isset($_POST['offset']) && isset($_POST['limit'])  ){
				$postid=  $_POST['post_id'];
				$offset=  $_POST['offset'];
				$Limit=  $_POST['limit'];
				$offset=  $offset+$Limit;
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
				$user_edit = current_user_can( $cap ) || (get_current_user_id() == $comment->user_id );
				$commenthtml .= rthd_render_comment( $comment, $user_edit, $comment_render_type, false );
				$count++;
			}

			$placeholder = '';
			if ( ! ( $count < $Limit ) ) {
				$placeholder = '<div class="content-stream stream-loading js-loading-placeholder"><img id="load-more-hdspinner" class="js-loading-placeholder" src="' . admin_url() . 'images/spinner.gif' . '" /></div>';
			}

			$response['count n limit'] = $count . ' : ' . $Limit;
			$response['offset']= $offset;
			$response['comments']= $commenthtml;
			$response['placeholder'] = $placeholder;
			$response['status']= true;
			echo json_encode($response);
			die();


		}

	}

}
