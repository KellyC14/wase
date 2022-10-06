<?php
include "_copyright.php";

$pagenav = 'makeappt'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<link type="text/css" rel="stylesheet" href="../css/calendar.css" media="all" />

<!-- spinbox widget: based on jtsage on github.  HEAVILY modified. -->
<script type="text/javascript" src="../../libraries/jqm-spinbox.js"></script>

<script type="text/javascript" language="javascript" src="../../libraries/classes/calendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/block.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/appointment.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/course.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/waitlistentry.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/MakeApptViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/WaitlistController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="makeapptview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
   	$(document).ready(function() {
      	doOnReady();
		controller.getParameters(["NETID","COURSECALS","WAITLIST","ALERTMSG"]);
		makeapptViewController.checkUserStatus();
		makeapptview.setPageVars($.urlParam("calid"),$.urlParam("blockid"),$.urlParam("sdt"));

		//check infmsg
		controller.getAndClearSessionVar(["infmsg","apptid"]);
		
		popupCalInit(makeapptview);
		popupCConfirmInit("makeapptview");
		popupNotifyOverlapInit();
		
		var pathArr = window.location.pathname.split("/");
		var lnk = encodeURIComponent(pathArr[pathArr.length-1] + window.location.search);
		//push breadcrumbs to session
		controller.setSessionVar([["breadcrumbs",[["Make " + capitalize(g_ApptText),lnk,""]]]]);
	}); 
</script>                                                               
</head>

<body onUnload="">

<div data-role="page" id="makeappt">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
		<h1 id="pagetitle" class="clear">Title</h1>      
    	<form id="search" method="post" action="#" data-ajax="false">
            <input type="hidden" id="helpTopic" value="quickmake" />
    	
            <div class="instructions"></div>
            <div id="divSearch" class="ui-field-contain">
                <label for="txtSearch" class="ui-hidden-accessible">Search Text:</label>
                <input type="search" name="txtSearch" id="txtSearch" value="" placeholder="" data-mini="true" /><a id="goSearch" href="#" onClick="makeapptview.doSearch();" class="ui-btn ui-btn-inline">Go</a>
            </div>
            <div class="coursecalsearch"></div>
            
            <div id="divResults">   
            	<hr>
            </div>

            <input type="hidden" id="fld" />
            <input type="hidden" id="fldTitle" />
            <?php include('_dateselect.php'); ?>
			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="makeappt" />
			<?php include('_quickapptfull.php');?>
			<?php include('_notifyapptoverlap.php');?>
       		<?php include('_txtemailselect.php');?>
            
        </form>

	</div><!-- /content -->

	<?php include('_footer.php'); ?>

	<script type="text/javascript">
	function openMakeApptTxtMsg() {
		var val = $("#wlinfo #divWLTextEmail #divTextEmail").text();
		var callback = _.bind(makeapptViewController.isValidEmail,makeapptViewController);
	    $("#editTextMsgEmail").popup('open');
	    initTextPopup(val,callback,'#makeappt #editTextMsgEmail-popup');
    }
    
	$("#linkCourseCals").click(function(e) {
		/*MakeApptView.clearCalendars();
		makeapptview.showLoadingMessage();
		controller.getCourseCals();
		return false;*/
		//console.log("uh oh2");
	});
    </script>                                                               

</div><!-- /page -->

</body>
</html>
