/*! 
 * rtBiz Helpdesk JavaScript Library 
 * @package rtBiz Helpdesk 
 */jQuery(document).ready(function(a){var b=!1,c={init:function(){c.scroll(),c.initAddHdErrorPage()},scroll:function(){"undefined"!=typeof rthd_shortcode_params&&jQuery(document).scroll(function(){if(jQuery(".rthd_ticket_short_code tr:last").length>0){var a=jQuery(".rthd_ticket_short_code tr:last").offset().top;jQuery(window).scrollTop()>a-500&&c.load_more_helpdesk_ticket()}})},load_more_helpdesk_ticket:function(){if(!b){var c={};c.offset=a(".rthd_ticket_short_code tr").length-1,c.offset>=a(".rt-hd-total-ticket-count").val()||(c.short_code_param=rthd_shortcode_params,c.action="rtbiz_hd_load_more_ticket_shortcode",b=!0,loader=a(".rthd-ticket-short-code-loader"),loader.css("display","block"),a.ajax({url:ajaxurl,type:"POST",dataType:"json",data:c,success:function(c){c.status?(a(".rthd_ticket_short_code").append(c.html),count=a(".rthd_ticket_short_code tr").length-1,a(".rthd-current-count").html(count)):console.log(c.msg),b=!1,loader.hide()}}))}},initAddHdErrorPage:function(){jQuery("#rthd-add-hd-error-page").on("click",function(a){var b={};b.action="rtbiz_add_hd_error_page",jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:b,success:function(a){a.status?location.reload():console.log(a.msg)},error:function(a,b,c){console.log(data.msg)}})})}};c.init()});