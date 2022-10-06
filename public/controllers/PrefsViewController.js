/********************************************/
/* AJAX/API calls for the prefs view page   */
/********************************************/

var PrefsViewController = function(options) {
    BaseController.call(this,options);
};
PrefsViewController.prototype = Object.create(BaseController.prototype);
PrefsViewController.prototype.constructor = PrefsViewController;

_.extend(PrefsViewController.prototype, {

    init: function() {
        this.appView = preferencesview;
    },
    
    showPrefsViewData: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //getprefs
        var prefs = {};
        $xml.find("pref").each(function() {
            prefs[$(this).find("keytag").text()] = $(this).children("val").text();
        });
        
        //getparameter
        $xml.find("getparameter").each(function() {
            gParms[$(this).find("parameter").text()] = $(this).find("value").text();
        });
        preferencesview.loadPrefData(prefs);
        
        //getusercalendarstatus
        var isowner = false;
        var ismanager = false;
        var ismember = false;
        $xml.find("getusercalendarstatus").each(function() {
            isowner = ($(this).find("isowner").text() === "1");
            ismanager = ($(this).find("ismanager").text() === "1");
            ismember = ($(this).find("ismember").text() === "1");
        });
        preferencesview.setUserStatus(isowner,ismanager,ismember);
    },
    loadPrefsViewData: function() {
        //getprefs
        var prefkeys = ["txtmsgemail","localcal","confirm","remind","waitnotify","google_token"];
        var str = '<getprefs>'
            + this.buildSessionTag();
        _.each(prefkeys, function(key) {
            str += '<pref>' + this.buildTag('keytag',key) + '</pref>';
        }, this);
        str += '</getprefs>';
        
        //getparameter
        _.each(['GOOGLEID','EXCHANGE_USER','EXCHANGE_DIRECT'], function(key) {
            str += '<getparameter>' + this.buildTag('parameter',key) + '</getparameter>';
        }, this);
                    
        //getusercalendarstatus - is owner/manager/member of any calendar
        str += "<getusercalendarstatus>"
            + this.buildSessionTag() 
            + this.buildTag('userid',g_loggedInUserID)
            + "</getusercalendarstatus>";

        this.callAJAX(str,this.showPrefsViewData);
    },
    
    /* save prefs */
    showSavePrefs: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        preferencesview.showSaveMsg();
        
    	//update sync calendar button, if applicable
    	if (typeof calendarconfigview !== 'undefined') {
    		var localcal = 'none';
    		_.each($xml.find("pref"), function(el) {
    			if ($(el).find("keytag").text() === 'localcal')
    				localcal = $(el).find("val").text();
    		});
    		calendarconfigview.setupCalSync(localcal);
    	}
    },
    savePrefs: function(prefs) {
        var str = '<saveprefs>'
            + this.buildSessionTag();
        _.each(Object.keys(prefs), function(key) {
            str += '<pref>' 
                + this.buildTag('keytag',key)
                + this.buildTag('val',prefs[key])
                + this.buildTag('class','user')
                + '</pref>';
        }, this);
        str += '</saveprefs>';
        this.callAJAX(str,this.showSavePrefs);
    },
    /* from google confirmation, update new google id val */
    showUpdatedGoogle: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        preferencesview.loadUpdatedGoogle($xml.find("pref val").text());
    },
    getUpdatedGoogle: function() {
        var str = '<getprefs>'
            + this.buildSessionTag()
            + '<pref>' 
            + this.buildTag('keytag','google_token') 
            + '</pref>'
            + '</getprefs>';
        this.callAJAX(str,this.showUpdatedGoogle);
    },
    
    /* text message email validation - extension */
    showValidEmail: function(data) {
        var callback = _.bind(preferencesview.showIsValidTxtEmail,preferencesview);
        BaseController.prototype.showValidEmail.call(this,data,callback);
    },
    isValidEmail: function(email) {
        BaseController.prototype.isValidEmail.call(this,email);
    }

    
});

//declare controller instance
var prefsViewController = new PrefsViewController();
