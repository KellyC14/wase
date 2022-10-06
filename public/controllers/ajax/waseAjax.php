<?php
/**
 *
 * The waseAjax.php script receives the AJAX http request (as a POST data stream), parses it,
 * invokes the appropriate function to execute the request,
 * and then passes the results back to the caller as an AJAX http data stream.
 * It is typically invoked as:
 *
 * https://serverurl/wasehome/controllers/ajax/waseAjax.php
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
define('XMLHEADER', '<?xml version="1.0" encoding="UTF-8"?>');
define('XMLTRAILER', '');

// We no longer use this function
function __oldautoload($class)
{
    /* Set path to waseParms and WaseLocal */
    if ($class == 'waseParms')
        $parmspath = '../../../config/';
    else
        $parmspath = '../../../models/classes/';
    
    /* Now load the class */
    if ($class != 'WaseLocal')
        require_once ($parmspath . $class . '.php');
    else
        @include_once ($parmspath . $class . '.php');
}

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Special case code for CAS */
// require_once('../../../libraries/CAS/CAS.php');
// phpCAS::setDebug('casdebug');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/*
 * Save the POST data as a string (this is the raw xml sent through the Ajax call).
 */

/* $ajaxml = $HTTP_RAW_POST_DATA; */

$input = trim(file_get_contents("php://input"));

// Determine if caller wants json from the accept header, if any.
$wantjson = false;
if (key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
    if (strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false)
        $wantjson = true;
}

/* Init the return stream buffer */
$retbuf = XMLHEADER;

/* If no stream, return nothing */
if (! $input) {
    ($wantjson) ? WaseUtil::returnJSON('{}') : WaseUtil::returnXML($retbuf . '<wase></wase>' . XMLTRAILER);
    exit();
}

// If user is talking JSON, then we need to convert the inout stream to xml
if ($wantjson) {
    // Convert input json into an associative array
    $array = json_decode($input, true);
    // Init the xml
    $xml = new SimpleXMLElement('<xml></xml>');
    // Now convert the input json into an xml stream
    array_to_xml($array, $xml);
    // Now convert to an xml string
    $ajaxml = $xml->wase->asXML();
} else
    $ajaxml = $input;

/* In case we got multiple <wase> streams, split them into individual units. */
$streams = explode('</wase>', $ajaxml);

// Turn on/off object caching
WaseCache::$caching = true;
// WaseCache::clear();
WaseCache::$hits = 0;
WaseCache::$loging = false;
WaseCache::$getloging = false;

foreach ($streams as $stream) {
    
    /* Ignore blank/null streams */
    if (! $stream = trim($stream))
        continue;
    
    /* Put back ending tag */
    $stream .= '</wase>';
    
    /* Start the response */
    $retbuf .= '<wase>';
    
    /* Convert the xml into a SimpleXMLElement object. */
    
    if (! $sme = simplexml_load_string($stream, "SimpleXMLElement", LIBXML_NOWARNING)) {
        $retbuf .= WaseUtil::Error(3, array(
            $stream,
            $ajaxml
        ), 'AJAXERR') . '</wase>';
        continue;
    }
    
    /* Dump all of the objects (requests) in the stream */
    $requests = get_object_vars($sme);
    
    // Now call the functions
    if ($requests)
        foreach ($requests as $key => $val) {
            $func = "do" . $key;
            if (! function_exists($func)) {
                $retbuf .= WaseUtil::Error(39, array(
                    $func
                ), 'AJAXERR');
                continue;
            }
            if (is_array($val)) {
                if ($val)
                    foreach ($val as $value) {
                        $retbuf .= "<" . $key . ">" . $func($value) . "</" . $key . ">";
                    }
            } else {
                $retbuf .= "<" . $key . ">" . $func($val) . "</" . $key . ">";
            }
        }
    
    // End the response
    $retbuf .= '</wase>';
    
    // Get the next request
    continue;
}

// Turn off object caching
WaseCache::$caching = false;

/* Now return the results to the caller */
if (! $wantjson)
    WaseUtil::returnXML($retbuf . XMLTRAILER);
else
    // Convert XML to a json stream
    WaseUtil::returnJSON(str_replace(':{}', ':""', json_encode(simplexml_load_string($retbuf))));

exit();

/**
 * Authenticate (establish a session).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doauthenticate($child)
{
    global $stream, $sessionid;
    // Flag "return here"
    @session_start();
    $_SESSION['redirurl'] = 'https://wase.princeton.edu/' . $_SERVER['INSTITUTION'] . '/controllers/waseAjax.php';
    
    /* Now complete the login data stream. */
    return WaseUtil::Error(0, '', 'AJAXERR') . $stream . '<sessionid>' . $sessionid . '</sessionid>';
}

/**
 * Login (establish a session).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dologin($child)
{

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    /* Save the parameters */
    $userid = trim((string) $child->userid);
    $password = trim((string) $child->password);
    $email = trim((string) $child->email);
    
    /* Init sessionid return variable */
    $sessionid = '';
    
    /* If a userid passed, validate it */
    if ($userid) {
        // First, see if user has set an api password
        if ($api = WasePrefs::getPref($userid, 'apipassword')) {
            if ($api != $password)
                return WaseUtil::Error(6, '', 'AJAXERR');
        } /* If no api password, check directory password */
        elseif (!$directory->idCheck($userid, $password))
            return WaseUtil::Error(6, '', 'AJAXERR');
    } /* If no userid, but an email, then it is a guest or "secret" login */
elseif ($email) {
        /* See if "secret" provided */
    if ($directory->idCheck($email, $password)) {
            $userid = $email;
            $email = '';
        }
        /* Else, just a guest login */
    } /* If no userid or email, go authenticate */
else
    $directory->authenticate();
    
    /* Start the session and save the session id */
    @session_start();
    $sessionid = session_id();
    
    /* Now save the session variables */
    if ($userid) {
        $_SESSION['authtype'] = 'user';
        $_SESSION['authid'] = $userid;
        $stream = '<userid>' . $userid . '</userid><password></password>';
    } else {
        $_SESSION['authtype'] = 'guest';
        $_SESSION['authid'] = $email;
        $stream = '<email>' . $email . '</email><password></password>';
    }
    $_SESSION['authenticated'] = true;
    $_SESSION['anon'] = false;
    $_SESSION['INSTITUTION'] = $_SERVER['INSTITUTION'];
    
    /* Now complete the login data stream. */
    return WaseUtil::Error(0, '', 'AJAXERR') . $stream . '<sessionid>' . $sessionid . '</sessionid>';
}

/**
 * This function returns a list of named dates (if any).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetnameddates($child)
{
    /* Grab the sessionid */
    // $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    // if ($err = testauth($sessionid, 'seticalpass'))
    // return $err;
    $nameddates = '';
    
    if (($d = WaseUtil::getParm('CURTERMSTART')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>Start_of_Term</name></nameddate>';
    if (($d = WaseUtil::getParm('CURTERMEND')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>End_of_Term</name></nameddate>';
    if (($d = WaseUtil::getParm('NEXTTERMSTART')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>Start_of_Next_Term</name></nameddate>';
    if (($d = WaseUtil::getParm('NEXTTERMEND')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>End_of_Next_Term</name></nameddate>';
    if (($d = WaseUtil::getParm('CURYEARSTART')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>Start_of_Academic_Year</name></nameddate>';
    if (($d = WaseUtil::getParm('CURYEAREND')) && ! WaseUtil::beforeToday($d))
        $nameddates .= '<nameddate><date>' . $d . '</date><name>End_Of_Academic_Year</name></nameddate>';
    
    // Let the caller know
    return WaseUtil::Error(0, '', 'AJAXERR') . $nameddates;
}

/**
 * This function returns a list of labels for the items that WASE manipulates.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetlabels($child)
{
    /* Grab the sessionid */
    // $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    // if ($err = testauth($sessionid, 'seticalpass'))
    // return $err;
    
    // Determine if calendar or block passed
    if ($calid = trim($child->calendarid))
        $idarray['calid'] = $calid;
    elseif ($calid = trim($child->calid))
        $idarray['calid'] = $calid;
    elseif ($calid = trim($child->blockid))
        $idarray['blockid'] = trim($child->blockid);
    elseif ($calid = trim($child->appointmentid))
        $idarray['appointmentid'] = trim($child->appointmentid);
    else
        $idarray = array();
    
    // Get the labels
    $labels = WaseUtil::getLabels($idarray);
    
    // Let the data
    return WaseUtil::Error(0, '', 'AJAXERR') . '<labels>' . '<NAMETHING>' . $labels['NAMETHING'] . '</NAMETHING>' . '<NAMETHINGS>' . $labels['NAMETHINGS'] . '</NAMETHINGS>' . '<APPTHING>' . $labels['APPTHING'] . '</APPTHING>' . '<APPTHINGS>' . $labels['APPTHINGS'] . '</APPTHINGS>' . '</labels>';
}

/**
 * Return a didyouknow entry.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetdidyouknow($child)
{
    
    /* Start the session and save the session id */
    @session_start();
    $sessionid = session_id();
    
    /* Extract the parameters */
    $didyouknowid = trim($child->didyouknowid);
    $dateadded = trim($child->dateadded);
    $release = trim($child->release);
    $topics = trim($child->topics);
    
    /* Topics can be a blank-separated list */
    $alltopics = explode(' ', $topics);
    if ($alltopics)
        $alltopics = implode(',', $alltopics);
    
    /* Start output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Get a WaseList of all matching entries */
    $entries = WaseDidYouKnow::getEntries($dateadded, $release, $alltopics);
    $entry_count = $entries->entries();
    
    /* Get list of entries already shown */
    $allready_seen = $_SESSION['didyouknow'];
    if ($allready_seen)
        $allready_seen = explode(',', $allready_seen);
    else
        $allready_seen = array();
    $seen_count = count($allready_seen);
    
    /* If all entries have been shown, cycle back to the first one */
    if ($seen_count == $entry_count) {
        $allready_seen = array();
        $seen_count = 0;
        $_SESSION['didyouknow'] = '';
    }
    
    /* Find "next" one to show */
    $first = true;
    $match = '';
    if ($entries)
        foreach ($entries as $entry) {
            if ($first) {
                $match = clone $entry;
                $first = false;
            }
            if (! in_array($entry->didyouknowid, $allready_seen)) {
                $match = clone $entry;
                break;
            }
        }
    
    /* Add matching entry, if any (and remember it) */
    if ($match) {
        $ret .= '<didyouknow>' . WaseUtil::objectToXML($match) . '</didyouknow>';
        
        if ($seen_count == 0)
            $allready_seen = array(
                $match->didyouknowid
            );
        else
            $allready_seen[] = $match->didyouknowid;
        $seen_count ++;
        
        if ($seen_count > 1)
            $_SESSION['didyouknow'] = implode(',', $allready_seen);
        else
            $_SESSION['didyouknow'] = $allready_seen[0];
    }
    
    /* Add in total count */
    $ret .= '<total>' . (int) $entry_count . '</total>';
    /* Add in remaining count */
    $ret .= '<remaining>' . max(0, ($entry_count - $seen_count)) . '</remaining>';
    
    return $ret;
}

/**
 * Save a user preferences.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosaveprefs($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'saveprefs'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Get userid for preference */
    $userid = $_SESSION['authid'];
    
    /* Iterate through the pref elements */
    foreach ($child->pref as $pref) {
        $key = trim((string) $pref->keytag);
        $value = trim((string) $pref->val);
        $class = trim((string) $pref->class);
        if (! $class)
            $class = 'user';
        
        /* Save the value */
        $ok = WasePrefs::savePref($userid, $key, $value, $class);
        
        /* Add to output string */
        $ret .= '<pref><keytag>' . WaseUtil::safeXML($key) . '</keytag><val>' . WaseUtil::safeXML($value) . '</val></pref>';
    }
    
    return $ret;
}

/**
 * Get user preferences.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetprefs($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getprefs'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Save userid for prefrences */
    $userid = $_SESSION['authid'];
    
    /* Iterate through the pref elements */
    if ($child->pref)
        foreach ($child->pref as $pref) {
            $key = trim((string) $pref->keytag);
            $class = trim((string) $pref->class);
            if (! $class)
                $class = 'user';
            /* Get */
            $value = WasePrefs::getPref($userid, $key, $class);
            
            /* Add to output string */
            $ret .= '<pref><keytag>' . WaseUtil::safeXML($key) . '</keytag><val>' . WaseUtil::safeXML($value) . '</val></pref>';
        }
    
    return $ret;
}
/**
 * Return user's notify and reminder for a calendar
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output XML to be returned to the caller.
 */
function dogetusercalnotifyremind($child)
{
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getusercalnotifyremind'))
        return $err;

    /* Test non-blank userid */
    if (! $userid = (string) $child->userid)
        return WaseUtil::Error(11, array(
            'userid',
            'getusercalnotifyremind'
        ), 'AJAXERR');

    /* test non-blank calendarid */
    if(!$calendarid = (int) $child->calendarid)
        return WaseUtil::Error(11, array(
            'calendarid',
            'getusercalnotifyremind'
        ), 'AJAXERR');

    // Read in the calendar
    try {
        $calendar = new WaseCalendar('load',
            array('calendarid' => $calendarid));
        } catch (Exception $e) {
            return WaseUtil::Error($e->getCode(), array(
                $e->getMessage()
            ), 'AJAXERR', 'false');
    };

    // If the specified user owns the calendar, then use calendar notify and remind.
    if ($calendar->userid == $userid) {
        $remind = $calendar->remind;
        $notify = $calendar->notify;
    }
    else {
        // If user is a member, return member remind/notity, else return error.
        if (!$member = WaseMember::find($calendarid,$userid))
            return WaseUtil::Error(14,array("$userid is not a member of calendar $calendarid"),'AJAXERR');
        $remind = $member['remind'];
        $notify = $member['notify'];
    }

    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    /*fix bug - before it was +$session+  */
    $ret.='<sessionid>'.$sessionid.'</sessionid><userid>'.$userid.'</userid><calendarid>'.$calendarid.'</calendarid><notify>'.$notify.'</notify>';
    $ret.='<remind>'.$remind.'</remind>';

    return $ret;
}


/**
 * Get all user preferences.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetalluserprefs($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getalluserprefs'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Get all of the prerefences */
    $valarray = WasePrefs::getAllUserPrefs($_SESSION['authid']);
    
    if ($valarray) {
        foreach ($valarray as $key => $value) {
            /* Add to output string */
            $ret .= '<pref><keytag>' . WaseUtil::safeXML($key) . '</keytag><val>' . WaseUtil::safeXML($value) . '</val></pref>';
        }
    }
    
    return $ret;
}

/**
 * Set a session variable.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosetvar($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'setvar'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Iterate through the var elements */
    if ($child->sessionvar)
        foreach ($child->sessionvar as $sessionvar) {
            $var = trim((string) $sessionvar->var);
            $val = trim((string) $sessionvar->val);
            
            /* Save the value */
            $_SESSION[$var] = $val;
            
            /* Add to output string */
            $ret .= '<sessionvar><var>' . WaseUtil::safeXML($var) . '</var><val>' . WaseUtil::safeXML($val) . '</val></sessionvar>';
        }
    
    return $ret;
}

/**
 * Get a session variable.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetvar($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getvar'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Iterate through the var elements */
    if ($child->sessionvar)
        foreach ($child->sessionvar as $sessionvar) {
            /* Add to output string */
            $ret .= '<sessionvar><var>' . WaseUtil::safeXML($sessionvar->var) . '</var><val>' . WaseUtil::safeXML($_SESSION[(string) $sessionvar->var]) . '</val></sessionvar>';
        }
    
    return $ret;
}

/**
 * Get amd clear session variable.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetandclearvar($child)
{
    
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getandclearvar'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Iterate through the var elements */
    if ($child->sessionvar)
        foreach ($child->sessionvar as $sessionvar) {
            $var = (string) $sessionvar->var;
            /* Add to output string */
            $ret .= '<sessionvar><var>' . WaseUtil::safeXML($sessionvar->var) . '</var><val>' . WaseUtil::safeXML($_SESSION[$var]) . '</val></sessionvar>';
            /* Clear the variable */
            $_SESSION[$var] = null;
        }
    
    return $ret;
}

/**
 * Validate a list of users.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dovalidateusers($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    /* Iterate through the user elements */
    if ($child->user)
        foreach ($child->user as $user) {
            $userid = trim((string) $user->userid);
            $password = trim((string) $user->password);
            $ret .= '<user><userid>' . WaseUtil::safeXML($userid) . '</userid><password>' . WaseUtil::safeXML($password) . '</password><isvalid>';
            $guest = false;
            /* Check userid/password if specified */
            if ($password) {
                if ($directory->idCheck($userid, $password))
                    $ok = 'true';
                else
                    $ok = 'false';
            } /* Otherwise just check userid, unless a guest */
            else {
                // If not a guest
                if (strpos($userid, '@') === false) {
                    if ($userid = netidoremail($userid))
                        $ok = 'true';
                    else
                        $ok = false;
                } // Accept any email address
                else {
                    $guest = true;
                    $ok = 'true';
                }
            }

            $ret .= $ok . '</isvalid>';
            /* Now add userinfo field */
            if ($ok == 'true' && ! $guest)
                $ret .= getUserInfo($userid) . '</user>';
            else
                $ret .= '<userinfo></userinfo></user>';
        }
    
    return $ret;
}

/**
 * Validate a list of courses.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dovalidatecourses($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Iterate through the course elements */
    if ($child->course)
        foreach ($child->course as $course) {
            $courseid = trim((string) $course->courseid);
            $ret .= '<course><courseid>' . WaseUtil::safeXML($courseid) . '</courseid><isvalid>';
            /* Check if course is valid */
            if ((WaseUtil::isCourseValid($courseid)) || (WaseUtil::isCourseValid(strtoupper($courseid))))
                $ret .= 'true';
            else
                $ret .= 'false';
            $ret .= '</isvalid></course>';
        }
    
    /* Return the response. */
    return $ret;
}

/**
 * Validate a list of groups
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dovalidategroups($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    /* Iterate through the group elements */
    foreach ($child->group as $group) {
        $groupid = trim((string) $group->groupid);
        $ret .= '<group><groupid>' . trim(WaseUtil::safeXML($groupid)) . '</groupid><isvalid>';
        /* Check if group is valid */
        if ($directory->isGroup($groupid))
            $ret .= 'true';
        else
            $ret .= 'false';
        $ret .= '</isvalid></group>';
    }
    
    /* Return the response. */
    return $ret;
}


/**
 * Validate a status.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output XML to be returned to the caller.
 */
function dovalidatestatuses($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    // Explode the status name and value lists into an array
    $names = explode(',',trim(WaseUtil::getParm('STATUS_NAMES')));
    $values = explode(',',trim(WaseUtil::getParm('STATUS_VALUES')));
    
    foreach ($child->status as $status) { 
        
        $ret .= '<status>';
        
        // Go through the requested statuses
        foreach ($status->statusid as $statusid) {
            
            // Start the return string
            $ret .= '<statusid>' . htmlspecialchars($statusid) . '</statusid><isvalid>';
            
            // Asume status does not exist
            $isvalid = 0;
            $statusid = trim((string) $statusid);
            
            $index = array_search($statusid,$names);
            
            if ($index !== false) {
                if ($values[$index] !== '')
                    $isvalid = 1;
            }
            $ret .= $isvalid . '</isvalid>';
        }
        
        $ret .= '</status>';
    }
        
    
    /* Return the response. */
    return $ret;
    
}

/**
 * Return a list of courses in which user is enrolled.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output json to be returned to the caller.
 */
function dogetmatchingcourses($child)
{
    /* Test authentication */
    $sessionid = trim((string) $child->sessionid);
    if ($err = testauth($sessionid, 'getmatchingcourses'))
        return $err;
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    $search = (string) $child->searchstring;
    
    // Start the return string
    $ret .= '<searchstring>' . htmlspecialchars($search) . '</searchstring><courselist>';
    
    // Get our LMS
    $lms = WaseLMSFactory::getLMS();
    
    // Look up courses
    $courses = array();
    if ($lms) {
        // Get list of courses
        $allcourses = $lms::getEnrollments($_SESSION['authid']);
        // Check for errors
        if ($maybeerr = trim($allcourses[0])) {
            if (($loc = stripos($maybeerr, 'ErrorCodes:')) !== false)
                return WaseUtil::Error(1, array(
                    substr($maybeerr, $loc + 11)
                ), 'AJAXERR', false);
        }
        
        if ($allcourses) {
            $courses = array();
            foreach ($allcourses as $course) {
                list ($name, $id, $title) = explode('|', $course);
                if (! $search || (stripos($id, $search) !== false))
                    $courses[] = $id;
            }
        }
    }
    
    // Encode special characters
    $ncourses = array();
    for ($i = 0; $i < count($courses); $i ++)
        $ncourses[$i] = htmlspecialchars($courses[$i]);
    
    $ret .= json_encode($ncourses);
    
    /* Return the response. */
    return $ret . '</courselist>';
}

/**
 * Return a list of groups
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output json to be returned to the caller.
 */
function dogetmatchinggroups($child)
{
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    $search = (string) $child->searchstring;
    
    // Start the return string
    $ret .= '<searchstring>' . htmlspecialchars($search) . '</searchstring><grouplist>';

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    // Get the groups
    $groups = $directory->someGroups($search);
    $ngroups = array();
    
    // Encode special characters
    for ($i = 0; $i < count($groups); $i ++)
        $ngroups[$i] = htmlspecialchars($groups[$i]);
    
    $ret .= json_encode($ngroups);
    
    /* Return the response. */
    return $ret . '</grouplist>';
}


/**
 * Return a list of statuses
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output to be returned to the caller.
 */
function dogetmatchingstatuses($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    $search = trim((string) $child->searchstring);
    
    // Start the return string
    $ret .= '<searchstring>' . htmlspecialchars($search) . '</searchstring>';
    
    // Get names and values of all statuses 
    $names = explode(',',trim(WaseUtil::getParm('STATUS_NAMES')));
    
    // Compute length of search string
    $slength = strlen($search);
    
    // Init return array
    $mnames = array();
    
    // Now build lists of matching names
    for ($i=0; $i<count($names); $i++) {
        if (substr($names[$i],0,$slength) == $search) {
            $mnames[] = trim($names[$i]);
        }
    }
    // Now complete and return the response.
    return $ret . '<namelist>' . json_encode($mnames) . '</namelist>';
    
}

