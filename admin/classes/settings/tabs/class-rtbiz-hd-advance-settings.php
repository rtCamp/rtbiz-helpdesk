<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Advance_Settings ' ) ) :
	class rtBiz_HD_Advance_Settings extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_advance_settings';
			$this->label = __( 'Advanced Settings', RTBIZ_HD_TEXT_DOMAIN );

			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_admin_field_rthd_auto_response_dayshift_time' , 'rtbiz_hd_auto_response_dayshift_view' );
			add_action( 'rtbiz_admin_field_rthd_auto_response_nightshift_time' , 'rtbiz_hd_auto_response_daynightshift_view' );
		}


		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {


			$settings = apply_filters( 'rtbiz_hd_advance_settings', array(

				array(
					'title' => __( 'Advanced Options', RTBIZ_HD_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'advance_options'
				),
				array(
					'title'    => __( 'Auto Assign Tickets', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'To auto assign a ticket to staff on reply', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_enable_ticket_unique_hash',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),
				array(
					'title'    => __( 'Event For Auto assign', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_auto_assign_events',
					'default'  => 'on_first_followup',
					'type'     => 'radio',
					'options'  => array(
						'on_first_followup'  => __( 'When first follow-up is added to a ticket by any staff member.', RTBIZ_HD_TEXT_DOMAIN ),
						'on_any_followup' => __( 'When any follow-up is added to a ticket by any staff member.', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),

				array(
					'title'    => __( 'Auto Response', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'To enable/disable auto response feature', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_enable_auto_response',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),
				array(
					'title'    => __( 'Select working shift', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'Day shift / Day-Night Shift', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_enable_auto_response_mode',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'Day Shift', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Day-Night Shift', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),

				array(
					'id'       => 'rthd_auto_response_dayshift_time',
					'type'     => 'rthd_auto_response_dayshift_time',
					'title'    => __( 'Configure working time for dayshift' ),
					'desc' => __( 'Add hours of operation' ),
				),

				array(
					'id'       => 'rthd_auto_response_nightshift_time',
					'type'     => 'rthd_auto_response_nightshift_time',
					'title'    => __( 'Configure working time for nightshift' ),
					'desc' => __( 'Add hours of operation' ),
				),

				array(
					'title'    => __( 'Auto-respond on weekends only.', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_autoresponse_weekend',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),

				array(
					'title'    => __( 'Auto response message', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => esc_attr( 'Add a message here that will be sent to the customer when your team is offline. ' ) . 'Use <b>{NextStartingHour}</b> to get next working hours like <b>`Today after 10 pm` or `Monday after 9 AM`</b>',
					'id'       => 'rthd_auto_response_message',
					'default'      => esc_attr( 'We have received your support request. Our support team is currently offline. We will get back at the soonest.' ),
					'type'     => 'textarea',
					'css'     => 'width:400px; height: 65px;',
					'autoload' => true
				),


				array(
					'title'    => __( 'Adult Content Filter', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'For customer, a form feature to mark adult content will be enabled. For staff, profile level setting to filter the adult content will be enabled.', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_enable_ticket_adult_content',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),

				array( 'type' => 'sectionend', 'id' => 'advance_options' ),

			) );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}

	}
endif;

return new rtBiz_HD_Advance_Settings();
