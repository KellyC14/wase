<?php

/**
 * This script returns the role of a specified user in a specified course using the native Bb web services.
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

// Extract parms and call the function

$userid = $_REQUEST['userid'];
$courseid = $_REQUEST['courseid'];

$roles = $lms::getUserRole($userid,$courseid);


// Init the html output
echo "<html><head><title>Role for user '$userid' in course '$courseid'</title><style>
table, th, td {
border: 1px solid black;
}
</style></head><body>";
echo "<h2>Role for user '$userid' in course '$courseid' at " . WaseUtil::getParm('BLACKBOARD_URL') . " in LMS '" . WaseUtil::getParm('LMS') . "'</h2>"; 
if (substr($roles[0],0,5) == "Error") {
    echo $roles[0];
}
else {
    echo "<table><tr><th>Role</th></tr>";
    if (!is_array($roles)) {
        $c = array($roles);
        $roles = $c;
    }
    
    
    foreach ($roles as $role) {
        echo "<tr><td>$role</td></tr>";
    }
    echo "</table>";
}
echo "</table></body></html>";
?>