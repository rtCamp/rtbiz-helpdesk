rtcrm_user_edit = rtcrm_user_edit[0];
jQuery(document).ready(function($) {

	function format_date_moment() {
		$(".moment-from-now").each(function() {

			if($(this).is(".comment-date"))
				$(this).html(moment(new Date($(this).attr("title"))).fromNow());
			else
				$(this).html(moment(new Date($(this).html())).fromNow());
		});
	}

	format_date_moment();

	$('.rtcrm_sticky_div').stickyfloat( {duration: 400, delay: 3, offsetY:40} );

	$("#savefollwoup").click(function(){
		var followuptype =$("#followup-type").val();

		var requestArray= new Object();

		requestArray['post_type'] = rtcrm_post_type;
		requestArray["comment_id"]=  $("#edit-comment-id").val();
		requestArray["action"] = "rtcrm_add_new_followup_front";
		requestArray["followuptype"] = 'note';

		requestArray["followup_lead_unique_id"] = $("#lead_unique_id").val();
		requestArray["follwoup-time"]=$("#follwoup-time").val();

		if(jQuery("#followup_content").val()=="" && typeof tinyMCE != 'undefined' ){
			jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
		}
		requestArray["followup_content"]=$("#followup_content").val();
		if(requestArray["followup_content"]==""){
			alert("Please Type Content Atleast");
			return false;
		}
		requestArray["attachemntlist"]= new Array();
		$("#attachmentList input").each(function(){
			requestArray["attachemntlist"].push($(this).val());
		})


				requestArray['user_edit'] = rtcrm_user_edit;
				$.ajax({
					url: ajaxurl,
					dataType: "json",
					type: 'post',
					data: requestArray,
					success: function(data) {
						if (data.status) {
							jQuery("#followup_content").val('');
							$("#commentlist").prepend(data.data);
							format_date_moment();
							$("#commentlist .comment-wrapper").filter(":first").show();
							if(!$('.accordion-expand-all').parent().is(':visible'))
								$('.accordion-expand-all').parent().show();
						} else {
							alert(data.message);
						}
					}
				});

	});

	$("#commentlist .comment-wrapper").filter(":first").show();

	$(document).on("click", "#commentlist .comment-header", function(e) {
		var panel = $(this).next();
		var isOpen = panel.is(':visible');

		// open or close as necessary
		panel[isOpen? 'hide': 'show']()
			// trigger the correct custom event
			.trigger(isOpen? 'hide': 'show');

		// stop the link from causing a pagescroll
		return false;
	});

	$(document).on("click", '.accordion-expand-all',function (e) {

		var contentAreas = $("#commentlist .comment-wrapper");

		e.preventDefault();
		var isAllOpen = $(this).data('isallopen');

		contentAreas[isAllOpen? 'hide': 'show']()
			.trigger(isAllOpen? 'hide': 'show');

	})

	$(document).on({
		// whenever we open a panel, check to see if they're all open
		// if all open, swap the button to collapser
		show: function(){
			var isAllOpen = !$("#commentlist .comment-wrapper").is(':hidden');
			if(isAllOpen){
				$('.accordion-expand-all').html('<i class="general foundicon-up-arrow" title="Collapse All"></i>')
					.data('isallopen', true);
			}
		},
		// whenever we close a panel, check to see if they're all open
		// if not all open, swap the button to expander
		hide: function(){
			var isAllOpen = !$("#commentlist .comment-wrapper").is(':hidden');
			if(!isAllOpen){
				$('.accordion-expand-all').html('<i class="general foundicon-down-arrow" title="Expand All"></i>')
				.data('isallopen', false);
			}
		}
	}, "#commentlist .comment-wrapper");

	var page = 1;
	$(document).on('click', '#load_more_btn', function(e) {
		e.preventDefault();
		$.post( ajaxurl, {
			action: 'rtcrm_get_lead_comments_ajax',
			page: page,
			lead_unique_id: $("#lead_unique_id").val()
		}, function(data) {
			data = JSON.parse(data);
			if(typeof data.status != undefined && data.status == 'success') {
				$('#commentlist').append(data.data);
				page++;
				if(data.more==false)
                    $("#load_more_btn").hide();
				format_date_moment();
			} else {
				alert(data);
			}
		});
	});
});