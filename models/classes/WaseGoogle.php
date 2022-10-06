<?php

/**
 * This class implements some class (static) methods that are useful for dealing with Google calendars.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * 
 * The following are the Google calendaring credentials that are used to manage the Google OAUTH2 access.
 * 
 * google account = wasp2gcal@gmail.com
 * account pw = same as Princeton wase database password (not login, database password)
 * google developer web site = https://console.developers.google.com/project
 * https://console.developers.google.com/project/omega-dahlia-497/apiui/credential
 * 
 * 
 */
class WaseGoogle
{

    
    /**
     * This function test a set of Google credentials.
     *
     *
     * @static
     *
     * @param string $userid
     *            the userid of the Google calendar owner.
     * @param array $token
     *            the Google access token array
     *
     * @return bool true|false token is valid/invalid.
     */
    static function testConnect($userid)
    {
        
        /* Abort if we don't have both a uer id and an access token */
        If (! $userid) {
            return false;
        }
        
        /* Get the json_encoded user's access token */
        $token = WasePrefs::getPref($userid, 'google_token');
        if (!$token)
            return false;
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($userid);
        if (!$client)
            return false;
        
        /* Verify the token */
        try {
            // $client->verifyIdToken($token);
            $client->verifyIdToken();
            return true;
        } catch (Exception $e) {
            WaseMsg::logMsg("Google test connect error trying to verifyIdToken: " . $e->getMessage());
            return false;
        }
        
    }
    
    
    /**
     * This function inserts an appointment into a Google calendar.
     *
     *
     * @static
     *
     * @param WaseAppointment $app
     *            the target appointment.
     *            
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @param string $userid
     *            the userid of the Google calendar owner.
     *            
     * @return bool true|false able/unable to add the appointment
     */
    static function addApp($app, $block, $userid)
    {
        
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        
        
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($userid, 'google_calendarid');
        
        /* Abort if we don't have both a calendar id  */
        If (! $calid) {
            return false;
        }
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($app);
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($userid);
        if (!$client)
            return false;
        
        /* Create the Google event */
        $event = new Google_Service_Calendar_Event(); // note in the API examples it calls Event().
                                                      // $event = new Google_Event();
        $event->setSummary(WaseUtil::getParm('SYSID') . ' Appointment for ' . $app->userid . ' (' . $app->name . ') with ' . $block->userid . ' (' . $block->name . ')');
        // Build the body.
        $body = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this appointment, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.';
        if ($app->purpose)
            $body = $app->purpose . ' ' . $body;
        $event->setDescription($body);
        $event->setLocation($block->location);
        $start = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                              // $start = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $start->setDateTime($dates . 'T' . $tstart . '.00Z');
        $event->setStart($start);
        $end = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                            // $end = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $end->setDateTime($datee . 'T' . $tend . '.00Z');
        $event->setEnd($end);
        
        /*  We bypass attendee setting for now. 
        $attendee1 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee1 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        $attendee1->setEmail($app->email); // make sure you actually put an email address in here
        $attendee2 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee2 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        $attendee2->setEmail($block->email); // make sure you actually put an email address in here
        if ($block->email != $app->email)
            $attendees = array(
                $attendee1,
                $attendee2
            );
        else
            $attendees = array(
                $attendee1
            );
        $event->attendees = $attendees;
        */
        
        $optParams = array(
            'sendNotifications' => false
        );
        
        // Create the Event
        $worked = true;
        
        try {
            $createdEvent = $googlecal->events->insert($calid, $event, $optParams);
        } catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to insert event for calendar $calid app $app->appointmentid : " . $e->getMessage() . ': credentials = ' . WasePrefs::getPref($userid, 'google_token'));
            return false;
        }
        if (is_object($createdEvent)) {
            if (method_exists($createdEvent, 'getId'))
                $app->gid = $createdEvent->getId();
            else
                $worked = false;
        } elseif (is_array($createdEvent))
            $app->gid = $createdEvent['id'];
        else
            $worked = false;
        
