<?php
/**
 * Created by PhpStorm.
 * User: sai
 * Date: 12/9/14
 * Time: 7:37 PM
 */

class test_Rt_HD_Admin extends RT_WP_TestCase {

	var $rthdAdmin;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdAdmin = new Rtbiz_HD_Admin();
	}

	/**
	 * Test register_menu
	 */
	function  test_custom_upload_dir() {

		$arg = array(
			'basedir'=>'upload',
			'baseurl'=>'http://hd.com/upload',
		    'subdir'=>'/2012/12/12/',
		);
		$arg = $this->rthdAdmin->custom_upload_dir( $arg );
		$this->assertEquals( 'upload/rtbiz-helpdesk/2012/12/12/', $arg['path'] );
		$this->assertEquals( 'http://hd.com/upload/rtbiz-helpdesk/2012/12/12/', $arg['url'] );
	}

}
