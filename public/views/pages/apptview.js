var gAppt;
var gBlkID;
//var gApptDate;

function ApptView() {
	this.apptid = null;
	this.isNewAppt = true;
	this.startdatetime = new Date();
	this.enddatetime = new Date();
	this.ismultidate = false;
	
	this.calid = null;
	this.managers = [];
	this.purreq = false;
	
	this.isOwnerManager = false;
	this.canUpdate = true;
}

//set and show the page title and set the global apptid to the input apptid (sent from calling page).
ApptView.prototype.setPageVars = function(inApptID,inSDT,inEDT) {
	if (!isNullOrBlank(inApptID)) { //existing appointment
		this.apptid = inApptID;
        $(".btnSubmit").button();
		$(".btnSubmit").val("Save");
		$(".btnSubmit").button("refresh");
		this.isNewAppt = false;
	} else { //new appointment
	    //set up for guest
		var isGuest = !isNullOrBlank(g_GuestEmail);
    	this.setApptType(isGuest);
	    if (isGuest) {
	        $("#txtEmail").val(g_GuestEmail);
	    }
	}

	if (inSDT) this.startdatetime = inSDT;
	if (inEDT) this.enddatetime = inEDT;
	
	//Show/Hide appropriate fields
	if (this.apptid === null) {
		$("#divWhenMade").hide();
		$("#cancelbutton").hide();
	} else {
		$("#cancelbutton").show();
	}
	
	//set up guest toggle
	var that = this;
	$(".appttyperadio").on("click", function() {
		that.setGuestFields($(this).val() === 'guest');
	});
	
	//set up reminders section
	$("#chkRemindText").parents(".ui-checkbox").hide();
	$("#divTextMsgEmail").hide();
	$("#chkRemind").on("click", function() {
		var isChecked = $(this).prop("checked");
		//show the text reminder field, only if email reminder checked
		$("#chkRemindText").parents(".ui-checkbox").toggle(isChecked);
		if (!isChecked) {
			$("#chkRemindText").prop("checked",false).checkboxradio('refresh');
			$("#divTextMsgEmail").hide();
		}
	});
	$("#chkRemindText").on("click", function() {
		//show the text message address field, only if text reminder checked
		var isTextRemind = $(this).prop("checked");
		$("#divTextMsgEmail").toggle(isTextRemind);
	});
};

//called only on edit
ApptView.prototype.checkEnabled = function() {
	var today = new Date();
	if ((gAppt.get('startdatetime').getTime() < today.getTime() && !this.isOwnerManager) || !this.canUpdate) {
		$("form#apptinfo").find("input[type='text']","textarea").each(function() {
			if ($(this).textinput())
				$(this).textinput("disable");
		});
		$("textarea").addClass('ui-disabled');
		$("form#apptinfo").find("input[type='checkbox']").each(function() {
			$(this).checkboxradio('disable');
		});
		$("form#apptinfo").find("select").each(function() {
			$(this).selectmenu('disable');
		});
		$(".btnSubmit").button('disable');
		$("#cancelbutton").hide();
	}
	
	if ($('#txtUserID').length && !this.isOwnerManager) $('#txtUserID').textinput('disable');
};

ApptView.prototype.setParmNetID = function() {
	//txtUserID set dynamically below
};

ApptView.prototype.setTextMsgEmail = function(txtemail) {
	var isChecked = true;
	if (isNullOrBlank(txtemail)) {
		txtemail = "<span class='italic'>" + cTxtMsgNoneSelText + "</span>";
		isChecked = false;
	}
	$("#apptinfo #divTextEmail").html(txtemail);
	$("#apptinfo .txtmsgclick").text("edit");
	
	//remindtext - set opposite, then trigger click
	$("#chkRemindText").prop("checked",!isChecked).checkboxradio('refresh').trigger("click");
};

ApptView.prototype.showIsValidTxtEmail = function(isValid,txtaddr) {
	if (!isValid) {
		alert("Note: This text message email address does not appear to be valid; please check to make sure you have the correct cell phone number and provider SMS gateway.");
	} else {
		this.setTextMsgEmail(txtaddr);
		$("#editTextMsgEmail").popup("close");
	}
};


