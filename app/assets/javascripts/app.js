rthd_user_edit = rthd_user_edit[0];
jQuery( document ).ready( function ( $ ) {

	jQuery('#submit-ajax' ).click(function (){
		var options = {
			//	target:        '#output1',      // target element(s) to be updated with server response
			beforeSubmit:  showRequest,     // pre-submit callback
			success:       showResponse,    // post-submit callback
			url:    ajaxurl                 //  ajaxurl is always defined in the admin header and points to admin-ajax.php
		};
		jQuery('#thumbnail_upload').ajaxSubmit(options );
	});

	function showRequest(formData, jqForm, options) {
		//do extra stuff before submit like disable the submit button
		jQuery('#output1').html('Sending...');
		jQuery('#submit-ajax').attr("disabled", "disabled");
	}
	function showResponse(responseText, statusText, xhr, jQueryform)  {
		var responseText = jQuery.parseJSON(responseText);
		jQuery( '#output1' ).text( responseText.msg );
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
			jQuery('#submit-ajax' ).removeAttr('disabled');
		}
	}

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

	$( '.rthd_sticky_div' ).stickyfloat( {duration: 400, delay: 3, offsetY: 40} );

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
		jQuery("#delfollowup" ).click(function() {
			var r = confirm( "Are you sure you want to remove this Followup?" );
			if ( r != true ) {
				e.preventDefault();
				return false;
			}
			jQuery.ajax( { url: ajaxurl,
				             type: 'POST',
				             dataType: 'json',
				             data: {
					             action: 'helpdesk_delete_followup',
					             comment_id: commentid,
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
				             },
				             error: function ( xhr, textStatus, errorThrown ) {
					             alert( "error in remove " );
				             }
			             } );

		});

	jQuery( 'li.self .messages' ).click( function () {
		jQuery('#edited_followup_content' ).val( jQuery(this).find('p').text().replace(/\s+/g, " ") );

		commentid=jQuery(this).find('#followup-id' ).val();
		jQuery("#dialog-form").dialog().dialog("close");
		jQuery( "#dialog-form" ).dialog( "open" );

	} );

		jQuery("#editfollowup" ).click(function(){
			var requestArray = new Object();
			if (! jQuery('#edited_followup_content' ).val()){
				alert("Please enter comment");
				return false;
			}
			if (jQuery('#edited_followup_content' ).val().replace(/\s+/g, " ") === jQuery('#comment-'+commentid ).find('p' ).val().replace(/\s+/g, " ") ){
				alert('You have not edited comment! :/');
				return false;
			}
			requestArray['post_type'] = rthd_post_type;
			requestArray["comment_id"] = commentid ;
			requestArray["action"] = "rthd_add_new_followup_ajax";
			requestArray['followuptype']="comment";
			requestArray['followup_ticket_unique_id']=jQuery('#ticket_unique_id' ).val();
			requestArray['followup_private']='no';
			requestArray['followup_post_id']=jQuery('#post-id' ).val();
			//requestArray["followuptype"] = followuptype;
			//requestArray["followup_post_id"] = jQuery( "#ticket_id" ).val();
			//requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();
			requestArray["followup_content"]=jQuery('#edited_followup_content' ).val().replace(/\s+/g, " ");

			jQuery.ajax(
				{
					url: ajaxurl,
					dataType: "json",
					type: 'post',
					data: requestArray,
					success: function ( data ) {
						if ( data.status ) {
							jQuery('#comment-'+commentid ).find('p' ).text(jQuery('#edited_followup_content' ).val().replace(/\s+/g, " "));
							jQuery("#dialog-form").dialog().dialog("close");
						} else {
							alert( data.message )
						}
					},
					error: function (data){
						alert("Sorry something went wrong!");
					}
				} );
		});


		jQuery( "#savefollwoup" ).click( function () {
			var followuptype = jQuery( "#followup-type" ).val();

			var requestArray = new Object();
			requestArray['post_type'] = rthd_post_type;
			requestArray["comment_id"] = jQuery( "#edit-comment-id" ).val();
			requestArray["action"] = "rthd_add_new_followup_front";
			requestArray["followuptype"] = '';//'note';

			requestArray["followup_ticket_unique_id"] = jQuery( "#ticket_unique_id" ).val();
			if ( ! requestArray["followup_ticket_unique_id"]) {
				alert('Please publish ticket before adding followup! :( ');
				return false;
			};
			requestArray["follwoup-time"] = jQuery( "#follwoup-time" ).val();

			if ( jQuery( "#followup_content" ).val() == "" && typeof tinyMCE != 'undefined' ) {
				jQuery( "#followup_content" ).val( tinyMCE.get( 'followup_content' ).getContent() );
			}
			requestArray["followup_content"] = jQuery( "#followup_content" ).val();
			if ( ! requestArray["followup_content"] ) {
				alert( "Please input followup :/" );
				return false;
			}
			requestArray["attachemntlist"] = new Array();
			jQuery( "#attachmentList input" ).each( function () {
				requestArray["attachemntlist"].push( jQuery( this ).val() );
			});


			console.log(requestArray);
			requestArray['user_edit'] = rthd_user_edit;
			jQuery.ajax( {
				             url: ajaxurl,
				             dataType: "json",
				             type: 'post',
				             data: requestArray,
				             success: function ( data ) {
					             if ( data.status ) {


						             /* jQuery( "#commentlist" ).prepend( data.data );
						              jQuery( ".moment-from-now" ).each( function () {
						              s
						              if ( jQuery( this ).is( ".comment-date" ) ) {
						              jQuery( this ).html( moment( new Date( jQuery( this ).attr( "title" ) ) ).fromNow() );
						              } else {
						              jQuery( this ).html( moment( new Date( jQuery( this ).html() ) ).fromNow() );
						              }
						              } );*/
						             var newcomment=" <li class='self'> <div class='avatar'> " + jQuery("#user-avatar" ).val() + " </div> <div class='messages'> <p>" + jQuery('#followup_content' ).val() + " </p> <time title='just now' ><span title='"+ jQuery('#user_email').val() +"'>" +jQuery('#user-name' ).val()+ "</span>  • now </time> </div> </li>";
						             console.log(newcomment);
						             jQuery('#chat-UI' ).prepend(newcomment);
						             jQuery( "#followup_content" ).val( '' );
						             /*jQuery( "#commentlist .comment-wrapper" ).filter( ":first" ).show();
						              if ( ! jQuery( '.accordion-expand-all' ).parent().is( ':visible' ) ) {
						              jQuery( '.accordion-expand-all' ).parent().show();
						              }*/
					             } else {
						             alert( data.message );
					             }
				             }
			             } );
		} );

} );