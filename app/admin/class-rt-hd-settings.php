<?php

/**
 * Don't load this file directly!
 */
if (!defined('ABSPATH'))
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Settings
 *
 * @author udit
 */
if (!class_exists('Rt_HD_Settings')) {

	class Rt_HD_Settings {

		public $sync_period = "00:20:00"; //"h:m:s"

		public function __construct() { }

		function ui() {
			global $rt_hd_settings;
			$args = array(
				'rt_hd_settings' => $rt_hd_settings,
			);
			rthd_get_template( 'admin/settings.php', $args );
		}

		public function update_sync_meta_time($email, $replytime) {
			global $rt_hd_mail_accounts_model;
			$rows_affected = $rt_hd_mail_accounts_model->update_mail_account( array( 'last_sync_time' => $replytime ), array( 'email' => $email ) );
			return ( !empty( $rows_affected ) );
		}

		public function update_sync_status($email, $isSyncing) {
			$status = "synced";
			if ($isSyncing)
				$status = "syncing";
			global $rt_hd_mail_accounts_model;
			$rows_affected = $rt_hd_mail_accounts_model->update_mail_account( array( 'sync_status' => $status ), array( 'email' => $email ) );
			return ( !empty( $rows_affected ) );
		}

		function get_email_acc( $email ) {
			global $rt_hd_mail_accounts_model;
			$emails = $rt_hd_mail_accounts_model->get_mail_account( array( 'email' => $email ) );
			$email = false;
			if ( ! empty( $emails ) ) {
				$email = $emails[0];
			}
			return $email;
		}

		public function get_user_google_ac($user_id = 0) {
			global $rt_hd_mail_accounts_model;
			if ($user_id == 0)
				$user_id = get_current_user_id();
			return $rt_hd_mail_accounts_model->get_mail_account( array( 'user_id' => $user_id ) );
		}

		public function get_accesstoken_from_email( $email, &$signature, &$email_type, &$imap_server ) {
			global $rt_hd_mail_accounts_model;
			$ac = $rt_hd_mail_accounts_model->get_mail_account( array( 'email' => $email ) );
			if(isset($ac[0])) {
				$ac = $ac[0];
			} else {
				$signature = '';
				// Terminating because no email is found
				return false;
			}
			$signature = $ac->signature;
			$email_type = $ac->type;
			$imap_server = $ac->imap_server;
			if (!$signature)
				$signature = "";
			$redirect_url = get_site_option('rthd_googleapi_redirecturl');
			if (!$redirect_url) {
				$redirect_url = admin_url("admin.php?page=rthd-settings&tab=my-settings&type=personal");
				update_site_option("rthd_googleapi_redirecturl", $redirect_url);
			}

			$ac->email_data = unserialize($ac->email_data);

			$email = filter_var($ac->email_data['email'], FILTER_SANITIZE_EMAIL);

			$access_token = $ac->outh_token;

			if ( $ac->type == 'goauth' ) {

				$google_client_id = get_site_option('rthd_googleapi_clientid', "");
				$google_client_secret = get_site_option('rthd_googleapi_clientsecret', "");
				$google_client_redirect_url = get_site_option('rthd_googleapi_redirecturl', "");

				include_once RT_HD_PATH_VENDOR . 'google-api-php-client/Google_Client.php';
				include_once RT_HD_PATH_VENDOR . 'google-api-php-client/contrib/Google_Oauth2Service.php';

				$client = new Google_Client();
				$client->setApplicationName("Helpdesk Studio");
				$client->setClientId($google_client_id);
				$client->setClientSecret($google_client_secret);
				$client->setRedirectUri($google_client_redirect_url);
				$client->setScopes(array('https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile'));
				$client->setAccessType("offline");

				$token = json_decode($ac->outh_token);
				$client->setAccessToken($ac->outh_token);

				if ($client->isAccessTokenExpired()) {
					$client->refreshToken($token->refresh_token);
					$oauth2 = new Google_Oauth2Service($client);
					$user = $oauth2->userinfo_v2_me->get();
					$email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
					if ( isset( $ac->email_data['inbox_folder'] ) ) {
						$user['inbox_folder'] = $ac->email_data['inbox_folder'];
					}
					if ( isset( $ac->email_data['mail_folders'] ) ) {
						$user['mail_folders'] = $ac->email_data['mail_folders'];
					}
					$this->update_user_google_ac($client->getAccessToken(), $email, serialize($user));
					$ac->email_data = $user;
					$token = json_decode($client->getAccessToken());
				}

				$access_token = $token->access_token;
			}
			return $access_token;
		}

		public function update_gmail_ac_count() {
			global $rt_hd_mail_accounts_model;
			$accounts = $rt_hd_mail_accounts_model->get_all_mail_accounts();
			Rt_HD_Utils::setAccounts( sizeof( $accounts ) );
		}

		public function add_user_google_ac( $outh_token, $email, $email_data, $user_id = -1, $type = "goauth", $imap_server = NULL ) {
			global $rt_hd_mail_accounts_model;
			if ($user_id == -1) {
				$user_id = get_current_user_id();
			}

			$args = array(
				'user_id' => $user_id,
				'email' => $email,
				'outh_token' => $outh_token,
				'email_data' => $email_data,
				'type' => $type,
				'flag' => 'Y'
			);

			if ( $imap_server != NULL ) {
				$args['imap_server'] = $imap_server;
			}

			$rows_affected = $rt_hd_mail_accounts_model->add_mail_account( $args );
			$this->update_gmail_ac_count();
			return ( !empty( $rows_affected ) );
		}

		public function update_user_google_ac($outh_token, $email, $email_data) {
			global $rt_hd_mail_accounts_model;
			$data = array(
				'outh_token' => $outh_token,
				'email_data' => $email_data,
			);
			$where = array(
				'email' => $email,
			);
			$rows_affected = $rt_hd_mail_accounts_model->update_mail_account( $data, $where );
			return ( !empty( $rows_affected ) );
		}

		public function delete_user_google_ac($email, $user_id = -1) {
			if ($user_id == -1) {
				$user_id = get_current_user_id();
			}
			global $rt_hd_mail_accounts_model;
			$result = $rt_hd_mail_accounts_model->remove_mail_account( array( 'email' => $email, 'user_id' => $user_id ) );
			$this->update_gmail_ac_count();
			return $result;
		}

		public function update_mail_acl( $email, $token = NULL, $email_data = NULL,$user_ids = array(), $signature = '', $imap_server = NULL ) {
			global $rt_hd_mail_acl_model, $rt_hd_mail_accounts_model;
			$rt_hd_mail_acl_model->remove_acl( array( 'email' => $email ) );
			foreach ( $user_ids as $uid ) {
				$args = array(
					'allow_user' => $uid,
					'email' => $email,
				);
				$rt_hd_mail_acl_model->add_acl( $args );
			}

			$args = array( 'signature' => $signature );

			if ( $email_data != NULL ) {
				$args['email_data'] = $email_data;
			}
			if ( $token != NULL ) {
				$args['outh_token'] = $token;
			}
			if ( $imap_server != NULL ) {
				$args['imap_server'] = $imap_server;
			}

			$rows_affected = $rt_hd_mail_accounts_model->update_mail_account( $args, array( 'email' => $email ) );
		}

		public function get_email_acl($email) {
			global $rt_hd_mail_acl_model;
			return $rt_hd_mail_acl_model->get_acl( array( 'email' => $email ) );
		}

		public function get_allow_email_address() {
			$user_id = get_current_user_id();
			global $wpdb, $rt_hd_mail_accounts_model, $rt_hd_mail_acl_model;
			$sql = $wpdb->prepare("(select * from {$rt_hd_mail_accounts_model->table_name} where user_id=%d)
                                union (select a.* from {$rt_hd_mail_acl_model->table_name} b inner join
                                {$rt_hd_mail_accounts_model->table_name} a on a.email=b.email where b.allow_user=%d)", $user_id, $user_id);
			return $wpdb->get_results($sql);
		}

		public function get_all_email_address() {
			global $rt_hd_mail_accounts_model;
			return $rt_hd_mail_accounts_model->get_all_mail_accounts();
		}

		function get_email_for_sync_debug( $email ) {
			global $wpdb, $rt_hd_mail_accounts_model;
			$sql = $wpdb->prepare( "select * from $rt_hd_mail_accounts_model->table_name where email = %s", $email );
			$row = $wpdb->get_row( $sql );
			return $row;
		}

		public function get_email_for_sync() {
			sleep(5);
			global $wpdb, $rt_hd_mail_accounts_model;
			$sql = $wpdb->prepare( "select * from $rt_hd_mail_accounts_model->table_name where sync_status in ( 'syncing' ) and ( last_sync_time is NULL or addtime( last_sync_time, %s ) < NOW() ) order by last_sync_time limit 1", $this->sync_period );
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = "select * from $rt_hd_mail_accounts_model->table_name where not sync_status in ( 'syncing' ) order by last_sync_time limit 1";
				$row = $wpdb->get_row( $sql );
			} else {
				$this->update_sync_meta_time( $row->email, current_time( 'mysql' ) );
			}
			return $row;
		}

		public function insert_new_send_email($fromemail, $subject, $body, $toemail = array(), $ccemail = array(), $bccemail = array(), $attachement = array(), $refrence_id = 0, $refrence_type = "notification") {

			$user_id = get_current_user_id();
			global $rt_hd_mail_outbound_model;
			$args = array(
				'user_id' => $user_id,
				'fromemail' => $fromemail,
				'toemail' => serialize($toemail),
				'ccemail' => serialize($ccemail),
				'bccemail' => serialize($bccemail),
				'subject' => $subject,
				'body' => $body,
				'attachement' => serialize($attachement),
				'refrence_id' => $refrence_id,
				'refrence_type' => $refrence_type,
			);

			return $rt_hd_mail_outbound_model->add_outbound_mail( $args );
		}

		public function get_new_sent_mail() {
			global $rt_hd_mail_outbound_model;
			return $rt_hd_mail_outbound_model->get_outbound_mail( array( 'sent' => 'no' ) );
		}

		public function update_sent_email($sentEmailID, $status = 'yes', $oldStatus = 'no') {
			global $rt_hd_mail_outbound_model;
			$rows_affected = $rt_hd_mail_outbound_model->update_outbound_mail( array( 'sent' => $status ), array( 'id' => $sentEmailID, 'sent' => $oldStatus ) );
			echo $rows_affected;
			return $rows_affected;
		}

	}

}
