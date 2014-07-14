rtcrm_user_edit = rtcrm_user_edit[0];

jQuery(document).ready(function($) {
    $(document).foundation();
    var LOADER_OVERLAY = $("<div class='loading-overlay'><i class='loader-icon'></i></div>");
$.ajaxSetup({
	beforeSend : function(jqXHR, settings) {
		if(settings.data.indexOf('heartbeat') === -1 && settings.data.indexOf('closed-postboxes') === -1 && settings.data.indexOf('meta-box-order') === -1) {
			$("body").append(LOADER_OVERLAY);
		}
	},
	complete : function(jqXHR, settings) {
		$("body").find(".loading-overlay").remove();
	}
});

	if($(".datepicker").length > 0) {
		$(".datepicker").datepicker({
			'dateFormat': 'M d,yy',
			onClose: function(newDate,inst) {

				if( $(this).hasClass("moment-from-now") ) {
					var oldDate = $(this).attr("title");

					if( newDate != "" && moment(newDate).isValid() ) {
						$(this).val(moment(new Date(newDate)).fromNow());
						$(this).attr("title",newDate);

						if( $(this).next().length > 0 ) {
							$(this).next().val(newDate);
						}
					} else if( oldDate != "" ) {
						$(this).val(moment(new Date(oldDate)).fromNow());
						$(this).attr("title",oldDate);

						if( $(this).next().length > 0 ) {
							$(this).next().val(newDate);
						}
					}
				}
			}
		});
	}
    $(".datepicker-toggle").click(function(e) {
        $("#" + $(this).data("datepicker")).datepicker("show");
    })
    $("#subscriber_user_ac")
    try {
        if (arr_subscriber_user != undefined) {
            jQuery("#subscriber_user_ac").autocomplete({
                source: function (request, response) {
                    var term = $.ui.autocomplete.escapeRegex(request.term)
                        , startsWithMatcher = new RegExp("^" + term, "i")
                        , startsWith = $.grep(arr_subscriber_user, function (value) {
                            return startsWithMatcher.test(value.label || value.value || value);
                        })
                        , containsMatcher = new RegExp(term, "i")
                        , contains = $.grep(arr_subscriber_user, function (value) {
                            return $.inArray(value, startsWith) < 0 &&
                                containsMatcher.test(value.label || value.value || value);
                        });

                    response(startsWith.concat(contains));
                },
                focus: function(event, ui) {

                },
                select: function(event, ui) {
                    if (jQuery("#subscribe-auth-" + ui.item.id).length < 1) {
                        jQuery("#divSubscriberList").append("<li id='subscribe-auth-" + ui.item.id + "' class='contact-list' >" + ui.item.imghtml + "<a class='heading' target='_blank' href='"+ui.item.user_edit_link+"'>" + ui.item.label + "</a><a href='#removeSubscriber' class='right'><i class='foundicon-remove'></i></a><input type='hidden' name='subscribe_to[]' value='" + ui.item.id + "' /></li>")
                    }
                    jQuery("#subscriber_user_ac").val("");
                    return false;
                }
            }).data("ui-autocomplete")._renderItem = function(ul, item) {
                return $("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
            };

            jQuery(document).on('click', "a[href=#removeSubscriber]", function(e) {
                e.preventDefault();
                $(this).parent().remove();
            });

        }
    } catch (e) {

    }
	function add_contact_meta(contact_meta, term_id) {
		$.ajax({
			url: ajaxurl,
			dataType: "json",
			type: 'post',
			data: {
				action: "rtcrm_get_term_meta",
				query: term_id
			},
			success: function (data) {
				$.each(data, function (key, value) {
					if (key == 'account_id' && !isNaN(value)) {
						add_account_from_id(parseInt(value))
						return true;
					}
					if ($(contact_meta).children().length < 3) {
						if (key.indexOf("email") > -1)
							$(contact_meta).append("<i class=''><a href='mailto:" + value + "'> " + value + "</a><a class='inline' target='_blank' href='http://mail.google.com/mail/#search/" + value + "'> <i class='foundicon-search'></i></a></i> ");
						if (key.indexOf("skype") > -1)
							$(contact_meta).append("<i class=''>" + value + "</i>")
					} else {


					}
				});

			}
		});
	}

	if (jQuery("#search_contact").length > 0) {
        jQuery("#search_contact").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    type: 'post',
                    data: {
                        action: "rtcrm_search_contact",
                        query: request.term,
						post_type: rtcrm_post_type
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                imghtml: item.imghtml,
                                label: item.label,
                                url:item.url
                            }
                        }));
                    }
                });
            }, minLength: 2,
            select: function(event, ui) {
                if (jQuery("#hd-contact-" + ui.item.id).length < 1) {
                    //get_term_meta
                    var contact_meta = "#hd-contact-meta-" + ui.item.id
                    $("#divContactsList").append(genrate_contact_li(ui.item));
                    add_contact_meta(contact_meta,ui.item.id)


                }

                jQuery("#search_contact").val("");
                return false;

            }
        }).data("ui-autocomplete")._renderItem = function(ul, item) {
            return $("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
        };

        $(document).on("click", "a[href=#removeContact]", function(e) {
            e.preventDefault();
            $(this).parent().parent().parent().remove();
        });
    }
    if (jQuery("#search_account").length > 0) {
        jQuery("#search_account").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    type: 'post',
                    data: {
                        action: "rtcrm_search_account",
                        query: request.term,
						post_type: rtcrm_post_type
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                imghtml: item.imghtml,
                                label: item.label,
                                url:item.url
                            }
                        }));
                    }
                });
            }, minLength: 2,
            select: function(event, ui) {
                if (jQuery("#crm-account-" + ui.item.id).length < 1) {
                    jQuery("#divAccountsList").append(genrate_account_li(ui.item));
                }
                jQuery("#search_account").val("");

                //rtcrm_get_account_contacts
                $.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    type: 'post',
                    data: {
                        action: "rtcrm_get_account_contacts",
                        query: ui.item.id,
						post_type: rtcrm_post_type
                    },
                    success: function(data) {
                        $.each(data,function(key,value){
                            if (jQuery("#hd-contact-" + value.id).length < 1) {
                                jQuery("#divContactsList").append(genrate_contact_li(value));
                            }
                        })
                    }
                });
                return false;
            }
        }).data("ui-autocomplete")._renderItem = function(ul, item) {
            return $("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected' >" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
        };

        $(document).on("click", "a[href=#removeAccount]", function(e) {
            e.preventDefault();
            $(this).parent().parent().parent().remove();
        });
    }
    $("#add-new-contact").click(function() {
        $("#add-contact").reveal({
            open:function(){
                reset_contact_form();
                $("#new-contact-name").val($("#search_contact").val());
                $("#search_contact").val("");
            }
        });
    });
    var accountForContact = false;
    var lastAccountId = 0;
    var lastAccountDetail = null;
    function reset_account_form(){
        jQuery("#form-add-account").each (function(){
                    this.reset();
                });
                $("#account_meta_container").html("");
    }
    function reset_contact_form(){
        jQuery("#form-add-contact").each (function(){
                    this.reset();
                });
                $("#contact_meta_container").html("");
                $("#div_contact_account").html("");
    }
    $("#contact-add-new-account").click(function() {
        $("#add-account").reveal({
            open: function() {

                reset_account_form();
                accountForContact = true;
                lastAccountId = 0;
                lastAccountDetail = null;
            },
            closed: function() {
                if (lastAccountId > 0 && lastAccountDetail != null) {
                    jQuery("#div_contact_account").html("<li id='crm-account-" + lastAccountDetail.id + "' class='contact-list' ><div class='row collapse'><div class='large-2 columns'> " + lastAccountDetail.imghtml
                            + "</div><div class='large-9 columns'><a target='_blank' class='heading' href='" +  lastAccountDetail.url  + "' title='" + lastAccountDetail.label +"'>" + lastAccountDetail.label +"</a>"
                            + "</div><div class='large-1 columns'><a href='#removeAccount'><i class='foundicon-remove'></i></a><input type='hidden' name='new-contact-account' value='" + lastAccountDetail.id + "' /></div>"
                            + "</div></li>");

                }
                $("#add-contact").reveal();
            }
        });
    });
    $("#add-new-account").click(function() {
        $("#add-account").reveal({
            open: function() {
                reset_account_form();
               $("#new-account-name").val($("#search_account").val());
                $("#search_account").val("");
                accountForContact = false  ;
            }, closed: function() {
                lastAccountId = 0;
            }
        });
    });

    function add_account_from_id(account_id) {
        //rtcrm_get_term_by_key
        $.ajax({
            url: ajaxurl,
            dataType: "json",
            type: 'post',
            data: {
                action: "rtcrm_get_term_by_key",
	            account_id: account_id,
                post_type: rtcrm_post_type
            },
            success: function(data) {
                if (jQuery("#crm-account-" + data.id).length < 1) {
                    jQuery("#divAccountsList").append(genrate_account_li(data));
                }
            }
        });

    }
    $("#contact-add-new-meta").click(function(e) {
        var txtElement = $(this).parent().find("input[type=text]");

        if ($(txtElement).val().trim() == "") {
            return true;
        }
        var txtType = ( $(this).parent().find("select").val() ) ? $(this).parent().find("select").val() : 'phone';
        var icon_class = get_icon_class(txtType);

        var tmpHTML = ""
        tmpHTML += '<div class="row collapse">'
        tmpHTML += '<div class="large-1 columns mobile-large-1">'
        tmpHTML += "<span class='prefix'><i class='" + icon_class + "'></i></span>"
        tmpHTML += '</div><div class="large-10 columns mobile-large-2">'
        tmpHTML += '<input type="text" data-metakey="' + txtType + '" value="' + $(txtElement).val() + '"/>'
        tmpHTML += '</div><div class="large-1 mobile-large-1 columns"><button class="postfix mybutton alert remove-contact-meta" type="button" ><i class="foundicon-minus"></i></button></div>'
        tmpHTML += '</div>'
        $(txtElement).val("");
        $("#contact_meta_container").append(tmpHTML);
    });
    $(document).on('click', '.remove-contact-meta', function() {
        $(this).parent().parent().remove();
    })
