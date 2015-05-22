// Conditional Load do not add in admin-min.js
jQuery( document ).ready(function () {
	if (typeof rthd_menu !== 'undefined' && typeof rthd_url !== 'undefined') {
		jQuery( '#menu-posts' ).removeClass( 'wp-menu-open wp-has-current-submenu' ).addClass( 'wp-not-current-submenu' );
		jQuery( '#menu-posts a.wp-has-submenu' ).removeClass( 'wp-has-current-submenu wp-menu-open menu-top' );
		jQuery( '#menu-posts-' + rthd_menu ).addClass( 'wp-has-current-submenu wp-menu-open menu-top menu-top-first' ).removeClass( 'wp-not-current-submenu' );
		jQuery( '#menu-posts-' + rthd_menu + ' a.wp-has-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open menu-top' );
		jQuery( 'li.menu-icon-' + rthd_menu + ' ul li' ).removeClass( 'current' );
		jQuery( 'li.menu-icon-' + rthd_menu + ' ul li a' ).removeClass( 'current' );
		jQuery( 'li.menu-icon-' + rthd_menu + ' ul li a' ).each(function (e) {
			if (this.href === rthd_url) {
				jQuery( this ).parent().addClass( 'current' );
				jQuery( this ).addClass( 'current' );
			}
		});
		jQuery( window ).resize();
	}

	if (jQuery( 'h2 .add-new-h2' )) {
		$contact_type = getParameterByName( 'rt_contact_group' );
		$module = getParameterByName( 'module' );
        if ( $contact_type && $module ){
            jQuery( 'h2 .add-new-h2' ).attr( 'href', jQuery( 'h2 .add-new-h2' ).attr( 'href' ) + '&rt_contact_group=' + $contact_type + '&module=' + $module );
        }
	}

	function getParameterByName(name) {
		name = name.replace( /[\[]/, "\\[" ).replace( /[\]]/, "\\]" );
		var regex = new RegExp( "[\\?&]" + name + "=([^&#]*)" ),
			results = regex.exec( location.search );
		return results === null ? "" : decodeURIComponent( results[1].replace( /\+/g, " " ) );
	}

});
