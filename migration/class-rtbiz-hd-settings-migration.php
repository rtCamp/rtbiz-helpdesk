<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 3:48 PM
 */

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'rtBiz_HD_Settings_Migration' ) ) :

	class rtBiz_HD_Settings_Migration {

		/**
		 * rtBiz_Settings_Migration constructor.
		 */
		public function __construct() {
			//add_action( 'rt_db_update_finished', array( $this, 'setting_migration' ) );
			add_action( 'init', array( $this, 'setting_migration' ) );
		}

		public function setting_migration() {
			$flag = get_option( 'rthd_setting_migration_complete' );
			if ( empty( $flag ) ) {
				if ( version_compare( RTBIZ_HD_VERSION, '1.6', '>=' ) ) {
					/*$redux = get_option( 'redux_helpdesk_settings', array() );
					echo '<pre style="margin-left: 500px;">';
					var_dump( $redux );
					echo '</pre>';*/

					$redux =  get_option( 'redux_helpdesk_settings', array() );
					if ( empty( $redux ) ) {
						return;
					}

					update_option( 'rthd_settings_support_page', ! empty( $redux['rthd_support_page'] ) ? $redux['rthd_support_page'] : '' );
					update_option( 'rtbiz_product_plugin', ! empty( $redux['product_plugin'] ) ? $redux['product_plugin'] : array() );
					update_option( 'rthd_settings_default_user', ! empty( $redux['rthd_default_user'] ) ? $redux['rthd_default_user'] : '' );
					update_option( 'rthd_settings_enable_ticket_unique_hash', ! empty( $redux['rthd_enable_ticket_unique_hash'] ) ? 'on' : 'off' );


					update_option( 'rthd_settings_email_support', ! empty( $redux['rthd_email_support'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_enable_mailbox_reading', ! empty( $redux['rthd_enable_mailbox_reading'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_reply_via_email', ! empty( $redux['rthd_reply_via_email'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_web_support', ! empty( $redux['rthd_web_support'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_outgoing_email_mailbox', ! empty( $redux['rthd_outgoing_email_mailbox'] ) ? $redux['rthd_outgoing_email_mailbox'] : '' );
					update_option( 'rthd_settings_outgoing_email_from_name', ! empty( $redux['rthd_outgoing_email_from_name'] ) ? $redux['rthd_outgoing_email_from_name'] : '' );
					update_option( 'rthd_settings_outgoing_via_same_email', ! empty( $redux['rthd_outgoing_via_same_email'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_blacklist_emails_textarea', ! empty( $redux['rthd_blacklist_emails_textarea'] ) ? $redux['rthd_blacklist_emails_textarea'] : '' );


					update_option( 'rthd_settings_enable_notification_acl', ! empty( $redux['rthd_enable_notification_acl'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_notification_emails', ! empty( $redux['rthd_notification_emails'] ) ? implode( ',', $redux['rthd_notification_emails'] ) : '' );

					update_option( 'rthd_settings_client_new_ticket_created_mail', ! empty( $redux['rthd_notification_acl_client_events']['new_ticket_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_client_new_followup_created_mail', ! empty( $redux['rthd_notification_acl_client_events']['new_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_client_new_followup_updated_mail', ! empty( $redux['rthd_notification_acl_client_events']['new_followup_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_client_new_followup_deleted_mail', ! empty( $redux['rthd_notification_acl_client_events']['new_followup_deleted_mail'] ) ? 'yes' : 'no' );

					update_option( 'rthd_settings_assignee_new_ticket_created_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_ticket_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_new_followup_created_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_new_followup_updated_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_followup_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_new_followup_deleted_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_followup_deleted_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_ticket_reassigned_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['ticket_reassigned_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_new_staff_only_followup_created_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_staff_only_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_ticket_updated_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['ticket_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_new_subscriber_added_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['new_subscriber_added_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_assignee_subscriber_removed_mail', ! empty( $redux['rthd_notification_acl_assignee_events']['subscriber_removed_mail'] ) ? 'yes' : 'no' );

					update_option( 'rthd_settings_staff_new_ticket_created_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_ticket_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_new_followup_created_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_new_followup_updated_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_followup_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_new_followup_deleted_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_followup_deleted_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_ticket_reassigned_mail', ! empty( $redux['rthd_notification_acl_staff_events']['ticket_reassigned_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_new_staff_only_followup_created_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_staff_only_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_ticket_updated_mail', ! empty( $redux['rthd_notification_acl_staff_events']['ticket_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_new_subscriber_added_mail', ! empty( $redux['rthd_notification_acl_staff_events']['new_subscriber_added_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_staff_subscriber_removed_mail', ! empty( $redux['rthd_notification_acl_staff_events']['subscriber_removed_mail'] ) ? 'yes' : 'no' );

					update_option( 'rthd_settings_group_new_ticket_created_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_ticket_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_new_followup_created_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_new_followup_updated_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_followup_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_new_followup_deleted_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_followup_deleted_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_ticket_reassigned_mail', ! empty( $redux['rthd_notification_acl_group_events']['ticket_reassigned_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_new_staff_only_followup_created_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_staff_only_followup_created_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_ticket_updated_mail', ! empty( $redux['rthd_notification_acl_group_events']['ticket_updated_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_new_subscriber_added_mail', ! empty( $redux['rthd_notification_acl_group_events']['new_subscriber_added_mail'] ) ? 'yes' : 'no' );
					update_option( 'rthd_settings_group_subscriber_removed_mail', ! empty( $redux['rthd_notification_acl_group_events']['subscriber_removed_mail'] ) ? 'yes' : 'no' );

					update_option( 'rthd_settings_enable_signature', ! empty( $redux['rthd_enable_signature'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_email_signature', ! empty( $redux['rthd_email_signature'] ) ? $redux['rthd_email_signature'] : '' );

					update_option( 'rthd_settings_enable_auto_assign', ! empty( $redux['rthd_enable_auto_assign'] ) ? 'on' : 'off' );
					update_option( 'rthd_settings_auto_assign_events', ! empty( $redux['rthd_auto_assign_events'] ) ? $redux['rthd_auto_assign_events'] : '' );
					update_option( 'rthd_settings_enable_auto_response', ! empty( $redux['rthd_enable_auto_response'] ) ? 'on' : 'off' ); //todo jquery
					update_option( 'rthd_settings_enable_auto_response_mode', ! empty( $redux['rthd_enable_auto_response_mode'] ) ? 'on' : 'off' ); //todo jquery

					update_option( 'rthd_settings_dayshift_time_start', ! empty( $redux['rthd_dayshift_time_start'] ) ? $redux['rthd_dayshift_time_start'] : '' );
					update_option( 'rthd_settings_dayshift_time_end', ! empty( $redux['rthd_dayshift_time_end'] ) ? $redux['rthd_dayshift_time_end'] : '' );
					update_option( 'rthd_settings_daynight_am_time_start', ! empty( $redux['rthd_daynight_am_time_start'] ) ? $redux['rthd_daynight_am_time_start'] : '' );
					update_option( 'rthd_settings_daynight_am_time_end', ! empty( $redux['rthd_daynight_am_time_end'] ) ? $redux['rthd_daynight_am_time_end'] : '' );
					update_option( 'rthd_settings_daynight_pm_time_start', ! empty( $redux['rthd_daynight_pm_time_start'] ) ? $redux['rthd_daynight_pm_time_start'] : '' );
					update_option( 'rthd_settings_daynight_pm_time_end', ! empty( $redux['rthd_daynight_pm_time_end'] ) ? $redux['rthd_daynight_pm_time_end'] : '' );

					update_option( 'rthd_settings_autoresponse_weekend', ! empty( $redux['rthd_autoresponse_weekend'] ) ? 'on' : 'off' ); //todo jquery
					update_option( 'rthd_settings_auto_response_message', ! empty( $redux['rthd_auto_response_message'] ) ? $redux['rthd_auto_response_message'] : '' );
					update_option( 'rthd_settings_enable_ticket_adult_content', ! empty( $redux['rthd_enable_ticket_adult_content'] ) ? 'on' : 'off' );

				}
				update_option( 'rthd_setting_migration_complete', 'yes' );
			}

		}
	}

endif;
