<?php

/**
 * Created by PhpStorm.
 * User: Dipesh
 * Date: 3/9/14
 * Time: 8:47 PM
 */
class test_RT_WP_Help_desk extends RT_WP_TestCase {

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
		$this->rtdbupdate   = new RT_DB_Update( false, realpath( dirname( __FILE__ ) . '/../../app/schema/' ) );
		$this->rtdbupdate->do_upgrade();
	}

	/**
	 * Ensure that required Class exist
	 */
	function test_check_class_exist() {

		$this->assertTrue( class_exists( 'Rt_HD_Mail_ACL_Model' ), 'Class Rt_HD_Mail_ACL_Model does not exist' );


		$this->assertTrue( class_exists( 'Rt_Form' ), 'Class Rt_Form does not exist' );
		$this->assertTrue( class_exists( 'Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata' ), 'Class Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Attributes' ), 'Class Rt_HD_Attributes does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Module' ), 'Class Rt_HD_Module does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_CPT_Tickets' ), 'Class Rt_HD_CPT_Tickets does not exist' );

		$this->assertTrue( class_exists( 'Rt_Reports' ), 'Class Rt_Reports does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Dashboard' ), 'Class Rt_HD_Dashboard does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_ACL' ), 'Class Rt_HD_ACL does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Accounts' ), 'Class Rt_HD_Accounts does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Contacts' ), 'Class Rt_HD_Contacts does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Tickets_Operation' ), 'Class Rt_HD_Tickets_Operation does not exist' );
		$this->assertTrue( class_exists( 'RT_HD_Email_Notification' ), 'Class RT_HD_Email_Notification does not exist' );

		$this->assertTrue( class_exists( 'Redux_Framework_Helpdesk_Config' ), 'Class Redux_Framework_Helpdesk_Config does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Import_Operation' ), 'Class Rt_HD_Import_Operation does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Gravity_Form_Importer' ), 'Class Rt_HD_Gravity_Form_Importer does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Gravity_Form_Mapper' ), 'Class Rt_HD_Gravity_Form_Mapper does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_User_Settings' ), 'Class Rt_HD_User_Settings does not exist' );
		$this->assertTrue( class_exists( 'Rt_HD_Logs' ), 'Class Rt_HD_Logs does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Offering_Support' ), 'Class Rt_HD_Offering_Support does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Admin' ), 'Class Rt_HD_Admin does not exist' );

		$this->assertTrue( class_exists( 'Rt_HD_Tickets_Front' ), 'Class Rt_HD_Tickets_Front does not exist' );
	}

	/**
	 * Ensure that required function exist
	 */
	function  test_check_function() {
		$this->assertTrue( function_exists( 'rthd_admin_notice_dependency_not_installed' ) , 'Helpdesk does not have method rt_biz_admin_notice' );
		$this->assertTrue( function_exists( 'rthd_check_plugin_dependecy' ) , 'Helpdesk does not have method rthd_check_plugin_dependecy' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'init_globals' ), 'Class RT_WP_Helpdesk does not have method init_globals' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'admin_init' ), 'Class RT_WP_Helpdesk does not have method admin_init' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'update_database' ), 'Class RT_WP_Helpdesk does not have method update_database' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'init' ), 'Class RT_WP_Helpdesk does not have method init' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'load_scripts' ), 'Class RT_WP_Helpdesk does not have method load_scripts' );
		$this->assertTrue( method_exists( $this->rtwpHelpDesk, 'localize_scripts' ), 'Class RT_WP_Helpdesk does not have method localize_scripts' );
	}


	/**
	 * Ensure that rtbiz dependecy & it's function
	 */
	function test_check_rt_biz_dependecy() {
		$this->assertTrue( rthd_check_plugin_dependecy(), 'rtbiz depend function not exist' );
	}
}
 