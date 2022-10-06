<?php
/**
 * 
 * This script allows an administrator to re-assign calendar/block ownership.
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

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

/* Init error/inform messages */
$errmsg = '';  $infmsg = '';

/* Make sure password was specified */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('REOWN_USERS'));

// Init calendar table display
$calendars = '';

// If we are invoking ourselves to lookup a user.
if ($_POST['submit'] == 'LOOKUP') {
    if (!$_REQUEST['curcalowner'])
        $errmsg = 'You must specify the current owner ' . WaseUtil::getParm('NETID') . '.';
    else {
        $curcalowner = $_REQUEST['curcalowner'];

        if (!$_REQUEST['curcalid']) {
            // $errmsg = 'You must specify the calendar id.';
            // If no calid specified, display list of matching calids.
            $cals = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE userid = "' . $curcalowner . '"', 'Calendar');
            if (!$calcount = $cals->entries())
                $errmsg = 'No calendar(s) found for user "' . $curcalowner . '"';
            else {
                $calendars = "<br>User $curcalowner owns the following calendars: <br/><table><tr><td>calendar id</td><td>title</td><td>description</td></tr>";
                foreach ($cals as $cal) {
                    $calendars .= "<tr><td>$cal->calendarid</td><td>$cal->title</td><td>$cal->description</td></tr>";
                }
                $calendars .= "</table><br/>";
            }
        }
        else {
            $_SESSION['curcalid'] = $_REQUEST['curcalid'];

            // Make sure specified user owns the calendar.
            try {
                $cal = new WaseCalendar('load',array("calendarid"=>$_REQUEST['curcalid']));
            } catch (Exception $e) {
                $errmsg = "Calendar " . $_REQUEST['curcalid'] . ' not found.';
            }
            if (!$errmsg) {
                if ($cal->userid != $curcalowner)
                    $errmsg = 'Calendar ' . $_REQUEST['curcalid'] . ' is not owned by ' . $curcalowner;
                else
                    $_SESSION['curcalowner'] = $_REQUEST['curcalowner'];

                if (!$errmsg) {
                    if (!$newowner = $_REQUEST['newowner'])
                        $errmsg = 'You must specify a proposed new owner ' . WaseUtil::getParm('NETID') . '.';
                    else {
                        if (!$directory->useridCheck($newowner))
                            $errmsg = $newowner. ' not found in Directory.  Pleae check your spelling, then re-try.';
                        else {
                            // If proposed new owner is a manager/member, warn
                            if ($cal->isManager($newowner)) {
                                $infmsg = 'Proposed new owner "' . $newowner . '" is currently a manager of this calendar; they will be removed as a manager when ownership is changed.';
                                $_SESSION['ismanager'] = true;
                            }
                            elseif ($cal->isMember($newowner)) {
                                $infmsg = 'Proposed new owner "' . $newowner . '" is currently a member of this calendar; they will be removed as a member when ownership is changed.';
                                $_SESSION['ismember'] = true;
                            }
                            $_SESSION['newowner'] = $newowner;
                            $_SESSION['newname'] = $directory->getName($newowner);
                            $_SESSION['newemail'] = $directory->getEmail($newowner);
                            $_SESSION['newoffice'] = $directory->getOffice($newowner);
                            $_SESSION['founduser'] = true;
                        }
                    }
                }
            }
        }
    }
}

