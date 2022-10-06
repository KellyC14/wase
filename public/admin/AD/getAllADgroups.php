<?php


/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

echo 'Syntax: getAllADgroups.php<br />Returns the members of the specified group.<br /><br />';

echo print_r($directory->allGroups());

 


 
?> 