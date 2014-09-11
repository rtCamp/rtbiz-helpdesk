<?php

class test_Rt_HD_Module extends RT_WP_TestCase {
	/**
	 * @var $rthdModule object of RT_WP_Helpdesk
	 */
	var $rthdModule;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdModule = new Rt_HD_Module();
		//$this->rthdModule->db_ticket_table_update();
	}

	/**
	 * Ensure that required function exist
	 */
	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rthdModule, 'get_custom_labels' ), 'Class Rt_HD_Module does not have method get_custom_labels' );
		$this->assertTrue( method_exists( $this->rthdModule, 'get_custom_statuses' ), 'Class Rt_HD_Module does not have method get_custom_statuses' );
		$this->assertTrue( method_exists( $this->rthdModule, 'get_custom_menu_order' ), 'Class Rt_HD_Module does not have method get_custom_menu_order' );
		$this->assertTrue( method_exists( $this->rthdModule, 'init_hd' ), 'Class Rt_HD_Module does not have method init_hd' );
		$this->assertTrue( method_exists( $this->rthdModule, 'register_custom_post' ), 'Class Rt_HD_Module does not have method register_custom_post' );
		$this->assertTrue( method_exists( $this->rthdModule, 'register_custom_statuses' ), 'Class Rt_HD_Module does not have method register_custom_statuses' );
	}

	/**
	 * Test Class variable
	 */
	function  test_class_local_variable() {
		$this->assertEquals( 'rtbiz_hd_ticket', Rt_HD_Module::$post_type );
		$this->assertEquals( 'Helpdesk', $this->rthdModule->name );
	}

	/**
	 * Test get_custom_labels
	 */
	function  test_get_custom_labels() {
		$this->assertTrue( is_array( $this->rthdModule->labels ) );
	}

	/**
	 * Test get_custom_statuses
	 */
	function  test_get_custom_statuses() {
		$this->assertTrue( is_array( $this->rthdModule->statuses ) );
	}

	/**
	 * Test get_custom_menu_order
	 */
	function  test_get_custom_menu_order() {
		$this->assertTrue( is_array( $this->rthdModule->custom_menu_order ) );
	}

	/**
	 * Test register_custom_post
	 */
	function  test_register_custom_post() {
		$this->rthdModule->register_custom_post( 32 );
		$this->assertTrue( post_type_exists( Rt_HD_Module::$post_type ) );
	}

	/**
	 * Test register_custom_statuses
	 */
	function  test_register_custom_statuses() {
		foreach ( $this->rthdModule->statuses as $status ) {
			$this->assertTrue( is_object( $this->rthdModule->register_custom_statuses( $status ) ) );
		}
	}
}
 