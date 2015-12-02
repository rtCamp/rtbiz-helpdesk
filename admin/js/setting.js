/**
 * Created by dips on 1/12/15.
 */
jQuery( document ).ready(function () {
	var rthdSettings = {
		init: function () {
			rthdSettings.initMailsetup()
			rthdSettings.initNotificationsetup()
			rthdSettings.initAdvanceSetting()
			rthdSettings.initSaveForm()
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
		initDayValidation: function ($tr_parent) {
			var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
			var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
			var flag = true;
			var allflag = true;

			if (starting_val == -1 && ending_val == -1) {
				jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if (starting_val == -1 || ending_val == -1) {
					if (starting_val == -1) {
						jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if (parseInt( ending_val ) < parseInt( starting_val )) {
					jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			jQuery( '#rthd-response-day-error' ).hide().html( '' );
			if (flag) {
				if (jQuery( '#rthd_autoresponse_weekend' ).val() === 0) { // if Weekend only off then che check weektime enter or not
					for (var i = 0; i < 7; i++) {
						$tr_parent = jQuery( '.rthd-dayshift-time-start' ).eq( i ).parent().parent();
						starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
						ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
						if (starting_val != -1 || ending_val != -1) {
							allflag = false;
						}
					}
					if (allflag) {
						jQuery( '#rthd-response-day-error' ).show().html( 'please select working time' );
						flag = false;
					}
				}
			}

			return flag;
		},
		initDayNightValidation: function ($tr_parent) {
			var starting_am_val = $tr_parent.find( '.rthd-daynight-am-time-start' ).val();
			var ending_am_val = $tr_parent.find( '.rthd-daynight-am-time-end' ).val();
			var starting_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-start' ).val();
			var ending_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-end' ).val();
			var flag = true;
			var allflag = true;

			if (starting_am_val == -1 && ending_am_val == -1) {
				jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if (starting_am_val == -1 || ending_am_val == -1) {
					if (starting_am_val == -1) {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if (parseInt( ending_am_val ) < parseInt( starting_am_val )) {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			if (starting_pm_val == -1 && ending_pm_val == -1) {
				jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if (starting_pm_val == -1 || ending_pm_val == -1) {
					if (starting_pm_val == -1) {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if (parseInt( ending_pm_val ) < parseInt( starting_pm_val )) {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			jQuery( '#rthd-response-daynight-error' ).hide().html( '' );
			if (flag) {
				if (jQuery( '#rthd_autoresponse_weekend' ).val() === 0) { // if Weekend only off then che check weektime enter or not
					for (var i = 0; i < 7; i++) {
						$tr_parent = jQuery( '.rthd-daynight-am-time-start' ).eq( i ).parent().parent();
						starting_am_val = $tr_parent.find( '.rthd-daynight-am-time-start' ).val();
						ending_am_val = $tr_parent.find( '.rthd-daynight-am-time-end' ).val();
						starting_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-start' ).val();
						ending_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-end' ).val();
						if (starting_am_val != -1 || ending_am_val != -1 || starting_pm_val != -1 || ending_pm_val != -1) {
							allflag = false;
						}
					}
					if (allflag) {
						jQuery( '#rthd-response-daynight-error' ).show().html( 'please select working time' );
						flag = false;
					} else {

					}
				}
			}

			return flag;
		},
		toggleAutoresponseMode : function( value ){
			if ( value  == 'on' ){
				jQuery("#rthd-response-day-error").parents("tr:first").show();
				jQuery("#rthd-response-daynight-error").parents("tr:first").hide();

				//set for all
				jQuery( '#rthd-dayshift-time-set-all' ).click(function ( e ) {
					jQuery('.rthd-dayshift-time-start').val( jQuery('#rthd-dayshift-time-start-0').val()).change();
					jQuery('.rthd-dayshift-time-end').val( jQuery('#rthd-dayshift-time-end-0').val()).change();
					e.preventDefault();
				});

				//day shift
				jQuery( '.rthd-dayshift-time-end' ).change(function () {
					rthdSettings.initDayValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '.rthd-dayshift-time-start' ).change(function () {
					rthdSettings.initDayValidation( jQuery( this ).parent().parent() );
				});

			} else {
				jQuery("#rthd-response-day-error").parents("tr:first").hide();
				jQuery("#rthd-response-daynight-error").parents("tr:first").show();

				//set for all
				jQuery( '#rthd-daynight-time-set-all' ).click(function ( e ) {
					jQuery('.rthd-daynight-am-time-start').val( jQuery('#rthd-daynight-am-time-start-0').val() ).change();
					jQuery('.rthd-daynight-am-time-end').val( jQuery('#rthd-daynight-am-time-end-0').val() ).change();
					jQuery('.rthd-daynight-pm-time-start').val( jQuery('#rthd-daynight-pm-time-start-0').val() ).change();
					jQuery('.rthd-daynight-pm-time-end').val( jQuery('#rthd-daynight-pm-time-end-0').val() ).change();
					e.preventDefault();
				});

				//day/night shift
				jQuery( '.rthd-daynight-am-time-start' ).change(function () {
					rthdSettings.initDayNightValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '.rthd-daynight-am-time-end' ).change(function () {
					rthdSettings.initDayNightValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '.rthd-daynight-pm-time-start' ).change(function () {
					rthdSettings.initDayNightValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '.rthd-daynight-pm-time-end' ).change(function () {
					rthdSettings.initDayNightValidation( jQuery( this ).parent().parent() );
				});
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
		},
		initSaveForm: function(){
			jQuery('#mainform').submit(function ( e ) {
				var qd = {};
				location.search.substr(1).split("&").forEach(function(item) {var s = item.split("="), k = s[0], v = s[1] && decodeURIComponent(s[1]); qd[k] = v});
				if ( qd['page'] == 'rtbiz-ticket-settings' ) {

					// advance setting validation
					if (qd['tab'] == 'rtbiz_hd_advance_settings') {
						var flag = true, i, tr_parent;
						var autoresponse = jQuery("input[name='rthd_settings_enable_auto_response']:checked").val();
						if ( autoresponse  == 'on' ){
							var autoresponsemode = jQuery("input[name='rthd_settings_enable_auto_response_mode']:checked").val();
							if ( autoresponsemode  == 'on' ){
								for ( i = 0; i < 7; i++) {
									tr_parent = jQuery( '.rthd-dayshift-time-start' ).eq( i ).parent().parent();
									if ( ! rthdSettings.initDayValidation( tr_parent )) {
										flag = false;
									}
								}
							} else {
								for ( i = 0; i < 7; i++) {
									tr_parent = jQuery( '.rthd-daynight-am-time-start' ).eq( i ).parent().parent();
									if ( ! rthdSettings.initDayNightValidation( tr_parent )) {
										flag = false;
									}
								}
							}

						}
						return flag;
					}

				}
			});
		}
	};
	rthdSettings.init();
});

