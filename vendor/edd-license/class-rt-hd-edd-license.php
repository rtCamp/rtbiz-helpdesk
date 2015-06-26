<?php

/**
 * Created by PhpStorm.
 * User: dipesh
 */
if ( ! class_exists( 'Rt_HD_Edd_License' ) ) {
	class Rt_HD_Edd_License {

		function __construct() {

			if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
				// load our custom updater
				include_once( RTBIZ_HD_PATH . 'vendor/edd-license/class-edd-sl-plugin-updater.php' );
			}

			add_action( 'admin_init', array( $this, 'edd_sl_sample_plugin_updater' ) );
			add_action( 'admin_init', array( $this, 'edd_sample_save_activate_license' ) );
			add_action( 'admin_init', array( $this, 'edd_sample_remove_deactivate_license' ) );
			add_action( 'rthelpdesk_addon_license_details', array( $this, 'edd_sample_license_page' ), 10 );

		}

		function edd_sample_license_page() {
			$license = get_option( 'rtbiz_hd_edd_license_key' );
			$status = get_option( 'rtbiz_hd_edd_license_status' );

			if ( false !== $status && 'valid' == $status ) {
				$status_class = 'rthd-addon-status-activated';
				$status_value = __( 'Activated', 'rtmedia' );
			} else {
				$status_class = 'rthd-addon-status-deactivated';
				$status_value = __( 'Deactivated', 'rtmedia' );
			}
			?>
            <div class="rthd-addon-license">
                <div class="row">
                    <div class="columns large-12">
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
	                            <th scope="row" valign="top">
		                            <fieldset class="redux-field-container"><span class="rthd-addon-license-status-label"> Helpdesk Status: </span></fieldset>
	                            </th>
	                            <td>
		                            <fieldset class="redux-field-container"><span class="rthd-addon-license-status <?php echo $status_class ?>"><?php echo $status_value; ?></span></fieldset>
	                            </td>
                            </tr>
                            <tr valign="top" style="border-bottom: medium none;">
                                <th scope="row" valign="top">
	                                <fieldset class="redux-field-container"><?php _e( 'License Key', RTBIZ_HD_TEXT_DOMAIN ); ?></fieldset>
                                </th>
                                <td>
	                                <fieldset class="redux-field-container"><input id="rtbiz_hd_edd_license_key" name="rtbiz_hd_edd_license_key"
                                           type="text"
                                           class="regular-text" value="<?php esc_attr_e( $license ); ?>"/></fieldset>
                                </td>
                            </tr>
                            <tr valign="top" >
	                            <th scope="row" valign="top">
		                            <fieldset><?php _e( '', RTBIZ_HD_TEXT_DOMAIN ); ?></fieldset>
	                            </th>
	                            <td><fieldset><?php
		                            wp_nonce_field( 'edd_rthelpdesk_nonce', 'edd_rthelpdesk_nonce' );
		                            if ( false !== $license && ! empty( $license ) ) {
			                            if ( $status !== false && $status == 'valid' ) { ?>
	                                        <input type="submit" class="button-secondary"
	                                               name="edd_rthelpdesk_license_deactivate"
	                                               value="<?php _e( 'Deactivate License', RTBIZ_HD_TEXT_DOMAIN ); ?>"/><?php
										} else { ?>
	                                        <input type="submit" class="button-secondary"
	                                               name="edd_rthelpdesk_license_activate"
	                                               value="<?php _e( 'Activate License', RTBIZ_HD_TEXT_DOMAIN ); ?>"/><?php
			                            } ?>
			                            <input type="submit" class="button-secondary"
			                                   name="edd_rthelpdesk_remove_deactivate"
			                                   value="<?php _e( 'Remove License', RTBIZ_HD_TEXT_DOMAIN ); ?>"/><?php
		                            } else{ ?>
			                            <input type="submit" class="button-secondary"
			                                   name="edd_rthelpdesk_save_activate"
			                                   value="<?php _e( 'Save & Activate License', RTBIZ_HD_TEXT_DOMAIN ); ?>"/><?php
                                    } ?>
	                            </fieldset></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php
		}

		function edd_sl_sample_plugin_updater() {

			// retrieve our license key from the DB
			$license_key = trim( get_option( 'rtbiz_hd_edd_license_key' ) );

			// setup the updater
			$edd_updater = new EDD_SL_Plugin_Updater( EDD_RT_HELPDESK_STORE_URL, RTBIZ_HD_BASE_NAME, array(
				'version' => RTBIZ_HD_VERSION, // current version number
				'license' => $license_key, // license key (used get_option above to retrieve from DB)
				'item_name' => EDD_RT_HELPDESK_ITEM_NAME, // name of this plugin
				'author' => 'rtCamp', // author of this plugin
			) );

		}

		function edd_sample_save_activate_license(){
			if ( isset( $_POST['edd_rthelpdesk_save_activate'] ) ){
				if ( isset( $_POST['rtbiz_hd_edd_license_key'] ) && ! empty( $_POST['rtbiz_hd_edd_license_key'] ) ) {
					update_option( 'rtbiz_hd_edd_license_key', $_POST['rtbiz_hd_edd_license_key'] );
					$this->edd_sample_activate_license();
				}
			}

			// listen for our activate button to be clicked
			if ( isset( $_POST['edd_rthelpdesk_license_activate'] ) ) {
				$this->edd_sample_activate_license();
			}


		}

		function edd_sample_activate_license() {

			// run a quick security check
			if ( ! check_admin_referer( 'edd_rthelpdesk_nonce', 'edd_rthelpdesk_nonce' ) ) {
				return;
			} // get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( 'rtbiz_hd_edd_license_key' ) );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
			'license' => $license,
			'item_name' => urlencode( EDD_RT_HELPDESK_ITEM_NAME ), // the name of our product in EDD
				'url' => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_RT_HELPDESK_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			//		var_dump($response);

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "valid" or "invalid"

			update_option( 'rtbiz_hd_edd_license_status', $license_data->license );

		}

		function edd_sample_remove_deactivate_license(){
			if ( isset( $_POST['edd_rthelpdesk_remove_deactivate'] ) ){
				$status = get_option( 'rtbiz_hd_edd_license_status' );
				if ( false !== $status && 'valid' == $status ){
					$this->edd_sample_deactivate_license();
				}

				delete_option( 'rtbiz_hd_edd_license_key' );
			}

			// listen for our deactivate button to be clicked
			if ( isset( $_POST['edd_rthelpdesk_license_deactivate'] ) ) {
				$this->edd_sample_deactivate_license();
			}
		}

		function edd_sample_deactivate_license() {

			// run a quick security check
			if ( ! check_admin_referer( 'edd_rthelpdesk_nonce', 'edd_rthelpdesk_nonce' ) ) {
				return;
			} // get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( 'rtbiz_hd_edd_license_key' ) );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
			'license' => $license,
			'item_name' => urlencode( EDD_RT_HELPDESK_ITEM_NAME ), // the name of our product in EDD
				'url' => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_RT_HELPDESK_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			//		var_dump($response);

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			//		var_dump($license_data);

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' ) {
				delete_option( 'rtbiz_hd_edd_license_status' );
			}

		}

		function edd_sample_check_license() {

			global $wp_version;

			$license = trim( get_option( 'rtbiz_hd_edd_license_key' ) );

			$api_params = array(
				'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode( EDD_RT_HELPDESK_ITEM_NAME ),
			'url' => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_RT_HELPDESK_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $license_data->license == 'valid' ) {
				echo 'valid';
				exit;
				// this license is still valid
			} else {
				echo 'invalid';
				exit;
				// this license is no longer valid
			}
		}
	}
}
