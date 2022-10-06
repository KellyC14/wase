var gBlock = null;
var gWASECal = null;
var gIsAvailable = true; //default to true
var gOrigBlock = null;

/********************************************/
/*    Date and Time selectors               */
/********************************************/
function buildMobiscrollDate(selector) {
    $(selector).mobiscroll().date({
        theme: 'jqm',
        display: 'modal',
        mode: 'scroller',
        dateOrder: 'D ddMyy',
        minDate: new Date(2009,0,1)
    });
}
function setMobiscrollMaxDate(selector,sd) {
    //set max date
    if ($(selector).mobiscroll) {
        $(selector).mobiscroll("option","maxDate",sd);
    }
}
function buildMobiscrollTime(selector) {
    $(selector).mobiscroll().time({
        theme: 'jqm',
        display: 'modal',
        mode: 'scroller',
        stepMinute: 1
    });
}
function openDateSelector() {
    var vals = {
        txtStartDateSeries: "Series Start Date",
        txtEndDateSeries: "Series End Date"
    };
    var fld = $(this).attr("id");
    $("#fld").val(fld);

    var header = vals[fld] || $(this).attr("title") || "Date";
    $("#fldTitle").val(header);
    $("#popupCal").popup("open");
}
var arrTimeSelectors = [];
function openTimeSelector(e) {
    var fld = $(this).attr("id");
    if (!arrTimeSelectors[fld]) {
        arrTimeSelectors[fld] = new TimeSelector(fld);
    }
    arrTimeSelectors[fld].openSelector(this);
    e.stopPropagation();
    return false;
}
function buildDatePicker(inSelector) {
    if (isMediumScreen() || isLargeScreen()) {
        $(inSelector).on("click", openDateSelector);
    } else {
        buildMobiscrollDate(inSelector);
    }
}
function buildTimePicker(inSelector) {
    if (isMediumScreen() || isLargeScreen()) {
        $(inSelector).on("click", openTimeSelector);
    } else {
        buildMobiscrollTime(inSelector);
    }
}

function getDurationDisplay(d) {
    var str = "";
    if (d.days() > 0) {
        str += d.days();
        if (d.days() > 1) str += " days ";
        else str += " day ";
    }
    if (d.hours() > 0) {
        str += d.hours();
        if (d.hours() > 1) str += " hours ";
        else str += " hour ";
    }
    if (d.minutes() > 0) {
        str += d.minutes();
        if (d.minutes() > 1) str += " minutes ";
        else str += " minute ";
    }
    return str;
}
function getDeadlineDurationDisplay(d) {
    var str = getDurationDisplay(d);
    if (isNullOrBlank(str)) {
        str = "<i>No Deadline</i>";
    }
    return str;
}
function calculateDurationByDateTime(mStart,mEnd) {
    var mSt = moment(mStart).seconds(0).milliseconds(0);
    var mEd = moment(mEnd).seconds(0).milliseconds(0);
    var dur = mEd.diff(mSt,'m'); //get difference in minutes
    var d = moment.duration(dur,'m');
    return d;
}



