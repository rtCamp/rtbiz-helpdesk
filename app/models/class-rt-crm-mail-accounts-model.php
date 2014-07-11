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
 * Description of RtCRMMailAccountsModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_CRM_Mail_Accounts_Model' ) ) {
	class Rt_CRM_Mail_Accounts_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_crm_mail_accounts' );
		}

		function add_mail_account( $data ) {
			return parent::insert( $data );
		}

		function get_mail_account( $where ) {
			return parent::get( $where );
		}

		function update_mail_account( $data, $where ) {
			return parent::update( $data, $where );
		}

		function remove_mail_account( $where ) {
			return parent::delete( $where );
		}

		function get_all_mail_accounts() {
			return parent::get( array() );
		}
	}
}
