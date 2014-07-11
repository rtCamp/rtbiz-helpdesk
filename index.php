<?php

/*
  Plugin Name: WordPress CRM
  Plugin URI: http://rtcamp.com/
  Description: Manage leads, contacts and followups
  Version: 0.1.10
  Author: rtCamp
  Author URI: http://rtcamp.com
  License: GPL
  Text Domain: rt_crm
 */

if ( !defined( 'RT_CRM_VERSION' ) ) {
	define( 'RT_CRM_VERSION', '0.1.9' );
}
if ( !defined( 'RT_CRM_TEXT_DOMAIN' ) ) {
	define( 'RT_CRM_TEXT_DOMAIN', 'rt_crm' );
}
if ( !defined( 'RT_CRM_PATH' ) ) {
	define( 'RT_CRM_PATH', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'RT_CRM_URL' ) ) {
	define( 'RT_CRM_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RT_CRM_PATH_APP' ) ) {
	define( 'RT_CRM_PATH_APP', plugin_dir_path( __FILE__ ) . 'app/' );
}
if ( !defined( 'RT_CRM_PATH_ADMIN' ) ) {
	define( 'RT_CRM_PATH_ADMIN', plugin_dir_path( __FILE__ ) . 'app/admin/' );
}
if ( !defined( 'RT_CRM_PATH_MODELS' ) ) {
	define( 'RT_CRM_PATH_MODELS', plugin_dir_path( __FILE__ ) . 'app/models/' );
}
if ( !defined( 'RT_CRM_PATH_SCHEMA' ) ) {
	define( 'RT_CRM_PATH_SCHEMA', plugin_dir_path( __FILE__ ) . 'app/schema/' );
}
if ( !defined( 'RT_CRM_PATH_LIB' ) ) {
	define( 'RT_CRM_PATH_LIB', plugin_dir_path( __FILE__ ) . 'app/lib/' );
}
if ( !defined( 'RT_CRM_PATH_VENDOR' ) ) {
	define( 'RT_CRM_PATH_VENDOR', plugin_dir_path( __FILE__ ) . 'app/vendor/' );
}
if ( !defined( 'RT_CRM_PATH_HELPER' ) ) {
	define( 'RT_CRM_PATH_HELPER', plugin_dir_path( __FILE__ ) . 'app/helper/' );
}
if ( !defined( 'RT_CRM_PATH_TEMPLATES' ) ) {
	define( 'RT_CRM_PATH_TEMPLATES', plugin_dir_path( __FILE__ ) . 'templates/' );
}

include_once RT_CRM_PATH_LIB . 'wp-helpers.php';

function rt_crm_include() {

	include_once RT_CRM_PATH_VENDOR . 'MailLib/zendAutoload.php';
	include_once RT_CRM_PATH_VENDOR . 'forceutf8/src/ForceUTF8/Encoding.php';
	include_once RT_CRM_PATH_VENDOR . 'excel_reader2.php';
	include_once RT_CRM_PATH_VENDOR . 'parsecsv.lib.php';
	include_once RT_CRM_PATH_VENDOR . 'rfc822_addresses.php';
	include_once RT_CRM_PATH_VENDOR . 'simplexlsx.php';
	include_once RT_CRM_PATH_VENDOR . 'taxonomy-metadata.php';

	include_once RT_CRM_PATH_HELPER . 'rtcrm-functions.php';

	global $rtcrm_app_autoload, $rtcrm_admin_autoload, $rtcrm_models_autoload, $rtcrm_helper_autoload, $rtcrm_form_autoload, $rtcrm_reports_autoload;
	$rtcrm_app_autoload = new RT_WP_Autoload( RT_CRM_PATH_APP );
	$rtcrm_admin_autoload = new RT_WP_Autoload( RT_CRM_PATH_ADMIN );
	$rtcrm_models_autoload = new RT_WP_Autoload( RT_CRM_PATH_MODELS );
	$rtcrm_helper_autoload = new RT_WP_Autoload( RT_CRM_PATH_HELPER );
	$rtcrm_form_autoload = new RT_WP_Autoload( RT_CRM_PATH_LIB . 'rtformhelpers/' );
	$rtcrm_reports_autoload = new RT_WP_Autoload( RT_CRM_PATH_LIB . 'rtreports/' );
}

function rt_crm_init() {

	rt_crm_include();

	global $rt_wp_crm;
	$rt_wp_crm = new RT_WP_CRM();

}
add_action( 'rt_biz_init', 'rt_crm_init', 1 );
