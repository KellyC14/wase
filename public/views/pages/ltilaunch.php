<?php
/*
 * This script implements the LTI launch interface for WASE. The LTI configuration in your LMS
 * should point to this file.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Stephen P Vickers, stephen@spvsoftwareproducts.com
 *
 */

/*
 * Handle loading of classes.
 * function __autoload($class)
 * {
 * if (($class == 'WaseParms') || ($class == 'WaseLocal'))
 * $parmspath = getenv('WASSPARMS');
 * if (! $parmspath)
 * $parmspath = '../classes/';
 *
 * if ($class != 'WaseLocal')
 * require_once ($parmspath . $class . '.class.php');
 * else
 * @include_once ($parmspath . $class . '.class.php');
 * }
 */

/* Include the Composer autoloader. */
require_once ('../../../vendor/autoload.php');

/* Make sure we are up and running; if not, terminate with an error message. */
WaseUtil::Init();

/* Make sure we are running under SSL (if required) */
WaseUtil::CheckSSL();

/* Destroy any existing session */
@session_start();
$_SESSION = array();
session_destroy();

/* Start session support */
session_start();

/* Initialize the LTI Tool Provider object and data connector */
$data_connector = LTI_Data_Connector::getDataConnector('', NULL, 'wase');
$tool = new LTI_Tool_Provider(array(
    'launch' => 'doLaunch'
), $data_connector);
$tool->setParameterConstraint('oauth_consumer_key', TRUE);
$tool->setParameterConstraint('context_id', TRUE);
$tool->setParameterConstraint('context_title', TRUE);
$tool->setParameterConstraint('user_id', TRUE);
$tool->setParameterConstraint('roles', TRUE);
$tool->execute();

exit();

/* Callback function to process a valid launch request. */
function doLaunch($tool_provider)
{
    // Make sure we have session support
    if (session_status() == PHP_SESSION_NONE)
        session_start();
    WaseMsg::dMsg('LTI','tool_provider is: ' . print_r($tool_provider,true));
    /* Get course id and course title and userid */
    $_SESSION['courseid'] = $tool_provider->context->getId();
    $_SESSION['coursetitle'] = $tool_provider->context->title;
    $userid = $tool_provider->user->getId();
    
    /* Save login credentials */
    $_SESSION['authenticated'] = true;
    $_SESSION['authtype'] = 'user';
    $_SESSION['authid'] = $userid;
    $_SESSION['inlogin'] = false;
    
    // Start building the re-direct URL.
    $location = 'https://' . $_SERVER['SERVER_NAME'];
    if ($institution = $_SERVER['INSTITUTION'])
        $location .= '/'. $institution;
    
    /* If a student (learner), we now need to get enrollemnt (membership) information to determine the instructors */
    if ($tool_provider->user->isLearner() && $tool_provider->resource_link->hasMembershipsService()) { // If a student and membership data are available
        
        $users = $tool_provider->resource_link->doMembershipsService(); // Get the array of enrolled users in course
        
        /* Build list of calendar ids of WASS calendars for the instructors */
        $calids = array();
        foreach ($users as $user) {
            if ($user->isStaff()) {
                /* Get the userid of the user */
                $instructor = $user->getId();
                /* Get the calendars ids of any of their WASS calendars */
                if ($instructor) {
                    $calids = array_merge($calids, WaseCalendar::arrayOwnedCalendarids($instructor));
                }
            }
        }
        
        /* We now have the calendarids of all WASS calendars for the instructors. */
        if ($calids) {
            $location .= '/views/pages/makeappt.php?calid=' . implode(',', $calids); 
            return $location;
            // header('Location: viewcalendar.php?calid=' . implode(',', $calids));
            //exit();
        }
    }
    
    /* Save whether/not user owns a calendar */
    $calids = WaseCalendar::arrayOwnedCalendarids($userid);
    if (count($calids)) {
        $_SESSION['owner'] = true;
        /* Set ucal_id if only one calendar */
        if (count($calids) == 1)
            $_SESSION['ucal_id'] = $calids[0];
    } else {
        /* If user is a staff member (instructor) and does not own a calendar, create one */
        if ($tool_provider->user->isStaff()) {
            try {
                $cal = new WaseCalendar('create',
                    array(
                        "userid"=>$userid,
                    )
                );
                $id = $cal->save('create');
                $_SESSION['owner'] = true;
                $_SESSION['ucal_id'] = $id;
                $calids = array($id);
            } catch (Exception $e) {
                // Drop through    
            }           
        }
        else
            $_SESSION['owner'] = false;
    }
    
    /* Save whether/not user is a manager */
    if (WaseManager::listManagedids($userid, null)) {
        $_SESSION['ismanager'] = true;
    } else
        $_SESSION['ismanager'] = false;
        
    /* Check for an authentication redirect URL */
    if ($_SESSION['redirurl'] != "") {
        $temp = $_SESSION['redirurl'];
        $_SESSION['redirurl'] = "";
        return htmlspecialchars($temp);
        //header('Location: ' . $temp);
        //exit();
    }
    
    /* Send managers to the managecalendars page */
    if ($_SESSION['ismanager']) {
        $location .= '/views/pages/calendars.php';
        return $location;
        //header('Location: calendars.php');
        //exit();
    } /* Send non-owners to make appointments */
    elseif (! $_SESSION['owner']) {
        $location .= '/views/pages/makeappt.php';
        return $location;
        //header('Location: makeappt.php');
        //exit();
    }  /* Send calendar owners to view their calendar if only one, else to managecalendars */
    else {
        if (count($calids) > 1) {
            $location .= '/views/pages/calendars.php';
            return $location;
            //header('Location: calendars.php');
            //exit();
        } else {
            $_SESSION['cal_id'] = $calids[0];
            $_SESSION['ucal_id'] = $calids[0];
            $location .= '/views/pages/viewcalendar.php?calid=' . $_SESSION['cal_id'];
            return $location;
            //header('Location: viewcalendar.php?calid=' . $_SESSION['cal_id']);
            //exit();
        }
    }
}

?>