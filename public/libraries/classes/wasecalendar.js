/*
 * ManagerMember
 */
var ManagerMember = function(options) {
    WASEObject.call(this,options);
};
ManagerMember.prototype = Object.create(WASEObject.prototype);
ManagerMember.prototype.constructor = ManagerMember;
_.extend(ManagerMember.prototype, {
    defaults: {
        user: null,
        calendarid: '',
        status: '',
        notify: false,
        remind: false,
        memberfutureapps: 0
    },
    setFromXML: function($xml) {
        this.set('user',new User($xml.children('user')));
        this.set('calendarid',this.getTagText($xml,'calendarid'));
        this.set('status',this.getTagText($xml,'status'));
        this.set('notify',isTrue(this.getTagText($xml,'notify')));
        this.set('remind',isTrue(this.getTagText($xml,'remind')));
        this.set('memberfutureapps',this.getTagInt($xml,'memberfutureapps'));
    }
});


/*
 * WASECalendarHeader
 */
var WASECalendarHeader = function(options) {
    WASEObject.call(this,options);
    this.xmlWrapper = 'calendar';
};
WASECalendarHeader.prototype = Object.create(WASEObject.prototype);
WASECalendarHeader.prototype.constructor = WASECalendarHeader;
_.extend(WASECalendarHeader.prototype, {
    defaults: {
        calendarid: '',
        title: '',
        description: '',
        owner: null,
        waitlist: '',
        waitcount: 0,
        labels: {},
        ownerfutureapps: 0
    },
    setFromXML: function($xml) {
        this.set('calendarid',this.getTagText($xml,'calendarid'));
        this.set('title',this.getTagText($xml,'title'));
        this.set('description',this.getTagText($xml,'description'));
        this.set('owner',new User($xml.find("owner")));
        this.set('waitlist',isTrue(this.getTagText($xml,'waitlist')));
        this.set('waitcount',this.getTagText($xml,'waitcount'));
       //labels
        var $labels = $xml.find('labels');
        this.set('labels',new WASEObject({
        	'NAMETHING': $labels.find('NAMETHING').text(),
        	'NAMETHINGS': $labels.find('NAMETHINGS').text(),
        	'APPTHING': $labels.find('APPTHING').text(),
        	'APPTHINGS': $labels.find('APPTHINGS').text()
        }));
        this.set('ownerfutureapps',this.getTagInt($xml,'ownerfutureapps'));
    }
});


/*
 * WASECalendar
 */
var WASECalendar = function(options) {
    WASECalendarHeader.call(this,options);
};
WASECalendar.prototype = Object.create(WASECalendarHeader.prototype);
WASECalendar.prototype.constructor = WASECalendar;
_.extend(WASECalendar.prototype, {
        defaults: _.extend(WASECalendarHeader.prototype.defaults, {
        location: '',
        overlapok: false,
        available: true,
        purreq: false,
        icalpass: '',
        
        notifyandremind: null,
        accessrestrictions: null,
        
        managers: [],
        members: [],
    }),
    getCopy: function() {
    	var cpyVals = {};
    	_.each(_.keys(this), function(k) {
    		if (k === 'accessrestrictions') { //separate case because of arrays
    			var ar = this['accessrestrictions'];
    			var cpyAR = {};
    			_.each(_.keys(ar), function(arkey) {
    				if (Array.isArray(ar[arkey])) {
    					cpyAR[arkey] = ar[arkey].slice(0);
    				} else
    					cpyAR[arkey] = ar[arkey];
    			});
    			cpyVals[k] = cpyAR;
    		} else {
    			cpyVals[k] = this[k];
    		}
    	}, this);
    	return new WASECalendar(cpyVals);
    },
    getMgrMemWithStatus: function(type,status) {
        var arr = type === 'managers' ? this.managers : this.members;
        return $.grep(arr, function(m) {
            return m.get('status') === status;
        });
    },
    setFromXML: function($xml) {
        WASECalendarHeader.prototype.setFromXML.call(this,$xml);
        this.set('location',this.getTagText($xml,'location'));
        this.set('overlapok',isTrue(this.getTagText($xml,'overlapok')));
        this.set('available',isTrue(this.getTagText($xml,'available')));
        this.set('purreq',isTrue(this.getTagText($xml,'purreq')));
        this.set('icalpass',this.getTagText($xml,'icalpass'));
        
        this.set('notifyandremind',new NotifyAndRemind($xml.find("notifyandremind")));
        this.set('accessrestrictions',new AccessRestrictions($xml.find("accessrestrictions")));
        
        var obj = this;
        this.managers = [];
        $xml.find("manager").each(_.bind(function(i,x) {
            obj.managers.push(new ManagerMember($(x)));
        },this));
        
        this.members = [];
        $xml.find("member").each(_.bind(function(i,x) {
            obj.members.push(new ManagerMember($(x)));
        },this));
        
    },
    managersToXML: function() {
        var tmpVal = '';
        _.each(this.managers, function(m) {
            tmpVal += m.toXML('manager');
        });
        return tmpVal;
    },
    membersToXML: function() {
        var tmpVal = '';
        _.each(this.members, function(m) {
            tmpVal += m.toXML('member');
        });
        return tmpVal;
    },
    getArrayXML: function(key) {
        var val = '';
        if (key === 'managers') {
            val = this.managersToXML();
        } else if (key === 'members') {
            val = this.membersToXML();
        } else if ($.type(val) === 'string') {
        	val = this.escapeSpecialChars(val);
        }
       return val;
    },
});