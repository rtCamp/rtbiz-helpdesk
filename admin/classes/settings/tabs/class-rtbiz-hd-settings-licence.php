<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_Licence ' ) ) :
	class rtBiz_HD_Settings_Licence extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_licence';
			$this->label = __( 'Licence', RTBIZ_TEXT_DOMAIN );
			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_admin_field_' . $this->id, 'rtbiz_hd_activation_view');
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters( 'rtbiz_hd_licence_setting', array(

				array(
					'title' => __( 'Plugin Activation', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'licence_option'
				),
				array(
					'title' => 'Helpdesk Activation',
					'id'       => 'rtbiz_hd_licence',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_licence',
					'autoload' => false
				),
				array( 'type' => 'sectionend', 'id' => 'licence_option' ),

			) );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_Licence();
