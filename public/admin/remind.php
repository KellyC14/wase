<?php
/**
 *
 * This script sends email reminders to people who have made, or are the targets of, appointments.  
 * It is intended that this script be invoked through an automated process that runs every evening.  
 * It sends reminders out for appointments on the following day, unless invoked with a date argument, 
 * in which case it sends out reminders for appointments scheduled on the specified date. 
 *
 * 
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */ 


/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

 
/* Start session support */ 
session_start();  

/* Init error/inform messages */ 
$errmsg = '';  $infmsg = ''; 
 
/* See if argument passed from the command line */
if ($_SERVER['argc'] > 1) {
	list($secret,$pass) = explode("=",$_SERVER['argv'][1]);
	if (strtoupper($secret) == 'SECRET') 
		$_REQUEST['secret'] = $pass;
	elseif ($pass == '')
		$_REQUEST['secret'] = $secret;		
} 
else 
    WaseUtil::CheckSSL();

/* Make sure password was specified */
if ($_REQUEST['secret'] != WaseUtil::getParm('PASS')) {
	$msg = 'Attempt to access admin application ' . $_SERVER['SCRIPT_NAME'] . ' without a password from: ' . $_SERVER['REMOTE_ADDR'];
	WaseMsg::logMsg($msg);
	die('Unauthorized access.');
}

	 
/* If date passed as argument, use that. */
if (isset($_REQUEST["date"]))
	$date = $_REQUEST["date"];
/* If not passed date, use tomorrow. */
else {
	$tomorrow = time() + (60*60*24); /* Next day as Unix timestamp */
	$fulldate = getdate($tomorrow);    /* Convert to date format */
	$date = $fulldate['mon'] . '/' . $fulldate['mday'] . '/' . $fulldate['year'];
}

 
/* Compute tomorrow's date (or passed date) in SQL format */
$sqldate = WaseSQL::sqlDate($date);

/* See if we are supposed to ignore the "reminded" flag */
if (isset($_REQUEST["ignore_reminded"]))
	$request = array('date(startdatetime)'=>$sqldate,'available'=>1);
else
	$request = array('date(startdatetime)'=>$sqldate,'available'=>1,'reminded'=>0);	
 

/* See if we are just testing the remind function */
if (isset($_REQUEST["test"])) {
    $test = true;
    echo 'Testing: ' . print_r($request,true) . '<br />';
}
else
    $test = false;
 
/* Find all available appopintments for tomorrow (or specified date) */	  
$apps = WaseAppointment::listMatchingAppointments($request);
	

/* Init appointment counters */
$remindpt=0;  $remindst=0;  $remindmt=0; $remindet=0;

/* Go through apps, send email reminders. */

foreach ($apps as $app) { 				
    /* Extract start date and time */
    list ($startdate, $starttime) = explode(' ', $app->startdatetime);
    
    // Read in the block
	$block = new WaseBlock('load',array('blockid'=>$app->blockid));
	 
	
	$subject = $block->APPTHING . ' reminder: ' . $app->name . ' with ' . $block->name . ' at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate);
							
	/* Send out notice and get back count of notices sent */
	if (!$test)
	    $sentout = $app->notify('The following ' . $block->APPTHING .  ' is scheduled:<br /><br/>',$subject,'','remind',''); 
	else
	    echo $subject . '<br />';
	    
	/* Parse out the counts */
	list($remindp, $reminds, $remindm, $reminde) = explode(',',$sentout);
	/* Accumulate */
	$remindpt += $remindp;  $remindst += $reminds;  $remindmt += $remindm; $remindet += $reminde;
	/* Set reminded flag */
	$app->reminded = 1;
	$app->save('reminded');
	
}

/* Lets purge the wait list */
$purged = WaseWait::purgeWaitListAll(); 

 
/* Send summary information to administrator */
$omsg = "<html><head></head><body>Statistics from remind.php:<br />\r\n";
if ($remindst == 0)   
	$omsg .= "No student reminders to send.<br />";
elseif ($remindst == 1)
	$omsg .= "1 student reminder sent.<br />";
else
	$omsg .=  "$remindst student reminders sent.<br />";	
$omsg .= "\r\n";
 
if ($remindpt == 0)   
	$omsg .= "No owner reminders to send.<br />";
elseif ($remindpt == 1)
	$omsg .= "1 owner reminder sent.<br />";
else
	$omsg .=  "$remindpt owner reminders sent.<br />";

if ($remindet == 0)   
	$omsg .= "No member reminders to send.<br />";
elseif ($remindet == 1)
	$omsg .= "1 member reminder sent.<br />";
else
	$omsg .=  "$remindet member reminders sent.<br />";
	
if ($remindmt == 0)   
	$omsg .= "No manager reminders to send.<br />";
elseif ($remindmt == 1)
	$omsg .= "1 manager reminder sent.<br />";
else
	$omsg .=  "$remindmt manager reminders sent.<br />";


if ($purged == 0)
    $omsg .= "No wait list entries purged.<br />";
elseif ($purged == 1)
    $omsg .= "1 wait list entry purged.<br />";
else
    $omsg .=  "$purged wait list entry purged.<br />";

// Warn about out-of-date term dates in the config file.
if (WaseUtil::beforeToday(WaseUtil::getParm('CURTERMEND')) ||
    WaseUtil::beforeToday(WaseUtil::getParm('NEXTTERMEND')) || 
    WaseUtil::beforeToday(WaseUtil::getParm('CURYEAREND'))
    ) {
    $omsg .= '<br /><br />Please review the current/next/year term (semester) dates in you waseParms.Custom.yml file (such as CURTERMEND)... they appear to be out of date.';
}

$omsg .= "</body></html>";

/* Let administrator know results */
$headers = "Content-type: text/html\r\n";
// $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
if (WaseUtil::getParm('MAILREM') && !$test)
    WaseUtil::Mailer(WaseUtil::getParm('REMEMAIL'), 'Output of remind.php', $omsg, $headers);



/* All done */ 

exit();
	
	
