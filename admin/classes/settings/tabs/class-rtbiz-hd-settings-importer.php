<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_Importer ' ) ) :
	class rtBiz_HD_Settings_Importer extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_importer';
			$this->label = __( 'Importer', RTBIZ_TEXT_DOMAIN );

			add_action( 'rtbiz_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_admin_field_rtbiz_hd_gravity_importer', array( $this, 'gf_import_display' ) );
			add_action( 'rtbiz_admin_field_rtbiz_hd_gravity_importer_mapper', array( $this, 'mapper_display' ) );
			add_action( 'rtbiz_admin_field_rtbiz_hd_gravity_importer_log', 'rtbiz_hd_ticket_import_logs');
		}

		public function output() {
			global $current_section;
			if ( $current_section ) {
				if ( method_exists( $this, $current_section ) ) {
					rtBiz_Admin_Settings::output_fields( $this->$current_section() );
				}
			} else {
				parent::output();
			}
		}

		public function save() {
			global $current_section;

			if ( $current_section ) {
				if ( method_exists( $this, $current_section ) )
					rtBiz_Admin_Settings::save_fields( $this->$current_section() );
				do_action( 'rtbiz_update_options_' . $this->id . '_' . $current_section );
			} else {
				parent::save();
			}

		}

		public function importer_mapper() {
			$settings = array(
				array(
					'title' => __( 'Gravity importer mapper', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'rtbiz_hd_gravity_importer_mapper_title'
				),
				array(
					'id'       => 'rtbiz_hd_gravity_importer_mapper',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_gravity_importer_mapper',
					'autoload' => false
				),
				array( 'type' => 'sectionend', 'id' => 'rtbiz_hd_gravity_importer_mapper_title' ),
			);

			$settings = apply_filters( 'rtbiz_gravity_importer_mapper_settings', $settings );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}

		public function importer_log() {
			$settings = array(
				array(
					'title' => __( 'Gravity importer log', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'rtbiz_hd_gravity_importer_log_title'
				),
				array(
					'id'       => 'rtbiz_hd_gravity_importer_log',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_gravity_importer_log',
					'autoload' => false
				),
				array( 'type' => 'sectionend', 'id' => 'rtbiz_hd_gravity_importer_log_title' ),
			);

			$settings = apply_filters( 'rtbiz_gravity_importer_log_settings', $settings );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}

		public function mapper_display() {
			global $rtlib_importer_mapper;
			$rtlib_importer_mapper->ui();
		}

		public function gf_import_display() {
			echo rtbiz_hd_gravity_importer_view();
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				''                => __( 'Gravity Importer', RTBIZ_TEXT_DOMAIN ),
				'importer_mapper' => __( 'Importer Mapper', RTBIZ_TEXT_DOMAIN ),
				'importer_log'    => __( 'Importer Log', RTBIZ_TEXT_DOMAIN )
			);

			return apply_filters( 'rtbiz_get_sections_' . $this->id, $sections );
		}


		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = array(

				array(
					'title' => __( 'Gravity importer', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'gfimporter_option'
				),
				array(
					'id'       => 'rtbiz_hd_gravity_importer',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_gravity_importer',
					'autoload' => false
				),
				array( 'type' => 'sectionend', 'id' => 'gfimporter_option' ),

			);


			$settings = apply_filters( 'rtbiz_gravity_importer_settings', $settings );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_Importer();
