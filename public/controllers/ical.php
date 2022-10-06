<?php
/**
 * Generate iCal streams for Wase objects.
 * 
 * The ical.php script returns icalendar-formatted streams with information about various wase objects.  
 * For example, it can return an icalendar stream with information about an appointment. It is typically invoked as:
 * https://serverurl/wasehome/controllers/ical/ical.php?action=getapp&appid=543712
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */

/* Handle loading of classes. */
function __oldautoload($class)
{
    /* Set path to WaseParms and WaseLocal */
    if (($class == 'WaseParms') || ($class == 'WaseLocal'))
        $parmspath = getenv('WaseParms');
    if (! $parmspath)
        $parmspath = '../../models/classes/';
        
        /* Now load the class */
    if ($class != 'WaseLocal')
        require_once ($parmspath . $class . '.php');
    else
        @include_once ($parmspath . $class . '.php');
}

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

// Get passed password, if any
$icalpass = trim($_REQUEST['dbr']);

/* Authentication: we treat the user initiating the request as authenticated, IF AND ONLY IF they have provided a valid password. */
$_SESSION['authtype'] = 'user';
$_SESSION['authid'] = trim($_REQUEST['authid']);

/* Extract the GET (or POST) action arguments. */
if (! $action = strtoupper(trim($_REQUEST['action'])))
    $action = 'GETCAL';
    
    /* Extract the target */
$target = trim($_REQUEST['target']);

