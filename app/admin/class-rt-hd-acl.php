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
if ( ! class_exists( 'Rt_HD_ACL' ) ) {
	/**
	 * Class Rt_HD_ACL
	 * Add ACL(access control list) support to help desk plugin
	 *
	 * @since 0.1
	 */
	class Rt_HD_ACL {
		/**
		 * Hook for register rtbiz-HelpDesk module with rtbiz
		 *
		 * @since 0.1
		 */
		public function __construct() {
			add_filter( 'rt_biz_modules', array( $this, 'register_rt_hd_module' ) );
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
			$settings               = rthd_get_redux_settings();
			$module_key             = rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN );
			$modules[ $module_key ] = array(
				'label'      => isset( $settings['rthd_menu_label'] ) ? $settings['rthd_menu_label'] : 'Helpdesk',
				'post_types' => array( Rt_HD_Module::$post_type ),
				'department_support' => array( Rt_HD_Module::$post_type ),
				'offering_support' => array( Rt_HD_Module::$post_type ),
				'setting_option_name' => Redux_Framework_Helpdesk_Config::$hd_opt, // Use For ACL
				'mailbox_setting_page_url' => admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&page=rthd-settings'), // for Mailbox
			);
			return $modules;
		}
	}
}
