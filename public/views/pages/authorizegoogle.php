<?php

/**
 * This script obtains google authorization credentials for a google calendar.
 */

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Start session support */
@session_start();

/* Grab the userid of the user whose Google calendar we want to access */
$userid = $_SESSION['authid'];
if (! $userid) {
    $userid = $_REQUEST['userid'];
    $_SESSION['authid'] = $userid;
}

if (! $userid)
    die('Userid not provided to the authorizegoogle script');

/* Determine if called by WASE */
if ($_REQUEST['init'] == 'true') {
    // If we already have Google credentials, just return success
    $token = trim(WasePrefs::getPref($userid, 'google_token'));
    $calid = trim(WasePrefs::getPref($userid, 'google_calendarid'));
    if ($token && $calid) {
        /* We need to test to make sure that the Google credentials are still valid */
        if (WaseGoogle::testConnect($userid)) {
            header('Location: preferences.php?google_result=success');
            exit();
        } else {
            /* Clear out the credentials */
            $token = WasePrefs::savePref($userid, 'google_token', '');
            $calid = WasePrefs::savePref($userid, 'google_calendarid', '');
        }
        // We will now drop down to the authorization code
    }
} 

/* Now set up the google calendar objects */
$client = new Google_Client();
$client->setApplicationName("WASE");

// Visit https://code.google.com/apis/console?api=calendar to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId(WaseUtil::getParm('GOOGLEID'));
$client->setClientSecret(WaseUtil::getParm('GOOGLESECRET'));
$client->setDeveloperKey(WaseUtil::getParm('GOOGLEKEY'));
// We need to handle the institution directory
if ($_SERVER['INSTITUTION'])
    $ins = '/' . $_SERVER['INSTITUTION'];
else
    $ins = '';
$client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . $ins .  $_SERVER['SCRIPT_NAME']);

// Set the offline access only if you need mailto call an API
// when the user is not present and the token may expire.
 $client->setAccessType('offline');

// Set the access scope
//$client->setScopes("https://www.googleapis.com/auth/calendar");
$client->setScopes("https://www.googleapis.com/auth/calendar.events");

/*
 * Now create the calendar object.
 *
 * We will be coming back to this script as the authroization progresses,
 * so we test to see what Google is passing back to determine where we
 * are in the process.
 *
 */

// $cal = new Google_CalendarService($client);
$cal = new Google_Service_Calendar($client);

/* If caller rejects the access request, let the preferences system know */
if (isset($_REQUEST['logout']) || isset($_REQUEST['error'])) {
    unset($_SESSION['google_token']);
    /* Reset the calendar sync preference to None */
    WasePrefs::savePref($userid, 'localcal', 'none');
    header('Location: preferences.php?google_result=denied');
    exit();
}

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
