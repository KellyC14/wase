function Controller() {
	this.blockheaders = new Array();
}


/********************************************/
/* Global Utilities							*/
/********************************************/

Controller.doErrorCheck = function($xml) {
	//error checking
	var errs = [];
	var err = null;
	($xml.find("error")).each(function() {
		err = readErrorXML($(this));
		if(err != null) {
			errs.push(err);
		}
	});
	if (errs.length > 0) {
		Error.displayErrors(errs);
		return true;
	}
	return false;
};

Controller.doInfMsgCheck = function($xml) {
	//infmsg checking
	var msg = null;
	($xml.find("infmsg")).each(function() {
		msg = $(this).text();
	});
	if (!isNullOrBlank(msg)) {
		controller.setSessionVar([["infmsg",msg]]);
	}
	return msg;
};

Controller.getXMLDoc = function(data) {
	var xmlDoc = data.getElementsByTagName('wase')[0];
	//var xmlDoc = $.parseXML(data);
	return $(xmlDoc);
	
};


/********************************************/
/* XML Builders								*/
/********************************************/
function safeXML(inVal) {
	if (isNullOrBlank(inVal)) return "";
	return inVal;	
}

/********************************************/
/* Global - get parameter					*/
/********************************************/

Controller.prototype.showGetParameters = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;
		
	//getparameter
	var parm = "";
	var val = "";
	if ($xml.find("getparameter")) {
		$xml.find("getparameter").each(function() {
			parm = $(this).find("parameter").text();
			val = $(this).find("value").text();
			gParms[parm] = val;
			if (parm === "ALERTMSG") {
				if (val !== "") setAlertMsg(val); //in login.page.php, _header.php
			}
			else if (parm === "DAYTYPES") {
				blockview.loadDayTypes(val);
			}
			else if (parm === "COURSECALS") {
				makeapptview.setCourseCalParmVal(val);
			}
			else if (parm === "WAITLIST") {
			    val = isTrue(val);
			    gIsWaitlistOn = val;
	            if (typeof calendarconfigview !== 'undefined') calendarconfigview.setWaitlistParmVal(val);
	            if (typeof myapptsview !== 'undefined') myapptsview.setWaitlistParmVal(val);
			}
		});
		if (parm !== "SYSID" && parm !== "SYSNAME") { //These are the parms called on every page in the global "_header.php" file, and separate getparameters call
			if (typeof apptview !== 'undefined') apptview.setParmNetID();
			if (typeof blockview !== 'undefined') blockview.setParms();
			if (typeof calendarconfigview !== 'undefined') calendarconfigview.setParms();
			if (typeof calendarsview !== 'undefined') calendarsview.setParmNetID();
			if (typeof makeapptview !== 'undefined') makeapptview.setParmNetID();
			if (typeof myapptsview !== 'undefined') myapptsview.setParmNetID();
		}
	}
};

Controller.prototype.getParameters = function(inParamArray) {
	var str = "";
	for (var i=0; i < inParamArray.length; i++) {
		str += "<getparameter>";
		str += "<parameter>" + inParamArray[i] + "</parameter>";
		str += "</getparameter>";
	}
	callAJAX(str,this.showGetParameters);
};


/********************************************/
/* Global - session variables				*/
/********************************************/

Controller.prototype.showSetSessionVar = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;
	
	$xml.find("sessionvar").each(function() {
		sesvar = $(this).find("var").text();
		val = $(this).find("val").text();
		if (sesvar === "infmsg") {
			if (typeof apptview !== 'undefined') document.location.href = getURLPath() + "/" + Controller.gGoToPage; //myappts.php";
			if (typeof viewcalview !== 'undefined') document.location.href = getURLPath() + "/" + Controller.gGoToPage;
			if (typeof blockview !== 'undefined') document.location.href = getURLPath() + "/" + Controller.gGoToPage;
		}
	});
};

Controller.prototype.setSessionVar = function(inVarValArray) {
	var str = "";
	str += "<setvar><sessionid>" + g_sessionid + "</sessionid>";
	for (var i=0; i < inVarValArray.length; i++) {
		str += "<sessionvar><var>" + inVarValArray[i][0] + "</var>";
		str += "<val>" + inVarValArray[i][1] + "</val></sessionvar>";
	}
	str += "</setvar>";

	callAJAX(str,this.showSetSessionVar);
};

