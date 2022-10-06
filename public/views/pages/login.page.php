<?php
include "_copyright.php";

/*

        This page allows appointment makers to log in.
 
*/


/* We no longer use this autoloader. */
function __oldautoload($class) {
	/* Set path to WaseParms and WaseLocal */
	if ($class == 'WaseParms')
		$parmspath = '../../../config/';
	else
		$parmspath = '../../../models/classes/';
		
	/* Now load the class */ 
	if ($class != 'WaseLocal')
		require_once($parmspath.$class.'.php');
	else
		@include_once($parmspath.$class.'.php');
	
}
 

/* Include the Composer autoloader. */
require_once('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Start session support */
if (session_status() == PHP_SESSION_NONE) session_start();

/* Init error/inform message */
$errmsg = ''; $infmsg = ''; 


// If we are authenticated, make sure we are still accessing the same system -- if not, force re-authentication
if (isset($_SESSION['authenticated']) && isset($_SESSION['INSTITUTION']) && isset($_SERVER['INSTITUTION']) && ($_SESSION['INSTITUTION'] != $_SERVER['INSTITUTION'])) {
    // For now, just die
    die('You cannot switch WASE systems without logging out of the current system.');
}

/* If using CAS, initialize */
if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS')) {
    phpCAS::setDebug(FALSE);
    //phpCAS::setdebug('/tmp/casdebug');
    // phpCAS::setVerbose(true);
 
    $cashost = WaseUtil::getParm('CAShost');
	phpCAS::client(WaseUtil::getParm('CASversion'),$cashost,WaseUtil::getParm('CASport'),WaseUtil::getParm('CASuri'));
	phpCAS::setNoCasServerValidation();

	// If the CAS server hstname is a URL, reset it
    if (substr($cashost,0,4) == 'http')
        phpCAS::setServerLoginURL($cashost);
		
	
	/* If we are here to logout of CAS, do it */
	if (@$_REQUEST['doCASlogout']) {
		phpCAS::logout();
		header('Location:' . WaseUtil::getParm('CASlogout'));
		exit();		
	}
	
	
	/* If already authenticated, go to the right place */
	if (phpCAS::isAuthenticated()  && !@$_SESSION['caslogout']) {
	     
		$userid = phpCAS::getUser();
		 
		$_SESSION['CAS']['userid'] = $userid;
		getCAS();
		doLogin($userid);
	}	
}
if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHSHIB'))	{
    // The isAuthenticated function and SimpleSAML_Auth_Simple potential trashes our session variables, so we need to save and restoire them across the call
    $shiblogout = $_SESSION['shiblogout'];
    $doSHIBlogout = $_SESSION["doSHIBlogout"];
    $shibuserid = $_SESSION['SHIB']['userid'];
    $redirurl = $_SESSION['redirurl'];
    //WaseMsg::dMsg('SHIB',"redirurl[$redirurl]");
    /* Build the SHIB client class */
    if (!$sp = WaseUtil::getParm('SHIBSP'))
        $sp = 'default-sp';
    //WaseMsg::dMsg('SHIB',"Trying to create new simplesaml object from $sp");
    $as = new SimpleSAML_Auth_Simple($sp);
    //WaseMsg::dMsg('SHIB',"Created new simplesaml object from $sp");

    /* If we are here to logout of SHIB, do it */
    if (@$_REQUEST['doSHIBlogout']) {
        //WaseMsg::dMsg('SHIB','Trying a SHIB logout');
        // $as->logout();
        // $location = $as->getLogoutURL("/");
        // If SHIB Logout page specified, go to it.
        $location = WaseUtil::getParm('SHIBLOGOUT');
        session_destroy();
        if ($location)
            header('Location: ' . $location);
        exit();
    }
    
    /* Point to the institutional IdP */
    // $as->login(array('saml:idp' => WaseUtil::getParm('SHIBIDP')));
    /* If already authenticated, go to the right place */
    //WaseMsg::dMsg('SHIB','Trying a SHIB isAuthenicated');

    $authenticated = $as->isAuthenticated();
    $_SESSION['shiblogout'] = $shiblogout;
    $_SESSION["doSHIBlogout"] = $doSHIBlogout;
    $_SESSION['SHIB']['userid'] = $shibuserid;
    $_SESSION['redirurl'] = $redirurl;
    if ($authenticated && !@$_SESSION['shiblogout']) {
        //WaseMsg::dMsg('SHIB','Did a SHIB isAuthenicated');
       /* Get the userid */
        //WaseMsg::dMsg('SHIB','Trying a SHIB getAttributes');
       $attributes = $as->getAttributes();
        //$attrs = print_r($attributes, true);
        //WaseMsg::dMsg('SHIB', 'Did a SHIB getAttributes: ' . $attrs);
       $userid = getShib($attributes);
       //WaseMsg::dMsg('SHIB',"SHIB userid is $userid, session auth = " . (int) $_SESSION['authenticated']);
       dologin($userid);
   }
}



