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
		$this->rthdAcl = new Rtbiz_HD_Admin();
	}

	/**
	 * Test register_rt_hd_module
	 */
	function  test_register_rt_hd_module() {
		$tmp = $this->rthdAcl->module_register( array() );
		$this->assertEquals(
			array(
				'label'      => 'Helpdesk',
				'post_types' => array( Rtbiz_HD_Module::$post_type ),
				'department_support' => array( Rtbiz_HD_Module::$post_type ),
				'product_support' => array( Rtbiz_HD_Module::$post_type ),
				'setting_option_name' => Rtbiz_HD_Settings::$hd_opt, // Use For ACL
				'setting_page_url' => admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&page=rthd-settings'), // for Mailbox
				'email_template_support' => array( Rtbiz_HD_Module::$post_type ),
			), $tmp['rtbiz-helpdesk'] );
	}
}
