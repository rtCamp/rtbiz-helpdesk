var status_dropdown_visible = 1
var attachmentSep = '';
var attachmentString = '';
var arr_failed_lead = Array();

function admin_side_menu_patch() {
	jQuery( "li.toplevel_page_hd-dashboard" ).removeClass( "wp-not-current-submenu menu-top menu-icon-generic menu-top-first menu-top-last" )
	jQuery( "li.toplevel_page_hd-dashboard" ).addClass( "wp-has-current-submenu wp-menu-open open-if-no-js menu-top menu-icon-generic menu-top-first" )
	jQuery( "a.toplevel_page_hd-dashboard" ).removeClass( "wp-not-current-submenu menu-top menu-icon-generic menu-top-first menu-top-last opensub" )
	jQuery( "a.toplevel_page_hd-dashboard" ).addClass( "wp-has-current-submenu wp-menu-open open-if-no-js menu-top menu-icon-post menu-top-first" )
	jQuery( "li.toplevel_page_hd-dashboard a" ).each( function ( e ) {
		if ( (window.location.href).indexOf( this.href ) != - 1 ) {
			jQuery( this ).parent().addClass( "current" );
			jQuery( this ).addClass( 'current' )
		}
	} );
}

jQuery( document ).ready( function ( $ ) {

	/**
	 * WordPress Menu Hack for Dashboard
	 */
	if ( typeof rt_hd_top_menu != 'undefined' && typeof rt_hd_dashboard_url != 'undefined' ) {
		$( '#' + rt_hd_top_menu + ' ul li' ).removeClass( 'current' );
		$( '#' + rt_hd_top_menu + ' ul li a' ).removeClass( 'current' );
		$( '#' + rt_hd_top_menu + ' ul li a' ).each( function ( e ) {
			if ( this.href == rt_hd_dashboard_url ) {
				$( this ).parent().addClass( "current" );
				$( this ).addClass( 'current' );
			}
		} );
	}

	/**
	 *    Studio Settings Page
	 */
	if ( $( ".rtcamp-user-ac" ).length > 0 ) {
		jQuery.each( arr_rtcamper, function ( ind, val ) {
			$( "div[acl-user=" + val.id + "]" ).each( function () {
				$( this ).prepend( val.imghtml + val.label );
			} )
			//acl-user
		} );

		jQuery( ".rtcamp-user-ac" ).autocomplete( {
			                                          source: arr_rtcamper,
			                                          select: function ( event, ui ) {
				                                          var tmpName = this.name + "_" + ui.item.id;
				                                          if ( jQuery( "#" + tmpName ).length == 0 ) {
					                                          jQuery( this ).after( "<div class='mail-acl_user' id='" + tmpName + "'>" + ui.item.imghtml + ui.item.label + "&nbsp;&nbsp;<a href='#removeAccess'>X</a><input type='hidden' name='allow_users[]' value='" + ui.item.id + "' /></div>" )
				                                          }
				                                          jQuery( this ).val( "" );
				                                          return false;
			                                          }
		                                          } ).data( "ui-autocomplete" )._renderItem = function ( ul, item ) {
			return $( "<li></li>" ).data( "ui-autocomplete-item", item ).append( "<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>" ).appendTo( ul );
		};
		jQuery( document ).on( 'click', "a[href=#removeAccess]", function ( e ) {
			e.preventDefault();
			$( this ).parent().remove();
		} );

	}
/*	$( ".remove-google-ac" ).click( function ( e ) {
		var r = confirm( "Are you sure you want to remove this email A/C ?" );
		if ( r == true ) {

		} else {
			e.preventDefault();
			return false;
		}
	} );*/


	/**
	 * Gravity Importer Handlebars
	 */
		//	Handlebars.registerHelper('mapfieldnew', function(obj1, obj2) {
		//        if (obj1.toLowerCase().indexOf(obj2.toLowerCase()) > -1) {
		//            return new Handlebars.SafeString(
		//                    " selected "
		//                    );
		//        } else {
		//            return new Handlebars.SafeString(" ");
		//        }
		//    });

		//set normal priority
		//    jQuery("[name=priority]").val(3);

		//    $("#postcustomstuff input[type=text],#postcustomstuff textarea").each(function(e) {

		//        var test_val= $(this).val();
		//        if (IsEmail(test_val)){
		//             test_val = "<a target='_blank' href='mailto:"+ test_val+"'> " + test_val  + "</a>";
		//        } else if (isUrl(test_val)){
		//            test_val = "<a target='_blank' href='"+ test_val+"'> " + test_val  + "</a>";
		//        }

		//        $(this).parent().append("<label class='custome-readonly'>" + test_val + "</label>");
		//        $(this).siblings(".submit").hide();
		//        $(this).hide();
		//        $(this).parent().dblclick(function() {
		//            $(this).parent().find("input[type=text],textarea,.submit").each(function() {
		//                $(this).show();
		//            })
		//            $(this).parent().find(".custome-readonly").each(function() {
		//                $(this).hide();
		//            })
		//        })
		//    });
		//	$(document).on("dblclick", ".custome-readonly", function() {

		//        $(this).parent().parent().find("input[type=text],textarea,.submit").each(function() {
		//            $(this).show();
		//        })
		//        $(this).parent().parent().find(".custome-readonly").each(function() {
		//            $(this).hide();
		//        })

		//    })

		//	$("#newmeta").hide();
		//    $("#newmeta").prev().dblclick(function() {
		//        $("#newmeta").toggle();
		//    })
	$( ".revertChanges" ).click( function ( e ) {
		var r = confirm( "This will delete all the helpdesk data created in this trannsaction ! Are you sure you want to continue ?" );
		if ( r == true ) {

		} else {
			e.preventDefault();
			return false;
		}
	} )



	// Imap Servers
	/*jQuery( document ).on( 'click', '.rthd-edit-server', function ( e ) {
		e.preventDefault();
		server_id = jQuery( this ).data( 'server-id' );
		jQuery( '#rthd_imap_server_' + server_id ).toggleClass( 'rthd-hide-row' ).toggleClass( 'rthd-show-row' );
	} );
	jQuery( document ).on( 'click', '#rthd_add_imap_server', function ( e ) {
		e.preventDefault();
		jQuery( '#rthd_new_imap_server' ).toggleClass( 'rthd-hide-row' ).toggleClass( 'rthd-show-row' );
	} );
	jQuery( document ).on( 'click', '.rthd-remove-server', function ( e ) {
		e.preventDefault();
		flag = confirm( 'Are you sure you want to remove this server ?' );
		server_id = jQuery( this ).data( 'server-id' );
		if ( flag ) {
			jQuery( '#rthd_imap_server_' + server_id ).remove();
			jQuery( this ).parent().parent().remove();
		}
	} );
*/

	// User Settings Page - Add Email
/*	jQuery( document ).on( 'click', '#rthd_add_personal_email', function ( e ) {
		e.preventDefault();
		jQuery( '#rthd_email_acc_type_container' ).toggleClass( 'rthd-hide-row' ).toggleClass( 'rthd-show-row' );
		if ( jQuery( '#rthd_email_acc_type_container' ).hasClass( 'rthd-hide-row' ) ) {
			jQuery( '#rthd_goauth_container' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_add_imap_acc_form' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_select_email_acc_type' ).val( '' ).change();
		}
	} );
	jQuery( document ).on( 'change', '#rthd_select_email_acc_type', function ( e ) {
		if ( jQuery( this ).val() == 'goauth' ) {
			jQuery( '#rthd_goauth_container' ).removeClass( 'rthd-hide-row' ).addClass( 'rthd-show-row' );
			jQuery( '#rthd_add_imap_acc_form' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_add_imap_acc_form input[type=email]' ).remove();
			jQuery( '#rthd_add_imap_acc_form input[type=password]' ).remove();
		} else if ( jQuery( this ).val() == 'imap' ) {
			jQuery( '#rthd_add_imap_acc_form' ).removeClass( 'rthd-hide-row' ).addClass( 'rthd-show-row' );
			jQuery( '#rthd_goauth_container' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_add_imap_acc_form' ).append( '<input type="email" autocomplete="off" name="rthd_imap_user_email" placeholder="Email"/>' );
			jQuery( '#rthd_add_imap_acc_form' ).append( '<input type="password" autocomplete="off" name="rthd_imap_user_pwd" placeholder="Password"/>' );
		} else {
			jQuery( '#rthd_goauth_container' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_add_imap_acc_form' ).removeClass( 'rthd-show-row' ).addClass( 'rthd-hide-row' );
			jQuery( '#rthd_add_imap_acc_form input[type=email]' ).remove();
			jQuery( '#rthd_add_imap_acc_form input[type=password]' ).remove();
		}
	} );*/

} );

function IsEmail( email ) {
	var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if ( ! regex.test( email ) ) {
		return false;
	} else {
		return true;
	}
}

function isUrl( value ) {
	var urlregex = new RegExp( "^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$" );
	if ( urlregex.test( value ) ) {
		return (true);
	}
	return (false);
}