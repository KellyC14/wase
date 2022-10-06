var gPrevLocalCal = "none";
function PreferencesView() {
	
}

PreferencesView.prototype.setPageVars = function() {
	$("#pagetitle").text(this.pagetitle);
	
	$("#divGoogleCalList").hide();
	$("#divExchangeMessage").hide();
	$("#divNoneMessage").hide();
    $("#diviCalMessage").hide();
    $("#divGoogleMessage").hide();
	$("#divWaitlistArea").hide();
	
	//check for google_result parameter
	/*switch ($.urlParam("google_result")) {
		case "success":
			$("#divGoogleAuthMsg").hide();
			prefsViewController.getUpdatedGoogle();
			break;
		
		case "callist":
			$("#divGoogleAuthMsg").hide();
			controller.getSessionVar(["CALLIST"]);
			break;
			
		case "denied":
			$("#divGoogleAuthMsg").show();
			//error message
			setAlertMsg("Request for access to Google calendar(s) denied by user");
			//set back to previous
			$("#selSyncCalType").val(gPrevLocalCal).selectmenu('refresh');
			break;
	}*/
};

/*PreferencesView.prototype.setPrefsParms = function(googleid,exchangeuser,exchangedirect) {
CONSOLE.log("setting PrefsParms");
	if (!isNullOrBlank(googleid))
		$("<option>").val("google").text("Google Calendar").appendTo("#selSyncCalType");
	if (!isNullOrBlank(exchangeuser)) {
		$("<option>").val("exchange").text("Exchange/Outlook").appendTo("#selSyncCalType");
		if (exchangedirect === "1") {
			//display message
			$("#divExchangeMessage").show();
		}
	}
	$("<option>").val("ical").text("iCal").appendTo("#selSyncCalType");
	$("<option>").val("none").text("None").appendTo("#selSyncCalType");
}*/

PreferencesView.prototype.setUserStatus = function(isowner,ismanager,ismember) {
	if (isowner || ismanager || ismember) {
		$("#divWaitlistArea").show();
	} else {
		$("#divWaitlistArea").hide();
	}
};


PreferencesView.prototype.setTextMsgEmail = function(txtemail) {
	if (isNullOrBlank(txtemail)) txtemail = "<span class='italic'>(none selected)</span>";
	$("#prefinfo #divTextEmail").html(txtemail);
	$("#prefinfo .txtmsgclick").text("edit");
};


PreferencesView.prototype.showIsValidTxtEmail = function(isValid,txtaddr) {
	if (!isValid) {
		alert("Note: This text message email address does not appear to be valid; please check to make sure you have the correct cell phone number and provider SMS gateway.");
	} else {
		this.setTextMsgEmail(txtaddr);
		$('#prefinfo #divTextMsgEmailContents').hide();
		doPrefsSubmit();
	}
};

PreferencesView.prototype.loadUpdatedGoogle = function(google_token) {
	$("#txtGoogleToken").val(google_token);	
};