if(jQuery("#new-contact-account").length > 0){
    jQuery("#new-contact-account").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                type: 'post',
                data: {
                    action: "rtcrm_search_account",
                    query: request.term,
					post_type: rtcrm_post_type
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            id: item.id,
                            imghtml: item.imghtml,
                            label: item.label,
                            url:item.url
                        }
                    }));
                }
            });
        }, minLength: 2,
        select: function(event, ui) {
            jQuery("#div_contact_account").html("<li  class='contact-list' ><div class='row collapse'><div class='large-2 columns'> " + ui.item.imghtml
                    + "</div><div class='large-9 columns'><a target='_blank' class='heading' href='" +  ui.item.url  + "' title='" + ui.item.label +"'>" + ui.item.label +"</a>"
                    + "</div><div class='large-1 columns'><a href='#removeAccount'><i class='foundicon-remove'></i></a><input type='hidden' name='new-contact-account' value='" + ui.item.id + "' /></div>"
                    + "</div></li>")
            jQuery("#new-contact-account").val("");
            return false;

        }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
        return $("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
    };

    }

    function get_icon_class(txtType) {
        var icon_class = '';
        if (txtType == 'email') {
            icon_class = 'foundicon-mail';
        } else if (txtType == 'phone') {
            icon_class = 'foundicon-phone';
        } else if (txtType == 'website') {
            icon_class = 'foundicon-globe';
        } else {
            icon_class = 'foundicon-social-' + txtType;
        }
        return icon_class;
    }
    $("#account-add-new-meta").click(function(e) {
        var txtElement = $(this).parent().find("input[type=text]");

        if ($(txtElement).val().trim() == "") {
            return true;
        }

        var txtType = ( $(this).parent().find("select").val() ) ? $(this).parent().find("select").val() : 'phone' ;
        var icon_class = get_icon_class(txtType);
        if (txtType == 'email') {
            icon_class = 'foundicon-mail';
        } else if (txtType == 'phone') {
            icon_class = 'foundicon-phone';
        } else {
            icon_class = 'foundicon-social-' + txtType;
        }

        var tmpHTML = ""
        tmpHTML += '<div class="row collapse">'
        tmpHTML += '<div class="large-1 columns mobile-large-1">'
        tmpHTML += "<span class='prefix'><i class='" + icon_class + "'></i></span>"
        tmpHTML += '</div><div class="large-10 columns mobile-large-2">'
        tmpHTML += '<input type="text" data-metakey="' + txtType + '" value="' + $(txtElement).val() + '"/>'
        tmpHTML += '</div><div class="large-1 mobile-large-1 columns"><button class="postfix mybutton alert remove-contact-meta" type="button" ><i class="foundicon-minus"></i></button></div>'
        tmpHTML += '</div>'
        $(txtElement).val("");
        $("#account_meta_container").append(tmpHTML);
    });
    $(".sugget-meta-select").change(function() {
        var txtMeta = $(this).val().toLowerCase();
        var metaType = $(this).parent().parent().find("select");
        if (IsEmail(txtMeta)) {
            $(metaType).val("mail");
        } else if (txtMeta.indexOf("facebook.com") > -1) {
            $(metaType).val("facebook");
        } else if (txtMeta.indexOf("linkedin.") > -1) {
            $(metaType).val("linkedin");
        } else if (txtMeta.indexOf("@") == 0) {
            $(metaType).val("twitter");
        } else if (isUrl(txtMeta)) {
            $(metaType).val("website");
        }


    })
    function addError(element, message) {
        $(element).addClass("error");
        if ($(element).next().length > 0) {
            if ($(element).next().hasClass("error")) {
                $(element).next().html(message);
            } else {
                $(element).after("<small class='error'>" + message + "</small>");
            }
        } else {
            $(element).after("<small class='error'>" + message + "</small>");
        }
    }
    function removeError(element) {
        $(element).removeClass("error");
        if ($(element).next().length > 0) {
            if ($(element).next().hasClass("error")) {
                $(element).next().remove();
            }
        }
    }

	function check_closing_reason() {
		removeError($('#rtcrm_closing_reason'));
		if ( $('#rtcrm_post_status').val() !== 'closed' ) {
			return true;
		}

		if ( $('#rtcrm_closing_reason').val() !== '' ) {
			return true;
		}

		addError($('#rtcrm_closing_reason'),'You need to give at least one reason to close the lead');
		return false;
	}

	$('#rtcrm_closing_reason').on('change', function(e) {
		removeError($(this));
	});

	$('#rtcrm_post_status').on('change', function(e) {
		if($(this).val() == 'closed') {
			$('#rtcrm_closing_reason_wrapper').show();
		} else {
			$('#rtcrm_closing_reason_wrapper').hide();
		}
	});

    $("#form-add-account").submit(function(e) {
        try {
            var eleAccountName = $("#new-account-name");
            if ($(eleAccountName).val().trim() == "") {
                addError(eleAccountName, "Please Enter Account Name");
                return false;
            }
            removeError(eleAccountName);

            var eleAccountCountry = $("#new-account-country");
            if ($(eleAccountCountry).val().trim() == "") {
                addError(eleAccountCountry, "Please Enter Country");
                return false;
            }
            removeError(eleAccountCountry);
            var data = jQuery("#form-add-account").serializeArray();
            var errorFlag = false;
            var dataRequest = new Array();
            var tmpObject = new Object();
            $.each(data, function(key, value) {
                tmpObject[value.name] = value.value;
            })
            var rCount = 0;
			tmpObject['post_type'] = rtcrm_post_type;
            tmpObject["accountmeta"] = new Object()
            $("#account_meta_container input[type=text]").each(function() {
                type = $(this).data("metakey");
                if (type == "email") {
                    if (!IsEmail($(this).val())) {
                        errorFlag = true;
                        addError($(this), "Invalid Email");
                    } else {
                        removeError($(this));
                    }
                }
                if (tmpObject["accountmeta"][type] == undefined) {
                    tmpObject["accountmeta"][type] = new Array();
                }
                tmpObject["accountmeta"][type].push($(this).val());
            });

            if (errorFlag) {
                return false;
            }

            $.ajax({
                url: ajaxurl,
                dataType: "json",
                type: 'post',
                data: {
                    action: "rtcrm_add_account",
                    data: tmpObject
                },
                success: function(data) {
                    if (data.status) {

                        if (jQuery("#crm-account-" + data.data.id).length < 1) {
                            jQuery("#divAccountsList").append(genrate_account_li(data.data));
                        }
                        if (accountForContact) {
                            lastAccountId = data.data.id;
                            lastAccountDetail = data.data;
                        } else {
                            lastAccountId = 0;
                            lastAccountDetail = null;
                        }
                        $("#add-account").trigger('reveal:close');
                    } else {
                        alert(data.message)
                    }
                }
            });



        } catch (e) {
            console.log(e);
        }
        return false;


    });
    $("#form-add-contact").submit(function(e) {
        try {
            var eleAccountName = $("#new-contact-name");
            if ($(eleAccountName).val().trim() == "") {
                addError(eleAccountName, "Please Enter Contact Name");
                return false;
            }
            removeError(eleAccountName);

            var data = jQuery("#form-add-contact").serializeArray();
            var errorFlag = false;
            var dataRequest = new Array();
            var tmpObject = new Object();
            $.each(data, function(key, value) {
                tmpObject[value.name] = value.value;
            })
            var rCount = 0;
            tmpObject["contactmeta"] = new Object()
            $("#contact_meta_container input[type=text]").each(function() {
                type = $(this).data("metakey");
                if (type == "email") {
                    if (!IsEmail($(this).val())) {
                        errorFlag = true;
                        addError($(this), "Invalid Email");
                    } else {
                        removeError($(this));
                    }
                }
                if (tmpObject["contactmeta"][type] == undefined) {
                    tmpObject["contactmeta"][type] = new Array();
                }
                tmpObject["contactmeta"][type].push($(this).val());
            });

            if (errorFlag) {
                return false;
            }
			tmpObject['post_type'] = rtcrm_post_type;
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                type: 'post',
                data: {
                    action: "rtcrm_add_contact",
                    data: tmpObject
                },
                success: function(data) {
                    if (data.status) {
                        $("#add-contact").trigger('reveal:close');
                        if (jQuery("#hd-contact-" + data.data.id).length < 1) {
                            jQuery("#divContactsList").append(genrate_contact_li(data.data));
                            add_contact_meta("#hd-contact-meta-" + data.data.id,data.data.id)
                        }

                    } else {
                        alert(data.message)
                    }
                }
            });
        } catch (e) {
            console.log(e);
        }
        return false;


    });
	$("#form-add-post").submit(function(e) {
		try {
			var eleAccountName = $("#new_"+rtcrm_post_type+"_title");
			if ($(eleAccountName).val().trim() == "") {
				addError(eleAccountName, "Please Enter the Title");
				return false;
			}
			removeError(eleAccountName);
			return check_closing_reason();
		} catch (e) {
			console.log(e);
			return false;
		}
	});
    $("#form-add-post,#form-add-contact,#form-add-account").keypress(function(e){
        if(e.keyCode==13){
            e.preventDefault();
            return false;
        }
    })
    enter_key_to_click("search_contact","add-new-contact")
    enter_key_to_click("search_account","add-new-account")
    enter_key_to_click("new-account-meta","account-add-new-meta")
    enter_key_to_click("new-contact-meta","contact-add-new-meta")

    if($(".email-auto-complete").length > 0){
        $(".email-auto-complete").each(function() {
            $(this).tagit({
                tagSource: arr_comment_reply_to,
                singleFieldOverride: true,
                itemName: this.id
            });
        });
    }
    jQuery("#sendCommentMail").change(function(e) {
        if (jQuery(this).attr('checked') == undefined) {
            sendMailVal = 'member';
            jQuery.each(arr_comment_reply_to, function(i, person) {
                if (person.contact) {
                    var tHide = jQuery("#comment-reply-to").parent().parent().find("ul").find("input[value='" + person.email + "']");
                    if (tHide.length > 0) {
                        $(tHide).parent().remove();
                    }
                }
            });
        } else {
            sendMailVal = 'all';
            clientFlag = true;
            var tStr = '';
            jQuery.each(arr_comment_reply_to, function(i, person) {
                if (person.contact) {
                    if (jQuery("#comment-reply-to").html().indexOf(person.email) == -1) {
                        tStr += '<li class="tagit-choice ui-widget-content ui-state-default ui-corner-all"><span class="tagit-label">'
                        tStr += person.imghtml + person.label + '<a class="tagit-close custome-tagit-close"><span class="text-icon">×</span><span class="ui-icon ui-icon-close"></span></a>'
                        tStr += '</span><input type="hidden" style="display:none;" value="' + person.email + '" name="crm-email-to[' + new Date().getTime() + ']"></li>'
                    }
                }
            })
            jQuery("#comment-reply-to").parent().parent().find("ul").prepend(tStr);
            $(".custome-tagit-close").click(function() {
                $(this).parent().parent().remove();
            })
        }

    });

	jQuery(document).on('click', '.rtcrm_delete_attachment',function(e) {
		e.preventDefault();
		jQuery(this).parent().remove();
	});

	jQuery('#add_ticket_attachment').on('click', function(e) {
		e.preventDefault();
		if (file_frame_lead) {
			file_frame_lead.open();
			return;
		}
		file_frame_lead = wp.media.frames.file_frame = wp.media({
			title: jQuery(this).data('uploader_title'),
			searchable: true,
			button: {
				text: 'Attach Selected Files',
			},
			multiple: true // Set to true to allow multiple files to be selected
		});
		file_frame_lead.on('select', function() {
			var selection = file_frame_lead.state().get('selection');
			var strAttachment = '';
			selection.map(function(attachment) {
				attachment = attachment.toJSON();
				strAttachment = '<div class="large-12 mobile-large-3 columns attachment-item" data-attachment-id="'+attachment.id+'">';
				strAttachment += '<a target="_blank" href="'+attachment.url+'"><img height="20px" width="20px" src="' +attachment.icon + '" > '+attachment.filename+'</a>';
				strAttachment += '<a href="#" class="rtcrm_delete_attachment right">x</a>';
				strAttachment += '<input type="hidden" name="attachment[]" value="' + attachment.id +'" /></div>';

				jQuery("#attachment-container .scroll-height").append(strAttachment);

				// Do something with attachment.id and/or attachment.url here
			});
			// Do something with attachment.id and/or attachment.url here
		});
		file_frame_lead.open();
	});

