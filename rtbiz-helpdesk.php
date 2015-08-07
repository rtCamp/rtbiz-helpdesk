<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://rtcamp.com/
 * @since             1.2.6
 * @package           rtbiz-helpdesk
 *
 * @wordpress-plugin
 * Plugin Name:       rtBiz Helpdesk
 * Plugin URI:        https://rtcamp.com/rtbiz/helpdesk/
 * Description:       A WordPress based Helpdesk system with mail sync features, web based ticket UI and many custom settings. Easy to use for admin, staff and customers.
 * Version:           1.4
 * Author:            rtCamp
 * Author URI:        https://rtcamp.com/
 * License:           GPL-2.0+
 * License URI:       https://rtcamp.com/
 * Text Domain:       rtbiz-helpdesk
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'RTBIZ_HD_VERSION' ) ) {
	define( 'RTBIZ_HD_VERSION', '1.4' );
}

if ( ! defined( 'RTBIZ_HD_TEXT_DOMAIN' ) ) {
	define( 'RTBIZ_HD_TEXT_DOMAIN', 'rtbiz-helpdesk' );
}

if ( ! defined( 'RTBIZ_HD_PLUGIN_FILE' ) ) {
	define( 'RTBIZ_HD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'RTBIZ_HD_PATH' ) ) {
	define( 'RTBIZ_HD_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'RTBIZ_HD_URL' ) ) {
	define( 'RTBIZ_HD_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'RTBIZ_HD_BASE_NAME' ) ) {
	define( 'RTBIZ_HD_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'RTBIZ_HD_PATH_TEMPLATES' ) ) {
	define( 'RTBIZ_HD_PATH_TEMPLATES', plugin_dir_path( __FILE__ ) . 'public/templates/' );
}

if ( ! defined( 'EDD_RT_HELPDESK_STORE_URL' ) ) {

	define( 'EDD_RT_HELPDESK_STORE_URL', 'https://rtcamp.com/' );
}

if ( ! defined( 'EDD_RT_HELPDESK_ITEM_NAME' ) ) {

	define( 'EDD_RT_HELPDESK_ITEM_NAME', 'rtBiz Helpdesk' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rt-biz-helpdesk-activator.php
 */
function activate_rtbiz_hd() {

	require_once RTBIZ_HD_PATH . 'includes/class-rtbiz-hd-activator.php';
	Rtbiz_HD_Activator::activate();

}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_rtbiz_hd() {

	require_once RTBIZ_HD_PATH . 'includes/class-rtbiz-hd-deactivator.php';
	Rtbiz_HD_deactivator::deactivate();

}

register_activation_hook( RTBIZ_HD_PLUGIN_FILE, 'activate_rtbiz_hd' );
register_activation_hook( RTBIZ_HD_PLUGIN_FILE, 'deactivate_rtbiz_hd' );

function rtbiz_redirection_remove(){
	update_option( 'rtbiz_activation_redirect', false, false );
}
add_action( 'rtbiz_activation_redirect', 'rtbiz_redirection_remove' );


require_once RTBIZ_HD_PATH . 'vendor/edd-license/class-rt-hd-edd-license.php';
new Rt_HD_Edd_License();

require_once RTBIZ_HD_PATH . 'includes/class-rtbiz-hd-plugin-check.php';

global $rtbiz_hd_plugin_check;

$plugins_dependency = array(
	'rtbiz' => array(
		'project_type' => 'all',
		'name'         => esc_html__( 'rtBiz', RTBIZ_HD_TEXT_DOMAIN ),
		'desc' => esc_html__( 'WordPress for Business.', RTBIZ_HD_TEXT_DOMAIN ),
		'active' => class_exists( 'Rt_Biz' ),
		'filename' => 'rtbiz.php',
	),
);

$rtbiz_hd_plugin_check = new Rtbiz_HD_Plugin_Check( $plugins_dependency );

add_action( 'init', array( $rtbiz_hd_plugin_check, 'rtbiz_hd_check_plugin_dependency' ) );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rtbiz_hd() {

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require_once RTBIZ_HD_PATH. 'includes/class-rtbiz-hd.php';

	global $rtbiz_hd;

	$rtbiz_hd = new Rtbiz_HD();

}
add_action( 'rtbiz_init', 'run_rtbiz_hd', 1 );
