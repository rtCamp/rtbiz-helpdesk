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
 * Description of RtHDMailOutboundModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Mail_Outbound_Model' ) ) {
	class Rt_HD_Mail_Outbound_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_mail_outbound' );
		}

		function get_outbound_mail( $where ) {
			return parent::get( $where );
		}

		function update_outbound_mail( $data, $where ) {
			return parent::update( $data, $where );
		}

		function add_outbound_mail( $data ) {
			return parent::insert( $data );
		}
	}
}
