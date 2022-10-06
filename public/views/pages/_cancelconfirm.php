<?php 
/*
        This page is a pop-up cancel/delete confirmation box.
*/
?>
<div data-role="popup" id="popupCancelConfirm" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupCancelConfirm").popup("close");' title="Close Window">Close</a></div>
    <div id="divCancel" class="popupinner">
        <form id="frmCancel" method="post" action="#" data-ajax="false">
        <h2>Remove this Calendar?</h2>
        <div class="instructionslarge"></div>
        
        <fieldset>
            <div id="divDeleteWhat" class="ui-field-contain fullwidthcontrols">
                <fieldset data-role="controlgroup" id="cgDeleteWhat" class="horizontalradio">
                    <input type="radio" name="radDeleteWhat" id="radInstance" value="instance" />
                    <label id="lblInstance" for="radInstance">This instance</label>
                    <input type="radio" name="radDeleteWhat" id="radSeries" value="series" />
                    <label id="lblSeries" for="radSeries">Entire series</label>
					<input type="radio" name="radDeleteWhat" id="radSeriesFromToday" value="seriesfromtoday" />
                    <label id="lblSeriesFromToday" for="radSeriesFromToday">The entire series from (today) forward</label>
               	</fieldset>
            </div>
            <div id="divCancelText" class="ui-field-contain">
                <label id="lblCancelText" for="txtCancelText" class="hidden-accessible">Text to include in cancelation e-mail:</label>
                <textarea name="txtCancelText" id="txtCancelText" placeholder="Text to include in cancelation e-mail"></textarea>
                <div id="divSendCancel"><input type="checkbox" name="chkSendCancel" id="chkSendCancel" title="Do not send cancelation notification" />
                <label id="lblSendCancel" for="chkSendCancel">Do not send cancelation notification</label></div>
           	</div> 
            <div id="divCancelButton" class="popupbutton ui-field-contain">    
                <input type="button" value="" data-inline="true" onclick="doCancel();" />
            </div>
            <div class="heightfix"></div>
        </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
var gObj = null;
var gObjID = null;
var gNavID = null;
var	gGoToPage = "";
var viewStr = null;

function doCancel() {
	var txt = $("#txtCancelText").val();
	if ($("#chkSendCancel").is(":checked")) txt = "DO NOT SEND CANCELLATION NOTIFICATION(S)";

	switch(gObj) {
		case "appointment": 
			apptController.deleteAppt(gObjID,txt,gGoToPage);
			break;
		case "makeappt": 
			makeapptViewController.deleteAppt(gObjID,txt,gGoToPage);
			break;
		case "block":
			blockController.deleteBlock(gObjID,gNavID,txt,$("#cgDeleteWhat :radio:checked").val(),viewStr);
			break;
		case "calendar":
		    waseCalController.deleteCalendar(gObjID,txt);
			break;
		case "manager":
			if (typeof waseCalController !== 'undefined')
                waseCalController.deleteManager(gObjID);
			if (typeof calendarsViewController !== 'undefined')
                calendarsViewController.deleteManager(gObjID);
			break;
		case "member":
			if (typeof waseCalController !== 'undefined')
		    	waseCalController.deleteMember(gObjID,txt);
			if (typeof calendarsViewController !== 'undefined')
				calendarsViewController.deleteMember(gObjID);
			break;
		case "entry":
		    waitlistController.deleteWaitlistEntry(gObjID,txt);
			break;
	}
	$("#popupCancelConfirm").popup("close");		
	return false;
}

function getEmailTextLabel(inAppName) {
	return 'Text to include in ' + inAppName + ' cancelation e-mail'
}

