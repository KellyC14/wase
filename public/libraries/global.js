$.urlParam = function(name){
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
	if (results !== null)
    	return results[1] || "";
	return "";
};
getURLDateTime = function(dt) {
	return encodeURIComponent(moment(dt).format("YYYY-MM-DDTHH:mm:ss"));
};
getDateTimeFromURL = function(urlDT) {
	return moment(decodeURIComponent(urlDT),"YYYY-MM-DDTHH:mm:ss").toDate();
};

/********************************************************/
/* Checkboxes											*/
/********************************************************/
function selectAllChk(inChkClass, inIsOn) {
	$("." + inChkClass).each(function() {
		$(this).prop("checked",inIsOn).checkboxradio("refresh");
	});
}
function shouldAllBeChecked(inChkClass,inCGID) {
	var areAllChecked = true;
	$("." + inChkClass).each(function() {
		if (!$(this).hasClass("chkall") && !$(this).is(":checked")) {
			areAllChecked = false;
		}
	});
	$("#"+inCGID+ " .chkall").prop("checked",areAllChecked).checkboxradio("refresh");
}

/********************************************************/
/*  Date and Time										*/
/********************************************************/

var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

var fmtTime = "h:mm a";
var fmtDate = "M/D/YYYY";

function formatDateforServer(inDate) {
	return moment(inDate).format("YYYY-MM-DD");
}
function formatTimeforServer(inDateTime) {
	return moment(inDateTime).format("HH:mm:ss");
}
function formatDateTimeforServer(inDateTime) {
	return formatDateforServer(inDateTime) + " " + formatTimeforServer(inDateTime);
}

//Input String is format yyyy-mm-dd hh:mm:ss.
function formatDateTimeFromServer(inDateTimeStr) {
	return moment(inDateTimeStr,"YYYY-MM-DD HH:mm:ss").toDate();
}

//Input String is format yyyy-mm-dd.
function formatDateFromServer(inDateStr) {
    return moment(inDateStr,"YYYY-MM-DD").toDate();
}

//Input String is format hh:mm:ss.
function formatTimeFromServer(inTimeStr) {
	return moment(inTimeStr,"HH:mm:ss").toDate();
}


//To see if date 1 is same as date 2 - DAY only
function isDateSame(inDate1, inDate2) {
	return moment(inDate1).isSame(moment(inDate2), "day");
}
function isDateTimeSame(inDate1, inDate2) {
	return moment(inDate1).isSame(moment(inDate2), "minutes");
}

function convertDHMtoMin(inDays,inHours,inMinutes) {
	var mins = 0;
	mins = mins + (inDays * 24 * 60);
	mins = mins + (inHours * 60);
	mins = mins + (inMinutes * 1);
	return mins;
}

//inDHMStr format is days, hours, minutes
function getDHMMinutes(inDHMStr) {
	var arr = inDHMStr.split(", ");
	var dys = (arr[0].split(" "))[0];
    var hrs = (arr[1].split(" "))[0];
    var mns = (arr[2].split(" "))[0];
	return convertDHMtoMin(dys,hrs,mns);
}



function convertMintoDHM(inMinutes) {
	var days = Math.floor(inMinutes / (60*24));
	var minsleft = inMinutes % (60*24);
	var hrs = Math.floor(minsleft / 60);
	minsleft = minsleft % 60;
	var dayText = days === 1 ? "day" : "days";
    var hourText = hrs === 1 ? "hour" : "hours";
    var minText = minsleft === 1 ? "minute" : "minutes";
	return days + " " + dayText + ", " + hrs + " " + hourText + ", " + minsleft + " " + minText;
}



function isTrue(inVal) {
	return inVal === 1 || inVal === "1" || inVal === "true" || inVal === true;
}
function getServerBoolean(inTF) {
	if (inTF) return 1;
	else return 0;
}
function isNullOrBlank(inVal) {
	return inVal === null || $.trim(inVal) === "" || $.trim(inVal) === "null";
}


function getURLStart() {
	return window.location.protocol; //ends with ":"
}
function getURLMiddle() {
	var hst = window.location.host;
	var fullpath = window.location.pathname; //starts with "/"
	var arrPath = fullpath.split("/");
	arrPath.pop(); //get rid of the file name
	var pth = "";
	for (var i=0; i < arrPath.length; i++) {
		if (i > 0 && arrPath[i].length > 0) pth += "/";
		pth += arrPath[i];
	}
	return hst + pth;
}
function getURLPath() {
	return getURLStart() + "//" + getURLMiddle();	
}

