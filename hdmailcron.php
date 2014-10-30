<?php
if ( ! defined( 'WP_LOAD_PATH' ) ) {
	$path = '../../../';
	if ( file_exists( $path . 'wp-load.php' ) ) {
		define( 'WP_LOAD_PATH', $path );
	} else {
		exit( 'Could not find wp-load.php' );
	}
}

require_once( WP_LOAD_PATH . 'wp-load.php' );
global $rt_hd_settings, $redux_helpdesk_settings;

if ( isset( $redux_helpdesk_settings ) && isset( $redux_helpdesk_settings['rthd_enable_reply_by_email'] ) && ! empty( $redux_helpdesk_settings['rthd_enable_reply_by_email'] ) && $redux_helpdesk_settings['rthd_enable_reply_by_email'] == 1
) {

	$emailRow = $rt_hd_settings->get_email_for_sync();
	if ( ! $emailRow ) {
		return;
	}
	$email = $emailRow->email;
	echo '\r\n' . sanitize_email( $email ) . ' Selected. \r\n';

	$rt_hd_settings->update_sync_status( $email, true );
	$last_sync_time = $emailRow->last_mail_time;

	if ( ! $last_sync_time ) {
		$dt = new DateTime( 'now' );
		$dt->sub( new DateInterval( 'P1D' ) );
		$last_sync_time = $dt->format( 'd-M-Y' );
	} else {
		$dt = new DateTime( $last_sync_time );
		$dt->sub( new DateInterval( 'P1D' ) );
		$last_sync_time = $dt->format( 'd-M-Y' );
	}
	global $rt_mail_uid;
	if ( $emailRow->last_mail_uid ) {
		$rt_mail_uid = unserialize( $emailRow->last_mail_uid );
	} else {
		$rt_mail_uid = array();
	}


	$signature    = '';
	$email_type   = '';
	$imap_server  = '';
	$access_token = $rt_hd_settings->get_accesstoken_from_email( $email, $signature, $email_type, $imap_server );

	$hdZendEmail = new Rt_HD_Zend_Mail();
	//System Mail
	$isSystemMail = false;
	if ( rthd_is_system_email( $email ) ) {
		$isSystemMail = true;
	}
	$hdZendEmail->reademail( $email, $access_token, $email_type, $imap_server, $last_sync_time, $emailRow->user_id, $isSystemMail, $signature );

	$rt_hd_settings->update_sync_status( $email, true );
	//thread Importer
	$hdZendEmail->reademail( $email, $access_token, $email_type, $imap_server, $last_sync_time, $emailRow->user_id, $isSystemMail, $signature, true );
	$rt_hd_settings->update_sync_status( $email, false );
}