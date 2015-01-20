<?php

class test_Rt_HD_Module extends RT_WP_TestCase {
	/**
	 * @var $rthdModule object of Rt_HD_Module
	 */
	var $rthdModule;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdModule = new Rt_HD_Module();
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


		$this->assertTrue( method_exists( $this->rthdModule, 'create_database_table' ), 'Class Rt_HD_Module does not have method create_database_table' );
		$this->assertTrue( method_exists( $this->rthdModule, 'update_ticket_table' ), 'Class Rt_HD_Module does not have method update_ticket_table' );
		$this->assertTrue( method_exists( $this->rthdModule, 'ticket_chnage_action_publish_update' ), 'Class Rt_HD_Module does not have method ticket_chnage_action_publish_update' );
		$this->assertTrue( method_exists( $this->rthdModule, 'custom_pages_order' ), 'Class Rt_HD_Module does not have method custom_pages_order' );
	}

	/**
	 * Test Class variable
	 */
	function  test_class_local_variable() {
		$this->assertEquals( 'rtbiz_hd_ticket', Rt_HD_Module::$post_type );
		$this->assertEquals( 'Helpdesk', Rt_HD_Module::$name);
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
		$this->assertTrue( post_type_exists( Rt_HD_Module::$post_type ) );
	}

	/**
	 * Test register_custom_statuses
	 */
	function  test_register_custom_statuses() {
		$status = array(
			'slug'        => 'Demo',
			'name'        => __( 'Demo', RT_HD_TEXT_DOMAIN ),
			'description' => __( 'Ticket is unanswered. It needs to be replied. The default state.', RT_HD_TEXT_DOMAIN ),
		);
		$this->assertTrue( is_object( $this->rthdModule->register_custom_statuses( $status ) ) );
	}

}
 