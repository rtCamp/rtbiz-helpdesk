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
 * Description of RtHDMailMessageModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Mail_Message_Model' ) ) {
	class Rt_HD_Mail_Message_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_mail_messageids' );
		}

		function get_message( $where ) {
			return parent::get( $where );
		}

		function add_message( $data ) {
			return parent::insert( $data );
		}
	}
}
