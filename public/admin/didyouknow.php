<?php

/**
 * This script allows an administrator to add/edit/remove did-you-know entries.  These entries show up on various WASE pages (manage calendars, calendar setup).
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */



/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();
 
/* Start session support */
session_start();

/* Init error/inform messages */
$infmsg = '';  


/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('DIDYOUKNOW_USERS'));


/* Get resource for all entries */
$entries = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow ORDER BY didyouknowid');
/* Compute how many entries we have */
$entry_count = WaseSQL::num_rows($entries);

/* If no entries, clear current entry id and entry array */
if ($entry_count == 0) {
	$entry = '';
	$current = 0;
	$_SESSION['current'] = 0;	
}
else {
	/* If we have entries, try to read in the current one. */
	$current = $_SESSION['current'];
	$entry = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow where didyouknowid=' . WaseSQL::sqlSafe($current)));
	/* If we can't read thqat one in, reset to the first one */
	if (!$entry) {
		$entry = WaseSQL::doFetch($entries);
		$current = $entry['didyouknowid'];
		$_SESSION['current'] = $current;
	}

}


/* For remove, delete the entry, then do a next */
if ($_POST['submit'] == 'Remove') {
	/* If no current, issue an error message */
	if (!$current)
		$infmsg .= 'No current entry to remove. ';
	else {
		$ret = WaseSQL::doQuery('DELETE FROM ' .WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow WHERE didyouknowid=' . $current . ' LIMIT 1');
		if ($ret) {
			$infmsg .= 'Entry removed. ';
			$entry_count--;
			/* Go to the next entry */
			$_POST['submit'] = 'Next';
		}
		else 
			$infmsg .= 'Entry not removed: ' . WaseSQL::error() . ' ';
	}
}
	
/* For Next, scroll to the next entry.  */
if ($_POST['submit'] == 'Next') {
  if ($entry_count == 0) {
  	$infmsg .= 'No entries to display. ';
	$entry = '';
  }
  else { 
	$first = ''; $entry = '';
	$entries = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow ORDER BY didyouknowid');
	while ($entry = WaseSQL::doFetch($entries)) {
		if (!$first) 
		  $first = $entry;
		if ($entry['didyouknowid'] > $current) 
			break;
	}
	
	/* If at end, make first entry the current one */
	if (!$entry) { 
		$entry = $first;
		if ($entry_count == 1)
			$infmsg .= 'Only 1 entry to display. ';
		else
			$infmsg .= 'Wrapped back to first entry. ';
	}
		
	/* Save new current */
	if ($entry) 
		$current = $entry['didyouknowid'];
	else
	  $current = 0;
	  
	$_SESSION['current'] = $current;
	
  }
}

/* For edit, write out the updated values */
if ($_POST['submit'] == 'Edit') {
	/* If adding, go do that */
	if (!$current)
		$_POST['submit'] = 'Add';
	
	else {
	  if (!(trim($_POST['header'])) || !(trim($_POST['details'])))
		  $infmsg .= 'Entry must have a header and some details. ';
	  else {
		/* Update the entry */	  
		$ret = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow SET ' .
				'`release`=' . WaseSQL::sqlSafe(trim($_POST['release'])) . ',' .
				'`topics`=' . WaseSQL::sqlSafe(trim($_POST['topics'])) . ',' .
				'`header`=' . WaseSQL::sqlSafe(trim($_POST['header'])) . ',' .
				'`details`=' . WaseSQL::sqlSafe(trim($_POST['details'])) . 
				' WHERE didyouknowid= ' . $current . ' LIMIT 1');
		
		if ($ret) {
			$infmsg .= 'Entry updated. ';
			/* re-read the entry */
			$entry = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow where didyouknowid=' . WaseSQL::sqlSafe($current)));
		}
		else
			$infmsg .= 'Entry not updated: ' . WaseSQL::error() . ' ';
	  }
	}
}
		

/* For Add, add the entry */
if ($_POST['submit'] == 'Add') {
	
	if (!(trim($_POST['header'])) || !(trim($_POST['details'])))
		$infmsg .= 'Entry must have a header and some details. ';
	else {
	  /* Add the entry */
	  $ret = WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow (`dateadded`,`release`,`topics`,`header`,`details`) VALUES (' .
			   WaseSQL::sqlSafe(trim(date('Y-m-d'))) . ',' .
			   WaseSQL::sqlSafe(trim($_POST['release'])) . ',' .
			   WaseSQL::sqlSafe(trim($_POST['topics'])) . ',' .
			   WaseSQL::sqlSafe(trim($_POST['header'])) . ',' .
			   WaseSQL::sqlSafe(trim($_POST['details'])) . ')');
	  
	  if ($ret) {
		  $infmsg .= 'Entry added.  To add another entry, overlay the displayed fields with the new values, then click <em>Add</em>. ';
		  $current = WaseSQL::insert_id();
		  $_SESSION['current'] = $current;
		  $entry = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow WHERE didyouknowid=' . $current));
		  $entry_count++;
	  }
	  else
		  $infmsg .= 'Entry not added: ' . WaseSQL::error() . ' ';
	}
	  
}

