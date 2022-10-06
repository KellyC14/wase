var WASECalController = function(options) {
    BaseController.call(this,options);
};
WASECalController.prototype = Object.create(BaseController.prototype);
WASECalController.prototype.constructor = WASECalController;

_.extend(WASECalController.prototype, {

    init: function() {
        this.appView = calendarconfigview;
    },
    showCalendar: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) {
            enableButton(".btnSubmit", doSubmit);
            return false;
        }

        var calID = $xml.find('calendarid').text();
        this.newPageURL = getPageURL('viewcalendar',{calid: calID});
        this.doInfMsgCheck($xml,this.goToNewPage);
    },
    
    addCalendar: function(inCal) {
        var str = '<addcalendar>'
            + this.buildSessionTag()
            + inCal.toXML()
            + "</addcalendar>";
        
        this.callAJAX(str,this.showCalendar,this);
    },

    editCalendar: function(inCal,inPropagateValue) {
    	inPropagateValue = inPropagateValue || "none";
        var str = '<editcalendar>'
            + this.buildSessionTag()
            + inCal.toXML()
            + this.buildTag('propagate',inPropagateValue)
            + "</editcalendar>";
        
        this.callAJAX(str,this.showCalendar,this);
    },
    
    showDeletedCalendar: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        this.newPageURL = getURLPath() + "/calendars.php";
        var str = '<dummyxml>'+this.buildTag('infmsg','Calendar deleted.')+'</dummyxml>';
        this.doInfMsgCheck($(str),this.goToNewPage);
    },
    deleteCalendar: function(inCalID,inCancelText) {
        var str = '<deletecalendar>'
            + this.buildSessionTag()
            + this.buildTag('calendarid',inCalID)
            + this.buildTag('canceltext',inCancelText)
            + '</deletecalendar>';
        
        this.callAJAX(str,this.showDeletedCalendar);
    },
    
    /*
     * Managers/Members
     */
    parseValidateUserXML: function(data,msgObj,callback) {
        var $xml = $(data);
        if (this.doErrorCheck($xml,msgObj)) return false;
        
        var appView = this.appView;
        $xml.find("user").each(function() {
            var isvalid = isTrue($(this).children("isvalid").text());
            var usr = new User($(this).find("userinfo"));
            callback.call(appView,isvalid,usr);
        });
    },
    getValidateUserXML: function(inUserID) {
        return '<validateusers><user>'
            + this.buildTag('userid',inUserID)
            + this.buildTag('password','')
            + "</user></validateusers>";
    },
    showValidManager: function(data) {
        this.parseValidateUserXML(data,this.appView.messageManagers,this.appView.showAddedManager);
    },
    validateManager: function(inUserID) {
        var str = this.getValidateUserXML(inUserID);
        this.callAJAX(str,this.showValidManager,this);
    },
    showValidMember: function(data) {
        this.parseValidateUserXML(data,this.appView.messageMembers,this.appView.showAddedMember);
    },
    validateMember: function(inUserID) {
        var str = this.getValidateUserXML(inUserID);
        this.callAJAX(str,this.showValidMember,this);
    },

    showAddedManager: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml, this.appView.messageManagers)) return false;
        var appView = this.appView;
        $xml.find("manager").each(function() {
            appView.showAddedManager.call(appView,true,new ManagerMember($(this)));
        });
    },
    addManager: function(inMgr) {
        var str = '<addmanager>'
            + this.buildSessionTag()
            + inMgr.toXML('manager')
            + '</addmanager>';
        this.callAJAX(str,this.showAddedManager);
    },

    showAddedMember: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml, this.appView.messageMembers)) return false;
        var appView = this.appView;
        $xml.find("member").each(function() {
            appView.showAddedMember.call(appView,true,new ManagerMember($(this)));
        });
    },
    addMember: function(inMem) {
        var str = '<addmember>'
            + this.buildSessionTag()
            + inMem.toXML('member')
            + '</addmember>';
        this.callAJAX(str,this.showAddedMember);
    },

    showCheckUser: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml, this.appView.messageManagers)) return false;

        //getuserinfo. Will be just one.
        var $getuserinfo = $xml.find("getuserinfo");
        var usr = new User($getuserinfo.find("userinfo"));
        var userID = usr.get("userid");
        var enteredID = $getuserinfo.children('userid').text();

        //set up event handler
        if (enteredID !== userID)
            this.workingMgrMem['user']['userid'] = userID;
        var onYes = $.noop;
        if (this.isMgrOrMem === 'manager')
            onYes = _.bind(this.addManager,this,this.workingMgrMem);
        else
            onYes = _.bind(this.addMember,this,this.workingMgrMem);

        //entered userid was really email. Popup message
        if (enteredID !== userID) {
            initEmailAsUserID({
                enteredID: enteredID,
                userEmail: usr.get("email"),
                userID: userID,
                onYes: onYes
            });
            $("#popupEmailAsUserID").popup("open");

        } else {
            onYes();
        }
        this.isMgrOrMem = '';
        this.workingMgrMem = null;
    },
    getUserInfoXML: function(inUserID) {
        var str = "<getuserinfo><sessionid>" + g_sessionid + "</sessionid>";
        str += "<userid>" + inUserID + "</userid>";
        str += "</getuserinfo>";
        return str;
    },
    checkMember: function(inMem) {
        this.workingMgrMem = inMem;
        this.isMgrOrMem = 'member';
        this.callAJAX(this.getUserInfoXML(inMem.get('user').get('userid')),this.showCheckUser);
    },
    checkManager: function(inMgr) {
        this.workingMgrMem = inMgr;
        this.isMgrOrMem = 'manager';
        this.callAJAX(this.getUserInfoXML(inMgr.get('user').get('userid')),this.showCheckUser);
    },

    showEditedManager: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml, this.appView.messageManagers)) return false;
            
        var appView = this.appView;
        $xml.find("manager").each(function() {
            appView.showChangedManager.call(appView,new ManagerMember($(this)));
        }); 
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
        if (this.doErrorCheck($xml, this.appView.messageMembers)) return false;
            
        var appView = this.appView;
        $xml.find("member").each(function() {
            appView.showChangedMember.call(appView,new ManagerMember($(this)));
        }); 
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
        if (this.doErrorCheck($xml, this.appView.messageManagers)) return false;
            
        $xml.find("manager").each(function() {
            calendarconfigview.showRemovedManager.call(calendarconfigview,new ManagerMember($(this)));
        }); 
    },
    deleteManager: function(inMgr) {
        var str = '<deletemanager>'
            + this.buildSessionTag()
            + inMgr.toXML('manager')
            + '</deletemanager>';

        this.callAJAX(str,this.showDeletedManager);
    },

    showDeletedMember: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml, this.appView.messageMembers)) return false;
            
        var appView = this.appView;
        $xml.find("member").each(function() {
            calendarconfigview.showRemovedMember.call(calendarconfigview,new ManagerMember($(this)));
        }); 
    },
    deleteMember: function(inMem,inText) {
    	inText = inText || '';
        var str = '<deletemember>'
            + this.buildSessionTag()
            + inMem.toXML('member')
            + this.buildTag('canceltext', inText)
            + '</deletemember>';

        this.callAJAX(str,this.showDeletedMember);
    },

    showSetIcalPass: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        var appView = this.appView;
        $xml.find("seticalpass").each(function() {      
            icalpass = $(this).children("icalpass").text();
            appView.setPublishFeeds.call(appView,icalpass);
        });
    },
    setIcalPass: function(inCalendarID,inIcalPass) {
        //if (!inIcalPass) return;
        var icalpass = inIcalPass || '';
        
        var str = '<seticalpass>'
            + this.buildSessionTag()
            + this.buildTag('calendarid',inCalendarID)
            + this.buildTag('icalpass',icalpass)
            + '</seticalpass>';

        this.callAJAX(str,this.showSetIcalPass);
    },

});

//declare controller instance
var waseCalController = new WASECalController();
