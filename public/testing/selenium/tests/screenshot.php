<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 2/13/19
 * Time: 2:32 PM
 */


// Take a screen shot
// Take a screenshot
$driver->takeScreenshot($fn = screenshot());
$steps[] = "Took a screen shot in $fn";
$steps[] = "Here is the screenshot ...";
$steps[] = "<br><img src=$fn><br>";

