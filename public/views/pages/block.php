<?php
include "_copyright.php";

/*
        This page allows calendar owners to create, view, edit blocks.
		Arguments:
			- calid: the calendar that the block is on (new blocks only)
			- blockid: the block to be viewed/edited
			- start: the date on which the block should start (default)
*/

$pagenav = 'calendars'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<link type="text/css" rel="stylesheet" href="../css/calendar.css" media="all" />
<link type="text/css" rel="stylesheet" href="../css/formfields.css" media="all" />

<!-- spinbox widget: based on jtsage on github.  HEAVILY modified. -->
<script type="text/javascript" src="../../libraries/jqm-spinbox.js"></script>

<script type="text/javascript" language="javascript" src="../../libraries/classes/calendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/notifyremind.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/accessrestrictions.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/block.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/timeselector.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/selectlist.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/BlockViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/BlockController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="blockview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
var gNavDate = null;
	$(document).on('pagecreate', '[data-role="page"]', function(){
		//var ispanel = window.matchMedia !== undefined && window.matchMedia("(max-width: 480px)").matches; //small screen

		var str = '';
    		str += '<div class="submitbuttons"><div class="ui-field-contain">';
    		str += '<input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" data-mini="true" /> ';
    		str += '<input class="btnSubmit" type="button" value="Save" data-inline="true" data-mini="true" />	';
    		str += '</div>';
			str += '</div>';
		    str += '<div data-type="horizontal" class="localnav">';
    		str += '<a id="cancelbutton" href="#" class="ui-btn ui-icon-delete ui-btn-icon-left ui-btn-b" title="Delete Block">Delete</a>';
    		str += '</div>';
		    $(".actionbuttons").append(str);
            

		$("#cancelbutton").click(function(e) {
			gNavDate = moment($("#txtStartDate_0").val(),"M/D/YYYY").toDate();
			$("#popupCancelConfirm").popup("open");
		});

        $("#ownerEditButton").click(function(e) {
            $("#divBlockOwner .editor").toggle(true);
        });
        $("#ownerEditSave").click(function(e) {
            blockview.setUserDisplay($("#txtName").val(),$("#txtPhone").val(),$("#txtEmail").val());
            $("#divBlockOwner .editor").toggle(false);
        });
        $("#ownerEditCancel").click(function(e) {
            $("#divBlockOwner .editor").toggle(false);
        });

		$(".btnCancelSubmit").button();
		$(".btnCancelSubmit").on("click", goToViewCal);
		
		$(".btnSubmit").button();
		$(".btnSubmit").on("click", doSubmit);

		$("input[type=text], textarea").on('focus', function() {
			$(this).select();
		});
});
	
	var blockid = "";
	var calid = "";
	var sdt = new Date();
    	
   	$(document).ready(function() {
      	doOnReady();

		blockid = $.urlParam("blockid");
		calid = $.urlParam("calid");
		var urlStart = $.urlParam("startdate");
		if (urlStart)
			sdt = getDateTimeFromURL($.urlParam("startdate"));
		
		blockview.setPageVars(calid, blockid, sdt);
		blockViewController.loadBlockViewData(calid, blockid, ["DAYTYPES","INSNAME","NETID","CURTERMSTART","CURTERMEND","COURSELIM","COURSEIDPROMPT","ADRESTRICTED","GROUPIDPROMPT","STATUS_ATTRIBUTE","STATUSPROMPT","USERPROMPT"]);
		blockViewController.getNamedDates();
		
		//initialize popups
		popupCalInit(blockview);
        popupDeadlineInit();
		popupCConfirmInit("blockview");
		popupVerifyUnavailInit();

		controller.getSessionVar(["breadcrumbs"]);
		controller.getAndClearSessionVar(["infmsg"]);
		
   		$('.loader').hide();
	});

</script>                                                               
</head>


<body onUnload="">

