/**
 * jQuery.browser.mobile (http://detectmobilebrowser.com/)
 *
 * jQuery.browser.mobile will be true if the browser is a mobile device
 *
 **/
(function(a){(jQuery.browser=jQuery.browser||{}).mobile=/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);


rthd_user_edit = rthd_user_edit[0];
var file_frame_ticket;
jQuery( document ).ready( function ( $ ) {

	jQuery('.rthd-scroll-up' ).click(function (e){
		e.preventDefault();
		$('html, body').animate({ scrollTop: 0 }, 'slow');
	});

	jQuery(window).scroll(function () {
		if (jQuery(this).scrollTop() > 500) {
			jQuery('.rthd-scroll-up').fadeIn();
		} else {
			jQuery('.rthd-scroll-up').fadeOut();
		}
	});

	function rthd_tinymce_set_content( id, text ) {
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
	}

	function rthd_tinymce_get_content( id ) {
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

	if ( ! jQuery.browser.mobile ) {
		$( '.rthd_sticky_div' ).stickyfloat( { duration: 400, delay: 3 } );
	}
	/*jQuery( '.followup-hash-url' ).click( function(e) {
		//e.preventDefault();
		jQuery(document).scrollTop( ( jQuery( window.location.hash ).offset().top ) - 100 );
	});
	jQuery(function() {
		jQuery(document).scrollTop( ( jQuery( window.location.hash ).offset().top ) - 100 );
	});*/

	function check_hash_call_hash(){
		return jQuery( window.location.hash ).exists();
	}

	$.fn.exists = function () {
		return this.length !== 0;
	};
	var hashflag = false;
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
		//jQuery('#editedticketcontent' ).html(jQuery(this ).closest('.ticketcontent' ).find('.rthd-comment-content' ).html());
		rthd_tinymce_set_content( 'editedticketcontent', jQuery(this).closest('.ticketcontent').find('.rthd-comment-content' ).data('content') );
	});

	jQuery('.close-edit-content' ).click(function(e){
		e.preventDefault();
		jQuery('#edit-ticket-data' ).slideToggle('slow');
		jQuery('#new-followup-form' ).show();
		jQuery(document).scrollTop( ( jQuery('.ticketcontent').offset().top ) - 50 );
	});
	function validateEmail( email ) {
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test( email );
	}
	jQuery('.rthd-subscribe-email-submit' ).click( function ( ) {
		jQuery('.rthd-subscribe-validation' ).hide();
		jQuery('#rthd-subscribe-email-spinner' ).show();
		var email = jQuery('#rthd-subscribe-email' ).val();
		if ( ! validateEmail(email)){
			jQuery('.rthd-subscribe-validation' ).show();
			jQuery('.rthd-subscribe-validation' ).html('Invalid Email');
			jQuery('#rthd-subscribe-email-spinner' ).hide();
			return ;
		}
		var requestArray = new Object();
		requestArray['action'] = 'rt_hd_add_subscriber_email';
		requestArray['email'] = email;
		requestArray['post_id']= jQuery('#post-id' ).val();
		jQuery.ajax( {
			             url: ajaxurl,
			             type: 'POST',
			             dataType: 'json',
			             data: requestArray,
			             success: function ( data ) {
								if ( data.status ){
									jQuery('.rthd-subscribe-validation' ).show();
									jQuery('.rthd-subscribe-validation' ).text('Added Successfully!');
									jQuery('#rthd-subscribe-email' ).val('');
								}
				                else{
									if ( data.msg.length > 0 ){
										jQuery('.rthd-subscribe-validation' ).show();
										jQuery('.rthd-subscribe-validation' ).html(data.msg);
									} else{
										jQuery('.rthd-subscribe-validation' ).html('Something went wrong!');
									}
								}
				                jQuery('#rthd-subscribe-email-spinner' ).hide();
			             }
		});
	});

	jQuery('#edit-ticket-content-click' ).click(function(){
		jQuery('#edit-ticket-data' ).slideToggle('slow');
		jQuery('#new-followup-form' ).hide();
		var requestArray = new Object();
		requestArray['action']= 'rthd_add_new_ticket_ajax';
		requestArray['post_id']= jQuery('#post-id' ).val();
		var content = rthd_tinymce_get_content( 'editedticketcontent' );
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
				                jQuery('.edit-ticket-link' ).closest('.ticketcontent' ).find('.rthd-comment-content' ).html( rthd_tinymce_get_content('editedticketcontent') );
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

	var commentid;
	jQuery("#delfollowup" ).click(function(e) {
		var r = confirm( "Are you sure you want to remove this Followup?" );
		if ( r != true ) {
			e.preventDefault();
			return false;
		}
		jQuery(this).attr('disabled','disabled');
		jQuery('#edithdspinner' ).show();
		postid= jQuery('#post-id' ).val();
		jQuery.ajax( {
			url: ajaxurl,
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
				alert( "error in remove " );
				jQuery('#edithdspinner' ).hide();
				jQuery("#delfollowup" ).removeAttr('disabled');
			}
		} );
	});

	jQuery('.close-edit-followup' ).click(function (e){
		e.preventDefault();
		jQuery('#dialog-form' ).slideToggle('slow');
		jQuery('#new-followup-form' ).show();
		jQuery(document).scrollTop( ( jQuery('#comment-'+commentid ).offset().top ) - 50 );
	});

	jQuery( document ).on('click', '.editfollowuplink',function(e){
		e.preventDefault();
		var select =jQuery(this ).parents();
		rthd_tinymce_set_content( 'editedfollowupcontent', jQuery(this).parents().siblings('.rthd-comment-content').data('content') );
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
		var content =  rthd_tinymce_get_content( 'editedfollowupcontent' );
		if (! content){
			alert("Please enter comment");
			return false;
		}
		if (content === jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).data('content') ){
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
		requestArray['followup_private']= jQuery('#edit-private').val() ;
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
					alert("Sorry something went wrong!");
					jQuery('#edithdspinner' ).hide();
					jQuery("#editfollowup" ).removeAttr('disabled');
				}
			} );
	});

	jQuery(document).on('click', '.rthd_delete_attachment', function (e) {
		e.preventDefault();
		jQuery(this).parent().remove();
	});


	jQuery( "#savefollwoup" ).click( function () {
		var flagspinner = false;
		jQuery('#hdspinner' ).show();
		jQuery(this).attr('disabled','disabled');
		var followuptype = jQuery( "#followup-type" ).val();

		if ( ! jQuery( "#ticket_unique_id" ).val()) {
			alert('Please publish ticket before adding followup! :( ');
			jQuery( '#hdspinner' ).hide();
			jQuery(this).removeAttr('disabled');
			return false;
		};
		if ( ! rthd_tinymce_get_content( 'followupcontent' ) ) {
			alert( "Please input followup." );
			jQuery( '#hdspinner' ).hide();
			jQuery(this).removeAttr('disabled');
			return false;
		}
		var formData = new FormData();
		formData.append("private_comment", jQuery('#add-private-comment').val());
		formData.append("followup_ticket_unique_id", jQuery('#ticket_unique_id').val());
		formData.append("post_type", jQuery('#followup_post_type').val());
		formData.append("action", 'rthd_add_new_followup_front');
		formData.append("followuptype", jQuery('#followuptype').val());
		formData.append("follwoup-time", jQuery('#follwoup-time').val());
		formData.append("followup_content", rthd_tinymce_get_content( 'followupcontent' ) );
		var files = jQuery('#attachemntlist')[0];
		jQuery.each(jQuery("#attachemntlist")[0].files, function(i, file) {
			formData.append('attachemntlist['+i+']', file);
		});
        if(jQuery('#rthd_keep_status')){
            formData.append("rthd_keep_status", jQuery('#rthd_keep_status').is(':checked'));
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
		             var newcomment=data.comment_content;
		             //console.log(newcomment);
		             jQuery('#chat-UI' ).append(newcomment);
		             // below code is for front end side bar
		             jQuery('#rthd-assignee-list' ).val(data.assign_value);
		             if( jQuery('#rthd-status-list' ).length ){
			             jQuery('#rthd-status-list' ).val(data.post_status);
		             } else if (jQuery('#rthd-status-visiable' ).length ){
			             jQuery('#rthd-status-visiable' ).html(data.post_status);
		             }
		             jQuery('.rt-hd-ticket-last-reply' ).html(data.last_reply);
		             // front end code end
		             rthd_tinymce_set_content( 'followupcontent', '' );
		             jQuery('#add-private-comment' ).val(10);
		             $("#attachemntlist").val('');
	 				 $("#clear-attachemntlist").hide();

                     if (data.ticket_status=='answered'){
                         if(jQuery('#rthd_keep_status')){
                             jQuery('#rthd_keep_status').parent().hide();
                         }
                     } else {
	                     if ( jQuery('#rthd_keep_status' ).length > 0 ) {
		                     jQuery('#rthd_keep_status' ).prop("checked",false);
	                     }
                     }
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
					             if ( check_hash_call_hash() && hashflag ){
						             jQuery(document).scrollTop( ( jQuery( window.location.hash ).offset().top ) - 50 );
					             }
				             }
				             jQuery('#load-more-hdspinner' ).hide();
			             },
			             error: function(){
				             jQuery('#load-more-hdspinner' ).hide();
				             return false;
			             }
		             });

	});
	var hashcheck = check_hash_call_hash();
	if ( !hashcheck  && window.location.hash.length !== 0 ){
		hashflag = true;
		jQuery( '#followup-load-more' ).trigger( 'click' );
	}

	//front end ticket update
	//jQuery( '#rthd-status-list' ).hide();
	jQuery( '#rthd-change-status' ).click(function (e){
		//jQuery( '#rthd-status-list' ).show();
		//jQuery( this ).hide();
	});
	jQuery( '#rthd-status-list' ).change(function (e){
		var requestArray = new Object();
		requestArray['post_id'] =  jQuery('#post-id' ).val();
		requestArray['post_status'] =  jQuery('#rthd-status-list' ).val();
		requestArray["action"] = "front_end_status_change";
		jQuery('#status-change-spinner' ).show();
		jQuery.ajax( {
			             url: ajaxurl,
			             dataType: "json",
			             type: 'post',
			             data: requestArray,
			             success: function ( data ) {
				             if (data.status) {
					            //jQuery( '#rthd-status-visiable' ).html( data.stauts_markup );
				             }
				             jQuery('#status-change-spinner' ).hide();
			             },
			             error: function(){
				             jQuery('#status-change-spinner' ).hide();
				             return false;
			             }
		             });
	});

	jQuery( '#rthd-assignee-list' ).change(function (e){
		assgine_request( jQuery('#rthd-assignee-list' ).val() );
	});

	function assgine_request( userid ){
		var requestArray = new Object();
		requestArray['post_id'] =  jQuery('#post-id' ).val();
		requestArray['post_author'] =  userid;
		requestArray["action"] = "front_end_assignee_change";
		jQuery('#assignee-change-spinner' ).show();
		jQuery.ajax( {
			             url: ajaxurl,
			             dataType: "json",
			             type: 'post',
			             data: requestArray,
			             success: function ( data ) {
				             if (data.status) {
					             if ( jQuery('.rthd-current-user-id' ).val() == userid ){
						             jQuery('.rt-hd-assign-me' ).hide();
						             jQuery('#rthd-assignee-list' ).val( userid );
					             } else {
						             jQuery('.rt-hd-assign-me' ).show();
					             }
					             //jQuery( '#rthd-status-visiable' ).html( data.stauts_markup );
				             }
				             jQuery('#assignee-change-spinner' ).hide();
			             },
			             error: function(){
				             jQuery('#assignee-change-spinner' ).hide();
				             return false;
			             }
		             });
	}

	jQuery('.rt-hd-assign-me' ).click(function (e){
		e.preventDefault();
		assgine_request( jQuery('.rthd-current-user-id' ).val() );
	});

	jQuery( '#rthd-ticket-watch-unwatch' ).click(function (e){
		var requestArray = new Object();
		requestArray['post_id'] =  jQuery('#post-id' ).val();
		requestArray['watch_unwatch'] =  jQuery(this).attr('data-value');
		requestArray["action"] = "front_end_ticket_watch_unwatch";
		jQuery('#watch-unwatch-spinner' ).show();
		jQuery.ajax( {
             url: ajaxurl,
             dataType: "json",
             type: 'post',
             data: requestArray,
             success: function ( data ) {
            	 if (data.status) {
		            jQuery('#rthd-ticket-watch-unwatch').val( data.label );
		            jQuery('#rthd-ticket-watch-unwatch').attr('data-value', data.value);
		         }
	             jQuery('#watch-unwatch-spinner' ).hide();
             },
             error: function(){
            	 jQuery('#watch-unwatch-spinner' ).hide();
	             return false;
             }
         });
	});
} );

/*(function($) {
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
})(jQuery);*/
