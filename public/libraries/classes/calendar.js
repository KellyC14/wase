var CalDate = function(options) {
    WASEObject.call(this,options);
};
CalDate.prototype = Object.create(WASEObject.prototype);
CalDate.prototype.constructor = CalDate;
_.extend(CalDate.prototype, {
    defaults: {
        date: new Date(),
        daytype: '',
        daytypeabbv: '',
        status: 'noblocks' //possible values: noblocks,blocksnoappsnoslots,blocksnoappsslots,blocksappsnoslots,blocksappsslots,blocksmyappsslots,blocksmyappsnoslots
    },
    setFromXML: function($xml) {
        this.set('date',formatDateTimeFromServer(this.getTagText($xml,'date')));
        this.set('daytype',this.getTagText($xml,'daytype'));
        this.set('daytypeabbv',this.getTagText($xml,'daytypeabbv'));
        this.set('status',this.getTagText($xml,'status'));
    }
});


function isDefined(inVar) {
	return typeof inVar !== 'undefined';
}

function Calendar(options) { 
	options = options || {};
	this.$parent = $("#"+options.parentID) || null;
	this.selectorObj = options.selectorObj || null;
	
	//selected date(s)
	this.seltype = options.seltype || "week"; //day, week, month
	this.selectedDateIDs = [];
	
	//date properties, set with inDate
	this.datebegin = options.startDate;
	this.dateend = options.endDate;
	this.dateIDFormat = "YYYYMMDD";
	this.initialDate = options.initialDate || new Date();
	
	//styling properties
	this.showControls = isDefined(options.showControls) ? options.showControls : true;
	this.showHeader = isDefined(options.showHeader) ? options.showHeader : true;
	this.showFooter = isDefined(options.showFooter) ? options.showFooter : true;
	this.showWeekHandles = isDefined(options.showWeekHandles) ? options.showWeekHandles : true;
	
	this.canEnlarge = isDefined(options.canEnlarge) ? options.canEnlarge : false;

	this.caldates = {};
	this.dates = {};
	
	this.allstatus = "noblocks blocksnoappsnoslots blocksnoappsslots blocksappsnoslots blocksappsslots blocksmyappsslots blocksmyappsnoslots " +
			"myblocksnoappsnoslots myblocksnoappsslots myblocksappsnoslots myblocksappsslots myblocksmyappsslots myblocksmyappsnoslots";

	_.bindAll(this, 'doClickDay', 'doClickWeek', 'doClickMonth', 'scrollPrevious', 'scrollNext', 'showHideKey', 'clickToday', 'toggleCalSize', 'scrollToBlock', 'setCalSizeAndPref');
	
}