jQuery('#btnCommentAttachment').click(function(event) {
                        event.preventDefault();
                        // If the media frame already exists, reopen it.
                        if (file_frame_followup) {
                            file_frame_followup.open();
                            return;
                        }
                        // Create the media frame.
                        file_frame_followup = wp.media.frames.file_frame = wp.media({
                            title: jQuery(this).data('uploader_title'),
                            searchable: true,
                            button: {
                                text: 'Attach Selected Files',
                            },
                            multiple: true // Set to true to allow multiple files to be selected
                        });

                        // When an image is selected, run a callback.
                        file_frame_followup.on('select', function() {
                            var selection = file_frame_followup.state().get('selection');
                           var strAttachment = '';
                            selection.map(function(attachment) {
                                attachment = attachment.toJSON();
                                strAttachment = '<li>';
                                strAttachment += '<span class="th radius" href="#">'
                                strAttachment += '<img src="' +attachment.icon + '">'
                                strAttachment += '<label>' + attachment.filename+ '</label> <a href="#removeAttachement"><i class="foundicon-remove"></i></a> </span>'
                                strAttachment += '<input type="hidden" name="attachemnt" value="' + attachment.id +'" /></li>'

                                jQuery("#attachmentList").prepend(strAttachment);

                                // Do something with attachment.id and/or attachment.url here
                            });
                            // Do something with attachment.id and/or attachment.url here
                        });

                        // Finally, open the modal
                        file_frame_followup.open();
                    });

 $(".add-followup").click(function(){
     $("#div-add-followup").reveal({opened:function(){
             reset_followup();
        }});

 })
 if( $(".datetimepicker").length > 0 ) {
	$(".datetimepicker").datetimepicker({
		   dateFormat: "M d, yy",
		   timeFormat: "hh:mm TT",
		   onClose: function(newDate,inst) {

			   if( $(this).hasClass("moment-from-now") ) {
				   var oldDate = $(this).attr("title");

				   if( newDate != "" && moment(newDate).isValid() ) {
					   $(this).val(moment(new Date(newDate)).fromNow());
					   $(this).attr("title",newDate);

					   if( $(this).next().length > 0 ) {
						   $(this).next().val(newDate);
					   }
				   } else if( oldDate != "" ) {
					   $(this).val(moment(new Date(oldDate)).fromNow());
					   $(this).attr("title",oldDate);

					   if( $(this).next().length > 0 ) {
						   $(this).next().val(newDate);
					   }
				   }
			   }
		   }
   });
 }

