<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_Team_Setup ' ) ) :
	class rtBiz_HD_Settings_Team_Setup extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_team_setup';
			$this->label = __( 'Setup Your Team', RTBIZ_TEXT_DOMAIN );
			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_admin_field_' . $this->id, array( $this, 'display' ) );
		}

		public function display() {
			echo rtbiz_hd_get_setup_team_ui();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters( 'rtbiz_hd_team_setup_setting', array(

				array(
					'title' => __( 'Setup Your Team', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'teamsetup_option'
				),
				array(
					'id'       => 'rtbiz_hd_team_setup',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_team_setup',
					'autoload' => false
				),
				array( 'type' => 'sectionend', 'id' => 'teamsetup_option' ),

			) );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_Team_Setup();
