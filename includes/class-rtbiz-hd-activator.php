<?php
/**
 * Fired during plugin activation
 *
 * @link       https://rtcamp.com/
 * @since      1.2.19
 *
 * @package    rtbiz-helpdesk
 * @subpackage rtbiz-helpdesk/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.2.19
 * @package    rtbiz-helpdesk
 * @subpackage rtbiz-helpdesk/includes
 * @author     Dipesh <dipesh.kakadiya@rtcamp.com>
 */
if ( ! class_exists( 'Rtbiz_HD_Activator' ) ) {
	class Rtbiz_HD_Activator {

		/**
		 * Short Description. (use period)
		 *
		 * Long Description.
		 *
		 * @since    1.0.0
		 */
		public static function activate() {

			// Add the option to redirect
			update_option( 'rtbiz_hd_activation_redirect', true, false );

			// Plugin is activated flush rewrite rules for example.com/ticket to work
			update_option( 'rtbiz_hd_flush_rewrite_rules', true, false );
		}

	}
}

