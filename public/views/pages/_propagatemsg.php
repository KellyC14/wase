<?php 
/*
        This page is a pop-up to select whether or not to propagate calendar changes to blocks.
*/
?>
<div data-role="popup" id="popupPropagate" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupPropagate").popup("close");' title="Close Window">Close</a></div>
    <div id="divPropagate" class="popupinner">
        <form id="frmPropagate" method="post" action="#" data-ajax="false">
        <h2>Propagate Changes?</h2>
        <div class="msg">The changes you have made will be used as default values when you add new blocks to your calendar. Would you also like to propagate these changes to future blocks already on your calendar?</div>
        
        <fieldset>
        <div class="submitbuttons buttonspopup">
            <div id="divButtons" class="ui-field-contain popupbutton">    
                <input type="button" value="No" data-inline="true" onclick="doPropagate('none');" />
                <input type="button" value="Yes" data-inline="true" onclick="doPropagate('owner');" class="ownerblocks" />
                <input type="button" value="Owner Only" data-inline="true" onclick="doPropagate('owner');" class="memberblocks" />
                <input type="button" value="All" data-inline="true" onclick="doPropagate('all');" class="memberblocks" />
            </div>
            <div class="heightfix"></div>
        </div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
var gCalObj = null;
var gPropVal = "";

function doPropagate(propVal) {
    disableButton(".btnSubmit");
    waseCalController.editCalendar(gCalObj,propVal);
	$("#popupPropagate").popup("close");		
	return false;
}

function setPropagateValues(inCalObj, inPropVal) {
	gCalObj = inCalObj;
	gPropVal = inPropVal;
}
function popupPropagateInit() {
	$("#popupPropagate").bind({
		popupafteropen: function(event, ui) {
			var instr = "The changes you have made will be used as default values when you add new blocks to your calendar.  ";
			var instr2 = "Would you also like to apply these changes to future blocks already on your calendar?  ";
			var blocksingle = " block";
			var blockmulti = " blocks";
			if (gPropVal === "owner") {
				var blktext = calendarconfigview.numFutureBlocks === 1 ? blocksingle : blockmulti;
				instr += instr2 + "(<b><u>" + calendarconfigview.numFutureBlocks + blktext + "</u></b>)";
				$("#divPropagate .ownerblocks").closest('.ui-btn').show();
				$("#divPropagate .memberblocks").closest('.ui-btn').hide();
			} else if (gPropVal === "member" || gPropVal === "warnmember") {
				var owneronly = calendarconfigview.numFutureBlocks - calendarconfigview.numFutureMemberBlocks;
				var ownerblktext = owneronly === 1 ? blocksingle : blockmulti;
				var all = calendarconfigview.numFutureBlocks;
				var allblktext = all === 1 ? blocksingle : blockmulti;
				instr += instr2
					+ "<br><br>If yes, should only <b>calendar owner blocks (<u>" + owneronly + ownerblktext + "</u>)</b> " 
					+ "or <b>all owner and member blocks (<u>" + all + allblktext + "</u>)</b> be updated?";
				$("#divPropagate .ownerblocks").closest('.ui-btn').hide();
				$("#divPropagate .memberblocks").closest('.ui-btn').show();

				if (gPropVal === "warnmember") {
					instr += "<br><br>NOTE: If you choose 'All', contact information (name, phone, email) propagates to owner blocks ONLY.";
				}
			}
			$("#divPropagate .msg").html(instr);
		}
	});
}
</script> 
