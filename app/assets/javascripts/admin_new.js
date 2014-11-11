/**
 * Created by sai on 6/9/14.
 */
jQuery(function () {
    var file_frame_ticket;
    var rthdAdmin = {

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
	        rthdAdmin.initLoadMore();
        },
	    initLoadMore: function(){

		    (function($) {
			    "use strict";
			    var isLoading= false;
			    /**
			     * Quick implementation of infinite scroll
			     */
			    var $stream = $('.js-stream');
			    $(window).load(function (e) {
				    if ( ! $stream.length ) {
					    return;
				    }
				    //var isLoading = false;
				    //var $showMore = $('<a class="activate-infinite-scroll" href="#">Show more posts</a>');
				    var $showMore = $('#followup-load-more');
				    var loadingLabel = $('.js-loading-placeholder').text();
				    var $placeHolder = $('.js-loading-placeholder');
				    //$placeHolder.addClass('is-inactive').html($showMore);
				    console.log('check');

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
					    isLoading = true;

					    var requestArray = new Object();
					    requestArray['offset'] = parseInt(jQuery('#followup-offset' ).val(),10);
					    requestArray['limit'] = parseInt(jQuery('#followup-limit' ).val(),10);
					    requestArray["action"] = "load_more_followup";
					    requestArray['post_id'] =  jQuery('#post-id' ).val();
					    //jQuery(this ).hide();
					    //jQuery('#load-more-hdspinner' ).show();

					    var totalcomment=parseInt( jQuery('#followup-totalcomment' ).val(),10);
					    if (requestArray['offset'] == 0){
						    if(requestArray['limit'] >= totalcomment){
							    console.log(requestArray['limit'] + ' off limit 0 ' + totalcomment);
							    return false;
						    }
					    }
					    else{
						    //if( ( requestArray['offset'] + requestArray['limit']) >= totalcomment ){
						    if( ( requestArray['offset']) >= totalcomment ){
							    console.log(requestArray['offset'] + ' off limit' + totalcomment);
							    jQuery('#followup-load-more' ).hide();
							    return false;
						    }
					    }
					    jQuery('#load-more-hdspinner' ).show();
					    jQuery.ajax( {
						                 url: ajaxurl,
						                 dataType: "json",
						                 type: 'post',
						                 data: requestArray,
						                 success: function ( data ) {
							                 if (data.status){
								                 jQuery('#followup-offset' ).val(data.offset);
								                 jQuery('#chat-UI' ).append(data.comments);
							                 }
							                 else{
								                 $(window).off('scroll', scrollHandler);
							                 }
							                 jQuery('#load-more-hdspinner' ).hide();
							                 //jQuery('#followup-load-more' ).show();
						                 },
						                 error: function(){
							                 //jQuery('#followup-load-more' ).show();
							                 //jQuery('#load-more-hdspinner' ).hide();
							                 $(window).off('scroll', scrollHandler);
							                 alert('Error, while loading more followup :(');
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
					    $(this).animate({'opacity' : 0}, 1, function() {
						    $(this).replaceWith(loadingLabel);
						    $placeHolder.removeClass('is-inactive');
					    });
				    });
			    });
		    })(jQuery);
	    },
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
					                 post_id : postid,
				                 },
				                 success: function ( data ) {
					                 if ( data.status ) {
						                 jQuery( "#comment-" + commentid ).fadeOut( 500, function () {
							                 jQuery( this ).remove();
						                 } );
						                 jQuery("#dialog-form").dialog().dialog("close");
					                 } else {
						                 alert( "error in delete comment from server" );
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
		    jQuery( document ).on('click', '.editfollowuplink',function(){
			    //jQuery( document ).on('click', 'li.editable .messages',function(){
		    //jQuery( 'li.self .messages' ).click( function () {
			 //   jQuery('#edited_followup_content' ).val( jQuery(this).find('p').text().replace(/\s+/g, " ") );
			    var select =jQuery(this ).parents();
			    jQuery('#edited_followup_content' ).val(jQuery(this ).parents().siblings('.rthd-comment-content' ).text().trim());
			    commentid=select.siblings('#followup-id' ).val();
			    var that = select.siblings( '#is-private-comment' ).val();
				 if (that && that=='true' || that == true){
					 jQuery('#edit-private' ).prop('checked',true);
				 }
				else{
					 jQuery('#edit-private' ).prop('checked',false);
				 }
			    jQuery("#dialog-form").dialog().dialog("close");
				jQuery( "#dialog-form" ).dialog({
					                              width :600,
					                              height:300
				                              });
			    jQuery( "#dialog-form" ).dialog( "open" );

		    } );

		    jQuery("#editfollowup" ).click(function(){
			    var requestArray = new Object();
			    if (! jQuery('#edited_followup_content' ).val()){
				    alert("Please enter comment");
				    return false;
			    }
			    if (jQuery('#edited_followup_content' ).val().replace(/\s+/g, " ") === jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).val().replace(/\s+/g, " ") ){
				    alert('You have not edited comment! :/');
				    return false;
			    }
			    jQuery('#edithdspinner' ).show();
			    jQuery(this).attr('disabled','disabled');
			    requestArray['post_type'] = rthd_post_type;
			    requestArray["comment_id"] = commentid ;
			    requestArray["action"] = "rthd_add_new_followup_ajax";
			    requestArray['followuptype']="comment";
			    requestArray['followup_ticket_unique_id']=jQuery('#ticket_unique_id' ).val();
			    //requestArray['followup_private']='no';
			    requestArray['followup_post_id']=jQuery('#post-id' ).val();
			    requestArray['followup_private']= jQuery('#edit-private').is(':checked') ;
			    requestArray["followuptype"] = 'comment';
			    //requestArray["followup_post_id"] = jQuery( "#ticket_id" ).val();
			    //requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();
			    requestArray["followup_content"]=jQuery('#edited_followup_content' ).val();
			    jQuery.ajax(
				    {
					    url: ajaxurl,
					    dataType: "json",
					    type: 'post',
					    data: requestArray,
					    success: function ( data ) {
						    if ( data.status ) {
							    jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).html(data.comment_content);
							    jQuery('#comment-'+commentid ).find( '#is-private-comment' ).val(data.private);
							    jQuery("#dialog-form").dialog().dialog("close");
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

	        jQuery(document).on( 'click', '.add-followup-attachment', function (e) {
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
				        strAttachment = '<li data-attachment-id="' + attachment.id + '" class="attachment-item">';
				        strAttachment += '<a href="#" class="rthd_delete_attachment">x</a>';
				        strAttachment += '<a target="_blank" href="' + attachment.url + '"><img height="20px" width="20px" src="' + attachment.icon + '" > ' + attachment.filename + '</a>';
				        strAttachment += '<input type="hidden" name="attachemntitem[]" value="' + attachment.id + '" /></div>';

				        jQuery("#attachmentList").append(strAttachment);

				        // Do something with attachment.id and/or attachment.url here
			        });
			        // Do something with attachment.id and/or attachment.url here
		        });
		        file_frame_ticket.open();
	        });

            jQuery( "#savefollwoup" ).click( function () {
	            var flagspinner = false;
	            jQuery('#hdspinner' ).show();
	            jQuery(this).attr('disabled','disabled');

	            var followuptype = jQuery( "#followup-type" ).val();
                var requestArray = new Object();
                requestArray['post_type'] = rthd_post_type;
                requestArray["comment_id"] = jQuery( "#edit-comment-id" ).val();
                requestArray["action"] = "rthd_add_new_followup_front";
                requestArray["followuptype"] = '';//'note';
	            requestArray['private_comment']= jQuery('#add-private-comment').is(':checked') ;
                requestArray["followup_ticket_unique_id"] = jQuery( "#ticket_unique_id" ).val();
	            if ( ! requestArray["followup_ticket_unique_id"]) {
		            alert('Please publish ticket before adding followup! :( ');
		            jQuery( '#hdspinner' ).hide();
		            jQuery(this).removeAttr('disabled');
		            return false;
	            };
                requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();

                requestArray["followup_content"] = jQuery( "#followup_content" ).val();
                if ( ! requestArray["followup_content"] ) {
                    alert( "Please input followup :/" );
	                jQuery( '#hdspinner' ).hide();
	                jQuery(this).removeAttr('disabled');
                    return false;
                }
                requestArray["attachemntlist"] = new Array();
                jQuery( "#attachmentList input" ).each( function () {
                    requestArray["attachemntlist"].push( jQuery( this ).val() );
                });


                //console.log(requestArray);
                requestArray['user_edit'] = rthd_user_edit;
                jQuery.ajax( {
                    url: ajaxurl,
                    dataType: "json",
                    type: 'post',
                    data: requestArray,
                    success: function ( data ) {
                        if ( data.status ) {
	                        var newcomment=data.comment_content;
	                        jQuery('#chat-UI' ).append(newcomment);
	                        jQuery( "#followup_content" ).val( '' );
	                        jQuery('#add-private-comment' ).prop('checked',false );
	                        jQuery('#attachmentList' ).html('');
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
                        text: 'Attach Selected Files',
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
        }
    }
    rthdAdmin.init();
});