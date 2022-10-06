<?php

/**
 * This class contains functions static and object functions to handle members.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseMember
{
    
    /* Properties */
    
    /**
     *
     * @var int $calendarid Calendarid of calendar being membered.
     */
    public $calendarid;

    /**
     *
     * @var string $userid Userid of member.
     */
    public $userid;

    /**
     *
     * @var string $status Status of entry ('active' or 'pending').
     */
    public $status;

    /**
     *
     * @var bool $notify Member wants notifications.
     */
    public $notify;

    /**
     *
     * @var bool $remind Member wants reminders.
     */
    public $remind;
    
   
    /* Static (class) methods */
    
    /**
     * Look up a member in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Userid of member.
     *            
     * @return array|false member record values if found, else false.
     */
    static function find($calendarid, $userid)
    {
        
        /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid)))
            return false;
            
            /* Return the entry as an associative array (there can only be 1). */
        return WaseSQL::doFetch($entry);
    }

    /**
     * Is user a member of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     * @param string $userid
     *            Userid of member.
     * @param string $status
     *            Optionally, status of member.
     *            
     * @return bool true if yes, else false.
     */
    static function isMember($calendarid, $userid, $status = '')
    {
        /* Set up WHERE clause as per arguments */
        $where = '(calendarid=' . WaseSQL::sqlSafe($calendarid) . ' AND userid=' . WaseSQL::sqlSafe($userid);
        if ($status)
            $where .= ' AND status=' . WaseSQL::sqlSafe($status);
        $where .= ')';
        
        $result = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE ' . $where));
        
        if ($result)
            return true;
        else
            return false;
    }

    /**
     * Remove all members from a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return void
     */
    static function removeAllMembers($calendarid)
    {
        // Get list of all members
        $members = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE calendarid=' . WaseSQL::sqlSafe($calendarid), 'Member');
        // Remove them
        foreach ($members as $member) {
            $member->delete('');    
        }
            
    }

    /**
     * Who is an active memebr of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array Associative array of WaseMember database ids of members for this calendar.
     */
    static function arrayActiveMembers($calendarid)
    {
        return self::arrayMemberedids($calendarid, 'active');
    }

    /**
     * Who is an active memebr of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return string list of WaseMember database ids of members for this calendar.
     */
    static function listActiveMembers($calendarid)
    {
        return implode(',', self::arrayActiveMembers($calendarid));
    }

    /**
     * Who is an active memebr of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseMembers who actively manage a given calendar.
     */
    static function wlistActiveMembers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE status="active" AND calendarid=' . WaseSQL::sqlSafe($calendarid), 'Member');
    }

    /**
     * Who is a memebr of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseMembers who manage a given calendar.
     */
    static function wlistAllMembers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE calendarid=' . WaseSQL::sqlSafe($calendarid), 'Member');
    }

    /**
     * Who is a memebr of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList List of WaseMembers who manage a given calendar.
     */
    static function wListMembers($calendarid)
    {
        return self::wlistAllMembers($calendarid);
    }

    /**
     * Who is a pending member of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array of ids of pending members.
     */
    static function arrayPendingMembers($calendarid)
    {
        return self::arrayMemberedids($calendarid, 'pending');
    }

    /**
     * Who is a pending member of this calendar?
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return string of ids of pending members.
     */
    static function listPendingMembers($calendarid)
    {
        return implode(',', self::arrayPendingMembers($calendarid));
    }

    /**
     * This function returns a WaseList of all pending members for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return WaseList of pending members.
     */
    static function wlistPendingMembers($calendarid)
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE status="pending" AND calendarid=' . WaseSQL::sqlSafe($calendarid), 'Member');
    }

    /**
     * This function returns an array of all members for a calendar.
     *
     * @static
     *
     * @param int $calendarid
     *            Database id of calendar record.
     *            
     * @return array of ids of pending members.
     */
    static function arrayAllMembers($calendarid)
    {
        return self::arrayMemberedids($calendarid, '');
    }

    /**
     * This function returns an array of calendarids of all calendars of which a given user is a member.
     *
     *
     * @static
     *
     * @param string $userid
     *            userid of target user.
     * @param string $status
     *            optionmaly, status of the user (pending or active).
     *            
     * @return array of ids of calendars.
     */
    static function arrayMemberedids($userid, $status = '')
    {
        /* Init result array */
        $calendarids = array();
        
        /* Determine if restricting by status */
        if ($status)
            $wherestat = ' status=' . WaseSQL::sqlSafe($status) . ' AND ';
        else
            $wherestat = '';
            
            /* Get matching entries from the database */
        $allcalendars = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE ' . $wherestat . ' userid=' . WaseSQL::sqlSafe($userid));
        
        /* Now append the calendar ids to the array */
        while ($calendar = WaseSQL::doFetch($allcalendars)) {
            if (! in_array($calendar['calendarid'], $calendarids))
                $calendarids[] = $calendar['calendarid'];
        }
        /* Return the resulting list of calendarids as an array */
        return $calendarids;
    }

    /**
     * This function returns a list of calendarids of all calendars of which a given user is a member.
     *
     * @static
     *
     * @param string $userid
     *            userid of target user.
     * @param string $status
     *            optionmaly, status of the user (pending or active).
     *            
     * @return string of ids of calendars.
     */
    static function listMemberedids($userid, $status = '')
    {
        $calarray = self::arrayMemberedids($userid, $status);
        if (count($calarray) > 0)
            return implode(",", $calarray);
        else
            return '';
    }

    /**
     * This function returns a WaseList of calendarids of all calendars of which a given user is a member.
     *
     * @static
     *
     * @param string $userid
     *            userid of target user.
     * @param string $status
     *            optionaly, status of the user (pending or active).
     *            
     * @return WaseList of ids of calendars.
     */
    static function wlistMembered($userid, $status='')
    {
        /* Build and return the list. */
        $calendarids = self::listMemberedids($userid, $status);
        if ($calendarids)
            return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE calendarid IN (' . $calendarids . ')', 'Calendar');
        else
            return '';
    }
    
    /* Object Methods */
    
    /**
     * Construct a member.
     *
     * We have two constructors, one for a new member, one for an
     * existing member. In the former case, we create an entry in the
     * database member table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database member table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * member object.
     *
     * @param string $source
     *            'create','load' or 'update'
     * @param array $data
     *            an associative array some or all of the member property values.
     *            
     * @return WaseMember the contructed, loaded or updated member object.
     */
    function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create a member.
         * $data is an associative array of elements used when we need to create/update
         * a member; for 'load', it contains just the calendarid and the userid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        
        /*
         * For load and update, find the member and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            /* If the object doesn't exist, blow up. */
            if (! $values = WaseMember::find($ndata['calendarid'], $ndata['userid']))
                throw new Exception('Member ' . $ndata['userid'] . ' for calendar ' . $ndata['calendarid'] . ' does not exist', 14);
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
        
        /* For update, disallow resetting of calendarid or userid or status from active to pending */
        if ($source == 'update') {
            if (($ndata['calendarid']) && ($ndata['calendarid'] != $this->calendarid))
                throw new Exception('Cannot re-assign members', 15);
            if (($ndata['userid']) && ($ndata['userid'] != $this->userid))
                throw new Exception('Cannot re-assign members', 15);
            if ($ndata['status'] && ($ndata['status'] == 'pending') && $this->status == 'active')
                throw new Exception('Cannot change member status from active to pending', 15);
        }
        
        
        /* For update and create, load up the values and validate the appointment. */
        if (($source == 'update') || ($source == 'create')) {
            /* For create, reject attempt to add an existing member */
            if (($source == 'create') && ($values = WaseMember::find($ndata['calendarid'], $ndata['userid'])))
                throw new Exception('Member ' . $ndata['userid'] . ' for calendar ' . $ndata['calendarid'] . ' already exists', 14);
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate())
                throw new Exception($errors, 15);
        }
    }

    /**
     * Save this member.
     *
     * This function writes the member data out to the database and sends out notifications.
     *
     * The first argument specifies whether this is a new or not.
     *
     * @param string $type
     *            'create','load', 'update' or 'remind'
     * @param string $optext
     *            optional text for notifications.
     *            
     * @return int member id (database id) of created/saved/updated member.
     *        
     */
    function save($type, $optext = "")
    {
        
        /* Read in the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        /* For create, disallow if member is already owner or manager of the target calendar */
        if ($type == 'create') {
            // If owner or manager, abort
            if ($calendar->isOwnerOrManager('user', $this->userid))
                throw new Exception(WaseMsg::getMsg(40, array()),40);
        }
        
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
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
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseMember (' . $varlist . ') VALUES (' . $vallist . ')';
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
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseMember SET ' . $sql . ' WHERE calendarid=' . $this->calendarid . ' AND userid=' . WaseSQL::sqlSafe($this->userid);
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);


        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Set up email */
        $subject = 'Change in ' . WaseUtil::getParm('SYSNAME') . ' member status.';
        /* Set up default to/from fields */
        $to = $directory->getEmail($this->userid);
        
        /* If updating status */
        if ($type == 'update' && $activated) {
            $body = 'You have been added as member for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').';
            // Add in a pointer to the calendar.
            $body .= '<br /><br /><a href="' . WaseUtil::urlheader() . '/views/pages/viewcalendar.php?calid=' . $calendar->calendarid . '">Display calendar.</a>';
            $this->sendemail($to, $subject, $body);
        }
        
        /* If saving a new member, send out a notification email. */
        if ($type == 'create') {
           
            
            /* Send emails to pending member and calendar owner. */
            if ($this->status == 'pending') {
                /* Send email to the member, unless it is an '@' member. */
                if (substr($this->userid,0,1) != '@') {
                    $body = 'You have been added as a pending member for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').' . '<p />You will be notified via email once the pending request has been processed.<p />';               
                    $this->sendemail($to, $subject, $body);
                }
                /* Prepare enail for the calendar owner */
                $to = $directory->getEmail($calendar->userid);
                $subject = 'Request to be a member of your ' . WaseUtil::getParm('SYSID') . ' calendar.';
                $body = 'User ' . $this->userid . ' (' . $directory->getName($this->userid) . ') has requested permission to be a member of your calendar with title "' . $calendar->title . ' ".  To process this request (either allow or deny it), click the link below (or cut and paste the link into the address line of your web browser) and follow the displayed instructions.<br /><br />';
               
                if (trim($optext))
                    $body .= 'The requestor has provided the following text: <br /><br />' . WaseUtil::safeHTML(trim($optext)) . '<br /><br />';
                
                $body .= 'Note:  a calendar member is someone who can schedule blocks of available time for themselves on this calendar (e.g., make themselves available for appointments).<br /><br />';
                
                $body .= '<a href="' . WaseUtil::urlheader() . '/views/pages/calendars.php">Allow or Deny Member Request</a><br /><br />';
            } elseif (substr($this->userid,0,1) != '@') {   // Don't send out email for @members.
                $body = 'You have been added as a member for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').';
                $body .= '<br /><br /><a href="' . WaseUtil::urlheader() . '/views/pages/viewcalendar.php?calid=' . $this->calendarid . '">View Calendar</a><br /><br />';
                
                /* Complete the body and send out the email */
                $body .= '<p>' . WaseUtil::getParm('SYSNAME') . '</p>';
            
                /* Send out the email */
                $this->sendemail($to, $subject, $body);
            }
        }
    }

    /**
     * Validate member data, pass back error string if any errors.
     *
     * @return string an error string, if any errors are found, else null.
     *        
     */
    function validate()
    {
        
        /* Validate member data, pass back error string if any errors. */
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
            $errors .= 'status must have a value of "pending" or "active", not "' . $this->status . '"';
        
        return $errors;
    }

    /**
     * Remove this member from a calendar.
     *
     * @param string $canceltext
     *            Optional text for notification.
     *            
     * @return void
     */
    function delete($canceltext)
    {
        
        /* Delete the member record. */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseMember WHERE calendarid=' . $this->calendarid . ' AND userid=' . WaseSQL::sqlSafe($this->userid) . ' LIMIT 1');
        
        // Cancel any future appointments with this member on this calendar
        
        // Get list of future blocks
        $blockslist = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = "' . $this->userid . '" AND calendarid = ' . $this->calendarid . '  AND startdatetime >= "' . date('Y-m-d H:i:s') . '"','Block');
        foreach ($blockslist as $block) {
            $block->delete($canceltext);
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        // Send out removal email 
        //if (($canceltext != WaseConstants::DoNotSendCancellationNotice)) {
            /* Read in the calendard */
            $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            ));
            if (substr($this->userid,0,1) != '@')
                $this->sendemail($directory->getEmail($this->userid), 'You have been removed as a member for the calendar with title "' . $calendar->title . '", owned by ' . $calendar->userid . ' (' . $calendar->name . ').<p>' . WaseUtil::getParm('SYSNAME') . '</p>', 'Change in your ' . WaseUtil::getParm('SYSNAME') . ' member status');
        // }
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
        /*01/24/19 - fix for deprecated Errors:To */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $headers = "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Content-type: text/html\r\n" . "Errors-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
        
        /* Send the email */
        WaseUtil::Mailer($to, $subject, $body, $headers);
    }

    /**
     * Return member info as an XML stream.
     *
     *
     * @return string This member record as an XML stream.
     */
    function xmlMemberInfo()
    {
        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        return '<user>' . '<userid>' . $this->userid . '</userid>' . '<name>' . $directory->getName($this->userid) . '</name>' . '<phone>' . $directory->getPhone($this->userid) . '</phone>' . '<email>' . $directory->getEmail($this->userid) . '</email>' . '</user>' . '<calendarid>' . $this->calendarid . '</calendarid>' . '<status>' . $this->status . '</status>' . '<notify>' . $this->notify . '</notify>' . '<remind>' . $this->remind . '</remind>';
    }
}
?>
