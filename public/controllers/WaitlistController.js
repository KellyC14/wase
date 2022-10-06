/********************************************/
/* AJAX/API calls for waitlists             */
/********************************************/

var WaitlistController = function(options) {
    BaseController.call(this,options);
};
WaitlistController.prototype = Object.create(BaseController.prototype);
WaitlistController.prototype.constructor = WaitlistController;

_.extend(WaitlistController.prototype, {

    init: function() {
        //this.appView = ;
    },
    
    showEnableWaitlist: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        //nothing to show
    },
    enableWaitlist: function(inCalendarID,inIsOn) {
        var str = '<enablewaitlist>'
            + this.buildSessionTag()
            + this.buildTag('calendarid',inCalendarID)
            + this.buildTag('enable',getServerBoolean(inIsOn))
            + "</enablewaitlist>";
        this.callAJAX(str,this.showEnableWaitlist);
    },
    
    showWaitlistForCalendar: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //waitlistentry
        var entries = [];
        $xml.find("waitlistentry").each(function() {
            entries.push(new WaitlistEntry($(this)));
        }); 
        calendarconfigview.waitlistView.drawEntries(entries);
    },
    getWaitlistForCalendar: function(inCalendarID) {
        var str = '<getwaitlistforcalendar>'
            + this.buildSessionTag()
            + this.buildTag('calendarid',inCalendarID)
            + "</getwaitlistforcalendar>";
        this.callAJAX(str,this.showWaitlistForCalendar);
    },

    showCalInfoForWaitlist: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        var cals = [];
        $xml.find("getmatchingcalendars").each(function() {
            $(this).find("calendar").each(function() {
                cals.push(new WASECalendarHeader($(this)));
            });
        });
        WaitlistEntry.loadCalendarInfo(cals);
    },
    showWaitlistForUser: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //waitlistentry
        var entries = [];
        var calids = [];
        $xml.find("waitlistentry").each(function() {
            var wl = new WaitlistEntry($(this));
            entries.push(wl);
            calids.push(wl.get('calendarid'));
        });
        myapptsview.showWaitlist(entries);
        
        //now look up cal info, if there are any entries
        if (calids.length) {
	        var str = '<getmatchingcalendars>'
	            + this.buildSessionTag()
	            + this.buildTag('calendarid',calids.join(','))
	            + "</getmatchingcalendars>";
	        this.callAJAX(str,this.showCalInfoForWaitlist);
        }
    },
    getWaitlistForUser: function() {
        var userid = !isNullOrBlank(g_loggedInUserID) ? g_loggedInUserID : g_GuestEmail;
        var str = '<getwaitlistforuser>'
            + this.buildSessionTag()
            + this.buildTag('userid',userid)
            + "</getwaitlistforuser>";
        this.callAJAX(str,this.showWaitlistForUser);
    },

    showWaitlistEntry: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //waitlistentry
        $xml.find("waitlistentry").each(function() {        
            calendarconfigview.waitlistView.addEntry(new WaitlistEntry($(this)));
        });
    },
    addWaitlistEntry: function(inWaitlistEntry) {
        var str = '<addwaitlistentry>'
            + this.buildSessionTag()
            + inWaitlistEntry.toXML()
            + "</addwaitlistentry>";
        this.callAJAX(str,this.showWaitlistEntry);
    },

    showEditedWaitlistEntry: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //waitlistentry
        $xml.find("waitlistentry").each(function() {
            var wlVw = typeof calendarconfigview !== 'undefined' ? calendarconfigview.waitlistView : myapptsview.waitlistView;
            wlVw.showEditedEntry(new WaitlistEntry($(this)));
        });
    },
    editWaitlistEntry: function(inWaitlistEntry) {
        var str = '<editwaitlistentry>'
            + this.buildSessionTag()
            + inWaitlistEntry.toXML()
            + "</editwaitlistentry>";
        this.callAJAX(str,this.showEditedWaitlistEntry);
    },

    showDeletedWaitlistEntry: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        $xml.find("waitlistentry").each(function() {
            infmsg = $(this).text();
            var wlVw = typeof calendarconfigview !== 'undefined' ? calendarconfigview.waitlistView : myapptsview.waitlistView;
            wlVw.showRemovedEntry(new WaitlistEntry($(this)));
        });
    },
    deleteWaitlistEntry: function(inWaitlistEntryID,inDeleteText) {
        var str = '<deletewaitlistentry>'
            + this.buildSessionTag()
            + this.buildTag('waitid',inWaitlistEntryID)
            + this.buildTag('deletetext',inDeleteText)
            + "</deletewaitlistentry>";
        this.callAJAX(str,this.showDeletedWaitlistEntry);
    },
    
});

//declare controller instance
var waitlistController = new WaitlistController();
