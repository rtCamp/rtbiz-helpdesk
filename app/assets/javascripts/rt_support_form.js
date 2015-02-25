/**
 * Created by spock on 25/2/15.
 */

jQuery(document).ready(function (){
	jQuery('button.rt-hd-add-more-email' ).click(function(){
		jQuery('.rthd-email-group' ).append(jQuery('.rthd-hide-form-div' ).html());
	});

	jQuery('.rthd-email-group' ).on('click','.rt-hd-remove-textbox',function(){
		jQuery(this ).parent().remove();
	});
});
