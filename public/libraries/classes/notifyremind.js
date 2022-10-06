/*
 * NotifyAndRemind
 */
var NotifyAndRemind = function(options) {
    WASEObject.call(this,options);
};
NotifyAndRemind.prototype = Object.create(WASEObject.prototype);
NotifyAndRemind.prototype.constructor = NotifyAndRemind;
_.extend(NotifyAndRemind.prototype, {
    defaults: {
        notify: true,
        notifyman: true,
        notifymem: false, //unused
        remind: true,
        remindman: true,
        remindmem: false, //unused
        apptmsg: ''
    },
    setFromXML: function($xml) {
        this.set('notify',isTrue(this.getTagText($xml,'notify')));
        this.set('notifyman',isTrue(this.getTagText($xml,'notifyman')));
        this.set('notifymem',isTrue(this.getTagText($xml,'notifymem')));
        this.set('remind',isTrue(this.getTagText($xml,'remind')));
        this.set('remindman',isTrue(this.getTagText($xml,'remindman')));
        this.set('remindmem',isTrue(this.getTagText($xml,'remindmem')));
        this.set('apptmsg',this.getTagText($xml,'apptmsg'));
    }
});