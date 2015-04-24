/**
 * Created by spock on 21/4/15.
 */
jQuery(document).ready(function($) {

    var wizard;
	var skip_step = false;
    var rthdSetup = {
        init: function () {
            rthdSetup.setup_wizard();
	        rthdSetup.search_users();
	        rthdSetup.add_user_single();

        },
        setup_wizard: function(){
            wizard = jQuery( "#wizard" ).steps( {
                headerTag: "h1",
                bodyTag: "fieldset",
                transitionEffect: "slideLeft",
                forceMoveForward: true,
                //enableAllSteps: true,
                onStepChanging: function (event, currentIndex, newIndex)
                {
                    //alert("moving to "+newIndex+" from "+ currentIndex);
	                if ( skip_step ){
		                skip_step = false;
		                return true;
	                }
	                if (currentIndex == 1){
		                // save offering selection and sync offerings
		                rthdSetup.connect_store();
		                return false;
	                }
	                // active this after screen is fixed
	                if ( currentIndex == 0 ){
		                //save support form
		                rthdSetup.support_page();
		                return false;
	                }
                    return true;
                },
                onStepChanged: function (event, currentIndex, priorIndex)
                {
                    //alert("on step changed moved to "+currentIndex+" from "+ priorIndex);
                    return true;
                },
                onFinishing: function (event, currentIndex)
                {
                    //alert("on finishing changed moved to "+currentIndex);
                    return true;
                },
                onFinished: function (event, currentIndex)
                {
                    //alert("Submitted!");
                }
            });
        },
	    search_users : function(){
		    if ( jQuery( ".rthd-user-autocomplete" ).length > 0 ) {
			    jQuery( ".rthd-user-autocomplete" ).autocomplete( {
                     source: function( request, response ) {
                         $.ajax( {
                                     url: ajaxurl,
                                     dataType: "json",
                                     type: 'post',
                                     data: {
                                         action: 'rthd_search_non_helpdesk_user_from_name',
                                         maxRows: 10,
                                         query: request.term
                                     },
                                     success: function( data ) {
                                         if ( data.hasOwnProperty('have_access') ){
                                             // email have access so no need of popup to asking for adding user
                                             jQuery('.rthd-warning' ).html( '<strong>'+jQuery('#rthd-user-autocomplete' ).val()+'</strong> Already have helpdesk access' );
                                             jQuery('.rthd-warning' ).show();
                                             response();
                                         } else if ( data.hasOwnProperty('show_add') ){

                                             jQuery('.rthd-warning' ).html( 'Hey, Looks like <strong>'+jQuery('#rthd-user-autocomplete' ).val()+'</strong> is not in your system, would you like to add?' );
                                             jQuery('.rthd-importer-add-contact' ).show();
                                             jQuery('#rthd-new-user-email' ).val(jQuery('#rthd-user-autocomplete' ).val());
                                             jQuery('.rthd-warning' ).show();
                                             response();
                                         } else {
                                             response( $.map( data, function ( item ) {
                                                 return {
                                                     id: item.id,
                                                     imghtml: item.imghtml,
                                                     label: item.label,
                                                     editlink: item.editlink
                                                 }
                                             } ) );
                                             jQuery('.rthd-warning' ).hide();
                                             jQuery('.rthd-importer-add-contact' ).hide();
                                         }
                                     }
                                 } );
                     }, minLength: 2,
                     select: function( event, ui ) {
	                     rthdSetup.give_user_helpdesk_access( false, ui.item.id );
                         //if (jQuery("#imported-user-auth-" + ui.item.id).length < 1) {
                         //    jQuery(".rthd-setup-list-users").append("<li id='imported-user-auth-" + ui.item.id + "' class='contact-list' >" + ui.item.imghtml + "<a href='#removeUser' class='delete_row'>×</a><br/><a class='rthd-setup-user-title heading' target='_blank' href='" + ui.item.editlink + "'>" + ui.item.label + "</a><input type='hidden' class='rthd-import-selected-users' name='import_users[]' value='" + ui.item.id + "' /></li>")
                         //}
                         jQuery( ".rthd-user-autocomplete" ).val( '' );
                         return false;
                     }
                 } ).data( 'ui-Autocomplete' )._renderItem = function( ul, item ) {
                        return $( '<li></li>' ).data( 'ui-autocomplete-item', item ).append( '<a>' + item.imghtml + '&nbsp;' + item.label + '</a>' ).appendTo( ul );
                 };

			    jQuery( document ).on( "click", "a[href=#removeUser]", function() {
				    jQuery( this ).parent().remove();
			    } );
		    }
	    },
	    add_user_single: function(){
		    jQuery('.rthd-importer-add-contact' ).click(function (){
			    rthdSetup.give_user_helpdesk_access( jQuery('#rthd-new-user-email' ).val(), false );
		    });

		    jQuery('#rthd-get-domain-count-users' ).click(function(){
			    rthdSetup.import_domain_users(true);
		    });

		    $('#rthd-add-user-domain').on("keypress", function(e) {
			    if ( e.keyCode == 13 ) {
				    rthdSetup.import_domain_users(true);
				    e.preventDefault();
				    return false;
			    }
		    });

		    jQuery('#rthd-import-domain-users' ).click( function () {
			    rthdSetup.import_domain_users(false);
		    });

		    jQuery('#rthd-add-all-users' ).click( function ( e ) {
			    jQuery('#rthd-setup-import-users-progress' ).show();
			    rthdSetup.import_all_users();
		    });

		    jQuery('#rthd-setup-wizard-support-page' ).on('change', function ( e ) {
			    val = jQuery(this ).val();
			   if ( val == -1){
				   jQuery('.rthd-setup-wizard-support-page-new-div' ).show();
			   } else{
					if (jQuery('.rthd-setup-wizard-support-page-new-div' ).is(":visible")){
						jQuery('.rthd-setup-wizard-support-page-new-div' ).hide();
					}
		       }
		    });

		    jQuery('.rthd-wizard-skip' ).on('click', function ( e ) {
			    skip_step = true;
			    jQuery('.wizard').steps('next');
		    })
	    },
	    support_page:  function(){
			var requestArray = new Object();
			requestArray['action'] = 'rthd_setup_support_page';
			if (jQuery('.rthd-setup-wizard-support-page-new-div' ).is(":visible")){
				requestArray['new_page'] = jQuery('#rthd-setup-wizard-support-page-new' ).val();
				requestArray['page_action'] = 'add';

			} else {
				requestArray['old_page'] = jQuery('#rthd-setup-wizard-support-page' ).val();
			}
			requestArray['import']= true;
			jQuery('.rthd-support-process' ).show();
			$.ajax( {
				        url: ajaxurl,
				        dataType: "json",
				        type: 'post',
				        data:requestArray,
				        success: function( data ) {
					        if (data.status){
						        jQuery('.rthd-support-process' ).hide();
						        skip_step = true;
						        jQuery('.wizard').steps('next');
					        }
				        }
			        });

		},
	    import_all_users: function(){
		    var requestArray = new Object();
		    requestArray['action'] ='rthd_import_all_users';
		    requestArray['nonce']= jQuery('#import_all_users' ).val();
		    requestArray['import']= true;
		    $.ajax( {
			            url: ajaxurl,
			            dataType: "json",
			            type: 'post',
			            data:requestArray,
			            success: function( data ) {
				            if ( data.status ){
					            var remain = jQuery('#rthd-setup-import-all-count' ).val();
					            remain  =  parseInt(remain) - parseInt(data.imported_count);
					            var progressbar = jQuery('#rthd-setup-import-users-progress' ).val();
					            progressbar =  parseInt(progressbar) + parseInt(data.imported_count);
					            jQuery('#rthd-setup-import-users-progress' ).val(progressbar);
					            $.each(data.imported_users, function ( i, user ) {
						            rthdSetup.add_contact_to_list( user.id, user.label, user.imghtml, user.editlink );
					            });
					            if ( parseInt(remain) > 0 ){
						            jQuery('#rthd-setup-import-all-count' ).val(remain);
						            rthdSetup.import_all_users();
					            } else {

					            }
				            }
			            }
		            } );
	    },
	    import_domain_users: function ( get_count ) {
		    var requestArray = new Object();
		    requestArray['action'] ='rthd_domain_search_and_import';
		    requestArray['count'] =get_count;
		    requestArray['domain_query'] = jQuery('#rthd-add-user-domain' ).val();
		    requestArray['nonce']= jQuery('#import_domain' ).val();

		    $.ajax( {
			            url: ajaxurl,
			            dataType: "json",
			            type: 'post',
			            data:requestArray,
			            success: function( data ) {
				            if ( data.status ){
					            if ( data.hasOwnProperty('count') ){
						            jQuery('#rthd-domain-import-message' ).html('Total '+data.count+' Users found!');
					            } else {
						            $.each(data.imported_users, function ( i, user ) {
							            rthdSetup.add_contact_to_list( user.id, user.label, user.imghtml, user.editlink );
						            });
						            if ( data.imported_all){
							            jQuery('#rthd-domain-import-message' ).html('Imported '+(data.imported_users.size())+' Users !');
						            } else {
							            jQuery( '#rthd-domain-import-message' ).html( 'Could not import '+ ( data.not_imported_users.size() ) );
						            }
					            }
					            jQuery('#rthd-domain-import-message' ).show();
				            }
			            }
		            } );
	    },
	    give_user_helpdesk_access: function ( email, id ) {

		    var requestArray = new Object();
		    if (email != false){
			    requestArray['email'] = email;
		    }
		    if (id != false){
			    requestArray['ID'] = id;
		    }
		    requestArray['action'] = 'rthd_creater_rtbiz_and_give_access_helpdesk';
		    $.ajax( {
			            url: ajaxurl,
			            dataType: "json",
			            type: 'post',
			            data:requestArray,
			            success: function( data ) {
				            rthdSetup.add_contact_to_list(data.id,data.label, data.imghtml, data.editlink );
			            }
		            } );
	    },
	    add_contact_to_list: function( id, label, imghtml, editlink ){
		    if (jQuery("#imported-user-auth-" + id).length < 1) {
			    //jQuery(".rthd-setup-list-users").append("<li id='imported-user-auth-" + id + "' class='contact-list' >" + imghtml + "<a href='#removeUser' class='delete_row'>×</a><br/><a class='rthd-setup-user-title heading' target='_blank' href='" + editlink + "'>" + label + "</a><input type='hidden' class='rthd-import-selected-users' name='import_users[]' value='" + id + "' /></li>")
			    jQuery(".rthd-setup-list-users").append("<li id='imported-user-auth-" + id + "' class='contact-list' >" + imghtml + "<br/><a class='rthd-setup-user-title heading' target='_blank' href='" + editlink + "'>" + label + "</a><input type='hidden' class='rthd-import-selected-users' name='import_users[]' value='" + id + "' /></li>")
		    }
	    },
	    connect_store : function(){
		    var requestArray = new Object();
		    var selected = [];
		    jQuery("input:checkbox[name=rthd-wizard-store]:checked" ).each(function() {
			    selected.push($(this).val());
		    });
		    requestArray['store'] = selected ;
		    requestArray['action'] = 'rthd_offering_sync';
		    jQuery('.rthd-store-process' ).show();
		    $.ajax( {
			            url: ajaxurl,
			            dataType: "json",
			            type: 'post',
			            data:requestArray,
			            success: function( data ) {
				            if (data.status){
					            skip_step=true;
					            jQuery('.wizard').steps('next');
				            }
			            }
		            } );
	    }

};
    rthdSetup.init();
});