/**
 * Return a list of netids
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output json to be returned to the caller.
 */
function dogetmatchingusers($child)
{
    
    /* Start building the response */
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    $search = (string) $child->searchstring;
    
    // Start the return string
    $ret .= '<searchstring>' . $search . '</searchstring><userlist>';

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    // Get matching netids
    $userids = $directory->someUsers($search);
    // Get matching emails
    $emails = $directory->someEmails($search);
    // Merge
    $merged = array_values(array_unique(array_merge($userids, $emails)));
    // Return matching values netids/emails
    $ret .= json_encode($merged);

    
    /* Return the response. */
    return $ret . '</userlist>';
}

/**
 * Return a parameter value.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetparameter($child)
{
    
    /* Extract the desired parameter value */
    $parmobject = $child->parameter;
    $parameter = trim(strtoupper((string) $parmobject));
    
    /* Refuse to return the system password */
    if ($parameter == 'PASS')
        $ret = WaseUtil::Error(8, array(
            $parameter
        ), 'AJAXERR');
    /* Return an error if the parameter does not exist */
    elseif (! WaseUtil::isParm($parameter))
        $ret = WaseUtil::Error(8, array(
            $parameter
        ), 'AJAXERR');
    /* Else return the value */
    else
        $ret = WaseUtil::Error(0, array(), 'AJAXERR') . '<parameter>' . WaseUtil::safeXML($parameter) . '</parameter><value>' . WaseUtil::safeXML(WaseUtil::getParm($parameter)) . '</value>';
    
    return $ret;
}

/**
 * Return a pset of parameter values as a JSON string.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output JSON string to be returned to the caller.
 */
function dogetobjectnames($child)
{
    
    /* Return the fixed set of object names. */
    return WaseUtil::Error(0, array(), 'AJAXERR') . '<objectnames>' . '{ "objectnames": {' . '"NETID": "' . WaseUtil::getParm('NETID') . '",' . '"PASSNAME": "' . WaseUtil::getParm('PASSNAME') . '",' . '"SYSID": "' . WaseUtil::getParm('SYSID') . '",' . '"SYSNAME": "' . WaseUtil::getParm('SYSNAME') . '",' . '"NAME": "' . WaseUtil::getParm('NAME') . '",' . '"NAMES": "' . WaseUtil::getParm('NAMES') . '",' . '"APPOINTMENT": "' . WaseUtil::getParm('APPOINTMENT') . '",' . '"APPOINTMENTS": "' . WaseUtil::getParm('APPOINTMENTS') . '",' . '}' . '}' . '</objectnames>';
}

/**
 * Return a daytype value.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetdaytype($child)
{
    /* Get the date requested */
    $date = (string) $child->date;
    
    /* Return the reuested value */
    
    return WaseUtil::Error(0, '', 'AJAXERR') . '<date>' . WaseUtil::safeXML($date) . '</date><daytype>' . WaseUtil::safeXML(WaseAcCal::getDaytype($date)) . '</daytype>';
}

/**
 * Return indication of whether/not calendar is viewable by the authenticated user.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doiscalendarviewable($child)
{
    
    /* Extract the parameters */
    $calendarid = (int) $child->calendarid;
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'iscalendarviewable'))
        return $err;
    
    /* Load the calendar */
    try {
        if (! $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        )))
            return WaseUtil::Error(10, array(
                'calendarid',
                'iscalendarviewable'
            ), 'AJAXERR');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', 'false');
    }
    
    /* Return the availability status. */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><isallowed>' . $calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']) . '</isallowed>';
}

/**
 * Return indication of whether/not block is viewable.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doisblockviewable($child)
{
    
    /* Extract the parameter */
    $blockid = (int) $child->blockid;
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'isblockviewable'))
        return $err;
    
    /* Exit if no blockid passed */
    if (! $blockid)
        return WaseUtil::Error(11, array(
            'Blockid',
            'isblockviewable'
        ), 'AJAXERR');
    
    /* Load the block */
    else
        try {
            if (! $block = new WaseBlock('load', array(
                'blockid' => $blockid
            )))
                return WaseUtil::Error(10, array(
                    'blockid',
                    'isblockviewable'
                ), 'AJAXERR');
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
    
    /* Return the availability status */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><blockid>' . $blockid . '</blockid><isallowed>' . $block->isViewable($_SESSION['authtype'], $_SESSION['authid']) . '</isallowed>';
}

/**
 * Return indication of whether/not user can make appointments for this block.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doisappointmentmakeable($child)
{
    
    /* Extract the parameters */
    $blockid = (int) $child->blockid;
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'isappointmentmakeable'))
        return $err;
    
    /* Exit if no blockid passed */
    if (! $blockid)
        return WaseUtil::Error(11, array(
            'blockid',
            'isappointmentmakeable'
        ), 'AJAXERR');
    
    /* Load the block */
    try {
        if (! $block = new WaseBlock('load', array(
            'blockid' => $blockid
        )))
            return WaseUtil::Error(10, array(
                'blockid',
                'isappointmentmakeable'
            ), 'AJAXERR');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Check if user can make an appointment for this block. */
    $whynot = $block->isMakeable($_SESSION['authtype'], $_SESSION['authid'], "", "");
    if (! $whynot)
        $ok = 1;
    else
        $ok = 0;
    
    /* Return the availability status */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><blockid>' . $blockid . '</blockid><isallowed>' . $ok . '</isallowed><reason>' . WaseUtil::safeXML($whynot) . '</reason>';
}

/**
 * Return count (how many) of makeable appointments.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function domakeableapps($child)
{
    
    /* Extract the parameters */
    $blockid = (int) $child->blockid;
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'makeableapps'))
        return $err;
    
    /* Exit if no blockid passed */
    if (! $blockid)
        return WaseUtil::Error(11, array(
            'blockid',
            'makeableapps'
        ), 'AJAXERR');
    
    /* Load the block */
    try {
        if (! $block = new WaseBlock('load', array(
            'blockid' => $blockid
        )))
            return WaseUtil::Error(10, array(
                'blockid',
                'makeableapps'
            ), 'AJAXERR');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Return the count of available apppointment slots. */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><blockid>' . $blockid . '</blockid><count>' . $block->makeableApps($_SESSION['authtype'], $_SESSION['authid']) . '</count>';
}

/**
 * Return information about a user, different format.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetuserinfo($child)
{
    
    /* Extract the desired userid */
    $userid = trim((string) $child->userid);
    
    if (! $userid)
        return WaseUtil::Error(13, array(
            'userid',
            'getuserinfo'
        ), 'AJAXERR');

    // See if passed user is actually an email address.userid>
     if (!$matchuserid = netidoremail($userid))
        $matchuserid = $userid;

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    /* Return the data. */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<userid>' . $userid . '</userid>' . '<userinfo>' . tag('userid', $matchuserid) . '<name>' . WaseUtil::safeXML($directory->getName($matchuserid)) . '</name>' . '<phone>' . WaseUtil::safeXML($directory->getPhone($matchuserid)) . '</phone>' . '<email>' . WaseUtil::safeXML($directory->getEmail($matchuserid)) . '</email>' . '<office>' . WaseUtil::safeXML($directory->getOffice($matchuserid)) . '</office>' . '</userinfo>';
}

/**
 * Return calendar data for a specified calendarid or userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmatchingcalendars($child)
{
    
    /* Init return string */
    $ret = '';
    
    $userid_list = trim((string) $child->userid);
    $searchstring = trim((string) $child->searchstring);
    $sessionid = trim((string) $child->sessionid);
    $calendarid = trim((string) $child->calendarid);

    // Caller must specify something
    if (!$calendarid && !$userid_list && !$searchstring)
        return WaseUtil::Error(11,array('calendarid, userid or searchstring','getmatchingcalendars'), 'AJAXERR');

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    $select = '';
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmatchingcalendars')) {
        return $err;
    }
    /* Build calendar selection criteria based on arguments. */
    if ($calendarid) {
        $calendars = explode(',', $calendarid);
        if ($calendars)
            foreach ($calendars as $calendar) {
                $calendar = trim($calendar);
                if ($select)
                    $select .= ' OR ';
                $select .= 'calendarid=' . WaseSQL::sqlSafe((int) $calendar);
            }
    } else {
        if ($userid_list) {
            $userids = explode(',', $userid_list);
            if ($userids)
                foreach ($userids as $userid) {
                    if ($select)
                        $select .= ' OR ';
                    $select .= '`userid` =' . WaseSQL::sqlSafe(trim($userid));
                }
        } else {
            //if((preg_match("/$searchstring/i",'calendar for'))) {
                //do not do title search
                //$select = '`userid` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') . ' OR `name` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') ;
            //} else {
                //$select = '`userid` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') . ' OR `name` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') . ' OR `title` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%');
            //}
            $select = '`userid` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') . ' OR `name` LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%') . ' OR replace(`title`,"Calendar for","") LIKE ' . WaseSQL::sqlSafe('%' . $searchstring . '%');
        }
    }
    //WaseMsg::dMsg('DBG','Info',"dogetmatchingcalendar-select[$select]");
    $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE (' . $select . ') ORDER BY `userid`, `title`';
    
    /* Build return header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    if ($calendarid)
        $ret .= '<calendarid>' . $calendarid . '</calendarid>';
    elseif ($userid_list)
        $ret .= '<userid>' . $userid_list . '</userid>';
    else
        $ret .= '<searchstring>' . $searchstring . '</searchstring>';
    
    /* Now build list of matching calendars */
    $calendars = new WaseList($select, 'Calendar');
    $now = date('Y-m-d H:i:s');
    /* Loop through results and encode returned data on matching (viewable) calendars. */
    if ($calendars)
        foreach ($calendars as $calendar) {

            // If caller passed calendarids or a userid+_list, do not check for available blocks
            if ($calendarid  || $userid_list)
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
            else {
                // If search string passed, only show calendars with available blocks.

                // Get count of blocks
                $blocks = WaseBlock::listMatchingBlocks(array(
                        'calendarid' => $calendar->calendarid,
                        'startdatetime,>=' => $now
                    )
                );
                // Ignore calendars that have no (future) blocks, unless wait list is on
                if ($blocks->entries() || $calendar->waitlist) {
                    if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                        $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
                    // Calendar may not be viewable, but it may have blocks that are viewable
                    else {
                        $found = false;
                        foreach ($blocks as $block) {
                            if ($block->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                                $found = true;
                                break;
                            }
                        }
                        if ($found)
                            $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
                    }
                }
            }
        }
    
    /* Now handle calendars the user is managing */
    if ($userid_list) {
        foreach ($userids as $userid) {
            /* Get array listing all users whose calendars the specified user is managing. */
            $managedlist = WaseManager::arrayManagedids($userid);
            /* Go through the list of users being managed (but only once per distinct user) */
            $allreadysaw[$userid] = false;
            if ($managedlist) {
                foreach ($managedlist as $manager) {
                    if (! $allreadysaw[$manager]) {
                        $calendars = WaseCalendar::wlistMatchingCalendars(array(
                            'userid' => $manager
                        ));
                        if ($calendars)
                            foreach ($calendars as $calendar) {
                                if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                                    $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
                            }
                    }
                    $allreadysaw[$manager] = true;
                }
            }
        }
    }
    
    /* Return the response. */
    return $ret;
}

/**
 * Return calendar data for a specified calendarid or userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetcalendars($child)
{
    
    /* Init return string */
    $ret = '';
    
    $calendarid = trim((string) $child->calendarid);
    $userid = trim((string) $child->userid);
    $title = trim((string) $child->title);
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getcalendars'))
        return $err;
    
    /* Build calendar selection criteria based on arguments. */
    if ($calendarid) {
        $calendars = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE calendarid IN (' . $calendarid . ') ORDER BY `userid`, `title`', 'Calendar');
    } elseif ($userid)
        $loadarg = array(
            'userid' => $userid
        );
    elseif ($title)
        $loadarg = array(
            'title' => $title
        );
    else {
        $badxml = $child->asXML();
        return WaseUtil::Error(9, array(
            'getcalendars',
            'no calendarid, userid or title specified'
        ), 'AJAXERR');
    }
    /* Build return header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    if ($calendarid)
        $ret .= '<calendarid>' . $calendarid . '</calendarid>';
    elseif ($userid)
        $ret .= '<userid>' . $userid . '</userid>';
    else
        $ret .= '<title>' . $title . '</title>';
    
    /* Now build list of matching calendars (unless allready built) */
    if (! $calendarid)
        $calendars = WaseCalendar::wlistMatchingCalendars($loadarg);
    
    /* Loop through results and encode returned data on matching (viewable) calendars. */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarInfo() . '</calendar>';
        }
    
    /* Now handle calendars the user is managing */
    if ($userid) {
        /* Get WaseList of all calendars the user is managing. */
        $calendars = WaseManager::wlistManaged($userid);
        /* Go through the list and add the calendar info. */
        if ($calendars)
            foreach ($calendars as $calendar) {
                if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                    $ret .= '<calendar>' . $calendar->xmlCalendarInfo() . '</calendar>';
            }
        
        /* Now get the membered calendars */
        $calendars = WaseMember::wlistMembered($userid);
        /* Go through the list and add the calendar info. */
        if ($calendars)
            foreach ($calendars as $calendar) {
                if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                    $ret .= '<calendar>' . $calendar->xmlCalendarInfo() . '</calendar>';
            }
    }
    
    /* Return the response. */
    return $ret;
}

/**
 * Return calendar data for all owned calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetownedcalendars($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getownedcalendars'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    
    /* Now build list of matching calendars */
    $calendars = WaseCalendar::wlistOwnedCalendars($userid);
    
    /* Loop through results and encode returned data on matching (viewable) calendars. */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfoWithMgrMem() . '</calendar>';
        }
    
    return $ret;
}

/**
 * Return calendar header data for all owned calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetownedcalendarheaders($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getownedcalendarheaders'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    
    /* Now build list of matching calendars */
    $calendars = WaseCalendar::wlistOwnedCalendars($userid);
    
    /* Loop through results and encode returned data on matching (viewable) calendars. */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
        }
    
    return $ret;
}

/**
 * Return calendar data for all managed calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmanagedcalendars($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $status = trim((string) $child->status);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmanagedcalendars'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><status>' . $status . '</status>';
    
    /* Get array listing all calendars the specified user is managing. */
    $calendars = WaseManager::wlistManaged($userid, $status);
    
    /* Go through the calendars being managed */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfoWithMgrMem() . '</calendar>';
        }
    return $ret;
}

/**
 * Return calendar header data for all managed calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmanagedcalendarheaders($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $status = trim((string) $child->status);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmanagedcalendarheaders'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><status>' . $status . '</status>';
    
    /* Get array listing all calendars the specified user is managing. */
    $calendars = WaseManager::wlistManaged($userid, $status);
    
    /* Go through the calendars being managed */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
        }
    return $ret;
}

/**
 * Return calendar data for all member calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmembercalendars($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $status = trim((string) $child->status);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmembercalendars'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><status>' . $status . '</status>';
    
    /* Get array listing all calendars the specified user is managing. */
    $calendars = WaseMember::wlistMembered($userid, $status);
    
    /* Go through the calendars being managed */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfoWithMgrMem() . '</calendar>';
        }
    return $ret;
}

/**
 * Return calendar header data for all membered calendars given a userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmembercalendarheaders($child)
{
    
    /* Extract parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $status = trim((string) $child->status);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmembercalendarheaders'))
        return $err;
    
    /* Init return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><status>' . $status . '</status>';
    
    /* Get array listing all calendars the specified user is managing. */
    $calendars = WaseMember::wlistMembered($userid, $status);
    
    /* Go through the calendars being managed */
    if ($calendars)
        foreach ($calendars as $calendar) {
            if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
        }
    return $ret;
}

