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
		$this->rthdContacts = new Rt_HD_Contacts();
	}

	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rthdContacts, 'contact_autocomplete_ajax' ), 'Class Rt_HD_Contacts does not have method contact_autocomplete_ajax' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'get_taxonomy_meta_ajax' ), 'Class Rt_HD_Contacts does not have method get_taxonomy_meta_ajax' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'get_account_contacts_ajax' ), 'Class Rt_HD_Contacts does not have method get_account_contacts_ajax' );
		$this->assertTrue( method_exists( $this->rthdContacts, 'add_new_contact_ajax' ), 'Class Rt_HD_Contacts does not have method add_new_contact_ajax' );
	}

	/**
	 * Test contacts_columns
	 */
	function  test_contacts_columns() {
		global $rt_contact;
		$exp_output = array(
			'rtbiz_hd_ticket' => 'Ticket',
		);
		$this->assertEquals( $exp_output, $this->rthdContacts->contacts_columns( array(), $rt_contact ) );
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
