<?php

/**
 * This script looks up the courses in which a specified user is enrolled using the native Bb web services.
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

$userid = $_REQUEST['userid'];

$courses = $lms::getEnrollments($userid);

// Init the html output
echo "<html><head><title>Courses for user: $userid:</title><style>
table, th, td {
    border: 1px solid black;
}
</style></head><body>";
echo "<h2>Courses for user '$userid' at " . WaseUtil::getParm('BLACKBOARD_URL') . " in LMS '" . WaseUtil::getParm('LMS') . "'</h2>"; 
if (substr($courses[0],0,5) == "Error") 
    echo $courses[0];
else {
    if (!is_array($courses)) {
        $c = array($courses);
        $courses = $c;
    }
    
    echo "<table><tr><th>Name</th><th>Id</th><th>Title</th></tr>";
    foreach ($courses as $course) {
        list($name,$id,$title) = explode("|",$course);
        echo "<tr><td>$name</td><td>$id</td><td>$title</td></tr>";
    }
    echo "</table>";
}
echo "</body></html>";
?>