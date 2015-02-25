/**
 * Created by spock on 25/2/15.
 */

jQuery(document).ready(function (){
	jQuery('.rt-hd-add-more-email' ).click(function(e){
		e.preventDefault();
		jQuery('.rthd-email-group' ).append(jQuery('.rthd-hide-form-div' ).html());
	});

	jQuery('.rthd-email-group' ).on('click','.rt-hd-remove-textbox',function(e){
		e.preventDefault();
		jQuery(this ).parent().remove();
	});
});
