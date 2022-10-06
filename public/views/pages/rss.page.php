<?php
/**
 * This script generates iCal streams for synchronizing apps/blocks/calendars with local calendars.
 * 
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */

/* We no longer use this autoloader. */
function __oldautoload($class)
{
    /* Set path to WaseParms and WaseLocal */
    // / if (($class == 'WaseParms') || ($class == 'WaseLocal'))
    if ($class == 'WaseParms')
        $parmspath = '../../../config/';
    else
        $parmspath = '../../../models/classes/';
        
        /* Now load the class */
    if ($class != 'WaseLocal')
        require_once ($parmspath . $class . '.php');
    else
        @include_once ($parmspath . $class . '.php');
}

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Start session support */
@session_start();

/* Authentication:  we treat the user initiating the request as authenticated. */
$_SESSION['authtype'] = 'user';
$_SESSION['authid'] = trim($_REQUEST['authid']);

/* Extract the GET (or POST) action arguments. */
if (!$action = strtoupper(trim($_REQUEST['action'])))
	$action='LISTAPPS';
	
 	
/* Now select on the action argument */
switch($action) {
	
	case 'LISTAPPS':
		/* Return an rss stream of pending appointments for a user */
		
		/* Get the args */
		$userid = $_SESSION['authid'];
		$startdate = trim($_REQUEST['startdate']);
		$enddate = trim($_REQUEST['enddate']);
		$starttime = trim($_REQUEST['starttime']);
		$endtime = trim($_REQUEST['endtime']);
		
		if (!$startdate)
			$startdate = date('Y-m-d');
		
		/* Get the appointment list */
		$ret = WaseRss::listApps($userid,$startdate,$enddate,$starttime,$endtime);
		
		/* Return the list as an Rss feed */
		
		header("Content-Type: application/rss+xml");
		echo $ret;
		
		break;
	
	case 'LISTBLOCKS':
		/* Return an rss stream of blocks for a calendar */
		
		/* Get the args */
		$calendarid = trim($_REQUEST['calid']);
		$startdate = trim($_REQUEST['startdate']);
        $icalpass = trim($_REQUEST['dbr']);
		
		/* Return nothing if calid not specified */
		if ($calendarid) {
		    // Validate password, if required
		    $cal = new WaseCalendar('load',array('calendarid'=>$calendarid));
		    if ($cal->icalpass && ($cal->icalpass != $icalpass))
		        break;
		    
			/* Get the blocks list in RSS format */
			$ret = WaseRss::listBlocks($calendarid,$startdate);
		}
		/* Return the list as an Rss feed */
		
		header("Content-Type: application/rss+xml");
		echo $ret;
		
		break;
									
	
	default:
		WaseUtil::Error(8,array($action),'HTMLFORMAT,DIE');
		break;


 
}


exit(); 

?>