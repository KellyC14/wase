<?php
include "_copyright.php";

$pagenav = 'preferences'; /*for setting the "selected" page navigation */
include "_header.php";
include "_includes.php"; ?>

<script type="text/javascript" language="javascript" src="../../controllers/PrefsViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="preferencesview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
   	$(document).ready(function() {
      	doOnReady();

		$("#lnkmobilenav").hide();
		$("#nav").hide();

		controller.getParameters(["NETID","INSNAME"]);
		preferencesview.setPageVars();
		prefsViewController.loadPrefsViewData();

		$("#btnAuthGoogle").on("click",function() {
			document.location.href = getURLPath() + "/authorizegoogle.php?init=true";
		});
	});
</script>                                                               
</head>

<body onUnload="">

<div data-role="page" id="preferences_main">
	<?php include('_mainmenu.php'); ?>
	<div class="ui-content" role="main">	
        <h1 id="pagetitle">Preferences</h1>
	    <?php include('preferences_inner.php');?>
    </div>
</div>
</body>