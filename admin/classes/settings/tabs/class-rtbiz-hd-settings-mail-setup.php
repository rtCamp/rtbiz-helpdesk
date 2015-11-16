<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_Mail_Setup ' ) ) :
	class rtBiz_HD_Settings_Mail_Setup extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_hd_mail_setup';
			$this->label = __( 'Mail Setup', RTBIZ_TEXT_DOMAIN );
			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_admin_field_rtbiz_hd_mailbox_setup', 'rtbiz_hd_mailbox_setup_view' );
		}


		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {
			$system_emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );

			$mailbox_options = array();
			foreach ( $system_emails as $email ) {
				$mailbox_options[ $email ] = $email;
			}
			$domain_name = preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] );
			$domain_name = 'noreply@' . $domain_name;

			$settings = array(

				array(
					'title' => __( 'Mail Setup', RTBIZ_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'mailbox_setup_option'
				),
				array(
					'title'    => __( 'Mailboxes Setup', RTBIZ_TEXT_DOMAIN ),
					'id'       => 'rtbiz_hd_mailbox_setup',
					'default'  => 'no',
					'type'     => 'rtbiz_hd_mailbox_setup',
					'autoload' => false
				),
			);
			if ( ! empty( $system_emails ) ) {
				array_push( $settings,
					array(
						'title'    => __( 'Email support', RTBIZ_HD_TEXT_DOMAIN ),
						'desc'     => __( 'Allows customer to create tickets and reply tickets from configured mailbox.', RTBIZ_HD_TEXT_DOMAIN ),
						'id'       => 'rthd_settings_email_support',
						'default'  => 'on',
						'type'     => 'radio',
						'options'  => array(
							'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
							'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
						),
						'autoload' => true
					),
					array(
						'title'    => __( 'Mailbox Reading', RTBIZ_HD_TEXT_DOMAIN ),
						'desc'     => __( 'To enable/disable Mailbox Reading', RTBIZ_HD_TEXT_DOMAIN ),
						'id'       => 'rthd_settings_enable_mailbox_reading',
						'default'  => 'on',
						'type'     => 'radio',
						'options'  => array(
							'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
							'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
						),
						'autoload' => true
					),
					array(
						'id'       => 'rthd_settings_reply_via_email',
						'type'     => 'radio',
						'title'    => __( 'Reply Via Email', RTBIZ_HD_TEXT_DOMAIN ),
						'desc'     => __( 'To enable/disable Reply Via Email', RTBIZ_HD_TEXT_DOMAIN ),
						'default'  => 'on',
						'options'  => array(
							'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
							'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
						),
						'autoload' => true
					) );
			}
			array_push( $settings,
				array(
					'id'       => 'rthd_settings_web_support',
					'type'     => 'radio',
					'title'    => __( 'Web support', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'Allows customer to create support tickets and reply tickets from web interface.', RTBIZ_HD_TEXT_DOMAIN ),
					'default'  => 'on',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				) );
			if ( ! empty( $system_emails ) ) {
				array_push( $settings,
					array(
						'title'    => __( 'Outgoing Emails Mailbox', RTBIZ_HD_TEXT_DOMAIN ),
						'id'       => 'rthd_settings_outgoing_email_mailbox',
						'desc_tip' => sprintf( '%s.', __( 'Choose any one email from the configured mailboxes.', RTBIZ_HD_TEXT_DOMAIN ) ),
						'desc'     => __( 'Mailbox to be used to send outgoing emails/notifications.', RTBIZ_HD_TEXT_DOMAIN ),
						'type'     => 'select',
						'options'  => $mailbox_options,
					) );
				if ( count( $mailbox_options ) >= 2 ) {

					array_push( $email_fields, array(
						array(
							'id'       => 'rthd_settings_outgoing_via_same_email',
							'type'     => 'radio',
							'title'    => __( 'Outgoing Emails From Same Mailbox', RTBIZ_HD_TEXT_DOMAIN ),
							'desc'     => __( 'To enable/disable outgoing emails from same mailbox', RTBIZ_HD_TEXT_DOMAIN ),
							'default'  => 'on',
							'options'  => array(
								'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
								'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
							),
							'autoload' => true
						)
					) );
				}
			} else {
				array_push( $settings,
					array(
						'title'    => __( 'Outgoing Emails \'From\' Address', RTBIZ_HD_TEXT_DOMAIN ),
						'desc'     => 'Outgoing System Email used for all Helpdesk Communication',
						'desc_tip' => sprintf( '%s.', __( 'This Address will be used as FROM: name < email address > for all outgoing emails', RTBIZ_HD_TEXT_DOMAIN ) ),
						'id'       => 'rthd_settings_outgoing_email_mailbox',
						'default'  => $domain_name,
						'type'     => 'email',
						'css'      => 'width:400px;',
						'autoload' => true
					) );
			}
			array_push( $settings,
				array(
					'title'    => __( 'Outgoing Emails \'From\' Name', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => 'Outgoing System Name used for all Helpdesk Communication',
					'desc_tip' => sprintf( '%s.', __( 'System Name to be used for outbound emails. This Name will be used as FROM: name < email address > for all outgoing emails', RTBIZ_HD_TEXT_DOMAIN ) ),
					'id'       => 'rthd_settings_outgoing_email_from_name',
					'default'  => get_bloginfo(),
					'type'     => 'text',
					'css'      => 'width:400px;',
					'autoload' => true
				),

				array(
					'id'       => 'rthd_settings_blacklist_emails_textarea',
					'title'    => __( 'Blacklist Emails', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'Email addresses to be blacklisted for creating tickets / follow-ups.', RTBIZ_HD_TEXT_DOMAIN ),
					'desc_tip' => __( 'All mails coming from these addresses will be blocked by Helpdesk. It also accept arguments like *@example.com, @example.*, Keep each email in new line without comma(,).', RTBIZ_HD_TEXT_DOMAIN ),
					'type'     => 'textarea',
					'css'      => 'width:400px; height: 65px;',
					'autoload' => true
				),

				array( 'type' => 'sectionend', 'id' => 'mailbox_setup_option' )

			);

			$settings = apply_filters( 'rtbiz_mailbox_settings', $settings );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_Mail_Setup();
