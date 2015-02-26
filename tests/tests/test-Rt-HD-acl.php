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
		$tmp = $this->rthdAcl->register_rt_hd_module( array() );
		$settings               = rthd_get_redux_settings();

		$this->assertEquals(
			array(
				'label'      => isset( $settings['rthd_menu_label'] ) ? $settings['rthd_menu_label'] : 'Helpdesk',
				'post_types' => array( Rt_HD_Module::$post_type ),
				'department_support' => array( Rt_HD_Module::$post_type ),
				'offering_support' => array( Rt_HD_Module::$post_type ),
				'setting_option_name' => Redux_Framework_Helpdesk_Config::$hd_opt, // Use For ACL
				'setting_page_url' => admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&page=rthd-settings'), // for Mailbox

			), $tmp['rtbiz-helpdesk'] );
	}
}
