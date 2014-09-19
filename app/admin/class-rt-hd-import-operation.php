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
			//add_filter( 'comments_clauses', array( $this, 'filter_comment_from_admin' ), 100, 1 );

			add_action( 'comment_post', array( $this, 'mail_new_comment_data' ), 10, 2 );

			add_action( 'wp_ajax_rthd_add_new_followup_ajax', array( $this, 'add_new_followup_ajax' ) );
			add_action( 'wp_ajax_rthd_add_new_followup_front', array( $this, 'add_new_followup_front' ) );
			add_action( 'wp_ajax_nopriv_rthd_add_new_followup_front', array( $this, 'add_new_followup_front' ) );
			add_action( 'wp_ajax_rthd_get_ticket_comments_ajax', array( $this, 'get_ticket_comments_ajax' ) );
			add_action( 'wp_ajax_nopriv_rthd_get_ticket_comments_ajax', array( $this, 'get_ticket_comments_ajax' ) );
			add_action( 'wp_ajax_rthd_activecollab_task_comment_import_ajax', array(
				$this,
				'activecollab_task_comment_import_ajax',
			) );
			add_action( 'wp_ajax_helpdesk_delete_followup', array( $this, 'delete_followup_ajax' ) );
			add_action( 'wp_ajax_rthd_gmail_import_thread_request', array( $this, 'gmail_thread_import_request' ) );


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
				if ( strpos( $_comment1['join'], $wpdb->posts ) === false ) {
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
		 * @param        $userid
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
		public function insert_new_ticket( $title, $body, $userid, $mailtime, $allemail, $uploaded, $senderEmail, $messageid = '', $inreplyto = '', $references = '', $subscriber = array() ) {
			global $rt_hd_module, $rt_hd_tickets_operation, $rt_hd_ticket_history_model;

			$d             = new DateTime( $mailtime );
			$timeStamp     = $d->getTimestamp();
			$post_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
			$post_type     = Rt_HD_Module::$post_type;
			$labels        = $rt_hd_module->labels;
			$settings      = rthd_get_settings();

			$postArray = array(
				'post_author'   => $userid,
				'post_content'  => $body,
				'post_date'     => $post_date,
				'post_status'   => 'unanswered',
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

			$post_id = $rt_hd_tickets_operation->ticket_default_field_update( $postArray, $dataArray, $post_type );

			$rt_hd_ticket_history_model->insert(
				array(
					'ticket_id'   => $post_id,
					'type'        => 'post_status',
					'old_value'   => 'auto-draft',
					'new_value'   => $postArray['post_status'],
					'update_time' => current_time( 'mysql' ),
					'updated_by'  => get_current_user_id(),
				) );

			$rt_hd_tickets_operation->ticket_subscribe_update( $subscriber, $postArray['post_author'], $post_id );

			if ( isset( $settings['attach_contacts'] ) && $settings['attach_contacts'] == 'yes' ) {
				$this->add_contacts_to_post( $allemail, $post_id );
			}

			$this->add_attachment_to_post( $uploaded, $post_id, $post_type );

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
				$rt_hd_email_notification->notification_new_ticket_assigned( $post_id, $userid, $labels['name'], $uploaded );
			}

			$rt_hd_email_notification->ticket_created_notification( $post_id, $userid, $labels['name'], $body, $uploaded );

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
			global $helpdesk_import_ticket_id, $rt_hd_module;
			$post_type       = get_post_type( $helpdesk_import_ticket_id );
			$data['subject'] = '[' . strtoupper( $rt_hd_module->name ) . ' #' . $helpdesk_import_ticket_id . ']' . $data['subject'];
			$hd_url          = admin_url( "post.php?post={$helpdesk_import_ticket_id}action=edit" );
			$data['message'] = str_replace( '--rtcamp_hd_link--', $hd_url, $data['message'] );

			return $data;
		}

		/**
		 * create email title
		 *
		 * @param $post_id
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function create_title_for_mail( $post_id ) {
			global $rt_hd_module;

			return '[' . strtoupper( $rt_hd_module->name ) . ' #' . $post_id . '] ' . get_the_title( $post_id );
		}

		/**
		 * add attachment to post from mail
		 *
		 * @since 0.1
		 *
		 * @param      $uploaded
		 * @param      $post_id
		 * @param      $post_type
		 * @param bool $mainTicket
		 * @param int  $comment_id
		 */
		function add_attachment_to_post( $uploaded, $post_id, $post_type, $mainTicket = true, $comment_id = 0 ) {
			if ( isset( $uploaded ) && is_array( $uploaded ) ) {

				foreach ( $uploaded as $upload ) {

					$post_attachment_hashes = get_post_meta( $post_id, '_rtbiz_hd_attachment_hash' );
					if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $upload['file'] ), $post_attachment_hashes ) ) {
						continue;
					}

					//$uploaded["filename"]
					$attachment = array(
						'post_title'     => $upload['filename'],
						'image_alt'      => $upload['filename'],
						'post_type'      => $post_type,
						'post_content'   => '',
						'post_excerpt'   => '',
						'post_parent'    => $post_id,
						'post_mime_type' => $this->get_mime_type_from_extn( $upload['extn'] ),
						'guid'           => $upload['url'],
					);
					$attach_id  = wp_insert_attachment( $attachment );

					add_post_meta( $attach_id, '_wp_attached_file', $upload['file'] );
					add_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $upload['file'] ) );

					if ( $mainTicket ) {
						add_post_meta( $attach_id, 'show-in-main', 'true' );
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
			//\[([a-z]+)\ \#([1-9]+)\]
			global $rt_hd_module;
			$pattern     = '/\[([a-z]+)\ \#([0-9]+)\]/im';
			$intMatch    = preg_match_all( $pattern, $subject, $matches );
			$module_name = strtolower( $rt_hd_module->name );
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

			if ( $userid === false ) {
				$userid = $rt_hd_contacts->get_user_from_email( $fromemail['address'] );
			}
			//always true in mail cron  is use for importer
			if ( ! $check_duplicate ) {
				return $this->insert_new_ticket( $title, $body, $userid, $mailtime, $allemail, $uploaded, $fromemail['address'] );
			}
			//-----------------------------------------------------------------------------//
			global $threadPostId;
			$forwardFlag = false;
			if ( ! isset( $threadPostId ) ) {
				$forwardFlag = strpos( strtolower( $title ), 'fwd:' );
				if ( $forwardFlag === false ) {
					$forwardFlag = false;
				} else {
					$forwardFlag = true;
				}


				if ( strpos( strtolower( $title ), 're:' ) === false ) {
					$replyFlag = false;
				} else {
					$replyFlag = true;
				}

				$postid = $this->get_post_id_from_subject( $title );

				if ( ! $postid ) {
					//get postID from inreply to and refrence meta
					$postid = $this->get_post_id_from_mail_meta( $inreplyto, $references );
				}

				//if we got post id from subject
			} else {
				$postid = $threadPostId;
			}
			$dndEmails = array();

			if ( $postid && get_post( $postid ) != null ) {
				if ( $forwardFlag ) {
					$this->process_forward_email_data( $title, $body, $mailtime, $allemail, $mailBodyText, $dndEmails );
				}

				return $this->insert_post_comment( $postid, $userid, $body, $fromemail['name'], $fromemail['address'], $mailtime, $uploaded, $allemail, $dndEmails, $messageid, $inreplyto, $references, $subscriber );
			}
			//if subject is re to post title

			if ( $replyFlag ) {
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
						return $this->insert_new_ticket( $title, $body, $userid, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber );
					}
				}
			} else {
				$existPostId = $this->post_exists( $title, $mailtime );
				//if given post title exits then it will be add as comment other wise as post
				echo esc_attr( $existPostId );
				if ( ! $existPostId ) {
					if ( $systemEmail ) {
						return $this->insert_new_ticket( $title, $body, $userid, $mailtime, $allemail, $uploaded, $fromemail['address'], $messageid, $inreplyto, $references, $subscriber );
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
				if ( $row->type == 'post' ) {
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
				if ( ! ( strpos( $forwardline, 'to:' ) === false ) ) {
					$dndEmails = array_merge( $dndEmails, $this->extract_all_email_from_string( $forwardline ) );
				}
				if ( ! ( strpos( $forwardline, 'cc:' ) === false ) ) {
					$dndEmails = array_merge( $dndEmails, $this->extract_all_email_from_string( $forwardline ) );
				}
				if ( ! ( strpos( $forwardline, 'data:' ) === false ) ) {
					$mailtime = trim( str_replace( 'date:', '', $forwardline ) );
				}
				if ( ! ( strpos( $forwardline, 'title:' ) === false ) ) {
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
			$module_settings = rthd_get_settings();
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
			if ( $checkDupli !== false ) {
				if ( $messageid != '' ) {
					add_comment_meta( $checkDupli, '_messageid', $messageid );
				}
				if ( $inreplyto != '' ) {
					add_comment_meta( $checkDupli, '_inreplyto', $inreplyto );
				}
				if ( $references != '' ) {
					add_comment_meta( $checkDupli, '_references', $references );
				}

				return false;
			}

			$comment_id = wp_insert_comment( $data );
			if ( $messageid != '' ) {
				add_comment_meta( $comment_id, '_messageid', $messageid );
			}
			if ( $inreplyto != '' ) {
				add_comment_meta( $comment_id, '_inreplyto', $inreplyto );
			}
			if ( $references != '' ) {
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

			$this->add_attachment_to_post( $uploaded, $comment_post_ID, $post_type, false, $comment_id );
			if ( isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) {
				$this->add_contacts_to_post( $allemails, $comment_post_ID );
			}
			global $threadPostId;
			if ( ! isset( $threadPostId ) ) {
				$title = '[New Follwup Added]' . $this->create_title_for_mail( $comment_post_ID );
				$body  = ' New Follwup Added by ' . $comment_author . ' - ' . $comment_author_email;
				$body .= '<br/><b>Type : </b>' . 'Mail';
				$body .= '<br/><b>From : </b>' . $comment_author_email;
				$body .= '<br/><b>Body : </b>' . $comment_content;
				$body .= '<br/> ';
				$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $comment_id );
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
		 * Get comment emails
		 *
		 * @param $post_id
		 *
		 * @return array emails
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function get_comment_emails( $post_id ) {
			global $wpdb;

			$retuls    = ( $wpdb->prepare( "select distinct comment_author_email as email from $wpdb->comments where comment_post_ID = %d;", $post_id ) );
			$arrReturn = array();
			foreach ( $retuls as $email ) {
				$arrReturn[] = $email->email;
			}

			return $arrReturn;
		}

		/**
		 * Send new mail as someone post new comment
		 *
		 * @param $comment_id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function mail_new_comment_data( $comment_id ) {

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

			$title = $this->create_title_for_mail( $comment_post_ID );


			/* $dndEmailList = '';
				foreach ($allemails as $aEmail) {
					$dndEmailList .= "," . $aEmail["address"];
				}
				$dndEmailList = strtolower($dndEmailList) . ",";

				//$postterms = wp_get_post_terms($comment_post_ID, $contacts->slug);

				$ticket_email = array_merge(get_post_meta($comment_post_ID, "ticket_email"), $this->get_comment_emails($comment_post_ID));

				$bcc = "";
				$sep = "";
				$sendToMemerOnly = false;



				if (isset($_REQUEST["commentSendMail"]) && $_REQUEST["commentSendMail"] == "member") {
					$sendToMemerOnly = true;
				}*/

			$mailbody = $comment->comment_content;

			//commentSendAttachment
			$attachment = array();
			if ( isset( $_REQUEST['commentSendAttachment'] ) && $_REQUEST['commentSendAttachment'] != '' ) {
				$arrAttache = explode( ',', $_REQUEST['commentSendAttachment'] );
				foreach ( $arrAttache as $strAttach ) {
					$attachfile = get_attached_file( intval( $strAttach ) );
					if ( $attachfile ) {
						$attachment[] = $attachfile;
						//add_comment_meta($comment_id, "attachment", wp_get_attachment_url(intval($strAttach)));
					}
				}
			} else {
				$_REQUEST['commentSendAttachment'] = '';
			}
			/*if (isset($_REQUEST["commentReplyTo"]) && $_REQUEST["commentReplyTo"] != "") {
				$commentReplyTo = explode(",", $_REQUEST["commentReplyTo"]);
			}
			foreach ($postterms as $bemail) {
				//$bemail = get_term_meta($pterm->term_id, $contacts->email_key, true);
				if (in_array($bemail, $dndEmails)) {
					continue;
				}
				if ($sendToMemerOnly) {
					$userid = @email_exists($bemail);
					$user = new WP_User($userid);
					$flag = false;
					foreach ($user->roles as $role) {
						if ($role == $contacts->user_role) {
							$flag = true;
							break;
						}
					}
					if ($flag)
						continue;
				}
				if (!(strpos($dndEmailList, strtolower($bemail)) === false)) {
					continue;
				}
				if ($senderemail == $bemail) {
					continue;
				}
				if (!in_array($bemail, $commentReplyTo)) {
					continue;
				}
				if (strpos($bcc, $bemail) === false) {
					if ($bcc == "") {
						$toemail = $bemail;
						$bcc = "Bcc: ";
						continue;
					}
					$bcc.=$sep . $bemail;
					$sep = ",";
				}
			}

			$subscribe_to = get_post_meta($comment_post_ID, 'subscribe_to', true);
			if (is_array($subscribe_to) && sizeof($subscribe_to) > 0) {
				foreach ($subscribe_to as $subscriber) {
					$bemail = get_user_meta(intval($subscriber), "user_email", true);
					if (in_array($bemail, $dndEmails)) {
						continue;
					}
					if ($senderemail == $bemail) {
						continue;
					}
					if (!in_array($bemail, $commentReplyTo)) {
						continue;
					}
					if (strpos($bcc, $bemail) === false) {
						if ($bcc == "") {
							$toemail = $bemail;
							$bcc = "Bcc: ";
							continue;
						}
						$bcc.=$sep . $bemail;
						$sep = ",";
					}
				}
			}

			$headers = array();
			if ($sep == ",")
				$headers[] = $bcc;*/

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
				return true;
			}

			if ( ! $this->is_allow_to_sendemail_fromemail( $fromemail ) ) {
				return false;
			}
			$signature   = '';
			$email_type  = '';
			$imap_server = '';
			global $rt_hd_settings;
			$accessToken = $rt_hd_settings->get_accesstoken_from_email( $fromemail, $signature, $email_type, $imap_server );

			if ( strpos( $signature, '</' ) == false ) {
				$signature = htmlentities( $signature );
				$signature = preg_replace( '/(\n|\r|\r\n)/i', '<br />\n', $signature );
				$signature = preg_replace( '/  /i', '  ', $signature );
			}


			$mailbody .= '<br />' . $signature;
			global $rt_hd_settings;
			$tmpMailOutbountId = $rt_hd_settings->insert_new_send_email( $_POST['comment-reply-from'], $title, $mailbody, $toEmail, $ccEmail, $bccEmail, $attachment, $comment_id, 'comment' );

			return true;
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
			if ( $strFileType == '.asf' ) {
				$ContentType = 'video/x-ms-asf';
			}
			if ( $strFileType == '.avi' ) {
				$ContentType = 'video/avi';
			}
			if ( $strFileType == '.doc' ) {
				$ContentType = 'application/msword';
			}
			if ( $strFileType == '.zip' ) {
				$ContentType = 'application/zip';
			}
			if ( $strFileType == '.xls' ) {
				$ContentType = 'application/vnd.ms-excel';
			}
			if ( $strFileType == '.gif' ) {
				$ContentType = 'image/gif';
			}
			if ( $strFileType == '.jpg' || $strFileType == 'jpeg' ) {
				$ContentType = 'image/jpeg';
			}
			if ( $strFileType == '.wav' ) {
				$ContentType = 'audio/wav';
			}
			if ( $strFileType == '.mp3' ) {
				$ContentType = 'audio/mpeg3';
			}
			if ( $strFileType == '.mpg' || $strFileType == 'mpeg' ) {
				$ContentType = 'video/mpeg';
			}
			if ( $strFileType == '.rtf' ) {
				$ContentType = 'application/rtf';
			}
			if ( $strFileType == '.htm' || $strFileType == 'html' ) {
				$ContentType = 'text/html';
			}
			if ( $strFileType == '.xml' ) {
				$ContentType = 'text/xml';
			}
			if ( $strFileType == '.xsl' ) {
				$ContentType = 'text/xsl';
			}
			if ( $strFileType == '.css' ) {
				$ContentType = 'text/css';
			}
			if ( $strFileType == '.php' ) {
				$ContentType = 'text/php';
			}
			if ( $strFileType == '.asp' ) {
				$ContentType = 'text/asp';
			}
			if ( $strFileType == '.pdf' ) {
				$ContentType = 'application/pdf';
			}

			return $ContentType;
		}

		/**
		 * return class according to status
		 *
		 * @param $status
		 *
		 * @return string (Default,success,warning,important,info,inverse)
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_status_class( $status ) {
			//default,sucess,warning,important,info,inverse
			switch ( strtolower( $status ) ) {
				case 'in-progress':
					return 'success';
					break;
				case 'all':
					return 'default';
					break;
				case 'new':
					return 'info';
					break;
				case 'followup':
					return 'warning';
					break;
				case 'draft':
					return 'info';
					break;
				case 'won':
					return 'sucess';
					break;
				case 'pending':
					return 'important';
					break;
				case 'awaiting':
					return 'important';
					break;
				case 'lost':
					return 'inverse';
					break;

				default:
					return 'inverse';
			}
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

			$comment_content = $_POST['followup_content'];
			$comment_privacy = 'no';
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

			$comment    = get_comment( $comment_ID );
			$attachment = array();
			$uploaded   = array();
			if ( isset( $_REQUEST['attachemntlist'] ) && ! empty( $_REQUEST['attachemntlist'] ) ) {
				foreach ( $_REQUEST['attachemntlist'] as $strAttach ) {
					$attachfile = get_attached_file( intval( $strAttach ) );
					if ( $attachfile ) {
						$attachment[] = $attachfile;
						add_comment_meta( $comment_ID, 'attachment', wp_get_attachment_url( intval( $strAttach ) ) );
						$extn_array = explode( '.', $attachfile );
						$extn       = $extn_array[ count( $extn_array ) - 1 ];
						$file_array = explode( '/', $attachfile );
						$fileName   = $file_array[ count( $file_array ) - 1 ];
						$uploaded[] = array(
							'filename' => $fileName,
							'extn'     => $extn,
							'url'      => $attachfile,
							'file'     => get_post_meta( intval( $strAttach ), '_wp_attached_file', true )
						);
					}
				}
				$this->add_attachment_to_post( $uploaded, $comment_post_ID, false );
			}

			$currentUser = get_user_by( 'id', get_current_user_id() );

			if ( ! empty( $currentUser->user_email ) ) {
				update_comment_meta( $comment_ID, '_email_from', $currentUser->user_email );
			}

			$title = '[New Follwup Added]' . $this->create_title_for_mail( $comment_post_ID );

			$body = ' New Follwup Added ' . ( ( ! empty( $currentUser->display_name ) ) ? 'by ' . $currentUser->display_name : 'annonymously' );
			$body .= '<br/><b>Body : </b>' . $comment->comment_content;
			$body .= '<br/> ';

			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $comment_ID );

			$returnArray['status']        = true;
			$returnArray['data']          = $this->generate_comment_html_front( $comment );
			$returnArray['comment_count'] = get_comments(
				array(
					'order'     => 'DESC',
					'post_id'   => $comment_post_ID,
					'post_type' => $post_type,
					'count'     => true,
				) );

			echo json_encode( $returnArray );

			ob_end_flush();
			die( 0 );
		}

		/**
		 * Get tickets from ajax
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_ticket_comments_ajax() {
			if ( ! isset( $_POST['ticket_unique_id'] ) ) {
				wp_die( 'Invalid Request' );
			}
			if ( ! isset( $_POST['page'] ) ) {
				wp_die( 'Invalid Request' );
			}

			$ticket_unique_id = $_POST['ticket_unique_id'];
			$args             = array(
				'meta_key'    => 'rthd_unique_id',
				'meta_value'  => $ticket_unique_id,
				'post_status' => 'any',
				'post_type'   => Rt_HD_Module::$post_type,
			);
			$ticketpost       = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				wp_die( 'Invalid Ticket' );
			}
			$rthd_ticket   = $ticketpost[0];
			$page          = $_POST['page'];
			$comment_count = count( get_comments(
										array(
											'meta_key'   => '_rthd_privacy',
											'meta_value' => 'no',
											'order'      => 'DESC',
											'post_id'    => $rthd_ticket->ID,
											'post_type'  => $rthd_ticket->post_type,
											) ) );
			$comments = get_comments(
				array(
					'meta_key'   => '_rthd_privacy',
					'meta_value' => 'no',
					'order'      => 'DESC',
					'post_id'    => $rthd_ticket->ID,
					'post_type'  => $rthd_ticket->post_type,
					'number'     => '10',
					'offset'     => $page * 10,
				) );
			ob_start();
			foreach ( $comments as $comment ) {
				echo balanceTags( $this->generate_comment_html_front( $comment ) );
			} //End Loop for comments

			$returnStr = ob_get_clean();
			$more      = false;
			if ( ( $page + 1 ) < ( $comment_count / 10 ) ) {
				$more = true;
			}
			$returnArray = array(
				'status'       => 'success',
				'data'         => $returnStr,
				'more'         => $more,
				'result_count' => sizeof( $comments )
			);
			echo json_encode( $returnArray );
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
			if ( ! current_user_can( 'edit_post', $comment_post_ID ) ) {
				$returnArray['status']  = false;
				$returnArray['message'] = 'Unauthorized Access';
				echo json_encode( $returnArray );
				die( 0 );
			}
			$comment_content = $_POST['followup_content'];
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
				wp_update_comment( $commentdata );
				update_comment_meta( $_POST['comment_id'], '_rthd_privacy', $comment_privacy );
				$comment = get_comment( $_POST['comment_id'] );
				if ( isset( $_REQUEST['attachemntlist'] ) && ! empty( $_REQUEST['attachemntlist'] ) ) {
					delete_comment_meta( $_POST['comment_id'], 'attachment' );
					$uploaded = array();
					foreach ( $_REQUEST['attachemntlist'] as $strAttach ) {
						if ( intval( $strAttach ) > 0 ) {
							$attachfile = get_attached_file( intval( $strAttach ) );
							add_comment_meta( $_POST['comment_id'], 'attachment', wp_get_attachment_url( intval( $strAttach ) ) );
						} else {
							$attachfile = $strAttach;
							add_comment_meta( $_POST['comment_id'], 'attachment', $strAttach );
						}
						if ( $attachfile ) {
							$attachment[] = $attachfile;
							$extn_array   = explode( '.', $attachfile );
							$extn         = $extn_array[ count( $extn_array ) - 1 ];
							$file_array   = explode( '/', $attachfile );
							$fileName     = $file_array[ count( $file_array ) - 1 ];
							$uploaded[]   = array(
								'filename' => $fileName,
								'extn'     => $extn,
								'url'      => $attachfile,
								'file'     => get_post_meta( intval( $strAttach ), '_wp_attached_file', true )
							);
						}
					}
					$this->add_attachment_to_post( $uploaded, $comment_post_ID, false );
				}

				$currentUser = get_user_by( 'id', get_current_user_id() );
				$title       = '[Follwup Updated]' . $this->create_title_for_mail( $comment_post_ID );


				$body = ' Follwup Updated by ' . $currentUser->display_name;
				$body .= '<br/><b>Type : </b>' . $followuptype;


				$flag = false;

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

				$body .= '<br/> ';
				if ( $flag ) {
					$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $_POST['comment_id'] );
				}

				$returnArray['status']        = true;
				$returnArray['data']          = $this->genrate_comment_html_ajax( $comment );
				$returnArray['comment_count'] = get_comments(
					array(
						'order'     => 'DESC',
						'post_id'   => $comment_post_ID,
						'post_type' => $post_type,
						'count'     => true,
					) );
				$returnArray['private']       = get_comment_meta( $_POST['comment_id'], '_rthd_privacy', true );

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
						add_comment_meta( $comment_ID, 'attachment', wp_get_attachment_url( intval( $strAttach ) ) );
						$extn_array = explode( '.', $attachfile );
						$extn       = $extn_array[ count( $extn_array ) - 1 ];
						$file_array = explode( '/', $attachfile );
						$fileName   = $file_array[ count( $file_array ) - 1 ];
						$uploaded[] = array(
							'filename' => $fileName,
							'extn'     => $extn,
							'url'      => $attachfile,
							'file'     => get_post_meta( intval( $strAttach ), '_wp_attached_file', true )
						);
					}
				}
				$this->add_attachment_to_post( $uploaded, $comment_post_ID, false );
			}
			$to  = '';
			$bcc = '';
			$cc  = '';

			if ( $followuptype == 'mail' ) {


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
				global $rt_hd_settings;
				$accessToken = $rt_hd_settings->get_accesstoken_from_email( $fromemail, $signature, $email_type, $imap_server );

				if ( strpos( $signature, '</' ) == false ) {
					$signature = htmlentities( $signature );
					$signature = preg_replace( '/(\n|\r|\r\n)/i', '<br />\n', $signature );
					$signature = preg_replace( '/  /i', '  ', $signature );
				}
				$mailbody .= '<br />' . $signature;
				$rt_hd_settings->insert_new_send_email( $_POST['comment-reply-from'], $title, $mailbody, $toEmail, $ccEmail, $bccEmail, $attachment, $comment_ID, 'comment' );
			}

			update_comment_meta( $comment_ID, '_email_from', $_POST['comment-reply-from'] );
			update_comment_meta( $comment_ID, '_email_to', $to );
			update_comment_meta( $comment_ID, '_email_cc', $cc );
			update_comment_meta( $comment_ID, '_email_bcc', $bcc );

			$currentUser = get_user_by( 'id', get_current_user_id() );
			$title       = '[New Follwup Added]' . $this->create_title_for_mail( $comment_post_ID );


			$body = ' New Follwup Added by ' . $currentUser->display_name;
			$body .= '<br/><b>Type : </b>' . $followuptype;
			if ( $followuptype == 'mail' ) {
				$body .= '<br/><b>From : </b>' . $_POST['comment-reply-from'];
				$body .= '<br/><b>To : </b>' . $to;
				$body .= '<br/><b>CC : </b>' . $cc;
				$body .= '<br/><b>BCC : </b>' . $bcc;
			}

			$body .= '<br/><b>Body : </b>' . $comment->comment_content;

			$body .= '<br/> ';
			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, $comment_ID );
			$returnArray['status'] = true;

			$returnArray['data']          = $this->genrate_comment_html_ajax( $comment );
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
		 * @param $comment_id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function notify_subscriber_via_email( $post_id, $title, $body, $comment_id ) {
			$oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );
			$bccemails        = array();
			if ( $oldSubscriberArr && is_array( $oldSubscriberArr ) && ! empty( $oldSubscriberArr ) ) {
				foreach ( $oldSubscriberArr as $emailsubscriber ) {
					$userSub     = get_user_by( 'id', intval( $emailsubscriber ) );
					$bccemails[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
				}
			}
			$flag            = true;
			$post_type       = get_post_type( $post_id );
			$module_settings = rthd_get_settings();
			$systemEmail     = ( isset( $module_settings['system_email'] ) ) ? $module_settings['system_email'] : '';
			if ( $systemEmail ) {
				if ( ! is_email( $systemEmail ) ) {
					$flag = false;
				}
			} else {
				$flag = false;
			}
			if ( $flag && ! empty( $bccemails ) ) {
				global $rt_hd_settings;
				$accessToken = $rt_hd_settings->get_accesstoken_from_email( $systemEmail, $signature, $email_type, $imap_server );

				if ( strpos( $signature, '</' ) == false ) {
					$signature = htmlentities( $signature );
					$signature = preg_replace( '/(\n|\r|\r\n)/i', '<br />', $signature );
					$signature = preg_replace( '/  /i', '  ', $signature );
				}
				$emailHTML = $body . "</br> To View Follwup Click <a href='" . admin_url( 'edit.php?post_type={$post_type}&page=rthd-add-{$post_type}&{$post_type}_id=' . $post_id ) . "'>here</a>.<br/>";
				$emailHTML .= '<br />' . $signature;
				$rt_hd_settings->insert_new_send_email( $systemEmail, $title, $emailHTML, array(), array(), $bccemails, array(), $comment_id, 'comment' );
			}
		}

		/**
		 * Generate comment
		 *
		 * @param $comment
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function generate_comment_html_front( $comment ) {
			$user_edit = false;
			if ( isset( $_POST['user_edit'] ) ) {
				$user_edit = $_POST['user_edit'];
			}

			global $prev_month, $prev_year, $prev_day;
			$prev_month = '';
			$prev_day   = '';
			$prev_year  = '';
			ob_start();
			rthd_get_template( 'followup.php', array( 'comment' => $comment, 'user_edit' => $user_edit, ) );
			$commentstr = ob_get_clean();

			return $commentstr;
		}

		/**
		 * generate comment for ajax call
		 *
		 * @param $comment
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function genrate_comment_html_ajax( $comment ) {

			$user_edit = false;
			if ( isset( $_POST['user_edit'] ) ) {
				$user_edit = $_POST['user_edit'];
			}

			global $prev_month, $prev_year, $prev_day;
			$prev_month = '';
			$prev_day   = '';
			$prev_year  = '';
			ob_start();
			rthd_get_template( 'admin/followup.php', array( 'comment' => $comment, 'user_edit' => $user_edit, ) );
			$commentstr = ob_get_clean();

			return $commentstr;
		}

		/**
		 * activecollab task comment import ajax
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function activecollab_task_comment_import_ajax() {

			$respoArray = array();
			if ( ! isset( $_POST['comment_data'] ) ) {
				die( 0 );
			}
			$comment_datas = json_decode( stripcslashes( $_POST['comment_data'] ), true );
			if ( empty( $comment_datas ) ) {
				$respoArray['status']  = false;
				$respoArray['message'] = 'Nothing to Import';
				echo json_encode( $respoArray );
				die( 0 );
			}
			$commentID       = array();
			$comment_post_ID = $_POST['post_id'];
			$commentstr      = '';
			$project_id      = $_POST['project_id'];
			$task_id         = $_POST['task_id'];
			foreach ( $comment_datas as $comment_data ) {
				$comment_content = $comment_data['body_formatted'];
				global $wpdb;

				$user = get_user_by( 'email', $comment_data['created_by']['email'] );
				if ( $user ) {
					$user_id              = $user->ID;
					$comment_author       = esc_sql( $user->display_name );
					$comment_author_email = esc_sql( $user->user_email );
					$comment_author_url   = esc_sql( $user->user_url );
				} else {
					$comment_author       = esc_sql( $comment_data['created_by']['display_name'] );
					$comment_author_email = esc_sql( $comment_data['created_by']['email'] );
					$comment_author_url   = esc_sql( $comment_data['created_by']['urls']['view'] );
				}
				if ( '' == $comment_content ) {
					$returnArray['status']  = false;
					$returnArray['message'] = 'ERROR: please type a comment.';
					echo json_encode( $returnArray );
					die( 0 );
				}

				$comment_type                   = 'note - AC';
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


				$commentdata['comment_date']     = gmdate( 'Y-m-d H:i:s', ( intval( $comment_data['created_on']['timestamp'] ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
				$commentdata['comment_date_gmt'] = gmdate( 'Y-m-d H:i:s', ( intval( $comment_data['created_on']['timestamp'] ) ) );

				$commentdata['comment_approved'] = 1;
				$lastCommentId                   = wp_insert_comment( $commentdata );
				add_comment_meta( $lastCommentId, '_ac_comment_id', $comment_data['id'] );
				add_comment_meta( $lastCommentId, '_ac_task_id', $task_id );
				add_comment_meta( $lastCommentId, '_ac_project_id', $project_id );
				$commentID[]            = $lastCommentId;
				$comment_attachemnt_str = '';
				if ( $comment_data['attachments_count'] > 0 ) {
					$uploaded = array();
					foreach ( $comment_data['attachments'] as $attachment ) {
						$url      = 'http://ac.rtcamp.com/api.php?path_info=projects/' . $project_id . '/tasks/' . $task_id . '/comments/' . $comment_data['id'] . '/attachments/' . $attachment['id'] . '/download&auth_api_token=' . $_POST['auth_token'] . '&format=json';
						$response = wp_remote_get( $url );
						if ( is_wp_error( $response ) ) {
							continue;
						}
						$uploading = wp_upload_bits( $attachment['name'], null, $response['body'] );
						if ( $uploading['error'] === false ) {
							add_comment_meta( $lastCommentId, 'attachment', $uploading['url'] );
							$extn_array = explode( '.', $attachment['name'] );
							$extn       = $extn_array[ count( $extn_array ) - 1 ];
							$fileName   = $attachment['name'];
							$uploaded[] = array(
								'filename' => $fileName,
								'extn'     => $extn,
								'url'      => $uploading['url'],
								'file'     => $uploading['file'],
							);


							$comment_attachemnt_str .= "<li><a href='" . $uploading['url'] . "' title='Attachment' >";
							$comment_attachemnt_str .= "<img src='" . RT_HD_URL . 'app/assets/file-type/' . $extn . ".png' />";
							$comment_attachemnt_str .= '<span>' . $fileName . '</span></a></li>';
						}
					}
					$this->add_attachment_to_post( $uploaded, $comment_post_ID, get_post_type( $comment_post_ID ) );
				}


				$commentstr .= '<tr class="row"><td class="large-7"><div class="row"><div class="large-2 columns"><a href="#" class="th radius">';
				$commentstr .= get_avatar( $comment_author_email, 50 ) . '</a><label><b>';
				$commentstr .= $comment_author;
				$commentstr .= '</b></label><a class="folowup-hover" href="#editFollowup" title="Edit" data-comment-id="' . $lastCommentId . '">Edit</a>';
				$commentstr .= '<a class="folowup-hover delete" href="#deleteFollowup" title="Delete" data-comment-id="' . $lastCommentId . '">Delete</a>';
				$commentstr .= '</div><div class="large-10 columns comment-content">';
				$commentstr .= $comment_content . '</div></div></td><td class="large-2 centered">';
				$commentstr .= '<label class="comment-type"><b>' . ucfirst( $comment_type ) . '</b></label>';
				$commentstr .= '<div class="row collapse"><p class="comment-date">';
				$commentstr .= $comment_data['created_on']['formatted'];
				$commentstr .= '</p></div></td><td class="large-3">';
				$commentstr .= "<ul class='comment_attechment large-block-grid large-2'>";
				$commentstr .= $comment_attachemnt_str;
				$commentstr .= '  </ul>';

				$commentstr .= '</td></tr>';
			}
			$currentUser = get_user_by( 'id', get_current_user_id() );
			$title       = '[' . count( $comment_datas ) . ' New Follwup Imported]' . get_the_title( $comment_post_ID );
			$body        = count( $comment_datas ) . ' New Follwup Imported by ' . $currentUser->display_name;
			$body .= "<br/> AC Project : <a href='http://ac.rtcamp.com/projects/" . $project_id . '/tasks/' . $task_id . "' > AC Link </a>";
			$body .= '<br/> ';

			$this->notify_subscriber_via_email( $comment_post_ID, $title, $body, 0 );
			$respoArray['status']  = true;
			$respoArray['message'] = 'Done Import';
			$respoArray['data']    = $commentstr;
			echo json_encode( $respoArray );
			die( 0 );
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
			$response['status'] = wp_delete_comment( $_POST['comment_id'], true );
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

			if ( $email_type != 'goauth' ) {
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
			if ( $userid == 0 ) {
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

	}

}
