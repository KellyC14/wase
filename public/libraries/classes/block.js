/*
Defines:	Slot
			BlockHeader
			Period
			Block
*/

/*
 * Slot
 */
var Slot = function(options) {
    WASEObject.call(this,options);
};
Slot.prototype = Object.create(WASEObject.prototype);
Slot.prototype.constructor = Slot;
_.extend(Slot.prototype, {
    defaults: {
        blockid: '',
        startdatetime: '',
        enddatetime: '',
        available: true,
        makeable: true,
        showappinfo: true,
        numappts: 0, /* some calls return just an appt count */
        appts: [],
        unavailapptid: 0,
        available_flag: true
    },
    addAppt: function(inAppt) {
        if (!inAppt.get('available')) {
            this.available = false;
            this.unavailapptid = inAppt.get('appointmentid');
        }
        else {
            if (!this.appts) this.appts = [];
            this.appts.push(inAppt);
        }
    },
    setFromXML: function($xml) {
        this.set('blockid',this.getTagText($xml,'blockid'));
        this.set('startdatetime',formatDateTimeFromServer(this.getTagText($xml,'startdatetime')));
        this.set('enddatetime',formatDateTimeFromServer(this.getTagText($xml,'enddatetime')));
        this.set('makeable',isTrue(this.getTagText($xml,'makeable')));
        var avail = this.getTagText($xml,'available');
        this.set('available',avail ? avail : true);
        this.set('showappinfo',isTrue(this.getTagText($xml,'showappinfo')));
        this.set('available_flag',isTrue(this.getTagText($xml,'available_flag')));

        var numappts = this.getTagText($xml,'numappts');
        numappts = numappts ? parseInt(numappts,10) : 0;
        this.set('numappts',numappts);
        
        this.appts = [];
        var obj = this;
        $xml.find("appt").each(_.bind(function(i,a) {
            var appt = new AppointmentHeader($(a));
            obj.addAppt(appt);
        },this));
    },
    setFromJSON: function(obj) {
    	var dateKeys = ['startdatetime','enddatetime'];
    	var boolKeys = ['available','makeable','showappinfo','available_flag'];
    	var intKeys = ['numappts'];
    	this.appts = [];
    	
    	_.each(Object.keys(obj), function(k) {
    		if (k === 'appt') {
    			if (Array.isArray(obj[k])) {
    	        	_.each(obj[k], function(a) {
            			var appt = new AppointmentHeader({fromJSON: true, obj: a});
            			this.addAppt(appt);
    	        	},this);
            	} else {
        			var appt = new AppointmentHeader({fromJSON: true, obj: obj[k]});
        			this.addAppt(appt);
            	}
			} else if (dateKeys.indexOf(k) !== -1) {
        		//date
    			this.set(k,formatDateTimeFromServer(obj[k]));
    		} else if (boolKeys.indexOf(k) !== -1) {
    			//boolean
    			this.set(k,isTrue(obj[k]));
    		} else if (intKeys.indexOf(k) !== -1) {
    			//integer
    			this.set(k,parseInt(obj[k],10) || 0);
    		} else {
    			this.set(k,obj[k]);
    		}
    		
    	},this);
    	
    	//not all slots have 'available'.  Default to true.
    	if (!this.hasOwnProperty("available")) {
    		this.set("available",true);
    	}
    }
});



/*
 * BlockHeader
 */
