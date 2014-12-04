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
		 * Add Notification Email into Queue
		 *
		 * @since 0.1
		 *
		 * @param        $subject
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
			global $rt_hd_mail_outbound_model;
			$settings = rthd_get_redux_settings();

			$toemail = $this->filter_user_notification_preference( $toemail );
			$ccemail = $this->filter_user_notification_preference( $ccemail );
			$bccemail = $this->filter_user_notification_preference( $bccemail );
			$signature = rthd_get_email_signature_settings();
			$args = array(
				'user_id'       => $user_id,
				'fromname'      => $settings['rthd_outgoing_email_from_name'],
				'fromemail'     => ( $settings['rthd_outgoing_email_delivery'] == 'wp_mail' ) ? $settings['rthd_outgoing_email_from_address'] : $settings['rthd_outgoing_email_mailbox'],
				'toemail'       => serialize( $toemail ),
				'ccemail'       => serialize( $ccemail ),
				'bccemail'      => serialize( $bccemail ),
				'subject'       => $subject,
				'body'          => rthd_content_filter( $body ) . '<br/>' . ( ( ! empty( $signature ) ) ? '<div style="color:#666;">' . $signature . '</div>' : '' ) . '<br/>' ,
				'attachement'   => serialize( $attachement ),
				'refrence_id'   => $refrence_id,
				'refrence_type' => $refrence_type,
			);
			if ( $this->is_wp_email( ) ) {
				$id = $rt_hd_mail_outbound_model->add_outbound_mail( $args );
				$sendflag = $this->send_wp_email( $args );
				if ( $sendflag ){
					$rt_hd_mail_outbound_model->update_outbound_mail( array( 'sent' => 'yes' ), array( 'id' => $id ) );
				}
				return $sendflag;
			} else {
				return $rt_hd_mail_outbound_model->add_outbound_mail( $args );
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
			$redux = rthd_get_redux_settings();
			if ( 'wp_mail' == $redux['rthd_outgoing_email_delivery'] ) {
				return true;
			}
			return false;
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
			$notificationFlag = ( $redux['rthd_notification_events']['new_ticket_assigned'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			global $current_user;
			$ticket_creaters = array();
			foreach ( $contacts as $c ) {
				if ( ! empty( $c['name'] ) ) {
					$ticket_creaters[] = $c['name'];
				} else if ( ! empty( $c['email'] ) ) {
					$ticket_creaters[] = $c['email'];
				}
			}

			$newUser  = get_user_by( 'id', $assignee );
			$to    = array(
				array(
					'email' => $newUser->user_email,
					'name'  => $newUser->display_name,
				),
			);

			$title = rthd_create_new_ticket_title( 'rthd_ticket_assign_email_title', $post_id );

			$body = '';
			if ( $mail_parse ) {
				$body = 'New ticket is assigned to you.';
			} else {
				$body = '<b>'.$current_user->display_name . '</b> assigned you new ticket.';
			}
			$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$body .= 'Ticket created by : <b>' . ( ( $mail_parse ) ? implode( ',', $ticket_creaters ) : $current_user->display_name ) . '</b>';
			// added Notification Emails
			$this->insert_new_send_email( $title, $body, $to, $cc, array(), $uploaded, $post_id, 'post' );
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
			$notificationFlag = ( $redux['rthd_notification_events']['new_ticket_reassigned'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}
			global $current_user;
			$newUser  = get_user_by( 'id', $assignee );
			$oldUser  = get_user_by( 'id', $oldassignee );
			if ( ! empty( $oldUser->user_email ) && ! empty( $oldUser->display_name ) ) {
				$to = array( array(
						'email' => $oldUser->user_email,
						'name'  => $oldUser->display_name,
				), );
			} else {
				$to = array();
			}

			$title = rthd_create_new_ticket_title( 'rthd_ticket_reassign_email_title', $post_id );

			$body = 'You are no longer responsible for this ticket. It has been reassigned to ' . $newUser->display_name;
			$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$body .= 'Ticket Updated by : <a target="_blank" href="">' . $current_user->display_name . '</a>';
			// added Notification Emails
			$this->insert_new_send_email( $title, $body, $to, $cc, array(), $uploaded, $post_id, 'post' );
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
			$notificationFlag = ( $redux['rthd_notification_events']['ticket_subscribed'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			$title = rthd_create_new_ticket_title( 'rthd_ticket_subscribe_email_title', $post_id );
			$body = 'You have been <b>subscribed</b> to this ticket';
			$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$this->insert_new_send_email( $title, $body, array(), array(), $newSubscriberList, array(), $post_id, 'post' );

			if ( $notificationFlag ){
				foreach ( $newSubscriberList as $user ){
					$body = 'Name: '.$user['name']. '('.$user['email'].')' ;
					$body .= '<br />';
				}
				$body .= ' have been <b>subscribed</b> to this ticket';
				$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
				$this->insert_new_send_email( $title, $body, array(), $cc, array(), array(), $post_id, 'post' );
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
			$notificationFlag = ( $redux['rthd_notification_events']['ticket_unsubscribed'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			$title = rthd_create_new_ticket_title( 'rthd_ticket_unsubscribe_email_title', $post_id );
			$body = 'You have been <b>unsubscribed</b> to this ticket';
			$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$this->insert_new_send_email( $title, $body, array(), array(), $oldSubscriberList, array(), $post_id, 'post' );
			if ( $notificationFlag ){
				$body = '';
				foreach ( $oldSubscriberList as $user ){
					$body .= 'Name: '.$user['name']. '('.$user['email'].')' ;
					$body .= '<br />';
				}
				$body .= 'have been <b>unsubscribed</b> from this ticket';
				$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
				$this->insert_new_send_email( $title, $body, array(), $cc, array(), array(), $post_id, 'post' );
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
			$notificationFlag = ( $redux['rthd_notification_events']['status_metadata_changed'] == 1 ) ;
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}
			global $current_user;
			$post_author_id = get_post_field( 'post_author', $post_id );
			$userSub     = get_user_by( 'id', intval( $post_author_id ) );
			$to[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );

			$title = rthd_create_new_ticket_title( 'rthd_update_ticket_email_title', $post_id );
			$body .= '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$body .= '<br />' . 'Ticket updated by : <a target="_blank" href="">' . $current_user->display_name . '</a>';
			$this->insert_new_send_email( $title, stripslashes( $body ), $to, $cc, $bccemails, array(), $post_id, 'post' );
		}

		/**
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $allemail
		 * @param $uploaded
		 */
		public function ticket_created_notification( $post_id, $post_type, $body, $allemail, $uploaded ) {
			$redux = rthd_get_redux_settings();
			$notificationFlag = ( $redux['rthd_notification_events']['new_ticket_created'] == 1 );
			$cc = array();
			if ( $notificationFlag ) {
				$cc = $this->get_notification_emails();
			}

			$title     = rthd_create_new_ticket_title( 'rthd_new_ticket_email_title',$post_id );
			$body      = $body . '<br />To View ' . $post_type . " Click <a href='" . ( rthd_is_unique_hash_enabled() ? rthd_get_unique_hash_url( $post_id ) : get_post_permalink( $post_id ) ) . "'>here</a>. <br/>";
			$notify_emails = array();
			if ( isset( $allemail ) && ! empty( $allemail ) ) {
				foreach ( $allemail as $email ) {
					if ( is_email( $email['address'] ) ) {
						$notify_emails[] = array( 'email' => $email['address'], 'name' => $email['name'] );
					}
				}
			}
			$this->insert_new_send_email( $title, $body, array(), $cc, $notify_emails, $uploaded, $post_id );
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
	}
}
