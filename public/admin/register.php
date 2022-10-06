<?php
/**
 * 
 * This script allows an administrator to create, delete, search and update entries in the WaseUser table.
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

/* Check authorization:
 * 
 * IF SELF_REGISTER parameter is set to true, then authorization is not required (to register self or modify self).
 * 
 */
  
if (WaseUtil::IsAdminAuth(WaseUtil::getParm('REGISTER_USERS')))
    $admin = true;
else {
    if (WaseUtil::getParm("SELF_REGISTER") == 1)
        $admin = false;
    else 
        die('You are not authoized to use this application');
}

// Save heavilly used value
$db = WaseUtil::getParm('DATABASE') . '.WaseUser ';

// Init record counter.
$current = 0; $count = 0;

// Read in form fields, if any
if (isset($_REQUEST['submit'])) {
    $netid = (isset($_REQUEST['netid'])) ? $_REQUEST['netid'] : '';
    $password = (isset($_REQUEST['password'])) ? $_REQUEST['password'] : '';
    $email= (isset($_REQUEST['email'])) ? $_REQUEST['email'] : '';
    $office = (isset($_REQUEST['office'])) ? $_REQUEST['office'] : '';
    $name = (isset($_REQUEST['name'])) ? $_REQUEST['name'] : '';
    $telephone = (isset($_REQUEST['telephone'])) ? $_REQUEST['telephone'] : '';
    $search= (isset($_REQUEST['search'])) ? $_REQUEST['search'] : '';
    $current= (isset($_REQUEST['current'])) ? $_REQUEST['current'] : 0;
}


// Load up the current record
$rec = array();
loadrec();
$id = $rec['id'];


/* If we are invoking ourselves to process the form. */
if (isset($_REQUEST['submit'])) {
    
    $action = $_REQUEST['submit'];
  
    
    // Exit if requested, by entering the system.
    if ($action == 'Exit') {
        header("Location: ../views/pages/login.page.php");
        exit;
    }
    
    // Clear searches
    if ($action == "Reset") {
        $search = '';
        $current = 0;
        loadrec();
    }  
    
    // Register a user
    elseif ($action == 'Register') {
        if (!$infmsg = validate()) {
            // See if netid already exists
            if ($rec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . $db . ' WHERE userid=' . WaseSQL::sqlSafe($netid))))
                $infmsg = "User $netid aleady exists";
            else {
                $sql = 'INSERT INTO ' . $db  . ' (userid,password,displayname,mail,street,telephonenumber) VALUES (' .
                    WaseSQL::sqlSafe($netid) .',' .  WaseSQL::sqlSafe($password) . ',' . WaseSQL::sqlSafe($name) .',' 
                    . WaseSQL::sqlSafe($email) . ',' . WaseSQL::sqlSafe($office) . ',' . WaseSQL::sqlSafe($telephone) . ')';
                if (!WaseSQL::doQuery($sql))
                        $errmsg = 'Error in SQL ' . $sql . ':' . WaseSQL::error();
                else {
                    $count++;
                    $current = $count;
                    $infmsg = "User $netid registered.";
                    $justregistered = true;
                    loadrec(); 
                }
            }         
        }
    }
    
    elseif ($action == 'Delete') {
        if (!$count)
            $infmsg = 'No records to delete';
        else {
            if (!$rec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid=' . WaseSQL::sqlSafe($netid))))
                $errmsg = "Record with netid = $netid not found.";
            else {
                if (!WaseSQL::doQuery('DELETE FROM ' . $db . ' WHERE userid = ' . WaseSQL::sqlSafe($netid)))  
                    $errmsg = 'Error in SQL ' . $sql . ':' . WaseSQL::error();
                else {
                    $infmsg = "Record deleted. ";
                    $count--;
                    $current = $count;
                    loadrec();
                }
            } 
        }     
    }
    
    elseif ($action == 'Next') {
        if ($current < $count) {
            $current++;
            loadrec();
        }
        else 
            $infmsg = 'Last record reached: ';
    }
    
    elseif ($action == 'Previous') {
        if ($current > 1) {
            $current--;
            loadrec();
        }
        else
            $infmsg = 'First record reached: ';
    }
    
    elseif ($action == 'Search') {
        $search = ' WHERE 1 AND ';
        if ($netid)
            $search .= 'userid LIKE ' . WaseSQL::sqlSafe('%'.$netid.'%') . ' AND ';
        if ($password)
            $search .= 'password LIKE ' . WaseSQL::sqlSafe('%'.$password.'%') . ' AND ';
        if ($email)
            $search .= 'mail LIKE %' . WaseSQL::sqlSafe('%'.$email.'%') . ' AND ';
        if ($name)
            $search .= 'displayname LIKE ' . WaseSQL::sqlSafe('%'.$name.'%') . ' AND ';
        if ($office)
            $search .= 'street LIKE ' . WaseSQL::sqlSafe('%'.$office.'%') . ' AND ';
        if ($telephone)
            $search .= 'telephonenumber LIKE ' . WaseSQL::sqlSafe('%'.$telephone.'%') . ' AND ';
        $search .= ' 1';
        
        // Find  and load the records
        $current = 0;
        loadrec(); 
        
    }
    
    elseif ($action == 'Update') {
        if (!$netid)
            $errmsg = 'You mjst specify the netid of the entry that you want to update';
        else {
            $vars = 'SET userid='. WaseSQL::sqlSafe($netid) . ',';
            if ($password)
                $vars .= 'password=' . WaseSQL::sqlSafe($password) . ',';
            if ($email)
                $vars .= 'mail=' .WaseSQL::sqlSafe($email) . ',';
            if ($name)
                $vars .= 'displayname=' . WaseSQL::sqlSafe($name) . ',';
            if ($office)
                $vars .= 'street=' .  WaseSQL::sqlSafe($office) . ',';
            if ($telephone)
                $vars .= 'telephonenumber=' .  WaseSQL::sqlSafe($telephone) . ',';
           
            $vars = rtrim(trim($vars),",");
            $vars .= ' WHERE `id` = ' . $id;
            // Update
            $ret = WaseSQL::doQuery('UPDATE ' . $db . $vars);
            if ($ret) {
                $infmsg = 'Record updated';
                loadrec();
            }
            else 
                $errmsg = 'Update failed for UPDATE ' . $db . $vars . ':' . WaseSQL::error();
        }  
    }
    
    
}    


