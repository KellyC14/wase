<?php 
/*
        This page is a pop-up to select whether or not to propagate calendar changes to blocks.
*/
?>
<div data-role="popup" id="popupSaveWhat" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupSaveWhat").popup("close");' title="Close Window">Close</a></div>
    <div id="divSaveWhat" class="popupinner">
        <form id="frmSaveWhat" method="post" action="#" data-ajax="false">
        <h2>Save Recurring Block</h2>
        <div class="instructions">Apply changes made to:</div>
        
        <fieldset>
            <fieldset data-role="controlgroup" id="cgSaveWhat" class="horizontalradio">
                <input type="radio" name="radSaveWhat" id="radInstance" value="instance" data-mini="true" />
                <label id="lblInstance" for="radInstance">Only this instance</label>
                <input type="radio" name="radSaveWhat" id="radSeries" value="series" data-mini="true" />
                <label id="lblSeries" for="radSeries">The entire series</label>
                <input type="radio" name="radSaveWhat" id="radSeriesFromToday" value="seriesfromtoday" data-mini="true" />
                <label id="lblSeriesFromToday" for="radSeriesFromToday">The entire series from (today) forward</label>
            </fieldset>
            <div class="submitbuttons buttonspopup">
                <div id="divButtons" class="ui-field-contain popupbutton">    
                    <input type="button" value="Cancel" data-inline="true" onclick="$('#popupSaveWhat').popup('close');" />
                    <input type="button" value="Save" data-inline="true" onclick="doSaveWhat();" />
                </div>
                <div class="heightfix"></div>
            </div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
function doSaveWhat() {
	disableButton(".btnSubmit");
    var savewhat = $("#cgSaveWhat :radio:checked").val();
	blockController.editBlock(gBlock,savewhat);
	$("#popupSaveWhat").popup("close");		
	return false;
}
</script> 
