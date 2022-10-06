<?php
/**
 *
 * This script sends txt-msg reminders to people who have made, or are the targets of, appointments.
 * It is intended that this script be invoked by CRON on an hourly basis.
 * It sends reminders out for appointments that are >=1 and <2 hours of the current time.
 * It only sends txt-msg reminders only if the the txt-msg field in the appointment is set.
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */


/* Include the Composer autoloader. */
require_once('../../vendor/autoload.php');


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();


/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';
$infmsg = '';

/* Make sure password was specified */
if ($_REQUEST['secret'] != WaseUtil::getParm('PASS')) {
    $msg = 'Attempt to access admin application ' . $_SERVER['SCRIPT_NAME'] . ' without a password from: ' . $_SERVER['REMOTE_ADDR'];
    WaseMsg::logMsg($msg);
    die('Unauthorized access.');
}


/* Compute start datetime 1 hour from now and 2 hours from now. */
$start = date('Y-m-d H:m:00', time() + (60 * 60));
$end = date('Y-m-d H:m:00', time() + (2 * 60 * 60));

$request = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE textemail != "" AND startdatetime >= "' . $start . '" AND startdatetime < "' . $end . '"';

/* See if we are just testing the remind function */
if (isset($_REQUEST["test"])) {
    $test = true;
    echo 'Testing: ' . print_r($request, true) . '<br />';
} else
    $test = false;


/* Find all available appopintments for 1+ hour from now */
$apps = new WaseList($request, 'Appointment');


// Set up email headers
$shortheaders = "Errors-To: " . WaseUtil::getParm('SYSMAIL' . "\r\n" . "Return-Path: .  WaseUtil::getParm('SYSMAIL");

/* Go through apps, send email textmsg reminders. */

$sent = 0;

foreach ($apps as $app) {

    /* Extract start date and time */
    list ($startdate, $starttime) = explode(' ', $app->startdatetime);

    // Read in the block
    $block = new WaseBlock('load', array('blockid' => $app->blockid));

    $subject = $block->APPTHING;
    $body = $app->name . ' with ' . $block->name . ' at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate);

    /* Send out notice and get back count of notices sent */
    if (!$test) {
        WaseUtil::Mailer($app->textemail, $subject, $body, $shortheaders);
        $sent++;
    }
    else
        echo "To: $app->textemail Subject: $subject Body: $subject Headers: $headers" . "\r\n" . '<br />';

}

/* All done */
// echo "$sent text message reminders sent \r\n";

exit();
	
	