Controller.prototype.showGetSessionVar = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;

	
	//getvar or getandclearvar
	var arr = [];
	var sesvar = "";
	var val = "";
	if ($xml.find("sessionvar")) {
		$xml.find("sessionvar").each(function() {
			sesvar = $(this).find("var").text();
			val = $(this).find("val").text();
			arr.push([sesvar, val]);
			if (sesvar === "breadcrumbs") {
				if (typeof viewcalview !== 'undefined') viewcalview.drawBreadcrumbs(val.split(","));
				if (typeof calendarconfigview !== 'undefined') calendarconfigview.drawBreadcrumbs(val.split(","));
				if (typeof blockview !== 'undefined') blockview.drawBreadcrumbs(val.split(","));
			}
			if (sesvar === "infmsg") {
			    gMessage.displayConfirm(val);
			}
			if (sesvar === "apptid") {
				if (typeof myapptsview !== 'undefined') myapptsview.highlightAppt(val);
				if (typeof makeapptview !== 'undefined') makeapptview.setHighlightedApptID(val);
			}
			if (sesvar === "CALLIST") {
				if (typeof preferencesview !== 'undefined') preferencesview.showCalList(val);
			}
		});
		if (typeof myapptsview !== 'undefined') myapptsview.setFilters(arr);
	}
};

Controller.prototype.getSessionVar = function(inVarArray) {
	var str = "";
	str += "<getvar><sessionid>" + g_sessionid + "</sessionid>";
	for (var i=0; i < inVarArray.length; i++) {
		str += "<sessionvar><var>" + inVarArray[i] + "</var>";
		str += "</sessionvar>";
	}
	str += "</getvar>";

	callAJAX(str, this.showGetSessionVar);
};

Controller.prototype.getAndClearSessionVar = function(inVarArray) {
	var str = "";
	str += "<getandclearvar><sessionid>" + g_sessionid + "</sessionid>";
	for (var i=0; i < inVarArray.length; i++) {
		str += "<sessionvar><var>" + inVarArray[i] + "</var>";
		str += "</sessionvar>";
	}
	str += "</getandclearvar>";

	callAJAX(str,this.showGetSessionVar);
};


/********************************************/
/* User Info								*/
/********************************************/

Controller.prototype.showUserInfo = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;

    if (typeof apptview !== 'undefined') {
        //getuserinfo. Will be just one.
        var $getuserinfo = $xml.find("getuserinfo");
        var usr = new User($getuserinfo.find("userinfo"));
        var userID = usr.get("userid");
        var enteredID = $getuserinfo.children('userid').text();
        //entered userid was really email. Popup message
        if (enteredID !== userID) {
            initEmailAsUserID({
            	enteredID: enteredID,
				userEmail: usr.get("email"),
				userID: userID,
				onYes: _.bind(apptview.loadLoggedInUserData,apptview,usr),
				onNo: _.bind(apptview.emptyUserData,apptview)});
            $("#popupEmailAsUserID").popup("open");
        } else {
            apptview.loadLoggedInUserData(usr);
		}
    }
};

//Look up user info based on entered user ID
Controller.prototype.loadUserInfo = function(inUserID) {
	var str = "<getuserinfo><sessionid>" + g_sessionid + "</sessionid>";
	str += "<userid>" + inUserID + "</userid>";
	str += "</getuserinfo>";
	callAJAX(str,this.showUserInfo);
};


/********************************************/
/* Appointment - Add/Edit/Delete			*/
/********************************************/

//After adding or editing, and the AJAX response is returned, the user goes to my appts page
Controller.gGoToPage = "myappts.php";

/********************************************/
/* Block - Add/Edit/Delete					*/
/********************************************/

//After adding or editing, and the AJAX response is returned, the user goes to view calendar page
gNavCalID = null;
var gSvrSDT = null;
Controller.prototype.goToViewCalendar = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;

	var sdt = formatDateforServer(new Date());
	if ($xml.find("block").length) {
		$xml.find("block").each(function() {			
			$(this).find("startdatetime").each(function() {			
				sdt = $(this).text();
			});
		});
	} else if (typeof gNavDate !== 'undefined'){
		sdt = formatDateforServer(gNavDate);
	} 
	gSvrSDT = getURLDateTime(sdt);
	var msg = Controller.doInfMsgCheck($xml); //if there is a msg here, it sets a session var to be retrieved on viewcalendar.php
	
	if (isNullOrBlank(msg)) //when there's not an infmsg that we have to store and pass (done above)		
		document.location.href = getURLPath() + "/viewcalendar.php?calid=" + gNavCalID + "&sdt=" + gSvrSDT;
};


/********************************************/
/* Login									*/
/********************************************/

Controller.prototype.doLogin = function(data) {
	$xml = Controller.getXMLDoc(data);
	
	if (Controller.doErrorCheck($xml)) return false;
	
	var sid = null;
	if ($xml.find("login")) {
		$xml.find("login").each(function() {
			sid = $(this).find("sessionid");
		});	
	}
	g_sessionid = sid;

	document.location.href = getURLPath() + "/makeappt.php";
};

Controller.prototype.loginGuest = function(inEmailAddr,inPwd) {
	var str = "<login>";
	if (!isNullOrBlank(inPwd)) {
		str += "<email>" + inEmailAddr + "</email>";
		str += "<password>" + inPwd + "</password>";
	} else {
		str += "<email>" + inEmailAddr + "</email>";
	}
	str += "</login>";
	
	callAJAX(str,this.doLogin);
};

