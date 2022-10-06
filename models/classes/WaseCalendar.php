<?php

/**
 * This class describes a calendar in the WASE system.
 * 
 * A WaseCalendar object is created for each calendar created in WASE.
 * This object is saved as a single record (row) in the WaseCalendar database table.
 * The object contains the database id of the calendar record,
 * and default values for all of the blocks belonging to this calendar.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 * 
 */
class WaseCalendar
{
    
    /* Properties */
    
    /**
     *
     * @var int $calendarid Database id of the WaseCalendar.
     */
    public $calendarid;

    /**
     *
     * @var string $title Title.
     */
    public $title;

    /**
     *
     * @var string $description Description.
     */
    public $description;

    /**
     *
     * @var string $userid Userid of owning user.
     */
    public $userid;

    /**
     *
     * @var string $name Name of owning user.
     */
    public $name;

    /**
     *
     * @var string $phone Phone number of user
     */
    public $phone;

    /**
     *
     * @var string $email Email of user
     */
    public $email;

    /**
     *
     * @var string $location Office location
     */
    public $location;

    /**
     *
     * @var bool $notify Send email notifications?.
     */
    public $notify;

    /**
     *
     * @var bool $notifyman Send email notifications to manager?.
     */
    public $notifyman;

    /**
     *
     * @var bool $notifymem Send email notifications to mmber?.
     */
    public $notifymem;

    /**
     *
     * @var bool $available Is block viewable?.
     */
    public $available;

    /**
     *
     * @var string $makeaccess Make restrictions.
     */
    public $makeaccess;

    /**
     *
     * @var string $viewaccess View restrictions.
     */
    public $viewaccess;

    /**
     *
     * @var string $makeulist Appointment make user list.
     */
    public $makeulist;
    
    /**
     *
     * @var string $makeclist Appointment make course list.
     */
    public $makeclist;
    
    /**
     *
     * @var string $makeglist Appointment make group list.
     */
    public $makeglist;
    
    /**
     *
     * @var string $makeslist Appointment make status list.
     */
    public $makeslist;

    /**
     *
     * @var string $viewulist Block view user list.
     */
    public $viewulist;

    /**
     *
     * @var string $viewclist Block view course list.
     */
    public $viewclist;
    
    
    /**
     *
     * @var string $viewglist Block view group list.
     */
    public $viewglist;
    
    
    /**
     *
     * @var string $viewslist Block view status list.
     */
    public $viewslist;
    
    /**
     *
     * @var bool $remind Send appointment reminders to owner?.
     */
    public $remind;

    /**
     *
     * @var bool $remindman Send appointment reminders to managers?.
     */
    public $remindman;

    /**
     *
     * @var bool $remindmem Send appointment reminders to members?.
     */
    public $remindmem;

    /**
     *
     * @var string $apptmsg Text to be included in notification and reminder emails.
     */
    public $apptmsg;

    /**
     *
     * @var bool $showappinfo Set to 1 if everyone sees appointment details.
     */
    public $showappinfo;

    /**
     *
     * @var bool $purreq This block requires that appointment purposes be specified.
     */
    public $purreq;

    /**
     *
     * @var bool $overlapok Allow blocks on this calendar to overlap blocks on other calendars.
     */
    public $overlapok;

    /**
     *
     * @var bool $waitlist This calendar has a waitlist boolean (0 or 1).
     */
    public $waitlist;
    
    /**
     *
     * @var string $icalpass The password required for ical functions.
     */
    public $icalpass;
  
    /**
     *
     * @var string $NAMETHING The label used (singular) to define what this calendar manages (e.g., "Office Hour").
     */
    public $NAMETHING;
    
    /**
     *
     * @var string $NAMETHINGS The label used (plural) to define what this calendar manages (e.g., "Office Hours").
     */
    public $NAMETHINGS;
    
    /**
     *
     * @var string $APPTHING The label used (singular) to define what this calendar schedules ("Appointment").
     */
    public $APPTHING;
    
    /**
     *
     * @var string $APPTHINGS The label used (plural) to define what this calendar schedules ("Appointments").
     */
    public $APPTHINGS;
    
    
    
