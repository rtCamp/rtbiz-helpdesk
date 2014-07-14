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
 * Description of RtHDTicketModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Ticket_Model' ) ) {
	class Rt_HD_Ticket_Model extends RT_DB_Model {

		public function __construct() {
			$table_name = rthd_get_ticket_table_name();
			parent::__construct( $table_name, true );
		}

		function add_ticket( $data ) {
			return parent::insert( $data );
		}

		function update_ticket( $data, $where ) {
			return parent::update( $data, $where );
		}

		function delete_ticket( $where ) {
			return parent::delete( $where );
		}
	}
}