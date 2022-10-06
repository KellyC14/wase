<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 6/18/19
 * Time: 8:32 AM
 */


putenv('WASE_DATABASE=wase');
putenv('WASE_USER=serge');
putenv('WASE_PASS=dockerrules');
putenv('WASE_HOST=waseqa03l.princeton.edu');

include("autoload.include.php");


echo "SERVER: <br />";
print_r($_SERVER);
echo "<br /><br />";

echo "ENV: <br />";
print_r($_ENV);
echo "<br /><br />";
echo "DATABASE: <br />";
echo "WASE_INSTITUTION: " . getenv("WASE_INSTITUTION") . "<br />";
echo "WASE_DATABASE_HOST: " . getenv("WASE_DATABASE_HOST") . "<br />";
echo "WASE_DATABASE_DATABASE: " . getenv("WASE_DATABASE_DATABASE") . "<br />";
echo "WASE_DATABASE_USER " . getenv("WASE_DATABASE_USER") . "<br />";
echo "WASE_DATABASE_PASS: " . getenv("WASE_DATABASE_PASS") . "<br /><br />";


echo "WSO2parms:<br /> WSO2HOST: " . WaseUtil::getParm("WSO2HOST") . ";<br /> WSO2KEY: " . WaseUtil::getParm("WSO2KEY") . ";<br /> WSO2SECRET: " . WaseUtil::getParm("WSO2SECRET") .
    ";<br /> WSO2TOKENPATH: " . WaseUtil::getParm("WSO2TOKENPATH") . ";<br /> WSO2MATCHSEARCHPATH: " . WaseUtil::getParm("WSO2MATCHSEARCHPATH") . ";<br />WSO2LDAPSEARCHPATH: " . WaseUtil::getParm("WSO2LDAPSEARCHPATH");

echo "<br /><br />";


$w = new WaseWSO2();

echo "getUser(serge) = " . print_r($w->getUser('serge'), true) . "<br /><br />";
echo "getNetid(uid,serge) = " . print_r($w->getNetid('uid', 'serge'), true) . "<br /><br />";
echo "getEmail(serge) = " . print_r($w->getEmail("serge"), true) . "<br /><br />";

echo "<br />parms:<br />";
print_r(WaseUtil::$config);


?>