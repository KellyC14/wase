<?php 
include "_copyright.php";

/*
        This page allows appointment makers to sign up for an appointment.
		Arguments:
			- calid: the calendar that the block/appointment is on
			- blockid: the block that the appointment is on (new appointments only)
			- st: the start time of the selected appointment slot
			- et: the end time of the selected appointment slot
			- apptid: the appointment to be viewed/edited
*/

$pagenav = 'makeappt'; /*for setting the "selected" page navigation, new appointment */
if (isset($_GET["apptid"])) $pagenav = 'myappts'; /* if view/edit appointment */
include "_header.php";
include "_includes.php"; ?>

<script type="text/javascript" language="javascript" src="../../libraries/classes/notifyremind.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/accessrestrictions.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/block.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/appointment.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/ApptViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/ApptController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="apptview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
	$(document).on('pagecreate', '[data-role="page"]', function(){
		var str = '';
    		str += '<div class="submitbuttons"><div class="ui-field-contain">';
			str += '<input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" data-mini="true" /> ';
    		str += '<input class="btnSubmit" type="button" value="Save" data-inline="true" data-mini="true" />	';
			str += '</div>';
    		str += '</div>';
			str += '<div data-type="horizontal" class="localnav">';
    		str += '<a id="cancelbutton" href="#" class="ui-btn ui-icon-delete ui-btn-icon-left ui-btn-b" title="Cancel">Cancel</a>'; //text updated in apptview.js
    		str += '</div>';
			$(".actionbuttons").append(str);

		$(".btnCancelSubmit").button();
		$(".btnCancelSubmit").on("click", cancelSubmit);
		$(".btnSubmit").button();
		$(".btnSubmit").on("click", doSubmit);
	});
	
	var apptid = "";
	var blockid = "";
	var calid = "";
	var sdt = "";
	var edt = "";
	
   	$(document).ready(function() {
      	doOnReady();
		controller.getParameters(["NETID"]);
		
		apptid = $.urlParam("apptid");
		blockid = $.urlParam("blockid");
		calid = $.urlParam("calid");
		sdt = getDateTimeFromURL($.urlParam("sdt"));
		edt = getDateTimeFromURL($.urlParam("edt"));

		apptview.setPageVars(apptid,sdt,edt);
		apptViewController.loadApptViewData(calid, blockid, apptid);
		
		//check infmsg
		controller.getAndClearSessionVar(["infmsg"]);

		if (isNullOrBlank(apptid)) controller.getPrefs(["txtmsgemail","remind"]);

		popupCConfirmInit("apptview");
		popupNotifyOverlapInit();
		
	});

</script>                                                               
</head>

<body onUnload="">