function popupCConfirmInit(inViewObj) {
		viewStr = inViewObj;
		
	$("#popupCancelConfirm").bind({
		popupafteropen: function(event, ui) {
			//set up text for what we're canceling/deleting
			var ccObject = $("#ccObject").val();
			gObj = ccObject;
			var title = "";
			var act = "";
			var btn = "";
			var obj = ccObject;
			var appName = g_ApptText;
			var appsName = g_ApptsText;

			$("#divDeleteWhat").hide();

			//cancel appointment text
			var askApptText = true;
			var numFutureAppts = 0;

			switch(ccObject) {
				case "appointment": 
				case "makeappt":
					if (typeof makeapptview !== 'undefined') {
    					var calView = makeapptview.calendarViews[gShownCalViewID];
    					var blkView = calView.blockViews[gShownBlockID];
    					appName = blkView.model.get('labels')['APPTHING'];
    					appsName = blkView.model.get('labels')['APPTHINGS']
					} else if (typeof apptview !== 'undefined') { //apptview
						appName = apptview.appName;
						appsName = apptview.appsName;
					} else if (typeof viewcalview !== 'undefined' || typeof myapptsview !== 'undefined') {
						appName = gLabels["APPTHING"];
						appsName = gLabels["APPTHINGS"];
					}
					obj = appName; //for text below
					
					title = "Cancel this " + capitalize(appName) + "?";
					act = "cancel";
					btn = "Cancel the " + appName;
					if (typeof apptview !== 'undefined') { //appt view
						gObjID = apptview.apptid;
						gGoToPage = getReturnLink();
					} else if (typeof viewcalview !== 'undefined') { //view calendar
						gObjID = gCurApptID;
						gGoToPage = "";
					} else { //my appts
						gObjID = gCurApptID;
						gGoToPage = "";
					}
					askApptText = false;
					break;
				case "block":
					title = "Delete this Block?";
					act = "delete";
					btn = "Delete the Block";
					var isrecur = false;
					if (typeof blockview !== 'undefined') {
						gObjID = blockview.blockid;
						gNavID = blockview.calid;
						isrecur = $("#chkRepeats").is(":checked");
						appName = blockview.labels['APPTHING'];
						appsName = blockview.labels['APPTHINGS']
					} else { //view calendar
						gObjID = gCurBlockID;
						appName = gLabels['APPTHING'];
						appsName = gLabels['APPTHINGS'];
						gNavID = gSelectedCalIDs.join(",");
						isrecur = gSerID > 0;
					}
					if (!isrecur) $("#divDeleteWhat").hide();
					else $("#divDeleteWhat").show();
					$(':radio[value="instance"]').siblings('label').trigger('vclick');
					break;
				case "calendar": //only calendar config page
					title = "Remove this Calendar?";
					act = "remove";
					btn = "Remove the Calendar";
					gObjID = calendarconfigview.calid;
					appName = calendarconfigview.savedCalendar.get("labels")["APPTHING"];
					appsName = calendarconfigview.savedCalendar.get("labels")["APPTHINGS"];
					numFutureAppts = calendarconfigview.savedCalendar.get("ownerfutureapps");
					_.each(calendarconfigview.memberList, function(mem) {
						numFutureAppts += mem.memberfutureapps;
					});
					break;
				case "manager":
					if (typeof calendarsViewController !== 'undefined') {
						title = "Deny this Manage Request?";
						act = "deny";
						btn = "Deny the Request";
					} else { //calendar config view
    					title = "Delete this Manager?";
    					act = "delete";
    					btn = "Delete the Manager";
					}
					gObjID = gCurMgrMem;
					askApptText = false;
					break;
				case "member":
					if (typeof calendarsViewController !== 'undefined') {
						title = "Deny this Member Request?";
						act = "deny";
						btn = "Deny the Request";
					} else { //calendar config view
    					title = "Delete this Member?";
    					act = "delete";
    					btn = "Delete the Member";
    					appName = calendarconfigview.savedCalendar.get("labels")["APPTHING"];
    					appsName = calendarconfigview.savedCalendar.get("labels")["APPTHINGS"];
					}
					gObjID = gCurMgrMem;
					numFutureAppts = gCurMgrMem.memberfutureapps;
					askApptText = numFutureAppts > 0;
					break;
				case "entry":
					title = "Delete this Wait List Entry?";
					act = "delete";
					btn = "Delete the Entry";
					gObjID = gCurWaitEntryID;
					askApptText = false;
					break;
			}
			//Set title
			var remimg = '';
			$("#popupCancelConfirm h2").html(remimg + title);

			//Set instructions
			var instr = "Are you sure you want to " + act + " this " + obj;
			if (askApptText)
				instr += " <b>and cancel all future " + appsName;
			if (numFutureAppts > 0) {
				var apptxt = numFutureAppts === 1 ? appName : appsName;
				instr += " (" + numFutureAppts + " " + apptxt + ")";
			}
			instr += "? </b><br><br><b><u>This action cannot be undone</u></b>.";
			$("#popupCancelConfirm .instructionslarge").html(instr);

			$("#divCancelText").toggle(askApptText);
			
			//Set email text label
			var emailTextLabel = getEmailTextLabel(appName);
			$("#lblCancelText").text(emailTextLabel);
			$("#txtCancelText").attr('placeholder', emailTextLabel);
			
			//set button
			$("#popupCancelConfirm #divCancelButton .ui-btn input").val(btn);
			$("#popupCancelConfirm #divCancelButton .ui-btn input").button("refresh");
		}
	});
}
</script> 