/**
 * Created by sai on 6/9/14.
 */
jQuery( document ).ready( function () {

	var file_frame_ticket;
	var rthdAdmin = {
		rthd_tinymce_set_content: function ( id, text ) {
			if ( typeof tinymce != "undefined" ) {
				var editor = tinymce.get( id );
				if ( editor && editor instanceof tinymce.Editor ) {
					editor.setContent( text );
					editor.save( { no_events: true } );
				} else {
					jQuery( '#' + id ).val( text );
				}
				return true;
			}
			return false;
		},
		rthd_tinymce_get_content: function ( id ) {
			if ( typeof tinymce != "undefined" ) {
				var editor = tinymce.get( id );
				if ( editor && editor instanceof tinymce.Editor ) {
					return editor.getContent();
				} else {
					return jQuery( '#' + id ).val();
				}
			}
			return '';
		},
		init: function () {
			rthdAdmin.initDatePicket();
			rthdAdmin.initDateTimePicker();
			rthdAdmin.initMomentJS();
			rthdAdmin.initattchmentJS();
			rthdAdmin.initExternalFileJS();
			rthdAdmin.initSubscriberSearch();
			rthdAdmin.initAutoResponseSettings();
			rthdAdmin.initBlacklistConfirmationOrRemove();
			rthdAdmin.initAddContactBlacklist();
		},
		initDatePicket: function () {
			if ( jQuery( ".datepicker" ).length > 0 ) {
				jQuery( ".datepicker" ).datepicker( {
					'dateFormat': 'M d,yy',
					onClose: function ( newDate, inst ) {

						if ( jQuery( this ).hasClass( "moment-from-now" ) ) {
							var oldDate = jQuery( this ).attr( "title" );

							if ( newDate != "" && moment( newDate ).isValid() ) {
								jQuery( this ).val( moment( new Date( newDate ) ).fromNow() );
								jQuery( this ).attr( "title", newDate );

								if ( jQuery( this ).next().length > 0 ) {
									jQuery( this ).next().val( newDate );
								}
							} else if ( oldDate != "" ) {
								jQuery( this ).val( moment( new Date( oldDate ) ).fromNow() );
								jQuery( this ).attr( "title", oldDate );

								if ( jQuery( this ).next().length > 0 ) {
									jQuery( this ).next().val( newDate );
								}
							}
						}
					}
				} );
			}
			jQuery( ".datepicker-toggle" ).click( function ( e ) {
				jQuery( "#" + jQuery( this ).data( "datepicker" ) ).datepicker( "show" );
			} )
		},
		initDateTimePicker: function () {
			if ( jQuery( ".datetimepicker" ).length > 0 ) {
				jQuery( ".datetimepicker" ).datetimepicker( {
					dateFormat: "M d, yy",
					timeFormat: "hh:mm TT",
					onClose: function ( newDate, inst ) {

						var oldDate = jQuery( this ).attr( "title" );

						if ( newDate != "" && moment( newDate ).isValid() ) {
							jQuery( this ).val( moment( new Date( newDate ) ).fromNow() );
							jQuery( this ).attr( "title", newDate );

							if ( jQuery( this ).next().length > 0 ) {
								if ( jQuery( this ).hasClass( "moment-from-now" ) ) {
									jQuery( this ).next().val( newDate );
								}
							} else if ( oldDate != "" ) {
								jQuery( this ).val( moment( new Date( oldDate ) ).fromNow() );
								jQuery( this ).attr( "title", oldDate );

								if ( jQuery( this ).next().length > 0 ) {
									jQuery( this ).next().val( newDate );
								}
							}
						}
					}
				} );
			}
		},
		initMomentJS: function () {
			jQuery( document ).on( "click", ".moment-from-now", function ( e ) {
				var oldDate = jQuery( this ).attr( "title" );

				if ( oldDate != "" ) {
					jQuery( this ).datepicker( "setDate", new Date( jQuery( this ).attr( "title" ) ) );
				}
			} );

			jQuery( ".moment-from-now" ).each( function () {

				if ( jQuery( this ).is( "input[type='text']" ) && jQuery( this ).val() != "" ) {
					jQuery( this ).val( moment( new Date( jQuery( this ).attr( "title" ) ) ).fromNow() );
				} else if ( jQuery( this ).is( ".comment-date" ) ) {
					jQuery( this ).html( moment( new Date( jQuery( this ).attr( "title" ) ) ).fromNow() );
				} else {
					jQuery( this ).html( moment( new Date( jQuery( this ).html() ) ).fromNow() );
				}
			} );
		},
		initattchmentJS: function () {
			jQuery( document ).on( 'click', '.rthd_delete_attachment', function ( e ) {
				e.preventDefault();
				jQuery( this ).parent().remove();
			} );

			jQuery( '#add_ticket_attachment' ).on( 'click', function ( e ) {
				e.preventDefault();
				if ( file_frame_ticket ) {
					file_frame_ticket.open();
					return;
				}
				file_frame_ticket = wp.media.frames.file_frame = wp.media( {
					title: jQuery( this ).data( 'uploader_title' ),
					searchable: true,
					button: {
						text: 'Attach Selected Files'
					},
					multiple: true // Set to true to allow multiple files to be selected
				} );
				file_frame_ticket.on( 'select', function () {
					var selection = file_frame_ticket.state().get( 'selection' );
					var strAttachment = '';
					selection.map( function ( attachment ) {
						attachment = attachment.toJSON();
						strAttachment = '<li data-attachment-id="' + attachment.id + '" class="attachment-item row_group">';
						strAttachment += '<a href="#" class="delete_row rthd_delete_attachment"><span class="dashicons dashicons-dismiss"></span></a>';
						strAttachment += '<a target="_blank" href="' + attachment.url + '"><img height="20px" width="20px" src="' + attachment.icon + '" > ' + attachment.filename + '</a>';
						strAttachment += '<input type="hidden" name="attachment[]" value="' + attachment.id + '" /></div>';

						jQuery( "#attachment-container .scroll-height" ).append( strAttachment );

						// Do something with attachment.id and/or attachment.url here
					} );
					// Do something with attachment.id and/or attachment.url here
				} );
				file_frame_ticket.open();
			} );
		},
		initExternalFileJS: function () {
			var exf_count = 12345;
			jQuery( "#add_new_ex_file" ).click( function ( e ) {
				var title = jQuery( "#add_ex_file_title" ).val();
				var link = jQuery( "#add_ex_file_link" ).val();
				if ( jQuery.trim( link ) == "" ) {
					return false;
				}
				jQuery( "#add_ex_file_title" ).val( "" );
				jQuery( "#add_ex_file_link" ).val( "" );

				var tmpstr = '<div class="row_group">';
				tmpstr += '<button class="delete_row removeMeta"><i class="foundicon-minus"></i>X</button>';
				tmpstr += '<input type="text" name="ticket_ex_files[' + exf_count + '][title]" value="' + title + '" />';
				tmpstr += '<input type="text" name="ticket_ex_files[' + exf_count + '][link]" value="' + link + '" />';
				tmpstr += '</div>';
				exf_count ++;
				jQuery( "#external-files-container" ).append( tmpstr );
			} );

		},
		initSubscriberSearch: function () {
			try {
				if ( arr_subscriber_user != undefined ) {
					jQuery( "#subscriber_user_ac" ).autocomplete( {
						source: function ( request, response ) {
							var term = jQuery.ui.autocomplete.escapeRegex( request.term ), startsWithMatcher = new RegExp( "^" + term, "i" ), startsWith = jQuery.grep( arr_subscriber_user, function ( value ) {
								return startsWithMatcher.test( value.label || value.value || value );
							} ), containsMatcher = new RegExp( term, "i" ), contains = jQuery.grep( arr_subscriber_user, function ( value ) {
								return jQuery.inArray( value, startsWith ) < 0 && containsMatcher.test( value.label || value.value || value );
							} );

							response( startsWith.concat( contains ) );
						},
						focus: function ( event, ui ) {

						},
						select: function ( event, ui ) {
							if ( jQuery( "#subscribe-auth-" + ui.item.id ).length < 1 ) {
								jQuery( "#divSubscriberList" ).append( "<li id='subscribe-auth-" + ui.item.id + "' class='contact-list' >" + ui.item.imghtml + "<a href='#removeSubscriber' class='delete_row'><span class='dashicons dashicons-dismiss'></span></a><br/><a class='subscribe-title heading' target='_blank' href='" + ui.item.user_edit_link + "'>" + ui.item.label + "</a><input type='hidden' name='subscribe_to[]' value='" + ui.item.id + "' /></li>" )
							}
							jQuery( "#subscriber_user_ac" ).val( "" );
							return false;
						}
					} ).data( "ui-autocomplete" )._renderItem = function ( ul, item ) {
						return jQuery( "<li></li>" ).data( "ui-autocomplete-item", item ).append( "<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>" ).appendTo( ul );
					};

					jQuery( document ).on( 'click', "a[href=#removeSubscriber]", function ( e ) {
						e.preventDefault();
						jQuery( this ).parent().remove();
					} );

				}
			} catch ( e ) {

			}
		},
		initAutoResponseSettings: function () {

			if ( jQuery( '#rthd_enable_auto_response_mode' ) ) {

				//day shift
				jQuery( '.rthd-dayshift-time-end' ).change( function () {
					rthdAdmin.initDayValidation( jQuery( this ).parent().parent() );
				} );
				jQuery( '.rthd-dayshift-time-start' ).change( function () {
					rthdAdmin.initDayValidation( jQuery( this ).parent().parent() );
				} );

				//day/night shift
				jQuery( '.rthd-daynight-am-time-start' ).change( function () {
					rthdAdmin.initDayNightValidation( jQuery( this ).parent().parent() );
				} );
				jQuery( '.rthd-daynight-am-time-end' ).change( function () {
					rthdAdmin.initDayNightValidation( jQuery( this ).parent().parent() );
				} );
				jQuery( '.rthd-daynight-pm-time-start' ).change( function () {
					rthdAdmin.initDayNightValidation( jQuery( this ).parent().parent() );
				} );
				jQuery( '.rthd-daynight-pm-time-end' ).change( function () {
					rthdAdmin.initDayNightValidation( jQuery( this ).parent().parent() );
				} );

				jQuery.ajaxPrefilter( function ( options, originalOptions, jqXHR ) {
					var action = JSON.stringify( options.data );
					if ( action.indexOf( 'action=redux_helpdesk_settings_ajax_save&' ) !== - 1 ) {
						var flag = true;
						if ( jQuery( '#rthd_enable_auto_response' ).val() == 0 ) {
							return true
						}
						if ( jQuery( '#rthd_enable_auto_response_mode' ).val() == 1 ) {
							for ( var i = 0; i < 7; i ++ ) {
								var tr_parent = jQuery( '.rthd-dayshift-time-start' ).eq( i ).parent().parent();
								if ( ! rthdAdmin.initDayValidation( tr_parent ) ) {
									flag = false;
								}
							}
						}

						if ( jQuery( '#rthd_enable_auto_response_mode' ).val() == 0 ) {
							for ( var i = 0; i < 7; i ++ ) {
								var tr_parent = jQuery( '.rthd-daynight-am-time-start' ).eq( i ).parent().parent();
								if ( ! rthdAdmin.initDayNightValidation( tr_parent ) ) {
									flag = false;
								}
							}
						}

						if ( ! flag ) {
							jqXHR.abort();
							jQuery( '.redux-action_bar input' ).removeAttr( 'disabled' );
							jQuery( document.getElementById( 'redux_ajax_overlay' ) ).fadeOut( 'fast' );
							jQuery( '.redux-action_bar .spinner' ).fadeOut( 'fast' );
							return;
						}
						return flag;
					}
				} );

			}
		},
		initDayValidation: function ( $tr_parent ) {
			var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
			var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
			var flag = true;
			var allflag = true;

			if ( starting_val == - 1 && ending_val == - 1 ) {
				jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if ( starting_val == - 1 || ending_val == - 1 ) {
					if ( starting_val == - 1 ) {
						jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if ( parseInt( ending_val ) < parseInt( starting_val ) ) {
					jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).show().find( '.error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			if ( flag ) {
				jQuery( $tr_parent ).next( '.rthd-dayshift-error' ).hide();
				if ( jQuery( '#rthd_autoresponse_weekend' ).val() == 0 ) { // if Weekend only off then che check weektime enter or not
					for ( var i = 0; i < 7; i ++ ) {
						$tr_parent = jQuery( '.rthd-dayshift-time-start' ).eq( i ).parent().parent();
						var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
						var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
						if ( starting_val != - 1 || ending_val != - 1 ) {
							allflag = false;
						}
					}
					if ( allflag ) {
						jQuery( '#rthd-response-day-error' ).show().html( 'please select working time' );
						flag = false;
					}
				}
			} else {
				jQuery( '#rthd-response-day-error' ).hide().html( '' );
			}

			return flag;
		},
		initDayNightValidation: function ( $tr_parent ) {
			var starting_am_val = $tr_parent.find( '.rthd-daynight-am-time-start' ).val();
			var ending_am_val = $tr_parent.find( '.rthd-daynight-am-time-end' ).val();
			var starting_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-start' ).val();
			var ending_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-end' ).val();
			var flag = true;
			var allflag = true;

			if ( starting_am_val == - 1 && ending_am_val == - 1 ) {
				jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if ( starting_am_val == - 1 || ending_am_val == - 1 ) {
					if ( starting_am_val == - 1 ) {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if ( parseInt( ending_am_val ) < parseInt( starting_am_val ) ) {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.am-time-error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			if ( starting_pm_val == - 1 && ending_pm_val == - 1 ) {
				jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).removeClass( 'myerror' ).html( '' );
			} else {
				if ( starting_pm_val == - 1 || ending_pm_val == - 1 ) {
					if ( starting_pm_val == - 1 ) {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Please select `Start` time' );
					} else {
						jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Please select `End` time' );
					}
					flag = false;
				} else if ( parseInt( ending_pm_val ) < parseInt( starting_pm_val ) ) {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).addClass( 'myerror' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else {
					jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).show().find( '.pm-time-error' ).removeClass( 'myerror' ).html( '' );
				}
			}

			if ( flag ) {
				jQuery( $tr_parent ).next( '.rthd-daynightshift-error' ).hide();
				if ( jQuery( '#rthd_autoresponse_weekend' ).val() == 0 ) { // if Weekend only off then che check weektime enter or not
					for ( var i = 0; i < 7; i ++ ) {
						$tr_parent = jQuery( '.rthd-daynight-am-time-start' ).eq( i ).parent().parent();
						var starting_am_val = $tr_parent.find( '.rthd-daynight-am-time-start' ).val();
						var ending_am_val = $tr_parent.find( '.rthd-daynight-am-time-end' ).val();
						var starting_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-start' ).val();
						var ending_pm_val = $tr_parent.find( '.rthd-daynight-pm-time-end' ).val();
						if ( starting_am_val != - 1 || ending_am_val != - 1 || starting_pm_val != - 1 || ending_pm_val != - 1 ) {
							allflag = false;
						}
					}
					if ( allflag ) {
						jQuery( '#rthd-response-daynight-error' ).show().html( 'please select working time' );
						flag = false;
					} else {

					}
				}
			} else {
				jQuery( '#rthd-response-daynight-error' ).hide().html( '' );
			}

			return flag;
		},
		initBlacklistConfirmationOrRemove: function () {
			jQuery( document ).on( 'click', '#rthd_ticket_contacts_blacklist', function ( e ) {
				e.preventDefault();
				var action = jQuery( this ).data( 'action' );
				var requestArray = new Object();
				requestArray['post_id'] = jQuery( '#post-id' ).val();

				if ( action == 'remove_blacklisted' ) {
					requestArray['action'] = 'rthd_remove_blacklisted_contact';
					jQuery.ajax( {
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: requestArray,
						success: function ( data ) {
							if ( data.status ) {
								jQuery( '#contacts-blacklist-container' ).html( '' ).hide();
								jQuery( '#contacts-blacklist-action' ).html( data.addBlacklist_ui ).show();
							}
						},
						error: function ( xhr, textStatus, errorThrown ) {
							jQuery( '#contacts-blacklist-container' ).html( "Some error with ajax request!!" ).show();
						}
					} );
				} else if ( action == 'blacklisted_confirmation' ) {
					requestArray['action'] = 'rthd_show_blacklisted_confirmation';
					jQuery.ajax( {
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: requestArray,
						success: function ( data ) {
							if ( data.status ) {
								jQuery( '#contacts-blacklist-container' ).html( data.confirmation_ui ).show();
								jQuery( '#contacts-blacklist-action' ).hide();
							}
						},
						error: function ( xhr, textStatus, errorThrown ) {
							jQuery( '#contacts-blacklist-container' ).html( "Some error with ajax request!!" ).show();
						}
					} );
				}
			} );
		},
		initAddContactBlacklist: function () {
			jQuery( document ).on( 'click', '#rthd_ticket_contacts_blacklist_yes', function ( e ) {
				e.preventDefault();
				var action = jQuery( this ).data( 'action' );
				var requestArray = new Object();
				requestArray['post_id'] = jQuery( '#post-id' ).val();
				if ( action == 'blacklisted_contact' ) {
					requestArray['action'] = 'rthd_add_blacklisted_contact';
				}

				jQuery.ajax( {
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: requestArray,
					success: function ( data ) {
						if ( data.status ) {
							jQuery( '.confirmation-container' ).hide();
							jQuery( '#contacts-blacklist-action' ).html( data.remove_ui ).show();
						}
					},
					error: function ( xhr, textStatus, errorThrown ) {
						jQuery( '#contacts-blacklist-container' ).html( "Some error with ajax request!!" ).show();
					}
				} );

			} );
			jQuery( document ).on( 'click', '#rthd_ticket_contacts_blacklist_no', function ( e ) {
				e.preventDefault();
				jQuery( '#contacts-blacklist-container' ).html( '' ).hide();
				jQuery( '#contacts-blacklist-action' ).show();
			} );
		}
	};
	rthdAdmin.init();
} );
