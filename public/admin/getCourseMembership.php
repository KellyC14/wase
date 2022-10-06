<?php

/**
 * This script returns the userid and role of users enrolled in a specified course.
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

/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth();

// Get the LMS 
$lms = WaseLMSFactory::getLMS();

// Do the registration and let the user know.
// echo $lms::register();

// Extract parms and call the function

$courseid = $_REQUEST['courseid'];

$users = $lms::getCourseMembership($courseid); 


// Init the html output
echo "<html><head><title>Enrollment for course: $courseid:</title><style>
table, th, td {
border: 1px solid black;
}
</style></head><body>";
echo "<h2>Enrollment for course '$courseid' at " . WaseUtil::getParm('BLACKBOARD_URL') . " in LMS '" . WaseUtil::getParm('LMS') . "'</h2>"; 
if (substr($users[0],0,5) == "Error")
    echo $users[0];
else {
    echo "<table><tr><th>Userid</th><th>Role</th></tr>";
    if (!is_array($users)) {
        $c = array($users);
        $users = $c;
    }
    
    
    foreach ($users as $user) {
        list($id,$role) = explode("|",$user);
        echo "<tr><td>$id</td><td>$role</td></tr>";
    }
    echo "</table>";
}

echo "</body></html>";
?>