/* For exit, exit */
if ($_POST['submit'] == 'Exit') {
	echo 'Bye.';
	exit;
}





/* We can send email to any of the following mutually-exclusive groups (or combination of groups; select all groups to send to everyone; no-one gets more than one copy of the email): 
	1. Calendar owners who do not have managers.
	2. Calendar owners who do have managers.
	3. Calendar managers.
	4. Students who have expired appointments.
	5. Students who have active appointments.
*/


?>
<html>
<head>
<title>Manage Did-You-Know Entries</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
body {
	background-color: #E6DDD0;
	font-family: Arial, Helvetica, sans-serif;
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
</head>
<body >
<div id="divHeaderLogo"></div> 
<h1 align="center">Add/Edit/Remove Did-You-Know Entries for <?php echo '"' . $_SERVER['INSTITUTION'] . '"';?></h1>

  <p> This form allows you to add, edit and remove did-you-know entries from the WaseDidYouKnow table. These entries are displayed on various WASE pages as a way to inform users about features of which they may not be aware.</p>
  <p>Existing entries are displayed below. You can cycle through the entries by clicking the <em>Next</em> button.  You can make changes to the displayed entry by editing the entry fields and clicking the <em>Edit</em> button.  You can remove the displayed entry by clicking the <em>Remove</em> button. You can create a new entry by filling in the fields (overlay what is currently displayed) and clicking the <em>Add</em> button.  You can exit by clicking the <em>Exit</em> button.</p>



<form action="didyouknow.php" method="POST" name="didyouknow" id="didyouknow">
<DIV >
  <p>
  <h3>Entries: <?php echo $entry_count;?></h3>
  <?php
if (!$infmsg) $infmsg = '&nbsp;';  ?>
	<div id="error" ><?php echo '<strong>>>>' . $infmsg . '<<<</strong>';?><br>
			  </div>
<?php ?>	
  <p>Header: &nbsp;&nbsp;Enter the short (one-line) header text to display as a tag for this entry (you can include simple HTML, such as bold or italic):<br />
    <input name="header" type="text" id="header" size="100" value="<?php if ($entry) echo WaseUtil::safeHTML($entry['header']);?>">
  </p>
  <p>Release: &nbsp;&nbsp;If this entry applies to a feature only available starting with a certain release, enter that release number.  If it applies to all releases, enter 0 (zero).  Releases must be of the form 'n.n.n' (where each n is an integer, and you can have as many n's as you like):<br />
    <input name="release" type="text" id="release" size="100" value="<?php if ($entry) echo WaseUtil::safeHTML($entry['release']);?>">
  </p>
    <p>Topics: &nbsp;&nbsp;Optionally, enter a comma-seperated list of topic names to which this entry applies (single words).<br />
    <input name="topics" type="text" id="topics" size="150" value="<?php if ($entry) echo WaseUtil::safeHTML($entry['topics']);?>">
  </p>
  <p>Enter the entry Details: &nbsp;&nbsp;You may include simple html (bold, italic).</p>
  <p>
    <textarea name="details" cols="80" rows="20"><?php if ($entry) echo WaseUtil::safeHTML($entry['details']);?></textarea>
  </p>
  <p>
    <input type="hidden" name="secret" value="<?php echo WaseUtil::getParm('PASS');?>" />
  </p>
</DIV>
<DIV align="center">  
    <p>
     <input type="submit" class="submit" name="submit" value="Next">
      &nbsp;&nbsp;&nbsp;
      <input type="submit" class="submit" name="submit" value="Edit">
      &nbsp;&nbsp;&nbsp;
        <input type="submit" class="submit" name="submit" value="Remove">
      &nbsp;&nbsp;&nbsp;
        <input type="submit" class="submit" name="submit" value="Add">
      &nbsp;&nbsp;&nbsp;
        <input type="submit" class="submit" name="submit" value="Exit">

    </p>
  
</DIV>


</form>
</body>
</html>