/**
 * Create a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddcalendar($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addcalendar'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendar = $child->calendar;
    
    if (! is_object($calendar))
        return WaseUtil::Error(13, array(
            'calendar',
            'addcalendar'
        ), 'AJAXERR');
    
    /*
     * Make sure authenticated user is creating their own calendar or is
     * creating a calendar for someone whose calendar they manage.
     */
    
    $owner = $calendar->owner;
    if ($_SESSION['authtype'] == 'guest')
        return WaseUtil::Error(23, '', 'AJAXERR');
    elseif (strtoupper($_SESSION['authid']) != strtoupper(trim($owner->userid))) {
        /* Check if this user is a manager for target user */
        if (! in_array(strtoupper($_SESSION['authid']), WaseManager::arrayActiveManagers(strtoupper(trim($owner->userid)))))
            return WaseUtil::Error(24, array(
                $_SESSION['authid'],
                trim($owner->userid)
            ), 'AJAXERR');
    }
    
    /* You may not specify a calendarid when creating a calendar */
    if ((string) $calendar->calendarid)
        return WaseUtil::Error(10, array(
            'calendarid',
            'addcalendar',
            (string) $calendar->calendarid
        ), 'AJAXERR');
    
    /* Create the calendar. */
    try {
        $calobject = new WaseCalendar('create', arrayiffy_shortcalendarinfo($calendar));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now save the calendar. */
    try {
        $calid = $calobject->save('create');
        /* Notify sysadmin */
        if (WaseUtil::getParm('MAILCAL')) {
            WaseUtil::Mailer(WaseUtil::getParm('SYSMAIL'), WaseUtil::getParm('SYSID') . ' Calendar created for ' . $calobject->userid . ' (' . $calobject->name . ')', '', 'Reply-To: ' . WaseUtil::getParm('FROMMAIL') . "\r\n");
        }
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    $calobject->calendarid = $calid;
    
    /* Now we need to process the manager data. This is a new calendar, so we will need to add new managers, as requested. */
    
    /* Iterate through managers */
    if ($calendar->manager)
        foreach ($calendar->manager as $manager) {
            /* Create the manager */
            try {
                $manager->calendarid = $calid;
                $managerdata = arrayiffy_managermemberinfo($manager);
                $newmanager = new WaseManager('create', $managerdata);
            } catch (Exception $error) {
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
            /* Now add the manager */
            try {
                $newmanager->save('create');
            } catch (Exception $error) {
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
        }
    
    /* Now we need to process the member data. This is a new calendar, so we will need to add new members, as requested. */
    
    /* Iterate through members */
    if ($calendar->member)
        foreach ($calendar->member as $member) {
            /* Create the member */
            try {
                $member->calendarid = $calid;
                $memberdata = arrayiffy_managermemberinfo($member);
                $newmember = new WaseMember('create', $memberdata);
            } catch (Exception $error) {
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
            /* Now add the member */
            try {
                $newmember->save('create');
            } catch (Exception $error) {
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
        }
    
    // Set an infmsg to let the user know that hey need to add blocks to the calendar.
    $infmsg = "Calendar created.  To add availability for appointments, click on Add Block.";
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg><calendar>' . $calobject->xmlCalendarInfo() . '</calendar>';
}

/**
 * Edit calendar properties.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditcalendar($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editcalendar'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendar = $child->calendar;
    if (! is_object($calendar))
        return WaseUtil::Error(10, array(
            'calendar',
            'editcalendar'
        ), 'AJAXERR');
    
    /* Save 'propagate' status */
    $propagate = (string) $child->propagate;
    
    /* Load up the calendar and update it. */
    try {
        /* Save calendar update array */
        $updates = arrayiffy_shortcalendarinfo($calendar);
        /* Create new. updated version of the calendar */
        $calendarobject = new WaseCalendar('update', $updates);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $calendarobject->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR');
    
    /* Write out the updated calendar */
    try {
        $calendarid = $calendarobject->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    // Init infmsg
    $infmsg = '';
    $propagate = trim($propagate);
    
    /* Propagate changes to future blocks, if requested */
    if ((is_int($propagate) && $propagate == 1) || (is_string($propagate) && $propagate != 'none')) {
        
        /* Build list of affected blocks */
        if ($propagate == 'owner')
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = "' . $calendarobject->userid . '" AND calendarid=' . $updates['calendarid'] . ' AND startdatetime>="' . date('Y-m-d') . '"', 'Block');
        else
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid=' . $updates['calendarid'] . ' AND startdatetime>="' . date('Y-m-d') . '"', 'Block');
        
        /* Init block counter */
        $numblocks = 0;
        
        /* Remove calendarid */
        unset($updates['calendarid']);
        
        /* Go through the blocks and update them. */
        if ($blocks)
            foreach ($blocks as $block) {
                // Copy global updates
                $bupdates = $updates;
                // If block not owned by calendar owner, unset all the user fields.
                if ($calendarobject->userid != $block->userid) {
                    unset($bupdates['userid']);
                    unset($bupdates['name']);
                    unset($bupdates['email']);
                    unset($bupdates['phone']);
                }
                /* We need to add in the block header data */
                $bupdates['blockid'] = $block->blockid;
                try {
                    $block = new WaseBlock('update', $bupdates);
                } catch (Exception $error) {
                    /* If an error, return error code and message */
                    return WaseUtil::Error($error->getCode(), array(
                        $error->getMessage()
                    ), 'AJAXERR', false);
                }
                
                /* Write out the updated block */
                try {
                    $blockid = $block->save('update');
                    $numblocks ++;
                } catch (Exception $error) {
                    /* If an error, return error code and message */
                    return WaseUtil::Error($error->getCode(), array(
                        $error->getMessage()
                    ), 'AJAXERR', false);
                }
            }
        $infmsg = $numblocks . ' block(s) updated.';
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg><calendar>' . $calendarobject->xmlShortCalendarInfo() . '</calendar><propagate>' . (int) $propagate . '</propagate><numblocks>' . $numblocks . '</numblocks>';
}

/**
 * Delete a celendar (and all series/blocks/periods/appointments).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeletecalendar($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deletecalendar'))
        return $err;
    
    /* Get the seriesid and cancel text */
    $calendarid = $child->calendarid;
    if (! $calendarid)
        return WaseUtil::Error(13, array(
            'calendarid',
            'deletecalendar'
        ), 'AJAXERR');
    
    $canceltext = $child->canceltext;
    
    /* Load up the calendar */
    try {
        $calobject = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $calobject->canDelete($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR', false);
    
    /* Now delete the calendar (this will delete all blocks and appointments). */
    $calobject->delete($canceltext);
    /* Notify sysadmin */
    if (WaseUtil::getParm('MAILCAL')) {
        WaseUtil::Mailer(WaseUtil::getParm('SYSMAIL'), 'Calendar deleted for ' . $calobject->userid . ' (' . $calobject->name . ')', '', 'Reply-To: ' . WaseUtil::getParm('FROMMAIL') . "\r\n");
    }
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><canceltext>' . $canceltext . '</canceltext>';
}

/**
 * Sync a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosynccalendar($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'synccalendar'))
        return $err;
    
    /* Get userid */
    $userid = (string) $child->userid;
    
    /* Get the calendar and load the calendar */
    $calendarid = (int) $child->calendarid;
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $calendar->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR');
    
    /* Get list of blocks as per the whichblocks argument */
    $whichblocks = (string) $child->whichblocks;
    switch ($whichblocks) {
        case 'all':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid=' . $calendar->calendarid . ' ORDER BY startdatetime ASC', 'Block');
            break;
        case 'fromtoday':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid=' . $calendar->calendarid . ' AND date(`startdatetime`) >="' . date('Y-m-d') . '" ORDER BY startdatetime ASC', 'Block');
            break;
        default:
            return WaseUtil::Error(36, array(
                $child->whichblocks
            ), 'AJAXERR');
            break;
    }
    
    /* Get the user's sync preference */
    $syncpref = WasePrefs::getPref($userid, 'localcal');
    
    $syncdone = false;
    $syncfail = '';
    
    $ical = '';
    
    // Init counters
    $blocksdone = 0;
    $appsdone = 0;
    
    // For iCal, just call the allCalendar function if they want the entire calendar.
    if ($syncpref != 'google' && $syncpref != 'exchange' && $whichblocks == 'all') {
        $ical = WaseIcal::allCalendar($calendar->calendarid);
        // Compute count of blocks and apps
        $blocksdone = substr_count($ical, "CATEGORIES:OFFICEHOURS\r\n");
        $appsdone = substr_count($ical, "CATEGORIES:APPOINTMENT\r\n");
        $syncdone = true;
    }    /* Go through the blocks and synchronize as requested */
    elseif ($blocks)
        foreach ($blocks as $newblock) {
            // Get all of the appointments
            $allapps = allappsforblock($newblock->blockid);
            
            /* If user wants Google synchronization, go do it */
            switch ($syncpref) {
                case 'google':
                    $syncdone = WaseGoogle::addBlock($newblock);
                    /* Save synchronization done status */
                    if (! $syncdone)
                        $syncfail = 'Google';
                    else {
                        $blocksdone ++;
                        foreach ($allapps as $app) {
                            $appdone = WaseGoogle::addApp($app, $newblock, $newblock->userid);
                            $appsdone ++;
                        }
                    }
                    break;
                
                case 'exchange':
                    $syncdone = WaseExchange::addBlock($newblock);
                    /* Save synchronization done status */
                    if (! $syncdone)
                        $syncfail = 'Exchange';
                    else {
                        $blocksdone ++;
                        foreach ($allapps as $app) {
                            WaseExchange::addApp($app, $newblock, $newblock->userid, $newblock->email);
                            $appsdone ++;
                        }
                    }
                    break;
                
                case 'ical':
                    if (! $ical)
                        $ical = WaseIcal::ICALHEADER;
                    $ical .= WaseIcal::addBlock($newblock);
                    $blocksdone ++;
                    foreach ($allapps as $app) {
                        $ical .= WaseIcal::addApp($app, $newblock, 'owner');
                        $appsdone ++;
                    }
                    $syncdone = true;
                    break;
                
                default:
                    break;
            }
        }
    
    // Finish iCal stream, if any
    if ($ical)
        $ical .= WaseIcal::ICALTRAILER;
    
    // Start return string
    if (! $syncdone && $syncfail)
        $ret = WaseUtil::Error(14, array(
            'Unable to synchronize with ' . $syncfail . ': ' . $syncdone
        ), 'AJAXERR');
    else
        $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Now return the status to the caller */
    $ret .= '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><whichblocks>' . $whichblocks . '</whichblocks><calendarid>' . $calendarid . '</calendarid><ical>' . $ical . '</ical>' . '<blocks>' . $blocksdone . '</blocks><apps>' . $appsdone . '</apps>';
    
    return $ret;
}

/**
 * Count blocks/apps to be synced on a calendar sync.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosynccalendarcount($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'synccalendarcount'))
        return $err;
    
    /* Get userid */
    $userid = (string) $child->userid;
    
    /* Get the calendar and load the calendar */
    $calendarid = (int) $child->calendarid;
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    // Init block/apps counters
    $blocksdone = 0;
    $appsdone = 0;
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $calendar->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR');
    
    /* Get list of blocks as per the whichblocks argument */
    $whichblocks = (string) $child->whichblocks;
    switch ($whichblocks) {
        case 'all':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid=' . $calendar->calendarid . ' ORDER BY startdatetime ASC', 'Block');
            break;
        case 'fromtoday':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid=' . $calendar->calendarid . ' AND date(`startdatetime`) >="' . date('Y-m-d') . '" ORDER BY startdatetime ASC', 'Block');
            break;
        default:
            return WaseUtil::Error(36, array(
                $child->whichblocks
            ), 'AJAXERR');
            break;
    }
    
    /* Go through the blocks and count as requested */
    if ($blocks)
        foreach ($blocks as $newblock) {
            // Increment block count
            $blocksdone ++;
            // Get all of the appointments
            $allapps = allappsforblock($newblock->blockid);
            // Add them up
            $appsdone += $allapps->entries();
        }
    
    $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Now return the status to the caller */
    $ret .= '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><whichblocks>' . $whichblocks . '</whichblocks><calendarid>' . $calendarid . '</calendarid><blocks>' . $blocksdone . '</blocks><apps>' . $appsdone . '</apps>';
    
    return $ret;
}

/**
 * Return block header information for a given calendar and date range and time range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockswithslots($child)
{
    
    // NOT CURRENTLY USED
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockswithslots'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((int) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    $blockid = trim((int) $child->blockid);
    
    // Save calling userid
    $userid = trim($_SESSION['authid']);
    
    if (! $enddatetime)
        $enddatetime = '2999-12-31 23:59:59';
    if (! $startdatetime)
        $startdatetime = '2000-01-01 00:00:00';
    
    /* Get list of matching blocks */
    if ($blockid)
        $foundblocks = WaseBlock::listMatchingBlocks(array(
            'blockid' => $blockid
        ));
    else
        $foundblocks = WaseBlock::listOrderedBlocks(array(
            array(
                'calendarid,IN,AND',
                '(' . $calendarid . ')'
            ),
            array(
                'startdatetime,<=,AND',
                trim($enddatetime)
            ),
            array(
                'enddatetime,>=,AND',
                trim($startdatetime)
            )
        ), 'ORDER BY `startdatetime`');
    
    /* Build output header */
    $ret .= '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime>';
    if ($blockid)
        $ret .= '<blockid>' . $blockid . '</blockid>';
    
    /* Go through all of the blocks */
    // Buffer database parm.
    $database = WaseUtil::getParm('DATABASE');
    if ($foundblocks)
        foreach ($foundblocks as $fblock) {
            
            /* Get list of all apps for this block owned either by the logged in user or made with the logged in user */
            $blockapps = userappsforblock($userid, $fblock->blockid);
            
            /* Make sure block is viewable by this user, or user has an appointment in this block */
            if (($entries = $blockapps->entries()) || $fblock->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                /* Generate the XML for the block data */
                $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo() . '</block>';
                
                /* Get list of ALL slots */
                $available = $fblock->allSlots();
                
                /* Add slot data */
               
                if ($available)
                    foreach ($available as $slotstart) {
                        $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $fblock->slotsize);
                        $whynot = $fblock->isMakeable($_SESSION['authtype'], $_SESSION['authid'], $slotstart, $slotend);
                        if ($whynot) {
                            if ($entries)
                                $makeable = 1;
                            else
                                $makeable = 0;
                        } else
                            $makeable = 1;
                        $ret .= '<slot>' . '<blockid>' . $fblock->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime><numappts>' . $entries . '</numappts><makeable>' . $makeable . '</makeable>';
                        /* Now get the appointment data */
                        $apps = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid . ' AND available = 1  AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
                        if ($apps)
                            foreach ($apps as $app) {
                                if ($app->isViewable($_SESSION['authtype'], $_SESSION['authid']))
                                    $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                                else
                                    $ret .= '<appt>' . $app->xmlMaskedApptHeaderInfo() . '</appt>';
                            }
                        $ret .= '</slot>';
                    }
                $ret .= '</blockswithslots>';
            }
        }
    
    /* Return the response */
    return $ret;
}

/**
 * Return block and appointment header information for a given calendar and date range and time range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockswithslotsandmyappts($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockswithslotsandmyappts'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((int) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    $blockid = trim((int) $child->blockid);
    $userid = trim((string) $child->userid);
    //WaseMsg::dMsg('DBG','Info',"calendarid[$calendarid]blockid[$blockid]userid[$userid]");

    if (! $enddatetime)
        $enddatetime = '2999-12-31 23:59:59';
    if (! $startdatetime)
        $startdatetime = '2000-01-01 00:00:00';
    
    /* Get list of matching blocks */
    if ($blockid)
        $foundblocks = WaseBlock::listMatchingBlocks(array(
            'blockid' => $blockid
        ));
    else
        /* change enddatetime >= to > */
        $foundblocks = WaseBlock::listOrderedBlocks(array(
            array(
                'calendarid,IN,AND',
                '(' . $calendarid . ')'
            ),
            array(
                'startdatetime,<=,AND',
                trim($enddatetime)
            ),
            array(
                'enddatetime,>,AND',
                trim($startdatetime)
            )
        ), 'ORDER BY `startdatetime`');
    /* Build output header */
    $ret .= '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime>';
    if ($blockid)
        $ret .= '<blockid>' . $blockid . '</blockid>';
    
    if ($userid)
        $ret .= '<userid>' . $userid . '</userid>';
    else
        $userid = trim($_SESSION['authid']);
    
    /* Go through all of the blocks */
    if ($foundblocks)
        foreach ($foundblocks as $fblock) {
            
            /*
             * Get list of all apps for this block owned either by the logged in user or with the logged in user,
             * unless showappinfo is on, in which case all apps should be returned
             */
            // if ($fblock->showappinfo)
            // $blockapps = allappsforblock($fblock->blockid);
            // else
            $blockapps = userappsforblock($userid, $fblock->blockid);
            
            // If user has apps in the block, or if they can make appointments in the block:
            if ($blockapps->entries() || $fblock->isMakeable($_SESSION['authtype'], $userid, '', '') == "") {
                
                /* Generate the XML for the block data */
                $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo();
                // Set a flag for makeability in the block, and add showappinfo flag
                $ret .= '<makeable>B' . $fblock->blockid . '</makeable><showappinfo>' . $fblock->showappinfo . '</showappinfo></block>';
                $bmakeableset = false;
                
                /* Get list of all slots for this block */
                $available = $fblock->allSlots();
                /* Add slot data: for all slots in the block */
                if ($available)
                    foreach ($available as $slotstart) {
                        /* Compute slot end time */
                        $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $fblock->slotsize);
                        // $time0 = "startdatetime=\"$slotstart\" and enddatetime=\"$slotend\"";
                        // $time1 = "startdatetime<=\"$slotstart\" and enddatetime>\"$slotend\"";
                        // $time2 = "startdatetime<\"$slotstart\" and enddatetime>=\"$slotend\"";
                        // $time_sql = "( ($time0) or ( $time1) or  ( $time2) )";
                        /* Get list of all apps for this slot */
                        // $allslotapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid = ' . $fblock->blockid . ' AND startdatetime >="' . $slotstart . '" AND enddatetime<="' . $slotend . '")', 'Appointment');
                        // $jc_allslotapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE blockid = ' . $fblock->blockid. ' and ('.$time_sql. ')'  , 'Appointment');
                        $allslotapps = allappsforslot($fblock->blockid, $slotstart, $slotend);
                        /* Now get list of user apps for this slot */
                        // $userslotapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (((userid=' . WaseSQL::sqlSafe($_SESSION['authid']) . ' OR (blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($_SESSION['authid']) . '))) AND blockid=' . $fblock->blockid . ') AND startdatetime >="' . $slotstart . '" AND enddatetime<="' . $slotend . '")', 'Appointment');
                        // $jc_userslotapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE userid=' . WaseSQL::sqlSafe($_SESSION['authid']).' and blockid ='.$fblock->blockid. ' and ('.$time_sql. ')', 'Appointment');
                        $userslotapps = userappsforslot($userid, $fblock->blockid, $slotstart, $slotend);
                        /* Determine if user can make apps in this block. */
                        $whynot = $fblock->isMakeable($_SESSION['authtype'], $userid, $slotstart, $slotend);
                        if ($whynot)
                            $makeable = 0;
                        else
                            $makeable = 1;
                        
                        // Get count of user apps for this slot.
                        $entries = $userslotapps->entries();
                        
                        // Get count of "available" apps for this slot.
                        $numappts = 0;
                        foreach ($allslotapps as $slotapp) {
                            if ($slotapp->available)
                                $numappts ++;
                        }
                        
                        // If user can make apps or has apps in the slot:
                        if ($makeable || $entries) {
                            
                            // Flag that user can make apps in the block, or has apps in the block
                            if (! $bmakeableset) {
                                $ret = str_replace('<makeable>B' . $fblock->blockid . '</makeable><showappinfo>', '<makeable>1</makeable><showappinfo>', $ret);
                                $bmakeableset = true;
                            }
                            
                            if ($makeable)
                                $makeable = 1;
                            else
                                $makeable = 0;
                            $numappslots=$fblock->slotsMade($_SESSION['authtype'], $userid);
                            //WaseMsg::dMsg('DBG','Info', 'numappslots '.$numappslots);
                            $ret .= '<slot>' . '<blockid>' . $fblock->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime>' . '<numappts>' . $numappts . '</numappts><numapptslots>'.$numappslots.'</numapptslots><makeable>' . $makeable . '</makeable><showappinfo>' . $fblock->showappinfo . '</showappinfo>';
                            
                            if ($userslotapps)
                                foreach ($userslotapps as $app) {
                                    if ($app->isViewable($_SESSION['authtype'], $userid) || $fblock->showappinfo) {
                                        $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                                    } else {
                                        $ret .= '<appt>' . $app->xmlMaskedApptHeaderInfo() . '</appt>';
                                    }
                                }
                            $ret .= '</slot>';
                        }
                    }
                
                if (! $bmakeableset)
                    $ret = str_replace('<makeable>B' . $fblock->blockid . '</makeable><showappinfo>', '<makeable>0</makeable><showappinfo>', $ret);
                
                $ret .= '</blockswithslots>';
            }
        }
    
    /* Return the response */
    
    return $ret;
}

/**
 * Return number of blocks for a given calendar and date range and time range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockcount($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockcount'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((int) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    
    if (! $enddatetime)
        $enddatetime = '2999-12-31 23:59:59';
    if (! $startdatetime)
        $startdatetime = '2000-01-01 00:00:00';
    
    // Read in the calendar
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $e) {
        return WaseUtil::Error(10, array(
            'calendarid',
            'getblockcount'
        ), 'AJAXERR');
    }
    
    // Get list of future blocks
    $foundblocks = WaseBlock::listOrderedBlocks(array(
        array(
            'calendarid,=,AND',
            $calendarid
        ),
        array(
            'startdatetime,<=,AND',
            trim($enddatetime)
        ),
        array(
            'enddatetime,>=,AND',
            trim($startdatetime)
        )
    
    ), 'ORDER BY `startdatetime`');
    
    // Get list of future member blocks
    $memberblocks = WaseBlock::listOrderedBlocks(array(
        array(
            'calendarid,=,AND',
            $calendarid
        ),
        array(
            'startdatetime,<=,AND',
            trim($enddatetime)
        ),
        array(
            'enddatetime,>=,AND',
            trim($startdatetime)
        ),
        array(
            'userid,!=,AND',
            $calendar->userid
        )
    ), 'ORDER BY `startdatetime`');
    
    /* Return the xml */
    return '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime><numblocks>' . $foundblocks->entries() . '</numblocks><nummemberblocks>' . $memberblocks->entries() . '</nummemberblocks>';
}

/**
 * Return block header information for a given calendar and date range and time range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockswithallslots($child)
{
    
    // Save database name
    $database = WaseUtil::getParm('DATABASE');
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockswithallslots')) {
        return $err;
    }
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((string) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    $blockid = trim((int) $child->blockid);
    
    // Save userid
    $userid = $_SESSION['authid'];
    
    if (! $enddatetime)
        $enddatetime = '2999-12-31 23:59:59';
    if (! $startdatetime)
        $startdatetime = '2000-01-01 00:00:00';
    
    /* Get list of matching blocks */
    if ($blockid)
        $foundblocks = WaseBlock::listMatchingBlocks(array(
            'blockid' => $blockid
        ));
    else
        $foundblocks = WaseBlock::listOrderedBlocks(array(
            array(
                'calendarid,IN,AND',
                '(' . $calendarid . ')'
            ),
            array(
                'startdatetime,<=,AND',
                trim($enddatetime)
            ),
            array(
                'enddatetime,>=,AND',
                trim($startdatetime)
            )
        ), 'ORDER BY `startdatetime`');
    
    /* Build output header */
    $ret .= '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime><blockid>' . $blockid . '</blockid>';
    
    /* Go through all of the blocks */
    if ($foundblocks)
        foreach ($foundblocks as $fblock) {
            
            /*
             * Get list of all apps for this block owned either by the logged in user or with the logged in user,
             * unless showappinfo is on, in which case all apps should be returned
             */
            if ($fblock->showappinfo) {
                $blockapps = allappsforblock($fblock->blockid);
            } else {
                $blockapps = alluserappsforblock($userid, $fblock->blockid);
            }
            $slot_size=$fblock->slotsize;
            if ($blockapps->entries() || $fblock->isViewable($_SESSION['authtype'], $userid)) {
                
                /* Generate the XML for the block data */
                // $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo() . '</block><freeslots>' . ($fblock->slots() - $fblock->blockAppsMade('', '')) . '</freeslots>';
                // 11/29/18 - $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo() . '</block><freeslots>' . ($fblock->slots() - $fblock->blockAppsMade_slots('', '',$slot_size)) . '</freeslots>';
                // $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo() . '</block><freeslots>0</freeslots>';
                // 11/29/18 - check on maxapps = 0 and unslotted blocks ( blocks with 1 slot) and non-zero max appts
                $numslots = $fblock->slots();
                $maxapps =$fblock->maxapps;
                if($maxapps == 0) {
                  $freeslots = 1;
                } else if(($numslots == 1) && ($maxapps > 0) ) {
                    $freeslots = $maxapps - $fblock->blockAppsMade_slots('', '',$slot_size);
                } else {
                    $freeslots  = $numslots - $fblock->blockAppsMade_slots('', '',$slot_size);
                }
                //WaseMsg::dMsg('DBG', 'Info', 'numslots[' .$numslots . ']maxappts[' . $maxapps . ']freeslots[' . $freeslots . ']');
                if($freeslots < 0 ) $freeslots = 0;
                $ret .= '<blockswithslots><block>' . $fblock->xmlBlockHeaderInfo() . '</block><freeslots>' . $freeslots  . '</freeslots>';
                /* Get list of all slots */
                $available = $fblock->allSlots();
                //WaseMsg::dMsg('DBG','Info',"dogetblockswithallslots is available[$available]");
                /* Add slot data */
                if ($available)
                    $all_apps = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid.')', 'Appointment');
                    $all_apps_cnt=0;
                    foreach($all_apps as $app) {
                        $all_apps_cnt++;
                    }
                    $all_locked = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid .' AND available = 0 )', 'Appointment');
                    $all_locked_cnt=0;
                    foreach($all_locked as $app) {
                        $all_locked_cnt++;
                    }
                    foreach ($available as $slotstart) {
                        $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $fblock->slotsize);
                        $slotstart_minutes = WaseUtil::datetimeToMin($slotstart);
                        $slotend_minutes = $slotstart_minutes + $fblock->slotsize;
                        // $ret.='<slotstart_minutes>'.$slotstart_minutes.'</slotstart_minutes><slotend_minutes>'.$slotend_minutes.'</slotend_minutes>';
                        //WaseMsg::dMsg('DBG','Info',"dogetblockswithallslots is slotstart[$slotstart]slotend[$slotend]");
                        $whynot = $fblock->isMakeable($_SESSION['authtype'], $_SESSION['authid'], $slotstart, $slotend);
                        
                        if ($whynot)
                            $makeable = 0;
                        else
                            $makeable = 1;
                        $numappslots=$fblock->slotsMade($_SESSION['authtype'], $userid);
                        //$slotend_2 = $slotend + $fblock->blocksize;
                        // Get the appointments
                        // $apps = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid . ' AND ( (startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '") OR ( (startdatetime >="'.$slotstart.'" AND enddatetime<="'.$slotend_2.'"))) )', 'Appointment');
                        //$apps = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid . ' AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
                        $apps =array();
                        if($all_apps_cnt) {
                            foreach($all_apps as $app) {
                                $appt_start = WaseUtil::datetimeToMin($app->startdatetime);
                                $appt_end = WaseUtil::datetimeToMin($app->enddatetime);
                                // $ret.='<appt_start>'.$appt_start.'</appt_start><appt_end>'.$appt_end.'</appt_end>';
                                if(($appt_start <= $slotstart_minutes) && ($slotstart_minutes < $appt_end)) {
                                    $apps[] = $app;
                                } elseif(($appt_start == $slotstart_minutes) && ($appt_end == $slotend_minutes)) {
                                    $apps[] = $app;
                                } elseif(($appt_start < $slotend_minutes) && ($slotend_minutes <= $appt_end)) {
                                    $apps[]=$app;
                                }
                            }
                        }
                        // Get count of locked apps
                        // $locked = new WaseList('SELECT * FROM ' . $database . '.WaseAppointment WHERE (blockid=' . $fblock->blockid . ' AND available = 0 AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
                        $locked=array();
                        if($all_locked_cnt) {
                            foreach($all_locked as $app) {
                                $appt_start = WaseUtil::datetimeToMin($app->starttimedate);
                                $appt_end = WaseUtil::datetimeToMin($app->endtimedate);
                                if(($appt_start <= $slotstart_minutes) && ($slotstart_minutes < $appt_end)) {
                                    $locked[] = $app;
                                } elseif(($appt_start == $slotstart_minutes) && ($appt_end == $slotend_minutes)) {
                                    $locked[] = $app;
                                } elseif(($appt_start < $slotend_minutes) && ($slotend_minutes <= $appt_end)) {
                                    $locked[]=$app;
                                }
                            }
                        }
                        $my_apps_cnt=count($apps);
                        $my_locked_cnt=count($locked);
                        // Compute count of unlocked apps
                        //$unlockedapps = $apps->entries() - $locked->entries();
                        $unlockedapps = $my_apps_cnt - $my_locked_cnt;
                        // Add in the block and slot data
                        $ret .= '<slot>' . '<blockid>' . $fblock->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime><numappts>' . $unlockedapps . '</numappts><numapptslots>'.$numappslots.'</numapptslots><makeable>' . $makeable . '</makeable>';
                        
                        if ($my_apps_cnt)
                            foreach ($apps as $app) {
                                if ($app->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                                    
                                    $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                                } else
                                    $ret .= '<appt>' . $app->xmlMaskedApptHeaderInfo() . '</appt>';
                            }
                        $ret .= '</slot>';
                    }
                $ret .= '</blockswithslots>';
            }
        }
    
    /* Return the response */
    return $ret;
}

