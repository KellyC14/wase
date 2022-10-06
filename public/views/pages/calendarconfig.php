<?php 
include "_copyright.php";

/*
        This page allows users to create, view, edit calendars.
		Arguments:
			- calid: the calendar to be viewed/edited
*/

$pagenav = 'calendars'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<link type="text/css" rel="stylesheet" href="../css/calendar.css" media="all" />

<!-- for Blob support -->
<script type="text/javascript" src="../../libraries/FileSaver.min.js"></script>

<!-- spinbox widget: based on jtsage on github.  HEAVILY modified. -->
<script type="text/javascript" src="../../libraries/jqm-spinbox.js"></script>

<script type="text/javascript" language="javascript" src="../../libraries/classes/calendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/notifyremind.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/accessrestrictions.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/waitlistentry.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/CalConfigViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/WASECalController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/WaitlistController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="calendarconfigview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
	$(document).on('pagecreate', '[data-role="page"]', function(){
		//set up action buttons
		var str = '';
    		str += '<div class="submitbuttons"><div class="ui-field-contain">';
			str += '<input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" data-mini="true" /> ';
    		str += '<input class="btnSubmit" type="button" value="Save" data-inline="true" data-mini="true" />	';
			str += '</div>';
    		str += '</div>';
			str += '<div data-type="horizontal" class="localnav">';
    		str += '<a id="cancelbutton" href="#" class="ui-btn ui-icon-delete ui-btn-icon-left ui-btn-b" title="Remove Calendar">Remove Cal</a>';
    		str += '</div>';
		$(".actionbuttons").append(str);
		$(".localnav").controlgroup({ mini: true });
		
		$("#cancelbutton").click(function() {
			$("#ccObject").val("calendar");
			$("#popupCancelConfirm").popup("open");
		});

		var $cancel = $(".btnCancelSubmit");
        $cancel.button();
        $cancel.on("click", doCancelForm);
        var $submit = $(".btnSubmit");
        $submit.button();
        $submit.on("click", doSubmit);
		
});
	
   	$(document).ready(function() {
      	doOnReady();
		calendarconfigview.initPage($.urlParam("calid")); 
		
		//check infmsg
		controller.getAndClearSessionVar(["infmsg"]);

		popupCalInit(calendarconfigview);
		popupCConfirmInit("calendarconfigview");
		popupPropagateInit();
		
		controller.getSessionVar(["breadcrumbs"]);

		controller.getPrefs(["localcal"]);
		
   		$('.loader').hide();
	});

</script>                                                               
</head>

<body onUnload="">

