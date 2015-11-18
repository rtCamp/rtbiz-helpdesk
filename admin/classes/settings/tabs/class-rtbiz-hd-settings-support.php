<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_Support ' ) ) :
	class rtBiz_HD_Settings_Support extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_support';
			$this->label = __( 'Support', RTBIZ_TEXT_DOMAIN );


			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action('rtbiz_admin_field_rtbiz_hd_setting_support',array($this,'display'));
		}

		public function display() {
			if ( isset($_POST['rtbiz_hd-submit-request'])){
				Rtbiz_HD_Support::support_sent();
			} else {
				$support = new Rtbiz_HD_Support();
				$support->call_get_form();
			}
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = array(

				array(
					'id'       => 'rtbiz_hd_setting_support',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_setting_support',
					'autoload' => false
				),

			);

			$settings = apply_filters( 'rtbiz_support_settings', $settings );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_Support();