/**
 * Return block information for a given blockid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblock'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Read in the block */
    $blockid = $child->blockid;
    /* Load up the block */
    try {
        $block = new WaseBlock('load', array(
            'blockid' => $blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    /* Make sure it is viewable */
    if ($block->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
        /* Get count of appointments */
        $apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $block->blockid . ' AND available = 1)', 'Appointment');
        $hasappointments = ($apps->entries()) ? 1 : 0;
        $ret .= '<block>' . $block->xmlBlockInfo() . '</block><hasappointments>' . $hasappointments . '</hasappointments>';
    }
    
    return $ret;
}

/**
 * Return availability information for a given calendar and date range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockdates($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockdates'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((int) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    $blockid=trim((string) $child->blockid);
    //WaseMsg::dMsg('DBG','Info',"dogetblockdates-blockid[$blockid]");
    
    /* Set defaults for missing data */
    if (! $startdatetime)
        $startdatetime = '1990-01-01';
    if (! $enddatetime)
        $enddatetime = '2999-12-31';
    
    // Extract just the dates
    list ($startdate, $junk) = explode(' ', $startdatetime);
    list ($enddate, $junk) = explode(' ', $enddatetime);
    
    $foundblocks = WaseBlock::listOrderedBlocks(array(
        array(
            'calendarid,=,AND',
            trim($calendarid)
        ),
        array(
            'date(startdatetime),<=,AND',
            trim($enddate)
        ),
        array(
            'date(enddatetime),>=,AND',
            trim($startdate)
        )
    ), 'ORDER BY `startdatetime`');
    
    /**
     * Build output header
     */
    $ret .= '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime>';
    
    // Start with no date.
    $olddate = '';
    $makeable = 0;
    $showapps = 0;
    $hasappts = 0;
    /* Go through all of the blocks */
    if ($foundblocks)
        foreach ($foundblocks as $fblock) {
            $cur_blockid=$fblock->blockid;
            if(($blockid > 0) && ($cur_blockid != $blockid)) {
                //WaseMsg::dMsg('DBG','Info',"dogetblockdates-skip cur_blockid[$cur_blockid]");
                continue;
            }
            //WaseMsg::dMsg('DBG','Info',"dogetblockdates-proceed cur_blockid[$cur_blockid]");
            // Get block date
            list ($newdate, $junk) = explode(' ', $fblock->startdatetime);
            // If a new date
            if ($newdate != $olddate) {
                // If we already have a date, set it's makeable and showappinfo and hasappts status and close the blockdate.
                if ($olddate)
                    $ret .= "<makeable>$makeable</makeable><showappinfo>$showapps</showappinfo><hasappts>$hasappts</hasappts></blockdate>";
                // Save new date and init the blockdate field
                $olddate = $newdate;
                $myblockid=$fblock->blockid;
                $ret .= "<blockdate><date>$newdate</date><myblockid>$myblockid</myblockid>";
                // Init the makeable and showapps flags.
                $makeable = 0;
                $showapps = 0;
                $hasappts = 0;
            }
            // Determine if user can make appointments for this block.
            if (! $makeable) {
                $whynot = $fblock->isMakeable($_SESSION['authtype'], $_SESSION['authid'], "", "");
                if ($whynot == '')
                    $makeable = 1;
            }
            
            // Determine if any appts and if appts for this user
            // $allapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $fblock->blockid . ' AND available = 1)', 'Appointment');
            $allapps = allappsforblock($fblock->blockid);
            // $userapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $fblock->blockid . ' and (userid = ' . WaseSQL::sqlSafe($_SESSION['authid']) . ' OR (blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($_SESSION['authid']) . '))))', 'Appointment');
            $userapps = userappsforblock($_SESSION['authid'], $fblock->blockid);
            
            // Flag if there are apps to show
            if ($fblock->showappinfo && $allapps->entries())
                $showapps = 1;
            // Flag if user has apps
            if ($userapps->entries())
                $hasappts = 1;
        }
    // If we are processing a date, set it's makeable and showappinfo status and close the blockdate.
    if ($olddate)
        $ret .= "<makeable>$makeable</makeable><showappinfo>$showapps</showappinfo><hasappts>$hasappts</hasappts></blockdate>";
    //WaseMsg::dMsg('DBG','Info',"dogetblockdates-ret[$ret]");
    /* Return the response */
    
    return $ret;
}

/**
 * Return block header and availability information for a given calendar and date range and time range.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockheaders($child)
{
    
    // NOT CURRENTLY USED
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockheaders'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract arguments */
    $calendarid = trim((int) $child->calendarid);
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    
    $userid = trim($_SESSION['authid']);
    
    /* Set defaults for missing data */
    if (! $startdatetime)
        $startdatetime = '1990-01-01';
    if (! $enddatetime)
        $enddatetime = '2999-12-31';
    
    $foundblocks = WaseBlock::listOrderedBlocks(array(
        array(
            'calendarid,=,AND',
            trim($calendarid)
        ),
        array(
            'startdatetime,<=,AND',
            trim($enddatetime)
        ),
        array(
            'enddatetime,>=,AND',
            trim($startdatetime)
        )
    ), 'ORDER BY `startdatetime`');
    
    /**
     * Build output header
     */
    $ret .= '<calendarid>' . $calendarid . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime>';
    
    /* Go through all of the blocks */
    if ($foundblocks)
        foreach ($foundblocks as $fblock) {
            
            /* Get list of all apps for this block owned either by the logged in user or with the logged in user */
            $blockapps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $fblock->blockid . ' and (userid = ' . WaseSQL::sqlSafe($userid) . ' OR (blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))))', 'Appointment');
            /* Make sure block is viewable by this user, or user has an appointment in this block (either for or with) */
            if ($blockapps->entries() || $fblock->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                // Determine if user can make appointments in this block, or has appointments in this block

                if ($blockapps->entries() || $fblock->isMakeable($_SESSION['authtype'], $_SESSION['authid'], "", ""))
                    $makeable = 1;
                else
                    $makeable = 0;
                
                /* Generate the XML for the block data */
                $ret .= '<block>' . $fblock->xmlBlockHeaderInfo() . '<makeable>' . $makeable . '</makeable><showappinfo>' . $fblock->showappinfo . '</showappinfo></block>';
            }
        }
    
    /* Return the response */
    return $ret;
}

/**
 * Return block available slots for a given block or series, ordered by calendar date.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockheaderinfo($child)
{
    
    // NOT CURRENTLY USED
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockheaderinfo'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract blockid */
    $blockid = trim((int) $child->blockid);
    
    /* Get the block */
    $ret .= '<blockid>' . $blockid . '</blockid>';
    $block = new WaseBlock('load', (array(
        'blockid' => $blockid
    )));
    
    /* Make sure block is viewable by this user, or user has an appointment in this block */
    // if ($block->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
    $ret .= '<block>' . $block->xmlBlockHeaderInfo() . '</block>';
    
    /* Get list of available start times */
    if ($whynot = $block->isMakeable($_SESSION['authtype'], $_SESSION['authid']))
        $available = array();
    else
        $available = $block->availableSlots($_SESSION['authtype'], $_SESSION['authid']);
    
    /* Add slot data */
    if ($available)
        foreach ($available as $slotstart) {
            $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $block->slotsize);
            $ret .= '<slot>' . '<blockid>' . $block->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime>';
            /* Now get the appointment data */
            
            $apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $block->blockid . ' AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
            if ($apps)
                foreach ($apps as $app) {
                    $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                }
            $ret .= '</slot>';
        }
    // }
    
    /* Return the response */
    return $ret;
}

/**
 * Return block available slots for a given block or series, ordered by calendar date.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetblockinfo($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockinfo'))
        return $err;
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Extract blockid */
    $blockid = trim((int) $child->blockid);
    $ret .= '<blockid>' . $blockid . '</blockid>';
    
    // See if this is an "edit" call
    $editapp = trim((int) $child->editapp);
    if ($editapp) {
        $ret .= '<editapp>' . $editapp . '</editapp>';
        try {
            $curapp = new WaseAppointment('load', array(
                'appointmentid' => $editapp
            ));
        } catch (Exception $exception) {
            $editapp = false;
        }
    }
    
    /* Load the block */
    $block = new WaseBlock('load', (array(
        'blockid' => $blockid
    )));
    
    /* Make sure block is viewable by this user, or user has an appointment in this block */
    // if ($block->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
    $ret .= '<block>' . $block->xmlBlockInfo() . '</block>';
    
    /* Get list of available start times */
    if ($editapp)
        $available = $block->editableSlots($_SESSION['authtype'], $_SESSION['authid'], $curapp);
    else {
        if ($whynot = $block->isMakeable($_SESSION['authtype'], $_SESSION['authid']))
            $available = array();
        else
            $available = $block->availableSlots($_SESSION['authtype'], $_SESSION['authid']);
    }
    
    // If editing an app, the app start time should always be returned.
    if ($editapp) {
        if (count($available == 0)) {
            if (! in_array($curapp->startdatetime, $available))
                $available[] = $curapp->startdatetime;
        }
    }
    
    /* Add slot data */
    if ($available)
        foreach ($available as $slotstart) {
            $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $block->slotsize);
            $ret .= '<slot>' . '<blockid>' . $block->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime>';
            
            /* Now get the appointment data */
            
            $apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $block->blockid . ' AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
            if ($apps)
                foreach ($apps as $app) {
                    $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                }
            $ret .= '</slot>';
        }
    
    /* Return the response */
    return $ret;
}

/**
 * Return block slots for a given block or series, ordered by calendar date.
 *( clone of dogetblockinfo but calls nocheck versions of editableSlots and avaialable_Slots )
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output XML to be returned to the caller.
 */
function dogetblockinfo_nocheck($child)
{

    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);

    /* Test authentication */
    if ($err = testauth($sessionid, 'getblockinfo'))
        return $err;

    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';

    /* Extract blockid */
    $blockid = trim((int) $child->blockid);
    $ret .= '<blockid>' . $blockid . '</blockid>';

    // See if this is an "edit" call
    $editapp = trim((int) $child->editapp);
    if ($editapp) {
        $ret .= '<editapp>' . $editapp . '</editapp>';
        try {
            $curapp = new WaseAppointment('load', array(
                'appointmentid' => $editapp
            ));
        } catch (Exception $exception) {
            $editapp = false;
        }
    }

    /* Load the block */
    $block = new WaseBlock('load', (array(
        'blockid' => $blockid
    )));

    /* Make sure block is viewable by this user, or user has an appointment in this block */
    // if ($block->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
    $ret .= '<block>' . $block->xmlBlockInfo() . '</block>';

    // slot_available array
    $slots_available = array();
    /* Get list of available start times */
    if ($editapp)
        // $available = $block->editableSlots($_SESSION['authtype'], $_SESSION['authid'], $curapp);
        $available = $block->editableSlots_nocheck($_SESSION['authtype'], $_SESSION['authid'], $curapp, $slots_available);
    else {
        if ($whynot = $block->isMakeable($_SESSION['authtype'], $_SESSION['authid']))
            $available = array();
        else
            // $available = $block->availableSlots($_SESSION['authtype'], $_SESSION['authid']);
            $available = $block->availableSlots_nocheck($_SESSION['authtype'], $_SESSION['authid'],$slots_available);
    }

    // If editing an app, the app start time should always be returned.
    if ($editapp) {
        if (count($available == 0)) {
            if (! in_array($curapp->startdatetime, $available))
                $available[] = $curapp->startdatetime;
                $slots_available [] =1;
        }
    }

    /* Add slot data */
    if ($available)
        $i=0;
    foreach ($available as $slotstart) {
        $available_flag = $slots_available[$i];
        $i++;
        $slotend = WaseUtil::minToDateTime(WaseUtil::datetimeToMin($slotstart) + $block->slotsize);
        $ret .= '<slot>' . '<blockid>' . $block->blockid . '</blockid>' . '<startdatetime>' . $slotstart . '</startdatetime>' . '<enddatetime>' . $slotend . '</enddatetime>';

        $tmp_apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $block->blockid. ')', 'Appointment');
        // $tmp_cnt=$tmp_apps->entries();
        // WaseMsg::dMsg('DBG','Info','tmp_cnt '.$tmp_cnt);
        //if($tmp_apps)
            //foreach($tmp_apps as $one_app) {
                // $one_id=$one_app->appointmentid;
                // WaseMsg::dMsg('DBG','Info','tmp_apps-apptid is '.$one_id);
            //}
            $m_slotstart=WaseUtil::datetimeToMin($slotstart);
            $m_slotend=$m_slotstart+$block->slotsize;
            // WaseMsg::dMsg('DBG','Info','m_slotstart'.$m_slotstart." m_slotend".$m_slotend);
        /* Now get the appointment data */

        // $apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND blockid=' . $block->blockid . ' AND startdatetime >="' . $slotstart . '"  AND enddatetime<="' . $slotend . '")', 'Appointment');
        if ($tmp_apps)
            foreach ($tmp_apps as $app) {
                $one_id=$app->appointmentid;
                $app_min_startdatetime=WaseUtil::datetimeToMin($app->startdatetime);
                $app_min_enddatetime=WaseUtil::datetimeToMin($app->enddatetime);
                $match=0;
                if(($app_min_startdatetime == $m_slotstart) && ($app_min_enddatetime == $m_slotend)) {
                    $match = 1;
                } elseif(($app_min_startdatetime <= $m_slotstart) && ($m_slotstart <$app_min_enddatetime)) {
                    $match=1;
                } elseif(($app_min_startdatetime < $m_slotend) && ($m_slotend <= $app_min_enddatetime)) {
                    $match=1;
                }
                if($match) {
                    $ret .= '<appt>' . $app->xmlApptHeaderInfo() . '</appt>';
                }
                // WaseMsg::dMsg('DBG','Info','apps-apptid is '.$one_id." start_minutes".$min_startdatetime." end_minutes".$min_enddatetime." match".$match);
            }
        $ret .= '<available_flag>'.$available_flag.'</available_flag></slot>';
    }

    /* Return the response */
    return $ret;
}

