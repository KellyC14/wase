<?php 
/*
        This page is a pop-up to select whether or not to propagate calendar changes to blocks.
*/
?>
<div data-role="popup" id="popupUnavailable" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupUnavailable").popup("close");' title="Close Window">Close</a></div>
    <div id="divUnavailable" class="popupinner">
        <form id="frmUnavailable" method="post" action="#" data-ajax="false">
        <h2>Verify Block Unavailable</h2>
        <div class="instructions">Are you sure you want to save this block as 'unavailable'?  If not, please update the "Is the Block Available?" field.</div>
        
        <fieldset>
            <div class="submitbuttons buttonspopup">
                <div id="divButtons" class="ui-field-contain popupbutton">    
                    <input type="button" value="Cancel" data-inline="true" onclick="$('#popupUnavailable').popup('close');" />
                    <input type="button" value="Continue" data-inline="true" onclick="doSave();" />
                </div>
                <div class="heightfix"></div>
            </div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
var gContinueUnavail = false;
function popupVerifyUnavailInit() {
	$("#popupUnavailable").on("popupafterclose", function (e) {
		if (gContinueUnavail) {
    	    blockview.doSaveEditBlock();
    	    doSaveBlock();
    	    gContinueUnavail = false;
		}
	});
}
function doSave() {
	gContinueUnavail = true;
	$('#popupUnavailable').popup('close');
}
</script> 
