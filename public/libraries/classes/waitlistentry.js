var gCurWaitEntryID = 0;
var gWaitlist = new Array();
var gIsWaitlistOn = true;
var gIsOwnerView = false;

/*
 * WaitlistEntry
 */
var WaitlistEntry = function(options) {
    WASEObject.call(this,options);
    this.xmlWrapper = 'waitlistentry';
};
WaitlistEntry.prototype = Object.create(WASEObject.prototype);
WaitlistEntry.prototype.constructor = WaitlistEntry;
_.extend(WaitlistEntry.prototype, {
    defaults: {
        waitid: '',
        calendarid: '',
        user: '',
        textemail: '',
        msg: '',
        startdatetime: '',
        enddatetime: '',
        whenadded: ''
    },
    getWhenAdded: function() {
        var dt = formatDateTimeFromServer(this.whenadded);
        return moment(dt).format(fmtDate + " " + fmtTime);
    },
    setFromXML: function($xml) {
        this.set('waitid',this.getTagText($xml,'waitid'));
        this.set('calendarid',this.getTagText($xml,'calendarid'));
        //this.set('user',new User($xml.children('user')));
        this.set('userid',this.getTagText($xml,'userid'));
        this.set('name',this.getTagText($xml,'name'));
        this.set('phone',this.getTagText($xml,'phone'));
        this.set('email',this.getTagText($xml,'email'));
        
        this.set('textemail',this.getTagText($xml,'textemail'));
        this.set('msg',this.getTagText($xml,'msg'));
        this.set('startdatetime',formatDateTimeFromServer(this.getTagText($xml,'startdatetime')));
        this.set('enddatetime',formatDateTimeFromServer(this.getTagText($xml,'enddatetime')));
        this.set('whenadded',this.getTagText($xml,'whenadded'));
    }
});



