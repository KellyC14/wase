<?php
/*
Copyright 2006, 2008, 2013 The Trustees of Princeton University.

For licensing terms, see the license.txt file in the docs directory.

Written by: Serge J. Goldstein, serge@princeton.edu.
            Kelly D. Cole, kellyc@princeton.edu
			Jill Moraca, jmoraca@princeton.edu
		   
*/


/*

        This page logs out the user and redirects to the welcome page.
 
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

/* Special case code for CAS.  Should no longer be required.  */
//require_once('../../../libraries/CAS/CAS.php');
//phpCAS::setDebug('casdebug');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Start session support */
@session_start();

/* Save type of user */
$usertype = $_SESSION['authtype'];
$casuserid = $_SESSION['CAS']['userid'];
$shibuserid = $_SESSION['SHIB']['userid'];
$Parm_AUTHTYPE=WaseUtil::getParm('AUTHTYPE');
$Parm_AUTHCAS=WaseUtil::getParm('AUTHCAS');
$Parm_AUTHSHIB=WaseUtil::getParm('AUTHSHIB');
WaseMsg::dMsg('DBG','Info',"usertype[$usertype]casuserid[$casuserid]shibuserid[$shibuserid]Parm_AUTHTYPE[$Parm_AUTHTYPE]Parm_AUTHCAS[$Parm_AUTHCAS]Parm_AUTHSHIB[$Parm_AUTHSHIB]");

/* Clear our authentication variables */
	
$_SESSION['authenticated'] = '';
$_SESSION['authtype'] = '';
$_SESSION['authid'] = '';

/*clear blackbloard user_course_info */
$_SESSION['bb_user_course_array'] = array();

// make sure
session_destroy();

/* Restart session support */
@session_start();
	
/* Logout of CAS if needed */
if ((WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS'))  && ($usertype != 'guest') && $casuserid) {
		
	/* Flag that we just logged out */
	$_SESSION['caslogout'] = true;
	$_SESSION['CAS']['userid'] = $casuserid;
	
}

/* Logout of SHIB if needed */
if ((WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHSHIB'))  && ($usertype != 'guest') && $shibuserid) {

    /* Flag that we just logged out */
    $_SESSION['shiblogout'] = true;
    $_SESSION['SHIB']['userid'] = $shibuserid;

}

$my_session_shiblogout=$_SESSION['shiblogout'];
$my_session_shib_userid=$_SESSION['SHIB']['userid'];
WaseMsg::dMsg('DBG','Info',"my_session_shiblogout[$my_session_shiblogout]my_session_shib_userid[$my_session_shib_userid]");
/* Redirect to the welcome page */
header('Location: login.page.php');
exit();
