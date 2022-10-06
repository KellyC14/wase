<?php 
$notdoauth = true;
 ?>
<?php
include "_copyright.php";

$pagenav = 'help'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>
 
<script type="text/javascript">	
   	$(document).ready(function() {
   	    doOnReady();

		$("#lnkmobilenav").hide();
		$("#nav").hide();

		//update browser tab title to include 'help'
		var txt = $('title').text();
		$('title').text(txt+' Help');
		
   	    //show the topic
		var tpc = $.urlParam("helptopic") ? $.urlParam("helptopic") : "start";
		showTopic(tpc);
	});
	
	function showTopic(inTopic) {
		//hide all of the help topics, then open the selected one
		$(".helpsection").hide();
		
		//show the topic
		$("#"+inTopic).show();
		$("#divHelp").scrollTop(0);
	}
	
</script>
</head>

<body onUnload="">

<div data-role="page" id="help_main">
	<?php include('_mainmenu.php'); ?>
	<div class="ui-content" role="main">	
        <h1 id="pagetitle">Help</h1>
	    <?php include('help_inner.php');?>
    </div>
</div>
</body>