<?php

/**
 * This class contains functions static and object functions to handle managers.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseManager
{
    
    /* Properties */
    
    /**
     *
     * @var int $calendarid Calendarid of calendar being managed.
     */
    public $calendarid;

    /**
     *
     * @var string $userid Userid of manager.
     */
    public $userid;

    /**
     *
     * @var string $status Status of entry ('active' or 'pending').
     */
    public $status;

    /**
     *
     * @var bool $notify Manager wants notifications.
     */
    public $notify;

    /**
     *
     * @var bool $remind Manager wants reminders.
     */
    public $remind;
    
    
    /* Static (class) methods */
    
    /**
     * Look up a manager in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Userid of manager.
     *            
     * @return array|false of manager record values if found, else false.
     */
    static function find($calendarid, $userid)
    {
        
        /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid)))
            return false;
        else
            /* Return the entry as an associative array (there can only be 1). */
            return WaseSQL::doFetch($entry);
    }

    /**
     * Does user manage calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Userid of manager.
     * @param string $status
     *            Optionally, status of manager.
     *            
     * @return bool true if yes, else false.
     */
    static function isManager($calendarid, $userid, $status = '')
    {
        /* Set up WHERE clause as per arguments */
        $where = '(calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid);
        if ($status)
            $where .= ' AND status=' . WaseSQL::sqlSafe($status);
        $where .= ')';
        
        $result = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE ' . $where));
        
        if ($result)
            return true;
        else
            return false;
    }

    /**
     * Remove all managers from a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return void
     */
    static function removeAllManagers($calendarid)
    {
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE calendarid=' . WaseSQL::sqlSafe($calendarid));
    }

    /**
     * Who manages a calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array Associative array of WaseManager database ids of managers for this calendar.
     */
    static function arrayActiveManagers($calendarid)
    {
        return self::arrayManagedids($calendarid, 'active');
    }

    /**
     * Who manages a calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return string List of WaseManager database ids of managers for this calendar.
     */
    static function listActiveManagers($calendarid)
    {
        return implode(',', self::arrayActiveManagers($calendarid));
    }

    /**
     * Who actively manages a calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseManagers who actively manage a given calendar.
     */
    static function wlistActiveManagers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE status="active" AND calendarid=' . WaseSQL::sqlSafe($calendarid), 'Manager');
    }

    /**
     * Who manages a calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseManagers who manage a given calendar.
     */
    static function wlistAllManagers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE calendarid=' . WaseSQL::sqlSafe($calendarid), 'Manager');
    }

    /**
     * Who manages a calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseManagers who manage a given calendar.
     */
    static function wListManagers($calendarid)
    {
        return self::wlistAllManagers($calendarid);
    }

    /**
     * This function returns an array of all pending managers for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array of ids of pending managers.
     */
    static function arrayPendingManagers($calendarid)
    {
        return self::arrayManagedids($calendarid, 'pending');
    }

    /**
     * This function returns a list of all pending managers for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return string of ids of pending managers.
     */
    static function listPendingManagers($calendarid)
    {
        return implode(',', self::arrayPendingManagers($calendarid));
    }

    /**
     * This function returns a WaseList of all pending managers for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList of pending managers.
     */
    static function wlistPendingManagers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE status="pending" AND calendarid=' . WaseSQL::sqlSafe($calendarid), 'Manager');
    }

    /**
     * This function returns an array of all managers for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array of ids of pending managers.
     */
    static function arrayAllManagers($calendarid)
    {
        return self::arrayManagedids($calendarid, '');
    }

    /**
     * This function returns an array of calendarids of all calendars managed by a given user.
     *
     * @static
     *
     * @param string $userid
     *            Target userid.
     * @param string $status
     *            Optional status oif manager.
     *            
     * @return array of ids of calendarids.
     */
    static function arrayManagedids($userid, $status='')
    {
        /* Init result array */
        $calendarids = array();
        
        /* Determine if restricting by status */
        if ($status)
            $wherestat = ' status=' . WaseSQL::sqlSafe($status) . ' AND ';
        else
            $wherestat = '';
            
            /* Get matching entries from the database */
        $allcalendars = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE ' . $wherestat . ' userid=' . WaseSQL::sqlSafe($userid));
        
        /* Now append the calendar ids to the array */
        while ($calendar = WaseSQL::doFetch($allcalendars)) {
            if (! in_array($calendar['calendarid'], $calendarids))
                $calendarids[] = $calendar['calendarid'];
        }
        /* Return the resulting list of calendarids as an array */
        return $calendarids;
    }

    /**
     * This function returns a list of calendarids of all calendars managed by a given user.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Target userid.
     *            
     * @return string of ids of calendarids.
     */
    static function listManagedids($userid, $status = '')
    {
        $calarray = self::arrayManagedids($userid, $status);
        if (count($calarray) > 0)
            return implode(",", $calarray);
        else
            return '';
    }

    /**
     * This function returns a WaseList of all calendars managed by a given user.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Target userid.
     *            
     * @return WaseList of calendars.
     */
    static function wlistManaged($userid, $status = '')
    {
        /* Build and return the list. */
        if ($calendarids = self::listManagedids($userid, $status))
            return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE calendarid IN (' . $calendarids . ')', 'Calendar');
        else
            return '';
    }
    
    /* Object Methods */
    
    /**
     * Construct a manager.
     *
     * We have two constructors, one for a new manager, one for an
     * existing manager. In the former case, we create an entry in the
     * database manager table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database manager table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * manager object.
     *
     * @param string $source
     *            'create','load' or 'update'
     * @param array $data
     *            an associative array some or all of the manager property values.
     *            
     * @return WaseManager the contructed, loaded or updated manager object.
     */
    function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create' a manager.
         * $data is an associative array of elements used when we need to create/update
         * a manager; for 'load', it contains just the calendarid and the userid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        
        /*
         * For load and update, find the manager and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            /* If the object doesn't exist, blow up. */
            if (! $values = WaseManager::find($ndata['calendarid'], $ndata['userid']))
                throw new Exception('Manager ' . $ndata['userid'] . ' for calendar ' . $ndata['calendarid'] . ' does not exist', 14);
                /* Load the database data into the object. */
            WaseUtil::loadObject($this, $values);
        }
        
        /* Set defaults for unspecified values */
        if ($source == 'create') {
            if (! array_key_exists('remind', $ndata))
                $ndata['remind'] = 1;
            if (! array_key_exists('notify', $ndata))
                $ndata['notify'] = 1;
            if (! array_key_exists('status', $ndata))
                $ndata['status'] = 'pending';
        }
        
        /* For update, disallow resetting of calendarid or userid or status to pending */
        if ($source == 'update') {
            if (($ndata['calendarid']) && ($ndata['calendarid'] != $this->calendarid))
                throw new Exception('Cannot re-assign managers', 15);
            if (($ndata['userid']) && ($ndata['userid'] != $this->userid))
                throw new Exception('Cannot re-assign managers', 15);
            if ($ndata['status'] && ($ndata['status'] == 'pending') && $this->status == 'active')
                throw new Exception('Cannot change manager status from active to pending', 15);
        }
        
        /* For update and create, load up the values and validate the appointment. */
        if (($source == 'update') || ($source == 'create')) {
            /* For create, reject attempt to add an existing manager */
            if (($source == 'create') && ($values = WaseManager::find($ndata['calendarid'], $ndata['userid'])))
                throw new Exception('Manager ' . $ndata['userid'] . ' for calendar ' . $ndata['calendarid'] . ' already exists', 14);
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate())
                throw new Exception($errors, 15);
        }
    }

    /**
     * Save this manager.
     *
     * This function writes the manager data out to the database and sends out notifications.
     *
     * The first argument specifies whether this is a new manager or not.
     *
     * @param string $type
     *            'create','load', 'update' or 'remind'
     * @param string $optext
     *            optional text for notifications.
     *            
     * @return int manager id (database id) of created/saved/updated manager.
     *        
     */
    function save($type, $optext = "")
    {
        
        // Read in the calendar
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            
            
            /* Reject attempt to save a manager if already a member of the calendar */    
            
            if ($calendar->isMember($this->userid))
                throw new Exception(WaseMsg::getMsg(41, array()), 41);
            
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                
                if ($varlist)
                    $varlist .= ',';
                $varlist .= '`' . $key . '`';
                if ($vallist)
                    $vallist .= ',';
                $vallist .= WaseSQL::sqlSafe($value);
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseManager (' . $varlist . ') VALUES (' . $vallist . ')';
        }         /* Else create the update list */
        else {
            
            /* Before we update the entry, we need to see if we are changing a pending enrty to an active entry */
            $oldentry = self::find($this->calendarid, $this->userid);
            if ($this->status == 'active' && $oldentry['status'] != 'active')
                $activated = true;
            else
                $activated = false;
                
                /* Now build the SQL to update the entry */
            $sql = '';
            foreach ($this as $key => $value) {
                
                if ($sql)
                    $sql .= ', ';
                $sql .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
            }
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseManager SET ' . $sql . ' WHERE calendarid=' . $this->calendarid . ' AND userid=' . WaseSQL::sqlSafe($this->userid);
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);


        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();
        
        /* Set up email */
        $subject = 'Change in ' . WaseUtil::getParm('SYSNAME') . ' manager status.';
        /* Set up default to/from fields */
        $to = $directory->getEmail($this->userid);
        
        /* If updating status */
        if ($type == 'update' && $activated) {
            $body = 'You have been added as manager for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').';
            // Add in a pointer to the calendar.
            $body .= '<br /><br /><a href="' . WaseUtil::urlheader() . '/views/pages/viewcalendar.php?calid=' . $calendar->calendarid . '">Display calendar.</a>';
            $this->sendemail($to, $subject, $body);
        }
        
        /* If saving a new manager, send out a notification email. */
        if ($type == 'create') {
            
            
            
            /* Send emails to pending manager and calendar owner. */
            if ($this->status == 'pending') {
                /* Send email to the manager */
                $body = 'You have been added as a pending manager for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').' . '<p />You will be notified via email once the pending request has been processed.<p />';
                
                $this->sendemail($to, $subject, $body);                
                                
                /* Prepare enail for the calendar owner */
                $to = $directory->getEmail($calendar->userid);
                $subject = 'Request to manage your ' . WaseUtil::getParm('SYSNAME') . ' calendar.';

                $body = 'User ' . $this->userid . ' (' . $directory->getName($this->userid) . ') has requested permission to manage your calendar with title "' . $calendar->title . ' ".  To process this request (either allow or deny it), click the link below (or cut and paste the link into the address line of your web browser) and follow the displayed instructions.<br /><br />';
                
                if (trim($optext))
                    $body .= 'The requestor has provided the following text: <br /><br />' . WaseUtil::safeHTML(trim($optext)) . '<br /><br />';
                
                $body .= 'Note:  a calendar manager is someone who can act on your behalf with respect to the calendar, including adding, deleting, and editing blocks or appointments on the calendar<br /><br />';
                
                $body .= '<a href="' . WaseUtil::urlheader() . '/views/pages/calendars.php">Allow or Deny Manager Request</a><br /><br />';
            } else
                $body = 'You have been added as a manager for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').';
                $body .= '<br /><br /><a href="' . WaseUtil::urlheader() . '/views/pages/viewcalendar.php?calid=' . $this->calendarid . '">View Calendar</a><br /><br />';
                
            /* Complete the body and send out the email */
            $body .= '<p>' . WaseUtil::getParm('SYSNAME') . '</p>';
            
            /* Send out the email */
            $this->sendemail($to, $subject, $body);
        }
    }

    /**
     * Validate manager data, pass back error string if any errors.
     *
     * @return string an error string, if any errors are found, else null.
     *        
     */
    function validate()
    {
        
        /* Validate manager data, pass back error string if any errors. */
        $errors = '';
        
        /* Read in the calendar */
        if (! ($calendar = WaseCalendar::find($this->calendarid)))
            $errors .= 'Invalid calendarid: ' . $this->calendarid . ';';
            
            /* remind */
        if (! WaseUtil::checkBool($this->remind))
            $errors .= 'remind must be a boolean; ';
        else
            $this->remind = WaseUtil::makeBool($this->remind);
            
            /* notify */
        if (! WaseUtil::checkBool($this->notify))
            $errors .= 'notify must be a boolean;';
        else
            $this->notify = WaseUtil::makeBool($this->notify);
            
            /* status */
        if ($this->status != 'pending' && $this->status != 'active')
            $errors .= 'status must be "pending" or "active"';
        
        return $errors;
    }

    /**
     * Remove this manager from a calendar.
     *
     * @param string $canceltext
     *            Optional text for notification.
     *            
     * @return void
     */
    function delete($canceltext)
    {
        
        /* Delete the manager record. */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseManager WHERE calendarid=' . $this->calendarid . ' AND userid=' . WaseSQL::sqlSafe($this->userid) . ' LIMIT 1');

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Send out removal email */
        if (($canceltext != WaseConstants::DoNotSendCancellationNotice)) {
            /* Read in the calendard */
            $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            ));
            $this->sendemail($directory->getEmail($this->userid), 'Change in your ' . WaseUtil::getParm('SYSNAME') . ' manager status', 'You have been removed as a manager for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').<p>' . WaseUtil::getParm('SYSNAME') . '</p>');
        }
         
    }

    /**
     * Send email.
     *
     * @param string $to
     *            email to.
     * @param string $subject
     *            email subject.
     * @param string $body
     *            email body.
     *            
     * @return void
     */
    function sendemail($to, $subject, $body)
    {
        
        /* Set up msg headers */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $headers = "From: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Content-type: text/html\r\n" . "Errors-To: $my_sysmail\r\n". "Return-Path: $my_sysmail\r\n";
        
        /* Send the email */
        WaseUtil::Mailer($to, $subject, $body, $headers);
    }
    

    /**
     * Return manager info as an XML stream.
     *
     *
     * @return string This manager record as an XML stream.
     */
    function xmlManagerInfo()
    {
        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        return '<user>' . '<userid>' . $this->userid . '</userid>' . '<name>' . $directory->getName($this->userid) . '</name>' . '<phone>' . $directory->getPhone($this->userid) . '</phone>' . '<email>' . $directory->getEmail($this->userid) . '</email>' . '</user>' . '<calendarid>' . $this->calendarid . '</calendarid>' . '<status>' . $this->status . '</status>' . '<notify>' . $this->notify . '</notify>' . '<remind>' . $this->remind . '</remind>';
    }
}
?>