/********************************************/
/* PeriodDisplay                            */
/********************************************/
var PeriodDisplay = function(options) {
    options = options || {};
    this.periodNum = options.periodNum;
    this.$repeatContainer = options.repeatContainer;
    this.$timeContainer = options.timeContainer;
    this.$parentContainer = options.parentSelector;

    this.periodStart = moment();
    this.periodEnd = moment();
    this.periodDuration = 0;

    this.drawPeriod();
    this.drawPeriodTimes();
}
_.extend(PeriodDisplay.prototype, {
    drawPeriod: function() {
        var num = this.periodNum;
        var $pd = $("<div>",{class: "periodrecur alignedText"});
        this.$repeatContainer.append($pd);

        var sds = $("#txtStartDateSeries").val();
        var mStart = !isNullOrBlank(sds) ? moment(sds,fmtDate) : moment();

        //DayOfMonth
        var domName = "dayOfMonth";
        var domClass = "dom";
        $pd.append(
            $("<div>",{id: domName+"Label_"+num, class: domClass+"Label alignedText", text: "on day"}),
            $("<div>",{id: domName+"Value_"+num, class: domClass+"Value editableField alignedText"}),
            $("<div>",{id: domName+"List_"+num})
        );
        var daysArray = [];
        for (var i=1; i <=30; i++) {
            daysArray.push(i);
        }
        this.domSelectList = new SelectList({
            valueFieldID: domName+"Value_"+num,
            selectID: domName+"List_"+num,
            vals: daysArray,
            labels: _.clone(daysArray),
            defaultVal: mStart.date() //TODO var dt = Math.min(mSD.format('D'),30);
        });

        //WeekOfMonth
        var womName = "weekOfMonth";
        var womClass = "wom";
        $pd.append(
            $("<div>",{id: womName+"Label_"+num, class: womClass+"Label alignedText", text: "on the"}),
            $("<div>",{id: womName+"Value_"+num, class: womClass+"Value editableField alignedText"}),
            $("<div>",{id: womName+"List_"+num})
        );
        this.womSelectList = new SelectList({
            valueFieldID: womName+"Value_"+num,
            selectID: womName+"List_"+num,
            vals: ["1","2","3","4","5"],
            labels: ["1st","2nd","3rd","4th","last"],
            defaultVal: Math.ceil(mStart.date()/7)
        });

        //DayOfWeek
        var dowName = "dayOfWeek";
        var dowClass = "dow";
        $pd.append(
            $("<div>",{id: dowName+"Label_"+num, class: dowClass+"Label alignedText", text: "on"}),
            $("<div>",{id: dowName+"Value_"+num, class: dowClass+"Value editableField alignedText"}),
            $("<div>",{id: dowName+"List_"+num})
        );
        this.dowSelectList = new SelectList({
            valueFieldID: dowName+"Value_"+num,
            selectID: dowName+"List_"+num,
            vals: ["0","1","2","3","4","5","6"],
            labels: ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
            defaultVal: mStart.day()
        });

        if (num > 0) $pd.append(this.drawRemoveButton());
    },
    drawPeriodTimes: function() {
        var num = this.periodNum;
        //From Row
        var $fromRow = $("<div>",{class: "dateTimeRow"});
        this.$timeContainer.append($fromRow);

        var $startDate = $("<div>",{class: "ui-field-contain startdatefield datebox"});
        this.$sdInput = $("<input>",{type: "text", id: "txtStartDate_"+num, placeholder: "__/__/____", title: "From Date", "data-mini": "true"});
        $startDate.append(
            $("<label>",{for: "txtStartDate_"+num, class: "required ui-hidden-accessible", text: "Start Date:"}),
            this.$sdInput
        );
        var $startTime = $("<div>",{class: "ui-field-contain timebox"});
        this.$stInput = $("<input>",{type: "text", id: "txtStartTime_"+num, class: "starttime", placeholder: "hh:mm AM", title: "From Time", "data-mini": "true"});
        $startTime.append(
            $("<label>",{for: "txtStartTime_"+num, class: "required", text: "Start Time:"}),
            this.$stInput
        );
        var $overnight = $("<div>",{class: "divOvernight"});
        this.$chkOvernight = $("<input>",{type: "checkbox", id: "chkOvernight_"+num, value: "yes", "data-mini": "true"});
        $overnight.append(
            this.$chkOvernight,
            $("<label>",{for:"chkOvernight_"+num, text: "Block time spans multiple days"})
        );

        $fromRow.append(
            $("<legend>",{class:"requiredstyle", text: "From:"}),
            $startDate,
            $startTime,
            $overnight
        );
        this.$sdInput.textinput();
        this.$stInput.textinput();

        this.$chkOvernight.checkboxradio();
        this.$chkOvernight.checkboxradio("refresh");

        buildDatePicker("#txtStartDate_"+num);
        buildTimePicker("#txtStartTime_"+num);

        //To Row
        var $toRow = $("<div>",{class: "dateTimeRow"});
        this.$timeContainer.append($toRow);

        var $endDate = $("<div>",{class: "ui-field-contain enddatefield datebox"});
        this.$edInput = $("<input>",{type: "text", id: "txtEndDate_"+num, placeholder: "__/__/____", title: "To Date", "data-mini": "true"});
        $endDate.append(
            $("<label>",{for: "txtEndDate_"+num, class: "required ui-hidden-accessible", text: "End Date:"}),
            this.$edInput
        );
        var $endTime = $("<div>",{class: "ui-field-contain endtimefield timebox"});
        this.$etInput = $("<input>",{type: "text", id: "txtEndTime_"+num, class: "endtime", placeholder: "hh:mm AM", title: "To Time", "data-mini": "true"});

        //Duration
        this.$dur = $("<div>",{id: "duration_"+num, class: "durationText alignedText ui-field-contain"});

        $endTime.append(
            $("<label>",{for: "txtEndTime_"+num, class: "required", text: "End Time:"}),
            this.$etInput
        );
        $toRow.append(
            $("<legend>",{class:"requiredstyle", text: "To:"}),
            $endDate,
            $endTime,
            this.$dur
        );
        this.$edInput.textinput();
        this.$etInput.textinput();
        buildDatePicker("#txtEndDate_"+num);
        buildTimePicker("#txtEndTime_"+num);

        this.setInitialDateTime();

        //EVENT handlers
        var pdDisp = this;
        this.$sdInput.on("change", function() {
            var sdVal = $(this).val();
            var newStart = moment(sdVal + " " + pdDisp.$stInput.val(), fmtDate + " " + fmtTime);
            pdDisp.updateStart(sdVal,pdDisp.$stInput.val(),pdDisp);

            //update the end date to the start date unless it's an overnight block where the end date is before the start date
            var isOvernightBlock = false;
            if (!(isOvernightBlock && moment(pdDisp.$edInput.val(),fmtDate).isBefore(moment(sdVal,fmtDate)))) {
                pdDisp.$edInput.val(sdVal);
                pdDisp.updateEnd(sdVal,pdDisp.$etInput.val(),pdDisp);
            }

            //for first period (or non recurring) only
            if (num === 0) {
                //set a sessionVar for the view calendar page
                blockViewController.setSessionVar([["viewcal_selstartdate",newStart.format("YYYYMMDD")]],$.noop);

                //reset deadline controls max date
                var sd = newStart.toDate();
                setMobiscrollMaxDate('#txtOpening',sd);
                setMobiscrollMaxDate('#txtDeadline',sd);
                setMobiscrollMaxDate('#txtCanDeadline',sd);
            }
        });
        this.$stInput.on("change", function() {
            pdDisp.updateStart(pdDisp.$sdInput.val(),$(this).val(),pdDisp);
        });
        this.$edInput.on("change", function() {
            pdDisp.updateEnd($(this).val(),pdDisp.$etInput.val(),pdDisp);
        });
        this.$etInput.on("change", function() {
            pdDisp.updateEnd(pdDisp.$edInput.val(),$(this).val(),pdDisp);
        });
        this.$chkOvernight.on("click",function(e) {
            pdDisp.onClickOvernight($(e.target).is(":checked"));
        });

    },
    setEnabled: function(isEnabled) {
        this.domSelectList.setEnabled(isEnabled);
        this.womSelectList.setEnabled(isEnabled);
        this.dowSelectList.setEnabled(isEnabled);

        var txtEnabled = isEnabled ? "enable" : "disable";
        this.$sdInput.textinput(txtEnabled);
        this.$stInput.textinput(txtEnabled);
        this.$edInput.textinput(txtEnabled);
        this.$etInput.textinput(txtEnabled);

        this.$chkOvernight.checkboxradio(txtEnabled);
    },
    updateDateTimeVals: function(start,end,pdDisp) {
        //set duration
        pdDisp.setDuration(start,end);
        //update slot size
        blockview.updateSlotSize();
    },
    onClickOvernight: function(isChecked) {
        this.$timeContainer.toggleClass('overnight',isChecked);
        if (!isChecked) {
            this.$edInput.val(this.$sdInput.val());
            this.updateEnd(this.$edInput.val(),this.$etInput.val(),this);
        }
    },
    setOvernight: function(isOvernight) {
        this.$chkOvernight.prop("checked",isOvernight).checkboxradio('refresh');
        this.onClickOvernight(isOvernight);
    },
    updateStart: function(dateVal,timeVal,pdDisp) {
        //calculate new start
        var newStart = moment(dateVal + " " + timeVal, fmtDate + " " + fmtTime);
        pdDisp.periodStart = moment(newStart);
        this.updateDateTimeVals(newStart,pdDisp.periodEnd,pdDisp);
    },
    updateEnd: function(dateVal,timeVal,pdDisp) {
        //calculate new end
        var newEnd = moment(dateVal + " " + timeVal, fmtDate + " " + fmtTime);
        pdDisp.periodEnd = moment(newEnd);
        this.updateDateTimeVals(pdDisp.periodStart,newEnd,pdDisp);
    },
    setInitialDateTime: function() {
        var mStart = moment();
        var stToHalfHour = Math.ceil(mStart.minutes() / 30) * 30;
        var newStart = moment(mStart).minutes(stToHalfHour);
        this.setDateTimes(newStart,moment(newStart).add(2,'h'));
    },
    setDateTimes: function(mStart,mEnd) {
        this.$sdInput.val(mStart.format(fmtDate));
        this.$edInput.val(mEnd.format(fmtDate));

        this.$stInput.val(mStart.format(fmtTime));
        this.$etInput.val(mEnd.format(fmtTime));

        //if date is different, check the 'overnight' checkbox
        this.setOvernight(mStart.isBefore(mEnd,'day'));

        this.periodStart = moment(mStart);
        this.periodEnd = moment(mEnd);
        this.setDuration(mStart,mEnd);
    },
    setDuration: function(mStart,mEnd) {
        var durObj = calculateDurationByDateTime(mStart,mEnd);
        this.$dur.text(getDurationDisplay(durObj));
        this.periodDuration = durObj.asMinutes();
    },
    drawRemoveButton: function() {
        //remove button
        var $btnRemove = $('<div>', {class: "removebutton clearfix"});
        $btnRemove.append(
            $('<div>', {class: "ui-icon-delete ui-btn ui-btn-icon-notext"}),
            $('<div>', {class: "icontext", html: "Remove"})
        );
        $btnRemove.on("vmousedown", _.bind(function() {
            blockview.removePeriod(this.periodNum);
        },this));
        return $btnRemove;
    },
    removePeriod: function() {
        this.$repeatContainer.remove();
        this.$timeContainer.remove();
        if (this.$parentContainer) this.$parentContainer.remove();
    }
});