_.extend(Calendar.prototype, {
	get: function(inProperty) {
		return this[inProperty];
	},
	
	/********************************************************/
	/* Draw Calendar										*/
	/********************************************************/
	setHeaderDate: function() {
        var mFirstDate = moment(this.datebegin);
        this.$hdrCol.attr("id", mFirstDate.format(this.dateIDFormat));
        this.$hdrCol.html(mFirstDate.format("MMMM YYYY"));
	},
	drawHeader: function() {
		var $hdrRow = $("<tr>");
		
		if (this.showWeekHandles) {
			$hdrRow.append(this.drawWeekHandle({className: "weekhandleempty"}));
		}
		
		var colSpan = '7';
		if (this.showControls) {
			this.$prev = $("<td>", {class: "monthcontrol", title: "Last Month", html: "&lt;"});
			$hdrRow.append(this.$prev);
			colSpan = '5';
		}
		//this.$hdrCol = $("<td>", {id: "month"+mFirstDate.format(this.dateIDFormat), class: "monthheader", colSpan: colSpan, html: mFirstDate.format("MMMM YYYY")});
        this.$hdrCol = $("<td>", {class: "monthheader", colSpan: colSpan});
		$hdrRow.append(this.$hdrCol);
		//set up event handler
		this.$hdrCol.on("vmousedown",this.doClickMonth);
	
		if (this.showControls) {
			this.$next = $("<td>", {class: "monthcontrol", title: "Next Month", html: "&gt;"});
			$hdrRow.append(this.$next);
		}
		return $hdrRow;
	},
	
	drawKeyItem: function(symbolClass,text) {
		var $item = $("<div>", {class: "keyitem"});
		var $symbol = $("<div>", {class: "keysymbol " + symbolClass});
		var $text = $("<div>", {class: "keycode", html: text});
		$item.append($symbol, $text);
		return $item;
	},
	drawKey: function() {
		var $key = $("<div>", {class: "calKey"});
		
		var $close = $("<div>", {class: "closeButton", html: "x"});
		$key.append($close);
		$close.on("vmousedown",this.showHideKey);
		
		$key.append(this.drawKeyItem("myblocksnoappsslots","Block(s), nothing scheduled"));
		$key.append(this.drawKeyItem("myblocksmyappsslots","Block(s), item(s) scheduled for " + g_loggedInUserID));
	
		return $key;
	},
	drawOverlay: function() {
		return $("<div>", {class: "calOverlay"});
	},
	showHideKey: function() {
		this.$cal.find(".calOverlay").toggle();
		this.$cal.find(".calKey").toggle();
	},
	drawFooter: function() {
		var $ftrRow = $("<div>", {class: "calFooter"});
	
		var $keyBtn = $("<div>", {class: "keybutton", title: "Key", html: '<a href="#">Key</a>'});
		$ftrRow.append($keyBtn);
		$keyBtn.on("vmousedown",this.showHideKey);
		
		var $todayBtn = $("<div>", {class: "todaybutton", title: "Today", html: "<a href='#'>Today</a>"});
		$ftrRow.append($todayBtn);
		$todayBtn.on("vmousedown",this.clickToday);
	
		return $ftrRow;
	},
	
	drawDOWRow: function() {
		var $dowRow = $("<tr>");
	
		if (this.showWeekHandles) {
			$dowRow.append(this.drawWeekHandle({className: "weekhandleheader", text: "WK"}));
		}
		
		for(var i=0;i<days.length;i++){
			$dowRow.append($("<td>", {class: "dayofweek dow"+moment().weekday(i).format("dddd")}));
		}
		
		return $dowRow;
	},
	
	drawWeekHandle: function(opts) {
		opts = opts || {};
		var clickable = opts.clickable || false;
		var cls = opts.className || "";
		var txt = opts.text || "";
		
		var $weekCol = $("<td>",{class: cls, html: txt});
		if (clickable) {
			$weekCol.on("vmousedown", this.doClickWeek);
		}
		return $weekCol;	
	},
	
	drawEnlargeButton: function() {
		var strHTML = "<a href='#' data-ajax='false' " +
				"class='ui-btn ui-corner-all ui-icon-arrow-u-r ui-btn-icon-notext noborder' " +
				"title='Expand Calendar'></a>";

		return $("<div>",{class: "calexpander", html: strHTML});
	},
	setEnlargeIcon: function() {
		var $btnExpand = this.$parent.find(".calexpander a");
		if ($("#divViewCalContent").hasClass("expanded")) {
			$btnExpand.removeClass("ui-icon-arrow-u-r").addClass("ui-icon-arrow-d-l");
			$btnExpand.attr("title","Reduce Calendar");
		} else {
			$btnExpand.removeClass("ui-icon-arrow-d-l").addClass("ui-icon-arrow-u-r");
			$btnExpand.attr("title","Expand Calendar");
		}
	},
	toggleCalSize: function() {
		//set to opposite
		var size = 'large';
        if ($("#divViewCalContent").hasClass("expanded"))
            size = 'small';
        this.setCalSizeAndPref(size);
	},
    //values for "size" are large, small
	setCalSizeAndPref: function(size) {
		this.setCalSize(size);
		
		//set preference for large/small calendar
		controller.savePref("isEnlargedViewCal",getServerBoolean(this.$cal.hasClass("fullcal")));
	},
    //values for "size" are large, small
	setCalSize: function(size) {
	    if (size === 'large') {
            $("#divViewCalContent").addClass("expanded");
            this.$cal.addClass("fullcal");
	    } else {
            $("#divViewCalContent").removeClass("expanded");
            this.$cal.removeClass("fullcal");
	    }
        this.setEnlargeIcon();
	    this.calendarSize = size;
	},
	
	getDateID: function(inDate) {
		return moment(inDate).format(this.dateIDFormat);
	},
	
	//populate data on calendar
	setCalDateInfo: function(calDates) {
		this.caldates = {};
		
		//loop over the caldates
		_.each(calDates, function(caldate) {
			var dateID = this.getDateID(caldate.date);
			this.caldates[dateID] = caldate;
			var $day = this.dates[dateID];
			
			//set status class
			if ($day) {
    			$day.removeClass(this.allstatus);
    			$day.addClass(caldate.status);
    			
    			//add the footer with day type
    			$day.find(".datefooter").html(caldate.daytype);
			}
		}, this);
	},
	emptyCalDateInfo: function() {
        this.caldates = {};
        this.$cal.find(".day").removeClass(this.allstatus);
	},
	//populate data on calendar. Need to pass hash of dateIDs with text
	setDateTextInfo: function(infoByDateID) {
		_.each(infoByDateID, function(info, dateID) {
			var $day = this.dates[dateID];
			var $dt = $day.find(".datetext");
			$dt.empty();
			if ($day) {
    			_.each(info, function(i) {
    				$dt.append(i);
    				$(i).on("vmousedown", _.partial(this.setCalSizeAndPref,'small'));
    			}, this);
			}
		}, this);
	},
	
	scrollToBlock: function(e) {
        //Get the event and the target $td
        if (!e) e = window.event;  // IE Event Model
        var $blk = $(e.target);
        if (this.selectorObj && this.selectorObj.scrollToBlock)
            this.selectorObj.scrollToBlock($blk);
	},

	drawCalendar: function() {
	    //don't allow any clicking while drawing
		this.canClick = false;
		
		//if it's not already drawn, draw the headers and set up the event handlers
		if (!this.$cal) {
	        this.$cal = $("<table>", {class: "calSelector", cellSpacing: "0", cellPadding: "0", border: "0"});
	        
	        this.$calBody = $("<tbody>");
	        this.$cal.append(this.$calBody);
		    
	        if (this.showHeader) {
	            this.$calBody.append(this.drawHeader());
	        }
	        
	        //Draw days of week row
	        this.$calBody.append(this.drawDOWRow());
	        
	        if (this.$parent) {
	            //append the calendar
	            this.$parent.append(this.$cal);

	            //can it be enlarged
	            if (this.canEnlarge) {
	                var $enlargeBtn = this.drawEnlargeButton();
	                this.$parent.append($enlargeBtn);
	                $enlargeBtn.on("vmousedown",this.toggleCalSize);
	            }
	            
	            //draw the footer and corresponding button elements
	            if (this.showFooter) {
	                this.$parent.append(this.drawFooter());
	                this.$cal.append(this.drawOverlay());
	                this.$cal.append(this.drawKey());
	            }
	            
	            //fix the height from floats
	            this.$parent.append("<div class='heightfix'></div>");
	        }
	        
		}
		
		//set header
		if (this.$hdrCol) {
		    this.setHeaderDate();
		}
		
		//empty and re-draw the calendar dates
		this.$calBody.find(".monthrow").remove();

		//Draw the first row - for blanks
		var $tr = $("<tr>", {class: "monthrow"});

		if (this.showWeekHandles) {
			$tr.append(this.drawWeekHandle({clickable: true, className: "weekhandle", text: "1"}));
		}
		
		//Write the leading empty cells
		var mFirstDate = moment(this.datebegin);
		var mLastDate = moment(this.dateend);
		var firstDay = mFirstDate.weekday();
		for (var j=0;j<firstDay;j++) {
        	var $empty = $("<td>", {class: "dayempty", html: "&nbsp;"});
			$tr.append($empty);
		}
		//Append row, even if not completely full.  Will append to row below
		this.$calBody.append($tr);
	
		//all cells with dates - loop over every day in the calendar
		var ctr = firstDay;
		var weeknum = 1;
		for (var k=mFirstDate.date();k<=mLastDate.date();k++) {
			var iNumDisplay = k > this.length ? k - this.length : k; //this is for showing weeks, because a week can span multiple months
	
			var mTmp = moment(mFirstDate).date(k);
			var dateID = this.getDateID(mTmp.toDate());
			var $day = $("<td>", {class: "day tdday", id: dateID});

			var $dateInfo = $("<div>", {class: "dateholder"});
			$dateInfo.append($("<div>", {class: "datenum dt" + dateID, html: iNumDisplay}));
			$dateInfo.append($("<div>", {class: "datetext"}));
			
			$day.append($dateInfo);
            $day.append($("<div>", {class: "datefooter"}));
			this.dates[dateID] = $day;
			$tr.append($day);
	
			//start a new row?
			if (ctr%7 === 6) {
				$tr = $("<tr>", {class: "monthrow"});
				this.$calBody.append($tr);
				if (this.showWeekHandles && k !== mLastDate.date()) {
					weeknum++;
					$tr.append(this.drawWeekHandle({clickable: true, className: "weekhandle", text: weeknum}));
				}
			}
			ctr++;
		}
		
		//draw the rest of the empty cells after the last date
		ctr--;
		while (ctr%7 < 6) {
			var $empty = $("<td>", {class: "dayempty", html: "&nbsp;"});
			$tr.append($empty);
			ctr++;
		}
	
		
		//set up event handler
		$(".tdday").on("vmousedown",this.doClickDay);
		
		
		//set up event handlers here to ensure calendar is drawn before scrolling
		if (this.showControls) {
			this.$prev.off("vmousedown").on("vmousedown",this.scrollPrevious);
			this.$next.off("vmousedown").on("vmousedown",this.scrollNext);
		}
			
	},
	
	
	
	setSelectedDateIDs: function(inRangeType,inSelDateID) {
		this.seltype = inRangeType;
		this.selectedDateIDs = [];
		
		var mSel = moment(inSelDateID,this.dateIDFormat);
		var mDt = mSel.clone().startOf(inRangeType);
		while (mDt.isBefore(mSel.clone().endOf(inRangeType))) {
			if (mDt.isSame(mSel,'month'))
				this.selectedDateIDs.push(mDt.format(this.dateIDFormat));
			mDt.add(1,"day");
		}
	},
	
	setSelection: function(opts) {
		opts = opts || {};
		var range =  opts.range || this.seltype;
		var id = this.tmpSelStartDateID || this.selectedDateIDs[0] || this.getDateID(this.initialDate);
		this.tmpSelStartDateID = null;
		var selector = "#"+id;
		if (range !== 'month')
		    selector += '.tdday';
		var $td = this.$cal.find(selector);
		//just in case, pick the first day
		if (!$td.length)
			$td = this.$cal.find(".day.tdday").first();

		switch(range) {
			case "day":
				this.selectDay($td);
				break;
			case "week":
				this.selectWeek($td);
				break;
			case "month":
				this.selectMonth($td);
				break;			
		}
		
		this.canClick = true;
	},
	
	//handle a day select.  Calling class can define "selectDay" to trigger updates to content.
	selectDay: function($td) {
		this.$cal.find(".selected").removeClass("selected");
		$td.addClass("selected");
		this.setSelectedDateIDs("day",$td.attr("id"));
		
		if (!_.isUndefined(this.selectorObj) && !_.isUndefined(this.selectorObj.selectDay))
			this.selectorObj.selectDay($td);
	},
	doClickDay: function(e) {
		//Get the event and the target $td
		if (!e) e = window.event;  // IE Event Model
		e.preventDefault();

		var $td = $(e.target);
		if ($(e.target).parents(".tdday").length > 0) {
			$td = $(e.target).parents(".tdday");
		}

		this.selectDay($td);

		if (!_.isUndefined(this.selectorObj) && !_.isUndefined(this.selectorObj.doClickDay))
			this.selectorObj.doClickDay($td);
	},

	//handle a week select.  Calling class can overwrite "selectWeek" to trigger updates to content.
	selectWeek: function($td) {
		this.$cal.find(".selected").removeClass("selected");
		var $tr = $td.closest("tr");
		$tr.addClass("selected");
		this.setSelectedDateIDs("week",$tr.find(".day").first().attr("id"));
		
		if (this.selectorObj && this.selectorObj.selectWeek)
			this.selectorObj.selectWeek();
	},
	doClickWeek: function(e) {
		//Get the event and the target $td
		if (!e) e = window.event;  // IE Event Model
		var $td = $(e.target);

		this.selectWeek($td);
	},
	
	//handle a month select.  Calling class can overwrite "selectMonth" to trigger updates to content.
	selectMonth: function() {
		var $td = $(".monthheader");

		this.$cal.find(".selected").removeClass("selected");
		$td.addClass("selected");
		this.setSelectedDateIDs("month",$td.attr("id"));

		if (this.selectorObj && this.selectorObj.selectMonth)
			this.selectorObj.selectMonth();
	},
	doClickMonth: function() {
		this.selectMonth();
	},

	setToDay: function(mSetToDay) {
        var setToDayID = mSetToDay.format(this.dateIDFormat);
        this.seltype = "day";

        if (!mSetToDay.isSame(moment(this.datebegin),"month")) {
            this.datebegin = mSetToDay.startOf("month").toDate();
            this.dateend = mSetToDay.clone().endOf("month").toDate();

            this.tmpSelStartDateID = setToDayID;

            this.drawCalendar();

            if (this.selectorObj && this.selectorObj.scrollAny)
                this.selectorObj.scrollAny();
            else
                this.setSelection();

        } else {
            this.selectDay(this.$cal.find("#"+setToDayID+".tdday"));
        }
	},
	clickToday: function() {
		var mToday = moment().startOf("day");
		this.setToDay(mToday);
	},
	
	scrollPrevious: function() {
		if (!this.canClick) return;
		
		var mNewStart = moment(this.datebegin).subtract(1,"month").startOf("month");
		this.datebegin = mNewStart.toDate();
		this.dateend = mNewStart.clone().endOf("month").toDate();
		
		//set a temporary selected start date to use later
		var mTmp;
		var mSelStartID = moment(this.selectedDateIDs[0],this.dateIDFormat);
		switch (this.seltype) {
			case "day":
				mTmp = mSelStartID.clone().subtract(1,"month");
				break;
			case "week":
			    var mNewMonthStart = mSelStartID.clone().subtract(1,"month");
			    mTmp = mNewMonthStart.clone().startOf("week");
			    if (!mTmp.isSame(mNewMonthStart,'month')) {
			        mTmp = mNewMonthStart.clone();
			    }
				break;
			case "month":
				mTmp = mSelStartID.clone().subtract(1,"month");
				break;
		}
		this.tmpSelStartDateID = mTmp.format(this.dateIDFormat);
		
		this.drawCalendar();
		
		if (this.selectorObj && this.selectorObj.scrollPrevious)
			this.selectorObj.scrollPrevious();
		else
			this.setSelection();
	},
	
	scrollNext: function() {
		if (!this.canClick) return;
		var mNewStart = moment(this.datebegin).add(1,"month").startOf("month");
		this.datebegin = mNewStart.toDate();
		this.dateend = mNewStart.clone().endOf("month").toDate();
		
		//set a temporary selected start date to use later
		var mTmp;
		var mSelStartID = moment(this.selectedDateIDs[0],this.dateIDFormat);
		switch (this.seltype) {
			case "day":
				mTmp = mSelStartID.clone().add(1,"month");
				break;
			case "week":
                var mNewMonthStart = mSelStartID.clone().add(1,"month");
                mTmp = mNewMonthStart.clone().startOf("week");
                if (!mTmp.isSame(mNewMonthStart,'month')) {
                    mTmp = mNewMonthStart.clone();
                }
				break;
			case "month":
				mTmp = mSelStartID.clone().add(1,"month");
				break;
		}
		this.tmpSelStartDateID = mTmp.format(this.dateIDFormat);

		this.drawCalendar();

		if (this.selectorObj && this.selectorObj.scrollNext)
			this.selectorObj.scrollNext();
		else
			this.setSelection();
	}
	
});
