/**
 * Created by spock on 3/2/15.
 */
jQuery( '#bulk_edit' ).live( 'click', function($) {

	// define the bulk edit row
	var $bulk_row = jQuery( '#bulk-edit' );

	// get the selected post ids that are being edited
	var $post_ids = new Array();
	$bulk_row.find( '#bulk-titles' ).children().each( function() {
		$post_ids.push( jQuery( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
	});

	// get the post status
	var $status = $bulk_row.find('.inline-edit-status').find('select').val();

	// get all taxonomy values along with taxonomy slug.
	var tax_input = {};

	$bulk_row.find( 'ul.cat-checklist' ).each( function() {

		var tax_ids = [];
		var tax_class = jQuery( this ).attr( 'class' ).split( ' ' )[1].replace('-checklist', '');

		jQuery( this ).find( 'input[type="checkbox"]' ).each( function() {

			if( jQuery( this ).is( ':checked' ) ) {
				tax_ids.push( jQuery( this ).val() );
			}
		});
		tax_input[ tax_class ] = tax_ids;
	});

	// save the data
	jQuery.ajax({
       url: ajaxurl, // this is a variable that WordPress has already defined for us
       type: 'POST',
       async: false,
       cache: false,
       data: {
	       action: 'ticket_bulk_edit', // this is the action of our WP AJAX
	       post_ids: $post_ids, // and these are the 3 parameters we're passing to our function
	       ticket_status: $status,
	       tax_input: tax_input,
       }
   });
});