    /* Static (class) methods */
    
    /**
     * Look up a calendar in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $id
     *            database id of the calendar record.
     *            
     * @return array an associative array of the calendar fields.
     */
    static function find($id)
    {
        /* Get a database handle. */
        if (! WaseSQL::openDB())
            return false;
            
            /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE calendarid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Get the entry into an associative array (there can only be 1). */
        $result = WaseSQL::doFetch($entry);
        
        /* Free up the query */
        WaseSQL::freeQuery($entry);
        
        /* Return the result */
        return $result;
    }

    /**
     * Locate all calendars that meet the designated criteria, and return the php resource, or NULL.
     *
     * @static
     *
     * @param array $arr
     *            Asociative array of SELECT criteria.
     *            
     * @return resource a PHP resource that contains the database records matching the SELECT criteria.
     */
    static function select($arr)
    {
        /* Issue the select call and return the results */
        return WaseSQL::selectDB('WaseCalendar', $arr);
    }

    /**
     * Return a WaseList of all calendars owned or managed or membered by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Owner userid (netid).
     *            
     * @return WaseList a list that contains the calendars owned or managed or membered by the specified user.
     */
    static function wlistAllCalendars($userid)
    {
        /* Get array of calendarids of calendars managed by this user. */
        $managedids = WaseManager::arrayManagedids($userid);
        /* Get array of calendarids of calendars owned by this user */
        $ownedids = self::arrayOwnedCalendarids($userid);
        /* Merge the arrays */
        $allids = array_merge($managedids, $ownedids);
        
        /* Now build a select string */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE (';
        $needor = false;
        foreach ($allids as $id) {
            if ($needor)
                $select .= ' OR ';
            $select .= 'calendarid=' . $id;
            $needor = true;
        }
        $select .= ') ORDER BY `userid`, `title`';
        
        /* Now return the list of calendars */
        return new WaseList($select, 'Calendar');
    }

    /**
     * Return a WaseList of all calendars managed by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return WaseList a list that contains the calendars managed by the specified user.
     */
    static function wlistManagedCalendars($userid)
    {
        /* Get list of calendarids of calendars managed by this user. */
        $managedids = WaseManager::arrayManagedids($userid);
        
        /* Now build a select string */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE (';
        $needor = false;
        foreach ($managedids as $id) {
            if ($needor)
                $select .= ' OR ';
            $select .= 'calendarid=' . $id;
            $needor = true;
        }
        $select .= ') ORDER BY `userid`, `title`';
        
        /* Now return the list of calendars */
        return new WaseList($select, 'Calendar');
    }

    /**
     * Return a WaseList of all calendars owned by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return WaseList a list that contains the calendars owned by the specified user.
     */
    static function wlistOwnedCalendars($userid)
    {
        /* Return the list of calendars */
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE (userid =' . WaseSQL::sqlSafe($userid) . ') ORDER BY `userid`, `title`', 'Calendar');
    }

    /**
     * Return a WaseList of all calendars membered by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return WaseList a list that contains the calendars membered by the specified user.
     */
    static function wlistMemberedCalendars($userid)
    {
        /* Return the list of calendars */
        return WaseMember::wlistMembered($userid);
    }

    /**
     * Return a list of ids of all calendars owned by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return array an array that contains the calendar ids of calendars owned by the specified user.
     */
    static function arrayOwnedCalendarids($userid)
    {
        return self::arrayCalendarField('calendarid', 'WHERE userid=' . WaseSQL::sqlSafe($userid));
    }

    /**
     * Return a list of ids of all calendars membered by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return array an array that contains the calendar ids of calendars membered by the specified user.
     */
    static function arrayMemberedCalendarids($userid)
    {
        return WaseMember::arrayMemberedids($userid,'');
    }

    /**
     * Return a list of ids of all calendars owned or membered by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return array an array that contains the calendar ids of calendars owned or membered by the specified user.
     */
    static function arrayOwnedorMemberedCalendarids($userid)
    {
        /* Return merged lists */
        return array_merge(self::arrayOwnedCalendarids($userid), self::arrayMemberedCalendarids($userid));
    }

    /**
     * Return a list of titles of all calendars owned by the specified user.
     *
     * @static
     *
     * @param string $userid
     *            Userid (netid).
     *            
     * @return array an array that contains the calendar titles of calendars owned by the specified user.
     */
    static function arrayOwnedCalendartitles($userid)
    {
        return self::arrayCalendarField('title', 'WHERE userid=' . WaseSQL::sqlSafe($userid));
    }

    /**
     * Return a WaseList of all calendars that meet the designated criteria.
     *
     * @static
     *
     * @param array $criteria
     *            Asociative array of SELECT criteria.
     * @param string $orderby
     *            a tring that contains the SELECT statement ORDER BY clause (minus 'ORDER BY').
     *            
     * @return WaseList a list that contains the database records matching the SELECT criteria.
     */
    static function wlistMatchingCalendars($criteria, $orderby = 'ORDER BY userid')
    {
        /* Build the list and return it in the specified order. */
        return new WaseList(WaseSQL::buildSelect('WaseCalendar', $criteria, $orderby), 'Calendar');
    }

    /**
     * Return an array of all distinct calendar field values that meet the designated criteria.
     *
     * @static
     *
     * @param string $field
     *            specified field names, comma separated.
     * @param string $orderby
     *            a tring that contains the SELECT statement ORDER BY clause (minus 'ORDER BY').
     *            
     * @return array an array that contains the database records matching the SELECT criteria.
     */
    static function arrayCalendarField($field, $where = '')
    {
        /* Do the query */
        $values = WaseSQL::doQuery('SELECT DISTINCT ' . $field . ' FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar ' . $where . ' ORDER BY ' . $field . ' ASC');
        /* Build return array */
        $ret = array();
        while ($value = WaseSQL::doFetch($values)) {
            $ret[] = $value[$field];
        }
        return $ret;
    }
    
    /* Obect Methods */
    
    /**
     * Construct a calendar.
     *
     * We have two constructors, one for a new calendar, one for an
     * existing calendar. In the former case, we create an entry in the
     * database calendar table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database appointment table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * calendar object.
     *
     * @param string $source
     *            'create','load' or 'update'
     * @param array $data
     *            an associative array of some or all of the calendar property values.
     *            
     * @return WaseCalendar the contructed, loaded or updated calendar object.
     */
    function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create' a calendar
         * $data is an associative array of elements used when we need to create/update
         * a calendar; for 'load', it contains just the calendarid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        
        /*
         * For load and update, find the calendar and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            // Grab the object from the cache, if there. 
            if (WaseCache::exists('calendar'.$ndata['calendarid']))
                $values = WaseCache::get('calendar'.$ndata['calendarid']);
            // If the object doesn't exist, blow up.
            else {
                if (! $values = WaseCalendar::find($ndata['calendarid']))           
                    throw new Exception('Calendar id ' . $ndata['calendarid'] . ' does not exist', 14);
                // Cache the properties
                WaseCache::add('calendar'.$ndata['calendarid'], $values);
            }
           
            // Load the data into the object.
            WaseUtil::loadObject($this, $values);
           
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();
        
        /* Set defaults for unspecified values */
        if ($source == 'create') {
            if (! $ndata['makeaccess'])
                $ndata['makeaccess'] = 'limited';
            if (! $ndata['viewaccess'])
                $ndata['viewaccess'] = 'limited';
            if (! $ndata['name'])
                $ndata['name'] = $directory->getName($ndata['userid']);
            if (! $ndata['phone'])
                $ndata['phone'] = $directory->getPhone($ndata['userid']);
            if (! $ndata['email'])
                $ndata['email'] = $directory->getEmail($ndata['userid']);
            if (! $ndata['location'])
                $ndata['location'] = $directory->getOffice($ndata['userid']);
            if (! array_key_exists('remind', $ndata))
                $ndata['remind'] = 1;
            if (! array_key_exists('remindman', $ndata))
                $ndata['remindman'] = 1;
            if (! array_key_exists('remindmem', $ndata))
                $ndata['remindmem'] = 1;
            if (! array_key_exists('notify', $ndata))
                $ndata['notify'] = 1;
            if (! array_key_exists('notifyman', $ndata))
                $ndata['notifyman'] = 1;
            if (! array_key_exists('notifymem', $ndata))
                $ndata['notifymem'] = 1;
            if (! array_key_exists('showappinfo', $ndata))
                $ndata['showappinfo'] = 0;
            if (! array_key_exists('available', $ndata))
                $ndata['available'] = 1;
            if (! array_key_exists('purreq', $ndata))
                $ndata['purreq'] = 0;
            if (! array_key_exists('NAMETHING', $ndata))
                $ndata['NAMETHING'] = WaseUtil::getParm('NAME');
            if (! array_key_exists('NAMETHINGS', $ndata))
                $ndata['NAMETHINGS'] = WaseUtil::getParm('NAMES');
            if (! array_key_exists('APPTHING', $ndata))
                $ndata['APPTHING'] = WaseUtil::getParm('APPOINTMENT');
            if (! array_key_exists('APPTHINGS', $ndata))
                $ndata['APPTHINGS'] = WaseUtil::getParm('APPOINTMENTS');
            if (!array_key_exists('waitlist', $ndata))
                $ndata['waitlist'] = 0;
        }
        
        /* For update, disallow certain changes */
        if ($source == 'update') {
            /* Anything goes */
        }
        
        /* For update and create, load up the values and validate the calendar. */
        if (($source == 'update') || ($source == 'create')) {
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate($source))
                throw new Exception($errors, 16);
        }
    }

