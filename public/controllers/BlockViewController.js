/********************************************/
/* AJAX/API calls for the block view page   */
/********************************************/

var BlockViewController = function(options) {
    BaseController.call(this,options);
};
BlockViewController.prototype = Object.create(BaseController.prototype);
BlockViewController.prototype.constructor = BlockViewController;
var atMemberUserID = "";

_.extend(BlockViewController.prototype, {

    init: function() {
        this.appView = blockview;
    },
    showBlockViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        var appView = this.appView;
        
        //getparameter
        var parm, val;
        $xml.find("getparameter").each(function() {
        	parm = $(this).find("parameter").text();
			val = $(this).find("value").text();
			if (parm === "DAYTYPES") {
				blockview.loadDayTypes(val);
			}
			gParms[parm] = val;
        });
        blockview.setParms();
        
        //getusercalendarstatus
        var isowner = false;
        var ismanager = false;
        var ismember = false;
        $xml.find("getusercalendarstatus").each(function() {
            isowner = ($(this).find("isowner").text() === "1");
            ismanager = ($(this).find("ismanager").text() === "1");
            ismember = ($(this).find("ismember").text() === "1");
        });

        //getcalendars
        var isEdit = $xml.find("getblock").length > 0;
        var cal = null;
        $xml.find("getcalendars").each(function() {
            $(this).find("calendar").each(function() {
                if (isEdit) appView.loadCalHeaderData(new WASECalendarHeader($(this)),isowner,ismanager,ismember);
                else {
                    cal = new WASECalendar($(this));
                    appView.loadCalendarData(cal,isowner,ismanager,ismember);
                }
            });
        });

        if (ismember && cal !== null) {
            this.loadUserNotifyRemind(g_loggedInUserID,cal.get('calendarid'));
        }

        //getuserinfo for logged in user (if no block loading). Will be just one.
        $xml.find("getuserinfo").each(function() {
            var usr = new User($(this).find("userinfo"));
            blockview.loadLoggedInUserData(usr);
        });

        //getblock
        $xml.find("getblock").each(function() {
            var blk = new Block($(this).find("block"));
            blockview.loadBlockData(blk);
        });
        
    },
    
    loadBlockViewData: function(inCalID,inBlockID,inParmArray) {
      //If this is a new block, look up the calendar default info and logged in user.  Otherwise, look up the block
        var str = '';
        
        //Get parameters needed for display
        _.each(inParmArray, function(parm) {
        	str += "<getparameter><parameter>" + parm + "</parameter></getparameter>";
        });
        
        str += "<getusercalendarstatus>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + this.buildTag('calendarid',inCalID)
            + "</getusercalendarstatus>";

        str += "<getcalendars>"
            + this.buildSessionTag()
            + this.buildTag('calendarid',inCalID)
            + "</getcalendars>";

        if (isNullOrBlank(inBlockID)) {
            if (!isNullOrBlank(g_loggedInUserID)) {
                str += "<getuserinfo>"
                    + this.buildSessionTag() 
                    + this.buildTag('userid',g_loggedInUserID)
                    + "</getuserinfo>";
            }
            
        } else {
            str += "<getblock>" 
                + this.buildSessionTag() 
                + this.buildTag('blockid',inBlockID)
                + "</getblock>";
        }
        
        this.callAJAX(str,this.showBlockViewData);
    },

    showUserNotifyRemind: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        $xml.find("getusercalnotifyremind").each(function() {
            var usernotify = ($(this).find("notify").text() === "1");
            var userremind = ($(this).find("remind").text() === "1");
            blockview.loadUserNotifyRemind(usernotify,userremind);
        });
    },
    loadUserNotifyRemind: function(inUserID,inCalID) {
        var str = "<getusercalnotifyremind>"
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('calendarid',inCalID)
            + "</getusercalnotifyremind>";
        this.callAJAX(str,this.showUserNotifyRemind);
    },
    showUserInfo: function(data) {
        //note that this method is called only for the initial load of user data on the Create Block drop down.  Special consideration for @members.
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        $xml.find("getuserinfo").each(function() {
            var usr = new User($(this).find("userinfo"));
            if (atMemberUserID != "") {
                usr.name = atMemberUserID;
                atMemberUserID= "";
            }
            blockview.loadUserData(usr);
        });
    },
    loadUserInfo: function(userid, selUserID) {
        //note that this method is called only for the initial load of user data on the Create Block drop down. Special consideration for @members.
        atMemberUserID = !isNullOrBlank(selUserID) && selUserID.charAt(0) === '@' ? selUserID : "";
        var str = "<getuserinfo>"
            + this.buildSessionTag()
            + this.buildTag('userid',userid)
            + "</getuserinfo>";
        this.callAJAX(str,this.showUserInfo);
    },
    showNamedDates: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        var nameddates = [];
        $xml.find("nameddate").each(function() {
            nameddates.push({"date": $(this).find("date").text(), "name": $(this).find("name").text()});
        });
        blockview.setNamedDates(nameddates);
    },
    
    getNamedDates: function() {
        var str = "<getnameddates>"
            + "</getnameddates>";
        this.callAJAX(str,this.showNamedDates);
    }
    
});

//declare controller instance
var blockViewController = new BlockViewController();
