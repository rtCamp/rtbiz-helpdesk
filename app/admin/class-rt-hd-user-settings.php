<?php
/**
 * Don't load this file directly!
 */
if (!defined('ABSPATH'))
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_User_Settings
 *
 * @author udit
 *
 * @since rt-Helpdesk 0.1
 */
if ( !class_exists( 'Rt_HD_User_Settings' ) ) {
	class Rt_HD_User_Settings {
		var $page_url;

		public function __construct() {
			$this->page_url = admin_url( 'admin.php?page=rthd-user-settings' );
		}

		function ui() {
			global $rt_hd_settings, $rt_hd_imap_server_model, $rt_hd_module;

			$args = array(
				'rt_hd_settings' => $rt_hd_settings,
				'rt_hd_imap_server_model' => $rt_hd_imap_server_model,
				'rt_hd_module' => $rt_hd_module,
			);
			rthd_get_template( 'admin/user-settings.php', $args );
		}
	}
}