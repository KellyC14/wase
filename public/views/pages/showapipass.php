<?php

/**
 * This script displays an api password for a user.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */

// Start session support.
session_start();

// Include the supprt files.
include '_header.php';


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();


// Make sure we are running under SSL.
WaseUtil::CheckSSL();

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

// Force a login.
$directory->authenticate($_SERVER['REQUEST_URI']);

if (!$_SESSION['authid'])
    die('authentication failed');
      

// Get the password
$ret = WasePrefs::getPref($_SESSION['authid'],'apipassword');

//Let the user know
echo '<html><head></head><body>';
if ($ret)
    echo 'Api passsword='.$ret.'<br /><br/>Save this (or generate a new one by calling the setapipass script).';
else 
    echo 'Api password not set.  Use the setapipassword ascript to set an api password.';
echo '</body></html>';

exit();
?>