<?php
/*
Copyright 2006, 2008, 2012 The Trustees of Princeton University.

For licensing terms, see the license.txt file in the docs directory.

Written by: Serge J. Goldstein, serge@princeton.edu.
            Kelly D. Cole, kellyc@princeton.edu
			Jill Moraca, jmoraca@princeton.edu
	
	
This code in invoked by an HTTP POST.  It returns a CSV file of appointments matching the posted search criteria.	   
*/
?>

<?php 

  
//Criteria fields: txtStartDate, txtEndDate, txtStartTime, txtEndTime, txtApptName=apptwithorfor, txtApptMadeBy=apptby


//  We no longer use this function
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

/* Make sure authenticated */
// Get our directory class
//        $directory = WaseDirectoryFactory::getDirectory();
// $directory->authenticate();


/* Create selection criteria to get the appointment list */

WaseUtil::Init();

/* Start session support */
@session_start();

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

/* Make sure authenticated */
$directory->authenticate();


/* Save logged-in userid in a convenient place */
$userid = $_SESSION['authid'];

/* Init the SQL query string that will return the appointments. */
$query = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND (';

// If guest, just use authid
if ($_SESSION['authtype'] == 'guest')
    $query .= '(userid = ' . WaseSQL::sqlSafe($userid) . ')';
else {
    // We start by restricting appointments to those owned by the user, or made with the user, or made with someone whose calendar the user manages.

    // 1) Appointments owned by user
    $query .= '(userid = ' . WaseSQL::sqlSafe($userid);
    // 2) Appointments made with the user
    $query .= ' OR (blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))';
    // Add in owned calendars
    if ($owned = WaseCalendar::arrayOwnedCalendarids($userid))
        $query .= ' OR calendarid IN (' . implode(',', $owned) . ') ';
    // 3) Appointments made on calendars managed by the user.
    if ($managed = WaseManager::arrayActiveManagers($userid))
        $query .= ' OR calendarid IN (' . implode(',', $managed) . ')) ';
    else
        $query .= ') ';
}


/* Add in additional selectors if specified */
if ($startdate = WaseSQL::sqlDate(trim($_REQUEST['txtStartDate']))) {
  $query .= ' AND date(startdatetime) >= ' . WaseSQL::sqlSafe($startdate);
}
if ($enddate = WaseSQL::sqlDate(trim($_REQUEST['txtEndDate']))) {
  $query .= ' AND date(enddatetime) <= ' . WaseSQL::sqlSafe($enddate);
}
if ($starttime = WaseSQL::sqlDate(trim($_REQUEST['txtStartTime']))) {
  $query .= ' AND time(startdatetime) >= ' . WaseSQL::sqlSafe($starttime);
}
if ($endtime = WaseSQL::sqlDate(trim($_REQUEST['txtEndTime']))) {
  $query .= ' AND time(enddatetime) <= ' . WaseSQL::sqlSafe($endtime);
}
if ($apptby = trim($_REQUEST['txtApptMadeBy'])) {
  $query .= ' AND madeby = ' . WaseSQL::sqlSafe($apptby);
}
if ($apptwithorfor = trim($_REQUEST['txtApptName'])) {  /* Appointments made by apptwithorfor user with logged-in user or appointments made by logged-in user with apptwithorfor user */
  $madebyapptwithorfor = '(userid = ' . WaseSQL::sqlSafe($apptwithorfor) . ' OR name LIKE ' . WaseSQL::sqlSafe('%'.$apptwithorfor.'%') . ')';
  $madebyloggedin = '(userid = ' . WaseSQL::sqlSafe($userid) . ' OR name LIKE ' . WaseSQL::sqlSafe('%'.$userid.'%') . ')';
  $madewithapptwithorfor = '(blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($apptwithorfor) . '))';
  $madewithloggedin = '(blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))';

    // $query .= ' AND ((' . $madebyapptwithorfor . ' AND ' . $madewithloggedin . ') OR (' . $madebyloggedin . ' AND ' . $madewithapptwithorfor . '))';

    $query .= ' AND (' . $madebyapptwithorfor . ' OR ' . $madewithapptwithorfor . ')';
}


/* Add in the ordering */
$query .= ')) ORDER BY startdatetime';

/* Now get list of matching appointments */
$apps = new WaseList($query,'Appointment');
/* Save viewable matching appointments in an array. */ 
foreach ($apps as $appt) {
  if ($_SESSION['authid'] && !$appt->isViewable($_SESSION['authtype'],$_SESSION['authid']))
	  continue;
  $block = new WaseBlock('load',array('blockid' => $appt->blockid));
    $searchresult['ApptWith' . WaseUtil::getParm('NETID')] = $block->userid;
    $searchresult['ApptWithName'] = $block->name;
  $searchresult['BlockTitle'] = $block->title;
  $searchresult['Location'] = $block->location;
  $searchresult['StartDate'] = WaseUtil::usDate(substr($appt->startdatetime,0,10));
  $searchresult['StartTime'] = WaseUtil::AmPm(substr($appt->startdatetime,11,8));
  $searchresult['EndDate'] = WaseUtil::usDate(substr($appt->enddatetime,0,10));
  $searchresult['EndTime'] = WaseUtil::AmPm(substr($appt->enddatetime,11,8));
    $searchresult['ApptFor' . WaseUtil::getParm('NETID')] = $appt->userid;
    $searchresult['ApptForName'] = $appt->name;
  $searchresult['ApptForEmail'] = $appt->email;
  $searchresult['ApptForTxtMsgEmail'] = $appt->textemail;
  $searchresult['ApptForPhone'] = $appt->phone;
  $searchresult['Purpose'] = $appt->purpose;
  $searchresult['WhenMade'] = $appt->whenmade;
  $searchresult['LastChanged'] = $appt->lastchange;

    // If local extension exists, go add local fields
    if (class_exists('WaseLocal')) {
        $func = 'exportappts';
        if ($inst = @$_SERVER['INSTITUTION'])
            $func = $inst . '_' . $func;
        if (method_exists('WaseLocal', $func)) {
            WaseLocal::$func($block, $appt, $searchresult);
        }
    }


  $searchresults[] = $searchresult;
}
	
/* Now return the CSV file */	
WaseUtil::writecsvfile($searchresults);


/* That's all folks */  


?>