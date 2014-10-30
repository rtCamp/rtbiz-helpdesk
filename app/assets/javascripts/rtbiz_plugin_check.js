/**
 * Created by spock on 17/10/14.
 */


function activate_rtBiz_plugins( path, action, rtm_nonce ) {
	jQuery('.rtBiz-not-installed-error').removeClass('error');
	jQuery('.rtBiz-not-installed-error').addClass('updated');
	jQuery('.rtBiz-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> rtBiz will be activated. Please wait. <div class="spinner"> </div>');
	jQuery("div.spinner").show();
	var param = {
		action: action,
		path: path,
		_ajax_nonce: rtm_nonce
	};
	jQuery.post( rtbiz_ajax_url, param,function(data){
		data = data.trim();
		if(data == "true") {
			jQuery('.rtBiz-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> rtBiz activated.');
			location.reload();
		} else {
			jQuery('.rtBiz-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> There is some problem. Please try again.');
		}
	});
}
