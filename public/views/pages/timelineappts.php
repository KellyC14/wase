<?php
/*
 * Copyright 2006, 2008, 2012 The Trustees of Princeton University.
 *
 * For licensing terms, see the license.txt file in the docs directory.
 *
 * Written by: Serge J. Goldstein, serge@princeton.edu.
 * Kelly D. Cole, kellyc@princeton.edu
 * Jill Moraca, jmoraca@princeton.edu
 *
 *
 * This code in invoked by an HTTPGET/ POST. It returns a JSON file of appointments matching the posted search criteria.
 */

// Criteria fields: startdate, starttime, enddate, endtime, userid

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Start session support */
// @session_start();

/* Make sure password was specified */
if ($_REQUEST['secret'] != WaseUtil::getParm('PASS')) {
    $msg = 'Attempt to access admin application ' . $_SERVER['SCRIPT_NAME'] . ' without a password from: ' . $_SERVER['REMOTE_ADDR'];
    WaseMsg::logMsg($msg);
    die('Unauthorized access.');
}

/* Grab the parameters */
$ruserid = array_key_exists('userid',$_REQUEST) ? trim((string) $_REQUEST['userid']) : '';
$rstartdate = array_key_exists('startdate',$_REQUEST) ? urldecode(trim((string) $_REQUEST['startdate'])) : '';
$renddate = array_key_exists('enddate',$_REQUEST) ? urldecode(trim((string) $_REQUEST['enddate'])) : '';
$rstarttime = array_key_exists('starttime',$_REQUEST) ? urldecode(trim((string) $_REQUEST['starttime'])) : '';
$rendtime = array_key_exists('endtime',$_REQUEST) ? urldecode(trim((string) $_REQUEST['endtime'])) : '';
$rlastchange = array_key_exists('lastchange',$_REQUEST) ? urldecode(trim((string) $_REQUEST['lastchange'])) : '';

/* save timezone value for later */
$timezone = date_default_timezone_get();


// Set up the institution URL folder name.
if ($_SERVER['INSTITUTION'])
    $server = '/' . $_SERVER['INSTITUTION'];
else 
    $server = '';

/* First, get the group data */

// Get the list of users that only have "new" or changed appointments
$newusers = WaseSQL::doQuery('SELECT userid, name, lastchange FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment GROUP BY userid HAVING min(lastchange) > ' . WaseSQL::sqlSafe($rlastchange));

/* Start building the group data */
$groups = array();
$members = array();

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

/* Iterate through users building their personal wase group and group membership */
$seen = array();
while ($user = WaseSQL::doFetch($newusers)) {
    $newuserid = $user['userid'];
    $seen[] = $newuserid;
    $univid = '';
    if (!$univid = WasePrefs::getPref($newuserid,'universityid')) {
        $univid = $directory->getlDIR($newuserid, 'universityid');
        if (!$univid)
            continue;
        $result = WasePrefs::savePref($newuserid,'universityid',$univid);
    }
    if (! empty($newuserid) && ! empty($univid)) {
        $groups[] = array(
            'guid' => $newuserid,
            'name' => 'My ' . WaseUtil::getParm('SYSID') . ' ' . WaseUtil::getParm('APPOINTMENTS') . ' and Blocks',
            'memberType' => 'closed'
        );
        $members[] = array(
            'groupGuid' => $newuserid,
            'personGuid' => $univid,
            'personBatchKey' => 'ldap-people'
        );
    }
}

/* Repeat the above for blocks */
$newusers = WaseSQL::doQuery('SELECT userid, name, lastchange FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock GROUP BY userid HAVING min(lastchange) > ' . WaseSQL::sqlSafe($rlastchange));

/* Iterate through users building their personal wase group and group membership */
while ($user = WaseSQL::doFetch($newusers)) {
    $univid = '';
    if (! in_array($user['userid'], $seen)) {
        $newuserid = $user['userid'];
        if (!$univid = WasePrefs::getPref($newuserid,'universityid')) {
            $univid = $directory->getlDIR($newuserid, 'universityid');
            if (!$univid)
                continue;
            WasePrefs::savePref($newuserid,'universityid',$univid);
        }
        if (! empty($newuserid) && ! empty($univid)) {
            $groups[] = array(
                'guid' => $newuserid,
                'name' => 'My ' . WaseUtil::getParm('SYSID') . ' ' . WaseUtil::getParm('APPOINTMENTS') . ' and Blocks',
                'memberType' => 'closed'
            );
            $members[] = array(
                'groupGuid' => $newuserid,
                'personGuid' => $univid,
                'personBatchKey' => 'ldap-people'
            );
        }
    }
}
 
/* Bulld the select criteria for appointments */
$select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE 1 ';

