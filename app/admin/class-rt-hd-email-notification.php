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
			$settings = rthd_get_settings();

			$args = array(
				'user_id'       => $user_id,
				'fromemail'     => $settings['outbound_emails'],
				'toemail'       => serialize( $toemail ),
				'ccemail'       => serialize( $ccemail ),
				'bccemail'      => serialize( $bccemail ),
				'subject'       => $subject,
				'body'          => $body,
				'attachement'   => serialize( $attachement ),
				'refrence_id'   => $refrence_id,
				'refrence_type' => $refrence_type,
			);

			return $rt_hd_mail_outbound_model->add_outbound_mail( $args );
		}

		/**
		 * Send Notification to assigned
		 *
		 * @param $post_id
		 * @param $assignee
		 * @param $post_type
		 * @param $uploaded
		 */
		public function notification_new_ticket_assigned( $post_id, $assignee, $post_type, $uploaded ) {
			global $current_user;
			$newUser  = get_user_by( 'id', $assignee );
			$to    = array(
				array(
					'email' => $newUser->user_email,
					'name'  => $newUser->display_name,
				),
			);
			$title = '[New ' . $post_type . ' Assigned You]' . $this->create_title_for_mail( $post_id );

			$body = $current_user->display_name . '</b> assigned you new ticket.';
			$body .= '<br />To View ' . $post_type . " Click <a href='" . admin_url( 'post.php?post=' . $post_id . 'action=edit' ) . "'>here</a>. <br/>";
			$body .= 'Ticket created by : <a target="_blank" href="">' . $current_user->display_name . '</a>';
			// added Notification Emails
			$this->insert_new_send_email( $title, $body, $to, array(), array(), $uploaded, $post_id, 'post' );
		}

		/**
		 * Send Notification to reassigned event
		 *
		 * @param $post_id
		 * @param $oldassignee
		 * @param $assignee
		 * @param $post_type
		 * @param $body
		 * @param $uploaded
		 */
		public function notification_new_ticket_reassigned( $post_id, $oldassignee, $assignee, $post_type, $body, $uploaded ) {
			global $current_user;
			$newUser  = get_user_by( 'id', $assignee );
			$oldUser  = get_user_by( 'id', $oldassignee );
			$to    = array(
				array(
					'email' => $oldUser->user_email,
					'name'  => $oldUser->display_name,
				),
			);
			$title = '[Reassigned ' . $post_type . ']' . $this->create_title_for_mail( $post_id );

			$body = 'You are no longer responsible for this ticket. It has been reassigned to ' . $newUser->display_name;
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$body .= '<br />To View ' . $post_type . " Click <a href='" . trailingslashit( site_url() ) . strtolower( $post_type ) . '/?rthd_unique_id=' . $unique_id . "'>here</a>. <br/>";
			$body .= 'Ticket Updated by : <a target="_blank" href="">' . $current_user->display_name . '</a>';
			// added Notification Emails
			$this->insert_new_send_email( $title, $body, $to, array(), array(), $uploaded, $post_id, 'post' );
		}

		/**
		 * Send Notification to subscribed
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $newSubscriberList
		 */
		public function notification_ticket_subscribed( $post_id, $post_type, $newSubscriberList ) {
			$title = '[Subscribe ' . $post_type . ']' . $this->create_title_for_mail( $post_id );
			$body = 'You have been <b>subscribed</b> to this ticket';
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$body .= '<br />To View ' . $post_type . " Click <a href='" . trailingslashit( site_url() ) . strtolower( $post_type ) . '/?rthd_unique_id=' . $unique_id . "'>here</a>. <br/>";
			$this->insert_new_send_email( $title, $body, array(), array(), $newSubscriberList, array(), $post_id, 'post' );
		}

		/**
		 * Send Notification to unsubscribed
		 *
		 * @param $post_id
		 * @param $post_type
		 * @param $oldSubscriberList
		 */
		public function notification_ticket_unsubscribed( $post_id, $post_type, $oldSubscriberList  ) {
			$title = '[Unsubscribe ' . $post_type . ']' . $this->create_title_for_mail( $post_id );
			$body = 'You have been <b>unsubscribed</b> to this ticket';
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$body .= '<br />To View ' . $post_type . " Click <a href='" . trailingslashit( site_url() ) . strtolower( $post_type ) . '/?rthd_unique_id=' . $unique_id . "'>here</a>. <br/>";
			$this->insert_new_send_email( $title, $body, array(), array(), $oldSubscriberList, array(), $post_id, 'post' );
		}

		/**
		 * Send Notification on update ticket
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $bccemails
		 */
		public function notification_ticket_updated( $post_id, $post_type, $body, $bccemails ) {
			global $current_user;

			$title = '[' . $post_type . ' Updated]' . $this->create_title_for_mail( $post_id );
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$body .= '<br />To View ' . $post_type . " Click <a href='" . trailingslashit( site_url() ) . strtolower( $post_type ) . '/?rthd_unique_id=' . $unique_id . "'>here</a>. <br/>";
			$body .= '<br />' . 'Ticket updated by : <a target="_blank" href="">' . $current_user->display_name . '</a>';
			$this->insert_new_send_email( $title, stripslashes( $body ), array(), array(), $bccemails, array(), $post_id, 'post' );
		}

		/**
		 * @param $post_id
		 * @param $post_type
		 * @param $body
		 * @param $allemail
		 * @param $uploaded
		 */
		public function ticket_created_notification( $post_id, $post_type, $body, $allemail, $uploaded ) {
			$title     = '[New ' . $post_type . ']' . $this->create_title_for_mail( $post_id );
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			$body      = $body . '<br />To View ' . $post_type . " Click <a href='" . trailingslashit( site_url() ) . strtolower( $post_type ) . '/?rthd_unique_id=' . $unique_id . "'>here</a>. <br/>";
			$notify_emails = array();
			if ( isset( $allemail ) && ! empty( $allemail ) ) {
				foreach ( $allemail as $email ) {
					if ( is_email( $email['address'] ) ) {
						$notify_emails[] = array( 'email' => $email['address'], 'name' => $email['name'] );
					}
				}
				$this->insert_new_send_email( $title, $body, array(), array(), $notify_emails, $uploaded, $post_id );
			}
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
	}
}
