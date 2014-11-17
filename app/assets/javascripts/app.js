rthd_user_edit = rthd_user_edit[0];
var file_frame_ticket;
jQuery( document ).ready( function ( $ ) {

	$( '.rthd_sticky_div' ).stickyfloat( { duration: 400, delay: 3 } );

	$( "#commentlist .comment-wrapper" ).filter( ":first" ).show();

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
				post_id : postid,
			},
			success: function ( data ) {
				if ( data.status ) {
					jQuery( "#comment-" + commentid ).fadeOut( 500, function () {
			            jQuery( this ).remove();
					} );
					jQuery("#dialog-form").dialog().dialog("close");
				} else {
					alert( "error in delete comment from server" );
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

	jQuery( document ).on('click', '.editfollowuplink',function(e){
		e.preventDefault();
		var select =jQuery(this ).parents();
		jQuery('#edited_followup_content' ).val(jQuery(this ).parents().siblings('.rthd-comment-content' ).html().trim());
		commentid=select.siblings('#followup-id' ).val();
		var that = select.siblings( '#is-private-comment' ).val();
		if (that && that=='true' || that == true){
			jQuery('#edit-private' ).prop('checked',true);
		}
		else{
			jQuery('#edit-private' ).prop('checked',false);
		}
		jQuery("#dialog-form").dialog().dialog("close");
		jQuery( "#dialog-form" ).dialog({
	        width :600,
            height:300
        });
		jQuery( "#dialog-form" ).dialog( "open" );

	} );

	jQuery("#editfollowup" ).click(function(){
		var requestArray = new Object();
		if (! jQuery('#edited_followup_content' ).val()){
			alert("Please enter comment");
			return false;
		}
		if (jQuery('#edited_followup_content' ).val().replace(/\s+/g, " ") === jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).val().replace(/\s+/g, " ") ){
			alert('You have not edited comment! :/');
			return false;
		}
		jQuery('#edithdspinner' ).show();
		jQuery(this).attr('disabled','disabled');

		requestArray['post_type'] = rthd_post_type;
		requestArray["comment_id"] = commentid ;
		requestArray["action"] = "rthd_add_new_followup_ajax";
		requestArray['followuptype']="comment";
		requestArray['followup_ticket_unique_id']=jQuery('#ticket_unique_id' ).val();
		//requestArray['followup_private']='no';
		requestArray['followup_post_id']=jQuery('#post-id' ).val();
		requestArray['followup_private']= jQuery('#edit-private').is(':checked') ;
		requestArray["followuptype"] = 'comment';
		//requestArray["followup_post_id"] = jQuery( "#ticket_id" ).val();
		//requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();
		requestArray["followup_content"]=jQuery('#edited_followup_content' ).val();
		jQuery.ajax(
			{
				url: ajaxurl,
				dataType: "json",
				type: 'post',
				data: requestArray,
				success: function ( data ) {
					if ( data.status ) {
						jQuery('#comment-'+commentid ).find('.rthd-comment-content' ).html(data.comment_content);
						jQuery('#comment-'+commentid ).find( '#is-private-comment' ).val(data.private);
						jQuery("#dialog-form").dialog().dialog("close");
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
		if ( ! jQuery( "#followup_content" ).val()) {
			alert( "Please input followup :/" );
			jQuery( '#hdspinner' ).hide();
			jQuery(this).removeAttr('disabled');
			return false;
		}
		var formData = new FormData(jQuery('#add_followup_form')[0]);
		formData.append("private_comment", jQuery('#add-private-comment').is(':checked'));
		jQuery.ajax( {
             url: ajaxurl,
             dataType: "json",
             type: 'POST',
             data: formData,
			 cache: false,
			 contentType: false,
			 processData: false,
			 async: false,
             success: function ( data ) {
	             if ( data.status ) {
		             var newcomment=data.comment_content;
		             //console.log(newcomment);
		             jQuery('#chat-UI' ).append(newcomment);
		             jQuery( "#followup_content" ).val( '' );
		             jQuery('#add-private-comment' ).prop('checked',false );
		             var control = jQuery('#attachemntlist' );
		             control.replaceWith( control = control.clone( true ) );
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
				             }
				             jQuery('#load-more-hdspinner' ).hide();
			             },
			             error: function(){
				             jQuery('#load-more-hdspinner' ).hide();
				             return false;
			             },
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
