function WhatsNewView() {
	this.pagetitle = "What's New";
}

//set and show the page title and set the global apptid to the input apptid (sent from calling page).
WhatsNewView.prototype.setPageVars = function() {
	$("#pagetitle").text(this.pagetitle);	
};

var whatsnewview = new WhatsNewView();
