<?php
/*
 * Code included on every page
 */
?>
<?php
/* Update $versionString, which is part of every JS file declaration, to force browser cache refresh */
$versionString = '306';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" class="ui-mobile">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com/;">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title><?php echo WaseUtil::getParm('SYSID');?></title>

<link type="text/css" rel="stylesheet" href="../../components/require.css" media="all" />
<!-- PT Sans font -->
<link href='https://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic' rel='stylesheet' type='text/css' />

<link type="text/css" rel="stylesheet" href="../css/jquery.mobile.icons-1.4.2.min.css" media="all" />
<link type="text/css" rel="stylesheet" href="../css/wase-theme-1.4.2.css" media="all" />
<link type="text/css" rel="stylesheet" href="../css/custom.css" media="all" />

<!-- jquery-rcrumbs: download new version from github.com (cm0s/jquery-rcrumbs). -->
<link type="text/css" rel="stylesheet" href="../css/jquery.rcrumbs.min.css" media="all" />

<script type="text/javascript" src="../../components/underscore/underscore-built.js"></script>
<script type="text/javascript" src="../../components/jquery/jquery-built.js"></script>


<script type="text/javascript" src="../../components/mobiscroll/mobiscroll-built.js"></script>
<script type="text/javascript" src="../../components/moment/moment-built.js"></script>

<script type="text/javascript" src="../../libraries/jquery.mobile-1.4.2.min.js"></script>

<script type="text/javascript" src="../../libraries/draggable.js"></script>

<script type="text/javascript" language="javascript">
$(document).bind("mobileinit", function(){
  //apply overrides here
  $.mobile.pushStateEnabled = false;
});
</script>

<!-- jquery-rcrumbs: download new version from github.com (cm0s/jquery-rcrumbs). -->
<script type="text/javascript" src="../../libraries/jquery.rcrumbs.min.js"></script>

<script type="text/javascript" language="javascript">
var g_sessionid = "<?php echo session_id();?>";
var g_loggedInUserID = "<?php if ($_SESSION['authtype'] != 'guest') echo $_SESSION['authid'];?>";
var g_GuestEmail = "<?php if ($_SESSION['authtype'] == 'guest') echo $_SESSION['authid'];?>";
var gParms = {};
var gBreadCrumbs = new Array();
var g_ApptText = "<?php echo WaseUtil::getParm('APPOINTMENT')?>";
var g_ApptsText = "<?php echo WaseUtil::getParm('APPOINTMENTS')?>";

var g_schemaRoot = "http://<?php echo $_SERVER['SERVER_NAME']?>/controllers/ajax/";
</script>
<script type="text/javascript" language="javascript" src="../../libraries/global.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/mediacheck.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/WASEObject.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/BaseView.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/message.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/BaseController.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/controller.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../libraries/classes/user.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript" src="../../controllers/ajax/ajax.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript" language="javascript">
    /* called on $(document).ready() */
    function doOnReady() {
        if (typeof gMessage !== 'undefined') gMessage.removeMessage();
    }
	window.onpageshow = function(event) {
		if (event.persisted) {
			window.location.reload();
		}
	};
	window.onunload = function(){}; //prevent backward-forward cache
	function setAlertMsg(inMsg) {
	    // get unescaped XML char so use two ifs
		if (inMsg !== '') {
		    if($("#divMessage").is(":hidden")) {
                var err = new Error({errorcode: "", errortext: inMsg});
                Error.displayErrors(new Array(err));
            }
			// var err = new Error({errorcode: "", errortext: inMsg});
			// Error.displayErrors(new Array(err));
		}
	}
</script>
