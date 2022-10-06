<?php
/**
 * 
 * This script allows an administrator to get a list of users who have appointments in the system
 * whose start date is greater than or equal to a specified, or the current, date.
 *
 * 
 * The script can be passed a "date" GET argument.  If not argument is passwed, it uses the current date.
 * The script can also be passed an "attr" argument, which it will use to classify users based on the specified (ldap)
 * attribute.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */


/* Handle loading of classes. */
require_once('autoload.include.php');


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();
 
/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';  $infmsg = '';

/* Make sure password was specified */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('LIST_USERS'));

// Get date argument, if any
if (!$date = $_REQUEST['date'])
    $date = date('Y-m-d');
else 
    $date = WaseSQL::sqlDate($date);

// Determine which LDAP attribute we will use to classify the owners.
if (!$attr = $_REQUEST['attr'])
    $attr = 'status';

// Determine if we have LDAP
$ldap=0;
if (WaseUtil::getParm('LDAP'))
    $ldap=1;


/* Get list of active users */
$users = WaseSQL::doQuery('SELECT DISTINCT userid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE date(startdatetime) >= ' . WaseSQL::sqlSafe($date));

$attrtab = array();

/* Now list them */

// Set up the html form
?>
<!DOCTYPE html>
<html>
<head>
<?php $title = 'WASE Active Owners'; if ($institution = $_SERVER['INSTITUTION']) $title .= ' for ' . $institution;?>
<title><?php echo $title;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
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
<h1><?php echo 'WASE Active Appointment Makers'; if ($institution = $_SERVER['INSTITUTION']) echo ' for "' . $institution . '"';?></h1>
<?php if (!$ldap) {
    $note = "Note: invoke this script with a 'date=yyyy-mm-dd' argument to change the minimum appointment start date"; ?>
    <h2>LDAP not supported; using  <?php 
    }
    else {
    $note = "Note: invoke this script with an 'attr=value' GET argument to change the classifying attribute, and/or 'date=yyyy-mm-dd' to change the minimum appointment start date'"?>
    <h2>Using '<?php echo $attr?>' as classifying attribute, 
    <?php }
     echo $date?> as the minimum appointment start date.</h2>
     <?php echo $note;?>
.<br /><br />
<table border="1"><tr><td>Userid</td>
<?php if ($ldap) { echo "<td>" . $attr . "</td>"; }?>
</tr>
<?php 
$sumtab = array(); $count = 0;
// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();
while ($user = WaseSQL::doFetch($users)) {
    $count++;
    if ($ldap) $value = $directory->getlDIR($user['userid'], $attr);
	echo '<tr><td>' . $user['userid'] . '</td>';  if ($ldap) echo '<td>' . $value . '&nbsp;</td>';  
	echo '</tr>';
	if ($value == '')
		$attrtab['None']++;
	else {
		$attrtab[$value]++;	
	}
}
echo '</table>';
if ($ldap) {
    echo '<p>Summary By ' . $attr . ':<br /><table border="1"><tr><td>Value</td><td>Count</td></tr>';
    foreach ($attrtab as $name=>$count) {
	   echo '<tr><td>' . $name . '</td><td>' . $count . '</td></tr>';
    }
}
?>
<h3><?php echo $count;?> active maker(s)</h3>
</table>
</body>
</html>