/**
 * Add a block to a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addblock'))
        return $err;
    
    /* Make sure we got the block data */
    $block = $child->block;
    // Save for later return in Ajax response.
    $oriblock = $block;
    
    if (! is_object($block))
        return WaseUtil::Error(10, array(
            'block',
            'addblock',
            $child
        ), 'AJAXERR');
    
    /* Read in the calendar */
    try {
        if (! $calendar = new WaseCalendar('load', array(
            'calendarid' => $block->calendarid
        )))
            return WaseUtil::Error(10, array(
                'calendarid',
                'addblock'
            ), 'AJAXERR', false);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Set aside the calendarid */
    $calendarid = $calendar->calendarid;
    
    /* Make sure the authenticated user owns, manages or is a member of the calendar. */
    if (! $calendar->isOwnerOrManagerOrMember($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'block  calendar'
        ), 'AJAXERR');
    
    // Init id pointers
    $blockid = '';
    $seriesid = '';
    $periodid = '';
    
    /* If we were passed some series data, then this is a recurring block, and we need to build the series, periods and blocks */
    if (isset($block->series)) {
        
        /* Create and save the series */
        try {
            /* Build the series/block data */
            $seriesarray = arrayiffy_blockinfo($block);
            $seriesarray['calendarid'] = $calendarid;
            $series = new WaseSeries('create', $seriesarray);
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        /* Now save the series (and capture the series id) */
        try {
            $seriesid = $series->save('create');
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        $series->seriesid = $seriesid;
        
        /* Now iteratively create and save the periods and blocks */
        $totalblocks = 0;
        if ($block->period)
            foreach ($block->period as $perioddata) {
                /* Create and save the period */
                
                try {
                    /* Build the period */
                    $periodarray = arrayiffy_periodinfo($perioddata);
                    $periodarray['seriesid'] = $seriesid;
                    // $periodarray['calendarid'] = $calendarid;
                    if (! (int) $periodarray['duration'])
                        $periodarray['duration'] = WaseUtil::datetimeToMin((string) $block->enddatetime) - WaseUtil::datetimeToMin((string) $block->startdatetime);
                    $period = new WasePeriod('create', $periodarray);
                } catch (Exception $error) {
                    /* If an error, return error code and message */
                    return WaseUtil::Error($error->getCode(), array(
                        $error->getMessage()
                    ), 'AJAXERR', false);
                }
                /* Now save the period (and capture the period id) */
                try {
                    $periodid = $period->save('create');
                } catch (Exception $error) {
                    /* If an error, return error code and message */
                    return WaseUtil::Error($error->getCode(), array(
                        $error->getMessage()
                    ), 'AJAXERR', false);
                }
                $period->periodid = $periodid;
                
                /* And now add the blocks */
                $blocks_created = $series->buildblocks($period);
                
                /* Set up error/response msg */
                if (! is_int($blocks_created))
                    return WaseUtil::Error(14, array(
                        $blocks_created
                    ), 'AJAXERR');
                else
                    $totalblocks += $blocks_created;
            }
        
        if ($totalblocks == 1)
            $infmsg = '1 block added.  You may add additional blocks, or click on Done if done.';
        elseif ($totalblocks == 0)
            $infmsg = '0 blocks added;  please check your block specifications.';
        else
            $infmsg = $totalblocks . ' blocks added. You may add additional blocks, or click on Done if done.';
    } else {
        /* Load up the block. */
        try {
            /* Build the block */
            $blockobject = new WaseBlock('create', arrayiffy_shortblockinfo($block));
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        
        /* Now save the block (and capture the block id) */
        try {
            $blockid = $blockobject->save('create', true);
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        $blockobject->blockid = $blockid;
        $infmsg = '1 block added. You may add additional blocks, or click on Done if done.';
    }
    
    // We want to return the original block xml request, but we need to fill in the blockid, seriesid and periodid.
    $bstring = $oriblock->asXML();
    
    if (strpos($bstring, '<blockid></blockid>') !== false)
        $bstring = str_replace('<blockid></blockid>', '<blockid>' . $blockid . '</blockid>', $bstring);
    elseif (strpos($bstring, '</blockid>') !== false)
        $bstring = str_replace('</blockid>', '<blockid>' . $blockid . '</blockid>', $bstring);
    
    if (strpos($bstring, '<seriesid></seriesid>') !== false)
        $bstring = str_replace('<seriesid></seriesid>', '<seriesid>' . $seriesid . '</seriesid>', $bstring);
    elseif (strpos($bstring, '</seriesid>') !== false)
        $bstring = str_replace('</seriesid>', '<seriesid>' . $seriesid . '</seriesid>', $bstring);
    
    if (strpos($bstring, '<periodid></periodid>') !== false)
        $bstring = str_replace('<periodid></periodid>', '<periodid>' . $periodid . '</periodid>', $bstring);
    elseif (strpos($bstring, '</periodid>') !== false)
        $bstring = str_replace('</periodid>', '<periodid>' . $periodid . '</periodid>', $bstring);
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg>>' . $bstring;
}

/**
 * Change the data for a block.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editblock'))
        return $err;
    
    /* Make sure we got the block data */
    $block = $child->block;
    // Save for later return in Ajax response.
    $oriblock = $block;
    if (! is_object($block))
        return WaseUtil::Error(10, array(
            'block',
            'editblock'
        ), 'AJAXERR');
    
    /* Read in the calendar */
    try {
        if (! $calendar = new WaseCalendar('load', array(
            'calendarid' => (int) $block->calendarid
        )))
            return WaseUtil::Error(10, array(
                'calendarid',
                'editblock'
            ), 'AJAXERR');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Build list of affected blocks */
    /* If request is for series, get list of blocks for the series */
    $occurence = (string) $child->whichblocks;
    if ($occurence == 'series')
        $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE seriesid=' . (int) $block->seriesid, 'Block');
    elseif ($occurence == 'seriesfromtoday')
        $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE seriesid=' . (int) $block->seriesid . ' AND date(`startdatetime`) >="' . date('Y-m-d') . '"', 'Block');
    elseif ($occurence == 'instance')
                            /* Else just get list of the one block */ {
        $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE blockid=' . (int) $block->blockid, 'Block');
    } else
        return WaseUtil::Error(10, array(
            'whichblocks',
            'editblock',
            $child->whichblocks
        ), 'AJAXERR');
    
    /* Build array of updated block data */
    if ($occurence == 'instance')
        $newdata = arrayiffy_editblockinfo($block);
    else
        $newdata = arrayiffy_editseriesinfo($block);
    
    /* Init block counter */
    $blockcount = 0;
    
    // First, make sure all blocks are editable
    if ($blocks)
        foreach ($blocks as $block) {
            if (! $block->canEditBlock($_SESSION['authtype'], $_SESSION['authid']))
                return WaseUtil::Error(10, array(
                    'block',
                    'editblock'
                ), 'AJAXERR');
        }
    
    /* Go through the blocks and update them. */
    $elements = count($blocks);
    $i = 0;
    
    if ($blocks)
        foreach ($blocks as $block) {
            $i ++;
            /* We need to add in the block header data */
            $newdata['blockid'] = $block->blockid;
            try {
                $blockobject = new WaseBlock('update', $newdata);
            } catch (Exception $error) {
                /* If an error, return error code and message */
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
            
            /* Write out the updated block */
            if ($i == 1)
                $first = true;
            else
                $first = false;
            try {
                $blockid = $blockobject->save('update', $first);
                $blockcount ++;
            } catch (Exception $error) {
                /* If an error, return error code and message */
                return WaseUtil::Error($error->getCode(), array(
                    $error->getMessage()
                ), 'AJAXERR', false);
            }
        }
    
    /* Now return the data to the caller */
    if (is_object($blockobject))
        return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . (int) $blockcount . ' block(s) updated</infmsg><whichblocks>' . $child->whichblocks . '</whichblocks>' . $oriblock->asXML();
    else
        return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . (int) $blockcount . ' block(s) updated</infmsg><whichblocks>' . (string) $child->whichblocks . '</whichblocks>' . $oriblock->asXML();
}

/**
 * Delete a block.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeleteblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deleteblock'))
        return $err;
    
    /* Get the blockid and cancel text */
    $blockid = (int) $child->blockid;
    if (! $blockid)
        return WaseUtil::Error(13, array(
            'blockid',
            'deleteblock'
        ), 'AJAXERR');
    
    $canceltext = (string) $child->canceltext;
    if (! $canceltext)
        $canceltext = 'Block of time has become unavailable.';
    
    /* Load up the block */
    try {
        $blockobject = new WaseBlock('load', array(
            'blockid' => $blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    /* Make sure the authenticated user owns the block. */
    if (! $blockobject->canDeleteBlock($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            ' block calendar'
        ), 'AJAXERR');
    
    /* If request is to delete series, do that */
    if ((string) $child->whichblocks == 'series') {
        /* Load up the series */
        try {
            $seriesobject = new WaseSeries('load', array(
                'seriesid' => $blockobject->seriesid
            ));
        } catch (Exception $error) {
            /* If an error, return error code and message */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        /* Now delete the series (this will delete the blocks and cancel the appointments). */
        $seriesobject->delete($canceltext);
    } /* If request is just for this block */
elseif ($child->whichblocks == 'instance')
                                /* Delete the block (this will cancel the appointments). */
                                $blockobject->delete($canceltext, true);
    /* If request is for blocks from today, build list and do it */
    elseif ($child->whichblocks == 'seriesfromtoday') {
        /* Build WaseList */
        $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE seriesid=' . $blockobject->seriesid . ' AND date(`startdatetime`) >="' . date('Y-m-d') . '"', 'Block');
        /* Now delete the blocks */
        $elements = count($blocks);
        $i = 0;
        if ($blocks)
            foreach ($blocks as $block) {
                $i ++;
                if ($i == 1)
                    $first = true;
                else
                    $first = false;
                $block->delete($canceltext, $first);
            }
    } else {
        return WaseUtil::Error(36, array(
            $child->whichblocks
        ), 'AJAXERR');
    }
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><whichblocks>' . $child->whichblocks . '</whichblocks><blockid>' . $blockid . '</blockid><canceltext>' . $canceltext . '</canceltext>';
}

/**
 * Sync a block.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosyncblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'syncblock'))
        return $err;
    
    /* Get userid */
    $userid = (string) $child->userid;
    
    /* Get the blockid and load the block */
    $blockid = (int) $child->blockid;
    try {
        $block = new WaseBlock('load', array(
            'blockid' => $blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $block->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            ' block calendar'
        ), 'AJAXERR');
    
    /* Get list of blocks as per the whichblocks argument */
    $whichblocks = (string) $child->whichblocks;
    switch ($whichblocks) {
        case 'series':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE seriesid=' . $block->seriesid . ' ORDER BY startdatetime ASC', 'Block');
            break;
        case 'seriesfromtoday':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE seriesid=' . $block->seriesid . ' AND date(`startdatetime`) >="' . date('Y-m-d') . '" ORDER BY startdatetime ASC', 'Block');
            break;
        case 'instance':
            $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE blockid=' . $block->blockid, 'Block');
            break;
        default:
            return WaseUtil::Error(36, array(
                $child->whichblocks
            ), 'AJAXERR');
            break;
    }
    
    /* Get the user's sync preference */
    $syncpref = WasePrefs::getPref($userid, 'localcal');
    
    $syncdone = false;
    $syncfail = '';
    
    $ical = '';
    $i = 0;
    
    /* Go through the blocks and synchronize as requested */
    if ($blocks)
        foreach ($blocks as $newblock) {
            // Get all of the appointments
            $allapps = allappsforblock($blockid);
            $i ++;
            /* If user wants Google synchronization, go do it */
            switch ($syncpref) {
                case 'google':
                    $syncdone = WaseGoogle::addBlock($newblock);
                    /* Save synchronization done status */
                    if (! $syncdone)
                        $syncfail = 'Google';
                    else
                        foreach ($allapps as $app) {
                            $appdone = WaseGoogle::addApp($app, $newblock, $newblock->userid);
                        }
                    
                    break;
                
                case 'exchange':
                    $syncdone = WaseExchange::addBlock($newblock);
                    /* Save synchronization done status */
                    if (! $syncdone)
                        $syncfail = 'Exchange';
                    else
                        foreach ($allapps as $app) {
                            WaseExchange::addApp($app, $newblock, $newblock->userid, $newblock->email);
                        }
                    
                    break;
                
                default:
                    if (! $ical) {
                        if ($whichblocks == 'series' || $whichblocks == 'seriesfromtoday')
                            $ical .= WaseIcal::addSeries($newblock);
                        else
                            $ical = WaseIcal::addBlock($newblock);
                        
                        $syncdone = true;
                    }
                    break;
            }
        }
    
    // If an iCal return, complete the stream.
    if ($ical) {
        $ical = WaseIcal::ICALHEADER . "METHOD:PUBLISH\r\n" . $ical . WaseIcal::ICALTRAILER;
        $syncdone = true;
    }
    
    if (! $syncdone)
        $ret = WaseUtil::Error(14, array(
            'Unable to synchronize with ' . $syncfail . ': ' . $syncdone
        ), 'AJAXERR');
    else
        $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Now return the status to the caller */
    return $ret . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><whichblocks>' . $whichblocks . '</whichblocks><blockid>' . $blockid . '</blockid><ical>' . $ical . '</ical>';
}

/**
 * Lock or unlock a block.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolockblock($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deleteblock'))
        return $err;
    
    /* Get the blockid and load the block */
    $blockid = $child->blockid;
    try {
        $block = new WaseBlock('load', array(
            'blockid' => $blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $block->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            ' block calendar'
        ), 'AJAXERR', false);
    
    /* Get the desired availability status */
    $avail = trim((string) $child->makeavailable);
    
    /* Set the availability status */
    $block->available = $avail;
    try {
        $block->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><blockid>' . $blockid . '</blockid><makeavailable>' . $avail . '</makeavailable>';
}

/**
 * Return appointment info.
 * as per the specified search criteria.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetappointments($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getappointments'))
        return $err;
    
    /* Init the output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Save logged-in userid in a convenient place */
    $userid = $_SESSION['authid'];
    
    /* Init the SQL query string that will return the appointments. */
    $query = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND (';
    
    // If guest, just use authid
    if ($_SESSION['authtype'] == 'guest')
        $query .= '(userid = ' . WaseSQL::sqlSafe($userid) . ')';
    else {
        // We start by restricting appointments to those owned by the user, or made with the user, or made with someone whose calendar the user manages.
        
        // 1) Appointments owned by user
        $query .= '(userid = ' . WaseSQL::sqlSafe($userid);
        // 2) Appointments made with the user
        $query .= ' OR (blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))';
        // Add in owned calendars
        if ($owned = WaseCalendar::arrayOwnedCalendarids($userid))
            $query .= ' OR calendarid IN (' . implode(',', $owned) . ') ';
        // 3) Appointments made on calendars managed by the user.
        if ($managed = WaseManager::arrayActiveManagers($userid))
            $query .= ' OR calendarid IN (' . implode(',', $managed) . ')) ';
        else
            $query .= ') ';
    }
    
    /* Add in additional selectors if specified */
    if (property_exists($child, 'startdate') && (string) $child->startdate) {
        $query .= ' AND date(startdatetime) >= ' . WaseSQL::sqlSafe($child->startdate);
        $ret .= tag('startdate', $child->startdate);
    }
    if (property_exists($child, 'enddate') && (string) $child->enddate) {
        $query .= ' AND date(enddatetime)<= ' . WaseSQL::sqlSafe($child->enddate);
        $ret .= tag('enddate', $child->enddate);
    }
    if (property_exists($child, 'starttime') && (string) $child->starttime) {
        $query .= ' AND time(startdatetime) >= ' . WaseSQL::sqlSafe($child->starttime);
        $ret .= tag('starttime', $child->starttime);
    }
    if (property_exists($child, 'endtime') && (string) $child->endtime) {
        $query .= ' AND time(enddatetime) <= ' . WaseSQL::sqlSafe($child->endtime);
        $ret .= tag('endtime', $child->endtime);
    }
    if (property_exists($child, 'calendarid') && (int) $child->calendarid) {
        $query .= ' AND calendarid = ' . WaseSQL::sqlSafe($child->calendarid);
        $ret .= tag('calendarid', $child->calendarid);
    }
    if (property_exists($child, 'apptby') && (string) $child->apptby) {
        $query .= ' AND madeby = ' . WaseSQL::sqlSafe($child->apptby);
        $ret .= tag('apptby', $child->apptby);
    }
    if (property_exists($child, 'apptwithorfor') && (string) $child->apptwithorfor) { /* Appointments made by apptwithorfor user with logged-in user or appointments made by logged-in user with apptwithorfor user */
        $madebyapptwithorfor = '(userid = ' . WaseSQL::sqlSafe($child->apptwithorfor) . ' OR name LIKE ' . WaseSQL::sqlSafe('%' . $child->apptwithorfor . '%') . ')';
        $madebyloggedin = '(userid = ' . WaseSQL::sqlSafe($userid) . ' OR name LIKE ' . WaseSQL::sqlSafe('%' . $userid . '%') . ')';
        $madewithapptwithorfor = '(blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($child->apptwithorfor) . '))';
        $madewithloggedin = '(blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))';
        
        $query .= ' AND (' . $madebyapptwithorfor . ' OR ' . $madewithapptwithorfor . ')';
        $ret .= tag('apptwithorfor', $child->apptwithorfor);
    }
    
    /* Add in the ordering */
    $query .= ')) ORDER BY startdatetime';
    WaseMsg::log($query);
    /* Get the appointments */
    $appts = new WaseList($query, 'Appointment');
    
    /* Go through and format the appts */
    if ($appts)
        foreach ($appts as $appt) {
            if ($appt->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                $ret .= '<apptwithblock>';
                $ret .= '<appt>' . $appt->xmlApptInfo() . '</appt>';
                
                /* Get the block for the appointment */
                $block = new WaseBlock('load', (array(
                    'blockid' => $appt->blockid
                )));
                $ret .= '<block>' . $block->xmlBlockHeaderInfo() . '</block>';
                unset($block);
                $ret .= '</apptwithblock>';
            }
        }
    
    /* Return the result */
    return $ret;
}

/**
 * Return all appointments within a start/end datetime window.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolistappointments($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'listappointments'))
        return $err;
    
    /* Init the output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>';
    
    /* Grab the parameters */
    if ($userid = trim((string) $child->userid))
        $ret .= '<userid>' . $userid . '</userid>';
    if ($startdatetime = trim((string) $child->startdatetime))
        $ret .= '<startdatetime>' . $startdatetime . '</startdatetime>';
    if ($enddatetime = trim((string) $child->enddatetime))
        $ret .= '<enddatetime>' . $enddatetime . '</enddatetime>';
    
    /* Bulld the select criteria */
    $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE 1 ';
    
    if ($userid)
        $select .= ' AND (userid=' . WaseSQL::sqlSafe($userid) . ' OR blockid in (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid=' . WaseSQL::sqlSafe($userid) . '))';
    
    if ($startdatetime)
        $select .= ' AND startdatetime >=' . WaseSQL::sqlSafe($startdatetime);
    
    if ($enddatetime)
        $select .= ' AND enddatetime <=' . WaseSQL::sqlSafe($enddatetime);
    
    $select .= 'AND available = 1 ORDER by startdatetime';
    
    /* Now get the waselist of appointments */
    $apps = new WaseList($select, 'Appointment');
    
    /* Save viewable matching appointments in the results. */
    if ($apps)
        foreach ($apps as $app) {
            if ($app->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                /* Read in the block */
                $block = new WaseBlock('load', array(
                    'blockid' => $app->blockid
                ));
                /* Now build the output string */
                $ret .= '<appointment>' . '<appointmentid>' . $app->appointmentid . '</appointmentid>' . '<blockid>' . $app->blockid . '</blockid>' . '<calendarid>' . $app->calendarid . '</calendarid>' .
                    '<for_name>' . $app->name . '</for_name>' . '<for_email>' . $app->email . '</for_email>' . '<for_phone>' . $app->phone . '</for_phone>' . '<for_userid>' . $app->userid . '</for_userid>' .
                    '<with_name>' . $block->name . '</with_name>' . '<with_email>' . $block->email . '</with_email>' . '<with_phone>' . $block->phone . '</with_phone>' . '<with_userid>' . $block->userid .
                    '</with_userid>' . '<location>' . $block->location . '</location>' . '<startdatetime>' . $app->startdatetime . '</startime>' . '<enddatetime>' . $app->enddatetime . '</enddatetime>' .
                    '<purpose>' . $app->purpose . '</purpose>' . '<venue>' . $app->venue . '</venue>' . '<whenmade>' . $app->whenmade . '</whenmade>' . '</appointment>';
            }
        }
    
    /* Return the result */
    return $ret;
}

/**
 * Return appointment data.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetappointmentinfo($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getappointmentinfo'))
        return $err;
    
    /* Grab the parameter (appointmentid) */
    $apptid = trim((string) $child->apptid);
    
    /* Build output header */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<apptid>' . $apptid . '</apptid>';
    
    /* Get the appointment */
    $appt = new WaseAppointment('load', array(
        'appointmentid' => $apptid
    ));
    
    /* Generate the XML for the appointment data */
    if (is_object($appt)) {
        if ($appt->isViewable($_SESSION['authtype'], $_SESSION['authid']))
            $ret .= '<appt>' . $appt->xmlApptInfo() . '</appt>';
    }
    
    /* Return the result */
    return $ret;
}

/**
 * Add an appointment, new format.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddappointment($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addappointment'))
        return $err;
    
    /* Make sure we got the appointment data */
    $appt = $child->appt;
    if (! is_object($appt))
        return WaseUtil::Error(13, array(
            'appointment',
            'addappointment',
            $child
        ), 'AJAXERR');
    $apptmaker = $appt->apptmaker;
    
    // Get the force flag, if any (allow overlaps)
    //$force = (int) $child->force;
    $force = (int) $appt->force;
    
    /* Extract the blockid from the appointment data */
    $blockid = (int) $appt->blockid;
    if (! $blockid)
        return WaseUtil::Error(13, array(
            'blockid',
            'addappointment'
        ), 'AJAXERR');
    
    /* Load up the block. */
    try {
        $block = new WaseBlock('load', array(
            'blockid' => $blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Get the requested start and end datetimes. */
    $startdatetime = $appt->startdatetime;
    $enddatetime = $appt->enddatetime;
    
    /* Make sure the authenticated user can schedule appointments. */
    $whynot = $block->isMakeable($_SESSION['authtype'], $_SESSION['authid'], $startdatetime, $enddatetime);
    if ($whynot)
        return WaseUtil::Error(14, array(
            $whynot
        ), 'AJAXERR');
    
    // If the authenticated user is the block owner or manager, see if a non-owner manager would have been bounced.
    if ($block->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        $notifymsg = $block->isMakeable($_SESSION['authtype'], '_________', $startdatetime, $enddatetime);
    else
        $notifymsg = '';
    
    /*
     * Check the userid/email, as follows:
     * if the authenticated user owns/manages the block, then accept any userid/email.
     * otherwise, the specified userid or email must match the authenticated user/email.
     */
    
    if (! $block->isOwner($_SESSION['authtype'], $_SESSION['authid'])) {
        /* Authenticated user does not own/manage the calendar */
        /* If userid specified, must match authenticated userid */
        if ($_SESSION['authtype'] == 'guest') {
            if (! $apptmaker->email)
                $apptmaker->email = $_SESSION['authid'];
            elseif ($apptmaker->email != $_SESSION['authid'])
                return WaseUtil::Error(28, array(
                    $apptmaker->email,
                    $_SESSION['authid']
                ), 'AJAXERR');
        } elseif (strtoupper($apptmaker->userid) != strtoupper($_SESSION['authid']))
            return WaseUtil::Error(27, array(
                $_SESSION['authid'],
                'appointment',
                $apptmaker->userid
            ), 'AJAXERR');
        
        /* Fail if required purpose was not specified */
        if ($block->purreq && ! $appt->purpose)
            return WaseUtil::Error(34, '', 'AJAXERR');
    }
    
    // Get our labels
    $labels = WaseUtil::getLabels($block);
    
    /* Default the return message */
    $infmsg = $labels['APPTHING'] . ' not created.';
    
    /* Create the appointment. */
    try {
        /* Get the appointment properties into an associative array */
        $appvals = arrayiffy_apptinfo($appt);
        
        // If remind not set, set from the user's preferences.
        if (! key_exists('remind', $appvals)) {
            if ($_SESSION['authtype'] == 'guest')
                $appvals['remind'] = 1;
            elseif ($remind = WasePrefs::getPref($_SESSION['authid'], 'remind'))
                $appvals['remind'] = WaseUtil::makeBool($remind);
        }
        
        /* Remove the superfluous 'office' entry' */
        unset($appvals['office']);
        if (! $appvals['calendarid'])
            $appvals['calendarid'] = $block->calendarid;
        if (! $appvals['userid']) {
            /* Use the authenticated user identifier if not specified */
            if ($_SESSION['authtype'] != 'guest')
                $appvals['userid'] = $_SESSION['authid'];
            else {
                if (! $appvals['email'])
                    $appvals['email'] = $_SESSION['authid'];
                $appvals['userid'] = $_SESSION['authid'];
            }
        }
        /*
         * Add in a UID for Ical purposes -- not needed, th appointment creation code generates this value.
         * $appvals['uid'] = date('Ymd\TGis') . "-" . rand(1000,1000000) . '@' . $_SERVER['REQUEST_URI'];
         */
        
        /* Now build the appointment. */
        if ($force)
            $appointment = new WaseAppointment('force', $appvals);
        else
            $appointment = new WaseAppointment('create', $appvals);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        WaseMsg::dMsg('overlap','error','msg = ' . $error->getMessage());
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', true);
    }
    
    /* Now save the appointment (and capture the appointment id). */
    /* Write out the updated appointment. */
    
    try {
        $appointmentid = $appointment->save('create');
        
        $infmsg = $labels['APPTHING'] . ' created ';
        if ($notifymsg)
            $infmsg .= ' because you are the block owner/manager.  Non-owners/managers would have gotten the following error msg: ' . $notifymsg;
        else
            $infmsg .= '.';
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    $appointment->appointmentid = $appointmentid;
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg><appt>' . $appointment->xmlApptInfo() . '</appt>';
}

/**
 * Edit an appointment.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditappointment($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editappointment'))
        return $err;
    
    /* Make sure we got the appointment data */
    $appt = $child->appt;
    if (! is_object($appt))
        return WaseUtil::Error(13, array(
            'appointment',
            'editappointment',
            $child),
            'AJAXERR'
        );
    $apptmaker = $appt->apptmaker;
    
    // Get the force flag, if any (allow overlaps)
    $force = (int) $child->force;
    
    /* Load up the appointment. */
    try {
        $appobject = new WaseAppointment('load', array(
            'appointmentid' => $appt->appointmentid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    // Load up the owning block
    try {
        $block = new WaseBlock('load', array(
            'blockid' => $appobject->blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user can schedule/edit this appointment. */
    if (! $appobject->canEditAppointment($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'appointment'
        ), 'AJAXERR');
    
    // Get our labels
    $labels = WaseUtil::getLabels(array(
        'blockid' => $appobject->blockid
    ));
    /* Default return message */
    $infmsg = $labels['APPTHING'] . ' not changed.';
    
    /* Update fields that can be updated */
    
    // If owner, can change userid
    if (property_exists($apptmaker, 'userid') && $block->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        $appobject->userid = (string) $apptmaker->userid;
    if (property_exists($apptmaker, 'name'))
        $appobject->name = (string) $apptmaker->name;
    if (property_exists($apptmaker, 'phone'))
        $appobject->phone = (string) $apptmaker->phone;
    if (property_exists($apptmaker, 'email'))
        $appobject->email = (string) $apptmaker->email;
    /*
     * if ($apptmaker->office)
     * $appobject->office = $apptmaker->office;
     */
    if (property_exists($appt, 'purpose'))
        $appobject->purpose = (string) $appt->purpose;
    if (property_exists($appt, 'venue'))
        $appobject->venue = (string)$appt->venue;
    if (property_exists($appt, 'remind'))
        $appobject->remind = (int) $appt->remind;
    if (property_exists($appt, 'textemail'))
        $appobject->textemail = (string) $appt->textemail;
    if (property_exists($appt, 'available'))
        $appobject->available = (int) $appt->available;
    
    /* If changing appointment start/end times, we have to do some extra work */
    if ((property_exists($appt, 'startdatetime') && (string) $appt->startdatetime != $appobject->startdatetime) || (property_exists($appt, 'enddatetime') && (string) $appt->enddatetime != $appobject->enddatetime)) {
        /* See if requested slot is available */
        if ($block->isMakeable($_SESSION['authtype'], $_SESSION['authid'], (string) $appt->startdatetime, (string) $appt->enddatetime, $appobject))
            return WaseUtil::Error(14, array(
                $labels['APPTHING'] . ' slot is unavailable.'
            ), 'AJAXERR');
        
        /* Reset start and end times */
        $appobject->startdatetime = (string) $appt->startdatetime;
        $appobject->enddatetime = (string) $appt->enddatetime;
    }
    
    // See if updated values are valid
    if ($force)
        $errors = $appobject->validate('force');
    else
        $errors = $appobject->validate('update');
    
    if ($errors)
                                                                         /* If an error, return error code and message */
                                                                         return WaseUtil::Error(12, array(
            $errors
        ), 'AJAXERR', false);
    
    /* Now update the appointment. */
    try {
        $appointmentid = $appobject->save('update');
        $infmsg = $labels['APPTHING'] . ' changed.';
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Re-read the updated appointment */
    try {
        $appobject = new WaseAppointment('load', array(
            'appointmentid' => $appt->appointmentid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg><appt>' . $appobject->xmlApptInfo() . '</appt>';
}

/**
 * Delete an appointment.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeleteappointment($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deleteappointment'))
        return $err;
    
    /* Get the appointmentid and cancel text */
    $appointmentid = (int) $child->appointmentid;
    if (! $appointmentid)
        return WaseUtil::Error(13, array(
            'appointmentid',
            'deleteappointment'
        ), 'AJAXERR');
    
    $text = $child->text;
    
    /* Load up the appointment */
    try {
        $appobject = new WaseAppointment('load', array(
            'appointmentid' => $appointmentid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    // Get our labels
    $labels = WaseUtil::getLabels(array(
        'blockid' => $appobject->blockid
    ));
    
    /* Default return message */
    $infmsg = $labels['APPTHING'] . ' not deleted.';
    
    /* Load up the block */
    try {
        $blockobject = new WaseBlock('load', array(
            'blockid' => $appobject->blockid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Disallow if user does not own appointment or block or calendar */
    if (! $appobject->canDeleteAppointment($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'appointment'
        ), 'AJAXERR');
    
    /* Also disallow if not the block owner and a cancellation deadline has been exceeded */
    if (! $blockobject->isOwner($_SESSION['authtype'], $_SESSION['authid'])) {
        if ($blockobject->candeadline_reached($appobject->startdatetime))
            return WaseUtil::Error(33, array(
                'calendar'
            ), 'AJAXERR');
    }
    
    /* Now delete the appointment (this will send email as needed). */
    $appobject->delete($text, '');
    $infmsg = $labels['APPTHING'] . ' deleted.';
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><infmsg>' . $infmsg . '</infmsg><appointmentid>' . $appointmentid . '</appointmentid><canceltext>' . $text . '</canceltext>';
}

/**
 * Send out iCal appointment information.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dosyncappointment($child)
{
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'syncappointment'))
        return $err;
    
    /* Make sure we got the needed data */
    $appointmentid = $child->appointmentid;
    if (! $appointmentid)
        return WaseUtil::Error(13, array(
            'appointmentid',
            'syncappointment',
            $child
        ), 'AJAXERR');
    
    $userid = $child->userid;
    if (! $userid)
        return WaseUtil::Error(13, array(
            'userid',
            'syncappointment',
            $child
        ), 'AJAXERR');
    
    /* Load up the appointment. */
    try {
        $appobject = new WaseAppointment('load', array(
            'appointmentid' => $appointmentid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user can schedule/edit this appointment. */
    if (! $appobject->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'appointment'
        ), 'AJAXERR');
    
    /* Get the owning block */
    $block = new WaseBlock('load', array(
        'blockid' => $appobject->blockid
    ));
    
    /* How we sync depends on the user's local sync preference */
    
    /* Get the user's sync preference */
    $syncpref = WasePrefs::getPref($userid, 'localcal');
    
    $syncdone = false;
    $syncfail = '';
    $ical = '';
    
    /* Sync as per the user's preference. */
    switch ($syncpref) {
        case 'google':
            if (! $appobject->gid)
                $syncdone = WaseGoogle::addApp($appobject, $block, $userid);
            else
                $syncdone = WaseGoogle::changeApp($appobject, $block, $userid);
            
            /* Save synchronization done status */
            if (! $syncdone)
                $syncfail = 'Google';
            
            break;
        
        case 'exchange':
            if (! $appobject->eid)
                $syncdone = WaseExchange::addApp($appobject, $block, $userid, $appobject->email);
            else
                $syncdone = WaseExchange::changeApp($appobject, $block, $userid, $appobject->email);
            
            /* Save synchronization done status */
            if (! $syncdone)
                $syncfail = 'Exchange';
            
            break;
        
        default :
                                        /* Build and return an iCalendar stream for the appointment */
                                        $ical = WaseIcal::addApp($appobject, $block);
            $syncdone = true;
            break;
    }
    
    if (! $syncdone)
        $ret = WaseUtil::Error(14, array(
            'Unable to synchronize with ' . $syncfail . ': ' . $syncdone
        ), 'AJAXERR');
    else
        $ret = WaseUtil::Error(0, '', 'AJAXERR');
    
    /* Now return the data to the caller */
    return $ret . '<sessionid>' . $sessionid . '</sessionid><appointmentid>' . $appointmentid . '</appointmentid><userid>' . $userid . '</userid><ical>' . $ical . '</ical>';
}

/**
 * Lock an appointment.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolockappointment($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'lockappointment'))
        return $err;
    
    /* Make sure we got the needed data */
    $appointmentid = $child->appointmentid;
    if (! $appointmentid)
        return WaseUtil::Error(13, array(
            'appointmentid',
            'lockappointment',
            $child
        ), 'AJAXERR');
    
    /* Load up the appointment. */
    try {
        $appobject = new WaseAppointment('load', array(
            'appointmentid' => $appointmentid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user can schedule/edit this appointment. */
    if (! $appobject->isOwner($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'appointment'
        ), 'AJAXERR');
    
    /* Update available as specified */
    if ($child->makeavailable == 0)
        $appobject->available = 0;
    else
        $appobject->available = 1;
    
    /* Now update the appointment. */
    try {
        $appointmentid = $appobject->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><appointmentid>' . $appointmentid . '</appointmentid><makeavailable>' . $child->makeavailable . '</makeavailable>';
}

/**
 * Validate an email address.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doisemailvalid($child)
{
    
    /* Get parameter */
    $email = trim($child->email);
    
    /* See if valid */
    if (WaseUtil::validateEmail($email) == '')
        $valid = 1;
    else
        $valid = 0;
    
    /* Return results */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<email>' . $email . '</email><isvalid>' . $valid . '</isvalid>';
}

/**
 * Add a manager to a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddmanager($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addmanager'))
        return $err;
    
    /* Make sure we got the manager data */
    $manager = $child->manager;
    if (! is_object($manager))
        return WaseUtil::Error(13, array(
            'manager',
            'addmanager'
        ), 'AJAXERR');

    /* Make sure we got the user data */
    $user = $manager->user;
    if (! is_object($user))
        return WaseUtil::Error(13, array(
            'manager',
            'addmanager'
        ), 'AJAXERR');

    /* Make sure we got the userid */
    if (!$userid = $user->userid)
        return WaseUtil::Error(13, array(
            'manager',
            'addmanager'
        ), 'AJAXERR');

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $manager->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is adding to their own calendar or is
     * adding a manager for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');

    // Manager must be a valid netid.  If not, return error 10 unless userid is first part of an email address,
    // in which case we return error 43.

    if (!$testuserid = netidoremail($userid))
        return WaseUtil::Error(10, array(
            WaseUtil::getParm('NETID'),
            'manager',
            $userid
            ), 'AJAXERR') .
            $manager->asXML();
    elseif ($testuserid != $userid) {
        // Return error with email/name of matching user
        $name = '(' . $directory->getName($testuserid) . ')';
        return WaseUtil::Error(43, array(
            $userid,
            WaseUtil::getParm('NETID'),
                $directory->getEmail($testuserid),
            $testuserid,
            $name,
            'manager'
            ), 'AJAXERR') .
            $manager->asXML();
    }

    /*
     * A member cannot also be a manager.
     */
    if ($calendar->isMember($userid))
        return WaseUtil::Error(41, array(), 'AJAXERR');
    
    /* If specified user is already a manager (pending or active), reject the add request */
    if (WaseManager::isManager($calendar->calendarid, $userid))
        return WaseUtil::Error(35, array(
            $manager,
            'manager',
            $calendar->calendarid
        ), 'AJAXERR');
    
    /* If the user owns the calendar, they cannot also be a manager */
    if ($calendar->userid == $userid)
        return WaseUtil::Error(42, array(
            'manager'
        ), 'AJAXERR');
    
    /* Try to create the WaseManager entry. */
    try {
        $managerdata = arrayiffy($manager);
        $WaseManager = new WaseManager('create', $managerdata);
    } catch (exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now save the mnager */
    try {
        $WaseManager->save('create');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<manager>' . $WaseManager->xmlManagerInfo() . '</manager>';
}

/**
 * Edit manager properties.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditmanager($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editmanager'))
        return $err;
    
    /* Make sure we got the manager data */
    $manager = $child->manager;
    if (! is_object($manager))
        return WaseUtil::Error(13, array(
            'manager',
            'editmanager'
        ), 'AJAXERR');
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $manager->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * editing a manager for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Load up the manager and update it. */
    try {
        $managerdata = arrayiffy($manager);
        $WaseManager = new WaseManager('update', $managerdata);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Write out the updated manager */
    try {
        $WaseManager->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<manager>' . $WaseManager->xmlManagerInfo() . '</manager>';
}

/**
 * Delete a manager.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeletemanager($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deletemanager'))
        return $err;
    
    /* Make sure we got the userid data */
    $manager = $child->manager;
    if (! is_object($manager))
        return WaseUtil::Error(13, array(
            'manager',
            'deletemanager'
        ), 'AJAXERR');
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $manager->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * removing a manager for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Load up the manager and delete it. */
    try {
        $managerdata = arrayiffy($manager);
        $WaseManager = new WaseManager('load', $managerdata);
        $WaseManager->delete($child->canceltext);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<manager>' . $WaseManager->xmlManagerInfo() . '</manager>' . '<canceltext>' . $child->canceltext . '</canceltext>';
}

/**
 * List specified data values from all calendars.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolistmanagers($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'listcalendars'))
        return $err;
    
    /* Start to build the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $child->calendarid . '</calendarid>';
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $child->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * removing a manager for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Add manager info to the stream. */
    $ret .= $calendar->xmlManagerInfo();
    
    /* Return the completed string */
    return $ret;
}

/**
 * List the calendars that a user manages.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolistmanaged($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'listmanaged'))
        return $err;
    
    /* Make sure we got the userid data */
    $userid = $child->userid;
    if (! is_object($userid))
        return WaseUtil::Error(13, array(
            'userid',
            'listmanaged'
        ), 'AJAXERR');
    
    /* Start to build the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    
    /* Get WaseList of all calendars managed by the specified user. */
    $calendars = WaseManager::wListManaged($child->userid);
    
    /* Go through and give the header data for each calendar */
    if ($calendars)
        foreach ($calendars as $calendar) {
            $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
        }
    
    /* Return the completed string */
    return $ret;
}

/**
 * Add a member to a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddmember($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addmember'))
        return $err;
    
    /* Make sure we got the member data */
    $member = $child->member;
    if (! is_object($member))
        return WaseUtil::Error(13, array(
            'member',
            'addmember'
        ), 'AJAXERR');

    /* Make sure we got the user data */
    $user = $member->user;
    if (! is_object($user))
        return WaseUtil::Error(13, array(
            'member',
            'addmember'
        ), 'AJAXERR');

    /* Make sure we got the member userid */
    if (!$userid = $user->userid)
        return WaseUtil::Error(13, array(
            'member',
            'addmember'
        ), 'AJAXERR');
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $member->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is adding to their own calendar or is
     * adding a member for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');

    // Member must be a valid netid.  If not, return error 10 unless userid is first part of an email address,
    // in which case we return error 43.
    // UNLESS member is an @member

    $userid = trim($userid);
    $atmember = substr($userid, 0, 1) == '@' ? true : false;

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    if (!$atmember) {
        if (!$testuserid = netidoremail($userid))
            return WaseUtil::Error(10, array(
                    WaseUtil::getParm('NETID'),
                    'member',
                    $userid
                ), 'AJAXERR') .
                $member->asXML();
        elseif ($testuserid != $userid) {
            // Return error with email/name of matching user
            $name = '(' . $directory->getName($testuserid) . ')';
            return WaseUtil::Error(43, array(
                    $userid,
                    WaseUtil::getParm('NETID'),
                    $directory->getEmail($testuserid),
                    $testuserid,
                    $name,
                    'member'
                ), 'AJAXERR') .
                $member->asXML();
        }


        // Proposed member cannot own or manage the target calendar.

        if ($calendar->isOwnerOrManager('user', $userid))
            return WaseUtil::Error(40, array(), 'AJAXERR');

    }


    /* If specified user is already a member (pending or active), reject the add request */
    if (WaseMember::isMember($calendar->calendarid, $userid))
        return WaseUtil::Error(35, array(
            $userid,
            'member',
            $calendar->calendarid
        ), 'AJAXERR');
    
    /* If the user owns the calendar, they cannot also be a member */
    if ($calendar->userid == $userid)
        return WaseUtil::Error(42, array(
            'member'
        ), 'AJAXERR');
    
    /* Try to create the WaseMember entry. */
    try {
        $memberdata = arrayiffy($member);
        $WaseMember = new WaseMember('create', $memberdata);
    } catch (exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now save the member */
    try {
        $WaseMember->save('create');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<member>' . $WaseMember->xmlMemberInfo() . '</member>';
}

/**
 * Edit member properties.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditmember($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editmember'))
        return $err;
    
    /* Make sure we got the member data */
    $member = $child->member;
    if (! is_object($member))
        return WaseUtil::Error(13, array(
            'member',
            'editmember'
        ), 'AJAXERR');
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $member->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * editing a member for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Load up the member and update it. */
    try {
        $memberdata = arrayiffy($member);
        $WaseMember = new WaseMember('update', $memberdata);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Write out the updated member */
    try {
        $WaseMember->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<member>' . $WaseMember->xmlMemberInfo() . '</member>';
}

/**
 * Delete a member.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeletemember($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'deletemember'))
        return $err;
    
    /* Make sure we got the userid data */
    $member = $child->member;
    if (! is_object($member))
        return WaseUtil::Error(13, array(
            'member',
            'deletemember'
        ), 'AJAXERR');
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $member->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * removing a member for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Load up the member and delete it. */
    try {
        $memberdata = arrayiffy($member);
        $WaseMember = new WaseMember('load', $memberdata);
        $WaseMember->delete($child->canceltext);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now let the caller know all went well */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid>' . '<member>' . $WaseMember->xmlMemberInfo() . '</member>' . '<canceltext>' . $child->canceltext . '</canceltext>';
}

/**
 * List members of a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolistmembers($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'listmembers'))
        return $err;
    
    /* Start to build the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $child->calendarid . '</calendarid>';
    
    /* Get the calendar. */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $child->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /*
     * Make sure authenticated user is editing their own calendar or is
     * removing a member for someone whose calendar they manage.
     */
    if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(24, array(
            $_SESSION['authid'],
            $calendar->userid
        ), 'AJAXERR');
    
    /* Add list of all members to the stream. */
    $ret .= '<members>' . $calendar->xmlMemberInfo() . '</members>';
    
    /* Return the completed string */
    return $ret;
}

/**
 * List the calendars that a user is a member of.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dolistmembered($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'listmembered'))
        return $err;
    
    /* Make sure we got the userid data */
    $userid = $child->userid;
    if (! is_object($userid))
        return WaseUtil::Error(13, array(
            'userid',
            'listmembered'
        ), 'AJAXERR');
    
    /* Start to build the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    
    /* Get WaseList of all calendars membered by the specified user. */
    $calendars = WaseMember::wListMembered($child->userid);
    
    /* Go through and give the header data for each calendar */
    if ($calendars)
        foreach ($calendars as $calendar) {
            $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
        }
    
    /* Return the completed string */
    return $ret;
}

/**
 * Apply to manage the calendar for the userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doapplytomanagecalendar($child)
{
    
    /* Extract the parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $calendarid = $child->calendarid;
    $emailtext = $child->emailtext;
    
    /* Build return string trailer */
    $trail = '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><calendarid>' . $calendarid . '</calendarid><emailtext>' . $emailtext . '</emailtext>';
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'applytomanagecalendar'))
        return $err;
    
    /* Make sure we got the userid data */
    $userid = $child->userid;
    if (! is_object($userid))
        return WaseUtil::Error(13, array(
            'userid',
            'applytomanagecalendar'
        ), 'AJAXERR') . $trail;
    
    /* Make sure we got the calendar data */
    if (! $calendarid)
        return WaseUtil::Error(13, array(
            'calendarid',
            'applytomanagecalendar'
        ), 'AJAXERR') . $trail;
    
    /* Load up the calendar */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false) . $trail;
    }
    
    /* If specified userid is not the same as the authenticated userid */
    if ($userid != $_SESSION['authid'])
                                /* Only allow if logged in user owns or manages the calendar */
                                if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid'])) {
            return WaseUtil::Error(24, array(
                $_SESSION['authid'],
                $userid
            ), 'AJAXERR') . $trail;
        }
    
    /* Return an error if the user is already a pending or active manager */
    $allready = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid), 'Manager');
    if ($allready->entries()) {
        return WaseUtil::Error(35, array(
            $userid,
            'manager',
            $calendarid
        ), 'AJAXERR') . $trail;
    }
    
    /* Make the user a pending manager */
    $manager = new WaseManager('create', array(
        'userid' => $userid,
        'calendarid' => $calendarid,
        'status' => 'pending'
    ));
    
    try {
        $manager->save('create', $child->emailtext);
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false) . $trail;
    }
    
    /* Pass back the XML */
    
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . $trail;
    
    return $ret;
}

/**
 * Apply to be a member of the calendar for the userid.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doapplytomembercalendar($child)
{
    
    /* Extract the parameters */
    $sessionid = trim((string) $child->sessionid);
    $userid = trim((string) $child->userid);
    $calendarid = $child->calendarid;
    $emailtext = $child->emailtext;
    
    /* Build return string trailer */
    $trail = '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid><calendarid>' . $calendarid . '</calendarid><emailtext>' . $emailtext . '</emailtext>';
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'applytomembercalendar'))
        return $err . $trail;
    
    /* Make sure we got the userid data */
    $userid = $child->userid;
    if (! is_object($userid))
        return WaseUtil::Error(13, array(
            'userid',
            'applytomembercalendar'
        ), 'AJAXERR') . $trail;
    
    /* Make sure we got the calendar data */
    if (! $calendarid)
        return WaseUtil::Error(13, array(
            'calendarid',
            'applytomembercalendar'
        ), 'AJAXERR') . $trail;
    
    /* Load up the calendar */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false) . $trail;
    }
    
    /* If specified userid is not the same as the authenticated userid */
    if ($userid != $_SESSION['authid'])
                                /* Only allow if logged in user owns or manages the calendar */
                                if (! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid'])) {
            return WaseUtil::Error(24, array(
                $_SESSION['authid'],
                $userid
            ), 'AJAXERR') . $trail;
        }
    
    /* Return an error if the user is already a pending or active member */
    $allready = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid), 'Member');
    if ($allready->entries()) {
        return WaseUtil::Error(35, array(
            $userid,
            'member',
            $calendarid
        ), 'AJAXERR') . $trail;
    }
    
    /* Make the user a pending member */
    $member = new WaseMember('create', array(
        'userid' => $userid,
        'calendarid' => $calendarid,
        'status' => 'pending'
    ));
    
    try {
        $member->save('create', $child->emailtext);
    } catch (Exception $error) {
        /* If an error, return error code and message. */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false) . $trail;
    }
    
    /* Pass back the XML */
    
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . $trail;
    
    return $ret;
}

/**
 * Return list of courses and course instructors for a given user.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetmycourses($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getmycourses'))
        return $err;
    
    /* Make sure we got the userid data */
    $userid = $child->userid;
    if (! is_object($userid))
        return WaseUtil::Error(13, array(
            'userid',
            'getmycourses'
        ), 'AJAXERR');
    
    /* Start to build the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    //WaseMsg::dMsg('DBG','Info',"dogetmycourses set blackboard_instructor_mode");
    $_SESSION['blackboard_instructor_mode'] = 1;
    /* We need to get the list of courses, instructors and their course calendars. */
    $course_calendars = WaseUtil::getCourseCalendars($userid);
    $_SESSION['blackboard_instructor_mode'] = 0;
    //WaseMsg::dMsg('DBG','Info',"dogetmycourses clear blackboard_instructor_mode");

    /* Now go through the courses and add in their information. */
    if ($course_calendars) {
        
        $ret .= '<courses>';
        if ($course_calendars)
            foreach ($course_calendars as $course) {
                $seencals = array();
                if ($course['course_name'] || $course['course_title']) {
                    if (strpos($course['course_name'], 'ErrorCodes: User ') !== 0) {
                        $course_title = htmlspecialchars($course['course_title'], ENT_XML1);
                        //$ret .= '<course><id>' . $course['course_name'] . '</id><title>' . $course['course_title'] . '</title><instructors>';
                        $ret .= '<course><id>' . $course['course_name'] . '</id><title>' . $course_title . '</title><instructors>';
                        /* Now see if the course has instructors course calendar */
                        if ($course['instructors'])
                            foreach ($course['instructors'] as $instructor) {
                                if ($instructor['userid']) {
                                    $ret .= '<instructor><userid>' . $instructor['userid'] . '</userid><name>' . $instructor['name'] . '</name><calendars>';
                                    /* Now the calendars */
                                    if ($instructor['calendars'])
                                        foreach ($instructor['calendars'] as $calendarid) {
                                            /* Read in the calendard */
                                            $calendar = new WaseCalendar('load', array(
                                                'calendarid' => $calendarid
                                            ));
                                            if (! in_array($calendar->calendarid, $seencals)) {
                                                $seencals[] = $calendar->calendarid;
                                                $ret .= '<calendar>' . $calendar->xmlCalendarHeaderInfo() . '</calendar>';
                                            }
                                            unset($calendar);
                                        }
                                    $ret .= '</calendars></instructor>';
                                }
                            }
                        $ret .= '</instructors></course>';
                    }
                }
            }
        $ret .= '</courses>';
    }

    
    /* Return completed XML to caller */
    return $ret;
}

/**
 * Get info about a given date (or range) to display in a calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetcalendardates($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getcalendardates'))
        return $err;
    
    /* Make sure we got the calendar id data */
    $calendarids = explode(',', (string) trim($child->calendarid));
    if (! $calendarids)
        return WaseUtil::Error(11, array(
            'Calendar',
            'getcalendardates'
        ), 'AJAXERR');
    
    /* Get a list of the viewable calendars */
    $viewable = '';
    
    if ($calendarids)
        foreach ($calendarids as $calendarid) {
            $calendarid = trim($calendarid);
            try {
                $calendar = new WaseCalendar('load', array(
                    'calendarid' => $calendarid
                ));
                /* Make sure the authenticated user can view this calendar. */
                if ($calendar->isViewable($_SESSION['authtype'], $_SESSION['authid'])) {
                    if ($viewable)
                        $viewable .= ',';
                    $viewable .= $calendarid;
                }
                unset($calendar);
            } catch (Exception $error) {
                /* If an error, ignore */
            }
        }
    
    if (! $viewable)
        return WaseUtil::Error(11, array(
            'Viewable calendar',
            'getcalendardates'
        ), 'AJAXERR');
 
        
    /* Now extract the start/end dates */
    $startdatetime = trim((string) $child->startdatetime);
    $enddatetime = trim((string) $child->enddatetime);
    
    if (! $startdatetime)
        $startdatetime = '2000-01-01 00:00:01';
    else {
        @list ($date, $time) = explode(' ', trim($startdatetime));
        if (! $time)
            $startdatetime .= ' 00:00:01';
    }
    $startmin = WaseUtil::datetimeToMin($startdatetime);
    
    if (! $enddatetime)
        $enddatetime = '2999-12-31 23:59:59';
    else {
        @list ($date, $time) = explode(' ', $enddatetime);
        if (! $time)
            $enddatetime .= ' 23:59:59';
    }
    $endmin = WaseUtil::datetimeToMin($enddatetime);
    
    /* Check for reasonable dates */
    if ($endmin < $startmin)
        return WaseUtil::Error(11, array(
            'date range',
            'getcalendardates'
        ), 'AJAXERR');
    
    $test = $endmin - $startmin;
    if ($test > (60 * 24 * 1000))
        return WaseUtil::Error(11, array(
            'date range',
            'getcalendardates'
        ), 'AJAXERR');
    
    /* Init the SELECT string */
    $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE calendarid IN (' . $viewable . ')';
    
    /* Init the output */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $viewable . '</calendarid><startdatetime>' . $startdatetime . '</startdatetime><enddatetime>' . $enddatetime . '</enddatetime>';
    
    /* Loop through dates */
    $olddate = '';
    for ($i = $startmin; $i <= $endmin; $i += (60 * 24)) {
        list ($date, $time) = explode(' ', WaseUtil::minToDateTime($i));
        if ($date == $olddate)
            continue;
        $olddate = $date;
        /* Get the daytype for this date */
        $daytype = WaseAcCal::getDaytype($date);
        
        /* Get all of the block for the specified calendar that start on this date */
        $blocks = new WaseList($select . ' AND date(startdatetime) = "' . $date . '" ORDER BY `startdatetime`', 'Block');  
        
        /* Assume no blocks/apps/slots */
        $blockstat = 'noblocks';
        $appstat = 'noapps';
        $slotstat = 'noslots';
        
        if ($blocks)
            foreach ($blocks as $block) {
                /* We have blocks */
                if ($block->isOwner($_SESSION['authtype'],$_SESSION['authid']))
                    $blockstat = 'myblocks';
                elseif ($blockstat == 'noblocks')
                    $blockstat = 'blocks';
                
                /* Check if user can make an appointment for this block. */
                $whynot = trim($block->isMakeable($_SESSION['authtype'], $_SESSION['authid'], "", ""));
                if (! $whynot)
                    /* We have slots */
                    $slotstat = 'slots';
                
                /* Check if any appointments have been made for this block */
                $appointments = WaseAppointment::listMatchingAppointments(array(
                    "blockid" => $block->blockid,
                    "available" => 1
                ));
                
                if ($appointments->entries()) {
                    /* We have appointments */
                    if ($appstat == 'noapps') {
                        if ($block->isOwner($_SESSION['authtype'],$_SESSION['authid']))
                            $appstat = 'myapps';
                        else
                            $appstat = 'apps';
                    }
                    /* See if any for this user */
                    if ($appointments)
                        foreach ($appointments as $appointment) {
                            // We need to test if block owner owns the appointment (appointment with)
                            if ($appointment->isOwner($_SESSION['authtype'],$_SESSION['authid'])) {
                                $appstat = 'myapps';
                                break;
                            }
                        }
                }
            }
        
        $ret .= '<caldate><date>' . $date . '</date><daytype>' . $daytype . '</daytype><status>' . $blockstat . $appstat . $slotstat . '</status></caldate>';
        
    }
    return $ret;
}

/**
 * Return whether the user is an owner, manager, member of the given calendar (if calendarid), or of any calendars (if no calendarid).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output XML to be returned to the caller.
 */
function dogetusercalendarstatus($child)
{

    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);

    /* Test authentication */
    if ($err = testauth($sessionid, 'getusercalendarstatus'))
        return $err;

    /* Get userid */
    $userid = $child->userid;
    if (! $userid)
        $userid = $_SESSION['authid'];

    /* Get calendarid, if specified */
    $calendarid = (int) $child->calendarid;
    if (! $calendarid)
        $calendarid = '';

    /* Init the return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><userid>' . $userid . '</userid>';

    /* Init return values */
    $isowner = 0;
    $ismanager = 0;
    $ismember = 0;
    $calhasblocks = 0;
    $calhasmembers = 0;
    $calhasmanagers = 0;

    /* If calendar specified, validate it */
    if ($calendarid) {
        /* Get the calendar. */
        try {
            $calendar = new WaseCalendar('load', array(
                'calendarid' => $calendarid
            ));
        } catch (Exception $error) {
            /* If an error, return error code and message. */
            return WaseUtil::Error($error->getCode(), array(
                $error->getMessage()
            ), 'AJAXERR', false);
        }
        /* Make sure the authenticated user can view this calendar. */
        // NO:  return status even if calendar not viewable.
        ////if (! $calendar->isViewable($_SESSION['authtype'], $_SESSION['authid']))//    return WaseUtil::Error(22, array(
        //         $_SESSION['authid'],
        //         'calendar'
        //     ), 'AJAXERR');

        if ($calendar->isOwner('user', $userid))
            $isowner = 1;
        if ($calendar->isManager($userid))
            $ismanager = 1;
        if ($calendar->isMember($userid))
            $ismember = 1;
        $members = WaseMember::wlistActiveMembers($calendarid);
        if ($members->entries())
            $calhasmembers = 1;
        $managers = WaseManager::wlistActiveManagers($calendarid);
        if ($managers->entries())
            $calhasmanagers = 1;
        $blocks = WaseBlock::listMatchingBlocks(array('calendarid'=>$calendarid));
        if ($blocks->entries())
            $calhasblocks = 1;
    } else {
        /* Determine if user owns or manages or is a member of any calendars */
        if (WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE userid=' . WaseSQL::sqlSafe($userid))))
            $isowner = 1;
        if (WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE (status = "active" OR status = "user") AND userid=' . WaseSQL::sqlSafe($userid))))
            $ismanager = 1;
        if (WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE (status = "active" OR status = "user") AND userid=' . WaseSQL::sqlSafe($userid))))
            $ismember = 1;
    }

    return $ret . tag('calhasblocks', $calhasblocks) . tag('calhasmanagers', $calhasmanagers) . tag('calhasmembers', $calhasmembers) . tag('isowner', $isowner) . tag('ismanager', $ismanager) . tag('ismember', $ismember);
}


/**
 * Return whether the user is an owner, manager, member of any calendars, and/or has appointments.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *
 * @return string The output XML to be returned to the caller.
 */
function dogetuserstatus($child)
{

    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);

    /* Test authentication */
    if ($err = testauth($sessionid, 'getuserstatus'))
        return $err;

    /* Get userid */
    $userid = $child->userid;
    if (! $userid)
        $userid = $_SESSION['authid'];

    /* Init the return string */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';

    // Init return values
    $isowner = 0;
    $ismanager = 0;
    $ismember = 0;
    $hasappts = 0;

    // Save whether/not user owns a calendar
    $calids = WaseCalendar::arrayOwnedCalendarids($userid);
    if ($calcount = count($calids))
        $isowner  = 1;


    // Save whether/not user is a manager
    if (WaseManager::listManagedids($userid,''))
        $ismanager  = 1;

    // Save whether/not user is a member
    if (WaseMember::listMemberedids($userid,''))
        $ismember = 1;

    // Save whether or not user has appointments.
    $appids = WaseAppointment::listMatchingAppointments(array('userid'=>$userid));
    if ($appids->entries())
        $hasappts = 1;

    return $ret . tag('isowner', $isowner) . tag('ismanager', $ismanager) . tag('ismember', $ismember) . tag('hasappts', $hasappts);

}

/**
 * This function turns the calendar waitlist flag on or off.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doenablewaitlist($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'enablewaitlist'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendarid = (int) $child->calendarid;
    if (! $calendarid)
        return WaseUtil::Error(10, array(
            'calendarid',
            'enablewaitlist'
        ), 'AJAXERR');
    
    /* Save enable status */
    $enable = (int) $child->enable;
    
    /* Load up the calendar and update it. */
    try {
        /* Create new. updated version of the calendar */
        $calendarobject = new WaseCalendar('update', array(
            'waitlist' => $enable,
            'calendarid' => $calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Make sure the authenticated user owns the calendar. */
    if (! $calendarobject->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR');
    
    /* Write out the updated calendar */
    try {
        $calendarid = $calendarobject->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Purge the wait list if the flag is on */
    if ($calendarobject->waitlist)
        WaseWait::purgeWaitList($calendarobject->calendarid);
    
    /* Now return the data to the caller */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><enable>' . $enable . '</enable>';
}

/**
 * This function adds a waitlist entry.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doaddwaitlistentry($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'addwaitlistentry'))
        return $err;
    
    /* Create the waitlist entry array */
    $waitarray = arrayiffy_waitlist($child->waitlistentry);
    
    /* Create the entry object */
    try {
        $waitentry = new WaseWait('create', $waitarray);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now save the entry */
    try {
        $waitid = $waitentry->save('create');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the results */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><waitlistentry>' . $waitentry->xmlWaitInfo() . '</waitlistentry>';
}

/**
 * This function edits a waitlist entry.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doeditwaitlistentry($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editwaitlistentry'))
        return $err;
    
    /* The wait list user and/or calendar owner can edit the waity list entry */
    
    /* Create the waitlist entry array */
    $waitarray = arrayiffy_waitlist($child->waitlistentry);
    /* Create the entry object */
    try {
        $waitentry = new WaseWait('update', $waitarray);
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $waitentry->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* The wait list user and/or calendar owner can edit the waity list entry */
    if (! ($waitentry->uerid == $_SESSION['authid']) && ! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'wait-entry  calendar'
        ), 'AJAXERR');
    
    /* Now save the entry */
    try {
        $waitentry->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Now return the results */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><waitlistentry>' . $waitentry->xmlWaitInfo() . '</waitlistentry>';
}

/**
 * This function removes a wait list entry
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dodeletewaitlistentry($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'editwaitlistentry'))
        return $err;
    
    /* Get the wait list entry id */
    $waitid = (int) $child->waitid;
    
    /* Load the entry object */
    try {
        $waitentry = new WaseWait('load', array(
            "waitid" => $waitid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    /* Load the calendar */
    try {
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $waitentry->calendarid
        ));
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    /* Only the wait list user and/or calendar owner can delete the wait list entry */
    if (! ($waitentry->userid == $_SESSION['authid']) && ! $calendar->isOwnerOrManager($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'wait-entry  calendar'
        ), 'AJAXERR');
    
    /* Now save the entry */
    $waitentry->delete((string) $child->deletetext);
    
    /* Now return the results */
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><waitlistentry>' . $waitentry->xmlWaitInfo() . '</waitlistentry><deletetext>' . $child->deletetext . '</deletetext>';
}

/**
 * This function returns all of the wait list entries for a given calendar.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetwaitlistforcalendar($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getwaitlistforcalendar'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendarid = (int) $child->calendarid;
    if (! $calendarid)
        return WaseUtil::Error(10, array(
            'calendarid',
            'getwaitlistforcalendar'
        ), 'AJAXERR');
    
    /* Load up the calendar */
    $calendar = new WaseCalendar('load', array(
        'calendarid' => $calendarid
    ));
    
    /* If not owner/manager/member, reject the request */
    if (! $calendar->isOwnerOrManagerOrMember($_SESSION['authtype'], $_SESSION['authid']))
        return WaseUtil::Error(12, array(
            $_SESSION['authid'],
            'calendar'
        ), 'AJAXERR');
    
    /* Get all wait-list entries */
    $entries = WaseWait::listWaitForCalendar($calendarid);
    
    /* Start up the return XML */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid>';
    /* Add in the entries */
    foreach ($entries as $entry) {
        $ret .= '<waitlistentry>' . $entry->xmlWaitInfo() . '</waitlistentry>';
    }
    
    return $ret;
}

/**
 * This function returns all of the wait list entries for a given user.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetwaitlistforuser($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getwaitlistforuser'))
        return $err;
    
    /* Make sure we got the user data */
    $userid = (string) $child->userid;
    if (! $userid)
        // WaseUtil::Error(11, array(
        // 'userid',
        // 'getwaitlistforuser'
        // ), 'AJAXERR');
        $entries = '';
    else
        $entries = WaseWait::listWaitForUser($userid);
    
    /* Start up the return XML */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><userid>' . $userid . '</userid>';
    
    /* Add in the entries if authroized */
    foreach ($entries as $entry) {
        /* Load up the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $entry->calendarid
        ));
        if ($_SESSION['authid'] == $userid || $calendar->isOwnerorManagerorMember($_SESSION['authtype'], $_SESSION['authid']))
            $ret .= '<waitlistentry>' . $entry->xmlWaitInfo() . '</waitlistentry>';
    }
    
    return $ret;
}

/**
 * This function returns all of the wait list entries for a given calendar and user.
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function dogetwaitlistforcalendaranduser($child)
{
    
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'getwaitlistforcalendaranduser'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendarid = (int) $child->calendarid;
    if (! $calendarid)
        return WaseUtil::Error(10, array(
            'calendarid',
            'getwaitlistforcalendaranduser'
        ), 'AJAXERR');
    
    /* Load up the calendar */
    $calendar = new WaseCalendar('load', array(
        'calendarid' => $calendarid
    ));
    
    /* Make sure we got the calendar data */
    $userid = (string) $child->userid;
    if (! $userid)
        // WaseUtil::Error(10, array(
        // 'userid',
        // 'getwaitlistforcalendaranduser',
        // $userid
        // ), 'AJAXERR');
        $entries = '';
    else
        $entries = WaseWait::listWaitForUserAndCalendar($userid, $calendarid);
    
    /* Start up the return XML */
    $ret = WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><userid>' . $userid . '</userid>';
    /* Add in the entries if authroized */
    foreach ($entries as $entry) {
        if ($_SESSION['authid'] == $userid || $calendar->isOwnerorManagerorMember($_SESSION['authtype'], $_SESSION['authid']))
            $ret .= '<waitlistentry>' . $entry->xmlWaitInfo() . '</waitlistentry>';
    }
    
    return $ret;
}

/**
 * This function sets and returns the icalendar password (to subscribe to a calendar).
 *
 * @param object $child
 *            The simplexml object contaning the request.
 *            
 * @return string The output XML to be returned to the caller.
 */
function doseticalpass($child)
{
    /* Grab the sessionid */
    $sessionid = trim((string) $child->sessionid);
    
    /* Test authentication */
    if ($err = testauth($sessionid, 'seticalpass'))
        return $err;
    
    /* Make sure we got the calendar data */
    $calendarid = (int) $child->calendarid;
    if (! $calendarid)
        return WaseUtil::Error(10, array(
            'calendarid',
            'seticalpass',
            0
        ), 'AJAXERR');
    
    // Generate an ical password, unless one was passed in the stream.
    $icalpass = (string) $child->icalpass;
    if (! $icalpass) {
        $icalpass = 'notset';
        while ($icalpass == 'notset') {
            $icalpass = WaseUtil::genpass(10);
        }
    }
    
    /* Update the calendar */
    try {
        $calendar = new WaseCalendar('update', array(
            'calendarid' => $calendarid,
            'icalpass' => $icalpass
        ));
        $calendar->save('update');
    } catch (Exception $error) {
        /* If an error, return error code and message */
        return WaseUtil::Error($error->getCode(), array(
            $error->getMessage()
        ), 'AJAXERR', false);
    }
    
    // Let the caller know
    return WaseUtil::Error(0, '', 'AJAXERR') . '<sessionid>' . $sessionid . '</sessionid><calendarid>' . $calendarid . '</calendarid><icalpass>' . $icalpass . '</icalpass>';
}

/**
 * This function tests authentication credentials passed in the ajax stream against the session variables set by login.
 *
 * @param string $sessionid
 *            The PHP session id.
 * @param string $caller
 *            Name of calling function (for error message).
 *            
 * @return null|string authenticated, else error string.
 */
function testauth($sessionid, $caller)
{
    
    /* If no sessionid passed, return an error */
    if (! $sessionid)
        return WaseUtil::Error(11, array(
            'sessionid',
            $caller
        ), 'AJAXERR');
    
    /* Start the session (this will set the session variables). */
    if (session_status() != PHP_SESSION_ACTIVE)
        session_id($sessionid);
    @session_start();
    
    /* Test authentication */
    if (! $_SESSION['authenticated'])
        return WaseUtil::Error(21, '', 'AJAXERR');
}


/**
 * This function looks for a netid OR an email address that starts with the passed netid.
 *
 * @param string $netid
 *            The netid or email address.
 *
 * @return null if neither valid netid OR email, else return macthing netid (which may be the same as the passed netid).
 */

function netidoremail($netid)
{

    // Make sure netid passed.
    if (!$netid)
        return "";

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    // If valid netid passed, just return it.
    if ($directory->useridCheck($netid))
        return $netid;
    else {
        // Append @^ to passed netid, unless it already has an @ sign
        if (($atpos = strpos($netid, '@')) !== false)
            $netid = substr($netid, 0, $atpos);
        // Check if passed netid is first part of an email address, and, if so, return matching netid.
        if ($userid = $directory->getNetid(WaseUtil::getParm('LDAPEMAIL'), $netid . '@*'))
            return $userid;
        else
            return "";

    }

}

/**
 * This function returns a WaseList of appointments for/with a given user for a given block.
 *
 * @param string $userid
 *            The userid of the whose appointments are being queried.
 *            
 * @param
 *            int blockid
 *            The block id of the block to be searched.
 *            
 * @return WaseList The list of appointments.array
 *        
 */
function userappsforblock($userid, $blockid)
{
    
    // Get the block
    $block = new WaseBlock('load', array(
        "blockid" => $blockid
    ));
    // Get the calendar
    $calendar = new WaseCalendar('load', array(
        "calendarid" => $block->calendarid
    ));
    
    // If user is calendar owner or member, return all appointments.
    if ($calendar->isOwnerOrManager('user', $userid))
        return allappsforblock($blockid);
    
    // Return appointments with or for logged-in user.
    return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $blockid . ' AND (userid = ' . WaseSQL::sqlSafe($userid) . '  OR blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . ')))', 'Appointment');
}

/**
 * This function returns a WaseList of appointments for/with a given user for a given block.
 *
 * @param string $userid
 *            The userid of the whose appointments are being queried.
 *            
 * @param
 *            int blockid
 *            The block id of the block to be searched.
 *            
 * @return WaseList The list of appointments.array
 *        
 */
function alluserappsforblock($userid, $blockid)
{
    
    // Get the block
    $block = new WaseBlock('load', array(
        "blockid" => $blockid
    ));
    // Get the calendar
    $calendar = new WaseCalendar('load', array(
        "calendarid" => $block->calendarid
    ));
    
    // If user is calendar owner or member, return all appointments.
    if ($calendar->isOwnerOrManagerorMember('user', $userid))
        return allappsforblock($blockid);
    
    // Return appointments with or for logged-in user.
    return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $blockid . ' AND (userid = ' . WaseSQL::sqlSafe($userid) . '  OR blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . ')))', 'Appointment');
}

/**
 * This function returns a WaseList of appointments for a given block.
 *
 * @param
 *            int blockid
 *            The block id of the block to be searched.
 *            
 * @return WaseList The list of appointments.array
 *        
 */
function allappsforblock($blockid)
{
    return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $blockid . ')', 'Appointment');
}

/**
 * This function returns a WaseList of appointments for/with a given user for a given block.
 *
 * @param string $userid
 *            The userid of the whose appointments are being queried.
 *            
 * @param
 *            int blockid
 *            The block id of the block to be searched.
 *            
 * @param
 *            datetime startdatetime
 *            Start yime of slot.
 *            
 * @param
 *            datetime enddatetime
 *            End time of slot.
 *            
 * @return WaseList The list of appointments.array
 *        
 */
function userappsforslot($userid, $blockid, $startdatetime, $enddatetime)
{
    
    // Get the block
    $block = new WaseBlock('load', array(
        "blockid" => $blockid
    ));
    // Get the calendar
    $calendar = new WaseCalendar('load', array(
        "calendarid" => $block->calendarid
    ));
    
    // Return all apps if showappinfo is on
    if ($block->showappinfo)
        return allappsforslot($blockid, $startdatetime, $enddatetime);
    
    // If user is calendar owner or manager, return all appointments.
    if ($calendar->isOwnerOrManager('user', $userid))
        return allappsforslot($blockid, $startdatetime, $enddatetime);
    
    // Return appointments with or for logged-in user.
    return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $blockid . ' AND startdatetime < "' . $enddatetime . '" AND enddatetime > "' . $startdatetime . '"  AND (userid = ' . WaseSQL::sqlSafe($userid) . '  OR blockid IN (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . ')))', 'Appointment');
}

/**
 * This function returns a WaseList of appointments for a given slot.
 *
 * @param
 *            int blockid
 *            The block id of the block to be searched.
 *            
 * @param
 *            datetime startdatetime
 *            Start yime of slot.
 *            
 * @param
 *            datetime enddatetime
 *            End time of slot.
 *            
 * @return WaseList The list of appointments.array
 *        
 */
function allappsforslot($blockid, $startdatetime, $enddatetime)
{
    return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid=' . $blockid . ' AND startdatetime < "' . $enddatetime . '" AND enddatetime > "' . $startdatetime . '")', 'Appointment');
}

/**
 * This functionconverts an array to xml.
 *
 * @param array $array
 *            The array to be converted.
 *            
 * @param SimpleXMLElement $xml_data
 *            The (building) xml string.
 *            
 * @return SimpleXMLElenent The simplexml element.
 *        
 */
function array_to_xml($array, &$xml_data)
{
    foreach ($array as $key => $value) {
        if (is_numeric($key)) {
            $key = 'item' . $key; // dealing with <0/>..<n/> issues
        }
        if (is_array($value)) {
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

/**
 * This function returns an xml tag with the specified name and value
 *
 * @param string $name
 *            The XML tag name.
 *            @ param string $value The XML vale.
 *            
 * @return string The XML tag.
 */
function tag($name, $value)
{
    return '<' . $name . '>' . WaseUtil::safeXML($value) . '</' . $name . '>';
}

/**
 * This function takes a nested Simplexml object and returns a flattened associative array.
 *
 * @param object $simplexml
 *            The simplexml object contaning the request.
 *            
 * @return array An associative array of name/value pairs.
 */
function arrayiffy($simplexml)
{
    $result = array();
    $arr = get_object_vars($simplexml);
    if ($arr)
        foreach ($arr as $key => $value) {
            if (is_object($value)) {
                /* We want to merge, but not replace existing elements */
                $result = array_merge_noreplace($result, arrayiffy($value));
            } elseif (! array_key_exists($key, $result))
                $result[$key] = $value;
        }
    return $result;
}

/**
 * This function merges a second array into a first array, but does not override the first element values
 *
 * @param array $prime
 *            The first array.
 * @param array $second
 *            The second array.
 *            
 * @return array The merged array.
 */
function array_merge_noreplace($prime, $second)
{
    if ($second)
        foreach ($second as $key => $value) {
            if (! array_key_exists($key, $prime))
                $prime[$key] = $value;
        }
    return $prime;
}

/**
 * This function returns an xml stream of information about a user.
 *
 * @param string $userid.            
 *
 * @return string The XMl string.
 *
 * This function first checks to see if a netid was passed, and, if that fails, if the passed userid is actually the first part of an email address.
 *
 *
 */
function getUserInfo($userid)
{
    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    return '<userinfo>' . '<userid>' . $userid . '</userid>' . '<name>' . $directory->getName($userid) . '</name>' . '<phone>' . $directory->getPhone($userid) . '</phone>' . '<email>' . $directory->getEmail($userid) . '</email>' . '</userinfo>';

}

/**
 * This function returns an array of information about an appoitment.
 *
 * @param WaseAppointment $xml
 *            The appointment object.
 *            
 * @return array The appointment as an array.
 */
function arrayiffy_apptinfo($xml)
{
    $a = arrayiffy_apptheaderinfo($xml);
    
    if (property_exists($xml, 'purpose'))
        $a['purpose'] = (string) $xml->purpose;
    if (property_exists($xml, 'venue'))
        $a['venue'] = (string)$xml->venue;
    if (property_exists($xml, 'remind'))
        $a['remind'] = (int) $xml->remind;
    if (property_exists($xml, 'textemail'))
        $a['textemail'] = (string) $xml->textemail;
    if (property_exists($xml, 'whenmade'))
        $a['whenmade'] = (string) $xml->whenmade;
    if (property_exists($xml, 'madeby'))
        $a['madeby'] = (string) $xml->madeby;
    if (property_exists($xml, 'uid'))
        $a['uid'] = (string) $xml->uid;
    if (property_exists($xml, 'gid'))
        $a['gid'] = (string) $xml->gid;
    if (property_exists($xml, 'eid'))
        $a['eid'] = (string) $xml->eid;
    if (property_exists($xml, 'sequence'))
        $a['sequence'] = (string) $xml->sequence;
    
    return $a;
}

/**
 * This function returns an array of header information about an appoitment.
 *
 * @param WaseAppointment $xml
 *            The appointment object.
 *            
 * @return array The appointment header as an array.
 */
function arrayiffy_apptheaderinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'appointmentid'))
        $a['appointmentid'] = (int) $xml->appointmentid;
    if (property_exists($xml, 'startdatetime'))
        $a['startdatetime'] = (string) $xml->startdatetime;
    if (property_exists($xml, 'enddatetime'))
        $a['enddatetime'] = (string) $xml->enddatetime;
    $a = array_merge($a, arrayiffy_userinfo($xml->apptmaker));
    if (property_exists($xml, 'blockid'))
        $a['blockid'] = (int) $xml->blockid;
    if (property_exists($xml, 'calendarid'))
        $a['calendarid'] = (int) $xml->calendarid;
    if (property_exists($xml, 'available'))
        $a['available'] = (int) $xml->available;
    
    return $a;
}

function arrayiffy_editapptinfo($xml)
{}

/**
 * This function returns an array of header information about a block.
 *
 * @param WaseBlock $xml
 *            The block object.
 *            
 * @return array The block header as an array.
 */
function arrayiffy_blockheaderinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'blockid'))
        $a['blockid'] = (int) $xml->blockid;
    if (property_exists($xml, 'seriesid'))
        $a['seriesid'] = (int) $xml->seriesid;
    if (property_exists($xml, 'calendarid'))
        $a['calendarid'] = (int) $xml->calendarid;
    if (property_exists($xml, 'title'))
        $a['title'] = (string) $xml->title;
    if (property_exists($xml, 'description'))
        $a['description'] = (string) $xml->description;
    if (property_exists($xml, 'startdatetime'))
        $a['startdatetime'] = (string) $xml->startdatetime;
    if (property_exists($xml, 'enddatetime'))
        $a['enddatetime'] = (string) $xml->enddatetime;
    if (property_exists($xml, 'location'))
        $a['location'] = (string) $xml->location;
    if (property_exists($xml, 'maxapps'))
        $a['maxapps'] = (int) $xml->maxapps;
    if (property_exists($xml, 'maxper'))
        $a['maxper'] = (int) $xml->maxper;
    if (property_exists($xml, 'opening'))
        $a['opening'] = (int) $xml->opening;
    if (property_exists($xml, 'deadline'))
        $a['deadline'] = (int) $xml->deadline;
    if (property_exists($xml, 'candeadline'))
        $a['candeadline'] = (int) $xml->candeadline;
    if (property_exists($xml, 'available'))
        $a['available'] = WaseUtil::makeBool($xml->available);
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    
    return array_merge(arrayiffy_userinfo($xml->blockowner), arrayiffy_labelinfo($xml->labels), $a);
}

