<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 11/9/14
 * Time: 6:22 PM
 */

class test_Rt_HD_Closing_Reason extends RT_WP_TestCase {
	/**
	 * @var $rthdClosing object of Rt_HD_Closing_Reason
	 */
	var $rthdClosing;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdClosing = new Rt_HD_Closing_Reason();
	}

	/**
	 * Test Class variable
	 */
	function  test_class_local_variable() {
		$this->assertEquals( Rt_HD_Module::$post_type, $this->rthdClosing->post_type );
	}

	/**
	 * Test register closing_reason
	 */
	function  test_register_closing_reason() {
		$this->assertTrue( taxonomy_exists( rthd_attribute_taxonomy_name( 'closing-reason' ) ) );
	}
}
 