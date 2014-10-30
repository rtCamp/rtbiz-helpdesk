<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 7:37 PM
 */

class test_Rt_HD_Admin extends RT_WP_TestCase {

	var $rthdAdmin;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdAdmin = new Rt_HD_Admin();
	}

	/**
	 * Test register_menu
	 */
//	function  test_register_menu() {
//		$this->assertEquals( 'admin_page_rthd-settings', $this->rthdAdmin->register_menu() );
//	}

}
 