$(document).on("click", ".moment-from-now", function(e) {
    var oldDate = $(this).attr("title");

    if( oldDate != "" ) {
        $(this).datepicker("setDate",new Date($(this).attr("title")));
    }
});

$(".moment-from-now").each(function() {

    if($(this).is("input[type='text']") && $(this).val()!="")
       $(this).val(moment(new Date($(this).attr("title"))).fromNow());
    else if($(this).is(".comment-date"))
        $(this).html(moment(new Date($(this).attr("title"))).fromNow());
	else
		$(this).html(moment(new Date($(this).html())).fromNow());
});


$("#commentlist .comment-wrapper").filter(":first").show();

$(document).on("click", "#commentlist .comment-header", function(e) {
	var panel = $(this).next();
	var isOpen = panel.is(':visible');

	// open or close as necessary
	panel[isOpen? 'hide': 'show']()
		// trigger the correct custom event
		.trigger(isOpen? 'hide': 'show');

	// stop the link from causing a pagescroll
	return false;
});

$(document).on("click", '.accordion-expand-all',function (e) {

	var contentAreas = $("#commentlist .comment-wrapper");

	e.preventDefault();
	var isAllOpen = $(this).data('isallopen');

	contentAreas[isAllOpen? 'hide': 'show']()
		.trigger(isAllOpen? 'hide': 'show');

});