PreferencesView.setCalTypeDisplay = function() {
	var val = $("#selSyncCalType").val();
    $("#divGoogleAuthMsg").hide();
    $("#divExchangeMessage").hide(); 
    $("#divNoneMessage").hide();
    $("#diviCalMessage").hide();
    $("#divGoogleMessage").hide();
    
    if (val === "google") {
        if (isNullOrBlank($("#txtGoogleToken").val())) {   
            $("#divGoogleAuthMsg").show();
        } else {
            $("#divGoogleMessage").show();
        }
    } else if (val === "none") {
		$("#txtGoogleToken").val('');
		$("#divNoneMessage").show();
    } else if (val === "exchange") {
		$("#txtGoogleToken").val('');
        $("#divExchangeMessage").show();
    } else if (val === "ical") {
        $("#txtGoogleToken").val('');
        $("#diviCalMessage").show();
    }
    gPrevLocalCal = val;
};
PreferencesView.prototype.loadPrefData = function(prefObj) {
    $("#selSyncCalType").empty();
	//calendar type
	if (isNullOrBlank(prefObj['localcal'])) prefObj['localcal'] = "none";

	//if (!isNullOrBlank(gParms['GOOGLEID']))
	$("<option>").val("google").text("Google Calendar").appendTo("#selSyncCalType");
	
	
	if (!isNullOrBlank(gParms['EXCHANGE_USER'])) {
		$("<option>").val("exchange").text("Exchange/Outlook").appendTo("#selSyncCalType");
		if (gParms['EXCHANGE_DIRECT'] === "1" && prefObj['localcal'] === "exchange") {
			//display message
			$("#divExchangeMessage").show();
		}
	}
	$("<option>").val("ical").text("iCal").appendTo("#selSyncCalType");
    if (prefObj['localcal'] === "ical")
        $("#diviCalMessage").show();
    
	$("<option>").val("none").text("None").appendTo("#selSyncCalType");
	if (prefObj['localcal'] === "none")
	    $("#divNoneMessage").show();
	
	$("#selSyncCalType").val(prefObj['localcal']);
	$("#selSyncCalType").selectmenu('refresh');

	//txt msg email
	this.setTextMsgEmail(prefObj['txtmsgemail']);
	
	//confrm and remind 
	$("#chkConfirm").prop("checked",isTrue(prefObj['confirm'])).checkboxradio('refresh');
	$("#chkRemind").prop("checked",isTrue(prefObj['remind'])).checkboxradio('refresh');
	$("#chkNotifyWL").prop("checked",isTrue(prefObj['waitnotify'])).checkboxradio('refresh');
	
	$("#txtGoogleToken").val(prefObj['google_token']);

    PreferencesView.setCalTypeDisplay();

    //for google authorization redirects
    gPrevLocalCal = prefObj['localcal'];
    var res = $.urlParam("google_result");
    if (isNullOrBlank(res)) {
        $("#selSyncCalType").val(prefObj['localcal']).selectmenu('refresh');
        $("#selSyncCalType").change();
    } else {
        //check for google_result parameter
        switch (res) {
            case "success":
                $("#divGoogleAuthMsg").hide();
                prefsViewController.getUpdatedGoogle();
                break;
            
            case "callist":
                $("#divGoogleAuthMsg").hide();
                controller.getSessionVar(["CALLIST"]);
                break;
                
            case "denied":
                $("#divGoogleAuthMsg").show();
                //error message
                setAlertMsg("Request for access to Google calendar(s) denied by user");
                //set back to previous
                $("#selSyncCalType").val(gPrevLocalCal).selectmenu('refresh');
                $("#selSyncCalType").change();
                break;
        }
    }

    $("#selSyncCalType").bind("change",function() {
    	PreferencesView.setCalTypeDisplay();
        doPrefsSubmit(); //automatically save pref data on value change
    });

	$("#prefinfo input").bind("change",function() {
	    doPrefsSubmit();
	});
};

//Show "saved" message on page
PreferencesView.prototype.showSaveMsg = function() {
	gMessage.displayConfirm("Preferences Saved.");
};

PreferencesView.prototype.showCalList = function(inVal) {
	var arrCalList = inVal.split(";;;");
	for (var i=0; i < arrCalList.length; i++) {
		$('<option>').val(arrCalList[i]).text(arrCalList[i]).appendTo('#selCalLists');
	}
	$('#selCalLists').selectmenu('refresh');
};

PreferencesView.prototype.showCalID = function(inVal) {
	if (!isNullOrBlank(inVal)) {
		if (!$("#selCalLists option[value='"+inVal+"']").length > 0)
			$('<option>').val(inVal).text(inVal).appendTo('#selCalLists');
		$("#selCalLists").val(inVal);
		$('#selCalLists').selectmenu('refresh', true);
		//$("#selCalLists").prop("disabled","disabled");
	}
};

//Save preference data from form
PreferencesView.prototype.savePrefData = function() {
    var localcal = $("#selSyncCalType").val();
    var txtmsgemail = $("#prefinfo #divTextEmail").text();
    if (txtmsgemail === cTxtMsgNoneSelText) txtmsgemail = "";
    var vals = {
        txtmsgemail: txtmsgemail,
        localcal: localcal,
        confirm: getServerBoolean($("#chkConfirm").is(":checked")),
        remind: getServerBoolean($("#chkRemind").is(":checked")),
        waitnotify: getServerBoolean($("#chkNotifyWL").is(":checked"))
    };
	prefsViewController.savePrefs(vals);
};

var preferencesview = new PreferencesView();