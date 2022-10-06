<?php
/*
Copyright 2006, 2008, 2013 The Trustees of Princeton University.

For licensing terms, see the license.txt file in the docs directory.

Written by: Serge J. Goldstein, serge@princeton.edu.
            Kelly D. Cole, kellyc@princeton.eduduration
			Jill Moraca, jmoraca@princeton.edu
		   
*/

/* This routine reads a WASS database and writes out an equivalent WASE database.

This script expects to find a WaseParms file with login information for the WASE
database.  
	
*/


// This could take 10 minutes+
set_time_limit(3600);


/* Handle loading of classes. */
require_once('autoload.include.php');

/* Start session support */
session_destroy();
session_start();


// Prepare the alternate (WASS) database parameters.  Set $_SESSION['ALT_HOST'] to '' if both databases on same host.
if (!$_SESSION['ALT_HOST'] = WaseUtil::getParm('WASS_HOST'))
    die("WASS_HOST not set in system parameters file.");


$_SESSION['ALT_DATABASE'] = WaseUtil::getParm('WASS_DATABASE');
$_SESSION['ALT_USER'] = WaseUtil::getParm('WASS_USER');
$_SESSION['ALT_PASS'] = WaseUtil::getParm('WASS_PASS');
$_SESSION['ALT_CHARSET'] = WaseUtil::getParm('WASS_CHARSET');


/* Init error/inform messages */
$errmsg = '';
$infmsg = '';

/* See if password argument passed from the command line */
if ($_SERVER['argc'] > 1) {
    list($secret, $pass) = explode("=", $_SERVER['argv'][1]);
    if (strtoupper($secret) == 'SECRET')
        $_REQUEST['secret'] = $pass;
    elseif ($pass == '')
        $_REQUEST['secret'] = $secret;
}

// Init date and such
WaseUtil::init();

/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth();

// Save cutoff, if specified
$cutoff = $_REQUEST['cutoff'];

// calendars will hold list of calendars to be migrated.
$calendars = array();

// Build list of calendar IDs
if ($calid = $_REQUEST['calid']) {
    if ($calid == 'ALL') {
        // Must have a cutoff with ALL.
        if (!$cutoff)
            die('You must specify a cutoff value with calid=ALL');
        // Get list of all calendars with blocks past the curoff.

        $calendarselect = 'SELECT DISTINCT calendarid FROM ' . $_SESSION['ALT_DATABASE'] . '.wassBlock WHERE `date` >= "' . $cutoff . '"';

    } else {
        $calendarselect = $calid;
        if ($cutoff)
            echo('Note: cutoff value ignored when calid is not ALL.<br />');
    }
} else
    die('You must specify a calid= value (either a calendar id number, or ALL');


// Save force migration status
// $force = $_REQUEST['force'];

// Save notification status
$notify = $_REQUEST['notify'];
if ($notify != 'yes' && $notify != 'no')
    die('You must specify notify=yes or notify=no to set whether or not migration notification emails should be sent out.');


// Bring in the table definitions and table alterations.
require_once("tables.php");

/* Init counters */
$cals = 0;
$series = 0;
$periods = 0;
$blocks = 0;
$appts = 0;
$managers = 0;
$members = 0;

/* Start by building the calendar/series/block/appointment structure */
if ($calid)
    echo 'Copying table data for calendar ' . $calid . ' from wass to wase ...<br /><br />';
else
    echo 'Copying table data from wass to wase ...<br /><br />';

// Init arrays that will keep track of users to be notified.
$calusers = array();
$appusers = array();


// Switch to alternate  
if ($_SESSION['ALT_HOST'])
    $_SESSION['ALT_SQL'] = true;


/* Read in all of the calendars */
echo 'SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassCalendar WHERE calendarid IN (' . $calendarselect . '<br />';

$calendars = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassCalendar WHERE calendarid IN (' . $calendarselect . ')');

$counter = 0;
$start = microtime(true);

$total = WaseSQL::num_rows($calendars);

WaseUtil::Mailer('serge@princeton.edu', 'Migrating calendars', "Migrating $total calendars");

