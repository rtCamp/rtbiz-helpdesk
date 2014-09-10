<?php

/**
 * Created by PhpStorm.
 * User: Dipesh
 * Date: 3/9/14
 * Time: 8:47 PM
 */
class test_RTWPHelpdesk extends RT_WP_TestCase {

	/**
	 * @var $rtwpHelpDesk object of RT_WP_Helpdesk
	 */
	var $rtwpHelpDesk;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rtwpHelpDesk = new RT_WP_Helpdesk();
	}

	/**
	 * Ensure that required Class exist
	 */
	function test_check_class_exist() {

		$this->assertTrue( class_exists( 'Rt_HD_Mail_Accounts_Model' ), 'Class Rt_HD_Mail_Accounts_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Mail_ACL_Model' ), 'Class Rt_HD_Mail_ACL_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Mail_Thread_Importer_Model' ), 'Class Rt_HD_Mail_Thread_Importer_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Mail_Message_Model' ), 'Class Rt_HD_Mail_Message_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Mail_Outbound_Model' ), 'Class Rt_HD_Mail_Outbound_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Gravity_Fields_Mapping_Model' ), 'Class Rt_HD_Gravity_Fields_Mapping_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Ticket_History_Model' ), 'Class Rt_HD_Ticket_History_Model does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_IMAP_Server_Model' ), 'Class Rt_HD_IMAP_Server_Model does not exist' );


		$this->assertTrue( class_exists( 'Rt_Form' ), 'Class Rt_Form does not exist' );
		$this->assertTrue( class_exists( 'Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata' ), 'Class Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Closing_Reason' ), 'Class Rt_HD_Closing_Reason does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Attributes' ), 'Class Rt_HD_Attributes does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Dashboard' ), 'Class Rt_HD_Dashboard does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Module' ), 'Class Rt_HD_Module does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_ACL' ), 'Class Rt_HD_ACL does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Accounts' ), 'Class Rt_HD_Accounts does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Contacts' ), 'Class Rt_HD_Contacts does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Tickets' ), 'Class Rt_HD_Tickets does not exist' );

		$this->assertTrue( class_exists( 'Rt_Reports' ), 'Class Rt_Reports does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Gravity_Form_Importer' ), 'Class Rt_HD_Gravity_Form_Importer does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Gravity_Form_Mapper' ), 'Class Rt_HD_Gravity_Form_Mapper does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Settings' ), 'Class Rt_HD_Settings does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_User_Settings' ), 'Class Rt_HD_User_Settings does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Logs' ), 'Class Rt_HD_Logs does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Woocommerce' ), 'Class Rt_HD_Woocommerce does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Admin' ), 'Class Rt_HD_Admin does not exist' );
		$this->assertTrue( class_exists( 'RT_HD_Admin_Meta_Boxes' ), 'Class RT_HD_Admin_Meta_Boxes does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Tickets_Front' ), 'Class Rt_HD_Tickets_Front does not exist' );
	}

	/**
	 * Ensure that required function exist
	 */
	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'check_rt_biz_dependency' ), 'Class RT_WP_Helpdesk does not have method check_rt_biz_dependency' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'rt_biz_admin_notice' ), 'Class RT_WP_Helpdesk does not have method rt_biz_admin_notice' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'init_redux' ), 'Class RT_WP_Helpdesk does not have method init_redux' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'init_globals' ), 'Class RT_WP_Helpdesk does not have method init_globals' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'admin_init' ), 'Class RT_WP_Helpdesk does not have method admin_init' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'update_database' ), 'Class RT_WP_Helpdesk does not have method update_database' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'init' ), 'Class RT_WP_Helpdesk does not have method init' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'loadScripts' ), 'Class RT_WP_Helpdesk does not have method loadScripts' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'localize_scripts' ), 'Class RT_WP_Helpdesk does not have method localize_scripts' );
	}


	/**
	 * Ensure that rtbiz dependency & it's function
	 */
	function test_check_rt_biz_dependency() {
		$this->assertTrue( $this->rtwpHelpDesk->check_rt_biz_dependency(), 'rtbiz depend function not exist' );
	}

	function test_check_loadScripts() {
//		global $wp_query;
//		$wp_query->query_vars[ 'name' ] = 'ticket';
//		$this->assertTrue( $this->rtwpHelpDesk->loadScripts() );
	}


}