if ($_POST['submit'] == 'Exit') {
	echo 'Bye.';
	exit;
}

// Always do a search to retrieve and display the current record.
if ($count) {
    $netid = $rec['userid'];
    $password = $rec['password'];
    $email= $rec['mail'];
    $office = $rec['street'];
    $name = $rec['displayname'];
    $telephone = $rec['telephonenumber'];
    $infmsg .= " Showing record $current of $count";
    $id = $rec['id'];
}
else 
    $infmsg = "No records in DB.";

function validate() {
    global $userid, $password;
    if (!$password)
        return 'Password is a required field.';
    
    return "";
}


function loadrec() {
    global $rec, $db, $search, $count, $current;
    $recs = array();
    $res = WaseSQL::doQuery('SELECT * FROM ' . $db . $search . ' ORDER BY `id` ASC');

    while ($rec = WaseSQL::doFetch($res)) {
        $recs[] = $rec;
    }

    $count = count($recs);

    if (!$current) {
        if ($count)
            $current = 1;
        else
            $current = 0;
    }
    
    if ($current>$count)
        $current = $count;
    
    if ($current)
        $rec = $recs[$current-1];
    
}


/* 
Prompt for registration fields
*/
?>
<html>
<head>
<?php if ($admin) {?>
<title>Register <?php echo WaseUtil::getParm('SYSNAME');?> Users <?php if ($ins = $_SERVER['INSTITUTION']) echo " at $ins";?></title>
<?php }
else {?>
<title>Register as a "<?php echo WaseUtil::getParm('SYSNAME');?>" User at "<?php if ($ins = $_SERVER['INSTITUTION']) echo '"'.$ins.'"';?></title>
<?php }
?>
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
</head>
<body>
<div id="divHeaderLogo"></div> 
<?php if ($admin) {?>
<h1>Register "<?php echo WaseUtil::getParm('SYSNAME');?>" users <?php if ($ins = $_SERVER['INSTITUTION']) echo 'at "' . $ins .'"';?></h1>
<?php }
else {?>
<h1>Register as a <?php echo WaseUtil::getParm('SYSNAME');?> User at <?php if ($ins = $_SERVER['INSTITUTION']) echo "$ins";?></h1>
<?php }
if ($infmsg) echo '<h3>'.WaseUtil::safeHTML($infmsg).'</h3>';
if ($errmsg) echo '<h3>'.WaseUtil::safeHTML($errmsg).'</h3>'?>
<?php if ($admin) {?>
  <p align="left"> <table>
                    <tr>
                        <td>To register a user:</td> <td>Enter or modify the form fields below, then click on Register.   This will create a new user.</td>
                   <?php if ($count) {?>
                   <tr>
                       <td>To search for a user:</td> <td>Fill in one or more form fields below, then click on Search (click on Next/Previous to see the next/previous search result, if any).   Click on Reset to clear the search.</td></tr>
                   <tr>
                       <td>To delete a user:</td> <td>Fill in the <?php echo WaseUtil::getParm("NETID")?> field, then click on Delete (this action cannot be undone). </td></tr>
                   <tr>
                       <td>To update a user:</td> <td>Modify the fields below, then click on Update. (Note: if you clear a field, it will be cleared in the database). </td></tr>
                   <?php }?>
                   <tr>
                    <td>To exit:</td> <td>Click on Exit. </td></tr>
                    </table>
                   </p>
<?php }
else {?>    
 <p align="left"> To register, fill in the form fields below, then click on Register.  Click on Exit to exit.</p>
<?php }?>    
     
