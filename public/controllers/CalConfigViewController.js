/********************************************/
/* AJAX/API calls for cal config view       */
/********************************************/

var CalConfigViewController = function(options) {
    BaseController.call(this,options);
};
CalConfigViewController.prototype = Object.create(BaseController.prototype);
CalConfigViewController.prototype.constructor = CalConfigViewController;

_.extend(CalConfigViewController.prototype, {

    showCalConfigViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //getparameter
        var parm, val;
        $xml.find("getparameter").each(function() {
        	parm = $(this).find("parameter").text();
			val = $(this).find("value").text();
			if (parm === "WAITLIST") {
			    val = isTrue(val);
			    gIsWaitlistOn = val;
	            calendarconfigview.setWaitlistParmVal(val);
			}
			gParms[parm] = val;
        });
        calendarconfigview.setParms();
        
        //getcalendars
        $xml.find("getcalendars").each(function() {
            $(this).find("calendar").each(function() {
                calendarconfigview.loadCalendarData(new WASECalendar($(this)));
            });
        });
        
        //getblockcount
        var numblocks = 0;
        var nummemberblocks = 0;
        $xml.find("getblockcount").each(function() {           
            numblocks = $(this).find("numblocks").text();
            nummemberblocks = $(this).find("nummemberblocks").text();
        });
        calendarconfigview.setNumBlocks(numblocks,nummemberblocks);
        
        //getuserinfo for logged in user (if no calendar loading). Will be just one.
        $xml.find("getuserinfo").each(function() {
            var usr = new User($(this).find("userinfo"));
            calendarconfigview.loadLoggedInUserData(usr);
        });
        
        //getlabels (if editing calendar)
        var labels = {};
        $xml.find("getlabels").each(function() {
            _.each($(this).find("labels")[0].childNodes, function(el) {
            	labels[el.nodeName] = $(el).text();
            });
            calendarconfigview.drawLabelsSection(labels);
        });
    },
    
    /*
     * get all data needed for page load 
     */
    loadCalConfigViewData: function(inCalID,inStartDateTime,inParmArray) {
        var str = ""; 
        
        //Get parameters needed for display
        _.each(inParmArray, function(parm) {
        	str += "<getparameter><parameter>" + parm + "</parameter></getparameter>";
        });
        
        //If this is a new calendar, look up the logged in user.  Otherwise, look up the calendar
        if (isNullOrBlank(inCalID) && !isNullOrBlank(g_loggedInUserID)) {
            str += "<getuserinfo>"
	            + this.buildSessionTag() 
	            + this.buildTag('userid',g_loggedInUserID)
	            + "</getuserinfo>";
            
            str += "<getlabels>"
            	+ "</getlabels>";

        } else {
            str += "<getcalendars>"
                + this.buildSessionTag() 
                + this.buildTag('calendarid',inCalID)
                + "</getcalendars>";
            
            var sdt = isNullOrBlank(inStartDateTime) ? "" : formatDateTimeforServer(inStartDateTime);
            str += "<getblockcount>"
                + this.buildSessionTag() 
                + this.buildTag('calendarid',inCalID)
                + this.buildTag('startdatetime',sdt)
                + "</getblockcount>";
        }
        
        this.callAJAX(str,this.showCalConfigViewData);
    },

    showSyncedStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //synccalendar
        var $sync = $xml.find('synccalendar');
        var calid = $sync.find("calendarid").text();
        var ical = $sync.find("ical").html();
        var numblocks = $sync.find("blocks").text();
        var numapps = $sync.find("apps").text();

        calendarconfigview.showCalSync(calid,ical,numblocks,numapps);
    },
    syncCalendar: function(inUserID,inWhat,inCalID) {
        var str = '<synccalendar>'
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('whichblocks',inWhat)
            + this.buildTag('calendarid',inCalID)
            + '</synccalendar>';
        this.callAJAX(str,this.showSyncedStatus);
    },
    
    showSyncCounts: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //synccalendarcount
        var fromTodayBlocks = 0;
        var fromTodayApps = 0;
        var allBlocks = 0;
        var allApps = 0;
        $xml.find("synccalendarcount").each(function() {
        	var which = $(this).find('whichblocks').text();
        	if (which === 'fromtoday') {
        		fromTodayBlocks = $(this).find("blocks").text();
        		fromTodayApps = $(this).find("apps").text();
        	} else { //all
        		allBlocks = $(this).find("blocks").text();
        		allApps = $(this).find("apps").text();
        	}
        });

    	calendarconfigview.openSyncCal(fromTodayBlocks,fromTodayApps,allBlocks,allApps);
    },   
    getSyncCounts: function(inUserID,inCalID) {
        var str = '<synccalendarcount>'
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('whichblocks','fromtoday')
            + this.buildTag('calendarid',inCalID)
            + '</synccalendarcount>'
            + '<synccalendarcount>'
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('whichblocks','all')
            + this.buildTag('calendarid',inCalID)
            + '</synccalendarcount>';
        this.callAJAX(str,this.showSyncCounts);
    	
    }

});

//declare controller instance
var calconfigViewController = new CalConfigViewController();
