/**
 * Created by spock on 25/2/15.
 */

jQuery(document).ready(function (){
	jQuery('.rt-hd-add-more-email' ).click(function(e){
		e.preventDefault();
		jQuery('.rthd-email-group' ).append(jQuery('.rthd-hide-form-div' ).html());
	});

	jQuery('.rthd-email-group' ).on('click','.rt-hd-remove-textbox',function(e){
		e.preventDefault();
		jQuery(this ).parent().remove();
	});


	function rthd_tinymce_get_content_support( id ) {
		if( typeof tinymce != "undefined" ) {
			var editor = tinymce.get( id );
			if( editor && editor instanceof tinymce.Editor ) {
				return editor.getContent();
			} else {
				return jQuery( '#'+id ).val();
			}
		}
		return '';
	}

	/**
	 * Created by spock on 3/4/15.
	 */

	jQuery( document ).ready( function ( $ ) {
		var uploadedfiles= [];
		//$accesscode = jQuery('#rthd_support_nonce' ).val();
		if ( typeof plupload != 'undefined' ) {

			var uploader = new plupload.Uploader( {
				                                      // General settings
				                                      runtimes: 'html5,flash,silverlight,html4',
				                                      browse_button: 'attachemntlist', // you can pass in id...
				                                      url: ajaxurl,
				                                      multipart: true,
				                                      multipart_params: {'action': 'rthd_upload_attachment'},
				                                      container: document.getElementById( 'attachment-container' ), // ... or DOM Element itself

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
						                                      document.getElementById( 'support-filelist' ).innerHTML = '';

						                                      document.getElementById( 'submit-support-form' ).onclick = function ( e ) {
							                                      e.preventDefault();
							                                      jQuery( '#support-form-filelist' ).html( '' );
							                                      jQuery( '#rthd_support_attach_ids' ).val( uploadedfiles );
							                                      if ( ! rthd_tinymce_get_content_support( 'post_description' ) ) {
								                                      alert( 'Please enter Description' );
								                                      // need to find tinyMCE change event and add
								                                      // jQuery('#editor_container').after("<span class='error'>Please enter Discrpition</span>");
							                                      }
							                                      var name = jQuery('#title' );
							                                      var product = jQuery('select[name="post[product_id]"]' );
							                                      if ( ! name.val().length ){
								                                      name.css('border-color','red');
							                                      }
							                                      if ( product.length ) {
								                                      if ( ! product.val().length ) {
									                                      product.css( 'border-color', 'red' );
								                                      }
							                                      }
																  if ( jQuery('.rthd_support_from')[0].checkValidity() ){
																	  uploader.start();
																  }
							                                      //return false;
						                                      };
					                                      },

					                                      FilesAdded: function ( up, files ) {
						                                      plupload.each( files, function ( file ) {
							                                      document.getElementById( 'support-filelist' ).innerHTML += '<div id="' + file.id + '"><a href="#" class="support-attach-remove"> x </a> ' + file.name + ' (' + plupload.formatSize( file.size ) + ') <b></b></div>';
						                                      } );
					                                      },

					                                      FilesRemoved: function ( up, files ) {
						                                      plupload.each( files, function ( file ) {
							                                      jQuery( '#' + file.id ).remove();
						                                      } );
					                                      },

					                                      UploadProgress: function ( up, file ) {
						                                      document.getElementById( file.id ).getElementsByTagName( 'b' )[0].innerHTML = '<span>' + file.percent + '% </span><progress max="100" value="' + file.percent + '"></progress>';
					                                      },

					                                      Error: function ( up, err ) {
						                                      document.getElementById( 'console' ).innerHTML += "\nError #" + err.code + ": " + err.message;
					                                      },

					                                      UploadComplete: function () {
						                                      jQuery( '#support-form-filelist' ).html( '' );
						                                      jQuery( '#rthd_support_attach_ids' ).val( uploadedfiles );
						                                      jQuery( '.rthd_support_from' ).submit();
					                                      },

					                                      FileUploaded: function ( up, file, info ) {
						                                      // Called when file has finished uploading
						                                      var response = jQuery.parseJSON( info.response );
						                                      if ( response.status ) {
							                                      jQuery( '#' + file.id + ' b' ).replaceWith( '<span class="dashicons dashicons-yes rthd-ticket-file-uploaded"></span>' );
							                                      uploadedfiles = uploadedfiles.concat( response.attach_ids );
						                                      }
					                                      }
				                                      }
			                                      } );
			uploader.init();
		}

		jQuery(document).on('click','.support-attach-remove', function( e ){
			e.preventDefault();
			uploader.removeFile(jQuery(this ).parent().attr("id"));
		});

		jQuery('#title' ).change( function ( e ) {
			ShowErrors(this);
		});

		function ShowErrors(val){
			if ( jQuery(val).val().length > 0 ) {
				//jQuery(val).css('border-color','');
				jQuery(val).removeClass('rthd-support-input-error');
			} else{
				//jQuery(val).css('border-color','red');
				jQuery(val).addClass('rthd-support-input-error');
			}
		}

		jQuery('select[name="post[product_id]"]' ).change( function ( e ) {
			ShowErrors(this);
		})


	});


});
