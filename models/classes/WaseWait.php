<?php
/**
 * This class describes an entry in the wase wait list table and contains methods to find, add and remove these entries.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseWait
{
     
    /* Properties */
    
    /**
     *
     * @var int $waitid MySQL id of wait record.
     */
    public $waitid;

    /**
     *
     * @var string $calendarid MySQL id of calendar record.
     */
    public $calendarid;

    /**
     *
     * @var string $userid Userid of waiting user..
     */
    public $userid;

    /**
     *
     * @var string $name Name of user.
     */
    public $name;

    /**
     *
     * @var string $email Email of user.
     */
    public $email;

    /**
     *
     * @var string $textemail Text msg email address of user.
     */
    public $textemail;

    /**
     *
     * @var string $phone Phone number of user.
     */
    public $phone;

    /**
     *
     * @var string $msg Text specified by the user for display to calendar owner.
     */
    public $msg;

    /**
     *
     * @var string $startdatetime Start date/time of wait period.
     */
    public $startdatetime;

    /**
     *
     * @var string $enddatetime End date/time of wait period.
     */
    public $enddatetime;

    /**
     *
     * @var string $whenadded Date/time when entry was added to waitlist.
     */
    public $whenadded;
    
    /* Static (class) methods */
    
    /**
     * Look up a wait entry in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $waitid
     *            Database id of calendar record.
     *            
     * @return array|false entry record values if found, else false.
     */
    static function find($id)
    {
        
        /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE waitid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Return the entry as an associative array (there can only be 1). */
        return WaseSQL::doFetch($entry);
    }

    /**
     * This method returns a WaseList of all wait list entries for a given user.
     *
     * @static
     *
     * @param string $userid
     *            Userid of waiter.
     *            
     * @return WaseList of wait record values.
     */
    public static function listWaitForUser($userid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE userid=' . WaseSQL::sqlSafe($userid), 'Waiter');
    }

    /**
     * This method returns a WaseList of all wait list entries for a given calendar
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     *            
     * @return WaseList of wait record values.
     */
    public static function listWaitForCalendar($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE calendarid=' . WaseSQL::sqlSafe($calendarid), 'Waiter');
    }

    /**
     * This method returns a WaseList of all wait list entries for a given calendar and user.
     *
     * @static
     *
     * @param string $userid
     *            Userid of waiter.
     * @param int $calendarid
     *            Id of calendar.
     *            
     * @return WaseList of wait record values.
     */
    public static function listWaitForUserAndCalendar($userid, $calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid), 'Waiter');
    }

    /**
     * This method returns a WaseList of all wait list entries for a given calendar and date/time range.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     * @param string $startdatetime
     *            Starting date/time.
     * @param string $enddatetime
     *            Ending date/time.
     *            
     * @return WaseList of wait record values.
     */
    public static function listWaitForCalendarAndWindow($calendarid, $startdatetime, $enddatetime)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND startdatetime <=' . WaseSQL::sqlSafe($enddatetime) . ' AND enddatetime >= ' . WaseSQL::sqlSafe($startdatetime), 'Waiter');
    }

    /**
     * This method notifies waiting users of availability.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     * @param int $blockid
     *            Blockid of block with available time.
     * @param string $startdatetime
     *            Starting date/time.
     * @param string $enddatetime
     *            Ending date/time.
     *            
     * @return void.
     */
    public static function notifyForCalendarAndWindow($calendarid, $blockid, $startdatetime, $enddatetime)
    {
        /* First, get list of matching wait list entries */
        $entries = self::listWaitForCalendarAndWindow($calendarid, $startdatetime, $enddatetime);
        /* Load up the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $calendarid
        ));
        /* Load up the block */
        $block = new WaseBlock('load', array(
            'blockid' => $blockid
        ));


        /* Build mail headers */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $headers = "Content-Type: text/html; charset=UTF-8\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Errors-To: $my_sysmail\r\nReply-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
        /* Go through the entries and send out notifications */
        foreach ($entries as $entry) {
            /* Build the mail components */
            $subject = 'New ' . WaseUtil::getParm('SYSID') . ' ' . $block->APPTHING . ' slots for ' . $calendar->userid . ' (' . $calendar->name . ')';

            // Build the URL
            $urlheader = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
            if ($_SERVER['INSTITUTION'])
                $urlheader .= '/' . $_SERVER['INSTITUTION'];

            $body = 'New ' . $block->APPTHING . ' slots available for calendar with title "' . $calendar->title . '" owned by ' . $calendar->userid .
                ' (' . $calendar->name . ') between ' . WaseUtil::datetimeToUS($startdatetime) . ' and ' . WaseUtil::datetimeToUS($enddatetime) . '<br /><br />' .
                '<a href="' . 'https://' . $urlheader . '/views/pages/makeappt.php?calid=' . $calendar->calendarid . '&blockid=' . $block->blockid . '">Make appointment</a><br /><br />' .
                'Note: You have been removed from the waitlist for this calendar (you can add yourself back if you wish to be notified of future availability, using the URL above).<br /><br />' .
                WaseUtil::getParm('SYSNAME');
            $headers = "Content-Type: text/html; charset=UTF-8\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Errors-To: $my_sysmail\r\nReply-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
            if ($entry->email)
                $to = $entry->email;
            else {
                // Get our directory class
                $directory = WaseDirectoryFactory::getDirectory();
                $to = $directory->getEmail($entry->userid);
                /* Send the email */
            }
            WaseUtil::Mailer($to, $subject, $body, $headers);
        }
        /* Now let calendar owner know */
        // if ($calendar->email)
        // $to = $calendar->email;
        // else
        // $to = $directory->getEmail($calendar->userid);
        // $subject = 'Wait list users notified of ' . $block->APPTHING .  ' slots for your calendar with title "' . $calendar->title . '"';
        // $body = 'New ' . $block->APPTHING .  ' slot availability for your calendar with title "' . $calendar->title . '" has been sent to ' . $entries->entries() . ' user(s)<br /><br />' .
        // 'The ' . WaseUtil::getParm('SYSNAME');
    }

    /**
     * This method purges a calendar's waitlist entries.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     *            
     * @return void.
     */
    public static function purgeWaitList($calendarid) 
    {
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE calendarid = ' . WaseSQL::sqlSafe($calendarid));
    }

    
    /**
     * This method purges all wait list entries that end before now (pointless entries).
     *
     * @static
     *
     *
     * @return int number of entries purged.
     */
    public static function purgeWaitListAll() 
    {
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE enddatetime <= ' .  WaseSQL::sqlSafe(date('Y-m-d H:i:s')));
        return WaseSQL::affected_rows();
    }
    
     
    
    /**
     * This method purges a calendar's wait list of all entries whose entries are within a given date range.
     * It does NOT send out messages.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     * @param int $datetime
     *            Ending date/time.
     *            
     * @return void.
     */
    public static function purgeWaitListWindow($calendarid, $startdatetime, $enddatetime)
    {
        if (!$enddatetime)
            $datetime = date('Y-m-d H:i:s');
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE calendarid = ' . WaseSQL::sqlSafe($calendarid) . ' AND startdatetime <=' . WaseSQL::sqlSafe($enddatetime) . ' AND enddatetime >= ' . WaseSQL::sqlSafe($startdatetime));
    }

       
    
    /**
     * This method purges a calendar's wait list of all entries for a given user whose end datetime is less than the given datetime.
     * It does NOT send out messages.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     * @param string $userid
     *            Target userid.
     * @param int $datetime
     *            Ending date/time.
     *            
     * @return void.
     */
    public static function purgeWaitListUser($calendarid, $userid, $datetime)
    {
        if (! $datetime)
            $datetime = date('Y-m-d H:i:s');
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE userid = ' . WaseSQL::sqlSafe($userid) . ' AND calendarid = ' . WaseSQL::sqlSafe($calendarid) . ' AND enddatetime < ' . WaseSQL::sqlSafe($datetime));
    }

    /**
     * This method purges a calendar's wait list of all entries for a given user.
     * It does NOT send out messages.
     *
     * @static
     *
     * @param int $calendarid
     *            Id of calendar.
     * @param string $userid
     *            Target userid.
     *            
     * @return void.
     */
    public static function purgeWaitListUserAll($calendarid, $userid)
    {
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE userid = ' . WaseSQL::sqlSafe($userid) . ' AND calendarid = ' . WaseSQL::sqlSafe($calendarid));
    }
    
    // Object methods
    
    /**
     * Construct a wait list entry.
     *
     * We have two constructors, one for a new entry, one for an
     * existing entry. In the former case, we create an entry in the
     * database waitlist table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database waitlist table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * entry object.
     *
     * @param string $source
     *            'create','load' or 'update'
     * @param array $data
     *            an associative array some or all of the manager property values.
     *            
     * @return WaseManager the contructed, loaded or updated manager object.
     */
    public function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create' a wait list entry.
         * $data is an associative array of elements used when we need to create/update an entry; for 'load', it contains just the waitid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        
        /*
         * For load and update, find the wait entry and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            /* If the object doesn't exist, blow up. */
            if (! $values = WaseWait::find($ndata['waitid']))
                throw new Exception('Wait id ' . $ndata['waitid'] . ' does not exist', 14);
                /* Load the database data into the object. */
            WaseUtil::loadObject($this, $values);
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Set defaults for unspecified values */
        if ($source == 'create') {
            
            /* Set directory defaults for all unspecified values */
            if ($ndata['name'] == '')
                $ndata['name'] = $directory->getName($ndata['userid']);
            if ($ndata['phone'] == '')
                $ndata['phone'] = $directory->getPhone($ndata['userid']);
            if ($ndata['email'] == '')
                $ndata['email'] = $directory->getEmail($ndata['userid']);
            
            if ($ndata['startdatetime']) {
                list ($date, $time) = explode(' ', $ndata['startdatetime']);
                if ($date && ! $time)
                    $ndata['startdatetime'] .= ' 00:00:00';
            }
            
            if ($ndata['enddatetime']) {
                list ($date, $time) = explode(' ', $ndata['enddatetime']);
                if ($date && ! $time)
                    $ndata['enddatetime'] .= ' 11:59:59';
            }
        }
        
        /*
         * For update, disallow changes that violate business rules.
         */
        if ($source == 'update') {
            $errmsg = $this->updateCheck($ndata);
            if ($errmsg)
                throw new Exception($errmsg, 38);
        }
        
        /* For update and create, load up the values and validate the wait entry. */
        if (($source == 'update') || ($source == 'create')) {
            
            WaseUtil::loadObject($this, $ndata);
            
            if ($errors = $this->validate())
                throw new Exception($errors, 38);
        }
    }

    /**
     * Save this entry.
     *
     * This function writes the wait list entry data out to the database and sends out notifications.
     *
     * The first argument specifies whether this is a new entry or not.
     *
     * @param string $type
     *            'create','load', 'update' or 'remind'
     *            
     * @return int entry id (database id) of created/saved/updated entry.
     *        
     */
    function save($type)
    {
        
        /* First, check to make sure that all fields have been supplied */
        $err = '';
        if (! $this->calendarid)
            $err .= 'calendarid ';
        if (! $this->userid)
            $err .= 'userid ';
        if (! $this->startdatetime)
            $err .= 'startdatetime ';
        if (! $this->enddatetime)
            $err .= 'enddatetime ';
        if ($err) {
            $err = 'The following required field(s) have not been specified: ' . $err;
            throw new Exception($err, 14);
        }
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            
            /* First, make sure entry does not already exist */
            $allready = WaseWait::listWaitForUserAndCalendar($this->userid, $this->calendarid);
            if ($allready->entries())
                throw new Exception('Cannot create waitlist entry for user ' . $this->userid . ' on calendar ' . $this->calendarid . ': entry already exists', 14);
                
                /* Save the create datetime */
            $this->whenadded = date('Y-m-d H:i:s');
            
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify a waitid */
                if ($key != 'waitid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= '`' . $key . '`';
                    if ($vallist)
                        $vallist .= ',';
                    $vallist .= WaseSQL::sqlSafe($value);
                }
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseWait(' . $varlist . ') VALUES (' . $vallist . ')';
        }  /* Else create the update list */
        else {
            $sql = '';
            foreach ($this as $key => $value) {
                if ($sql)
                    $sql .= ', ';
                $sql .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
            }
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseWait SET ' . $sql . ' WHERE waitid=' . $this->waitid;
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Get the (new) id and notify calendar owner */
        if ($type == 'create') {
            $this->waitid = WaseSQL::insert_id();
            
            /* Load up the calendar */
            $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            ));
            if ($calendar->email)
                $to = $calendar->email;
            else
                $to = $directory->getEmail($calendar->userid);
                
            // Set up URL header for the email

            $urlheader = '<a href="' . 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
            if ($_SERVER['INSTITUTION']) 
                $urlheader .= '/'.$_SERVER['INSTITUTION'];
            
            /* If waitnotofy preference exists and is 0, do NOT send notice */
            if (!WasePrefs::getPref($calendar->userid, 'waitnotify') === 0) {
                /* Build the email */
                $my_sysmail = WaseUtil::getParm('SYSMAIL');
                $headers = "Content-Type: text/html; charset=UTF-8\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Errors-To: $my_sysmail\r\nReply-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
                $subject = 'User ' . $this->userid . ' (' . $this->name . ') added to the wait list for calendar "' . $calendar->title . '"';
                $body = 'User ' . $this->userid . ' (' . $this->name . ') has been added to the wait list for your calendar with title "' . $calendar->title . '" for the period ' . WaseUtil::datetimeToUS($this->startdatetime) . ' to ' . WaseUtil::datetimeToUS($this->enddatetime) . '<br /><br />' . $urlheader . '/views/pages/calendarconfig.php
				?calid=' . $calendar->calendarid . '">View wait list </a><br /><br />' . WaseUtil::getParm('SYSNAME');
                WaseUtil::Mailer($to, $subject, $body, $headers);
            }
        }
        
        return $this->waitid;
    }

    /**
     * Remove this entry from the waitlist.
     *
     * @param string $canceltext
     *            Optional text for notification.
     *            
     * @return void
     */
    function delete($canceltext)
    {
        /* Delete this entry */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseWait WHERE waitid=' . $this->waitid . ' LIMIT 1');
        
        /* Let the user know if requested */
        If ($canceltext) {
            // $calendar = new WaseCalendar('load',$this->calendarid);
            // $this->notify('You have been removed from the wait list for calendar with title ' . $calendar->title .', owned by ' . $calendar->userid . '(' . $calendar->name . ')');
            $this->notify($canceltext);
        }
    }

    /**
     * Validate wait list data, pass back error string if any errors.
     *
     * @return string an error string, if any errors are found, else null.
     *        
     */
    function validate()
    {
        /* Validate passed data, pass back error string if any errors. */
        $errors = '';
        
        /* userid */
        if (! $this->userid)
            $errors .= 'userid required; ';
            
            /* Read in the calendar */
        if (! ($calendar = WaseCalendar::find($this->calendarid)))
            $errors .= 'Invalid calendarid: ' . $this->calendarid . '; ';
            
            /* startdatetime and enddatetime */
        if (! $temp = WaseUtil::checkDateTime($this->startdatetime))
            $errors .= 'Invalid start date and time: ' . $this->startdatetime . '; ';
        else
            $this->startdatetime = $temp;
        if (! $temp = WaseUtil::checkDateTime($this->enddatetime))
            $errors .= 'Invalid end date and time: ' . $this->enddatetime . '; ';
        else
            $this->enddatetime = $temp;
            
            /* Return any detected errors */
        return $errors;
    }

    /**
     * Validate updates to this entry.
     *
     * This function checks to see if proposed changes to the entry violate any business rules.
     *
     * @param array $ndata
     *            associative array of proposed updates to the wait list entry.
     *            
     * @return string|null error message string.
     */
    function updateCheck($ndata)
    {
        if (array_key_exists('calendarid', $ndata) && ($ndata['calendarid'] != $this->calendarid))
            return 'Cannot assign wait entry to a new calendar';
    }

    /**
     * Send a notification to the wait list user.
     *
     *
     * @param string $text
     *            The notification.
     *            
     * @return void
     */
    function notify($text)
    {
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $headers = "Content-Type: text/html; charset=UTF-8\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Errors-To: $my_sysmail\r\nReply-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
        if ($this->email)
            WaseUtil::Mailer($this->email, 'Change in waitlist status', $text, $headers);
        if ($this->textemail)
            WaseUtil::Mailer($this->textemail, $text, $text, null, "-f$my_sysmail");
    }

    /**
     * Return entry information as an XML stream.
     *
     * @return string XML.
     */
    public function xmlWaitInfo()
    {
        return $this->tag('waitid') . $this->tag('calendarid') . $this->tag('userid') . $this->tag('name') . $this->tag('email') . $this->tag('textemail') . $this->tag('phone') . $this->tag('msg') . $this->tag('startdatetime') . $this->tag('enddatetime') . $this->tag('whenadded');
    }

    /**
     * Return an xml tag with the specified property name and value.
     *
     * @param string $property
     *            the tag property name.
     * @param string $val
     *            the tag value.
     *            
     * @return string XML.
     */
    private function tag($property, $val='')
    {
        if (func_num_args() == 2 )
            return '<' . $property . '>' .  $val . '</' . $property . '>';
        else
            return '<' . $property . '>' . htmlspecialchars($this->{$property},ENT_NOQUOTES)  . '</' . $property . '>';
    }
}
?>