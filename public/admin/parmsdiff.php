<?php

/**
 * This script outputs differences in the system and custom parms files in two different config directories.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';
$infmsg = '';

//If invoked from command line, skip SSL and auth checks
if ($argc > 1) {
    $_REQUEST['ins1'] = $argv[1];
    $_REQUEST['ins2'] = $argv[2];
    $nl = "\n";
}
else {
    /* Check for SSL connection. */
    if (!$_SERVER['HTTPS'] && !$_REQUEST['nossl'])
        die('The install.php script must be invoked using https.  If you really want to run this without SSL, append "&nossl=1" to the URL you used to invoke this script.');


    /* Make sure password was specified */
    WaseUtil::CheckAdminAuth();

    $nl = "<br />";
    echo "<html><header></header><body>";



}

// Read in first institution directory
if (!$ins1 = $_REQUEST['ins1'])
    die('ins1 parameter not specified');
if (!$ins2 = $_REQUEST['ins2'])
    die('ins2 config directory not specified');

// Define the various config files
$conf1System =  __DIR__ . '/../../config/'.$ins1.'/waseParmsSystem.yml';
$conf1Custom =  __DIR__ . '/../../config/'.$ins1.'/waseParmsCustom.yml';
$conf2System =  __DIR__ . '/../../config/'.$ins2.'/waseParmsSystem.yml';
$conf2Custom =  __DIR__ . '/../../config/'.$ins2.'/waseParmsCustom.yml';



// Parse the files into arrays

$parser = new \Symfony\Component\Yaml\Parser();

// Read the config files into arrays
if (file_exists($conf1System))
    $system2 = $parser->parse(file_get_contents($conf1System));
else
    $system2 = array();
if (file_exists($conf1Custom))
    $custom1 = $parser->parse(file_get_contents($conf1Custom));
else
    $custom1 = array();
if (file_exists($conf2System))
    $system1 = $parser->parse(file_get_contents($conf2System));
else
    $system1 = array();
if (file_exists($conf2Custom))
    $custom2 = $parser->parse(file_get_contents($conf2Custom));
else
    $custom2 = array();


// Init array of keys
$keys = array();

// Now go through system 1 and custom 1 parms and see if any are different
foreach ($system1 as $key => $value) {
    if (!in_array($key,$keys))
        $keys[] = $key;
    if (!key_exists($key, $system2)) {
        if (!key_exists($key, $custom2))
            echo "key $key in $ins1 system file not found in $ins2 system or custom file $nl";
        else {
            if ($value != $custom2[$key])
                echo "key $key in $ins1 system file has value '".$value."', while same key in $ins2 custom file has value '".$custom2[$key]."' $nl";
            else
                echo "key $key in $ins1 system file has value '".$value."', matches key in $ins2 custom file $nl";
        }
    } else if ($value != $system2[$key])
        echo "key $key has value '".$value."' in $ins1 system file, but has value '".$system2[$key]."' in $ins2 system file $nl";
}

foreach ($custom1 as $key => $value) {
    if (!in_array($key,$keys))
        $keys[] = $key;
    if (!key_exists($key, $custom2)) {
        if (!key_exists($key, $system2))
            echo "key $key in $ins1 custom file not found in $ins2 custom or system file $nl";
        else {
            if ($value != $system2[$key])
                echo "key $key in $ins1 custom file has value '".$value."', while same key in $ins2 system file has value '".$system2[$key]."' $nl";
            else
                echo "key $key in $ins1 custom file has value '".$value."', matches key in $ins2 system file $nl";
        }
    } else if ($value != $custom2[$key])
        echo "key $key has value '".$value."' in $ins1 custom file, but has value '".$custom2[$key]."' in $ins2 custom file $nl";
}

// Now go through system 2 and custom 2 parms and see if any are different
foreach ($system2 as $key => $value) {
    if (in_array($key,$keys))
        continue;
    if (!key_exists($key, $system1)) {
        if (!key_exists($key, $custom1))
            echo "key $key in $ins2 system file not found in $ins1 system or custom file $nl";
        else {
            if ($value != $custom1[$key])
                echo "key $key in $ins2 system file has value '".$value."', while same key in $ins1 custom file has value '".$custom1[$key]."' $nl";
            else
                echo "key $key in $ins2 system file has value '".$value."', matches key in $ins1 custom file $nl";
        }
    } else if ($value != $system1[$key])
        echo "key $key has value '".$value."' in $ins2 system file, but has value '".$system1[$key]."' in $ins1 system file $nl";
}

foreach ($custom2 as $key => $value) {
    if (in_array($key,$keys))
        continue;
    if (!key_exists($key, $custom1)) {
        if (!key_exists($key, $system1))
            echo "key $key in $ins2 custom file not found in $ins1 custom or system file $nl";
        else {
            if ($value != $system1[$key])
                echo "key $key in $ins2 custom file has value '".$value."', while same key in $ins1 system file has value '".$system1[$key]."' $nl";
            else
                echo "key $key in $ins2 custom file has value '".$value."', matches key in $ins1 system file $nl";
        }
    } else if ($value != $custom1[$key])
        echo "key $key has value '".$value."' in $ins2 custom file, but has value '".$custom1[$key]."' in $ins1 custom file $nl";
}