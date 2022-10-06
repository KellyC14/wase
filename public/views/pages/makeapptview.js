var gCals = new Array();
var gBlocks = new Array();
var gShownCalID = 0;
var gShownBlockID = "";
var gShownSDT = "";
var gCurApptID = 0;
var gJustMadeApptID = 0;
var gIsOwnerOrManager = false;
var gShownCalViewID = "";
var gDisplayedDate = null;

var gBlockids = new Array();

/********************************************/
/* Block - on Make Appointment 				*/
/********************************************/
var gCurBlockID = null;
var gSerID = null;

//waitlist
var MAWaitlist = function(options) {
	this.calendarid = options.calendarid;
	this.calendartitle = options.calendartitle;
	this.labels = options.labels;
	this.isUserOnWaitlist = options.isUserOnWaitlist;
	_.bindAll(this, 'addToWaitlist', 'openStartDateCal', 'openEndDateCal', 'toggleWaitlist');
};
_.extend(MAWaitlist.prototype, {
	draw: function() {
		this.$el = $("<div>", {id: "divWaitlist"});
		
		//is the user on the waitlist?
		if (this.isUserOnWaitlist) {
			//user is on the waitlist, show message
			this.displayInfoMessage("You are on the wait list for calendar '" + this.calendartitle + "'");
		} else {
			//user is NOT on the waitlist, show prompt
			this.$el.append(
				this.$prompt = $("<div>", {class: "waitlistprompt"}).append(
					this.$btnShowWL = $("<a>", {class: "lnkShowWLForm", href: "#", "data-ajax": "false",
						title: "Add Me to the Wait List", html: "Add Me to the Wait List"})
				)
			);
			this.$btnShowWL.on("vmousedown", this.toggleWaitlist);
		}
		return this.$el;
	},
	
	showWaitlistMessage: function(inMsg) {
		$(".waitlistprompt").hide();
		$("#divWaitlistForm").hide();
		$(".wlmessagearea").html(inMsg);
		$(".wlmessagearea").show();	
	},

	drawWaitlistForm: function() {
		var $fields = $("<fieldset>");
		var $elForm = $("<div>", {id: 'divWaitlistForm'});
		$elForm.append(
			$("<form>", {id: 'wlinfo', method: 'post', action: '#', "data-ajax": "false"}).append($fields)
		);
		
		var usr = makeapptview.user;
		var userid = usr ? usr.get('userid') : '';
		var username = usr ? usr.get('name') : '';
		var phone = usr ? usr.get('phone') : '';
		var email = usr ? usr.get('email') : '';
		
		var appsName = this.labels.get("APPTHINGS");
		
		$fields.append($("<div>", {id: "divWLUserID", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLUserID", for: "txtWLUserID", html: "User ID:"}),
			$("<div>", {class: 'lineuptext', html: g_loggedInUserID})
		));
		
		$fields.append($("<div>", {id: "divWLUserName", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLUserName", class: "required wlfield", for: "txtWLUserName", html: "Name:"}),
			$("<input>", {type: 'text', name: 'txtWLUserName', id: 'txtWLUserName', placeholder: 'Enter User Name', title: 'User Name'}).val(username)
		));
		
		$fields.append($("<div>", {id: "divWLPhone", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLPhone", class: "wlfield", for: "txtWLPhone", html: "Phone:"}),
			$("<input>", {type: 'text', name: 'txtWLPhone', id: 'txtWLPhone', placeholder: 'Enter Phone', title: 'Phone'}).val(phone)
		));
		
		$fields.append($("<div>", {id: "divWLEmail", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLEmail", class: "required wlfield", for: "txtWLEmail", html: "E-mail:"}),
			$("<input>", {type: 'text', name: 'txtWLEmail', id: 'txtWLEmail', placeholder: 'Enter Email', title: 'Email'}).val(email)
		));
		
		$fields.append($("<div>", {id: "divWLTextEmail", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLTextEmail", class: "wlfield", for: "txtWLTextEmail", html: "Text Msg Address:"}),
			$("<div>", {class: 'lineuptext', id: 'divTextEmail'})).append(
				$("<a>", {class: 'txtmsgclick', href: '#', onclick: 'openMakeApptTxtMsg();', 'data-type': 'button', title: 'Enter text message email address', html: 'click to enter'})
			
		));
		
		$fields.append($("<div>", {id: "divWLSDT", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLStartDateTime", class: "required wlfield", for: "txtWLStartDateTime", html: "Available Starting On:"}),
			this.$startdate = $("<input>", {type: 'text', name: 'txtWLStartDateTime', id: 'txtWLStartDateTime', placeholder: 'Enter Available Start Date', title: 'Notify me when ' + appsName + ' become available starting on this date', class: 'waitstartdatetime'})
		));
		
		$fields.append($("<div>", {id: "divWLEDT", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLEndDateTime", class: "required wlfield", for: "txtWLEndDateTime", html: "Available Ending On:"}),
			this.$enddate = $("<input>", {type: 'text', name: 'txtWLEndDateTime', id: 'txtWLEndDateTime', placeholder: 'Enter Available End Date', title: 'Notify me when ' + appsName + ' become available ending on this date', class: 'waitenddatetime'})
		));
		
		$fields.append($("<div>", {id: "divWLMsg", class: "ui-field-contain"}).append(
			$("<label>", {id: "lblWLMsg", class: "wlfield", for: "txtWLMsg", html: "Message:"}),
			$("<input>", {type: 'text', name: 'txtWLMsg', id: 'txtWLMsg', placeholder: 'Enter a message', title: 'Message for Calendar Owner'})
		));
		
		$fields.append($("<div>", {id: 'divAddWLButton', class: 'ui-field-contain'}).append(
			this.$btnAdd = $("<input>", {type: 'button', name: 'btnAddToWL', id: 'btnAddToWL', value: 'Add to Wait List', "data-inline": 'true', class: 'wlfield'})
		));
		this.$btnAdd.button();
		this.$btnAdd.button("refresh");
		
		$fields.find("input[type=text]").textinput();
		$fields.find("input[type=text]").textinput("refresh");

		this.$startdate.on("vmousedown", this.openStartDateCal);
		this.$enddate.on("vmousedown", this.openEndDateCal);
		this.$btnAdd.on("vmousedown", this.addToWaitlist);

		return $elForm;
	},
	
	setUpWaitlist: function() {
        this.$waitListForm = this.drawWaitlistForm();
        this.$el.append(this.$waitListForm);
        this.$waitListForm.show();
	},
	toggleWaitlist: function(e) {
		if (!this.$waitListForm) {
		    if (!isNullOrBlank(g_loggedInUserID))
		        makeapptViewController.getUserInfo();
		    else
		        this.setUpWaitlist();
		} else
			this.$waitListForm.toggle();
	},
	
	addToWaitlist: function(e) {
		if (isValidForm("wlinfo")) { 
		    var txtmsgemail = $("#divTextEmail").text();
	        if (txtmsgemail === cTxtMsgNoneSelText) txtmsgemail = "";
             var vals = {
                 waitid: '',
                 calendarid: gShownCalID,
                 userid: g_loggedInUserID,
                 name: $("#txtWLUserName").val(),
                 phone: $("#txtWLPhone").val(),
                 email: $("#txtWLEmail").val(),
                 textemail: txtmsgemail,
                 msg: $("#txtWLMsg").val(),
                 startdatetime: moment($("#txtWLStartDateTime").val(),fmtDate).startOf('day').toDate(),
                 enddatetime: moment($("#txtWLEndDateTime").val(),fmtDate).endOf('day').toDate(),
                 whenadded: ''
             };
			makeapptViewController.addWaitlistEntry(new WaitlistEntry(vals));
		}
	},
	
	showAdded: function(inEntry) {
	    var usr = inEntry;
		this.displayInfoMessage(usr.get('name') + " (" + usr.get('userid') + ") has been added to the wait list for calendar '" + this.calendartitle + "'");
	},
	
	openCal: function(title, txtID) {
		$("#fld").val(txtID);
		$("#fldTitle").val(title);
		$("#popupCal").popup("open");
	},
	
	openStartDateCal: function(e) {
		this.openCal("Available Start Date", "txtWLStartDateTime");
	},
	
	openEndDateCal: function(e) {
		this.openCal("Available End Date", "txtWLEndDateTime");
	},
	
	hideInfoMessage: function() {
		if (this.$elWaitlistMsg)
			this.$elWaitlistMsg.hide();
	},
	displayInfoMessage: function(msg) {
		if (!this.$elWaitlistMsg)
			this.$el.append(this.$elWaitlistMsg = $("<div>", {class: "wlmessagearea message confirm clear"}));
		this.$elWaitlistMsg.html(msg).show();
		
		//hide the prompt and form
		if (this.$prompt) this.$prompt.hide();
		if (this.$waitListForm) this.$waitListForm.hide();
	},
	
	setTextMsgEmail: function(txtemail) {
		if (isNullOrBlank(txtemail)) txtemail = "<span class='italic'>(none selected)</span>";
		$("#search #divTextEmail").html(txtemail);
		$("#search .txtmsgclick").text("edit");
		
	},
	showIsValidTxtEmail: function(isValid,txtaddr) {
		if (!isValid) {
			alert("Note: This text message email address does not appear to be valid; please check to make sure you have the correct cell phone number and provider SMS gateway.");
		} else {
			this.setTextMsgEmail(txtaddr);
			$("#editTextMsgEmail").popup("close");
		}
	}

});
		
		
		
		
var MABlock = function(options) {
	this.model = options.model;
	this.slotViews = {}; //sdt: slotView
};
_.extend(MABlock.prototype, {
	drawSlots: function() {
		_.each(this.model.slots, function(slot) {
			var slotView = new MASlot({model: slot, block: this.model, calID: this.model.get("calendarid"), blockView: this});
			var sdtID = moment(slot.get("startdatetime")).format("YYYYMMDDTHHmm");
			this.$slots.append(slotView.draw());
			if (!this.slotViews[sdtID]) this.slotViews[sdtID] = slotView;
		}, this);
	},
	draw: function() {
		var canSeeAccessInfo = (gIsOwnerOrManager || this.model.get("blockowner")["userid"] === g_loggedInUserID);
		
		//if this block is not makeable and has no appts (hasappointments = 0), do not show
		if (!this.model.get("makeable")) // && !this.model.get("hasappointments"))
			return '';
			
		var blkid = this.model.get('blockid');
		var appName = this.model.get('labels')['APPTHING'];
		var appsName = this.model.get('labels')['APPTHINGS'];
		
		var cls = "blksection blk" + formatDateforServer(this.model.get('startdatetime')) + " blkid_" + blkid;
		if (!this.model.get('available'))
			cls += ' locked';
		
		var $blkVw = $("<div>", {class: cls});
		this.$el = $blkVw;
		
		var isSlotted = this.model.slots && this.model.slots.length > 1;
		
		//not available?
		if (!this.model.get('available')) {
			var $locked = $('<div>', {class: "lockedmsg", text: 'Locked'});
			$locked.prepend($('<a>', {href: "#", class: "ui-btn ui-btn-icon-notext ui-icon-lock ui-btn-inline", onclick: "return false;"}));
			$blkVw.append($locked);
		}
		//make access
		if (canSeeAccessInfo) {
			var $makeaccess = $("<div>", {class: "blockmakeaccess", html: 'Make Access: <b>' + this.model.get("makeaccess") + '</b>'});
			$blkVw.append($makeaccess);
		}
		
		//title
		var recurText = '';
		$blkVw.append($("<div>", {class: "blktitle", html: this.model.get("title")+recurText}));
        //blockowner
        var blockowner = this.model.get("blockowner").getDisplayName();
        $blkVw.append($("<div>", {class: "blockowner", html: "<b>" + appName + " with:</b> " + blockowner}));
		//location
		var loc = this.model.get("location") || "(no location)";
		$blkVw.append($("<div>", {class: "blocklocation", html: loc}));
		//description
		$blkVw.append($("<div>", {class: "blockdesc", html: this.model.get("description")}));
		//maxappts
		var maxAppts = this.model.get("maxapps");
		maxAppts = parseInt(maxAppts) > 0 ? maxAppts : "unlimited";
		var maxText = isSlotted ? "Max " + appsName + " per slot: " : "Max " + appsName + " per block: ";
		$blkVw.append($("<div>", {class: "maxappts", html: maxText + maxAppts}));		
		//maxper
		if (isSlotted) {
			var maxPer = this.model.get("maxper");
			maxPer = parseInt(maxPer) > 0 ? maxPer : "unlimited";
			$blkVw.append($("<div>", {class: "maxappts", html: "Max " + appsName + " per person: " + maxPer}));	
		}
		
		//deadlines
		var mStart = moment(this.model.get("startdatetime"));
		var strDeadlines = "";
		var op = this.model.get("opening");
        if (op > 0 && canSeeAccessInfo)
            strDeadlines += "<b>Available:</b> " + mStart.clone().subtract(op, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
        
		var dl = this.model.get("deadline");
		if (dl > 0)
			strDeadlines += "<b>Make Deadline:</b> " + mStart.subtract(dl, "minutes").format("M/D/YYYY [at] h:mmA") + "<BR>";
		
		var cdl = this.model.get("candeadline");
        if (cdl > 0 && canSeeAccessInfo)
            strDeadlines += "<b>Deadline to Cancel:</b> " + mStart.clone().subtract(cdl, "minutes").format("M/D/YYYY [at] h:mmA");
        
        if (strDeadlines.length) {
        	var $el = $("<div>", {html: strDeadlines});
			var $icon = $("<span>", {class: "ui-btn-icon-notext ui-icon-clock", style: "position: relative"});
            var $iconWrapper = $("<div>", {class: "iconWrapper"});
            $iconWrapper.append($icon);
            var $par = $("<div>", {class: "deadlines"});
            $par.append($iconWrapper, $el);
			$blkVw.append($par);
		}
		
		//slots
		this.$slotArea = $("<div>", {class: "blockslots"});
		$blkVw.append(this.$slotArea);
		this.$slots = $("<ul>", {id: "ulSlots"+blkid, "data-role": "listview", "data-mini": "true", "data-inset": "true"});
		this.$slotArea.append(this.$slots);
		this.$slots.listview();
		this.$slots.listview('refresh');

		this.drawSlots();
		//
		
		return $blkVw;
	}
});


/********************************************/
/* Slot - on Make Appointment 				*/
/********************************************/
var MASlot = function(options) {
	this.model = options.model;
	this.block = options.block;
	this.calID = options.calID;
	this.blockView = options.blockView;
	this.apptID = 0;

	_.bindAll(this, 'openMoreAdd', 'gotoEditAppt', 'gotoAddAppt', 'quickAddAppt');
};
_.extend(MASlot.prototype, {
	draw: function() {
		var blkid = this.block.get("blockid");
		//slot time
		var st = this.model.get("startdatetime");
		var et = this.model.get("enddatetime");
		//display time
		var strTime = moment(st).format(fmtTime) + ' - ' + moment(et).format(fmtTime);
		if (this.block.isMultiDate()) strTime += " (" + moment(st).format("M/D/YYYY") + ")";
		var $time = $("<div>", {class: 'slottimes', html: strTime});
		
		var $slot = $("<li>", {"data-role": "list-divider"});
		this.$el = $slot;
		
		var $link = this.drawButtons();
		
		//available?
		var $locked = '';
		if (!this.model.get("available")) {
			this.$el.addClass("locked");
			$locked = $("<div>",{class: "lockedmsg", text: "Locked"});
		}
		$slot.append($link, $locked, $time);

		//draw appointments
		$slot.append(this.drawAppointments());
		
		return $slot;
	},
	
	drawButtons: function() {
        var $link = '';
        var appName = this.block.get("labels")["APPTHING"];

        //can the user make a new appointment?
        if (this.model.get("makeable")) {
            var lnkCls = 'ui-btn ui-icon-plus ui-btn-icon-notext ui-corner-all';
            if (this.$addapptbtn)
                $link = this.$addapptbtn;
            else
                $link = $("<a>", {href: "#", "data-ajax": "false", class: lnkCls, title: 'New ' + capitalize(appName)});
            
            var handler = $.noop;
            if (isNullOrBlank(g_loggedInUserID) || gIsOwnerOrManager || this.block.get("blockowner")["userid"] === g_loggedInUserID) {
                //if guest, go to add appointment
                handler = this.gotoAddAppt;
            } else if (this.block.get('purreq')) {
                //request purpose
                handler = this.openMoreAdd;
            } else {
                //quick add the appointment
                handler = this.quickAddAppt;
            }
            $link.on("vmousedown", handler);
            this.$addapptbtn = $link;
            
        }
        return $link;
	},
	
	drawAppointments: function() {
		if (!this.$apptArea)
		    this.$apptArea = $("<div>",{class: "makeapptwith"});
		var $inner = '';
	    if (this.showDetails()) {
	    	$inner = this.drawApptDetails();
	    }
	    this.$apptArea.empty().append($inner, this.drawApptSummaryDetails());
	    return this.$apptArea;
	},
	
	showDetails: function() {
	    // var showAppInfo = false;
        var showAppInfo = this.model.get("showappinfo");
	    return !!(showAppInfo || gIsOwnerOrManager || this.model.appts.length);

	},
	
    drawControl: function(inName,inIcon,inText,inClass) {
        var $button = $("<div>",{class: "listbuttonouter"});
        $button.append('<a href="#" data-ajax="false" class="'+inName+'_btn '+inClass+' ui-btn ui-corner-all '+inIcon+' ui-btn-icon-notext noborder" title="'+inText+'">'+inText+'</a>');
        return $button;
    },
	drawApptDetails: function() {
        var $details = $("<div>",{class: "apptlist"});
       _.each(this.model.appts, function(appt) {
            var cls = "apptwitheach clearfix ";
            if (gJustMadeApptID === appt.get('appointmentid')) {
                cls += "highlightappt ";
                gJustMadeApptID = 0;
            }
            var $appt = $('<div>', {class: cls}).data({"apptid": appt.get('appointmentid')});
            
            //buttons - only if calendar owner, calendar manager, block owner, or appointment owner
    		var isBlockOwner = this.block.blockowner.userid === g_loggedInUserID;
    		var isMyAppt = IsThisUserLoggedIn(appt.get('apptmaker').userid);

    		if (gIsOwnerOrManager || isBlockOwner || isMyAppt) {
	            var txt = capitalize(this.block.get("labels")["APPTHING"]);
	            var $buttons = $('<div>', {class: "apptcontrols"});
	            
	            var $editBtn = this.drawControl('editappt','ui-icon-edit','Edit ' + txt,'');
	            $buttons.append($editBtn);
	            $editBtn.on("vmousedown",this.gotoEditAppt);
	                
	            var $deleteBtn = this.drawControl('deleteappt','ui-icon-delete','Cancel ' + txt,'');
	            $buttons.append($deleteBtn);
	            $appt.append($buttons);
            }
            
            var nm = appt.get('apptmaker').get('name');
            if (isNullOrBlank(nm)) nm = appt.get('apptmaker').get('userid');
            $appt.append($("<div>", {class: 'apptname', text: nm}));
            
            $details.append($appt);
            
    		//check for cancelation deadline
            if (typeof $deleteBtn !== 'undefined') {
            	$deleteBtn.on("vmousedown",_.bind(this.confirmDelete,appt));
	    		if (appt.get('candeadline') === 'reached') {
	    			if (!gIsOwnerOrManager) {
		            	$deleteBtn.addClass('pastdeadline');
		            	$deleteBtn.find('a').attr('title','Past Cancelation Deadline');
		            	$deleteBtn.on("vmousedown",$.noop);
	    			}
	    			var $msg = $('<div>', {class: "pastMsg", html: "**Cancel Deadline Reached**"});
	    			$details.append($msg).addClass("pastCanDeadline");
	            }
            }
        }, this);

       return $details;
	},
	
	drawApptSummaryDetails: function($parent) {
		var appsName = this.block.get('labels')['APPTHINGS'];
        var $nodetails = $("<div>",{class: "apptsummary"});
	    if ($parent && $parent.find('.apptsummary'))
	        $nodetails = $parent.find('.apptsummary');
        var numAppts = parseInt(this.model.get('numappts'),10);
        var numAvail = '';
            var maxAppts = parseInt(this.block.get("maxapps"),10);
        if (maxAppts === 0) {
            numAvail = 'unlimited'
        } else {
            numAvail = maxAppts - numAppts;
            if (numAvail < 0) numAvail = 0;
        }
        var strMsg = numAppts + ' ' + appsName + ' made, ' + numAvail + ' available';
        $nodetails.html(strMsg);
	    return $nodetails;
	},
	
	quickAddAppt: function() {
	    var appt = new Appointment({
            'blockid': this.model.get("blockid"),
            'calendarid': this.calID,
            'startdatetime': this.model.get("startdatetime"),
            'enddatetime': this.model.get("enddatetime"),
            'available': true,
            'apptmaker': new User({userid: g_loggedInUserID}),
            'madeby': isNullOrBlank(g_loggedInUserID) ? g_GuestEmail : g_loggedInUserID
        });
	    makeapptViewController.addAppt(appt);
	},
	openMoreAdd: function() {
	    quickApptInit(this.model, this.block.get('labels'), this.calID);
	    $("#popupQuickAppt").popup("open");
	},
	gotoAddAppt: function(e) {
	    goToPage('appt',{calid: this.calID, blockid: this.block.get("blockid"), sdt: formatDateTimeforServer(this.model.get("startdatetime"))});
	},
	gotoEditAppt: function(e) {
		var apptid = $(e.target).parents(".apptwitheach").data("apptid");
		window.location.href = 'appt.php?calid=' + this.calID
            + '&blockid=' + this.model.get("blockid")
            + '&apptid=' + apptid;
	},
	confirmDelete: function(e) {
	    //remove recently deleted, if any
	    makeapptview.removeAppt($(".apptwitheach.deletedappt"));
	    
		gCurApptID = this.get("appointmentid");
		gShownBlockID = this.get("blockid");
		gShownSDT = this.get("startdatetime");
		$("#popupCancelConfirm").popup("open");	
	}
});



/********************************************/
/* Calendar - on Make Appointment 			*/
/********************************************/
var MACalendar = function(options) {
	this.model = options.model;
	
	//get unique view ID
	var courseID = options.courseID ? options.courseID+"_" : "";
	var instructorID = options.instructorID ? options.instructorID+"_" : "";
	this.viewID = courseID + instructorID + options.model.get("calendarid");
	
	_.bindAll(this, 'expandCalendar');
	
	this.expandClass = "ui-icon-carat-r";
	this.unexpandClass = "ui-icon-carat-d";
	
	this.blockViews = {};
};
_.extend(MACalendar.prototype, {
	draw: function() {
		var $calVw = $("<div>", {class: "calsection group calid" + this.viewID, 
			"data-role": "collapsible", 
			"data-collapsed": "true", 
			"data-collapsed-icon": "carat-r",
		 	"data-expanded-icon": "carat-d"});
		this.$el = $calVw;
		
		var $header = $("<h3>");
		//title
		$header.append($("<span>", {class: 'calendartitle', html: this.model.get("title")}));
		//description
		$header.append($("<div>", {class: "collapsible-desc", html: this.model.get("description")}));
		//owner
		$header.append($("<div>", {class: "collapsible-owner", html: this.model.get('owner').getDisplayName()}));
		$calVw.append($header);
		
		$calVw.append(this.$elCalDisplay = $("<div>", {class: "caltext"}));
				
		$calVw.collapsible();
		$calVw.on("collapsibleexpand", this.expandCalendar);
		
		return $calVw;
	},
	
	expandCalendar: function(event, ui) {
		//Close the previously expanded calendar section, unless it is this section
		if (gShownCalViewID !== "" && gShownCalViewID !== this.viewID && $(".calid" + gShownCalViewID) !== null) {
			$(".calid" + gShownCalViewID).collapsible("collapse");
		}

		//Show a "loading" message
		this.$elCalDisplay.html("<div class='loadingmsg'>...Looking for Availability...</div>");
		showLoadingMsg(true);
					
		//Look up all blocks for this calendar ID, starting today
		gShownCalViewID = this.viewID;
		gShownCalID = this.model.get("calendarid");
		makeapptViewController.getBlockDates(this.model.get("calendarid"),gShownBlockID);
	},
	
	placeBlockDisplay: function(isSmallDisplay) {
		if (typeof isSmallDisplay === 'undefined') isSmallDisplay = true;
		
		var $elThisDate = null;
		
		var mDateOpened = gShownSDT ? moment(gShownSDT) : gDisplayedDate;
		gShownSDT = '';
		if (!isNullOrBlank(mDateOpened)) {
			this.$datelist.find(":data(startdate)").each(function(i, d) {
				if (moment($(this).data("startdate")).isSame(mDateOpened, 'day')) {
					$elThisDate = $(this);
				}
			});
		}
		
		if (isSmallDisplay) {
			this.$blockArea.insertAfter($elThisDate);
		} else {
			this.$blockArea.insertAfter(this.$datelist.parent());
		}
		
		if (!isNullOrBlank($elThisDate)) {
			$elThisDate.find("a").removeClass(this.expandClass).addClass(this.unexpandClass);
			$elThisDate.addClass("disp_on");
		}
	},
	
	showBlocks: function() {
		var appName = this.model.get("labels")["APPTHING"];
		this.$blockArea.empty().append(
			$("<div>", {class: "instructions", html: 'Select an available ' + appName + ' slot by clicking its "+" sign.'})
		);
		var mDateShown = null;
		_.each(gBlocks, function(blk) {
			if (isNullOrBlank(mDateShown))
				mDateShown = moment(blk.get("startdatetime"));
			var b = new MABlock({model: blk});
			this.$blockArea.append(b.draw());
			this.blockViews[blk.get('blockid')] = b;
		}, this);
		gDisplayedDate = mDateShown;
		this.$blockArea.find("ul").listview();
		this.$blockArea.find("ul").listview("refresh");
		
		this.placeBlockDisplay(window.innerWidth < 768);
		
		//reset
		gBlocks = [];
	},
	
	showBlockDates: function(dateObjs) {
		this.$elCalDisplay.append(
			$("<div>", {class: "blockssection"}).append(
				this.$datelist = $("<ul>", {id: 'ulBlocks', 'data-role': 'listview'})),
			this.$blockArea = $("<div>", {id: "divBlockDisplay"}),
			$("<div>", {class: "heightfix"})
		);
		this.$datelist.listview();
		var mPrevDate = null;
		//draw block dates
		_.each(dateObjs, function(blockdate) {
			var mDate = moment(blockdate.date);
			//check for month divider
			if (mPrevDate === null || mPrevDate.isBefore(mDate,"month")) {
			    //are any dates in this month makeable?
			    var anyMakeableOrMine = _(dateObjs).filter(function(obj) {
			        return moment(obj.date).isSame(mDate,"month") && (isTrue(obj.makeable) || isTrue(obj.hasappts));
			    });
			    if (anyMakeableOrMine.length)
			        this.$datelist.append($("<li>", {"data-role": "list-divider", class: "month-divider", html: mDate.format("MMMM, YYYY")}));
				mPrevDate = mDate.clone();
			}
			var showappinfo = isTrue(blockdate.showappinfo);
			var makeableOrMine = isTrue(blockdate.makeable) || isTrue(blockdate.hasappts);
			//if the user has no appts or can't make appts, don't show blockdate
			if (!makeableOrMine)
				return;
				
			//set up classes
			var cls = "expandable";
			var displayDate = mDate.format("ddd, MMMM DD, YYYY");
			var buttonCls = "ui-btn ui-btn-icon-notext ui-corner-all " + this.expandClass;
			var $btnExpandDate;
			this.$datelist.append(
				$btnExpandDate = $("<li>", {"data-role": "list-divider", class: cls}).append(
					$("<a>", {href: "#", "data-ajax": "false", class: buttonCls, title: "Show/hide blocks for date"}),
					"&nbsp;&nbsp;&nbsp;"+displayDate
				).data({"startdate": blockdate.date})
			);
			//set up expand
			var expCls = this.expandClass;
			var unexpCls = this.unexpandClass;
			$btnExpandDate.on("vmousedown", _.bind(function(e) {
				var $el = $(e.target);
				if ($el.hasClass("ui-btn"))
					$el = $el.parent(".expandable");
				var $link = $el.find("a");
				var sdt = moment($el.data("startdate")).hours(0).minutes(0).seconds(0);
				var edt = moment($el.data("startdate")).hours(23).minutes(59).seconds(59);

				this.$blockArea.empty();
				if ($el.hasClass("disp_on")) {
					//show un-expanded
					$link.removeClass(unexpCls).addClass(expCls);
					$el.removeClass("disp_on");
					
				} else {
					//get data
					var my_sdt=sdt.toDate();
					var my_edt=edt.toDate();
					if(gShownBlockID) {
						//console.log("call getBlocksByID");
						makeapptViewController.getBlocksByID(this.model.get("calendarid"),gShownBlockID);
					} else {
						//console.log("call getBlocksByDates");
						makeapptViewController.getBlocksByDates(this.model.get("calendarid"),sdt.toDate(),edt.toDate());
					}

					
					//un-expand other dates
					this.$datelist.find(".disp_on").each(function(id, el) {
						$(el).removeClass("disp_on");
						$(el).find("a").removeClass(unexpCls).addClass(expCls);
					});
					//show expanded
					$link.removeClass(expCls).addClass(unexpCls);
					$el.addClass("disp_on");
					
				}
			}, this));
		}, this);
		this.$datelist.listview('refresh');
		//expand the blockdate if there is only one
		if (dateObjs.length === 1) {
			this.$datelist.find("li.expandable").trigger("vmousedown");
			gShownBlockID = "";
		}

		//if there is a "shown block", show it - use gShownSDT
		if (!isNullOrBlank(gShownSDT)) {
			var mStart = moment(gShownSDT);
			var mEnd = moment(gShownSDT).hours(23).minutes(59).seconds(59);
			makeapptViewController.getBlocksByDates(this.model.get("calendarid"),mStart.toISOString(),mEnd.toISOString());
		}
			
	},
	
	//response from getBlockDates
	showCalendar: function(dateObjs, isUserOnWaitlist) {
		this.$elCalDisplay.empty();
		
		var anyMakeableOrMine = _(dateObjs).filter(function(obj) {
	        return obj.makeable || obj.hasappts;
	    });
		
		if (!dateObjs.length || !anyMakeableOrMine.length) {
            var apptTxt = this.model.get('labels')['APPTHINGS'] || g_ApptsText;
			this.$elCalDisplay.append(
				$("<div>", {class: "nonefoundmsg", html: "No " + apptTxt + " available"})
			);
		} else {
			this.showBlockDates(dateObjs);	
		}
		
		//if there is a waitlist for this calendar, draw waitlist area
		if (gIsWaitlistOn && this.model.get('waitlist')) {
			this.waitlist = new MAWaitlist({calendarid: this.model.get("calendarid"), calendartitle: this.model.get("title"), labels: this.model.get("labels"), isUserOnWaitlist: isUserOnWaitlist});
			this.$elCalDisplay.append(this.waitlist.draw());
		}
	}
});





/********************************************/
/* Make Appt View							*/
/********************************************/
var MakeApptView = function() {
	this.pagetitle = "Make an " + capitalize(g_ApptText);
	this.user = null;
	_.bindAll(this, 'getCourses', 'checkSearch', 'checkSearchEnter');
};

_.extend(MakeApptView.prototype, {
	//initialize the page
	setPageVars: function(inCalID,inBlockID,inSDT) {
		$("#pagetitle").text(this.pagetitle);	
		
		//bind search events
		$("#txtSearch").textinput();
		$("#txtSearch").focus();
		$('#txtSearch').on("keypress", this.checkSearchEnter);
        $('#txtSearch').on("input", this.checkSearch);
		$('.ui-input-clear').on('tap', this.clearResults);
		
		//do direct URL searches, if any
		if (!isNullOrBlank(inCalID)) {
			this.clearResults();
			this.showLoadingMessage();
			gShownBlockID = inBlockID;
			gShownCalID = inCalID;
			gShownCalViewID = inCalID+"";
			gShownSDT = inSDT;
			makeapptViewController.searchCalendarsByCalID(inCalID);
		}
		
		//set up vars
		this.calendarViews = {};
	},
	
	confirmUserStatus: function(isowner,ismanager,ismember,userid,hasappts) {
		//put message for users who are not owner/manager/members AND not guests
	    if (!(isowner || ismanager || ismember) && isNullOrBlank(getGuestUserID())) {
	    	var txt = '';
	    	if (!hasappts) {
	    		txt = 'It looks like this may be your first use of WASE. If you are here to make an ' + g_ApptText + ', '
		    		+ 'follow the instructions below. ';
	    	}
	    	txt += 'This page is used to make ' + g_ApptsText + ' with people who have a calendar in the system. '
				+ 'If you instead want to make yourself (or something/someone) available for ' + g_ApptsText + ', '
	    		+ 'click on the Calendars tab.';
	        gMessage.displayHelp(txt);
	    }
	},
	
	//parameter callbacks
	setParmNetID: function() {
		$("#txtSearch").attr("placeholder",gParms["NETID"].toLowerCase()+", name, or calendar title");
		$("#txtSearch").textinput();
		$("#search > .instructions").text("Search for a calendar by "+gParms["NETID"].toLowerCase()+", name, or calendar title to make an " + g_ApptText + ".");
	},
	
	setCourseCalParmVal: function(inVal) {
		if (isTrue(inVal) && !isNullOrBlank(g_loggedInUserID)) {
			var $lnk = $('<a>',{id: 'linkCourseCals', href: '#', html: 'find my course instructor calendars.'});
			$lnk.on("vmousedown", this.getCourses);
			$(".coursecalsearch").append('OR ', $lnk).show();
		} else {
			$(".coursecalsearch").hide();		
		}
	},
	
	//search
    checkSearchEnter: function(e) {
        if (e.keyCode === 13) {
            //user clicked 'enter', do the search
            this.doSearch();
            return false;
        }
    },
    checkSearch: function(e) {
        //auto-search
        var val = $(e.target).val();
        if (val === null ||  $.trim(val).length < 3 ) {
            this.clearResults();
        }
        else {
            this.doSearch();
        }
	},
	doSearch: function() {
	    var val = $("#txtSearch").val();
	    if (val.length < 3) { //if less than 2 char, show error.
	    	gMessage.displayError("Please enter at least 3 characters to search.");
	    } else {
			this.clearResults();
			this.showLoadingMessage();
			makeapptViewController.searchCalendarsByString(val);
	    }
	},
	
	getCourses: function(e) {
		this.clearResults();
		this.showLoadingMessage('...Loading: Please be patient...');
		makeapptViewController.getCourseCals();
		return false;
	},
	
	clearResults: function() {
		//Remove calendar section from previous search
		if ($(".calsection")) {
			$(".calsection").remove();
		}
		$('#divResults').empty();
		this.calendarViews = {};
		gShownCalID = 0;
		gShownCalViewID = "";
	},
	showLoadingMessage: function(inMsg) {
		var msg = inMsg || "...Loading...";
		$('#divResults').html('<hr><div class="loadingmsg">' + msg + '</div>');
		showLoadingMsg(true);
	},
	
	loadCalendars: function() {
		var $elCalendars = $("<div>",{id: 'divCalendars', class: 'divCalendars'});
		$("#divResults").empty().append($("<hr>"), $elCalendars);
		
		//no calendars found
		if (!gCals.length) {
			var $elNone = $("<div>", {class: "nonefoundmsg", html: "No calendars found"});
			$elCalendars.append($elNone);
		}
		
		//draw each calendar
		var that = this;
		_.each(gCals, function(cal) {
			var calVw = new MACalendar({model: cal});
			that.calendarViews[cal.get('calendarid')] = calVw;
			$elCalendars.append(calVw.draw());
		}, this);
							
		//if there is a "shown calendar", show it
		if (!isNullOrBlank(gShownCalViewID))
			that.calendarViews[gShownCalViewID].$el.collapsible("expand");

	},
	
	loadCourses: function(inCourses) {
		var $elCourses = $("<div>",{id: 'divCourses', class: 'divCalendars'});
		$("#divResults").empty().append($("<hr>"), $elCourses);
		//no courses found
		if (!inCourses.length) {
			var $elNone = $("<div>", {class: "nonefoundmsg coursemsg", html: "No courses found"});
			$elCourses.append($elNone);
		}
		
		//draw the courses
		var that = this;
		_.each(inCourses, function(course) {
			var $course = $("<div>", {class: 'course'});
			var courseID = course.get('courseid');
			
			//course info
			var $courseinfo = $("<div>", {class: 'courseinfo'});
			$courseinfo.append(
				$("<span>", {class: 'courseid', html: courseID}),
				$("<span>", {class: 'coursetitle', html: course.get('title')})
			);
			$course.append($courseinfo);
			
			//instructors
			if (course.anyInstructorCals()) {
				var $instrList = $("<ul>", {class: 'instructorlist'});
				_.each(course.instructors, function(instructor) {
					var instrID = instructor.get('userid');
					
					var $li = $("<li>", {class: 'instructor'});
					$li.append($("<span>", {class: 'instructorname', html: instructor.get('name') + " (" + instrID + ")"}));
					
					//draw instructor calendars
					if (instructor.get('calendars').length) {
						_.each(instructor.get('calendars'), function(cal) {
							var $instrCals = $("<div>", {class: 'instructorcals'});
							var calVw = new MACalendar({model: cal, courseID: courseID, instructorID: instrID});	
							that.calendarViews[courseID+"_"+instrID+"_"+cal.get('calendarid')] = calVw;
							$instrCals.append(calVw.draw());
							$li.append($instrCals);
						}, this);
					} else {
						//none found
						$li.append($("<div>", {class: "noinstructorcalmsg", html: "No calendar"}));
					}
					$instrList.append($li);
				});
				$course.append($instrList);
			} else {
				$course.append($("<div>", {class: 'nocoursecalmsg', html: 'No calendars currently associated with this course'}));
			}
			$elCourses.append($course);
		});
	},
	
	loadBlocks: function(calID) {
		var id = gShownCalViewID !== "" ? gShownCalViewID : calID;
		this.calendarViews[id].showBlocks();
	},
	showCalendarSection: function(calID, dateObjs, isUserOnWaitlist) {
		var id = gShownCalViewID !== "" ? gShownCalViewID : calID;
		this.calendarViews[id].showCalendar(dateObjs, isUserOnWaitlist);
	},
	
	showCalendarSection_blockids: function(calID, dateObjs, isUserOnWaitlist,blockids) {
		var id = gShownCalViewID !== "" ? gShownCalViewID : calID;
		if(blockids.length) {
			//console.log('load blockids to gblockids');
			gBlockids = blockids;
		}
		this.calendarViews[id].showCalendar(dateObjs, isUserOnWaitlist);
	},

	showWaitlistAdded: function(inWLEntry) {
		var id = gShownCalViewID !== "" ? gShownCalViewID : inWLEntry.get('calendarid')+'';
	    var wl = this.calendarViews[id].waitlist;
	    wl.toggleWaitlist();
		wl.showAdded(inWLEntry);
	},
	
	setHighlightedApptID: function(inApptID) {
		gJustMadeApptID = inApptID;
	},
	
    setTextMsgEmail: function(txtemail) {
        if (isNullOrBlank(txtemail)) txtemail = "<span class='italic'>(none selected)</span>";
        $("#search #divTextEmail").html(txtemail);
        $("#search .txtmsgclick").text("edit");
        
    },
    showIsValidTxtEmail: function(isValid,txtaddr) {
        if (!isValid) {
            alert("Note: This text message email address does not appear to be valid; please check to make sure you have the correct cell phone number and provider SMS gateway.");
        } else {
            this.setTextMsgEmail(txtaddr);
            $("#editTextMsgEmail").popup("close");
        }
    }, 
	setUserStatus: function(isowner,ismanager,ismember) {
		gIsOwnerOrManager = isowner || ismanager;
	},
	
	removeAppt: function($appt) {
	    //remove from the DOM
	    $appt.fadeOut();
        gCurApptID = 0;
	},
	showDeletedAppt: function() {
        var sltVw = this.calendarViews[gShownCalViewID].blockViews[gShownBlockID].slotViews[moment(gShownSDT).format("YYYYMMDDTHHmm")];
        var appName = '';
        
        //remove the appt to the model
        sltVw.model.appts = $.grep(sltVw.model.appts, function(appt) {
        	var isDelAppt = appt.get('appointmentid') === gCurApptID;
        	if (isDelAppt)
        		appName = appt.get('labels')['APPTHING'];
            return !isDelAppt;
        }); 
        //show 'deleted' in the DOM
        var maVw = this;
	    sltVw.$apptArea.find(":data(apptid)").each(function() {
	        var $appt = $(this);
	        if ($appt.data("apptid") === gCurApptID) {
	            $appt.addClass('deletedappt');
	            $appt.find('.apptname').html(capitalize(appName) + ' Deleted');
	            $appt.find('.apptcontrols').html($("<div>",{class:"btnCloseDeleted", text: 'x'}));
	            $appt.find('.btnCloseDeleted').on("vmousedown", _.bind(maVw.removeAppt,maVw,$appt));
	            
	            //decrement the numappts
	            sltVw.model.set('numappts', parseInt(sltVw.model.get('numappts'),10)-1);
	            sltVw.drawApptSummaryDetails(sltVw.$apptArea);
	            
	            //if cancel deadline message, remove it
	            var $par = $appt.parents(".pastCanDeadline");
	            if ($par.length) {
	            	$par.find(".pastMsg").remove();
	            }
	        }
	    });
	    
	    //check makeable for add btn
	    sltVw.drawButtons();
	    
	    //reset
        gShownBlockID = 0;
        gShownSDT = '';
	},
    showAddedAppt: function(appt) {
        //if any have been deleted, remove the 'deleted' area
        makeapptview.removeAppt($(".apptwitheach.deletedappt"));

        var id = gShownCalViewID !== "" ? gShownCalViewID : appt.get('calendarid');
        var sltVw = this.calendarViews[id].blockViews[appt.get('blockid')].slotViews[moment(appt.get('startdatetime')).format("YYYYMMDDTHHmm")];
        //add the appt to the model
        if (!sltVw.model.appts || !sltVw.model.appts.length) sltVw.model.appts = [];
        sltVw.model.appts.push(appt);
        
        //unhighlight old and highlight new
        $(".apptwitheach.highlightappt").removeClass("highlightappt");
        gJustMadeApptID = appt.get('appointmentid');
        
        //increment the numappts
        sltVw.model.set('numappts', parseInt(sltVw.model.get('numappts'),10)+1);
        //draw the appointments for the slot
        sltVw.drawAppointments();

        //check makeable for add btn
        sltVw.drawButtons();
},

	
});

var makeapptview = new MakeApptView();
