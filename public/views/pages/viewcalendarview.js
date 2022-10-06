//$(function(){
var gBlocks = [];

var gSelectedCalIDs = []; //Originally from URL param, but can change based on calendar selector
var gCalendars = {};			   //All of the calendars in the calendar selector

var gApptViews = {};				//id: apptVw
var gManagedIDs = [];		//array of all calendar IDs managed by logged in user

/********************************************/
/* Date Selector 							*/
/********************************************/
var DateSelector = function(viewcal, selDate) {
	this.viewcal = viewcal;
	selDate = selDate || new Date();
	var mStart = moment(selDate).startOf("month");
	var mEnd = moment(selDate).endOf("month");
	this.cal = new Calendar({parentID: "divDateScroll", selectorObj: this, startDate: mStart.toDate(), endDate: mEnd.toDate(), canEnlarge: true, initialDate: selDate});
	this.cal.drawCalendar();

};
_.extend(DateSelector.prototype, {
	selAny: function(inRangeType) {
		$(".loadingmsg").show();
		var selIDs = this.cal.selectedDateIDs;
		if (selIDs.length > 0 && !isNaN(selIDs[0]))
			viewCalViewController.setSessionVar([["viewcal_calrangetype",inRangeType],["viewcal_selstartdate",selIDs[0]]],$.noop);
		if (!viewcalview.loadingData) 
			viewcalview.drawBlocks();
	},
	//set up day,week,month click handlers
	selectDay: function() {
		this.selAny("day");
	},
	doClickDay: function() {
		if (isSmallScreen()) {
		    this.scrollToBlock();
		} else {
			this.cal.setCalSizeAndPref('small');
		}
	},
	selectWeek: function() {
		this.selAny("week");
	},
	selectMonth: function() {
		this.selAny("month");
	},
	
	scrollToBlock: function($blk) {
        var $target = $("#divBlocks");

        $('html, body').stop().animate({
            'scrollTop': $target.offset().top
        }, 900, 'swing', function () {});
	},
	
	//set up scroll handlers
	scrollAny: function() {
		this.viewcal.getNewData();
	},
	scrollPrevious: function() {
		this.scrollAny();
	},
	scrollNext: function() {
		this.scrollAny();
	},
	
	getSelectedDateIDs: function() {
		return this.cal.selectedDateIDs;
	},
	getDateStatus: function(inDateID) {
		if (typeof this.cal.caldates[inDateID] !== 'undefined')
			return this.cal.caldates[inDateID].daytype;
		return '';
	},
	
	createDateTextInfo: function(inBlocks) {
		var textByDateID = {};
		
		_.each(inBlocks, function(blkArr, dateID) {
			
			if (!textByDateID[dateID]) textByDateID[dateID] = [];
			
			_.each(blkArr, function(blk) {
				var $divBlk = $("<div>",{id: "blk"+blk.get("blockid"), class: "calblock"});
				var $availMsg = '';
				if (blk.get("available")) {
					$divBlk.addClass(getCalStyle(blk.get("calendarid")));
				} else {
					$divBlk.addClass("unavailableBlock");
					var icon = $("<div>", {class: "ui-btn-icon-notext ui-icon-lock", style: "position: relative"});
					$availMsg = $("<div>",{class: "unavailText", html: "Locked"});
					$availMsg.prepend(icon);
				}
				var $time = $("<div>",{class: "calblocktime", html: moment(blk.get("startdatetime")).format("h:mma")+"-"+moment(blk.get("enddatetime")).format("h:mma")});
				var $title = $("<div>",{class: "calblocktitle", html: blk.get("title")});
				var $recurring = blk.get("seriesid") > 0 ? $("<div>",{class: "calblockrecurring", html: "(recurring)"}) : "";
				var $blockowner = $("<div>", {class: "calblockowner", html: "<b>With:</b> " + blk.get("blockowner").getDisplayName()});
								
		        //deadlines
				var $deadlines = "";
		        var mStart = moment(blk.get("startdatetime"));
		        var strDeadlines = "";
		        var op = blk.get("opening");
		        if (op > 0)
		            strDeadlines += "<b>Available:</b> " + mStart.clone().subtract(op, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
		        var dl = blk.get("deadline");
		        if (dl > 0)
		            strDeadlines += "<b>Deadline:</b> " + mStart.clone().subtract(dl, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
		        var cdl = blk.get("candeadline");
		        if (cdl > 0)
		            strDeadlines += "<b>Deadline to Cancel:</b> " + mStart.clone().subtract(cdl, "minutes").format("M/D/YYYY [at] h:mmA");
		        
		        if (op > 0 || dl > 0 || cdl > 0) {
		            var el = $("<div>", {html: strDeadlines});
		            var icon = $("<span>", {class: "ui-btn-icon-notext ui-icon-clock", style: "position: relative"});
		            var iconWrapper = $("<div>", {class: "iconWrapper"});
		            iconWrapper.append(icon);
		            $deadlines = $("<div>", {class: "deadlines"});
		            $deadlines.append(iconWrapper, el);
		        }

				//appts
		        var totalAppts = 0;
				var $appts = $("<div>",{class: "blockappointments"});
				
				var calID = blk.get("calendarid");
				
				_.each(blk.get("slots"), function(slt) {
					totalAppts += parseInt(slt.get("numappts"),10);
					_.each(slt.get("appts"), function(appt) {
						var href = "appt.php?calid="+calID+"&blockid="+appt.get("blockid")+"&apptid="+appt.get("appointmentid");
						$appts.append($("<div>",{class: "calappttime", "data-ajax": "false", href: href, html: moment(appt.get("startdatetime")).format("h:mma")+ " - " +moment(appt.get("enddatetime")).format("h:mma")}));
					});
				});
				
				var apptText = totalAppts === 1 ? capitalize(blk.get('labels')['APPTHING']) : capitalize(blk.get('labels')['APPTHINGS']);
				if (totalAppts > 0) $appts.prepend($("<div>",{html: "<b><i>" + apptText + "</b></i><br>"}));

				//available appointments
                var $availappts = $("<div>", {class: "availableAppts", html: totalAppts + " "+ apptText + "<br>" + blk.numAvailAppts + " Available"});
                
                $divBlk.empty().append($time, $availMsg, $title, $recurring, $blockowner, $deadlines, $availappts, $appts);
			
				textByDateID[dateID].push($divBlk);
			});		
			
		});
		
		this.cal.setDateTextInfo(textByDateID);
	},
	
	setCalSelectedDates: function(type,inID) {
		type = type || "week";
		this.cal.tmpSelStartDateID = isNaN(parseInt(inID,10)) ? null : inID;
		this.cal.setSelection({range: type});
	},
	
	saveSelected: function() {
		this.cal.tmpSelStartDateID = this.cal.selectedDateIDs[0];
	},
	
	setCalData: function() {
		//this should set the style of the blocks on the "expanded" calendar

	},
	
	setCalSizeFromPref: function(prefVal) {
	    var size = '';
	    //if no preference is set
	    if (prefVal === null) {
	        if (isLargeScreen())
	            size = 'large';
	        else size = 'small';
	    } else {  //a preference is set
	        if (isLargeScreen()) {
	            if (isTrue(prefVal)) size = 'large';
	            else size = 'small'; 
	        } else {
	            size = 'small';
	        }
	    }
	    this.cal.setCalSize(size);
	}
	
});


/********************************************/
/* Calendar Selector 						*/
/********************************************/
function getCalStyle(id) {
	var ind = Object.keys(gCalendars).indexOf(id);
	if (ind !== -1)
		return "calstyle_"+ind;
	return "";
}
	
var CalendarSelector = function(viewcal) {
	this.viewcal = viewcal;
	this.parentSelector = '#divCalSelect .ui-collapsible-content';
	this.titles = {
		'Owned': 'Owned',
		'Managed': 'Managed',
		'Member': 'Member Of'
	};
	
	_.bindAll(this, 'doCalCheck');

	this.draw();
	viewCalViewController.loadCalendarSelectorData();
};
_.extend(CalendarSelector.prototype, {
	getListID: function(inType) {
		return "ul"+inType+"Cals";
	},
	//inType is Owned, Managed, Member
	drawSection: function(inType) {
		var sectionID = "div"+inType;
		var listID = this.getListID(inType);
		
		//is it already drawn?
		if (!$("#"+sectionID).length) {
			//draw it
			$("#divCalSelect").collapsible();
			$("#divCalSelect .ui-collapsible-content").append(
				'<div id="' + sectionID + '" class="sidesection"><h2>' + this.titles[inType] + '</h2>'+
				'<ul id="' + listID + '" data-role="listview"></ul></div>'
			);
			$("#"+listID).listview();
			if (isMediumScreen() || isLargeScreen())
			    $("#divCalSelect").collapsible("expand");
		} else {
			//empty the list
			$("#"+listID).empty();
		}	
	},
	draw: function() {
		this.drawSection("Owned");
		this.drawSection("Managed");
		this.drawSection("Member");
		$(this.parentSelector).append('<div class="heightfix"></div>');
	},
	
	checkEmptySection: function(inType,inIDArr) {
		if (!inIDArr.length) {
			$("#"+this.getListID(inType)).append("<li><div><i>No " + inType + " Calendars</i></div></li>");
		}
	},
	loadSectionCals: function(inType,inIDArr) {
		var str = "";
		var listID = this.getListID(inType);
		
		$.each(inIDArr, _.bind(function(ind,id) {
			if (gCalendars[id]) {
				var chkName = "chk"+inType;
				str = '<li><div>';
				str += '<input type="checkbox" class="calchk" name="' + chkName + '" id="' + chkName + id + '" value="' + id + '"';
				//is the calendar selected?
				if ($.inArray(id,gSelectedCalIDs) >= 0)
					str += ' checked="checked"';
				str += ' />';
				//color box
				str += '<div class="checkboxlistitem"><div class="caldispstyle ' + getCalStyle(id) + '"></div>';
				str += gCalendars[id].get("title");
				if (inType !== "Owned")
					str += '<div class="calowner">' + gCalendars[id].get("owner").getDisplayName() + '</div>';
				str += '</div><div class="heightfix"></div>';
				str += '</div></li>';

				var $lst = $("#"+listID);
                $lst.append(str);
                $lst.listview("refresh");
				
			}
		},this));
	},
	loadCals: function(calObjs, ownedIDs, managedIDs, memberIDs) {
		gCalendars = _.clone(calObjs);
		gManagedIDs = _.clone(managedIDs);

		//show help message if calendar does not have any blocks
		var userStatus = this.viewcal.userCalStatus;
		if (!userStatus['calhasblocks']) {
			var firstCal = gCalendars[gSelectedCalIDs[0]];
			var who = firstCal.get("owner").get("name"); //ismanager is default
			if (userStatus['isowner'] || userStatus['ismember']) {
				who = 'yourself';
			}
			if (!userStatus['ismember'] && userStatus['calhasmembers']) 
				who += ' or any members';
    		var txt = 'To make ' + who + ' available for appointments on this calendar, you need to add one or more blocks of available time. Click on Add Block to do that.';
    		gMessage.displayHelp(txt);
		}
		
		//set the title and breadcrumbs
		var caltitle = gSelectedCalIDs.length > 1 ? "Multiple Calendars" : gCalendars[gSelectedCalIDs[0]].get("title");
		$("#pagetitle").html(caltitle);
		$(".bccurpage").text(caltitle);
		
		//if any blocks are already drawn, update their calendar titles
		_.each(this.viewcal.blockViews, function(blkVwArray) {
			_.each(blkVwArray, function(blkVw) {
				if (blkVw && blkVw.setCalData) blkVw.setCalData();
			});
		},this);
		
		//set calendar data on the small calendar, too
		this.viewcal.dateSelector.setCalData();
		
		//owned
		this.checkEmptySection("Owned",ownedIDs);
		this.loadSectionCals("Owned",ownedIDs);
		
		//managed
		this.checkEmptySection("Managed",managedIDs);
		this.loadSectionCals("Managed",managedIDs);
		
		//member
		this.checkEmptySection("Member",memberIDs);
		this.loadSectionCals("Member",memberIDs);

		var $calchk = $(".calchk");
        $calchk.checkboxradio();
        $calchk.checkboxradio("refresh");
        $calchk.on("click",this.doCalCheck);
	},
	doCalCheck: function(e) {
		var $chk = $(e.target);
		//add to or remove from gSelectedCalIDs
		if ($chk.is(":checked"))
			gSelectedCalIDs.push($chk.val());
		else
			gSelectedCalIDs.splice($.inArray($chk.val(), gSelectedCalIDs),1);
		
		//set the title and breadcrumbs
		var caltitle = 'No Calendars Selected';
		if (gSelectedCalIDs.length > 1) {
            caltitle =  "Multiple Calendars";
		} else if (gSelectedCalIDs.length === 1) {
            caltitle = gCalendars[gSelectedCalIDs[0]].get("title");
        }
		$("#pagetitle").html(caltitle);
		$(".bccurpage").text(caltitle);

        var $loadingmsg = $(".loadingmsg");
		if (gSelectedCalIDs.length > 0) {
            $loadingmsg.hide();
            $loadingmsg.text('...Loading...');
    		this.viewcal.setButtonStyle();
    		this.viewcal.getNewData();
		} else {
            $loadingmsg.text('No calendars selected.');
            $loadingmsg.show();
		    this.viewcal.emptyBlockDisplay();
            this.viewcal.dateSelector.cal.emptyCalDateInfo();
        }
	}
});


/********************************************/
/* Block - on View Calendar  				*/
/********************************************/
var gCurBlockID = null;
var gSerID = null;
var gLabels = {}; //for cancel confirm

var VCBlock = function(options) {
	this.model = options.model;
	this.slotViews = {}; //sdt: slotView
	
	_.bindAll(this, 'confirmDelete', 'changeAvailable', 'confirmSync', 'gotoEditBlock');
};
_.extend(VCBlock.prototype, {
	drawControl: function(inName,inIcon,inText,inHref, extra) {
		var href = inHref || "#";
		extra = extra || "";
		var $button = $("<div>",{class: "listbuttonouter"});
		$button.append('<a href="' + href + '" data-ajax="false" class="'+inName+'_btn ui-btn ui-corner-all '+inIcon+' ui-btn-icon-notext noborder" title="'+inText+'" ' + extra + '>'+inText+'</a>');
		return $button;
	},
	drawControls: function() {
		var $buttons = $('<div>', {class: "blockcontrols"});
		
		//check calendar owner, calendar manager, block owner to display controls
		var mod = this.model;
		var isCalOwner = !_.isEmpty(gCalendars) && gCalendars[mod.get('calendarid')].owner['userid'] === g_loggedInUserID;
		var isCalManager = gManagedIDs.indexOf(mod.get('calendarid')) >= 0;
		var isBlockOwner = mod.get('blockowner').userid === g_loggedInUserID;

		var $availBtn;
		if (isCalOwner || isCalManager || isBlockOwner) {
			var $deleteBtn = this.drawControl('delete','ui-icon-delete','Delete Block','#popupCancelConfirm','data-rel="popup"');
			$buttons.append($deleteBtn);
			$deleteBtn.on("vmousedown",this.confirmDelete);

			$availBtn = this.drawControl('makeavailable','ui-icon-available','Lock/Unlock Block');
			$buttons.append($availBtn);
			$availBtn.on("vmousedown",this.changeAvailable);
		} else if (mod.get("available") === false){
        	$availBtn = this.drawControl('makeavailable','ui-icon-lock','Block is Locked',"#");
        	$availBtn.addClass("notclickable");
            $buttons.append($availBtn);
        }
		
		var $syncBtn = this.drawControl('sync','ui-icon-recycle','Sync Block');
		$buttons.append($syncBtn);
		$syncBtn.on("vmousedown",this.confirmSync);

		if (isCalOwner || isCalManager || isBlockOwner) {
			var $editBtn = this.drawControl('edit','ui-icon-edit','Edit Block');
			$buttons.append($editBtn);
			$editBtn.on("vmousedown",this.gotoEditBlock);
		} else { //view
			var $viewBtn = this.drawControl('view','ui-icon-info','View Block');
			$buttons.append($viewBtn);
			$viewBtn.on("vmousedown",this.gotoEditBlock);
		}
		
		//for showing unavailble block
		$buttons.append($('<div>', {class: "lockedmsg", text: 'Locked'}));

		return $buttons;		
	},
	//event handler
	confirmDelete: function() {
		gCurBlockID = this.model.get("blockid");
		gLabels = this.model.get("labels");
		gSerID = this.model.get("seriesid");
		$("#ccObject").val("block");
		$("#popupCancelConfirm").popup("open");

		return false;	
	},
	//event handler
	confirmSync: function() {
		//if it's a series, have to prompt for "sync what"
		if (this.model.get("seriesid") > 0) {
		    gSyncBlockView = this;
			$("#popupSyncConfirm").popup("open");
		}
		else
			this.doSync();
	},
	//send sync request to server
	doSync: function(inSyncWhat) {
		blockController.syncBlock(g_loggedInUserID,inSyncWhat,this.model.get("blockid"));
	},
	//after server response
	showSynced: function(inIcal) {
		if (isNullOrBlank(inIcal)) {
			alert("Sync Complete");
		} else {
			var blob = new Blob([inIcal], {type : 'text/calendar;method=PUBLISH'});
			saveAs(blob, "calendar.ics");
		}
	},
	//event handler
	changeAvailable: function() {
		var newVal = !this.model.get("available");
		this.model.set("available",newVal);
		blockController.lockBlock(this.model.get("blockid"),newVal);
	},
	//after server response
	showIsAvailable: function() {
		var $btn = this.$el.find(".blockcontrols .makeavailable_btn");
		var avail = this.model.get("available");
		if (avail) {
			$btn.removeClass("ui-icon-lock locked").addClass("ui-icon-available").html("Available Block");
		} else {
			$btn.removeClass("ui-icon-available").addClass("ui-icon-lock locked").html("Unavailable Block");
		}
		this.$el.toggleClass("unavailableBlock",!avail);
		this.$el.find(".blockcontrols .lockedmsg").toggle(!avail);
	},
	//event handler
	gotoEditBlock: function() {
		window.location.href = 'block.php?calid=' + this.model.get("calendarid")
            + '&blockid=' + this.model.get("blockid");
	},
	
	drawSlots: function() {
		_.each(this.model.slots, function(slot) {
			var slotView = new VCSlot({model: slot, calID: this.model.get("calendarid"), blockView: this});
			var sdtID = moment(slot.get("startdatetime")).format("YYYYMMDDTHHmm");
			this.$slotArea.append(slotView.draw());
			if (!this.slotViews[sdtID]) this.slotViews[sdtID] = slotView;
		}, this);
	},
	setCalData: function() {
		var calID = this.model.get("calendarid");
		if (gCalendars && gCalendars[calID]) {
			this.$el.addClass(getCalStyle(calID));
			var cal = gCalendars[calID];
			this.$el.find(".caltitle").html(cal.get("title"));
			//if logged in user is not the cal owner, cal manager, or block owner, add style class
			var isCalOwner = !_.isEmpty(gCalendars) && cal.get("owner").userid === g_loggedInUserID;
			var isCalManager = gManagedIDs.indexOf(calID) >= 0;
			var isBlockOwner = this.model.get('blockowner').userid === g_loggedInUserID;
			if (!isCalOwner && !isCalManager && !isBlockOwner)
				this.$el.addClass("memberNonOwner");
		}
	},
	draw: function() {
		var $blkVw = $("<div>", {class: "blocksection"});
		this.$el = $blkVw;
		
		var isSlotted = this.model.slots && this.model.slots.length > 1;
		
		//block controls
		$blkVw.append(this.drawControls());
		this.showIsAvailable();

		//make access
		var $makeaccess = $("<div>", {class: "blockmakeaccess", html: 'Make Access: <b>' + this.model.get("makeaccess") + '</b>'});
		$blkVw.append($makeaccess);

		//deadlines
		var mStart = moment(this.model.get("startdatetime"));
        var strDeadlines = "";
        var op = this.model.get("opening");
        if (op > 0)
            strDeadlines += "<b>Available:</b> " + mStart.clone().subtract(op, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
        var dl = this.model.get("deadline");
        if (dl > 0)
            strDeadlines += "<b>Deadline:</b> " + mStart.clone().subtract(dl, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
        var cdl = this.model.get("candeadline");
        if (cdl > 0)
            strDeadlines += "<b>Deadline to Cancel:</b> " + mStart.clone().subtract(cdl, "minutes").format("M/D/YYYY [at] h:mmA");
        
        if (op > 0 || dl > 0 || cdl > 0) {
            var el = $("<div>", {html: strDeadlines});
            var icon = $("<span>", {class: "ui-btn-icon-notext ui-icon-clock", style: "position: relative"});
            var iconWrapper = $("<div>", {class: "iconWrapper"});
            iconWrapper.append(icon);
            var par = $("<div>", {class: "deadlines"});
            par.append(iconWrapper, el);
            $blkVw.append(par);
        }
        
        //title
		$blkVw.append($("<h2>", {html: this.model.get("title")}));
        //blockowner
        $blkVw.append($("<div>", {class: "blockowner", html: "Block owner: "+this.model.get("blockowner").getDisplayName()}));
		//calendar title - blank until calendars load
		$blkVw.append($("<div>", {class: "caltitle", html: ""}));	
		//description
		$blkVw.append($("<div>", {class: "blockdesc", html: this.model.get("description")}));
		//location
		var loc = this.model.get("location") || "(no location)";
		$blkVw.append($("<div>", {class: "blocklocation", html: loc}));
		//maxappts
		var maxAppts = this.model.get("maxapps");
		maxAppts = maxAppts > 0 ? maxAppts : "(no limit)";
		var appsName = this.model.get("labels")["APPTHINGS"];
		var maxText = isSlotted ? "Max " + appsName + " per slot: " : "Max " + appsName + " per block: ";
		$blkVw.append($("<div>", {class: "maxappts", html: maxText + maxAppts}));		
		//maxper
		if (isSlotted) {
			var maxPer = this.model.get("maxper");
			maxPer = maxPer > 0 ? maxPer : "(no limit)";
			$blkVw.append($("<div>", {class: "maxappts", html: "Max " + appsName + " per person: " + maxPer}));	
		}
		//recurring block
        if (this.model.get("seriesid") && this.model.get("seriesid") > 0)
            $blkVw.append($("<div>", {class: "recurtext", html: 'Block is part of a recurring series.'}));
		//block url
		var blkurl = getURLPath() + "/makeappt.php?calid=" + this.model.get("calendarid") + "&blockid=" + this.model.get("blockid");
		var $url = $("<div>", {class: "urldisplay"});
		$url.append($("<div>", {class: "urltitle", html: "Block URL: "}));		
		$url.append($("<div>", {class: "urlstring", html: blkurl}));		
		$blkVw.append($url);
		
			
		//slots
		this.$slotArea = $("<div>", {class: "slotlist"});
		$blkVw.append(this.$slotArea);
		this.drawSlots();
		
		//calendar data
		this.setCalData();
		
		return $blkVw;
	}
});


/********************************************/
/* Slot - on View Calendar  				*/
/********************************************/
var VCSlot = function(options) {
	this.model = options.model;
	this.calID = options.calID;
	this.blockView = options.blockView;
	this.apptID = 0;

	_.bindAll(this, 'changeAvailable', 'gotoMakeAppt');
};
_.extend(VCSlot.prototype, {
	drawControl: function(inName,inIcon,inText,inHref) {
		var $button = $("<div>",{class: "listbuttonouter"});
		$button.append('<a href="' + inHref + '" data-ajax="false"'
			+ ' class="'+inName+'_btn ui-btn ui-corner-all '+inIcon+' ui-btn-icon-notext noborder"'
			+ ' title="'+inText+'">'+inText+'</a>');
		return $button;
	},
    drawControls: function() {
    	var apptText = this.blockView.model.get('labels')['APPTHING'] || g_ApptText;
        var $buttons = $('<div>', {class: "slotcontrols"});

		//check calendar owner, calendar manager, block owner to display controls
		var isCalOwner = !_.isEmpty(gCalendars) && gCalendars[this.calID].get("owner").userid === g_loggedInUserID;
		var isCalManager = gManagedIDs.indexOf(this.calID) >= 0;
		var isBlockOwner = this.blockView.model.get('blockowner').userid === g_loggedInUserID;
		
        //make an appointment
        if (this.model.get("makeable")) {
            var $makeBtn = this.drawControl('makeappt','ui-icon-plus','New ' + capitalize(apptText),"#");
            $buttons.append($makeBtn);
            $makeBtn.on("vmousedown",this.gotoMakeAppt);
        }
        
        //make slot available/unavailable
		var $availBtn;
        if (isCalOwner || isCalManager || isBlockOwner) {
            $availBtn = this.drawControl('makeavailable','ui-icon-available','',"#");
            $buttons.append($availBtn);
            $availBtn.on("vmousedown",this.changeAvailable);
        } else if (this.model.get("available") === false){
        	$availBtn = this.drawControl('makeavailable','ui-icon-lock','Slot is Locked',"#");
        	$availBtn.addClass("notclickable");
            $buttons.append($availBtn);
        }

        return $buttons;        
    },
	drawAppts: function() {
		var blk = this.blockView;
		_.each(this.model.appts, function(appt) {
			appt.labels = _.clone(blk.model.get('labels'));
			var apptView = new VCAppointment({model: appt, blockowner: blk.model.get('blockowner')});
			gApptViews[appt.get("appointmentid")] = apptView;
			this.$apptArea.append(apptView.draw());
		}, this);
	},
	draw: function() {
		var $slotRow = $("<div>",{class: "blockslotrow"});
		this.$el = $slotRow;
		
		var $slot = $("<div>",{class: "blockslot"});
		var $slottime = $("<div>",{class: "slottime", html: moment(this.model.get("startdatetime")).format("h:mm a")});
		$slottime.append($("<span>",{class: "slotendtime", html: "&nbsp;- "+ moment(this.model.get("enddatetime")).format("h:mm a")}));
		$slot.append($slottime);
		
		//draw appointments
		this.$apptArea = $("<div>",{class: "slotapptlist"});
		$slot.append(this.$apptArea);
		this.drawAppts();

		$slotRow.append($slot);		
		
		//draw the controls
		$slot.append(this.drawControls());
		//must set "available" after the controls are drawn
        this.showIsAvailable(this.model.get("unavailapptid"));

		return $slotRow;
	},
	
	showIsAvailable: function(apptID) {
		var $btn = this.$el.find(".makeavailable_btn");
		var avail = this.model.get("available");
		if (avail) {
			$btn.removeClass("ui-icon-lock locked").addClass("ui-icon-available").html("Available Slot");
			this.model.set("unavailapptid",0);
			this.apptID = 0;
		} else {
			$btn.removeClass("ui-icon-available").addClass("ui-icon-lock locked").html("Unavailable Slot");
			this.model.set("unavailapptid",apptID);
			this.apptID = apptID;
		}
		this.$el.toggleClass("unavailableSlot",!avail);
	},
	changeAvailable: function() {
		var oldVal = this.model.get("available");
		this.model.set("available",!oldVal);
		if (isTrue(oldVal)) {
			//lock the slot
			blockController.lockSlot(this.calID, this.model.get("blockid"), this.model.get("startdatetime"), this.model.get("enddatetime"));
		} else {
			//unlock the slot
			blockController.unlockSlot(this.model.get("unavailapptid"));
		}
	},
	
	gotoMakeAppt: function() {
		window.location.href = 'appt.php?calid=' + this.calID + '&blockid='
            + this.model.get("blockid")
            + '&sdt=' + getURLDateTime(this.model.get("startdatetime"))
            + '&edt=' + getURLDateTime(this.model.get("enddatetime"));
	}
});


/********************************************/
/* Appointment - on View Calendar  			*/
/********************************************/
var gCurApptID = null;

var VCAppointment = function(options) {
	this.model = options.model;
	this.blockowner = options.blockowner;

	_.bindAll(this, 'gotoEditAppt', 'syncAppt', 'confirmDelete');
};
_.extend(VCAppointment.prototype, {
	drawControl: function(inName,inIcon,inText,inClass) {
		var $button = $("<div>",{class: "listbuttonouter"});
		$button.append('<a href="#" data-ajax="false" class="'+inName+'_btn '+inClass+' ui-btn ui-corner-all '+inIcon+' ui-btn-icon-notext noborder" title="'+inText+'">'+inText+'</a>');
		return $button;
	},
	drawControls: function() {
		var $buttons = $('<div>', {class: "apptcontrols"});
		
		//check calendar owner, block owner to display controls
		var mod = this.model;
		var txt = mod.get('labels')['APPTHING'] || g_ApptText;
		txt = capitalize(txt);
		var isCalOwner = !_.isEmpty(gCalendars) && gCalendars[mod.get('calendarid')].get("owner").userid === g_loggedInUserID;
		var isCalManager = gManagedIDs.indexOf(mod.get('calendarid')) >= 0;
		var isBlockOwner = this.blockowner.userid === g_loggedInUserID;
		var isMyAppt = mod.get('apptmaker').userid === g_loggedInUserID;
		
		if (isCalOwner || isCalManager || isBlockOwner || isMyAppt) {
			var $editBtn = this.drawControl('editappt','ui-icon-edit','Edit ' + txt,'');
			$buttons.append($editBtn);
			$editBtn.on("vmousedown",this.gotoEditAppt);
		} else { //view
			var $viewBtn = this.drawControl('view','ui-icon-info','View ' + txt,'');
			$buttons.append($viewBtn);
			$viewBtn.on("vmousedown",this.gotoEditAppt);
		}
			
		var $syncBtn = this.drawControl('syncappt','ui-icon-recycle','Sync ' + txt,'extracontrols');
		$buttons.append($syncBtn);
		$syncBtn.on("vmousedown",this.syncAppt);

		if (isCalOwner || isCalManager || isBlockOwner || isMyAppt) {
			var $deleteBtn = this.drawControl('deleteappt','ui-icon-delete','Delete ' + txt,'extracontrols');
			$buttons.append($deleteBtn);
			$deleteBtn.on("vmousedown",this.confirmDelete);
		}
		
		return $buttons;
	},
	draw: function() {
		var $apptVw = $("<div>",{class: "slotappt"});
		this.$el = $apptVw;
		
		var maker = this.model.get("apptmaker");
		
		//with
		$apptVw.append($("<span>", {class: "apptwith", html: "with " + maker.get("name")}));

		//extra data for large screens
		var $moreData = $("<span>",{class: "moreapptdata"});	
		//email
		$moreData.append("(",$("<div>", {class: "apptemail", html: maker.get("email")}));
		//phone
		if (!isNullOrBlank(maker.get("phone")))
			$moreData.append(", ",$("<div>", {class: "apptphone", html: maker.get("phone")}));
		$moreData.append(")");
		$apptVw.append($moreData);
		
		$apptVw.append(this.drawControls());
				
		return $apptVw;
	},
	
	gotoEditAppt: function() {
		window.location.href = 'appt.php?calid=' + this.model.get("calendarid")
            + '&blockid=' + this.model.get("blockid")
            + '&apptid=' + this.model.get("appointmentid");
	},
	syncAppt: function() {
		apptController.syncAppt(g_loggedInUserID,this.model.get("appointmentid"));
	},
	//after server response
	showSynced: function(inIcal) {
		if (isNullOrBlank(inIcal)) {
			alert("Sync Complete");
		} else {
			var blob = new Blob([inIcal], {type : 'text/calendar;method=PUBLISH'});
			saveAs(blob, "calendar.ics");
		}
	},

	confirmDelete: function() {
		gCurApptID = this.model.get("appointmentid");
		gLabels = this.model.get("labels");
		$("#ccObject").val("appointment");
		$("#popupCancelConfirm").popup("open");	
	}

});



/********************************************/
/* View Calendar View						*/
/********************************************/
var ViewCalendarView = function() {
	this.pagetitle = "";

	this.loadingData = false;
	this.userCalStatus = {};
	
	this.dateFormat = {
		'day': 'MMMM D, YYYY',
		'week': 'MMMM D, YYYY',
		'month': 'MMMM, YYYY'
	};
	
	_.bindAll(this, 'goToSettings', 'goToAddBlock', 'showBlockDelete');
};

_.extend(ViewCalendarView.prototype, {
	//initialize the page
	setPageVars: function(sdt) {
	    //init controllers
	    viewCalViewController.init();
        blockController.init();

		//Get URL Param
		gSelectedCalIDs = $.urlParam('calid').split(",");
		
		//Small Calendar setup
		this.seldt = null;
		if (sdt) {
		    this.seldt = sdt;
		}
		viewCalViewController.checkUserStatus(gSelectedCalIDs);
	},
	
	confirmUserStatus: function(isowner,ismanager,ismember,userid,calid,calhasblocks,calhasmembers,calhasmanagers) {
		this.userCalStatus = {
			'isowner': isowner,
			'ismanager': ismanager,
			'ismember': ismember,
			'calhasblocks': calhasblocks,
			'calhasmembers': calhasmembers,
			'calhasmanagers': calhasmanagers
		};
	    if (isowner || ismanager || ismember) {
	        this.showPage();
	    } else {
	    	var caltext = isNullOrBlank(calid) ? 'none selected' : 'id '+calid;
            gMessage.displayError('User '+userid+' does not have access to edit this calendar ('+caltext+').');
	    }
	},
	
	//if user is allowed to see the page
	showPage: function() {
	    showAll(true);
	    
        this.$blockArea = $("#divBlocks");
        this.blocks = {}; //dateID: [Block Objects]
        this.blockViews = {}; //dateID: [VCBlock Objects]
        
        this.dateSelector = new DateSelector(this, this.seldt);
        controller.getAndClearSessionVar(["infmsg"]);
		controller.getPrefs(["isEnlargedViewCal"]);
        
        viewCalViewController.getCalendarSessionVars();

        //Calendar Selector setup
        this.calSelector = new CalendarSelector(this);
        
        this.getNewData();
	},
	
	//buttons are enabled/disabled based on how many are selected
	setButtonStyle: function() {
		var isJustOne = gSelectedCalIDs.length === 1;
		$("#btnAddBlock").toggleClass("ui-disabled", !isJustOne);
		$("#btnSettings").toggleClass("ui-disabled", !isJustOne);
	},
	
	goToSettings: function() {
		if (gSelectedCalIDs.length === 1) {
			window.location.href = "calendarconfig.php?calid=" + gSelectedCalIDs[0];	
		}
	},
	goToAddBlock: function() {
		if (gSelectedCalIDs.length === 1) {
			var fmt = "YYYY-MM-DD";
			var dtstr = moment(this.dateSelector.cal.selectedDateIDs[0], this.dateSelector.cal.dateIDFormat).format(fmt) || moment().format(fmt);
			window.location.href = "block.php?calid=" + gSelectedCalIDs[0] + "&startdate=" + dtstr;
		}
	},
	
	drawBreadcrumbs:  function(inBCFlatArr) {
		var bcarray = [];
	
		if (inBCFlatArr.length >= 2) {
			for (var i=0; i < inBCFlatArr.length; i+=3) {
				bcarray.push([inBCFlatArr[i],inBCFlatArr[i+1],inBCFlatArr[i+2]]);
			}
		} else {
			inBCFlatArr = []; //reset, because it is just 1 value of ""
		}
		//special case for view calendar, check to see if it already exists in array. If so, don't add.
		if (inBCFlatArr.indexOf("View Calendar") > -1) {
			bcarray.pop();
		} else {
			inBCFlatArr.push("View Calendar","viewcalendar.php?calid=" + gSelectedCalIDs.join(","),"lnkViewCal");
		}
		bcarray.push(["View Calendar","",""]);
	
		buildBreadcrumbs(bcarray);
		
		//push breadcrumbs to session
		controller.setSessionVar([["breadcrumbs",inBCFlatArr]]);
		
	},
	drawBlocks: function() {
		this.$blockArea.empty();
		
		//loop over the selected date IDs and show the blocks on those dates
		var blockCounter = 0;
		var selDateIDs = this.dateSelector.getSelectedDateIDs();
		_.each(selDateIDs, function(dtID) {
			
			//draw the day heading only if there are any blocks
			if (this.blocks && this.blocks[dtID] && this.blocks[dtID].length) {			
				this.$blockArea.append($("<h3>", {html: moment(dtID,"YYYYMMDD").format("dddd, MMMM D, YYYY")}));
				this.$blockArea.append($("<span>", {class: "daytype", html: this.dateSelector.getDateStatus(dtID)}));
			}
			
			//loop over all blocks for the date
			_.each(this.blocks[dtID], function(blk) {
				var blkVw = new VCBlock({model: blk});
				
				if (!this.blockViews[dtID]) this.blockViews[dtID] = [];
				this.blockViews[dtID].push(blkVw);
				
				this.$blockArea.append(blkVw.draw());
				blockCounter++;
			}, this);
		}, this);

		var selType = this.dateSelector.cal.get("seltype");
		
		//show a message if there are no blocks
		if (blockCounter === 0) {
			this.$blockArea.append($("<div>", {class: "noblocks", html: "There are no blocks for this " + selType + "; click Add Block to add blocks of available time."}));
		}
		
		//if there are any blocks, show the print button
		$(".lnkPrint").toggle(blockCounter > 0);
		
		var idFormat = this.dateSelector.cal.get("dateIDFormat");
		if (!isNullOrBlank(selType)) {
			var strShowWhat = "Showing blocks for ";
			switch(selType) {
				case 'week': 
					strShowWhat += "week of " + moment(selDateIDs[0],idFormat).format(this.dateFormat[selType]) + " to " + moment(selDateIDs[selDateIDs.length-1],idFormat).format(this.dateFormat[selType]);
					break;
				case 'month':
					strShowWhat += "month of " + moment(selDateIDs[0],idFormat).format(this.dateFormat[selType]);
					break;
				case 'day':
					strShowWhat += moment(selDateIDs[0],idFormat).format(this.dateFormat[selType]);
					break;
			}
			this.$blockArea.prepend($("<div>", {class: "showingblockdesc", html: strShowWhat}));
		}
	
		$(".loadingmsg").hide();
	},
	emptyBlockDisplay: function() {
        this.$blockArea.empty();
        this.blocks = {};
        gApptViews = {};
	},
	loadUpdatedBlock: function(inBlocks) {
		this.loadingData = false;
		
		//update the attribute blocks data for this/these blocks
		_.each(inBlocks, function(blkArr, dateID) {
			this.blocks[dateID] = blkArr;
		}, this);
		
		//update data on calendar (expanded)
		this.dateSelector.createDateTextInfo(inBlocks);
	},
	loadData: function(inCalDates, inBlocks) {
		this.loadingData = false;
		
		//set data on dateSelector
		this.dateSelector.cal.setCalDateInfo(inCalDates);
		
		//set data in main area	
		this.emptyBlockDisplay();
		this.blocks = _.clone(inBlocks);
		
		this.dateSelector.cal.setSelection(); //this calls drawBlocks
		
		//update data on calendar (expanded)
		this.dateSelector.createDateTextInfo(this.blocks);
	},
	//called when the month on the date selector changes 
	//	OR when the displayed calendar selection changes
	getNewData: function() {
		this.loadingData = true;
		
		this.$blockArea.empty();
		$(".loadingmsg").show();
		
		var sdt = new Date(this.dateSelector.cal.get("datebegin"));
		var edt = new Date(this.dateSelector.cal.get("dateend"));
		if (gSelectedCalIDs.length)
			viewCalViewController.loadViewCalData({calIDs: gSelectedCalIDs, sdt: sdt, edt: edt});
		else
			this.dateSelector.cal.setSelection();
	},
	
	
	//AJAX responses for blocks
	getBlockViewByID: function(blockID) {
		var blockVw = null;
		_.each(this.blockViews, function(blkVwArray) {
			_.each(blkVwArray, function(blkVw) {
				if (blkVw.model.get("blockid") === blockID)
					blockVw = blkVw;
			});
		},this);
		return blockVw;
	},
	//lock/unlock block
	setBlockAvailability: function(blockID, avail) {
		var blkVw = this.getBlockViewByID(blockID);
		//update model
		blkVw.model.set("availability", avail);
		blkVw.showIsAvailable();
		
		//update calendar display
		if (gSelectedCalIDs.length)
			viewCalViewController.getUpdatedBlockInfoForDate(gSelectedCalIDs, blkVw.model.get("startdatetime"));
	},
	//sync block
	showBlockSync: function(blockID, iCal) {
		var blkVw = this.getBlockViewByID(blockID);
		blkVw.showSynced(iCal);
	},
	//delete block
	showBlockDelete: function(blockID) {
		this.dateSelector.saveSelected();
		
		//remove from expanded calendar
		var blkEl = this.dateSelector.cal.$cal.find("#blk"+blockID);
		if (blkEl)
			blkEl.remove();
			
		this.getNewData();
	},

	
	
	//AJAX responses for appointments
	getApptViewByID: function(apptID) {
		return gApptViews[apptID];
	},
	//sync appt
	showApptSync: function(apptID, iCal) {
		var apptVw = this.getApptViewByID(apptID);
		apptVw.showSynced(iCal);
	},

	//AJAX responses for slots
	getSlotView: function(blockID, sdt) {
		var blkView = this.getBlockViewByID(blockID);
		return blkView.slotViews[moment(sdt).format("YYYYMMDDTHHmm")];
	},
	//locked slot
	showSlotLocked: function(blockID, apptID, sdt, edt) {
		var slotVw = this.getSlotView(blockID, sdt);
		slotVw.showIsAvailable(apptID);
	},
	//unlocked slot
	showSlotUnlocked: function(apptID) {
		var slotVw = null;
		_.each(this.blockViews, function(blkVwArray) {
			_.each(blkVwArray, function(blkVw) {
				_.each(blkVw.slotViews, function(sltVw) {
					if (sltVw.apptID && sltVw.apptID === apptID)
						slotVw = sltVw;
				});
			});
		});
		slotVw.showIsAvailable(apptID);
	}

	
});

var viewcalview = new ViewCalendarView();
