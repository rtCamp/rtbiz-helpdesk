/*! 
 * rtBiz Helpdesk JavaScript Library 
 * @package rtBiz Helpdesk 
 */jQuery(document).ready(function(){var a={rthd_tinymce_set_content:function(a,b){if("undefined"!=typeof tinymce){var c=tinymce.get(a);return c&&c instanceof tinymce.Editor?(c.setContent(b),c.save({no_events:!0})):jQuery("#"+a).val(b),!0}return!1},rthd_tinymce_get_content:function(a){if("undefined"!=typeof tinymce){var b=tinymce.get(a);return b&&b instanceof tinymce.Editor?b.getContent():jQuery("#"+a).val()}return""},init:function(){a.initAddNewFollowUp(),a.initEditFollowUp(),a.initLoadAll(),a.initEditContent()},initAddNewFollowUp:function(){function b(){return jQuery("#hdspinner").show(),jQuery("#ticket_unique_id").val()?a.rthd_tinymce_get_content("followupcontent")?!0:(alert("Please input followup."),jQuery("#hdspinner").hide(),!1):(alert("Please publish ticket before adding followup! :( "),jQuery("#hdspinner").hide(),!1)}function c(b){jQuery("#savefollwoup").attr("disabled","disabled");var c=(jQuery("#followup-type").val(),new FormData),e=jQuery("input[name='private_comment']:checked");e=e.length?e.val():jQuery("input[name='private_comment']").val(),c.append("private_comment",e),c.append("followup_ticket_unique_id",jQuery("#ticket_unique_id").val()),c.append("post_type",jQuery("#followup_post_type").val()),c.append("action","rthd_add_new_followup_front"),c.append("followuptype",jQuery("#followuptype").val()),c.append("follwoup-time",jQuery("#follwoup-time").val()),c.append("followup_content",a.rthd_tinymce_get_content("followupcontent")),c.append("followup_attachments",d),jQuery("#rthd_keep_status")&&c.append("rthd_keep_status",jQuery("#rthd_keep_status").is(":checked")),jQuery.ajax({url:ajaxurl,dataType:"json",type:"POST",data:c,cache:!1,contentType:!1,processData:!1,success:function(b){b.status?(jQuery("#chat-UI").append(b.comment_content),jQuery("#rthd-assignee-list").length&&jQuery("#rthd-assignee-list").val(b.assign_value),jQuery("#rthd-status-list").length?jQuery("#rthd-status-list").val(b.post_status):jQuery("#rthd-status-visiable").length&&jQuery("#rthd-status-visiable").html(b.post_status),jQuery(".rt-hd-ticket-last-reply-value").html(b.last_reply),a.rthd_tinymce_set_content("followupcontent",""),jQuery("#add-private-comment").val(10),d=[],"answered"==b.ticket_status?jQuery("#rthd_keep_status")&&jQuery("#rthd_keep_status").parent().hide():jQuery("#rthd_keep_status").length>0&&jQuery("#rthd_keep_status").prop("checked",!1),jQuery("#hdspinner").hide()):(console.log(b.message),jQuery("#hdspinner").hide()),jQuery("#savefollwoup").removeAttr("disabled")}})}$ticket_unique_id=jQuery("#ticket_unique_id").val();var d=[],e=!1;if("undefined"!=typeof plupload){var f=new plupload.Uploader({runtimes:"html5,flash,silverlight,html4",browse_button:"attachemntlist",url:ajaxurl,multipart:!0,multipart_params:{action:"rthd_upload_attachment",followup_ticket_unique_id:$ticket_unique_id},container:document.getElementById("rthd-attachment-container"),filters:{max_file_size:"10mb"},flash_swf_url:"Moxie.swf",silverlight_xap_url:"Moxie.xap",init:{PostInit:function(){document.getElementById("followup-filelist").innerHTML="",document.getElementById("savefollwoup").onclick=function(){b()&&f.start()}},FilesAdded:function(a,b){plupload.each(b,function(a){document.getElementById("followup-filelist").innerHTML+='<div id="'+a.id+'"><a href="#" class="followup-attach-remove"><span class="dashicons dashicons-dismiss"></span></a> '+a.name+" ("+plupload.formatSize(a.size)+") <b></b></div>"})},FilesRemoved:function(a,b){plupload.each(b,function(a){jQuery("#"+a.id).remove()})},UploadProgress:function(a,b){document.getElementById(b.id).getElementsByTagName("b")[0].innerHTML="<span>"+b.percent+"%</span>"},Error:function(a,b){document.getElementById("console").innerHTML+="\nError #"+b.code+": "+b.message},UploadComplete:function(){document.getElementById("followup-filelist").innerHTML="",c(e),e=!1,d=[]},FileUploaded:function(a,b,c){var e=jQuery.parseJSON(c.response);e.status&&(d=d.concat(e.attach_ids))}}});f.init()}jQuery(document).on("click",".followup-attach-remove",function(a){a.preventDefault(),f.removeFile(jQuery(this).parent().attr("id"))})},initEditFollowUp:function(){var b;jQuery("#delfollowup").click(function(){var a=confirm("Are you sure you want to remove this Followup?");return 1!=a?(e.preventDefault(),!1):(jQuery("#edithdspinner").show(),jQuery(this).attr("disabled","disabled"),postid=jQuery("#post-id").val(),void jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:{action:"helpdesk_delete_followup",comment_id:b,post_id:postid},success:function(a){a.status?(jQuery("#comment-"+b).fadeOut(500,function(){jQuery(this).remove()}),jQuery(".close-edit-followup").trigger("click")):alert("Error while deleting comment from server"),jQuery("#edithdspinner").hide(),jQuery("#delfollowup").removeAttr("disabled")},error:function(a,b,c){alert("error while removing follow up."),jQuery("#edithdspinner").hide(),jQuery("#delfollowup").removeAttr("disabled")}}))}),jQuery(document).on("click",".close-edit-followup",function(a){a.preventDefault(),jQuery("#dialog-form").slideToggle("slow"),jQuery("#new-followup-form").show(),jQuery(document).scrollTop(jQuery("#comment-"+b).offset().top)}),jQuery(document).on("click",".editfollowuplink",function(c){c.preventDefault();var d=jQuery(this).parents();a.rthd_tinymce_set_content("editedfollowupcontent",jQuery(this).parents().siblings(".rthd-comment-content").data("content")),b=d.siblings("#followup-id").val();var e=d.siblings("#is-private-comment").val(),f=jQuery("input[name='edit_private'][value='"+e+"']");f.length?f.prop("checked",!0):f.val(e),jQuery("#new-followup-form").hide(),jQuery("#dialog-form").is(":visible")||jQuery("#dialog-form").slideToggle("slow"),jQuery("#edit-ticket-data").is(":visible")&&jQuery("#edit-ticket-data").slideToggle("slow"),jQuery(document).scrollTop(jQuery("#dialog-form").offset().top-50)}),jQuery("#editfollowup").click(function(){var c=new Object,d=a.rthd_tinymce_get_content("editedfollowupcontent");if(!d)return alert("Please enter comment"),!1;if(d.replace(/\s+/g," ")===jQuery("#comment-"+b).find(".rthd-comment-content").data("content"))return alert("You have not edited comment!"),!1;jQuery("#edithdspinner").show(),jQuery(this).attr("disabled","disabled"),c.post_type=rthd_post_type,c.comment_id=b,c.action="rthd_update_followup_ajax",c.followuptype="comment",c.followup_ticket_unique_id=jQuery("#ticket_unique_id").val(),c.followup_post_id=jQuery("#post-id").val();var e=jQuery("input[name='edit_private']:checked");e=e.length?e.val():jQuery("input[name='edit_private']").val(),c.followup_private=e,c.followuptype="comment",c.followup_content=d,jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:c,success:function(a){a.status?(jQuery("#comment-"+b).replaceWith(a.comment_content),jQuery(".close-edit-followup").trigger("click"),jQuery(document).scrollTop(jQuery("#comment-"+b).offset().top-50)):alert(a.message),jQuery("#edithdspinner").hide(),jQuery("#editfollowup").removeAttr("disabled")},error:function(a){alert("Sorry :( something went wrong!"),jQuery("#edithdspinner").hide(),jQuery("#editfollowup").removeAttr("disabled")}})})},initLoadAll:function(){jQuery("#followup-load-more, .load-more-block").click(function(a){a.preventDefault();var b=new Object,c=parseInt(jQuery("#followup-totalcomment").val(),10),d=parseInt(jQuery("#followup-limit").val(),10);3==d&&(jQuery(this).parent().hide(),jQuery("#load-more-hdspinner").show(),b.limit=c-3,b.offset=0,b.action="load_more_followup",b.post_id=jQuery("#post-id").val(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(a){a.status&&(jQuery("#followup-offset").val(a.offset),jQuery("#chat-UI").prepend(a.comments)),jQuery("#load-more-hdspinner").hide()},error:function(){return jQuery("#load-more-hdspinner").hide(),!1}}))})},initEditContent:function(){jQuery(".edit-ticket-link").click(function(b){b.preventDefault(),jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#dialog-form").is(":visible")&&jQuery("#dialog-form").slideToggle("slow"),jQuery("#edit-ticket-data").is(":visible")||jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").hide(),jQuery(document).scrollTop(jQuery("#edit-ticket-data").offset().top-50),a.rthd_tinymce_set_content("editedticketcontent",jQuery(this).closest(".ticketcontent").find(".rthd-comment-content").data("content"))}),jQuery(".close-edit-content").click(function(a){a.preventDefault(),jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").show(),jQuery(document).scrollTop(jQuery(".ticketcontent").offset().top-50)}),jQuery("#edit-ticket-content-click").click(function(){jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").hide();var b=new Object;b.action="rthd_add_new_ticket_ajax",b.post_id=jQuery("#post-id").val();var c=a.rthd_tinymce_get_content("editedticketcontent");b.body=c,b.nonce=jQuery("#edit_ticket_nonce").val(),jQuery("#ticket-edithdspinner").show(),jQuery(this).attr("disabled","disabled"),jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:b,success:function(b){b.status?(jQuery("#ticket-edithdspinner").hide(),jQuery("#edit-ticket-content-click").removeAttr("disabled"),jQuery(".edit-ticket-link").closest(".ticketcontent").find(".rthd-comment-content").html(a.rthd_tinymce_get_content("editedticketcontent")),jQuery("#edit-ticket-data").hide(),jQuery("#new-followup-form").slideToggle("slow"),jQuery(document).scrollTop(jQuery(".ticketcontent").offset().top-50)):console.log(b.msg)},error:function(a,b,c){alert("Error"),jQuery("#ticket-edithdspinner").hide(),jQuery("#edit-ticket-content-click").removeAttr("disabled")}})})}};a.init()}),function(a){(jQuery.browser=jQuery.browser||{}).mobile=/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))}(navigator.userAgent||navigator.vendor||window.opera),rthd_user_edit=rthd_user_edit[0];var file_frame_ticket;jQuery(document).ready(function(a){function b(){return jQuery(window.location.hash).exists()}function c(a){var b=/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;return b.test(a)}function d(a){var b=new Object;b.post_id=jQuery("#post-id").val(),b.offering_id=a,b.action="front_end_offering_change",jQuery("#offering-change-spinner").show(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(b){b.status&&jQuery("#rthd-offering-list").val(a),jQuery("#offering-change-spinner").hide()},error:function(){return jQuery("#offering-change-spinner").hide(),!1}})}function e(a){var b=new Object;b.post_id=jQuery("#post-id").val(),b.post_author=a,b.action="front_end_assignee_change",jQuery("#assignee-change-spinner").show(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(b){b.status&&(jQuery(".rthd-current-user-id").val()==a?(jQuery(".rt-hd-assign-me").hide(),jQuery("#rthd-assignee-list").val(a)):jQuery(".rt-hd-assign-me").show()),jQuery("#assignee-change-spinner").hide()},error:function(){return jQuery("#assignee-change-spinner").hide(),!1}})}function f(){jQuery(".rthd-add-people-box").is(":visible")?jQuery(".rthd-add-people-box").hide():jQuery(".rthd-add-people-box").show()}jQuery(".fancybox").fancybox({afterLoad:function(){this.title='<a class="rthd_quick_download" download="'+jQuery(this.element).data("downloadlink")+'" href="'+jQuery(this.element).data("downloadlink")+'">Download</a> '+this.title},iframe:{preload:!1},helpers:{title:{type:"inside"}}}),jQuery(".rthd-scroll-up").click(function(b){b.preventDefault(),a("html, body").animate({scrollTop:0},"slow")}),jQuery(window).scroll(function(){jQuery(this).scrollTop()>500?jQuery(".rthd-scroll-up").fadeIn():jQuery(".rthd-scroll-up").fadeOut()}),jQuery.browser.mobile||a(".rthd_sticky_div").stickyfloat({duration:400,delay:3}),a.fn.exists=function(){return 0!==this.length};var g=!1;jQuery(".rthd-subscribe-email-submit").click(function(){jQuery(".rthd-subscribe-validation").hide(),jQuery("#rthd-subscribe-email-spinner").show();var a=jQuery("#rthd-subscribe-email").val();if(!c(a))return jQuery(".rthd-subscribe-validation").show(),jQuery(".rthd-subscribe-validation").html("Invalid Email"),void jQuery("#rthd-subscribe-email-spinner").hide();var b=new Object;b.action="rt_hd_add_subscriber_email",b.email=a,b.post_id=jQuery("#post-id").val(),jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:b,success:function(a){if(a.status){if(jQuery(".rthd-subscribe-validation").show(),jQuery(".rthd-subscribe-validation").text("Added Successfully"),jQuery(".rthd-subscribe-validation").hide(5e3),jQuery("#rthd-subscribe-email").val(""),jQuery(".rthd-participants").is(":visible")||jQuery(".rthd-participants").show(),!a.has_replied){var b='<a title="'+a.display_name+'" class="rthd-last-reply-by" href="'+a.edit_link+'">'+a.avatar+" </a>";a.is_contact?jQuery(".rthd-contact-avatar-no-reply-div").append(b):jQuery(".rthd-subscriber-avatar-no-reply-div").append(b)}jQuery(".rthd-add-people-box").hide()}else a.msg.length>0?(jQuery(".rthd-subscribe-validation").show(),jQuery(".rthd-subscribe-validation").html(a.msg)):jQuery(".rthd-subscribe-validation").html("Something went wrong"),jQuery(".rthd-subscribe-validation").hide(5e3);jQuery("#rthd-subscribe-email-spinner").hide()}})});var h=b();h||0===window.location.hash.length||(g=!0,jQuery("#followup-load-more").trigger("click")),jQuery("#rthd-change-status").click(function(a){}),jQuery("#rthd-status-list").change(function(a){var b=new Object;b.post_id=jQuery("#post-id").val(),b.post_status=jQuery("#rthd-status-list").val(),b.action="front_end_status_change",jQuery("#status-change-spinner").show(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(a){a.status,jQuery("#status-change-spinner").hide()},error:function(){return jQuery("#status-change-spinner").hide(),!1}})}),jQuery("#ticket-add-fav").click(function(a){a.preventDefault();var b=new Object;b.action="rthd_fav_ticket",b.nonce=jQuery("#rthd_fav_tickets_nonce").val(),b.post_id=jQuery("#post-id").val(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(a){a.status&&jQuery("#ticket-add-fav span").toggleClass("dashicons-star-empty dashicons-star-filled")},error:function(){return!1}})}),jQuery("#rthd-assignee-list").change(function(a){e(jQuery("#rthd-assignee-list").val())}),jQuery("#rthd-offering-list").change(function(a){return 0==jQuery("#rthd-offering-list").val()?!1:void d(jQuery("#rthd-offering-list").val())}),jQuery(".rt-hd-assign-me").click(function(a){a.preventDefault(),e(jQuery(".rthd-current-user-id").val())}),jQuery("#rthd-ticket-watch-unwatch").click(function(a){a.preventDefault();var b=new Object;b.post_id=jQuery("#post-id").val(),b.watch_unwatch=jQuery(this).attr("data-value"),b.action="front_end_ticket_watch_unwatch",jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(a){a.status&&(jQuery("#rthd-ticket-watch-unwatch span").toggleClass("dashicons-email dashicons-email-alt"),jQuery("#rthd-ticket-watch-unwatch").attr("data-value",a.value),jQuery("#rthd-ticket-watch-unwatch").attr("title",a.label))},error:function(){return!1}})}),jQuery(".rthd-collapse-click").click(function(a){a.preventDefault(),jQuery(this).closest(".rt-hd-ticket-info").next().slideToggle(),jQuery("span",this).toggleClass("dashicons-arrow-up-alt2 dashicons-arrow-down-alt2")}),jQuery("#rthd-add-contact").click(function(a){a.preventDefault(),a.stopPropagation(),f()}),jQuery(document).mouseup(function(b){var c=a(".rthd-add-people-box");c.is(b.target)||0!==c.has(b.target).length||jQuery(".rthd-add-people-box").is(":visible")&&jQuery(".rthd-add-people-box").hide()})});