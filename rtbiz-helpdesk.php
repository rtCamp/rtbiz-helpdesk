<?php

/**
 * Plugin Name: rtBiz Helpdesk
 * Plugin URI: http://rtcamp.com/
 * Description: A WordPress based Helpdesk system with mail sync features, web based ticket UI and many custom settings. Easy to use for admin, staff and customers.
 * Version: 1.2.6
 * Author: rtCamp
 * Author URI: http://rtcamp.com
 * License: GPL
 * Text Domain: rtbiz-helpdesk
 * Contributors: Udit<udit.desai@rtcamp.com>, Dipesh<dipesh.kakadiya@rtcamp.com>, Utkarsh<utkarsh.patel@rtcamp.com>
 */

if ( ! defined( 'RT_BIZ_HD_VERSION' ) ) {
	/**
	 * Defines RT_BIZ_HD_VERSION if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_VERSION', '1.2.6' );
}
if ( ! defined( 'RT_BIZ_HD_TEXT_DOMAIN' ) ) {
	/**
	 * Defines RT_BIZ_HD_TEXT_DOMAIN if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_TEXT_DOMAIN', 'rtbiz-helpdesk' );
}
if ( ! defined( 'RT_BIZ_HD_BASE_NAME' ) ) {
	/**
	 * Defines RT_BIZ_HD_PATH if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_BASE_NAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'RT_BIZ_HD_PATH' ) ) {
	/**
	 * Defines RT_BIZ_HD_PATH if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RT_HD_URL' ) ) {
	/**
	 * Defines RT_HD_URL if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RT_BIZ_HD_PATH_INCLUDE' ) ) {
	/**
	 * Defines app folder path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH_INCLUDE', plugin_dir_path( __FILE__ ) . 'includes/' );
}

if ( ! defined( 'RT_BIZ_HD_PATH_ADMIN' ) ) {
	/**
	 * Defines app folder path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH_ADMIN', plugin_dir_path( __FILE__ ) . 'admin/' );
}
if ( ! defined( 'RT_BIZ_HD_PATH_PUBLIC' ) ) {
	/**
	 * Defines app folder path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH_PUBLIC', plugin_dir_path( __FILE__ ) . 'public/' );
}

if ( ! defined( 'RT_BIZ_HD_PATH_SCHEMA' ) ) {
	/**
	 *  Defines app/schema path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH_SCHEMA', plugin_dir_path( __FILE__ ) . 'admin/schema/' );
}
if ( ! defined( 'RT_BIZ_HD_PATH_TEMPLATES' ) ) {
	/**
	 * Defines templates/ path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_BIZ_HD_PATH_TEMPLATES', plugin_dir_path( __FILE__ ) . 'public/templates/' );
}

if ( ! defined( 'EDD_RT_HELPDESK_STORE_URL' ) ) {
	/**
	 * Defines helpdesk store url
	 *
	 * @since 0.1
	 */
	define( 'EDD_RT_HELPDESK_STORE_URL', 'https://rtcamp.com/' );
}

if ( ! defined( 'EDD_RT_HELPDESK_ITEM_NAME' ) ) {
	/**
	 * define helpdesk item slug
	 *
	 * @since 0.1
	 */
	define( 'EDD_RT_HELPDESK_ITEM_NAME', 'rtBiz Helpdesk' );
}

include_once( RT_BIZ_HD_PATH_ADMIN . 'vendor/edd-license/class-rt-hd-edd-license.php' );
new Rt_HD_Edd_License();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rt-biz-helpdesk-activator.php
 */
function activate_rtbiz_helpdesk() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rt-biz-helpdesk-activator.php';
	Rt_Biz_Helpdesk_Activator::activate();

}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_rtbiz_helpdesk() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rt-biz-helpdesk-deactivator.php';
	Rt_Biz_Helpdesk_deactivator::deactivate();

}

register_activation_hook( __FILE__, 'activate_rtbiz_helpdesk' );
register_activation_hook( __FILE__, 'deactivate_rtbiz_helpdesk' );


/**
 * Main function that initiate rt-helpdesk plugin
 *
 * @since 0.1
 */
function run_rtbiz_helpdesk() {

	require RT_BIZ_HD_PATH_INCLUDE. 'class-rt-biz-helpdesk.php';
	global $rt_biz_hd;
	$rt_biz_hd = new Rt_Biz_Helpdesk();
	$rt_biz_hd->run();

}
add_action( 'rt_biz_init', 'run_rtbiz_helpdesk', 1 );