/********************************************/
/* Block View                               */
/********************************************/
var BlockView = function() {
	this.blockid = null;
	this.ismultidate = false;	
	this.calid = null;
	this.isOwnerManager = false;
	this.labels = { 'APPTHING': '', 'APPTHINGS': '' };
};

_.extend(BlockView.prototype, {

    setPageVars: function(inCalID, inBlockID, inStartDate) {
        blockController.init();
        blockViewController.init();
        
    	this.calid = inCalID;
    	this.startdate = inStartDate || new Date();

    	$("#divBlockOwner").hide();

    	var isNewBlock = isNullOrBlank(this.blockid = inBlockID);

		//page title
		$("#pagetitle").text(isNewBlock ? "Create a Block" : "Edit Block");

        //URL section
        $("#divURLs").toggle(!isNewBlock);
	    //Delete Block button
	    $("#cancelbutton").toggle(!isNewBlock);

		this.initDeadlines();
		this.initRecurArea();
	
		//Lock/unlock block (isavailable)
		this.setBlockAvailability(gIsAvailable);
		$('#lblAvailable').on("click",_.bind(function() {
			this.setBlockAvailability(!gIsAvailable);
		},this));


		//set up radio toggle slotted/unslotted relevant fields show/hide
		var that = this;
		$('.divintoradio').on("click",function() {
			var isSlotted = $(this).val() === "multipleslots" && $("#radMultipleSlots:checked").length > 0;
			$("#divSlotSize").toggle(isSlotted);
			$("#divMaxApptsPerPerson").toggle(isSlotted);
			$("#divMaxApptsPerSlot label").text(isSlotted ? 'Max ' + capitalize(that.labels['APPTHINGS']) + ' Per Slot:' : 'Max ' + capitalize(that.labels['APPTHINGS']) + ' Per Block:');
			$("#divideIntoInstr").toggle(isSlotted);
			//set defaults - TODO - shouldn't be on every change of radio
			$("#txtMaxApptsPerPerson").val(isSlotted ? "0" : "1");
			$("#txtMaxApptsPerSlot").val(isSlotted ? "1" : "0");
		});
		
		//hide the "options" for saving for recurring blocks
		$("#divSaveWhat").hide();	
		$("#divDeleteWhat").hide(); //on _cancelconfirm
	},

    setBlockAvailability: function(isAvail) {
		gIsAvailable = isAvail;
	    var onCls = isAvail ? 'ui-icon-available' : 'ui-icon-lock';
	    var imgTxt = isAvail ? 'Available Block' : 'Unavailable Block';
	    var txt = isAvail ? "Available: " + capitalize(this.labels['APPTHINGS']) + " can be made (click to lock)" : 
	        "Unavailable: NO " + capitalize(this.labels['APPTHINGS']) + " can be made (click to unlock)";

	    var $imgLock = $("#imgLockBlock");
        $imgLock.removeClass("ui-icon-lock ui-icon-available").addClass(onCls).html(imgTxt);
	    $("#spLockBlock").text(txt);
	},

    drawBreadcrumbs: function(inBCFlatArr) {
        var bcarray = new Array();
        for (var i=0; i < inBCFlatArr.length; i+=3) {
            //special case for view calendar, make sure calid is URL calid.
            if (inBCFlatArr[i].indexOf("View Calendar") > -1)
                inBCFlatArr[i+1] = getPageURL("viewcalendar", {calid: this.calid});;
            bcarray.push([inBCFlatArr[i],inBCFlatArr[i+1],inBCFlatArr[i+2]]);
        }
        if (!isNullOrBlank(this.blockid))
            bcarray.push(["Edit Block","",""]);
        else
            bcarray.push(["Create Block","",""]);
        buildBreadcrumbs(bcarray);
    },

    //inCal can be calendarinfo or calendarheaderinfo... using only calendarheaderinfo fields
    setBreadcrumbs: function(inCalID,inCalTitle) {
        //Set breadcrumbs link
        var $vcLink = $("#lnkViewCal");
        if ($vcLink.length) {
            $vcLink.text(inCalTitle).attr("href","#");
            $vcLink.on("click", function() {
                goToViewCal();
            });
        }
    },

    setParms: function() {
        //Initialize date and time widgets
        var today = moment();
        var mTermStartDate = moment(gParms["CURTERMSTART"],"YYYY-MM-DD");
        this.startdatedefault = today.isBefore(mTermStartDate) ? mTermStartDate : moment(this.startdate);
        if (this.startdatedefault.isBefore(today))
            this.startdatedefault = today;
        this.setDateValues();

        //Now, create Access Restrictions Display
        gARdisplay = new AccessRestrictionsDisplay();
        gARdisplay.draw();
    },

    /***********/
    /* Periods */
    /***********/
	removePeriod: function(n) {
        var pdDisp = this.periods[n];

        //remove from the DOM via PeriodDisplay
        pdDisp.removePeriod();

		//remove from the periods array
		this.periods[n] = null; //to keep unique IDs when creating new periods
	},
    addPeriod: function(){
        if (!this.hasOwnProperty('periods'))
            this.periods = [];
        var numPds = this.periods.length;

        //defaults for 1st period
        var $parentContainer = null;
        var $repeatContainer = $("#repeatOptions");
        var $timeContainer = $("#divFirstPeriod");
        //all other periods
        if (numPds > 0) {
            $parentContainer = $("<div>",{class: "periodsection"});
            $parentContainer.append($("<hr>",{class: "dashed short"}));
            $("#divPeriods").append($parentContainer);
            $repeatContainer = $timeContainer = $parentContainer;
        }

        this.periods.push(new PeriodDisplay({periodNum: numPds, repeatContainer: $repeatContainer, timeContainer: $timeContainer, parentContainer: $parentContainer}));
    },


    onChangeRepeats: function(repeats) {
        $("#dateSection").toggleClass('repeats',repeats);
    },
    setRepeats: function(repeats) {
        $("#chkRepeats").prop("checked",repeats).checkboxradio('refresh');
        this.onChangeRepeats(repeats);
    },
    initRecurArea: function() {
        //set up repeats toggle - relevant fields show/hide
        $('#chkRepeats').on("click",function() {
            blockview.onChangeRepeats($(this).is(":checked"));
        });

        //create the Every select list
        this.everySelectList = new SelectList({
            valueFieldID: "repeatEveryValue",
            selectID: "repeatEveryList",
            vals: ["daily","dailyweekdays","weekly","otherweekly","monthlyday","monthlyweekday"],
            labels: ["day","week day","week","other week","month (same day)","month (same week)"],
            defaultVal: "weekly",
            changeHandler: function(inVal) {
                var allEveryVals = "daily dailyweekdays weekly otherweekly monthlyday monthlyweekday";
                this.$valField.parents("#repeatOptions").removeClass(allEveryVals).addClass(inVal);
                $("#divPeriods").removeClass(allEveryVals).addClass(inVal);
            }
        });

        //set up date pickers for series start/end dates
        buildDatePicker("#txtStartDateSeries");
        buildDatePicker("#txtEndDateSeries");

        //clicking anywhere else closes open lists
        $(document).bind("click", function(e) {
            if (!$(e.target).closest('.selectListWrapper').length && !$(e.target).hasClass('editableField')) {
                $(".selectListWrapper").hide();
            }
        });
        //set up the 'add period' button click
        $('#btnAddPeriod').bind("click", function() {
            blockview.addPeriod();
        });
    },

    openDurationSelector: function() {
    	var fld = $(this).prev().prev().attr("id");
		$("#fld").val(fld);
		$("#popupDeadline").popup("open");
    },

    setDateValues: function() {
    	//get values from block, if it exists, otherwise, use 'smart' defaults
    	if (!isNullOrBlank(gBlock)) {
            var mStart = moment(gBlock.get('startdatetime'));
            var mEnd = moment(gBlock.get('enddatetime'));

            var series = gBlock.get('series');
            var isRecur = !isNullOrBlank(series);
            this.setRepeats(isRecur);

            if (isRecur) {
                $("#dateSection").addClass('editing');

                //series values
                $("#txtStartDateSeries").val(moment(series.get('startdate')).format(fmtDate));
                $("#txtEndDateSeries").val(moment(series.get('enddate')).format(fmtDate));
                var every = series.get('every');
                this.everySelectList.setValue(every);

                //Day types
                var daytypes = series.get('daytypes').split(",");
                for (var i=0; i < daytypes.length; i++) {
                    $("#chk"+daytypes[i]).prop("checked",true).checkboxradio('refresh');
                }
                //TODO CHECK ALL

                //period values
                _.each(gBlock.get('periods'), function(pd,i) {
                    if (i > 0)
                        this.addPeriod();
                    var pdObj = this.periods[i];
                    pdObj.domSelectList.setValue(pd.get('dayofmonth'));
                    pdObj.womSelectList.setValue(pd.get('weekofmonth'));
                    var dow = moment().day(pd.get('dayofweek')).day();
                    pdObj.dowSelectList.setValue(dow);

                    //times
                    var mST = moment(pd.get('starttime'));
                    var newStart = mStart.clone().hour(mST.hour()).minute(mST.minute()).second(mST.second());
                    var newEnd = newStart.clone().add(pd.get('duration'),'m');
                    pdObj.setDateTimes(newStart,newEnd);
                },this);

            } else { //instance values
                var pdObj = this.periods[0];
                pdObj.setDateTimes(mStart,mEnd);
            }

    		//Deadlines - are in minutes, ahead of each block instance
    		this.setDeadlineField("Opening", moment.duration(parseInt(gBlock.get('opening'),10),'m'));
            this.setDeadlineField("Deadline", moment.duration(parseInt(gBlock.get('deadline'),10),'m'));
            this.setDeadlineField("CanDeadline", moment.duration(parseInt(gBlock.get('candeadline'),10),'m'));

    	} else { //NEW BLOCK
    		//default 'repeats' to 'no'
            this.setRepeats(false);

            var dfStart = moment(this.startdatedefault);
            $("#txtStartDateSeries").val(dfStart.format(fmtDate));
            var dfEndSeries = dfStart.clone().add(1,'M');
            $("#txtEndDateSeries").val(dfEndSeries.format(fmtDate));

            //set dates/times using default date and initial times already set in new PeriodDisplay
            //add the first period
            this.addPeriod();
            var pdObj = this.periods[0];
            var newStart = pdObj.periodStart.clone().year(dfStart.year()).month(dfStart.month()).date(dfStart.date());
            var newEnd = pdObj.periodEnd.clone().year(dfStart.year()).month(dfStart.month()).date(dfStart.date());
            pdObj.setDateTimes(newStart,newEnd);

    	    //deadlines
            var dfDur = moment.duration();
            this.setDeadlineField("Opening", dfDur);
            this.setDeadlineField("Deadline", dfDur);
            this.setDeadlineField("CanDeadline", dfDur);
    	}

    	this.updateSlotSize(); //trigger the slot size calculation
    },
    
    initDeadlines: function() {
    	//Deadlines
        this.opening = 0;
        this.deadline = 0;
        this.candeadline = 0;
    	$("#btnEditOpening").bind("click", this.openDurationSelector);
    	$("#btnEditDeadline").bind("click", this.openDurationSelector);
    	$("#btnEditCanDeadline").bind("click", this.openDurationSelector);
    },
    
    getFactors: function(int) {
    	var arr = [];
    	for (var i=1; i < int; i++) {
    		if (int % i === 0) {
    			arr.push(i);
    		}
    	}
    	return arr;
    },
    updateSlotSize: function(e) {
        var factors = [];
        _.each(this.periods, function(pd) {
            if (isNullOrBlank(pd)) return;
            var newFactors = this.getFactors(pd.periodDuration);
            //keep intersection of 2 arrays: factors and newFactors
            factors = factors.length > 0 ? _.intersection(factors, newFactors) : newFactors;
        },this);
    	
    	var oldVal = $("#selSlotSize").val();
    	$("#selSlotSize").empty();
    	//check for invalid entries
    	if (factors.length === 0) {
            $('<option>').val(0).text("(choose times for values)").appendTo("#selSlotSize");
        } else {
	    	var df = ''; //default value of list
            _.each(factors, function(f) {
                $('<option>').val(f).text(f).appendTo("#selSlotSize");
                if (oldVal === '0' && df === '' && f >= 15) df = f;
            });
            //set the default value of the list
	    	if (!isNullOrBlank(oldVal) && oldVal !== '0') df = oldVal;
	        $("#selSlotSize").val(df);
        }
        $("#selSlotSize").selectmenu("refresh");
    },

	drawURLs: function() {
		var str = "";
		var blkurl = getURLPath() + "/makeappt.php?calid=" + this.calid + "&blockid=" + this.blockid;
		
		str += '<div class="urltitle">Block URL (for ' + capitalize(this.labels['APPTHING']) + ' Makers):</div>';
		str += '<div class="urlstring">' + blkurl + '</div>';
		$("#divURLs").append(str);	
	},

	//Set defaults for fields not defaulted on calendar settings page
	setDefaultDisplayVals: function() {
		//slotted
		$(':radio[value="multipleslots"]').siblings('label').trigger('vclick');	
		//appt slot size - set in updateSlotSize
		//maxapps
		$("#txtMaxApptsPerSlot").val("1");
		//maxper
		$("#txtMaxApptsPerPerson").val("1");
		//require purpose
		$("#chkRequirePurpose").prop("checked",false).checkboxradio('refresh');
		//deadlines - set in setDeadlineField
	},

	loadUserNotifyRemind: function(usernotify,userremind) {
        $("#chkNotify").prop("checked",usernotify).checkboxradio('refresh');
        $("#chkRemind").prop("checked",userremind).checkboxradio('refresh');
	},
    setUserDisplay: function(nameText,phoneText,emailText) {
        $("#vwName").text(nameText);
        $("#vwPhone").text(phoneText).removeClass('novalue');
        if (isNullOrBlank(phoneText)) {
            $("#vwPhone").text('<no phone listed>').addClass('novalue');
        }
        $("#vwEmail").text(emailText);
    },
	loadUserData: function(inUser) {
    	//check for members - for @members, 'inUser' has the owner's userid
		var $sel = $("#selBlockOwner");
		var usrid = $sel.is(":visible") ? $sel.val() : inUser.get('userid');
	    $("#hidUserID").val(usrid);
	    var nameText = inUser.get('name');
	    $("#txtName").val(nameText);
        var phoneText = inUser.get('phone');
	    $("#txtPhone").val(phoneText);
		var emailText = inUser.get('email');
	    $("#txtEmail").val(emailText);
        this.setUserDisplay(nameText,phoneText,emailText);
	},

	//Put the user data from the user object on the form
	loadLoggedInUserData: function(inUser) {
	    var owner = gWASECal.get('owner');
		if (this.userStatus['member'] || isNullOrBlank(owner)) {
			this.loadUserData(inUser);
		} else {
		    this.loadUserData(owner);
		}
	},

//Get only header info for breadcrumbs if blockid not null
loadCalHeaderData: function(inCal,isOwner,isManager,isMember) {
    this.userStatus = {owner: isOwner, manager: isManager, member: isMember};
	//Set breadcrumbs link
	this.setBreadcrumbs(inCal.get('calendarid'),inCal.get('title'));
},

	loadCalendarData: function(inCal,isOwner,isManager,isMember) {
		gWASECal = inCal;
	    this.userStatus = {owner: isOwner, manager: isManager, member: isMember};
	
	    this.calid = inCal.get('calendarid');
	
		//Set breadcrumbs link
		this.setBreadcrumbs(inCal.get('calendarid'),inCal.get('title'));
		
		//First, set the defaults for the values, in case they are not set here.
		this.setDefaultDisplayVals();
		
		//Defaults from the calendar
		$("#txtBlockTitle").val(inCal.get('title'));
		$("#txtDescription").val(inCal.get('description'));
		$("#txtLocation").val(inCal.get('location'));
		
		//contact information
		var owner = inCal.get('owner');
		this.loadUserData(owner);

		//notification/reminders
		var nr = inCal.get('notifyandremind');
		var notify = false;
		var remind = false;
		if (this.userStatus['owner']) {
			notify = nr.get('notify');
			remind = nr.get('remind');
		} else if (this.userStatus['manager']) {
			notify = nr.get('notifyman');
			remind = nr.get('remindman');
		} //member taken care of with separate AJAX call
		this.loadUserNotifyRemind(notify,remind);
		//appt text
		$("#txtApptText").val(nr.get('apptmsg'));
	
		//require appt purpose
		$("#chkRequirePurpose").prop("checked",inCal.get('purreq')).checkboxradio('refresh');
		
		//Access Restrictions
		AccessRestrictions.loadAccessRestrData(inCal.get("accessrestrictions"));
		
		//display the 'block owner' select list (of members) if user is owner/manager, NOT member, and cal has > 0 members
		var mems = inCal.get('members');
		var showmemlist = false;
		if ((isOwner || isManager) && !isMember && mems.length > 0) {
	        //add the owner
	        $("#selBlockOwner").append($("<option>",{value: owner.get('userid'), text: owner.getDisplayName()}));
		    _.each(mems, function(mem) {
		        var usr = mem.get('user');
		        var userid = usr.get('userid');
		        // set @member name to @member userid.
		        //$("#selBlockOwner").append($("<option>",{value: userid, text: usr.getDisplayName()}));
                $("#selBlockOwner").append($("<option>",{value: userid, text: usr.get('userid')}));
		    });
		    $("#selBlockOwner").val(owner.get('userid')).selectmenu('refresh',true);

		    var calid = this.calid;
		    $("#selBlockOwner").on('change', function() {
		        //reset the contact information
				var selVal = $(this).val();
				var calUserID = selVal.charAt(0) === '@' ? owner.get('userid') : $(this).val();
		        blockViewController.loadUserInfo(calUserID);
		        blockViewController.loadUserNotifyRemind($(this).val(),calid);
		    });
            showmemlist = true;
		}
        $("#divBlockOwner").show();
        $("#divBlockOwnerDisplay").hide();
        $("#divBlockOwner .ui-select").toggle(showmemlist);
		
		//labels
		this.drawLabelsSection(inCal.get('labels'));
		//set Access Restrictions labels
		gARdisplay.labels = _.clone(this.labels);
	},

setEditRules: function(inBlk) {
	//If there are appointments, cannot change slot size
	if (inBlk.get('hasappointments')) {
		$("#selSlotSize").selectmenu("disable");
	}
	
	//CANNOT Change anything about recurrence on edit
	$("#chkRepeats").checkboxradio("disable");
	$("#txtStartDateSeries").textinput("disable");
	$("#txtEndDateSeries").textinput("disable");

	this.everySelectList.setEnabled(false);

	_.each(this.periods, function(pd) {
        if (!isNullOrBlank(pd)) pd.setEnabled(false);
    });
    $("#btnAddPeriod").hide();

	$("#divDayTypes input[type=checkbox]").each( function () {
		$(this).checkboxradio("disable");
	});
},

    setDeadlineField: function(fld, dur) {
	    if (fld.indexOf('txt') >= 0) fld = fld.substr(3);
	    var mins = dur.asMinutes();
        this[fld.toLowerCase()] = mins;
        var str = getDeadlineDurationDisplay(dur);
        var $fld = $("#txt"+fld);
        $fld.html(str);
        $fld.change();
        $("#txt"+fld+"Msg").toggle(mins > 0);
    },

//check if fields should be enabled - edit only
disableFields: function() {
	$("form#blockinfo").find("input[type='text']","textarea").each(function() {
		if ($(this).textinput())
			$(this).textinput("disable");
	});
	$("textarea").addClass('ui-disabled');
	$("form#blockinfo").find("input[type='checkbox']").each(function() {
		$(this).checkboxradio('disable');
	});
	$("form#blockinfo").find("input[type='radio']").each(function() {
		$(this).checkboxradio('disable');
	});
	$("form#blockinfo").find("select").each(function() {
		$(this).selectmenu('disable');
	});
	$("form#blockinfo").find(".cgspinbox .ui-btn").each(function() {
		$(this).addClass('ui-state-disabled');
	});
	$(".btnSubmit").button('disable');
	$("#cancelbutton").hide();
},

//Put the block data from the block object on the form
loadBlockData: function(inBlk) {
	this.calid = inBlk.get('calendarid');
	gBlock = _.clone(inBlk);
    gOrigBlock = _.clone(inBlk);

	//First, set the defaults for the values, in case they are not set here.
	this.setDefaultDisplayVals();
	
	//show block owner
	if (this.userStatus.owner || this.userStatus.manager || this.userStatus.member) {
	    $("#divBlockOwner").show();
	    $("#divBlockOwner .ui-select").hide();
	    $("#divBlockOwnerDisplay").text(inBlk.blockowner.getDisplayName()).show();
	}
	
	$("#txtBlockTitle").val(inBlk.get('title'));
	$("#txtDescription").val(inBlk.get('description'));
	$("#txtLocation").val(inBlk.get('location'));

	//Divide into
	$('#cgDivideInto :radio[value="' + inBlk.get('divideinto') + '"]').siblings('label').trigger('vclick');

	//Set date and time values - calls updateSlotSize
	this.setDateValues();

	//set slotsize, maxapps, maxper
	$('#selSlotSize').val(inBlk.get('slotsize')).selectmenu('refresh');
	$("#txtMaxApptsPerSlot").val(inBlk.get('maxapps'));
	$("#txtMaxApptsPerPerson").val(inBlk.get('maxper'));

	//require appt purpose
	$("#chkRequirePurpose").prop("checked",inBlk.get('purreq')).checkboxradio('refresh');
	
	//owner info
	this.loadUserData(inBlk.get('blockowner'));

	//Notify and Remind
    var nr = inBlk.get('notifyandremind');
	$("#chkNotify").prop("checked",nr.get('notify')).checkboxradio('refresh');
	$("#chkRemind").prop("checked",nr.get('remind')).checkboxradio('refresh');
	$("#txtApptText").val(nr.get('apptmsg'));
			
	//access restrictions
	AccessRestrictions.loadAccessRestrData(inBlk.get("accessrestrictions"));

	//if it's a recurring block, show "save what" and "delete what" radios
	if (!isNullOrBlank(inBlk.get('series')) && !isNullOrBlank(inBlk.get('series').get('seriesid'))) {
		$("#divSaveWhat").show();
		$("#divDeleteWhat").show();
	}
	$(':radio[value="instance"]').siblings('label').trigger('vclick');
	
	//Set the rules for editing
	this.setEditRules(inBlk);
	
	//labels
	this.drawLabelsSection(inBlk.get('labels'));
    //update text of deadlines with label
	$("#canDeadlineText").text("prior to start of " + inBlk.get('labels')['APPTHING']);
	//set Access Restrictions labels
	gARdisplay.labels = _.clone(this.labels);

    //Lock/Unlock - must be after labels
    this.setBlockAvailability(inBlk.get('available'));

    //if not a calendar owner/manager or block owner, disable fields
	if (!this.userStatus['owner'] && !this.userStatus['manager'] && owner.get('userid') !== g_loggedInUserID)
		this.disableFields();
	
    //Draw URLs
    this.drawURLs();

},
	

loadDayTypes: function(inDayTypes) {
	var str = "";
	if (inDayTypes !== null && inDayTypes !== "") {
		//Add the "All" checkbox
		str += '<input type="checkbox" name="chkAll" id="chkAll" value="all" class="daytypecheckbox chkall" onclick="selectAllChk(\'daytypecheckbox\',this.checked);" /> <label for="chkAll">All</label>';
		var arrDayTypes = inDayTypes.split(",");
		var col1lim = Math.ceil(arrDayTypes.length/2);
		str += '<div class="col1">';
		for (var i=0; i < arrDayTypes.length; i++) {
			var dt = arrDayTypes[i];
			if (i === col1lim) {
				str += '</div><div class="col2">';
			}
			str += '<input type="checkbox" name="chk' + dt + '" id="chk' + dt + '" value="' + dt + '" class="daytypecheckbox" onclick="shouldAllBeChecked(\'daytypecheckbox\',\'cgDayTypes\');" /> <label for="chk'+ dt + '">' + dt + '</label>';
		}
		str += '</div>';
		
		$("#cgDayTypes legend").text("Day Type(s):");
		$("#cgDayTypes").controlgroup("container").append(str);
		$(".daytypecheckbox").checkboxradio();
		$(".daytypecheckbox").checkboxradio("refresh");
		
		//Check all only if new block
		if (isNullOrBlank(this.blockid)) $("#chkAll").click();
	}
},

	//Save block data from form to input block object (gBlock)
	saveBlockData: function() {
		var errs = [];
		var isNewBlock = isNullOrBlank(this.blockid);
		
		var bVals = {
			calendarid: this.calid,
			
			title: $("#txtBlockTitle").val(),
			description: $("#txtDescription").val(),
			location: $("#txtLocation").val(),
            blockowner: new User({
                userid: $("#hidUserID").val(),
                name: $("#txtName").val(),
                phone: $("#txtPhone").val(),
                email: $("#txtEmail").val()
            }),
			maxapps: $("#txtMaxApptsPerSlot").val(),
			maxper: $("#txtMaxApptsPerPerson").val(),
			
			purreq: $("#chkRequirePurpose").is(":checked"),
			available: gIsAvailable,
			
			notifyandremind: new NotifyAndRemind({
			        notify: $("#chkNotify").is(":checked"),
			        remind: $("#chkRemind").is(":checked"),
			        apptmsg: $("#txtApptText").val()
			    }),
			    
			labels: new WASEObject({
					NAMETHING: $("#txtNamething").val(),
					NAMETHINGS: $("#txtNamethings").val(),
					APPTHING: $("#txtAppthing").val(),
					APPTHINGS: $("#txtAppthings").val()
				})
		};
		
		//get dates/times
		var isrecur = $("#chkRepeats").is(":checked");
		if (isrecur) { //recurring
			var every = this.everySelectList.getValue();
	
			//only get series info if new block
			if (isNewBlock) {
				var daytypes = $.map($('#divDayTypes :checkbox:checked'), function(n, i){
					 if (n.value !== 'all') return n.value;
				}).join(',');
		
				bVals.series = new Series({
                    seriesid: '',
                    startdate: moment($("#txtStartDateSeries").val(),fmtDate).toDate(),
                    enddate: moment($("#txtEndDateSeries").val(),fmtDate).toDate(),
                    every: every,
                    daytypes: daytypes
                });
			}
			
			//periods
			var pds = [];
			_.each(this.periods, function(pdObj) {
                if (isNullOrBlank(pdObj)) return;
                var pd = new Period({
                    'periodid': '',
                    'starttime': pdObj.periodStart.toDate(),
                    'duration': pdObj.periodDuration,
                    'dayofweek': ['weekly','otherweekly','monthlyweekday'].indexOf(every) >= 0 ? moment().day(pdObj.dowSelectList.getValue()).format('dddd') : '',
                    'dayofmonth': every === 'monthlyday' ? pdObj.domSelectList.getValue() : '',
                    'weekofmonth': every === 'monthlyweekday' ? pdObj.womSelectList.getValue() : ''
                });
                pds.push(pd);
            });
			bVals.periods = _.clone(pds);
		}

		//get instance startdate/starttime and endtime if not recurring OR if recurring edit
		if (!isrecur || (isrecur && !isNewBlock)) {
		    var pdObj = this.periods[0];
			bVals.startdatetime = pdObj.periodStart.seconds(0).milliseconds(0).toDate();
			bVals.enddatetime = pdObj.periodEnd.seconds(0).milliseconds(0).toDate();
		}

		//divide into, slot size
	    var divinto = $("#cgDivideInto :radio:checked").val();
	    bVals.divideinto = divinto;
	
	    var sltsize = 0; //default to 'unslotted'
	    if (divinto === "multipleslots") {
	        sltsize = $("#selSlotSize").val();
	    }
	    bVals.slotsize = sltsize;
	    
	    	
		//deadlines
		//opening
	    var op = this.opening;
	    //deadline
		var dl = this.deadline;
	    //cancel by
		var cdl = this.candeadline;
		
		//VALIDATION: Deadline duration values: deadline must be < opening and candeadline must be < opening
		if (op > 0 && dl > op) {
			errs.push(new Error({errorcode: "", errortext: "Deadlines: " + capitalize(this.labels['APPTHING']) + " Deadline must be after the " + capitalize(this.labels['APPTHING']) + " Opening"}));
		}
		if (op > 0 && cdl > op) {
			errs.push(new Error({errorcode: "", errortext: "Deadlines: " + capitalize(this.labels['APPTHING']) + " Cancelation Deadline must be after the " + capitalize(this.labels['APPTHING']) + " Opening"}));
		}
	    bVals.opening = op;
	    bVals.deadline = dl;
	    bVals.candeadline = cdl;
	
		
		//access restrictions
		var vAccess = $("#cgRestrict_view :radio:checked").val();
		var isViewRestr = vAccess === "restricted";
		var mAccess = $("#cgRestrict_make :radio:checked").val();
		var isMakeRestr = mAccess === "restricted";
	
		//VALIDATION: Access Restrictions - complicated, and few use cases.
		bVals.accessrestrictions = new AccessRestrictions({
		    viewaccess: vAccess,
	        viewulist: isViewRestr ? gAccessRestrictions.get('viewulist') : "",
	        viewclist: isViewRestr ? gAccessRestrictions.get('viewclist') : "",
	        viewglist: isViewRestr ? gAccessRestrictions.get('viewglist') : "",
	    	viewslist: isViewRestr ? gAccessRestrictions.get('viewslist') : "",
	        makeaccess: mAccess,
	        makeulist: isMakeRestr ? gAccessRestrictions.get('makeulist') : "",
	        makeclist: isMakeRestr ? gAccessRestrictions.get('makeclist') : "",
	        makeglist: isMakeRestr ? gAccessRestrictions.get('makeglist') : "",
	    	makeslist: isMakeRestr ? gAccessRestrictions.get('makeslist') : "",
	        showappinfo: isTrue($("#cgShowApptInfo :radio:checked").val())
		});
	
		//display any errors
		if (errs.length > 0) {
			Error.displayErrors(errs);
			return false;		
		}
	
		this.vals = bVals;

        //notify if unavailable
	    if (!gIsAvailable) {
	        $('#popupUnavailable').popup('open');
	        return false;
	    } else {
	        this.doSaveEditBlock();
	    }
	    return true;
	}, 

	doSaveEditBlock: function() {
		if (isNullOrBlank(this.blockid)) {
			this.vals.blockid = '';
			gBlock = new Block(this.vals);
		} else {
			gBlock.setFromObject(this.vals);
		}
	},

afterAdd: function(infmsg, blk) {
    //show the message
    gMessage.displayConfirm(infmsg);

    //set 'cancel' to 'done'
    $(".btnCancelSubmit").val('Done');
    $(".btnCancelSubmit").button('refresh');

    enableButton(".btnSubmit", doSubmit);
},

setNamedDates: function(arrDates) {
    this.namedDates = _.clone(arrDates);
},

//For building the Labels section
drawLabelsSection: function(labels) {
	//instructions
	var str = "";
	str += '<div class="instructions">You can change the labels used to identify the type of event '
		+ 'being scheduled (for example, "Office Hour", or "Try-out", or "Sign-up", or "Advising session") '
		+ 'and the event being scheduled (for example, "Appointment", "Booking", "Reservation").</div>';
	$("#divLabels").append(str);
	
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
	$("#divLabels").append($fields);
	$("#txtNamething").textinput();
	$("#txtNamethings").textinput();
	$("#txtAppthing").textinput();
	$("#txtAppthings").textinput();
	
	//set block label text using calendar values
	var lbls = labels;
	var capApp = capitalize(lbls['APPTHING']);
	var capApps = capitalize(lbls['APPTHINGS']);
	$(".appName").text(lbls['APPTHING']);
	$(".appNameUpper").text(capApp);
	$(".appsName").text(lbls['APPTHINGS']);
	$(".appsNameUpper").text(capApps);
	$("#lblMaxApptsPerSlot").text('Max ' + capApps + ' Per Slot:');
	$("#lblMaxApptsPerPerson").text('Max ' + capApps + ' Per Person:');
	$("#divRequirePurpose legend").text('Require ' + capApp + ' Purpose?:');
	$("#lblApptText").text('Text for ' + capApp + ' E-mail:');
	//titles
	$("#selSlotSize").attr('title',capApp + ' Slot Size');
	$("#txtMaxApptsPerSlot").attr('title','Maximum ' + capApps + ' Allowed Per Slot');
	$("#txtMaxApptsPerPerson").attr('title','Maximum ' + capApps + ' Allowed Per Person');
	$("#chkRequirePurpose").attr('title','Require the Purpose field on ' + capApps + '?');
	$("#selBlockOwner").attr('title','With whom ' + lbls['APPTHINGS'] + ' will be made');
	//set global
	this.labels = lbls;
}
});

var blockview = new BlockView();