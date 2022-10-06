<?php 
/**
 * 
 * The waseRest.php script receives the RESTful http request (as a POST and GET and PUT, etc., data stream), parses it, 
 * invokes the appropriate function to execute the request, 
 * and then passes the results back to the caller as a json http data stream.  
 * It is typically invoked as:
 *
 * https://serverurl/wasehome/rest/vx/waseRest.php/argument1/argument2/...  (where "x" in vx is a version number).
 * 
 * The invocation HTTP method can be GET, POST, PUT, DELETE.
 *  POST: to create new things.
 *  GET: to retrieve old things.  
 *  PUT: to change/update old things.
 *  DELETE: to delete old things.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */

// Include the Composer autoloader.
require_once ('../../../../vendor/autoload.php');

// Make sure we are up and running; if not, terminate with an error message. 
WaseUtil::Init();

// Make sure we are running under SSL (if required)  
WaseUtil::CheckSSL(); 

// Save the POST data (if any) as a string (this is the raw POST data sent through the HTTP call) and as an object.
$json = trim(file_get_contents("php://input"));
$po = json_decode($json);

// Save the invoking HTTP method (GET|POST|PUT|UPDATE)

$method = $_SERVER['REQUEST_METHOD'];

// Save the NOUN (the object being queried):  CALENDAR, BLOCK, APPOINTMENT.
$matches = array();
if (preg_match('/rest/v1/(.*)/',$_SERVER['PHP_SELF'],$matches))
    $noun = $matches(1);
else  {
    WaseUtil::returnJSON(json_encode(
        array(
            "error"=>array(
                "code"=>1,
                "message"=>"Noun not supplied to restful interface."
            )
        )));
        exit();
}
    

/* Init return code and the error msg and return json. */

$errcode = 0;
$errmsg = '';  
$ret = array();


/* Now return the results to the caller */
$ret = array(
            "error"=>array(
                "code"=>$errcode,
                "message"=>$errcode
                ),
            $ret   
            );

// return json_encode($ret);
WaseUtil::returnJSON(json_encode($ret));

?>