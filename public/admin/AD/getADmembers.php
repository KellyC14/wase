<?php


/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

echo 'Syntax: getADmembers.php?group=groupname<br />Returns the members of the specified group.<br /><br />';

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

if (!$group = $_REQUEST['group'])
    $group = 'Ambassadors';

echo 'Group  ' . $group . ' membership = ' . print_r($directory->members($group), true);

 


?> 