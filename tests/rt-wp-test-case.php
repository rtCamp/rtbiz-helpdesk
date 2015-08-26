<?php

/**
 * Created by PhpStorm.
 * User: faishal
 * Date: 18/02/14
 * Time: 4:30 PM
 */
class RT_WP_TestCase extends WP_UnitTestCase {
	/**
	 * Ensure that the base plugin [ rtbiz ] has been installed and activated.
	 */
	function test_base_plugin_activated() {
		$this->assertTrue( is_plugin_active( RTBIZ_PLUGIN_FILE ) );
		var_dump(RTBIZ_PLUGIN_FILE);
	}

	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_plugin_activated() {
		$this->assertTrue( is_plugin_active( RTBIZ_HD_PLUGIN_FILE ) );
		var_dump( RTBIZ_HD_PLUGIN_FILE );
	}

}
