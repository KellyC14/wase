<?php
/*
Copyright 2006, 2008, 2013 The Trustees of Princeton University.

For licensing terms, see the license.txt file in the docs directory.

Written by: Serge J. Goldstein, serge@princeton.edu.
            Kelly D. Cole, kellyc@princeton.eduduration
			Jill Moraca, jmoraca@princeton.edu
		   
*/

/*
    This routine reads a WASS database and writes out a set of wass2wase calls to
    migrate the calendars to WASE.
*/


// define('FILE','/wasss/home/wasss/www/wase/public/admin/wasscals.script');
define("FILE", '/tmp/wasscals.script');
define("CURL", 'curl "https://waseqa.princeton.edu/princeton/admin/wass2wase.php?secret=7eizje8zn&notify=yes&calid=');

/* Handle loading of classes. */
require_once('autoload.include.php');

/* Start session support */
session_destroy();
session_start();

// Init date and such
WaseUtil::init();

/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth();

// Read in optional cutoff date for blocks.
if (!$cutoff = $_REQUEST['cutoff'])
    die('You must provide a cutoff date as cutoff=yyyy-mm-dd');

// Start by working with the WASE tables.
$_SESSION['ALT_SQL'] = false;


// Try to open the file for output
// if ($handle = fopen(FILE,'w') == false)
//    die('Unable to fopen file ' . FILE . ' for output.');


/* Init counters */
$cals = 0;

// Prepare the alternate (WASS) database parameters.  Set $_SESSION['ALT_HOST'] to '' if both databases on same host.
if (!$_SESSION['ALT_HOST'] = WaseUtil::getParm('WASS_HOST'))
    die("WASS_HOST not set in system parameters file.");


$_SESSION['ALT_DATABASE'] = WaseUtil::getParm('WASS_DATABASE');
$_SESSION['ALT_USER'] = WaseUtil::getParm('WASS_USER');
$_SESSION['ALT_PASS'] = WaseUtil::getParm('WASS_PASS');
$_SESSION['ALT_CHARSET'] = WaseUtil::getParm('WASS_CHARSET');


// Switch to alternate  
if ($_SESSION['ALT_HOST'])
    $_SESSION['ALT_SQL'] = true;

/* Build a list of all wass calendars with block on them that postdate the cutoff. */
$wasscals = WaseSQL::doQuery('SELECT DISTINCT calendarid FROM ' . $_SESSION['ALT_DATABASE'] . '.wassBlock where `date` >= "' . $cutoff . '";');


// Sanity controls
$max = 4000;
$calsdone = 0;
$lines = '';

/* Run through the list and write out the wase calendar entries */
while ($wasscal = WaseSQL::doFetch($wasscals)) {
    $calsdone++;
    if ($calsdone > $max)
        die("max limit of $max reached");

    // Write out the curl command.
    // if (!$bytes = fwrite($handle,CURL . $wasscal['calendarid'] . '"' . "\n"));
    //     die('Unable to fwrite ' . CURL . $wasscal['calendarid'] . '"'  . "\n");

    $lines .= CURL . $wasscal['calendarid'] . '"' . "\n";

}
echo "$calsdone calendars written.";

// fclose($handle);

WaseUtil::Mailer('serge@princeton.edu', 'lines', $lines);

?>