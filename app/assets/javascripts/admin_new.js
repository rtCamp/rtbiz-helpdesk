/**
 * Created by sai on 6/9/14.
 */
jQuery(function () {
    var file_frame_ticket;
    var rthdAdmin = {

	    rthd_tinymce_set_content: function( id, text ) {
			if( typeof tinymce != "undefined" ) {
				var editor = tinymce.get( id );
				if( editor && editor instanceof tinymce.Editor ) {
					editor.setContent( text );
					editor.save( { no_events: true } );
				} else {
					jQuery( '#'+id ).val( text );
				}
				return true;
			}
			return false;
		},

		rthd_tinymce_get_content: function( id ) {
			if( typeof tinymce != "undefined" ) {
				var editor = tinymce.get( id );
				if( editor && editor instanceof tinymce.Editor ) {
					return editor.getContent();
				} else {
					return jQuery( '#'+id ).val();
				}
			}
			return '';
		},

        init: function () {
            rthdAdmin.initToolTop();
            rthdAdmin.initDatePicket();
            rthdAdmin.initDateTimePicker();
            rthdAdmin.initMomentJS();
            rthdAdmin.initattchmentJS();
            rthdAdmin.initExternalFileJS();
            rthdAdmin.initSubscriberSearch();
	        rthdAdmin.initAddNewFollowUp();
	        rthdAdmin.initEditFollowUp();
	        //rthdAdmin.initLoadMore();
	        rthdAdmin.initLoadAll();
	        rthdAdmin.initEditContent();
	        rthdAdmin.initAutoResponseSettings();

            rthdAdmin.initBlacklistConfirmationOrRemove();
            rthdAdmin.initAddContactBlacklist();
        },

	    initEditContent: function(){
		    jQuery('.edit-ticket-link' ).click(function(e){
			    e.preventDefault();
			    jQuery('#edit-ticket-data' ).slideToggle('slow');
			    if ( jQuery('#dialog-form').is(":visible")){
				    jQuery('#dialog-form' ).slideToggle('slow');
			    }
			    if ( ! jQuery('#edit-ticket-data').is(":visible")){
				    jQuery('#edit-ticket-data' ).slideToggle('slow');
			    }
			    jQuery('#new-followup-form' ).hide();
			    jQuery(document).scrollTop( ( jQuery('#edit-ticket-data').offset().top ) - 50 );
			    rthdAdmin.rthd_tinymce_set_content( 'editedticketcontent', jQuery(this).closest('.ticketcontent').find('.rthd-comment-content' ).data('content') );
		    });
		    jQuery('.close-edit-content' ).click(function(e){
			    e.preventDefault();
			    jQuery('#edit-ticket-data' ).slideToggle('slow');
			    jQuery('#new-followup-form' ).show();
			    jQuery(document).scrollTop( ( jQuery('.ticketcontent').offset().top ) - 50 );
		    });

		    jQuery('#edit-ticket-content-click' ).click(function(){
			    jQuery('#edit-ticket-data' ).slideToggle('slow');
			    jQuery('#new-followup-form' ).hide();
			    var requestArray = new Object();
			    requestArray['action']= 'rthd_add_new_ticket_ajax';
			    requestArray['post_id']= jQuery('#post-id' ).val();
			    var content = rthdAdmin.rthd_tinymce_get_content( 'editedticketcontent' );
			    requestArray['body']= content;
			    requestArray['nonce']= jQuery('#edit_ticket_nonce' ).val();
			    jQuery('#ticket-edithdspinner' ).show();
			    jQuery(this).attr('disabled','disabled');
			    jQuery.ajax( {
				                 url: ajaxurl,
				                 type: 'POST',
				                 dataType: 'json',
				                 data: requestArray,
				                 success: function ( data ) {
					                 if(data.status){
						                 jQuery('#ticket-edithdspinner' ).hide();
						                 jQuery("#edit-ticket-content-click" ).removeAttr('disabled');
						                 jQuery('.edit-ticket-link' ).closest('.ticketcontent' ).find('.rthd-comment-content' ).html( rthdAdmin.rthd_tinymce_get_content( 'editedticketcontent' ) );
						                 jQuery('#edit-ticket-data' ).hide();
						                 jQuery('#new-followup-form' ).slideToggle('slow');
						                 jQuery(document).scrollTop( ( jQuery('.ticketcontent').offset().top ) - 50 );
					                 }
					                 else{
						                 console.log(data.msg);
					                 }
				                 },
				                 error: function ( xhr, textStatus, errorThrown ) {
					                 alert( "Error" );
					                 jQuery('#ticket-edithdspinner' ).hide();
					                 jQuery("#edit-ticket-content-click" ).removeAttr('disabled');
				                 }
			                 });
		    });
	    },
	    initLoadAll: function(){
		    jQuery('#followup-load-more, .load-more-block' ).click(function (e){
			    e.preventDefault();
			    var requestArray = new Object();
			    var totalcomment=parseInt( jQuery('#followup-totalcomment' ).val(),10);
			    var limit = parseInt(jQuery('#followup-limit').val(),10);
			    if( limit != 3 ){
				    return;
			    }
			    jQuery(this ).parent().hide();
			    jQuery('#load-more-hdspinner' ).show();
			    requestArray['limit']=totalcomment-3;
			    requestArray['offset']=0;
			    requestArray["action"] = "load_more_followup";
			    requestArray['post_id'] =  jQuery('#post-id' ).val();
			    //requestArray['all'] =  'true';
			    jQuery.ajax( {
				                 url: ajaxurl,
				                 dataType: "json",
				                 type: 'post',
				                 data: requestArray,
				                 success: function ( data ) {
					                 if (data.status) {
						                 jQuery( '#followup-offset' ).val( data.offset );
						                 jQuery( '#chat-UI' ).prepend( data.comments );
					                 }
					                 jQuery('#load-more-hdspinner' ).hide();
				                 },
				                 error: function(){
					                 jQuery('#load-more-hdspinner' ).hide();
					                 return false;
				                 }
			                 });

		    });
	    },
/*	    initLoadMore: function(){

		    (function($) {
			    "use strict";

			    *//**
			     * Quick implementation of infinite scroll
			     *//*
			    var $stream = $('.js-stream');
			    $(window).load(function (e) {
				    if ( ! $stream.length ) {
					    return;
				    }
				    var isLoading = false;
				    var $showMore = $('#followup-load-more');
				    var $placeHolder = $('.js-loading-placeholder');

				    $showMore.show();

				    var scrollHandler = function (e) {
					    if ( isLoading ) {
						    return;
					    }

					    var $loadingPlaceholder = $( '.js-loading-placeholder' );
					    var scrollTopTrigger = $loadingPlaceholder.length === 0 ? null : $loadingPlaceholder.offset().top;
					    if ( $(window).scrollTop() < scrollTopTrigger - $(window).height() ) {
						    return;
					    }
					    if ( !scrollTopTrigger ) {
						    return;
					    }

					    var requestArray = new Object();
					    requestArray['offset'] = parseInt(jQuery('#followup-offset').val(),10);
					    requestArray['limit'] = parseInt(jQuery('#followup-limit').val(),10);
					    requestArray["action"] = "load_more_followup";
					    requestArray['post_id'] =  jQuery('#post-id' ).val();

					    var totalcomment=parseInt( jQuery('#followup-totalcomment' ).val(),10);
					    if ( requestArray['offset'] == 0 && requestArray['limit'] >= totalcomment ) {
						    $loadingPlaceholder.remove();
						    $(window).off('scroll', scrollHandler);
						    return;
					    } else if ( ( requestArray['offset']) >= totalcomment ) {
						    $loadingPlaceholder.remove();
						    $(window).off('scroll', scrollHandler);
						    return;
					    }

					    isLoading = true;
					    jQuery.ajax( {
							url: ajaxurl,
							dataType: "json",
							type: 'post',
							data: requestArray,
							success: function ( data ) {
								if (data.status){
									jQuery('#followup-offset' ).val(data.offset);
									jQuery('#chat-UI' ).append(data.comments);
									$loadingPlaceholder.replaceWith( data.placeholder );

									if ( data.placeholder == '' ) {
										$loadingPlaceholder.remove();
										$(window).off('scroll', scrollHandler);
									}

								} else {
									$loadingPlaceholder.remove();
									$(window).off('scroll', scrollHandler);
								}
							},
							error: function(){
								$loadingPlaceholder.remove();
							$(window).off('scroll', scrollHandler);
								return false;
							},
							complete: function () {
								isLoading = false;
							}
						});

				    };
				    scrollHandler = $.throttle( 500, scrollHandler );

				    // Activate infinite scroll manually. Allows footer to be reached by default.
				    $showMore.on('click' ,function(e) {
					    e.preventDefault();
					    $(window).on('scroll', scrollHandler).trigger('scroll');
					    $(this).animate({'opacity' : 0}, 500, function() {
						    $(this ).replaceWith($placeHolder.html());
					    });
				    });
			    });
		    })(jQuery);
	    },*/
	    initEditFollowUp: function () {
			var commentid;
		    jQuery("#delfollowup" ).click(function() {
			    var r = confirm( "Are you sure you want to remove this Followup?" );
			    if ( r != true ) {
				    e.preventDefault();
				    return false;
			    }
			    jQuery('#edithdspinner' ).show();
			    jQuery(this).attr('disabled','disabled');
			    postid= jQuery('#post-id' ).val();
			    jQuery.ajax( { url: ajaxurl,
				                 type: 'POST',
				                 dataType: 'json',
				                 data: {
					                 action: 'helpdesk_delete_followup',
					                 comment_id: commentid,
					                 post_id : postid
				                 },
				                 success: function ( data ) {
					                 if ( data.status ) {
						                 jQuery( "#comment-" + commentid ).fadeOut( 500, function () {
							                 jQuery( this ).remove();
						                 } );
						                 jQuery( '.close-edit-followup' ).trigger( 'click' );
					                 } else {
						                 alert( "Error while deleting comment from server" );
					                 }
					                 jQuery('#edithdspinner' ).hide();
					                 jQuery("#delfollowup" ).removeAttr('disabled');
				                 },
				                 error: function ( xhr, textStatus, errorThrown ) {
					                 alert( "error while removing follow up." );
					                 jQuery('#edithdspinner').hide();
					                 jQuery("#delfollowup" ).removeAttr('disabled');
				                 }
		    } );

	    	});
		    jQuery(document).on('click', '.close-edit-followup', function (e){
			    e.preventDefault();
			    jQuery('#dialog-form' ).slideToggle('slow');
			    jQuery('#new-followup-form' ).show();
			    jQuery(document).scrollTop( ( jQuery('#comment-'+commentid ).offset().top ) );
		    });
		    jQuery( document ).on('click', '.editfollowuplink',function(e){
			    e.preventDefault();
			    var select =jQuery(this ).parents();
			    rthdAdmin.rthd_tinymce_set_content( 'editedfollowupcontent', jQuery(this).parents().siblings('.rthd-comment-content').data('content'));
			    commentid=select.siblings('#followup-id' ).val();
			    var that = select.siblings( '#is-private-comment' ).val();
                 jQuery('#edit-private' ).val(that);
			    jQuery('#new-followup-form' ).hide();
			    if ( ! jQuery('#dialog-form').is(":visible")){
				    jQuery('#dialog-form' ).slideToggle('slow');
			    }
			    if ( jQuery('#edit-ticket-data').is(":visible")){
				    jQuery('#edit-ticket-data' ).slideToggle('slow');
			    }
			    jQuery(document).scrollTop( ( jQuery('#dialog-form').offset().top ) - 50 );

		    } );

		    jQuery("#editfollowup" ).click(function(){
			    var requestArray = new Object();
			    var content =  rthdAdmin.rthd_tinymce_get_content( 'editedfollowupcontent' );
			    if (! content){
				    alert("Please enter comment");
				    return false;
			    }
			    if (content.replace(/\s+/g, " ") === jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).data('content') ){
				    alert('You have not edited comment!');
				    return false;
			    }
			    jQuery('#edithdspinner' ).show();
			    jQuery(this).attr('disabled','disabled');
			    requestArray['post_type'] = rthd_post_type;
			    requestArray["comment_id"] = commentid ;
			    requestArray["action"] = "rthd_update_followup_ajax";
			    requestArray['followuptype']="comment";
			    requestArray['followup_ticket_unique_id']=jQuery('#ticket_unique_id' ).val();
			    //requestArray['followup_private']='no';
			    requestArray['followup_post_id']=jQuery('#post-id' ).val();
			    requestArray['followup_private']= jQuery('#edit-private').val();
			    requestArray["followuptype"] = 'comment';
			    //requestArray["followup_post_id"] = jQuery( "#ticket_id" ).val();
			    //requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();
			    requestArray["followup_content"] = content;
			    jQuery.ajax(
				    {
					    url: ajaxurl,
					    dataType: "json",
					    type: 'post',
					    data: requestArray,
					    success: function ( data ) {
						    if ( data.status ) {
							    jQuery('#comment-'+commentid ).replaceWith(data.comment_content);
							    jQuery( '.close-edit-followup' ).trigger( 'click' );
							    jQuery(document).scrollTop( ( jQuery('#comment-'+commentid ).offset().top ) - 50 );
						    } else {
							    alert( data.message );
						    }
						    jQuery('#edithdspinner' ).hide();
						    jQuery("#editfollowup" ).removeAttr('disabled');

					    },
					    error: function (data){
						    alert("Sorry :( something went wrong!");
						    jQuery('#edithdspinner' ).hide();
						    jQuery("#editfollowup" ).removeAttr('disabled');
					    }
				    } );
		    });
	    },
        initAddNewFollowUp : function(){

	        jQuery( "#savefollwoup" ).click( function () {
		        var flagspinner = false;
		        jQuery('#hdspinner' ).show();
		        jQuery(this).attr('disabled','disabled');
		        var followuptype = jQuery( "#followup-type" ).val();

		        if ( ! jQuery( "#ticket_unique_id" ).val()) {
			        alert('Please publish ticket before adding followup! :( ');
			        jQuery( '#hdspinner' ).hide();
			        jQuery(this).removeAttr('disabled');
			        return false;
		        };
		        if ( ! rthdAdmin.rthd_tinymce_get_content( 'followupcontent' ) ) {
			        alert( "Please input followup." );
			        jQuery( '#hdspinner' ).hide();
			        jQuery(this).removeAttr('disabled');
			        return false;
		        }
		        var formData = new FormData();
		        formData.append("private_comment", jQuery('#add-private-comment').val());
		        formData.append("followup_ticket_unique_id", jQuery('#ticket_unique_id').val());
		        formData.append("post_type", jQuery('#followup_post_type').val());
		        formData.append("action", 'rthd_add_new_followup_front');
		        formData.append("followuptype", jQuery('#followuptype').val());
		        formData.append("follwoup-time", jQuery('#follwoup-time').val());
		        formData.append("followup_content", rthdAdmin.rthd_tinymce_get_content( 'followupcontent' ) );
		        var files = jQuery('#attachemntlist')[0];
		        jQuery.each(jQuery("#attachemntlist")[0].files, function(i, file) {
			        formData.append('attachemntlist['+i+']', file);
		        });
		        if(jQuery('#rthd_keep_status')){
			        formData.append("rthd_keep_status", jQuery('#rthd_keep_status').is(':checked'));
		        }
		        jQuery.ajax( {
			                     url: ajaxurl,
			                     dataType: "json",
			                     type: 'POST',
			                     data: formData,
			                     cache: false,
			                     contentType: false,
			                     processData: false,
			                     //async: false, // no more page freezing
			                     success: function ( data ) {
				                     if ( data.status ) {
					                     var newcomment=data.comment_content;
					                     //console.log(newcomment);
					                     jQuery('#chat-UI' ).append(newcomment);
					                     rthdAdmin.rthd_tinymce_set_content( 'followupcontent', '' );
					                     jQuery('#add-private-comment' ).val(10);
					                     jQuery("#attachemntlist").val('');
					                     jQuery("#clear-attachemntlist").hide();
                                         if (data.ticket_status=='answered'){
                                             if(jQuery('#rthd_keep_status')){
                                                 jQuery('#rthd_keep_status').parent().hide();
                                             }
                                         } else {
	                                         if ( jQuery('#rthd_keep_status' ).length > 0 ) {
		                                         jQuery('#rthd_keep_status' ).prop("checked",false);
	                                         }
                                         }
				                     } else {
					                     alert( data.message );
				                     }
				                     if (! flagspinner) {
					                     jQuery( '#hdspinner' ).hide();
				                     }
				                     jQuery('#savefollwoup').removeAttr('disabled');
			                     }
		                     } );
	        } );
        },
        initToolTop: function () {
            jQuery(".tips, .help_tip").tipTip({
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        },
        initDatePicket: function () {
            if (jQuery(".datepicker").length > 0) {
                jQuery(".datepicker").datepicker({
                    'dateFormat': 'M d,yy',
                    onClose: function (newDate, inst) {

                        if (jQuery(this).hasClass("moment-from-now")) {
                            var oldDate = jQuery(this).attr("title");

                            if (newDate != "" && moment(newDate).isValid()) {
                                jQuery(this).val(moment(new Date(newDate)).fromNow());
                                jQuery(this).attr("title", newDate);

                                if (jQuery(this).next().length > 0) {
                                    jQuery(this).next().val(newDate);
                                }
                            } else if (oldDate != "") {
                                jQuery(this).val(moment(new Date(oldDate)).fromNow());
                                jQuery(this).attr("title", oldDate);

                                if (jQuery(this).next().length > 0) {
                                    jQuery(this).next().val(newDate);
                                }
                            }
                        }
                    }
                });
            }
            jQuery(".datepicker-toggle").click(function (e) {
                jQuery("#" + jQuery(this).data("datepicker")).datepicker("show");
            })
        },
        initDateTimePicker: function () {
            if (jQuery(".datetimepicker").length > 0) {
                jQuery(".datetimepicker").datetimepicker({
                    dateFormat: "M d, yy",
                    timeFormat: "hh:mm TT",
                    onClose: function (newDate, inst) {

                        var oldDate = jQuery(this).attr("title");

                        if (newDate != "" && moment(newDate).isValid()) {
                            jQuery(this).val(moment(new Date(newDate)).fromNow());
                            jQuery(this).attr("title", newDate);

                            if (jQuery(this).next().length > 0) {
                                if (jQuery(this).hasClass("moment-from-now")) {
                                    jQuery(this).next().val(newDate);
                                }
                            } else if (oldDate != "") {
                                jQuery(this).val(moment(new Date(oldDate)).fromNow());
                                jQuery(this).attr("title", oldDate);

                                if (jQuery(this).next().length > 0) {
                                    jQuery(this).next().val(newDate);
                                }
                            }
                        }
                    }
                });
            }
        },
        initMomentJS: function () {
            jQuery(document).on("click", ".moment-from-now", function (e) {
                var oldDate = jQuery(this).attr("title");

                if (oldDate != "") {
                    jQuery(this).datepicker("setDate", new Date(jQuery(this).attr("title")));
                }
            });

            jQuery(".moment-from-now").each(function () {

                if (jQuery(this).is("input[type='text']") && jQuery(this).val() != "") {
                    jQuery(this).val(moment(new Date(jQuery(this).attr("title"))).fromNow());
                } else if (jQuery(this).is(".comment-date")) {
                    jQuery(this).html(moment(new Date(jQuery(this).attr("title"))).fromNow());
                } else {
                    jQuery(this).html(moment(new Date(jQuery(this).html())).fromNow());
                }
            });
        },
        initattchmentJS: function () {
            jQuery(document).on('click', '.rthd_delete_attachment', function (e) {
                e.preventDefault();
                jQuery(this).parent().remove();
            });

            jQuery('#add_ticket_attachment').on('click', function (e) {
                e.preventDefault();
                if (file_frame_ticket) {
                    file_frame_ticket.open();
                    return;
                }
                file_frame_ticket = wp.media.frames.file_frame = wp.media({
                    title: jQuery(this).data('uploader_title'),
                    searchable: true,
                    button: {
                        text: 'Attach Selected Files'
                    },
                    multiple: true // Set to true to allow multiple files to be selected
                });
                file_frame_ticket.on('select', function () {
                    var selection = file_frame_ticket.state().get('selection');
                    var strAttachment = '';
                    selection.map(function (attachment) {
                        attachment = attachment.toJSON();
                        strAttachment = '<li data-attachment-id="' + attachment.id + '" class="attachment-item row_group">';
                        strAttachment += '<a href="#" class="delete_row rthd_delete_attachment">x</a>';
                        strAttachment += '<a target="_blank" href="' + attachment.url + '"><img height="20px" width="20px" src="' + attachment.icon + '" > ' + attachment.filename + '</a>';
                        strAttachment += '<input type="hidden" name="attachment[]" value="' + attachment.id + '" /></div>';

                        jQuery("#attachment-container .scroll-height").append(strAttachment);

                        // Do something with attachment.id and/or attachment.url here
                    });
                    // Do something with attachment.id and/or attachment.url here
                });
                file_frame_ticket.open();
            });
        },
        initExternalFileJS: function () {
            var exf_count = 12345;
            jQuery("#add_new_ex_file").click(function (e) {
                var title = jQuery("#add_ex_file_title").val();
                var link = jQuery("#add_ex_file_link").val();
                if (jQuery.trim(link) == "") {
                    return false;
                }
                jQuery("#add_ex_file_title").val("");
                jQuery("#add_ex_file_link").val("");

                var tmpstr = '<div class="row_group">';
                tmpstr += '<button class="delete_row removeMeta"><i class="foundicon-minus"></i>X</button>';
                tmpstr += '<input type="text" name="ticket_ex_files[' + exf_count + '][title]" value="' + title + '" />';
                tmpstr += '<input type="text" name="ticket_ex_files[' + exf_count + '][link]" value="' + link + '" />';
                tmpstr += '</div>';
                exf_count++;
                jQuery("#external-files-container").append(tmpstr);
            });

        },
        initSubscriberSearch: function () {
            try {
                if (arr_subscriber_user != undefined) {
                    jQuery("#subscriber_user_ac").autocomplete({
                        source: function (request, response) {
                            var term = jQuery.ui.autocomplete.escapeRegex(request.term), startsWithMatcher = new RegExp("^" + term, "i"), startsWith = jQuery.grep(arr_subscriber_user, function (value) {
                                return startsWithMatcher.test(value.label || value.value || value);
                            }), containsMatcher = new RegExp(term, "i"), contains = jQuery.grep(arr_subscriber_user, function (value) {
                                return jQuery.inArray(value, startsWith) < 0 && containsMatcher.test(value.label || value.value || value);
                            });

                            response(startsWith.concat(contains));
                        },
                        focus: function (event, ui) {

                        },
                        select: function (event, ui) {
                            if (jQuery("#subscribe-auth-" + ui.item.id).length < 1) {
                                jQuery("#divSubscriberList").append("<li id='subscribe-auth-" + ui.item.id + "' class='contact-list' >" + ui.item.imghtml + "<a href='#removeSubscriber' class='delete_row'>×</a><br/><a class='subscribe-title heading' target='_blank' href='" + ui.item.user_edit_link + "'>" + ui.item.label + "</a><input type='hidden' name='subscribe_to[]' value='" + ui.item.id + "' /></li>")
                            }
                            jQuery("#subscriber_user_ac").val("");
                            return false;
                        }
                    }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        return jQuery("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
                    };

                    jQuery(document).on('click', "a[href=#removeSubscriber]", function (e) {
                        e.preventDefault();
                        jQuery(this).parent().remove();
                    });

                }
            } catch (e) {

            }
        },
		initAutoResponseSettings: function () {

			if ( jQuery('#rthd_enable_auto_response_mode') ) {

				//day shift
				jQuery( '.rthd-dayshift-time-end' ).change( function() {
					rthdAdmin.initDayValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '.rthd-dayshift-time-start' ).change( function() {
					rthdAdmin.initDayValidation( jQuery( this ).parent().parent() );
				});
				jQuery( '#redux-form-wrapper' ).submit( function() {
					var flag = true;
					if ( jQuery('#rthd_enable_auto_response_mode').val() == 1  ) {
						for( var i = 0; i < 7; i++ ) {
							if (!rthdAdmin.initDayNightValidation(jQuery('.rthd-dayshift-time-start').eq(i).parent().parent())) {
								flag = false;
							}
						}
					}
					return flag;
				});

				//day/night shift
				jQuery('.rthd-daynigt-am-time-start').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynigt-am-time-end').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynigt-pm-time-start').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynigt-pm-time-end').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('#redux-form-wrapper').submit(function () {
					var flag = true;
					if ( jQuery('#rthd_enable_auto_response_mode').val() == 0  ) {
						for (var i = 0; i < 7; i++) {
							if (!rthdAdmin.initDayNightValidation(jQuery('.rthd-daynigt-am-time-start').eq(i).parent().parent())) {
								flag = false;
							}

							if (!rthdAdmin.initDayNightValidation(jQuery('.rthd-daynigt-pm-time-start').eq(i).parent().parent())) {
								flag = false;
							}
						}
					}
					return flag;
				});
			}
		},
		initDayValidation: function ( $tr_parent ) {
			var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
			var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
			var flag = true;

			if ( starting_val == -1 && ending_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).html( '' );
			} else {
				if ( starting_am_val == -1 || ending_am_val == -1 ){
					jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).html( 'Please select `Starting` or `Ending` For AM' );
					flag = false;
				} else if( parseInt( ending_am_val ) < parseInt( starting_am_val ) ){
					jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).html( 'Starting Time should be less then ending time' );
					flag = false;
				} else{
					jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).html( '' );
				}
			}
			return flag;
		},
		initDayNightValidation: function ( $tr_parent ) {
			var starting_am_val = $tr_parent.find('.rthd-daynigt-am-time-start').val();
			var ending_am_val = $tr_parent.find('.rthd-daynigt-am-time-end').val();
			var starting_pm_val = $tr_parent.find('.rthd-daynigt-pm-time-start').val();
			var ending_pm_val = $tr_parent.find('.rthd-daynigt-pm-time-end').val();
			var flag = true;
			if ( starting_am_val == -1 && ending_am_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').html('');
			} else {
				if ( starting_am_val == -1 || ending_am_val == -1 ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').html('Please select `Starting` or `Ending` For AM');
					flag = false;
				} else if( parseInt( ending_am_val ) < parseInt( starting_am_val ) ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').html('Starting Time should be less then ending time');
					flag = false;
				}else {
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').html('');
				}
			}

			if ( starting_pm_val == -1 && ending_pm_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').html('');
			}else{
				if ( starting_pm_val == -1 || ending_pm_val == -1 ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').html('Please select `Starting` or `Ending` For PM');
					flag = false;
				}else if( parseInt( ending_pm_val ) < parseInt( starting_pm_val )  ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').html('Starting Time should be less then ending time');
					flag = false;
				}else{
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').html('');
				}
			}
			return flag;
		},
        initBlacklistConfirmationOrRemove: function(){
            jQuery( document ).on('click', '#rthd_ticket_contacts_blacklist', function (e) {
                e.preventDefault();
                var action = jQuery(this).data('action');
                var requestArray = new Object();
                requestArray['post_id']= jQuery('#post-id' ).val();

                if ( action == 'remove_blacklisted' ){
                    requestArray['action']= 'rthd_remove_blacklisted_contact';
                    jQuery.ajax( {
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: requestArray,
                        success: function ( data ) {
                            if(data.status) {
                                jQuery('#contacts-blacklist-container').html('').hide();
                                jQuery('#contacts-blacklist-action').html( data.addBlacklist_ui).show();
                            }
                        },
                        error: function ( xhr, textStatus, errorThrown ) {
                            jQuery('#contacts-blacklist-container').html( "Some error with ajax request!!" ).show();
                        }
                    });
                }else if( action == 'blacklisted_confirmation' ){
                    requestArray['action']= 'rthd_show_blacklisted_confirmation';
                    jQuery.ajax( {
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: requestArray,
                        success: function ( data ) {
                            if(data.status) {
                                jQuery('#contacts-blacklist-container').html( data.confirmation_ui).show();
                                jQuery('#contacts-blacklist-action').hide();
                            }
                        },
                        error: function ( xhr, textStatus, errorThrown ) {
                            jQuery('#contacts-blacklist-container').html( "Some error with ajax request!!" ).show();
                        }
                    });
                }
            });
        },
        initAddContactBlacklist: function(){
            jQuery( document ).on('click', '#rthd_ticket_contacts_blacklist_yes', function (e) {
                e.preventDefault();
                var action = jQuery(this).data('action');
                var requestArray = new Object();
                requestArray['post_id']= jQuery('#post-id' ).val();
                if ( action == 'blacklisted_contact' ){
                    requestArray['action']= 'rthd_add_blacklisted_contact';
                }

                jQuery.ajax( {
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: requestArray,
                    success: function ( data ) {
                        if(data.status) {
                            jQuery('.confirmation-container').hide();
                            jQuery('#contacts-blacklist-action').html( data.remove_ui).show();
                        }
                    },
                    error: function ( xhr, textStatus, errorThrown ) {
                        jQuery('#contacts-blacklist-container').html( "Some error with ajax request!!" ).show();
                    }
                });

            });
            jQuery( document ).on('click','#rthd_ticket_contacts_blacklist_no', function (e) {
                e.preventDefault();
                jQuery('#contacts-blacklist-container').html('').hide();
                jQuery('#contacts-blacklist-action').show();
            });
        }
    }
    rthdAdmin.init();
});