/* Now select on the action argument */
switch ($action) {
    
    case 'GETAPP':
		/* Return an iCalendar stream with information on an appointment */
		$appid = trim($_REQUEST['appid']);
        if (! $appid)
            WaseUtil::Error(31, array(
                'appid'
            ), 'HTMLFORMAT,DIE');
            
            /* Load the appointment */
        try {
            $app = new WaseAppointment('load', array(
                'appointmentid' => $appid
            ));
        } catch (Exception $error) {
            WaseUtil::Error(14, array(
                'Unable to locate specified appointment.'
            ), 'HTMLFORMAT,DIE');
        }
        
        /* See if user is authorized to 'get' the appointment */
        if (! $app->isViewable($_SESSION['authtype'], $_SESSION['authid']))
            WaseUtil::Error(22, array(
                'user',
                'appointment'
            ), 'HTMLFORMAT,DIE');
            
            /* Get the owning calendar */
        $cal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        /* Get the owning block */
        $block = new WaseBlock('load', array(
            'blockid' => $app->blockid
        ));
        
        /* Now build an iCalendar stream for the appointment */
        $ret = WaseIcal::addApp($app, $block, $target);
        
        /* Return the icalendar stream */
        WaseIcal::returnICAL($ret, "PUBLISH", 'OfficeHours.ics');
        
        break;
    
    case 'MAILAPP':
		
		/* Return email with an iCalendar stream with information on an appointment */
		$appid = trim($_REQUEST['appid']);
        if (! $appid)
            WaseUtil::Error(31, array(
                'appid'
            ), 'HTMLFORMAT,DIE');
        
        $toid = trim(urldecode($_REQUEST['toid']));
        if (! $toid)
            WaseUtil::Error(31, array(
                'toid'
            ), 'HTMLFORMAT,DIE');
            
            /* Load the appointment */
        try {
            $app = new WaseAppointment('load', array(
                'appointmentid' => $appid
            ));
        } catch (Exception $error) {
            WaseUtil::Error(14, array(
                'Unable to locate specified appointment.'
            ), 'HTMLFORMAT,DIE');
        }
        
        /* See if user is authorized to 'get' the appointment */
        if (! $app->isViewable($_SESSION['authtype'], $_SESSION['authid']))
            WaseUtil::Error(22, array(
                'user',
                'appointment'
            ), 'HTMLFORMAT,DIE');
            
            /* Get the owning calendar */
        $cal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        /* Get the owning block */
        $block = new WaseBlock('load', array(
            'blockid' => $app->blockid
        ));
        
        /* Now build an iCalendar stream for the appointment */
        $icalstream = WaseIcal::addApp($app, $block, $target);
        
        /* Now build the email headers. */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $icalheaders = "MIME-Version: 1.0\r\nReply-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\nErrors-To: $my_sysmail\r\nContent-Type: text/calendar; method=PUBLISH\r\nContent-Transfer-Encoding: 7bit";
        
        
        /* Send the email */
        WaseUtil::Mailer($toid, 'New ' . $block->APPTHING, $icalstream, $icalheaders);
        
        echo 'Email has been sent';
        
        break;
    
    case 'DELAPP':
		/* Load up the appointment */
		$appointmentid = trim(urldecode($_REQUEST['id']));
        $app = new WaseAppointment('load', array(
            'appointmentid' => $appointmentid
        ));
        
        /* Now build an iCalendar stream for deleting the appointment */
        $ret = WaseIcal::delApp($app);
        
        /* Return the icalendar stream */
        WaseIcal::returnICAL($ret, 'PUBLISH', 'OfficeHours.ics');
        
        break; 
    
    case 'MAILDEL':

		/* Load up the appointment */
		$appointmentid = trim(urldecode($_REQUEST['id']));
        $app = new WaseAppointment('load', array(
            'appointmentid' => $appointmentid
        ));
        
        /* Load up the block */
        $block = new WaseBlock('load', array(
                'blockid' => $app->blockid
            ));
        
        /* Extract target email */
        $toid = trim(urldecode($_REQUEST['toid']));
        if (! $toid)
            WaseUtil::Error(31, array(
                'toid'
            ), 'HTMLFORMAT,DIE');
            
            /* Now build an iCalendar stream for deleting the appointment */
        $icalstream = WaseIcal::delApp($app);
        
        /* Now build the email headers. */
        $icalheaders = "MIME-Version: 1.0\r\nReply-To: $my_sysmail\r\nErrors-To: $my_sysmail\r\nContent-Type: text/calendar; method=CANCEL\r\nContent-Transfer-Encoding: 7bit";
        
       
        /* Send the email */
        WaseUtil::Mailer($toid, 'Delete ' . $block->APPTHING, $icalstream, $icalheaders);
        
        echo 'Email has been sent';
        
        break;
    
    case 'GETBLOCK':
		/* Return an iCalendar stream with information on a block */
		
		
		/* Get the block id */	
		$blockid = trim($_REQUEST['blockid']);
        if (! $blockid)
            WaseUtil::Error(31, array(
                'blockid'
            ), 'HTMLFORMAT,DIE');
        
        $recurrence = trim($_REQUEST['recurrence']);
        if (! $recurrence)
            $recurrence = 'all';
            
            /* Load the block */
        try {
            $block = new WaseBlock('load', array(
                'blockid' => $blockid
            ));
        } catch (Exception $error) {
            WaseUtil::Error(14, array(
                'Unable to locate specified block.'
            ), 'HTMLFORMAT,DIE');
        }
        
        /* See if user is authorized to 'get' the block */
        if (! $block->isViewable($_SESSION['authtype'], $_SESSION['authid']))
            WaseUtil::Error(22, array(
                'user',
                'block'
            ), 'HTMLFORMAT,DIE');
            
        /* Start the iCal stream */
        $ret = WaseUtil::getParm('ICALHEADER');
        $ret .= "METHOD:PUBLISH\r\n";
        
        /* Now add the block(s) as ical streams */
        if ((! $block->seriesid) || ($recurrence != 'all'))
			/* If single block */
			$ret .= WaseIcal::addBlock($block);
        else {
            /* First, build a list of all of the blocks */
            $blocks = new WaseList(WaseSQL::buildSelect('WaseBlock', array(
                'seriesid' => $block->seriesid
            )), 'Block');
            
            /* Now add each block as an ical stream */
            $i = 0;
            foreach ($blocks as $block) {
                $i++;
                if (is_object($block))
                    $ret .= WaseIcal::addBlock($block);
            }
        }
        
        $ret .= WaseUtil::getParm('ICALTRAILER');
        
        /* Return the icalendar stream */
        WaseIcal::returnICAL($ret, 'PUBLISH', 'OfficeHours.ics');
        
        break;
    
    case 'GETCAL':
		/* Return an iCalendar stream with information on an entire calendar */
		
		
		/* Get the block id */	
		$calid = trim($_REQUEST['calid']);
        if (! $calid)
            WaseUtil::Error(31, array(
                'calid'
            ), 'HTMLFORMAT,DIE');
            
            /* Load the calendar */
        try {
            $cal = new WaseCalendar('load', array(
                'calendarid' => $calid
            ));
        } catch (Exception $error) {
            WaseUtil::Error(14, array(
                'Unable to locate specified calendar.'
            ), 'HTMLFORMAT,DIE');
        }
        
        /* See if user is authorized to 'get' the calendar */
        if ($cal->icalpass) {
            if (($icalpass != $cal->icalpass) || ($cal->icalpass == 'notset'))
                WaseUtil::Error(22, array(
                'user',
                'calendar'
                    ), 'HTMLFORMAT,DIE');
        }
        if (! $cal->isViewable($_SESSION['authtype'], $_SESSION['authid']))
            WaseUtil::Error(22, array(
                'user',
                'calendar'
            ), 'HTMLFORMAT,DIE');
            
        /* Return the icalendar stream */
        WaseIcal::returnICAL(WaseIcal::allCalendar($calid), 'PUBLISH', 'OfficeHours.ics');
        
        break;
    
    case 'LISTAPPS':
		/* Return an icalendar stream of appointments for a user */
		
		/* Get the args */
		$userid = $_SESSION['authid'];
        $startdate = trim($_REQUEST['startdate']);
        $enddate = trim($_REQUEST['enddate']);
        $starttime = trim($_REQUEST['starttime']);
        $endtime = trim($_REQUEST['endtime']);
        
        /* Return appointment list to the user */
        WaseIcal::returnICAL(WaseIcal::listApps($userid, $startdate, $enddate, $starttime, $endtime), 'PUBLISH', 'OfficeHours.ics');
        
        break;
    
    default:
        WaseUtil::Error(8, array(
            $action
        ), 'HTMLFORMAT,DIE');
        break;
}

exit();

?>