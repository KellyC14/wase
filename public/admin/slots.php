<?php
/**
 *
 * This script reports on used and unused slots for a given calendar owner userid and a given time range.
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
WaseUtil::CheckSSL();
 
/* Start session support */ 
session_start();  

/* Init error/inform messages */ 
$errmsg = '';  $infmsg = ''; 
 
// Define the netid and period for which a report is to be generated.
$userid = "";
$periodstart = WaseUtil::getParm('CURTERMSTART');
$periodend = WaseUtil::getParm('CURTERMEND');
$output = 'html';


/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth('SLOTS_USERS');

	 
/* Grab any data specified to override default settings. */
if (isset($_REQUEST["periodstart"]))
	$periodstart = $_REQUEST["periodstart"];
if (isset($_REQUEST["periodend"]))
    $periodend = $_REQUEST["periodend"];
if (isset($_REQUEST["userid"]))
    $userid = $_REQUEST["userid"];
if (isset($_REQUEST["output"]))
    $output = $_REQUEST["output"];

if (!($output=='csv' or  $output=='html'))
    die('output must be set to csv or html'); 

/* Issue syntax message if missing a userid */
if (!$userid) {?>
<html>
<head>
<style type="text/css">
body {
	background-color: #E6DDD0;
	font-family: Arial, Helvetica, sans-serif;
}

td {
	background-color: #CCCCCC;
}

#divHeaderLogo {
	height: 110px;
	background-position: top left;
 	background-image: url(../views/images/waselogo.gif);
	background-repeat: no-repeat;
	repeat: no-repeat;
	margin: 0;
}
</style>
</head>
<body>
<div id="divHeaderLogo"></div> 
<h1>Syntax of Slots Script</h1>
<p>
You must pass the Slots script a minum of 1 http parameter, the userid of the user for which you want a slot report. The following additional parameters can be passed:
<p>
userid=user  The userid of the paerson for which you want a slot report.</p>
<p>
periodstart=YYYYmmdd  The starting date of the slots (default is <?php echo WaseUtil::getParm('CURTERMSTART');?>)</p>
<p>
periodend=YYYYmmdd The ending date of the slots (default is <?php echo WaseUtil::getParm('CURTERMEND');?>)</p>
<p>
output=csv|html The format of the output, either CSV or HTML (default is html)</p>
<p>
For example: 
<pre>slots.php?user=someuserid&periodstart=20160101&periodend=20160331&output=csv</pre>
</p>
</body></html>
<?php
    exit(); }

/* Find all blocks for the given userid/start/end period */	  
$blocks = WaseBlock::listOrderedBlocks(
    array(
        array(
                'date(startdatetime),>=,AND',
                trim($periodstart)
            ),
        array(
                'date(enddatetime),<=,AND',
                trim($periodend)
            ),
        array(
                'userid,=,AND',
                $userid
            )
        ), 'ORDER BY `startdatetime`');
	

/* Init counters */
$cblocks=0;  $cslots=0;  $capps=0;

// Start the output
if ($output != 'csv') 
	echo '<html><head><style type="text/css">
body {
	background-color: #E6DDD0;
	font-family: Arial, Helvetica, sans-serif;
}

td {
	background-color: #CCCCCC;
}

#divHeaderLogo {
	height: 110px;
	background-position: top left;
 	background-image: url(../views/images/waselogo.gif);
	background-repeat: no-repeat;
	repeat: no-repeat;
	margin: 0;
}
</style></head><body><div id="divHeaderLogo"></div> <h1>Report on Slot Usage for ' . htmlspecialchars($userid) . '</h1><br />';
else
	$csv = 'Title'.','.'startdate'.','.'starttime'.','.'enddate'.','.'endtime'.','.'slots'.','.'apps'.','.'unused'."\r\n";

// Go through blocks, counting slots and apps

foreach ($blocks as $block) { 				
    // Compute total slots available
    $bslots = $block->slots();
    // Get all appoinments for this block
    $apps = WaseAppointment::listMatchingAppointments(array('blockid'=>$block->blockid));
    $bapps = $apps->entries();
    $unused = $bslots - $bapps;
    // Accumulate
    $cblocks++;
    $cslots += $bslots;
    $capps += $bapps;
    // Split out the date-time information
    list($sdate,$stime) = explode(' ',$block->startdatetime);
    list($sday,$smonth,$syear) = explode('-',$sdate);
    list($edate,$etime) = explode(' ',$block->enddatetime);
    list($eday,$emonth,$eyear) = explode('-',$edate);
    // Let the userid know
    if ($output != 'csv') {
        echo 'Block for ' . $sday.'/'.$smonth.'/'.$syear . ' ' . WaseUtil::AmPM($stime) . ' to ';
        if ($sdate != $edate) echo $eday.'/'.$emonth.'/'.$eyear . ' ';
        echo WaseUtil::AmPm($etime) . ': ' . $bslots . ' slots, ' . $bapps . ' apps = ' . $unused . ' unused slots' . '<br />';
    }
    else
        $csv .= $block->title.','.$sday.'/'.$smonth.'/'.$syear.','.$stime.','.$eday.'/'.$emonth.'/'.$eyear.','.$etime.','.$bslots.','.$bapps.','.$unused."\r\n";
    
	
}

 
/* Output summary information */
if ($output != 'csv') {
    echo '<br /><be />';
    echo '<h2>Totals</h2><p>';
    list($sday,$smonth,$syear) = explode('-',$periodstart);
    list($eday,$emonth,$eyear) = explode('-',$periodend);
    $unused = $cslots - $capps;
    echo 'For the period ' . $sday.'/'.$smonth.'/'.$syear . ' ' . ' to ' . $eday.'/'.$emonth.'/'.$eyear . ': ' . $cblocks . ' blocks, ' . $cslots . ' slots, ' . $capps . ' apps = ' . $unused . ' unused slots' . '</p>';   
}

    
// End the output
if ($output != 'csv')
	echo '</body></html>';
else {
	 header('Content-Type: text/csv; charset=utf-8');
     header('Content-Disposition: attachment; filename=wass.csv');
	 echo $csv;
}

/* All done */ 

exit();