<form action="register.php" method="POST" name="Register" id="Register">
  <table>
  <tr><td><?php echo WaseUtil::getParm('NETID')?>: (Required)</td>
    <td><input name="netid" type="text" id="netid"   size="100" value="<?php echo WaseUtil::safeHTML($netid);?>"></td></tr>
  <tr><td><?php echo WaseUtil::getParm('PASSNAME')?>: (Required)</td>
    <td><input name="password" type="text" id="password"  size="100"  value="<?php echo WaseUtil::safeHTML($password);?>"></td></tr>
  <tr><td>Full Name:</td>
   <td><input name="name" type="text" id="name"   size="100" value="<?php echo WaseUtil::safeHTML($name);?>"></td></tr>
  <tr><td>Email:</td>
   <td><input name="email" type="text" id="email"   size="100" value="<?php echo WaseUtil::safeHTML($email);?>"></td></tr>
  <tr><td>Telephone Number:</td>
   <td><input name="telephone" type="text" id="telephone"  size="100" value="<?php echo WaseUtil::safeHTML($telephone);?>"></td></tr>
  <tr><td>Office/room location:</td>
   <td><input name="office" type="text" id="office" size="100" value="<?php echo WaseUtil::safeHTML($office);?>"></td></tr> 
  </table>
<DIV align="left">
    <p> <?php if ($admin) {?>
          <input type="submit" class="submit" name="submit" value="Register">
          &nbsp;&nbsp;&nbsp;
          <?php if ($count) {?>
               <input type="submit" class="submit" name="submit value="Search">
              &nbsp;&nbsp;&nbsp;
              <input type="submit" class="submit" name="submit" value="Update">
              &nbsp;&nbsp;&nbsp;
              <input type="submit" class="submit" name="submit" value="Reset">
              &nbsp;&nbsp;&nbsp;
              <input type="submit" class="submit" name="submit" value="Next">
              &nbsp;&nbsp;&nbsp;
               <input type="submit" class="submit" name="submit" value="Previous">
              &nbsp;&nbsp;&nbsp;
               <input type="submit" class="submit" name="submit" value="Delete">
              &nbsp;&nbsp;&nbsp;
           <?php }?>
           <input type="submit" class="submit" name="submit" value="Exit">
       <?php }
       else {?>
            <input type="submit" class="submit" name="submit" value="Register">
          &nbsp;&nbsp;&nbsp;
          <?php 
          if ($justregistered) {?> 
               <input type="submit" class="submit" name="submit" value="Update">
               &nbsp;&nbsp;&nbsp;
           <?php }?>
          <input type="submit" class="submit" name="submit" value="Exit">
      <?php }?>
    </p>
</DIV>
 <input type="hidden" name="current" value="<?php echo $current; ?>" />
 <input type="hidden" name="search" value="<?php echo WaseUtil::safeHTML($search); ?>" />
</form>
</body>
</html>