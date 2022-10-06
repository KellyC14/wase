var gApptCals = new Array();
var gAppts = new Array();
var gIsOwnerOrManager = false;

function MyApptsView() {
	this.pagetitle = "My " + capitalize(g_ApptsText);
	this.filters = {};
	this.numfilters = 0;
}

//set and show the page title and set the global apptid to the input apptid (sent from calling page).
MyApptsView.prototype.setPageVars = function() {
	$("#pagetitle").text(this.pagetitle);
	
	//Set Date and Time filters
	this.initDateTimes();
	
	//Initialize the "clear" buttons
    $('.clrbutton').click(function() {
		var txtel = $(this).closest("div").find(":text");
		if (txtel.attr("id") === "txtStartDate") {
			$("#txtStartTime").val('');
			$("#txtStartTimeClear").hide();
		} else if (txtel.attr("id") === "txtEndDate") {
			$("#txtEndTime").val('');
			$("#txtEndTimeClear").hide();
		}
        txtel.val('');
		$(this).hide();
			
		getfilteredappts();
        return false;
    });
	$('.clrbutton').hide();
	$("#txtStartDateClear").show();
	$('.setfilter').bind("change",function () {
		if (!isNullOrBlank($(this).val()))
			$(this).closest("div.filtergroup").find(".clrbutton").show();
		else
			$(this).closest("div.filtergroup").find(".clrbutton").hide();
		getfilteredappts();
	});

	//Filter: Start Date - need to set after other ".setfilter"s initialized
	$('#txtStartDate').bind("change",function () {
		if (!isNullOrBlank($(this).val())) {
			$('#txtStartDateClear').show();
			var sd = moment($(this).val(),fmtDate).toDate();
			//reset end date mindate, and input text if it is before the start date
			if ($('#txtEndDate').scroller) {
				$('#txtEndDate').scroller("option","minDate",sd);
			}
		} else {
			$('#txtStartDateClear').hide();
		}
		getfilteredappts();		
	});
};

MyApptsView.prototype.createWaitlist = function() {
    this.waitlistView = new WaitlistEntryView();
    $("#divWaitlist .ui-collapsible-content").append(this.waitlistView.$waitList);
};
MyApptsView.prototype.setWaitlistDisplay = function(isWaitlistOn) {
    if (isWaitlistOn) {
        if (!myapptsview.waitlistView)
            myapptsview.createWaitlist();
        waitlistController.getWaitlistForUser();
        $("#divWaitlist").show();
    } else {
        $("#divWaitlist").hide();
    }
};
MyApptsView.prototype.showWaitlist = function(entries) {
    $("#pagetitle").html('<a href="#" id="btnAppointments" class="" data-ajax="false">' + this.pagetitle + '</a><span class="waitlisttitle"> and ' + '<a href="#waitlist" id="btnWaitlist" class="" data-ajax="false">My Wait List Entries</a></span>');
    $("#btnWaitlist").click(function(e) {
        $("#divMyApptsContent").hide();
        $("#divApptFilter").hide();
        $("#divFilters").hide();
    });
    $("#btnAppointments").click(function(e) {
        $("#divMyApptsContent").show();
        $("#divApptFilter").show();
        $("#divFilters").show();
    });
    this.waitlistView.drawEntries(entries);
};
MyApptsView.prototype.setWaitlistParmVal = function(isWaitlistOn) {
    this.setWaitlistDisplay(isWaitlistOn);
};

MyApptsView.prototype.initDateTimes = function() {
	//Set up date/time scrollers for Start Date, Start Time, End Date, End Time
	var today = new Date();
	var startdatedefault = today;
	
	if (isNullOrBlank($("#txtStartDate").val()))
		$("#txtStartDate").val(moment(startdatedefault).format(fmtDate));
		
	if (isMediumScreen() || isLargeScreen()) {
		//Filter: Start Date
		$("#txtStartDate").on("click",function() {
			$("#fld").val("txtStartDate");
			$("#fldTitle").val("Start Date");
			$("#popupCal").popup("open");
		});
		//Filter: End Date
		$("#txtEndDate").on("click",function() {
			$("#fld").val("txtEndDate");
			$("#fldTitle").val("End Date");
			$("#popupCal").popup("open");
		});
		var ts = new TimeSelector("txtStartTime");
		$("#txtStartTime").on("click",function() {
			ts.openSelector(this);
		});
		var te = new TimeSelector("txtEndTime");
		$("#txtEndTime").on("click",function() {
			te.openSelector(this);
		});	
	}
	else {
		//Filter: Start Date
		$('#txtStartDate').mobiscroll().date({
			theme: 'jqm',
			display: 'modal',
			mode: 'scroller',
			dateOrder: 'D ddMyy', //'MD ddyy',
			endYear: startdatedefault.getFullYear()+2,
			minDate: new Date(2009,0,1)
		});    
		$('#txtStartDate').mobiscroll('setDate',startdatedefault,true);
	
		//Filter: End Date
		$('#txtEndDate').mobiscroll().date({
			theme: 'jqm',
			display: 'modal',
			mode: 'scroller',
			dateOrder: 'D ddMyy', //'MD ddyy',
			endYear: today.getFullYear()+2,
			minDate: new Date(2009,0,1)
		});
		$('#txtEndDate').mobiscroll("option","minDate", new Date($("#txtStartDate").val()));
	}
	
};


