<?php 
include "_copyright.php";

$pagenav = 'calendars'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<script type="text/javascript" language="javascript" src="../../libraries/classes/notifyremind.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/accessrestrictions.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/CalendarsViewController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="calendarsview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">	
	$(document).on('pagecreate', '[data-role="page"]', function(){
		//set up action buttons
		var str = '';
			str += '<div data-role="controlgroup" data-type="horizontal" data-mini="true" class="localnav iconsonly">';
    		str += '<a id="btnView" href="#" class="ui-btn ui-icon-carat-r ui-btn-b ui-btn-icon-left" data-ajax="false" title="View Calendar"><div class="text">View<br>Calendar</div></a>';
			str += '<a id="btnSettings" href="#" class="ui-btn ui-icon-gear ui-btn-b ui-btn-icon-left" data-ajax="false" title="Calendar Settings"><div class="text">Calendar<br>Settings</div></a>';
			str += '<a href="calendarconfig.php" class="ui-btn ui-icon-plus ui-btn-b ui-btn-icon-left" data-ajax="false" title="New Calendar"><div class="text">New<br>Calendar</div></a>';
			str += '</div>';
			$(".actionbuttons").append(str);
	});
	
   	$(document).ready(function() {
      	doOnReady();
        calendarsview.setPageVars();
		showLoadingMsg(true);
		calendarsViewController.checkUserStatus();
		calendarsViewController.loadCalendarsViewData();

		popupApplyInit();
		popupCConfirmInit("calendarsview");

		//push breadcrumbs to session
		controller.getAndClearSessionVar(["infmsg"]);
		controller.setSessionVar([["breadcrumbs",[["Calendars","calendars.php",""]]]]);

   		$('.loader').hide();
	});	
</script>                                                               
</head>

<body onUnload="">

<div data-role="page" id="calendars">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
    	<form id="frmCalendars" method="post" action="#" data-ajax="false">
            <input type="hidden" id="helpTopic" value="calendarinfo" />
            
    	    <div class="actionbuttons"></div>
            <h1 id="pagetitle">Title</h1>    
            <div class="loader ui-loader"><span class='ui-icon ui-icon-loading'></span></div>
            <div id="divCalLists">
            	<div class="loadingmsg">...Loading...</div>
			</div>
			
			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="" />
                        	
        </form>
        
		<?php include('_applycalendar.php');?>
	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
    <script type="text/javascript"> 
    </script>                                                               

</div><!-- /page -->

</body>
</html>
