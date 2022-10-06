<?php


/**
 * This script runs a Selenium test script from the testscripts directory.
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */

// Set the URL of the selenium server
define('SELENIUMSERVER', 'http://ais327L.princeton.edu:4444/wd/hub');
define('LOGIN', 'Waseprof secret=7eizje8zn');

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverPoint;


// Load the composer autoload file to find all of our classes.
require_once __DIR__ . '/../../../vendor/autoload.php';

// Initialize array of steps successfully taken
$steps = array();

// Set Exception trap to catch all errors and report them:

try {

// See all the capabilities here: https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities
    $driver = RemoteWebDriver::create(SELENIUMSERVER, DesiredCapabilities::chrome(), 5000);
    $step[] = "Driver created/";

// Set default timer wait
    $driver->manage()->timeouts()->implicitlyWait(1);
    $steps[] = "Default timer wait set to 1 second.";

// Set size
    $driver->manage()->window()->setPosition(new WebDriverPoint(0, 0));
    $steps[] = "Position 0.0 set";
    $driver->manage()->window()->setSize(new WebDriverDimension(1280, 800));
    $steps[] = "Window size set to 1280 by 800.";

// Navigate to WASE
    $driver->get('https://waseqanew.princeton.edu');
    $steps[] = "Navigated to WASE at https://waseqanew.princeton.edu.";

// Wait at most 10s until the page is loaded
    $driver->wait(1)->until(
        WebDriverExpectedCondition::titleContains('WASE')
    );
    $steps[] = "Waited for page to load.";

// Find the LOGIN guest element
    $login = $driver->findElement(
        WebDriverBy::id('txtEmail')
    );
    $steps[] = "Found the login text box.";

// Now click in this field
    $login->click();
    $steps[] = "Clicked into the login text box.";

// Now enter the login
    $login->sendKeys(LOGIN);

//Now find the guest login button

    $loginbutton = $login = $driver->findElement(
        WebDriverBy::id('btnLogInGuest')
    );
    $steps[] = "Found the login button.";

// Click it
    $loginbutton->click();
    $steps[] = "Clicked the login button.";


// Include the test code
    $testfile = __DIR__ . "/tests/" . $_REQUEST['testfile'] . ".php";
    $test = __DIR__ . "/testfiles/" . $_REQUEST['test'] . ".php";
    if (file_exists($testfile) && is_readable($testfile)) {
        $steps[] = 'RUNING TEST: ' . $_REQUEST['testfile'];
        require_once $testfile;
    } elseif (file_exists($test) && is_readable($test)) {
        $steps[] = 'RUNING TEST: ' . $_REQUEST['test'];
        require_once $test;
    } else
        throw new exception("file $testfile $test not found.");

// End the test
    $driver->quit();
    $steps[] = "Success:  All done.";
    $rc = 0;

} catch (Exception $exception) {
    $steps[] = "Exception: " . $exception->getMessage();
    if (!$rc = $exception->getCode())
        $rc = 1;
    // Try to take a screen shot
    try {
        $driver->takeScreenshot($fn = screenshot());
        $steps[] = "Took a screen shot in $fn";
        $driver->quit();
    } catch (Exception $e) {
        $steps[] = "Could not take screen shot: " . $e->getMessage();
        try {
            $driver->quit();
        } catch (Exception $ee) {
        }
    }
}

// Let the user know the results
?>
    <html>
    <head>
        <title>Test Results</title>
    </head>
    <body>
    <h1>Test Results</h1>
    <hl></hl>
    <ol>
        <?php
        foreach ($steps as $step) {
            echo "<li>$step</li>";
        }
        ?>
    </ol>
    </body>
    </html>
<?php

exit($rc);

function screenshot()
{
    // Find avaliable screenshot number
    $n = 1;
    $dir = "./screenshots/screenshot_";
    while (file_exists($dir . $n . ".png")) {
        $n++;
    }

    return $dir . $n . ".png";
}
