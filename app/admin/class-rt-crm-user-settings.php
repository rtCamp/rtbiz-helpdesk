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
 * Description of Rt_CRM_User_Settings
 *
 * @author udit
 */
if ( !class_exists( 'Rt_CRM_User_Settings' ) ) {
	class Rt_CRM_User_Settings {
		var $page_url;

		public function __construct() {
			$this->page_url = admin_url( 'admin.php?page=rtcrm-user-settings' );
		}

		function ui() {
			global $rt_crm_settings, $rt_crm_imap_server_model, $rt_crm_module;

			$args = array(
				'rt_crm_settings' => $rt_crm_settings,
				'rt_crm_imap_server_model' => $rt_crm_imap_server_model,
				'rt_crm_module' => $rt_crm_module,
			);
			rtcrm_get_template( 'admin/user-settings.php', $args );
		}
	}
}