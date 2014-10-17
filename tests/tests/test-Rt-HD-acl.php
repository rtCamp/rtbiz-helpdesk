<?php

/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 6:56 PM
 */
class test_Rt_HD_ACL extends RT_WP_TestCase {

	var $rthdAcl;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdAcl = new Rt_HD_ACL();
	}

	/**
	 * Test register_rt_hd_module
	 */
	function  test_register_rt_hd_module() {
		$this->assertEquals(
			array(
				'rt_helpdesk' => array(
					'label'      => 'rtHelpdesk',
					'post_types' => array( 'rtbiz_hd_ticket' ),
					'require_support' => true
				)
			), $this->rthdAcl->register_rt_hd_module( array() ) );
	}
}
 