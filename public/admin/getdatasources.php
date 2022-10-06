<?php



// Blackboard definitions
// URL of Blackboard server
define('BLACKBOARD_URL','https://princetonqual.blackboard.com');
// Context XML wsdl file
define('BLACKBOARD_CONTEXT_XML','https://princetonqual.blackboard.com/webapps/ws/services/Context.WS?wsdl');
// define('BLACKBOARD_XML','adduser.xml');
// Course Membership XML file
define('BLACKBOARD_MEMBERSHIP_XML','https://princetonqual.blackboard.com/webapps/ws/services/CourseMembership.WS?wsdl');
// User XML file
define('BLACKBOARD_USER_XML','https://princetonqual.blackboard.com/webapps/ws/services/User.WS?wsdl');
define('BLACKBOARD_UTIL_XML','https://princetonqual.blackboard.com/webapps/ws/services/Util.WS?wsdl');
// Shared secret
define('BLACKBOARD_SHARED_SECRET','hdbcheeids12wdahe');
// Vendor ID
define('BLACKBOARD_VENDOR_ID','Princeton University');
// Program ID
define('BLACKBOARD_PROGRAM_ID','GetDataSources');
// define Blackboard tool description
define("BLACKBOARD_TOOL_DESCRIPTION","A tool to list data sources.");
// Proxy Tool registration password for Learn 9 server
define("BLACKBOARD_REGISTRATION_PASSWORD","akl390g2kdalb3");
// Blackboard login type: either "tool" or "user"
define("BLACKBOARD_LOGIN_TYPE","tool");
// Possible values for Blackboard login type
define("BLACKBOARD_LOGIN_TYPE_TOOL","tool");
define("BLACKBOARD_LOGIN_TYPE_USER","user");
// Login username, if login type is "user"
define('BLACKBOARD_LOGIN_USERNAME','adduser');
// Login password, if login type is "user"
define('BLACKBOARD_LOGIN_PASSWORD','fkai294s,kjaio:ks');
// Whether to verify SSL certificates (set to FALSE to allow access to a server with a self-signed certificate
define('VERIFY_SSL', FALSE);




// Include the Composer autoloader.
require_once ('../../vendor/autoload.php');


// Load dependent library files
require_once('../../libraries/Blackboard/lib.php');
require_once('../../libraries/Blackboard/soap-wsse.php');
// require_once('../../libraries/Blackboard/xmlseclibs.php');

session_start();

// For Blackboard, create an array that disables certificate validation on Blackboard web services calls.
$bboptions = array();
$bboptions['stream_context'] = stream_context_create(
    array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        )
    )
);
// Init Blackboard variables.
$bberror='';
$username = '';  $password = '';


// die(bb_register()); 

$request = new stdClass();
$request->ids='';
$cclient = bb_init();
 
 try {
      $context_client = new BbSoapClient(BLACKBOARD_UTIL_XML, $bboptions);
    } catch (Exception $e) {
     die("Error creating Blackboard util client: {$e->getMessage()}\n");
    }

bb_login($cclient);

try {
      $dclient = new BbSoapClient(BLACKBOARD_UTIL_XML, $bboptions);
    } catch (Exception $e) {
      die("Error creating Blackboard util client: {$e->getMessage()}\n");
    }
       
$result = $dclient->getDataSources($request);

$sources = $result->return;
foreach($sources as $source) {
    echo 'Source: '.$source->batchUid.' ('.$source->description.') = ' . $source->id.'<br />';
}

exit();


function bb_register()
{
     
    global $bberror, $bboptions, $username, $password;
     
    // Set SOAP username/password
    $username = 'session';
    $password = 'nosession';


    // Initialise a Context SOAP client object
    if (! $context_client = bb_init()) {
        return $bberror;
    }


    if ($bberror)
        return $bberror;

    $register_tool = new stdClass();
    $register_tool->clientVendorId = BLACKBOARD_VENDOR_ID;
    $register_tool->clientProgramId = BLACKBOARD_PROGRAM_ID;
    // $register_tool->registrationPassword = BLACKBOARD_REGISTRATION_PASSWORD;
    $register_tool->registrationPassword = '';
    $register_tool->description = BLACKBOARD_TOOL_DESCRIPTION;
    $register_tool->initialSharedSecret = BLACKBOARD_SHARED_SECRET;
    $register_tool->requiredToolMethods = array('Util.WS:getDataSources');
    
    try {
        $result = $context_client->registerTool($register_tool);
        $ok = $result->return->status;
        if ($ok) {
            return "Success (now make the proxy tool available in Learn 9).";
        } else {
            $err = TRUE;
            return "Failed (may already be registered)\n";
        }
    } catch (Exception $e) {
        $err = TRUE;
        return "Blackboard tool registration error: {$e->getMessage()}";
    }
}


/**
 * Initialize the soap context.
 *
 * @static @private
 *
 * @param string $error
 *            a pointer to a string variable into which an error message can be returned
 *
 *
 * @return bool|BbSoapClient FALSE if failure, else a BbSoapClient object
 *
 *
 */
function bb_init()
{

    global $bberror, $bboptions, $username, $password;

    // Global variables for SOAP username and password
    $username = 'session';
    $password = 'nosession';
     
    try {
      $context_client = new BbSoapClient(BLACKBOARD_CONTEXT_XML, $bboptions);
    } catch (Exception $e) {
      $context_client = FALSE;
      $bberror = "Error creating Blackboard util client: {$e->getMessage()}\n";
    }

    return $context_client;  
}

/**
 * Login to Blackboard as a tool.
 *
 * @static @private
 *
 * @param string $context_client
 *            the SOAP object returned from init().
 *
 * @return string null if successful, else error message.
 *
 */
function bb_login($context_client)
{
    global $bberror, $bboptions, $username, $password;
    
    $username = 'session';
    $password = 'nosession';
    
        
    // Get a session ID
    try {
        $result = $context_client->initialize();
        $password = $result->return;
    } catch (Exception $e) {
        return "Error initializing Blackboard context client: {$e->getMessage()}\n";
    }
      
    if (BLACKBOARD_LOGIN_TYPE == BLACKBOARD_LOGIN_TYPE_TOOL) {
        // Log in as a tool
        $input = new stdClass();
        $input->password = BLACKBOARD_SHARED_SECRET;
        $input->clientVendorId = BLACKBOARD_VENDOR_ID;
        $input->clientProgramId =BLACKBOARD_PROGRAM_ID;
        $input->loginExtraInfo = ''; // not used but must not be NULL
        $input->expectedLifeSeconds = 3600;
        try {
            $result = $context_client->loginTool($input);
            if ($result->return  & $result->return != 1)
                return "LOGIN RESULT ERROR: " . print_r($result,true);
        } catch (Exception $e) {
            return "LOGIN ERROR: {$e->getMessage()}\n";
        }
    }   
    else {
        // Log in as a user
        $input = new stdClass();
        $input->userid = BLACKBOARD_LOGIN_USERNAME; 
        $input->password = BLACKBOARD_LOGIN_PASSWORD;
        $input->clientVendorId = BLACKBOARD_VENDOR_ID;
        $input->clientProgramId = BLACKBOARD_PROGRAM_ID;
        $input->loginExtraInfo = ''; // not used but must not be NULL
        $input->expectedLifeSeconds = 3600;
        try {
            $result = $context_client->login($input);
        } catch (Exception $e) {
            return "LOGIN ERROR: {$e->getMessage()}\n";
        }
    }
    
    return '';
}


?>