/* If we are already logged in with session variables set, just go right to the relevant page. */
if (!$errmsg && @$_SESSION['authenticated'] && @$_SESSION['authtype']=='user') {
     
	/* Only one place to go */
	header('Location: makeappt.php');
	exit();
}


/* If we are using CAS, just go to the CAS login */
//if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS') && !@$_SESSION['caslogout']) {
		  /* force CAS authentication */
/*		  phpCAS::setDebug(FALSE);
		  $auth = phpCAS::forceAuthentication();
		  $userid = phpCAS::getUser();
		  doLogin($userid);
}*/


Header('Cache-Control: no-cache');
Header('Pragma: no-cache');
$my_session_shiblogout=$_SESSION['shiblogout'];
$my_session_shib_userid=$_SESSION['SHIB']['userid'];
$my_parm_shiblogout=WaseUtil::getParm('SHIBLOGOUT');
WaseMsg::dMsg('DBG','Info',"login page-my_session_shiblogout[$my_session_shiblogout]my_session_shib_userid[$my_session_shib_userid]my_parm_shiblogout[$my_parm_shiblogout]");
/* If we got here just after a CAS logout, let the user know */
if (@$_SESSION['caslogout'] && @$_SESSION['CAS']['userid']) {
    $infmsg = 'Although your current session has been terminated, you are still logged in to ' . WaseUtil::getParm('CASname') . ', meaning that you can access ' .   WaseUtil::getParm('CASname') . ' -enabled services (including this system) without having to re-enter your ' . WaseUtil::getParm('NETID') . ' and ' . WaseUtil::getParm('PASSNAME') . '. If you are finished using all ' . WaseUtil::getParm('CASname') . ' enabled services you may <a style="font-size:14px; font-weight: bold; margin:0; color:#3366CC; text-decoration:underline" href="login.page.php?doCASlogout=1" data-ajax="false">Logout of ' . WaseUtil::getParm('CASname') . '</a>, so that further access to any ' . WaseUtil::getParm('CASname') . ' enabled services will require that you re-enter your ' . WaseUtil::getParm('NETID') . ' and ' . WaseUtil::getParm('PASSNAME') . '.';
	$_SESSION['caslogout'] = false;
}

