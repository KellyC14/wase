/********************************************/
/* AJAX/API calls for appointments          */
/********************************************/

var ApptController = function(options) {
    BaseController.call(this,options);
};
ApptController.prototype = Object.create(BaseController.prototype);
ApptController.prototype.constructor = ApptController;

_.extend(ApptController.prototype, {

    init: function() {
        if (apptview)
            this.appView = apptview;
    },
    
    goToAppt: function(data) {
        var $xml = $(data);
        
        //intercept error re: overlapping appointments
        var isOverlap = false;
        $xml.find("error").each(function() {
            var errc = $(this).children("errorcode").text();
            if (errc === '15' || errc === '12') {
            	gIsNewAppt = errc === '15';
            	gDetailText = $(this).children("errortext").text();
            	$("#popupNotifyOverlap").popup("open");
            	isOverlap = true;
            }
        });
        if (isOverlap) return false;
        
        if (this.doErrorCheck($xml)) {
            enableButton(".btnSubmit", doSubmit);
            return false;
        }

        //Get which appointment was made to show on the "my appts" page
        var apptid = null;
        if ($xml.find("appt")) {        
            apptid = $xml.find("appointmentid").text();
        }
        
        var msg = "";
        this.newPageURL = this.gGoToPage;
        var controller = this;
        ($xml.find("infmsg")).each(function() {
            msg = $(this).text();
            controller.setSessionVar([["infmsg",msg],["apptid",apptid]],controller.goToNewPage);
        });
        if (isNullOrBlank(msg)) {
            this.goToNewPage();     
        }
    },
    addAppt: function(inAppt,inGoToPage,isForce) {
    	var force = typeof isForce !== 'undefined' ? isForce : false;
        var str = '<addappointment>'
            + this.buildSessionTag()
            + inAppt.toXML();
        if (force)
        	str += '<force>1</force>';
		str += "</addappointment>";
        
        this.gGoToPage = inGoToPage;
        this.callAJAX(str,this.goToAppt);
    },
    editAppt: function(inAppt,inGoToPage,isForce) {
    	var force = typeof isForce !== 'undefined' ? isForce : false;
        var str = '<editappointment>'
            + this.buildSessionTag()
            + inAppt.toXML();
        if (force)
        	str += '<force>1</force>';
		str += "</editappointment>";
        
        this.gGoToPage = inGoToPage;
        this.callAJAX(str,this.goToAppt);
    },
    
    showDeletedAppt: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        if (typeof viewcalview !== 'undefined') {
	        viewcalview.dateSelector.saveSelected();
	        viewcalview.getNewData();
        } else {
        	myapptsview.showDeletedAppt();
        }
    },
    deleteAppt: function(inApptID,inCancelText,inGoToPage) {
        var str = '<deleteappointment>'
            + this.buildSessionTag()
            + this.buildTag('appointmentid',inApptID)
            + this.buildTag('canceltext',inCancelText)
            + '</deleteappointment>';
        
        if (!isNullOrBlank(inGoToPage)) {
            this.gGoToPage = inGoToPage;
            this.callAJAX(str,this.goToAppt);
        } else {
            this.callAJAX(str,this.showDeletedAppt);
        }
    },
        
    showSyncedApptStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //syncappointment
        var apptid = $xml.find("syncappointment appointmentid").text();
        var ical = $xml.find("syncappointment ical").text();
        viewcalview.showApptSync(apptid,ical);
    },
    syncAppt: function(inUserID,inApptID) {
        var str = '<syncappointment>'
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('appointmentid',inApptID)
            + '</syncappointment>';
       this.callAJAX(str,this.showSyncedApptStatus);
    }

});

//declare controller instance
var apptController = new ApptController();