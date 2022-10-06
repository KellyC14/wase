/********************************************/
/* AJAX/API calls for the calendars view    */
/********************************************/

var CalendarsViewController = function(options) {
    BaseController.call(this,options);
};
CalendarsViewController.prototype = Object.create(BaseController.prototype);
CalendarsViewController.prototype.constructor = CalendarsViewController;

_.extend(CalendarsViewController.prototype, {

    init: function() {
        this.appView = calendarsview;
        this.msgText = '';
    },
    
    confirmUserStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //parameter SYSID
        var sysID = '';
        $xml.find("getparameter").each(function() {
        	sysID = $(this).find("value").text();
        });
        
        $xml.find("getuserstatus").each(function() {
        	var userid = $(this).find("userid").text();
            var isowner = isTrue($(this).find("isowner").text());
            var ismanager = isTrue($(this).find("ismanager").text());
            var ismember = isTrue($(this).find("ismember").text());
            var hasappts = isTrue($(this).find("hasappts").text());
            calendarsview.confirmUserStatus(isowner,ismanager,ismember,userid,hasappts,sysID);
        });
    },
    checkUserStatus: function() {
        var str = "<getuserstatus>"
            + this.buildSessionTag() 
            + this.buildTag('userid',getLoggedInUserID())
            + "</getuserstatus>";
        //add parm for 'WASE' system ID for help message
        str += '<getparameter><parameter>SYSID</parameter></getparameter>';
        this.callAJAX(str,this.confirmUserStatus);
    },
    
    showManagedCals: function($xml) {
        gManagedCals = []; //reset 
        gManagedPendingCals = []; //reset
        $xml.find("getmanagedcalendars").each(function() {
            var stat = $(this).children("status").text();
            $(this).children("calendar").each(function() {
                var cal = new WASECalendar($(this));
                if (stat === 'active')
                    gManagedCals.push(cal);
                else
                    gManagedPendingCals.push(cal);
            });
        });
        calendarsview.loadManagedCalendars();
        if (gManagedPendingCals.length > 0)
            calendarsview.loadManagedPendingCalendars();
    },
    showMemberCals: function($xml) {
        gMemberCals = []; //reset 
        gMemberPendingCals = []; //reset
        $xml.find("getmembercalendars").each(function() {
            var stat = $(this).children("status").text();
            $(this).find("calendar").each(function() {
                var cal = new WASECalendar($(this));
                if (stat === 'active')
                    gMemberCals.push(cal);
                else
                    gMemberPendingCals.push(cal);
            });
        });
        calendarsview.loadMemberCalendars();
        if (gMemberPendingCals.length > 0)
            calendarsview.loadMemberPendingCalendars();
    },
    showCalendarsViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //owned
        gOwnedCals = []; //reset
        $xml.find("getownedcalendars").each(function() {
            $(this).find("calendar").each(function() {
                gOwnedCals.push(new WASECalendar($(this)));
            });
        });
        calendarsview.loadOwnedCalendars();
        
        //managed
        this.showManagedCals($xml);
        
        //member
        this.showMemberCals($xml);
        
        //if there is messagetext, show it
        if (!isNullOrBlank(this.msgText)) {
            $('.loader').hide();
            gMessage.displayConfirm(this.msgText);
            this.msgText = '';
        }
        
        //anything to do?
        if (calendarsview.anyToDos)  {
        	gMessage.displayConfirm("Note: you have pending manager/member requests.");
        	calendarsview.anyToDos = false;
        }
        
        //fill in the parameter after all sections are created
        controller.getParameters(["NETID","ALERTMSG"]);
    },
    
    getManagedXML: function() {
        var str = "<getmanagedcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','active')
            + "</getmanagedcalendars>";
        
        str += "<getmanagedcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','pending')
            + "</getmanagedcalendars>";
        return str;
    },
    getMemberXML: function() {
        var str = "<getmembercalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','active')
            + "</getmembercalendars>";

        str += "<getmembercalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('status','pending')
            + "</getmembercalendars>";
        return str;
    },
    loadCalendarsViewData: function() {
        var str = "<getownedcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getownedcalendars>";
        
        str += this.getManagedXML();
        str += this.getMemberXML();

        this.callAJAX(str,this.showCalendarsViewData);
    },
    
    updateManagedCals: function(data) {
        //check for errors then get the 'new' managed/pending managed
        var $xml = $(data);
        this.doErrorCheck($xml);
        
        this.loadCalendarsViewData();
        this.msgText = "Manage request sent.  You will be notified when request is confirmed or denied.";
    },
    applyToManage: function(inCalIDs,inEmailText) {
        var str = '';
        _.each(inCalIDs, function(calid) {
            str += "<applytomanagecalendar>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('calendarid',calid)
            + this.buildTag('emailtext',inEmailText)
            + "</applytomanagecalendar>";
        }, this);
        this.callAJAX(str,this.updateManagedCals);
    },
    
    updateMemberCals: function(data) {
        //check for errors then get the 'new' member/pending member
        var $xml = $(data);
        this.doErrorCheck($xml);

        this.loadCalendarsViewData();
        this.msgText = "Member request sent.  You will be notified when request is confirmed or denied.";
    },
    applyToMember: function(inCalIDs,inEmailText) {
        var str = '';
        _.each(inCalIDs, function(calid) {
            str += "<applytomembercalendar>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('calendarid',calid)
            + this.buildTag('emailtext',inEmailText)
            + "</applytomembercalendar>";
        }, this);
        this.callAJAX(str,this.updateMemberCals);
    },
    
    showCalResults: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        gApplyCals = []; //reset
        //getcalendars
        $xml.find("getcalendars").each(function() {
            $(this).find("calendar").each(function() {
                gApplyCals.push(new WASECalendarHeader($(this)));
            });
        });
        loadApplyCalendars();

    },
    searchCalendarsByNetIDs: function(inUserIDs) {
        //getcalendars
        var str = "<getcalendars>"
            + this.buildSessionTag() 
            + this.buildTag('userid',inUserIDs)
            + "</getcalendars>";
        this.callAJAX(str,this.showCalResults);
    },
    
    showEditedManager: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        this.loadCalendarsViewData();
        this.msgText = "Manager Added.";
    },
    editManager: function(inMgr) {
        var str = '<editmanager>'
            + this.buildSessionTag()
            + inMgr.toXML('manager')
            + '</editmanager>';
        this.callAJAX(str,this.showEditedManager);
    },

    showEditedMember: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
            
        this.loadCalendarsViewData();
        this.msgText = "Member Added.";
    },
    editMember: function(inMem) {
        var str = '<editmember>'
            + this.buildSessionTag()
            + inMem.toXML('member')
            + '</editmember>';

        this.callAJAX(str,this.showEditedMember);
    },

    showDeletedManager: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
            
        this.loadCalendarsViewData();
        this.msgText = "Manager request denied.";
    },
    deleteManager: function(inMgr) {
        var str = '<deletemanager>'
            + this.buildSessionTag()
            + inMgr.toXML('manager')
            + '</deletemanager>';

        $('.loader').show();
        this.callAJAX(str,this.showDeletedManager);
    },

    showDeletedMember: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
            
        this.loadCalendarsViewData();
        this.msgText = "Member request denied.";
    },
    deleteMember: function(inMem) {
        var str = '<deletemember>'
            + this.buildSessionTag()
            + inMem.toXML('member')
            + '</deletemember>';

        this.callAJAX(str,this.showDeletedMember);
    },


});

//declare controller instance
var calendarsViewController = new CalendarsViewController();