Controller.prototype.loginUserPwd = function(inUserID,inPassword) {
	var str = "<login>";
	str += "<userid>" + inUserID + "</userid>";
	str += "<password>" + inPassword + "</password>";
	str += "</login>";
	
	callAJAX(str,this.doLogin);
};


Controller.prototype.showDidYouKnow = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;

	var dyk = null;
	if ($xml.find("getdidyouknow")) {
		$xml.find("getdidyouknow").each(function() {
			if ($(this).find("didyouknow")) {
				$(this).find("didyouknow").each(function() {
					$didyouknowid = $(this).find("didyouknowid");
					$dateadded = $(this).find("dateadded");
					$release = $(this).find("release");
					$topics = $(this).find("topics");
					$header = $(this).find("header");
					$details = $(this).find("details");
	                dyk = new DidYouKnow($didyouknowid.text(),$dateadded.text(),$release.text(),$topics.text(),$header.text(),$details.text());
	                var total = null;
	                var remaining = null;
	                if ($(this).find("total"))
	                    total = $(this).find("total").text();
	                if ($(this).find("remaining"))
	                    remaining = $(this).find("remaining").text();
	                loginview.loadDYKData(dyk); //on the login page
				});
		    }
		});
	}
	
};

Controller.prototype.getDidYouKnow = function($xml) {
	var str = "<getdidyouknow>";
	str += "</getdidyouknow>";
	
	callAJAX(str,this.showDidYouKnow);
};


/********************************************/
/* PreferencessView - page load				*/
/********************************************/

Controller.showPrefs = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;


	//getprefs
	var txtmsgemail = "";
	var localcal = "none";
	var confrm = 1;
	var remind = 1;
	var waitnotify = 0;
	var google_calendarid = "";
	if ($xml.find("getprefs")) {
		$xml.find("pref").each(function() {
			switch ($(this).find("keytag").text()) {
				case "txtmsgemail": 
					txtmsgemail = $(this).children("val").text();
					break;
				case "localcal": 
					localcal  = $(this).children("val").text();
					break;
				case "confirm":
					confrm = $(this).children("val").text();
					break;
				case "remind":
					remind = $(this).children("val").text();
					break;
				case "waitnotify":
					waitnotify = $(this).children("val").text();
					break;
				case "google_calendarid":
					google_calendarid = $(this).children("val").text();
					break;
			}
		});
	}

	if (typeof apptview !== 'undefined') apptview.loadPrefData(txtmsgemail,remind);
	else if (typeof calendarconfigview !== 'undefined') calendarconfigview.setupCalSync(localcal);
	else if (typeof preferencesview !== 'undefined') preferencesview.loadUpdatedGoogle(google_calendarid);
};

Controller.showViewCalPrefs = function(data) {
    $xml = Controller.getXMLDoc(data);  
    if (Controller.doErrorCheck($xml)) return false;
    
    var prefVal = null;
    $xml.find("getprefs pref").each(function() {
        switch ($(this).find("keytag").text()) {
        case "isEnlargedViewCal":
            prefVal = $(this).children("val").text();
            viewcalview.dateSelector.setCalSizeFromPref(prefVal);
            break;
        }
    });
    
};

Controller.prototype.getPrefs = function(inKeyArr) {
	var str = "";
	str += "<getprefs><sessionid>" + g_sessionid + "</sessionid>";
	for (var i=0; i < inKeyArr.length; i++) {
		str += "<pref><keytag>" + inKeyArr[i] + "</keytag></pref>";	
	}
	str += "</getprefs>";

	if (typeof apptview !== 'undefined') callAJAX(str,Controller.showPrefs);
    else if (typeof viewcalview !== 'undefined') callAJAX(str,Controller.showViewCalPrefs);
    else if (typeof calendarconfigview !== 'undefined') callAJAX(str,Controller.showPrefs);
    else if (typeof preferencesview !== 'undefined') callAJAX(str,Controller.showPrefs);
};

Controller.prototype.showSavePrefs = function(data) {
	$xml = Controller.getXMLDoc(data);	
	if (Controller.doErrorCheck($xml)) return false;

	if (typeof preferencesview !== 'undefined') preferencesview.showSaveMsg();
};

Controller.prototype.savePref = function(inPrefKeyTag,inPrefVal) {
	var str = "";
	str += "<saveprefs><sessionid>" + g_sessionid + "</sessionid>";
	str += "<pref><keytag>" + inPrefKeyTag + "</keytag><val>" + inPrefVal + "</val><class>user</class></pref>";	
	str += "</saveprefs>";

	callAJAX(str,this.showSavePrefs);
};

/********************************************/
/* Declare controller instance globally		*/
/********************************************/
	
var controller = new Controller();