ApptView.prototype.loadPrefData = function(txtmsgemail,remind) {
	//txt msg email
	this.setTextMsgEmail(txtmsgemail);
	
	//remind - set opposite, then trigger click
	$("#chkRemind").prop("checked",!isTrue(remind)).checkboxradio('refresh').trigger("click");
};


ApptView.prototype.setApptType = function(isGuest) {
	var typeVal = isGuest ? 'guest' : 'member';
	$('#cgApptType :radio[value="' + typeVal + '"]').siblings('label').trigger('vclick');
};

ApptView.prototype.setGuestFields = function(isGuest) {
	if (isGuest) {
		$("#lblUserID").text('E-mail (guest log-in):');
	} else {
		$("#lblUserID").text('User ID:');
	}
	$("#divEmail").toggle(!isGuest);
};

ApptView.prototype.loadUserStatus = function(isOwner,isManager,isMember) {
	//Check if logged in user is owner of the calendar, or manager of calendar
	if (isOwner || isManager)
		this.isOwnerManager = true;
};

//use labels on the block to set 'appointment' text
ApptView.prototype.setAppText = function(inBCFlatArr) {
	//page title
	var titleText = "Sign Up for " + capitalize(this.appName); //Default to new appointment
	if (!isNullOrBlank(this.apptid)) {
		titleText = "Edit " + capitalize(this.appName);
	}
	$("#pagetitle").text(titleText);
	
	//sub title
	$("#hApptFor").text("Who is the " + this.appName + " for?");
	$("#divApptType legend").text(capitalize(this.appName) + " For");
	
	//cancel button
	var cancelText = 'Cancel ' + capitalize(this.appName);
	$("#cancelbutton").text(cancelText).attr('title',cancelText);

	//set up breadcrumbs
	var bcarray = new Array();
	for (var i=0; i < inBCFlatArr.length; i+=3) {
		//special case for view calendar, make sure calid is URL calid.
		if (inBCFlatArr[i].indexOf("View Calendar") > -1) {
			var lnk = "viewcalendar.php?calid=" + $.urlParam("calid");
			inBCFlatArr[i+1] = lnk;
			this.goToPage = lnk;
		}
		bcarray.push([inBCFlatArr[i],inBCFlatArr[i+1],inBCFlatArr[i+2]]);
	}
	bcarray.push([titleText,"",""]);
	buildBreadcrumbs(bcarray); 
};


ApptView.prototype.getTimeDisplay = function(st,et,inBlk,slt) {
    var strText = moment(st).format(fmtTime) + " - " + moment(et).format(fmtTime);
    if (this.ismultidate) strText += " (" + moment(st).format(fmtDate) + ")";
    //only show how many are taken to owner/managers
    if (this.isOwnerManager && inBlk.slots.length > 1 && slt.appts.length > 0) {
        var howmany = inBlk.get('maxapps') === 0 ? "unlimited" : inBlk.get('maxapps');
        var what = howmany === 1 ? this.appName : this.appsName;
		strText += "<span class='apptstaken'> (" + slt.appts.length + " of " + howmany + " " + what + " taken)";
        var thisID = _.find(slt.appts, function(appt) { return appt["appointmentid"] === this.apptid; },this);
        if (thisID)
            strText += "**";
        strText += "</span>";
    }
    return strText;
};

