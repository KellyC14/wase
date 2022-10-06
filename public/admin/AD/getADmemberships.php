<?php


/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

echo 'Syntax: getADmemberships.php?netid=userid<br />Returns all groups the specified netid is a member of.<br /><br />';

if (!$netid = $_REQUEST['netid'])
    $netid = 'serge';

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

echo 'Netid  ' . $netid . ' membership = ' . print_r($directory->memberOf($netid), true);





?> 