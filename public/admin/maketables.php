<?php
/*
 * Copyright 2006, 2008, 2013 The Trustees of Princeton University.
 *
 * For licensing terms, see the license.txt file in the docs directory.
 *
 * Written by: Serge J. Goldstein, serge@princeton.edu.
 * Kelly D. Cole, kellyc@princeton.edu
 * Jill Moraca, jmoraca@princeton.edu
 */

/* This routine creates the MySQL tables used by WASE */

/* Handle loading of classes. */
require_once ('autoload.include.php');

/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';
$infmsg = '';

/* See if argument passed from the command line */
if ($_SERVER['argc'] > 1) {
    list ($secret, $pass) = explode("=", $_SERVER['argv'][1]);
    if (strtoupper($secret) == 'SECRET')
        $_REQUEST['secret'] = $pass;
    elseif ($pass == '')
        $_REQUEST['secret'] = $secret;
}


/* Make sure user is authorized. */
WaseUtil::CheckAdminAuth();

// Bring in the MySQL table definitions and table alteration statements.
require_once("tables.php");

/* Build the tables */
echo '<h2>Creating Tables</h2><br />';
$needtoalter = false;
$tablesmade = 0;
foreach ($tables as $table) {
    $varname = '$d_' . $table;
    $res = '';
    eval('$res = WaseSQL::doQuery(' . $varname . ');');
    if ($res) {
        echo "$table table built.<br />";
        $tablesmade++;
        eval('$o_' . $table . ' = true;');
    } else {
        echo "$table table NOT built. " . WaseSQL::error() . "<br />";
        $needtoalter = true;
    }
}
echo $tablesamde . ' tables(s) built.<br /><br />'; 


/* Alter the tables as needed */
if ($needtoalter) {
    echo '<h2>Altering Existing Tables</h2><br />';
    foreach ($al as $a) {
        if (!$res = WaseSQL::doQuery($a))
            $err = 'ERROR: ' . WaseSQL::error();
        else
            $err = 'Done';
        echo $a. $err. '<br />';  
    }
    echo '<br /><br />';
}


/* All done */
echo '<h2>All Done</h2>';
echo "<p>Execution completed.  If errors were reported, and you are running an installation (you are NOT upgrading from an earlier version), you should quit out of the installation, fix the problem(s), then re-run the installation script.</p><p>If you are upgrading from an earlier release, and the instructions said you should run this script, it is likely that your tables are up-to-date and do not need to be altered.</p><p>
If this script opened in a new browser window (e.g., during instllation of WASE), you should close this window to continue with the installation.</p>";

exit();

function sqlQuery($table, $query)
{
    $res = WaseSQL::doQuery($query);
    if ($res)
        echo $table . ' table changed as follows: ' . $query . "<br />";
    else
        echo $table . ' table NOT changed: ' . WaseSQL::error() . "<br />";
}

?>