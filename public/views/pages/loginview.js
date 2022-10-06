var DidYouKnow = function(inID,inDateAdded,inRelease,inTopics,inHeader,inDetails) {
	this.dykID = inID;
	this.dateadded = inDateAdded;
	this.release = inRelease;
	this.topics = inTopics;
	this.header = inHeader;
	this.details = inDetails;
};
_.extend(DidYouKnow.prototype, {
	getID: function() {
		return this.dykID;
	},
	getTopics: function() {
		return this.topics;
	},
	getHeader: function() {
		return this.header;
	},
	getDetails: function() {
		return this.details;
	},
	getRelease: function() {
		return this.release;
	},
	getDateAdded: function() {
		return this.dateadded;
	}
});


function LoginView() {
}

//Load DidYouKnow data
LoginView.prototype.loadDYKData = function(inDYK) {
	$("#divDYKHeader").html(inDYK.getHeader());
	$("#divDYKDetails").html(inDYK.getDetails());
};

var loginview = new LoginView();