        return $worked;
    }

    /**
     * This function updates an appointment in a Google calendar.
     *
     *
     * @static
     *
     * @param WaseAppointment $app
     *            the target appointment.
     *            
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @param string $userid
     *            the userid of the Google calendar owner.
     *            
     * @return bool true|false able/unable to update the appointment
     */
    static function changeApp($app, $block, $userid)
    {
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($userid, 'google_calendarid');
        
        /* Abort if we don't have both a calendar id  */
        If (! $calid) {
            return false;
        }
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($app);
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($userid);
        if (!$client)
            return false;
        
        /* Create the Google event */
        $event = new Google_Service_Calendar_Event(); // note in the API examples it calls Event().
                                                      // $event = new Google_Event();
        $event->setSummary(WaseUtil::getParm('SYSID') . ' Appointment for ' . $app->userid . ' (' . $app->name . ') with ' . $block->userid . ' (' . $block->name . ')');
        // Build the body.
        $body = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this appointment, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.';
        if ($app->purpose)
            $body = $app->purpose . ' ' . $body;
        $event->setDescription($body);
        $event->setLocation($block->location);
        $start = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                              // $start = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $start->setDateTime($dates . 'T' . $tstart . '.00Z');
        $event->setStart($start);
        $end = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                            // $end = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $end->setDateTime($datee . 'T' . $tend . '.00Z');
        $event->setEnd($end);
        $attendee1 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee1 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        // Extract first email address, if multiple specified
        list($firstappemail, $rest) = explode(',', $app->email);
        $attendee1->setEmail($firstappemail); // make sure you actually put an email address in here

        $attendee2 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee2 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        // Extract first email address, if multiple specified
        list($firstblockemail, $rest) = explode(',', $block->email);
        $attendee2->setEmail($firstblockemail); // make sure you actually put an email address in here
        if ($firstblockemail != $firstappemail)
            $attendees = array(
                $attendee1,
                $attendee2
            );
        else
            $attendees = array(
                $attendee1
            );
        $event->attendees = $attendees;
        
        // Create the Event
        $worked = true;
        try {
            $updatedEvent = $googlecal->events->update($calid, $app->gid, $event);
        } catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to update event for calendar $calid app $app->appointmentid : " . $e->getMessage() . ': credentials = ' . WasePrefs::getPref($userid, 'google_token'));
            $worked = false;
        }
        
        return $worked;
    }

    /**
     * This function deletes an appointment or block from a Google calendar.
     *
     *
     * @static
     *
     * @param
     *            WaseAppointment || WaseBlock $obj
     *            the target appointment or block.
     *            
     *            
     * @return bool true|false able/unable to delete the appointment or block
     */
    static function delObj($obj, $userid)
    {
        
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($userid, 'google_calendarid');
        
        /* Abort if we don't have both a calendar id and an event id */
        If (! $calid || ! $obj->gid) {
            return false;
        }
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($userid);
        if (!$client)
            return false;
        
        /* Now delete the event */
        $worked = true;
        try {
            $googlecal->events->delete($calid, $obj->gid);
            $obj->gid = '';
        } catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to delete object for calendar $calid object " . print_r($obj, true) . ": " . $e->getMessage() . ': credentials = ' . WasePrefs::getPref($userid, 'google_token'));
            $worked = false;
        }
        
        return $worked;
    }

    /**
     * This function adds a WaseBlock as an event to a Google calendar.
     *
     * @static
     *
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @return bool true|false able/unable to add the block
     */
    static function addBlock($block)
    {
        
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $block->calendarid
        ));
        
        
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($block->userid, 'google_calendarid');
        
        /* Abort if we don't have both a calendar id  */
        If (! $calid) {
            return false;
        }
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($block);
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($block->userid);
        if (!$client)
            return false;
        
        /* Create the Google event */
        $event = new Google_Service_Calendar_Event(); // note in the API examples it calls Event().
                                                      // $event = new Google_Event();
        if ($block->title)
            $event->setSummary($block->title);
        else 
            $event->setSummary(WaseUtil::getParm('SYSID') . ' Appointment Block');
        
        $event->setLocation($block->location);
        
        // Build the body.
        $body = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this appointment, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.';
        $event->setDescription($body);
        
        $start = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                              // $start = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $start->setDateTime($dates . 'T' . $tstart . '.00Z');
        $event->setStart($start);
        $end = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                            // $end = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $end->setDateTime($datee . 'T' . $tend . '.00Z');
        $event->setEnd($end);
        $attendee1 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee1 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        // Extract first email address, if multiple specified
        list($firstblockemail, $rest) = explode(',', $block->email);
        $attendee1->setEmail($firstblockemail); // make sure you actually put an email address in here
        $attendees = array(
            $attendee1
        );
        $event->attendees = $attendees;
        
        $optParams = array(
            'sendNotifications' => false
        );
        
        // Create the Event
        $worked = true;
        try {
            $createdEvent = $googlecal->events->insert($calid, $event, $optParams);
        } catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to insert block for calendar $calid block $block->blockid : " . $e->getMessage() . ': credentials = ' . WasePrefs::getPref($block->userid, 'google_token'));
            return false;
        }
        if (is_object($createdEvent)) {
            if (method_exists($createdEvent, 'getId'))
                $block->gid = $createdEvent->getId();
            else
                $worked = false;
        } elseif (is_array($createdEvent))
            $block->gid = $createdEvent['id']; 
        else
            $worked = false;
        
        return $worked;
    }

    /**
     * This function updates a block in a Google calendar.
     *
     *
     * @static
     *
     *
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @return bool true|false able/unable to update the block
     */
    static function changeBlock($block)
    {
        
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $block->calendarid
        ));
        
       
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($block->userid, 'google_calendarid');
        
        /* Abort if we don't have both a calendar id and an access token */
        If (! $calid) {
            return false;
        }
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($block);
        
        /* Build our Google client and calendar. */
        list ($client, $googlecal) = self::buildClientandCal($block->userid);
        if (!$client)
            return false;
        
        /* Create the Google event */
        $event = new Google_Service_Calendar_Event(); // note in the API examples it calls Event().
                                                      // $event = new Google_Event();
        $event->setSummary(WaseUtil::getParm('SYSID') . ' Appointment Block');
        $event->setLocation($block->location);
        $start = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                              // $start = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $start->setDateTime($dates . 'T' . $tstart . '.00Z');
        $event->setStart($start);
        $end = new Google_Service_Calendar_EventDateTime(); // note in the API examples it calls EventDateTime().
                                                            // $end = new Google_EventDateTime(); // note in the API examples it calls EventDateTime().
        $end->setDateTime($datee . 'T' . $tend . '.00Z');
        $event->setEnd($end);
        $attendee1 = new Google_Service_Calendar_EventAttendee(); // note in the API examples it calls EventAttendee().
                                                                  // $attendee1 = new Google_EventAttendee(); // note in the API examples it calls EventAttendee().
        // Extract first email address, if multiple specified
        list($firstblockemail, $rest) = explode(',', $block->email);
        $attendee1->setEmail($firstblockemail); // make sure you actually put an email address in here
        $attendees = array(
            $attendee1
        );
        $event->attendees = $attendees;
        
        $optParams = array(
            'sendNotifications' => false
        );
        
        // Create the Event
        $worked = true;
        try {
            $updatedEvent = $googlecal->events->update($calid, $block->gid, $event);
        } catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to update block for calendar $calid block $block->blockid : " . $e->getMessage() . ': credentials = ' . WasePrefs::getPref($block->userid, 'google_token'));
            $worked = false;
        }
        
        return $worked;
    }

    /**
     * This function saves a list of appointments to a Google calendar.
     *
     *
     * The passed parameters are used as selection criteria for building the list of appointments.
     *
     * @static
     *
     * @param string $userid
     *            that target userid (AppWith or AppFor).
     * @param string $startdate
     *            the start date.
     * @param string $enddate
     *            the end date.
     * @param string $starttime
     *            the start time (minimum).
     * @param string $endtime
     *            the end time (maximum).
     *            
     * @return null.
     *
     */
    static function listApps($userid, $startdate, $enddate, $starttime, $endtime)
    {
        
        /* Bulld the select criteria */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (userid=' . WaseSQL::sqlSafe($userid) . ' OR blockid in (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid=' . WaseSQL::sqlSafe($userid) . '))';
        
        if ($startdate)
            $select .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($startdate);
        
        if ($enddate)
            $select .= ' AND date(enddatetime) <=' . WaseSQL::sqlSafe($enddate);
        
        if ($starttime)
            $select .= ' AND time(startdatetime) >=' . WaseSQL::sqlSafe($starttime);
        
        if ($endtime)
            $select .= ' AND time(enddatetime) <=' . WaseSQL::sqlSafe($endtime);
        
        $select .= ' ORDER by startdatetime';
        
        /* Now get the waselist of appointments */
        $apps = new WaseList($select, 'Appointment');
        
        /* Now add in all of the appointments to the Google calendar */
        foreach ($apps as $app) {
            
            /* Read in the block */
            $block = new WaseBlock('load', array(
                "blockid" => $app->blockid
            ));
            
            /* Add the appointment */
            $gid = self::addApp($app, $block, $userid);
            /* Save appointment id */
            if ($gid && $gid != $app->gid) {
                $app->gid = $gid;
                WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment SET gid=' . WaseSQL::sqlSafe($app->gid) . ' WHERE appointmentid=' . $app->appointmentid);
            }
        }
        ;
        
        return null;
    }

    /**
     * This function creates a Google client and a Google calendar.
     *
     * @static @private
     *        
     * @param string $userid The
     *            userid of the user whose Google calendar is being managed.
     *            
     * @return array Google client and calendar.
     *        
     */
    static private function buildClientandCal($userid)
    {
        
        /* Get the user's calendar id */
        $calid = WasePrefs::getPref($userid, 'google_calendarid');
        /* Get the json_encoded user's access token */
        $token = WasePrefs::getPref($userid, 'google_token');
        $tokenobj = json_decode($token);
       

        if (!$calid || !$token)
            return false;
        
        /* Set up the google calendar objects */
        $client = new Google_Client();
        $client->setApplicationName("WASE");
        // Visit https://code.google.com/apis/console?api=calendar to generate your
        // client id, client secret, and to register your redirect uri.
        $client->setClientId(WaseUtil::getParm('GOOGLEID'));
        $client->setClientSecret(WaseUtil::getParm('GOOGLESECRET'));
        $client->setDeveloperKey(WaseUtil::getParm('GOOGLEKEY'));
        // $client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']);
        $client->setAccessType('offline');
        // Set the access scope
        // switch scope to edit/delete
        //$client->setScopes("https://www.googleapis.com/auth/calendar");
        $client->setScopes("https://www.googleapis.com/auth/calendar.events");

        /* Now create the calendar object. */
        // $cal = new Google_CalendarService($client);
        $cal = new Google_Service_Calendar($client);
        
        /* Set the access token */
        try {
            $client->setAccessToken($token);
        }
        catch (Exception $e) {
            WaseMsg::logMsg("Google sync error trying to setAccessToken : " . $e->getMessage());
            return false;
        }
        
        /* If expired, refresh */
        if ($client->isAccessTokenExpired()) {
            try {
                $client->setAccessType('offline');
                $client->refreshToken($tokenobj->refresh_token);
            } catch (Exception $e) {
                WaseMsg::logMsg("Google sync error trying to refreshToken : " . $e->getMessage());
                return false;
            }
            try {
                $client->setAccessType('offline');
                $token = self::jsoniffy($client->getAccessToken());
                WasePrefs::savePrefRaw($userid, 'google_token', $token);
            } catch (Exception $e) {
                WaseMsg::logMsg("Google sync error trying to getAccessToken : " . $e->getMessage());
                return false;
            }
        }
        return array(
            $client,
            $cal
        );
    }
    
    /**
     * This function revokes a user's credentials for Google access (to force the user to go back through the authorization).
     * 
     * @param string $userid 
     *          userid of the user whose credentials are to be revoked.
     *          
     * @return boolean
     *          true if revoked, false if an error.
     */
    static function revoke($userid) {
        // First, get the client 
        list ($client, $googlecal) = self::buildClientandCal($userid);
        if (!$client)
            return false;
        // Now revoke
        try {
            $client->revokeToken();
        } catch (Exception $e) {
            WaseMsg::logMsg("Google error trying to revoke access token : " . $e->getMessage());
            return false;
        }
        return true;       
    }

    /**
     * This internal function computes start/end dates and times for an appointment or block
     *
     * @static @private
     *        
     * @param WaseAppointment $obj
     *            the appointment or block.
     *            
     * @return array start date, start time, end date, end time.
     */
    static private function datetimes($obj)
    {
        return array(
            gmdate('Y-m-d', mktime(substr($obj->startdatetime, 11, 2), substr($obj->startdatetime, 14, 2), 0, substr($obj->startdatetime, 5, 2), substr($obj->startdatetime, 8, 2), substr($obj->startdatetime, 0, 4))),
            gmdate('Y-m-d', mktime(substr($obj->enddatetime, 11, 2), substr($obj->enddatetime, 14, 2), 0, substr($obj->enddatetime, 5, 2), substr($obj->enddatetime, 8, 2), substr($obj->enddatetime, 0, 4))),
            gmdate('H:i:s', mktime(substr($obj->startdatetime, 11, 2), substr($obj->startdatetime, 14, 2), 0, substr($obj->startdatetime, 5, 2), substr($obj->startdatetime, 8, 2), substr($obj->startdatetime, 0, 4))),
            gmdate('H:i:s', mktime(substr($obj->enddatetime, 11, 2), substr($obj->enddatetime, 14, 2), 0, substr($obj->enddatetime, 5, 2), substr($obj->enddatetime, 8, 2), substr($obj->enddatetime, 0, 4)))
        );
    }
    
    /**
     * This internal function returns a google token in json format.
     *
     * @static @private
     *
     * @param string $token 
     *          the token
     *
     * @return string the token in json format.
     */
    // This function 
    static private function jsoniffy($token) {
        if (is_string($token))
            return $token;
        if (!is_array($token))
            $token = (array) $token;
        return json_encode($token);
    }
}
?>