// If we are invoking ourselves to make the change.
elseif ($_POST['submit'] == 'CHANGE') {
    if (!$_SESSION['founduser'] ||
        !$_SESSION['curcalowner'] ||
        !$_SESSION['curcalid'] ||
        !$_SESSION['newowner']          
        )
        $errmsg = 'The calendar to be changed has not been found';
    else {
        // Try to update the calendar
       $query = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET ' . 
                    '`userid` = ' . WaseSQL::sqlSafe($_SESSION['newowner']) . ',' .
                    '`name` = ' . WaseSQL::sqlSafe($_SESSION['newname']) . ',' .
                    '`location` = ' . WaseSQL::sqlSafe($_SESSION['newoffice']) . ',' .
                    '`email` = ' . WaseSQL::sqlSafe($_SESSION['newemail']) .
                ' WHERE (`calendarid` = ' . WaseSQL::sqlSafe($_SESSION['curcalid']) . ' AND ' .
                         '`userid` = ' . WaseSQL::sqlSafe($_SESSION['curcalowner']) . ') LIMIT 1';
       if (!WaseSQL::doQuery($query)) 
           $errmsg = 'Unable to change calendar ownership: ' . WaseSQL::error();
       else {
           $rows = WaseSQL::affected_rows();
           if ($rows != 1)
               $errmsg = 'Unknown error:  The following SQL statement did not update the calendar: ' . $query;
           else {
               $infmsg = 'Calendar updated. ';
           }
           // Remove new owner as member or manager if required
           if ($_SESSION['ismanager']) {
               $manager = new WaseManager('load', array("calendarid"=>$_SESSION['curcalid'], "userid"=>$_SESSION['newowner']));
               $manager->delete(WaseConstants::DoNotSendCancellationNotice);
           }
           elseif ($_SESSION['ismember']) {
               $member = new WaseMember('load', array("calendarid"=>$_SESSION['curcalid'], "userid"=>$_SESSION['newowner'])); 
               $member->delete(WaseConstants::DoNotSendCancellationNotice);
           }
               
       }
       
       
       // Now try to update the blocks
       if (!$errmsg) {
           if ($_REQUEST['blocks'] == "no")
               $blockcount = 0;
           else {
               $squery = 'SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock ' .
                   ' WHERE (`calendarid` = ' . WaseSQL::sqlSafe($_SESSION['curcalid']) .
                   ' AND `userid` = '  . WaseSQL::sqlSafe($_SESSION['curcalowner']);
    
                $query = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET ' .
                        '`userid` = ' . WaseSQL::sqlSafe($_SESSION['newowner']) . ',' .
                        '`name` = ' . WaseSQL::sqlSafe($_SESSION['newname']) . ',' .
                        '`location` = ' . WaseSQL::sqlSafe($_SESSION['newoffice']) . ',' .
                        '`email` = ' . WaseSQL::sqlSafe($_SESSION['newemail']) .
                    ' WHERE (`calendarid` = ' . WaseSQL::sqlSafe($_SESSION['curcalid']) . ' AND ' .
                             '`userid` = '  . WaseSQL::sqlSafe($_SESSION['curcalowner']); 
                
                if ($_REQUEST['blocks'] == "all") {
                    $squery .= ')';
                    $query .= ')'; 
                }
                else {
                    $squery .= ' AND `startdatetime` >= ' . WaseSQL::sqlSafe(date('Y-m-d H:i:s')) . ')';
                    $query .= ' AND `startdatetime` >= ' . WaseSQL::sqlSafe(date('Y-m-d H:i:s')) . ')';
                }
                if (!WaseSQL::doQuery($squery))
                    $errmsg = 'Unable to change block ownerships: ' . WaseSQL::error();
                else {
                    $blockcount = WaseSQL::affected_rows();
                    if ($blockcount > 5000 && !$_REQUEST['justdoit'])
                        $errmsg = 'This update will affect > 5000 blocks; it must be done manually.';
                    elseif (!WaseSQL::doQuery($query))
                        $errmsg = 'Unable to change block ownerships: ' . WaseSQL::error();
                    else
                        $blockcount = WaseSQL::affected_rows();
                } 
           }
           $infmsg .=  $blockcount . ' block(s) updated.';
           clearsession();
       }
   }
    
}

else {
    clearsession();
    if ($_POST['submit'] == 'EXIT') {
	   echo 'Bye.';
	   exit;
    }
}


if ($errmsg) {
   clearsession();
}    


// This function clears the session variables.
function clearsession() {
    $_SESSION['owner'] = '';
    $_SESSION['curcalowner'] = '';
    $_SESSION['curcalid'] = '';
    $_SESSION['newowner'] = '';
    $_SESSION['newname'] = '';
    $_SESSION['newemail'] = '';
    $_SESSION['newoffice'] = '';
    $_SESSION['ismanager'] = '';
    $_SESSION['ismember'] = '';
    $_SESSION['founduser'] = false;
}

		
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

select {
	font-size:16px;
}

.submit {
	width:100px; 
	height:100px;
	// font-size:24px;
}