    /**
     * Save this calendar.
     *
     * This function writes the calendar data out to the database and sends out notifications.
     *
     * The first argument specifies whether this is a new calendar or not.
     *
     * @param string $type
     *            'create','load', 'update'
     *            
     * @return int calendar id (database id) of created/saved/updated calendar.
     *        
     */
    function save($type)
    {
        /* Reject template title */
        if ($this->title == '*** Replace this with a Calendar title ***')
            throw new Exception('Invalid Calendar title', 14);
            
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify a calendarid */
                if ($key != 'calendarid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= '`' . $key . '`';
                    if ($vallist)
                        $vallist .= ',';
                    // Set the ical password
                    if ($key == 'icalpass')
                        // $value = WaseUtil::genpass(10);
                        $value = 'notset';
                    $vallist .= WaseSQL::sqlSafe($value);
                }
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar (' . $varlist . ') VALUES (' . $vallist . ')';
        }         /* Else create the update list */
else {
            $sql = '';
            foreach ($this as $key => $value) {
                if ($key != 'calendarid') {
                    if ($sql)
                        $sql .= ', ';
                    $sql .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
                }
            }
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET ' . $sql . ' WHERE calendarid=' . $this->calendarid;
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);
            
            /* Get the (new) id. */
        if ($type == 'create')
            $this->calendarid = WaseSQL::insert_id();
        
