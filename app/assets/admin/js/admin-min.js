/*! 
 * rtBiz Helpdesk JavaScript Library 
 * @package rtBiz Helpdesk 
 */(function(a){function b(a,b){return function(c){return i(a.call(this,c),b)}}function c(a){return function(b){return this.lang().ordinal(a.call(this,b))}}function d(){}function e(a){g(this,a)}function f(a){var b=this._data={},c=a.years||a.year||a.y||0,d=a.months||a.month||a.M||0,e=a.weeks||a.week||a.w||0,f=a.days||a.day||a.d||0,g=a.hours||a.hour||a.h||0,i=a.minutes||a.minute||a.m||0,j=a.seconds||a.second||a.s||0,k=a.milliseconds||a.millisecond||a.ms||0;this._milliseconds=k+1e3*j+6e4*i+36e5*g,this._days=f+7*e,this._months=d+12*c,b.milliseconds=k%1e3,j+=h(k/1e3),b.seconds=j%60,i+=h(j/60),b.minutes=i%60,g+=h(i/60),b.hours=g%24,f+=h(g/24),f+=7*e,b.days=f%30,d+=h(f/30),b.months=d%12,c+=h(d/12),b.years=c}function g(a,b){for(var c in b)b.hasOwnProperty(c)&&(a[c]=b[c]);return a}function h(a){return 0>a?Math.ceil(a):Math.floor(a)}function i(a,b){for(var c=a+"";c.length<b;)c="0"+c;return c}function j(a,b,c){var d,e=b._milliseconds,f=b._days,g=b._months;e&&a._d.setTime(+a+e*c),f&&a.date(a.date()+f*c),g&&(d=a.date(),a.date(1).month(a.month()+g*c).date(Math.min(d,a.daysInMonth())))}function k(a){return"[object Array]"===Object.prototype.toString.call(a)}function l(a,b){var c,d=Math.min(a.length,b.length),e=Math.abs(a.length-b.length),f=0;for(c=0;d>c;c++)~~a[c]!==~~b[c]&&f++;return f+e}function m(a,b){return b.abbr=a,J[a]||(J[a]=new d),J[a].set(b),J[a]}function n(a){return a?(!J[a]&&K&&require("./lang/"+a),J[a]):F.fn._lang}function o(a){return a.match(/\[.*\]/)?a.replace(/^\[|\]$/g,""):a.replace(/\\/g,"")}function p(a){var b,c,d=a.match(M);for(b=0,c=d.length;c>b;b++)ea[d[b]]?d[b]=ea[d[b]]:d[b]=o(d[b]);return function(e){var f="";for(b=0;c>b;b++)f+="function"==typeof d[b].call?d[b].call(e,a):d[b];return f}}function q(a,b){function c(b){return a.lang().longDateFormat(b)||b}for(var d=5;d--&&N.test(b);)b=b.replace(N,c);return ba[b]||(ba[b]=p(b)),ba[b](a)}function r(a){switch(a){case"DDDD":return Q;case"YYYY":return R;case"YYYYY":return S;case"S":case"SS":case"SSS":case"DDD":return P;case"MMM":case"MMMM":case"dd":case"ddd":case"dddd":case"a":case"A":return T;case"X":return W;case"Z":case"ZZ":return U;case"T":return V;case"MM":case"DD":case"YY":case"HH":case"hh":case"mm":case"ss":case"M":case"D":case"d":case"H":case"h":case"m":case"s":return O;default:return new RegExp(a.replace("\\",""))}}function s(a,b,c){var d,e=c._a;switch(a){case"M":case"MM":e[1]=null==b?0:~~b-1;break;case"MMM":case"MMMM":d=n(c._l).monthsParse(b),null!=d?e[1]=d:c._isValid=!1;break;case"D":case"DD":case"DDD":case"DDDD":null!=b&&(e[2]=~~b);break;case"YY":e[0]=~~b+(~~b>68?1900:2e3);break;case"YYYY":case"YYYYY":e[0]=~~b;break;case"a":case"A":c._isPm="pm"===(b+"").toLowerCase();break;case"H":case"HH":case"h":case"hh":e[3]=~~b;break;case"m":case"mm":e[4]=~~b;break;case"s":case"ss":e[5]=~~b;break;case"S":case"SS":case"SSS":e[6]=~~(1e3*("0."+b));break;case"X":c._d=new Date(1e3*parseFloat(b));break;case"Z":case"ZZ":c._useUTC=!0,d=(b+"").match($),d&&d[1]&&(c._tzh=~~d[1]),d&&d[2]&&(c._tzm=~~d[2]),d&&"+"===d[0]&&(c._tzh=-c._tzh,c._tzm=-c._tzm)}null==b&&(c._isValid=!1)}function t(a){var b,c,d=[];if(!a._d){for(b=0;7>b;b++)a._a[b]=d[b]=null==a._a[b]?2===b?1:0:a._a[b];d[3]+=a._tzh||0,d[4]+=a._tzm||0,c=new Date(0),a._useUTC?(c.setUTCFullYear(d[0],d[1],d[2]),c.setUTCHours(d[3],d[4],d[5],d[6])):(c.setFullYear(d[0],d[1],d[2]),c.setHours(d[3],d[4],d[5],d[6])),a._d=c}}function u(a){var b,c,d=a._f.match(M),e=a._i;for(a._a=[],b=0;b<d.length;b++)c=(r(d[b]).exec(e)||[])[0],c&&(e=e.slice(e.indexOf(c)+c.length)),ea[d[b]]&&s(d[b],c,a);a._isPm&&a._a[3]<12&&(a._a[3]+=12),a._isPm===!1&&12===a._a[3]&&(a._a[3]=0),t(a)}function v(a){for(var b,c,d,f,h=99;a._f.length;){if(b=g({},a),b._f=a._f.pop(),u(b),c=new e(b),c.isValid()){d=c;break}f=l(b._a,c.toArray()),h>f&&(h=f,d=c)}g(a,d)}function w(a){var b,c=a._i;if(X.exec(c)){for(a._f="YYYY-MM-DDT",b=0;4>b;b++)if(Z[b][1].exec(c)){a._f+=Z[b][0];break}U.exec(c)&&(a._f+=" Z"),u(a)}else a._d=new Date(c)}function x(b){var c=b._i,d=L.exec(c);c===a?b._d=new Date:d?b._d=new Date(+d[1]):"string"==typeof c?w(b):k(c)?(b._a=c.slice(0),t(b)):b._d=new Date(c instanceof Date?+c:c)}function y(a,b,c,d,e){return e.relativeTime(b||1,!!c,a,d)}function z(a,b,c){var d=I(Math.abs(a)/1e3),e=I(d/60),f=I(e/60),g=I(f/24),h=I(g/365),i=45>d&&["s",d]||1===e&&["m"]||45>e&&["mm",e]||1===f&&["h"]||22>f&&["hh",f]||1===g&&["d"]||25>=g&&["dd",g]||45>=g&&["M"]||345>g&&["MM",I(g/30)]||1===h&&["y"]||["yy",h];return i[2]=b,i[3]=a>0,i[4]=c,y.apply({},i)}function A(a,b,c){var d=c-b,e=c-a.day();return e>d&&(e-=7),d-7>e&&(e+=7),Math.ceil(F(a).add("d",e).dayOfYear()/7)}function B(a){var b=a._i,c=a._f;return null===b||""===b?null:("string"==typeof b&&(a._i=b=n().preparse(b)),F.isMoment(b)?(a=g({},b),a._d=new Date(+b._d)):c?k(c)?v(a):u(a):x(a),new e(a))}function C(a,b){F.fn[a]=F.fn[a+"s"]=function(a){var c=this._isUTC?"UTC":"";return null!=a?(this._d["set"+c+b](a),this):this._d["get"+c+b]()}}function D(a){F.duration.fn[a]=function(){return this._data[a]}}function E(a,b){F.duration.fn["as"+a]=function(){return+this/b}}for(var F,G,H="2.0.0",I=Math.round,J={},K="undefined"!=typeof module&&module.exports,L=/^\/?Date\((\-?\d+)/i,M=/(\[[^\[]*\])|(\\)?(Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|YYYYY|YYYY|YY|a|A|hh?|HH?|mm?|ss?|SS?S?|X|zz?|ZZ?|.)/g,N=/(\[[^\[]*\])|(\\)?(LT|LL?L?L?|l{1,4})/g,O=/\d\d?/,P=/\d{1,3}/,Q=/\d{3}/,R=/\d{1,4}/,S=/[+\-]?\d{1,6}/,T=/[0-9]*[a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF]+\s*?[\u0600-\u06FF]+/i,U=/Z|[\+\-]\d\d:?\d\d/i,V=/T/i,W=/[\+\-]?\d+(\.\d{1,3})?/,X=/^\s*\d{4}-\d\d-\d\d((T| )(\d\d(:\d\d(:\d\d(\.\d\d?\d?)?)?)?)?([\+\-]\d\d:?\d\d)?)?/,Y="YYYY-MM-DDTHH:mm:ssZ",Z=[["HH:mm:ss.S",/(T| )\d\d:\d\d:\d\d\.\d{1,3}/],["HH:mm:ss",/(T| )\d\d:\d\d:\d\d/],["HH:mm",/(T| )\d\d:\d\d/],["HH",/(T| )\d\d/]],$=/([\+\-]|\d\d)/gi,_="Month|Date|Hours|Minutes|Seconds|Milliseconds".split("|"),aa={Milliseconds:1,Seconds:1e3,Minutes:6e4,Hours:36e5,Days:864e5,Months:2592e6,Years:31536e6},ba={},ca="DDD w W M D d".split(" "),da="M D H h m s w W".split(" "),ea={M:function(){return this.month()+1},MMM:function(a){return this.lang().monthsShort(this,a)},MMMM:function(a){return this.lang().months(this,a)},D:function(){return this.date()},DDD:function(){return this.dayOfYear()},d:function(){return this.day()},dd:function(a){return this.lang().weekdaysMin(this,a)},ddd:function(a){return this.lang().weekdaysShort(this,a)},dddd:function(a){return this.lang().weekdays(this,a)},w:function(){return this.week()},W:function(){return this.isoWeek()},YY:function(){return i(this.year()%100,2)},YYYY:function(){return i(this.year(),4)},YYYYY:function(){return i(this.year(),5)},a:function(){return this.lang().meridiem(this.hours(),this.minutes(),!0)},A:function(){return this.lang().meridiem(this.hours(),this.minutes(),!1)},H:function(){return this.hours()},h:function(){return this.hours()%12||12},m:function(){return this.minutes()},s:function(){return this.seconds()},S:function(){return~~(this.milliseconds()/100)},SS:function(){return i(~~(this.milliseconds()/10),2)},SSS:function(){return i(this.milliseconds(),3)},Z:function(){var a=-this.zone(),b="+";return 0>a&&(a=-a,b="-"),b+i(~~(a/60),2)+":"+i(~~a%60,2)},ZZ:function(){var a=-this.zone(),b="+";return 0>a&&(a=-a,b="-"),b+i(~~(10*a/6),4)},X:function(){return this.unix()}};ca.length;)G=ca.pop(),ea[G+"o"]=c(ea[G]);for(;da.length;)G=da.pop(),ea[G+G]=b(ea[G],2);for(ea.DDDD=b(ea.DDD,3),d.prototype={set:function(a){var b,c;for(c in a)b=a[c],"function"==typeof b?this[c]=b:this["_"+c]=b},_months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_"),months:function(a){return this._months[a.month()]},_monthsShort:"Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),monthsShort:function(a){return this._monthsShort[a.month()]},monthsParse:function(a){var b,c,d;for(this._monthsParse||(this._monthsParse=[]),b=0;12>b;b++)if(this._monthsParse[b]||(c=F([2e3,b]),d="^"+this.months(c,"")+"|^"+this.monthsShort(c,""),this._monthsParse[b]=new RegExp(d.replace(".",""),"i")),this._monthsParse[b].test(a))return b},_weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),weekdays:function(a){return this._weekdays[a.day()]},_weekdaysShort:"Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),weekdaysShort:function(a){return this._weekdaysShort[a.day()]},_weekdaysMin:"Su_Mo_Tu_We_Th_Fr_Sa".split("_"),weekdaysMin:function(a){return this._weekdaysMin[a.day()]},_longDateFormat:{LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D YYYY",LLL:"MMMM D YYYY LT",LLLL:"dddd, MMMM D YYYY LT"},longDateFormat:function(a){var b=this._longDateFormat[a];return!b&&this._longDateFormat[a.toUpperCase()]&&(b=this._longDateFormat[a.toUpperCase()].replace(/MMMM|MM|DD|dddd/g,function(a){return a.slice(1)}),this._longDateFormat[a]=b),b},meridiem:function(a,b,c){return a>11?c?"pm":"PM":c?"am":"AM"},_calendar:{sameDay:"[Today at] LT",nextDay:"[Tomorrow at] LT",nextWeek:"dddd [at] LT",lastDay:"[Yesterday at] LT",lastWeek:"[last] dddd [at] LT",sameElse:"L"},calendar:function(a,b){var c=this._calendar[a];return"function"==typeof c?c.apply(b):c},_relativeTime:{future:"in %s",past:"%s ago",s:"a few seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",M:"a month",MM:"%d months",y:"a year",yy:"%d years"},relativeTime:function(a,b,c,d){var e=this._relativeTime[c];return"function"==typeof e?e(a,b,c,d):e.replace(/%d/i,a)},pastFuture:function(a,b){var c=this._relativeTime[a>0?"future":"past"];return"function"==typeof c?c(b):c.replace(/%s/i,b)},ordinal:function(a){return this._ordinal.replace("%d",a)},_ordinal:"%d",preparse:function(a){return a},postformat:function(a){return a},week:function(a){return A(a,this._week.dow,this._week.doy)},_week:{dow:0,doy:6}},F=function(a,b,c){return B({_i:a,_f:b,_l:c,_isUTC:!1})},F.utc=function(a,b,c){return B({_useUTC:!0,_isUTC:!0,_l:c,_i:a,_f:b})},F.unix=function(a){return F(1e3*a)},F.duration=function(a,b){var c,d=F.isDuration(a),e="number"==typeof a,g=d?a._data:e?{}:a;return e&&(b?g[b]=a:g.milliseconds=a),c=new f(g),d&&a.hasOwnProperty("_lang")&&(c._lang=a._lang),c},F.version=H,F.defaultFormat=Y,F.lang=function(a,b){return a?(b?m(a,b):J[a]||n(a),void(F.duration.fn._lang=F.fn._lang=n(a))):F.fn._lang._abbr},F.langData=function(a){return a&&a._lang&&a._lang._abbr&&(a=a._lang._abbr),n(a)},F.isMoment=function(a){return a instanceof e},F.isDuration=function(a){return a instanceof f},F.fn=e.prototype={clone:function(){return F(this)},valueOf:function(){return+this._d},unix:function(){return Math.floor(+this._d/1e3)},toString:function(){return this.format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ")},toDate:function(){return this._d},toJSON:function(){return F.utc(this).format("YYYY-MM-DD[T]HH:mm:ss.SSS[Z]")},toArray:function(){var a=this;return[a.year(),a.month(),a.date(),a.hours(),a.minutes(),a.seconds(),a.milliseconds()]},isValid:function(){return null==this._isValid&&(this._a?this._isValid=!l(this._a,(this._isUTC?F.utc(this._a):F(this._a)).toArray()):this._isValid=!isNaN(this._d.getTime())),!!this._isValid},utc:function(){return this._isUTC=!0,this},local:function(){return this._isUTC=!1,this},format:function(a){var b=q(this,a||F.defaultFormat);return this.lang().postformat(b)},add:function(a,b){var c;return c="string"==typeof a?F.duration(+b,a):F.duration(a,b),j(this,c,1),this},subtract:function(a,b){var c;return c="string"==typeof a?F.duration(+b,a):F.duration(a,b),j(this,c,-1),this},diff:function(a,b,c){var d,e,f=this._isUTC?F(a).utc():F(a).local(),g=6e4*(this.zone()-f.zone());return b&&(b=b.replace(/s$/,"")),"year"===b||"month"===b?(d=432e5*(this.daysInMonth()+f.daysInMonth()),e=12*(this.year()-f.year())+(this.month()-f.month()),e+=(this-F(this).startOf("month")-(f-F(f).startOf("month")))/d,"year"===b&&(e/=12)):(d=this-f-g,e="second"===b?d/1e3:"minute"===b?d/6e4:"hour"===b?d/36e5:"day"===b?d/864e5:"week"===b?d/6048e5:d),c?e:h(e)},from:function(a,b){return F.duration(this.diff(a)).lang(this.lang()._abbr).humanize(!b)},fromNow:function(a){return this.from(F(),a)},calendar:function(){var a=this.diff(F().startOf("day"),"days",!0),b=-6>a?"sameElse":-1>a?"lastWeek":0>a?"lastDay":1>a?"sameDay":2>a?"nextDay":7>a?"nextWeek":"sameElse";return this.format(this.lang().calendar(b,this))},isLeapYear:function(){var a=this.year();return a%4===0&&a%100!==0||a%400===0},isDST:function(){return this.zone()<F([this.year()]).zone()||this.zone()<F([this.year(),5]).zone()},day:function(a){var b=this._isUTC?this._d.getUTCDay():this._d.getDay();return null==a?b:this.add({d:a-b})},startOf:function(a){switch(a=a.replace(/s$/,"")){case"year":this.month(0);case"month":this.date(1);case"week":case"day":this.hours(0);case"hour":this.minutes(0);case"minute":this.seconds(0);case"second":this.milliseconds(0)}return"week"===a&&this.day(0),this},endOf:function(a){return this.startOf(a).add(a.replace(/s?$/,"s"),1).subtract("ms",1)},isAfter:function(a,b){return b="undefined"!=typeof b?b:"millisecond",+this.clone().startOf(b)>+F(a).startOf(b)},isBefore:function(a,b){return b="undefined"!=typeof b?b:"millisecond",+this.clone().startOf(b)<+F(a).startOf(b)},isSame:function(a,b){return b="undefined"!=typeof b?b:"millisecond",+this.clone().startOf(b)===+F(a).startOf(b)},zone:function(){return this._isUTC?0:this._d.getTimezoneOffset()},daysInMonth:function(){return F.utc([this.year(),this.month()+1,0]).date()},dayOfYear:function(a){var b=I((F(this).startOf("day")-F(this).startOf("year"))/864e5)+1;return null==a?b:this.add("d",a-b)},isoWeek:function(a){var b=A(this,1,4);return null==a?b:this.add("d",7*(a-b))},week:function(a){var b=this.lang().week(this);return null==a?b:this.add("d",7*(a-b))},lang:function(b){return b===a?this._lang:(this._lang=n(b),this)}},G=0;G<_.length;G++)C(_[G].toLowerCase().replace(/s$/,""),_[G]);C("year","FullYear"),F.fn.days=F.fn.day,F.fn.weeks=F.fn.week,F.fn.isoWeeks=F.fn.isoWeek,F.duration.fn=f.prototype={weeks:function(){return h(this.days()/7)},valueOf:function(){return this._milliseconds+864e5*this._days+2592e6*this._months},humanize:function(a){var b=+this,c=z(b,!a,this.lang());return a&&(c=this.lang().pastFuture(b,c)),this.lang().postformat(c)},lang:F.fn.lang};for(G in aa)aa.hasOwnProperty(G)&&(E(G,aa[G]),D(G.toLowerCase()));E("Weeks",6048e5),F.lang("en",{ordinal:function(a){var b=a%10,c=1===~~(a%100/10)?"th":1===b?"st":2===b?"nd":3===b?"rd":"th";return a+c}}),K&&(module.exports=F),"undefined"==typeof ender&&(this.moment=F),"function"==typeof define&&define.amd&&define("moment",[],function(){return F})}).call(this),jQuery(document).ready(function(){var a,b={rthd_tinymce_set_content:function(a,b){if("undefined"!=typeof tinymce){var c=tinymce.get(a);return c&&c instanceof tinymce.Editor?(c.setContent(b),c.save({no_events:!0})):jQuery("#"+a).val(b),!0}return!1},rthd_tinymce_get_content:function(a){if("undefined"!=typeof tinymce){var b=tinymce.get(a);return b&&b instanceof tinymce.Editor?b.getContent():jQuery("#"+a).val()}return""},init:function(){b.initDatePicket(),b.initDateTimePicker(),b.initMomentJS(),b.initattchmentJS(),b.initExternalFileJS(),b.initSubscriberSearch(),b.initAddNewFollowUp(),b.initEditFollowUp(),b.initLoadAll(),b.initEditContent(),b.initAutoResponseSettings(),b.initBlacklistConfirmationOrRemove(),b.initAddContactBlacklist()},initEditContent:function(){jQuery(".edit-ticket-link").click(function(a){a.preventDefault(),jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#dialog-form").is(":visible")&&jQuery("#dialog-form").slideToggle("slow"),jQuery("#edit-ticket-data").is(":visible")||jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").hide(),jQuery(document).scrollTop(jQuery("#edit-ticket-data").offset().top-50),b.rthd_tinymce_set_content("editedticketcontent",jQuery(this).closest(".ticketcontent").find(".rthd-comment-content").data("content"))}),jQuery(".close-edit-content").click(function(a){a.preventDefault(),jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").show(),jQuery(document).scrollTop(jQuery(".ticketcontent").offset().top-50)}),jQuery("#edit-ticket-content-click").click(function(){jQuery("#edit-ticket-data").slideToggle("slow"),jQuery("#new-followup-form").hide();var a=new Object;a.action="rthd_add_new_ticket_ajax",a.post_id=jQuery("#post-id").val();var c=b.rthd_tinymce_get_content("editedticketcontent");a.body=c,a.nonce=jQuery("#edit_ticket_nonce").val(),jQuery("#ticket-edithdspinner").show(),jQuery(this).attr("disabled","disabled"),jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:a,success:function(a){a.status?(jQuery("#ticket-edithdspinner").hide(),jQuery("#edit-ticket-content-click").removeAttr("disabled"),jQuery(".edit-ticket-link").closest(".ticketcontent").find(".rthd-comment-content").html(b.rthd_tinymce_get_content("editedticketcontent")),jQuery("#edit-ticket-data").hide(),jQuery("#new-followup-form").slideToggle("slow"),jQuery(document).scrollTop(jQuery(".ticketcontent").offset().top-50)):console.log(a.msg)},error:function(a,b,c){alert("Error"),jQuery("#ticket-edithdspinner").hide(),jQuery("#edit-ticket-content-click").removeAttr("disabled")}})})},initLoadAll:function(){jQuery("#followup-load-more, .load-more-block").click(function(a){a.preventDefault();var b=new Object,c=parseInt(jQuery("#followup-totalcomment").val(),10),d=parseInt(jQuery("#followup-limit").val(),10);3==d&&(jQuery(this).parent().hide(),jQuery("#load-more-hdspinner").show(),b.limit=c-3,b.offset=0,b.action="load_more_followup",b.post_id=jQuery("#post-id").val(),jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:b,success:function(a){a.status&&(jQuery("#followup-offset").val(a.offset),jQuery("#chat-UI").prepend(a.comments)),jQuery("#load-more-hdspinner").hide()},error:function(){return jQuery("#load-more-hdspinner").hide(),!1}}))})},initEditFollowUp:function(){var a;jQuery("#delfollowup").click(function(){var b=confirm("Are you sure you want to remove this Followup?");return 1!=b?(e.preventDefault(),!1):(jQuery("#edithdspinner").show(),jQuery(this).attr("disabled","disabled"),postid=jQuery("#post-id").val(),void jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:{action:"helpdesk_delete_followup",comment_id:a,post_id:postid},success:function(b){b.status?(jQuery("#comment-"+a).fadeOut(500,function(){jQuery(this).remove()}),jQuery(".close-edit-followup").trigger("click")):alert("Error while deleting comment from server"),jQuery("#edithdspinner").hide(),jQuery("#delfollowup").removeAttr("disabled")},error:function(a,b,c){alert("error while removing follow up."),jQuery("#edithdspinner").hide(),jQuery("#delfollowup").removeAttr("disabled")}}))}),jQuery(document).on("click",".close-edit-followup",function(b){b.preventDefault(),jQuery("#dialog-form").slideToggle("slow"),jQuery("#new-followup-form").show(),jQuery(document).scrollTop(jQuery("#comment-"+a).offset().top)}),jQuery(document).on("click",".editfollowuplink",function(c){c.preventDefault();var d=jQuery(this).parents();b.rthd_tinymce_set_content("editedfollowupcontent",jQuery(this).parents().siblings(".rthd-comment-content").data("content")),a=d.siblings("#followup-id").val();var e=d.siblings("#is-private-comment").val();jQuery("#edit-private").val(e),jQuery("#new-followup-form").hide(),jQuery("#dialog-form").is(":visible")||jQuery("#dialog-form").slideToggle("slow"),jQuery("#edit-ticket-data").is(":visible")&&jQuery("#edit-ticket-data").slideToggle("slow"),jQuery(document).scrollTop(jQuery("#dialog-form").offset().top-50)}),jQuery("#editfollowup").click(function(){var c=new Object,d=b.rthd_tinymce_get_content("editedfollowupcontent");return d?d.replace(/\s+/g," ")===jQuery("#comment-"+a).find(".rthd-comment-content").data("content")?(alert("You have not edited comment!"),!1):(jQuery("#edithdspinner").show(),jQuery(this).attr("disabled","disabled"),c.post_type=rthd_post_type,c.comment_id=a,c.action="rthd_update_followup_ajax",c.followuptype="comment",c.followup_ticket_unique_id=jQuery("#ticket_unique_id").val(),c.followup_post_id=jQuery("#post-id").val(),c.followup_private=jQuery("#edit-private").val(),c.followuptype="comment",c.followup_content=d,void jQuery.ajax({url:ajaxurl,dataType:"json",type:"post",data:c,success:function(b){b.status?(jQuery("#comment-"+a).replaceWith(b.comment_content),jQuery(".close-edit-followup").trigger("click"),jQuery(document).scrollTop(jQuery("#comment-"+a).offset().top-50)):alert(b.message),jQuery("#edithdspinner").hide(),jQuery("#editfollowup").removeAttr("disabled")},error:function(a){alert("Sorry :( something went wrong!"),jQuery("#edithdspinner").hide(),jQuery("#editfollowup").removeAttr("disabled")}})):(alert("Please enter comment"),!1)})},initAddNewFollowUp:function(){function a(){return jQuery("#hdspinner").show(),jQuery("#ticket_unique_id").val()?b.rthd_tinymce_get_content("followupcontent")?!0:(alert("Please input followup."),jQuery("#hdspinner").hide(),!1):(alert("Please publish ticket before adding followup! :( "),jQuery("#hdspinner").hide(),!1)}function c(a){var c=(jQuery("#followup-type").val(),new FormData);c.append("private_comment",jQuery("#add-private-comment").val()),c.append("followup_ticket_unique_id",jQuery("#ticket_unique_id").val()),c.append("post_type",jQuery("#followup_post_type").val()),c.append("action","rthd_add_new_followup_front"),c.append("followuptype",jQuery("#followuptype").val()),c.append("follwoup-time",jQuery("#follwoup-time").val()),c.append("followup_content",b.rthd_tinymce_get_content("followupcontent")),c.append("followup_attachments",d),jQuery("#rthd_keep_status")&&c.append("rthd_keep_status",jQuery("#rthd_keep_status").is(":checked")),jQuery.ajax({url:ajaxurl,dataType:"json",type:"POST",data:c,cache:!1,contentType:!1,processData:!1,success:function(a){a.status?(jQuery("#chat-UI").append(a.comment_content),b.rthd_tinymce_set_content("followupcontent",""),jQuery("#add-private-comment").val(10),d=[],"answered"==a.ticket_status?jQuery("#rthd_keep_status")&&jQuery("#rthd_keep_status").parent().hide():jQuery("#rthd_keep_status").length>0&&jQuery("#rthd_keep_status").prop("checked",!1),jQuery("#hdspinner").hide(),jQuery("#savefollwoup").removeAttr("disabled")):(console.log(a.message),jQuery("#hdspinner").hide(),jQuery("#savefollwoup").removeAttr("disabled"))}})}$ticket_unique_id=jQuery("#ticket_unique_id").val();var d=[],e=!1;if("undefined"!=typeof plupload){var f=new plupload.Uploader({runtimes:"html5,flash,silverlight,html4",browse_button:"attachemntlist",url:ajaxurl,multipart:!0,multipart_params:{action:"rthd_upload_attachment",followup_ticket_unique_id:$ticket_unique_id},container:document.getElementById("rthd-attachment-container"),filters:{max_file_size:"10mb"},flash_swf_url:"Moxie.swf",silverlight_xap_url:"Moxie.xap",init:{PostInit:function(){document.getElementById("followup-filelist").innerHTML="",document.getElementById("savefollwoup").onclick=function(){a()&&f.start()}},FilesAdded:function(a,b){plupload.each(b,function(a){document.getElementById("followup-filelist").innerHTML+='<div id="'+a.id+'"><a href="#" class="followup-attach-remove"> x </a> '+a.name+" ("+plupload.formatSize(a.size)+") <b></b></div>"})},FilesRemoved:function(a,b){plupload.each(b,function(a){jQuery("#"+a.id).remove()})},UploadProgress:function(a,b){document.getElementById(b.id).getElementsByTagName("b")[0].innerHTML="<span>"+b.percent+"%</span>"},Error:function(a,b){document.getElementById("console").innerHTML+="\nError #"+b.code+": "+b.message},UploadComplete:function(){document.getElementById("followup-filelist").innerHTML="",c(e),e=!1,d=[]},FileUploaded:function(a,b,c){var e=jQuery.parseJSON(c.response);e.status&&(d=d.concat(e.attach_ids))}}});f.init()}jQuery(document).on("click",".followup-attach-remove",function(a){a.preventDefault(),f.removeFile(jQuery(this).parent().attr("id"))})},initDatePicket:function(){jQuery(".datepicker").length>0&&jQuery(".datepicker").datepicker({dateFormat:"M d,yy",onClose:function(a,b){if(jQuery(this).hasClass("moment-from-now")){var c=jQuery(this).attr("title");""!=a&&moment(a).isValid()?(jQuery(this).val(moment(new Date(a)).fromNow()),jQuery(this).attr("title",a),jQuery(this).next().length>0&&jQuery(this).next().val(a)):""!=c&&(jQuery(this).val(moment(new Date(c)).fromNow()),jQuery(this).attr("title",c),jQuery(this).next().length>0&&jQuery(this).next().val(a))}}}),jQuery(".datepicker-toggle").click(function(a){jQuery("#"+jQuery(this).data("datepicker")).datepicker("show")})},initDateTimePicker:function(){jQuery(".datetimepicker").length>0&&jQuery(".datetimepicker").datetimepicker({dateFormat:"M d, yy",timeFormat:"hh:mm TT",onClose:function(a,b){var c=jQuery(this).attr("title");""!=a&&moment(a).isValid()&&(jQuery(this).val(moment(new Date(a)).fromNow()),jQuery(this).attr("title",a),jQuery(this).next().length>0?jQuery(this).hasClass("moment-from-now")&&jQuery(this).next().val(a):""!=c&&(jQuery(this).val(moment(new Date(c)).fromNow()),jQuery(this).attr("title",c),jQuery(this).next().length>0&&jQuery(this).next().val(a)))}})},initMomentJS:function(){jQuery(document).on("click",".moment-from-now",function(a){var b=jQuery(this).attr("title");""!=b&&jQuery(this).datepicker("setDate",new Date(jQuery(this).attr("title")))}),jQuery(".moment-from-now").each(function(){jQuery(this).is("input[type='text']")&&""!=jQuery(this).val()?jQuery(this).val(moment(new Date(jQuery(this).attr("title"))).fromNow()):jQuery(this).html(jQuery(this).is(".comment-date")?moment(new Date(jQuery(this).attr("title"))).fromNow():moment(new Date(jQuery(this).html())).fromNow())})},initattchmentJS:function(){jQuery(document).on("click",".rthd_delete_attachment",function(a){a.preventDefault(),jQuery(this).parent().remove()}),jQuery("#add_ticket_attachment").on("click",function(b){return b.preventDefault(),a?void a.open():(a=wp.media.frames.file_frame=wp.media({title:jQuery(this).data("uploader_title"),searchable:!0,button:{text:"Attach Selected Files"},multiple:!0}),a.on("select",function(){var b=a.state().get("selection"),c="";b.map(function(a){a=a.toJSON(),c='<li data-attachment-id="'+a.id+'" class="attachment-item row_group">',c+='<a href="#" class="delete_row rthd_delete_attachment">x</a>',c+='<a target="_blank" href="'+a.url+'"><img height="20px" width="20px" src="'+a.icon+'" > '+a.filename+"</a>",c+='<input type="hidden" name="attachment[]" value="'+a.id+'" /></div>',jQuery("#attachment-container .scroll-height").append(c)})}),void a.open())})},initExternalFileJS:function(){var a=12345;jQuery("#add_new_ex_file").click(function(b){var c=jQuery("#add_ex_file_title").val(),d=jQuery("#add_ex_file_link").val();if(""==jQuery.trim(d))return!1;jQuery("#add_ex_file_title").val(""),jQuery("#add_ex_file_link").val("");var e='<div class="row_group">';e+='<button class="delete_row removeMeta"><i class="foundicon-minus"></i>X</button>',e+='<input type="text" name="ticket_ex_files['+a+'][title]" value="'+c+'" />',e+='<input type="text" name="ticket_ex_files['+a+'][link]" value="'+d+'" />',e+="</div>",a++,jQuery("#external-files-container").append(e)})},initSubscriberSearch:function(){try{void 0!=arr_subscriber_user&&(jQuery("#subscriber_user_ac").autocomplete({source:function(a,b){var c=jQuery.ui.autocomplete.escapeRegex(a.term),d=new RegExp("^"+c,"i"),e=jQuery.grep(arr_subscriber_user,function(a){return d.test(a.label||a.value||a)}),f=new RegExp(c,"i"),g=jQuery.grep(arr_subscriber_user,function(a){return jQuery.inArray(a,e)<0&&f.test(a.label||a.value||a)});b(e.concat(g))},focus:function(a,b){},select:function(a,b){return jQuery("#subscribe-auth-"+b.item.id).length<1&&jQuery("#divSubscriberList").append("<li id='subscribe-auth-"+b.item.id+"' class='contact-list' >"+b.item.imghtml+"<a href='#removeSubscriber' class='delete_row'>×</a><br/><a class='subscribe-title heading' target='_blank' href='"+b.item.user_edit_link+"'>"+b.item.label+"</a><input type='hidden' name='subscribe_to[]' value='"+b.item.id+"' /></li>"),jQuery("#subscriber_user_ac").val(""),!1}}).data("ui-autocomplete")._renderItem=function(a,b){return jQuery("<li></li>").data("ui-autocomplete-item",b).append("<a class='ac-subscribe-selected'>"+b.imghtml+"&nbsp;"+b.label+"</a>").appendTo(a)},jQuery(document).on("click","a[href=#removeSubscriber]",function(a){a.preventDefault(),jQuery(this).parent().remove()}))}catch(a){}},initAutoResponseSettings:function(){jQuery("#rthd_enable_auto_response_mode")&&(jQuery(".rthd-dayshift-time-end").change(function(){b.initDayValidation(jQuery(this).parent().parent())}),jQuery(".rthd-dayshift-time-start").change(function(){b.initDayValidation(jQuery(this).parent().parent())}),jQuery(".rthd-daynight-am-time-start").change(function(){b.initDayNightValidation(jQuery(this).parent().parent())}),jQuery(".rthd-daynight-am-time-end").change(function(){b.initDayNightValidation(jQuery(this).parent().parent())}),jQuery(".rthd-daynight-pm-time-start").change(function(){b.initDayNightValidation(jQuery(this).parent().parent())}),jQuery(".rthd-daynight-pm-time-end").change(function(){b.initDayNightValidation(jQuery(this).parent().parent())}),jQuery.ajaxPrefilter(function(a,c,d){var e=JSON.stringify(a.data);if(-1!==e.indexOf("action=redux_helpdesk_settings_ajax_save&")){var f=!0;if(0==jQuery("#rthd_enable_auto_response").val())return!0;if(1==jQuery("#rthd_enable_auto_response_mode").val())for(var g=0;7>g;g++){var h=jQuery(".rthd-dayshift-time-start").eq(g).parent().parent();b.initDayValidation(h)||(f=!1)}if(0==jQuery("#rthd_enable_auto_response_mode").val())for(var g=0;7>g;g++){var h=jQuery(".rthd-daynight-am-time-start").eq(g).parent().parent();b.initDayNightValidation(h)||(f=!1)}return f?f:(d.abort(),jQuery(".redux-action_bar input").removeAttr("disabled"),jQuery(document.getElementById("redux_ajax_overlay")).fadeOut("fast"),void jQuery(".redux-action_bar .spinner").fadeOut("fast"))}}))},initDayValidation:function(a){var b=a.find(".rthd-dayshift-time-start").val(),c=a.find(".rthd-dayshift-time-end").val(),d=!0,e=!0;if(-1==b&&-1==c?jQuery(a).next(".rthd-dayshift-error").show().find(".error").removeClass("myerror").html(""):-1==b||-1==c?(jQuery(a).next(".rthd-dayshift-error").show().find(".error").addClass("myerror").html(-1==b?"Please select `Start` time":"Please select `End` time"),d=!1):parseInt(c)<parseInt(b)?(jQuery(a).next(".rthd-dayshift-error").show().find(".error").addClass("myerror").html("Starting Time should be less then ending time"),d=!1):jQuery(a).next(".rthd-dayshift-error").show().find(".error").removeClass("myerror").html(""),d){if(jQuery(a).next(".rthd-dayshift-error").hide(),0==jQuery("#rthd_autoresponse_weekend").val()){for(var f=0;7>f;f++){a=jQuery(".rthd-dayshift-time-start").eq(f).parent().parent();var b=a.find(".rthd-dayshift-time-start").val(),c=a.find(".rthd-dayshift-time-end").val();(-1!=b||-1!=c)&&(e=!1)}e&&(jQuery("#rthd-response-day-error").show().html("please select working time"),d=!1)}}else jQuery("#rthd-response-day-error").hide().html("");return d},initDayNightValidation:function(a){var b=a.find(".rthd-daynight-am-time-start").val(),c=a.find(".rthd-daynight-am-time-end").val(),d=a.find(".rthd-daynight-pm-time-start").val(),e=a.find(".rthd-daynight-pm-time-end").val(),f=!0,g=!0;if(-1==b&&-1==c?jQuery(a).next(".rthd-daynightshift-error").show().find(".am-time-error").removeClass("myerror").html(""):-1==b||-1==c?(jQuery(a).next(".rthd-daynightshift-error").show().find(".am-time-error").addClass("myerror").html(-1==b?"Please select `Start` time":"Please select `End` time"),f=!1):parseInt(c)<parseInt(b)?(jQuery(a).next(".rthd-daynightshift-error").show().find(".am-time-error").addClass("myerror").html("Starting Time should be less then ending time"),f=!1):jQuery(a).next(".rthd-daynightshift-error").show().find(".am-time-error").removeClass("myerror").html(""),-1==d&&-1==e?jQuery(a).next(".rthd-daynightshift-error").show().find(".pm-time-error").removeClass("myerror").html(""):-1==d||-1==e?(jQuery(a).next(".rthd-daynightshift-error").show().find(".pm-time-error").addClass("myerror").html(-1==d?"Please select `Start` time":"Please select `End` time"),f=!1):parseInt(e)<parseInt(d)?(jQuery(a).next(".rthd-daynightshift-error").show().find(".pm-time-error").addClass("myerror").html("Starting Time should be less then ending time"),
f=!1):jQuery(a).next(".rthd-daynightshift-error").show().find(".pm-time-error").removeClass("myerror").html(""),f){if(jQuery(a).next(".rthd-daynightshift-error").hide(),0==jQuery("#rthd_autoresponse_weekend").val()){for(var h=0;7>h;h++){a=jQuery(".rthd-daynight-am-time-start").eq(h).parent().parent();var b=a.find(".rthd-daynight-am-time-start").val(),c=a.find(".rthd-daynight-am-time-end").val(),d=a.find(".rthd-daynight-pm-time-start").val(),e=a.find(".rthd-daynight-pm-time-end").val();(-1!=b||-1!=c||-1!=d||-1!=e)&&(g=!1)}g&&(jQuery("#rthd-response-daynight-error").show().html("please select working time"),f=!1)}}else jQuery("#rthd-response-daynight-error").hide().html("");return f},initBlacklistConfirmationOrRemove:function(){jQuery(document).on("click","#rthd_ticket_contacts_blacklist",function(a){a.preventDefault();var b=jQuery(this).data("action"),c=new Object;c.post_id=jQuery("#post-id").val(),"remove_blacklisted"==b?(c.action="rthd_remove_blacklisted_contact",jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:c,success:function(a){a.status&&(jQuery("#contacts-blacklist-container").html("").hide(),jQuery("#contacts-blacklist-action").html(a.addBlacklist_ui).show())},error:function(a,b,c){jQuery("#contacts-blacklist-container").html("Some error with ajax request!!").show()}})):"blacklisted_confirmation"==b&&(c.action="rthd_show_blacklisted_confirmation",jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:c,success:function(a){a.status&&(jQuery("#contacts-blacklist-container").html(a.confirmation_ui).show(),jQuery("#contacts-blacklist-action").hide())},error:function(a,b,c){jQuery("#contacts-blacklist-container").html("Some error with ajax request!!").show()}}))})},initAddContactBlacklist:function(){jQuery(document).on("click","#rthd_ticket_contacts_blacklist_yes",function(a){a.preventDefault();var b=jQuery(this).data("action"),c=new Object;c.post_id=jQuery("#post-id").val(),"blacklisted_contact"==b&&(c.action="rthd_add_blacklisted_contact"),jQuery.ajax({url:ajaxurl,type:"POST",dataType:"json",data:c,success:function(a){a.status&&(jQuery(".confirmation-container").hide(),jQuery("#contacts-blacklist-action").html(a.remove_ui).show())},error:function(a,b,c){jQuery("#contacts-blacklist-container").html("Some error with ajax request!!").show()}})}),jQuery(document).on("click","#rthd_ticket_contacts_blacklist_no",function(a){a.preventDefault(),jQuery("#contacts-blacklist-container").html("").hide(),jQuery("#contacts-blacklist-action").show()})}};b.init()});