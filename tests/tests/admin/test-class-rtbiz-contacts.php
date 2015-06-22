<?php

/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 7:37 PM
 */
class test_Rtbiz_HD_Contacts extends RT_WP_TestCase {

	var $rtbizhdContacts;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rtbizhdContacts = new Rtbiz_HD_Contact();
	}

	/**
	 * all methods include properly or not
	 */
	function test_method_exist() {
		$methods = array(

		);

		foreach ( $methods as $method ) {
			$this->assertTrue( method_exists( $this->rtbizhdContacts, $method ), "Class Rtbiz_HD doesn't have method $method" );
		}
	}



}
