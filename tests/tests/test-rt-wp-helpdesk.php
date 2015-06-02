<?php

/**
 * Created by PhpStorm.
 * User: Dipesh
 * Date: 3/9/14
 * Time: 8:47 PM
 */
class test_RT_WP_Help_desk extends RT_WP_TestCase {

	/**
	 * @var $rtwpHelpDesk object of Rt_Biz_Helpdesk
	 */
	var $rtwpHelpDesk;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rtwpHelpDesk = new Rt_Biz_Helpdesk();
		$this->rtdbupdate   = new RT_DB_Update( false, realpath( dirname( __FILE__ ) . '/../../admin/schema/' ) );
		$this->rtdbupdate->do_upgrade();
	}

	/**
	 * Ensure that required Class exist
	 */
	function test_check_class_exist() {

		$this->assertTrue( class_exists( 'Rtbiz_HD_Mail_ACL_Model' ), 'Class Rt_HD_Mail_ACL_Model does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Ticket_History_Model' ), 'Class Rt_HD_Ticket_History_Model does not exist' );


		$this->assertTrue( class_exists( 'Rt_Form' ), 'Class Rt_Form does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Attributes' ), 'Class Rt_HD_Attributes does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Module' ), 'Class Rt_HD_Module does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_CPT_Tickets' ), 'Class Rt_HD_CPT_Tickets does not exist' );

		$this->assertTrue( class_exists( 'Rt_Reports' ), 'Class Rt_Reports does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Dashboard' ), 'Class Rt_HD_Dashboard does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_ACL' ), 'Class Rt_HD_ACL does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Accounts' ), 'Class Rt_HD_Accounts does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Contacts' ), 'Class Rt_HD_Contacts does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Tickets_Operation' ), 'Class Rt_HD_Tickets_Operation does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Email_Notification' ), 'Class RT_HD_Email_Notification does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Settings' ), 'Class Redux_Framework_Helpdesk_Config does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Import_Operation' ), 'Class Rt_HD_Import_Operation does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Gravity_Form_Importer' ), 'Class Rt_HD_Gravity_Form_Importer does not exist' );
		$this->assertTrue( class_exists( 'Rtbiz_HD_Logs' ), 'Class Rt_HD_Logs does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Offering_Support' ), 'Class Rt_HD_Offering_Support does not exist' );

		$this->assertTrue( class_exists( 'Rt_Biz_Helpdesk_Admin' ), 'Class Rt_Biz_Helpdesk_Admin does not exist' );

		$this->assertTrue( class_exists( 'Rtbiz_HD_Tickets_Front' ), 'Class Rt_HD_Tickets_Front does not exist' );
	}

	/**
	 * Ensure that required function exist
	 */
	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'define_admin_hooks' ), 'Class Rt_Biz_Helpdesk does not have method define_admin_hooks' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'admin_init' ), 'Class Rt_Biz_Helpdesk does not have method admin_init' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'update_database' ), 'Class Rt_Biz_Helpdesk does not have method update_database' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'define_public_hooks' ), 'Class Rt_Biz_Helpdesk does not have method define_public_hooks' );
	}


	/**
	 * Ensure that rtbiz dependecy & it's function
	 */
	function test_check_rtbiz_dependecy() {
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'rtbiz_hd_check_plugin_dependency' ), 'Class Rt_Biz_Helpdesk does not have method rtbiz_hd_check_plugin_dependency' );
	}
}
