var gOwnedCals = new Array();
var gManagedCals = new Array();
var gManagedPendingCals = new Array();
var gMemberCals = new Array();
var gMemberPendingCals = new Array();
var gApplyCals = new Array();
var gApplyWhat = null;

function CalendarsView() {
	this.pagetitle = "Calendars";
	this.anyToDos = false;
}

//set and show the page title and set the global apptid to the input apptid (sent from calling page).
CalendarsView.prototype.setPageVars = function() {
	$("#pagetitle").text(this.pagetitle);
	
	//set up view button click
	$("#btnView").click(function() {
		var calids = new Array();
		$("#frmCalendars .calchk").each(function() {
			 if($(this).is(":checked")) {
				 calids.push($(this).val());
			 } 
		});
		$("#btnView").attr("href","viewcalendar.php?calid=" + calids.toString());	
	});
	$("#btnSettings").click(function() {
		var calid = 0;
		$("#frmCalendars .calchk").each(function() {
			 if($(this).is(":checked")) {
				 calid = $(this).val();
			 } 
		});
		$("#btnSettings").attr("href","calendarconfig.php?calid=" + calid);	
	});
	
	//view button styling
	$("#btnView").addClass("ui-disabled");
	$("#btnSettings").addClass("ui-disabled");
	
	//set up checkbox click to toggle view enable/disable
	$("#frmCalendars").on("click",".calchk",function(e) {
		if ($('.calchk:checked').length) {
			$("#btnView").removeClass("ui-disabled");
			if ($('.calchk:checked').length === 1) $("#btnSettings").removeClass("ui-disabled");
			else $("#btnSettings").addClass("ui-disabled");
		} else {
			$("#btnView").addClass("ui-disabled");
			$("#btnSettings").addClass("ui-disabled");
		}
	});
	
};

CalendarsView.prototype.confirmUserStatus = function(isowner,ismanager,ismember,userid,hasappts,sysID) {
    if (!(isowner || ismanager || ismember)) {
    	var txt = 'If you are using ' + sysID + ' to make yourself (or some item you control) available for appointments, '
    		+ 'your first step is to create a ' + sysID + ' Calendar. Click on "New Calendar" to do that. '
    		+ 'If you are here to help make someone else available for appointments, click on the "Apply to Manage" button. '
    		+ 'If you are here to join an existing calendar, click on the "Apply for Membership" button.';
        gMessage.displayHelp(txt);
    }
};

CalendarsView.prototype.setParmNetID = function() {
	$(".searchtext").attr("placeholder",gParms["NETID"].toLowerCase()+"(s), comma-separated");
	$(".searchtext").textinput();
	$(".parmnetid").text(gParms["NETID"]);
};

CalendarsView.grantPending = function(isgrant, granttype, calid, userid) {
	var stat = isgrant ? 'active' : 'pending';
	var m = new ManagerMember({
        user: new User({userid: userid}),
        calendarid: calid,
        status: stat,
        notify: true,
        remind: true
    });
	
	if (isgrant) {
		$('.loader').show();
		if (granttype === 'manager') 
			calendarsViewController.editManager(m);
		else 
			calendarsViewController.editMember(m);		
	} else {
		gCurMgrMem = m;
		$("#ccObject").val(granttype);
		$("#popupCancelConfirm").popup("open");	
	}
};

