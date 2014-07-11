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
 * Description of RtCRMMailThreadImporterModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_CRM_Mail_Thread_Importer_Model' ) ) {
	class Rt_CRM_Mail_Thread_Importer_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_crm_mail_thread_importer' );
		}

		function add_thread( $data ) {
			return parent::insert( $data );
		}

		function get_thread( $where ) {
			return parent::get( $where );
		}

		function update_thread( $data, $where ) {
			return parent::update( $data, $where );
		}
	}
}
