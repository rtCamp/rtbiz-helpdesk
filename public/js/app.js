/**
 * jQuery.browser.mobile (http://detectmobilebrowser.com/)
 *
 * jQuery.browser.mobile will be true if the browser is a mobile device
 *
 **/
(function (a) {
	(jQuery.browser = jQuery.browser || {}).mobile = /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test( a ) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test( a.substr( 0, 4 ) );
})(navigator.userAgent || navigator.vendor || window.opera);


//rtbiz_hd_user_edit = rtbiz_hd_user_edit[0];
var file_frame_ticket;
jQuery( document ).ready(function ($) {

	jQuery( ".fancybox" ).fancybox({
		afterLoad: function () {
			this.title = '<a class="rtbiz_hd_quick_download" target="_blank" download="' + jQuery( this.element ).data( "downloadlink" ) + '" href="' + jQuery( this.element ).data( "downloadlink" ) + '">Download</a> ' + this.title;
		},
		iframe: {
			preload: false
		},
		helpers: {
			title: {
				type: 'inside'
			}
		}
	});

	jQuery( '.rthd-scroll-up' ).click(function (e) {
		e.preventDefault();
		$( 'html, body' ).animate( {scrollTop: 0}, 'slow' );
	});

	jQuery( window ).scroll(function () {
		if (jQuery( this ).scrollTop() > 500) {
			jQuery( '.rthd-scroll-up' ).fadeIn();
		} else {
			jQuery( '.rthd-scroll-up' ).fadeOut();
		}
	});

	function rthd_tinymce_set_content(id, text) {
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
	}

	function rthd_tinymce_get_content(id) {
		if (typeof tinymce != "undefined") {
			var editor = tinymce.get( id );
			if (editor && editor instanceof tinymce.Editor) {
				return editor.getContent();
			} else {
				return jQuery( '#' + id ).val();
			}
		}
		return '';
	}

	if ( ! jQuery.browser.mobile) {
		$( '.rthd_sticky_div' ).stickyfloat( {duration: 400, delay: 3} );
	}
	/*jQuery( '.followup-hash-url' ).click( function(e) {
     //e.preventDefault();
     jQuery(document).scrollTop( ( jQuery( window.location.hash ).offset().top ) - 100 );
     });
     jQuery(function() {
     jQuery(document).scrollTop( ( jQuery( window.location.hash ).offset().top ) - 100 );
     });*/

	function check_hash_call_hash() {
		return jQuery( window.location.hash ).exists();
	}

	$.fn.exists = function () {
		return this.length !== 0;
	};
	var hashflag = false;

	function validateEmail(email) {
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test( email );
	}

    jQuery('#frm-rthd-subscribe-email-submit').submit(function( e ){
        jQuery( '.rthd-subscribe-email-submit' ).click();
        e.preventDefault();
    });

	jQuery( '.rthd-subscribe-email-submit' ).click(function () {
		jQuery( '.rthd-subscribe-validation' ).hide();
		jQuery( '.rthd-subscribe-validation' ).text( '' );
		jQuery( '#rthd-subscribe-email-spinner' ).show();
		var email = jQuery( '#rthd-subscribe-email' ).val();
		if ( ! validateEmail( email )) {
			jQuery( '.rthd-subscribe-validation' ).show();
			jQuery( '.rthd-subscribe-validation' ).html( 'Invalid Email' );
			jQuery( '#rthd-subscribe-email-spinner' ).hide();
			return;
		}
		var requestArray = {};
		requestArray.action = 'rtbiz_hd_add_subscriber_email';
		requestArray.email = email;
		requestArray.post_id = jQuery( '#post-id' ).val();
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					jQuery( '.rthd-subscribe-validation' ).show();
					jQuery( '.rthd-subscribe-validation' ).text( 'Added Successfully' );
					//jQuery( '.rthd-subscribe-validation' ).hide( 5000 );
					jQuery( '#rthd-subscribe-email' ).val( '' );
					//show participants lable
					if ( ! jQuery( '.rthd-participants' ).is( ":visible" )) {
						jQuery( '.rthd-participants' ).show();
					}
					if ( ! data.has_replied) {
						var htmlappend = '<div class="rthd-participant-container"><a title="' + data.display_name + '" class="rthd-last-reply-by" href="' + data.edit_link + '">' + data.avatar + ' </a><a href="javascript:;" class="rthd-participant-remove" data-email="' + email + '" data-post_id="' + jQuery( '#post-id' ).val() + '" >X</a></div>';
						jQuery( '.rthd-ticket-created-by').parent().after( htmlappend );
					}
					// hide box when person is added
					jQuery( '.rthd-add-people-box' ).hide();
				} else {
					if (data.msg.length > 0) {
						jQuery( '.rthd-subscribe-validation' ).show();
						jQuery( '.rthd-subscribe-validation' ).html( data.msg );
					} else {
						jQuery( '.rthd-subscribe-validation' ).html( 'Something went wrong' );
					}
					//jQuery( '.rthd-subscribe-validation' ).hide( 10000 );
				}
				jQuery( '#rthd-subscribe-email-spinner' ).hide();
			}
		});
	});

	var hashcheck = check_hash_call_hash();
	if ( ! hashcheck && window.location.hash.length !== 0) {
		hashflag = true;
		jQuery( '#followup-load-more' ).trigger( 'click' );
	}

	//front end ticket update
	//jQuery( '#rthd-status-list' ).hide();
	jQuery( '#rthd-change-status' ).click(function (e) {
		//jQuery( '#rthd-status-list' ).show();
		//jQuery( this ).hide();
	});
	jQuery( '#rthd-status-list' ).change(function (e) {
		var requestArray = {};
		requestArray.post_id = jQuery( '#post-id' ).val();
		requestArray.post_status = jQuery( '#rthd-status-list' ).val();
		requestArray.action = "rtbiz_hd_front_end_status_change";
		jQuery( '#status-change-spinner' ).show();
		jQuery.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					//jQuery( '#rthd-status-visiable' ).html( data.stauts_markup );
				}
				jQuery( '#status-change-spinner' ).hide();
			},
			error: function () {
				jQuery( '#status-change-spinner' ).hide();
				return false;
			}
		});
	});

	jQuery( '#ticket-add-fav' ).click(function (e) {
		e.preventDefault();
		var requestArray = {};
		requestArray.action = "rtbiz_hd_fav_ticket";
		requestArray.nonce = jQuery( '#rtbiz_hd_fav_tickets_nonce' ).val();
		requestArray.post_id = jQuery( '#post-id' ).val();

		jQuery.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					jQuery( '#ticket-add-fav span' ).toggleClass( 'rthd-gray' );
					jQuery( '#ticket-add-fav' ).attr( 'title', data.label );
				}
			},
			error: function () {
				return false;
			}
		});
	});

	jQuery( '#rthd-assignee-list' ).change(function (e) {
		assgine_request( jQuery( '#rthd-assignee-list' ).val() );
	});

	jQuery( '#rthd-product-list' ).change(function (e) {
		if (jQuery( '#rthd-product-list' ).val() === 0) {
			return false;
		}
		change_products( jQuery( '#rthd-product-list' ).val() );
	});

	function change_products(term_id) {
		var requestArray = {};
		requestArray.post_id = jQuery( '#post-id' ).val();
		requestArray.product_id = term_id;
		requestArray.action = "rtbiz_hd_front_end_product_change";
		jQuery( '#product-change-spinner' ).show();
		jQuery.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					jQuery( '#rthd-product-list' ).val( term_id );
				}
				jQuery( '#product-change-spinner' ).hide();
			},
			error: function () {
				jQuery( '#product-change-spinner' ).hide();
				return false;
			}
		});
	}

	function assgine_request(userid) {
		var requestArray = {};
		requestArray.post_id = jQuery( '#post-id' ).val();
		requestArray.post_author = userid;
		requestArray.action = "rtbiz_hd_front_end_assignee_change";
		jQuery( '#assignee-change-spinner' ).show();
		jQuery.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					if (jQuery( '.rthd-current-user-id' ).val() == userid) {
						jQuery( '.rt-hd-assign-me' ).hide();
						jQuery( '#rthd-assignee-list' ).val( userid );
					} else {
						jQuery( '.rt-hd-assign-me' ).show();
					}
					//jQuery( '#rthd-status-visiable' ).html( data.stauts_markup );
				}
				jQuery( '#assignee-change-spinner' ).hide();
			},
			error: function () {
				jQuery( '#assignee-change-spinner' ).hide();
				return false;
			}
		});
	}

	jQuery( '.rt-hd-assign-me' ).click(function (e) {
		e.preventDefault();
		assgine_request( jQuery( '.rthd-current-user-id' ).val() );
	});

	jQuery( '#rthd-ticket-watch-unwatch' ).click(function (e) {
		e.preventDefault();
		var requestArray = {};
		requestArray.post_id = jQuery( '#post-id' ).val();
		requestArray.watch_unwatch = jQuery( this ).attr( 'data-value' );
		requestArray.action = "rtbiz_hd_front_end_watch_unwatch";
		//jQuery('#watch-unwatch-spinner' ).show();
		jQuery.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: requestArray,
			success: function (data) {
				if (data.status) {
					//jQuery('#rthd-ticket-watch-unwatch').val( data.label );
					jQuery( '#rthd-ticket-watch-unwatch span' ).toggleClass( 'rthd-gray' );
					jQuery( '#rthd-ticket-watch-unwatch' ).attr( 'data-value', data.value );
					jQuery( '#rthd-ticket-watch-unwatch' ).attr( 'title', data.label );
				}
				//jQuery('#watch-unwatch-spinner' ).hide();
			},
			error: function () {
				//jQuery('#watch-unwatch-spinner' ).hide();
				return false;
			}
		});
	});
	jQuery( '.rthd-collapse-click' ).click(function (e) {
		e.preventDefault();
		jQuery( this ).closest( '.rt-hd-ticket-info' ).next().slideToggle();
		jQuery( 'span', this ).toggleClass( 'dashicons-arrow-up-alt2 dashicons-arrow-down-alt2' );
	});

	jQuery( '#rthd-add-contact' ).click(function (e) {
		if (jQuery( '.rthd-add-people-box' ).is( ':visible' )) {
			jQuery( '.rthd-add-people-box' ).hide();
		} else {
			jQuery( '.rthd-add-people-box' ).show();
            jQuery( '#rthd-subscribe-email').focus();
		}
		e.preventDefault();
		e.stopPropagation();
	});

	jQuery( document ).mouseup(function (e) {

		var container = jQuery( ".rthd-add-people-box" );
		// if the target of the click isn't the container...
		// ... nor a descendant of the container
		if ( ! container.is( e.target ) && container.has( e.target ).length === 0 && ! jQuery( '.rthd-add-contact-icon' ).is( e.target )) {
			if (container.is( ':visible' )) {
				container.hide();
			}
		}
	});

});
