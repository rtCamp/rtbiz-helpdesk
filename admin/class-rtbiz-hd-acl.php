<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of Rt_HD_ACL
 *
 * @author udit
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rtbiz_HD_ACL' ) ) {
	/**
	 * Class Rt_HD_ACL
	 * Add ACL(access control list) support to help desk plugin
	 *
	 * @since 0.1
	 */
	class Rtbiz_HD_ACL {
		/**
		 * Hook for register rtbiz-HelpDesk module with rtbiz
		 *
		 * @since 0.1
		 */
		public function __construct() {
			add_filter( 'rtbiz_modules', array( $this, 'register_rt_hd_module' ) );
		}

		/**
		 * Register module rtbiz-HelpDesk
		 *
		 * @since 0.1
		 *
		 * @param $modules
		 *
		 * @return mixed
		 */
		function register_rt_hd_module( $modules ) {
			$settings               = rtbiz_hd_get_redux_settings();
			$module_key             = rtbiz_sanitize_module_key( RTBIZ_HD_TEXT_DOMAIN );
			$modules[ $module_key ] = array(
				'label'      => 'Helpdesk',
				'post_types' => array( Rtbiz_HD_Module::$post_type ),
				'department_support' => array( Rtbiz_HD_Module::$post_type ),
				'offering_support' => array( Rtbiz_HD_Module::$post_type ),
				'setting_option_name' => Rtbiz_HD_Settings::$hd_opt, // Use for setting page acl to add manage_options capability
				'setting_page_url' => admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&page=rthd-settings' ), //
			    'email_template_support' => array( Rtbiz_HD_Module::$post_type ),
			);
			return $modules;
		}
	}
}