$(document).on({
	// whenever we open a panel, check to see if they're all open
	// if all open, swap the button to collapser
	show: function(){
		var isAllOpen = !$("#commentlist .comment-wrapper").is(':hidden');
		if(isAllOpen){
			$('.accordion-expand-all').html('<i class="general foundicon-up-arrow" title="Collapse All"></i>')
				.data('isallopen', true);
		}
	},
	// whenever we close a panel, check to see if they're all open
	// if not all open, swap the button to expander
	hide: function(){
		var isAllOpen = !$("#commentlist .comment-wrapper").is(':hidden');
		if(!isAllOpen){
			$('.accordion-expand-all').html('<i class="general foundicon-down-arrow" title="Expand All"></i>')
			.data('isallopen', false);
		}
	}
}, "#commentlist .comment-wrapper");


var saveFollowupText=$("#savefollwoup").html();
 $(".myfollowup").click(function(e){
      if($(this).data("type")=="ac_import"){
         $("#followup-slider").hide();
         $("#followup-editor").hide();
         $("#savefollwoup").html("Start Import");
         if($("#ac_project_id").val()==""){
            $("#ac_project_id").val(ac_default_project);
         }

     } else if($(this).data("type")=="gm_import"){
        $("#followup-slider").hide();
         $("#followup-editor").hide();
         $("#savefollwoup").html("Start Import");
     }else if($(this).data("type")=="mail"){
        $("#followup-slider").show();
        $("#followup-editor").show();
        $("#savefollwoup").html("Send");
     } else {
        $("#followup-slider").show();
        $("#followup-editor").show();
        $("#savefollwoup").html(saveFollowupText);
     }

     $("#followup-type").val($(this).data("type"));

 });

