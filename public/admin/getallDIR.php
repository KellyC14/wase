<?php


/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


if (!$netid = $_REQUEST['netid'])
    $netid = 'serge';


    if (!$attr = $_REQUEST['attr'])
        $attr= 'memberof';

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

echo 'Netid  ' . $netid . ' attribute ' . $attr . ' = ' . print_r($directory->getallDIR($netid, $attr), true);





?> 