/* If we got here just after a SHIB logout, let the user know */
if (@$_SESSION['shiblogout'] && @$_SESSION['SHIB']['userid']) {
    //WaseMsg::dMsg('SHIB','Letting user know to complete SHIB logout');
    if ($shiblogout = WaseUtil::getParm('SHIBLOGOUT'))
        $infmsg = 'Although your current session has been terminated, you are still logged in to ' . WaseUtil::getParm('SHIBSYSNAME') . ', meaning that you can access ' . WaseUtil::getParm('SHIBSYSNAME') . ' -enabled services (including this system) without having to re-enter your ' . WaseUtil::getParm('NETID') . ' and ' . WaseUtil::getParm('PASSNAME') . '. If you are finished using all ' . WaseUtil::getParm('SHIBSYSNAME') . ' enabled services you may <a style="font-size:14px; font-weight: bold; margin:0; color:#3366CC; text-decoration:underline" href="login.page.php?doSHIBlogout=1" data-ajax="false">Logout of ' . WaseUtil::getParm('SHIBSYSNAME') . '</a>, so that further access to any ' . WaseUtil::getParm('SHIBSYSNAME') . ' enabled services will require that you re-enter your ' . WaseUtil::getParm('NETID') . ' and ' . WaseUtil::getParm('PASSNAME') . '.';
    $_SESSION['shiblogout'] = false;
}




/*
Handle a form submission.
*/

