<?php

/**
 * This script send back the WASE doc file to the caller.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */


// Include the Composer autoloader.  
require_once ('../../vendor/autoload.php');

// Make sure we are running under SSL.
WaseUtil::CheckSSL();

// Set up the headaers, then return the file
header( "Content-type: application/pdf" );
header( "Content-disposition: attachment; filename=WASEAdmin.pdf" );
echo file_get_contents( "../docs/WASE.pdf" );
// All done
exit();