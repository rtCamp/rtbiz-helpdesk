/**
 * Created by spock on 29/6/15.
 */


jQuery( document ).ready(function ($) {
	var doing_ajax = false;
	var rthdShortcode = {
		init: function () {
			rthdShortcode.scroll();
		},
		scroll : function (){
			if ( typeof rthd_shortcode_params !== 'undefined' ) {
				$( window ).scroll( function () {
					if ( $( window ).scrollTop() == $( document ).height() - $( window ).height() ) {
						// run our call for pagination
						rthdShortcode.load_more_helpdesk_ticket();
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
			if ( requestArray.offset >= $( '.rthd-count-total' ).html() ) {
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
	}
};
	rthdShortcode.init();
});