/**
 * This function returns an array of information about a block.
 *
 * @param WaseBlock $xml
 *            The block object.
 *            
 * @return array The block as an array.
 */
function arrayiffy_blockinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'divideinto'))
        $a['divideinto'] = (string) $xml->divideinto;
    if (property_exists($xml, 'slotsize'))
        $a['slotsize'] = (int) $xml->slotsize;
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    if (property_exists($xml, 'uid'))
        $a['uid'] = (string) $xml->uid;
    if (property_exists($xml, 'gid'))
        $a['gid'] = (string) $xml->gid;
    if (property_exists($xml, 'eid'))
        $a['eid'] = (string) $xml->eid;
    if (property_exists($xml, 'sequence'))
        $a['sequence'] = (int) $xml->sequence;
    
    return array_merge(arrayiffy_blockheaderinfo($xml), arrayiffy_periodinfo($xml->period), arrayiffy_seriesinfo($xml->series), arrayiffy_notifyandremind($xml->notifyandremind), arrayiffy_accessrestrictions($xml->accessrestrictions), $a);
}

/**
 * This function returns an array of information about a block minus the series/period data.
 *
 * @param WaseBlock $xml
 *            The block object.
 *            
 * @return array The block as an array.
 */
function arrayiffy_shortblockinfo($xml)
{
    $a = array_merge(arrayiffy_blockheaderinfo($xml), arrayiffy_notifyandremind($xml->notifyandremind), arrayiffy_accessrestrictions($xml->accessrestrictions));
    
    if (property_exists($xml, 'divideinto'))
        $a['divideinto'] = (string) $xml->divideinto;
    if (property_exists($xml, 'slotsize'))
        $a['slotsize'] = (int) $xml->slotsize;
    if (property_exists($xml, 'available'))
        $a['available'] = WaseUtil::makeBool($xml->available);
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    if (property_exists($xml, 'uid'))
        $a['uid'] = (string) $xml->uid;
    if (property_exists($xml, 'gid'))
        $a['gid'] = (string) $xml->gid;
    if (property_exists($xml, 'eid'))
        $a['eid'] = (string) $xml->eid;
    if (property_exists($xml, 'sequence'))
        $a['sequence'] = (int) $xml->sequence;
    
    return $a;
}

