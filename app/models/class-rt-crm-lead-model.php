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
 * Description of RtCRMLeadModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_CRM_Lead_Model' ) ) {
	class Rt_CRM_Lead_Model extends RT_DB_Model {

		public function __construct() {
			$table_name = rtcrm_get_lead_table_name();
			parent::__construct( $table_name, true );
		}

		function add_lead( $data ) {
			return parent::insert( $data );
		}

		function update_lead( $data, $where ) {
			return parent::update( $data, $where );
		}

		function delete_lead( $where ) {
			return parent::delete( $where );
		}
	}
}