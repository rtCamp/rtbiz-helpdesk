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

	jQuery(document).on('click', '.rthd_delete_attachment', function (e) {
		e.preventDefault();
		jQuery(this).parent().remove();
	});

	jQuery(document).on( 'click', '.add-followup-attachment', function (e) {
		e.preventDefault();
		if (file_frame_ticket) {
			file_frame_ticket.open();
			return;
		}
		file_frame_ticket = wp.media.frames.file_frame = wp.media({
			title: jQuery(this).data('uploader_title'),
			searchable: true,
			button: {
				text: 'Attach Selected Files'
			},
			multiple: true // Set to true to allow multiple files to be selected
		});
		file_frame_ticket.on('select', function () {
			var selection = file_frame_ticket.state().get('selection');
			var strAttachment = '';
			selection.map(function (attachment) {
				attachment = attachment.toJSON();
				strAttachment = '<li data-attachment-id="' + attachment.id + '" class="attachment-item">';
				strAttachment += '<a href="#" class="rthd_delete_attachment">x</a>';
				strAttachment += '<a target="_blank" href="' + attachment.url + '"><img height="20px" width="20px" src="' + attachment.icon + '" > ' + attachment.filename + '</a>';
				strAttachment += '<input type="hidden" name="attachemntitem[]" value="' + attachment.id + '" /></div>';

				jQuery("#attachmentList").append(strAttachment);

				// Do something with attachment.id and/or attachment.url here
			});
			// Do something with attachment.id and/or attachment.url here
		});
		file_frame_ticket.open();
	});

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
		             jQuery('#attachmentList' ).html('');
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