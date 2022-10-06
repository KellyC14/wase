<?php
/**
 * 
 * This script allows an administrator to create, delete, search and update entires in a simple mysql table.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * 
 */




/************************************************************
 * 
 * START OF CUSTOMIZATION SECTION
 * 
 * Define the constants and variables that will drive the script.
 * 
 * YOU MUST MODIFY THE VALUES LISTED BELOW.  
 ************************************************************/

define("TITLE","What you wangt the form Title to be.");

/* Database definitions */
// Hostname of the machine running the MySQL service, or path to socket file (used in mysql_connect call).
define("HOST", ":/some/socket/path/mysql/data/mysql.sock");

// Name of MySQL database that is being managed.
define("DATABASE", "database");

// Name of MySQL table that is being managed.
define("TABLE", "table");

// Userid associated with the MySQL database.
define("USER", "user");

// Password associated with the MySQL database.
define("PASS", "password");

// List of userids allowed access (if using CAS).  Leave null if not using CAS.
define("CASUSERS","");
// CAS parameters
define("CASHOST","fed.princeton.edu");
define("CASPORT",443);
define("CASURI","cas");
define("CASNAME","CAS");
define("CASLOGOUT","https://authenticate.princeton.edu/cas/logout");


/* Field definitions:
 *       This is an array of arrays..  Each entry defines a field, along with flags
 *       specified as a string of comman-separated values, and a description.  
 *       
 *       Possible flags are: KEY: define the key field
 *                           AUTO: also defines a KEY field, but one that is an auto-number field.
 *                           REQUIRED: value must be specified (cannot be null)
 *                           UNIQUE: value must be unique.
 *                           INT: the value specified must be an integer.
 *                           BOOL: the value specified must by 0/1, or "yes/no", pr "true/false".
 *                           
 *                           
 *       BOOL values are always written our as 0 (false) or 1 (true).  yes/no, true/false are accept in any case.
 *       
 *       A KEY FIELD must be specified.  The KEY field is assumed to be UNIQUE and REQUIRED
 *       (if an auto-number field, then it is never displayed or prompted for).
 *       
 *       
 *       Substitute the actual table field names in the array below.  Add or remove as many lines
 *       as you need.
 *       
 *       
 */
$FIELDS = array(
        array('keyfieldname','AUTO','This is never displayed'),
        array('requireduniquefieldname','REQUIRED,UNIQUE','Field description.  You must specify me, and I must be unique.'),
        array('requirednonuniquefieldnam','REQUIRED','Field description.  You must specify me.'),
        array('nonrequireduniquefieldname','UNIQUE','Field description.  I need a unique value.'), 
        array('intnonrequiredfield','INT','Field description.  Value must be an integer.'),
        array('boolnonrequiredfield','BOOL','Field description.  Value must be an integer.'),
        array('stringnonrequiredfield','','Field description.')
);


/************************************************************
 *
 * END OF CUSTOMIZATION SECTION
 *
 * Define the constants and variables that will drive the script.
 * 
 * YOU MUST MODIFY THE VALUES LISTED ABOVE.
 ************************************************************/


/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';  $infmsg = '';

/* Init database handle */
$DBHANDLE = '';

/* Check authorization:
 * 
 * This probably has to be MODIFIED.
 * 
 */
if (CASUSERS) {
    phpCAS::setDebug(FALSE);
    phpCAS::client(CASVERSION,CASHOST,CASPORT,CASURI);
    phpCAS::setNoCasServerValidation();

    if (!$_SESSION['authenticated']) {
        //phpCAS::setdebug('/tmp/casdebug');
        //phpCAS::setVerbose(true);

        if (phpCAS::isAuthenticated()) {
            $userid = phpCAS::getUser();
            $_SESSION['userid'] = $userid;
            $_SESSION['authenticated'] = 1;
        }
        else {
            $auth = phpCAS::forceAuthentication();
        }
    }
    else {
        $userid = $_SESSION['userid'];
        $okusers = explode(',',CASUSERS);
        if (!in_array($userid,$okusers))
            die('You are not authorized to run this application.');
    }
}
 
// Define heavilly used value
$db = DATABASE.'.'.TABLE;


// Init record counter.
$current = 0;

// Read in form fields, if any
$VALUES = array();  
if (isset($_REQUEST['submit'])) {
    foreach ($FIELDS as $fieldarray) {
         list($field,$flags,$description) = $fieldarray;
         if (isset($_REQUEST[$field])) 
             $VALUES[$field] = $_REQUEST[$field];
         // Save name of key field.
         $allflags = explode(',',$flags);
         if (in_array('KEY',$allflags) || in_array('AUTO',$allflags))
             $key = $field;
    }
}