while ($wasscal = WaseSQL::doFetch($calendars)) {

    // Let me know
    if (($counter % 100) == 1)
        WaseUtil::Mailer('serge@princeton.edu', 'Migrated calendars', "Have reached $counter out of $total .");


    $now = (microtime(true) - $start);
    if ($now > (60 * 60))
        exit('Thirty minutes have elapsed');

    $counter++;
    echo '>br />Processing calendar ' . $counter . ' with calendarid = ' . $wasscal['calendarid'] . '<br />';

    // Save the calendar
    $savecal = $wasscal;

    // Determine if user already has a WASE calendar, and, if so, use it.
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = false;

    $wasecal = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE userid=' . WaseSQL::sqlSafe($wasscal['userid']) . ' AND title=' . WaseSQL::sqlSafe($wasscal['title']) . ';'));
    if ($wasecal['title']) {
        echo "Calendar with title '" . WaseSQL::sqlSafe($wasscal['title']) . "' for user " . WaseSQL::sqlSafe($wasscal['userid']) . " already exists in WASE: it will be updated.<br />";
    }
    // Check if already migrated (unless 'force' is set to 1)
    // if (!$force) {
    if ($migrated = WasePrefs::getPref('system', 'wass2wase ' . $wasscal['calendarid'], 'system')) {
        echo '<p>WASS Calendar ' . $wasscal['calendarid'] . ' previously migrated on ' . $migrated . '</p>';
        $afterdate = $migrated;
        echo '<p>Will only migrate blocks and appointments with date/times after ' . $afterdate;
        $afterif = ' AND (`date` >= "' . substr($afterdate, 0, 10) . '" OR (`date` = "' . substr($afterdate, 0, 10) . '" AND starttime >= "' . substr($afterdate, 11, 6) . '00" ))';
        $afterifs = ' AND (`startdate` >= "' . substr($afterdate, 0, 8) . '")';
    } else {
        $afterif = '';
        $afterifs = '';
        echo "<p>Calendar does not appear to have been previously migrated.</p>";
    }

    if (!$wasecal['title']) {
        echo "Calendar with title '" . WaseSQL::sqlSafe($wasscal['title']) . "' for user " . WaseSQL::sqlSafe($wasscal['userid']) . " does NOT exist in WASE, creating it.<br />";
        /* Copy the array for wase */
        $wasecal = $wasscal;
        /* Now fix up the values. */
        unset($wasecal['calendarid']);
        unset($wasecal['managers']);
        unset($wasecal['group']);
        unset($wasecal['password']);
        $wasecal['remindmem'] = 1;
        $wasecal['notifymem'] = 1;
        $wasecal['apptmsg'] = $wasecal['appmsg'];
        unset($wasecal['appmsg']);
        $wasecal['APPTHING'] = 'appointment';
        $wasecal['APPTHINGS'] = 'appointments';
        $wasecal['NAMETHING'] = 'office hour';
        $wasecal['NAMETHINGS'] = 'office hours';

        /* Now try to insert the table entry */
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = false;
        /* Build the insert string. */
        $query = buildinsert($wasecal, 'WaseCalendar');

        if (!$ret = WaseSQL::doQuery($query)) {
            echo('WaseCalendar insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
            exit();
        } else {
            $cals++;
            if (!in_array($wasscal['email'], $calusers))
                $calusers[] = $wasscal['email'];
        }

        $wasecal['calendarid'] = WaseSQL::insert_id();
    }

    /* Now handle the managers and members for this calendar */

    /* Get all of the current managers/members */
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = true;
    $wassmanmems = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassManager WHERE calendarid=' . $wasscal['calendarid'] . ';');
    while ($wassmanmem = WaseSQL::doFetch($wassmanmems)) {
        // If the manager or member is already in the WASE calendar, skip it.
        if (!(WaseMember::isMember($wasecal['calendarid'], $wassmanmem['userid']) || WaseManager::isManager($wasecal['calendarid'], $wassmanmem['userid']))) {
            /* Copy over the data */
            $wasemanmem = $wassmanmem;

            /* Fix up */
            unset($wasemanmem['password']);
            $wasemanmem['calendarid'] = $wasecal['calendarid'];
            $wasemanmem['userid'] = $wassmanmem['managerid'];
            $wasemanmem['status'] = $wassmanmem['source'];
            if ($wasemanmem['status'] == 'user' || $wasemanmem['status'] == '')
                $wasemanmem['status'] = 'active';
            unset($wasemanmem['source']);
            unset($wasemanmem['managerid']);

            /* Determine whether manager or member */

            if ($wasscal['group']) {
                $type = 'WaseMember';
                $counter = '$members';
            } else {
                $type = 'WaseManager';
                $counter = '$managers';
            }
            /* Build and do the insert */
            $query = buildinsert($wasemanmem, $type);
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = false;
            if (!$ret = WaseSQL::doQuery($query)) {
                echo($type . '  insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
                exit();
            } else {
                eval($counter . '++;');
                if (!in_array($wassmanmem['email'], $calusers))
                    $calusers[] = $wassmanmem['email'];
            }
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = true;
        }
    }

    /* Now copy over the series */
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = true;
    $wassseriess = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassSeries WHERE calendarid=' . $wasscal['calendarid'] . $afterifs . ';');
    while ($wassseries = WaseSQL::doFetch($wassseriess)) {
        /* Copy over the data */
        $waseseries = $wassseries;
        /* Now fix up */
        $waseseries['calendarid'] = $wasecal['calendarid'];
        unset($waseseries['seriesid']);
        unset($waseseries['slotted']);
        $waseseries['remindmem'] = 1;
        $waseseries['notifymem'] = 1;
        $waseseries['apptmsg'] = $wassseries['appmsg'];
        unset($waseseries['appmsg']);
        $waseseriesl['APPTHING'] = 'appointment';
        $waseseries['APPTHINGS'] = 'appointments';
        $waseseriesl['NAMETHING'] = 'office hour';
        $waseseriesl['NAMETHINGS'] = 'office hours';
        /* Build and do the insert */
        $query = buildinsert($waseseries, 'WaseSeries');
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = false;
        if (!$ret = WaseSQL::doQuery($query)) {
            echo('WaseSeries insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
            exit();
        } else {
            $series++;
            if (!in_array($wassseries['email'], $calusers))
                $calusers[] = $wassseries['email'];
        }
        $waseseries['seriesid'] = WaseSQL::insert_id();

        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = true;
        /* Now copy over the periods that belong to series */
        $wassperiods = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassPeriod WHERE seriesid=' . $wassseries['seriesid'] . ';');
        while ($wassperiod = WaseSQL::doFetch($wassperiods)) {
            /* Copy over the data */
            $waseperiod = $wassperiod;
            /* Now fix up */
            $waseperiod['seriesid'] = $waseseries['seriesid'];
            unset($waseperiod['periodid']);
            unset($waseperiod['endtime']);
            $waseperiod['duration'] = WaseUtil::elapsedTime($wassperiod['starttime'], $wassperiod['endtime']);
            /* Build and do the insert */
            $query = buildinsert($waseperiod, 'WasePeriod');
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = false;
            if (!$ret = WaseSQL::doQuery($query)) {
                echo('WasePeriod insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
                exit();
            } else
                $periods++;
            $waseperiod['periodid'] = WaseSQL::insert_id();

            /* Now do the blocks that belong to this period */
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = true;
            $wassblocks = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassBlock WHERE periodid=' . $wassperiod['periodid'] . $afterif . ';');
            while ($wassblock = WaseSQL::doFetch($wassblocks)) {
                /* Copy over the data */
                $waseblock = $wassblock;
                /* Now fix up */
                if ($wassblock['slotted']) $waseblock['maxapps'] = 1;
                $waseblock['seriesid'] = $waseseries['seriesid'];
                $waseblock['periodid'] = $waseperiod['periodid'];
                $waseblock['calendarid'] = $wasecal['calendarid'];
                unset($waseblock['blockid']);
                $waseblock['notifymem'] = 1;
                $waseblock['remindmem'] = 1;
                unset($waseblock['slotted']);
                $waseblock['startdatetime'] = $wassblock['date'] . ' ' . $wassblock['starttime'];
                $waseblock['enddatetime'] = $wassblock['date'] . ' ' . $wassblock['endtime'];
                // Need to set slotsize to blocksize if a wass unslotted block
                if ($wassblock['slotted'] == 0)
                    $waseblock['slotsize'] = WaseUtil::elapsedDateTime($waseblock['startdatetime'], $waseblock['enddatetime']);
                unset($waseblock['starttime']);
                unset($waseblock['endtime']);
                unset($waseblock['date']);
                unset($waseblock['recurrence']);
                $waseblock['apptmsg'] = $waseblock['appmsg'];
                unset($waseblock['appmsg']);
                $waseblockl['APPTHING'] = 'appointment';
                $waseblock['APPTHINGS'] = 'appointments';
                $waseblock['NAMETHING'] = 'office hour';
                $waseblock['NAMETHINGS'] = 'office hours';
                /* Build and do the insert */
                $query = buildinsert($waseblock, 'WaseBlock');
                if ($_SESSION['ALT_HOST'])
                    $_SESSION['ALT_SQL'] = false;
                if (!$ret = WaseSQL::doQuery($query)) {
                    echo(' WaseBlock insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
                    exit();
                } else {
                    $blocks++;
                    if (!in_array($wassblock['email'], $calusers))
                        $calusers[] = $wassblock['email'];
                }
                $waseblock['blockid'] = WaseSQL::insert_id();
                /* Add in the appts */
                buildappts($waseblock, $wassblock);
                if ($_SESSION['ALT_HOST'])
                    $_SESSION['ALT_SQL'] = true;
            }
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = true;
        }

        /* Now add blocks that belong to this series, but not to any period */
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = true;
        $wassblocks = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassBlock WHERE seriesid = ' . $wassseries['seriesid'] . ' and periodid = 0' . $afterif . ';');
        while ($wassblock = WaseSQL::doFetch($wassblocks)) {
            /* Copy over the data */
            $waseblock = $wassblock;
            /* Now fix up */
            if ($wassblock['slotted']) $waseblock['maxapps'] = 1;
            $waseblock['seriesid'] = $waseseries['seriesid'];
            $waseblock['calendarid'] = $wasecal['calendarid'];
            unset($waseblock['blockid']);
            $waseblock['notifymem'] = 1;
            $waseblock['remindmem'] = 1;
            unset($waseblock['slotted']);
            $waseblock['startdatetime'] = $wassblock['date'] . ' ' . $wassblock['starttime'];
            $waseblock['enddatetime'] = $wassblock['date'] . ' ' . $wassblock['endtime'];
            // Need to set slotsize to blocksize if a wass unslotted block
            if ($wassblock['slotted'] == 0)
                $waseblock['slotsize'] = WaseUtil::elapsedDateTime($waseblock['startdatetime'], $waseblock['enddatetime']);
            unset($waseblock['starttime']);
            unset($waseblock['endtime']);
            unset($waseblock['date']);
            unset($waseblock['recurrence']);
            $waseblock['apptmsg'] = $waseblock['appmsg'];
            unset($waseblock['appmsg']);
            $waseblockl['APPTHING'] = 'appointment';
            $waseblock['APPTHINGS'] = 'appointments';
            $waseblock['NAMETHING'] = 'office hour';
            $waseblock['NAMETHINGS'] = 'office hours';
            /* Build and do the insert */
            $query = buildinsert($waseblock, 'WaseBlock');
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = false;
            if (!$ret = WaseSQL::doQuery($query)) {
                echo('WaseBlock insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
                exit();
            } else {
                $blocks++;
                if (!in_array($wassblock['email'], $calusers))
                    $calusers[] = $wassblock['email'];
            }
            $waseblock['blockid'] = WaseSQL::insert_id();
            /* Add in the appts */
            buildappts($waseblock, $wassblock);
            if ($_SESSION['ALT_HOST'])
                $_SESSION['ALT_SQL'] = true;
        }
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = true;
    }

    /* Now add the blocks for this calendar that don't belong to any series */
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = true;

    $wassblocks = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassBlock WHERE seriesid = 0 and periodid = 0 and calendarid = ' . $wasscal['calendarid'] . $afterif . ';');
    while ($wassblock = WaseSQL::doFetch($wassblocks)) {
        /* Copy over the data */
        $waseblock = $wassblock;
        /* Now fix up */
        if ($wassblock['slotted']) $waseblock['maxapps'] = 1;
        $waseblock['calendarid'] = $wasecal['calendarid'];
        unset($waseblock['blockid']);
        $waseblock['notifymem'] = 1;
        $waseblock['remindmem'] = 1;
        unset($waseblock['slotted']);
        $waseblock['startdatetime'] = $wassblock['date'] . ' ' . $wassblock['starttime'];
        $waseblock['enddatetime'] = $wassblock['date'] . ' ' . $wassblock['endtime'];
        // Need to set slotsize to blocksize if a wass unslotted block
        if ($wassblock['slotted'] == 0)
            $waseblock['slotsize'] = WaseUtil::elapsedDateTime($waseblock['startdatetime'], $waseblock['enddatetime']);
        unset($waseblock['starttime']);
        unset($waseblock['endtime']);
        unset($waseblock['date']);
        unset($waseblock['recurrence']);
        $waseblock['apptmsg'] = $waseblock['appmsg'];
        unset($waseblock['appmsg']);
        $waseblockl['APPTHING'] = 'appointment';
        $waseblock['APPTHINGS'] = 'appointments';
        $waseblock['NAMETHING'] = 'office hour';
        $waseblock['NAMETHINGS'] = 'office hours';
        /* Build and do the insert */
        $query = buildinsert($waseblock, 'WaseBlock');
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = false;
        if (!$ret = WaseSQL::doQuery($query)) {
            echo('WaseBlock insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
            exit();
        } else {
            $blocks++;
            if (!in_array($wassblock['email'], $calusers))
                $calusers[] = $wassblock['email'];
        }
        $waseblock['blockid'] = WaseSQL::insert_id();
        /* Add in the appts */
        buildappts($waseblock, $wassblock);
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = true;
    }

    // Save date/time of last update in wass
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = false;
    WasePrefs::savePref('system', 'wass2wase ' . $wasscal['calendarid'], date('Y-m-d H:i:s'), 'system');
    if ($migrated)
        echo '<p> WASS calendar ' . $wasscal['calendarid'] . ' updated on ' . date('Y-m-d H:i:s') . '</p>';
    else
        echo '<p> WASS calendar ' . $wasscal['calendarid'] . ' migrated on ' . date('Y-m-d H:i:s') . '</p>';

    // Switch to alternate
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = true;
}


/* Let em know */
echo $cals . ' calendars, ' . $managers . ' managers, ' . $members . ' members, ' . $series . ' series, ' . $periods . ' periods, ' . $blocks . ' blocks, ' . $appts . ' appointments migrated.<br /><br />';

/* Now simply copy over the remaining tables (unless copying just one calendar) */
if (!$calid) {
    // copytable('Prefs');
    // copytable('AcCal');
    // copytable('NonGrata');
    // copytable('DidYouKnow');
    // copytable('User');
}

// Save date/time of last update in wase
if ($_SESSION['ALT_HOST'])
    $_SESSION['ALT_SQL'] = false;


/* All done */
echo 'All Done. ';

// Build WASE URL
$waseurl = "https://wase.princeton.edu";
if ($institution = @$_SERVER['INSTITUTION'])
    $waseurl .= "/" . $institution;

// Let users know
if ($notify == 'yes') {
    // First, notify appointment holders
    $appc = 0;
    $calc = 0;
    foreach ($appusers as $appuser) {

        WaseUtil::Mailer($appuser, 'Migration of WASS calendar to WASE',
            'The WASS calendar owned by user ' . $savecal['userid'] . '(' . $savecal['name'] . ') with title "' . $savecal['title'] . '" has been migrated from the WASS to the WASE system. ' .
            'You appear to have one or more appointments on this calendar.  Please use the WASE system, rather than WASS, to modify. cancel or ' .
            'to make additional appointments on this calendar.  WASE is available at "' . $waseurl . '". ' .
            'Please contact ' . WaseUtil::getParm('CONTACTEMAIL') . ' if you have any questions.' .
            "\r\n" .
            'The WASE support staff.'
        );
        $appc++;
    }
    foreach ($calusers as $caluser) {
        if (!in_array($caluser, $appusers)) {

            WaseUtil::Mailer($caluser, 'Migration of WASS calendar to WASE',
                'The WASS calendar owned by user ' . $savecal['userid'] . '(' . $savecal['name'] . ') with title "' . $savecal['title'] . '" has been migrated from the WASS to the WASE system. ' .
                'Please use the WASE system, rather than WASS, to modify. cancel or ' .
                'to make additional appointments,  or add, modify or delete blocks on this calendar.  WASE is available at "' . $waseurl . '". ' .
                'Please contact ' . WaseUtil::getParm('CONTACTEMAIL') . ' if you have any questions.' .
                "\r\n" .
                'The WASE support staff.'
            );
            $calc++;
        }
    }
    if ($appc) echo '<p>' . $appc . ' users with future appointments were notified via email. ';
    if ($calc) {
        $start = ($appc) ? '<p>An additional ' : '<p>  ';
        echo $start . $calc . ' users who own, or manage, or member a calendar were notified by email. ';
    }
    if ($appc || $calc)
        echo '</p>';
}
echo "<p>Execution completed. </p>";

exit();


function copytable($table)
{
    /* We cannot do a simple SQL copy because we need to convert values to utf8 */

    /* Read in all of the old values */
    if ($_SESSION['ALT_HOST']) $_SESSION['ALT_SQL'] = true;
    $wassrow = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.' . $table . ';');
    /* Write out all of the new values */
    $entries = 0;
    while ($row = WaseSQL::doFetch($wassrow)) {
        /* Build and do the insert */
        $query = buildinsert($row, 'Wase' . $table);
        if ($_SESSION['ALT_HOST']) $_SESSION['ALT_SQL'] = false;
        if (!$ret = WaseSQL::doQuery($query)) {
            echo('Wase' . $table . ' insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
            exit();
        } else
            $entries++;
        if ($_SESSION['ALT_HOST']) $_SESSION['ALT_SQL'] = true;
    }
    echo $entries . ' rows copied into Wase' . $table . '<br /><br />';
}


function buildappts($waseblock, $wassblock)
{
    global $appts, $appusers, $afterif;
    if ($_SESSION['ALT_HOST'])
        $_SESSION['ALT_SQL'] = true;
    $wassappts = WaseSQL::doQuery('SELECT * FROM ' . $_SESSION['ALT_DATABASE'] . '.wassAppointment WHERE blockid = ' . $wassblock['blockid'] . $afterif . ';');
    while ($wassappt = WaseSQL::doFetch($wassappts)) {
        // If this appointment spans multiple slots, then we need to build multiple WASE appointments.
        if ($wassblock['slotted'] == 0)
            $slotsize = WaseUtil::elapsedTime($wassblock['starttime'], $wassblock['endtime']);
        else
            $slotsize = $wassblock['slotsize'];
        // Check for bad slotsize
        if ($slotsize == 0)
            die('Zero slotsize encountered for WASS block: ' . print_r($wassblock, true));
        $start = WaseUtil::timeToMin($wassappt['starttime']);
        $end = WaseUtil::timeToMin($wassappt['endtime']);
        // Check for bad appt times
        if (($duration = $end - $start) <= 0)
            die('Zero slotsize encountered for WASS appt: ' . print_r($wassappt, true));
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = false;

        // WASE now supports appointments that span a slot boundary, so we just reset the slotsize to
        // the length of the appointment.
        $slotsize = $end - $start;

        for ($time = $start; $time < $end; $time += $slotsize) {
            /* Copy over the data */
            $waseappt = $wassappt;
            /* Now fix up */
            unset($waseappt['appointmentid']);
            $waseappt['calendarid'] = $waseblock['calendarid'];
            $waseappt['blockid'] = $waseblock['blockid'];
            $waseappt['startdatetime'] = $wassblock['date'] . ' ' . WaseUtil::minToTime($time) . ':00';
            $waseappt['enddatetime'] = $wassblock['date'] . ' ' . WaseUtil::minToTime($time + $slotsize) . ':00';
            unset($waseappt['starttime']);
            unset($waseappt['endtime']);
            unset($waseappt['date']);
            /* Build and do the insert */
            $query = buildinsert($waseappt, 'WaseAppointment');
            if (!$ret = WaseSQL::doQuery($query)) {
                die('WaseAppointment insert failed: ' . $query . ': ' . WaseSQL::error() . '<br />');
            } else {
                $appts++;
                // Add user to-be-notified if it is a "future" appointment
                if (!WaseUtil::beforeNow($wassappt['date'] . ' ' . $wassappt['endtime'])) {
                    if (!in_array($wassappt['email'], $appusers))
                        $appusers[] = $wassappt['email'];
                }
            }
            // $waseappt['appointmentid'] = WaseSQL::insert_id();
            unset($waseappt);
        }
        if ($_SESSION['ALT_HOST'])
            $_SESSION['ALT_SQL'] = true;
    }
}

function buildinsert($arr, $table)
{

    /* Build the insert string. */
    $vars = '';
    $values = '';
    foreach ($arr as $key => $value) {
        if ($key) {
            if ($vars)
                $vars .= ',';
            $vars .= '`' . $key . '`';
            if ($values)
                $values .= ',';
            if ($value) {
                if (is_string($value))
                    $value = utf8_encode($value);
                // Quote if not a number or a numeric string
                if (!(is_numeric($value)))
                    $value = WaseSQL::sqlSafe($value);
            } else
                $value = "''";
            $values .= $value;
        }
    }
    return 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.' . $table . ' (' . $vars . ') VALUES (' . $values . ')';
}


?>