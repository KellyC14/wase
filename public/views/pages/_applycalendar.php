<?php 
/*
        This page is a pop-up to apply to manage/member a calendar.
*/
?>
<div data-role="popup" id="popupApply" data-dismissible="false">
    <div style="float:right;"><a id="btnPopupCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupApply").popup("close");' title="Close Window">Close</a></div>
    <div id="divApply" class="popupinner">
        <form id="frmApply" method="post" action="#" data-ajax="false">
            <h2>Apply</h2>
            <input type="hidden" id="hidApplyType" value="" />
            <div class="instructions">Enter the <span class="parmnetid"></span>(s) (comma-separated) for the calendar(s)<span id="extra-instructions"></span><br>
            <span id="defn"></span></div>
            <div class="ui-field-contain">
        		<label id="lblSearchNetIDs" for="txtSearchNetIDs" class="hidden-accessible parmnetid"></label>
    			<input type="search" name="txtSearchNetIDs" id="txtSearchNetIDs" value="" placeholder="" class="searchtext" data-mini="true" />
			</div>
			<div id="divApplyResultArea"></div>
        </form>
    </div>
</div>

<script type="text/javascript">
var gApplyType = "manage";
function popupApplyInit() {
	//empty results when clear search
	$("#txtSearchNetIDs + .ui-input-clear").on('click', function(e) {
		$("#divApplyResultArea").empty();
	});
	$("#frmApply").submit(function(e) {
		calendarsViewController.searchCalendarsByNetIDs($("#txtSearchNetIDs").val());
		return false;
	});
	
	$("#popupApply").on("popupafterclose", function (e) {
	});
	$("#popupApply").on("popupafteropen", function (e) {
		var applyheader = gApplyType === "manage" ? "Manage" : "be a Member of";
		var applytext = gApplyType === "manage" ? " you wish to manage" : " of which you wish to be a member";
		var defn = gApplyType === "manage" ? "Managers can schedule the calendar owner's availability for appointments." : "Members can make themselves available for appointments on a calendar other than their own.";
		$(this).find('h2').html("Apply to " + applyheader + " a Calendar");
		$(this).find('#extra-instructions').text(applytext + '.');
		$(this).find('#defn').text("Note: " + defn);
		$(this).find("#hidApplyType").val(gApplyType);
	});
}

function loadApplyCalendars() {
	$("#divApplyResultArea").empty();
	
	var instrtype = gApplyType === "Manage" ? "" : "of which ";
	var instrtype2 = gApplyType === "Manage" ? "manage" : "be a member";
	
	var str = '';
	
	//There may be no calendars, if so, put a message
	if (gApplyCals.length === 0) {
		str += '<div><i>No Resulting Calendars</i></div>';
	} else {
		str += '<div class="instructions">Click the checkboxes for the calendar(s) ' + instrtype + 'you wish to ' + instrtype2 + '.  Optional: specify text to be included in the e-mail request.</div>';
		str += '<div id="divApplyResults" class="applyresults">';
		str += '<ul id="ulApplyCals" class="applycallist" data-role="listview" data-theme="b" data-divider-theme="b">';
	
		//put a header row in (for large screens)
		str += '<li class="liHeader" data-role="list-divider">';
		str += '<div class="col1 colhdr">Calendar Title</div><div class="col2 colhdr">Owner</div><div class="col3 colhdr">Description</div>';
		str += '</li>';
	
		for (var i=0; i < gApplyCals.length; i++) {
			var cal = gApplyCals[i];
			if (cal !== null) {
				str += '<li>';
				str += '<div class="col1">';
				str += '<div class="checkboxlefttop"><input type="checkbox" class="applychk" name="chkApplyCal" id="chkApply' + cal.get('calendarid') + '" value="' + cal.get('calendarid') + '" /></div>';
				str += '<div class="checkboxlistitem">' + cal.get('title') + '</div>';
				str += '</div>';
				str += '<div class="col2 calowner">&nbsp;' + cal.get('owner').get('name');
				str += '</div><div class="col3 caldesc">&nbsp;' + cal.get('description');
				str += '</div>';			
				str += '<div class="heightfix"></div></li>';
			}
		}
		str += '</ul>';
	
		str += '</div>';
		str += '<div id="divEmailText" class="ui-field-contain labelontop"><label id="lblEmailText" for="txtEmailText">Text for E-mail:</label>';
		str += '<textarea name="txtEmailText" id="txtEmailText" placeholder="Text to be included in e-mail for the ' + gApplyType.toLowerCase() + ' request"></textarea></div>';
		str += '<div id="divSubmit" class="ui-field-contain"><input id="btnApply" name="btnApply" type="button" value="Apply" data-inline="true"/></div>';
	}
	
	$("#divApplyResultArea").append(str);

	if ($("#ulApplyCals").length) $("#ulApplyCals").listview();
	if ($("#txtEmailText").length) $("#txtEmailText").textinput();

	//set up apply button click
	if ($("#btnApply").length) {
		$("#btnApply").click(function() {
			var calids = new Array();
			$("#frmApply .applychk").each(function() {
				 if($(this).is(":checked")) {
					 calids.push($(this).val());
				 } 
			});
			var emailText = $("#txtEmailText").val();
			if ($("#hidApplyType").val() === "manage")
			    calendarsViewController.applyToManage(calids,emailText);
			else //Member
			    calendarsViewController.applyToMember(calids,emailText);
			$("#popupApply").popup("close");
			return false;
		});
	
		//view button styling
		$("#btnApply").button();
		$("#btnApply").button("disable");
		
		//set up checkbox click to toggle view enable/disable
		$("#frmApply").on("click",".applychk",function(e) {
			var doWhat = $('.applychk:checked').length ? 'enable' : 'disable';
			$("#btnApply").button(doWhat);
		});
	}
}

</script> 
