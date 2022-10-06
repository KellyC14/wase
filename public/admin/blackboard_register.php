<?php

/**
 * This script registers WASE as a Blackboard tool.
 * 
 * This script must be run before WASE can use native Blackboard
 * web services to access enrollment data in Blackboard.  These data
 * are used to support the functions in WASE that allow users to restrict
 * access to their blocks/calendars based on course enrollment.
 *
 *
 * @copyright 2006, 2008, 2013. 2015 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 *
 */



/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

// Global variables for SOAP username and password
$username = 'session';
$password = 'nosession';


// Make sure user is authorized 


/* Make sure user is authorized */
WaseUtil::CheckAdminAuth();

// Get the LMS 
// $lms = WaseLMSFactory::getLMS();

// Do the registration and let the user know.
// echo $lms::register();
echo WaseBlackboard::register();

    

?>