MyApptsView.prototype.setParmNetID = function() {
	$("#txtApptName").attr("placeholder",gParms["NETID"].toLowerCase()+" or name");
	$("#txtApptName").textinput();
	$("#txtApptMadeBy").attr("placeholder",gParms["NETID"].toLowerCase());
	$("#txtApptMadeBy").textinput();
};

MyApptsView.prototype.setUserStatus = function(isowner,ismanager,ismember) {
	if (isowner || ismanager) {
		gIsOwnerOrManager = true;
		$("#divApptMadeBy").show();
	} else {
		$("#divApptMadeBy").hide();
	}
};

//Show "saved" message on page
MyApptsView.prototype.highlightAppt = function(inApptID) {
	$("#appt" + inApptID + " div").addClass("highlightappt");
};


//Unused
MyApptsView.prototype.loadCalendarList = function() {
	for (var i=0; i < gApptCals.length; i++) {
		if (gApptCals[i] !== null) {
			$('#selCalendars').append("<option value = '" + gApptCals[i].get('calendarid') + "'>" + gApptCals[i].get('title') + "</option>");
		}
	}
	$('#selCalendars').selectmenu('refresh');
};



//Put the appt data from the appt object on the form
MyApptsView.prototype.loadAppts = function() {
	showLoadingMsg(false);

	var str = "";
	if (!this.hasOwnProperty('$apptList')) {
	    this.$apptList = $("<table>", {class: "apptlisttable", 'data-mode': 'reflow', 'data-role': 'table', id: 'tblAppts', cellspacing: 0, cellpadding: 0, border: 0});
	    this.$headerRow = $("<thead><tr></tr></thead>");
	    this.$headerRow.append(
            $('<th>Time</th>'),
            $('<th data-priority="1" class="ui-table-priority-1">For</th>'),
            $('<th data-priority="1" class="ui-table-priority-1">With</th>'),
            $('<th data-priority="3" class="ui-table-priority-3">By</th>'),
            $('<th data-priority="2" class="ui-table-priority-2">At</th>'),
            $('<th>Block Title</th>'),
            $('<th>&nbsp;</th>')
        );
        this.$apptList.append(this.$headerRow);
        
        this.$body = $("<tbody></tbody>");
        this.$apptList.append(this.$body);
        
        //jquery mobile init
        this.$apptList.trigger("create");
        this.$apptList.table();
        this.$apptList.table("refresh");

        //set up 'no entries' row
        this.$noEntriesRow = $('<tr>',{id: 'trWait_none', class: 'apptrow'}).append($('<td>',{class: 'wlnonemsg', colspan: 6, text: 'No ' + g_ApptsText + ' found'}));
	}

    this.$body.empty();
    
	//no appointments to show
	if (gAppts.length === 0) {
		this.$body.append(this.$noEntriesRow);
		$(".actionbuttons").hide();
        this.$headerRow.hide();
	} else {
		$(".actionbuttons").show();
        this.$headerRow.show();
	}
	
	var dt = null;
	_.each(gAppts, function(a) {
		if (a !== null && a.get('available')) {
			
			//get the appointment date and if it is different, create a new divider
			var st = a.get('startdatetime');
			var $row;
			if (dt === null || moment(dt).isBefore(moment(st),'day')) {
				$row = $('<tr class="apptdividerrow"></tr>');
	            $row.append($('<td colspan="7">' + moment(st).format("MMMM D, YYYY") + '</td>'));
				this.$body.append($row);
				dt = st;
			}
			
			var et = a.get('enddatetime');
			var strTime = moment(st).format(fmtTime) + '<span class="slotendtime">&nbsp;- ' + moment(et).format(fmtTime);
			if (a.isMultiDate()) strTime += " (" + moment(st).format(fmtDate) + ")";
			
			var loc = a.get('blocklocation');
			if (isNullOrBlank(loc)) loc = "(no location)";

			var aID = a.get('appointmentid');
			$row = $('<tr class="apptrow" id="appt' + aID + '"></tr>');
			var title = 'View ' + capitalize(g_ApptText);
			
			var madebystr = a.get('madeby');
			if (!isNullOrBlank(a.get('madebyname')))
				madebystr = madebystr + ' (' + a.get('madebyname') + ')';
			
			//view/edit and delete appt buttons
			var txt = a.get('labels')['APPTHING'] || g_ApptText;
			var isBlockOwner = IsThisUserLoggedIn(a.get('blockowner').userid);
			var isMyAppt = IsThisUserLoggedIn(a.get('apptmaker').userid);
			var canEdit = isBlockOwner || isMyAppt;
			
			var icon = canEdit ? 'ui-icon-edit' : 'ui-icon-info';
			var action = canEdit ? 'Edit ' : 'View ';
			var $editBtn = $("<div>",{class: "listbuttonouter"});
			$editBtn.append('<a href="#" data-ajax="false" class="ui-btn ui-corner-all '+icon+' ui-btn-icon-notext noborder" title="'+action+txt+'">'+action+txt+'</a>');
			
			var $deleteBtn = '';
			if (canEdit) {
				$deleteBtn = $("<div>",{class: "listbuttonouter"});
				$deleteBtn.append('<a href="#" data-ajax="false" class="deleteappt_btn ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Delete '+txt+'">Delete '+txt+'</a>');
			}
			
		    $row.append(
	            $('<td title="'+txt+' Time"><b class="ui-table-cell-label">Time</b>' + strTime + '</th>'),
	            $('<td title="Who is the '+txt+' for?" data-priority="1" class="ui-table-priority-1"><b class="ui-table-cell-label">For</b>' + a.get('apptmaker').getLongDisplayName() + '</td>'),
	            $('<td title="Who is the '+txt+' with?" data-priority="1" class="ui-table-priority-1"><b class="ui-table-cell-label">With</b>' + a.get('blockowner').getLongDisplayName() + '</td>'),
	            $('<td title="Who made the '+txt+'?" data-priority="3" class="ui-table-priority-3"><b class="ui-table-cell-label">By</b>' + madebystr + '</td>'),
	            $('<td title="'+txt+' Location" data-priority="2" class="ui-table-priority-2"><b class="ui-table-cell-label">At</b>' + loc + '</td>'),
	            $('<td title="Block Title"><b class="ui-table-cell-label">Block Title</b>' + a.get('blocktitle') + '</td>'),
	            $('<td class="apptbtns"></td>').append($editBtn, $deleteBtn)
	        );
			this.$body.append($row);
			
		    //set up link to appt
			$editBtn.on("vmousedown",function() {
				window.location.href = 'appt.php?calid=' + a.get('calendarid')
                    + '&blockid=' + a.get('blockid')
                    + '&apptid=' + aID;
			});
			if (!isNullOrBlank($deleteBtn)) {
				$deleteBtn.on("vmousedown",function() {
					gCurApptID = aID;
					gLabels = a.get("labels");
					$("#ccObject").val("appointment");
					$("#popupCancelConfirm").popup("open");
				});
			}
		}
	}, this);
	
    $("#divMyApptsContent .ui-collapsible-content").append(this.$apptList);
    $("#divMyApptsContent .ui-li-count").text(gAppts.length);
    
	//Get the apptid to show what appointment was made or edited, visually
	controller.getAndClearSessionVar(["apptid"]);

};

