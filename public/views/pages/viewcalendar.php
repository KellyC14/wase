<?php 
include "_copyright.php";

$pagenav = 'calendars'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<link type="text/css" rel="stylesheet" href="../css/calendar.css" media="all" />
<link type="text/css" rel="stylesheet" href="../css/print.css" media="print" />

<!-- for Blob support -->
<script type="text/javascript" src="../../libraries/FileSaver.min.js"></script>

<script type="text/javascript" language="javascript" src="../../libraries/classes/calendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/wasecalendar.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/block.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/appointment.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="../../controllers/ViewCalViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/BlockController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/ApptController.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript" language="javascript" src="viewcalendarview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">	

function showAll(isShow) {
    $(".actionbuttons").toggle(isShow);
    $("#divControlArea").toggle(isShow);
}

	//after the page has been created in the DOM
	$(document).on('pagecreate', '[data-role="page"]', function(){
		showAll(false);
		
		//set up action buttons
		var str = '';
			str += '<div data-role="controlgroup" data-type="horizontal" data-mini="true" class="localnav iconsonly">';
			str += '<a id="btnSettings" href="#" class="ui-btn ui-icon-gear ui-btn-b ui-btn-icon-left" data-ajax="false" title="Calendar Settings"><div class="text">Calendar<br>Settings</div></a>';
			str += '<a id="btnAddBlock" href="#" class="ui-btn ui-icon-plus ui-btn-b ui-btn-icon-left" data-ajax="false" title="Add Block"><div class="text">Add<br>Block</div></a>';
			str += '</div>';
			$("#divActions").append(str);
			
			$("#btnSettings").on("vmousedown",viewcalview.goToSettings);
			$("#btnAddBlock").on("click",viewcalview.goToAddBlock);

	});
	
	var sdt = null;
	
   	$(document).ready(function() {
      	doOnReady();
		if ($.urlParam("sdt")) 
			sdt = getDateTimeFromURL($.urlParam("sdt"));

		viewcalview.setPageVars(sdt);
		
		if (gSelectedCalIDs === "") {
			setAlertMsg("Please select a calendar to display");
			return false;
		}

		popupCConfirmInit("viewcalview");

		controller.getSessionVar(["breadcrumbs"]);
	}); 	
</script>                                                               
</head>

<body onUnload="">

<div data-role="page" id="viewcalendar">

	<?php include('_mainmenu.php'); ?>

	<div class="ui-content" role="main">	
    	<form id="viewcal" method="post" action="#" data-ajax="false">
            <input type="hidden" id="helpTopic" value="doviewcal" />
    	    <div id="divActions" class="actionbuttons"></div>
            <h1 id="pagetitle"></h1>
                        
            <div id="divViewCalContent">
                <div id="divControlArea">
                    <div id="divDateScroll"></div>
                    <div id="divCalSelect" data-role="collapsible" data-collapsed="false" data-collapsed-icon="carat-r" data-expanded-icon="carat-d">
                    	<h3>My Calendars</h3>
                	</div>
                </div>
				<a id="lnkBlockArea"></a>
                <div class="loadingmsg">...Loading...</div>
                <div class="actionbuttons"><a href="#" class="lnkPrint ui-btn ui-corner-all ui-icon-print ui-btn-icon-notext noborder" onClick="print();" data-ajax="false" title="Print Blocks">Print</a>
            </div>
				<div id="divBlocks"></div>
			</div>                

			<?php include('_syncconfirm.php');?>
			<?php include('_cancelconfirm.php');?>
            <input type="hidden" id="ccObject" value="block" />
                        
        </form>
	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
    <script type="text/javascript"> 

    </script>                                                               

</div><!-- /page -->

</body>
</html>
