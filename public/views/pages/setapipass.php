<?php

/**
 * This script generates and saves an api password for a user.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */

// Start session support.
session_start();

// Include the supprt files.
include '_header.php';


/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();


// Make sure we are running under SSL.
WaseUtil::CheckSSL();

// Get our directory class
$directory = WaseDirectoryFactory::getDirectory();

// Force a login.
$directory->authenticate($_SERVER['REQUEST_URI']);

if (!$_SESSION['authid'])
    die('authentication failed');
      

// Generate an api password.
$api = generateStrongPassword(10, false, 'lud');

// Save the password
$ret = WasePrefs::savePref($_SESSION['authid'],'apipassword',$api);

//Let the user know
echo '<html><head></head><body>Api passsword='.$api.'<br /><br/>Save this (or generate a new one by calling this script again).</body></html>';

exit();


// Generates a strong password of N length containing at least one lower case letter,
// one uppercase letter, one digit, and one special character. The remaining characters
// in the password are chosen at random from those four sets.
//
// The available characters in each set are user friendly - there are no ambiguous
// characters such as i, l, 1, o, 0, etc. This, coupled with the $add_dashes option,
// makes it much easier for users to manually type or speak their passwords.
//
// Note: the $add_dashes option will increase the length of the password by
// floor(sqrt(N)) characters.
function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds')
{
	$sets = array();
	if(strpos($available_sets, 'l') !== false)
		$sets[] = 'abcdefghjkmnpqrstuvwxyz';
	if(strpos($available_sets, 'u') !== false)
		$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	if(strpos($available_sets, 'd') !== false)
		$sets[] = '23456789';
	if(strpos($available_sets, 's') !== false)
		$sets[] = '!@#$%&*?';
	$all = '';
	$password = '';
	foreach($sets as $set)
	{
		$password .= $set[array_rand(str_split($set))];
		$all .= $set;
	}
	$all = str_split($all);
	for($i = 0; $i < $length - count($sets); $i++)
		$password .= $all[array_rand($all)];
	$password = str_shuffle($password);
	if(!$add_dashes)
		return $password;
	$dash_len = floor(sqrt($length));
	$dash_str = '';
	while(strlen($password) > $dash_len)
	{
		$dash_str .= substr($password, 0, $dash_len) . '-';
		$password = substr($password, $dash_len);
	}
	$dash_str .= $password;
	return $dash_str;
}


exit();