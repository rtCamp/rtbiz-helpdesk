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
global $rt_hd_settings;
$emailRow = $rt_hd_settings->get_new_sent_mail();
if ( empty( $emailRow ) ) {
	return;
}
$hdZendEmail      = new Rt_HD_Zend_Mail();
$accessTokenArray = array();
$signature        = '';
$settings         = rthd_get_settings();
foreach ( $emailRow as $email ) {
	if ( ! isset( $accessTokenArray[ $email->fromemail ] ) ) {
		$email_type                            = '';
		$imap_server                           = '';
		$accessTokenArray[ $email->fromemail ] = array(
			'token'       => $rt_hd_settings->get_accesstoken_from_email( $email->fromemail, $signature, $email_type, $imap_server ),
			'email_type'  => $email_type,
			'imap_server' => $imap_server,
		);
	}
	if ( $rt_hd_settings->update_sent_email( $email->id, 'p', 'no' ) > 0 ) {
		$updateFlag = false;
		try {
			if ( isset( $settings['outgoing_email_delivery'] ) && ! empty( $settings['outgoing_email_delivery'] ) ) {
				if ( $settings['outgoing_email_delivery'] == 'wp_mail' ) {

					$arrayBCC = unserialize( $email->bccemail );
					$arrayCC   = unserialize( $email->ccemail );
					$arrayTo   = unserialize( $email->toemail );
					$headers[] = 'From:' . $settings['outbound_emails'];
					add_filter( 'wp_mail_from', 'rthd_my_mail_from' );
					if ( ! empty( $arrayBCC ) ) {
						foreach ( $arrayBCC as $temail ) {
							add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
							$res = wp_mail( array( $temail['email'] ), $email->subject, $email->body, $headers );
							remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
						}
					}

					if ( ! empty( $arrayCC ) ) {
						foreach ( $arrayCC as $temail ) {
							add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
							$res = wp_mail( array( $temail['email'] ), $email->subject, $email->body, $headers );
							remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
						}
					}

					if ( ! empty( $arrayTo ) ) {
						foreach ( $arrayTo as $key => $temail ) {
							add_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
							$res = wp_mail( array( $temail['email'] ), $email->subject, $email->body, $headers );
							remove_filter( 'wp_mail_content_type', 'rthd_set_html_content_type' );
						}
					}
				} elseif ( $settings['outgoing_email_delivery'] == 'user_mail_login' ) {
					$hdZendEmail->sendemail( $email->fromemail, $accessTokenArray[ $email->fromemail ]['token'], $accessTokenArray[ $email->fromemail ]['email_type'], $accessTokenArray[ $email->fromemail ]['imap_server'], $email->subject, $email->body, unserialize( $email->toemail ), unserialize( $email->ccemail ), unserialize( $email->bccemail ), unserialize( $email->attachement ) );
				} elseif ( $settings['outgoing_email_delivery'] == 'amazon_ses' ) {

				} elseif ( $settings['outgoing_email_delivery'] == 'google_smtp' ) {

				}
				$updateFlag = true;
			}
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