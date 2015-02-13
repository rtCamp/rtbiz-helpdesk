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
			if ( $reply_above_line ){
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
				'body'          => ( $htmlbody ) . '<br/>' . ( ( ! empty( $signature ) ) ? '<div style="color:#666;">' . $signature . '</div>' : '' ) . '<br/>' ,
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
		 * @param $args
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

			$contact_flag = true; // send email to contacts too
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['new_comment_added'] == 1 );

			if ( isset( $comment_privacy ) && ! empty( $comment_privacy ) && intval( $comment_privacy ) && $comment_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ){
				if ( $comment_privacy == Rt_HD_Import_Operation::$FOLLOWUP_STAFF){
					$contact_flag = false; // do not send email to contact in case of follow up staff
				}
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

			$toemails = array();
			$bccemails = array();
			if ( $notificationFlag ) {
				if ( isset( $redux['rthd_notification_emails'] ) ) {
					foreach ( $redux['rthd_notification_emails'] as $email ) {
						array_push( $bccemails, array( 'email' => $email ) );
					}
				}
			}
			$subscriber = $this->get_subscriber( $comment->comment_post_ID );
			array_push( $bccemails, $subscriber );

			// sending email to followup author
			$toBody = rthd_replace_followup_placeholder( $body, 'you' );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $toBody ), array( array( 'email' => $comment->comment_author_email, 'name' => $comment->comment_author ) ), array(), array(), $uploaded, $comment->comment_ID , 'comment', true );

			//group notification
			$toBody = rthd_replace_followup_placeholder( $body, $comment->comment_author );
			// sending email to contacts excluding if it is follow up author
			if ( $contact_flag ){
				$contacts  = $this->get_contacts($comment->comment_post_ID );
				$contacts = $this->exclude_author($contacts, $comment->comment_author_email);
				if ( ! empty( $contacts ) ){
					$toemails = $contacts;
				}
			}



			// sending email to subscriber, assignee, global list and exclude if it is follow up author
			$bccemails[] = $this->get_assigne_email( $comment->comment_post_ID );
			$bccemails = $this->exclude_author( $bccemails, $comment->comment_author_email );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $toBody ), $toemails , array(), $bccemails, $uploaded, $comment->comment_ID , 'comment', true );
		}


		/**
		 * This will handle all notification for deleting followup
		 * @param $comment Object that is being deleted
		 * @param $user_id int user id who deleted comment
		 */
		public function notification_followup_deleted( $comment, $user_id ){
			$User       = get_user_by( 'id', $user_id );
			$bccemails  = array();
			$body  = 'A Follwup is deleted by <Strong>{comment_author}</Strong>';
			if ( ! ( isset( $comment->comment_type ) && ! empty( $comment->comment_type ) && intval( $comment->comment_type ) && $comment->comment_type > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC  ) ) {
				$body .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $comment->comment_content ) . '</div>';
			}
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( $redux['rthd_notification_events']['followup_deleted'] == 1 );
			if ( $notificationFlag ) {
				if ( isset( $redux['rthd_notification_emails'] ) ) {
					foreach ( $redux['rthd_notification_emails'] as $email ) {
						array_push( $bccemails, array( 'email' => $email ) );
					}
				}
			}
			$subscriber = $this->get_subscriber( $comment->comment_post_ID );
			array_push( $bccemails, $subscriber );

			$subject = rthd_create_new_ticket_title( 'rthd_delete_followup_email_title', $comment->comment_post_ID );
			global $rt_hd_module;
			$labels = $rt_hd_module->labels;
			$title = $this->get_email_title( $comment->comment_post_ID, $labels['name'] );
			$assignee_email = array();
			$assignee_email[] = $this->get_assigne_email( $comment->comment_post_ID );
			$assignee_email = $this->exclude_author( $assignee_email, $User->user_email );

			if ( user_can( $User, rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ){
				$bodyto = rthd_replace_followup_placeholder( $body, 'you' );
				$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), array( array( 'email' => $User->user_email ) ), array(), array(), array(), $comment->comment_ID , 'comment', true );
			}

			$bodyto = rthd_replace_followup_placeholder( $body, $User->display_name );
			$bccemails = $this->exclude_author( $bccemails, $User->user_email );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), $assignee_email, array(), $bccemails, array(), $comment->comment_ID , 'comment', true );
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
			$user       = get_user_by( 'id', $user_id );
			// find if followup was private before or right now it is private
			$private_update = false;
			if ( ( intval( $old_privacy ) && $old_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) || ( intval( $new_privacy ) && $new_privacy > Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) ){
				$private_update = true;
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
			$bodyto = rthd_replace_followup_placeholder( $body, 'you' );
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), array( array( 'email'=>$user->user_email ) ), array(), array(), array(), $comment->comment_ID , 'comment', true );
			// Group notification
			$bccemails = array();
			$bodyto = rthd_replace_followup_placeholder( $body, $user->display_name );
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( $redux['rthd_notification_events']['followup_edited'] == 1 );

			if ( $notificationFlag ) {
				if ( isset( $redux['rthd_notification_emails'] ) ) {
					foreach ( $redux['rthd_notification_emails'] as $email ) {
						array_push( $bccemails, array( 'email' => $email ) );
					}
				}
			}

			$subscriber = $this->get_subscriber( $comment->comment_post_ID );
			array_push( $bccemails, $subscriber );
			$bccemails = $this->exclude_author( $bccemails, $user->user_email );

			$assignee_email = array();
			$assignee_email[] = $this->get_assigne_email( $comment->comment_post_ID );
			$assignee_email = $this->exclude_author( $assignee_email, $user->user_email );


			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $bodyto ), $assignee_email, array(), $bccemails, array(), $comment->comment_ID , 'comment', true );

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
		 * @param $allemail
		 * @param $uploaded
		 */
		//ticket_created_notification
		public function notification_new_ticket_created( $post_id, $post_type, $body, $allemail, $uploaded ) {
			$creatorEmail = $otherContactEmail = $groupEmail = $assigneEmail = $subscriberEmail = array();

			$user = get_post_meta( $post_id, '_rtbiz_hd_created_by', true );
			$ticket_created_by = get_user_by( 'id', $user );
			$post = get_post( $post_id );
			$assigne_user = get_user_by( 'id', $post->post_author );
			$produc_list = wp_get_object_terms( $post_id, Rt_Offerings::$offering_slug );
			$arrProducts = array_unique( wp_list_pluck( $produc_list, 'name' ) );
			$arrProducts = implode( ', ', $arrProducts );

			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['new_ticket_created'] == 1 );
			if ( $notificationFlag ) {
				$groupEmail = $this->get_notification_emails();
			}

			if ( isset( $allemail ) && ! empty( $allemail ) ) {
				foreach ( $allemail as $email ) {
					if ( is_email( $email['address'] ) ) {
						if ( $this->is_internal_user( $email['address'] ) ) {
							if ( $email['address'] != $assigne_user->user_email ) { // check it's assignee email
								$groupEmail[ ] = array( 'email' => $email[ 'address' ], 'name' => $email[ 'name' ] );
							}
						} else {
							if ( $email['address'] == $ticket_created_by->user_email ) {
								$creatorEmail[] = array( 'email' => $email['address'], 'name' => $email['name'] );
							} else {
								$otherContactEmail[] = array( 'email' => $email['address'], 'name' => $email['name'] );
							}
						}
					}
				}
			}

			$assigneEmail[] = array( 'email' => $assigne_user->user_email, 'name' => $assigne_user->display_name );

			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title',$post_id );
			
			// Customer Notification
			if ( ! empty( $creatorEmail ) ) {
				$htmlbody = 'Thank you for opening a new support ticket. We will look into your request and respond as soon as possible.<br/>';
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, $creatorEmail, array(), array(), $uploaded, $post_id );
			}
			
			// Other Conatcts Information.
			if ( ! empty( $otherContactEmail ) ){
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong>';
				$htmlbody .= '<br/>Ticket Assigned to: <strong>' . $assigne_user->display_name.'</strong>';
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $otherContactEmail , $uploaded, $post_id );
			}
			
			// Group Notification
			if ( ! empty( $groupEmail ) ){
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
				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $groupEmail , $uploaded, $post_id );
			}

			// Assignee Notification
			if ( ! empty( $assigneEmail ) ){
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong> is assigned to you';
				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody .= "<p><b>Product: </b>" . $arrProducts . '</p> <br />';
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$htmlbody = rthd_get_general_body_template( $htmlbody );
				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $assigneEmail , $uploaded, $post_id );
			}

			// Subscrible Notification
			if ( ! empty( $subscriberEmail ) ){
				//A new support ticket is created by [CREATOR CONTACT NAME]. You have been subscribed to this ticket.
				$htmlbody = 'A new support ticket created by <strong>' . $ticket_created_by->display_name.'</strong>. You have been subscribed to this ticket.<br/>';
				// Add product info into mail body.
				if( ! empty( $arrProducts ) ) {
					$htmlbody .= "<p><b>Product: </b>" . $arrProducts . '</p> <br />';
				}
				if ( isset( $body ) && !empty( $body ) ){
					$htmlbody .= '<hr style="color: #DCEAF5;" /><div>' . rthd_content_filter( $body ) . '</div>';
				}
				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $assigneEmail , $uploaded, $post_id );
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
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['ticket_subscribed'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			// New subscriber added Notification to subscriber
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title', $post_id );
			$htmlbody = 'You have been subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
			$htmlbody = rthd_get_general_body_template( $htmlbody );
			$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $newSubscriberList, array(), $post_id, 'post' );

			//group notification for subscriber added
			if ( $notificationFlag ){
				$htmlbody = '';
				foreach ( $newSubscriberList as $user ){
					$htmlbody .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
				}
				$htmlbody = trim( $htmlbody, ',' );
				$htmlbody = trim( $htmlbody, ' ' );
				$htmlbody .= ( count( $newSubscriberList ) >= 2 ) ? ' are ' : ' is ' ;
				$htmlbody .= ' subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), $cc, array(), array(), $post_id, 'post' );
			}
		}

		/**
		 * Send Notification to unsubscribed
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $oldSubscriberList
		 */
		public function notification_ticket_unsubscribed( $post_id, $post_type, $oldSubscriberList  ) {
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['ticket_unsubscribed'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			//subscriber removed Notification
			$title = $this->get_email_title( $post_id, $post_type );
			$subject     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title', $post_id );

//			$htmlbody = 'You have been no longer subscribed to this ticket.';
//			$htmlbody = rthd_get_general_body_template( $htmlbody );
//			$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $oldSubscriberList, array(), $post_id, 'post' );

			if ( $notificationFlag ){
				$htmlbody = '';
				foreach ( $oldSubscriberList as $user ){
					$htmlbody .= ' ' . $user['name']. ' ('.$user['email'].'),' ;
				}
				$htmlbody = trim( $htmlbody, ',' );
				$htmlbody = trim( $htmlbody, ' ' );
				$htmlbody .= ( count( $oldSubscriberList ) >= 2 ) ? ' are ' : ' is ' ;
				$htmlbody .= ' un-subscribed to this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $cc, array(), $post_id, 'post' );
			}
		}

		/**
		 * Send Notification to reassigned event
		 *
		 * @param $post_id
		 * @param $oldassignee
		 * @param $assignee
		 * @param $post_type
		 * @param $uploaded
		 */
		public function notification_new_ticket_reassigned( $post_id, $oldassignee, $assignee, $post_type, $uploaded ) {
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['new_ticket_reassigned'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			$oldUser  = get_user_by( 'id', $oldassignee );
			if ( ! empty( $oldUser->user_email ) && ! empty( $oldUser->display_name ) ) {
				$to = array( array(
					             'email' => $oldUser->user_email,
					             'name'  => $oldUser->display_name,
				             ), );
			} else {
				$to = array();
			}

			// reassign Notification
			$title = $this->get_email_title( $post_id, $post_type );
			$subject = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title', $post_id );
			$htmlbody = 'You are no longer responsible for this ticket. ';
			$htmlbody = rthd_get_general_body_template( $htmlbody );

			$this->insert_new_send_email( $subject, $title, $htmlbody, $to, array(), array(), $uploaded, $post_id, 'post' );

			// group reassign notification
//			if ( $notificationFlag ){
//				$htmlbody = $oldUser->display_name . ' ('. $oldUser->user_email .')' ;
//				$htmlbody .= ' is no longer responsible for this ticket. <a href="'.  ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) .'">Click here</a>';
//				$htmlbody = rthd_get_general_body_template( $htmlbody );
//
//				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $cc, array(), $post_id, 'post' );
//			}
		}

		/**
		 * Send Notification to assigned
		 *
		 * @param $post_id
		 * @param $assignee
		 * @param $post_type
		 * @param $contacts
		 * @param $uploaded
		 * @param $mail_parse
		 */
		public function notification_new_ticket_assigned( $post_id, $assignee, $post_type, $contacts = array(), $uploaded = array(), $mail_parse = false ) {
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['new_ticket_assigned'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			$newUser  = get_user_by( 'id', $assignee );
			$to    = array(
				array(
					'email' => $newUser->user_email,
					'name'  => $newUser->display_name,
				),
			);

			// reassign Notification
			$title = $this->get_email_title( $post_id, $post_type );
			$subject = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title', $post_id );
			$htmlbody = 'A ticket is reassigned to you.';
			$htmlbody = rthd_get_general_body_template( $htmlbody );

			$this->insert_new_send_email( $subject, $title, $htmlbody, $to, array(), array(), $uploaded, $post_id, 'post' );

			// group reassign notification
			if ( $notificationFlag ){
				$htmlbody = 'A ticket is reassigned to ' . $newUser->display_name . ' ('. $newUser->user_email .')';
				$htmlbody = rthd_get_general_body_template( $htmlbody );

				$this->insert_new_send_email( $subject, $title, $htmlbody, array(), array(), $cc, array(), $post_id, 'post' );
			}

		}




		/**
		 * Send Notification on update ticket
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $bccemails
		 */
		public function notification_ticket_updated( $post_id, $post_type, $body, $bccemails ) {
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( isset( $redux['rthd_notification_events']) && $redux['rthd_notification_events']['status_metadata_changed'] == 1 ) ;
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
				foreach ($cc as $email){
					$bccemails[] = $email;
				}
			}

			$subscribers = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to' );
			foreach ( $subscribers as $s ){
				$s_user     = get_user_by( 'id', intval( $s ) );
				$bccemails[] =  $s_user->user_email;
			}

			global $current_user;
			$post_author_id = get_post_field( 'post_author', $post_id );
			$userSub     = get_user_by( 'id', intval( $post_author_id ) );
			$to[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );

			$subject = rthd_create_new_ticket_title( 'rthd_update_ticket_email_title', $post_id );
			$title = $this->get_email_title( $post_id, $post_type );
			$user = get_post_meta( $post_id, '_rtbiz_hd_updated_by', true );
			$ticket_update_user = get_user_by( 'id', $user );
			$body = '<br />' . 'Ticket updated by : <strong>' . $ticket_update_user->display_name . '</strong><br/>'. $body;
			$this->insert_new_send_email( $subject, $title, rthd_get_general_body_template( $body ) , $to, array(), array_unique( $bccemails ), array(), $post_id, 'post', true );
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
						$bccemails[ ]  = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
						$sendEmailFlag = true;
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
			return  array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
		}

	}
}
