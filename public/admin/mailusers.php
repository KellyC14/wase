<?php
/**
 * 
 * This script allows an administrator to send email to various subsets of WASE users (including all users). 
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */


/* Handle loading of classes. */
require_once('autoload.include.php');


/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();
 
/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';  $infmsg = '';

/* Make sure password was specified */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('EMAIL_USERS'));

// Get cutoff date, if any
if (!$cutoff = $_REQUEST['cutoff'])
    $cutoff = '2016-09-01';


/* If we are invoking ourselves to process the form. */
if ($_POST['submit'] == 'SEND'  || $_POST['submit'] == 'COUNT') {

		/* Send the emails */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
    $headers = "Reply-To: " . $_POST["replyto"] . "\r\n" . "Errors-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
		$message = stripslashes($_POST["text"]);
		$subject = stripslashes($_POST["subject"]);
		
		$recipients = array();
		$calown = 0; $calman = 0; $calmem = 0; $calby = 0; $calwith = 0; $calids = array();
		
		/* Get calendar owner emails */
		if (isset($_POST['ownerman']) || isset($_POST['ownernoman'])) {
			/* Get list of all calendars */
            $cals = WaseSQL::doQuery('SELECT distinct calendarid, email FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE date(startdatetime) >= "' . $cutoff . '"');
			while ($cal = WaseSQL::doFetch($cals)) {
				/* Get list of managers, if any */
			    $calids[] = $cal['calendarid'];
				$managers = WaseManager::arrayActiveManagers($cal['calendarid']);
				/* Now select based on form settings */
				if ((isset($_POST['ownerman']) && count($managers) != 0) || (isset($_POST['ownernoman']) && count($managers) == 0)) {
					if ($cal['email'] && !in_array($cal['email'], $recipients)) {
						$recipients[] = $cal['email'];
						$calown++;
					}
				}
			}	
		}

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

		/* Get managers */
		if (isset($_POST['man'])) {
			$mans = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager');
			while ($man = WaseSQL::doFetch($mans)) {
				if ($man['userid'] && in_array($man['calendarid'],$calids)) {
                    $manemail = $directory->getEmail($man['userid']);
					if ($manemail && !in_array($manemail, $recipients)) {
						$recipients[] = $manemail;
						$calman++;
					}
				}
			}	
		}

		/* Get members */
		if (isset($_POST['mem'])) {
		    $mems = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember');
		    while ($mem = WaseSQL::doFetch($mems)) {
		        if ($mem['userid']  && in_array($mem['calendarid'],$calids)) {
                    $mememail = $directory->getEmail($mem['userid']);
		            if ($mememail && !in_array($mememail, $recipients)) {
		                $recipients[] = $mememail;
		                $calmem++;
		            }
		        }
		    }
		}
		
		/* Get people with appointments */		

		$datenow = getdate();
		$today = WaseSQL::sqlDate($datenow["mon"] . "/" . $datenow["mday"] . '/' . $datenow["year"]);
		
		if (isset($_POST['by']) || isset($_POST['with']) || isset($_POST['anytime'])) {
		    if (isset($_POST['anytime'])) {
		        $apps = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment');
		        while ($app = WaseSQL::doFetch($apps)) {
		            if ($app['email'] && !in_array($app['email'],$recipients)) {
		                $recipients[] = $app['email'];
		                $calby++;
		            }
		        }
		    }
			$apps = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (date(startdatetime) >= "' . $today . '")');
			while ($app = WaseSQL::doFetch($apps)) {
				if (isset($_POST['by'])) {
					if ($app['email'] && !in_array($app['email'],$recipients)) {
						$recipients[] = $app['email'];
						$calby++;
					}
				}
				if (isset($_POST['with'])) {
					$cal = WaseCalendar::find($app['calendarid']);
					if ($cal) {
						$calemail = $cal['email'];
						if ($calemail && !in_array($calemail,$recipients)) {
							$recipients[] = $calemail;
							$calwith++;
						}
					}
				}
			}
		}
 
		/* Now send out the email */
		if ($_POST['submit'] == 'SEND') {		   
			foreach ($recipients as $recipient) {
                WaseUtil::Mailer($recipient, $subject, $message, $headers);
			}
			$wouldbe = '';
		}
		else
			$wouldbe = ' would be ';
		
		$total = $calown+$calman+$calmem+$calby+$calwith;

		$infmsg = "Email " . $wouldbe . "sent to:<br /> $calown Calendar owners, plus $calman Calendar managers, plus $calmem Calendar members, plus $calby Appointment makers, plus $calwith Appointment holders, $total in all. 
		<br />Note: an individual will only get one copy of the email, even if they meet multiple selected TO criteria.";

}

