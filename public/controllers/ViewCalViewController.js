/********************************************/
/* AJAX/API calls for the view calendar page*/
/********************************************/

var ViewCalViewController = function(options) {
    BaseController.call(this,options);
};
ViewCalViewController.prototype = Object.create(BaseController.prototype);
ViewCalViewController.prototype.constructor = ViewCalViewController;

_.extend(ViewCalViewController.prototype, {

    init: function() {
        this.appView = viewcalview;
    },
    readBlocksWithSlotsXML: function($xml) {
        var blocks = {};
        $xml.find("blockswithslots").each(function() {
            //one block
            var blk = new BlockHeader($(this).find("block"));
            blk.numAvailAppts = $(this).find("freeslots").text();
            //can have many slots
            $(this).find("slot").each(function() {
                blk.addSlot(new Slot($(this)));
            });
            var strStart = moment(blk.startdatetime).format("YYYYMMDD");
            if (!blocks[strStart]) blocks[strStart] = [];
            blocks[strStart].push(blk);
        });
        return blocks;
    },
    
    readSlotJSON: function(slt,args) {
    	//if there are no slots, just return
    	if (typeof slt === 'undefined') return;
    	
		var s = new Slot({fromJSON: true, obj: slt});
		args.blk.addSlot(s);
    },
    readBlockJSON: function(bs,args) {
    	//if there are no blocks, just return
    	if (typeof bs === 'undefined') return;
    	
    	var blk = new BlockHeader({fromJSON: true, obj: bs.block});
    	blk.numAvailAppts = bs.freeslots;

    	this.checkJSONObjectOrArray(bs.slot, this.readSlotJSON, {"blk": blk});

    	var strStart = moment(blk.startdatetime).format("YYYYMMDD");
        if (!args.blocks[strStart]) args.blocks[strStart] = [];
        args.blocks[strStart].push(blk);
    },
    showViewCalDataJSON: function(data,stat,xhr) {
        if (this.doJSONErrorCheck(data)) return false; 
        
        var calDates = data.getcalendardates.caldate;
        var blockswithslots = data.getblockswithallslots.blockswithslots;
        
        var blocks = {};
        this.checkJSONObjectOrArray(blockswithslots, this.readBlockJSON, {"blocks": blocks});

        viewcalview.loadData(calDates, blocks);
    },
    showViewCalData: function(data,stat,xhr) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //getcalendardates
        var calDates = [];
        $xml.find("getcalendardates caldate").each(function() {         
            calDates.push(new CalDate($(this)));
        });
        
        viewcalview.loadData(calDates, this.readBlocksWithSlotsXML($xml));
    },
    
    getGetBlocksWithAllSlotsXML: function(calIDs, sdt, edt) {
        var controller = this;
        var str = "";
        _.each(calIDs, function(id) {
            str += "<getblockswithallslots>"
                + controller.buildSessionTag() 
                + controller.buildTag('calendarid',id)
                + controller.buildTag('startdatetime',sdt)
                + controller.buildTag('enddatetime',edt)
                + "</getblockswithallslots>";
        });
        return str;
    },
    loadViewCalData: function(opts) {
        opts = opts || {};
        var sdt = !isNullOrBlank(opts.sdt) ? formatDateTimeforServer(opts.sdt) : '';
        var edt = !isNullOrBlank(opts.edt) ? formatDateTimeforServer(opts.edt) : '';
        var arrCalIDs = opts.calIDs;

        //this.loadViewCalDataXML(arrCalIDs, sdt, edt);
    	this.loadViewCalDataJSON(arrCalIDs, sdt, edt);
    },
    loadViewCalDataXML: function(arrCalIDs, sdt, edt) {
        //getcalendardates
        var str = "<getcalendardates>"
            + this.buildSessionTag() 
            + this.buildTag('calendarid',arrCalIDs.join())
            + this.buildTag('startdatetime',sdt)
            + this.buildTag('enddatetime',edt)
            + "</getcalendardates>";
        
        //getblockswithallslots
        str += this.getGetBlocksWithAllSlotsXML(arrCalIDs, sdt, edt);
        this.callAJAX(str,this.showViewCalData);
    },
    loadViewCalDataJSON: function(arrCalIDs, sdt, edt) {
        var obj = {
    		getcalendardates: {
    			sessionid: g_sessionid,
    			calendarid: arrCalIDs.join(),
    			startdatetime: sdt,
    			enddatetime: edt
    		},
    		getblockswithallslots: {
    			sessionid: g_sessionid,
    			calendarid: arrCalIDs.join(),
    			startdatetime: sdt,
    			enddatetime: edt
    		}
        };
        this.callAJAXJSON(obj,this.showViewCalDataJSON);
    },
    
    showCalendarSelectorData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        var cals = {}; 
        var oIDs = [], mIDs = [], memIDs = [];  
        var cal = null;
        
        //owned
        $xml.find("getownedcalendarheaders").each(function() {
            $(this).find("calendar").each(function() {
                cal = new WASECalendarHeader($(this));
                var id = cal.get('calendarid');
                cals[id] = cal;
                oIDs.push(id);
            });
        });

        //member
        $xml.find("getmembercalendarheaders").each(function() {
            $(this).find("calendar").each(function() {
                cal = new WASECalendarHeader($(this));
                var id = cal.get('calendarid');
                cals[id] = cal;
                memIDs.push(id);
            });
        });
        
        //managed
        $xml.find("getmanagedcalendarheaders").each(function() {
            $(this).children("calendar").each(function() {
                cal = new WASECalendarHeader($(this));
                var id = cal.get('calendarid');
                cals[id] = cal;
                mIDs.push(id);
            });
        });
        
        viewcalview.calSelector.loadCals(cals, oIDs, mIDs, memIDs);
    },
    
    loadCalendarSelectorData: function(opts) {
        var str = "<getownedcalendarheaders>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getownedcalendarheaders>";
        
        str += "<getmanagedcalendarheaders>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','active')
            + "</getmanagedcalendarheaders>";
        
        str += "<getmembercalendarheaders>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','active')
            + "</getmembercalendarheaders>";

        this.callAJAX(str,this.showCalendarSelectorData);
    },
    
    confirmUserStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        //usercalendarstatus - will be one and only one
        $xml.find("getusercalendarstatus").each(function() {
        	var userid = $(this).find("userid").text();
        	var calid = $(this).find("calendarid").text();
            var isowner = isTrue($(this).find("isowner").text());
            var ismanager = isTrue($(this).find("ismanager").text());
            var ismember = isTrue($(this).find("ismember").text());
            var calhasblocks = isTrue($(this).find("calhasblocks").text());
            var calhasmembers = isTrue($(this).find("calhasmembers").text());
            var calhasmanagers = isTrue($(this).find("calhasmanagers").text());
            viewcalview.confirmUserStatus(isowner,ismanager,ismember,userid,calid,calhasblocks,calhasmembers,calhasmanagers);
        });
    },
    checkUserStatus: function(inCalID) {
        var str = "<getusercalendarstatus>"
            + this.buildSessionTag() 
            + this.buildTag('calendarid',inCalID)
            + this.buildTag('userid',g_loggedInUserID)
            + "</getusercalendarstatus>";
        this.callAJAX(str,this.confirmUserStatus);
    },
    
    showCalendarSessionVars: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        var dtID = '';
        var range = 'week';
        $xml.find("sessionvar").each(function() {
            sesvar = $(this).find("var").text();
            val = $(this).find("val").text();
            if (sesvar === 'viewcal_calrangetype') range = val;
            else if (sesvar === 'viewcal_selstartdate') dtID = val;
        });
        viewcalview.dateSelector.setCalSelectedDates(range,dtID);
    },
    getCalendarSessionVars: function() {
        var str = "<getvar>"
            + this.buildSessionTag() 
            + "<sessionvar><var>viewcal_calrangetype</var></sessionvar>"
            + "<sessionvar><var>viewcal_selstartdate</var></sessionvar>"
            + "</getvar>";        
        this.callAJAX(str,this.showCalendarSessionVars);
    },
    
    showUpdatedBlockInfo: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        viewcalview.loadUpdatedBlock(this.readBlocksWithSlotsXML($xml));
    },
    getUpdatedBlockInfoForDate: function(calIDs, inSDT) {
    	var mSDT = moment(inSDT).hours(0).minutes(0).seconds(0);
    	var mEDT = moment(mSDT).hours(23).minutes(59).seconds(59);
        
        //this.getUpdatedBlockInfoForDateXML(calIDs, formatDateTimeforServer(mSDT), formatDateTimeforServer(mEDT));
    	this.getUpdatedBlockInfoForDateJSON(calIDs, formatDateTimeforServer(mSDT), formatDateTimeforServer(mEDT));
    },
    getUpdatedBlockInfoForDateXML: function(calIDs, sdt, edt) {
        var str = this.getGetBlocksWithAllSlotsXML(calIDs, sdt, edt);
        this.callAJAX(str,this.showUpdatedBlockInfo);
    },
    getUpdatedBlockInfoForDateJSON: function(arrCalIDs, sdt, edt) {
        var obj = {
    		getcalendardates: {
    			sessionid: g_sessionid,
    			calendarid: arrCalIDs.join(),
    			startdatetime: sdt,
    			enddatetime: edt
    		},
    		getblockswithallslots: {
    			sessionid: g_sessionid,
    			calendarid: arrCalIDs.join(),
    			startdatetime: sdt,
    			enddatetime: edt
    		}
        };
        this.callAJAXJSON(obj,this.showUpdatedBlockInfoJSON);
    }
    
});

//declare controller instance
var viewCalViewController = new ViewCalViewController();
