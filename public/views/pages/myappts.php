<?php 
include "_copyright.php";

$pagenav = 'myappts'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<link type="text/css" rel="stylesheet" href="../css/calendar.css" media="all" />
<link type="text/css" rel="stylesheet" href="../css/print.css" media="print" />

<!-- spinbox widget: based on jtsage on github.  HEAVILY modified. -->
<script type="text/javascript" src="../../libraries/jqm-spinbox.js"></script>

<script type="text/javascript" language="javascript" src="../../libraries/classes/calendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/appointment.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/waitlistentry.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/timeselector.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/MyApptsViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/ApptController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/WaitlistController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="myapptsview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">	
	var prefix = "myappts_filter_";
	var filterNames = ['startdate','enddate','starttime','endtime','apptwithorfor','apptby'];
	var varNames = [];
	_.each(filterNames, function(n) {
		varNames.push(prefix+n);
	});

	$(document).ready(function() {
      	doOnReady();
		controller.getParameters(["NETID","WAITLIST","ALERTMSG"]);
		myapptsview.setPageVars();
		showLoadingMsg(true);
		myapptsViewController.loadMyApptsViewData();
		
		popupCalInit(myapptsview);
		popupCConfirmInit("myapptsview");

		//push breadcrumbs to session
		controller.getAndClearSessionVar(["infmsg","apptid"]);
		controller.getSessionVar(varNames);
		controller.setSessionVar([["breadcrumbs",[["My " + capitalize(g_ApptsText),"myappts.php",""]]]]);
	}); 
</script>                                                                                                                             
</head>

<body onUnload="">

<div data-role="page" id="myappts">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
    	<form id="listappts" method="post" action="exportappts.php" data-ajax="false">
            <input type="hidden" id="helpTopic" value="showmyappts" />
    	
        <div class="actionbuttons">
            <a href="#" class="" onClick="exporttocsv();" data-ajax="false" title="Export <?php echo WaseUtil::getParm('APPOINTMENTS')?> to CSV format">Export to CSV</a>
            <a href="#" class="lnkPrint ui-btn ui-corner-all ui-icon-print ui-btn-icon-notext noborder" onClick="print();" data-ajax="false" title="Print <?php echo ucfirst(WaseUtil::getParm("APPOINTMENTS"));?>">Print <?php echo ucfirst(WaseUtil::getParm("APPOINTMENTS"));?></a>
        </div>
		<h1 id="pagetitle" class="clear">Title</h1>      
        
            <input type="hidden" id="fld" />
            <input type="hidden" id="fldTitle" />
            <?php include('_dateselect.php'); ?>
                       
            <div id="divApptFilter" data-role="collapsible" data-collapsed="false" data-collapsed-icon="carat-r" data-expanded-icon="carat-d">
            	<h3>Filtering Options</h3>
                    <fieldset>
                    <div class="filtercol1">
                        <div id="divStartDate" class="ui-field-contain filtergroup datetimefilter">
                        	<label id="lblStartDate" for="txtStartDate" class="filterlabel">Start:</label>
                            <input type="text" id="txtStartDate" name="txtStartDate" placeholder="__/__/____" class="setfilter filterfield" />
                            <a href="#" id="txtStartDateClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder fieldicon" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                        <div id="divEndDate" class="ui-field-contain filtergroup datetimefilter">
                        	<label id="lblEndDate" for="txtEndDate" class="filterlabel">End:</label>
                            <input type="text" id="txtEndDate" name="txtEndDate" placeholder="__/__/____" class="setfilter filterfield" />
                            <a href="#" id="txtEndDateClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                    </div>
                    <div class="filtercol2">
                        <div id="divStartTime" class="ui-field-contain filtergroup datetimefilter">
                        	<label id="lblStartTime" for="txtStartTime" class="ui-hidden-accessible filterlabel">Start Time:</label>
                            <!--<select id="txtStartTime" name="txtStartTime" class="setfilter filterfield"></select>-->
                            <input type="text" id="txtStartTime" name="txtStartTime" placeholder="hh:mm AM" class="setfilter filterfield" />
                            <a href="#" id="txtStartTimeClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                        <div id="divEndTime" class="ui-field-contain filtergroup datetimefilter">
                        	<label id="lblEndTime" for="txtEndTime" class="ui-hidden-accessible filterlabel">End Time:</label>
                            <input type="text" id="txtEndTime" name="txtEndTime" placeholder="hh:mm AM" class="setfilter filterfield" />
                            <a href="#" id="txtEndTimeClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                   </div>
                    <div class="filtercol3">
                       <div id="divApptName" class="ui-field-contain filtergroup">
                        	<label id="lblApptName" for="txtApptName" class="filterlabel">Appt With/For:</label>
                            <input type="text" id="txtApptName" name="txtApptName" placeholder="" class="setfilter filterfield" />
                            <a href="#" id="txtApptNameClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                         <div class="ui-field-contain filtergroup">
                        	<label id="lblApptMadeBy" for="txtApptMadeBy" class="filterlabel">Made By:</label>
                            <input type="text" id="txtApptMadeBy" name="txtApptMadeBy" placeholder="" class="setfilter filterfield" />
                            <a href="#" id="txtApptMadeByClear" class="clrbutton ui-btn ui-corner-all ui-icon-delete ui-btn-icon-notext noborder" title="Remove Filter"></a>
                            <div class="heightfix"></div>
                        </div>
                    </div>
                    <div class="filtergobutton">
                    	<a class="ui-btn ui-btn-inline goButton" href="#" onClick="getfilteredappts();" title="Filter <?php echo ucfirst(WaseUtil::getParm("APPOINTMENTS"));?>">Go</a>
                    </div>
                	</fieldset>
                <div class="heightfix"></div>
            </div>
            
            <div id="divFilters">
               	<div id="divFilterList"></div>
                <div id="divFilterDesc"></div>
            </div>
 			<div class="loadingmsg">...Loading...</div>

            <div id="divMyApptsContent" class="clear" data-role="collapsible" data-collapsed="false" data-collapsed-icon="carat-r" data-expanded-icon="carat-d">
            	<h3>My <?php echo WaseUtil::getParm('APPOINTMENTS')?> <span class="ui-li-count"></span></h3>
			</div>

			<a id="waitlist"></a>
            <div id="divWaitlist" data-role="collapsible" data-collapsed="false" data-collapsed-icon="carat-r" data-expanded-icon="carat-d">
            	<h3>My Wait List Entries <span class="ui-li-count"></span></h3>
			</div>
			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="entry" />
                
        </form>
	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
    <script type="text/javascript"> 
	function exporttocsv() {
		$("#listappts").submit();	
	}
	
	function getfilteredappts() {
		showLoadingMsg(true);
	    var filters = {
            startdate: $('#txtStartDate').val(),
            enddate: $('#txtEndDate').val(),
            starttime: $('#txtStartTime').val(), 
            endtime: $('#txtEndTime').val(), 
            calendarid: '',
            apptwithorfor: $("#txtApptName").val(),
            apptby: $("#txtApptMadeBy") ? $("#txtApptMadeBy").val() : ''
        };
		myapptsViewController.getAppointments(filters);
		
		//set preference for large/small calendar
		var arr = [];
		_.each(filterNames, function(n) {
			arr.push([prefix+n,filters[n]]);
		});
		controller.setSessionVar(arr);
		
		return false;
	}
		
	$("#listappts").submit(function(e) {
	});
    </script>                                                               

</div><!-- /page -->

</body>
</html>