if ($_POST['submit'] == 'EXIT') {
	echo 'Bye.';
	exit;
}



/* We can send email to any of the following mutually-exclusive groups (or combination of groups; select all groups to send to everyone; no-one gets more than one copy of the email): 
	1. Calendar owners who do not have managers.
	2. Calendar owners who do have managers.
	3. Calendar managers.
	4. Calendar members.
	5. People who have appointments for today or later.
	6. People with whom appointments have been made for today or later.
*/


		
/* 
Prompt for email fields.
*/
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
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

.submit {
	width:100px; 
	height:100px;
	font-size:24px;
}

</style>
<title>Mail WASE Users (cutoff=<?php echo $cutoff .') '; if ($ins = $_SERVER['INSTITUTION']) echo "at $ins";?></title> 
</head>
<body>
<div id="divHeaderLogo"></div> 
<h1 align="center">Mail WASE Users (cutoff="<?php echo $cutoff . '") '; if ($ins = $_SERVER['INSTITUTION']) echo 'at "' . $ins .'"';?></h1>
<?php if ($infmsg) echo '<h3>'.$infmsg.'</h3>'?>
<blockquote>
  <p align="left"> Select the email reipients (TO field), enter a SUBJECT, enter a REPLY-TO email address, and enter the
    TEXT of the email.</p>
    <p align="left">Click COUNT to get a count of how many emails would be sent, SEND to send the emails, and EXIT to cancel sending.</p>
</blockquote>
<form action="mailusers.php" method="POST" name="MailUsers" id="MailUsers">

<blockquote>
  <p>TO (Check one or more boxes.  Note:  no individual will receive more than one copy of the email, even if they match multiple check boxes.):<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="ownernoman"/>
    Owners of calendars that do NOT have a manager.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="ownerman"/>
    Owners of calendars that DO have a manager.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="man"/>
    Calendar managers.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="mem"/>
    Calendar members.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="anytime"/>
    People who have made appointments for any time, past, present or future.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="by"/>
    People who have made appointments for today or any future day.<br />
    &nbsp;&nbsp;
    <input type="checkbox" name="with"/>
    People with whom appointments have been made for today or any future day.<br />
  </p>
  <p>SUBJECT: 
    <input name="subject" type="text" id="subject" size="100">
  </p>
  <p>REPLY-TO: 
    <input name="replyto" type="text" id="replyto" size="100">
  </p>
  <p>TEXT: (Note: most email clients can parse simple HTML if you wish to include it in the text.)</p>
  <p>
    <textarea name="text" cols="180" rows="20"><?php echo $message; ?></textarea>
  </p>
  <p>
    <input type="hidden" name="secret" value="<?php echo WaseUtil::getParm('PASS');?>" />
  </p>
   <p>
    <input type="hidden" name="cutoff" value="<?php echo $cutoff;?>" />
  </p>
</blockquote>
<DIV align="center">
  <blockquote>
    <p>
     <input type="submit" class="submit" name="submit" value="COUNT">
      &nbsp;&nbsp;&nbsp;
      <input type="submit" class="submit" name="submit" value="SEND">
      &nbsp;&nbsp;&nbsp;
        <input type=submit name="submit" value="EXIT">
    </p>
  </blockquote>
</DIV>
<blockquote>
  <p>
    </p>
</blockquote>
</form>
</body>
</html>