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
		$this->rthdaccounts = new Rtbiz_HD_Accounts();
	}

	function  test_check_function() {
		$this->assertTrue( method_exists( $this->rthdaccounts, 'ajax_add_new_account' ), 'Class Rt_HD_Accounts does not have method ajax_add_new_account' );
		$this->assertTrue( method_exists( $this->rthdaccounts, 'ajax_account_autocomplete' ), 'Class Rt_HD_Accounts does not have method ajax_account_autocomplete' );
		$this->assertTrue( method_exists( $this->rthdaccounts, 'ajax_get_term_by_key' ), 'Class Rt_HD_Accounts does not have method ajax_get_term_by_key' );
	}

	/**
	 * Test accounts_columns
	 */
	function  test_accounts_columns() {
		global $rt_company;
		$exp_output = array();
		$this->assertEquals( $exp_output, $this->rthdaccounts->accounts_columns( array(), $rt_company ) );
	}

}