CalendarsView.prototype.loadCals = function(inType,calarray) {
	var isPending = inType === "ManagedPending" || inType === "MemberPending";
	var canGrant = inType !== "ManagedPending" && inType !== "Members" && inType !== "MemberPending";
	
	var str = "";
	
	//put a header row in (for large screens)
	str += '<li class="liHeader" data-role="list-divider">';
	str += '<div class="col1 colhdr">Calendar Title</div><div class="col2 colhdr">Manager(s)</div><div class="col3 colhdr">Member(s)</div><div class="col4 colhdr">Waitlist Entries</div>';
	str += '</li>';
	$('#ul' + inType + 'Cals').append(str);

	var listType = inType;
	if (inType === 'ManagedPending') listType = "Managed";
	if (inType === 'MemberPending') listType = "Member";			

	//There may be no calendars, if so, put a message
	if (calarray.length === 0) {
		$('#ul' + inType + 'Cals').append("<li><div class='nonefoundmsg'>No " + inType + " Calendars</div></li>");
	}
	for (var i=0; i < calarray.length; i++) {
		var cal = calarray[i];
		if (cal !== null) {
			var calid = cal.get("calendarid");
			
			var mgrsactive = cal.getMgrMemWithStatus('managers','active');
			var mgrspend = cal.getMgrMemWithStatus('managers','pending');
			var memsactive = cal.getMgrMemWithStatus('members','active');
			var memspend = cal.getMgrMemWithStatus('members','pending');

			str = '<li';
			if (!isPending && (mgrspend.length > 0 || memspend.length > 0)) {
				str += ' class="lipendingcal"';
				this.anyToDos = true;
			}
			str += '>';
			//Calendar title and owner
			str += '<div class="col1">';
			if (isPending)
				str += '<div class="checkboxlefttop">-</div>';
			else 
				str += '<div class="checkboxlefttop"><input type="checkbox" class="calchk" name="chk' + inType + '" id="chk' + inType + calid + '" value="' + calid + '" /></div>';
			str += '<div class="checkboxlistitem">';
			if (!isPending)
				str += '<a href="viewcalendar.php?calid=' + calid + '" data-ajax="false" title="View Calendar">';
			str += calarray[i].get('title');
			if (isPending) {
				str += '<div class="statustag pending">PENDING</div>'
			}
			if (inType !== "Owned")
				str += '<div class="calowner">' + calarray[i].get('owner').get('name') + '</div>';
			if (!isPending)
				str += '</a>';
			str += '</div></div>';
			
			//managers
			var noShowClass = 'noshow';
			var noShowMgrs = (mgrsactive.length || mgrspend.length) ? '' : noShowClass;
			str += '<div class="col2 ' + noShowMgrs + '"><span class="smalltitle">Managers:</span>';
			var vals = _.map(mgrsactive, function(mgr) {
				return mgr.get('user').getDisplayName();
			});
			str += vals.join(", ");
			
			//pending managers
			vals = _.map(mgrspend, function(mgr) {
				var uid = mgr.get('user').get('userid');
				if (canGrant) {
					return '<div class="statustag pending grant">PENDING:&nbsp;&nbsp;' + mgr.get('user').getDisplayName()
					+ '&nbsp;<span class="mgrmemaction"><a href="#" onclick="CalendarsView.grantPending(true,\'manager\',' + calid + ',\'' + uid + '\');" data-ajax="false" title="Allow User to be ' + inType + '">Allow</a></span>&nbsp;/'
					+ '<span class="mgrmemaction"><a href="#" onclick="CalendarsView.grantPending(false,\'manager\',' + calid + ',\'' + uid + '\');" data-ajax="false" title="Don\'t Allow User to be ' + inType + '">Deny</a></span>'
					+ '</div>';
				}
			});
			str += vals.join(", ");
			str += '&nbsp;</div>';
			
			//members
			var noShowMems = (memsactive.length || memspend.length) ? '' : noShowClass;
			str += '<div class="col3 ' + noShowMems + '"><span class="smalltitle">Members:</span>';
			vals = _.map(memsactive, function(mem) {
				return mem.get('user').getDisplayName();
			});
			str += vals.join(", ");
			
			//pending members
			vals = _.map(memspend, function(mem) {
				var uid = mem.get('user').get('userid');
				if (canGrant) {
					return '<div class="statustag pending grant">PENDING:&nbsp;&nbsp;' + mem.get('user').getDisplayName()
					+ '&nbsp;<span class="mgrmemaction"><a href="#" onclick="CalendarsView.grantPending(true,\'member\',' + calid + ',\'' + uid + '\');" data-ajax="false" title="Allow User to be ' + inType + '">Allow</a></span>&nbsp;/'
					+ '<span class="mgrmemaction"><a href="#" onclick="CalendarsView.grantPending(false,\'member\',' + calid + ',\'' + uid + '\');" data-ajax="false" title="Don\'t Allow User to be ' + inType + '">Deny</a></span>'
					+ '</div>';
				}
			});
			str += vals.join(", ");
			str += '&nbsp;</div>';

			//waitlist info
			str += '<div class="col4"><span class="smalltitle">Waitlist Entries:</span>';
			if (cal.get("waitlist")) {
				str += cal.get("waitcount")
			} else {
				str += "<span class='italicsoft'>OFF</span>";
			}
			str += '&nbsp;</div>';

			str += '<div class="heightfix"></div></li>';
			$('#ul' + listType + 'Cals').append(str);
		}
	}
	$("#ul" + listType + "Cals").listview('refresh');
};

