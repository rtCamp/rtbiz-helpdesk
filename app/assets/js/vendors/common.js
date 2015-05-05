jQuery( document ).ready( function () {

	var rthd_common = {

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
			rthd_common.initAddNewFollowUp();
			rthd_common.initEditFollowUp();
			rthd_common.initLoadAll();
			rthd_common.initEditContent();
			rthd_common.initAutoResponseSettings();
		},
		initAddNewFollowUp : function(){
			$ticket_unique_id = jQuery( '#ticket_unique_id' ).val();
			var uploadedfiles= [];
			var force_add_duplicate = false;
			if ( typeof plupload != 'undefined' ) {
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
				if ( ! rthd_common.rthd_tinymce_get_content( 'followupcontent' ) ) {
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
				formData.append( "followup_content", rthd_common.rthd_tinymce_get_content( 'followupcontent' ) );
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

							             // below code is for front end side bar
							             if (jQuery( '#rthd-assignee-list' ).length) {
								             jQuery( '#rthd-assignee-list' ).val( data.assign_value );
							             }
							             if ( jQuery( '#rthd-status-list' ).length ) {
								             jQuery( '#rthd-status-list' ).val( data.post_status );
							             } else if ( jQuery( '#rthd-status-visiable' ).length ) {
								             jQuery( '#rthd-status-visiable' ).html( data.post_status );
							             }
							             jQuery( '.rt-hd-ticket-last-reply-value' ).html( data.last_reply );

							             // front end code end

							             rthd_common.rthd_tinymce_set_content( 'followupcontent', '' );
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
	}

});