        // Update the cache
        WaseCache::add('calendar'.$this->calendarid, get_object_vars($this)); 
        
        return $this->calendarid;
    }

    /**
     * Validate appointment data, pass back error string if any errors.
     *
     * @param
     *            string source as per the constructor method.
     *            
     * @return string an error string, if any errors are found, else null.
     *        
     */
    function validate($source)
    {
        
        /* Validate passed data, pass back error string if any errors. */
        $errors = '';
        
        /* userid */
        if (! $this->userid)
            $errors .= 'userid required; ';
            
            /* title */
        $titles = self::arrayOwnedCalendartitles($this->userid);

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        if ($source == 'create') {
            if (! $this->title) {
                $name = $directory->getName($this->userid);
                if (! $name)
                    $name = $this->userid;
                $tcount = count($titles);
                if ($tcount > 0)
                    $this->title = 'Calendar (' . ($tcount + 1) . ')  for ' . $name;
                else
                    $this->title = 'Calendar for ' . $name;
            } else {
                foreach ($titles as $title) {
                    if (strtoupper(trim($this->title)) == strtoupper(trim($title))) {
                        $errors .= 'title "' . $this->title . '" already in use; ';
                        break;
                    }
                }
            }
        } else {
            if (! $this->title)
                $errors .= 'title cannot be blank';
            else {
                $allcals = self::wListOwnedCalendars($this->userid);
                foreach ($allcals as $cal) {
                    if ($cal->calendarid != $this->calendarid) {
                        if (strtoupper(trim($this->title)) == strtoupper(trim($cal->title))) {
                            $errors .= 'title "' . $this->title . '" already in use; ';
                            break;
                        }
                    }
                }
            }
        }
        
        /* notify, notifyman, notifymem, available */
        
        if (! WaseUtil::checkBool($this->notify))
            $errors .= 'notify must be a boolean; ';
        else
            $this->notify = WaseUtil::makeBool($this->notify);
        if (! WaseUtil::checkBool($this->notifyman))
            $errors .= 'notifyman must be a boolean; ';
        else
            $this->notifyman = WaseUtil::makeBool($this->notifyman);
        if (! WaseUtil::checkBool($this->notifymem))
            $errors .= 'notifymem must be a boolean; ';
        else
            $this->notifymem = WaseUtil::makeBool($this->notifymem);
        if (! WaseUtil::checkBool($this->available))
            $errors .= 'available must be a boolean; ';
        else
            $this->available = WaseUtil::makeBool($this->available);
            
            /* remind, remindman, remindmem, showappinfo, purreq, overlapok, waitlist */
        if (! WaseUtil::checkBool($this->remind))
            $errors .= 'remind must be a boolean;';
        else
            $this->remind = WaseUtil::makeBool($this->remind);
        if (! WaseUtil::checkBool($this->remindman))
            $errors .= 'remindman must be a boolean;';
        else
            $this->remindman = WaseUtil::makeBool($this->remindman);
        if (! WaseUtil::checkBool($this->remindmem))
            $errors .= 'remindmem must be a boolean;';
        else
            $this->remindmem = WaseUtil::makeBool($this->remindmem);
        if (! WaseUtil::checkBool($this->showappinfo))
            $errors .= 'showappinfo must be a boolean;';
        else
            $this->showappinfo = WaseUtil::makeBool($this->showappinfo);
        if (! WaseUtil::checkBool($this->purreq))
            $errors .= 'purreq must be a boolean;';
        else
            $this->purreq = WaseUtil::makeBool($this->purreq);
        if (! WaseUtil::checkBool($this->overlapok))
            $errors .= 'overlapok must be a boolean;';
        else
            $this->overlapok = WaseUtil::makeBool($this->overlapok);
        if (! WaseUtil::checkBool($this->waitlist))
            $errors .= 'waitlist must be a boolean;';
        else
            $this->waitlist = WaseUtil::makeBool($this->waitlist);
        
        return $errors;
    }

    /**
     * Delete this calendar.
     *
     * This function deletes the database record for this calendar.
     * Any appointments, blocks, series, periods and waitlist entries made in this calendar are also cancelled and deleted.
     *
     * @param string $canceltext
     *            text for appointment cancellation email.
     *            
     * @return void
     */
    function delete($canceltext)
    {
        /* First, build a list of all of the series. */
        $series = new WaseList(WaseSQL::buildSelect('WaseSeries', array(
            'calendarid' => $this->calendarid
        )), 'Series');
        
        /* Now delete each series (this will cancel the appointments) */
        foreach ($series as $serie)
            if (is_object($serie))
                $serie->delete($canceltext);
            
            /* Next, build a list of all of the blocks. */
        $blocks = new WaseList(WaseSQL::buildSelect('WaseBlock', array(
            'calendarid' => $this->calendarid
        )), 'Block');
        
        /* Now delete each block (this will cancel the appointments) */
        foreach ($blocks as $block)
            if (is_object($block))
                $block->delete($canceltext, 0);
            
            /* Delete all WaseManager entries for this calendar */
        WaseManager::removeAllManagers($this->calendarid);
        
        /* Delete all WaseMember entries for this calendar */
        WaseMember::removeAllMembers($this->calendarid);
        
        /* Delete this calendar */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar WHERE calendarid=' . $this->calendarid . ' LIMIT 1');
        
        /*
         * Delete all record of "deleted" entries
         * WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.waseDeleted WHERE calendarid=' . $this->calendarid . ';');
         */
        
        /* Delete the wait list */
        if (WaseUtil::getParm('WAITLIST') && $this->waitlist)
            WaseWait::purgeWaitList($this->calendarid);
        
        // Update the cache
        WaseCache::remove('calendar'.$this->calendarid);
    }

    /**
     * Can specified user view this calendar?
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true if user can view, else false.
     */
    function isViewable($usertype, $userid)
    {
        
        /* First, if userid is owner or manager, then viewable */
        if ($usertype != 'guest') {
            if ($this->isOwnerOrManagerOrMember($usertype, $userid))
                return true;
        }
        
        /* If not available, then not viewable */ 
        if (! $this->available)
            return false;
            
            /* If user is on the persona non-grata list for this calendar, then do not allow them access at all 
        if (WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseNonGrata WHERE ((calendarid=' . $this->calendarid . ' OR calendarid=0) AND userid=' . WaseSQL::sqlSafe($userid) . ');')))
            return false;
            */
        
            /* Select on view access type */
        switch ($this->viewaccess) {
             
            /* For open calendar, anyone can view it */
            case 'open':
                return true;
            
            /* For private calendar, must be owner or manager (we already tested for that) */
            case 'private':
                return false;
                break;
            
            /* For limited, must have a userid (any userid). */
            case 'limited':
                if ($usertype != 'guest')
                    return true;
                else
                    return false;
                break;
            
            /* For restricted access, must be in user list or course list. */
            case 'restricted':
                // Allow guests in alowed user list.
                //if ($usertype == 'guest')
                //    return false;
                if (in_array($userid, explode(',', $this->viewulist)))
                    return true;
                if (WaseUtil::IsUserInGroup(explode(',',$this->viewglist),$userid))
                    return true;
                if (WaseUtil::doesUserHaveStatus(explode(',',$this->viewslist),$userid))
                    return true; 
                else
                    return WaseUtil::IsUserInCourse(explode(',', $this->viewclist), $userid);
                break;
        }
    }

    /**
     * Is this calendar owned or managed or membered by the specified user.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true or false
     */
    function isOwnerOrManagerOrMember($usertype, $userid)
    {
        /* If a guest, return false */
        if ($usertype == 'guest')
            return false;
        /* if owner or manaager, return true. */
        if ($this->isOwnerOrManager($usertype, $userid))
            return true;
        /* If member, return true */
        if ($this->isMember($userid))
            return true;
        /* Otherwise, return false */
        return false;
    }

    /**
     * Is this calendar owned or managed by the specified user.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true or false
     */
    function isOwnerOrManager($usertype, $userid)
    {
        /* If a guest, return false */
        if ($usertype == 'guest')
            return false;
        /* if not owner or manager, return false */
        if (strtoupper(trim($this->userid)) != strtoupper(trim($userid))) {
            if (! ($this->isManager($userid)))
                return false;
        }
        return true;
    }

    /**
     * Is this calendar owned by the specified user.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true or false
     */
    function isOwner($usertype, $userid)
    {
        /* If a guest, return false */
        if ($usertype == 'guest')
            return false;
            /* if not owner return false */
        if (strtoupper(trim($this->userid)) == strtoupper(trim($userid)))
            return true;
        else
            return false;
    }

    /**
     * Can th specified user delete this calendar?
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true or false
     */
    function canDelete($usertype, $userid)
    {
        return $this->isOwnerOrManager($usertype, $userid);
    }

    /**
     * Is this calendar managed by the specified user.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $userid
     *            the specified userid or email (if guest).
     *            
     * @return bool true or false
     */
    function isManager($userid)
    {
        return WaseManager::isManager($this->calendarid, $userid);
    }

    /**
     * Who manages this calendar?
     *
     *
     * @return array array of userids of people who manage this calendar.
     */
    function listActiveManagers()
    {
        return WaseManager::listActiveManagers($this->calendarid);
    }

    /**
     * Who actively manages this calendar?
     *
     *
     * @return array array of userids of people who actively manage this calendar.
     */
    function wlistActiveManagers()
    {
        return WaseManager::wlistActiveManagers($this->calendarid);
    }

    /**
     * Who manages this calendar?
     *
     *
     * @return WaseList of userids of people who manage this calendar.
     */
    function wlistAllManagers()
    {
        return WaseManager::wlistAllManagers($this->calendarid);
    }

    /**
     * Does specified user manage this calendar?
     *
     * @param
     *            string userid userid of user.
     *            
     * @return bool yes or no.
     */
    function isMember($userid)
    {
        return WaseMember::isMember($this->calendarid, $userid);
    }

    /**
     * Who is a member of this calendar?
     *
     *
     * @return array array of userids of people who member this calendar.
     */
    function listMembers()
    {
        return WaseMember::listActiveMembers($this->calendarid);
    }

    /**
     * Who is a member of this calendar?
     *
     *
     * @return WaseList of userids of people who member this calendar.
     */
    function wlistMembers()
    {
        return WaseMember::wlistAllMembers($this->calendarid);
    }

    /**
     * This function returns an XML stream of calendar header information.
     *
     * @return string the xml stream.
     */
    function xmlCalendarHeaderInfo()
    {
        // Get waitlist.
        $waitlist = WaseWait::listWaitForCalendar($this->calendarid);
        // Get count of future blocks
        $blockslist = new WaseList('SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = "' . $this->userid . '" AND calendarid = ' . $this->calendarid . '  AND startdatetime >= "' . date('Y-m-d H:i:s') . '"','Block');
        // See if any apps on those blocks
        $ownerfutureapps = 0;
        foreach ($blockslist as $block) {
            $appslist = new WaseList('SELECT appointmentid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE blockid = ' . $block->blockid,'Appointment');
            $ownerfutureapps += $appslist->entries();
        }
       
        
        return $this->tag('calendarid') . $this->tag('title') . $this->tag('description') . $this->tag('waitlist') . $this->tag('waitcount',$waitlist->entries()) .
        '<owner>' . $this->tag('userid') . $this->tag('name') . $this->tag('phone') . $this->tag('email') . '<office>' . WaseUtil::safeXML($this->location) . '</office>' . '</owner>' . 
        $this->tag('labels',$this->xmlLabels()) . $this->tag('ownerfutureapps',$ownerfutureapps);
    }

    /**
     * This function returns an XML stream of short calendar information.
     *
     * @return string the xml stream.
     */
    function xmlShortCalendarInfo()
    {
        
        return $this->xmlCalendarHeaderInfo() . $this->tag('location') . $this->tag('notifyandremind', $this->xmlNotifyAndRemind()) . $this->tag('accessrestrictions', $this->xmlAccessRestrictions()) . $this->tag('purreq') . $this->tag('available') . $this->tag('overlapok') . $this->tag('purreq');
    }

    /**
     * This function returns an XML stream of short calendar information with member information.
     *
     * @return string the xml stream.
     */
    function xmlCalendarHeaderInfoWithMgrMem()
    {
        return $this->xmlCalendarHeaderInfo() . $this->xmlManagerInfo() . $this->xmlMemberInfo();
    }

    /**
     * This function returns an XML stream of full calendar information.
     *
     * @return string the xml stream.
     */
    function xmlCalendarInfo()
    {
        return $this->xmlCalendarHeaderInfo() . $this->tag('location') . $this->tag('notifyandremind', $this->xmlNotifyAndRemind()) . $this->tag('accessrestrictions', $this->xmlAccessRestrictions()) . 
        $this->tag('purreq') . $this->tag('available') . $this->tag('overlapok') . 
        $this->xmlManagerInfo() . $this->xmlMemberInfo() . $this->tag('icalpass');
    }

    /**
     * This function returns an XML stream of calendar manager information.
     *
     * @return string the xml stream.
     */
    function xmlManagerInfo()
    {
        // Init return
        $ret = '';
        // Get the list of managers who manage this calendar
        $managers = WaseManager::wlistAllManagers($this->calendarid);

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        // Return data on each manager
        foreach ($managers as $manager) {
            $ret .= '<manager><user>' . $this->tag('userid', $manager->userid) . $this->tag('name', $directory->getName($manager->userid)) . $this->tag('phone', $directory->getPhone($manager->userid)) . $this->tag('email', $directory->getEmail($manager->userid)) . $this->tag('location', $directory->getOffice($manager->userid)) . '</user>' . $this->tag('calendarid') . $this->tag('status', $manager->status) . $this->tag('notify', $manager->notify) . $this->tag('remind', $manager->remind) . '</manager>';
        }
        // Return the stream
        
        return $ret;
    }

    /**
     * This function returns an XML stream of calendar member information.
     *
     * @return string the xml stream.
     */
    function xmlMemberInfo()
    {
        // Init return
        $ret = '';
        // Get the list of calendars members 
        $members = self::wListMembers();

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        // Return data on each member
        foreach ($members as $member) {
            // Get count of future blocks
            $blockslist = new WaseList('SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = "' . $member->userid . '" AND calendarid = ' . $this->calendarid . '  AND startdatetime >= "' . date('Y-m-d H:i:s') . '"','Block');
            // See if any apps on those blocks
            $memberfutureapps = 0;
            foreach ($blockslist as $block) {
                $appslist = new WaseList('SELECT appointmentid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE blockid = ' . $block->blockid,'Appointment');
                $memberfutureapps += $appslist->entries();
            }
            $ret .= '<member><user>' . $this->tag('userid', $member->userid) . $this->tag('name', $directory->getName($member->userid)) . $this->tag('phone', $directory->getPhone($member->userid)) .
                $this->tag('email', $directory->getEmail($member->userid)) . $this->tag('location', $directory->getOffice($member->userid)) . '</user>' . $this->tag('calendarid') . $this->tag('status', $member->status) .
            $this->tag('notify', $member->notify) . $this->tag('remind', $member->remind)  . $this->tag('memberfutureapps',$memberfutureapps) . '</member>';
        }
        // Return the stream
        return $ret;
    }

    /**
     * This function returns an XML stream of calendar notification information.
     *
     * @return string the xml stream.
     */
    function xmlNotifyAndRemind()
    {
        return $this->tag("notify") . $this->tag("notifyman") . $this->tag("notifymem") . $this->tag("remind") . $this->tag("remindman") . $this->tag("remindmem") . $this->tag("apptmsg");
    }

    /**
     * This function returns an XML stream of calendar access restrictions information.
     *
     * @return string the xml stream.
     */
    function xmlAccessRestrictions()
    {
        return $this->tag("viewaccess") . $this->tag("viewulist") . $this->tag("viewclist") .  $this->tag("viewglist") . $this->tag("viewslist") . $this->tag("makeaccess") . $this->tag("makeulist") . $this->tag("makeclist") . $this->tag("makeglist") . $this->tag("makeslist") . $this->tag("showappinfo");
    }

    /**
     * This function returns an XML stream of calendar labels.  
     *
     * @return string the xml stream.
     */
    function xmlLabels()
    {
        return $this->tag("NAMETHING") . $this->tag("NAMETHINGS") . $this->tag("APPTHING") .  $this->tag("APPTHINGS");
    }
    
    /**
     * This private function returns an xml tag with the specified property name and value.
     *
     * @param string $property
     *            the property
     * @param string $val
     *            the property value
     *            
     * @return string the xml tag.
     *        
     */
    private function tag($property, $val='')
    {
        if (func_num_args() == 2)
            return '<' . $property . '>' .  $val . '</' . $property . '>';
        else
            return '<' . $property . '>' . htmlspecialchars($this->{$property},ENT_NOQUOTES)  . '</' . $property . '>';
    }
}

?>
