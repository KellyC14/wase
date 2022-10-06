<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 6/18/19
 * Time: 8:32 AM
 */


include("autoload.include.php");


$w = new WaseWSO2();

echo "getUser(serge) = " . print_r($w->getUser('serge'), true) . "<br /><br />";


?>