<?php

/**
 * This script maintains the daytype calendar in WASE.
 *
 * The WASE daytype calendar classifies days of the year into one
 * of a mutually exclusive set of day types, defined in the parameters file.
 * When creating recurring blocks, users can specify on what types of days
 * they do or do not want blocks to be created.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */

// A list of the colors to use to represent each day type on the daytype setup utility.
define("DAYCOLORS", "#FF9900,#FF00FF,#9999FF,#EDB579,#FFFF00,#FF4D4D,#FFFFFF,#66CCFF,#0099FF,#99FF99,#009933");

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';  $infmsg = '';

/* Make sure user is authorized */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('CALENDAR_USERS'));


/* Create form action variable */
// $editFormAction = $_SERVER['SCRIPT_NAME'];
$editFormAction = 'calendar.php';
// We need to insert the institution into the script name
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . $_SERVER['QUERY_STRING'];
}


/* Determine the last date currently in the calendar */
$lastdate = WaseSQL::doFetch(WaseSQL::doQuery('SELECT `date` FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAcCal ORDER BY  `date` DESC LIMIT 1'));
if ($lastdate)
    $lastdate = WaseUtil::usDate($lastdate['date']);
else
    $lastdate = "0/0/0";


/* 
1) Determine if we are being called to exit, to set the year, to process form data, or just to display the calendar.
*/
$action = "display";
if (isset($_POST['submit'])) {
	if ($_POST['submit'] == "Exit") {
		die('bye');
	}
	elseif ($_POST['submit'] == "Reset Year") {
		header("Location: calendar.php?secret=" . WaseUtil::getParm('PASS') . "&year=" . $_POST["year"]);
		exit();
	}
	else {
		$action = "process";
	}
}

/*
2) Determine desired year, from form data or using current year.
*/


if (isset($_REQUEST['year'])) {
	$yearchar = trim($_REQUEST['year']);
}
else {
	$date = getdate();
	$yearchar = $date["year"];
}

$yearint = (int) $yearchar;
/* Compute value of year as a Unix timestamp */
$yearunix = mktime(3, 0, 0, 1, 1, $yearint);
$dayinseconds = 60*60*24;

/* 
3) If being called to process form, store the form data.

*/
if ($action == "process") {
	$message = '<em>The specified calendar data has been succesfully saved.</em><br />';
	$further = "further ";
	/* Save data out to the database */
		
	$newday = $yearunix - $dayinseconds;  /* Start at last day of previous year */
	for ($dayi = 1; $dayi <= 366; $dayi++) {  /* Leap years have 366 days */
		$newday += $dayinseconds;         /* Move to the next day of the year */
		$thisday = "day" . (int) $dayi;   /* Compute form variable name */
		if (isset($_POST["$thisday"])){         /* If form data available */
			$dayval = $_POST["$thisday"];       /* Get day type */
			$newdate = getdate($newday);     	/* Get the new date as an array */
			$monthofyear = $newdate["mon"];    	/* Get the month as a number */
			if (strlen($monthofyear) == 1) $monthofyear = "0" . $monthofyear; /* Make it 2 chars long */
			$dayofmonth = $newdate["mday"];    /* Get the month day as a number */
			if (strlen($dayofmonth) == 1) $dayofmonth = "0" . $dayofmonth; /* Make it 2 chars long */
			$datechar = $yearchar . '-' . $monthofyear . "-" . $dayofmonth;  /* Format the date */
			
			/* See if we already have an entry for the given date */
			$oldentry = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAcCal WHERE date=' . WaseSQL::sqlSafe($datechar) . ';'));
			if ($oldentry) {
				/* If so, update it if necessary */
				if ($oldentry['daytypes'] != $dayval) {
					$result = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAcCal SET daytypes=' . WaseSQL::sqlSafe($dayval)  . ' WHERE date=' .  WaseSQL::sqlSafe($datechar) . ';'); 
					if (!$result) die(WaseSQL::error());
				}
			}
			/* If no old entry */
			else {
				/* Add an entry */
				$result = WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseAcCal (date, daytypes) VALUES (' . WaseSQL::sqlSafe($datechar) . ',' . WaseSQL::sqlSafe($dayval) . ');');
				if (!$result) die(WaseSQL::error()); 
			}
		}
	}		
}
else {
	$message = "";
	$further = "";
}
?>

<html>
<head>
<title>Set Calendar Day Types</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="admin.css">
</head>
<body >
<div id="divHeaderLogo"></div> 
<h1 align="center">Set "<?php echo $_SERVER['INSTITUTION'];?>" Calendar Day-Types for the year <?php echo $yearchar; ?></h1>
<?php if ($message <> "") echo "<p>$message</p>";?>
<blockquote>
  <p>Use this form to make any <?php echo $further ?>changes to the day-types
    for each day for the year . If you do not want to make any <?php echo $further ?>changes,
    click on any of the <em>Exit </em>buttons.
    Otherwise, make your changes and click on any of the <em>Submit </em>buttons.</p>
  <p>To make a change to a day-type, find the relevant day on the calendar, then
    check a day-type for that day. You may make changes to as many
    days as you like prior to clicking on the <em>Submit </em>button.</p>
	<p>To change the year, enter it as a four-digit number in the box below, then click <em>Reset Year</em></p>
	<p>&nbsp;</p>
	<p>The last entered date in the calendar is: <?php echo $lastdate ?></p>
