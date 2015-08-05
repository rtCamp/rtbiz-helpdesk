/**
 * Created by spock on 29/6/15.
 */


jQuery( document ).ready(function ($) {
	var doing_ajax = false;
	var rthdShortcode = {
		init: function () {
			rthdShortcode.scroll();
            rthdShortcode.initAddHdErrorPage();
		},
		scroll : function (){
			if ( typeof rthd_shortcode_params !== 'undefined' ) {
				jQuery( document ).scroll( function() {
					if ( jQuery('.rthd_ticket_short_code tr:last').length > 0 ) {
						var div_top = jQuery('.rthd_ticket_short_code tr:last').offset().top;
						if (jQuery(window).scrollTop() > div_top - 500) {
							//jQuery( document ).unbind( 'scroll' );
							rthdShortcode.load_more_helpdesk_ticket();
						}
					}
				} );
			}
		},

		load_more_helpdesk_ticket : function () {
			if ( doing_ajax ) {
				return;
			}
			var requestArray = {};
			requestArray.offset = $( '.rthd_ticket_short_code tr' ).length - 1;
			if ( requestArray.offset >= $( '.rt-hd-total-ticket-count' ).val() ) {
				return;
			}
			requestArray.short_code_param = rthd_shortcode_params;
			requestArray.action = 'rtbiz_hd_load_more_ticket_shortcode';
			doing_ajax = true;
			loader = $( '.rthd-ticket-short-code-loader' );
			loader.css( 'display', 'block' );
			$.ajax( {
			        url: ajaxurl, type: 'POST', dataType: 'json', data: requestArray,
			        success: function ( data ) {
						if ( data.status ) {
							$( '.rthd_ticket_short_code' ).append( data.html );
							count = $( '.rthd_ticket_short_code tr' ).length - 1;
							$( '.rthd-current-count' ).html( count );
						} else {
							console.log( data.msg );
						}
						doing_ajax = false;
						loader.hide();
					}
	        });
	    },
        initAddHdErrorPage: function(){
            jQuery( '#rthd-add-hd-error-page' ).on('click', function (e) {
                var requestArray = {};
                requestArray.action = 'rtbiz_add_hd_error_page';
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: requestArray,
                    success: function (data) {
                        if ( data.status ) {
                            location.reload();
                        }else{
                            console.log( data.msg );
                        }
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        console.log( data.msg );
                    }
                });
            });
        }
    };
	rthdShortcode.init();
});
