<?php
/**
 * Don't load this file directly!
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RtHDMailOutboundModel
 * Model for table wp_hd_mail_outbound in database
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Mail_Outbound_Model' ) ) {
	/**
	 * Class Rt_HD_Mail_Outbound_Model
	 */
	class Rt_HD_Mail_Outbound_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_mail_outbound' );
		}

		/**
		 * get outbound mail
		 *
		 * @param $where
		 *
		 * @return array
		 */
		function get_outbound_mail( $where ) {
			return parent::get( $where );
		}

		/**
		 * update outbound mail in DB
		 *
		 * @param $data
		 * @param $where
		 *
		 * @return mixed
		 */
		function update_outbound_mail( $data, $where ) {
			return parent::update( $data, $where );
		}

		/**
		 * add outbound mail in DB
		 *
		 * @param $data
		 *
		 * @return int
		 */
		function add_outbound_mail( $data ) {
			return parent::insert( $data );
		}
	}
}
