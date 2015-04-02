<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 9/9/14
 * Time: 7:37 PM
 */

if ( ! class_exists( 'RT_HD_Email_Notification' ) ) {

	/**
	 * Class RT_HD_Email_Notification
	 *
	 * @since  0.1
	 *
	 * @author dipesh
	 */
	class RT_HD_Email_Notification {

		function __construct() {
			add_filter( 'rthd_filter_adult_emails', array( $this, 'rthd_filter_adult_emails' ), 10, 2 );
		}

		function rthd_filter_adult_emails( $emails, $postid ){
			if ( rthd_get_redux_adult_filter() && rthd_get_adult_ticket_meta( $postid ) != 'no' ){
				$new = array();
				foreach ( $emails as $email ){
					if ( ! empty ( $email )  ){
						$user = get_user_by( 'email', $email[ 'email' ] ) ;
						if ( ! $user instanceof WP_Error && ! empty( $user ) && rthd_get_user_adult_preference( $user->ID ) == 'no' ) {
							continue;
						}
						$new[] = $email;
					}
				}
				return $new;
			}
			return $emails;
		}


		/**
		 * @param int $post_id to get link of post
		 * @param string $posttype View {$post_type}
		 *
		 * @return string Body Title
		 */
		public function get_email_title( $post_id, $posttype ){
			return '<div style="font-style:italic;color:#666"><a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a> to view ticket online.</div><br/>';
		}

		/**
		 * Add Notification Email into Queue
		 *
		 * @since 0.1
		 *
		 * @param        $subject
		 * @param        $title
		 * @param        $body
		 * @param array  $toemail
		 * @param array  $ccemail
		 * @param array  $bccemail
		 * @param array  $attachement
		 * @param int    $refrence_id
		 * @param string $refrence_type
		 *
		 * @return mixed
		 */
		public function insert_new_send_email( $subject, $body, $toemail = array(), $ccemail = array(), $bccemail = array(), $attachement = array(), $refrence_id = 0, $refrence_type = 'notification' ) {
			$user_id = get_current_user_id();
			global $rt_outbound_model;

			$settings = rthd_get_redux_settings();
			$attachments = wp_list_pluck( $attachement, 'file' );
			$toemail = $this->filter_user_notification_preference( $toemail );
			$ccemail = $this->filter_user_notification_preference( $ccemail );
			$bccemail = $this->filter_user_notification_preference( $bccemail );
			if ( $this->is_array_empty( $toemail ) && $this->is_array_empty( $ccemail ) && $this->is_array_empty( $bccemail ) ){  // check if all emails are empty do not send email
				return false;
			}
			$args = array(
				'user_id'       => $user_id,
				'fromname'      => $settings['rthd_outgoing_email_from_name'],
				'fromemail'     => $settings['rthd_outgoing_email_mailbox'],
				'toemail'       => serialize( $toemail ),
				'ccemail'       => serialize( $ccemail ),
				'bccemail'      => serialize( $bccemail ),
				'subject'       => $subject,
				'body'          => ( $body ),
				'attachement'   => serialize( $attachments ),
				'refrence_id'   => $refrence_id,
				'refrence_type' => $refrence_type,
			);
			if ( rtmb_get_module_mailbox_email( $settings['rthd_outgoing_email_mailbox'], RT_HD_TEXT_DOMAIN ) != false ) {
				// send from mailbox
				return $rt_outbound_model->add_outbound_mail( $args );
			} else {
				$id = $rt_outbound_model->add_outbound_mail( $args );
				$sendflag = $this->send_wp_email( $args );
				if ( $sendflag ){
					$rt_outbound_model->update_outbound_mail( array( 'sent' => 'yes' ), array( 'id' => $id ) );
				}
				return $sendflag;
			}
		}

		function filter_user_notification_preference( $emails ) {
			$email_ids = wp_list_pluck( $emails, 'email' );

			foreach ( $email_ids as $email_id ) {
				$user = get_user_by( 'email', $email_id );
				if ( false != $user && ! $user instanceof WP_Error ) {
					$user_pref = rthd_get_user_notification_preference( $user->ID );
					if (  'yes' != $user_pref ) { // if sets no
						unset( $emails[ array_search( $email_id, $email_ids ) ] ); // Remove from the list who does not want.
					}
				}
			}
			return $emails;
		}

		/**
		 * send email using wp email
		 *
		 * @param $args
		 * @return bool
		 */
		public function send_wp_email( $args ) {
			$arrayBCC  = unserialize( $args['bccemail'] );
			$arrayCC   = unserialize( $args['ccemail'] );
			$arrayTo   = unserialize( $args['toemail'] );
			$attachments = unserialize( $args['attachement'] );
			$blog_title = get_bloginfo();
			$headers[] = 'From: ' . ( ( ! empty( $args['fromname'] ) ) ? $args['fromname'] : $blog_title ) . ' <' . $args['fromemail'] . '>';
			add_filter( 'wp_mail_from', 'rthd_my_mail_from' );
			$emailsendflag = true;
			if ( ! empty( $arrayBCC ) ) {
				foreach ( $arrayBCC as $temail ) {
					add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					$res = wp_mail( array( $temail['email'] ), $args['subject'], $args['body'], $headers, $attachments );
					remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					if ( ! $res ) {
						$emailsendflag = false;
					}
				}
			}

			if ( ! empty( $arrayCC ) ) {
				foreach ( $arrayCC as $tomail ) {
					add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					$res = wp_mail( array( $tomail ['email'] ), $args['subject'], $args['body'], $headers, $attachments );
					remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					if ( ! $res ) {
						$emailsendflag = false;
					}
				}
			}

			if ( ! empty( $arrayTo ) ) {
				foreach ( $arrayTo as $key => $temail ) {
					add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					$res = wp_mail( array( $temail['email'] ), $args['subject'], $args['body'], $headers, $attachments );
					remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
					if ( ! $res ) {
						$emailsendflag = false;
					}
				}
			}
			remove_filter( 'wp_mail_from', 'rthd_my_mail_from' );
			return $emailsendflag;
		}

		/**
		 * @param $comment Object
		 * @param $comment_privacy int/string
		 * @param $uploaded array
		 */
		public function notification_new_followup_added( $comment, $comment_privacy, $uploaded ){
			$followup_creator = $ContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = $bccemails = array();

			$redux = rthd_get_redux_settings();
			if ( $comment_privacy != Rt_HD_Import_Operation::$FOLLOWUP_STAFF ){
				$notificationFlagClient = ( isset( $redux['rthd_notification_acl_client_events']) && $redux['rthd_notification_acl_client_events']['new_followup_created_mail'] == 1 );
				$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_followup_created_mail'] == 1 );
				$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_followup_created_mail'] == 1 );
				$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_followup_created_mail'] == 1 );
			} else {
				$notificationFlagClient = false;
				$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_staff_only_followup_created_mail'] == 1 );
				$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_staff_only_followup_created_mail'] == 1 );
				$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_staff_only_followup_created_mail'] == 1 );
			}

			$followup_creator[] = array( 'email' => $comment->comment_author_email, 'name' => $comment->comment_author );

			if ( $notificationFlagAssignee ){
				$assigneEmail[] = $this->get_assigne_email( $comment->comment_post_ID );
				$bccemails = array_merge( $bccemails, $assigneEmail );
			}

			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
				$bccemails = array_merge( $bccemails, $groupEmail );
			}

			if ( $notificationFlagSsubscriber ) {
				$subscriberEmail = $this->get_subscriber($comment->comment_post_ID);
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			$bccemails = $this->exclude_author( $bccemails, $comment->comment_author_email );
			$bccemails  = apply_filters( 'rthd_filter_adult_emails', $bccemails, $comment->comment_post_ID );

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
				$ContactEmail  = apply_filters( 'rthd_filter_adult_emails', $ContactEmail, $comment->comment_post_ID );
			}

			if ( isset( $comment_privacy ) && ! empty( $comment_privacy ) && intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ){
				// Filter for email when follow up is added as private
				$subject = rthd_create_new_ticket_title( 'rthd_new_followup_email_title_private', $comment->comment_post_ID );
				$body = apply_filters( 'rthd_email_template_followup_add_private', rthd_get_email_template_body( 'rthd_email_template_followup_add_private' ) );
				$uploaded = array();
			} else {
				$subject = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment->comment_post_ID );
				$body = apply_filters( 'rthd_email_template_followup_add', rthd_get_email_template_body('rthd_email_template_followup_add') );
			}
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );
			// replace followup_content placeholder with content
			$body = rthd_replace_placeholder( $body, '{followup_content}', rthd_content_filter( $comment->comment_content ) );
			// sending email to followup author [ To ]
			$toBody = rthd_replace_placeholder( $body,'{followup_author}', 'you' );
			$this->insert_new_send_email( $subject, rthd_get_general_body_template( $toBody, $title, true ), $followup_creator, array(), array(), $uploaded, $comment->comment_ID , 'comment' );

			//sending email to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$toBody = rthd_replace_placeholder( $body,'{followup_author}', $comment->comment_author );
			$this->insert_new_send_email( $subject, rthd_get_general_body_template( $toBody, $title, true ), $ContactEmail , array(), $bccemails, $uploaded, $comment->comment_ID , 'comment' );
		}


		/**
		 * This will handle all notification for deleting followup
		 * @param $comment Object that is being deleted
		 * @param $user_id int user id who deleted comment
		 */
		public function notification_followup_deleted( $comment, $user_id ){
			$followup_deletor = $ContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = $bccemails = array();

			$user = get_user_by( 'id', $user_id );

			$redux = rthd_get_redux_settings();
			$notificationFlagClient = ( isset( $redux['rthd_notification_acl_client_events']) && $redux['rthd_notification_acl_client_events']['new_followup_deleted_mail'] == 1 );
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_followup_deleted_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_followup_deleted_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_followup_deleted_mail'] == 1 );


			$followup_deletor[] = array( 'email' => $comment->comment_author_email, 'name' => $comment->comment_author );

			if ( $notificationFlagAssignee ){
				$assigneEmail[] = $this->get_assigne_email( $comment->comment_post_ID );
				$bccemails = array_merge( $bccemails, $assigneEmail );
			}

			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
				$bccemails = array_merge( $bccemails, $groupEmail );
			}

			if ( $notificationFlagSsubscriber ) {
				$subscriberEmail = $this->get_subscriber($comment->comment_post_ID);
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			$bccemails = $this->exclude_author( $bccemails, $comment->comment_author_email );
			$bccemails = apply_filters( 'rthd_filter_adult_emails', $bccemails , $comment->comment_post_ID );

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
				$ContactEmail = apply_filters( 'rthd_filter_adult_emails', $ContactEmail , $comment->comment_post_ID );
			}

			if ( ! ( isset( $comment->comment_type ) && ! empty( $comment->comment_type ) && intval( $comment->comment_type ) && $comment->comment_type > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ) ) {
				$body = apply_filters( 'rthd_email_template_followup_deleted', rthd_get_email_template_body('rthd_email_template_followup_deleted' ) );
				$subject = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title', $comment->comment_post_ID );
			} else {
				$body = apply_filters( 'rthd_email_template_followup_deleted_private', rthd_get_email_template_body('rthd_email_template_followup_deleted_private' ) );
				$subject = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title_private', $comment->comment_post_ID );
			}
			$body = rthd_replace_placeholder( $body, '{followup_content}', rthd_content_filter( $comment->comment_content ) );

			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );

			// sending email to followup author [ To ]
			if ( user_can( $user, rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ){
				$bodyto = $toBody = rthd_replace_placeholder( $body,'{followup_deleted_by}', 'you' );
				$this->insert_new_send_email( $subject, rthd_get_general_body_template( $bodyto, $title, true ), $followup_deletor, array(), array(), array(), $comment->comment_ID , 'comment' );
			}

			//sending emial to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$bodyto = rthd_replace_placeholder( $body,'{followup_deleted_by}', $comment->comment_author );
			$this->insert_new_send_email( $subject, rthd_get_general_body_template( $bodyto, $title, true ), $ContactEmail, array(), $bccemails, array(), $comment->comment_ID , 'comment' );
		}

		/**
		 * @param $comment object new comment object
		 * @param $user_id int user who edited comment
		 * @param $old_privacy int old privacy of followup
		 * @param $new_privacy int new privacy of followup
		 * @param $old_content string old string comment content
		 * @param $new_content string new comment content
		 */
		public function notification_followup_updated( $comment , $user_id, $old_privacy, $new_privacy, $old_content, $new_content ){
			$followup_updater = $ContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = $bccemails = array();

			$user = get_user_by( 'id', $user_id );

			$redux = rthd_get_redux_settings();
			$notificationFlagClient = ( isset( $redux['rthd_notification_acl_client_events']) && $redux['rthd_notification_acl_client_events']['new_followup_updated_mail'] == 1 );
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_followup_updated_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_followup_updated_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_followup_updated_mail'] == 1 );

			// find if followup was private before or right now it is private
			$private_update = false;
			if ( ( intval( $old_privacy ) && $old_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) || ( intval( $new_privacy ) && $new_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) ){
				$private_update = true;
			}

			$followup_updater[] = array( 'email' => $comment->comment_author_email, 'name' => $comment->comment_author );

			if ( $notificationFlagAssignee ){
				$assigneEmail[] = $this->get_assigne_email( $comment->comment_post_ID );
				$bccemails = array_merge( $bccemails, $assigneEmail );
			}

			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
				$bccemails = array_merge( $bccemails, $groupEmail );
			}

			if ( $notificationFlagSsubscriber ) {
				$subscriberEmail = $this->get_subscriber($comment->comment_post_ID);
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			$bccemails = $this->exclude_author( $bccemails, $comment->comment_author_email );
			$bccemails = apply_filters( 'rthd_filter_adult_emails', $bccemails , $comment->comment_post_ID );

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
				$ContactEmail = apply_filters( 'rthd_filter_adult_emails', $ContactEmail, $comment->comment_post_ID );
			}

			if ( $private_update ){
				$body =  apply_filters( 'rthd_email_template_followup_updated_private', rthd_get_email_template_body('rthd_email_template_followup_updated_private' ) );
				$subject = rthd_create_new_ticket_title( 'rthd_update_followup_email_title_private', $comment->comment_post_ID );
			} else {
				$body =  apply_filters( 'rthd_email_template_followup_updated', rthd_get_email_template_body('rthd_email_template_followup_updated' ) );
				$subject = rthd_create_new_ticket_title( 'rthd_update_followup_email_title', $comment->comment_post_ID );
			}
			$diff_visibility = rthd_text_diff( rthd_get_comment_type( $old_privacy ), rthd_get_comment_type( $new_privacy ) );
			$diff_followup_content = rthd_text_diff( trim( html_entity_decode( strip_tags( $old_content ) ) ), trim( html_entity_decode( strip_tags( $new_content ) ) ) );

			if ( $diff_visibility ){
				$body = rthd_replace_placeholder( $body, '{visibility_diff}', '<br/><b>Visibility : </b><hr style="color: #DCEAF5;" />' . $diff_visibility );
			} else{
				$body = rthd_replace_placeholder( $body, '{visibility_diff}', '' );
			}
			if ( ! $private_update ){ // not private then add diff content if exists or add actual content if no diff
				if ($diff_followup_content){
					$body = rthd_replace_placeholder( $body, '{followup_diff}', '<br/><b>Followup Content : </b><hr style="color: #DCEAF5;" />' . $diff_followup_content );
				}
				else{
					$body = rthd_replace_placeholder( $body, '{followup_diff}', '<br/><b>Followup Content : </b><hr style="color: #DCEAF5;" /><div  style="display: inline-block">' . rthd_content_filter( $comment->comment_content ) . '</div>');
				}
			}
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );

			// sending email to followup author [ To ]
			if ( user_can( $user, rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ) {
				$bodyto = rthd_replace_placeholder($body,'{followup_updated_by}', 'you');
				$this->insert_new_send_email($subject, rthd_get_general_body_template($bodyto, $title, true ), $followup_updater, array(), array(), array(), $comment->comment_ID, 'comment' );
			}

			//sending email to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$bodyto = rthd_replace_placeholder( $body,'{followup_updated_by}', $user->display_name );
			$this->insert_new_send_email( $subject, rthd_get_general_body_template( $bodyto, $title,  true ), $ContactEmail, array(), $bccemails, array(), $comment->comment_ID , 'comment'  );

		}

		/**
		 * @param $InputVariable array input multi dimensional array
		 * Check if mult level array is empty or not
		 * @return bool
		 */
		function is_array_empty( $InputVariable ) {
			$Result = true;

			if ( is_array( $InputVariable ) && count( $InputVariable ) > 0 ) {
				foreach ($InputVariable as $Value) {
					$Result = $Result && $this->is_array_empty($Value);
				}
			}
			else {
				$Result = empty($InputVariable);
			}

			return $Result;
		}


		/**
		 * @param $array array array of email
		 * @param $email string email to exclude from email
		 *
		 * @return array
		 */
		function exclude_author( $array ,$email ){
			$new = array();

			if ( ! empty( $array ) ){
				foreach ( $array as $arr ) {
					if ( ! empty ( $arr ) ){
						if ( $arr['email'] != $email ) {
							$new[] = $arr;
						}
					}
				}
			}
			return $new;
		}
		/**
		 * Notification while create ticket
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $uploaded
		 */
		public function notification_new_ticket_created( $post_id, $post_type, $body = '', $uploaded = array() ) {
			$ticket_creator = $ContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = array();

			$redux = rthd_get_redux_settings();
			$notificationFlagClient = ( isset( $redux['rthd_notification_acl_client_events']) && $redux['rthd_notification_acl_client_events']['new_ticket_created_mail'] == 1 );
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_ticket_created_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_ticket_created_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_ticket_created_mail'] == 1 );

			$user = get_post_meta( $post_id, '_rtbiz_hd_created_by', true );
			$ticket_created_by = get_user_by( 'id', $user );
			$post = get_post( $post_id );
			$assigne_user = get_user_by( 'id', $post->post_author );

			$ticket_creator[] = array( 'email' => $ticket_created_by->user_email );

			$assigneEmail[] = $this->get_assigne_email( $post_id );
			$assigneEmail = $this->exclude_author( $assigneEmail, $ticket_created_by->user_email );
			$assigneEmail = apply_filters( 'rthd_filter_adult_emails', $assigneEmail, $post_id );


			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
			}
			$groupEmail = $this->exclude_author( $groupEmail, $ticket_created_by->user_email );
			$groupEmail = apply_filters( 'rthd_filter_adult_emails', $groupEmail, $post_id );

			$subscriberEmail = $this->get_subscriber( $post_id );
			$subscriberEmail = $this->exclude_author( $subscriberEmail, $ticket_created_by->user_email );
			$subscriberEmail = apply_filters( 'rthd_filter_adult_emails', $subscriberEmail, $post_id );

			$ContactEmail  = $this->get_contacts( $post_id );
			$ContactEmail = $this->exclude_author( $ContactEmail, $ticket_created_by->user_email );
			$ContactEmail = apply_filters( 'rthd_filter_adult_emails', $ContactEmail, $post_id );

			$produc_list = wp_get_object_terms( $post_id, Rt_Offerings::$offering_slug );
			$arrProducts = array_unique( wp_list_pluck( $produc_list, 'name' ) );
			$arrProducts = implode( ', ', $arrProducts );

			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title', $post_id );

			// Ticket creator [ To ] Notification
			if ( ! empty( $ticket_creator ) && $notificationFlagClient ) {
				//rthd_email_template_new_ticket_created_author
				$htmlbody =  apply_filters( 'rthd_email_template_new_ticket_created_author', rthd_get_email_template_body('rthd_email_template_new_ticket_created_author' ) );
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>' );
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '' );
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title, true );

				$this->insert_new_send_email( $subject, $htmlbody, $ticket_creator, array(), array(), $uploaded, $post_id, 'post' );
			}

			// Conatcts [ TO ] Notification
			if ( ! empty( $ContactEmail ) && $notificationFlagClient ){
				$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title_contacts', $post_id );

				$htmlbody =  apply_filters( 'rthd_email_template_new_ticket_created_contacts', rthd_get_email_template_body('rthd_email_template_new_ticket_created_contacts' ) );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_author}', $ticket_created_by->display_name );
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>' );
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '' );
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title, true );
				$this->insert_new_send_email( $subject, $htmlbody, $ContactEmail, array(), array(), $uploaded, $post_id, 'post' );
			}

			// Group [ BCC ] Notification
			if ( ! empty( $groupEmail ) && $notificationFlagGroup ){
				$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title_group', $post_id );

				$htmlbody =  apply_filters( 'rthd_email_template_new_ticket_created_group_notification', rthd_get_email_template_body('rthd_email_template_new_ticket_created_group_notification' ) );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_author}', $ticket_created_by->display_name );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_assignee}', $assigne_user->display_name );

				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', "<p><b>Product: </b>" . $arrProducts . '</p> <br />');
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', '' );
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>' );
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '' );
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title, true );
				$this->insert_new_send_email( $subject, $htmlbody, $groupEmail, array(), array() , $uploaded, $post_id, 'post' );
			}

			// Assignee [ To ] Notification
			if ( ! empty( $assigneEmail ) && $notificationFlagAssignee ){
				$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title_assignee', $post_id );
				$htmlbody =  apply_filters( 'rthd_email_template_new_ticket_created_assignee', rthd_get_email_template_body('rthd_email_template_new_ticket_created_assignee' ) );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_author}', $ticket_created_by->display_name );

				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', "<p><b>Product: </b>" . $arrProducts . '</p> <br />');
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', '' );
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>' );
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '' );
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title, true );
				$this->insert_new_send_email( $subject, $htmlbody, $assigneEmail, array(), array() , $uploaded, $post_id, 'post' );
			}

			// Subscrible [ BCC ] Notification
			if ( ! empty( $subscriberEmail ) && $notificationFlagSsubscriber ){
				// A new support ticket is created by [CREATOR CONTACT NAME]. You have been subscribed to this ticket.
				$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title_subscriber', $post_id );
				$htmlbody =  apply_filters( 'rthd_email_template_new_ticket_created_subscriber', rthd_get_email_template_body('rthd_email_template_new_ticket_created_subscriber' ) );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_author}', $ticket_created_by->display_name );
				$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_assignee}', $assigne_user->display_name );

				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', "<p><b>Product: </b>" . $arrProducts . '</p> <br />');
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_offerings}', '' );
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>' );
				} else {
					$htmlbody = rthd_replace_placeholder( $htmlbody,'{ticket_body}', '' );
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title, true );
				$this->insert_new_send_email( $subject, $htmlbody, $subscriberEmail, array(), array() , $uploaded, $post_id, 'post' );
			}

		}

		/**
		 * Send Notification to subscribed
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $newSubscriberList
		 */
		public function notification_ticket_subscribed( $post_id, $post_type, $newSubscriberList ) {
			$bccemails = $groupEmail = $assigneEmail = $subscriberEmail = array();

			$redux = rthd_get_redux_settings();
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['new_subscriber_added_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['new_subscriber_added_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['new_subscriber_added_mail'] == 1 );

			if ( $notificationFlagGroup ) {
				$groupEmail = $this->get_notification_emails();
				$bccemails = array_merge($bccemails, $groupEmail);
			}

			if ( $notificationFlagAssignee ) {
				$assigneEmail[] = $this->get_assigne_email( $post_id );
			}

			if ( $notificationFlagSsubscriber ){
				$subscriberEmail = $this->get_subscriber( $post_id );
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			foreach ( $newSubscriberList as $user ){
				$bccemails = $this->exclude_author( $bccemails, $user['email'] );
				$assigneEmail = $this->exclude_author( $assigneEmail, $user['email'] );
				$bccemails = apply_filters( 'rthd_filter_adult_emails', $bccemails, $post_id );
				$assigneEmail = apply_filters( 'rthd_filter_adult_emails', $assigneEmail, $post_id );
			}

			// New subscriber added Notification to subscriber
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_ticket_subscribe_email_title', $post_id );
			//rthd_email_template_ticket_subscribed
			$htmlbody_subscriber =  apply_filters( 'rthd_email_template_ticket_subscribed', rthd_get_email_template_body('rthd_email_template_ticket_subscribed' ) );
			$htmlbody = rthd_replace_placeholder( $htmlbody_subscriber,'{ticket_subscribers}', 'You have ' );
			$htmlbody = rthd_get_general_body_template( $htmlbody, $title );
			$this->insert_new_send_email( $subject, $htmlbody, $newSubscriberList, array(), array(), array(), $post_id, 'post' );

			// Assignee [ To ] | group [ BCC ] notification for subscriber added
			$subscribers = '';
			foreach ( $newSubscriberList as $user ){
				$subscribers .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
			}
			$subscribers = trim( $subscribers, ' ' );
			$subscribers .= ( count( $newSubscriberList ) >= 2 ) ? ' have ' : ' has ' ;
			$htmlbody = rthd_replace_placeholder( $htmlbody_subscriber,'{ticket_subscribers}', $subscribers );
			$htmlbody = rthd_get_general_body_template( $htmlbody, $title );

			$this->insert_new_send_email( $subject, $htmlbody, $assigneEmail, array(), $bccemails, array(), $post_id, 'post' );
		}

		/**
		 * Send Notification to unsubscribed
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $oldSubscriberList
		 */
		public function notification_ticket_unsubscribed( $post_id, $post_type, $oldSubscriberList  ) {
			$bccemails = $groupEmail = $assigneEmail = $subscriberEmail = array();

			$redux = rthd_get_redux_settings();
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['subscriber_removed_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['subscriber_removed_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['subscriber_removed_mail'] == 1 );

			if ( $notificationFlagGroup ) {
				$groupEmail = $this->get_notification_emails();
				$bccemails = array_merge($bccemails, $groupEmail);
			}

			if ( $notificationFlagAssignee ) {
				$assigneEmail[] = $this->get_assigne_email( $post_id );
			}

			if ( $notificationFlagSsubscriber ){
				$subscriberEmail = $this->get_subscriber( $post_id );
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			foreach ( $oldSubscriberList as $user ){
				$bccemails = $this->exclude_author( $bccemails, $user['email'] );
				$assigneEmail = $this->exclude_author( $assigneEmail, $user['email'] );
				$bccemails = apply_filters( 'rthd_filter_adult_emails', $bccemails, $post_id );
				$assigneEmail = apply_filters( 'rthd_filter_adult_emails', $assigneEmail, $post_id );

			}

			//subscriber removed Notification
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_ticket_unsubscribe_email_title', $post_id );

			$htmlbody = '';
			foreach ( $oldSubscriberList as $user ){
				$htmlbody .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
			}
			$htmlbody = trim( $htmlbody, ',' );
			$htmlbody = trim( $htmlbody, ' ' );
			$htmlbody .= ( count( $oldSubscriberList ) >= 2 ) ? ' have' : '  has' ;

			$htmlbody_unsubscriber =  apply_filters( 'rthd_email_template_ticket_unsubscribed', rthd_get_email_template_body( 'rthd_email_template_ticket_unsubscribed' ) );
			$htmlbody = rthd_replace_placeholder( $htmlbody_unsubscriber,'{ticket_unsubscribers}', $htmlbody );
			$htmlbody = rthd_get_general_body_template( $htmlbody, $title );

			$this->insert_new_send_email( $subject, $htmlbody, $assigneEmail, array(), $bccemails, array(), $post_id, 'post' );
		}

		/**
		 * Send Notification to reassigned event
		 *
		 * @param $post_id
		 * @param $oldassignee
		 * @param $assignee
		 * @param $post_type
		 */
		public function notification_new_ticket_reassigned( $post_id, $oldassignee, $assignee, $post_type ) {
			$bccemails = $newassigneEmail = $oldassigneEmail = $groupEmail = $subscriberEmail = array();

			$redux = rthd_get_redux_settings();
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['ticket_reassigned_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['ticket_reassigned_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['ticket_reassigned_mail'] == 1 );

			if ( $notificationFlagGroup ) {
				$groupEmail = $this->get_notification_emails();
				$bccemails = array_merge($bccemails, $groupEmail);
			}

			if ( $notificationFlagSsubscriber ){
				$subscriberEmail = $this->get_subscriber( $post_id );
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}

			if ( ! empty( $oldassignee ) ){
				$oldUser  = get_user_by( 'id', $oldassignee );
				if ( ! empty( $oldUser->user_email ) ) {
					$oldassigneEmail[]= array( 'email' => $oldUser->user_email, );
				}
			}

			$newUser  = get_user_by( 'id', $assignee );
			if ( ! empty( $newUser->user_email ) ) {
				$newassigneEmail[]= array( 'email' => $newUser->user_email, );
			}

			$title = $this->get_email_title( $post_id, $post_type );

			// old assignee [ To ] mail Notification
			if ( ! empty( $oldassigneEmail ) && $notificationFlagAssignee ){
				$subject = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title_old_assignee', $post_id );
				$htmlbody =  apply_filters( 'rthd_email_template_ticket_reassigned_old_assignee', rthd_get_email_template_body( 'rthd_email_template_ticket_reassigned_old_assignee' ) );
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title );
				$this->insert_new_send_email( $subject, $htmlbody, $oldassigneEmail, array(), array(), array(), $post_id, 'post' );
			}
			$htmlbody_reassiged =  apply_filters( 'rthd_email_template_ticket_reassigned_new_assignee', rthd_get_email_template_body( 'rthd_email_template_ticket_reassigned_new_assignee' ) );
			$subject = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title', $post_id );

			// new assignee [ To ] mail Notification
			if ( !empty( $newassigneEmail ) && $notificationFlagAssignee ){
				$htmlbody = rthd_replace_placeholder( $htmlbody_reassiged,'{new_ticket_assignee}', 'you' );
				$htmlbody = rthd_get_general_body_template( $htmlbody, $title );
				$this->insert_new_send_email( $subject, $htmlbody, $newassigneEmail, array(), array(), array(), $post_id, 'post' );
			}

			// group [ BCC ] mail Notification
			$htmlbody = rthd_replace_placeholder( $htmlbody_reassiged,'{new_ticket_assignee}', $newUser->display_name . ' ('. $newUser->user_email .')');
			$htmlbody = rthd_get_general_body_template( $htmlbody, $title );
			$this->insert_new_send_email( $subject, $htmlbody, array(), array(), $bccemails, array(), $post_id, 'post' );
		}

		/**
		 * Send Notification on update ticket
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $bccemails
		 */
		public function notification_ticket_updated( $post_id, $post_type, $body, $bccemails ) {
			$ticket_updator = $ContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = array();

			$redux = rthd_get_redux_settings();
			$notificationFlagAssignee = ( isset( $redux['rthd_notification_acl_assignee_events']) && $redux['rthd_notification_acl_assignee_events']['ticket_reassigned_mail'] == 1 );
			$notificationFlagSsubscriber = ( isset( $redux['rthd_notification_acl_staff_events']) && $redux['rthd_notification_acl_staff_events']['ticket_reassigned_mail'] == 1 );
			$notificationFlagGroup = ( isset( $redux['rthd_notification_acl_group_events']) && $redux['rthd_notification_acl_group_events']['ticket_reassigned_mail'] == 1 );

			$user = get_post_meta( $post_id, '_rtbiz_hd_updated_by', true );
			$ticket_update_by = get_user_by( 'id', $user );
			$ticket_updator[] = array( 'email' => $ticket_update_by->user_email );


			if ( $notificationFlagAssignee ){
				$assigneEmail[] = $this->get_assigne_email( $post_id );
				$assigneEmail = $this->exclude_author( $assigneEmail, $ticket_update_by->user_email );
				$assigneEmail = apply_filters( 'rthd_filter_adult_emails', $assigneEmail, $post_id );
			}

			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
				$bccemails = array_merge( $bccemails, $groupEmail );
			}

			if ( $notificationFlagSsubscriber ) {
				$subscriberEmail = $this->get_subscriber( $post_id );
				$bccemails = array_merge($bccemails, $subscriberEmail);
			}
			$bccemails = $this->exclude_author( $bccemails, $ticket_update_by->user_email );
			$bccemails = apply_filters( 'rthd_filter_adult_emails', $bccemails, $post_id );

			$subject = rthd_create_new_ticket_title( 'rthd_update_ticket_email_title', $post_id );
			$title = $this->get_email_title( $post_id, $post_type );
			$user = get_post_meta( $post_id, '_rtbiz_hd_updated_by', true );
			$ticket_update_user = get_user_by( 'id', $user );
			$body = '<br />' . 'Ticket updated by : <strong>' . $ticket_update_user->display_name . '</strong><br/>'. $body;
			$this->insert_new_send_email( $subject, rthd_get_general_body_template( $body, $title, true ) , $assigneEmail, array(), $bccemails, array(), $post_id, 'post' );
		}

		function get_notification_emails() {
			global $redux_helpdesk_settings;
			$cc = array();
			if ( isset( $redux_helpdesk_settings['rthd_notification_emails'] ) ) {
				foreach ( $redux_helpdesk_settings['rthd_notification_emails'] as $email ) {
					array_push( $cc, array( 'email' => $email ) );
				}
			}
			return $cc;
		}

		function sort_emails( $emails ){
			$sortedEmail = array();
			$sortedEmail['client'] = array();
			$sortedEmail['internal'] = array();
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			foreach ( $emails as $email ){
				$user     = get_user_by( 'email', $email );
				if ( user_can( $user, $cap)){
					$sortedEmail['internal'][] = $email;
				}
				else{
					$sortedEmail['client'][] = $email;
				}
			}
			return $sortedEmail;
		}

		function is_internal_user( $email ){
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$user     = get_user_by( 'email', $email );
			if ( user_can( $user, $cap ) ){
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * @param $post_id
		 * get post subscriber
		 * @return array
		 */
		public function get_subscriber( $post_id ){
			$bccemails = array();
			$oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );
			if ( $oldSubscriberArr && is_array( $oldSubscriberArr ) && ! empty( $oldSubscriberArr ) ) {
				foreach ( $oldSubscriberArr as $emailsubscriber ) {
					$userSub = get_user_by( 'id', intval( $emailsubscriber ) );
					if ( ! empty( $userSub ) ) {
						$bccemails[ ]  = array( 'email' => $userSub->user_email );
					}
				}
			}
			return $bccemails;
		}


		/**
		 * @param $post_id
		 * get post contacts
		 * @return array
		 */
		public function get_contacts( $post_id ){
			$tocontact      = array();
			$contacts = rt_biz_get_post_for_contact_connection( $post_id, Rt_HD_Module::$post_type );
			foreach ( $contacts as $contact ) {
				$emails = get_post_meta( $contact->ID, Rt_Entity::$meta_key_prefix.Rt_Contact::$primary_email_key );
				foreach ( $emails as $email ) {
					array_push( $tocontact, array( 'email' => $email ) );
				}
			}
			return $tocontact;
		}

		/**
		 * @param $post_id
		 * get post assigne email
		 * @return array
		 */
		public function get_assigne_email( $post_id ){
			$post_author_id = get_post_field( 'post_author', $post_id );
			$userSub     = get_user_by( 'id', intval( $post_author_id ) );
			return  array( 'email' => $userSub->user_email );
		}

	}
}
