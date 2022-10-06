var WASEObject = function(options) {
    options = options || {};
	if (options instanceof jQuery) {
		this.setFromXML(options);
	} else {
		if (options.fromJSON && options.obj)
			this.setFromJSON(options.obj);
		this.setFromObject(options);
	}
};
_.extend(WASEObject.prototype, {
    get: function(property) {
        return this[property];
    },
    set: function(property,value) {
        this[property] = this.unescapeSpecialChars(value);
    },
    getTagText: function($xml,tag) {
        var val = '';
        if ($xml.children(tag))
            val = $xml.children(tag).text();
        return val;
    },
    getTagInt: function($xml,tag) {
        var val = this.getTagText($xml,tag);
        var int = 0;
        if (!isNullOrBlank(val))
        	int = parseInt(val,10);
        return int;
    },
    setFromXML: function($xml) {
        //implement
    },
    setFromObject: function(obj) {
        _.each(Object.keys(obj), function(key,ind) {
            this.set(key,obj[key]);
        }, this);
    },
    setFromJSON: function(obj) {
        //implement
    },
    unescapeSpecialChars: function(inVal) {
    	var retVal = inVal;
    	if ($.type(inVal) === 'string') {
    		retVal = retVal.replace(/&amp;/g, "&");
    		retVal = retVal.replace(/&lt;/g, "<");
    		retVal = retVal.replace(/&gt;/g, ">");
    		retVal = retVal.replace(/&apos;/g, "'");
    		retVal = retVal.replace(/&quot;/g, '"');
    	}
    	return retVal;
    },
    escapeSpecialChars: function(inVal) {
    	var retVal = inVal;
    	if ($.type(inVal) === 'string') {
	    	retVal = retVal.replace(/&/g, "&amp;");
	    	retVal = retVal.replace(/</g, "&lt;");
	    	retVal = retVal.replace(/>/g, "&gt;");
	    	retVal = retVal.replace(/'/g, "&apos;");
	    	retVal = retVal.replace(/"/g, "&quot;");
    	}
    	return retVal;
    },
    toXML: function(wrapper) {
        var strXML = '';
        _.each(Object.keys(this), function(key) {
            if (key !== 'defaults' && key !== 'xmlWrapper') {
                var val = this.get(key);
                var ok = true;
                if ($.type(val) === 'boolean') {
                    val = getServerBoolean(val);
                } else if ($.type(val) === 'date') {
                    val = formatDateTimeforServer(val);
                } else if ($.type(val) === 'array') {
                    console.log("ARRAY!!! " + key);
                    val = this.getArrayXML(key);
                    ok = false;
                    strXML += val;
                } else if ($.type(val) === 'object') {
                    try {
                        val = val.toXML();
                    } 
                    catch (e) {
                        console.log("NOVAL: " + key);
                        console.log(e);
                        val = '';
                    }
                } else if ($.type(val) === 'function') {
                    console.log("FUNCTION");
                    ok = !ok;
                } else if ($.type(val) === 'string') {
                	val = this.escapeSpecialChars(val);
                }
                if (ok) strXML += '<'+key+'>' + val + '</'+key+'>';
            }
        },this);
        
        //wrap it
        wrapper = wrapper || this.xmlWrapper;
        if (wrapper) {
            strXML = '<'+wrapper+'>'+strXML+'</'+wrapper+'>';
        }

        return strXML;
    },
    getArrayXML: function(key) {
        var val = this.get(key);
        return '<'+key+'>' + this.escapeSpecialChars(val.join(',')) + '</'+key+'>';
    }
});