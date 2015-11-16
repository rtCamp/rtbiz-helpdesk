<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 5/11/15
 * Time: 2:14 PM
 */

if ( !class_exists('rtBiz_HD_Settings_Email_Notification ')) :
class rtBiz_HD_Settings_Email_Notification extends rtBiz_Settings_Page{

	/**
	 * rtBiz_HD_Settings_Email_Notification constructor.
	 */
	public function __construct() {
		$this->id    = 'rtbiz_hd_notification_email';
		$this->label = __( 'Notification Emails', RTBIZ_HD_TEXT_DOMAIN );
		add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
		add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = apply_filters( 'rtbiz_hd_notification_email_settings', array(

			array( 'title' => __( 'Email Options', RTBIZ_HD_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'notification_email_options' ),

			array(
				'title'    => __( 'Notification Events', RTBIZ_HD_TEXT_DOMAIN ),
				'desc'     => __( 'To enable/disable Notification', RTBIZ_HD_TEXT_DOMAIN ),
				'id'       => 'rthd_settings_enable_notification_acl',
				'default'  => 'on',
				'type'     => 'radio',
				'options'  => array(
					'on'       => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
					'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
				),
				'desc_tip' =>  true,
				'autoload' => true
			),
			array( 'type' => 'sectionend', 'id' => 'notification_email_options'),

			array( 'title' => __( 'Notification Options', RTBIZ_HD_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'notification_options' ),

			array(
				'title'    => __( 'Email Addresses', RTBIZ_HD_TEXT_DOMAIN ),
				'desc'     => __('Email addresses to be notified on events', RTBIZ_HD_TEXT_DOMAIN ),
				'id'       => 'rthd_settings_notification_emails',
				'default'  => '',
				'type'     => 'email',
				'custom_attributes' => array(
					'multiple' => 'multiple'
				),
				'css'     => 'width:400px;',
				'autoload' => true
			),

			array(
				'title'         => __( 'Notification Event for Customer', RTBIZ_HD_TEXT_DOMAIN),
				'desc'          => __( 'When a customer creates a ticket via the web form or email', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_client_events_new_ticket_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a new follow-up is added to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_client_events_new_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),
			array(
				'desc'          => __( 'When any follow-up is edited by staff', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_client_events_new_followup_updated_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When any follow-up is edited by staff', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_client_events_new_followup_deleted_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => true
			),


			//Assignee notifications


			array(
				'title'         => __( 'Notification Event for Assignee', RTBIZ_HD_TEXT_DOMAIN),
				'desc'          => __( 'When a customer creates a ticket via the web form or email', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_ticket_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a new follow-up is added to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),
			array(
				'desc'          => __( 'When a follow-up is edited by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_followup_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a follow-up is deleted by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_followup_deleted_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a ticket is reassigned', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_ticket_reassigned_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff-only follow-up is added/edited on a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_staff_only_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When any status or metadata changed for a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_ticket_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff member subscribes to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_new_subscriber_added_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a subscriber is removed from a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_assignee_events_subscriber_removed_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => true
			),

			// Assignee notifications end

			// Staff notifications start

			array(
				'title'         => __( 'Notification Event for Staff', RTBIZ_HD_TEXT_DOMAIN),
				'desc'          => __( 'When a customer creates a ticket via the web form or email', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_ticket_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a new follow-up is added to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),
			array(
				'desc'          => __( 'When a follow-up is edited by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_followup_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a follow-up is deleted by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_followup_deleted_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a ticket is reassigned', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_ticket_reassigned_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff-only follow-up is added/edited on a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_staff_only_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When any status or metadata changed for a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_ticket_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff member subscribes to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_new_subscriber_added_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a subscriber is removed from a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_staff_events_subscriber_removed_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => true
			),
			// Staff notifications end

			// Group notifications Start

			array(
				'title'         => __( 'Notification Event for group', RTBIZ_HD_TEXT_DOMAIN),
				'desc'          => __( 'When a customer creates a ticket via the web form or email', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_ticket_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a new follow-up is added to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),
			array(
				'desc'          => __( 'When a follow-up is edited by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_followup_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a follow-up is deleted by staff/customer', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_followup_deleted_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a ticket is reassigned', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_ticket_reassigned_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff-only follow-up is added/edited on a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_staff_only_followup_created_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When any status or metadata changed for a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_ticket_updated_mail',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a staff member subscribes to a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_new_subscriber_added_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => true
			),

			array(
				'desc'          => __( 'When a subscriber is removed from a ticket', RTBIZ_HD_TEXT_DOMAIN ),
				'id'            => 'rthd_settings_notification_acl_group_events_subscriber_removed_mail',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => true
			),
			// Group notifications end


			array( 'type' => 'sectionend', 'id' => 'Notification_options'),

			array( 'title' => __( 'Email Signature', RTBIZ_HD_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'email_header_options' ),


			array(
				'title'    => __( 'Enable email signature', RTBIZ_HD_TEXT_DOMAIN ),
				'desc'     => __( 'Enable will append signature to each mail', RTBIZ_HD_TEXT_DOMAIN ),
				'id'       => 'rthd_settings_enable_signature',
				'default'  => 'off',
				'type'     => 'radio',
				'options'  => array(
					'on'       => __( 'Enable', RTBIZ_HD_TEXT_DOMAIN ),
					'off' => __( 'Disable', RTBIZ_HD_TEXT_DOMAIN ),
				),
				'desc_tip' =>  true,
				'autoload' => true
			),

			array(
				'title'    => __( 'Email Signature', RTBIZ_HD_TEXT_DOMAIN ),
				'desc'     => 'Add here Email Signature',
				'id'       => 'rthd_settings_email_signature',
				'default'  => '<br />Sent via rtBiz Helpdesk Plugin<br />',
				'type'     => 'textarea',
				'css'     => 'width:400px; height: 65px;',
				'autoload' => true
			),

			array( 'type' => 'sectionend', 'id' => 'email_header_options'),
		) );

		return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
	}

}
endif;

return new rtBiz_HD_Settings_Email_Notification();
