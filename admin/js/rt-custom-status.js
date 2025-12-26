// Conditional Load do not add in admin-min.js
jQuery( document ).ready(function () {
	if (typeof rtbiz_hd_menu !== 'undefined' && typeof rtbiz_hd_url !== 'undefined') {
		jQuery( '#menu-posts' ).removeClass( 'wp-menu-open wp-has-current-submenu' ).addClass( 'wp-not-current-submenu' );
		jQuery( '#menu-posts a.wp-has-submenu' ).removeClass( 'wp-has-current-submenu wp-menu-open menu-top' );
		jQuery( '#menu-posts-' + rtbiz_hd_menu ).addClass( 'wp-has-current-submenu wp-menu-open menu-top menu-top-first' ).removeClass( 'wp-not-current-submenu' );
		jQuery( '#menu-posts-' + rtbiz_hd_menu + ' a.wp-has-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open menu-top' );
		jQuery( 'li.menu-icon-' + rtbiz_hd_menu + ' ul li' ).removeClass( 'current' );
		jQuery( 'li.menu-icon-' + rtbiz_hd_menu + ' ul li a' ).removeClass( 'current' );
		jQuery( 'li.menu-icon-' + rtbiz_hd_menu + ' ul li a' ).each(function (e) {
			if (this.href === rtbiz_hd_url) {
				jQuery( this ).parent().addClass( 'current' );
				jQuery( this ).addClass( 'current' );
			}
		});
		jQuery( window ).resize();
	}

	if (jQuery( 'h2 .add-new-h2' )) {
		$contact_type = getParameterByName( 'contact_group' );
		$module = getParameterByName( 'module' );
		if ( $contact_type && $module ) {
			jQuery( 'h2 .add-new-h2' ).attr( 'href', jQuery( 'h2 .add-new-h2' ).attr( 'href' ) + '&contact_group=' + $contact_type + '&module=' + $module );
		}
	}

	function getParameterByName(name) {
		name = name.replace( /[\[]/g, "\\[" ).replace( /[\]]/g, "\\]" );
		var regex = new RegExp( "[\\?&]" + name + "=([^&#]*)" ),
			results = regex.exec( location.search );
		return results === null ? "" : decodeURIComponent( results[1].replace( /\+/g, " " ) );
	}

	jQuery( '#rtbiz_is_staff_member' ).click( function( e ){
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( '#rtbiz-permission-container' ).show();
		} else {
			jQuery( '#rtbiz-permission-container' ).hide();
		}
	});

});
