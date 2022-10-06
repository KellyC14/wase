/********************************************/
/* AJAX/API calls for appointments          */
/********************************************/

var ApptViewController = function(options) {
    BaseController.call(this,options);
};
ApptViewController.prototype = Object.create(BaseController.prototype);
ApptViewController.prototype.constructor = ApptViewController;

_.extend(ApptViewController.prototype, {

    init: function() {
        this.appView = apptview;
    },
    showApptViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //getusercalendarstatus
        var isowner = false;
        var ismanager = false;
        var ismember = false;
        $xml.find("getusercalendarstatus").each(function() {
            isowner = ($(this).find("isowner").text() === "1");
            ismanager = ($(this).find("ismanager").text() === "1");
            ismember = ($(this).find("ismember").text() === "1");
        });
        apptview.loadUserStatus(isowner,ismanager,ismember);
        
        //sessionvar - MUST do before loadBlockData for labels
        var bc = [];
        $xml.find("sessionvar").each(function() {
			var sesvar = $(this).find("var").text();
			var val = $(this).find("val").text();
			if (sesvar === 'breadcrumbs')
				bc = val.split(",");
        });
        
        //getblockinfo_nocheck
        var blk = null;
        $xml.find("getblockinfo_nocheck").each(function() {
            blk = new Block($(this).find("block"));
            $(this).find("slot").each(function() {
                blk.addSlot(new Slot($(this)));
            });
            apptview.loadBlockData(blk,bc);
        });
        
        //getuserinfo for logged in user (if no appointment loading). Will be just one.
        $xml.find("getuserinfo").each(function() {
            var usr = new User($(this).find("userinfo"));
            apptview.loadLoggedInUserData(usr);
        }); 
        
        //getappointmentinfo
        $xml.find("getappointmentinfo").each(function() {           
            var appt = new Appointment($(this).find("appt"));
            apptview.loadApptData(appt);
        });
    },
    
    /*
     * get all data needed for page load 
     */
    loadApptViewData: function(inCalID,inBlockID,inApptID) {
        //block header info with available slots
        var str = "<getblockinfo_nocheck>"
            + this.buildSessionTag() 
            + this.buildTag('blockid',inBlockID);
        if (!isNullOrBlank(inApptID))
        	str += this.buildTag('editapp',inApptID);
        str += "</getblockinfo_nocheck>";
        
        //get calendar status only if not a guest
        if (isNullOrBlank(g_GuestEmail))
	        str += "<getusercalendarstatus>"
	            + this.buildSessionTag() 
	            + this.buildTag('userid',g_loggedInUserID)
	            + this.buildTag('calendarid',inCalID)
	            + "</getusercalendarstatus>";

        //If this is a new appointment, look up the logged in user.  Otherwise, look up the appointment
        if (isNullOrBlank(inApptID)) {
            if (!isNullOrBlank(g_loggedInUserID)) 
                str += "<getuserinfo>"
                    + this.buildSessionTag() 
                    + this.buildTag('userid',g_loggedInUserID)
                    + "</getuserinfo>";
        } else {
            str += "<getappointmentinfo>"
                + this.buildSessionTag() 
                + this.buildTag('apptid',inApptID)
                + "</getappointmentinfo>";
        }
        
        //breadcrumbs sessionvar
    	str += "<getvar><sessionid>" + g_sessionid + "</sessionid>";
		str += "<sessionvar><var>breadcrumbs</var></sessionvar>";
    	str += "</getvar>";
        
        this.callAJAX(str,this.showApptViewData);
    },
    
    /* text message email validation - extension */
    showValidEmail: function(data) {
        var callback = _.bind(apptview.showIsValidTxtEmail,apptview);
        BaseController.prototype.showValidEmail.call(this,data,callback);
    },
    isValidEmail: function(email) {
        BaseController.prototype.isValidEmail.call(this,email);
    }
    
});

//declare controller instance
var apptViewController = new ApptViewController();