<!-- Create, View, Edit a Block -->
<div data-role="page" id="editblock">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
    <form id="blockinfo" method="post" action="#" data-ajax="false">
        <input type="hidden" id="helpTopic" value="addblock" />
    
        <h1 id="pagetitle" class="withbuttons">Title</h1>
        <div class="actionbuttons clearfix"></div>

        <fieldset>       

            <!--  block owner list -->
            <div id="divBlockOwner" class="ui-field-contain">
                <label id="lblBlockOwner" for="selBlockOwner">Block Owner:</label>
                <div class="fieldContainer ownerSelect">
                    <select name="selBlockOwner" id="selBlockOwner" title="" data-mini="true">
                    </select>
                    <div id="divBlockOwnerDisplay" class="textByLabel"></div>
                    <div class="editableSection">
                        <div id="ownerEditButton" class="editButton"><a href="#" data-ajax="false" class="ui-btn ui-corner-all ui-icon-edit ui-btn-icon-notext noborder">Edit Fields</a></div>
                        <span id="vwName" class="viewField"></span>
                        <span id="vwPhone" class="viewField"></span>
                        <span id="vwEmail" class="viewField"></span>

                    </div>
                </div>
                <div class="editor">
                    <input type="hidden" id="hidUserID" name="hidUserID" value="" />
                    <div id="divName" class="ui-field-contain username">
                        <label id="lblName" for="txtName" class="required">Name:</label>
                        <input type="text" name="txtName" id="txtName" placeholder="" title="Contact Name" class="highlight" data-mini="true" />
                    </div>
                    <div class="ui-field-contain email">
                        <label id="lblEmail" for="txtEmail" class="required">E-mail(s):</label>
                        <input type="text" name="txtEmail" id="txtEmail" placeholder="" title="Contact E-mail" class="highlight" data-mini="true" />
                    </div>
                    <div class="ui-field-contain telephone">
                        <label id="lblPhone" for="txtPhone" class="">Telephone:</label>
                        <input type="text" name="txtPhone" id="txtPhone" placeholder="___-___-____" title="Contact Telephone" class="highlight" data-mini="true" />
                    </div>
                    <input id="ownerEditSave" type="button" data-inline="true" data-mini="true" value="Close and Save" />
                    <input id="ownerEditCancel" type="button" data-inline="true" data-mini="true" value="Cancel" />
                </div>
            </div>


            <!--Basic Info-->
            <div id="divBlockTitle" class="ui-field-contain">
                <label id="lblBlockTitle" for="txtBlockTitle" class="required">Block Title:</label>
                <input type="text" name="txtBlockTitle" id="txtBlockTitle" placeholder="" title="Block Title" class="highlight" data-mini="true" />
            </div>
            <div id="divDescription" class="ui-field-contain">
                <label id="lblDescription" for="txtDescription" class="">Description:</label>
                <textarea name="txtDescription" id="txtDescription" placeholder="" title="Description" class="highlight" data-mini="true"></textarea>
            </div>
            <div id="divLocation" class="ui-field-contain">
                <label id="lblLocation" for="txtLocation" class="required">Location:</label>
                <input type="text" name="txtLocation" id="txtLocation" placeholder="" title="Location" class="highlight" data-mini="true" />
            </div>


            <!--Dates/Times-->
        	<hr class="smalldivider">
            <input type="hidden" id="fld" />
            <input type="hidden" id="fldTitle" />
            <?php include('_dateselect.php'); ?>

            <div id="dateSection">
                <h2>Date and Time</h2>
                <div id="divRepeats" class="ui-field-contain">
                    <fieldset data-role="controlgroup" id="cgRepeats">
                        <legend>Repeats?:</legend>
                        <input type="checkbox" name="chkRepeats" id="chkRepeats" title="" data-mini="true" />
                        <label for="chkRepeats">Repeats </label>
                        <div id="repeatExample" class="alignedText spacedText">(e.g. every week)</div>
                        <div id="repeatOptions" class="alignedText spacedText">
                            <div class="alignedText">every</div>
                            <div id="repeatEveryValue" class="editableField alignedText"></div>
                            <div id="repeatEveryList"></div>
                        </div>
                    </fieldset>
                </div>

                <div id="seriesDates" class="dateTimeRow">
                    <legend class="requiredstyle">Starts:</legend>
                    <div id="divStartDateSeries" class="ui-field-contain datebox">
                        <label for="txtStartDateSeries" class="required ui-hidden-accessible">Starts:</label>
                        <input type="text" name="txtStartDateSeries" id="txtStartDateSeries" placeholder="__/__/____" data-mini="true" />
                    </div>
                    <div id="divEndDateSeries" class="ui-field-contain datebox withlabel">
                        <label for="txtEndDateSeries" class="required">Ends:</label>
                        <input type="text" name="txtEndDateSeries" id="txtEndDateSeries" title="Series End Date" placeholder="__/__/____" data-mini="true" />
                    </div>
                </div>

                <div id="divFirstPeriod" class="periodsection"></div>
                <div id="divPeriods" class="recurfield"></div>
                <div id="divPeriodsBottom" class="recurfield">
                    <div id="btnAddPeriod">
                        <div class="ui-icon-plus ui-btn ui-btn-inline ui-btn-icon-notext"></div>
                        <div class="icontext">Add another time period</div>
                    </div>
                    <hr class="dashed short clearMe" />
                </div>

                <div id="divDayTypes" class="ui-field-contain recurfield">
                    <fieldset data-role="controlgroup" id="cgDayTypes">
                        <legend>Day Type(s):</legend>
                    </fieldset>
                </div>

            </div>



        	<hr class="smalldivider">
        	<h2>Slots</h2>

           	<div id="divDivideInto" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgDivideInto" class="horizontalradio">
                <legend>Divide into slots?:</legend>
                    <input type="radio" name="radDivideInto" id="radMultipleSlots" value="multipleslots" class="divintoradio" data-mini="true" />
                    <label id="lblMultipleSlots" for="radMultipleSlots">Yes</label>
                    <input type="radio" name="radDivideInto" id="radSingleSlots" value="singleslots" class="divintoradio" data-mini="true" />
                    <label id="lblSingleSlots" for="radSingleSlots">No</label>
                </fieldset>
            </div>

           	<div id="divSlotSize" class="ui-field-contain selectboxsmall">
                <label id="lblSlotSize" for="selSlotSize" class="required"><span class="appNameUpper"><?php echo ucfirst(WaseUtil::getParm('APPOINTMENT'));?></span> Slot Size:</label>
               	<select name="selSlotSize" id="selSlotSize" title="" data-mini="true">
                	<option value="0">(choose times for values)</option>
                </select><div class="lineupdiv lineuptextright">minutes </div>
            </div>
            
           <div id="divMaxApptsPerSlot" class="ui-field-contain smallintbox">
                <label id="lblMaxApptsPerSlot" for="txtMaxApptsPerSlot" class="required">Max Per Slot:</label>
               	<input type="text" data-role="spinbox" name="txtMaxApptsPerSlot" id="txtMaxApptsPerSlot" title="" value="" class="ui-btn-corner-all" data-mini="true" /><div class="lineupdiv lineuptextright">(0 means no limit)</div>
           </div>

           	<div id="divMaxApptsPerPerson" class="ui-field-contain smallintbox">
                <label id="lblMaxApptsPerPerson" for="txtMaxApptsPerPerson" class="required">Max Per Person:</label>
               	<input type="text" data-role="spinbox" name="txtMaxApptsPerPerson" id="txtMaxApptsPerPerson" pattern="[0-9]*" title="" value="" class="ui-btn-corner-all" data-mini="true" /><div class="lineupdiv lineuptextright">(0 means no limit)</div>
            </div>

           	<div id="divRequirePurpose" class="ui-field-contain smallcheckbox">
                <fieldset data-role="controlgroup" id="cgRequirePurpose">
            	<legend>Require Purpose?:</legend>
                <input type="checkbox" name="chkRequirePurpose" id="chkRequirePurpose" title="" data-mini="true" />
                <label id="lblRequirePurpose" for="chkRequirePurpose">Required</label>
                </fieldset>
          	</div>

        	<hr class="smalldivider">

			<!-- Block URL -->
            <div id="divURLs" class="urlarea"></div>
			
            <div id="divLockBlock" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgLockBlock">
                <legend>Is the Block Available?:</legend>
                <input type="checkbox" name="chkAvailable" id="chkAvailable" title="Available?" data-mini="true" />
                <label id="lblAvailable" for="chkAvailable" class="clickable"><a href="#" data-ajax="false" id="imgLockBlock" class="ui-btn ui-corner-all ui-icon-available ui-last-child ui-btn-icon-notext noborder">Available Block</a>&nbsp;<span id="spLockBlock" class="icontext">Available: <span class="appsName"><?php echo WaseUtil::getParm('APPOINTMENTS');?></span> can be made (click to lock)</span></label>
                </fieldset>
                <input type="hidden" id="hidAvailable" name="hidAvailable" value="true" data-mini="true" />
            </div>

    	</fieldset>
    
    
            <hr>
            <div class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Notifications and Reminders</h3>
                <div class="">      
                <div class="instructions">Select your (block owner) preferences for receiving <span class="appName"><?php echo WaseUtil::getParm('APPOINTMENT');?></span> notifications (when made) and reminders.</div>
                <fieldset>
              		<div id="divNotifyAndRemind" class="ui-field-contain">
               			<fieldset data-role="controlgroup" id="cgNotifyAndRemind">
                            <legend></legend>
                            <label id="lblNotify" for="chkNotify">Notify?</label>
                            <input type="checkbox" name="chkNotify" id="chkNotify" checked="checked" title="Notify" data-mini="true" />
                            <label id="lblRemind" for="chkRemind">Remind?</label>
                            <input type="checkbox" name="chkRemind" id="chkRemind" checked="checked" title="Remind" data-mini="true" />
                        </fieldset>
                    </div>                
                    <div id="divApptText" class="ui-field-contain">
                        <label id="lblApptText" for="txtApptText" class="">Text for E-mail:</label>
                        <textarea name="txtApptText" id="txtApptText" placeholder="Text to be included in e-mail notifications and reminders" title="Email Text" class="highlight" data-mini="true"></textarea>
                    </div>
                </fieldset>
                </div>
            </div>


            <hr>
            <div class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Deadlines</h3>
                <div class="deadlineSection">
                <div class="instructions">Limit when <span class="appsName"><?php echo WaseUtil::getParm('APPOINTMENTS');?></span> can be made or canceled.  All are optional.</div>
                <fieldset>
            	<?php include('_durationselect.php'); ?>

                <div id="divOpening" class="ui-field-contain textonlyfield">
                    <label><span class="appsNameUpper"><?php echo ucfirst(WaseUtil::getParm('APPOINTMENTs'));?></span> cannot be made until:</label>
                    <div id="txtOpening" class="durationText"></div><div id="txtOpeningMsg" class="deadlineMsg">prior to start of block</div><div id="btnEditOpening" class="buttonText" title="Edit Opening Deadline">Edit</div>
               	</div>

                <div id="divDeadline" class="ui-field-contain textonlyfield">
                    <label><span class="appsNameUpper"><?php echo ucfirst(WaseUtil::getParm('APPOINTMENTs'));?></span> must be made by:</label>
                    <div id="txtDeadline" class="durationText"></div><div id="txtDeadlineMsg" class="deadlineMsg">prior to start of block</div><div id="btnEditDeadline" class="buttonText">Edit</div>
                </div>

                <div id="divCanDeadline" class="ui-field-contain textonlyfield">
                    <label><span class="appsNameUpper"><?php echo ucfirst(WaseUtil::getParm('APPOINTMENTs'));?></span> must be canceled by:</label>
                    <div id="txtCanDeadline" class="durationText"></div><div id="txtCanDeadlineMsg" class="deadlineMsg">prior to start of slot</div><div id="btnEditCanDeadline" class="buttonText">Edit</div>
                </div>
                
             	</fieldset>
            	</div>
            </div>
            

            <hr>
            <div class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Access Restrictions</h3>
				<div id="blockAR" class="arContent">
				<fieldset> 
             	</fieldset>
            	</div>
            </div>
           
   			<!-- Labels -->
            <hr>
            <div id="divCollapseLabels" class="group" data-role="collapsible" data-collapsed="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right">
                <h3>Labels</h3>
            <div id="divLabels"></div>
            </div>
                    
            
            <div class="submitbuttons bottombuttons">
                <div id="divSubmit" class="ui-field-contain">
                	<input class="btnCancelSubmit" type="button" value="Cancel" data-inline="true" data-mini="true" />    
                    <input class="btnSubmit" type="button" value="Save" data-inline="true" data-mini="true" />
                </div>
                <div class="heightfix"></div>
            </div>

			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="block" />
            
        </form>        
        
	</div><!-- /content -->
	<?php include('_savewhatblocks.php');?>
	<?php include('_verifyunavailable.php');?>
	
	<?php include('_footer.php'); ?>
    
	<script type="text/javascript">
	    function canChangeSeries() {
	    	return !(!moment(gBlock.get('startdatetime')).isSame(moment(gOrigBlock.get('startdatetime'))) ||
                !moment(gBlock.get('enddatetime')).isSame(moment(gOrigBlock.get('enddatetime'))));

	    }
	    function doSaveBlock() {
			if (isNullOrBlank(blockview.blockid)) {
				//add block
				disableButton(".btnSubmit");
				blockController.addBlock(gBlock);
			} else {
				//edit block
				//check savewhat if series
				if ($("#chkRepeats").is(":checked")) {
					//if any date/time/slot field changed, do NOT show the popup
					if (canChangeSeries()) {
					    $('#popupSaveWhat').popup('open');
					} else {
						disableButton(".btnSubmit");
					    blockController.editBlock(gBlock,'instance');
					}
				}
			    else {
					disableButton(".btnSubmit");
			        blockController.editBlock(gBlock,'instance');
			    }
			}
	    }
		function doSubmit() {
			if (isValidForm("blockinfo")) {
				if (blockview.saveBlockData()) {
				    doSaveBlock();
				}
			}
			return false;
		}
		
        function goToViewCal() {
			var sdt = moment($("#txtStartDate_0").val(),fmtDate);
		    goToPage('viewcalendar',{calid: calid, sdt: getURLDateTime(sdt.toDate())});
        }
		
    </script>                                                           

</div><!-- /page -->

</body>
</html>