</style>
<title>Change Calendar/Block Ownership</title> 
</head>
<body>
<div id="divHeaderLogo"></div> 
<h1 align="center">Change Calendar/Block Ownership</h1>
<?php if ($errmsg) echo '<h3>ERROR: ' . $errmsg . '</h3>'; ?>
<?php if ($infmsg) echo '<h3>' . $infmsg . '</h3>'; ?>
<?php if ($calendars) echo $calendars; ?>
<blockquote>
<p align="left">
    <?php if (!$_SESSION['founduser']) { ?>
    Enter the calendar <em>id</em>, the current calendar-owner <?php echo WaseUtil::getParm('NETID') ?>, and the desired
    new owner <?php echo WaseUtil::getParm('NETID') ?>, then
    click LOOKUP to validate the new owner <?php echo WaseUtil::getParm('NETID') ?>.
    <p>
        If you do not know the calendar <em>id</em>, enter the current calendar
        owner <?php echo WaseUtil::getParm('NETID') ?> and click on Lookup.</p>
    <?php }
else {?>
    Make any desired changes to the new owner name, office (location) or email fields, select the blocks whose ownership should also be changed. then click
CHANGE to make the changes.
<br />
Click LOOKUP to lookup a different proposed new calendar owner. <?php }?>
<br /><br />
Click EXIT when you are done.<br /><br /></p>
</blockquote>
<form action="reown.php" method="POST" name="Reown" id="Reown">
<blockquote>
 <p>Calendar ID (available on the Calendar Settings page in WASE): 
    <input name="curcalid" type="text" id="curcalid" size="10" value="<?php echo WaseUtil::safeHTML($_SESSION['curcalid']);?>">
  </p>
    <p>Current Calendar-Owner <?php echo WaseUtil::getParm('NETID') ?> :
    <input name="curcalowner" type="text" id="curcalowner" size="20" value="<?php echo WaseUtil::safeHTML($_SESSION['curcalowner']);?>">
  </p>
    <p>Proposed New Calendar-Owner <?php echo WaseUtil::getParm('NETID') ?>
        <input name="newowner" type="text" id="newowner" size="20"
               value="<?php echo WaseUtil::safeHTML($_SESSION['newowner']); ?>">
  </p>
  <?php if ($_SESSION['founduser']) {?>
   <p>Proposed New Calendar-Owner Name: 
    <input name="newname" type="text" id="newname" size="50" value="<?php echo WaseUtil::safeHTML($_SESSION['newname']);?>">
  </p>
   <p>Proposed New Calendar-Owner Email: 
    <input name="newemail" type="text" id="newemail" size="60" value="<?php echo WaseUtil::safeHTML($_SESSION['newemail']);?>">
  </p>
   <p>Proposed New Calendar-Owner Office (default location for appointments): 
    <input name="newoffice" type="text" id="newoffice" size="100" value="<?php echo WaseUtil::safeHTML($_SESSION['newoffice']);?>">
  </p>
  <o>Should the ownership of blocks that belong to this calendar be changed? <br /><em>Be sure to get this right ... if you change the calendar ownership and not the block ownership(s), there is no simple way to go back and change the block ownerships.</em> </o>
  <p>
  <select name="blocks">
   	<option value="all">Yes, change block ownership for all blocks, even blocks in the past.</option> 
   	<option value="future">Yes, change block ownership to match calendar ownership, but only for future blocks (blocks already on the calendar that start in the future, and newly added blocks).</option> 
  	<option value="no">No, do not change any block ownership (existing blocks will continue to be owned by <?php echo $_SESSION['owner']?>; newly added blocks will be owned by <?php echo $_SESSION['newowner']?>).</option>
    </select>
  </p>
  <?php }?>
  </blockquote>
<DIV align="left">
  <blockquote>
    <p>
    <?php if ($_SESSION['founduser']) {?>
     <input type="submit" class="submit" name="submit" value="CHANGE">
      &nbsp;&nbsp;&nbsp;
      <input type="submit" class="submit" name="submit" value="LOOKUP">
       <?php } else {?>
      <input type="submit" class="submit" name="submit" value="LOOKUP"> 
      <?php }?>
      &nbsp;&nbsp;&nbsp;
         <input type="submit"  class="submit" name="submit" value="EXIT">
    </p>
  </blockquote>
  <input type="hidden" name="justdoit" value="<?php echo $_REQUEST['justdoit'];?>">
  <input type="hidden" name="secret" value="<?php echo $_REQUEST['secret'];?>">
</DIV>
</form>
</body>
</html>