<!-- Sign up for an Appointment -->
<div data-role="page" id="signup">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">
   		<form id="apptinfo" method="post" action="#" data-ajax="false">
            <input type="hidden" id="helpTopic" value="quickedit" />
   		
           	<h1 id="pagetitle" class="withbuttons">Title</h1>  
            <div class="actionbuttons clearfix"></div>
            
            <fieldset>
                <div id="divBlockTitle" class="ui-field-contain textonlyfield"></div>
                <div id="divBlockOwner" class="ui-field-contain textonlyfield"></div>
                <div id="divBlockLocation" class="ui-field-contain textonlyfield"></div>
                <div id="divDate" class="ui-field-contain textonlyfield"></div>
    
                <div id="divTime" class="ui-field-contain">
                    <label id="lblTime" for="selTime" class="required">Time:</label>
                    <select name="selTime" id="selTime" title="Time" data-mini="true"></select>
                    <label id="lblTimeMulti" for="divTimeSelect" class="">Time:</label>
                    <div id="divTimeSelect" class=""></div>
                    <div id="divTimeNote">** Denotes times for this appointment.</div>
                </div>
                <div id="divPurpose" class="ui-field-contain">
                    <label id="lblPurpose" for="txtPurpose" class="">Purpose:</label>
                    <textarea name="txtPurpose" id="txtPurpose" placeholder="Purpose" title="Purpose" class="highlight" data-mini="true"></textarea>
                </div>
                <div id="divVenue" class="ui-field-contain">
                    <label id="lblVenue" for="txtVenue" class="">Meeting Details:</label>
                    <input type="text" name="txtVenue" id="txtVenue" placeholder="Enter any information about the meeting that you wish to share"
                           title="Contact Specification" class="highlight" data-mini="true" />
                </div>
                <div id="divWhenMade" class="ui-field-contain textonlyfield"></div>
            </fieldset>
            <hr>
            
            <h2 id="hApptFor"></h2>

			<fieldset>
    			<div id="divApptType" class="ui-field-contain">
                    <fieldset data-role="controlgroup" id="cgApptType" class="horizontalradio">
                    <legend>Appt For:</legend>
                        <input type="radio" name="radApptType" id="radMember" value="member" class="appttyperadio" data-mini="true" />
                        <label id="lblMember" for="radMember"><?php echo WaseUtil::getParm('INSNAME');?> Community Member</label>
                        <input type="radio" name="radApptType" id="radGuest" value="guest" class="appttyperadio" data-mini="true" />
                        <label id="lblGuest" for="radGuest">Guest</label>
                    </fieldset>
                </div>
                
                <!-- need input text for owners/managers -->
                <div id="divUserID" class="ui-field-contain"></div>
                 
                <div id="divApptName" class="ui-field-contain">
                    <label id="lblApptName" for="txtApptName" class="required">Name:</label>
                    <input type="text" name="txtApptName" id="txtApptName" placeholder="Name" title="Name" class="highlight" data-mini="true" />
                </div>
                
                <div id="divEmail" class="ui-field-contain">
                    <label id="lblEmail" for="txtEmail" class="required">E-mail(s):</label>
                    <input type="text" name="txtEmail" id="txtEmail" placeholder="" title="E-mail" class="highlight" data-mini="true" />
                </div>
                
                <div id="divPhone" class="ui-field-contain">
                    <label id="lblPhone" for="txtPhone" class="">Phone:</label>
                    <input type="text" name="txtPhone" id="txtPhone" placeholder="___-___-____" title="Phone" class="highlight" data-mini="true" />
                </div>
            </fieldset>
            <hr>
            
			<fieldset>
            	<h2>Reminders</h2>
                <div class="instructions">If checked, a reminder will be sent to the e-mail address(es) provided (comma-separated). To also receive a text message, enter your cell phone number and provider.</div>
                
                <div id="divRemind" class="ui-field-contain">
                    <fieldset data-role="controlgroup" id="cgNotifyAndRemind">
                        <legend>Remind?</legend>
                        <label id="lblRemind" for="chkRemind">Email Reminder?</label>
                        <input type="checkbox" name="chkRemind" id="chkRemind" checked="checked" title="Remind via Email" data-mini="true" />
                        <label id="lblRemindText" for="chkRemindText">Text Message Reminder?</label>
                        <input type="checkbox" name="chkRemindText" id="chkRemindText" title="Remind via Text" data-mini="true" />
                    </fieldset>
                </div>
                
                <div id="divTextMsgEmail" class="ui-field-contain textonlyfield">
                    <label id="lblTxtEmail" for="txtTxtEmail">Text Msg Address:</label>
                    <div><div id="divTextEmail" class="lineuptext"></div>&nbsp;&nbsp;&nbsp;<a class="txtmsgclick" href="#" data-type="button" onclick="openApptTxtMsg();" title="Enter text message email address">click to enter</a></div>
               	</div>
            </fieldset>

			<div class="instructions" style="padding-top:20px;">* required</div>
            
            <div class="submitbuttons bottombuttons">
                <div id="divSubmit" class="ui-field-contain">    
                    <input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" data-mini="true" />
                    <input class="btnSubmit" type="button" value="Save" data-inline="true" data-mini="true" />
                </div>
                <div class="heightfix"></div>
            </div>

			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="appointment" />
           
       		<?php include('_txtemailselect.php');?>
			<?php include('_notifyapptoverlap.php');?>
            <?php include('_emailasuseridconfirm.php');?>
        </form>

	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
    <script type="text/javascript">
        function getReturnLink() {
		    var times = apptview.getSelectedStartEndTimes();
			var sdt = moment(times.sdt,'YYYY-MM-DD hh:mm:ss');
			var lnk = getPageURL('makeappt',{calid: calid, blockid: blockid, sdt: sdt.format('YYYY-MM-DD')});
			for (var i=0; i < gBreadCrumbs.length; i++) {
				if (typeof (gBreadCrumbs[i][1]) !== 'undefined' && gBreadCrumbs[i][1].indexOf("myappts.php") > -1) 
					lnk = getPageURL('myappts');
				else if (typeof (gBreadCrumbs[i][1]) !== 'undefined' && gBreadCrumbs[i][1].indexOf("viewcalendar.php") > -1) {
					lnk = getPageURL('viewcalendar',{calid: calid, sdt: getURLDateTime(sdt)});
				}
			}
			return lnk;
        }

        function checkRequired() {
            //check to see if timeslots are selected
            if (apptview.isMultiTimeSelect() && !$(".timeslot.selected").length) {
                var err = new Error({errorcode: "",errortext: "Please select an appointment time."});
                Error.displayErrors(new Array(err));
                return false;
            }
            return isvalid = isValidForm("apptinfo");
        }
		function doSubmit() {
			if (checkRequired()) {
				if (apptview.saveApptData()) {
    				disableButton(".btnSubmit");
    				//figure out the 'next' page
    			    var lnk = getReturnLink();
    				if (apptview.isNewAppt) {
    					apptController.addAppt(gAppt,lnk);
    				} else {
    					apptController.editAppt(gAppt,lnk);
    				}
				}
			}
		}
		
		function cancelSubmit() {
			document.location.href = getReturnLink();
		}
		
		function openApptTxtMsg() {
			var val = $("#apptinfo #divTextEmail").text();
			var callback = _.bind(apptViewController.isValidEmail,apptViewController);
		    $("#editTextMsgEmail").popup('open');
		    initTextPopup(val,callback,'#signup #editTextMsgEmail-popup');
	    }
    </script>                                                               

</div><!-- /page -->

</body>
</html>
