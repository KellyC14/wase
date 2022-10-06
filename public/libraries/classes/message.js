/*
 * Message
 */

var Message = function(options) {
	options = options || {};
    BaseView.call(this,options);
    this.messageSelector = options.messageSelector || "#divMessage";
    this.isTopMsg = typeof options.isTopMsg !== 'undefined' ? options.isTopMsg : true;
};
Message.prototype = Object.create(BaseView.prototype);
Message.prototype.constructor = Message;
_.extend(Message.prototype, {
    getMessage: function() {
        if (typeof this.$message === 'undefined') {
	        this.$message = $(this.messageSelector);
	        this.$message.addClass('message');
        }
    },
    display: function(msgText) {
        if (!isNullOrBlank(msgText)) {
            this.removeMessage();
            var $btnClose = $("<a>",{href: "#", text: "Close message"});
            this.$message.html(msgText).append($btnClose).show();
            var that = this;
            $btnClose.on('click', function() {
            	that.removeMessage();
            });
            if (this.isTopMsg) window.scrollTo(0,0);
        }
    },
    displayConfirm: function(msgText) {
        this.display(msgText);
        this.$message.addClass('confirm');
    },
    displayError: function(msgText) {
        this.display(msgText);
        this.$message.addClass('error');
    },
    displayHelp: function(msgText) {
        this.display(msgText);
        this.$message.addClass('help');
    },
    removeMessage: function() {
    	this.getMessage();
        this.$message.empty().removeClass('confirm').removeClass('error').removeClass('help').hide();
    }
});
var gMessage = new Message();



/*
 * Error
 */
var Error = function(options) {
    WASEObject.call(this,options);
};
Error.prototype = Object.create(WASEObject.prototype);
Error.prototype.constructor = Error;
_.extend(Error.prototype, {
    defaults: {
        errorcode: '',
        errortext: '',
    }
});
Error.displayErrors = function(errArray,msgObj) {
	var msgObj = typeof msgObj !== 'undefined' ? msgObj : gMessage;
	msgObj.removeMessage();
    var errString = '';
    _.each(errArray, function(err) {
        var errc = isNullOrBlank(err.get('errorcode')) ? '' : ' (' + err.get('errorcode') + ') ';
        errString += err.get('errortext') + errc + '\n';
    },this);
    msgObj.displayError(errString);
};


