<?php

/*
  Plugin Name: WordPress Helpdesk
  Plugin URI: http://rtcamp.com/
  Description: Helpdesk System for Wordpress
  Version: 0.0.1
  Author: rtCamp
  Author URI: http://rtcamp.com
  License: GPL
  Text Domain: rt_helpdesk
 */

if ( ! defined( 'RT_HD_VERSION' ) ) {
	define( 'RT_HD_VERSION', '0.0.1' );
}
if ( ! defined( 'RT_HD_TEXT_DOMAIN' ) ) {
	define( 'RT_HD_TEXT_DOMAIN', 'rt_helpdesk' );
}
if ( ! defined( 'RT_HD_PATH' ) ) {
	define( 'RT_HD_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RT_HD_URL' ) ) {
	define( 'RT_HD_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RT_HD_PATH_APP' ) ) {
	define( 'RT_HD_PATH_APP', plugin_dir_path( __FILE__ ) . 'app/' );
}
if ( ! defined( 'RT_HD_PATH_ADMIN' ) ) {
	define( 'RT_HD_PATH_ADMIN', plugin_dir_path( __FILE__ ) . 'app/admin/' );
}
if ( ! defined( 'RT_HD_PATH_MODELS' ) ) {
	define( 'RT_HD_PATH_MODELS', plugin_dir_path( __FILE__ ) . 'app/models/' );
}
if ( ! defined( 'RT_HD_PATH_SCHEMA' ) ) {
	define( 'RT_HD_PATH_SCHEMA', plugin_dir_path( __FILE__ ) . 'app/schema/' );
}
if ( ! defined( 'RT_HD_PATH_LIB' ) ) {
	define( 'RT_HD_PATH_LIB', plugin_dir_path( __FILE__ ) . 'app/lib/' );
}
if ( ! defined( 'RT_HD_PATH_VENDOR' ) ) {
	define( 'RT_HD_PATH_VENDOR', plugin_dir_path( __FILE__ ) . 'app/vendor/' );
}
if ( ! defined( 'RT_HD_PATH_HELPER' ) ) {
	define( 'RT_HD_PATH_HELPER', plugin_dir_path( __FILE__ ) . 'app/helper/' );
}
if ( ! defined( 'RT_HD_PATH_TEMPLATES' ) ) {
	define( 'RT_HD_PATH_TEMPLATES', plugin_dir_path( __FILE__ ) . 'templates/' );
}

include_once RT_HD_PATH_LIB . 'wp-helpers.php';

function rt_hd_include() {

	include_once RT_HD_PATH_VENDOR . 'MailLib/zendAutoload.php';
	include_once RT_HD_PATH_VENDOR . 'forceutf8/src/ForceUTF8/Encoding.php';
	include_once RT_HD_PATH_VENDOR . 'excel_reader2.php';
	include_once RT_HD_PATH_VENDOR . 'parsecsv.lib.php';
	include_once RT_HD_PATH_VENDOR . 'rfc822_addresses.php';
	include_once RT_HD_PATH_VENDOR . 'simplexlsx.php';
	include_once RT_HD_PATH_VENDOR . 'taxonomy-metadata.php';

	include_once RT_HD_PATH_HELPER . 'rthd-functions.php';

	global $rthd_app_autoload, $rthd_admin_autoload, $rthd_models_autoload, $rthd_helper_autoload, $rthd_form_autoload, $rthd_reports_autoload, $rthd_admin_metabox_autoload;
	$rthd_app_autoload = new RT_WP_Autoload( RT_HD_PATH_APP );
	$rthd_admin_autoload = new RT_WP_Autoload( RT_HD_PATH_ADMIN );
	$rthd_admin_metabox_autoload = new RT_WP_Autoload( RT_HD_PATH_ADMIN . 'meta-box/' );
	$rthd_models_autoload = new RT_WP_Autoload( RT_HD_PATH_MODELS );
	$rthd_helper_autoload = new RT_WP_Autoload( RT_HD_PATH_HELPER );
	$rthd_form_autoload = new RT_WP_Autoload( RT_HD_PATH_LIB . 'rtformhelpers/' );
	$rthd_reports_autoload = new RT_WP_Autoload( RT_HD_PATH_LIB . 'rtreports/' );
}

function rt_hd_init() {

	rt_hd_include();

	global $rt_wp_hd;
	$rt_wp_hd = new RT_WP_Helpdesk();
}

add_action( 'rt_biz_init', 'rt_hd_init', 1 );

function rt_hd_check_dependency() {
	global $rt_wp_hd;
	if ( empty( $rt_wp_hd ) ) {
		rt_hd_include();
		$rt_wp_hd = new RT_WP_Helpdesk();
	}
}
add_action( 'init', 'rt_hd_check_dependency' );
