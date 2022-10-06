/********************************************/
/* AJAX/API calls for the make appt page    */
/********************************************/

var MakeApptViewController = function(options) {
    BaseController.call(this,options);
};
MakeApptViewController.prototype = Object.create(BaseController.prototype);
MakeApptViewController.prototype.constructor = MakeApptViewController;

_.extend(MakeApptViewController.prototype, {

    init: function() {
        this.appView = makeapptview;
    },

    /* get calendars results */
    showMakeApptSearchResults: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        gCals = []; //reset

        //getmatchingcalendars
        $xml.find("getmatchingcalendars").each(function() {         
            $(this).find("calendar").each(function() {
                gCals.push(new WASECalendarHeader($(this)));
            });
        });
        makeapptview.loadCalendars();   
    },
    searchCalendarsByString: function(inString) {
        //getmatchingcalendars
        var str = "<getmatchingcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('searchstring',inString)
            + "</getmatchingcalendars>";        
        this.callAJAX(str,this.showMakeApptSearchResults);
    },
    searchCalendarsByCalID: function(inCalID) {
        //getmatchingcalendars
        var str = "<getmatchingcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('calendarid',inCalID)
            + "</getmatchingcalendars>";        
        gShownCalID = inCalID;
        this.callAJAX(str,this.showMakeApptSearchResults);
    },
    
    /* get block dates for calendar */
    showBlockDates: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        //read block dates
        var dateObjs = [];
        var calID = '';
        var myblockid='';
        var myblockids= [];
        $xml.find("getblockdates").each(function() {
            calID = $(this).find("calendarid").text();
            $(this).find("blockdate").each(function(ind,blkdate) {
                myblockid=$(blkdate).find("myblockid").text();
                //console.log("myblockid["+myblockid+"]");
                myblockids.push(myblockid);
                dateObjs.push({
                    date: formatDateTimeFromServer($(blkdate).find("date").text()),
                    makeable: isTrue($(blkdate).find("makeable").text()),
                    showappinfo: $(blkdate).find("showappinfo").text(),
                    hasappts: isTrue($(blkdate).find("hasappts").text())
                });
            });
        });
        //waitlistentry
        var entries = [];
        $xml.find("waitlistentry").each(function() {
            entries.push(new WaitlistEntry($(this)));
        });
        var idlist=myblockids.join(",");
        //console.log("call makeapptview.showCalendarSection_blockids-idlist[" + idlist +"]");
        //makeapptview.showCalendarSection(calID, dateObjs, entries.length > 0);
        makeapptview.showCalendarSection_blockids(calID, dateObjs, entries.length > 0,myblockids);
        
        //usercalendarstatus
        var isowner = false;
        var ismanager = false;
        var ismember = false;
        if ($xml.find("getusercalendarstatus")) {
            $xml.find("getusercalendarstatus").each(function() {
                isowner = isTrue($(this).find("isowner").text());
                ismanager = isTrue($(this).find("ismanager").text());
                ismember = isTrue($(this).find("ismember").text());
            });
        }
        makeapptview.setUserStatus(isowner,ismanager,ismember);
    },
    showBlockDatesJSON: function(data) {
        if (this.doJSONErrorCheck(data)) return false;

        //block dates
        var dateObjs = [];
        var calid = data.getblockdates.calendarid;
        _.each(data.getblockdates.blockdate, function(bd) {
        	dateObjs.push({
                date: formatDateTimeFromServer(bd.date),
                makeable: isTrue(bd.makeable),
                showappinfo: bd.showappinfo,
                hasappts: isTrue(bd.hasappts)
            });
        });
        //waitlistentry
        var hasEntry = typeof data.getwaitlistforcalendaranduser.waitlistentry !== 'undefined';
        makeapptview.showCalendarSection(calid, dateObjs, hasEntry);
        
        //usercalendarstatus
        var stat = data.getusercalendarstatus;
        makeapptview.setUserStatus(stat.isowner,stat.ismanager,stat.ismember);
    },
    getBlockDatesJSON: function(obj) {
        this.callAJAXJSON(obj,this.showBlockDatesJSON);
    },
    getBlockDatesXML: function(obj) {
    	var str = this.buildXMLFromObject(obj);
        this.callAJAX(str,this.showBlockDates);
    },
    getBlockDates: function(inCalID,inBlockID) {
        var userid = !isNullOrBlank(g_loggedInUserID) ? g_loggedInUserID : g_GuestEmail;
        var obj = {
    		getblockdates: {
    			sessionid: g_sessionid,
    			calendarid: inCalID,
    			startdatetime: formatDateTimeforServer(new Date()),
                blockid: inBlockID,
    		},
    		getwaitlistforcalendaranduser: {
    			sessionid: g_sessionid,
    			calendarid: inCalID,
    			userid: userid,
    		}
        };
        if (!isNullOrBlank(g_loggedInUserID)) {
        	obj.getusercalendarstatus = {
    			sessionid: g_sessionid,
    			calendarid: inCalID,
    			userid: userid,
        	}
        }
        //console.log("call getBlockDatesXML");
        this.getBlockDatesXML(obj);
    	//this.getBlockDatesJSON(obj);
    },
    
    /* get blocks for block date */
    showBlocks: function(data) {
    	//console.log($.isXMLDoc(data));
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        var calid = $xml.find("getblockswithslotsandmyappts").children("calendarid").text();

        //getblockswithslotsandmyappts
        gBlocks = []; //empty it
        $xml.find("blockswithslots").each(function() {
            //one block
            var blk = new BlockHeader($(this).find("block"));
            //can have many slots
            $(this).find("slot").each(function() {
                blk.addSlot(new Slot($(this)));
            });
            gBlocks.push(blk);
        });
        makeapptview.loadBlocks(calid);
    },
    showBlocksJSON: function(data) {
        if (this.doJSONErrorCheck(data)) return false;

        var calid = data.getblockswithslotsandmyappts.calendarid;
        var blockswithslots = data.getblockswithslotsandmyappts.blockswithslots;

        gBlocks = []; //empty it
        _.each(blockswithslots, function(bs) {
        	var blk = new BlockHeader({fromJSON: true, obj: bs.block});
        	if (Array.isArray(bs.slot)) {
	        	_.each(bs.slot, function(slt) {
	        		var s = new Slot({fromJSON: true, obj: slt});
	        		blk.addSlot(s);
	        	});
        	} else {
        		var s = new Slot({fromJSON: true, obj: bs.slot});
        		blk.addSlot(s);
        	}
            gBlocks.push(blk);
        });
        makeapptview.loadBlocks(calid);
    },
    getBlocksByID: function(inCalID,inBlockID) {
        var userid = !isNullOrBlank(g_loggedInUserID) ? g_loggedInUserID : g_GuestEmail;
        
        var str = "<getblockswithslotsandmyappts>"
            + this.buildSessionTag() 
            + this.buildTag('calendarid',inCalID)
            + this.buildTag('userid',userid)
            + this.buildTag('blockid',inBlockID)
            + "</getblockswithslotsandmyappts>";        
        this.callAJAX(str,this.showBlocks);
    },
    getBlocksByDatesJSON: function(obj) {
        this.callAJAXJSON(obj,this.showBlocksJSON);
    },
    getBlocksByDatesXML: function(obj) {
    	var str = this.buildXMLFromObject(obj);
        this.callAJAX(str,this.showBlocks);
    },
    getBlocksByDates: function(inCalID,inSDT,inEDT) {
        var userid = !isNullOrBlank(g_loggedInUserID) ? g_loggedInUserID : g_GuestEmail;
        var obj = {
    		getblockswithslotsandmyappts: {
    			sessionid: g_sessionid,
    			calendarid: inCalID,
    			startdatetime: formatDateTimeforServer(inSDT),
    			enddatetime: formatDateTimeforServer(inEDT),
    			userid: userid
    		}
        };
        this.getBlocksByDatesXML(obj);
    	//this.getBlocksByDatesJSON(obj);
    },

    /* get courses */
    showCourseCals: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //getmycourses
        var courses = [];
        $xml.find("getmycourses courses").each(function() {
            $(this).find("course").each(function() {
                courses.push(new Course($(this)));
            });
        });
        makeapptview.loadCourses(courses);
    },
    getCourseCals: function() {
        //getmycourses
        var str = "<getmycourses>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getmycourses>";        
        this.callAJAX(str,this.showCourseCals);
    },
    
    /* waitlist entry */
    showWaitlistEntry: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //waitlistentry
        $xml.find("waitlistentry").each(function() {        
            var wlentry = new WaitlistEntry($(this));
            makeapptview.showWaitlistAdded(wlentry);
        });
    },
    addWaitlistEntry: function(inWaitlistEntry) {
        var str = "<addwaitlistentry>"
            + this.buildSessionTag() 
            + inWaitlistEntry.toXML()
            + "</addwaitlistentry>";        
        this.callAJAX(str,this.showWaitlistEntry);
    },
    
    loadUserInfo: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        $xml.find("getuserinfo").each(function() {
            makeapptview.user = new User($(this).find("userinfo"));
            makeapptview.calendarViews[gShownCalViewID].waitlist.setUpWaitlist();
        }); 
        
    },
    getUserInfo: function() {
        var str = "<getuserinfo>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getuserinfo>";        
        this.callAJAX(str,this.loadUserInfo);
    },
    
    /* text message email validation - extension */
    showValidEmail: function(data) {
        var callback = _.bind(makeapptview.showIsValidTxtEmail,makeapptview);
        BaseController.prototype.showValidEmail.call(this,data,callback);
    },
    isValidEmail: function(email) {
        BaseController.prototype.isValidEmail.call(this,email);
    },
    
    /* quick add appointment */
    showAppt: function(data) {
        var $xml = $(data);
        //intercept error re: overlapping appointments
        var isOverlap = false;
        $xml.find("error").each(function() {
            var errc = $(this).children("errorcode").text();
            if (errc === '15') {
            	gDetailText = $(this).children("errortext").text();
            	$("#popupNotifyOverlap").popup("open");
            	isOverlap = true;
            }
        });
        if (isOverlap) return false;

        if (this.doErrorCheck($xml)) return false;
        $xml.find("addappointment").each(function() {           
            var appt = new Appointment($(this).find("appt"));
            makeapptview.showAddedAppt(appt);
        });
    },
    addAppt: function(inAppt,isForce) {
    	var force = typeof isForce !== 'undefined' ? isForce : false;
    	this.newAppt = inAppt;
        var str = '<addappointment>'
            + this.buildSessionTag()
            + inAppt.toXML();
        if (force)
        	str += '<force>1</force>';
		str += "</addappointment>";
        this.callAJAX(str,this.showAppt);
    },

    showDeletedAppt: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        makeapptview.showDeletedAppt();
    },
    deleteAppt: function(inApptID,inCancelText) {
        var str = '<deleteappointment>'
            + this.buildSessionTag()
            + this.buildTag('appointmentid',inApptID)
            + this.buildTag('canceltext',inCancelText)
            + '</deleteappointment>';
            this.callAJAX(str,this.showDeletedAppt);
    },
    
    confirmUserStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        $xml.find("getuserstatus").each(function() {
        	var userid = $(this).find("userid").text();
            var isowner = isTrue($(this).find("isowner").text());
            var ismanager = isTrue($(this).find("ismanager").text());
            var ismember = isTrue($(this).find("ismember").text());
            var hasappts = isTrue($(this).find("hasappts").text());
            makeapptview.confirmUserStatus(isowner,ismanager,ismember,userid,hasappts);
        });
    },
    checkUserStatus: function() {
        var str = "<getuserstatus>"
            + this.buildSessionTag() 
            + this.buildTag('userid',getLoggedInUserID())
            + "</getuserstatus>";
        this.callAJAX(str,this.confirmUserStatus);
    },

});

//declare controller instance
var makeapptViewController = new MakeApptViewController();
