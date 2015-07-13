/**
 * Created by spock on 25/2/15.
 */

jQuery( document ).ready(function () {

    var converter = new showdown.Converter({extensions: ['table', 'github', 'prettify']});

	jQuery( '.rt-hd-add-more-email' ).click(function (e) {
		e.preventDefault();
		jQuery( '.rthd-email-group' ).append( jQuery( '.rthd-hide-form-div' ).html() );
	});

	jQuery( '.rthd-email-group' ).on('click', '.rt-hd-remove-textbox', function (e) {
		e.preventDefault();
		jQuery( this ).parent().remove();
	});

    var uploadedfiles = [];
    //$accesscode = jQuery('#rthd_support_nonce' ).val();
    if (typeof plupload != 'undefined') {

        var uploader = new plupload.Uploader({
            // General settings
            runtimes: 'html5,flash,silverlight,html4',
            browse_button: 'attachemntlist', // you can pass in id...
            url: ajaxurl,
            multipart: true,
            multipart_params: {'action': 'rtbiz_hd_upload_attachment'},
            container: document.getElementById( 'attachment-container' ), // ... or DOM Element itself

            // Resize images on client-side if we can
            //resize : { width : 320, height : 240, quality : 90 },

            filters: {
                max_file_size: '10mb',
				mime_types: [
					{title : "Files ", extensions : rtbiz_hd_supported_extensions}
				]
            },

            flash_swf_url: 'Moxie.swf',
            silverlight_xap_url: 'Moxie.xap',

            // PreInit events, bound before the internal events

            init: {
                PostInit: function () {
                    document.getElementById( 'support-filelist' ).innerHTML = '';

                    document.getElementById( 'submit-support-form' ).onclick = function (e) {
                        e.preventDefault();
                        jQuery( '#support-form-filelist' ).html( '' );
                        jQuery( '#rthd_support_attach_ids' ).val( uploadedfiles );
                        var post_content = jQuery( '#post_description_body' );
                        if ( ! jQuery.trim( post_content.val() ) ) {
                            post_content.css( 'border-color', 'red' );
                        } else {
                            post_content.css( 'border-color', '' );
                        }
                        var name = jQuery( '#title' );
                        if ( ! name.val().length) {
                            name.css( 'border-color', 'red' );
                        } else {
                            name.css( 'border-color', '' );
                        }
                        var product = jQuery( 'select[name="post[product_id]"]' );
                        if (product.length) {
                            if ( ! product.val().length) {
                                product.css( 'border-color', 'red' );
                            } else {
                                product.css( 'border-color', '' );
                            }
                        }
                        if (jQuery( '.rthd_support_from' )[0].checkValidity()) {
                            uploader.start();
                        }
                        //return false;
                    };
                },

                FilesAdded: function (up, files) {
                    plupload.each(files, function (file) {
                        document.getElementById( 'support-filelist' ).innerHTML += '<div id="' + file.id + '"><a href="#" class="support-attach-remove"> x </a> ' + file.name + ' (' + plupload.formatSize( file.size ) + ') <b></b></div>';
                    });
                },

                FilesRemoved: function (up, files) {
                    plupload.each(files, function (file) {
                        jQuery( '#' + file.id ).remove();
                    });
                },

                UploadProgress: function (up, file) {
                    document.getElementById( file.id ).getElementsByTagName( 'b' )[0].innerHTML = '<span>' + file.percent + '% </span><progress max="100" value="' + file.percent + '"></progress>';
                },

                Error: function (up, err) {
                    jQuery( '#support-filelist' ).append('<div class="rthd-error">Selected file doesn\'t supported.</div>');
                    //document.getElementById( 'console' ).innerHTML += "\nError #" + err.code + ": " + err.message;
                },

                UploadComplete: function () {
                    jQuery( '#support-form-filelist' ).html( '' );
                    jQuery( '#rthd_support_attach_ids' ).val( uploadedfiles );

                    var post_content = jQuery( '#rt-hd-support-page #post_description_body' );
                    var post_content_html = jQuery( '<div/>', { html: converter.makeHtml( post_content.val() ) } );
                    if ( ! jQuery.trim( post_content.val() ) || ! jQuery.trim( post_content_html.text() ) ) {
                        post_content.css( 'border-color', 'red' );
                        return false;
                    }else{
                        post_content.css( 'border-color', '' );
                    }
                    jQuery( '.rthd_support_from' ).submit();
                },

                FileUploaded: function (up, file, info) {
                    // Called when file has finished uploading
                    var response = jQuery.parseJSON( info.response );
                    if (response.status) {
                        jQuery( '#' + file.id + ' b' ).replaceWith( '<span class="dashicons dashicons-yes rthd-ticket-file-uploaded"></span>' );
                        uploadedfiles = uploadedfiles.concat( response.attach_ids );
                    }
                }
            }
        });
        uploader.init();
    }

    jQuery( document ).on('click', '.support-attach-remove', function (e) {
        e.preventDefault();
        uploader.removeFile( jQuery( this ).parent().attr( "id" ) );
    });

    jQuery( '#title' ).change(function (e) {
        ShowErrors( this );
    });

    function ShowErrors(val) {
        if (jQuery( val ).val().length > 0) {
            //jQuery(val).css('border-color','');
            jQuery( val ).removeClass( 'rthd-support-input-error' );
        } else {
            //jQuery(val).css('border-color','red');
            jQuery( val ).addClass( 'rthd-support-input-error' );
        }
    }

    jQuery( 'select[name="post[product_id]"]' ).change(function (e) {
        ShowErrors( this );
    });

});
