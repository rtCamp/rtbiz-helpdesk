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
 * Description of RtHDIMAPServerModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_IMAP_Server_Model' ) ) {
	class Rt_HD_IMAP_Server_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_imap_server' );
		}

		function get_all_servers() {
			return parent::get( array() );
		}

		function delete_server( $id ) {
			return parent::delete( array( 'id' => $id ) );
		}

		function add_server( $data ) {
			return parent::insert( $data );
		}

		function update_server( $data, $id ) {
			return parent::update( $data, array( 'id' => $id ) );
		}

		function get_server( $id ) {
			$servers = parent::get( array( 'id' => $id ) );
			$server = false;
			if ( ! empty( $servers ) ) {
				$server = $servers[0];
			}
			return $server;
		}
	}
}