//Generic function for building a calendar section. inType is Owned, Managed, ManagedPending, or Member
CalendarsView.prototype.loadCalSection = function(inType,inParent) {
	var sectiontitle = "";
	var applybutton = "";
	var calarray = new Array();
	switch(inType) {
		case "Owned": 
			calarray = gOwnedCals;
			var owner = g_loggedInUserID;
			sectiontitle = 'Owned';
			break;
		case "Managed": 
			calarray = gManagedCals;
			sectiontitle = 'Managed';
			applybutton = "Apply to Manage";
			break;
		case "ManagedPending": 
			calarray = gManagedPendingCals;
			sectiontitle = 'Managed (Pending)';
			break;
		case "Member": 
			calarray = gMemberCals;
			sectiontitle = 'Member Of';
			applybutton = "Apply for Membership";
			break;
		case "MemberPending": 
			calarray = gMemberPendingCals;
			sectiontitle = 'Member Of (Pending)';
			break;
	}
	
	var str = "";
	var isNewSection = false;
	//check to see if section already exists
	if ($("#div" + inType).length > 0) {
		$('#ul' + inType + 'Cals').empty();
	} else {
		//create the section
		str = '<div id="div' + inType + '" class="section clear">';
        str += '<h2 class="sectionheader">' + sectiontitle + '</h2>';
		if (!isNullOrBlank(applybutton)) {
			str += '<div data-type="horizontal" class="sectionbutton">';
    		str += '<a href="#" class="applybutton ui-btn ui-btn-b" title="' + applybutton + '">' + applybutton + '</a>';
    		str += '</div>';
		}
		isNewSection = true;
	}
	str += '<ul id="ul' + inType + 'Cals" data-role="listview" data-theme="b" data-divider-theme="b" class="clearMe"></ul>';
	if (isNewSection) str += '</div>';
	
	if (isNewSection && inType === "ManagedPending")
		$(str).insertAfter("#divManaged");
	else if (isNewSection && inType === "MemberPending")
		$(str).insertAfter("#divMember");
	else
		$(inParent).append(str);
		
	$("#ul" + inType + "Cals").listview();

	if ($(".applybutton").length)
		$(".applybutton").click(function(e) {
			gApplyType = inType === "Member" ? "member" : "manage";
			$("#popupApply").popup("open");
		});
	
	//load calendars
	this.loadCals(inType,calarray);
};

CalendarsView.prototype.loadOwnedCalendars = function() {
	this.loadCalSection("Owned","#divCalLists");
};

CalendarsView.prototype.loadManagedCalendars = function() {
	this.loadCalSection("Managed","#divCalLists");
};

CalendarsView.prototype.loadManagedPendingCalendars = function() {
	this.loadCals("ManagedPending",gManagedPendingCals);
};

CalendarsView.prototype.loadMemberCalendars = function() {
	this.loadCalSection("Member","#divCalLists");
	showLoadingMsg(false);
};

CalendarsView.prototype.loadMemberPendingCalendars = function() {
	this.loadCals("MemberPending",gMemberPendingCals);
};

CalendarsView.prototype.resetApplySection = function() {
	$("#divApply" + gApplyWhat + "ResultArea").empty();
};

var calendarsview = new CalendarsView();
