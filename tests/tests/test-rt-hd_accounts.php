<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 10:05 PM
 */

class test_Rt_HD_Accounts extends PHPUnit_Framework_TestCase {
	var $rthdaccounts;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdaccounts = new Rt_HD_Accounts();
	}

	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rthdaccounts, 'add_new_account_ajax' ), 'Class Rt_HD_Accounts does not have method add_new_account_ajax' );
		$this->assertTrue( method_exists( $this->rthdaccounts, 'account_autocomplete_ajax' ), 'Class Rt_HD_Accounts does not have method account_autocomplete_ajax' );
		$this->assertTrue( method_exists( $this->rthdaccounts, 'get_term_by_key_ajax' ), 'Class Rt_HD_Accounts does not have method get_term_by_key_ajax' );
	}

	/**
	 * Test accounts_columns
	 */
	function  test_accounts_columns() {
		global $rt_company;
		$exp_output = array(
			'rtbiz_hd_ticket' => 'Ticket',
		);
		$this->assertEquals( $exp_output, $this->rthdaccounts->accounts_columns( array(), $rt_company ) );
	}

}
