<?php

/**
 * This script obtains timeline authorization credentials (access token and refresh token).
 */

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Start session support */
@session_start();

/* Grab the name of the destination we want to access */
if (isset($_SESSION['destination'])) 
    $destination = $_SESSION['destination'];
else
    die('destination not provided to authorizetimeline script');


/* Determine if called by WASE */
if ($_REQUEST['init'] == 'true') {
    // If we already have timeline credentials, just return success
    $token = WasePrefs::getPref('system', 'timeline_access_token','system');
    if ($token)
        exit('Timeline access token already set.');
} 


// We need to handle the institution directory
if (isset($_SERVER['INSTITUTION']))
    $ins = '/' . $_SERVER['INSTITUTION'];
else
    $ins = '';
$redirecturi ='https://' . $_SERVER['HTTP_HOST'] . $ins .  $_SERVER['SCRIPT_NAME'];


/* If we are being passed a session code, get the access token, save it, and return to caller */
if (isset($_REQUEST['code'])) {
    try {
        $client->authenticate($_REQUEST['code']);
    } catch (Exception $e) {
        WaseMsg::logMsg("Google sync error trying to authenticate code : " . $e->getMessage());
        header('Location: preferences.php?google_result=denied');
    }
    try {
         $token = jsoniffy($client->getAccessToken());
         WasePrefs::SavePref($userid, 'google_token', $token);
    } catch (Exception $e)  {
        WaseMsg::logMsg("Google sync error trying to get acces token : " . $e->getMessage());
        header('Location: preferences.php?google_result=denied');
    }
   
    
    /* Get list of owned calendars */
    
    // For now, just use the primary calendar -- comment out the next two lines, and uncomment out the lines below, to restore the calendar select function.
    $calcount = 1;
    $_SESSION['CALLIST'] = 'primary';
    
    // Commented-out calendar select code.
    /*
     * $_SESSION['CALLIST'] = '';
     * $calList = $cal->calendarList->listCalendarList();
     *
     * for ($i = 0; $i < count($calList['items']); $i ++) {
     * if (trim($calList['items'][$i]['accessRole']) == 'owner') {
     * if ($_SESSION['CALLIST'])
     * $_SESSION['CALLIST'] .= ';;;';
     * $_SESSION['CALLIST'] .= $calList['items'][$i]['id'];
     * $calcount ++;
     * }
     * }
     */
    
    if ($calcount == 1) {
        WasePrefs::savePref($userid, 'google_calendarid', $_SESSION['CALLIST']);
        header('Location: preferences.php?google_result=success');
    } else
        header('Location: preferences.php?google_result=callist');
    
    exit();
} 

/* If this is the first call, go ahead and let the user authorize us to access their calendar(s). */
else {
    header('Location: ' . $client->createAuthUrl());
    exit();
}


// This function returns a google token in json format.
function jsoniffy($token) {
    if (is_string($token))
        return $token;
    if (is_object($token))
        $token =  (array) $token;
    return json_encode($token);
}
?>