$("#savefollwoup").click(function(){
    var followuptype =$("#followup-type").val();
    if(followuptype=="ac_import"){
        get_task_comments($("#ac_project_id").val(),$("#ac_task_id").val());
        return false;
    }
    if(followuptype=="gm_import"){
        var email = $("#gm-from-email").val();
        if(email ==null || email == ""){
             alert("No Mailbox selected")
             return;
        }
        var thread_id= $("#gm-thread-id").val();
        if(thread_id ==""){
            alert("Please enter thread id");
            return;
        }

        var requestArray= new Object();
        requestArray["thread_id"]= thread_id;
        requestArray["action"] = "rtcrm_gmail_import_thread_request";
        requestArray["email"]=email;
        requestArray["post_id"]=$("#lead_id").val();;
         $.ajax({
                url: ajaxurl,
                dataType: "json",
                type: 'post',
                data: requestArray,
                success: function(data) {
                    if (data.status) {
                        $("#div-add-followup").trigger('reveal:close');
                    } else {
                        alert(data.message)
                    }
                }
            });

        return;
    }

    var requestArray= new Object();

	requestArray['post_type'] = rtcrm_post_type;
    requestArray["comment_id"]=  $("#edit-comment-id").val();
    requestArray["action"] = "rtcrm_add_new_followup_ajax";
    requestArray["followuptype"]=followuptype;

    requestArray["followup_post_id"] = $("#lead_id").val();
    requestArray["follwoup-time"]=$("#follwoup-time").val();

	requestArray['followup_private'] = ($('#followup-private').is(':checked')) ? 'yes' : 'no';

    if(jQuery("#followup_content").val()==""){
        jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
    }
    requestArray["followup_content"]=$("#followup_content").val();
    if(requestArray["followup_content"]==""){
        alert("Please Type Content Atleast");
        return false;
    }
    requestArray["attachemntlist"]= new Array();
    $("#attachmentList input").each(function(){
        requestArray["attachemntlist"].push($(this).val());
    })

    if (followuptype == "mail") {
            if ($("#email_from_ac").val()== null || $("#email_from_ac").val().trim() == "") {
                addError($("#email_from_ac"), "Invalid Mail form Address");
                return false;
            }
            removeError($("#email_from_ac"));
            requestArray["comment-reply-from"] = $("#email_from_ac").val();
            var errorFlag=true;
            requestArray["comment-reply-to"]= new Array();
            $("#comment-reply-to").parent().find("input[type=hidden]").each(function(){
                errorFlag=false;
                requestArray["comment-reply-to"].push($(this).val());
            });
            requestArray["comment-reply-cc"]= new Array();
            $("#comment-reply-cc").parent().find("input[type=hidden]").each(function(){
                errorFlag=false;
                requestArray["comment-reply-cc"].push($(this).val());
            });
            requestArray["comment-reply-bcc"]= new Array();
            $("#comment-reply-bcc").parent().find("input[type=hidden]").each(function(){
                errorFlag=false;
                requestArray["comment-reply-bcc"].push($(this).val());
            });

            if (errorFlag) {
                alert("Please Select Any Receipent");
                return false;
            }
        }
			requestArray['user_edit'] = rtcrm_user_edit;
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                type: 'post',
                data: requestArray,
                success: function(data) {
                    if (data.status) {
						jQuery("#followup_content").val('');
                        $("#div-add-followup").trigger('reveal:close');
                        if($("#edit-comment-id").val() !="") {
                            $("#comment-" + $("#edit-comment-id").val()).remove();
                            $("#header-" + $("#edit-comment-id").val()).remove();
						}
                        $("#commentlist").prepend(data.data);
						$("#commentlist .comment-wrapper").filter(":first").show();
//                        var date = $("#commentlist .comment-header:first-child .comment-date");
//                        date.attr("title",date.html());
//                        date.html(moment(new Date(date.attr("title"))).fromNow());
                    } else {
                        alert(data.message)
                    }
                }
            });

});

	if($("#add_newpost_meta_key").length > 0 ) {
		$("#add_newpost_meta_key").autocomplete({

			source: function (request, response) {
				var term = $.ui.autocomplete.escapeRegex(request.term)
					, startsWithMatcher = new RegExp("^" + term, "i")
					, startsWith = $.grep(arr_leadmeta_key, function(value) {
						return startsWithMatcher.test(value.meta_key || value.label || value.value || value);
					})
					, containsMatcher = new RegExp(term, "i")
					, contains = $.grep(arr_leadmeta_key, function (value) {
						return $.inArray(value, startsWith) < 0 &&
							containsMatcher.test(value.meta_key || value.label || value.value || value);
					});

				response(startsWith.concat(contains));
			},
			focus: function(event, ui) {

			},
			select: function(event, ui) {
				$(this).val(ui.item.meta_key);
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul, item) {
			return $("<li></li>").data("ui-autocomplete-item", item).append("<a>" + item.meta_key + "</a>").appendTo(ul);
		};
	}

	meta_count = 12345;
    $("#add_new_meta").click(function(e){
        var meta_key= $("#add_newpost_meta_key").val();
        var meta_value= $("#add_newpost_meta_Value").val();
        if($.trim(meta_key)=="")
            return false;
        $("#add_newpost_meta_key").val("");
        $("#add_newpost_meta_Value").val("");

        var tmpstr=' <div class="large-12 columns" >'
        tmpstr +='<div class="large-4 columns">'
        tmpstr +='<span>'
        tmpstr +='<input type="text" name="postmeta[' + meta_count +'][key]" value="' + meta_key +'" /></span>'
        tmpstr +='</div><div class="large-7 columns">'
        tmpstr +='<input type="text" name="postmeta[' + meta_count +'][value]" value="' + meta_value +'" />'
        tmpstr +='</div><div class="large-1 columns">'
        tmpstr +='<button class="removeMeta"><i class="foundicon-minus"></i></button>'
        tmpstr +='</div></div>'
        meta_count++;
        $("#add-meta-container").append(tmpstr);
    })

    $(document).on('click',"a[href=#editRemoveAttachemnt]",function(){
        $(this).parent().remove();
    });
    $(document).on('click',"a[href=#removeAttachement]",function(){
        $(this).parent().parent().remove();
    });
    $(document).on('click',".removeMeta",function(){
        $(this).parent().parent().remove();
    })

	exf_count = 12345;
    $("#add_new_ex_file").click(function(e){
        var title = $("#add_ex_file_title").val();
        var link = $("#add_ex_file_link").val();
        if($.trim(link)=="")
            return false;
        $("#add_ex_file_title").val("");
        $("#add_ex_file_link").val("");

        var tmpstr=' <div class="large-12 columns" >';
        tmpstr +='<div class="large-3 columns">';
        tmpstr +='<input type="text" name="lead_ex_files[' + exf_count +'][title]" value="' + title +'" />';
        tmpstr +='</div><div class="large-8 columns">';
        tmpstr +='<input type="text" name="lead_ex_files[' + exf_count +'][link]" value="' + link +'" />';
        tmpstr +='</div><div class="large-1 columns">';
        tmpstr +='<button class="removeMeta"><i class="foundicon-minus"></i></button>';
        tmpstr +='</div></div>';
        exf_count++;
        $("#external-files-container").append(tmpstr);
    });


//** Active Collab Importer  **/

 function get_task_comments(project_id,task_id){
     if(ac_auth_token== ''){
         alert("Please set active collab token from crm setting page");
         return false;
     }
    if($.trim(task_id)==""){
        alert("Invalid Task Id");
        return false;
    }

    if($.trim(task_id)==""){
        alert("Invalid Task Id");
        return false;
    }
    if(isNaN(task_id)){
        alert("Invalid Task Id");
        return false;
    }


    $.ajax({
      url: 'http://ac.rtcamp.com/api.php?path_info=/projects/' + project_id +'/tasks/'+ task_id + '/comments&auth_api_token=' + ac_auth_token +'&format=json',
      type: 'GET',
      dataType: 'json',
      success: function(data) {
          MyComments= new Array();
          completeCount=0;
          import_activecollab_ticket_comments(project_id,task_id,data)
      },
      error: function(xhr, textStatus, errorThrown) {
        alert("Error in Fetching comments");
      }
    });

 }
 var UserMap= new Object();
 var MyComments= new Array();
 var completeCount=0;
 function import_activecollab_ticket_comments(project_id,task_id,ac_comment_data){
   $.each(ac_comment_data,function(key,comment){
       $.ajax({
                url: 'http://ac.rtcamp.com/api.php?path_info=/projects/' + project_id +'/tasks/'+ task_id + '/comments/' + comment.id+ '&auth_api_token=' + ac_auth_token +'&format=json',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $objTemp= new Object();
                    $objTemp.body_formatted= data.body_formatted;
                    $objTemp.created_by= data.created_by;
                    $objTemp.created_on= data.created_on;
                    $objTemp.id= data.id;
                    $objTemp.attachments_count= data.attachments_count;
                    $objTemp.attachments= data.attachments;
                    MyComments.push($objTemp);
                    completeCount++;
                    if(completeCount==ac_comment_data.length){
                        send_import_request(project_id,task_id);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert("Error in Fetching comments");
                }, complete:function(e){
                    if($("body").find(".loading-overlay").length < 1)
                        $("body").append(LOADER_OVERLAY);
                }
              });
   });
    function send_import_request(project_id,task_id){

        MyComments.sort(function(a, b) {
            var x = a.id;
            var y = b.id;
            return x - y;
        });

           $.ajax({
          url: ajaxurl,
          type: 'POST',
          dataType: 'json',
          data:{
              action : 'activecollab_task_comment_import_ajax',
              post_id:$("#lead_id").val(),
              project_id:project_id,
              task_id:task_id,
              auth_token:ac_auth_token,
              comment_data:JSON.stringify(MyComments),
          },
          success: function(data) {
              if(data.status){
                  $("#commentlist").prepend(data.data);
                  $("#div-add-followup").trigger('reveal:close');
                  alert("Data imported Sucessfully");
              }
          },
          error: function(xhr, textStatus, errorThrown) {
            alert("error in import");
          },
          complete:function(e){
            $("body").find(".loading-overlay").remove();
          }
        });
    }
 }

    $(document).on("click","a[href=#deleteFollowup]",function(e){
      e.preventDefault();
        var r = confirm("Are you sure you want to remove this Followup?");
        if (r != true) {
            e.preventDefault();
            return false;
        }
        var del_comment_id = $(this).data("comment-id");
        var that=this;
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          dataType: 'json',
          data:{
              action : 'crm_delete_followup',
              comment_id:del_comment_id,
          },
          success: function(data) {
              if(data.status){
                  $("#comment-" + del_comment_id ).fadeOut(500, function() { $(this).remove(); });
                  $("#header-" + del_comment_id ).fadeOut(500, function() { $(this).remove(); });
              }else{
                alert("error in delete comment from server");
              }
          },
          error: function(xhr, textStatus, errorThrown) {
            alert("error in remove ");
          }

        });
    });
    var followEditFlag = true;
    var editFollowupData;
    $(document).on("click","a[href=#editFollowup]",function(e){
        e.preventDefault();
        var edit_comment_id = $(this).data("comment-id");
        var that =  $("div#comment-" + edit_comment_id )
//        if($(that).find(".comment-type").text().toLowerCase()=="mail"){
//            alert("can't edit emails");
//            return false;
//        }
        $("#div-add-followup").reveal({
            opened: function(){
                jQuery("#attachmentList li:not(.add)").remove()
                try{
                if(tinyMCE.get('followup_content')!=undefined){
                    tinyMCE.get('followup_content').setContent($(that).find(".comment-content").html());
                    tinyMCE.get('followup_content').onKeyDown.add(function(){
                        jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
                    });
                    tinyMCE.get('followup_content').onChange.add(function(){
                        jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
                    });
                }
                } catch(e){

                }

                jQuery("#followup_content").val($(that).find(".comment-content").html());
                $("#edit-comment-id").val(edit_comment_id);
                $("#follwoup-time").val($(that).find(".comment-date").text())
				var rtcrm_privacy = $('#header-'+edit_comment_id).find('.rtcrm_privacy').data('crm-privacy');
				if(rtcrm_privacy == 'yes') {
					$('#followup-private').attr('checked', 'checked').change();
				} else {
					$('#followup-private').removeAttr('checked').change();
				}
                $("a[data-type=note]").click(); //+$(that).find(".comment-type").text().toLowerCase()+
                $("#vertical-tab-header").hide();
                $("#savefollwoup").val("Update Followup");
                $("#attachmentList li:not(.add)").remove();
                $("#attachmentList").prepend($(that).find(".comment_attechment").html());

           }
        });


    });
