<?php 
/*
        This page is a pop-up cancel/delete confirmation box.
*/
?>
<div data-role="popup" id="popupSyncConfirm" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupSyncConfirm").popup("close");' title="Close Window">Close</a></div>
    <div id="divSyncConfirm" class="popupinner">
        <form id="frmSyncConfirm" method="post" action="#" data-ajax="false">
        <h2>Sync Blocks and <?php echo ucfirst(WaseUtil::getParm('APPOINTMENTS'))?>?</h2>
        <div class="instructions">Which blocks would you like to sync?</div>
        
        <fieldset>
            <div id="divWhat" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgWhat" class="horizontalradio">
                    <input type="radio" name="radWhat" id="radInstance" value="instance" />
                    <label id="lblInstance" for="radInstance">This instance</label>
                    <input type="radio" name="radWhat" id="radSeries" value="series" />
                    <label id="lblSeries" for="radSeries">Entire series</label>
                </fieldset>
            </div>
            <div id="divSyncButton" class="ui-field-contain popupbutton">    
                <input type="button" value="Sync" data-inline="true" onclick="doSyncConf();" />
            </div>
            <div class="heightfix"></div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
var gSyncBlockView = null;
function doSyncConf() {
    gSyncBlockView.doSync($("#cgWhat :radio:checked").val());
	$("#popupSyncConfirm").popup("close");		
	return false;
}
</script> 
