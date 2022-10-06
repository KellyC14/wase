<script type="text/javascript" language="javascript" src="../../controllers/PrefsViewController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="preferencesview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
	function initPrefPopup() {
		controller.getParameters(["NETID","INSNAME"]);
		preferencesview.setPageVars();
		prefsViewController.loadPrefsViewData();
	}
</script>                                                               

<div data-role="popup" id="prefs" data-dismissible="true">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#prefs").popup("close");' title="Close Window">Close</a></div>
    <h1 id="prefspagetitle">Preferences</h1>    

    <?php include('preferences_inner.php');?>
    
</div><!-- /page -->
