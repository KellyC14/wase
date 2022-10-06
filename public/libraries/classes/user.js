var User = function(options) {
    WASEObject.call(this,options);
};
User.prototype = Object.create(WASEObject.prototype);
User.prototype.constructor = User;
_.extend(User.prototype, {
    defaults: {
        userid: '',
        name: '',
        phone: '',
        email: '',
        office: ''
    },
    getDisplayName: function() {
        var nm = this.get('name');
        if (isNullOrBlank(nm))
            nm = this.get('userid');
        return nm;
    },
    getLongDisplayName: function() {
        var nm = this.get('name');
        var uid = this.get('userid');
        var fullname = '';
        if (isNullOrBlank(nm))
        	fullname = uid;
        else 
        	fullname = uid + ' ('+nm+')';
        return fullname;
    },
    setFromXML: function($xml) {
        this.set('userid',this.getTagText($xml,'userid'));
        this.set('name',this.getTagText($xml,'name'));
        this.set('phone',this.getTagText($xml,'phone'));
        this.set('email',this.getTagText($xml,'email'));
        this.set('office',this.getTagText($xml,'office'));
    }
});