var BlockHeader = function(options) {
    WASEObject.call(this,options);
};
BlockHeader.prototype = Object.create(WASEObject.prototype);
BlockHeader.prototype.constructor = BlockHeader;
_.extend(BlockHeader.prototype, {
    defaults: {
        blockid: '',
        seriesid: '',
        calendarid: '',
        title: '',
        description: '',
        startdatetime: '',
        enddatetime: '',
        location: '',
        available: true,
        makeable: true,
        maxapps: 0,
        maxper: 0,
        blockowner: null,
        opening: 0,
        deadline: 0,
        candeadline: 0,
        slots: [],
        hasappointments: false,
        purreq: '',
        labels: {},
        makeaccess: ''
    },
    isMultiDate: function() {
        return !(moment(this.startdatetime).isSame(moment(this.enddatetime), 'day'));
        //!isDateSame(this.startdatetime,this.enddatetime)
    },
    addSlot: function(slot) {
    	if (!this.slots) this.slots = [];
        this.slots.push(slot);
    },
    setFromXML: function($xml) {
        this.set('blockid',this.getTagText($xml,'blockid'));
        this.set('seriesid',this.getTagText($xml,'seriesid'));
        this.set('calendarid',this.getTagText($xml,'calendarid'));
        this.set('title',this.getTagText($xml,'title'));
        this.set('description',this.getTagText($xml,'description'));
        this.set('startdatetime',formatDateTimeFromServer(this.getTagText($xml,'startdatetime')));
        this.set('enddatetime',formatDateTimeFromServer(this.getTagText($xml,'enddatetime')));
        this.set('location',this.getTagText($xml,'location'));
        this.set('available',isTrue(this.getTagText($xml,'available')));
        this.set('makeable',isTrue(this.getTagText($xml,'makeable')));
        
        this.set('maxapps',parseInt(this.getTagText($xml,'maxapps'),10));
        this.set('maxper',parseInt(this.getTagText($xml,'maxper'),10));

        this.set('blockowner',new User($xml.children('blockowner')));

        var opening = this.getTagText($xml,'opening');
        opening = opening ? parseInt(opening,10) : 0;
        this.set('opening',opening);
        
        var deadline = this.getTagText($xml,'deadline');
        deadline = deadline ? parseInt(deadline,10) : 0;
        this.set('deadline',deadline);

        var candeadline = this.getTagText($xml,'candeadline');
        candeadline = candeadline ? parseInt(candeadline,10) : 0;
        this.set('candeadline',candeadline);

        this.set('hasappointments',isTrue(this.getTagText($xml,'hasappointments')));
        this.set('purreq',isTrue(this.getTagText($xml,'purreq')));

        //labels
        var $labels = $xml.find('labels');
        this.set('labels',new WASEObject({
        	'NAMETHING': $labels.find('NAMETHING').text(),
        	'NAMETHINGS': $labels.find('NAMETHINGS').text(),
        	'APPTHING': $labels.find('APPTHING').text(),
        	'APPTHINGS': $labels.find('APPTHINGS').text()
        }));
        
        
        this.set('makeaccess',this.getTagText($xml,'makeaccess'));

        this.slots = [];
    },
    setFromJSON: function(obj) {
    	var dateKeys = ['startdatetime','enddatetime'];
    	var boolKeys = ['available','makeable','hasappointments','purreq'];
    	var intKeys = ['maxapps','maxper','opening','deadline','candeadline'];
    	_.each(Object.keys(obj), function(k) {
    		if (k === 'blockowner') {
    			var owner = new User();
    			owner.setFromObject(obj[k]);
    			this.set(k,owner);
	    	} else if (k === 'labels') {
	    		//object
    			var o = new WASEObject();
    			o.setFromObject(obj[k]);
    			this.set(k,o);
	    	} else if (dateKeys.indexOf(k) !== -1) {
        		//date
    			this.set(k,formatDateTimeFromServer(obj[k]));
    		} else if (boolKeys.indexOf(k) !== -1) {
    			//boolean
    			this.set(k,isTrue(obj[k]));
    		} else if (intKeys.indexOf(k) !== -1) {
    			//integer
    			this.set(k,parseInt(obj[k],10) || 0);
    		} else {
    			this.set(k,obj[k]);
    		}

            
    		
    	},this);
    	this.slots = [];
    }
});



/*
 * Series
 */
