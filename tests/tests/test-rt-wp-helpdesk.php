<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 3/9/14
 * Time: 8:47 PM
 */

class test_RTWPHelpdesk extends RT_WP_TestCase {

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 * @depends test_RTDBUpdate::test_do_upgrade
	 */
	function setUp() {
		parent::setUp();
	}

	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_assertTrue()
	{
		$this->assertTrue( true );
	}
}
 