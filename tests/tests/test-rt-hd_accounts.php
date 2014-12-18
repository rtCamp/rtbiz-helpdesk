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

	/**
	 * Test accounts_columns
	 */
	function  test_accounts_columns() {
		global $rt_company;
		$exp_output = array(
			'country' => 'Country',
			'rtbiz_hd_ticket' => 'Ticket',
		);
		$this->assertEquals( $exp_output, $this->rthdaccounts->accounts_columns( array(), $rt_company ) );
	}

}
 