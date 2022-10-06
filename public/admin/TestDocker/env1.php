<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 6/18/19
 * Time: 8:32 AM
 */


include("autoload.include.php");


$w = new WaseWSO2();
echo "getNetid(uid,serge) = " . print_r($w->getNetid('uid', 'serge'), true) . "<br /><br />";


?>