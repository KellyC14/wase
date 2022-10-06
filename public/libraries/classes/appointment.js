/*
 * AppointmentHeader
 */
var AppointmentHeader = function(options) {
    WASEObject.call(this,options);
    this.xmlWrapper = 'appt';
};
AppointmentHeader.prototype = Object.create(WASEObject.prototype);
AppointmentHeader.prototype.constructor = AppointmentHeader;
_.extend(AppointmentHeader.prototype, {
    defaults: {
        appointmentid: '',
        blockid: '',
        calendarid: '',
        startdatetime: '',
        enddatetime: '',
        available: true,
        apptmaker: null,
        blockowner: null,
        blocklocation: '',
        blocktitle: '',
        labels: {},
        candeadline: 'none'
    },
    isMultiDate: function() {
        return !(moment(this.startdatetime).isSame(moment(this.enddatetime), 'day'));
        //!isDateSame(this.startdatetime,this.enddatetime)
    },
    setFromXML: function($xml) {
        this.set('appointmentid',this.getTagText($xml,'appointmentid'));
        this.set('blockid',this.getTagText($xml,'blockid'));
        this.set('calendarid',this.getTagText($xml,'calendarid'));
        this.set('startdatetime',formatDateTimeFromServer(this.getTagText($xml,'startdatetime')));
        this.set('enddatetime',formatDateTimeFromServer(this.getTagText($xml,'enddatetime')));
        this.set('available',isTrue(this.getTagText($xml,'available')));
        this.set('apptmaker',new User($xml.children('apptmaker')));
        this.set('candeadline',this.getTagText($xml,'candeadline'));

        //labels
        var $labels = $xml.find('labels');
        this.set('labels',new WASEObject({
        	'NAMETHING': $labels.find('NAMETHING').text(),
        	'NAMETHINGS': $labels.find('NAMETHINGS').text(),
        	'APPTHING': $labels.find('APPTHING').text(),
        	'APPTHINGS': $labels.find('APPTHINGS').text()
        }));
    },
    setFromJSON: function(obj) {
    	var dateKeys = ['startdatetime','enddatetime'];
    	var boolKeys = ['available'];
    	
    	_.each(Object.keys(obj), function(k) {
    		if (k === 'apptmaker') {
    			var owner = new User();
    			owner.setFromObject(obj[k]);
    			this.set(k,owner);
	    	} else if (typeof obj[k] === 'object') {
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
    		} else {
    			this.set(k,obj[k]);
    		}
    		
    	},this);
    }
});



/*
 * Appointment
 */
var Appointment = function(options) {
    AppointmentHeader.call(this,options);
};
Appointment.prototype = Object.create(AppointmentHeader.prototype);
Appointment.prototype.constructor = Appointment;
_.extend(Appointment.prototype, {
        defaults: _.extend(AppointmentHeader.prototype.defaults, {
        purpose: '',
        remind: true,
        textemail: '',
        whenmade: '',
        madeby: '',
        madebyname: '',
        venue: ''
    }),
    getWhenMadeDisplay: function() {
        return moment(this.whenmade).format(fmtDate) + " " + moment(this.whenmade).format("h:mm a");
    },
    setFromXML: function($xml) {
        AppointmentHeader.prototype.setFromXML.call(this,$xml);
        this.set('purpose',this.getTagText($xml,'purpose'));
        this.set('remind',isTrue(this.getTagText($xml,'remind')));
        this.set('textemail',this.getTagText($xml,'textemail'));
        this.set('whenmade',formatDateTimeFromServer(this.getTagText($xml,'whenmade')));
        this.set('madeby',this.getTagText($xml,'madeby'));
        this.set('madebyname',this.getTagText($xml,'madebyname'));
        this.set('venue',this.getTagText($xml,'venue'));
    }
});