//Put the block data from the block object on the form
ApptView.prototype.loadBlockData = function(inBlk, bcArray) {
	this.block = inBlk;
	gBlkID = inBlk.get('blockid');

	//toggle time display based on multiselect
	var isMulti = this.isMultiTimeSelect();
	$("#lblTimeMulti").toggle(isMulti);
	$("#divTimeSelect").toggle(isMulti);
	$("#divTimeNote").toggle(this.apptid !== null);
    $("#lblTime").toggle(!isMulti);
    $("#divTime .ui-select").toggle(!isMulti);

	this.calid = inBlk.get('calendarid');
	
	this.appName = inBlk.get('labels')['APPTHING'];
	this.appsName = inBlk.get('labels')['APPTHINGS'];
	this.setAppText(bcArray);
	
	//deadlines
	var mStart = moment(inBlk.get("startdatetime"));
    var strDeadlines = "";
    var dl = inBlk.get("deadline");
    if (dl > 0)
        strDeadlines += "<b>Make Deadline:</b> " + mStart.clone().subtract(dl, "minutes").format("M/D/YYYY [at] h:mmA");
	//get cancel deadline in loadApptData (on appt)
    if (dl > 0) {
        var el = $("<div>", {class: 'deadlinetext', html: strDeadlines});
        var icon = $("<span>", {class: "ui-btn-icon-notext ui-icon-clock", style: "position: relative"});
        var iconWrapper = $("<div>", {class: "iconWrapper"});
        iconWrapper.append(icon);
        var par = $("<div>", {class: "deadlines"});
        par.append(iconWrapper, el);
        $("#divBlockTitle").before(par);
    }
	
	$("#divBlockTitle").html('<label id="lblBlockTitle" class="">On Block: </label><div>' + inBlk.get('title') + '</div>');
	$("#divBlockOwner").html('<label id="lblBlockOwner" class="">With: </label><div>' + inBlk.get('blockowner').getDisplayName() + '</div>');
	
	var loc = inBlk.get('location');
	if (isNullOrBlank(loc)) loc = "<span style='font-style:italic;'>No location specified</span>";
	loc = '<label id="lblLocation" class="">Location: </label><div>' + loc + '</div>';
	$("#divBlockLocation").html(loc);
	var sdt = inBlk.get('startdatetime');
	var edt = inBlk.get('enddatetime');
	if (!isDateSame(sdt,edt))
		this.ismultidate = true;
	var strdt = moment(sdt).format("dddd, MMMM D, YYYY");
	if (this.ismultidate) {
		strdt += '<span class="multidatedivider"></span>' + moment(edt).format("dddd, MMMM D, YYYY");
	}
	strdt = '<label id="lblDate" class="">Date: </label>' + strdt;
	$("#divDate").html(strdt);
    
    //Check for purpose required
    this.purreq = inBlk.get('purreq');
    if (this.purreq) {
        $("#txtPurpose").attr("placeholder", "Purpose (required)");
        $("#lblPurpose").addClass("required");  
    }
        
    //Check to see if logged in user is block owner - additional to setting of calendar owner/manager
    if (g_loggedInUserID === inBlk.get('blockowner').get('userid'))
        this.isOwnerManager = true;
    
    //init user ID field, as this is called once and only once each page load
    this.initUserField();
	
	//set field display based on user status
	$("#divApptType").toggle(this.isOwnerManager);
    
	//loop over slots and show times.
	var vw = this;
	if (isMulti) {
		var inSDT = this.startdatetime;
		var totalapptslots = 0; //used to set parent class with larger height
        _.each(inBlk.slots, function(slt,i) {
			//TODO: account for appointment slots
            var st = slt.get('startdatetime');
            var et = slt.get('enddatetime');
            var availClass = slt.get('available_flag') ? "" : " notavailable";
            var displayTime = vw.getTimeDisplay(st,et,inBlk,slt);
            totalapptslots += slt.appts.length;

            var $timeslot = $("<div>",{class: "timeslot"+availClass, html: displayTime}).data({"st": st, "et": et, "slotind": i});
            $("#divTimeSelect").append($timeslot);

            //is this the time slot that was selected by the user to get to this page?
            if (moment(inSDT).isSame(moment(st),"minutes"))
                $timeslot.addClass("selected");

            //set up timeslot click event handler
            $timeslot.on("click", function() {
                var selClass = "selected";
                if ($(this).hasClass(selClass)) {
                    //can always deselect
                    $(this).removeClass(selClass);
                } else if ($(this).hasClass("canselect")) {
                    //check if user is allowed to select (pre-determined with 'canselect' class)
                    $(this).addClass(selClass);
                }
                //update all slots that can be selected
                apptview.setSelectableTimes();
            })
        });
        this.setSelectableTimes();
        $("#divTimeSelect").toggleClass("ownerdisplay",this.isOwnerManager && totalapptslots > 0);

    } else { //maxper is 1
        for (var i = 0; i < inBlk.slots.length; i++) {
            var slt = inBlk.slots[i];
            //check to see if this appointment is the appointment in the slot, if so, show the time
            var isApptThisAppt = false;
            var that = this;
            _.each(slt.appts, function (appt) {
                if (appt.appointmentid === that.apptid)
                    isApptThisAppt = true;
            });
            //if there is an appointment at this slot, skip it.  We are only showing open slots.
            if (!this.isOwnerManager && inBlk.get('maxapps') > 0 && slt.appts.length >= inBlk.get('maxapps') && !isApptThisAppt) continue;
            //if the slot is not available (and user is not owner/manager), skip it.
            if (!this.isOwnerManager && !slt.get('available_flag')) continue;
            var st = slt.get('startdatetime');
            var et = slt.get('enddatetime');
            var str = "<option value = '" + formatDateTimeforServer(st) + "," + formatDateTimeforServer(et) + "'";
            if (isDateTimeSame(st, this.startdatetime))
                str += " selected='selected'";
            str += ">" + this.getTimeDisplay(st,et,inBlk,slt);
            str += "</option>";
            $('#selTime').append(str);
        }
        $('#selTime').attr('title', capitalize(this.appName) + ' Time');
        $('#selTime').selectmenu('refresh');
    }
	
	//access restrictions - check for guests
	this.canUpdate = true;
	var isGuest = !isNullOrBlank(g_GuestEmail);
	if (inBlk.get('accessrestrictions').get('viewaccess') !== 'open' && isGuest)
		this.canUpdate = false;
};


