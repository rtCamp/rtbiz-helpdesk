jQuery( document ).ready(function () {

	var rthd_common = {
		rthd_tinymce_set_content: function (id, text) {
			if (typeof tinymce != "undefined") {
				var editor = tinymce.get( id );
				if (editor && editor instanceof tinymce.Editor) {
					editor.setContent( text );
					editor.save( {no_events: true} );
				} else {
					jQuery( '#' + id ).val( text );
				}
				return true;
			}
			return false;
		},
		rthd_tinymce_get_content: function (id) {
			if (typeof tinymce != "undefined") {
				var editor = tinymce.get( id );
				if (editor && editor instanceof tinymce.Editor) {
					return editor.getContent();
				} else {
					return jQuery( '#' + id ).val();
				}
			}
			return '';
		},
		init: function () {
            rthd_common.initAction();
            rthd_common.initAddNewFollowUp();
			rthd_common.initEditFollowUp();
			rthd_common.initLoadAll();
			rthd_common.initEditContent();
		},
        initAction : function () {
            jQuery( document ).on('click', '#followup-type-list li', function (e) {
                jQuery('#followuptype').val( jQuery( this).data('ctype') );
            });
            jQuery( document ).on('click', '#dialog-form #followup-type-list li', function (e) {
                jQuery('#dialog-form #followup-type').val( jQuery( this).data('ctype') );
            });
        },
		initAddNewFollowUp: function () {

			$ticket_unique_id = jQuery( '#ticket_unique_id' ).val();
			var uploadedfiles = [];
			var force_add_duplicate = false;
			// pluploader init
			if (typeof plupload != 'undefined') {
				var uploader = new plupload.Uploader({
					// General settings
					runtimes: 'html5,flash,silverlight,html4',
					browse_button: 'attachemntlist', // you can pass in id...
					url: ajaxurl,
					multipart: true,
					multipart_params: {
						'action': 'rtbiz_hd_upload_attachment',
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
								if (followupValidate()) {
									//if ( uploader.files.length ) {
									//    checkDuplicateFollowup();
									//} else {
									uploader.start();
									//}
								}
							};
						},
						FilesAdded: function (up, files) {
							plupload.each(files, function (file) {
								document.getElementById( 'followup-filelist' ).innerHTML += '<div id="' + file.id + '"><a href="#" class="followup-attach-remove"><span class="dashicons dashicons-dismiss"></span></a> ' + file.name + ' (' + plupload.formatSize( file.size ) + ') <b></b></div>';
							});
						},
						FilesRemoved: function (up, files) {
							plupload.each(files, function (file) {
								jQuery( '#' + file.id ).remove();
							});
						},
						UploadProgress: function (up, file) {
							document.getElementById( file.id ).getElementsByTagName( 'b' )[0].innerHTML = '<span>' + file.percent + "%</span>";
						},
						Error: function (up, err) {
							document.getElementById( 'console' ).innerHTML += "\nError #" + err.code + ": " + err.message;
						},
						UploadComplete: function () {
							document.getElementById( 'followup-filelist' ).innerHTML = '';
							sendFollowup( force_add_duplicate );
							force_add_duplicate = false;
							uploadedfiles = [];
						},
						FileUploaded: function (up, file, info) {
							// Called when file has finished uploading
							var response = jQuery.parseJSON( info.response );
							if (response.status) {
								jQuery( '#' + file.id + ' b' ).replaceWith( '<span class="dashicons dashicons-yes rthd-followup-file-uploaded"></span>' );
								uploadedfiles = uploadedfiles.concat( response.attach_ids );
							}
						}
					}
				});
				uploader.init();
			}

			// on click of attachment remove tell plupoloader to remove attachment from it's object
			jQuery( document ).on('click', '.followup-attach-remove', function (e) {
				e.preventDefault();
				uploader.removeFile( jQuery( this ).parent().attr( "id" ) );
			});

			// validate followup settings
			function followupValidate() {
				jQuery( '#hdspinner' ).show();
				//jQuery(this).attr('disabled','disabled');
				if ( ! jQuery( "#ticket_unique_id" ).val()) {
					alert( 'Please publish ticket before adding followup! :( ' );
					jQuery( '#hdspinner' ).hide();
					//jQuery(this).removeAttr('disabled');
					return false;
				}
                var post_content = jQuery( '#followupcontent' )
                if ( ! jQuery.trim( post_content.val() ) ) {
                    post_content.css( 'border-color', 'red' );
                    jQuery( '#hdspinner' ).hide();
                    return false;
                } else {
                    post_content.css( 'border-color', '' );
                }
				return true;
			}

			// send followup ajax
			function sendFollowup(force) {
				jQuery( '#savefollwoup' ).attr( 'disabled', 'disabled' );

                var followup_type = jQuery( '#followuptype' );
				if ( followup_type.length && followup_type.val() ) {
					followup_type = followup_type.val();
				} else {
						followup_type = 10;
				}
                var formData = new FormData();
				formData.append( "private_comment", jQuery( '#rthd_sensitive' ).is( ':checked' ) );
				formData.append( "followup_ticket_unique_id", jQuery( '#ticket_unique_id' ).val() );
				formData.append( "post_type", jQuery( '#followup_post_type' ).val() );
				formData.append( "action", 'rtbiz_hd_add_new_followup_front' );
				formData.append( "followuptype", followup_type );
				formData.append( "follwoup-time", jQuery( '#follwoup-time' ).val() );
				formData.append( "followup_content", jQuery( '#followupcontent' ).val() );
				formData.append( "followup_attachments", uploadedfiles );
				if (jQuery( '#rthd_keep_status' )) {
					formData.append( "rthd_keep_status", jQuery( '#rthd_keep_status' ).is( ':checked' ) );
				}
				jQuery.ajax({
					url: ajaxurl,
					dataType: "json",
					type: 'POST',
					data: formData,
					cache: false,
					contentType: false,
					processData: false,
					//async: false, // no more page freezing
					success: function (data) {
						if (data.status) {
							jQuery( '#chat-UI' ).append( data.comment_content );

							// below code is for front end side bar
							if (jQuery( '#rthd-assignee-list' ).length) {
								jQuery( '#rthd-assignee-list' ).val( data.assign_value );
							}
							if (jQuery( '#rthd-status-list' ).length) {
								jQuery( '#rthd-status-list' ).val( data.post_status );
							} else if (jQuery( '#rthd-status-visiable' ).length) {
								jQuery( '#rthd-status-visiable' ).html( data.post_status );
							}
							jQuery( '.rt-hd-ticket-last-reply-value' ).html( data.last_reply );

							// front end code end
                            jQuery('#followupcontent').val('');
                            jQuery('#followuptype').val('');

							uploadedfiles = [];
							if (data.ticket_status == 'answered') {
								if (jQuery( '#rthd_keep_status' )) {
									jQuery( '#rthd_keep_status' ).parent().hide();
								}
							} else {
								if (jQuery( '#rthd_keep_status' ).length > 0) {
									jQuery( '#rthd_keep_status' ).prop( "checked", false );
								}
							}
							jQuery( '#hdspinner' ).hide();
						} else {
							console.log( data.message );
							jQuery( '#hdspinner' ).hide();
						}
						jQuery( '#savefollwoup' ).removeAttr( 'disabled' );
					}
				});
			}
		},
		initEditFollowUp: function () {
			var followup_id;
			//ajax call to remove followup
			jQuery( "#delfollowup" ).click(function () {
				var r = confirm( "Are you sure you want to remove this Followup?" );
				if (r !== true) {
					e.preventDefault();
					return false;
				}
				jQuery( '#edithdspinner' ).show();
				jQuery( this ).attr( 'disabled', 'disabled' );
				postid = jQuery( '#post-id' ).val();
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'rtbiz_hd_delete_followup',
						comment_id: followup_id,
						post_id: postid
					},
					success: function (data) {
						if (data.status) {
							jQuery( "#comment-" + followup_id ).fadeOut(500, function () {
								jQuery( this ).remove();
							});
							jQuery( '.close-edit-followup' ).trigger( 'click' );
						} else {
							alert( "Error while deleting comment from server" );
						}
						jQuery( '#edithdspinner' ).hide();
						jQuery( "#delfollowup" ).removeAttr( 'disabled' );
					},
					error: function (xhr, textStatus, errorThrown) {
						alert( "error while removing follow up." );
						jQuery( '#edithdspinner' ).hide();
						jQuery( "#delfollowup" ).removeAttr( 'disabled' );
					}
				});

			});

			// close edit followup
			jQuery( document ).on('click', '.close-edit-followup', function (e) {
				e.preventDefault();
				jQuery( '#dialog-form' ).slideToggle( 'slow' );
				jQuery( '#new-followup-form' ).show();
				jQuery( document ).scrollTop( ( jQuery( '#comment-' + followup_id ).offset().top ) );
			});

			// show ui to edit or delete followup on click of edit link
			jQuery( document ).on('click', '.editfollowuplink', function (e) {
				e.preventDefault();
				var followup_information = jQuery( this ).parents().parents();
                followup_id = followup_information.siblings( '#followup-id' ).val();
                followup_content = followup_information.siblings( '.rthd-comment-content' ).data( 'content' );
                followup_type = followup_information.siblings( '#followup-type' ).val();
                followup_senstive = followup_information.siblings( '#followup-senstive' ).val();

                jQuery('#dialog-form #editedfollowupcontent').val( followup_content );
                jQuery('#dialog-form #followup-type').val( followup_type );

                console.log( jQuery('#dialog-form #editedfollowupcontent').val() )
                console.log( jQuery('#dialog-form #followup-type').val() )

                if ( 1 == followup_senstive ){
                    jQuery( '#dialog-form').find( '#rthd_sensitive' ).attr( 'checked', 'checked' );
                } else {
                    jQuery( '#dialog-form').find( '#rthd_sensitive' ).removeAttr('checked');
                }

				//jQuery('#edit-private' ).val(that);
				jQuery( '#new-followup-form' ).hide();
				if ( ! jQuery( '#dialog-form' ).is( ":visible" )) {
					jQuery( '#dialog-form' ).slideToggle( 'slow' );
				}

				jQuery( document ).scrollTop( jQuery( '#dialog-form' ).offset().top - 50 );

			});

			// edit followup ajax call
			jQuery( "#editfollowup" ).click(function () {
				var requestArray = {};
				var post_content = jQuery( '#dialog-form #editedfollowupcontent' );
                if ( ! jQuery.trim( post_content.val() ) ) {
                    post_content.css( 'border-color', 'red' );
                    return false;
                } else {
                    post_content.css( 'border-color', '' );
                }

				if ( post_content.val() === jQuery( '#comment-' + followup_id ).find( '.rthd-comment-content' ).data( 'content' )) {
					alert( 'You have not edited comment!' );
					return false;
				}

				jQuery( '#edithdspinner' ).show();
				jQuery( this ).attr( 'disabled', 'disabled' );

                var followup_type = jQuery( '#dialog-form #followup-type' );
                if ( followup_type.length && followup_type.val() ) {
                    followup_type = followup_type.val();
                } else {
                    followup_type = 10;
                }
				requestArray.post_type = rtbiz_hd_post_type;
				requestArray.comment_id = followup_id;
				requestArray.action = "rtbiz_hd_update_followup";
				requestArray.followup_ticket_unique_id = jQuery( '#ticket_unique_id' ).val();
				requestArray.followup_post_id = jQuery( '#post-id' ).val();
				requestArray.private_comment = jQuery( '#dialog-form').find( '#rthd_sensitive' ).is( ':checked' );
				requestArray.followuptype = followup_type;
				requestArray.followup_content = post_content.val();

				jQuery.ajax({
					url: ajaxurl,
					dataType: "json",
					type: 'post',
					data: requestArray,
					success: function (data) {
						if (data.status) {
							jQuery( '#comment-' + followup_id ).replaceWith( data.comment_content );
							jQuery( '.close-edit-followup' ).trigger( 'click' );
							jQuery( document ).scrollTop( jQuery( '#comment-' + followup_id ).offset().top - 50 );
						} else {
							alert( data.message );
						}
						jQuery( '#edithdspinner' ).hide();
						jQuery( "#editfollowup" ).removeAttr( 'disabled' );

					},
					error: function (data) {
						alert( "Sorry :( something went wrong!" );
						jQuery( '#edithdspinner' ).hide();
						jQuery( "#editfollowup" ).removeAttr( 'disabled' );
					}
				});
			});
		},
		initLoadAll: function () {
			// if there are more than 3 followup show load followp button
			jQuery( '#followup-load-more, .load-more-block' ).click(function (e) {
				e.preventDefault();
				var requestArray = {};
				var totalcomment = parseInt( jQuery( '#followup-totalcomment' ).val(), 10 );
				var limit = parseInt( jQuery( '#followup-limit' ).val(), 10 );
				if (limit != 3) {
					return;
				}
				jQuery( this ).parent().hide();
				jQuery( '.load-more-spinner-li' ).show();
				jQuery( '#load-more-hdspinner' ).css( 'display', 'inline' );
				requestArray.limit = totalcomment - 3;
				requestArray.offset = 0;
				requestArray.action = "rtbiz_hd_load_more_followup";
				requestArray.post_id = jQuery( '#post-id' ).val();
				//requestArray.all =  'true';
				jQuery.ajax({
					url: ajaxurl,
					dataType: "json",
					type: 'post',
					data: requestArray,
					success: function (data) {
						if (data.status) {
							jQuery( '#followup-offset' ).val( data.offset );
							jQuery( '#chat-UI' ).prepend( data.comments );
						}
						jQuery( '#load-more-hdspinner' ).hide();
					},
					error: function () {
						jQuery( '#load-more-hdspinner' ).hide();
						return false;
					}
				});

			});
		},
		initEditContent: function () {

			// edit ticket content
			jQuery( '.edit-ticket-link' ).click(function (e) {
				e.preventDefault();
				jQuery( '#edit-ticket-data' ).slideToggle( 'slow' );
				if (jQuery( '#dialog-form' ).is( ":visible" )) {
					jQuery( '#dialog-form' ).slideToggle( 'slow' );
				}
				if ( ! jQuery( '#edit-ticket-data' ).is( ":visible" )) {
					jQuery( '#edit-ticket-data' ).slideToggle( 'slow' );
				}
				jQuery( '#new-followup-form' ).hide();
				jQuery( document ).scrollTop( ( jQuery( '#edit-ticket-data' ).offset().top ) - 50 );
				rthd_common.rthd_tinymce_set_content( 'editedticketcontent', jQuery( this ).closest( '.ticketcontent' ).find( '.rthd-comment-content' ).data( 'content' ) );
			});

			// close tinyMCE editor and send user back to ticket content
			jQuery( '.close-edit-content' ).click(function (e) {
				e.preventDefault();
				jQuery( '#edit-ticket-data' ).slideToggle( 'slow' );
				jQuery( '#new-followup-form' ).show();
				jQuery( document ).scrollTop( ( jQuery( '.ticketcontent' ).offset().top ) - 50 );
			});

			// ajax request to change ticket content
			jQuery( '#edit-ticket-content-click' ).click(function () {
				jQuery( '#edit-ticket-data' ).slideToggle( 'slow' );
				jQuery( '#new-followup-form' ).hide();
				var requestArray = {};
				requestArray.action = 'rtbiz_hd_add_new_ticket_ajax';
				requestArray.post_id = jQuery( '#post-id' ).val();
				var content = rthd_common.rthd_tinymce_get_content( 'editedticketcontent' );
				requestArray.body = content;
				requestArray.nonce = jQuery( '#edit_ticket_nonce' ).val();
				jQuery( '#ticket-edithdspinner' ).show();
				jQuery( this ).attr( 'disabled', 'disabled' );
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: requestArray,
					success: function (data) {
						if (data.status) {
							jQuery( '#ticket-edithdspinner' ).hide();
							jQuery( "#edit-ticket-content-click" ).removeAttr( 'disabled' );
							jQuery( '.edit-ticket-link' ).closest( '.ticketcontent' ).find( '.rthd-comment-content' ).html( rthd_common.rthd_tinymce_get_content( 'editedticketcontent' ) );
							jQuery( '#edit-ticket-data' ).hide();
							jQuery( '#new-followup-form' ).slideToggle( 'slow' );
							jQuery( document ).scrollTop( ( jQuery( '.ticketcontent' ).offset().top ) - 50 );
						} else {
							console.log( data.msg );
						}
					},
					error: function (xhr, textStatus, errorThrown) {
						alert( "Error" );
						jQuery( '#ticket-edithdspinner' ).hide();
						jQuery( "#edit-ticket-content-click" ).removeAttr( 'disabled' );
					}
				});
			});
		}

	};
	rthd_common.init();

});