/**
 * This function returns an array of editable information about a series.
 *
 * @param WaseBlock $xml
 *            The block object.
 *            
 * @return array The block header as an array.
 */
function arrayiffy_editseriesinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'title'))
        $a['title'] = (string) $xml->title;
    if (property_exists($xml, 'description'))
        $a['description'] = (string) $xml->description;
    if (property_exists($xml, 'divideinto'))
        $a['divideinto'] = (string) $xml->divideinto;
    if (property_exists($xml, 'slotsize'))
        $a['slotsize'] = (int) $xml->slotsize;
    if (property_exists($xml, 'available'))
        $a['available'] = WaseUtil::makeBool($xml->available);
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    if (property_exists($xml, 'location'))
        $a['location'] = (string) $xml->location;
    if (property_exists($xml, 'maxapps'))
        $a['maxapps'] = (int) $xml->maxapps;
    if (property_exists($xml, 'maxper'))
        $a['maxper'] = (int) $xml->maxper;
    if (property_exists($xml, 'opening'))
        $a['opening'] = (int) $xml->opening;
    if (property_exists($xml, 'deadline'))
        $a['deadline'] = (int) $xml->deadline;
    if (property_exists($xml, 'candeadline'))
        $a['candeadline'] = (int) $xml->candeadline;
    // if (property_exists($xml, 'startdatetime'))
    // $a['startdatetime'] = (string) $xml->startdatetime;
    // if (property_exists($xml, 'enddatetime'))
    // $a['enddatetime'] = (string) $xml->enddatetime;
    
    $a = array_merge($a, arrayiffy_userinfo($xml->blockowner), arrayiffy_notifyandremind($xml->notifyandremind), arrayiffy_accessrestrictions($xml->accessrestrictions), arrayiffy_labelinfo($xml->labels));
    
    return $a;
}

