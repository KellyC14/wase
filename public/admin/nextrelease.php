<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 2/26/18
 * Time: 10:51 AM
 *
 * This script reads the current release number and outputs the next (patch) release number.
 *
 */

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');


// Parse the current release into it's components
list($major,$minor,$patch) = explode('.', trim(WaseRelease::RELEASE));

// Echo the next (patch) re;ease number.

die($major.'.'.$minor.'.'.($patch+1));
?>