var WaitlistEntryView = function(options) {
    BaseView.call(this,options);
    this.init(options);
};
WaitlistEntryView.prototype = Object.create(BaseView.prototype);
WaitlistEntryView.prototype.constructor = WaitlistEntryView;
_.extend(WaitlistEntryView.prototype, {
    init: function(options){
        options = options || {};
        this.calOwner = !!(options.calOwner) || false;
        this.drawList();
    },
    drawList: function() {
        this.$waitList = $("<table>", {class: "waitlisttable", 'data-mode': 'reflow', 'data-role': 'table', id: 'tblWaitlist', cellspacing: 0, cellpadding: 0, border: 0});
        this.$headerRow = $("<thead><tr></tr></thead>");
        if (this.calOwner)
            this.$headerRow.append($('<th>Person</th>'));
        else
            this.$headerRow.append($('<th>Calendar</th>'));
        this.$headerRow.append(
            $('<th>Available Starting</th>'),
            $('<th data-priority="1" class="ui-table-priority-1">Available Ending</th>'),
            $('<th data-priority="3" class="ui-table-priority-3">Added On</th>'),
            $('<th data-priority="2" class="ui-table-priority-2">Message</th>'),
            $('<th>&nbsp;</th>')
        );
        this.$waitList.append(this.$headerRow);
        
        this.$body = $("<tbody></tbody>");
        this.$waitList.append(this.$body);
        
        //jquery mobile init
        this.$waitList.trigger("create");
        this.$waitList.table();
        this.$waitList.table("refresh");

        //set up 'no entries' row
        this.$noEntriesRow = $('<tr>',{id: 'trWait_none', class: 'waitrow'}).append($('<td>',{class: 'wlnonemsg', colspan: 6, text: 'No wait list entries'}));

        //if calendar owner, set up 'add' row
        if (this.calOwner) {
            this.$addBtn = $("<a>", {class: "lnkAddWaiter", href: '#', 'data-ajax': false, title: 'Add Person to Wait List', text: 'Add'});
            
            this.$addRow = $('<tr class="addwaiterrow"></tr>');
            this.$addRow.append(
                $('<td>Add Wait List entry:<br><input type="text" name="txtNewWLUserID" id="txtNewWLUserID" placeholder="Enter User ID" title="Add Wait List User ID" class="textwluserid" /></td>'),
                $('<td></td>').append(this.$addStart = $('<input>', {type: "text", name:"txtNewWLStartDate", id:"txtNewWLStartDate", placeholder:"Enter Available Start", title:"Enter when you are available, starting...", class:"textwaitstartdate"})),
                $('<td class="ui-table-priority-1"></td>').append(this.$addEnd = $('<input>',{type:"text", name:"txtNewWLEndDate", id:"txtNewWLEndDate", placeholder:"Enter Available End", title:"Enter when you are available, ending...", class:"textwaitenddate"})),
                $('<td class="ui-table-priority-3">&nbsp;</td>'),
                $('<td class="ui-table-priority-2"><input type="text" name="txtNewWLMsg" id="txtNewWLMsg" placeholder="Enter a message" title="Enter Wait List Message" class="textwlmsg" /></td>'),
                $('<td></td>').append(this.$addBtn)
            );
            this.$waitList.append($('<tfoot></tfoot>').append(this.$addRow));
            this.initDates();
            this.$addBtn.on("click",function() {
                var userid = $("#txtNewWLUserID").val();
                var sdt = $("#txtNewWLStartDate").val();
                var edt = $("#txtNewWLEndDate").val();
                
                var validateReqd = '';
                if (userid === '') validateReqd += ' userid';
                if (sdt === '') validateReqd += ' startdate';
                if (edt === '') validateReqd += ' enddate';
                if (validateReqd !== '') {
                    validateReqd = 'The following fields are required for waitlist entry:'+validateReqd;
                    gMessage.displayError(validateReqd);
                    return false;
                }
                
                var validateDates = '';
                var mStart = moment(sdt,fmtDate);
                var mEnd = moment(edt,fmtDate);
                if (!mStart.isValid()) validateDates += ' startdate';
                if (!mEnd.isValid()) validateDates += ' enddate';
                if (validateDates !== '') {
                    validateDates = 'The following dates are invalid:'+validateDates;
                    gMessage.displayError(validateDates);
                    return false;
                }
                
                var calid = calendarconfigview.calid;
                if (!isNullOrBlank(calid)) { //calendar already exists, add waiters immediately
                    var vals = {
                        waitid: '',
                        calendarid: calid,
                        userid: userid,
                        textemail: '',
                        msg: $("#txtNewWLMsg").val(),
                        startdatetime: mStart.startOf('day').toDate(),
                        enddatetime: mEnd.endOf('day').toDate(),
                        whenadded: ''
                    };
                    waitlistController.addWaitlistEntry(new WaitlistEntry(vals));
                }
            });            
        }
    },
    initDates: function() {
        if (isMediumScreen() || isLargeScreen()) {
            this.$addStart.on("click focus",function() {
                $("#fld").val($(this).attr("id"));
                $("#fldTitle").val("Available Start Date");
                $("#popupCal").popup("open");
            });
            this.$addEnd.on("click focus",function() {
                $("#fld").val($(this).attr("id"));
                $("#fldTitle").val("Available End Date");
                $("#popupCal").popup("open");
            });
        } else {
            this.$addStart.mobiscroll().datetime({
                theme: 'jqm',
                display: 'modal',
                mode: 'clickpick',
                dateOrder: 'D ddMyy', //'MD ddyy',
                minDate: new Date()
            });   
            this.$addEnd.mobiscroll().datetime({
                theme: 'jqm',
                display: 'modal',
                mode: 'clickpick',
                dateOrder: 'D ddMyy', //'MD ddyy',
                minDate: new Date()
            });
        }
    },
    editEntry: function(waitid) {
        //get the entry
        var wle = WaitlistEntry.findEntryInWaitlist(waitid);
        
        //for the given row, change all of the fields to text fields.
        var $row = $("#trWait_" + waitid);

        //startdatetime
        var $sdt = $('<input>',{type: 'text', class: 'textwaitstartdate', id: 'sdtEdit'+waitid, placeholder: 'Enter Available Start', title: 'Wait List Start Date', value: moment(wle.get('startdatetime')).format(fmtDate)});
        $row.find(".wlstarttime").html($sdt);
        
        //enddatetime
        var $edt = $('<input>',{type: 'text', class: 'textwaitenddate', id: 'edtEdit'+waitid, placeholder: 'Enter Available End', title: 'Wait List End Date', value: moment(wle.get('enddatetime')).format(fmtDate)});
        $row.find(".wlendtime").html($edt);
        
        //msg
        var $msg = $('<input>',{type: 'text', class: 'textwlmsg', placeholder: 'Enter a message', title: 'Wait List Message', value: wle.get('msg')});
        $row.find(".wlmsg").html($msg);
        
        //buttons
        $row.find(".lnkEditWaiter").hide();
        $row.find(".lnkSaveWaiter").show();        
        
        //event set up
        $(".textwaitstartdate").on("click focus",function() {
            $("#fld").val($(this).attr("id"));
            $("#fldTitle").val("Available Start Date");
            $("#popupCal").popup("open");
        });
        $(".textwaitenddate").on("click focus",function() {
            $("#fld").val($(this).attr("id"));
            $("#fldTitle").val("Available End Date");
            $("#popupCal").popup("open");
        });
    },
    showEditedEntry: function(entry) {
        var waitid = entry.get('waitid');
        var wle = WaitlistEntry.findEntryInWaitlist(waitid);
        var $row = $("#trWait_" + waitid);

        //for the given row, change all of the fields to text fields.
        $row.find(".wlstarttime").html(moment(wle.get('startdatetime')).format(fmtDate));
        $row.find(".wlendtime").html(moment(wle.get('enddatetime')).format(fmtDate));
        $row.find(".wlmsg").html(wle.get('msg'));

        $row.find(".lnkEditWaiter").show();
        $row.find(".lnkSaveWaiter").hide();
    },
    saveEntry: function(waitid) {
        var wlentry = WaitlistEntry.findEntryInWaitlist(waitid);
        var $row = $("#trWait_" + waitid);

        wlentry.set('startdatetime',moment($row.find(".textwaitstartdate").val(),fmtDate).startOf("day").toDate());
        wlentry.set('enddatetime',moment($row.find(".textwaitenddate").val(),fmtDate).endOf("day").toDate());
        wlentry.set('msg',$row.find(".textwlmsg").val());
        
        waitlistController.editWaitlistEntry(wlentry);
    },
    showRemovedEntry: function(entry) {
        var waitid = entry.get('waitid');
        
        //remove from the array
        gWaitlist = $.grep(gWaitlist, function(wle) {
            return wle.get('waitid') !== waitid;
        }); 
        //remove from DOM
        $("#trWait_" + waitid).remove();
        //check for no entries
        if (!gWaitlist.length) {
            this.$body.append(this.$noEntriesRow);
        }
    },
    removeEntry: function(wait) {
        gCurWaitEntryID = wait;
        $("#ccObject").val("entry");
        $("#popupCancelConfirm").popup("open"); 
        
    },
    drawEntry: function(entry) {
        gWaitlist.push(entry);
        var waitid = entry.get('waitid');

        var $row = $('<tr id="trWait_' + waitid + '" class="waitrow"></tr>');
        if (this.calOwner) {
            var usr = entry;
            var strEmail = '';
            if (!isNullOrBlank(usr.get('name')) && !isNullOrBlank(usr.get('email'))) {
                strEmail = ' (<a href="mailto:' + usr.get('email') + '?subject=Wait List">' + usr.get('name') + '</a>) ';
            }
            $row.append($('<td class="wlwho"><b class="ui-table-cell-label">Person</b>' + usr.get('userid') + strEmail + '</td>'));
        } else {
            //calendar drawn later
            $row.append($('<td class="wlcal"><b class="ui-table-cell-label">Calendar</b></td>'));
        }
        //buttons
        var commonClasses = 'ui-btn ui-corner-all ui-btn-icon-notext noborder';
        var $editBtn = $('<a>',{href: '#', class: 'lnkEditWaiter ui-icon-edit '+commonClasses, title: 'Edit Wait List Entry', 'data-ajax': false, text: 'Update Entry'});
        var $saveBtn = $('<a>',{href: '#', class: 'lnkSaveWaiter ui-icon-save '+commonClasses, title: 'Save Wait List Entry', 'data-ajax': false, text: 'Save Entry'});
        var $deleteBtn = $('<a>',{href: '#', class: 'ui-icon-trash '+commonClasses, title: 'Remove Wait List Entry', 'data-ajax': false, text: 'Remove Entry'});
        
        //initial hide/show
        $saveBtn.hide();
        
        //event handling
        $editBtn.on('click',_.bind(function(waitid) {
            this.editEntry(waitid);
        },this,waitid));
        $saveBtn.on('click',_.bind(function(waitid) {
            this.saveEntry(waitid);
        },this,waitid));
        $deleteBtn.on('click',_.bind(function(waitid) {
            this.removeEntry(waitid);
        },this,waitid));
        
        $row.append(
            $('<td class="wlstarttime"><b class="ui-table-cell-label">Available Starting</b>' + moment(entry.get('startdatetime')).format(fmtDate) + '</td>'),
            $('<td class="wlendtime ui-table-priority-1"><b class="ui-table-cell-label">Available Ending</b>' + moment(entry.get('enddatetime')).format(fmtDate) + '</td>'),
            $('<td class="wladded ui-table-priority-3"><b class="ui-table-cell-label">Added On</b>' + entry.getWhenAdded() + '</td>'),
            $('<td class="wlmsg ui-table-priority-2"><b class="ui-table-cell-label">Message</b>' + entry.get('msg') + '</td>'),
            $('<td class="wlbtns"></td>').append($editBtn, $saveBtn, $deleteBtn)
        );
        
        //show the header
        //this.$headerRow.show();
        
        this.$body.append($row);
    },
    //callback from adding new entry
    addEntry: function(entry) {
        //if showing 'no entries', remove message
        if (!gWaitlist.length) {
            this.$body.empty();
        }
        this.drawEntry(entry);
        $("#divWaitlist .ui-li-count").text(gWaitlist.length);
        //reset the add row
        this.$addRow.find("input").val('');
    },
    drawEntries: function(entries) {
        gWaitlist = [];
        this.$body.empty();
        if (!entries.length) {
            this.$body.append(this.$noEntriesRow);
            //this.$headerRow.hide();
        } 
        _.each(entries, function(entry) {
            this.drawEntry(entry);
        }, this);
        $("#divWaitlist .ui-li-count").text(gWaitlist.length);
    },
    
});

WaitlistEntry.findEntryInWaitlist = function(inWaitID) {
	for (var i=0; i < gWaitlist.length; i++) {
		if (gWaitlist[i].get('waitid') === inWaitID)
			return gWaitlist[i];
	}
};
WaitlistEntry.findEntryInWaitlistByCalID = function(inCalID) {
	for (var i=0; i < gWaitlist.length; i++) {
		if (gWaitlist[i].get('calendarid') === inCalID)
			return gWaitlist[i];
	}
};
WaitlistEntry.loadCalendarInfo = function(inCals) {
	for (var i=0; i < inCals.length; i++) {
		var entry = WaitlistEntry.findEntryInWaitlistByCalID(inCals[i].get('calendarid'));
		var appText = inCals[i].get('labels')['APPTHING'];
		if (!isNullOrBlank(entry)) {
			$("#trWait_" + entry.get('waitid') + " td.wlcal").html('<a href="makeappt.php?calid=' + inCals[i].get('calendarid') + '" data-ajax="false" title="New ' + appText + '">' + inCals[i].get('title') + '</a>');
		}
	}
};