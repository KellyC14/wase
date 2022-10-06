<?php

/**
 * This script check to see if the local parms match the template parms, and complains if not.
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


/* Check for SSL connection. */
if (!$_SERVER['HTTPS'] && !$_REQUEST['nossl']) 
	die('The install.php script must be invoked using https.  If you really want to run this without SSL, append "&nossl=1" to the URL you used to invoke this script.'); 


/* Make sure password was specified */
WaseUtil::CheckAdminAuth();

// Read in the parms file.
if ($institution = $_SERVER['INSTITUTION'])
    $instdir = '/'.$institution;
else
    $instdir = '';

// Define the various config files
$confLocalSystem =  __DIR__ . '/../../config'. $instdir .'/waseParmsSystem.yml';
$confLocalCustom =  __DIR__ . '/../../config'. $instdir .'/waseParmsCustom.yml';
$confTempSystem =  __DIR__ . '/../../templates/config/waseParmsSystem.yml';
$confTempCustom =  __DIR__ . '/../../templates/config/waseParmsCustom.yml';



// Parse the files into arrays

$parser = new \Symfony\Component\Yaml\Parser();

// Read the config files into arrays
if (file_exists($confLocalSystem))
    $localsystem = $parser->parse(file_get_contents($confLocalSystem));
else
    die("Could not find $confLocalSystem");
if (file_exists($confLocalCustom))
    $localcustom = $parser->parse(file_get_contents($confLocalCustom));
else
    die("Could not find $confLocalCustom");
if (file_exists($confTempSystem))
    $tempsystem = $parser->parse(file_get_contents($confTempSystem));
else
    die("Could not find $confTempSystem");
if (file_exists($confTempCustom))
    $tempcustom = $parser->parse(file_get_contents($confTempCustom));
else
    die("Could not find $confTempCustom");

echo "<html><header></header><body>";

echo "The following parameters are in the template system file but not in the local system file: ";
print_r(array_diff_key($tempsystem, $localsystem));
echo "<br /><br />";

echo "The following parameters are in the template custom file but not in the local custom file: ";
print_r(array_diff_key($tempcustom, $localcustom));
echo "<br /><br />";


echo "The following parameters are in the local system file but not in the template system file: ";
print_r(array_diff_key($localsystem, $tempsystem));
echo "<br /><br />";

echo "The following parameters are in the local custom file but not in the template custom file: ";
print_r(array_diff_key($localcustom, $tempcustom));
echo "<br /><br />";


?>