</blockquote>
<p>&nbsp; </p>
<p>&nbsp;</p>

<form name="MakeCalendar" id="MakeCalendar" method="post" action="<?php echo $editFormAction; ?>">
&nbsp; &nbsp; &nbsp; &nbsp;
<input type="text" name="year" value="<?php echo $yearchar;?>" /> 
<input type="submit" class="submit" name="submit" value="Reset Year" />
&nbsp;&nbsp;&nbsp;&nbsp;   
<input type="submit" class="submit" name="submit" value="Exit" />
<p></p>


<?php
/* 4) Create the calendar display from the calendar table, 
	setting the day types from any information existing in the calendar table. 
*/


/* Cycle through days of the year, setting up the form fields */
$curmonth = "";
$newmonth = "";
$newday = $yearunix - $dayinseconds;  /* Start at last day of previous year */

/* Put daytypes into an array */
$daytypes = WaseAcCal::getAllDaytypes();

/* Associate a color with each daytype */
$daycolors = explode(',',DAYCOLORS);

for ($i = 1; $i <= 366; $i++) {		  /* Leap years have 366 days */
	$newday += $dayinseconds;          /* Move to the next day of the year */
	$newdate = getdate($newday);       /* Get the new date as an array */
	if ($newdate['year'] <> $yearchar) break;  /* Get out if day=366 and not a leap year */
	$newmonth = $newdate["month"];     /* Get the month as a character string */
	$monthofyear = $newdate["mon"];    /* Get the month as a number */
	if (strlen($monthofyear) == 1) $monthofyear = "0" . $monthofyear; /* Make it 2 chars long */
	$dayofweek = $newdate["weekday"];  /* Get the day of week as a character string */
	$dayofmonth = $newdate["mday"];    /* Get the month as a number */
	if (strlen($dayofmonth) == 1) $dayofmonth = "0" . $dayofmonth; /* Make it 2 chars long */
	$datechar = $yearchar . '-' . $monthofyear . "-" . $dayofmonth;  /* Format the date */
	
    /* Read any data available about this date */
	$datetype = WaseAcCal::getDaytype($datechar);
	$datecolor = $daycolors[array_search($datetype,$daytypes)%count($daycolors)];
	
	if ($newmonth != $curmonth) {      /* If it is a new month */
		if ($curmonth != "") {              /* If not first month, end previous table */
			echo "</table><br />\r\n";
			echo '<p align="center"><input type="submit" class="submit" name="submit" value="Submit" />' . "\r\n";  
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . "\r\n";       
			echo '<input type="submit" class="submit" name="submit" value="Exit" /><br \><br \>' . "\r\n"; 
		}
		$curmonth = $newmonth;
	
		echo '<table width="75%" align="center" border=1><tr><th align="center" colspan="7">';
		echo $curmonth . ' ' . $yearchar . '</th></tr>' . "\r\n" . '<tr align="center"><th width="14%">Su</th>' ."\r\n";
        echo '<th width="14%">Mo</th><th width="14%">Tu</th><th width="14%">We</th>';
		echo '<th width="14%">Th</th><th width="14%">Fr</th><th width="14%">Sa</th></tr>';
		echo "\r\n" . '<tr align="left">' . "\r\n";
		for ($day = 0;  $day < (int) $newdate["wday"]; $day++) {  /* If month does not start on Sunday */	
			echo '<td>&nbsp;</td>' . "\r\n";                                  /* Skip non-days */
		}
	}
	echo "\r\n<td";
	echo ' bgcolor="' . $datecolor . '">';
	echo $dayofmonth . "<br />\r\n";
	
	
	/* Display daytypes as checkboxes */
	for ($ai = 0; $ai < count($daytypes); $ai++) {
		echo '<input type=radio name="day' . (int) $i . '" value="' . $daytypes[$ai] . '"';
		if ($daytypes[$ai] == $datetype) 
			echo ' checked="checked"';
		echo '>' .  $daytypes[$ai] . "<br />\r\n";
	}
	echo "</td>\r\n";
	if ($dayofweek == "Saturday")  { /* If last day of the week */
		echo  '</tr>' . "\r\n\r\n" . '<tr align="left">'; /* Skip to the next row */
	}
}
echo '</table>';

?>
<p>&nbsp;</p>
<p align="center">
<input type="submit" class="submit" name="submit" value="Submit" />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;        
<input type="submit" class="submit" name="submit" value="Exit" />
</p>

</form>
</body>
</html>
