<?php 
/*
        This page is a pop-up to notify user of appointment overlaps and ask if they'd like to force overlap.
*/
?>
<div data-role="popup" id="popupNotifyOverlap" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupNotifyOverlap").popup("close");' title="Close Window">Close</a></div>
    <div id="divNotifyOverlap" class="popupinner">
        <form id="frmNotifyOverlap" method="post" action="#" data-ajax="false">
        <h2>Warning: Appointment Overlap</h2>
        <div class="instructions">This appointment will overlap with an existing appointment:</div>
        <div class="popupdetails"></div>
        <div class="instructions">Would you like to make the appointment anyway?</div>
        
        <fieldset>
            <div class="submitbuttons buttonspopup">
                <div id="divButtons" class="ui-field-contain popupbutton">    
                    <input type="button" value="Cancel" data-inline="true" onclick="$('#popupNotifyOverlap').popup('close');" />
                    <input type="button" value="Continue" data-inline="true" onclick="doSave();" />
                </div>
                <div class="heightfix"></div>
            </div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
var gForceOverlap = false;
var gDetailText = "";
var gIsNewAppt = true;
function popupNotifyOverlapInit() {
	$("#popupNotifyOverlap").on("popupafterclose", function (e) {
		var isApptPage = typeof apptController !== 'undefined';
		if (gForceOverlap) {
			if (isApptPage) {
				if (gIsNewAppt)
					apptController.addAppt(gAppt,apptController.gGoToPage,true);
				else
					apptController.editAppt(gAppt,apptController.gGoToPage,true);
			} else if (typeof makeapptViewController !== 'undefined') {
				makeapptViewController.addAppt(makeapptViewController.newAppt,true);
			}
    	    gForceOverlap = false;
		} else {
			if (isApptPage) 
				enableButton(".btnSubmit", doSubmit);
		}
	});
	$("#popupNotifyOverlap").on("popupafteropen", function (e) {
		$(this).find('.popupdetails').html(gDetailText);
	});
}
function doSave() {
	gForceOverlap = true;
	$('#popupNotifyOverlap').popup('close');
}
</script> 
