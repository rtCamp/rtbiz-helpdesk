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
 * Description of RtHDMailACLModel
 * model for wp_hd_mail_acl table in database
 * acl : access control list
 * it is for mail
 *
 * @author udit
 *
 */
if ( !class_exists( 'Rt_HD_Mail_ACL_Model' ) ) {
	/**
	 * Class Rt_HD_Mail_ACL_Model
	 */
	class Rt_HD_Mail_ACL_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_mail_acl' );
		}

		/**
		 * get access control list
		 *
		 * @param $where
		 *
		 * @return array
		 */
		function get_acl( $where ) {
			return parent::get( $where );
		}

		/**
		 * add ACL
		 *
		 * @param $data
		 *
		 * @return int
		 */
		function add_acl( $data ) {
			return parent::insert( $data );
		}

		/**
		 * remove ACL
		 *
		 * @param $where
		 *
		 * @return int
		 */
		function remove_acl( $where ) {
			return parent::delete( $where );
		}
	}
}
