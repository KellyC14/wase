<?php 
$notdoauth = true;
 ?>

<script type="text/javascript">	
	function showTopic(inTopic) {
		//hide all of the help topics, then open the selected one
		$(".helpsection").hide();
		
		//show the topic
		$("#"+inTopic).show();
		$("#divHelp").scrollTop(0);
	}
</script>
<div data-role="popup" id="help" data-dismissible="true">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#help").popup("close");' title="Close Window">Close</a></div>
    <h1 id="helppagetitle">Help</h1>    

    <?php include('help_inner.php');?>
    
</div><!-- /page -->
