<?php

/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 8:07 PM
 */
class test_Rt_HD_Contacts extends RT_WP_TestCase {

	var $rthdContacts;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdContacts = new Rtbiz_HD_Contacts();
	}

	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rthdContacts, 'ajax_contact_autocomplete' ), 'Class Rt_HD_Contacts does not have method ajax_contact_autocomplete' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'ajax_get_taxonomy_meta' ), 'Class Rt_HD_Contacts does not have method ajax_get_taxonomy_meta' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'ajax_get_account_contacts' ), 'Class Rt_HD_Contacts does not have method ajax_get_account_contacts' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'ajax_add_new_contact' ), 'Class Rt_HD_Contacts does not have method ajax_add_new_contact' );
	}

	/**
	 * Test contacts_columns
	 */
	function  test_contacts_columns() {
		global $rtbiz_contact;
		$exp_output = array(
			Rtbiz_HD_Module::$post_type => 'Ticket',
		);
		$this->assertEquals( $exp_output, $this->rthdContacts->contacts_columns( array(), $rtbiz_contact ) );
	}

	/**
	 * Test insert_new_contact
	 */
	function  test_insert_new_contact() {
		$contact = $this->rthdContacts->insert_new_contact( 'dipesh.kakadiya111@gmail.com', 'Dipesh Kakadiya' );
		$this->assertTrue( is_object( $contact ) );
		$this->assertEquals( 'Dipesh Kakadiya', $contact->post_title );
		$this->assertEquals( 'dipesh-kakadiya', $contact->post_name );
		$this->assertEquals( 'rt_contact', $contact->post_type );
	}

	/**
	 * Test get_user_from_emailv
	 */
	function  test_get_user_from_email() {
		$userid = wp_create_user( 'dipesh.kakadiya111@gmail.com', 'dips', 'dipesh.kakadiya111@gmail.com' );
		$this->assertEquals( $userid, $this->rthdContacts->get_user_from_email( 'dipesh.kakadiya111@gmail.com' ) );
	}
}