ApptView.prototype.setSelectableTimes = function() {
    //get all of the selected slots
    var selSlots = $(".timeslot.selected");
	//make an array of all of the selected time indices
    var selTimeInds = [];
    var lastInd = -1;
    _.each(selSlots, function(slt) {
    	var ind = $(slt).data("slotind");
        //for de-selecting, need to make sure to unselect anything after the deselected slot
    	if (lastInd !== -1 && ind > lastInd + 1) {
    		$(slt).removeClass("selected");
		} else {
            selTimeInds.push(ind);
            lastInd = ind;
		}
	});
    var first = 0 || selTimeInds[0];
    var last = 0 || selTimeInds[selTimeInds.length-1];
    var maxper = this.block.get('maxper');
    //loop over each timeslot to see if it can be selected (must be contiguous)
	$(".timeslot").each(function(i,slt) {
        //check selection rules
        var canSelect = false;
        //nothing is selected, so user can select anything
        if (selTimeInds.length === 0)
        	canSelect = true;
        else if (maxper === 0 || selTimeInds.length < maxper) {
			//can allow user to choose 1 before or 1 after
			if (i === first - 1) canSelect = true;
			if (i === last + 1) canSelect = true;
		}
		if ($(slt).hasClass("notavailable")) canSelect = false;
        $(slt).toggleClass("canselect",canSelect);
	});

    //remove the 'canselect' class from selected
    selSlots.removeClass("canselect");

};
//Put the user data from the user object on the form - response from 'loadUserInfo'
ApptView.prototype.loadLoggedInUserData = function(inUser) {
	this.setUserField(inUser.get('userid'));
	$("#txtApptName").val(inUser.get('name'));
	$("#txtPhone").val(inUser.get('phone'));
	$("#txtEmail").val(inUser.get('email'));
	
	//remove text msg email address - presumably not the same for a different user
	if (inUser.get('userid') !== g_loggedInUserID)
		this.setTextMsgEmail("");
};
ApptView.prototype.emptyUserData = function() {
    $("#txtApptName").val('');
    $("#txtPhone").val('');
    $("#txtEmail").val('');
};

