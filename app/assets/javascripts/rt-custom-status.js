var status_dropdown_visible = 1
var attachmentSep = '';
var attachmentString = '';
var arr_failed_lead = Array();

function admin_side_menu_patch() {
    jQuery("li.toplevel_page_crm-dashboard").removeClass("wp-not-current-submenu menu-top menu-icon-generic menu-top-first menu-top-last")
    jQuery("li.toplevel_page_crm-dashboard").addClass("wp-has-current-submenu wp-menu-open open-if-no-js menu-top menu-icon-generic menu-top-first")
    jQuery("a.toplevel_page_crm-dashboard").removeClass("wp-not-current-submenu menu-top menu-icon-generic menu-top-first menu-top-last opensub")
    jQuery("a.toplevel_page_crm-dashboard").addClass("wp-has-current-submenu wp-menu-open open-if-no-js menu-top menu-icon-post menu-top-first")
    jQuery("li.toplevel_page_crm-dashboard a").each(function(e) {
        if ((window.location.href).indexOf(this.href) != -1) {
            jQuery(this).parent().addClass("current");
            jQuery(this).addClass('current')
        }
    });
}

jQuery(document).ready(function($) {

	/**
	 * WordPress Menu Hack for Dashboard
	 */
	if (typeof rt_crm_top_menu != 'undefined' && typeof rt_crm_dashboard_url != 'undefined') {
		$('#' + rt_crm_top_menu + ' ul li').removeClass('current');
		$('#' + rt_crm_top_menu + ' ul li a').removeClass('current');
		$('#' + rt_crm_top_menu + ' ul li a').each(function(e) {
			if (this.href == rt_crm_dashboard_url) {
				$(this).parent().addClass("current");
				$(this).addClass('current');
			}
		});
	}

	/**
	 *	Studio Settings Page
	 */
	if ($(".rtcamp-user-ac").length > 0) {
		jQuery.each(arr_rtcamper, function(ind, val) {
			$("div[acl-user=" + val.id + "]").each(function() {
				$(this).prepend(val.imghtml + val.label);
			})
			//acl-user
		});

		jQuery(".rtcamp-user-ac").autocomplete({
			source: arr_rtcamper,
			select: function(event, ui) {
				var tmpName = this.name + "_" + ui.item.id;
				if (jQuery("#" + tmpName).length == 0) {
					jQuery(this).after("<div class='mail-acl_user' id='" + tmpName + "'>" + ui.item.imghtml + ui.item.label + "&nbsp;&nbsp;<a href='#removeAccess'>X</a><input type='hidden' name='allow_users[]' value='" + ui.item.id + "' /></div>")
				}
				jQuery(this).val("");
				return false;
			}
		}).data("ui-autocomplete")._renderItem = function(ul, item) {
			return $("<li></li>").data("ui-autocomplete-item", item).append("<a class='ac-subscribe-selected'>" + item.imghtml + "&nbsp;" + item.label + "</a>").appendTo(ul);
		};
		jQuery(document).on('click', "a[href=#removeAccess]", function(e) {
			e.preventDefault();
			$(this).parent().remove();
		});

	}
	$(".remove-google-ac").click(function(e) {
		var r = confirm("Are you sure you want to remove this email A/C ?");
		if (r == true) {

		}
		else
		{
			e.preventDefault();
			return false;
		}
	});



	/**
	 * Gravity Importer Handlebars
	 */
	Handlebars.registerHelper('mapfieldnew', function(obj1, obj2) {
        if (obj1.toLowerCase().indexOf(obj2.toLowerCase()) > -1) {
            return new Handlebars.SafeString(
                    " selected "
                    );
        } else {
            return new Handlebars.SafeString(" ");
        }
    });

	//set normal priority
//    jQuery("[name=priority]").val(3);

    $("#postcustomstuff input[type=text],#postcustomstuff textarea").each(function(e) {

        var test_val= $(this).val();
        if (IsEmail(test_val)){
             test_val = "<a target='_blank' href='mailto:"+ test_val+"'> " + test_val  + "</a>";
        } else if (isUrl(test_val)){
            test_val = "<a target='_blank' href='"+ test_val+"'> " + test_val  + "</a>";
        }

        $(this).parent().append("<label class='custome-readonly'>" + test_val + "</label>");
        $(this).siblings(".submit").hide();
        $(this).hide();
        $(this).parent().dblclick(function() {
            $(this).parent().find("input[type=text],textarea,.submit").each(function() {
                $(this).show();
            })
            $(this).parent().find(".custome-readonly").each(function() {
                $(this).hide();
            })
        })
    });
	$(document).on("dblclick", ".custome-readonly", function() {

        $(this).parent().parent().find("input[type=text],textarea,.submit").each(function() {
            $(this).show();
        })
        $(this).parent().parent().find(".custome-readonly").each(function() {
            $(this).hide();
        })

    })


	$("#newmeta").hide();
    $("#newmeta").prev().dblclick(function() {
        $("#newmeta").toggle();
    })
    $(".revertChanges").click(function(e) {
        var r = confirm("This will delete all the crm data created in this trannsaction ! Are you sure you want to continue ?");
        if (r == true) {

        }
        else
        {
            e.preventDefault();
            return false;
        }
    })


	/**
	 * Gravity Importer Page
	 */
	try {

        $(document).on("click", "a[href=#mapField]", function(e) {
            e.preventDefault();
            var fieldMap = this;
            if ($(this).next().length > 0) {
                $(this).next().toggle( );
                return false;
            }
            var ajaxdata = {
                action: 'rtcrm_defined_map_feild_value',
                mapSourceType: $("#mapSourceType").val(),
                map_form_id: jQuery('#mapSource').val(),
                field_id: $(fieldMap).data("field")

            }

            jQuery.post(ajaxurl, ajaxdata, function(response) {
                if (response.length < 1) {
                    alert("Too many distinct value, Can't Map");
                    $("[name=" + $(fieldMap).data("field-name") + "]").parent().parent().show();
                    $(fieldMap).prev().addClass("form-invalid");
                    $(fieldMap).parent().next().html("");
                    $(fieldMap).prev().val("");
                    $(fieldMap).remove();
                    return false;
                }
                var source = $("#map_table_content").html();
                var template = Handlebars.compile(source);

                var arrTmp = Object();
                arrTmp.name = '';
                arrTmp.data = response;
                arrTmp.mapData = window[$(fieldMap).data("map-data")];
                $(fieldMap).after(template(arrTmp));

                $(fieldMap).parent().find("tr").each(function() {
                    var tempTD = $(this).children();
                    var tempSelectOption = $(tempTD[1]).find("select option");
                    var searchQ = jQuery(tempTD[0]).text().trim().toLowerCase();
                    $(tempSelectOption).each(function() {
                        if (jQuery(this).text().trim().toLowerCase().indexOf(searchQ) != -1) {
                            $(tempTD[1]).find("select").val(jQuery(this).attr("value"));
                            $(tempTD[1]).find("select").change();
                            return false;
                        }
                    });

                });



            })

        });
        $(document).on("change", ".map_form_fixed_fields", function(e) {
            e.preventDefault();
            var field_name = jQuery(this).val();
            if (field_name == "")
                return false;
            if (arr_map_fields[field_name].type != undefined && arr_map_fields[field_name].type != "defined") {
                if ($(this).next().length > 0) {
                    $("[name=" + $(this).next().data("field-name") + "]").parent().parent().show();
                    $(this).next().remove();

                    $(this).next().remove();
                }
                if (arr_map_fields[field_name].type == "key") {
                    var source = $("#key-type-option").html();
                    var template = Handlebars.compile(source);
                    var tmpArr = window[arr_map_fields[field_name].key_list];
                    var tmpStr = "<select name='key-" + field_name + "'>";
                    tmpStr += template(tmpArr) + "</select>"
                    $(this).parent().append(tmpStr);
                }
                $(this).parent().next().html("<input type='text' name='default-" + field_name + "' value='' />");


            } else {
                var source = $("#defined_filed-option").html();
                var template = Handlebars.compile(source);

                var tmpStr = "<select name='default-" + field_name + "'>";
                var tmpArr = window[arr_map_fields[field_name].definedsource];

                tmpStr += template(tmpArr) + "</select>"
                $(this).parent().next().html(tmpStr);
                if ($(this).next().length < 1) {
                    $(this).after("<a data-field-name='" + field_name + "' href='#mapField' data-map-data='" + arr_map_fields[field_name].definedsource + "' data-field='" + this.name.replace("field-", "") + "' > Map </a>");
                    if(field_name !="product")
                        $("[name=" + field_name + "]").parent().parent().hide();
                    $(this).next().click();
                } else {
                    $("[name=" + $(this).next().data("field-name") + "]").parent().parent().show();
                    $(this).next().remove();
                    $(this).next().remove();
                    $(this).after("<a data-field-name='" + field_name + "' href='#mapField' data-map-data='" + arr_map_fields[field_name].definedsource + "' data-field='" + this.name.replace("field-", "") + "' > Map </a>");
                    if(field_name !="product")
                        $("[name=" + field_name + "]").parent().parent().hide();
                    $(this).next().click();

                }
            }
        })

        if (arr_map_fields != undefined) {
            var count = 1;

            var sucessCount = 0;
            var failCount = 0;
            var forceImport = false;
            jQuery("#rtCrmMappingForm .wp-list-table tbody tr").each(function() {
                var tempTD = $(this).children();
                var tempSelectOption = $(tempTD[1]).find("select option");
                var searchQ = jQuery(tempTD[0]).text().trim().toLowerCase();
                $(tempSelectOption).each(function() {
                    if (jQuery(this).text().trim().toLowerCase().indexOf(searchQ) != -1) {
                        $(tempTD[1]).find("select").val(jQuery(this).attr("value"));
                        $(tempTD[1]).find("select").change();
                        return false;
                    }
                });

            });
            var otherCount = 1;
            jQuery(document).on("change", ".other-field", function() {
                if ($(this).val() == '') {
                    return false;
                }
                if ($("#otherfeild" + otherCount).length > 0)
                    return false;
                var tempParent = $(this).parent().parent();
                $(tempParent).after("<tr>" + $(tempParent).html().replace(this.name, "otherfield" + otherCount).replace(this.name, "otherfield" + otherCount) + "</tr>");
                otherCount++;
            })
            var postdata;
            jQuery('#rtCrmMappingForm').submit(function(e) {
                e.preventDefault();
                postdata = new Object;
                var data = jQuery(this).serializeArray();
                var count = jQuery('#mapEntryCount').val();
                var errorFlag = false;
                jQuery.each(data, function(i, mapping) {
                    if (mapping.value == '')
                        return true;

                    var temp = mapping["name"];
                    if (temp.indexOf('default-') > -1)
                        return true;
                    if (temp.indexOf('key-') > -1)
                        return true;
                    if (temp.indexOf('field-') > -1) {
                        //checking Assigned  or not
                        if (postdata[mapping.value] == undefined) {

                            if (arr_map_fields[mapping.value].multiple) {
                                //multiple but assigne first time
                                postdata[mapping.value] = Array();
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = $($('#' + mapping["name"]).parent().next().children("input,select")).val();
                                if (arr_map_fields[mapping.value].type != undefined && arr_map_fields[mapping.value].type == "defined") {
                                    var arrMapSelects = $("#" + this.name).siblings("table").find("select");
                                    if (arrMapSelects.length < 1) {
                                        errorFlag = true;
                                        alert("Maping not Defined for " + arr_map_fields[mapping.value].display_name)
                                        $("#" + mapping["name"]).addClass("form-invalid");
                                        $("#" + mapping["name"]).focus();
                                        return false;
                                    } else {
                                        var tObj = Object();
                                        $.each(arrMapSelects, function(indx, obj) {
                                            tObj[$(obj).data("map-value")] = $(this).val();
                                        });
                                        tmpObj.mappingData = tObj;
                                    }

                                } else if (arr_map_fields[mapping.value].type == "key") {
                                    var arrMapSelects = $("#" + this.name).siblings("select");
                                    if (arrMapSelects.length > 0) {
                                        tmpObj.keyname = $(arrMapSelects).val();
                                    } else {
                                        tmpObj.keyname = "";
                                    }

                                } else {

                                    tmpObj.mappingData = null;
                                }

                                postdata[mapping.value].push(tmpObj);
                            } else {
                                //multiple not allowed
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = $($('#' + mapping["name"]).parent().next().children("input,select")).val();
                                if (arr_map_fields[mapping.value].type != undefined && arr_map_fields[mapping.value].type == "defined") {
                                    var arrMapSelects = $("#" + this.name).siblings("table").find("select");
                                    if (arrMapSelects.length < 1) {
                                        errorFlag = true;
                                        alert("Maping not Defined for " + arr_map_fields[mapping.value].display_name)
                                        $("#" + mapping["name"]).addClass("form-invalid");
                                        $("#" + mapping["name"]).focus();
                                        return false;
                                    } else {
                                        var tObj = Object();
                                        $.each(arrMapSelects, function(indx, obj) {
                                            tObj[$(obj).data("map-value")] = $(this).val();
                                        });
                                        tmpObj.mappingData = tObj;
                                    }

                                } else if (arr_map_fields[mapping.value].type == "key") {
                                    var arrMapSelects = $("#" + this.name).siblings("select");
                                    if (arrMapSelects.length > 0) {
                                        tmpObj.keyname = $(arrMapSelects).val();
                                    } else {
                                        tmpObj.keyname = "";
                                    }

                                } else {

                                    tmpObj.mappingData = null;
                                }

                                postdata[mapping.value] = tmpObj; //mapping["name"];
                            }

                        } else {
                            if (arr_map_fields[mapping.value].multiple) {
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = $($('#' + mapping["name"]).parent().next().children("input,select")).val();
                                if (arr_map_fields[mapping.value].type != undefined && arr_map_fields[mapping.value].type == "defined") {
                                    var arrMapSelects = $("#" + this.name).siblings("table").find("select");
                                    if (arrMapSelects.length < 1) {
                                        errorFlag = true;
                                        alert("Maping not Defined for " + arr_map_fields[mapping.value].display_name)
                                        $("#" + mapping["name"]).addClass("form-invalid");
                                        $("#" + mapping["name"]).focus();
                                        return false;
                                    } else {
                                        var tObj = Object();
                                        $.each(arrMapSelects, function(indx, obj) {
                                            tObj[$(obj).data("map-value")] = $(this).val();
                                        });
                                        tmpObj.mappingData = tObj;
                                    }

                                } else if (arr_map_fields[mapping.value].type == "key") {
                                    var arrMapSelects = $("#" + this.name).siblings("select");
                                    if (arrMapSelects.length > 0) {
                                        tmpObj.keyname = $(arrMapSelects).val();
                                    } else {
                                        tmpObj.keyname = "";
                                    }

                                } else {

                                    tmpObj.mappingData = null;
                                }

                                postdata[mapping.value].push(tmpObj);
                            } else {
                                errorFlag = true;
                                alert("Multiple " + arr_map_fields[mapping.value].display_name + " not allowed")
                                $("select,input[type=textbox]").each(function(e) {
                                    if ($(this).val() == mapping["value"]) {
                                        $(this).addClass("form-invalid");
                                    }
                                })
                                $("#" + mapping["name"]).addClass("form-invalid");
                                $("#" + mapping["name"]).focus();
                                return false;
                            }
                        }
                    } else if (temp.indexOf('otherfield') > -1) {
                        var mapElement = $("#" + mapping.name);
                        mapping.name = $(mapElement).val();
                        if ($.trim(mapping.name) == "") {

                        } else if (postdata[mapping.value] == undefined) {
                            if (arr_map_fields[mapping.value].multiple) {
                                postdata[mapping.value] = Array();
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = '';

                                postdata[mapping.value].push(tmpObj);
                            } else {
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = '';
                                postdata[mapping.value] = tmpObj;
                            }

                        } else {
                            if (arr_map_fields[mapping.value].multiple) {
                                var tmpObj = Object();
                                tmpObj.fieldName = mapping["name"];
                                tmpObj.defaultValue = '';
                                postdata[mapping.value].push(tmpObj);
                            } else {
                                errorFlag = true;
                                alert("Multiple " + arr_map_fields[mapping.value].display_name + " not allowed")
                                $("select,input[type=textbox]").each(function(e) {
                                    if ($(this).val() == mapping["value"]) {
                                        $(this).addClass("form-invalid");
                                    }
                                })
                                $(mapElement).addClass("form-invalid");
                                $(mapElement).focus();
                                return false;
                            }
                        }

                    } else {
                        if ($("[name=" + mapping.name + "]").parent().parent().css("display") != 'none') {
                            var tmpObj = Object();
                            tmpObj.fieldName = mapping.value;
                            tmpObj.defaultValue = '';
                            if (postdata[mapping.name] == undefined) {
                                if (arr_map_fields[mapping.name] != undefined && arr_map_fields[mapping.name].multiple) {
                                    tmpObj.mappingData = null;
                                    postdata[mapping.name] = Array();
                                    postdata[mapping.name].push(tmpObj);
                                } else {
                                    postdata[mapping.name] = tmpObj;
                                }
                            } else {
                                if (arr_map_fields[mapping.name] != undefined && arr_map_fields[mapping.name].multiple) {
                                    tmpObj.mappingData = null;
                                    postdata[mapping.name].push(tmpObj);
                                } else {
                                    errorFlag = true;
                                    alert("Multiple " + arr_map_fields[mapping.name].display_name + " not allowed")
                                    $("select,input[type=textbox]").each(function(e) {
                                        if ($(this).val() == mapping.name) {
                                            $(this).addClass("form-invalid");
                                        }
                                    })
                                    $("#" + mapping["name"]).addClass("form-invalid");
                                    $("#" + mapping["name"]).focus();
                                    return false;
                                }
                            }



                        }
                    }
                });
                if (errorFlag)
                    return false;
                jQuery.each(arr_map_fields, function(i, map_field) {
                    if (map_field.required) {
                        if (postdata[map_field.slug] == undefined) {
                            alert(map_field.display_name + " is required");
                            errorFlag = true;
                            return false;
                        }
                    }

                });
                if (errorFlag)
                    return false;
                jQuery('#rtCrmMappingForm').slideUp();
                jQuery(".myerror").addClass("error");
                jQuery(".myupdate").addClass("updated");
                jQuery('#startImporting').slideDown();
                $("#progressbar").progressbar({
                    value: 0,
                    max: arr_lead_id.length
                });

                if (jQuery("#forceimport").attr('checked') == undefined) {
                    forceImport = "false";
                } else {
                    forceImport = "true";
                }
                var rCount = 0;
                var ajaxdata = {
                    action: 'rtcrm_map_import',
                    mapSourceType: $("#mapSourceType").val(),
                    map_data: postdata,
                    map_form_id: jQuery('#mapSource').val(),
                    map_row_index: rCount,
                    gravity_lead_id: parseInt(arr_lead_id[rCount].id),
                    forceimport: forceImport,
                    trans_id: transaction_id,
					rtcrm_module: jQuery('#rtcrm_module').val()
                }
                try {
                    do_ajax_in_loop(ajaxdata, rCount);
                } catch (e) {

                }
                return false;
            });

        }
        var lead_index = 0;
        $(document).on('click', 'a[href=#dummyDataNext]', function(e) {
            e.preventDefault();
            lead_index++;
            if (arr_lead_id.length - 1 < lead_index) {
                lead_index = 0;
            }
            load_dummy_data(lead_index);
        });
        $(document).on('click', 'a[href=#dummyDataPrev]', function(e) {
            e.preventDefault();
            lead_index--;
            if (lead_index < 0) {
                lead_index = arr_lead_id.length - 1;
            }
            load_dummy_data(lead_index);
        });

        function load_dummy_data(lead_id) {
			try {
				var ajaxdata = {
					action: 'rtcrm_gravity_dummy_data',
					mapSourceType: $("#mapSourceType").val(),
					map_form_id: jQuery('#mapSource').val(),
					dummy_lead_id: arr_lead_id[lead_id].id

				}
				jQuery.post(ajaxurl, ajaxdata, function(response) {
					$(".crm-dummy-data").each(function(e, el) {
						var key = $(el).data("field-name");
						if (isNaN(key) && key.indexOf("-s-") > -1) {
							key = key.replace("/-s-/g", " ")
						}
						$(el).html(response[key]);
					})
				})
			} catch(e) {

			}
        }
        var lastCount = 0;
        function do_ajax_in_loop(ajaxdata, rCount) {
            ajaxdata.map_row_index = rCount;
            var tmpArray = Array();
            var i = 0;
            var limit = 1;
            if (ajaxdata.mapSourceType == "csv")
                limit = 10;
            while (i < limit) {
                if (arr_lead_id.length == rCount)
                    break;
                tmpArray.push(arr_lead_id[rCount++].id);
                i++;
            }
            lastCount = i;
            ajaxdata.gravity_lead_id = tmpArray;
            jQuery.post(ajaxurl, ajaxdata, function(response) {
                $.each(response, function(ind, obj) {
                    $("#progressbar").progressbar("option", "value", sucessCount + failCount + 1);
                    if (obj.status) {
                        sucessCount++;
                        $("#sucessfullyImported").html(sucessCount)

                    } else {
                        failCount++;
                        $("#failImported").html(failCount)
                        arr_failed_lead.push(obj.lead_id)
                    }

                })
                if (arr_lead_id.length > rCount) {
                    do_ajax_in_loop(ajaxdata, rCount);
                }
            }).fail(function() {
                $("#progressbar").progressbar("option", "value", sucessCount + failCount + lastCount);
                failCount += lastCount;
                $("#failImported").html(failCount)
                $.each(ajaxdata.gravity_lead_id, function(ind, obj) {
                    arr_failed_lead.push(obj.lead_id);
                });

                if (arr_lead_id.length > rCount) {
                    do_ajax_in_loop(ajaxdata, rCount)
                }
            });

        }
        $("#progressbar").on("progressbarcomplete", function(event, ui) {
            $(".importloading").hide();
            $(".sucessmessage").show();
//
//            arr_failed_lead.toString()
               var strHTML="";
               if(arr_failed_lead.toString() != "")
                strHTML += "Fail Lead Index : " + arr_failed_lead.toString() + "<br />"
               strHTML += "<a target='_blank' href='admin.php?page=rtcrmlogs&log-list=log-list&trans_id=" + transaction_id + "' >View All Inserted Leads </a>";
            $("#extra-data-importer").html(strHTML);

        });
        $("#futureYes").on("click", function(event, ui) {
            var ajaxdata = {
                action: 'rtcrm_map_import_feauture',
                map_data: postdata,
                map_form_id: jQuery('#mapSource').val(),
            }
            jQuery.post(ajaxurl, ajaxdata, function(response) {
                if (response.status) {
                    $("#futureYes").parent().html("<h3>Done</h3>");
                } else {
                    $("#futureYes").parent().html("<h3>already Registerd, Done</h3>");
                }
            });

        });
        $("#futureNo").on("click", function(event, ui) {
            $(this).parent().html("<h3>Done</h3>");
        });
    } catch (e) {

    }

    jQuery(document).on('click', ".delete-multiple", function(e) {
        $(this).prev().remove();
        $(this).remove();
    })
    jQuery(document).on('click', ".add-multiple", function(e) {
        var tempVal = $(this).prev().val();
        var name = $(this).prev().attr("name")
        if (tempVal == '')
            return;
        if ($(this).data("type") != undefined) {
            if ($(this).data("type") == 'email') {
                if (!IsEmail(tempVal))
                    return;
            }
        }

        $(this).prev().val('');

        $(this).after("<button type='button' class='button delete-multiple'> - </button>");
        $(this).after("<input type='text' name='" + name + "' value='" + tempVal + "' class='input-multiple' />");
    });

	// Imap Servers
	jQuery(document).on('click', '.rtcrm-edit-server', function(e) {
		e.preventDefault();
		server_id = jQuery(this).data('server-id');
		jQuery('#rtcrm_imap_server_'+server_id).toggleClass('rtcrm-hide-row').toggleClass('rtcrm-show-row');
	});
	jQuery(document).on('click', '#rtcrm_add_imap_server', function(e) {
		e.preventDefault();
		jQuery('#rtcrm_new_imap_server').toggleClass('rtcrm-hide-row').toggleClass('rtcrm-show-row');
	});
	jQuery(document).on('click', '.rtcrm-remove-server', function(e) {
		e.preventDefault();
		flag = confirm( 'Are you sure you want to remove this server ?' );
		server_id = jQuery(this).data('server-id');
		if(flag) {
			jQuery('#rtcrm_imap_server_'+server_id).remove();
			jQuery(this).parent().parent().remove();
		}
	});


	// User Settings Page - Add Email
	jQuery(document).on('click', '#rtcrm_add_personal_email', function(e) {
		e.preventDefault();
		jQuery('#rtcrm_email_acc_type_container').toggleClass('rtcrm-hide-row').toggleClass('rtcrm-show-row');
		if ( jQuery('#rtcrm_email_acc_type_container').hasClass('rtcrm-hide-row') ) {
			jQuery('#rtcrm_goauth_container').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
			jQuery('#rtcrm_add_imap_acc_form').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
			jQuery('#rtcrm_select_email_acc_type').val('').change();
		}
	});
	jQuery(document).on('change','#rtcrm_select_email_acc_type', function(e) {
		if ( jQuery(this).val() == 'goauth' ) {
			jQuery('#rtcrm_goauth_container').removeClass('rtcrm-hide-row').addClass('rtcrm-show-row');
			jQuery('#rtcrm_add_imap_acc_form').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
		} else if ( jQuery(this).val() == 'imap' ) {
			jQuery('#rtcrm_add_imap_acc_form').removeClass('rtcrm-hide-row').addClass('rtcrm-show-row');
			jQuery('#rtcrm_goauth_container').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
		} else {
			jQuery('#rtcrm_goauth_container').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
			jQuery('#rtcrm_add_imap_acc_form').removeClass('rtcrm-show-row').addClass('rtcrm-hide-row');
		}
	});
});

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