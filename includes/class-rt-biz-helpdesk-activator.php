<?php
/**
 * Fired during plugin activation.
 *
 * User: spock
 */

class Rt_Biz_Helpdesk_Activator {

	/**
	 * Called on plugin active.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Add the option to redirect
		add_option( '_rthd_activation_redirect', true, 'no' );

		// Plugin is activated flush rewrite rules for example.com/ticket to work
		add_option( 'rthd_flush_rewrite_rules', 'true', 'no' );

	}

}
