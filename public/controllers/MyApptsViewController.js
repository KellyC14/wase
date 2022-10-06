/********************************************/
/* AJAX/API calls for my appts view         */
/********************************************/

var MyApptsViewController = function(options) {
    BaseController.call(this,options);
};
MyApptsViewController.prototype = Object.create(BaseController.prototype);
MyApptsViewController.prototype.constructor = MyApptsViewController;

_.extend(MyApptsViewController.prototype, {
    showMyApptsViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //getappointments
        gAppts = []; //reset 
        $xml.find("getappointments").each(function() {          
            $(this).find("apptwithblock").each(function() {
                var appt = null;
                $(this).find("appt").each(function() {          
                    appt = new Appointment($(this));
                    gAppts.push(appt);
                });
                $(this).find("block").each(function() { 
                    appt.set('blockowner',new User($(this).find("blockowner")));
                    appt.set('blocklocation',$(this).children("location").text());
                    appt.set('blocktitle',$(this).children("title").text());
                	var labels = {};
                    _.each($(this).find("labels")[0].childNodes, function(el) {
                    	labels[el.nodeName] = $(el).text();
                    });
                    appt.set('labels',_.clone(labels));
                });
            });
        });
        myapptsview.loadAppts();
        
        //getusercalendarstatus
        var isowner = false;
        var ismanager = false;
        var ismember = false;
        $xml.find("getusercalendarstatus").each(function() {
            isowner = ($(this).find("isowner").text() === "1");
            ismanager = ($(this).find("ismanager").text() === "1");
            ismember = ($(this).find("ismember").text() === "1");
        });
        myapptsview.setUserStatus(isowner,ismanager,ismember);
    },
    
    loadMyApptsViewData: function() {
        //getappointments
        var mStart = moment().startOf('day').toDate();
        var str = "<getappointments>"
            + this.buildSessionTag() 
            + this.buildTag('startdate',formatDateforServer(mStart))
            + "</getappointments>";
        
        //getusercalendarstatus - is owner/manager/member of any calendar
        str += "<getusercalendarstatus>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getusercalendarstatus>";

        this.callAJAX(str,this.showMyApptsViewData);
    },
    
    getDateTimeStrings: function(strDate,strTime) {
        var dtstr = '';
        var tmstr = '';
        var dtfmt = '';
        if (strDate) {
            dtstr = strDate;
            dtfmt = fmtDate;
        }
        if (strTime) {
            dtstr += ' ' + strTime;
            dtfmt += ' ' + fmtTime;
        }
        if (dtstr) {
            var dtStart = moment(dtstr, dtfmt).toDate();
            dtstr = formatDateforServer(dtStart);
            if (strTime) tmstr = formatTimeforServer(dtStart);
        }
        return {serverDate: dtstr, serverTime: tmstr};
    },
    getAppointments: function(filters) {
        var objStart = this.getDateTimeStrings(filters.startdate,filters.starttime);
        var objEnd = this.getDateTimeStrings(filters.enddate,filters.endtime);
        var str = "<getappointments>"
            + this.buildSessionTag() 
            + this.buildTag('startdate',objStart.serverDate)
            + this.buildTag('enddate',objEnd.serverDate)
            + this.buildTag('starttime',objStart.serverTime)
            + this.buildTag('endtime',objEnd.serverTime)
            + this.buildTag('calendarid',filters.calendarid)
            + this.buildTag('apptwithorfor',filters.apptwithorfor)
            + this.buildTag('apptby',filters.apptby)
            + "</getappointments>";
        this.callAJAX(str,this.showMyApptsViewData);
    }
});

//declare controller instance
var myapptsViewController = new MyApptsViewController();