function reset_followup(){
	jQuery("#div-add-followup").find(".tagit-choice").remove();
	$("#savefollwoup").val("Save Followup");
	$("#vertical-tab-header").show();
	$("#attachmentList li:not(.add)").remove();

	if(tinyMCE.get('followup_content')!=undefined){
		try{ tinyMCE.get('followup_content').setContent(""); } catch( e ) { }
		try{
			tinyMCE.get('followup_content').onKeyDown.add(function(){
				jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
			});
		} catch( e ) { }
		try{
			tinyMCE.get('followup_content').onChange.add(function(){
				jQuery("#followup_content").val(tinyMCE.get('followup_content').getContent());
			});
		} catch(e){}
	}

	jQuery("#followup_content").val("");
	$("#edit-comment-id").val("")
	$("#follwoup-time").val("");
	$('#followup-private').removeAttr('checked').change();
}
	jQuery(".comment-content").each(function(){
		if(jQuery(this).find(".gmail_quote:first").length > 0)
		{
			var that = jQuery(this).find(".gmail_quote:first")[0];
				  jQuery(that).before("<a href='#toggleQuote'>show</a>");
		jQuery(that).toggle();
		}

	})

	jQuery(document).on("click","a[href=#toggleQuote]", function(e){
		jQuery(this).siblings(".gmail_quote").toggle();
	});
	$("#button-trash").click(function(){
		var r = confirm("Are you sure you want to move this lead to trash?");
		if (r != true) {
			return false;
		}
		window.location="edit.php?post_type="+rtcrm_post_type+"&page=rtcrm-add-"+rtcrm_post_type+"&action=trash&"+rtcrm_post_type+"_id=" + $("#lead_id").val();
	});

	$('a.close').on('click', function(e) { e.preventDefault(); $(this).parent().remove(); });

	$("#rtcrm-adv-module-toggle").click(function(e) {
		$("#rtcrm-adv-module-div").toggle('slide');
	});

	$(".rtcrm_enable_mapping").on('change', function(e){
		e.preventDefault();
		var update_mapping_id = $(this).data("mapping-id");
		var that=this;
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data:{
				action : 'crm_enable_mapping',
				mapping_id:update_mapping_id,
				mapping_enable:that.checked

			},
			success: function(data) {
				if(data.status){

				}else{
					alert("error in updating mapping from server");
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				alert("error in update ");
			}

		});
	});

	$(".rtcrm_delete_mapping").on('click', function(e){
		e.preventDefault();
		var r = confirm("Are you sure you want to remove this Mapping?");
		if (r != true) {
			e.preventDefault();
			return false;
		}
		var del_mapping_id = $(this).data("mapping-id");
		var that=this;
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data:{
				action : 'crm_delete_mapping',
				mapping_id:del_mapping_id
			},
			success: function(data) {
				if(data.status){
					$("#mapping_" + del_mapping_id ).fadeOut(500, function() { $(this).remove(); });
				}else{
					alert("error in delete mapping from server");
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				alert("error in remove ");
			}

		});
	});

});

