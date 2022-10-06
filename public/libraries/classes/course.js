/*
 * Course
 */
var Course = function(options) {
    WASEObject.call(this,options);
};
Course.prototype = Object.create(WASEObject.prototype);
Course.prototype.constructor = Course;
_.extend(Course.prototype, {
    defaults: {
        courseid: '',
        title: '',
        instructors: []
    },
    anyInstructorCals: function() {
        //var anyCals = false;
        var anyCals = 0;
        _.each(this.instructors, function(inst) {
            //comment out original code
            //anyCals = !anyCals && inst.get('calendars').length;
            //console.log('JSON stringify inst[' + JSON.stringify(inst) +']');
            if(inst.get('calendars').length) {
                // anyCals = !anyCals && inst.get('calendars').length;
                anyCals++;
            }
        });
        return anyCals;
    },
    setFromXML: function($xml) {
        this.set('courseid',this.getTagText($xml,'id'));
        this.set('title',this.getTagText($xml,'title'));
        var obj = this;
        this.instructors = [];
        $xml.find("instructor").each(_.bind(function(i,x) {
            obj.instructors.push(new Instructor($(x)));
        }));
        //console.log("instructors for " + this.get("courseid") + ": " + this.instructors);
    }
});



/*
 * Instructor
 */
var Instructor = function(options) {
    WASEObject.call(this,options);
};
Instructor.prototype = Object.create(WASEObject.prototype);
Instructor.prototype.constructor = Instructor;
_.extend(Instructor.prototype, {
    defaults: {
        userid: '',
        name: '',
        calendars: [],
    },
    setFromXML: function($xml) {
        this.set('userid',this.getTagText($xml,'userid'));
        this.set('name',this.getTagText($xml,'name'));
        this.calendars = [];
        var obj = this;
        $xml.find("calendar").each(_.bind(function(i,x) {
            obj.calendars.push(new WASECalendarHeader($(x)));
        },this));
    }
});