ApptView.prototype.initUserField = function() {
	var strUsr = '';
	if (this.isOwnerManager) {
		strUsr = '<label id="lblUserID" for="txtUserID" class="required">User ID:</label>' 
			+ '<input type="text" name="txtUserID" id="txtUserID" placeholder="' + gParms["NETID"] + '" title="' + gParms["NETID"] + '" value="" />';
	} else {
		var txt = '';
		if (!isNullOrBlank(g_GuestEmail)) txt = g_GuestEmail;
		strUsr = '<label id="lblUserID" class="">User ID: </label>'
			+ '<div id="divUserIDText">' + txt + '</div>';
		$("#divUserID").addClass("textonlyfield");
	}
	
	$("#divUserID").html(strUsr);
	if ($("#txtUserID").length) {
		$("#txtUserID").textinput();
		$("#txtUserID").bind("blur", function(event, ui) {
			var val = $(this).val();
			if (!isNullOrBlank(val) && $("#cgApptType :radio:checked").val() !== 'guest') controller.loadUserInfo(val);
		});
	}
};
ApptView.prototype.setUserField = function(inUserID) {
	if (this.isOwnerManager) {
		$("#txtUserID").val(inUserID);
	} else {
		$("#divUserIDText").text(inUserID);
	}
};
ApptView.prototype.getUserVal = function() {
	var userid = "";
	if($("#txtUserID").length > 0) { //this will have guest email if a guest
		userid = $("#txtUserID").val();
	} else {
		userid = $("#divUserID div").text(); 
	}
	return userid;
};

	
//Put the appt data from the appt object on the form
ApptView.prototype.loadApptData = function(inAppt) {
	gAppt = inAppt;

	//Set Appointment Time
	var stAppt = inAppt.get('startdatetime');
	var etAppt = inAppt.get('enddatetime');
	var strText = moment(stAppt).format(fmtTime) + " - " + moment(etAppt).format(fmtTime);
	if (this.ismultidate) strText += " (" + moment(stAppt).format(fmtDate) + ")";

	//if existing appointment, check if past cancel deadline
	if (!isNullOrBlank(this.apptid)) {
		var $cancelButton = $("#cancelbutton");
		$cancelButton.click(function(e) {
			$("#popupCancelConfirm").popup("open");
		});
		var $dls = $(".deadlinetext");
		var cdl = inAppt.get('candeadline');
		if (cdl === "reached") {
	    	if (!this.isOwnerManager) {
	    		$cancelButton.addClass('pastdeadline');
	    		$cancelButton.attr('title','Past Cancelation Deadline');
	    		$cancelButton.on("click",$.noop);
	    		//disable all editable fields and save button
	    		this.canUpdate = false;
			}
			var $msg = $('<div>', {class: "pastMsg", html: "**Cancel Deadline Reached**"});
	    	$dls.append($msg).addClass("pastCanDeadline");
	    } else if (cdl !== 'none') {
	    	$dls.append($('<div>', {html: cdl}));
	    }
	}

	if (this.isMultiTimeSelect()) {
        var selecting = false;
        _.each($(".timeslot"), function (slt) {
            var dta = $(slt).data();
            if (moment(stAppt).isSame(moment(dta.st), 'minutes')) {
                selecting = true;
                $(slt).addClass("selected");
            }
            if (selecting)
                $(slt).addClass("selected");
            if (moment(etAppt).isSame(moment(dta.et), 'minutes')) {
                selecting = false;
            }
        });
        this.setSelectableTimes();
    } else {
        var str = "<option value = '" + formatDateTimeforServer(stAppt) + "," + formatDateTimeforServer(etAppt) + "' selected='selected'>" + strText + "</option>";
        $("#selTime > option").each(function() {
            //read the option value start time
            var ind = $(this).val().indexOf(",");
            var val = $(this).val().substring(0,ind);
            var stSlot = formatDateTimeFromServer(val);
            if (moment(stSlot).isSame(moment(stAppt))) {
                $(this).attr("selected","selected");
            }
        });
        $('#selTime').selectmenu('refresh');
	}

	$("#txtPurpose").val(inAppt.get('purpose'));
	if (this.purreq) {
		$("#txtPurpose").attr("placeholder", "Purpose (required)");
		$("#lblPurpose").addClass("required");
	}

	$("#divWhenMade").html('<label id="lblWhenMade" class="">When Made: </label><div>' + inAppt.getWhenMadeDisplay() + '</div>');
	$("#divWhenMade").show();
	
    //appt for/maker
	var maker = inAppt.get('apptmaker');
	this.setUserField(maker.get('userid'));
	$("#txtApptName").val(maker.get('name'));
	$("#txtPhone").val(maker.get('phone'));
	$("#txtEmail").val(maker.get('email'));
	//guest radio
	this.setApptType(maker.get('userid').indexOf('@') > 0);

	//Venue field
	$("#txtVenue").val(inAppt.get('venue'));
	
	//set remind opposite, then trigger click
	$("#chkRemind").prop("checked",!inAppt.get('remind')).checkboxradio('refresh').trigger("click");

	this.setTextMsgEmail(inAppt.get('textemail'));
	
	//if not block or calendar owner or appointment maker, cannot update
	if (!this.isOwnerManager && !IsThisUserLoggedIn(maker.get('userid')))
		this.canUpdate = false;
	
	this.checkEnabled();
};