/**
 * This function returns an array of editable information about a series.
 *
 * @param WaseBlock $xml
 *            The block object.
 *            
 * @return array The block header as an array.
 */
function arrayiffy_editblockinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'title'))
        $a['title'] = (string) $xml->title;
    if (property_exists($xml, 'description'))
        $a['description'] = (string) $xml->description;
    if (property_exists($xml, 'divideinto'))
        $a['divideinto'] = (string) $xml->divideinto;
    if (property_exists($xml, 'slotsize'))
        $a['slotsize'] = (int) $xml->slotsize;
    if (property_exists($xml, 'available'))
        $a['available'] = WaseUtil::makeBool($xml->available);
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    if (property_exists($xml, 'location'))
        $a['location'] = (string) $xml->location;
    if (property_exists($xml, 'maxapps'))
        $a['maxapps'] = (int) $xml->maxapps;
    if (property_exists($xml, 'maxper'))
        $a['maxper'] = (int) $xml->maxper;
    if (property_exists($xml, 'opening'))
        $a['opening'] = (int) $xml->opening;
    if (property_exists($xml, 'deadline'))
        $a['deadline'] = (int) $xml->deadline;
    if (property_exists($xml, 'candeadline'))
        $a['candeadline'] = (int) $xml->candeadline;
    if (property_exists($xml, 'startdatetime'))
        $a['startdatetime'] = (string) $xml->startdatetime;
    if (property_exists($xml, 'enddatetime'))
        $a['enddatetime'] = (string) $xml->enddatetime;
    
    $a = array_merge($a, arrayiffy_userinfo($xml->blockowner), arrayiffy_notifyandremind($xml->notifyandremind), arrayiffy_accessrestrictions($xml->accessrestrictions), arrayiffy_labelinfo($xml->labels));
    
    return $a;
}

/**
 * This function returns an array of short information about a calendar.
 *
 * @param WaseCalendar $xml
 *            The calendar object.
 *            
 * @return array The short calendar information as an array.
 */
function arrayiffy_shortcalendarinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'purreq'))
        $a['purreq'] = WaseUtil::makeBool($xml->purreq);
    if (property_exists($xml, 'available'))
        $a['available'] = WaseUtil::makeBool($xml->available);
    if (property_exists($xml, 'overlapok'))
        $a['overlapok'] = WaseUtil::makeBool($xml->overlapok);
    if (property_exists($xml, 'location'))
        $a['location'] = (string) $xml->location;
    
    return array_merge(arrayiffy_calendarheaderinfo($xml), arrayiffy_notifyandremind($xml->notifyandremind), arrayiffy_accessrestrictions($xml->accessrestrictions), $a);
}

/**
 * This function returns an array of header information about a calendar.
 *
 * @param WaseCalendar $xml
 *            The calendar object.
 *            
 * @return array The header calendar information as an array.
 */
function arrayiffy_calendarheaderinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'calendarid'))
        $a['calendarid'] = (int) $xml->calendarid;
    if (property_exists($xml, 'title'))
        $a['title'] = (string) $xml->title;
    if (property_exists($xml, 'description'))
        $a['description'] = (string) $xml->description;
    if (property_exists($xml, 'waitlist'))
        $a['waitlist'] = WaseUtil::makeBool($xml->waitlist);
    
    return array_merge(arrayiffy_userinfo($xml->owner), arrayiffy_labelinfo($xml->labels), $a);
}

/**
 * This function returns an array of information about a user.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The user information as an array.
 */
function arrayiffy_userinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'userid'))
        $a['userid'] = (string) $xml->userid;
    if (property_exists($xml, 'name'))
        $a['name'] = (string) $xml->name;
    if (property_exists($xml, 'phone'))
        $a['phone'] = (string) $xml->phone;
    if (property_exists($xml, 'email'))
        $a['email'] = (string) $xml->email;
    
    return $a;
}

/**
 * This function returns an array of information about a user.
 *
 * If information is missing, it is filled in from the directory.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The user information as an array.
 */
function arrayiffy_userinfofillin($xml)
{
    $a = array();

    // Get our directory class
    $directory = WaseDirectoryFactory::getDirectory();

    if (property_exists($xml, 'userid')) {
        $a['userid'] = (string) $xml->userid;
        $a['name'] = (string) $xml->name;
        if (! $a['name'])
            $a['name'] = $directory->getName($a['userid']);
        $a['phone'] = (string) $xml->phone;
        if (! $a['phone'])
            $a['phone'] = $directory->getPhone($a['userid']);
        $a['email'] = (string) $xml->email;
        if (! $a['email'])
            $a['email'] = $directory->getEmail($a['userid']);
    }
    return $a;
}

/**
 * This function returns an array of notify/remind information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The notify/remind information as an array.
 */
function arrayiffy_notifyandremind($xml)
{
    $a = array();
    
    if (property_exists($xml, 'notify'))
        $a["notify"] = WaseUtil::makeBool($xml->notify);
    if (property_exists($xml, 'notifyman'))
        $a["notifyman"] = WaseUtil::makeBool($xml->notifyman);
    if (property_exists($xml, 'notifymem'))
        $a["notifymem"] = WaseUtil::makeBool($xml->notifymem);
    if (property_exists($xml, 'remind'))
        $a["remind"] = WaseUtil::makeBool($xml->remind);
    if (property_exists($xml, 'remindman'))
        $a["remindman"] = WaseUtil::makeBool($xml->remindman);
    if (property_exists($xml, 'remindmem'))
        $a["remindmem"] = WaseUtil::makeBool($xml->remindmem);
    if (property_exists($xml, 'apptmsg'))
        $a["apptmsg"] = (string) $xml->apptmsg;
    
    return $a;
}

/**
 * This function returns an array of access restrictions information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The access restrictions information as an array.
 */
function arrayiffy_accessrestrictions($xml)
{
    $a = array();
    
    if (property_exists($xml, 'viewaccess'))
        $a['viewaccess'] = (string) $xml->viewaccess;
    if (property_exists($xml, 'viewulist'))
        $a['viewulist'] = (string) $xml->viewulist;
    if (property_exists($xml, 'viewclist'))
        $a['viewclist'] = (string) $xml->viewclist;
    if (property_exists($xml, 'viewglist'))
        $a['viewglist'] = (string) $xml->viewglist;
    if (property_exists($xml, 'viewslist'))
        $a['viewslist'] = (string) $xml->viewslist;
    if (property_exists($xml, 'makeaccess'))
        $a['makeaccess'] = (string) $xml->makeaccess;
    if (property_exists($xml, 'makeulist'))
        $a['makeulist'] = (string) $xml->makeulist;
    if (property_exists($xml, 'makeclist'))
        $a['makeclist'] = (string) $xml->makeclist;
    if (property_exists($xml, 'makeglist'))
        $a['makeglist'] = (string) $xml->makeglist;
    if (property_exists($xml, 'makeslist'))
        $a['makeslist'] = (string) $xml->makeslist;
    if (property_exists($xml, 'showappinfo'))
        $a['showappinfo'] = WaseUtil::makeBool($xml->showappinfo);
    
    return $a;
}

/**
 * This function returns an array of manager/member information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The manager/member information as an array.
 */
function arrayiffy_managermemberinfo($xml)
{
    $a = arrayiffy_userinfofillin($xml->user);
    
    if (property_exists($xml, 'calendarid'))
        $a['calendarid'] = (int) $xml->calendarid;
    if (property_exists($xml, 'status'))
        $a['status'] = (string) $xml->status;
    if (property_exists($xml, 'notify'))
        $a['notify'] = WaseUtil::makeBool($xml->notify);
    if (property_exists($xml, 'remind'))
        $a['remind'] = WaseUtil::makeBool($xml->remind);
    
    return $a;
}

/**
 * This function returns an array of series information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The series information as an array.
 */
function arrayiffy_seriesinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'seriesid'))
        $a['seriesid'] = (int) $xml->seriesid;
    if (property_exists($xml, 'startdate'))
        $a['startdate'] = (string) $xml->startdate;
    if (property_exists($xml, 'enddate'))
        $a['enddate'] = (string) $xml->enddate;
    if (property_exists($xml, 'every'))
        $a['every'] = (string) $xml->every;
    if (property_exists($xml, 'daytypes'))
        $a['daytypes'] = (string) $xml->daytypes;
    
    return $a;
}

/**
 * This function returns an array of period information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The period information as an array.
 */
function arrayiffy_periodinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'periodid'))
        $a['periodid'] = (int) $xml->periodid;
    if (property_exists($xml, 'starttime'))
        $a['starttime'] = (string) $xml->starttime;
    if (property_exists($xml, 'duration'))
        $a['duration'] = (int) $xml->duration;
    
    if (property_exists($xml, 'dayofweek'))
        $a['dayofweek'] = (string) $xml->dayofweek;
    if (property_exists($xml, 'dayofmonth'))
        $a['dayofmonth'] = (int) $xml->dayofmonth;
    if (property_exists($xml, 'weekofmonth'))
        $a['weekofmonth'] = (int) $xml->weekofmonth;
    
    return $a;
}

/**
 * This function returns an array of waitlist entry information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The waitlist entry information as an array.
 */
function arrayiffy_waitlist($xml)
{
    $a = array();
    
    if (property_exists($xml, 'waitid'))
        $a['waitid'] = (int) $xml->waitid;
    if (property_exists($xml, 'calendarid'))
        $a['calendarid'] = (int) $xml->calendarid;
    if (property_exists($xml, 'startdatetime'))
        $a['startdatetime'] = (string) $xml->startdatetime;
    if (property_exists($xml, 'enddatetime'))
        $a['enddatetime'] = (string) $xml->enddatetime;
    if (property_exists($xml, 'whenadded'))
        $a['whenadded'] = (string) $xml->whenadded;
    if (property_exists($xml, 'userid'))
        $a['userid'] = (string) $xml->userid;
    if (property_exists($xml, 'name'))
        $a['name'] = (string) $xml->name;
    if (property_exists($xml, 'phone'))
        $a['phone'] = (string) $xml->phone;
    if (property_exists($xml, 'email'))
        $a['email'] = (string) $xml->email;
    if (property_exists($xml, 'textemail'))
        $a['textemail'] = (string) $xml->textemail;
    if (property_exists($xml, 'msg'))
        $a['msg'] = (string) $xml->msg;
    
    return $a;
}

/**
 * This function returns an array of labels information.
 *
 * @param object $xml
 *            The wase object.
 *            
 * @return array The label information as an array.
 */
function arrayiffy_labelinfo($xml)
{
    $a = array();
    
    if (property_exists($xml, 'NAMETHING'))
        $a['NAMETHING'] = (string) $xml->NAMETHING;
    if (property_exists($xml, 'NAMETHINGS'))
        $a['NAMETHINGS'] = (string) $xml->NAMETHINGS;
    if (property_exists($xml, 'APPTHING'))
        $a['APPTHING'] = (string) $xml->APPTHING;
    if (property_exists($xml, 'APPTHINGS'))
        $a['APPTHINGS'] = (string) $xml->APPTHINGS;
    
    return $a;
}
?>
