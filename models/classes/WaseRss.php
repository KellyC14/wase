<?php

/**
 * This class implements some class (static) methods that are useful for generating with Rss feeds.
 * 
 * This is a longer description that can span
 * multiple lines.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseRss
{

    /**
     * Return appointments as an RSS XML stream
     *
     * @static
     *
     * @param string $userid
     *            Target userid.
     * @param string $startdate
     *            Target start date.
     * @param string $enddate
     *            Target end date.
     * @param string $starttime
     *            Target start time.
     * @param string $endtime
     *            Target end time.
     *            
     * @return string the RSS XML stream.
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
        
        // Get institution name, if any
        if ($ins = $_SERVER['INSTITUTION'])
            $ins = '/' . $ins;
        else 
            $ins = '';
        
        /* Init the Rss stream. */
        $ret = '<?xml version="1.0"?>';
        $ret .= '<rss version="2.0">';
        $ret .= '<channel>';
        $ret .= '<title>' . WaseUtil::getParm('SYSID') . '</title>';
        $ret .= '<link>https://' . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $ins . dirname($_SERVER['SCRIPT_NAME']) . '/../pages/makeappt.page.php</link>';
        $ret .= '<description>Pending Appointments</description>';
        
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
                
                /* Add the item */ 
            $ret .= '<item>';
            $ret .= '<title>' . $show->name . ' (' . $show->userid . ') at ' . WaseUtil::AmPm($app->starttime) . ' on ' . date('D', mktime(2, 0, 0, substr($app->date, 5, 2), substr($app->date, 8, 2), substr($app->date, 0, 4))) . ', ' . substr($app->date, 5, 2) . '/' . substr($app->date, 8, 2) . '/' . substr($app->date, 0, 4) . '</title>';
            $ret .= '<link>https://' . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $ins . dirname($_SERVER['SCRIPT_NAME']) . '/../pages/apptinfo.page.php?appt_id=' . $app->appointmentid . '</link>';
            $ret .= '<description>Location: ' . $block->location . '; Purpose: ' . $app->purpose . '</description>';
            $ret .= '</item>';
        }
        
        /* Add in a "Make Appointment' item */
        $ret .= '<item>';
        $ret .= '<title>Make an Appointment</title>';
        $ret .= '<link>https://' . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $ins . dirname($_SERVER['SCRIPT_NAME']) . '/../pages/makeappt.page.php</link>';
        $ret .= '<description>Make/Cancel Appointments</description>';
        $ret .= '</item>';
        
        /* End of the scheduled appointments. */
        $ret .= '</channel>';
        $ret .= '</rss></xml>';
        
        /* Return the results */
        return $ret;
    }

    /**
     * Return all of the blocks on a calendar as an RSS XML stream.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of target calendar.
     * @param string $startdate
     *            Optional starting date.
     *            
     * @return string the RSS XML stream.
     */
    static function listBlocks($calendarid, $startdate = '')
    {
        // Get institution name, if any
        if ($ins = $_SERVER['INSTITUTION'])
            $ins = '/' . $ins;
        else
            $ins = '';
        
        /* Get the calendar */
        $cal = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
        
        /* Get the list of matching blocks */
        $where = 'WHERE calendarid = ' . WaseSQL::sqlSafe($calendarid);
        if ($startdate)
            $where .= ' AND date(startdatetime) >= ' . WaseSQL::sqlDate($startdate);
        $blocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock ' . $where . ' ORDER by `startdatetime` DESC', 'Block');
        
        /* Output the RSS header */
        $out = '<?xml version="1.0" encoding="ISO-8859-1"?><rss version="2.0" xmlns:gd="http://schemas.google.com/g/2005">';
        
        /* Add in the calendar info */
        $out .= '<channel>' . '<title>' . $cal->title . '</title>' . '<link>' . 'https://' . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $ins . dirname($_SERVER['SCRIPT_NAME']) . '/../pages/viewcalendar.page.php?cal_id=' . $calendarid . '&amp;view=month';
        if ($startdate)
            $out .= '&amp;st_dt=' . $startdate;
        
        $out .= '</link>' . '<description>' . $cal->description . '</description>';
        
        /* Add in the block data */
        foreach ($blocks as $block) {
            
            $out .= '<item>' . '<title>' . $block->title . '</title>' . '<link>' . 'https://' . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $ins . dirname($_SERVER['SCRIPT_NAME']) . '/../pages/viewcalendar.page.php?block_id=' . $block->blockid . '</link>' . '<guid>' . $block->blockid . '</guid>' . '<description>' . $block->description . '</description>' . '<pubDate>' . date(DATE_RSS, mktime(substr($block->starttime, 0, 2), substr($block->starttime, 3, 2), '00', substr($block->date, 5, 2), substr($block->date, 8, 2), substr($block->date, 0, 4))) . '</pubDate>' . '<gd:where valuestring="' . $block->location . '"/>' . '<gd:when startTime="' . $block->date . ' ' . $block->starttime . '" endTime="' . $block->date . ' ' . $block->endtime . '"/>' . '</item>';
        }
        
        /* End the stream */
        $out .= '</channel></rss>';
        
        /* Return the stream */
        return $out;
    }
}
?>