ApptView.prototype.isMultiTimeSelect = function() {
	return this.block.get("maxper") !== 1;
};
//returns javascript Date() formatted object.
ApptView.prototype.getSelectedTimes = function() {
    //NOTE: Assumes slots are in order
    var selSlots = $(".timeslot.selected");
    if (!selSlots.length) return;
    var st = selSlots.first().data("st");
    var et = selSlots.last().data("et");
    return {"st": st, "et": et};
};
//string time formatted 'YYYY-MM-DD HH:mm:ss'
var fmtDateTime = 'YYYY-MM-DD HH:mm:ss';
ApptView.prototype.getSelectedStartEndTimes = function() {
    var sdt = '';
    var edt = '';
    if (this.isMultiTimeSelect()) {
        var tms = this.getSelectedTimes();
        sdt = formatDateTimeforServer(tms.st);
        edt = formatDateTimeforServer(tms.et);
    } else {
        var tm = $('#selTime').val(); //time is date/time string formatted for server already
        var i = tm.indexOf(",");
        sdt = tm.substring(0,i);
        edt = tm.substring(i+1,tm.length);
    }
    return {"sdt":sdt, "edt":edt};
};

//Save appointment data from form to input appointment object (gAppt)
ApptView.prototype.saveApptData = function() {
	var errs = new Array();

	var times = this.getSelectedStartEndTimes();
	var sdt = times.sdt;
	var edt = times.edt;

	//set a sessionVar for the view calendar page
	apptViewController.setSessionVar([["viewcal_selstartdate",moment(sdt,"YYYY-MM-DD HH:mm:ss").format("YYYYMMDD")]],$.noop);
    
    var pur = $("#txtPurpose").val();
    var venue = $("#txtVenue").val();
    var email_value = $("#txtEmail").val();
    email_value = email_value.replace(/;/g,','); //global replace semicolons with commas
	
    var userid = this.getUserVal();
    var isGuest = $("#cgApptType :radio:checked").val() === 'guest' || !isNullOrBlank(g_GuestEmail);
	var maker = new User({
	    'userid': userid,
	    'name': $("#txtApptName").val(),
	    'phone': $("#txtPhone").val(),
	    'email': isGuest ? userid : email_value
	});

	var rmd = $("#chkRemind").prop("checked");
	var rmdText = $("#chkRemindText").prop("checked");
	var txteml = $("#divTextEmail").text();
	if (txteml === cTxtMsgNoneSelText || !isTrue(rmdText) || !isTrue(rmd)) {
		if (isTrue(rmdText))
			errs.push(new Error({errorcode: "", errortext: "Text message reminder requested, but no address entered."}));
		txteml = "";
	}

	//display any errors
	if (errs.length > 0) {
		Error.displayErrors(errs);
		return false;		
	}
	
    var vals = {
        'blockid': gBlkID,
        'calendarid': this.calid,
        'startdatetime': sdt,
        'enddatetime': edt,
        'available': true,
        'apptmaker': maker,
        'purpose': pur,
		'venue': venue,
        'remind': rmd,
        'textemail': txteml,
        'madeby': isNullOrBlank(g_loggedInUserID) ? g_GuestEmail : g_loggedInUserID
    };

    if (gAppt == null) {
		gAppt = new Appointment(vals);
		gAppt.set('appointmentid','');
	} else {	
        gAppt.setFromObject(vals);
	}

    return true;
};

var apptview = new ApptView();