var file_frame_lead, file_frame_followup;
function enter_key_to_click(textbox_id,button_id){
    jQuery("#" + textbox_id ).keypress(function(e){
        if(e.keyCode==13){
            e.preventDefault();
            jQuery("#" + button_id ).click();
            return false;
        }
    });
}
function genrate_followup_html(comment){
   return '<tr class="row"><td class="large-7"><div class="row"><div class="large-2 columns"><a href="#" class="th radius">'
    + useravtar +  '</a><label><b>'+ username +
    +'</b></label></div><div class="large-10 columns">'
    + comment.comment_content + '</div></div></td><td class="large-2 centered">'
    + '<label><b>'+ comment.comment_type + '</b></label><div class="row collapse"><p>'
    + 'NOW</p></div></td><td class="large-3">'
    +'</td></tr>'

}

function genrate_account_li(data){
    return "<li id='crm-account-" + data.id + "' class='contact-list' ><div class='row collapse'><div class='large-2 columns'> " + data.imghtml
                            + "</div><div class='large-9 columns'><a target='_blank' class='heading' href='" +  data.url  + "' title='" + data.label +"'>" + data.label +"</a>"
                            + "</div><div class='large-1 columns'><a href='#removeAccount'><i class='foundicon-remove'></i></a><input type='hidden' name='post[accounts][]' value='" + data.id + "' /></div>"
                            + "</div></li>";
}
function genrate_contact_li(data){
    return "<li id='hd-contact-" + data.id + "' class='contact-list' ><div class='row collapse'><div class='large-2 columns'> " + data.imghtml
            + "</div><div id='hd-contact-meta-" + data.id + "'  class='large-9 columns'> <a target='_blank' class='heading' href='" +  data.url  + "' title='" + data.label +"'>" + data.label +"</a>"
            + "</div><div class='large-1 columns'><a href='#removeContact'><i class='foundicon-remove'></i></a><input type='hidden' name='post[contacts][]' value='" + data.id + "' /></div>"
            + "</div></li>"

}
function IsEmail(email) {
    var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (!regex.test(email)) {
        return false;
    } else {
        return true;
    }
}

function isUrl(value) {
    var urlregex = new RegExp("^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");
    if (urlregex.test(value)) {
        return (true);
    }
    return (false);
}
function selecttab(obj){
    jQuery(".section-container.vertical-nav section").each(function(){
        jQuery(this).removeClass("active");
    });
	jQuery('a[data-tab='+obj+']').parent().parent().addClass('active');
    jQuery(".vertical-tab-content .content").each(function(){
        if(!jQuery(this).hasClass("hide")){
            jQuery(this).addClass("hide");
        }

    });

    jQuery("#tab" + obj).removeClass("hide");
}
