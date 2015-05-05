jQuery(document).ready(function() {
    if ( typeof rthd_menu !== 'undefined' && typeof rthd_url !== 'undefined' ) {
        jQuery('#menu-posts').removeClass('wp-menu-open wp-has-current-submenu').addClass('wp-not-current-submenu');
        jQuery('#menu-posts a.wp-has-submenu').removeClass('wp-has-current-submenu wp-menu-open menu-top');
        jQuery('#menu-posts-'+rthd_menu).addClass('wp-has-current-submenu wp-menu-open menu-top menu-top-first').removeClass('wp-not-current-submenu');
        jQuery('#menu-posts-'+rthd_menu+' a.wp-has-submenu').addClass('wp-has-current-submenu wp-menu-open menu-top');
        jQuery('li.menu-icon-'+rthd_menu+' ul li').removeClass('current');
        jQuery('li.menu-icon-'+rthd_menu+' ul li a').removeClass('current');
        jQuery('li.menu-icon-'+rthd_menu+' ul li a').each(function(e) {
            console.log( this.href )
            console.log( rthd_url )
            console.log( this.href === rthd_url )
            if ( this.href === rthd_url ) {
                jQuery(this).parent().addClass('current');
                jQuery(this).addClass('current');
            }
        });
        jQuery(window).resize();
    }

});
