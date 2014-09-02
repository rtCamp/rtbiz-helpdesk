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
 * IMAP server model for database table
 * @author udit
 */
if ( !class_exists( 'Rt_HD_IMAP_Server_Model' ) ) {
	/**
	 * Class Rt_HD_IMAP_Server_Model
	 */
	class Rt_HD_IMAP_Server_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_imap_server' );
		}

		/**
		 * get all servers
		 *
		 * @return array
		 */
		function get_all_servers() {
			return parent::get( array() );
		}

		/**
		 * delete server from id
		 *
		 * @param $id
		 *
		 * @return int
		 */
		function delete_server( $id ) {
			return parent::delete( array( 'id' => $id ) );
		}

		/**
		 * add server
		 *
		 * @param $data
		 *
		 * @return int
		 */
		function add_server( $data ) {
			return parent::insert( $data );
		}

		/**
		 * update server
		 *
		 * @param $data
		 * @param $id
		 *
		 * @return mixed
		 */
		function update_server( $data, $id ) {
			return parent::update( $data, array( 'id' => $id ) );
		}

		/**
		 * get server
		 *
		 * @param $id
		 *
		 * @return bool
		 */
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
