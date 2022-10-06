/********************************************/
/* AJAX/API calls for appointments          */
/********************************************/
var g_schemaRoot = "SROOT";
var g_AJAXXMLHeader = '<?xml version="1.0"?><wase xmlns="' + g_schemaRoot + '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xsi:schemaLocation="' + g_schemaRoot + 'wase.xsd">';
var g_AJAXXMLFooter = "</wase>";

var BaseController = function(options) {
    options = options || {};
};
_.extend(BaseController.prototype, {
    /*
     * Call AJAX
     */
	callAJAXBase: function(dataType, dataStr, callback, context) {
        var opts = {
            type: "POST",
            url: "../../controllers/ajax/waseAjax.php",
            dataType: dataType,
            data: dataStr,
            success: callback,
            context: context || this,
            error: function(jqXHR, textStatus, errorThrown) {
            	console.log("ERROR: " + textStatus);
            	console.log("error orig: " + dataType + ": " + dataStr);
            	console.log(jqXHR);
            	console.log(errorThrown);
            }
        };
        if (dataType === "json") {
        	opts['contentType'] = 'application/json';
        }
		$.ajax(opts);
	},
    callAJAX: function(xmlstring, callback, context) {
        var dataString = g_AJAXXMLHeader.replace(/\SROOT/g, g_schemaRoot) + xmlstring + g_AJAXXMLFooter;
        this.callAJAXBase("xml", dataString, callback, context);
    },
    callAJAXJSON: function(obj, callback, context) {
        this.callAJAXBase("json", JSON.stringify({wase: obj}), callback, context);
    },
    
    
    /*
     * Build XML
     */
    buildTag: function(property, value) {
        return '<'+property+'>'+value+'</'+property+'>';
    },
    buildXMLFromObject: function(obj) {
    	var str = '';
    	if (typeof obj === 'object') {
	    	_.each(Object.keys(obj), function(k) {
	    		str += '<'+k+'>'+this.buildXMLFromObject(obj[k])+'</'+k+'>';
	    	},this);
	    	return str;
    	} else {
    		return obj;
    	}
    },
    //TODO: Make this buildTag to use in all AJAX calls?
    buildEscapeTag: function(property, value) {
    	var val = value;
    	val = val.replace(/&/g, "&amp;");
    	val = val.replace(/</g, "&lt;");
    	val = val.replace(/>/g, "&gt;");
    	val = val.replace(/'/g, "&apos;");
    	val = val.replace(/"/g, '&quot;');
        return '<'+property+'>'+val+'</'+property+'>';
    },
    buildSessionTag: function() {
        return this.buildTag('sessionid',g_sessionid);
    },
    
    /*
     * Read XML
     */
    getTagText: function($xml,tag) {
        return $xml.children(tag).text();
    },
    
    /*
     * Check for response errors
     */
    doErrorCheckXML: function($xml,errs) {
        $xml.find("error").each(function() {
            var errc = $(this).children("errorcode").text();
            if (errc !== "" && errc !== "0") {
                errs.push(new Error({errorcode: parseInt(errc,10), errortext: $(this).children("errortext").text()}));
            }
        });
        return errs;
    },
    doJSONErrorCheck: function(obj,errs) {
    	_.each(Object.keys(obj), function(k) {
			var err = obj[k].error;
    		if (err && err.errorcode !== '0') {
    			errs.push(new Error({errorcode: parseInt(err.errorcode,10), errortext: err.errortext}));
    		}
    	});
    	return errs;
    },
    doErrorCheck: function(dataObj,msgObj) {
        //error checking
        var errs = [];
    	if (dataObj instanceof jQuery) {
    		errs = this.doErrorCheckXML(dataObj,errs);
    	} else {
    		errs = this.doJSONErrorCheck(dataObj,errs);
    	}
        if (errs.length > 0) {
            Error.displayErrors(errs,msgObj);
            showLoadingMsg(false);
            return true;
        }
        return false;
    },
    
    goToNewPage: function(data) {
        document.location.href = this.newPageURL;
    },
    
    /* session variables */
    setSessionVar: function(inVarValArray,callback) {
        var str = '<setvar>'
            + this.buildSessionTag();
        _.each(inVarValArray, function(arr) {
            str += '<sessionvar>'
                + this.buildTag('var',arr[0])
                + this.buildTag('val',arr[1])
                + '</sessionvar>';
        }, this);
        str += "</setvar>";

        callback = callback || this.showSetSessionVar;
        this.callAJAX(str,callback);
    },
    
    doInfMsgCheck: function($xml,callback) {
        if (!$xml.find("infmsg").length) {
            callback.call(this);
        }
        var controller = this;
        $xml.find("infmsg").each(function() {
            controller.setSessionVar([["infmsg",$(this).text()]],callback);
        });
        //return msg?
    },
    
    /* validations */
    showValidEmail: function(data,callback) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //isemailvalid
        var $isvalid = $xml.find("isemailvalid");
        var isvalid = isTrue($isvalid.find('isvalid').text());
        var email = $isvalid.find("email").text();
        callback.call(this,isvalid,email);
    },
    isValidEmail: function(inEmailAddr) {
        var str = "<isemailvalid>"
            + this.buildTag('email',inEmailAddr)
            + "</isemailvalid>";
        this.callAJAX(str, this.showValidEmail);
    },
    
    checkJSONObjectOrArray: function(obj,callback,args) {
    	if (Array.isArray(obj)) {
        	_.each(obj, function(o) {
        		callback.call(this,o,args);
        	},this);
    	} else {
    		callback.call(this,obj,args);
    	}
    }

});
//declare controller instance
var baseController = new BaseController();