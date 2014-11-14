<?php

/**
 * Plugin Name: rtBiz Helpdesk
 * Plugin URI: http://rtcamp.com/
 * Description: Helpdesk System for handle & track User request for Help
 * Version: 1.1
 * Author: rtCamp
 * Author URI: http://rtcamp.com
 * License: GPL
 * Text Domain: rt_helpdesk
 * Contributors: Udit<udit.desai@rtcamp.com>, Dipesh<dipesh.kakadiya@rtcamp.com>, Utkarsh<utkarsh.patel@rtcamp.com>
 */

if ( ! defined( 'RT_HD_VERSION' ) ) {
	/**
	 * Defines RT_HD_VERSION if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_VERSION', '1.0' );
}
if ( ! defined( 'RT_HD_TEXT_DOMAIN' ) ) {
	/**
	 * Defines RT_HD_TEXT_DOMAIN if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_TEXT_DOMAIN', 'rt_helpdesk' );
}
if ( ! defined( 'RT_HD_PATH' ) ) {
	/**
	 * Defines RT_HD_PATH if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RT_HD_URL' ) ) {
	/**
	 * Defines RT_HD_URL if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RT_HD_PATH_APP' ) ) {
	/**
	 * Defines app folder path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_APP', plugin_dir_path( __FILE__ ) . 'app/' );
}
if ( ! defined( 'RT_HD_PATH_ADMIN' ) ) {
	/**
	 *  Defines app/admin path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_ADMIN', plugin_dir_path( __FILE__ ) . 'app/admin/' );
}
if ( ! defined( 'RT_HD_PATH_MODELS' ) ) {
	/**
	 * Defines app/models path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_MODELS', plugin_dir_path( __FILE__ ) . 'app/models/' );
}
if ( ! defined( 'RT_HD_PATH_SCHEMA' ) ) {
	/**
	 *  Defines app/schema path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_SCHEMA', plugin_dir_path( __FILE__ ) . 'app/schema/' );
}
if ( ! defined( 'RT_HD_PATH_LIB' ) ) {
	/**
	 * Defines app/lib path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_LIB', plugin_dir_path( __FILE__ ) . 'app/lib/' );
}
if ( ! defined( 'RT_HD_PATH_VENDOR' ) ) {
	/**
	 *  Defines app/vendor path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_VENDOR', plugin_dir_path( __FILE__ ) . 'app/vendor/' );
}
if ( ! defined( 'RT_HD_PATH_HELPER' ) ) {
	/**
	 * Defines app/helper path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_HELPER', plugin_dir_path( __FILE__ ) . 'app/helper/' );
}
if ( ! defined( 'RT_HD_PATH_TEMPLATES' ) ) {
	/**
	 * Defines templates/ path if it does not exits.
	 *
	 * @since 0.1
	 */
	define( 'RT_HD_PATH_TEMPLATES', plugin_dir_path( __FILE__ ) . 'templates/' );
}

include_once RT_HD_PATH_LIB . 'rt-lib.php';

/**
 * Using rt-lib [ RT_WP_Autoload ] class, Includes all files & external Require Libraries with in given directory.
 *
 * @since 0.1
 */
function rt_hd_include() {

	include_once RT_HD_PATH_VENDOR . 'MailLib/zendAutoload.php';
	include_once RT_HD_PATH_VENDOR . 'forceutf8/src/ForceUTF8/Encoding.php';
	include_once RT_HD_PATH_VENDOR . 'excel_reader2.php';
	include_once RT_HD_PATH_VENDOR . 'parsecsv.lib.php';
	include_once RT_HD_PATH_VENDOR . 'rfc822_addresses.php';
	include_once RT_HD_PATH_VENDOR . 'simplexlsx.php';
	include_once RT_HD_PATH_VENDOR . 'taxonomy-metadata.php';

	require_once RT_HD_PATH_VENDOR . 'redux/ReduxCore/framework.php';

	include_once RT_HD_PATH_HELPER . 'rthd-functions.php';

	global $rthd_app_autoload, $rthd_admin_autoload, $rthd_admin_metabox_autoload, $rthd_models_autoload, $rthd_helper_autoload, $rthd_settings_autoload, $rthd_form_autoload, $rthd_reports_autoload;
	$rthd_app_autoload           = new RT_WP_Autoload( RT_HD_PATH_APP );
	$rthd_admin_autoload         = new RT_WP_Autoload( RT_HD_PATH_ADMIN );
	$rthd_admin_metabox_autoload = new RT_WP_Autoload( RT_HD_PATH_ADMIN . 'meta-box/' );
	$rthd_models_autoload        = new RT_WP_Autoload( RT_HD_PATH_MODELS );
	$rthd_helper_autoload        = new RT_WP_Autoload( RT_HD_PATH_HELPER );
	$rthd_settings_autoload      = new RT_WP_Autoload( RT_HD_PATH_APP . 'settings' );
	$rthd_form_autoload          = new RT_WP_Autoload( RT_HD_PATH_LIB . 'rtformhelpers/' );
	$rthd_reports_autoload       = new RT_WP_Autoload( RT_HD_PATH_LIB . 'rtreports/' );

}

/**
 * Main function that initiate rt-helpdesk plugin
 *
 * @since 0.1
 */
function rt_hd_init() {

	rt_hd_include();

	global $rt_wp_hd;
	$rt_wp_hd = new RT_WP_Helpdesk();
}
add_action( 'rt_biz_init', 'rt_hd_init', 1 );

/**
 * rt_hd_check_dependency check for rtbiz-HelpDesk dependency
 * dependencies are require to run file else this plugin can't function
 *
 * @since 0.1
 */
function rt_hd_check_dependency() {
	global $rt_wp_hd;
	if ( empty( $rt_wp_hd ) ) {
		rt_hd_include();
		$rt_wp_hd = new RT_WP_Helpdesk();
	}
}
//add_action( 'init', 'rt_hd_check_dependency' );


register_activation_hook( __FILE__, 'init_call_flush_rewrite_rules' );
function init_call_flush_rewrite_rules(){
	add_option( 'rthd_flush_rewrite_rules', 'true' );
}