var Series = function(options) {
    WASEObject.call(this,options);
};
Series.prototype = Object.create(WASEObject.prototype);
Series.prototype.constructor = Series;
_.extend(Series.prototype, {
    defaults: {
        seriesid: '',
        startdate: '',
        enddate: '',
        every: '', //Recurrence. Values: once,daily,dailyweekdays,weekly,otherweekly,monthlyday,monthlyweekday
        daytypes: '', //comma-separated strings
    },
    setFromXML: function($xml) {
        this.set('seriesid',this.getTagText($xml,'seriesid'));
        this.set('startdate',formatDateFromServer(this.getTagText($xml,'startdate')));
        this.set('enddate',formatDateFromServer(this.getTagText($xml,'enddate')));
        this.set('every',this.getTagText($xml,'every'));
        this.set('daytypes',this.getTagText($xml,'daytypes'));
    },
    toXML: function(wrapper) {
        var strXML = '';
        _.each(Object.keys(this), function(key) {
            if (key !== 'defaults' && key !== 'xmlWrapper') {
                var val = this.get(key);
                if ($.type(val) === 'date') {
                    val = formatDateforServer(val);
                }
                strXML += '<'+key+'>' + val + '</'+key+'>';
            }
           
        },this);
        //strXML = '<'+this.xmlWrapper+'>'+strXML+'</'+this.xmlWrapper+'>';
        return strXML;
    }
});


	
/*
 * Period
 */
var Period = function(options) {
    WASEObject.call(this,options);
    this.xmlWrapper = 'period';
};
Period.prototype = Object.create(WASEObject.prototype);
Period.prototype.constructor = Period;
_.extend(Period.prototype, {
    defaults: {
        periodid: '',
        starttime: '',
        duration: '', //in minutes
        //only one of the following will have data:
        dayofweek: '', //String: monday,tuesday,wednesday,etc. (every=weekly,otherweekly)
        dayofmonth: '', //Decimal (every=monthlyday,monthlyweekday?)
        weekofmonth: '' //Decimal
    },
    setFromXML: function($xml) {
        this.set('periodid',this.getTagText($xml,'periodid'));
        this.set('starttime',formatTimeFromServer(this.getTagText($xml,'starttime')));
        this.set('duration',this.getTagText($xml,'duration'));
        this.set('dayofweek',this.getTagText($xml,'dayofweek'));
        this.set('dayofmonth',this.getTagText($xml,'dayofmonth'));
        this.set('weekofmonth',this.getTagText($xml,'weekofmonth'));
    },
    toXML: function(wrapper) {
        var strXML = '';
        _.each(Object.keys(this), function(key) {
            if (key !== 'defaults' && key !== 'xmlWrapper') {
                var val = this.get(key);
                if ($.type(val) === 'date') {
                    val = formatTimeforServer(val);
                }
                strXML += '<'+key+'>' + val + '</'+key+'>';
            }
           
        },this);
        strXML = '<'+this.xmlWrapper+'>'+strXML+'</'+this.xmlWrapper+'>';
        return strXML;
    }

});
	



/*
 * Block
 */
var Block = function(options) {
    BlockHeader.call(this,options);
    this.xmlWrapper = 'block';
};
Block.prototype = Object.create(BlockHeader.prototype);
Block.prototype.constructor = Block;
_.extend(Block.prototype, {
        defaults: _.extend(BlockHeader.prototype.defaults, {
        divideinto: '',
        slotsize: '',
        periods: [],
        series: null,
        notifyandremind: null,
        accessrestrictions: null
    }),
    addPeriod: function(pd) {
        if (!this.periods) this.periods = [];
        this.periods.push(pd);
    },
    setFromXML: function($xml) {
        BlockHeader.prototype.setFromXML.call(this,$xml);
        this.set('divideinto',this.getTagText($xml,'divideinto'));
        this.set('slotsize',this.getTagText($xml,'slotsize'));
        
        var obj = this;
        this.periods = [];
        $xml.find("period").each(function() {
            if ($(this).children().length)
                obj.addPeriod(new Period($(this)));
        });
        
        var series = null;
        if ($xml.find('series').children().length)
            series = new Series($xml.find('series'));
        this.set('series',series);

        this.set('notifyandremind',new NotifyAndRemind($xml.find("notifyandremind")));
        this.set('accessrestrictions',new AccessRestrictions($xml.find("accessrestrictions")));
    },
    getArrayXML: function(key) {
        var val = '';
        if (key === 'periods') {
            _.each(this.get('periods'), function(v) {
                val += v.toXML();
            });
        }
        if (key === 'slots')
            val = '';
        if (key !== 'periods' && $.type(val) === 'string') {
        	val = this.escapeSpecialChars(val);
        }
        return val;
    }
});
