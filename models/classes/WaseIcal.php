<?php

/**
 * This class implements some class (static) methods that are useful for dealing with iCal.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseIcal
{
    
    /* Icalendar parameters */
    const ICALHEADER = "BEGIN:VCALENDAR\r\nPRODID:-//Princeton University/Web Appointment Scheduling Engine//EN\r\nVERSION:2.0\r\n";

    const ICALTRAILER = "END:VCALENDAR\r\n";

    /**
     * This function generates an iCal stream for creating an appointment.
     *
     *
     * @static
     *
     * @param WaseAppointment $app
     *            the target appointment.
     *            
     * @param WaseBlock $block
     *            the owning WaseBlock.
     * @param string $target
     *            whether intended for AppWith or AppFor.
     *            
     * @return string the appointment as an iCal stream.
     */
    static function addApp($app, $block, $target = 'manager')
    {
        /* Compute start/end date/time */
        list($dtstart, $dtend, $dtstamp) = self::startend($app);
       
        /* Get the calendar */
        $cal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        
        /* Now build an iCalendar stream for the appointment */
        $ret = self::ICALHEADER;
        $ret .= "METHOD:PUBLISH\r\n";
        
        // Make sure we have a sequence.
        if (!$app->sequence)
            $app->sequence = 0;
        
        $ret .= "BEGIN:VEVENT\r\n";
        $ret .= 'ORGANIZER:MAILTO:' . $app->email . "\r\n";
        $ret .= $dtstart;
        $ret .= $dtend; 
        $ret .= $dtstamp;
        $ret .= "UID:" . $app->uid . "\r\n";
        $ret .= "CATEGORIES:APPOINTMENT\r\n";
        if ($target == 'owner')
            $ret .= "SUMMARY:Appointment with " . $app->name . " (" . $app->email . ").\r\n";
        elseif ($target == 'student')
            $ret .= "SUMMARY:Appointment with " . $cal->name . " (" . $cal->email . ").\r\n";
        else
            $ret .= "SUMMARY:Appointment for " . $app->name . " with " . $cal->name . "\r\n";
        $ret .= "LOCATION:" . self::nlIcal($block->location) . "\r\n";
        $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . self::nlIcal($app->purpose) . "\r\n";
        $ret .= "CLASS:PUBLIC\r\n";
        $ret .= "SEQUENCE:" . (int) $app->sequence . "\r\n";
        $ret .= "END:VEVENT\r\n";
        
        // End the stream.
        $ret .= self::ICALTRAILER;
 
        /* Return the icalendar stream */
        return $ret;
    }

    /**
     * This function generates an iCal stream for deleting an appointment.
     *
     *
     * @static
     *
     * @param WaseAppointment $app
     *            the target appointment.
     *            
     *            
     * @return string the appointment deletion as an iCal stream.
     */
    static function delApp($app)
    {
        /* Compute start/end date/time */
        list($dtstart, $dtend, $dtstamp) = self::startend($app);
       
        
        // Make sure we have a sequence.
        if (!$app->sequence)
            $app->sequence = 0;
        
        /* Now build an iCalendar stream for the appointment */
        $ret = self::ICALHEADER;
        $ret .= "METHOD:CANCEL\r\n";
        
        $ret .= "BEGIN:VEVENT\r\n";
        $ret .= "SEQUENCE:" . (int) $app->sequence . "\r\n";
        $ret .= "STATUS:CANCELLED\r\n";
        $ret .= 'ORGANIZER:MAILTO:' . $app->email . "\r\n";
        $ret .= $dtstart;
        $ret .= $dtend;
        $ret .= $dtstamp;
        $ret .= "UID:" . $app->uid . "\r\n";
        $ret .= "END:VEVENT\r\n";
        
        // End the stream.
        $ret .= self::ICALTRAILER;
        
        /* Return the icalendar stream */
        return $ret;
    }

    /**
     * This function formats a WaseBlock as an iCal stream.
     *
     * @static
     *
     * @param WaseBlock $bk
     *            the owning WaseBlock.
     * @return string the block as an iCal stream.
     */
    static function addBlock($bk)
    {        
        // Return the stream.
        return self::formatBlock($bk);
    }

    
    /**
     * This function formats a WaseBlock series as an iCal stream.
     *
     * @static
     *
     * @param WaseBlock $bk
     *            the first (or only) WaseBlock (first if part of a series).
     *            
     * @return string the blocks in the series as an iCal stream.
     */
    static function addSeries($bk)
    {
        
        // If not a series, just return the block
        if (!$bk->seriesid)
            return self::addBlock($bk);
        
        return self::formatSeries($bk,'add');
    }


    /**
     * This function deletes a WaseBlock as an iCal stream.
     *
     * @static
     *
     * @param WaseBlock $bk
     *            the WaseBlock to be deleted.
     *
     * @return string the block as an iCal stream.
     */
    static function deleteBlock($bk)
    {  
        return self::formatBlock($bk,'delete');
    }
    

    /**
     * This function deletes a WaseBlock series as an iCal stream.
     *
     * @static
     *
     * @param WaseBlock $bk
     *            the first (or only) WaseBlock (first if part of a series).
     *
     * @return string the blocks in the series as an iCal stream.
     */
    static function deleteSeries($bk)
    {
    
        // If not a series, just return the block
        if (!$bk->seriesid)
            return self::deleteBlock($bk);
    
        return self::formatSeries($bk,'delete');
    }
    
    
    /**
     * This function returns an entire calendar (blocks and appointments) as an iCal stream.
     *
     * @static
     *
     * @param int $calendarid
     *            the database id of the calendar.
     * @return string the calendar as an iCal stream.
     */
    static function allCalendar($calendarid)
    {
        /* Get the calendar */
        $cal = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
        
        /* Get list of all blocks sorted by date/time */
        $allblocks = WaseBlock::listOrderedBlocks(array(
            array(
                'calendarid,=,AND',
                $calendarid
            )
        ), 'ORDER BY startdatetime');
        
        /* Init the iCal stream. */
        $ret = self::ICALHEADER;
        // CALNAME causes Outlook to create multiple calendars.
        // $ret .= "X-WR-CALNAME:Office Hours\r\n";
        $ret .= "X-WR-CALNAME:" . WaseUtil::getParm('SYSID') . "\r\n";
        
        /* Start by adding all of the scheduled blocks and appointments */
        
        $ret .= "METHOD:PUBLISH\r\n";
        
        
        /* Go through all of the blocks */
        foreach ($allblocks as $block) {
            /* Add the block */
            $ret .= self::formatBlock($block);
            /* Get all of the appoinments for that block */
            $apps = WaseAppointment::listMatchingAppointments(array(
                'blockid' => $block->blockid
            ));
            /* Add the appointments */
            foreach ($apps as $app) {
                /* Compute start/end date/time */
                list($dtstart, $dtend, $dtstamp) = self::startend($app);
       
                // Make sure we have a sequence.
                if (!$app->sequence)
                    $app->sequence = 0;
                
                $ret .= "BEGIN:VEVENT\r\n";
                $ret .= 'ORGANIZER:MAILTO:' . $app->email . "\r\n";
                $ret .= $dtstart;
                $ret .= $dtend;
                $ret .= $dtstamp;
                $ret .= "UID:" . $app->uid . "\r\n";
                $ret .= "CATEGORIES:APPOINTMENT\r\n";
                $ret .= "SUMMARY:Appointment with " . $app->name . " (" . $app->email . ").\r\n";
                $ret .= "LOCATION:" . self::nlIcal($block->location) . "\r\n";
                $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . self::nlIcal($app->purpose) . "\r\n";
                $ret .= "CLASS:PUBLIC\r\n";
                $ret .= "SEQUENCE:" . (int) $app->sequence . "\r\n";
                $ret .= "END:VEVENT\r\n";
            }
        }
        
        /* End of the scheduled events and appointments. */
        $ret .= self::ICALTRAILER;
        
        /*
         * Now try to say something about deleted blocks and cancelled appointments
         * $deleted = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.waseDeleted WHERE calendarid=' . $calendarid);
         * $needheader = true;
         * if ($deleted) {
         * while ($del = WaseSQL::doFetch($deleted)) {
         * if ($needheader) {
         * $ret .= self::ICALHEADER;
         * $ret .= "METHOD:CANCEL\r\n";
         * $needheader = false;
         * }
         * $ret .= "BEGIN:VEVENT\r\n";
         * $ret .= "UID:" . $del['uid'] . "\r\n";
         * $sequence = $del['sequence']+1;
         * $ret .= "SEQUENCE:" . $sequence . "\r\n";
         * $ret .= "STATUS:CANCELLED\r\n";
         * $ret .= "DTSTART" . self::tzone()  . ":" . $del['dtstart'] . "\r\n";
         * $ret .= "DTSTAMP" . self::tzone() . ':' . date('Ymd\THis') . "\r\n";
         * $ret .= "END:VEVENT\r\n";
         * }
         * }
         *
         * if (!$needheader)
         * $ret .= self::ICALTRAILER;
         */
        
        /* Log the request */
        $last = WasePrefs::getPref($cal->userid, 'getCal');
        if ($last) {
            list ($lt, $rest) = explode(',', $last);
            $last = $rest . ', ';
        } else
            $last = '';
        WasePrefs::savePref($cal->userid, 'getCal', $last . date('m/d/Y H:i'));
        
        return $ret;
    }

    /**
     * This function returns list of appointments as an iCal stream.
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
     *            $param string $starttime the start time (minimum).
     *            $param string $endtime the end time (maximum).
     *            
     * @return string the appointments as iCal streams.
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
        
        /* Init the iCal stream. */
        $ret = self::ICALHEADER;
        // CALNAME causes Outlook to create multiple calendars.
        // $ret .= "X-WR-CALNAME:Office Hours\r\n";
        // $ret .= "X-WR-CALNAME:" . WaseUtil::getParm('SYSID') . "\r\n";
        $ret .= "METHOD:PUBLISH\r\n";
        
        /* Now add in all of the appointments to the iCal stream */
        foreach ($apps as $app) {
            
            /* Read in the block */
            $block = new WaseBlock('load', array(
                "blockid" => $app->blockid
            ));
            
            /* If the userid owns the app, then display the block owner info. (the "with" user), else display the app owner info (the "for" user). */
            if ($userid == $app->userid)
                $show = $block;
            else
                $show = $app;
                
            /* Compute start/end date/time */
            list($dtstart, $dtend, $dtstamp) = self::startend($app);
   
            // Make sure we have a sequence.
            if (!$app->sequence)
                $app->sequence = 0;
            
            $ret .= "BEGIN:VEVENT\r\n";
            $ret .= 'ORGANIZER:MAILTO:' . $show->email . "\r\n";
            $ret .= $dtstart;
            $ret .= $dtend;
            $ret .= $dtstamp;
            $ret .= "UID:" . $app->uid . "\r\n";
            $ret .= "CATEGORIES:APPOINTMENT\r\n";
            $ret .= "SUMMARY:Appointment with " . $show->name . " (" . $show->email . ")\r\n";
            $ret .= "LOCATION:" . self::nlIcal($block->location) . "\r\n";
            $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . self::nlIcal($app->purpose) . "\r\n";
            $ret .= "CLASS:PUBLIC\r\n";
            $ret .= "SEQUENCE:" . (int) $app->sequence . "\r\n";
            $ret .= "END:VEVENT\r\n";
        }
        
        /* End of the scheduled appointments. */
        $ret .= self::ICALTRAILER;
        
        /* Return the results */
        return $ret;
    }
    
    
     /**
     * This function formats a block as an iCal stream.
     *
     * @static
     *
     * @param WaseBlock $bk 
     *            
     * @return string the block as iCal streams.
     *        
     */
     static function formatBlock($bk, $type='add') {
         
         /* Compute start/end date/time */
         list($dtstart, $dtend, $dtstamp) = self::startend($bk);
       
         // Make sure we have a sequence.
         if (!$bk->sequence)
             $bk->sequence = 0;
         
         
         // Build the iCal stream.
         $ret = "BEGIN:VEVENT\r\n";
         $ret .= 'ORGANIZER:MAILTO:' . $bk->email . "\r\n";
         $ret .= 'ATTENDEE;RSVP=FALSE;PARTSTAT=ACCEPTED;ROLE=REQ-PARTICIPANT:MAILTO:' . $bk->email . "\r\n";
         $ret .= $dtstart;
         $ret .= $dtend;
         $ret .= $dtstamp;
         $ret .= "UID:" . $bk->uid . "\r\n";
         
         // If deleting one instance of a recurring event, must specify the recurrence-id
         if ($type == 'delete' && $bk->periodid)
             $ret .= 'RECURRENCE-ID:' . $dtstart . "\r\n";
         
         $ret .= "CATEGORIES:OFFICEHOURS\r\n";
         if ($bk->title)
             $ret .= "SUMMARY:" . $bk->title . "\r\n";
         else
             $ret .= "SUMMARY:" . $bk->NAMETHING . " Appointment Block\r\n";
         $ret .= "LOCATION:" . self::nlIcal($bk->location) . "\r\n";
         if ($bk->description)
             $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . $bk->description . "\r\n";
         else
             $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:".$bk->NAMETHING."\r\n";
         $ret .= "CLASS:PUBLIC\r\n";
         $ret .= "SEQUENCE:" . (int) $bk->sequence . "\r\n";
         if ($type == 'delete')
             $ret .= "STATUS:CANCELLED" . "\r\n";
         $ret .= "END:VEVENT\r\n";
         
         // Return the stream.
         return $ret;
     } 
     
     /**
      * This function formats a series as an iCal stream.
      *     
      * @static
      *
      * @param WaseBlock $bk
      *
      * @return string the block as iCal streams.
      *
      */
     static function formatSeries($bk, $type='add') {
          
         // Format the block dates.
          
         /* Compute start/end date/time */
         list($dtstart, $dtend, $dtstamp) = self::startend($bk);
       
         
         // Make sure we have a sequence.
         if (!$bk->sequence)
             $bk->sequence = 0;
          
         
         // Build the iCal stream.
         $ret = "BEGIN:VEVENT\r\n";
         $ret .= 'ORGANIZER:MAILTO:' . $bk->email . "\r\n";
         $ret .= 'ATTENDEE;RSVP=FALSE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED:MAILTO:' . $bk->email . "\r\n";
         $ret .= $dtstart;
         $ret .= $dtend;
         $ret .= $dtstamp;
         // Add in the rdate and exdates, if any
         if ($bk->periodid) {
             $period = new WasePeriod('load',array(
                 'periodid'=>$bk->periodid
             ));
             //if ($period->rdate)
             //   $ret .= $period->rdate;
             if ($period->rrule)
                 $ret .= $period->rrule;
             if ($period->exdate)
                $ret .= $period->exdate;          
         }

         $ret .= "UID:" . $bk->uid . "\r\n";
           
         
         $ret .= "CATEGORIES:OFFICEHOURS\r\n";
         if ($bk->title)
             $ret .= "SUMMARY:" . $bk->title . "\r\n";
         else
             $ret .= "SUMMARY:" . $bk->NAMETHING . "Appointment Block\r\n";
         $ret .= "LOCATION:" . self::nlIcal($bk->location) . "\r\n";
         if ($bk->description)
             $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . $bk->description . "\r\n";
         else
             $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:".$bk->NAMETHINGS."\r\n";
         $ret .= "CLASS:PUBLIC\r\n";
         $ret .= "SEQUENCE:" . (int) $bk->sequence . "\r\n";
         if ($type == 'delete')
             $ret .= "STATUS:CANCELLED" . "\r\n";
         $ret .= "END:VEVENT\r\n";
          
         // Return the stream.
         return $ret;
     }
     
    
     /**
      * This function generates an iCal rrule based on a WasePeriod and a WaseSeries
      *
      *
      * @static
      *
      * @param WasePeriod $period
      *            the WasePeriod whose rrule we will build.
      *
      * @return string the rrule, or null if an error.
      *
      */
     static function makeRrule($period) {
         
         if (!$period)
             return '';
         
         try {
             $series = new WaseSeries('load', array(
                 'seriesid'=>$period->seriesid
             ));
         } catch (Exception $e) {
           WaseMsg::logMsg('Unable to load WaseSeries from WasePeriod ' . $period->periodid . ': Error: ' . $e->getCode() . ', ' . $e->getMessage()); 
           return ''; 
         }
         
         // Format the 'until' date (end date of series).  We need to add 1 day for 'until'.
         $enddate = gmdate('Ymd',gmmktime(0,0,0,substr($series->enddate,5,2),substr($series->enddate,8,2),substr($series->enddate,0,4))+(24*60*60)) . 'T000000Z';
         
          
         // Now build rrule based on period recurrence.
         switch ($series->every) {            
             case 'daily':
                 return 'RRULE:FREQ=DAILY;UNTIL='.$enddate."\r\n";
                 break;
             case 'dailyweekdays':
                 return 'RRULE:FREQ=WEEKLY;WKST=SU;BYDAY=MO,TU,WE,TH,FR;UNTIL='.$enddate."\r\n";
                 break;
             case 'weekly':
                 return 'RRULE:FREQ=WEEKLY;BYDAY='.strtoupper(substr($period->dayofweek,0,2)).';UNTIL='.$enddate."\r\n";
                 break;
             case 'otherweekly':
                 return 'RRULE:FREQ=WEEKLY;BYDAY='.strtoupper(substr($period->dayofweek,0,2)).';INTERVAL=2;UNTIL='.$enddate."\r\n";
                 break;
             case 'monthlyday':
                 return 'RRULE:FREQ=MONTHLY;BYMONTHDAY='.$period->dayofmonth.';UNTIL='.$enddate."\r\n";
                 break;
             case 'monthlyweekday':
                 return 'RRULE:FREQ=MONTHLY;BYDAY='.$period->weekofmonth.strtoupper(substr($period->dayofweek,0,2)).';UNTIL='.$enddate."\r\n";
                 break;
             default:
                 return ''; 
                         
         }
         
     }
       

    /**
     * Return an iCal stream to a web browser.
     *
     *
     * @static
     *
     * @param string $ical
     *            the iCal stream.
     * @param string $method
     *            the desired method (publish).
     * @param string $filename
     *            the filename to use in the MIME header.
     *            
     *            
     * @return void
     */
    static function returnICAL($ical, $method = "PUBLISH", $filename = '')
    {   
        srand(time());
        if (! $filename) {
            $random = rand(1, 10000);
            $filename = "v" . $random . ".ics";
        }
        
        header("Content-Type: text/calendar;method=$method");        
        header("Cache-Control: private");       
        header("Content-Disposition: inline;filename=$filename"); 
        echo self::foldICAL($ical);    
    }

    /**
     * Send an iCal stream as an email.
     *
     *
     * @static
     *
     * @param string $to
     *            target email address.
     * @param string $subject
     *            email subject line.
     * @param string $ical
     *            the iCal stream.
     * @param string $method
     *            the desired method (publish).
     *            
     *            
     * @return bool true if email sent, else false.
     */
    static function mailICAL($to, $subject, $ical, $method = 'PUBLISH')
    {
        
        /* Setting the header part, this is important */
        // $headers = "From: " . WaseUtil::getParm('FROMMAIL') . "\r\n";
        // $headers .= "MIME-Version: 1.0\r\n";
        $headers = "Content-Type: text/calendar; method=" . $method . "; charset=" . '"UTF-8"' . "\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Content-Disposition: inline;filename=request.ics\r\n";
        
        /* Send the email */
        // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
        returnWaseUtil::Mailer($to, $subject, self::foldICAL($ical), $headers);
    }

    /**
     * Fold a line as per iCal rules.
     *
     *
     * @static
     *
     * @param string $input
     *            the iCal stream.
     *            
     *            
     * @return string the folded iCal stream.
     */
    static function foldICAL($input)
    {
        /* Init output */
        $output = '';
        /* Split lines into an array of CRLF-seperated strings */
        $lines = explode("\r\n", $input);
        /* Fold lines at 72 characters */
        foreach ($lines as $line) {
            while (strlen($line) > 75) {
                /* Don't split at a '\n' divider */
                if (substr($line, 74, 2) == '\n')
                    $splitat = 74;
                else
                    $splitat = 75;
                $before = substr($line, 0, $splitat);
                $line = substr($line, $splitat);
                $output .= $before . "\r\n" . " ";
            }
            if (strlen($line) > 0)
                $output .= $line;
            $output .= "\r\n";
        }
        return $output;
    }

    /**
     * This function converts CRLF or LF text into \n as required for iCal text fields.
     *
     *
     * @static
     *
     * @param string $input
     *            the iCal stream.
     *            
     *            
     * @return string the string-replaced iCal stream.
     */
    static function nlIcal($input)
    {
        /* First translate CRLF into '\n' */
        $output = str_replace("\r\n", '\n', $input);
        /* Next, translate bare LF into '\n' */
        $output = str_replace("\n", '\n', $output);
        /* Finally, translate any bare CR into '\n' */
        return str_replace("\r", '\n', $output);
    }
    

    /**
     * This function converts start/end times and dates to iCal format.
     *
     *
     * @static
     *
     * @param string $object
     *            the object that has start/end dates/times.
     *
     *
     * @return array the converted dates/times.
     */
    static function startend($object) {
        
        /* Compute start/end date/time */
        $dates = gmdate('Ymd', mktime(substr($object->startdatetime, 11, 2), substr($object->startdatetime, 14, 2), 0, substr($object->startdatetime, 5, 2), substr($object->startdatetime, 8, 2), substr($object->startdatetime, 0, 4)));
        $datee = gmdate('Ymd', mktime(substr($object->enddatetime, 11, 2), substr($object->enddatetime, 14, 2), 0, substr($object->enddatetime, 5, 2), substr($object->enddatetime, 8, 2), substr($object->enddatetime, 0, 4)));
        $tstart = gmdate('His', mktime(substr($object->startdatetime, 11, 2), substr($object->startdatetime, 14, 2), 0, substr($object->startdatetime, 5, 2), substr($object->startdatetime, 8, 2), substr($object->startdatetime, 0, 4)));
        $tend = gmdate('His', mktime(substr($object->enddatetime, 11, 2), substr($object->enddatetime, 14, 2), 0, substr($object->enddatetime, 5, 2), substr($object->enddatetime, 8, 2), substr($object->enddatetime, 0, 4)));      
    
        $dtstart = "DTSTART" . self::tzone() . ":" . $dates . "T" . $tstart . "Z\r\n";
        $dtend = "DTEND" . self::tzone()  . ":" . $datee . "T" . $tend . "Z\r\n";
        $dtstamp = "DTSTAMP" . self::tzone() . ':' . gmdate('Ymd\THis') . "Z\r\n";
        
        
        // $dates = substr($object->startdatetime, 0, 4) . substr($object->startdatetime, 5, 2) . substr($object->startdatetime, 8, 2);
        // $datee = substr($object->enddatetime, 0, 4) . substr($object->enddatetime, 5, 2) . substr($object->enddatetime, 8, 2);
        // $tstart = substr($object->startdatetime, 11, 2) . substr($object->startdatetime, 14, 2) .  '00';
        // $tend = substr($object->enddatetime, 11, 2) . substr($object->enddatetime, 14, 2) .  '00';
        
        // $dtstart = "DTSTART" . self::tzone() . ":" . $dates . "T" . $tstart . "\r\n";
        // $dtend = "DTEND" . self::tzone()  . ":" . $datee . "T" . $tend . "\r\n";
        // $dtstamp = "DTSTAMP" . self::tzone() . ':' . date('Ymd\THis') . "\r\n";
        
        return array($dtstart, $dtend, $dtstamp);
        
    }
    
    /**
     * This function returns the 'TZID' property that corresponds to the deault timezone.
     *
     *
     * @static
     *
     *
     *
     * @return string the TZID string.
     */
    static function tzone() {
        
        // We will use the TZID ical property, rather than converting to UTC.
         
        if ($tzone = WaseUtil::getParm('TIMEZONE'))
            $tzone = ';TZID='.$tzone;
        else
            $tzone = '';
        
        // return $tzone;
        
        //  A tzid value can only be specified with a VTIMEZONE specification.  
        return '';
    }

    /**
     * This function wraps an iCal stream in an iCal header and trailer.
     *
     *
     * @static
     *
     * @param string $input
     *            the iCal stream.
     *            
     *            
     * @return string the wrappedd iCal stream.
     */
    static function wrap($ical)
    {
        return self::ICALHEADER . $ical . self::ICALTRAILER;
    }
}
?>