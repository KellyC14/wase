var gCurMgrMem = "";
var gUnsavedMgrMems = []; //Are there any unsaved manager/members

function buildMgrMem(userid,notify,remind) {
    if (userid.indexOf('xxx') === 0) {
        userid = userid.replace('xxx','@');
    }
    return new ManagerMember({
        user: new User({userid: userid}),
        calendarid: calendarconfigview.calid,
        status: 'active',
        notify: isTrue(notify),
        remind: isTrue(remind)
    });
}


var CalendarConfigView = function() {
	this.calid = null;
	this.savedCalendar = null; //WASEcalendar object
	this.icalpass = '';
	
	this.numFutureBlocks = 0;
	this.numFutureMemberBlocks = 0;
	
	this.managerList = [];
	this.memberList = [];
};

_.extend(CalendarConfigView.prototype, {
	//initialize the page
	initPage: function(inCalID) {
	    //init controller
	    waseCalController.init();

	    //create calendar vs. edit calendar
	    var pageTitle = "";
	    if (isNullOrBlank(inCalID)) { //create calendar
	    	pageTitle = "Create Calendar";
	    	//buttons
	    	$("#cancelbutton").hide();
			//hide managers/members section
			$("#divCollapseMgrMem").hide();
			$("#hrCollapseMgrMem").hide();
			$("#divCalIDField").hide();
			$("#divSetPublish").hide();
			$("#hrPublish").hide();
	    } else { //edit calendar
	    	this.calid = inCalID;
	    	pageTitle = "Calendar Settings";
			//buttons: delete, sync
	    	$("#cancelbutton").show();

            $("#newCalWaitlistNote").hide();
	    }

		$("#pagetitle").text(pageTitle);

		//set up URL section
		$('#divCalendarURL').hide();
        $('#divSubscribeURLs').hide();

        //Waitlist
		$("#divWaitlistEntries").hide();
		
		//Draw manager/member areas
		this.drawManagerSection();
		this.drawMemberSection();

		//get data from server
		var parms = ["NETID","INSNAME","WAITLIST","COURSELIM","COURSEIDPROMPT","ADRESTRICTED","GROUPIDPROMPT","STATUS_ATTRIBUTE","STATUSPROMPT","USERPROMPT"];
		calconfigViewController.loadCalConfigViewData(inCalID,new Date(),parms);
	},
	
	collapseAllSectionsBut: function(inOpenSectionID) {
		$(".group").collapsible("collapse");
		if (!isNullOrBlank(inOpenSectionID))
			$(inOpenSectionID).collapsible("expand");
	},
	
	//Put the user data on the form - new calendars ONLY
	loadLoggedInUserData: function(inUser) {
		this.calOwner = inUser;
		var nm = inUser.get('name');
		//calendar title
		$("#txtCalTitle").val("Calendar for " + nm);
		$("#divCalOwner").text(inUser.get('userid') + " (" + nm + ")");
		$("#txtName").val(nm);
		$("#txtPhone").val(inUser.get('phone'));
		$("#txtEmail").val(inUser.get('email'));
		$("#txtLocation").val(inUser.get('office'));
	},
	
	setNumBlocks: function(inNumBlocks,inNumMemberBlocks) {
		//Are there any future blocks?  This determines whether we show a "propagate changes" message.
		this.numFutureBlocks = inNumBlocks;
		this.numFutureMemberBlocks = inNumMemberBlocks;
	},
	
	loadCalendarData: function(inCal) {
		this.savedCalendar = inCal.getCopy();

		this.icalpass = inCal.get('icalpass');
		
		//calendar id
		$("#divCalID").text(this.calid);
		
		//labels
		this.drawLabelsSection(inCal.get('labels')); //sets this.labels
		//set labels on page
		$(".appName").text(capitalize(this.labels['APPTHING']));
		$(".appsName").text(capitalize(this.labels['APPTHINGS']));
		//set Access Restrictions labels
		gARdisplay.labels = _.clone(this.labels);
		
		//calendar title
		$("#txtCalTitle").val(inCal.get('title'));

		//Block Default Section
		//description
		$("#txtDescription").val(inCal.get('description'));
		//location
		$("#txtLocation").val(inCal.get('location'));
		
		//owner name/contact info
		var owner = inCal.get('owner');
		this.calOwner = owner;
		var nm = owner.get('name');
		$("#divCalOwner").text(owner.get('userid') + " (" + nm + ")");		
		$("#txtName").val(nm);
		//contact phone
		$("#txtPhone").val(owner.get('phone'));
		//contact email
		$("#txtEmail").val(owner.get('email'));
		
		//notify and remind calendar owner
		var nr = inCal.get('notifyandremind');
		$("#chkNotify").prop("checked",nr.get('notify')).checkboxradio('refresh');
		$("#chkRemind").prop("checked",nr.get('remind')).checkboxradio('refresh');
		//appt text
		$("#txtApptText").val(nr.get('apptmsg'));

		//require appt purpose
		$("#chkRequirePurpose").prop("checked",inCal.get('purreq')).checkboxradio('refresh');

		//allow overlap
		$("#chkAllowBlockOverlap").prop("checked",inCal.get('overlapok')).checkboxradio('refresh');

		//calendar url
		var calurl = getURLPath() + "/makeappt.php?calid=" + this.calid;
		var str = '<div class="urltitle">Advertise this URL to ' + capitalize(this.labels['APPTHING']) + ' makers:</div><div class="urlstring">' + calurl + '</div>';
		$(".cal_urlarea").append(str).show();
		$("#divCalendarURL").show();
		
		//icalpass
		var isSet = this.icalpass !== 'notset';
		var $selPub = $('#selSetPublish');
        $selPub.val(isSet + "");
        $selPub.flipswitch("refresh");
		if (isSet) this.drawPublishURLs();
        $selPub.bind("change",function() {
            if ($(this).val() === "true") {
                $('#warningIcal').popup('open');
            } else {
                $("#divSubscribeURLs").hide();
                waseCalController.setIcalPass(calendarconfigview.calid, 'notset');
            }
        });

		//waitlist
        var $selWait = $("#selWaitlist");
        $selWait.off("change");
        $selWait.val(inCal.get('waitlist') + "");
        $selWait.flipswitch("refresh");
		//hide the waitlist if it is off
		if (!inCal.get('waitlist'))
			$("#divWaitlistEntries").hide();
        $selWait.on("change",_.partial(CalendarConfigView.checkWaitlistOnOff,this.$confirmWaitlistOff));
		
		//access restrictions
		AccessRestrictions.loadAccessRestrData(inCal.get("accessrestrictions"));
		
		//Managers and Members
		var mgrs = inCal.get('managers');
		for (var m=0; m < mgrs.length; m++) {
			this.drawManagerMember("Managers",mgrs[m]);
		}
		var mems = inCal.get('members');
		for (m=0; m < mems.length; m++) {
			this.drawManagerMember("Members",mems[m]);
		}
		
		//Set notify/remind managers - must be after manager/member sections are drawn
		$("#chkNotManagers").prop("checked",nr.get('notifyman')); 
		$("#chkRemManagers").prop("checked",nr.get('remindman'));
	},

	drawPublishURLs: function() {
		var txt = this.labels['APPTHING'] || g_ApptText;
		var str = "";
		var suburl = "webcal://" + getURLMiddle() + "/ical.page.php?calid=" + this.calid + "&authid=" + g_loggedInUserID + "&dbr=" + this.icalpass;
		var rssurl = "rss://" + getURLMiddle() + "/rss.page.php?action=LISTBLOCKS&calid=" + this.calid + "&dbr=" + this.icalpass;
		
		str += '<div class="urltitle">Subscription URL (for Owners/Managers):</div>';
		str += '<div class="urlstring">' + suburl + '</div>';
		str += '<div class="urltitle">RSS Feed (for Owners/Managers/' + capitalize(txt) +' Makers):</div>';
		str += '<div class="urlstring">' + rssurl + '</div>';
		$(".ical_urlarea").empty().append(str).show();
		$("#divSubscribeURLs").show();
	},
	
	//For building the Labels section using given labels (from server parms for new, from saved calendar for existing)
	drawLabelsSection: function(labels) {
		//set var for page
		this.labels = labels;

		var $labels = $("#divLabels");

		//instructions
		var str = "";
		str += '<div class="instructions">You can change the labels used to identify the type of event '
			+ 'being scheduled (for example, "Office Hour", or "Try-out", or "Sign-up", or "Advising session") '
			+ 'and the event being scheduled (for example, "Appointment", "Booking", "Reservation").</div>';
		$labels.append(str);
		
		//fields
		var $fields = $("<fieldset>");
		$fields.append($("<div>", {id: "divNamething", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblNamething", class: "required", for: "txtNamething", html: "Label of type of event being scheduled:"}),
			$("<input>", {type: 'text', name: 'txtNamething', id: 'txtNamething', title: 'Label: event type'}).val(labels['NAMETHING'])
		));
		$fields.append($("<div>", {id: "divNamethings", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblNamethings", class: "required", for: "txtNamethings", html: "Plural of type of event being scheduled:"}),
			$("<input>", {type: 'text', name: 'txtNamethings', id: 'txtNamethings', title: 'Label: plural event type'}).val(labels['NAMETHINGS'])
		));
		$fields.append($("<div>", {id: "divAppthing", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblAppthing", class: "required", for: "txtAppthing", html: "Label of event being scheduled:"}),
			$("<input>", {type: 'text', name: 'txtAppthing', id: 'txtAppthing', title: 'Label: event'}).val(labels['APPTHING'])
		));
		$fields.append($("<div>", {id: "divAppthings", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblAppthings", class: "required", for: "txtAppthings", html: "Plural of event being scheduled:"}),
			$("<input>", {type: 'text', name: 'txtAppthings', id: 'txtAppthings', title: 'Label: plural event'}).val(labels['APPTHINGS'])
		));
        $labels.append($fields);
		$("#txtNamething").textinput();
		$("#txtNamethings").textinput();
		$("#txtAppthing").textinput();
		$("#txtAppthings").textinput();
	},
	
	//waitlist display
	createWaitlist: function() {
	    this.waitlistView = new WaitlistEntryView({calOwner: true});
	    $("#divWaitlistEntries").append(this.waitlistView.$waitList);
	},
	setWaitlistDisplay: function(isWaitlistOn) {
	    if (isWaitlistOn) {
	        if (!isNullOrBlank(this.calid)) {
	            if (!this.waitlistView)
	                this.createWaitlist();
	            waitlistController.getWaitlistForCalendar(this.calid);
	            $("#divWaitlistEntries").show();
	        }
	    } else {
	        $("#divWaitlistEntries").hide();
	    }
	},
	
	
	/************************************/
	/* called from Prefs/Parms/Vars     */
	/************************************/
	//getSessionVar - breadcrumbs
	drawBreadcrumbs: function(inBCFlatArr) {
		var bcarray = [];
		for (var i=0; i < inBCFlatArr.length; i+=3) {
			//special case for view calendar, make sure calid is URL calid.
			if (inBCFlatArr[i].indexOf("View Calendar") > -1)
				inBCFlatArr[i+1] = "viewcalendar.php?calid=" + this.calid;
			bcarray.push([inBCFlatArr[i],inBCFlatArr[i+1],inBCFlatArr[i+2]]);
		}
		if (!isNullOrBlank(this.calid))
			bcarray.push(["Calendar Settings","",""]);
		else
			bcarray.push(["Create Calendar","",""]);
		buildBreadcrumbs(bcarray); 
	},
	
	//getAndClearSessionVar - infmsg - taken care of in global gMessage handling
	
	//getPrefs - localcal
	setupCalSync: function(localcal) {
		var $elBtn = $(".localnav").find("#syncbutton");
		if ($elBtn.length && localcal === 'none') {
			$elBtn.remove();
		} else if (!isNullOrBlank(this.calid) && !$elBtn.length && localcal !== 'none') {
			//add the button
			var $el = $('<a id="syncbutton" href="#" class="ui-btn ui-icon-recycle ui-btn-icon-left ui-btn-b" title="Sync Calendar">Sync Calendar</a>');
			$(".localnav .ui-controlgroup-controls").append($el);
			//set the popup text
			$(".synccaltype").text(localcal);
			//set up the event
			$("#syncbutton").click(function() {
				calconfigViewController.getSyncCounts(g_loggedInUserID, calendarconfigview.calid);
			});
		}
	},	
	
	//getParameters - uses gParms
	setParms: function() {
		//Now, create Access Restrictions Display
		gARdisplay = new AccessRestrictionsDisplay();
		gARdisplay.draw();
		//set defaults, if new calendar
		if (isNullOrBlank(this.calid)) {
			var ar = new AccessRestrictions();
			AccessRestrictions.loadAccessRestrData(ar);		
		}
		$(".colName.colhdr").text("Name (" + gParms["NETID"] + ")");
		$(".textmgrmem").attr("placeholder", gParms["NETID"]);
	},
	
	//getParameters - waitlist specific handling
	setWaitlistParmVal: function(isWLOptionEnabled) {
	    if (!isWLOptionEnabled) {
            $("#divWaitlist").hide();
            $("#hrWaitlist").hide();
        } else {
            var $selWait = $('#selWaitlist');
            $selWait.val(false);
            $selWait.flipswitch("refresh");
            this.setWaitlistDisplay(true);

            //set up confirm popup for turning off waitlist
            this.$confirmWaitlistOff = $('<div>', {
                id: 'popupConfirmWaitlist',
                'data-role': 'popup',
                'data-dismissible': false,
                class: 'ui-page-theme-a'
            });
            var $inner = $('<div>', {class: 'popupinner'});
            $inner.append(
                $('<div>', {
                    id: 'divDisableWaitlist',
                    html: 'Are you sure you want to disable the waitlist? <br><b>WARNING</b>: This will delete all entries.'
                }),
                $('<div>', {class: 'buttonarea'}).append(
                    this.$btnNo = $('<input>', {type: 'button', value: 'No', 'data-inline': true, 'data-mini': true}),
                    this.$btnYes = $('<input>', {type: 'button', value: 'Yes', 'data-inline': true, 'data-mini': true})
                )
            );
            this.$confirmWaitlistOff.append($inner);
            this.$btnNo.on('click', function () {
                $selWait.off("change");
                $selWait.val('true');
                $selWait.flipswitch("refresh");
                $selWait.on("change", _.partial(CalendarConfigView.checkWaitlistOnOff, calendarconfigview.$confirmWaitlistOff));
                calendarconfigview.$confirmWaitlistOff.popup('close');
            });
            this.$btnYes.on('click', function () {
                CalendarConfigView.doWaitlistOnOff(false);
                calendarconfigview.$confirmWaitlistOff.popup('close');
            });
            this.$confirmWaitlistOff.popup();
            this.$confirmWaitlistOff.on('popupafteropen', function () {
                var $input = $('#popupConfirmWaitlist input');
                $input.button();
                $input.button('refresh');
            });

            //set the change event for the toggle switch
            $selWait.on("change", _.partial(CalendarConfigView.checkWaitlistOnOff, this.$confirmWaitlistOff));
        }
	},
	
	
	/************************************/
	/* Publish Event Handlers	        */
	/************************************/
	doCancelIcal: function() {
	    var $selPub = $('#selSetPublish');
        $selPub.val("false");
        $selPub.flipswitch("refresh");
	},
	doConfirmIcal: function() {
	    waseCalController.setIcalPass(calendarconfigview.calid);
	},
	setPublishFeeds: function(icalpass) {
		this.icalpass = icalpass;
		if (icalpass === 'notset') {
			this.doCancelIcal();
		} else {
			this.drawPublishURLs();
		}
	},
	
	
	/************************************/
	/* Manager/Member			        */
	/************************************/
	//draw
	drawMgrMemButtons: function(inType,inUserID,inStatus) {
		var btnstr = '';
		var shorttype = inType.substring(0,inType.length-1);
		var usrid = inUserID;
		if (inStatus.toLowerCase() !== "pending" && !isNullOrBlank(this.calid)) {
			btnstr += '<a href="#" class="btnsave ui-btn ui-btn-inline ui-mini" onclick="CalendarConfigView.editManagerMember(\'' + inType + '\',this);" data-ajax="false" title="Update ' + shorttype + '">Update</a>';
			btnstr += '<a href="#" onclick="CalendarConfigView.removeManagerMember(\'' + inType + '\',this);" data-ajax="false" class="btndelete ui-btn ui-btn-inline ui-mini" title="Remove ' + shorttype + '">Remove ' + shorttype + '</a>';
		}
		return btnstr;
	},
	drawManagerMember: function(inType,inMgrMem) {
		var usrid = "";
		var nm = "";
		if (inMgrMem.get('user') != null) {
			usrid = inMgrMem.get('user').get('userid');
			nm = inMgrMem.get('user').getDisplayName();
		}
		var htmlID = usrid.replace('@','xxx');
		
		var str = '<li id="li' + inType + '_' + htmlID + '"><div class="colName">';
		str += nm;
		if (nm !== usrid)
		    str += '&nbsp;(' + usrid + ')';
		str += '</div>';

		str += '<div class="colStatus">';
		str += inMgrMem.get('status');
		if (inMgrMem.get('status').toLowerCase() === "pending") {
			str += '&nbsp;<span class="mgrmemaction"><a href="#" onclick="CalendarConfigView.editManagerMember(\'' + inType + '\',this);" data-ajax="false" title="Allow User to be ' + inType + '">Allow</a></span>&nbsp;/';
			str += '<span class="mgrmemaction"><a href="#" onclick="CalendarConfigView.removeManagerMember(\'' + inType + '\',this);" data-ajax="false" title="Don\'t Allow User to be ' + inType + '">Deny</a></span>';
		}
		str += '</div>';

		var chkNot = inMgrMem.get('notify') ? ' checked="checked"' : '';
		str += '<div class="colNotify checkboxcol">';
		str += '<input type="checkbox" name="chkNot' + inType + '_' + htmlID + '" id="chkNot' + inType + '_' + htmlID + '"' + chkNot + ' />';
		str += '</div>';

		var chkRem = inMgrMem.get('remind') ? ' checked="checked"' : '';
		str += '<div class="colRemind checkboxcol">';
		str += '<input type="checkbox" name="chkRem' + inType + '_' + htmlID + '" id="chkRem' + inType + '_' + htmlID + '"' + chkRem + ' />';
		str += '</div>';

		str += '<div class="colBtns">';
		str += this.drawMgrMemButtons(inType,inMgrMem.get('user').get('userid'),inMgrMem.get('status'));
		str += '</div>';

		str += '<div class="heightfix"></div></li>';

		//insert item second to last
        var $ul = $('#ul' + inType);
        $ul.children(':last').before(str);
        $ul.listview('refresh');

		//add to global array
		var arr = inType === "Managers" ? this.managerList : this.memberList;
		arr.push(inMgrMem);	
		
		//enable/disable save button based on changed user
		$(".btnsave").addClass("ui-disabled");
		var vw = this;
		$("#divManagersAndMembers li:not(.addRow) :checkbox").click(function() {
			var $li = $(this).closest("li");
			vw.addToUnsavedMgrMem($li.attr("id"));
			$li.find(".btnsave").removeClass("ui-disabled");
		});
		$("#divManagersAndMembers .textmgrmem").on("blur",function() {
			var val = $(this).val();
	        if (val != null  && val.length > 0) {
	        	vw.addToUnsavedMgrMem($(this).closest("li").attr("id"));
	        }
		});

	},
	//Generic function for building a manager/member section. inType is Managers, Members
	drawMgrMemSection: function(inType) {
		//create the section
		var str = "";
		str += '<div id="div' + inType + '" class="section clear"><h2>' + inType + ':</h2>';
		str += '<div id="divMessage'+inType+'"></div>';
		str += '<div class="instructions">**IMPORTANT: To save changes, click on the individual row "update" or "add" button.**</div>';
		str += '<ul id="ul' + inType + '" data-role="listview"></ul></div>';
		$("#divManagersAndMembers").append(str);
		var $ul = $("#ul" + inType);
        $ul.listview();

		//put a header row in (for large screens)
		str = '<li class="liHeader" data-role="list-divider">';
		str += '<div class="colName colhdr">Name</div><div class="colStatus colhdr">Status</div>';
		str += '<div class="colNotify colhdr">Send Notifications?</div><div class="colRemind colhdr">Send Reminders?</div><div class="colBtns colhdr"></div>';
		str += '<div class="heightfix"></div></li>';
        $ul.append(str);

		//Add the "Add" Row
		var singular = inType.substring(0,inType.length-1);
		var am = '';
		am += '<li id="liAddRow'+ inType+ '" class="addRow">';
		am += '<div class="colName">';
		am += 'Add ' + singular + ':<br>';
		am += '<input type="text" name="txtNew' + singular + '" id="txtNew' + singular + '" placeholder="" title="Add ' + singular + '" class="textmgrmem" />';
		am += '</div>';
		am += '<div class="colStatus">&nbsp;</div>';
		am += '<div class="colNotify checkboxcol">';
		am += '<input type="checkbox" name="chkNot' + inType + '" id="chkNot' + inType + '" checked="checked" />';
		am += '</div>';
		am += '<div class="colRemind checkboxcol">';
		am += '<input type="checkbox" name="chkRem' + inType + '" id="chkRem' + inType + '" checked="checked" />';
		am += '</div>';
		am += '<div class="colBtns mgrmemadd mgrmemaction">';
		am += '<a href="#" data-ajax="false" class="btnadd lnkAdd' + inType + ' ui-btn ui-btn-inline ui-mini" title="Add ' + singular + '">Add</a>';
		am += '</div>';
		am += '<div class="heightfix"></div></li>';
        $ul.append(am);

        $ul.listview('refresh');
		
		this["message"+inType] = new Message({"messageSelector": "#divMessage"+inType, "isTopMsg": false});
	},
	drawManagerSection: function() {
		this.drawMgrMemSection("Managers");
		$(".lnkAddManagers").bind("click",function() {
			var id = $("#txtNewManager").val();
			if (id !== "") {
				if (!isNullOrBlank(calendarconfigview.calid)) { //calendar already exists, add managers immediately
					var mgr = buildMgrMem(id,$("#chkNotManagers").is(":checked"),$("#chkRemManagers").is(":checked"));
					waseCalController.checkManager(mgr);
				} else
				    waseCalController.validateManager(id);
			}
			return false;
		});
	},
	drawMemberSection: function() {
		this.drawMgrMemSection("Members");
		$(".lnkAddMembers").bind("click",function() {
			var id = $("#txtNewMember").val();
			if (id !== "") {
				if (calendarconfigview.calid !== null && calendarconfigview.calid !== "") { //calendar already exists, add memberse immediately
					var mem = buildMgrMem(id,$("#chkNotMembers").is(":checked"),$("#chkRemMembers").is(":checked"));
					waseCalController.checkMember(mem);
				} else
				    waseCalController.validateMember(id);
			}
			return false;
		});
	},
	
	//remove callbacks from server
	showRemovedMgrMem: function(arr, liID, userid) {
		//update display
		var usr = gCurMgrMem.get('user').get('userid').replace('@','xxx');
		// need to escape the period when the id has a period (globally)
		usr = usr.replace(/\./g,'\\.');
		$("#"+liID+"_" + usr).remove();

		//remove from the array
		return jQuery.grep(arr, function(mgrmem) {
	  		return mgrmem.get('user').get('userid') !== userid;
		});
	},
	showRemovedManager: function(userid) {
		this.managerList = this.showRemovedMgrMem(this.managerList, "liManagers", userid);
	},
	showRemovedMember: function(userid) {
		this.memberList = this.showRemovedMgrMem(this.memberList, "liMembers", userid);
	},
	
	addToUnsavedMgrMem: function(liid) {
		if (gUnsavedMgrMems.indexOf(liid) === -1)
			gUnsavedMgrMems.push(liid);
	},
	removeFromUnsavedMgrMem: function(liid) {
		var ind = gUnsavedMgrMems.indexOf(liid);
		if (ind !== -1)
			gUnsavedMgrMems.splice(ind,1);
	},
		
	//edit callbacks from server
	showChangedMgrMem: function(mgrmem, type) {
		var li = "li"+type+"_"+mgrmem.get('user').get('userid').replace('@','xxx');
        // need to escape the period when the id has a period (globally)
        li = li.replace(/\./g,'\\.');
		var liid = "#"+li;
		$(liid).find(".colStatus").text("active");
		if (isNullOrBlank($(liid).find(".colBtns").html()))
			$(liid).find(".colBtns").append(this.drawMgrMemButtons(type,mgrmem.get('user').get('userid'),"active"));
		$(liid).find(".btnsave").addClass("ui-disabled");
		this.removeFromUnsavedMgrMem(li);
	    //set 'cancel' to 'done'
        var $btnCancel = $(".btnCancelSubmit");
        $btnCancel.val('Done');
        $btnCancel.button('refresh');
	    //put inf message
	    this["message"+type].displayConfirm("User " + mgrmem.get("user").get('userid') + " updated");
	},
	showChangedManager: function(mgr) {
		this.showChangedMgrMem(mgr, "Managers");
	},
	showChangedMember: function(mem) {
		this.showChangedMgrMem(mem, "Members");
	},
	
	//add callbacks from server
	showAddedMgrMem: function(isvalid, mgrmem, typeSingle) {
		if (isvalid) {
			var addmgrmem = null;
			if (typeof mgrmem.user === "undefined")
				addmgrmem = new ManagerMember({
				    user: mgrmem,
				    calendarid: this.calid,
				    status: 'active',
				    notify: $("#chkNotManagers").is(":checked"),
				    remind: $("#chkRemManagers").is(":checked")});
			else addmgrmem = mgrmem;
			this.drawManagerMember(typeSingle+"s",addmgrmem);
			$("#txtNew"+typeSingle).val("");
			this.removeFromUnsavedMgrMem("liAddRow"+typeSingle+"s");
		}
	},
	showAddedManager: function(isvalid,mgr) {
		this.showAddedMgrMem(isvalid,mgr,"Manager");
	},
	showAddedMember: function(isvalid,mem) {
		this.showAddedMgrMem(isvalid,mem,"Member");
	},
	

	/************************************/
	/* Save Calendar			        */
	/************************************/
	//Save calendar data from form to calendar object
	saveCalData: function() {
		var title = $("#txtCalTitle").val();
		var desc = $("#txtDescription").val();
		var loc = $("#txtLocation").val();
		var email_value = $("#txtEmail").val();
		email_value = email_value.replace(/;/g,','); //global replace semicolons with commas
	    var owner = new User({
	        'userid': this.calOwner.get('userid'),
	        'name': $("#txtName").val(),
	        'phone': $("#txtPhone").val(),
	        'email': email_value,
	        'office': this.savedCalendar ? this.savedCalendar['owner']['office'] : ''
	    });
			
		var nr = new NotifyAndRemind({
		    notify: $("#chkNotify").is(":checked"),
	        remind: $("#chkRemind").is(":checked"),
	        apptmsg: $("#txtApptText").val()
		});
		
		var vAccess = $("#cgRestrict_view :radio:checked").val();
		var vUsers = [];
		var vCourses = [];
		var vGroups = [];
		var vStatuses = [];
		if (vAccess === "restricted") {
			vUsers = gAccessRestrictions.get('viewulist');
			vCourses = gAccessRestrictions.get('viewclist');
			vGroups = gAccessRestrictions.get('viewglist');
			vStatuses = gAccessRestrictions.get('viewslist');
		}
		var mAccess = $("#cgRestrict_make :radio:checked").val();
		var mUsers = [];
		var mCourses = [];
		var mGroups = [];
		var mStatuses = [];
		if (mAccess === "restricted") {
			mUsers = gAccessRestrictions.get('makeulist');
			mCourses = gAccessRestrictions.get('makeclist');
			mGroups = gAccessRestrictions.get('makeglist');
			mStatuses = gAccessRestrictions.get('makeslist');
		}
		var showApptInfo = $("#cgShowApptInfo :radio:checked").val();
	    var ar = new AccessRestrictions({
	        viewaccess: vAccess,
	        viewulist: vUsers,
	        viewclist: vCourses,
	        viewglist: vGroups,
	        viewslist: vStatuses,
	        makeaccess: mAccess,
	        makeulist: mUsers,
	        makeclist: mCourses,
	        makeglist: mGroups,
	        makeslist: mStatuses,
	        showappinfo: isTrue(showApptInfo)
	    });

		var overlapok = $("#chkAllowBlockOverlap").is(":checked");
		var purreq = $("#chkRequirePurpose").is(":checked");
		var waitlist = isTrue($("#selWaitlist").val());
		
		//labels
		var labels = new WASEObject({
			NAMETHING: $("#txtNamething").val(),
			NAMETHINGS: $("#txtNamethings").val(),
			APPTHING: $("#txtAppthing").val(),
			APPTHINGS: $("#txtAppthings").val()
		});

		var vals = {
		    title: title,
		    description: desc,
		    location: loc,
		    owner: owner,
		    overlapok: overlapok,
		    purreq: purreq,
		    available: true, //always available
		    waitlist: waitlist,
		    notifyandremind: nr,
		    accessrestrictions: ar,
		    labels: labels
		};
		//note: managers/members are set not through addcalendar/editcalendar, but through individual calls
		
		var calObj = null;
		var doSave = true;
		var askPropagate = 'no';

		if (isNullOrBlank(this.calid)) {  //new calendar
			calObj = new WASECalendar(vals);
			calObj.set('calendarid','');
		} else {
	        var propagateAttrs = ['title','description','location','notifyandremind','purreq','accessrestrictions','labels'];
	        var propagateOwnerAttrs = ['owner'];
	        var otherAttrs = ['overlapok','available','waitlist'];
	        
			//check against original to see what should be updated
	        var changedVals = {};
	        
			var propagateChangedVals = {};
			_.each(propagateAttrs, function(a) {
				propagateChangedVals = saveEqual(this.savedCalendar, vals, a, propagateChangedVals);
			},this);
			
			var propagateChangedOwnerVals = {};
			_.each(propagateOwnerAttrs, function(a) {
				propagateChangedOwnerVals = saveEqual(this.savedCalendar, vals, a, propagateChangedOwnerVals);
			},this);
			
			var otherChangedVals = {};
			_.each(otherAttrs, function(a) {
				otherChangedVals = saveEqual(this.savedCalendar, vals, a, otherChangedVals);
			},this);
			
			calObj = new WASECalendar();
			calObj.set('calendarid',this.calid);

			//if nothing at all was changed, don't save
			if (_.isEmpty(propagateChangedVals) && _.isEmpty(propagateChangedOwnerVals) && _.isEmpty(otherChangedVals)) {
				doSave = false; 
			} else { //only check propagate if saving
				//Do we ask if propagate?
				var anyOwnerOnly = !_.isEmpty(propagateChangedOwnerVals);
				var anyOtherPropagate = !_.isEmpty(propagateChangedVals);
				if (anyOtherPropagate || anyOwnerOnly) {
					if (parseInt(this.numFutureBlocks,10) > 0 && parseInt(this.numFutureMemberBlocks,10) === 0) { //future blocks, but all calendar owner
						askPropagate = "owner";
					} else if (parseInt(this.numFutureMemberBlocks,10) > 0) { //future blocks, including member blocks
						askPropagate = anyOwnerOnly ? "warnmember" : "member";
					}
				}
				_.extend(calObj, propagateChangedVals, propagateChangedOwnerVals, otherChangedVals);
			}
		}
		return {"calObj": calObj, "askPropagate": askPropagate, "doSave": doSave};

	},
	
	/************************************/
	/* Calendar Sync			        */
	/************************************/
	//sync button event handler response (getSyncCounts - opens sync popup)
	openSyncCal: function(fromTodayBlocks,fromTodayApps,allBlocks,allApps) {
		var appText = this.labels['APPTHINGS'] || "apps";
		$("#divFromTodayCounts").text("(Includes " + fromTodayBlocks + " blocks and " + fromTodayApps + " " + appText + ")");
		$("#divAllCounts").text("(Includes " + allBlocks + " blocks and " + allApps + " " + appText + ")");
		$("#popupSyncCalendar").popup("open");
	},
	
	//sync button event handler response (syncCalendar - in sync popup)
	showCalSync: function(calid,ical,numblocks,numapps) {
		var msg = 'Sync Complete';
		if (!isNullOrBlank(numblocks) && parseInt(numblocks,10) > 0)
			msg += ': ' + numblocks + ' blocks';
		if (!isNullOrBlank(numapps) && parseInt(numapps,10) > 0)
			msg += ', ' + numapps + ' ' + this.savedCalendar.get('labels')['APPTHINGS'];
		msg += '.';
		if (!isNullOrBlank(ical)) {
			msg += ' (Open the generated iCal file to update your local calendar).'
		}
		
		if (!isNullOrBlank(ical)) {
			var blob = new Blob([ical], {type : 'text/calendar;method=PUBLISH'});
			saveAs(blob, "calendar.ics");
		}
		gMessage.displayConfirm(msg);
	}

	
});


/************************************/
/* Event Handlers - Waitlist        */
/************************************/
CalendarConfigView.doWaitlistOnOff = function(isOn) {
    var calid = calendarconfigview.calid;
    calendarconfigview.setWaitlistDisplay(isOn);
    if (!isNullOrBlank(calid)) waitlistController.enableWaitlist(calid,isOn);
};
CalendarConfigView.checkWaitlistOnOff = function($popup) {
    var isOn = $(this).val() === "true";
    var calid = calendarconfigview.calid;
    if (isOn || isNullOrBlank(calid)) {
        CalendarConfigView.doWaitlistOnOff(isOn);
        $("#newCalWaitlistNote").toggle(isOn && isNullOrBlank(calid));
    } else {
        $popup.popup('open');
    }
};


/************************************/
/* Event Handlers - Manager/Member  */
/************************************/
CalendarConfigView.removeManagerMember = function(inType,inRemoveLinkElement) {
	//remove from the form/page (do it here because we have the element)
	var li = $(inRemoveLinkElement).closest('li');
	var id = li.attr("id");
	var removeItemUserID = id.substr(id.indexOf("_")+1);
	// "removeItemUserID.indexOf('xxx') === 0" check is not correct, it should be greater -1
    if (removeItemUserID.indexOf('xxx') > -1 ) {
    	removeItemUserID = removeItemUserID.replace('xxx','@');
    }
	var lst = inType === "Managers" ? calendarconfigview.managerList : calendarconfigview.memberList;
    var arrCurMgrMem = $.grep(lst, function(m) {
		return m.user['userid'] === removeItemUserID;
	});
    gCurMgrMem = arrCurMgrMem[0];

	var obj = inType === "Managers" ? "manager" : "member";
	$("#ccObject").val(obj);
	$("#popupCancelConfirm").popup("open");	
};
CalendarConfigView.editManagerMember = function(inType,inEditLinkElement) {
	//get the userid
	var li = $(inEditLinkElement).closest('li');
	var id = li.attr("id");
	var editItemDOMUserID = id.substr(id.indexOf("_")+1);
    var editItemUserID = editItemDOMUserID;
    if (editItemUserID.indexOf('xxx') > -1) {
        editItemUserID = editItemUserID.replace('xxx','@');
        // need to escape period globally in the DOM ID
		editItemDOMUserID.replace(/\./g,'\\.');
    }

	var lst = inType === "Managers" ? calendarconfigview.managerList : calendarconfigview.memberList;
    var arrMgrMem = $.grep(lst, function(m) {
		return m.user['userid'] === editItemUserID;
	});
    var mgrmem = arrMgrMem[0];
	mgrmem.set('notify',$("#chkNot"+inType+"_" + editItemDOMUserID).is(":checked"));
	mgrmem.set('remind',$("#chkRem"+inType+"_" + editItemDOMUserID).is(":checked"));

	if (inType === "Managers") {
		waseCalController.editManager(mgrmem);
	} else { //Members
		waseCalController.editMember(mgrmem);
	}
};



function saveEqual(oldObj, newObj, attr, tmpObj) {
	if (typeof(oldObj[attr]) === 'object') {
		var tmp = {};
		_.each(_.keys(oldObj[attr]), function(k) {
			var checkVal = newObj[attr][k];
			if (Array.isArray(checkVal)) {
				if (_.difference(checkVal, oldObj[attr][k]).length || _.difference(oldObj[attr][k], checkVal).length) {
					tmp[k] = _.clone(checkVal);
				}
			} else if (checkVal !== oldObj[attr][k]) {
				tmp[k] = checkVal;
			}
		});
		if (_.keys(tmp).length) {
			tmpObj[attr] = new WASEObject(tmp);
		}
	} else {
		if (newObj[attr] !== oldObj[attr])
			tmpObj[attr] = newObj[attr];
	}
	return tmpObj;
}

var calendarconfigview = new CalendarConfigView();