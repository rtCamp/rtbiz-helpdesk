<?php
/**
 * User: spock
 * Date: 8/7/15
 * Time: 7:08 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Migration' ) ) {

	/**
	 * Class Rtbiz_HD_Migration
	 *
	 * Single class to call all other class for migration
	 *
	 */
	class Rtbiz_HD_Migration {
		/*
		 * call migration class
		 */
		public function __construct() {
			new Rtbiz_HD_Ticket_Creator_Migration();
		}
	}
}
