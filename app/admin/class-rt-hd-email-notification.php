<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 9/9/14
 * Time: 7:37 PM
 */

if ( ! class_exists( 'RT_HD_Email_Notification' ) ){

	/**
	 * Class RT_HD_Email_Notification
	 *
	 * @since 0.1
	 *
	 * @author dipesh
	 */
	class RT_HD_Email_Notification {

		/**
		 * Add Notification Email into Queue
		 *
		 * @since 0.1
		 *
		 * @param $fromemail
		 * @param $subject
		 * @param $body
		 * @param array $toemail
		 * @param array $ccemail
		 * @param array $bccemail
		 * @param array $attachement
		 * @param int $refrence_id
		 * @param string $refrence_type
		 *
		 * @return mixed
		 */
		public function insert_new_send_email( $fromemail, $subject, $body, $toemail = array(), $ccemail = array(), $bccemail = array(), $attachement = array(), $refrence_id = 0, $refrence_type = 'notification' ) {

			$user_id = get_current_user_id();
			global $rt_hd_mail_outbound_model;
			$args = array(
				'user_id'       => $user_id,
				'fromemail'     => $fromemail,
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
	}
}
