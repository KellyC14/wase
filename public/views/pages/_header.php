<?php
Header('Cache-Control: no-cache');
Header('Pragma: no-cache');

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
//if (session_status() == PHP_SESSION_ACTIVE) WaseMsg::dMsg('SHIALL','S1: ' . $_SESSION['authid'] . ' ' . $_SESSION['authenticated']);
/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();
//if (session_status() == PHP_SESSION_ACTIVE) WaseMsg::dMsg('SHIALL','S2: ' . $_SESSION['authid'] . ' ' . $_SESSION['authenticated']);
/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();
//if (session_status() == PHP_SESSION_ACTIVE) WaseMsg::dMsg('SHIALL','S3: ' . $_SESSION['authid'] . ' ' . $_SESSION['authenticated']);
/* Start session support */
WaseMsg::dMsg('SHIALL','Header session before start is: ' .
    'status=' . (int) session_status() . ' : ' .
    'name=' . session_name() . ' : ' .
    'id=' . session_id() . ' : ' .
    'cookie=' . session_get_cookie_params() .  ' : ' .
    'cache_expire=' . session_cache_expire() .
    ' authid=' . $_SESSION['authid'] . ' authenticated=' . (int) $_SESSION['authenticated']);
@session_start();

//if (session_status() == PHP_SESSION_ACTIVE) WaseMsg::dMsg('SHIALL','S4: ' . $_SESSION['authid'] . ' ' . $_SESSION['authenticated']);
/* If not authenticated, send back to authenticate */
//if (!$notdoauth) $doauth = true;

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

if (!$notdoauth)
    $directory->authenticate($_SERVER['REQUEST_URI']);

function isGuest() {
	return $_SESSION['authtype'] == 'guest';
}

