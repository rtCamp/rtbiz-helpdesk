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

	// get the release date
	var $status = $bulk_row.find('.inline-edit-status').find('select').val();

	// save the data
	jQuery.ajax({
		       url: ajaxurl, // this is a variable that WordPress has already defined for us
		       type: 'POST',
		       async: false,
		       cache: false,
		       data: {
			       action: 'ticket_bulk_edit', // this is the name of our WP AJAX function that we'll set up next
			       post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
			       ticket_status: $status
		       }
	       });

});