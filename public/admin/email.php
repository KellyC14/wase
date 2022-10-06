<?php
/**
 *
 * This script reads emails sent to WASE, looks for iCal attachments, and, if found, acts on them
 * (changes or deletes appointments).
 *
 * 
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */ 


/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

  
/* Start session support */ 
session_start();  

/* Init error/inform messages */ 
$errmsg = '';  $infmsg = ''; 
 
/* See if argument passed from the command line */
if ($_SERVER['argc'] > 1) {
	list($secret,$pass) = explode("=",$_SERVER['argv'][1]);
	if (strtoupper($secret) == 'SECRET') 
		$_REQUEST['secret'] = $pass;
	elseif ($pass == '')
		$_REQUEST['secret'] = $secret;		
} 
else 
    WaseUtil::CheckSSL();

/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth(WaseUtil::getParm('EMAIL_USERS'));

// Read in any pending emails.
$emails = WaseEmail::getMessages();

// For now, just print out the array
echo "here is what we got: " . print_r($emails, true);


/* All done */ 

exit();
	
	