// Die if we don't have a key field.
if (!$key)
    die('No KEY or AUTO field specified in the $FIELDS array.');

// Load up the current record
$count = 0;
$rec = array();
loadrec();
if ($rec)
    $keyval = $rec[$key];



/* If we are invoking ourselves to process the form. */
if (isset($_POST['submit'])) {
    
    $action = $_POST['submit'];
  
    
    // Exit if requested, by going to login
    if ($action == 'Exit') {
        echo "Bye.";
        exit;
    }
    
    // Clear searches
    if ($action == "Reset") {
        $search = '';
        $current = 0;
        loadrec();
    }  
    
    // Add an entry 
    elseif ($action == 'Add') {
        if (!$infmsg = validate()) {      
            $vars =  '' ;
            $vals = '';
            $auto = false;
            foreach($FIELDS as $fieldarray) {
                list($field,$flags,$description) = $fieldarray;
                $allflags = explode(',',$flags);
                if (!in_array('AUTO',$allflags)) {
                    if ($vars) $vars .= ',';
                    $vars .= $field;
                    if ($vals) $vals .= ',';
                    $vals .= $VALUES[$field];
                    if ($field == $key)
                        $keyval = $VALUES[$field];                        
                }
                else 
                    $auto = true;                  
            }
            $query = 'INSERT INTO ' . $db  . ' (' . $vars . ') VALUES ' . $vals . ')';
            if (!$ret = doQuery($query))
                    $errmsg = 'Error in SQL ' . $query . ':' . error();
            else {
                if ($auto)
                    $keyval = insert_id();
                $count++;
                $current = $count;
                $infmsg = "Record added.";
                loadrec();               
            }         
        }
    }
    
    // Delete an entry
    elseif ($action == 'Delete') {
        if (!$count)
            $infmsg = 'No records to delete';
        else {
             if (!$ret = doQuery('DELETE FROM ' . $db . ' WHERE ' . $key . '=' . sqlSafe($keyval)))  
                    $errmsg = 'Error in SQL ' . $sql . ':' . error();
             else {
                $infmsg = "Record deleted. ";
                $count--;
                $current = $count;
                loadrec();
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
        foreach ($FIELDS as $fieldarray) {
            list($field,$flags,$description) = $fieldarray;
            if (isset($_REQUEST[$field]))
                $search .= $field . '=' . sqlSafe($VALUES[$field]) . ' AND ';
        }
        $search .= ' 1';
        
        // Find  and load the records
        $current = 0;
        loadrec(); 
        
    }
    
    elseif ($action == 'Update') {
        if (!$netid)
            $errmsg = 'You mjst specify the netid of the entry that you want to update';
        else {
            $vars = 'SET '; 
            foreach ($FIELDS as $fieldarray) {
                list($field,$flags,$description) = $fieldarray;
                if (isset($_REQUEST[$field]))
                    $vars  .= $field . '=' . sqlSafe($VALUES[$field]) . ',';
            }
            $vars = rtrim(trim($vars),",");
            $vars .= ' WHERE ' . $key . '=' . sqlSafe($keyval);
            // Update
            $ret = doQuery('UPDATE ' . $db . $vars);
            if ($ret) {
                $infmsg = 'Record updated';
                loadrec();
            }
            else 
                $errmsg = 'Update failed for UPDATE ' . $db . $vars . ':' . error();
        }  
    }
    
    
}    


if ($_POST['submit'] == 'EXIT') {
	echo 'Bye.';
	exit;
}

// Grab all of the current field values.
if ($count) {
    foreach ($FIELDS as $fieldarray) {
        list($field,$flags,$description) = $fieldarray;
        $VALUES[$field] = $rec[$field];
    }      
}
else 
    $infmsg = "No records in DB.";

function validate() {
    global $userid, $password;
  
    
    return "";
}


function loadrec() {
    global $rec, $db, $search, $count, $current, $DBHANDLE;
    $recs = array();
    $res = doQuery('SELECT * FROM ' . $db . $search . ' ORDER BY `id` ASC');

    while ($rec = doFetch($res)) {
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

function openDB () {
    global $DBHANDLE;
    if ($DBHANDLE)
        return $DBHANDLE;
    if (!$DBHANDLE = mysqli_connect(HOST, USER, PASS, DATABASE)) {
        die('Please report the following MySQL error to your IT support staff: ' . mysqli_connect_error());
    }
    return $DBHANDLE;
}

function doQuery($query)
{
    global $DBHANDLE;
    /* Execute the query. */
    $ret = mysqli_query(openDB(), $query);
    /* If error, log the error */
    if ($ret === false) {
        die('Mysqli error ' . mysqli_errno($DBHANDLE) . ' = ' . mysqli_error() . '. Generated from query: ' . $query . "\r\n");
    }
    return $ret;
}

function doFetch($resource)
{
    return mysqli_fetch_assoc($resource);
}

function insert_id()
{
    return mysqli_insert_id(openDB());
}

function error() {
    return mysqli_error(openDB());
}

function sqlSafe($value)
{
    // Stripslashes
    if (get_magic_quotes_gpc()) {
        $ret = stripslashes($value);
    } else
        $ret = $value;
    // Quote if not a number or a numeric string
    if (! (is_numeric($ret))) {
        $link = openDB();
        return "'" . mysqli_real_escape_string($link, $ret) . "'";
    } else
        return $ret;
}


/* 
Prompt for registration fields
*/
?>
<html>
<head>
<?php if ($admin) {?>
<title><?php echo TITLE;?></title>
<?php }
else {?>
<title><?php echo TITLE;?></title>
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
<h1>Register <?php echo WaseUtil::getParm('SYSNAME');?> Users <?php if ($ins = $_SERVER['INSTITUTION']) echo "at $ins";?></h1>
<?php }
else {?>
<h1>Register as a <?php echo WaseUtil::getParm('SYSNAME');?> User at <?php if ($ins = $_SERVER['INSTITUTION']) echo "$ins";?></h1>
<?php }
if ($infmsg) echo '<h3>'.WaseUtil::safeHTML($infmsg).'</h3>';
if ($errmsg) echo '<h3>'.WaseUtil::safeHTML($errmsg).'</h3>'?>
<?php if ($admin) {?>
  <p align="left"> <table>
                    <tr>
                        <td>To add an entry</td> <td>Enter or modify the form fields below, then click on Add.</td>
                   <?php if ($count) {?>
                   <tr>
                       <td>To search for an entry</td> <td>Fill in one or more form fields below, then click on Search (click on Next/Previous to see the next/previous search result, if any).   Click on Reset to clear the search.</td></tr>
                   <tr>
                       <td>To delete an entry</td> <td>Fill in the <?php echo WaseUtil::getParm("NETID")?> field, then click on Delete (this action cannot be undone). </td></tr>
                   <tr>
                       <td>To update an entry</td> <td>Modify the fields below, then click on Update. (Note: if you clear a field, it will be cleared in the database). </td></tr>
                   <?php }?>
                   <tr>
                    <td>To exit</td> <td>Click on Exit. </td></tr>
                    </table>
                   </p>
<?php }
else {?>    
 <p align="left"> To add an entry, fill in the form fields below, then click on Add.  Click on Exit to exit.</p>
<?php }?>    
      
<form action="gregister.php" method="POST" name="Register" id="Register">
  <table>
  <tr><td><?php echo WaseUtil::getParm('NETID')?>: (Required)</td>
    <td><input name="netid" type="text" id="netid"   size="100" value="<?php echo WaseUtil::safeHTML($netid);?>"></td></tr>
    <?php foreach ($FIELDS as $fieldarray) {
        list($field,$flags,$description) = $fieldarray;
        $allflags = explode(',',$flags);
        echo '<tr><td>'.$field;
        if (in_array('REQUIRED',$allflags)) echo ' (Required)';
        echo '</td><td><input name="'.$field.'" type="text" id="'.$field.'" size="100" value"'.$VALUES[$field].'"></td>';
        echo '<td>'.$description.'</td></tr>';      
    ?>
  </table>
<DIV align="center">
    <p> <?php if ($count) {?>
          <input type="submit" class="submit" name="submit" value="Add">
          &nbsp;&nbsp;&nbsp;
         <input type="submit" class="submit" name="submit" value="Search">
          &nbsp;&nbsp;&nbsp;
          <input type="submit" class="submit" name="submit" value="Reset">
          &nbsp;&nbsp;&nbsp;
          <input type="submit" class="submit" name="submit" value="Next">
          &nbsp;&nbsp;&nbsp;
           <input type="submit" class="submit" name="submit" value="Previous">
          &nbsp;&nbsp;&nbsp;
           <input type="submit" class="submit" name="submit" value="Delete">
          &nbsp;&nbsp;&nbsp;
           <input type="submit" class="submit" name="submit" value="Update">
          &nbsp;&nbsp;&nbsp;
           <input type="submit" class="submit" name="submit" value="Exit">
       <?php }
       else {?>
            <input type="submit" class="submit" name="submit" value="Add">
          &nbsp;&nbsp;&nbsp;
          <input type="submit" class="submit" name="submit" value="Exit">
      <?php }?>
    </p>
</DIV>
 <input type="hidden" name="current" value="<?php echo $current; ?>" />
 <input type="hidden" name="search" value="<?php echo WaseUtil::safeHTML($search); ?>" />
</form>
</body>
</html>
<?php 
    }?>