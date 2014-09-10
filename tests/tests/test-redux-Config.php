<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 4/9/14
 * Time: 1:02 PM
 */
class test_ReduxConfig extends RT_WP_TestCase {
	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 * @depends test_RTDBUpdate::test_do_upgrade
	 */
	var $hd;
	var $redux;

	function setUp() {
		parent::setUp();
		$this->hd = new RT_WP_Helpdesk();
		$this->redux      = new Redux_Framework_Helpdesk_Config();
	}

	function test_class() {
		$this->assertClassHasStaticAttribute( 'page_slug', 'Redux_Framework_Helpdesk_Config' );
		$this->assertTrue( class_exists( 'Redux_Framework_Helpdesk_Config' ), 'Class Redux_Framework_Helpdesk_Config does not exist' );
	}

	function test_save_replay_by_email() {
		$this->assertTrue( method_exists( $this->redux, 'save_replay_by_email' ), 'Class does not have method myFunction save_replay_by_email' );

		//	$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	function test_save_imap_servers() {
		//$this->assertTrue( method_exists( $this->redux, 'save_imap_servers' ), 'Class does not have method myFunction save_imap_servers' );
		//$this->markTestIncomplete( 'This test has not been implemented yet.' );

		//
		//		$rthd_imap_servers = array(
		//			'new' => array(
		//				'server_name'          => 'gmail',
		//				'incoming_imap_server' => 'dasf.com',
		//				'incoming_imap_port'   => '22',
		//				'incoming_imap_enc'    => 'ssl',
		//				'outgoing_smtp_server' => '120',
		//				'outgoing_smtp_port'   => '25',
		//				'outgoing_smtp_enc'    => 'ssl',
		//			)
		//		);

		//	$rthd_imap_servers_changed = 1;

		//$this->assertTrue( $this->redux->save_imap_servers( $rthd_imap_servers_changed, $rthd_imap_servers ) );

	}


	function test_remove_demo() {
		$this->assertTrue( method_exists( $this->redux, 'remove_demo' ), 'Class does not have method myFunction remove_demo' );
	}


	function test_init_settings() {
		//	 $this->assertTrue( method_exists( $this->redux, 'initSettings' ), 'Class does not have method myFunction initSettings' );
		//$this -> assertTrue( $this->redux->initSettings());
		//var_dump($this->redux->initSettings());
	}

	function test_set_sections() {
		$this->assertTrue( method_exists( $this->redux, 'setSections' ), 'Class does not have method myFunction setSections' );
		$this->assertTrue( $this->redux->setSections() );
	}

	function test_set_arugument() {
		$this->assertTrue( method_exists( $this->redux, 'setArguments' ), 'Class does not have method myFunction setArguments' );
		$this->assertTrue( $this->redux->setArguments() );

	}

	function test_setHelpTab() {
		$this->assertTrue( method_exists( $this->redux, 'setHelpTabs' ), 'Class does not have method myFunction setHelpTabs' );
		$this->assertTrue( $this->redux->setHelpTabs() );
		$this->assertArrayHasKey( 'help_tabs', $this->redux->args );
		$this->assertArrayHasKey( 'help_sidebar', $this->redux->args );


	}


	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_assertTrue() {
		$this->assertTrue( true );
	}

}
