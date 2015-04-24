/**
 * Created by sai on 6/9/14.
 */
jQuery(document).ready(function() {

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

	        $ticket_unique_id = jQuery( '#ticket_unique_id' ).val();
	        var uploadedfiles= [];
	        var force_add_duplicate = false;
	        if ( typeof plupload.Uploader === 'function' ) {
		        var uploader = new plupload.Uploader( {
			                                              // General settings
			                                              runtimes: 'html5,flash,silverlight,html4',
			                                              browse_button: 'attachemntlist', // you can pass in id...
			                                              url: ajaxurl,
			                                              multipart: true,
			                                              multipart_params: {
				                                              'action': 'rthd_upload_attachment',
				                                              'followup_ticket_unique_id': $ticket_unique_id
			                                              },
			                                              container: document.getElementById( 'rthd-attachment-container' ), // ... or DOM Element itself

			                                              // Resize images on client-side if we can
			                                              //resize : { width : 320, height : 240, quality : 90 },

			                                              filters: {
				                                              max_file_size: '10mb'

				                                              // Specify what files to browse for
				                                              //mime_types: [
				                                              //    {title : "Image files", extensions : "jpg,gif,png"},
				                                              //    {title : "Zip files", extensions : "zip"}
				                                              //]
			                                              },

			                                              flash_swf_url: 'Moxie.swf',
			                                              silverlight_xap_url: 'Moxie.xap',

			                                              // PreInit events, bound before the internal events

			                                              init: {
				                                              PostInit: function () {
					                                              document.getElementById( 'followup-filelist' ).innerHTML = '';
					                                              document.getElementById( 'savefollwoup' ).onclick = function () {
						                                              if ( followupValidate() ) {
							                                              //if ( uploader.files.length ) {
							                                              //    checkDuplicateFollowup();
							                                              //} else {
							                                              uploader.start();
							                                              //}
						                                              }
					                                              };
				                                              },

				                                              FilesAdded: function ( up, files ) {
					                                              plupload.each( files, function ( file ) {
						                                              document.getElementById( 'followup-filelist' ).innerHTML += '<div id="' + file.id + '"><a href="#" class="followup-attach-remove"> x </a> ' + file.name + ' (' + plupload.formatSize( file.size ) + ') <b></b></div>';
					                                              } );
				                                              },

				                                              FilesRemoved: function ( up, files ) {
					                                              plupload.each( files, function ( file ) {
						                                              jQuery( '#' + file.id ).remove();
					                                              } );
				                                              },

				                                              UploadProgress: function ( up, file ) {
					                                              document.getElementById( file.id ).getElementsByTagName( 'b' )[0].innerHTML = '<span>' + file.percent + "%</span>";
				                                              },

				                                              Error: function ( up, err ) {
					                                              document.getElementById( 'console' ).innerHTML += "\nError #" + err.code + ": " + err.message;
				                                              },

				                                              UploadComplete: function () {
					                                              document.getElementById( 'followup-filelist' ).innerHTML = '';
					                                              sendFollowup( force_add_duplicate );
					                                              force_add_duplicate = false;
					                                              uploadedfiles = [];
				                                              },

				                                              FileUploaded: function ( up, file, info ) {
					                                              // Called when file has finished uploading
					                                              var response = jQuery.parseJSON( info.response );
					                                              if ( response.status ) {
						                                              uploadedfiles = uploadedfiles.concat( response.attach_ids );
					                                              }
				                                              }
			                                              }
		                                              } );
		        uploader.init();
	        }

	        jQuery(document).on('click','.followup-attach-remove', function( e ){
		        e.preventDefault();
		        uploader.removeFile(jQuery(this ).parent().attr("id"));
	        });

	        function followupValidate(){
		        jQuery( '#hdspinner' ).show();
		        //jQuery(this).attr('disabled','disabled');
		        if ( ! jQuery( "#ticket_unique_id" ).val() ) {
			        alert( 'Please publish ticket before adding followup! :( ' );
			        jQuery( '#hdspinner' ).hide();
			        //jQuery(this).removeAttr('disabled');
			        return false;
		        }
		        if ( ! rthdAdmin.rthd_tinymce_get_content( 'followupcontent' ) ) {
			        alert( "Please input followup." );
			        jQuery( '#hdspinner' ).hide();
			        //jQuery(this).removeAttr('disabled');
			        return false;
		        }
		        return true;
	        }

	        function sendFollowup( force ){
		        var followuptype = jQuery( "#followup-type" ).val();
		        var formData = new FormData();
		        formData.append( "private_comment", jQuery( '#add-private-comment' ).val() );
		        formData.append( "followup_ticket_unique_id", jQuery( '#ticket_unique_id' ).val() );
		        formData.append( "post_type", jQuery( '#followup_post_type' ).val() );
		        formData.append( "action", 'rthd_add_new_followup_front' );
		        formData.append( "followuptype", jQuery( '#followuptype' ).val() );
		        formData.append( "follwoup-time", jQuery( '#follwoup-time' ).val() );
		        formData.append( "followup_content", rthdAdmin.rthd_tinymce_get_content( 'followupcontent' ) );
		        formData.append( "followup_attachments", uploadedfiles );

		        //if ( force ){
			     //   formData.append('followup_duplicate_force', true);
		        //}

		        if ( jQuery( '#rthd_keep_status' ) ) {
			        formData.append( "rthd_keep_status", jQuery( '#rthd_keep_status' ).is( ':checked' ) );
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
					                     jQuery( '#chat-UI' ).append( data.comment_content );
					                     //console.log(newcomment);
					                    rthdAdmin.rthd_tinymce_set_content( 'followupcontent', '' );
					                     jQuery( '#add-private-comment' ).val( 10 );
					                     uploadedfiles = [];
					                     if ( data.ticket_status == 'answered' ) {
						                     if ( jQuery( '#rthd_keep_status' ) ) {
							                     jQuery( '#rthd_keep_status' ).parent().hide();
						                     }
					                     } else {
						                     if ( jQuery( '#rthd_keep_status' ).length > 0 ) {
							                     jQuery( '#rthd_keep_status' ).prop( "checked", false );
						                     }
					                     }
					                     jQuery( '#hdspinner' ).hide();
					                     jQuery( '#savefollwoup' ).removeAttr( 'disabled' );
				                     } else {
					                     console.log(data.message );
					                     jQuery( '#hdspinner' ).hide();
					                     jQuery( '#savefollwoup' ).removeAttr( 'disabled' );
				                     }
			                     }
		                     } );
	        }
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

				//day/night shift
				jQuery('.rthd-daynight-am-time-start').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynight-am-time-end').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynight-pm-time-start').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});
				jQuery('.rthd-daynight-pm-time-end').change(function () {
					rthdAdmin.initDayNightValidation(jQuery(this).parent().parent());
				});

                jQuery.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
                    var action = JSON.stringify( options.data );
                    if ( action.indexOf('action=redux_helpdesk_settings_ajax_save&') !== -1 ){
                        var flag = true;

                        if (jQuery('#rthd_enable_auto_response_mode').val() == 1) {
                            for (var i = 0; i < 7; i++) {
                                var tr_parent = jQuery('.rthd-dayshift-time-start').eq(i).parent().parent();
                                if (!rthdAdmin.initDayValidation(tr_parent)) {
                                    flag = false;
                                }
                            }
                        }

                        if ( jQuery('#rthd_enable_auto_response_mode').val() == 0  ) {
                            for (var i = 0; i < 7; i++) {
                                var tr_parent = jQuery('.rthd-daynight-am-time-start').eq(i).parent().parent();
                                if (!rthdAdmin.initDayNightValidation( tr_parent )) {
                                    flag = false;
                                }
                            }
                        }

                        if ( ! flag ){
                            jqXHR.abort();
                            jQuery( '.redux-action_bar input' ).removeAttr( 'disabled' );
                            jQuery( document.getElementById( 'redux_ajax_overlay' ) ).fadeOut( 'fast' );
                            jQuery( '.redux-action_bar .spinner' ).fadeOut( 'fast' );
                            return ;
                        }
                        return flag;
                    }
                });

			}
		},
		initDayValidation: function ( $tr_parent ) {
			var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
			var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();
			var flag = true;
			var allflag = true;

			if ( starting_val == -1 && ending_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).removeClass('myerror').html( '' );
			} else {
				if ( starting_val == -1 || ending_val == -1 ){
                    if ( starting_val == -1 ){
                        jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error').addClass('myerror').html('Please select `Start` time');
                    }else{
                        jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error').addClass('myerror').html('Please select `End` time');
                    }
					flag = false;
				} else if( parseInt( ending_val ) < parseInt( starting_val ) ){
					jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).addClass('myerror').html( 'Starting Time should be less then ending time' );
					flag = false;
				} else{
					jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error').removeClass('myerror').html( '' );
				}
			}

            if ( flag ){
                jQuery( $tr_parent ).next('.rthd-dayshift-error').hide();
                if ( jQuery('#rthd_autoresponse_weekend').val() == 0  ) { // if Weekend only off then che check weektime enter or not
                    for (var i = 0; i < 7; i++) {
                        $tr_parent = jQuery('.rthd-dayshift-time-start').eq(i).parent().parent();
                        var starting_val = $tr_parent.find('.rthd-dayshift-time-start').val();
                        var ending_val = $tr_parent.find('.rthd-dayshift-time-end').val();
                        if (starting_val != -1 || ending_val != -1) {
                            allflag = false;
                        }
                    }
                    if ( allflag ){
                        jQuery('#rthd-response-day-error').show().html('please select working time');
                        flag = false;
                    }
                }
            }else{
                jQuery('#rthd-response-day-error').hide().html('');
            }

            return flag;
		},
		initDayNightValidation: function ( $tr_parent ) {
			var starting_am_val = $tr_parent.find('.rthd-daynight-am-time-start').val();
			var ending_am_val = $tr_parent.find('.rthd-daynight-am-time-end').val();
			var starting_pm_val = $tr_parent.find('.rthd-daynight-pm-time-start').val();
			var ending_pm_val = $tr_parent.find('.rthd-daynight-pm-time-end').val();
			var flag = true;
            var allflag = true;

			if ( starting_am_val == -1 && ending_am_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').removeClass('myerror').html('');
			} else {
				if ( starting_am_val == -1 || ending_am_val == -1 ){
                    if ( starting_am_val == -1 ){
                        jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').addClass('myerror').html('Please select `Start` time');
                    }else{
                        jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').addClass('myerror').html('Please select `End` time');
                    }
					flag = false;
				} else if( parseInt( ending_am_val ) < parseInt( starting_am_val ) ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').addClass('myerror').html('Starting Time should be less then ending time');
					flag = false;
				}else {
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.am-time-error').removeClass('myerror').html('');
				}
			}

			if ( starting_pm_val == -1 && ending_pm_val == -1 ){
				jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').removeClass('myerror').html('');
			}else{
				if ( starting_pm_val == -1 || ending_pm_val == -1 ){
                    if ( starting_pm_val == -1 ){
                        jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').addClass('myerror').html('Please select `Start` time');
                    }else{
                        jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').addClass('myerror').html('Please select `End` time');
                    }
					flag = false;
				}else if( parseInt( ending_pm_val ) < parseInt( starting_pm_val )  ){
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').addClass('myerror').html('Starting Time should be less then ending time');
					flag = false;
				}else{
					jQuery( $tr_parent ).next('.rthd-daynightshift-error').show().find( '.pm-time-error').removeClass('myerror').html('');
				}
			}

            if ( flag ){
                jQuery( $tr_parent ).next('.rthd-daynightshift-error').hide();
                if ( jQuery('#rthd_autoresponse_weekend').val() == 0  ) { // if Weekend only off then che check weektime enter or not
                    for (var i = 0; i < 7; i++) {
                        $tr_parent = jQuery('.rthd-daynight-am-time-start').eq(i).parent().parent();
                        var starting_am_val = $tr_parent.find('.rthd-daynight-am-time-start').val();
                        var ending_am_val = $tr_parent.find('.rthd-daynight-am-time-end').val();
                        var starting_pm_val = $tr_parent.find('.rthd-daynight-pm-time-start').val();
                        var ending_pm_val = $tr_parent.find('.rthd-daynight-pm-time-end').val();
                        if ( starting_am_val != -1 || ending_am_val != -1 || starting_pm_val != -1 || ending_pm_val != -1 ) {
                            allflag = false;
                        }
                    }
                    if ( allflag ){
                        jQuery('#rthd-response-daynight-error').show().html('please select working time');
                        flag = false;
                    }else{

                    }
                }
            }else{
                jQuery('#rthd-response-daynight-error').hide().html('');
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
