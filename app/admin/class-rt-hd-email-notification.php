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
		 * @param bool   $reply_above_line
		 *
		 * @return mixed
		 */
		public function insert_new_send_email( $subject, $title, $body, $toemail = array(), $ccemail = array(), $bccemail = array(), $attachement = array(), $refrence_id = 0, $refrence_type = 'notification', $reply_above_line = false ) {
			$user_id = get_current_user_id();
			global $rt_outbound_model;

			$beforeHTML = apply_filters( 'rthd_before_email_body', $body );
			$afterHTML = apply_filters( 'rthd_after_email_body', $body );

			if ( ! has_filter( 'rthd_before_email_body' ) ) {
				$beforeHTML = '';
			}
			if ( ! has_filter( 'rthd_after_email_body' ) ) {
				$afterHTML = '';
			}
			$reply_txt = '';
			if ( $reply_above_line && rthd_is_enable_mailbox_reading() ){
				$reply_txt = '<div style="color:#777">'.htmlentities('[!-------REPLY ABOVE THIS LINE-------!]').'</div><br /> ';
			}

			$htmlbody = $title . $beforeHTML . $body . $afterHTML;
			$htmlbody = $reply_txt . $htmlbody;
			$settings = rthd_get_redux_settings();
			$attachments = wp_list_pluck( $attachement, 'file' );
			$toemail = $this->filter_user_notification_preference( $toemail );
			$ccemail = $this->filter_user_notification_preference( $ccemail );
			$bccemail = $this->filter_user_notification_preference( $bccemail );
			if ( $this->is_array_empty( $toemail ) && $this->is_array_empty( $ccemail ) && $this->is_array_empty( $bccemail ) ){  // check if all emails are empty do not send email
				return false;
			}
			$signature = rthd_get_email_signature_settings();
			$args = array(
				'user_id'       => $user_id,
				'fromname'      => $settings['rthd_outgoing_email_from_name'],
				'fromemail'     => ( rthd_is_mailbox_configured() ) ?  $settings['rthd_outgoing_email_mailbox'] : $settings['rthd_outgoing_email_from_address'],
				'toemail'       => serialize( $toemail ),
				'ccemail'       => serialize( $ccemail ),
				'bccemail'      => serialize( $bccemail ),
				'subject'       => $subject,
				'body'          => ( $htmlbody ) . '<br/>' . ( ( ! empty( $signature ) ) ? '<div style="color:#666;">' . wpautop( $signature ) . '</div>' : '' ) . '<br/>' ,
				'attachement'   => serialize( $attachments ),
				'refrence_id'   => $refrence_id,
				'refrence_type' => $refrence_type,
			);
			if ( $this->is_wp_email( ) ) {
				$id = $rt_outbound_model->add_outbound_mail( $args );
				$sendflag = $this->send_wp_email( $args );
				if ( $sendflag ){
					$rt_outbound_model->update_outbound_mail( array( 'sent' => 'yes' ), array( 'id' => $id ) );
				}
				return $sendflag;
			} else {
				return $rt_outbound_model->add_outbound_mail( $args );
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
		 * check if user have selected wp_mail for sending email
		 * @return bool
		 */
		public function is_wp_email() {
			$flag = rthd_is_mailbox_configured();
			return ( ! $flag );
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

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
			}

			if ( isset( $comment_privacy ) && ! empty( $comment_privacy ) && intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ){
				$body = '<br /> A private followup has been added by <strong>{comment_author}</strong>. Please go to ticket to view content.';
				$uploaded = array();
			} else {
				$body = 'New Followup Added by <strong>{comment_author}</strong>';
				if ( ! empty( $comment->comment_content ) ){
					$body .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $comment->comment_content ) . '</div>';
				}
			}
			$subject = rthd_create_new_ticket_title( 'rthd_new_followup_email_title', $comment->comment_post_ID );
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );

			// sending email to followup author [ To ]
			$toBody = rthd_replace_followup_placeholder( $body, 'you' );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $toBody ), $followup_creator, array(), array(), $uploaded, $comment->comment_ID , 'comment', true );

			//sending email to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$toBody = rthd_replace_followup_placeholder( $body, $comment->comment_author );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $toBody ), $ContactEmail , array(), $bccemails, $uploaded, $comment->comment_ID , 'comment', true );
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

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
			}

			$body  = 'A Follwup is deleted by <Strong>{comment_author}</Strong>';
			if ( ! ( isset( $comment->comment_type ) && ! empty( $comment->comment_type ) && intval( $comment->comment_type ) && $comment->comment_type > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ) ) {
				$body .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $comment->comment_content ) . '</div>';
			}

			$subject = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title', $comment->comment_post_ID );
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );

			// sending email to followup author [ To ]
			if ( user_can( $user, rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ){
				$bodyto = rthd_replace_followup_placeholder( $body, 'you' );
				$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), $followup_deletor, array(), array(), array(), $comment->comment_ID , 'comment', true );
			}

			//sending emial to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$bodyto = rthd_replace_followup_placeholder( $body, $comment->comment_author );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), $ContactEmail, array(), $bccemails, array(), $comment->comment_ID , 'comment', true );
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

			if ( $notificationFlagClient ){
				$ContactEmail  = $this->get_contacts( $comment->comment_post_ID );
				$ContactEmail = $this->exclude_author( $ContactEmail, $comment->comment_author_email );
			}

			if ( $private_update ){
				$body = '<div><br /> A <strong>private</strong> followup has been edited by <strong>{comment_author}</strong>. Please go to ticket to view content.</div>';
			} else {
				$body          = '<div> A Follwup Updated by <strong>{comment_author}.</strong></div>';
				$body         .= '<br/><div> The changes are as follows: </div><br/>';
			}
			$diff_visibility = rthd_text_diff( rthd_get_comment_type( $old_privacy ), rthd_get_comment_type( $new_privacy ) );
			$diff_followup_content = rthd_text_diff( trim( html_entity_decode( strip_tags( $old_content ) ) ), trim( html_entity_decode( strip_tags( $new_content ) ) ) );

			if ( $diff_visibility ){
				$body.= '<br/><b>Visibility : </b><hr style="color: #DCEAF5;" />' . $diff_visibility;
			}
			if ( ! $private_update ){ // not private then add diff content if exists or add actual content if no diff
				if ($diff_followup_content){
					$body .= '<br/><b>Followup Content : </b><hr style="color: #DCEAF5;" />' . $diff_followup_content;
				}
				else{
					$body .= '<br/><b>Followup Content : </b><hr style="color: #DCEAF5;" />' . rthd_content_filter( $comment->comment_content );
				}
			}
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );
			$subject = rthd_create_new_ticket_title( 'rthd_update_followup_email_title', $comment->comment_post_ID );

			// sending email to followup author [ To ]
			if ( user_can( $user, rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ) {
				$bodyto = rthd_replace_followup_placeholder($body, 'you');
				$this->insert_new_send_email($subject, $title, rthd_get_general_body_template($bodyto), $followup_updater, array(), array(), array(), $comment->comment_ID, 'comment', true);
			}

			//sending emial to ticket assignee [ To ] | staff [ BCC ] | contact [ BCC ] | globel list [ BCC ]
			$bodyto = rthd_replace_followup_placeholder( $body, $comment->comment_author );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), $ContactEmail, array(), $bccemails, array(), $comment->comment_ID , 'comment', true );

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

			if ( $notificationFlagGroup && isset( $redux['rthd_notification_emails'] ) ) {
				foreach ( $redux['rthd_notification_emails'] as $email ) {
					array_push( $groupEmail, array( 'email' => $email ) );
				}
			}
			$groupEmail = $this->exclude_author( $groupEmail, $ticket_created_by->user_email );

			$subscriberEmail = $this->get_subscriber( $post_id );
			$subscriberEmail = $this->exclude_author( $subscriberEmail, $ticket_created_by->user_email );

			$ContactEmail  = $this->get_contacts( $post_id );
			$ContactEmail = $this->exclude_author( $ContactEmail, $ticket_created_by->user_email );

			$produc_list = wp_get_object_terms( $post_id, Rt_Offerings::$offering_slug );
			$arrProducts = array_unique( wp_list_pluck( $produc_list, 'name' ) );
			$arrProducts = implode( ', ', $arrProducts );

			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title',$post_id );

			// Ticket creator [ To ] Notification
			if ( ! empty( $ticket_creator ) && $notificationFlagClient ) {
				$htmlbody = 'Thank you for opening a new support ticket. We will look into your request and respond as soon as possible.<br/>';
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, $ticket_creator, array(), array(), $uploaded, $post_id, 'post', true );
			}

			// Conatcts [ TO ] Notification
			if ( ! empty( $ContactEmail ) && $notificationFlagClient ){
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong>. You have been subscribed to this ticket.<br/>';
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, $ContactEmail, array(), array(), $uploaded, $post_id, 'post', true );
			}

			// Group [ BCC ] Notification
			if ( ! empty( $groupEmail ) && $notificationFlagGroup ){
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong>';
				$htmlbody .= '<br/>Ticket Assigned to: <strong>' . $assigne_user->display_name.'</strong>';
				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody .= "<p><b>Product: </b>" . $arrProducts . '</p> <br />';
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, $groupEmail, array(), array() , $uploaded, $post_id, 'post', true );
			}

			// Assignee [ To ] Notification
			if ( ! empty( $assigneEmail ) && $notificationFlagAssignee ){
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong> is assigned to you';
				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody .= "<p><b>Product: </b>" . $arrProducts . '</p> <br />';
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, $assigneEmail, array(), array() , $uploaded, $post_id, 'post', true );
			}

			// Subscrible [ BCC ] Notification
			if ( ! empty( $subscriberEmail ) && $notificationFlagSsubscriber ){
				//A new support ticket is created by [CREATOR CONTACT NAME]. You have been subscribed to this ticket.
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong>. You have been subscribed to this ticket.';
				$htmlbody .= '<br/>Ticket Assigned to: <strong>' . $assigne_user->display_name.'</strong>';

				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody .= "<p><b>Product: </b>" . $arrProducts . '</p> <br />';
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, $subscriberEmail, array(), array() , $uploaded, $post_id, 'post', true );
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
			}

			// New subscriber added Notification to subscriber
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title', $post_id );
			$htmlbody = 'You have been subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
			$htmlbody = rthd_get_general_body_template( $htmlbody );
			$this->insert_new_send_email( $subject, $title, $htmlbody, $newSubscriberList, array(), array(), array(), $post_id, 'post' );

			// Assignee [ To ] | group [ BCC ] notification for subscriber added
			$htmlbody = '';
			foreach ( $newSubscriberList as $user ){
				$htmlbody .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
			}
			$htmlbody = trim( $htmlbody, ',' );
			$htmlbody = trim( $htmlbody, ' ' );
			$htmlbody .= ( count( $newSubscriberList ) >= 2 ) ? ' are ' : ' is ' ;
			$htmlbody .= ' subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
			$htmlbody = rthd_get_general_body_template( $htmlbody );

			$this->insert_new_send_email( $subject, $title, $htmlbody, $assigneEmail, array(), $bccemails, array(), $post_id, 'post' );
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
			}

			//subscriber removed Notification
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title', $post_id );

			$htmlbody = '';
			foreach ( $oldSubscriberList as $user ){
				$htmlbody .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
			}
			$htmlbody = trim( $htmlbody, ',' );
			$htmlbody = trim( $htmlbody, ' ' );
			$htmlbody .= ( count( $oldSubscriberList ) >= 2 ) ? ' are ' : ' is ' ;
			$htmlbody .= ' un-subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
			$htmlbody = rthd_get_general_body_template( $htmlbody );

			$this->insert_new_send_email( $subject, $title, $htmlbody, $assigneEmail, array(), $bccemails, array(), $post_id, 'post' );
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
			$subject = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title', $post_id );

			// old assignee [ To ] mail Notification
			if ( ! empty( $oldassigneEmail ) && $notificationFlagAssignee ){

				$htmlbody = 'You are no longer responsible for this ticket. ';
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, $oldassigneEmail, array(), array(), array(), $post_id, 'post' );
			}

			// new assignee [ To ] mail Notification
			if ( !empty( $newassigneEmail ) && $notificationFlagAssignee ){
				$htmlbody = 'A ticket is reassigned to you.';
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, $newassigneEmail, array(), array(), array(), $post_id, 'post' );
			}

			// group [ BCC ] mail Notification
			$htmlbody = 'A ticket is reassigned to ' . $newUser->display_name . ' ('. $newUser->user_email .')';
			$htmlbody = rthd_get_general_body_template( $htmlbody );
			$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $bccemails, array(), $post_id, 'post' );
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

			$subject = rthd_create_new_ticket_title( 'rthd_update_ticket_email_title', $post_id );
			$title = $this->get_email_title( $post_id, $post_type );
			$user = get_post_meta( $post_id, '_rtbiz_hd_updated_by', true );
			$ticket_update_user = get_user_by( 'id', $user );
			$body = '<br />' . 'Ticket updated by : <strong>' . $ticket_update_user->display_name . '</strong><br/>'. $body;
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $body ) , $assigneEmail, array(), $bccemails, array(), $post_id, 'post', true );
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
				global $rt_contact;
				$emails = get_post_meta( $contact->ID, Rt_Entity::$meta_key_prefix.$rt_contact->primary_email_key );
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