MyApptsView.prototype.showDeletedAppt = function() {
	var id = "#appt"+gCurApptID;
	//first get the siblings
	var prevDivider = $(id).prev('.apptdividerrow');
	var nextDivider = $(id).next('.apptdividerrow');
	var next = $(id).next();
	//now remove
	$(id).remove();
	if (prevDivider.length && (!next.length || nextDivider.length))
		prevDivider.remove();
	//remove from gAppts
	var deleteIndex = -1;
	_.each(gAppts, function(a,ind) {
		if (a.get("appointmentid") == gCurApptID)
			deleteIndex = ind;
	});
	gAppts.splice(deleteIndex,1);
	$("#divMyApptsContent .ui-li-count").text(gAppts.length);
};

//Validate the form before saving
MyApptsView.prototype.isValidForm = function() {
	return true;	
};

//set filters from session var
MyApptsView.prototype.setFilters = function(arr) {
	var hasAny = false;
	_.each(arr, function(item) {
		var nm = item[0];
		var val = item[1];
		if (nm.indexOf(prefix) !== -1) {
			hasAny = true;
			switch(nm) {
			case prefix+'startdate':
				//KDC original 'startdate' of today is overwritten by blank.  Need to set a session var for this but for now checking for null
				if (!(isNullOrBlank(val) && !isNullOrBlank($('#txtStartDate').val)))
					$('#txtStartDate').val(val);
				break;
			case prefix+'enddate':
				$('#txtEndDate').val(val);
				break;
			case prefix+'starttime':
				$('#txtStartTime').val(val);
				break;
			case prefix+'endtime':
				$('#txtEndTime').val(val);
				break;
			case prefix+'apptwithorfor':
				$('#txtApptName').val(val);
				break;
			case prefix+'apptby':
				if ($("#txtApptMadeBy")) $('#txtApptMadeBy').val(val);
				break;
			}
		}
	});
	if (hasAny)
		getfilteredappts();
};

var myapptsview = new MyApptsView();