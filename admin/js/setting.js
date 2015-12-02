/**
 * Created by dips on 1/12/15.
 */
jQuery( document ).ready(function () {
	var rthdSettings = {
		init: function () {
			rthdSettings.initMailsetup()
			rthdSettings.initNotificationsetup()
			rthdSettings.initAdvanceSetting()
		},
		toggleEmailSupport : function( value ){
			if ( value  == 'on' ){
				jQuery("input[name='rthd_settings_enable_mailbox_reading']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_reply_via_email']").parents("tr:first").show();
			} else {
				jQuery("input[name='rthd_settings_enable_mailbox_reading']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_reply_via_email']").parents("tr:first").hide();
			}
		},
		initMailsetup: function () {
			// if mailbox configure then setting display
			if ( jQuery('#mailbox-list>.rtmailbox-row').size() > 0 ){
				jQuery("input[name='rthd_settings_email_support']").parents("tr:first").show();

				//if Email support enable display mailbox reading & replay via email
				rthdSettings.toggleEmailSupport( jQuery("input[name='rthd_settings_email_support']:checked").val() );
				jQuery("input[name='rthd_settings_email_support']").change(function( ){
					rthdSettings.toggleEmailSupport( jQuery( this ).val() );
				});
			} else {
				jQuery("input[name='rthd_settings_email_support']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_enable_mailbox_reading']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_reply_via_email']").parents("tr:first").hide();
			}

		},
		toggleNotificationAcl : function( value ){
			if ( value  == 'on' ){
				jQuery("h3:contains('Notification Options')").show();
				jQuery("input[name='rthd_settings_notification_emails']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_client_new_ticket_created_mail']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_assignee_new_ticket_created_mail']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_staff_new_ticket_created_mail']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_group_new_ticket_created_mail']").parents("tr:first").show();
			} else {
				jQuery("h3:contains('Notification Options')").hide();
				jQuery("input[name='rthd_settings_notification_emails']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_client_new_ticket_created_mail']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_assignee_new_ticket_created_mail']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_staff_new_ticket_created_mail']").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_group_new_ticket_created_mail']").parents("tr:first").hide();
			}
		},
		toggleEmailSignature : function( value ){
			if ( value  == 'on' ){
				jQuery("textarea#rthd_settings_email_signature").parents("tr:first").show();
			} else {
				jQuery("textarea#rthd_settings_email_signature").parents("tr:first").hide();
			}
		},
		initNotificationsetup: function(){
			//if Notification acl enable display Notification event
			rthdSettings.toggleNotificationAcl( jQuery("input[name='rthd_settings_enable_notification_acl']:checked").val() );
			jQuery("input[name='rthd_settings_enable_notification_acl']").change(function( ){
				rthdSettings.toggleNotificationAcl( jQuery( this ).val() );
			});

			//if Email Signature enable display Email Signature
			rthdSettings.toggleEmailSignature( jQuery("input[name='rthd_settings_enable_signature']:checked").val() );
			jQuery("input[name='rthd_settings_enable_signature']").change(function( ){
				rthdSettings.toggleEmailSignature( jQuery( this ).val() );
			});
		},
		toggleAutoassign : function( value ){
			if ( value  == 'on' ){
				jQuery("input[name='rthd_settings_auto_assign_events']").parents("tr:first").show();
			} else {
				jQuery("input[name='rthd_settings_auto_assign_events']").parents("tr:first").hide();
			}
		},
		toggleAutoresponseMode : function( value ){
			if ( value  == 'on' ){
				jQuery("#rthd-response-day-error").parents("tr:first").show();
				jQuery("#rthd-response-daynight-error").parents("tr:first").hide();
			} else {
				jQuery("#rthd-response-day-error").parents("tr:first").hide();
				jQuery("#rthd-response-daynight-error").parents("tr:first").show();
			}
		},
		toggleAutoresponse : function( value ){
			if ( value  == 'on' ){
				jQuery("input[name='rthd_settings_enable_auto_response_mode']").parents("tr:first").show();
				jQuery("input[name='rthd_settings_autoresponse_weekend']").parents("tr:first").show();
				jQuery("textarea#rthd_settings_auto_response_message").parents("tr:first").show();

				//auto response mode setting
				rthdSettings.toggleAutoresponseMode( jQuery("input[name='rthd_settings_enable_auto_response_mode']:checked").val() );
				jQuery("input[name='rthd_settings_enable_auto_response_mode']").change(function( ){
					rthdSettings.toggleAutoresponseMode( jQuery( this ).val() );
				});
			} else {
				jQuery("input[name='rthd_settings_enable_auto_response_mode']").parents("tr:first").hide();
				jQuery("#rthd-response-day-error").parents("tr:first").hide();
				jQuery("#rthd-response-daynight-error").parents("tr:first").hide();
				jQuery("input[name='rthd_settings_autoresponse_weekend']").parents("tr:first").hide();
				jQuery("textarea#rthd_settings_auto_response_message").parents("tr:first").hide();
			}
		},
		initAdvanceSetting: function(){
			//if Auto Assign enable display Auto Assign event
			rthdSettings.toggleAutoassign( jQuery("input[name='rthd_settings_enable_auto_assign']:checked").val() );
			jQuery("input[name='rthd_settings_enable_auto_assign']").change(function( ){
				rthdSettings.toggleAutoassign( jQuery( this ).val() );
			});

			//if Auto response enable display Auto response setting
			rthdSettings.toggleAutoresponse( jQuery("input[name='rthd_settings_enable_auto_response']:checked").val() );
			jQuery("input[name='rthd_settings_enable_auto_response']").change(function( ){
				rthdSettings.toggleAutoresponse( jQuery( this ).val() );
			});
		}
	};
	rthdSettings.init();
});