//goto page
function getPageURL(pageName, opts) {
    var url = getURLPath();
    
    //get the page
    switch(pageName) {
    case 'viewcalendar':
        //args are calid, sdt
        url += '/viewcalendar.php';
        break;
    case 'makeappt':
        //args are calid, blockid
        url += '/makeappt.php';
        break;
    case 'myappts':
        //no args
        url += '/myappts.php';
        break;
    case 'appt':
        //args are calid, blockid, (apptid)
        url += '/appt.php';
        break;
    }

    //get the arguments
    opts = opts || {};
    var args = '';
    _.each(Object.keys(opts), function(opt,ind) {
        if (ind === 0) args = '?';
        else args += '&';
        args += opt + '=' + opts[opt];
    });
    
    url += args;
    return url;
}
function goToPage(pageName, opts) {
    document.location.href = getPageURL(pageName, opts);
}


function disableButton(btnSelector) {
    $(btnSelector).addClass("btn-disabled");
    $(btnSelector).off("click");
}
function enableButton(btnSelector, callback) {
    $(btnSelector).removeClass("btn-disabled");
    $(btnSelector).on("click", callback);
}


//inArr is 2 dim array of title,link,linkid to build breadcrumb bar
function buildBreadcrumbs(inArr) {
	var str = '<div class="rcrumbs clear" id="breadcrumbs">';
	str += '<ul>';
	for (var i=0; i < inArr.length; i++) {
		var txt = inArr[i][0];
		var lnk = inArr[i][1];
		var lnkid = inArr[i][2];
		str += '<li>';
		if (!isNullOrBlank(lnk)) {
			str += '<a ';
			if (!isNullOrBlank(lnkid)) 
				str += 'id="' + lnkid + '" ';
			str += 'href="' + decodeURIComponent(lnk) + '" data-ajax="false">';
		}
		else {
			str += '<span class="bccurpage">';
		}
		str += txt;
		if (!isNullOrBlank(lnk)) 
			str += '</a><span class="divider">></span>';
		else 
			str += '</span>';
		str += '</li>';
	}
    str += '</ul>';
	str += '</div>';
	$(str).insertAfter("#nav");
	$("#breadcrumbs").rcrumbs();
	
	gBreadCrumbs = inArr;
}


function isValidForm(inFormID) {
	var str = "";
	var numErrors = 0;
	
	$("#" + inFormID).find(".required").each(function() {
		var nameof = $(this).attr("for");
		if (!$("#"+nameof).is(":hidden")) {
			var val = $("#"+nameof).val();
			if (isNullOrBlank(val)) {
				if (!isNullOrBlank(str)) str += ", ";
				str += $("#"+nameof).attr("title");
				numErrors++;
			}
		}
	});
	
	if (numErrors > 0) {
		if (numErrors === 1) str += " is a required field";
		else str += " are required fields";
		var err = new Error({errorcode: "",errortext: str});
		Error.displayErrors(new Array(err));
		return false;		
	}
	return true;
}


function showLoadingMsg(inIsShow) {
	if (inIsShow)
		$(".loadingmsg").show();
	else
		$(".loadingmsg").hide();
}


function capitalize(capitalizeMe) {
	return capitalizeMe.charAt(0).toUpperCase() + capitalizeMe.substring(1)	;
}

function IsUserGuest(inUserID) {
	return inUserID === g_GuestEmail;
}
function IsUserMember(inUserID) {
	return inUserID === g_loggedInUserID;
}
function IsThisUserLoggedIn(inUserID) {
	return IsUserMember(inUserID) || IsUserGuest(inUserID);
}
function getGuestUserID() {
	return g_GuestEmail;
}
function getMemberUserID() {
	return g_loggedInUserID;
}
function getLoggedInUserID() {
	if (!isNullOrBlank(g_loggedInUserID))
		return g_loggedInUserID;
	return g_GuestEmail;
}


// Released under MIT license: http://www.opensource.org/licenses/mit-license.php
// Placeholder fix to make it show up in textareas.
$('[placeholder]').focus(function() {
  var input = $(this);
  if (input.val() === input.attr('placeholder')) {
    input.val('');
    input.removeClass('placeholder');
  }
}).blur(function() {
  var input = $(this);
  if (input.val() === '' || input.val() === input.attr('placeholder')) {
    input.addClass('placeholder');
    input.val(input.attr('placeholder'));
  }
}).blur().parents('form').submit(function() {
  $(this).find('[placeholder]').each(function() {
    var input = $(this);
    if (input.val() === input.attr('placeholder')) {
      input.val('');
    }
  })
});
