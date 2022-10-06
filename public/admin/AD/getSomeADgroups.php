<?php


/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');
 

echo 'Syntax: getSomeADgroups.php?group=pattern<br />Returns the groups matching the specified pattern .<br /><br />';

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();
 
if (!$group = $_REQUEST['group'])
    $group = 'Ambassadors';

echo print_r($directory->someGroups($group), true);

  


 
?> 