if (!$errmsg && @$_POST['btnCASLogIn']) {
    
	/* If we are using CAS, force authentication */
	if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS')) {
		/* force CAS authentication */
	    
		$auth = phpCAS::forceAuthentication();
	}
    elseif (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHSHIB'))	{
        /* Point to the institutional IdP */
        // $as->login(array('saml:idp' => WaseUtil::getParm('SHIBIDP')));
        /* force SHIB authentication */
        $as->requireAuth(array(
            'saml:idp' => WaseUtil::getParm('SHIBIDP'),
            // 'assertion.encryption'=>false,
            // 'nameid.encryption'=>false,
           // 'encryption.blacklisted-algorithms'=>array(
           //     'http://www.w3.org/2001/04/xmlenc#tripledes-cbc',
           //     'http://www.w3.org/2001/04/xmlenc#aes128-cbc',
           //     'http://www.w3.org/2001/04/xmlenc#aes192-cbc',
           //      'http://www.w3.org/2001/04/xmlenc#aes256-cbc',
           //     'http://www.w3.org/2001/04/xmlenc#rsa-1_5',
           //  )
        ));
        /* Get the userid and other SHIB attributes */
        $attributes = $as->getAttributes();
        // Dump if requested
        if (WaseUtil::getParm('SHIBDUMPEMAIL'))
            WaseUtil::Mailer(WaseUtil::getParm('SHIBDUMPEMAIL'), 'SHIB attributes for ' . $_SERVER['INSTITUTION'], print_r($attributes, true));
         
        $userid = getShib($attributes);   
    }
	if (!$errmsg ) {
		doLogin($userid);
	}
}
// Guest login
elseif (!$errmsg && @$_POST['btnLogInGuest']) {
     
    $userid = '';
     /* See if email entered */
    $oriemail = trim($_REQUEST['txtEmail']);
    $email = WaseUtil::slashstrip(filter_var($oriemail,FILTER_SANITIZE_EMAIL));
    if (!$oriemail)
        $errmsg = 'You must enter an email address.';
    else {
        /* Check for super-user request */
        list($userid,$secret,$junk) = explode(' ',$oriemail);
        $userid = filter_var(trim($userid), FILTER_SANITIZE_EMAIL);  $secret = trim($secret);
        if (substr($secret,0,7) == 'secret=') {
            if ((substr($secret,7) == WaseUtil::getParm('PASS')) || (($imp = WaseUtil::getParm('IMPERSONATEPASS')) && substr($secret,7) == $imp) && (!in_array($userid,explode(',',trim(WaseUtil::getParm('SUPER_USERS'))))))
                dologin($userid);
            else 
                $errmsg = 'Invalid credentials';
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Make sure email is not that of a domain user */
        if (!$errmsg) {
            if (WaseUtil::getParm('LDAPNETID')) {
                if ($netid = $directory->getNetid(WaseUtil::getParm('LDAPEMAIL'), $email))
                    $errmsg = 'Specified email is associated with a ' . WaseUtil::getParm('NETID') . '. You must login using that ' .  WaseUtil::getParm('NETID') . ' and your ' .  WaseUtil::getParm('PASSNAME') . '.';
            }
        }
        /* And now make sure it is valid */
        if (!$errmsg) {
            if ($merrmsg = WaseUtil::validateEmail($email))
                $errmsg = 'Specified email address "' . $email . '" is not valid: ' . $merrmsg;
        }
        	
        if (!$errmsg) {
            /* save login credentials */
            $_SESSION['authenticated'] = true;
            $_SESSION['authtype'] = 'guest';
            $_SESSION['authid'] = $email;
            $_SESSION['ismanager'] = false;
            $_SESSION['INSTITUTION'] = $_SERVER['INSTITUTION'];


            /* Check for an authentication redirect URL */
            if ($_SESSION['redirurl'] != "") {
                //$temp = htmlspecialchars($_SESSION['redirurl']);
                $temp = $_SESSION['redirurl'];
                $temp = str_replace("&","AMPERSAND",$temp);
                $temp = htmlspecialchars($temp);
                $temp = str_replace("AMPERSAND","&",$temp);
                $_SESSION['redirurl'] = "";
                header("Location: $temp");
                exit();
            }

            /* Now send to the appointments page */
            header('Location: makeappt.php');
            exit();
        }
    }
}


/* Handle login */
function doLogin($id) {
	global $errmsg; 

	// Make sure we have session support
    if (session_status() != PHP_SESSION_ACTIVE)
        session_start();

	/* Call the local authentication exit, if any */
	if (class_exists('WaseLocal')) {
	    $func = 'login';
	    if ($inst = @$_SERVER['INSTITUTION'])
	        $func = $inst.'_'.$func;
	    if (method_exists('WaseLocal', $func)) {		
			if ($errmsg = WaseLocal::$func($id))
				return;
		}
	}
	/*initialize blackbloard user_course_info */
    $_SESSION['bb_user_course_array'] = array();
     
	/* Save login credentials */
	$_SESSION['authenticated'] = true;
	$_SESSION['authtype'] = 'user';
	$_SESSION['authid'] = $id;
	$_SESSION['inlogin'] = false;
	$_SESSION['INSTITUTION'] = isset($_SERVER['INSTITUTION']) ? $_SERVER['INSTITUTION'] : '';

	// If user has no local calendar sync preference set, set it to none.
	if (!$localsync =  WasePrefs::getPref($id,'localcal'))
	    WasePrefs::savePref($id, 'localcal', 'none'); 
	
	// If user has no remind preference set, set it to on (1).
	if (($localremind =  WasePrefs::getPref($id,'remind')) === '')
	    WasePrefs::savePref($id, 'remind', 1);
	
	/* Save whether/not user owns a calendar */
	$calids = WaseCalendar::arrayOwnedCalendarids($id);
	if ($calcount = count($calids)) {
		$_SESSION['owner'] = true;
	}
	else {
		$_SESSION['owner'] = false; 
	}
	
	/* Save whether/not user is a manager */
	if (WaseManager::listManagedids($id,'')) {
		$_SESSION['ismanager'] = true;	
	}
	else
		$_SESSION['ismanager'] = false;		


	/* Save whether/not user is a member */
	if (WaseMember::listMemberedids($id,'')) {
	    $_SESSION['ismember'] = true;
	}
	else
	    $_SESSION['ismember'] = false;

	/* Check for an authentication redirect URL */
	if (@$_SESSION['redirurl'] != "") {
		//$temp = htmlspecialchars($_SESSION['redirurl']);
        $temp = $_SESSION['redirurl'];
        $temp = str_replace("&","AMPERSAND",$temp);
        $temp = htmlspecialchars($temp);
        $temp = str_replace("AMPERSAND","&",$temp);
		$_SESSION['redirurl'] = "";
        // If the authentiation redirect is to viewcalendar.php,
        // make sure the user can access that calendar, else ignore it.
        $matches = array();
        if ($calpos = strpos($temp, 'viewcalendar.php?calid=')) {
            if (preg_match('/[\d]+/', substr($temp, $calpos), $matches))
                if ($calid = $matches[0]) {
                    // Load the calendar and test for viewability
                    try {
                        $cal = new WaseCalendar('load', array('calid' => $calid));
                        if (!$cal->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                            $temp = '';
                    } catch (Exception $e) {
                        $temp = '';
                    }
                }

        }
        if ($temp) {
            header('Location: ' . $temp);
            exit();
        }
	}	

	/* If user owns one calendar, show it */
	if (!$_SESSION['ismanager'] && !$_SESSION['ismember'] && $_SESSION['owner'] && $calcount == 1) {
	    header('Location: viewcalendar.php?calid=' . $calids[0]);
	    exit();
	}

	/* If owner/manager/member, show calendars, else make appointemnt. */
	if ($_SESSION['owner']  || $_SESSION['ismanager'] || $_SESSION['ismember'])
	    header('Location: calendars.php');
	else {
        header('Location: makeappt.php?');
	}
	exit();
}



// Save SHIB attributes in session variables
function getShib($attributes) {
    //WaseMsg::dMsg('SHIB','Getting SHIB getAttributes');
    // Dump if requested
    if ($to = WaseUtil::getParm('SHIBDUMPEMAIL'))
        WaseUtil::Mailer($to, 'SHIB attributes for ' . $_SERVER['INSTITUTION'], print_r($attributes, true));
    if (!$_SESSION['SHIB']['userid'] = (string)$attributes[WaseUtil::getParm('SHIBUSERID')][0]) {
        // Try common attribute if configured one fails
        if (!$_SESSION['SHIB']['userid'] = $attributes['uid'][0]) {
            // Last try:  URN
            if (!$_SESSION['SHIB']['userid'] = $attributes[WaseConstants::SHIBUIDURN][0])
                die("SIBBOLETH userid attribute '" . WaseUtil::getParm('SHIBUSERID') . "' returns a null value, as does 'uid' and " . WaseConstants::SHIBUIDURN . "; please notify  " . WaseUtil::getParm('SYSMAIL'));
        }
    }
    if (!$_SESSION['SHIB']['office'] = (string)$attributes[WaseUtil::getParm('SHIBOFFICE')][0])
        $_SESSION['SHIB']['office'] = $attributes[WaseConstants::SHIBOFFICEURN][0];
    if (!$_SESSION['SHIB']['email'] = (string)$attributes[WaseUtil::getParm('SHIBEMAIL')][0])
        $_SESSION['SHIB']['email'] = (string)$attributes[WaseConstants::SHIBEMAILURN][0];
    if (!$_SESSION['SHIB']['name'] = (string) $attributes[WaseUtil::getParm('SHIBNAME')][0])
        $_SESSION['SHIB']['name'] = (string)$attributes['displayName'][0];
    if (!$_SESSION['SHIB']['name'])
        $_SESSION['SHIB']['name'] = (string)$attributes[WaseConstants::SHIBNAMEURN][0];
    if (!$_SESSION['SHIB']['phone'] = (string)$attributes[WaseUtil::getParm('SHIBPHONE')][0])
        $_SESSION['SHIB']['phone'] = (string)$attributes[WaseConstants::SHIBPHONEURN][0];
    
    return $_SESSION['SHIB']['userid'];
   
} 


// Save CAS attributes in session variables
function getCAS() {
    foreach (phpCAS::getAttributes() as $key => $value) {
        $casarray[$key] = $value;
        if (is_array($value))
            $val = (string) $value[0];
        else
            $val = (string) $value;
        // We save the attributes in thge same place as SHIB for simplicity.
        if ($key == WaseUtil::getParm('SHIBOFFICE')) $_SESSION['SHIB']['office'] = (string) $val;
        if ($key == WaseUtil::getParm('SHIBEMAIL')) $_SESSION['SHIB']['email'] = (string) $val;
        if ($key == WaseUtil::getParm('SHIBNAME')) $_SESSION['SHIB']['name'] = (string) $val;
        if ($key == WaseUtil::getParm('SHIBPHONE')) $_SESSION['SHIB']['phone'] = (string) $val;
    }
    $_SESSION['SHIB']['userid'] = $_SESSION['CAS']['userid'];
    // Dump if requested
    if (WaseUtil::getParm('SHIBDUMPEMAIL'))
        WaseUtil::Mailer(WaseUtil::getParm('SHIBDUMPEMAIL'), 'CAS attributes for ' . $_SERVER['INSTITUTION'], print_r($casarray, true));
    return; 
}

?>
<?php
include "_includes.php";
?>
<script type="text/javascript" language="javascript" src="loginview.js?v=<?php echo $versionString; ?>"></script>

<script type="text/javascript">	
var gParms = {};

   	$(document).ready(function() {
      	doOnReady();
        if (typeof gMessage !== 'undefined') {
		<?php if ($infmsg) { ?>
		    gMessage.displayConfirm('<?php echo $infmsg?>');
		<?php } ?>
   	   	}
		
		controller.getParameters(["ALERTMSG"]);

		$("#logininfo").submit(function(e) {
		    gMessage.removeMessage();
			var usr = $("#txtUsername").val();
			var pwd = $("#txtPassword").val();
			var errs = new Array();
			if (isNullOrBlank(usr))
				errs[errs.length] = new Error (0,"Please enter a user name");
			if (isNullOrBlank(pwd))
				errs[errs.length] = new Error (0,"Please enter a password");
			if (errs.length > 0)
				Error.displayErrors(errs);	
			else			
				controller.loginUserPwd($("#txtUsername").val(),$("#txtPassword").val());
			return false;
		});
		$('#guestlogininfo').submit(function(e) {
		    return true;
		    gMessage.removeMessage();
			var eml = $("#txtEmail").val();
			if (isNullOrBlank(eml)) {
				var errs = new Array();
				errs[0] = new Error (0,"Please enter an e-mail address");
				Error.displayErrors(errs);	
			}
			else {
				var arr = eml.split(" ");
				var guestpwd = "";
				if (arr.length > 1) guestpwd = arr[1];
				controller.loginGuest(arr[0],guestpwd);
			}
			return false;
		});
		
		//Do this only on large screens
		controller.getDidYouKnow();
	}); 
	function setRelease(inRelease) {
		$("#divHeaderLogo .release").text(inRelease);
	}
		
</script>                                                               
</head>

<body onunload="">

<div data-role="page" id="login">

    <div data-role="header" id="divHeader" class="nonav">
    	<div id="divHeaderLogo">
        	<div class="release">(Release <?php echo WaseRelease::RELEASE; ?>)</div>
        </div>
        <div class="borderdiv_top"></div>
        <div class="borderdiv_bottom"></div>
        <div id="divMessage"></div>
   	</div><!-- /header -->
	<div class="ui-content" role="main" style="clear:both;">
        <div class="divIntro">
                <p>The Web Appointment Scheduling Engine allows members of the <?php echo WaseUtil::getParm('INSNAME'); ?> University community to create web-based calendars and list their available times as appointment blocks. Other <?php echo WaseUtil::getParm('INSNAME'); ?> users and optional guest users can book appointments in these blocks.<p>

                <p>It is possible to synchronize appointments in the system with other calendaring applications, such as Outlook or Google Calendar. [<a href="#"  title="more info about the Web Appointment Scheduling Engine" data-ajax="false" onClick="openIntro(); return false;">More Info</a>]</p>
            </div>

		<h1 id="pagetitle" class="clear">Log In</h1>
        
        <div id="divLogIn">
        <?php if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHLDAP') || WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHAD') || WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHLOCAL')) { ?>     
		<form id="logininfo" method="post" action="makeappt.php" data-ajax="false">
            <input type="hidden" id="helpTopic" value="dologin" />
		
            <div id="divDirectLogin" class="loginsection">
            	<h2 class="clear"><?php echo WaseUtil::getParm('INSNAME');?> Community Members</h2>
                <div class="instructions">Enter a valid <?php echo WaseUtil::getParm('NETID'); ?> and <?php echo WaseUtil::getParm('PASSNAME');?> and click Log In.</div>
    
                <div id="divUsername" class="ui-field-contain">
                    <label id="lblUsername" for="txtUsername" class="ui-hidden-accessible"><?php echo WaseUtil::getParm('NETID'); ?>:</label>
                    <input type="text" name="txtUsername" id="txtUsername" autocapitalize="none" placeholder="<?php echo strtolower(WaseUtil::getParm('NETID')); ?>" />
                </div> 
                <div id="divPassword" class="ui-field-contain">
                    <label id="lblPassword" for="txtPassword" class="ui-hidden-accessible"><?php echo WaseUtil::getParm('PASSNAME');?>:</label>
                    <input type="password" name="txtPassword" id="txtPassword" placeholder="<?php echo WaseUtil::getParm('PASSNAME');?>" />
                </div> 
                <div id="divSubmit" class="ui-field-contain">    
                    <input type="submit" name="btnLogIn" id="btnLogIn" value="Log in" data-inline="true" />
                </div>
            </div>
    	</form>
        <?php } 
		else { ?>
		<form id="caslogininfo" method="post" action="login.page.php" data-ajax="false">  
            <div id="divCASLogin" class="loginsection">
            	<h2 class="clear"><?php echo WaseUtil::getParm('INSNAME');?> Community Members</h2>
                <div class="instructions">Click the Log in button to enter a valid <?php echo WaseUtil::getParm('NETID'); ?> and <?php echo WaseUtil::getParm('PASSNAME');?>.</div>
    
                <div id="divCASSubmit" class="ui-field-contain">    
                    <input type="submit" name="btnCASLogIn" id="btnCASLogIn" value="Log in" data-inline="true" />
                </div>
            </div>
    	</form>
        <?php } ?>
		<form id="guestlogininfo" method="post" action="login.page.php" data-ajax="false">       
            <div id="divGuestLogin" class="loginsection">
                <h2 class="clear">Guests</h2>
                <div class="instructions">Enter your e-mail address and click the Guest Log In button.</div>
    
                <div id="divEmail" class="ui-field-contain">
                    <label id="lblEmail" for="txtEmail" class="ui-hidden-accessible">E-mail:</label>
                    <input type="text" name="txtEmail" id="txtEmail" placeholder="e-mail address" />
                </div> 
                <div id="divSubmitGuest" class="ui-field-contain">    
                    <input type="submit" name="btnLogInGuest" id="btnLogInGuest" value="Guest Log In" data-inline="true" />
                </div>
            </div>
    	</form>
        </div>
        

        <div id="divLogInInfo"> 
               
            <div id="divLogInHelp" class="clearfix">
                <h2><a href="#" data-ajax="false" onClick="openHelp(); return false;">Help</a></h2>
                <h2><a href="whatsnew.php" data-ajax="false">What's new</a></h2>
            </div>
            
            <div id="divDidYouKnow">
            	<h2 class="sideInfoHeader">Did You Know?</h2>
                <div id="divDYKHeader" class="didYouKnowContent"></div>
                <div id="divDYKDetails" class="didYouKnowContentDetails"></div>
                <div class="sideInfoNav"><a href="#" onclick="controller.getDidYouKnow();">Next &gt;</a></div>
            </div>
            
        </div>
        
        
	</div><!-- /content -->

	<?php include('_footer.php'); ?>
    
<script type="text/javascript">
	function openHelp() {
		window.open(getURLPath() + "/help.php?helptopic=dologin", "_blank");
	}
</script>
</div><!-- /page -->

</body>
</html>
