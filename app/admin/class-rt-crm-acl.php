<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of Rt_HD_ACL
 *
 * @author udit
 */
if ( ! class_exists( 'Rt_HD_ACL' ) ) {
	class Rt_HD_ACL {
		public function __construct() {
			add_filter( 'rt_biz_modules', array( $this, 'register_rt_hd_module' ) );
		}

		function register_rt_hd_module( $modules ) {
			global $rt_hd_module;
			$menu_label = get_site_option( 'rthd_menu_label', __('rtHelpdesk') );
			$module_key = rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN );
			$modules[ $module_key ] = array(
				'label' => $menu_label,
				'post_types' => array( $rt_hd_module->post_type ),
			);
			return $modules;
		}
	}
}
