/**
 * Created by spock on 17/10/14.
 */


function activate_rthd_plugin( path, action, rthd_nonce ) {
	jQuery('.rthd-plugin-not-installed-error').removeClass('error');
	jQuery('.rthd-plugin-not-installed-error').addClass('updated');
	jQuery('.rthd-plugin-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> ' + path + ' will be activated. Please wait. <div class="spinner"> </div>');
	jQuery("div.spinner").show();
	var param = {
		action: action,
		path: path,
		_ajax_nonce: rthd_nonce
	};
	jQuery.post( rthd_ajax_url, param,function(data){
		data = data.trim();
		if(data == "true") {
			jQuery('.rthd-plugin-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> ' + path + ' activated.');
			location.reload();
		} else {
			jQuery('.rthd-plugin-not-installed-error p').html('<b>rtBiz Helpdesk  :</b> There is some problem. Please try again.');
		}
	});
}
