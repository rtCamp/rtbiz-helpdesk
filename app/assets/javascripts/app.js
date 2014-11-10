rthd_user_edit = rthd_user_edit[0];
jQuery( document ).ready( function ( $ ) {


	function format_date_moment() {
		$( ".moment-from-now" ).each( function () {

			if ( $( this ).is( ".comment-date" ) ) {
				$( this ).html( moment( new Date( $( this ).attr( "title" ) ) ).fromNow() );
			} else {
				$( this ).html( moment( new Date( $( this ).html() ) ).fromNow() );
			}
		} );
	}

	format_date_moment();

	$( '.rthd_sticky_div' ).stickyfloat( { duration: 400, delay: 3 } );

	$( "#commentlist .comment-wrapper" ).filter( ":first" ).show();

	$( document ).on( "click", "#commentlist .comment-header", function ( e ) {
		var panel = $( this ).next();
		var isOpen = panel.is( ':visible' );

		// open or close as necessary
		panel[isOpen ? 'hide' : 'show']()// trigger the correct custom event
			.trigger( isOpen ? 'hide' : 'show' );

		// stop the link from causing a pagescroll
		return false;
	} );

	$( document ).on( "click", '.accordion-expand-all', function ( e ) {

		var contentAreas = $( "#commentlist .comment-wrapper" );

		e.preventDefault();
		var isAllOpen = $( this ).data( 'isallopen' );

		contentAreas[isAllOpen ? 'hide' : 'show']().trigger( isAllOpen ? 'hide' : 'show' );

	} )

	$( document ).on( {
		                  // whenever we open a panel, check to see if they're all open
		                  // if all open, swap the button to collapser
		                  show: function () {
			                  var isAllOpen = ! $( "#commentlist .comment-wrapper" ).is( ':hidden' );
			                  if ( isAllOpen ) {
				                  $( '.accordion-expand-all' ).html( '<i class="general foundicon-up-arrow" title="Collapse All"></i>' ).data( 'isallopen', true );
			                  }
		                  },
		                  // whenever we close a panel, check to see if they're all open
		                  // if not all open, swap the button to expander
		                  hide: function () {
			                  var isAllOpen = ! $( "#commentlist .comment-wrapper" ).is( ':hidden' );
			                  if ( ! isAllOpen ) {
				                  $( '.accordion-expand-all' ).html( '<i class="general foundicon-down-arrow" title="Expand All"></i>' ).data( 'isallopen', false );
			                  }
		                  }
	                  }, "#commentlist .comment-wrapper" );


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
			jQuery.ajax( { url: ajaxurl,
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

	jQuery( document ).on('click', '.editfollowuplink',function(){
		var select =jQuery(this ).parents().parents();
		jQuery('#edited_followup_content' ).val(jQuery(this ).parents().siblings('.rthd-comment-content' ).text().trim());
		commentid=select.find('#followup-id' ).val();
		var that = select.find( '#is-private-comment' ).val();
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
	function showRequest(formData, jqForm, options) {
		//do extra stuff before submit like disable the submit button
	}
	function showResponse(responseText, statusText, xhr, jQueryform)  {
		var responseText = jQuery.parseJSON(responseText);
		if(responseText.status ) {
			var tempname;
			if (responseText.name.length > 20 ){
				tempname = responseText.name.substring(0,12) + "...";
			}else{
				tempname= responseText.name;
			}
			var attachhtml = "<div class='large-12 mobile-large-3 columns attachment-item' data-attachment-id='"+ responseText.attach_id +"'> <a class='rthd_attachment' title='' target='_blank' href='"+responseText.url+"'> <img height='20px' width='20px' src='"+responseText.img+"'><span title='"+responseText.name+"'> "+ tempname+" </span> </a><input type='hidden' name='attachment[]' value='"+ responseText.attach_id +"'></div>";
			console.log(attachhtml);
			jQuery('#attachment-files').prepend(attachhtml);
			var control= jQuery('#thumbnail');
			control.replaceWith( control = control.clone( true ) );
			jQuery('#hdspinner' ).hide();
		}
	}


		jQuery( "#savefollwoup" ).click( function () {
			var flagspinner = false;
			jQuery('#hdspinner' ).show();
			jQuery(this).attr('disabled','disabled');

			if ( jQuery('#thumbnail' ).val() ){
				var options = {
					beforeSubmit:  showRequest,     // pre-submit callback
					success:       showResponse,    // post-submit callback
					url:    ajaxurl                 //  ajaxurl is always defined in the admin header and points to admin-ajax.php
				};
				flagspinner=true;
				jQuery('#thumbnail_upload').ajaxSubmit(options );
			}
			var followuptype = jQuery( "#followup-type" ).val();

			var requestArray = new Object();
			requestArray['post_type'] = rthd_post_type;
			requestArray["comment_id"] = jQuery( "#edit-comment-id" ).val();
			requestArray["action"] = "rthd_add_new_followup_front";
			requestArray["followuptype"] = '';//'note';
			requestArray['private_comment']= jQuery('#add-private-comment').is(':checked') ;

			requestArray["followup_ticket_unique_id"] = jQuery( "#ticket_unique_id" ).val();
			if ( ! requestArray["followup_ticket_unique_id"]) {
				alert('Please publish ticket before adding followup! :( ');
				jQuery( '#hdspinner' ).hide();
				jQuery(this).removeAttr('disabled');
				return false;
			};
			requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();

			requestArray["followup_content"] = jQuery( "#followup_content" ).val();
			if ( ! requestArray["followup_content"] ) {
				alert( "Please input followup :/" );
				jQuery( '#hdspinner' ).hide();
				jQuery(this).removeAttr('disabled');
				return false;
			}
			requestArray["attachemntlist"] = new Array();
			jQuery( "#attachmentList input" ).each( function () {
				requestArray["attachemntlist"].push( jQuery( this ).val() );
			});


			//console.log(requestArray);
			requestArray['user_edit'] = rthd_user_edit;
			jQuery.ajax( {
				             url: ajaxurl,
				             dataType: "json",
				             type: 'post',
				             data: requestArray,
				             success: function ( data ) {
					             if ( data.status ) {
						             var newcomment=data.comment_content;
						             //console.log(newcomment);
						             jQuery('#chat-UI' ).prepend(newcomment);
						             jQuery( "#followup_content" ).val( '' );
						             jQuery('#add-private-comment' ).prop('checked',false );
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

} );