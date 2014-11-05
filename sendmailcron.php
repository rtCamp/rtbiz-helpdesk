<?php


/**
 * This file needs to be in Cron in order to start sending all the mails from the DB Queue.
 *
 * # "crontab -l" : This will dispaly the current crons which are set.
 *
 * # "crontab -e" : This will allow you to edit/add crons on the server.
 *
 * # Add following line in the crontab
 */
// # */1 * * * * cd /var/www/test.com/htdocs/wp-content/plugins/rtbiz-helpdesk && php sendmailcron.php >> /var/log/helpdesk_send_mail_cron_log.log 2>&1
/**
 * # Save the crontab. And cron will start to execute. The above line will run this script every 1 minute and log the output in the log file.
 *
 * # You can change the time accordingly.
 *
 */


if ( ! defined( 'WP_LOAD_PATH' ) ) {
	$path = '../../../';
	if ( file_exists( $path . 'wp-load.php' ) ) {
		define( 'WP_LOAD_PATH', $path );
	} else {
		exit( 'Could not find wp-load.php' );
	}
}

require_once( WP_LOAD_PATH . 'wp-load.php' );
global $rt_hd_settings;

$settings = rthd_get_redux_settings();
if ( ! empty( $settings['rthd_outgoing_email_delivery'] ) && $settings['rthd_outgoing_email_delivery'] == 'user_mail_login' ) {
	$emailRow = $rt_hd_settings->get_new_sent_mail();
	if ( empty( $emailRow ) ) {
		return;
	}
	$hdZendEmail      = new Rt_HD_Zend_Mail();
	$accessTokenArray = array();
	$signature        = '';
	foreach ( $emailRow as $email ) {
		var_dump($email);
		if ( ! isset( $accessTokenArray[ $email->fromemail ] ) ) {
			$email_type                            = '';
			$imap_server                           = '';
			$accessTokenArray[ $email->fromemail ] = array(
				'token'       => $rt_hd_settings->get_accesstoken_from_email( $email->fromemail, $signature, $email_type, $imap_server ),
				'email_type'  => $email_type,
				'imap_server' => $imap_server,
			);
		}
		var_dump($accessTokenArray);
		if ( $rt_hd_settings->update_sent_email( $email->id, 'p', 'no' ) > 0 ) {
			$updateFlag = false;
			try {
				var_dump($hdZendEmail->sendemail( $email->fromemail, $accessTokenArray[ $email->fromemail ]['token'], $accessTokenArray[ $email->fromemail ]['email_type'], $accessTokenArray[ $email->fromemail ]['imap_server'], $email->subject, $email->body, unserialize( $email->toemail ), unserialize( $email->ccemail ), unserialize( $email->bccemail ), unserialize( $email->attachement ) ));
				$updateFlag = true;
			} catch ( Exception $e ) {
				//var_dump( $e->getMessage() );
				$updateFlag = false;
			}
			if ( $updateFlag ) {
				$rt_hd_settings->update_sent_email( $email->id, 'yes', 'p' );
			} else {
				$rt_hd_settings->update_sent_email( $email->id, 'error', 'p' );
				echo 'Error: ' . esc_attr( $email->id  ). '<br />';
			}
		} else {
			echo 'Error: ' . esc_attr( $email->id  ). '<br />';
		}
	}
}