<?php 
/*
        This page is a pop-up details box for quick add appointments.
*/
?>
<div data-role="popup" id="popupQuickAppt" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupQuickAppt").popup("close");' title="Close Window">Close</a></div>
    <div id="divQuickAdd" class="popupinner">
        <form id="frmQuickAdd" method="post" action="#" data-ajax="false">
        <h2 id='quickAddTitle'>Create</h2>
        <div class="instructions">Please enter a purpose (required):</div>
        
        <fieldset>
            <div id="divPurpose" class="ui-field-contain">
                <label id="lblPurpose" for="txtPurpose" class="required">Purpose:</label>
                <textarea name="txtPurpose" id="txtPurpose" placeholder="Purpose" title="Purpose" class="highlight" data-mini="true"></textarea>
           	</div> 
            <div id="divSaveButton" class="popupbutton ui-field-contain">    
                <input type="button" value="Create" data-inline="true" onclick="MydoSave();" />
            </div>
            <div class="heightfix"></div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript"> 
var gAppt = null;
var gCalID = 0;
function quickApptInit(inAppt, inLabels, inCalID) {
    gAppt = inAppt;
    gCalID = inCalID;

    //set labels
    var appt = capitalize(inLabels['APPTHING']);
    $("#quickAddTitle").text('Create ' + appt);
    $("#popupQuickAppt #lblPurpose").text(appt + " Purpose:");
    $("#popupQuickAppt #divSaveButton input").val('Create ' + appt);
}
function MydoSave() {
	 
	var pur = $("#txtPurpose").val();
	 
	if (isNullOrBlank(pur)) {
		 
	    $("#lblPurpose").addClass('missing');
	    $("#txtPurpose").attr('placeholder','ENTER REQUIRED PURPOSE');
	} else {
		 
        var appt = new Appointment({
            'blockid': gAppt.get("blockid"),
            'calendarid': gCalID,
            'startdatetime': gAppt.get("startdatetime"),
            'enddatetime': gAppt.get("enddatetime"),
            'available': true,
            'apptmaker': new User({userid: g_loggedInUserID}),
            'purpose': pur,
            'madeby': isNullOrBlank(g_loggedInUserID) ? g_GuestEmail : g_loggedInUserID
        });
        makeapptViewController.addAppt(appt);
    	$("#popupQuickAppt").popup("close");
	}	
	return false;
}
</script> 