<!-- Create, View, Edit a Block -->
<div data-role="page" id="calconfig">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
    	<form id="calinfo" method="post" action="#" data-ajax="false">        
            <input type="hidden" id="helpTopic" value="createcalendar" />
    
		<h1 id="pagetitle" class="withbuttons">Title</h1>
        <div class="actionbuttons clearfix"></div>
        
        <fieldset>       
            <!--Basic Info-->
            <div id="divCalTitle" class="ui-field-contain">
                <label id="lblCalTitle" for="txtCalTitle" class="required">Calendar Title:</label>
                <input type="text" name="txtCalTitle" id="txtCalTitle" placeholder="" title="Calendar Title" class="highlight" />
            </div>
            <div class="ui-field-contain textonlyfield fieldContainer">
                <label id="lblCalOwner" for="divCalOwner" class="rightlabel">Calendar Owner:</label>
                <div id="divCalOwner" title="Calendar Owner" class="lineuptext"></div>
                <div id="divCalIDField" class="fieldContained textonlyfield">
                    <label id="lblCalID" for="divCalID" class="rightlabel">Calendar ID:</label>
                    <div id="divCalID" title="Calendar ID" class="lineuptext"></div>
                </div>
            </div>
            <div id="divCalendarURL" class="ui-field-contain">
                <label>Direct URL for Calendar:</label>
                <div class="urlarea">
                    <div class="cal_urlarea"></div>
                </div>
            </div>

            <div id="divAllowBlockOverlap" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgAllowBlockOverlap" class="checkBoxFitted">
                    <legend>Allow Block Overlap?:</legend>
                    <input type="checkbox" name="chkAllowBlockOverlap" id="chkAllowBlockOverlap" title="Allow blocks to overlap?" />
                    <label id="lblAllowBlockOverlap" for="chkAllowBlockOverlap">Allow (when blocks have same owner)</label>
                </fieldset>
            </div>

            <hr id="hrWaitlist" class="short">
            <!-- Waitlist -->
            <div id="divWaitlist" class="ui-field-contain">
                <label for="selWaitlist">Wait List:</label>
                <select name="selWaitlist" id="selWaitlist" data-role="flipswitch" title="Enable Wait List for this calendar">
                    <option value="false">Off</option>
                    <option value="true">On</option>
                </select>
                <div id="newCalWaitlistNote" class="linemeup italic">(Waitlist entries may be added after calendar is saved.)</div>
            </div>
            <div id="divWaitlistEntries"></div>

            <hr class="short" id="hrPublish">
            <!-- Calendar/Subscription URL -->
            <div id="divSetPublish" class="ui-field-contain">
                <label for="selSetPublish">Publish calendar (iCal/RSS):</label>
                <select name="selSetPublish" id="selSetPublish" data-role="flipswitch" title="Enable links for iCal and RSS feeds.">
                    <option value="false">Off</option>
                    <option value="true">On</option>
                </select>
            </div>
            <div id="divSubscribeURLs" class="ui-field-contain">
                <label>Subscription/RSS URLs:</label>
                <div class="urlarea">
                    <div class="ical_urlarea"></div>
                </div>
            </div>

            <div data-role="popup" id="warningIcal">
                <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#warningIcal").popup("close");' title="Close Window">Close</a></div>
                <h2>Warning</h2>
                <div class="popupinner">
                    <div class="instructions">Warning:  when you publish your calendar, WASE will generate a URL for iCal and RSS subscriptions.
                        Although these URLs include a password, anyone who has these URLs will be able to view your WASE calendar.  If you intend your calendar to be public, that is fine.
                        If not, be sure to keep these URLs private.  If all you want to do is to be able to see your WASE calendar in your local calendar (e.g., Outlook or Google),
                        use the WASE preferences system instead (it will let you export your calendar directly into Outlook or Google). </div>
                    <div class="submitbuttons buttonspopup">
                        <div class="ui-field-contain popupbutton">
                            <input type="button" value="Cancel" data-inline="true" onClick="calendarconfigview.doCancelIcal(); $('#warningIcal').popup('close');" />
                            <input type="button" value="Confirm" data-inline="true" onClick="calendarconfigview.doConfirmIcal(); $('#warningIcal').popup('close');" />
                        </div>
                        <div class="heightfix"></div>
                    </div>
                </div>
            </div>

            <hr>
            <!--Default Block Settings-->
            <div id="divDefBlockSettings" class="group" data-role="collapsible" data-collapsed="false" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Default Block Settings</h3>
                <div class="">      
                   <div class="instructions">The settings below will be used as default values when you add new blocks to your calendar. You can override them when you Add/Edit blocks.</div>
                <fieldset>
                <div id="divDescription" class="ui-field-contain">
                    <label id="lblDescription" for="txtDescription" class="">Description:</label>
                    <textarea name="txtDescription" id="txtDescription" placeholder="" title="Description" class="highlight"></textarea>
                </div>
                <div id="divLocation" class="ui-field-contain">
                    <label id="lblLocation" for="txtLocation" class="required">Location:</label>
                    <input type="text" name="txtLocation" id="txtLocation" placeholder="" title="Location" class="highlight" />
                </div>
                <div id="divName" class="ui-field-contain">
                    <label id="lblName" for="txtName" class="required">Name:</label>
                    <input type="text" name="txtName" id="txtName" placeholder="" title="Name" class="highlight" />
                </div>
                <div id="divPhone" class="ui-field-contain">
                    <label id="lblPhone" for="txtPhone" class="">Telephone:</label>
                    <input type="text" name="txtPhone" id="txtPhone" placeholder="___-___-____" title="Telephone" class="highlight" />
                </div>
                <div id="divEmail" class="ui-field-contain">
                    <label id="lblEmail" for="txtEmail" class="required">E-mail(s), comma separated:</label>
                    <input type="text" name="txtEmail" id="txtEmail" placeholder="" title="E-mail" class="highlight" />
                </div>
                <div id="divNotifyAndRemind" class="ui-field-contain">
                    <fieldset data-role="controlgroup" id="cgNotifyAndRemind" class="checkBoxFitted">
                        <legend>For <u>Calendar</u> Owner:</legend>
                        <label id="lblNotify" for="chkNotify">Send Notifications?</label>
                        <input type="checkbox" name="chkNotify" id="chkNotify" checked="checked" title="Notify" />
                        <label id="lblRemind" for="chkRemind">Send Reminders?</label>
                        <input type="checkbox" name="chkRemind" id="chkRemind" checked="checked" title="Remind" />
                    </fieldset>
                </div>                
                <div id="divApptText" class="ui-field-contain">
                    <label id="lblApptText" for="txtApptText">Text for <span class="appName"><?php echo WaseUtil::getParm('APPOINTMENT')?></span> E-mail:</label>
                    <textarea name="txtApptText" id="txtApptText" placeholder="Text to be included in e-mail notifications and reminders" title="Email Text" class="highlight"></textarea>
                </div>
                <div id="divRequirePurpose" class="ui-field-contain">
                    <fieldset data-role="controlgroup" id="cgRequirePurpose" class="checkBoxFitted">
                    <legend>Require <span class="appName"><?php echo WaseUtil::getParm('APPOINTMENT')?></span> Purpose?:</legend>
                    <input type="checkbox" name="chkRequirePurpose" id="chkRequirePurpose" title="Require the Purpose field on <?php echo WaseUtil::getParm('APPOINTMENTS')?>?" />
                    <label id="lblRequirePurpose" for="chkRequirePurpose">Required</label>
                    </fieldset>
                </div>

                <input type="hidden" id="fld" />
                <input type="hidden" id="fldTitle" />
                <?php include('_dateselect.php'); ?>
                </fieldset>
                </div>
            </div>


            <!-- Managers/Members -->
            <hr id="hrCollapseMgrMem">
            <div id="divCollapseMgrMem" class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Managers and Members</h3>
                <div id="divManagersAndMembers"></div>
            </div>

            <hr>
            <!-- Default Access Restrictions -->
            <div id="divDefAR" class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Default Access Restrictions</h3>
                <div id="calendarAR" class="arContent">
                <fieldset>
             	</fieldset>
            	</div>
            </div>

			<!-- Labels -->
            <hr id="hrCollapseLabels">
            <div id="divCollapseLabels" class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Labels</h3>
            <div id="divLabels"></div>
            </div>
                    

			<div class="instructions" style="padding-top:20px;">* required</div>
            <div class="submitbuttons bottombuttons">
                <div id="divSubmit" class="ui-field-contain">    
                 	<input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" />    
                   	<input class="btnSubmit" type="button" value="Save" data-inline="true" />
                </div>
                <div class="heightfix"></div>
            </div>

			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="calendar" />
  
 			<?php include('_propagatemsg.php');?>
 			
 			<div data-role="popup" id="popupUnsavedManMem" data-dismissible="false">
                <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupUnsavedManMem").popup("close");' title="Close Window">Close</a></div>
                <div class="popupinner">
                    <h2>Unsaved Managers/Members</h2>
                    <div class="instructions">Changes you have made to managers or members are not saved <span class="whoIsUnsaved"></span>.
                        <br><br>Choose "Cancel" to save each manager/member row individually before saving the calendar.
                        <br><br>Choose "Force Save" to continue without saving the changed manager/members.
                    </div>
                    <fieldset>
                    <div class="submitbuttons buttonspopup">
                        <div id="divButtons" class="ui-field-contain popupbutton">    
                            <input type="button" value="Cancel" data-inline="true" onclick='$("#popupUnsavedManMem").popup("close");' />
                            <input type="button" value="Force Save" data-inline="true" onclick='forceSave();' />
                        </div>
                        <div class="heightfix"></div>
                    </div>
                    </fieldset>
                </div>
            </div>
 			
        </fieldset>  
        </form>

        <?php include('_emailasuseridconfirm.php');?>

        <div data-role="popup" id="popupSyncCalendar" data-dismissible="false">
        <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupSyncCalendar").popup("close");' title="Close Window">Close</a></div>
        <div id="divSyncCal" class="popupinner">
            <form method="post" action="#" data-ajax="false">
            <h2>Sync Calendar?</h2>
            <div>Note: data will be written to your <span class="synccaltype">local</span> calendar even if it is already on your <span class="synccaltype">local</span> calendar. This could generate duplicate entries.</div>
            <div id="divWhat" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgWhat" class="horizontalradio">
                    <input type="radio" name="radWhat" id="radFromToday" value="fromtoday" checked="checked" />
                    <label id="lblFromToday" for="radFromToday">Sync from today forward<div id="divFromTodayCounts" class="synccount"></div></label>
                    <input type="radio" name="radWhat" id="radAll" value="all" />
                    <label id="lblAll" for="radAll">Sync everything<div id="divAllCounts" class="synccount"></div></label>
                </fieldset>
            </div>
            <fieldset>
                <div id="divSyncCalButton" class="ui-field-contain popupbutton">    
                    <input type="button" value="Sync" data-inline="true" onclick="doSyncCalendar();" />
                </div>
                <div class="heightfix"></div>
            </fieldset>
        
            </form>
        </div>
		</div>
              
        
	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
    <script type="text/javascript">
	function doSyncCalendar() {
		calconfigViewController.syncCalendar(g_loggedInUserID, $("#cgWhat :radio:checked").val(), calendarconfigview.calid);
		$("#popupSyncCalendar").popup("close");
		return false;
	}
	
    	function changedManagerMembers() {
			var changedIDs = [];
			_.each(gUnsavedMgrMems, function(mgrmem) {
				var $el = $('#divManagersAndMembers').find('#'+mgrmem);
				var userid = mgrmem.substr(mgrmem.indexOf('_')+1);

				var parid = $el.parent().attr('id');
				var txt = parid.substr(2,parid.length-3);
				if (mgrmem.indexOf('liAddRow') > -1) { //new manager or member?
					changedIDs.push('New ' + txt);
				} else {
					var listMgrMem = [];
    				if (parid === 'ulManagers') { //check for manager
    					listMgrMem = calendarconfigview.savedCalendar.managers;
    				} else { //member row
    					listMgrMem = calendarconfigview.savedCalendar.members;
    				}
    				_.each(listMgrMem, function(m) {
    					if (m.user.userid === userid) {
    						var newNotify = $el.find('.colNotify input').is(':checked');
    						var newRemind = $el.find('.colRemind input').is(':checked');
    						if (newNotify !== m.notify || newRemind !== m.remind) {
    							changedIDs.push(txt + ': ' + userid);
    						}
    					}
    				});
				} 
			});
			return changedIDs;
    	}
	
		function doSubmit() {
			//alert any unsaved manager/members
			var changedMgrMems = changedManagerMembers();
			if (gUnsavedMgrMems.length > 0 && changedMgrMems.length > 0) {
			    var $popup = $("#popupUnsavedManMem");
                $popup.find(".whoIsUnsaved").html(" (" + changedMgrMems.join(', ') + ")");
                $popup.popup("open");
			} else {
    			if (isValidForm("calinfo")) {
    				var obj = calendarconfigview.saveCalData();
    				var calObj = obj.calObj;
    				if (obj.doSave) {
        				if (isNullOrBlank(calObj.calendarid)) { //create calendar
        				    disableButton(".btnSubmit");
        					waseCalController.addCalendar(calObj);
        				} else { //edit calendar
        					if (obj.askPropagate !== 'no') { //propagate changes message
            					setPropagateValues(calObj, obj.askPropagate);
        						$("#popupPropagate").popup("open");
        					} else {
        					    disableButton(".btnSubmit");
        					    waseCalController.editCalendar(calObj);
        					}
        				}
    				} else {
    				    doCancelForm();  //DO NOT save if no changes made because Save may overwrite?
    				}
    			}
			}
		}
		
		function forceSave() {
            $("#popupUnsavedManMem").popup("close");
            gUnsavedMgrMems = [];
	        doSubmit();
        }

		function doCancelForm() {
			var lnk = "/calendars.php";
			for (var i=0; i < gBreadCrumbs.length; i++) {
				if (typeof (gBreadCrumbs[i][1]) !== 'undefined' && gBreadCrumbs[i][1].indexOf("viewcalendar.php") > -1) 
					lnk = "/viewcalendar.php?calid=" + $.urlParam("calid");
			}
			document.location.href = getURLPath() + lnk;
		}
		
    </script>

</div><!-- /page -->

</body>
</html>