if ($ruserid)
    $select .= ' AND (userid=' . WaseSQL::sqlSafe($ruserid) . ' OR blockid in (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid=' . WaseSQL::sqlSafe($ruserid) . '))';

if ($rstartdate)
    $select .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($rstartdate);
else
    die('You must specify a startdate');

if ($renddate)
    $select .= ' AND date(enddatetime) <=' . WaseSQL::sqlSafe($renddate);
else
    die('You must specify an enddate');

if ($rstarttime)
    $select .= ' AND time(startdatetime) >=' . WaseSQL::sqlSafe($rstarttime);

if ($rendtime)
    $select .= ' AND time(enddatetime) <=' . WaseSQL::sqlSafe($rendtime);

if ($rlastchange)
    $select .= ' AND lastchange > ' . WaseSQL::sqlSafe($rlastchange);
else
    die('You must specify a lastchange datetime');
    
    /* Now get the waselist of appointments */
$apps = new WaseList($select, 'Appointment');

/* Save viewable matching appointments in the results. */
$events = array();

/* Start with apps */
foreach ($apps as $app) {
    if (is_object($app)) {
        /* Read in the block */
        $block = new WaseBlock('load', array(
            'blockid' => $app->blockid
        ));
        list ($startdate, $starttime) = explode(' ', $app->startdatetime);
        // Set up purpose, if any
        if ($app->purpose)
            $purpose = ' purpose: ' . $app->purpose;
        else 
            $purpose = '';
        /* Now build the output string */
        $events[] = array(
            'guid' => $app->uid,
            'type' => 'event',
            'title' => WaseUtil::getParm('APPOINTMENT') . ': ' . $app->name . ' with ' . $block->name,
            'content' => WaseUtil::getParm('APPOINTMENT') . ' for ' . $app->userid . ' (' . $app->name . ') with ' . $block->userid . ' (' . $block->name . ') at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate) . $purpose,
            'startTime' => $app->startdatetime,
            'endTime' => $app->enddatetime,
            'timezone' => $timezone,
            'from' => WaseUtil::getParm('FROMMAIL'),
            'location' => array(
                'name' => $block->location
            ),
            'urlRef' => 'https://' . $_SERVER['SERVER_NAME'] . $server . '/views/pages/myappts.php',
            'groupSpec' => ($app->userid === $block->userid) ? array(
                'guid' => $app->userid
            ) : array(
                'union' => array(
                    array(
                        'guid' => $app->userid
                    ),
                    array(
                        'guid' => $block->userid
                    )
                )
            )
        );
    }
}

/* Bulld the select criteria for blocks */
$select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE 1 ';

if ($ruserid)
    $select .= ' AND (userid=' . WaseSQL::sqlSafe($ruserid) . ')';

if ($rstartdate)
    $select .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($rstartdate);
else
    die('You must specify a startdate');

if ($renddate)
    $select .= ' AND date(enddatetime) <=' . WaseSQL::sqlSafe($renddate);
else
    die('You must specify an enddate');

if ($rstarttime)
    $select .= ' AND time(startdatetime) >=' . WaseSQL::sqlSafe($rstarttime);

if ($rendtime)
    $select .= ' AND time(enddatetime) <=' . WaseSQL::sqlSafe($rendtime);

if ($rlastchange)
    $select .= ' AND date(lastchange) > ' . WaseSQL::sqlSafe($rlastchange);
else
    die('You must specify a lastchange datetime');
    
    /* Now get the waselist of blocks */
$blocks = new WaseList($select, 'Block');

/* Add the blocks */

// Init array to keep track of recurring blocks.
$seriesids = array();

foreach ($blocks as $block) {
    if (is_object($block)) {
        list ($startdate, $starttime) = explode(' ', $block->startdatetime);
        /* Now build the output string */
        
        // Init the guid with the block uid.
        $guid = $block->uid;
        // If recurring block, add in an increment.  Every block in a recurring series has the same uid.
        if ($block->seriesid) {
            // Increment counter and save it.
            $guid .= $seriesids[$block->uid]++;
        }
        $events[] = array(
            'guid' => $guid,
            'type' => 'event',
            'title' => WaseUtil::getParm('SYSID') . ' Appointment Block',
            'startTime' => $block->startdatetime,
            'endTime' => $block->enddatetime,
            'timezone' => $timezone,
            'from' => WaseUtil::getParm('FROMMAIL'),
            'location' => array(
                'name' => $block->location
            ),
            'urlRef' => 'https://' . $_SERVER['SERVER_NAME'] . $server . '/views/pages/login.page.php',
            'groupSpec' => array(
                'guid' => $block->userid
            )
        );
    }
} 

/* Return the result */
header('Content-Type: application/json');
echo json_encode(array(
    'group' => $groups,
    'membership' => $members,
    'postItem' => $events
),JSON_UNESCAPED_UNICODE);

