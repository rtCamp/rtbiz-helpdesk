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
			rthd_common.initAddNewFollowUp();
			rthd_common.initEditFollowUp();
			rthd_common.initLoadAll();
			rthd_common.initEditContent();
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
				if ( ! rthd_common.rthd_tinymce_get_content( 'followupcontent' )) {
					alert( "Please input followup." );
					jQuery( '#hdspinner' ).hide();
					//jQuery(this).removeAttr('disabled');
					return false;
				}
				return true;
			}

			// send followup ajax
			function sendFollowup(force) {
				jQuery( '#savefollwoup' ).attr( 'disabled', 'disabled' );
				var followuptype = jQuery( "#followup-type" ).val();
				var formData = new FormData();
				var followup_type = jQuery( "input[name='private_comment']:checked" );
				if (followup_type.length) {
					followup_type = followup_type.val();
				} else {
					//if ( jQuery( "input[name='private_comment']" ).is(':checked')){
					//	followup_type = jQuery( "input[name='private_comment']" ).val();
					//} else {
						followup_type = 10;
					//}
				}
				formData.append( "private_comment", followup_type );
				formData.append( "followup_ticket_unique_id", jQuery( '#ticket_unique_id' ).val() );
				formData.append( "post_type", jQuery( '#followup_post_type' ).val() );
				formData.append( "action", 'rthd_add_new_followup_front' );
				formData.append( "followuptype", jQuery( '#followuptype' ).val() );
				formData.append( "follwoup-time", jQuery( '#follwoup-time' ).val() );
				formData.append( "followup_content", rthd_common.rthd_tinymce_get_content( 'followupcontent' ) );
				formData.append( "followup_attachments", uploadedfiles );

				//if ( force ){
				//   formData.append('followup_duplicate_force', true);
				//}

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

							rthd_common.rthd_tinymce_set_content( 'followupcontent', '' );

							var comment_privacy = jQuery( "input[name='private_comment']" );
							if (comment_privacy.is(':radio')){
								jQuery('#followup_privacy_10').attr('checked','checked');
							} else {
								comment_privacy.attr('checked',false);
							}

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
			var commentid;
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
						action: 'helpdesk_delete_followup',
						comment_id: commentid,
						post_id: postid
					},
					success: function (data) {
						if (data.status) {
							jQuery( "#comment-" + commentid ).fadeOut(500, function () {
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
				jQuery( document ).scrollTop( ( jQuery( '#comment-' + commentid ).offset().top ) );
			});

			// show ui to edit or delete followup on click of edit link
			jQuery( document ).on('click', '.editfollowuplink', function (e) {
				e.preventDefault();
				var select = jQuery( this ).parents();
				rthd_common.rthd_tinymce_set_content( 'editedfollowupcontent', jQuery( this ).parents().siblings( '.rthd-comment-content' ).data( 'content' ) );
				commentid = select.siblings( '#followup-id' ).val();
				var that = select.siblings( '#is-private-comment' ).val();
				var followup_type = jQuery( "input[name='edit_private'][value='" + that + "']" );
				if (followup_type.length) {
					followup_type.prop( 'checked', true );
				} else {
					followup_type.val( that );
				}
				//jQuery('#edit-private' ).val(that);
				jQuery( '#new-followup-form' ).hide();
				if ( ! jQuery( '#dialog-form' ).is( ":visible" )) {
					jQuery( '#dialog-form' ).slideToggle( 'slow' );
				}
				if (jQuery( '#edit-ticket-data' ).is( ":visible" )) {
					jQuery( '#edit-ticket-data' ).slideToggle( 'slow' );
				}
				jQuery( document ).scrollTop( ( jQuery( '#dialog-form' ).offset().top ) - 50 );

			});

			// edit followup ajax call
			jQuery( "#editfollowup" ).click(function () {
				var requestArray = {};
				var content = rthd_common.rthd_tinymce_get_content( 'editedfollowupcontent' );
				if ( ! content) {
					alert( "Please enter comment" );
					return false;
				}
				if (content.replace( /\s+/g, " " ) === jQuery( '#comment-' + commentid ).find( '.rthd-comment-content' ).data( 'content' )) {
					alert( 'You have not edited comment!' );
					return false;
				}
				jQuery( '#edithdspinner' ).show();
				jQuery( this ).attr( 'disabled', 'disabled' );
				requestArray.post_type = rthd_post_type;
				requestArray.comment_id = commentid;
				requestArray.action = "rthd_update_followup_ajax";
				requestArray.followuptype = "comment";
				requestArray.followup_ticket_unique_id = jQuery( '#ticket_unique_id' ).val();
				//requestArray.followup_private='no';
				requestArray.followup_post_id = jQuery( '#post-id' ).val();
				var followup_type = jQuery( "input[name='edit_private']:checked" );
				if (followup_type.length) {
					followup_type = followup_type.val();
				} else {
					followup_type = jQuery( "input[name='edit_private']" ).val();
				}
				requestArray.followup_private = followup_type;
				requestArray.followuptype = 'comment';
				//requestArray.followup_post_id = jQuery( "#ticket_id" ).val();
				//requestArray.follwoup_time = jQuery( "#follwoup_time" ).val();
				requestArray.followup_content = content;
				jQuery.ajax({
					url: ajaxurl,
					dataType: "json",
					type: 'post',
					data: requestArray,
					success: function (data) {
						if (data.status) {
							jQuery( '#comment-' + commentid ).replaceWith( data.comment_content );
							jQuery( '.close-edit-followup' ).trigger( 'click' );
							jQuery( document ).scrollTop( ( jQuery( '#comment-' + commentid ).offset().top ) - 50 );
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
				requestArray.action = "load_more_followup";
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
				requestArray.action = 'rthd_add_new_ticket_ajax';
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
