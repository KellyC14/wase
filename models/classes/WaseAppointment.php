<?php

/**
 * This class describes an appointment in the WASE system.
 * 
 * A WaseAppointment object is created for each appointment created in WASE.
 * This object is saved as a single record (row) in the WaseAppointment database table.
 * The object contains the database id of the block and calendar to which the appointment belongs,
 * and all of the property values for the appointment.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 * 
 */
class WaseAppointment
{
    
    /* Properties */
    
    /**
     *
     * @var int $appointmentid Database id of the WaseAppointment appointment
     */
    public $appointmentid;

    /**
     *
     * @var int $blockid Database id of the WaseBlock record
     */
    public $blockid;

    /**
     *
     * @var int $calendarid Database id of the WaseCalendar record
     */
    public $calendarid;

    /**
     *
     * @var string $name Name of user
     */
    public $name;

    /**
     *
     * @var string $email Email of user
     */
    public $email;

    /**
     *
     * @var string $textemail Text msg email address of user
     */
    public $textemail;

    /**
     *
     * @var string $phone Phone number of user
     */
    public $phone;

    /**
     *
     * @var string $userid Userid of user
     */
    public $userid;

    /**
     *
     * @var string $startdatetime Starting date time of app (yyyy-mm-dd hh:mm:ss)
     */
    public $startdatetime;

    /**
     *
     * @var string $enddatetime Ending date time of app (yyyy-mm-dd hh:mm:ss)
     */
    public $enddatetime;

    /**
     *
     * @var string $purpose Purpose of appointment
     */
    public $purpose;

    /**
     *
     * @var bool $remind Send reminder?
     */
    public $remind;

    /**
     *
     * @var bool $reminded Sent reminder?
     */
    public $reminded;

    /**
     *
     * @var string $notes Text about the appointment
     */
    public $notes;

    /**
     *
     * @var string $uid Ical UID
     */
    public $uid;

    /**
     *
     * @var string $gid Google UID
     */
    public $gid;

    /**
     *
     * @var string $eid Exchange UID
     */
    public $eid;

    /**
     *
     * @var string $gid Exchange ChangeKey
     */
    public $eck;

    /**
     *
     * @var string $whenmade date/time appointment was made
     */
    public $whenmade;

    /**
     *
     * @var string $lastchange date/time appointment was last changed
     */
    public $lastchange;

    /**
     *
     * @var string $madeby Userid/email of user making the appointment
     */
    public $madeby;

    /**
     *
     * @var bool $available 1 for standard appointment, 0 for dummy appointment (slot holder)
     */
    public $available;

    /**
     *
     * @var string $sequence Update counter for generating iCal files
     */
    public $sequence;

    /**
     *
     * @var string $venue Stores appoint-with meeting venue preference.
     */
    public $venue;
    
    /* Static (class) methods */
    
    /**
     * Look up an appointment in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $id
     *            database id of the appointment record.
     *            
     * @return array an associative array of the appointment fields.
     */
    static function find($id)
    {
        
        /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE appointmentid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Get the entry into an associative array (there can only be 1). */
        $result = WaseSQL::doFetch($entry);
        
        /* Return the result */
        return $result;
    }

    /**
     * Locate all appointments that meet the designated criteria, and return the php resource, or NULL.
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
        return WaseSQL::selectDB('WaseAppointment', $arr);
    }

    /**
     * Return a WaseList of all appointments that meet the designated criteria.
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
    static function listMatchingAppointments($criteria, $orderby = 'ORDER BY startdatetime')
    {
        return new WaseList(WaseSQL::buildSelect('WaseAppointment', $criteria, $orderby), 'Appointment');
    }

    /**
     * Return a WaseList of matching appointments that meet criteria which may include multiple instances of the same parameter.
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
    static function listOrderedAppointments($criteria, $orderby)
    {
        return new WaseList(WaseSQL::buildOrderedSelect('WaseAppointment', $criteria, $orderby), 'Appointment');
    }
    
    /**
     * Return a WaseList of appoointment with or for a user.
     *
     * @static
     * 
     * @param string $userid
     *            The userid if the user whose appointments will be returned.
     * @param string $orderby
     *            a tring that contains the SELECT statement ORDER BY clause (minus 'ORDER BY').
     *
     * @return WaseList a list that contains the database records with or for the user.
     */
    static function myAppointments($userid, $orderby='`startdatetime`')
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (available = 1 AND ((userid = ' . WaseSQL::sqlSafe($userid) . ') OR ' .
            'blockid IN (SELECT distinct blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))) ' .
            'ORDER BY ' . $orderby, 'Appointment'); 
    }
    
    /**
     * Return a WaseList of appoointment with or for a user, including unavailable ones..
     *
     * @static
     *
     * @param string $userid
     *            The userid if the user whose appointments will be returned.
     * @param string $orderby
     *            a tring that contains the SELECT statement ORDER BY clause (minus 'ORDER BY').
     *
     * @return WaseList a list that contains the database records with or for the user.
     */
    static function myAllAppointments($userid, $orderby='`startdatetime`')
    {
        return new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (((userid = ' . WaseSQL::sqlSafe($userid) . ') OR ' .
            'blockid IN (SELECT distinct blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid = ' . WaseSQL::sqlSafe($userid) . '))) ' .
            'ORDER BY ' . $orderby, 'Appointment');
    }
    
    
    
    /* Object Methods */
    
    /**
     * Construct an appointment.
     *
     * We have two constructors, one for a new appointment, one for an
     * existing appointment. In the former case, we create an entry in the
     * database appointment table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database appointment table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * appointment object.
     *
     * @param string $source
     *            'create','load' or 'update'
     * @param array $data
     *            an associative array some or all of the appointment property values.
     *            
     * @return WaseAppointment the contructed, loaded or updated appointment object.
     */
    function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create' an appointment.
         * $data is an associative array of elements used when we need to create/update
         * an appointment; for 'load', it contains just the appointmentid.
         * 
         * "force" is a synonym for "create", escept that it bypases the overlap test.
         */
       
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        

        // Get our block
        $labels = WaseUtil::getLabels(array('blockid' => $ndata['blockid'])); 
        
        /*
         * For load and update, find the appointment and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            // Grab the object from the cache, if there.
            if (WaseCache::exists('appointment'.$ndata['appointmentid']))
                $values = WaseCache::get('appointment'.$ndata['appointmentid']);
                // If the object doesn't exist, blow up.
                else {
                    if (! $values = WaseAppointment::find($ndata['appointmentid']))
                        throw new Exception($labels['APPTHING'] .' id ' . $ndata['appointmentid'] . ' does not exist', 14);
                    // Cache the properties
                    WaseCache::add('appointment'.$ndata['appointmentid'], $values);
                }
                
                // Load the data into the object.
                WaseUtil::loadObject($this, $values);               
                
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Set defaults for unspecified values when creating an oppointment. */
        if ($source == 'create' OR $source == "force") {
            if ($ndata['userid']) {
                if (! $ndata['name'])
                    $ndata['name'] = $directory->getName($ndata['userid']);
                if (! $ndata['phone'])
                    $ndata['phone'] = $directory->getPhone($ndata['userid']);
                if (! $ndata['email'])
                    $ndata['email'] = $directory->getEmail($ndata['userid']);
            }
            if (! array_key_exists('remind', $ndata))
                $ndata['remind'] = 1;
            if (! array_key_exists('available', $ndata))
                $ndata['available'] = 1;
            if (! $ndata['uid'])
                $ndata['uid'] = date('Ymd\TGis') . "-" . rand(1000, 1000000) . '@' . $_SERVER['REQUEST_URI'];
            //12/23/19 -add default of 0 for reminded
            if (! array_key_exists('reminded', $ndata))
                $ndata['reminded'] = 0;
            //01/03/20 -add default of 0 for sequence
            if (! array_key_exists('sequence', $ndata))
                $ndata['sequence'] = 0;
        }        

        /* For update, disallow resetting of calendarid or blockid */
        elseif ($source == 'update') {
            if (($ndata['calendarid']) && ($ndata['calendarid'] != $this->calendarid))
                throw new Exception('Cannot assign ' . $labels['APPTHING'] . ' to a new calendar', 15);
            if (($ndata['blockid']) && ($ndata['blockid'] != $this->blockid))
                throw new Exception('Cannot assign '. $labels['APPTHING'] . ' to a new block', 15);
        }
        
        /* For update and create, load up the values and validate the appointment. */
        if (($source == 'update') || ($source == 'create')  || ($source == "force")) {
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate($source))
                throw new Exception($errors, 15);
        }
    }

    /**
     * Save this appointment.
     *
     * This function writes the appointment data out to the database and sends out notifications.
     *
     * The first argument specifies whether this is a new appointment or not. If this is not a new appointment
     * (an appointment is being edited), the second argument points to the old appointment object.
     *
     * @param string $type
     *            'create','load', 'update' or 'remind'
     * @param WaseAppointment $curapp
     *            current appointment object if any, else null.
     *            
     * @return int appointment id (database id) of created/saved/updated appointment.
     *        
     */
    function save($type, $curapp = '')
    {
        /* Read in the block object */
        $block = new WaseBlock('load', array(
            'blockid' => $this->blockid
        ));
        
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            
            // We need to check, across a LOCK, that an appointment slot is still available.
            
            
            /* If no whenmade, set a value */
            if (! $this->whenmade)
                $this->whenmade = date('Y-m-d H:m:s');
            $this->lastchange = $this->whenmade;
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify an appointmentid */
                if ($key != 'appointmentid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= '`' . $key . '`';
                    //12/23/19 add strlen check to handle 0
                    if (($vallist) || (strlen($vallist)))
                        $vallist .= ',';
                    $vallist .= WaseSQL::sqlSafe($value);
                }
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment (' . $varlist . ') VALUES (' . $vallist . ')';
        }
        /* Else if just setting the reminded flag, create a short update string */
        if ($type == 'reminded')
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment SET reminded=1 WHERE appointmentid=' . $this->appointmentid;
            /* Else create the update list */
        if ($type == 'update') {
            $this->lastchange = date('Y-m-d H:m:s');
            $sql = '';
            foreach ($this as $key => $value) {
                /* Don't specify an appointmentid */
                if ($key != 'appointmentid') {
                    if ($sql)
                        $sql .= ', ';
                    $sql .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
                }
            }
            /* We also need to increment the sequence number */
            $this->sequence++;
            if ($sql)
                $sql .= ', ';
            $sql .= 'sequence = ' . $this->sequence;
            
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment SET ' . $sql . ' WHERE appointmentid=' . $this->appointmentid;
        }
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);
             
        /* Extract start date and time */
        list ($startdate, $starttime) = explode(' ', $this->startdatetime);
        
        /* If saving a new appointment, save the id. */
        If ($type == 'create') {
            /* Save the appointment id. */
             
            $this->appointmentid = WaseSQL::insert_id();
             
            /* Record user's latest appointment */
            if ($this->userid)
                WasePrefs::savePref($this->userid, 'lastappmade', date('Y-m-d H:i:s'), 'user');
            else
                WasePrefs::savePref($this->email, 'lastappmade', date('Y-m-d H:i:s'), 'guest');
                
            /* Set up useful subject line */
            
            $subject = $block->APPTHING . ': ' . $this->name . ' with ' . $block->name . ' at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate);
            /* Sync the appointment and send out notification email */
            if ($this->available) {
                /* Save current gid and eid */
                $oldgid = $this->gid;
                $oldeid = $this->eid;
                $oldeck = $this->eck;
                $this->notify('The following ' . $block->APPTHING  . ' has been scheduled:', $subject, $block, 'add', '');
                /* If new google or exchange id assigned, save it */
                if ($this->gid != $oldgid || $this->eid != $oldeid || $this->eck != $oldeck) {
                    WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment SET gid=' . WaseSQL::sqlSafe($this->gid) . ', eid=' . WaseSQL::sqlSafe($this->eid) . ', eck=' . WaseSQL::sqlSafe($this->eck) . ' WHERE appointmentid=' . $this->appointmentid);
                }
            }
           
        } 
        /* If editing an appointment, send out change notification */
        elseif ($type != 'reminded') {
            
            $subject = $block->APPTHING . ' changed: ' . $this->name . ' with ' . $block->name . ' at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate);
            
            /* Send out notification email */
            if ($this->available) {
                /* Save current gid and eid */
                $oldgid = $this->gid;
                $oldeid = $this->eid;
                $oldeck = $this->eck;
                $this->notify('The following '.$block->APPTHING.' has been changed:', $subject, $block, 'change', $curapp);
                /* If new google or exchange id assigned, save it */
                if ($this->gid != $oldgid || $this->eid != $oldeid || $this->eck != $oldeck) {
                    WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment SET gid=' . WaseSQL::sqlSafe($this->gid) . ', eid=' . WaseSQL::sqlSafe($this->eid) . ', eck=' . WaseSQL::sqlSafe($this->eck) . ' WHERE appointmentid=' . $this->appointmentid);
                }
            }
        }
        
        /* Remove the user from the wait list, if on it */
        if (WaseUtil::getParm('WAITLIST')) {
            WaseWait::purgeWaitListUserAll($this->calendarid, $this->userid);
        }
        
        // Update the cache
        WaseCache::add('appointment'.$this->appointmentid, get_object_vars($this));
        
        return $this->appointmentid;
    }

    /**
     * Validate appointment data, pass back error string if any errors.
     * 
     * $param string $source
     *      whether creating/loading/forcing an appointment.
     *
     * @return string an error string, if any errors are found, else null.
     *        
     */
    function validate($source = "create")
    {
        $errors = '';
        
        /* Read in the calendar */
        if (! ($calendar = WaseCalendar::find($this->calendarid))) 
            $errors .= 'Invalid calendarid: ' . $this->calendarid . '; ';
            
        /* Read in the block */
        if (! ($block = WaseBlock::find($this->blockid)))
            $errors .= 'Invalid blockid: ' . $this->blockid . '; ';
       
        /* startdatetime and enddatetime */
        if (! $temp = WaseUtil::checkDateTime($this->startdatetime))
            $errors .= 'Invalid start date and time: ' . $this->startdatetime . '; ';
        else
            $this->startdatetime = $temp;
        if (! $temp = WaseUtil::checkDateTime($this->enddatetime))
            $errors .= 'Invalid end date and time: ' . $this->enddatetime . '; ';
        else
            $this->enddatetime = $temp;
            
        /* Make sure app stardatetime and enddatetime are within block start/end datetime. */
        if ($block) {
            if (WaseUtil::makeUnixTime($this->startdatetime) < WaseUtil::makeUnixTime($block['startdatetime']))
                $errors .= $block['APPTHING'] .' start date/time (' . $this->startdatetime . ') must be greater than or equal to block start/time (' . $block['startdatetime'] . '); ';
            if (WaseUtil::makeUnixTime($this->enddatetime) > WaseUtil::makeUnixTime($block['enddatetime']))
                $errors .=$block['APPTHING'] .' end date/time (' . $this->enddatetime . ') must be less than or equal to block end date/time (' . $block['enddatetime'] . '); ';
        }
        
        /* Make sure end datetime > start datetime */
        if (WaseUtil::makeUnixTime($this->enddatetime) <= WaseUtil::makeUnixTime($this->startdatetime))
            $errors .= $block['APPTHING'] . ' start date/time (' . $this->startdatetime . ') must be before end date/time (' . $this->enddatetime . '); ';
            
        /* remind */
        if (! WaseUtil::checkBool($this->remind))
            $errors .= 'remind must be a boolean; ';
        else
            $this->remind = WaseUtil::makeBool($this->remind);
            
        /* available */
        if (! WaseUtil::checkBool($this->available))
            $errors .= 'available must be a boolean; '; 
        else
            $this->available = WaseUtil::makeBool($this->available);
        

        // Check for any overlaps, unless force flag is set.
        if ($source != "force") {
            $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment' . 
                ' WHERE (userid = ' . WaseSQL::sqlSafe($this->userid);
            if ($this->appointmentid)
                $select .= ' AND appointmentid != ' . $this->appointmentid;
            $select .= ' AND (startdatetime < ' . WaseSQL::sqlSafe($this->enddatetime) .
                            ' AND  enddatetime > ' . WaseSQL::sqlSafe($this->startdatetime) . '))';
            $overlapsfor = new WaseList($select,'Appointment');  
            
            // Get the appointment with data
            $withblock = new WaseBlock('load',array("blockid"=>$this->blockid));
            $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment' . 
                ' WHERE (userid = ' . WaseSQL::sqlSafe($withblock->userid);
            if ($this->appointmentid)
                $select .= ' AND appointmentid != ' . $this->appointmentid;
            $select .=  ' AND (startdatetime < ' . WaseSQL::sqlSafe($this->enddatetime) .
                    ' AND  enddatetime > ' . WaseSQL::sqlSafe($this->startdatetime) . '))';   
            $overlapswith = new WaseList($select,'Appointment');  
             
            if ($overlapsfor->entries() || $overlapswith->entries()) {
                $errors .= $block['APPTHING'] . ' overlaps with: ';  
                // Avoid reporting overlaps twice
                $appsseen = array();
                foreach ($overlapsfor as $app) {
                    if (!in_array($app->appointmentid,$appsseen)) {
                        // Get the Block with the overlap
                        $appsseen[] = $app->appointmentid;
                        $ovblock = WaseBlock::find($app->blockid);
                        $errors .= $block['APPTHING'] . ' for ' . $this->userid . ' (' . $this->name . ') on ' . WaseUtil::datetimeToUS($app->startdatetime) . 
                        ' to ' . WaseUtil::datetimeToUS($app->enddatetime) . ' with ' . $ovblock['userid'] . ' (' . $ovblock['name'] . '), ';
                    }
                }
                foreach ($overlapswith as $app) {
                    if (!in_array($app->appointmentid,$appsseen)) {
                        $errors .= $block['APPTHING'] . ' for ' . $this->userid . ' (' . $this->name . ') on ' . WaseUtil::datetimeToUS($app->startdatetime) . ' to ' . WaseUtil::datetimeToUS($app->enddatetime) . ' with ' . $app->userid . ' (' . $app->name . '), ';
                    }
                }
                $errors = substr($errors,0,-2) . '; '; 
            }
           
        }
        return $errors; 
    }

    /**
     * Cancel (delete) this appointment.
     *
     * This function deletes this appointment record from the WASE database.
     * The first argument specifies any special text that should be sent to the appointment holders.
     * The optional second argument points to the owning block.
     *
     * @param string $canceltext
     *            The text to be emailed to the appointment holder.
     * @param WaseBlock $block
     *            pointer to owning block object, or null.
     *            
     * @return null
     *
     */
    function delete($canceltext, $block = 0)
    {
        /* If block not passed, read it in */
        if (! $block)
            $block = new WaseBlock('load', array(
                'blockid' => $this->blockid
            ));
            
            /* Save the deleted appointment data for later delivery as an Ical CANCEL */
         
        /*
         * First, compute the DTSTART
         * $dtstart = gmdate('Ymd\THis\Z',mktime(substr($this->startdatetime,11,2),substr($this->startdatetime,14,2),0,substr($this->startdatetime,5,2),substr($this->startdatetime,8,2),substr($this->startdatetime,
         * 0,4)));
         */
            /*
         * Now write out the deleted appointment data
         * WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.waseDeleted (calendarid,sequence,uid,dtstart) VALUES (' . WaseSQL::sqlSafe($this->calendarid) . ',' . WaseSQL::sqlSafe($this->sequence + 1) . ',' . WaseSQL::sqlSafe($this->uid) . ',' . WaseSQL::sqlSafe($dtstart) . ');');
         */
            
        /* Delete the appointment record. */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE appointmentid=' . $this->appointmentid . ' LIMIT 1');
        
        /* Extract start date and time */
        list ($startdate, $starttime) = explode(' ', $this->startdatetime);  
        
        /* Set up useful subject line */
        $subject = $block->APPTHING .' cancelled: ' . $this->name . ' with ' . $block->name . ' at ' . WaseUtil::AmPm($starttime) . ' on ' . WaseUtil::usDate($startdate); 
        
        /* Increment the sequence number for the iCal attachment */
        // A deleet is NOT considered an update, so the sequence number stays the same.
        // $this->sequence ++;
        
        /* Send out cancellation email and perform any needed synchronization. */
        if (($canceltext != WaseConstants::DoNotSendCancellationNotice) && ($this->available))
            $this->notify('The following '.$block->APPTHING.' has been cancelled: ' . $canceltext . '<br /><br/>', $subject, $block, 'delete', '');
            
        /* Notify users on the wait list */
        if (WaseUtil::getParm('WAITLIST')) {
            /* Read in the calendar */
            $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            ));
            if ($calendar->waitlist)
                WaseWait::notifyForCalendarAndWindow($this->calendarid, $this->blockid, $this->startdatetime, $this->enddatetime);
        }
        
        // Update the cache
        WaseCache::remove('appointment'.$this->appointmentid);
    }

    /**
     * Send out notifications about this appointment, and sync appointment to external calendars.
     *
     * This function sends email and textmsg notifications about various status changes with an appointment, such as
     * creation, deletion, change and reminders. It also syncs the appointment out to iCal or Google or Exchange, 
     * depending on the user's preferences.
     *
     *
     * @param string $msghead
     *            The text to be emailed to the appointment holders.
     * @param string $subject
     *            The email subject line and textmsg text line.
     * @param WaseBlock $block
     *            pointer to owning block object, or null.
     * @param string $type
     *            nature of notification, "add", "change" or "delete".
     * @param WaseAppointment $curapp
     *            If a change is being made to an appointment, the old appointment values.
     *            
     * @return string a set of comma-seperated counters that give message sent counts (appwith, appfor, managers, member).
     *        
     */
    function notify($msghead, $subject, $block, $type, $curapp)
    {
        /* Extract start date and time and parts of time */
        list ($startdate, $starttime) = explode(' ', $this->startdatetime);
        list ($starthour, $startmin, $startsec) = explode(':', $starttime);
        
        /* If appointment is in the past, don't send out any notifications. */
        if (WaseUtil::beforeToday($startdate))
            return '0,0,0';
            
            /* If today but before now, don't notify */
        if (WaseUtil::isToday($startdate)) {
            $currhour = date('H');
            $currmin = date('i');
            if ($currhour > $starthour)
                return '0,0,0';
            elseif ($currhour == $starthour)
                if ($currmin > $startmin)
                    return '0,0,0';
        }
        
        /* Initialize email counters */
        $sc = 0;
        $pc = 0;
        $mc = 0;
        $ec = 0;
        
        /* If no block passed, read it in */
        if (! $block)
            $block = new WaseBlock('load', array(
                'blockid' => $this->blockid
            ));
            
            /* We will also need access to the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        /* Figure out what, if anything, has changed (if editing) */
        if ($curapp) {
            if ($this->name != $curapp->name)
                $cname = '  ** Changed **';
            if ($this->email != $curapp->email)
                $cemail = '  ** Changed **';
            if ($this->phone != $curapp->phone)
                $cphone = '  ** Changed **';
            if (substr($this->startdatetime, 11, 5) != substr($curapp->startdatetime, 11, 5))
                $cstarttime = '  ** Changed **';
            if (substr($this->enddatetime, 11, 5) != substr($curapp->enddatetime, 11, 5))
                $cendtime = '  ** Changed **';
            if ($this->purpose != $curapp->purpose)
                $cpurpose = '  ** Changed **';
            if ($this->venue != $curapp->venue)
                $cvenue = '  ** Changed **';
        } else {
            $cname = '';
            $cemail = '';
            $cphone = '';
            $cstarttime = '';
            $cendtime = '';
            $cpurpose = '';
            $cvenue = '';
        }
        
        /* Keep track of whom we sent email to */
        $sendto = array();
        /* Keep track of who we synchronized with */
        $syncto = array();
        
        /* Set up headers */
        /*01/24/19 - fix for deprecated Errors-To */
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        $shortheaders = "Content-Type: text/html; charset=UTF-8" . "\r\n" . "Reply-To: " . WaseUtil::getParm('FROMMAIL') . "\r\n" . "Errors-To: $my_sysmail \r\nReply-To: $my_sysmail \r\nReturn-Path: $my_sysmail";
        // $fheader = "-f $my_sysmail";

        /* Handle the professors (AppWith) */
        
        /* First, determine if dealing with the calendar owner or a calendar member */
        /* no longer uses remindmem and notifymem */
        //if ($block->userid == $calendar->userid) {
            $remind = $block->remind;
            $notify = $block->notify;
        //} else {
            // $remind = $block->remindmem;
            // $notify = $block->notifymem;
        //}

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /* Get target email address */
        if (! $bemail = $block->email)
            $bemail = $directory->getEmail($block->userid);
     
        /* Get the user's sync preference */
        $syncpref = WasePrefs::getPref($block->userid, 'localcal');
        
        
        // Set flags for synchronization
        $syncdone = false;
        $syncfail = '';
        
        /* If block owner wants Google synchronization, go do it */
        if ($type != 'remind' && $syncpref == 'google') {
            if ($type == 'add')
                $syncdone = WaseGoogle::addApp($this, $block, $block->userid);
            elseif ($type == 'change')
                $syncdone = WaseGoogle::changeApp($this, $block, $block->userid);
            elseif ($type == 'delete')
                $syncdone = WaseGoogle::delObj($this, $block->userid);
                
            /* Save synchronization done status */
            if ($syncdone)
                $syncto[] = strtolower($bemail);
            else
                $syncfail = 'google';
                
            // Reset the user's sync preference to 'none'
            // if ($syncfail)
                // WasePrefs::savePref($block->userid, 'localcal', 'none');
        }
        
        /* If block owner wants Exchange synchronization, go do it */
        $syncdone = false;
        if ($type != 'remind' && $syncpref == 'exchange') {
            if ($type == 'add')
                $syncdone = WaseExchange::addApp($this, $block, $block->userid, $block->email);
            elseif ($type == 'change')
                $syncdone = WaseExchange::changeApp($this, $block, $block->userid, $block->email);
            elseif ($type == 'delete')
                $syncdone = WaseExchange::delObj($this, $block->userid);
                
            // Save synchronization done status 
            if ($syncdone) {
                $syncto[] = strtolower($bemail);
                // If the exchange integration is not direct, then invitations have already been sent out to the prof and student.  Flag that so that
                // we do not re-invite the student.
                $syncdirect = WaseUtil::getParm('EXCHANGE_DIRECT');              
            }
            else
                $syncfail = 'exchange';
                
            // Reset the user's sync preference to 'none'
            // if ($syncfail)
                // WasePrefs::savePref($block->userid, 'localcal', 'none');
        }
       
        /* If block owner wants notification, OR synchronization failed, send it */
        /*  add check on $syncpref with ical */
        if (($type == "remind" && $remind) || ($type != "remind" && $notify)  || ($syncfail || $syncpref == 'ical') ) {
            /* Generate text of email */
            if ($curapp)
                $msg = $msghead . '<br /><br /><i>Changed From</i>:<br /><br />' . $curapp->appHTML($block, $bemail, 'changefrom', 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue) .
                    '<br /><br /><i>Changed To</i>:<br /><br />' . $this->appHTML($block, $bemail, 'changeto', 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
            else
                $msg = $msghead . '<br /><br />' . $this->appHTML($block, $bemail, $type, 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
                
            /* Let user know if sync failed. */
             if ($syncfail == 'google')
                $msg .= '<p>Unable to update your Google calendar. Go into ' . WaseUtil::getParm('SYSID') . '  preferences, set your sync preference to none, then back to Google.  This will allow you to re-authorize access to your Google calendar.';
             elseif ($syncfail == 'exchange')
                $msg .= '<p>Unable to update your Exchange (Outlook) calendar. This email contains an iCal attachment.  Opening this attachment should put the appointment on your Exchange calendar (if it is not there already).';
            
            // Sign the email
            $msg .= '<br /><br />---' . WaseUtil::getParm('SYSNAME');
             
            /* If user wants sync and sync not successful, include the app as an email attachment (.ics file) */
            /*  add check on $syncpref with ical*/
            if (!in_array(strtolower($bemail), $syncto) && $syncpref != 'none' && $type != 'remind' && ($syncfail || $syncpref == 'ical') ) {
                /* This code will send out the notification with an imbeded iCal attachment, which takes care of the sync */
                if (WaseUtil::IcalMailer($bemail, $block->email, $subject, $msg, self::makeIcal($bemail, WaseUtil::getParm('FROMMAIL'), $type), WaseUtil::getParm('FROMMAIL'), $type)) {
                    $pc ++;
                    $didemail = true;
                }
                else 
                    $didemail = false;
            }
            if (!$didemail) {
                if (WaseUtil::Mailer($bemail, $subject, $msg, $shortheaders)) {
                    $pc ++;
                }
            }
            $sendto[] = strtolower($bemail);
        }

        
        /* Now handle managers */
        
        /* If managers want notice, send them out, unless already sent. */
        if (($type == "remind" && $block->remindman) || ($type != "remind" && $block->notifyman)) {
            /* Add in appointment info. */
            if ($curapp)
                $msg = $msghead . '<br /><br /><i>Changed From</i>:<br /><br />' .
                    $curapp->appHTML($block, $bemail, 'changefrom', 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue) .
                    '<br /><br /><i>Changed To</i>:<br /><br />' . $this->appHTML($block, $bemail, 'changeto', 'manager', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
            else
                $msg = $msghead . '<br /><br />' . $this->appHTML($block, $bemail, $type, 'manager', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
            
            // Sign the email
            $msg .= '<br /><br />---' . WaseUtil::getParm('SYSNAME');
                
            /* Get list of managers */
            $managers = WaseManager::wlistActiveManagers($this->calendarid);
            /* Send to each manager on the list */
            foreach ($managers as $manager) {
                $bemail = $directory->getEmail($manager->userid);
                if ((($type == "remind" && $manager->remind) || ($type != "remind" && $manager->notify)) && (! in_array(strtolower($bemail), $sendto)) && ($manager->userid != '')) {
                    WaseUtil::Mailer($bemail, $subject, $msg, $shortheaders);
                    $sendto[] = strtolower($bemail);
                    $mc ++;
                }
            }
        } 
        
        /* Now handle students (AppFor) */
         
        // Determine TO address
        if (! $semail = $this->email)
            $semail = $directory->getEmail($this->userid);
            
        /* Get the user's sync preference */
        $syncpref = WasePrefs::getPref($this->userid, 'localcal');
        /* Do synchronization unless already done for this user */
        if (! in_array(strtolower($semail), $syncto)) {
             
            /* If student wants Google synchronization, go do it, unless already done. */
            $syncdone = false;
            $syncfail = '';
            if ($syncpref == 'google' && $type != 'remind') {
                if ($type == 'add' && ! $this->gid)
                    $syncdone = WaseGoogle::addApp($this, $block, $this->userid);
                if ($type == 'change')
                    $syncdone = WaseGoogle::changeApp($this, $block, $this->userid);
                if ($type == 'delete' && $this->gid)
                    $syncdone = WaseGoogle::delObj($this, $this->userid);
                    
                    /* Save synchronization done status */
                if ($syncdone)
                    $syncto[] = strtolower($semail);
                else
                    $syncfail = 'google';
                    
                // Reset the user's sync preference to 'ical'
                // if ($syncfail)
                    // WasePrefs::savePref($this->userid, 'localcal', 'ical');
            }
            
            /* If student wants Exchange synchronization, go do it, unless already done */
            $syncdone = false;
            if ($syncpref == 'exchange' && $type != 'remind') {
                if ($type == 'add'  && $syncdirect != 1)  // syncdirect = 1 means invitation already sent to the student.
                    $syncdone = WaseExchange::addApp($this, $block, $this->userid, $this->email);
                elseif ($type == 'change')
                    $syncdone = WaseExchange::changeApp($this, $block, $this->userid, $this->email);
                elseif ($type == 'delete')
                    $syncdone = WaseExchange::delObj($this, $this->userid);
                // Save synchronization done status 
                if ($syncdone)
                    $syncto[] = strtolower($semail);
                else
                    $syncfail = 'exchange';
                    
                // Reset the user's sync preference to 'ical'
                // if ($syncfail)
                    // WasePrefs::savePref($this->userid, 'localcal', 'ical');
            }
        }
        /* If students wants notice, OR synchroinizatioin failed, send it (check preference) */
        /* add check on syncpref with ical */
        if (($type == "remind" && $this->remind) || $type != "remind" || ($syncfail || ($syncpref == 'ical'))) {
            
            /* Add in appointment info. */
            if ($curapp)
                $msg = $msghead . '<br /><br /><i>Changed From</i>:<br /><br />' . $curapp->appHTML($block, $bemail, 'changefrom', 'student', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue) .
                    '<br /><br /><i>Changed To</i>:<br /><br />' . $this->appHTML($block, 'changeto', 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
            else
                $msg = $msghead . '<br /><br />' . $this->appHTML($block, $bemail, $type, 'student', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue);
                
            /* Let user know if sync failed. */
             if ($syncfail == 'google')
                $msg .= '<p>Unable to update your Google calendar. Go into ' . WaseUtil::getParm('SYSID') . '  preferences, set your sync preference to none, then back to Google.  This will allow you to re-authorize access to your Google calendar.';
            elseif ($syncfail == 'exchange')
                $msg .= '<p>Unable to update your Exchange (Outlook) calendar. This email contains an iCal attachment.  Opening this attachment should put the appointment on your Exchange calendar (if it is not there already).';
            
            // Sign the email
            $msg .= '<br /><br />---' . WaseUtil::getParm('SYSNAME');
            
            // Send the email, keep track of it being sent. 
            if (!in_array(strtolower($semail), $sendto)) {
                
                if (!in_array(strtolower($semail), $syncto) && $syncpref != 'none' && $type != 'remind') {
                    if (WaseUtil::IcalMailer($semail, $block->email, $subject, $msg, self::makeIcal($semail, WaseUtil::getParm('FROMMAIL'), $type), WaseUtil::getParm('FROMMAIL'), $type)) {
                        $sc ++;
                        $didemail = true;
                    }
                    else 
                        $didemail = false;
                }  
                if (!$didemail) {
                    if (WaseUtil::Mailer($semail, $subject, $msg, $shortheaders)) {
                        $sc ++;
                    }
                }
                
                /* If user requested a text message, send email to the user's text email address */
                if ($this->textemail)
                    WaseUtil::Mailer($this->textemail, $subject, $subject, $shortheaders); //01/24/19 - need to include $shortheaders
                $sc ++;
            }
        }
        
        /* Return the counts */
        
        return "$pc,$sc,$mc,$ec";
    }

    /**
     * Convert this appointment into a text block (for email).
     *
     *
     * @param WaseBlock $block
     *            pointer to owning WaseBlock
     *            
     * @param string $target
     *            to what role is the text being sent (owner, manager, student, etc.)
     *            
     * @return string the email text, worwrapped to 70 characters.
     *        
     *        
     */
    private function appText($block, $target = 'owner')
    {

        $ret = $block->APPTHING . ' with: ' . $block->userid . "\r\n" . '            name: ' . $block->name . "\r\n" . '          e-mail: ' . $block->email . "\r\n" .
            '       Telephone: ' . $block->phone . "\r\n" . '        location: ' . $block->location . "\r\n" . "\r\n";
        if ($this->userid)
            $ret .= $block->APPTHING.'  for: ' . $this->userid . "\r\n" . '            name: ' . $this->name . "\r\n";
        else
            $ret .=$block->APPTHING.'  for: ' . $this->name . "\r\n";
        $ret .= '          e-mail: ' . $this->email . "\r\n" . '       Telephone: ' . $this->phone . "\r\n" . "\r\n" . 'Date            : ' . WaseUtil::usDate(substr($this->startdatetime, 0, 10)) .
            'Start time      : ' . WaseUtil::AmPm(substr($this->startdatetime, 11, 5)) . "\r\n" . 'End time        : ' . WaseUtil::AmPm(substr($this->enddatetime, 11, 5)) . "\r\n" . "\r\n" .
            'Purpose         : ' . stripslashes($this->purpose) . 'Meeting Details : ' . stripslashes($this->venue) . "\r\n";
        
        /* Include optional text */
        if ($block->apptmsg)
            $ret .= 'Note            : The calendar owner has provided the following text.' . "\r\n" . stripslashes($block->apptmsg) . "\r\n";
        
        return wordwrap($ret, 70, "\r\n");
    }

    /**
     * Convert this appointment into an html block (for email).
     *
     *
     * @param WaseBlock $block
     *            pointer to owning WaseBlock
     * @param string $bemail
     *            the target email address
     * @param string $type
     *            whether a 'create', 'delete', etc.
     * @param string $target
     *            to what role is the text being sent (owner, manager, student, etc.)
     * @param string $cname ,$cemail,$cphone,$cstarttime,$cendtime,$cpurpose, $cvenue
     *            the original values for an appointment edit
     *            
     * @return string the html text, worwrapped to 70 characters.
     *        
     *        
     */
    private function appHTML($block, $bemail, $type, $target = 'owner', $cname, $cemail, $cphone, $cstarttime, $cendtime, $cpurpose, $cvenue)
    {
        $ret = '<table>';
        $ret .= '<tr><td>Appointment with:</td><td>' . $block->userid . '</td><td>&nbsp;</td></tr>' . '<tr><td>Name:</td><td>' . $block->name . '</td><td>&nbsp;</td></tr>';
        if ($block->email)
            $ret .= '<tr><td>E-mail:</td><td><a href="mailto:' . $block->email . '">' . $block->email . '</a></td><td>&nbsp;</td></tr>';
        $ret .= '<tr><td>Telephone:</td><td>' . $block->phone . '</td><td>&nbsp;</td></tr>' . '<tr><td>Location:</td><td>' . $block->location . '</td><td>&nbsp;</td></tr>' . '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
        if ($this->userid) {
            $ret .= '<tr><td>Appointment for:</td><td>' . $this->userid . '</td><td>&nbsp;</td></tr>' . '<tr><td>Name:</td><td>' . $this->name . '</td><td>';
            if ($type == 'changeto')
                $ret .= $cname;
            $ret .= '&nbsp;</td></tr>';
        } else {
            $ret .= '<tr><td>Appointment for:</td><td>' . $this->name . '</td><td>';
            if ($type == 'changeto')
                $ret .= $cname;
            $ret .= '&nbsp;</td></tr>';
        }
        if ($this->email) {
            $ret .= '<tr><td>E-mail:</td><td><a href="mailto:' . $this->email . '">' . $this->email . '</a></td><td>';
            if ($type == 'changeto')
                $ret .= $cemail;
            $ret .= '&nbsp;</td></tr>';
        }
        $ret .= '<tr><td>Telephone:</td><td>' . $this->phone . '</td><td>';
        if ($type == 'changeto')
            $ret .= $cphone;
        $ret .= '&nbsp;</td></tr>' . '<tr><td>Start Date:</td><td>' . date('D', mktime(2, 0, 0, substr($this->startdatetime, 5, 2), substr($this->startdatetime, 8, 2), substr($this->startdatetime, 0, 4))) . ', ' . substr($this->startdatetime, 5, 2) . '/' . substr($this->startdatetime, 8, 2) . '/' . substr($this->startdatetime, 0, 4) . '</td><td>&nbsp;</td></tr>';
        $ret .= '<tr><td>Start time:</td><td>' . WaseUtil::AmPm(substr($this->startdatetime, 11, 5)) . '</td><td>';
        if ($type == 'changeto')
            $ret .= $cstarttime;
        if (substr($this->startdatetime,0,10) != substr($this->enddatetime,0,10))
            $ret .= '&nbsp;</td></tr>' . '<tr><td>End Date:</td><td>' . date('D', mktime(2, 0, 0, substr($this->enddatetime, 5, 2), substr($this->enddatetime, 8, 2), substr($this->enddatetime, 0, 4))) . ', ' . substr($this->enddatetime, 5, 2) . '/' . substr($this->enddatetime, 8, 2) . '/' . substr($this->enddatetime, 0, 4) . '</td><td>&nbsp;</td></tr>';;
        $ret .= '<tr><td>End time:</td><td>' . WaseUtil::AmPm(substr($this->enddatetime, 11, 5)) . '</td><td>';
        if ($type == 'changeto')
            $ret .= $cendtime;
        $ret .= '&nbsp;</td></tr>';
        if ($this->purpose) {
            $ret .= '<tr><td>Purpose:</td><td>' . stripslashes($this->purpose) . '</td><td>';
            if ($type == 'changeto')
                $ret .= $cpurpose;
            $ret .= '&nbsp;</td></tr>';
        }
        if ($this->venue) {
            $ret .= '<tr><td>Meeting Details:</td><td>' . stripslashes($this->venue) . '</td><td>';
            if ($type == 'changeto')
                $ret .= $cvenue;
            $ret .= '&nbsp;</td></tr>';
        }
        
        $ret .= '</table>' . "\r\n";
        
        /* Include optional text */
        $apptmsg = trim($block->apptmsg);
        if ($apptmsg && ($type != 'changefrom'))
            $ret .= '<br />The calendar owner has provided the following text: <br />' . stripslashes($block->apptmsg) . '<br /><br />';
        
       
            
        /*
         * We no longer need to include sync information in the notification email because sync is done either separately or via an iCal attachment
         *
         * // Now append icalendar link for synchronization.
         * if ($type == 'add' || $type == 'changeto') {
         *
         * // We need to determine whether to point to the student or professor calendar for the subscription.
         *
         * if ($target == 'student')
         * $authid = $this->userid;
         * else
         * $authid = $block->userid;
         *
         *
         *
         * $ret .= '<ul><li>Subscribe my local calendar to ' . WaseUtil::getParm('SYSID') . ' (do this just once): <br />' . '<a href="' . 'webcal://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=LISTAPPS&authid=' . $authid . '">webcal://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=LISTAPPS&authid=' . $authid . '</a><br />This should cause your local calendaring appplication to automatically download all '.WaseUtil::getParm('APPOINTMENTS').' you make. You only need to do this once.';
         *
         * $ret .= '</li><li>Add '.WaseUtil::getParm('APPOINTMENT').' to local calendar: <a href="' . 'https://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=GETAPP&appid=' . $this->appointmentid . '&authid=' . $this->userid . '&target=' . $target . '">click here</a>.';
         *
         * $ret .= '</li><li>' . 'Add appointment to web calendar (e.g., Gmail) <a href="' . 'https://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=MAILAPP&appid=' . $this->appointmentid . '&authid=' . $this->userid . '&target=' . $target . '&toid=' . $bemail . '">click here</a>. This will cause an email to be sent to you which, when opened, will add the '.WaseUtil::getParm('APPOINTMENT').' to your web calendar.</li></ul></p>';
         * } elseif ($type == 'delete') {
         * $ret .= '<br /><br /><br /><p>' . 'If you added this appointment to your local calendar using the email or link you initially received from the system, and you would like to remove the '.WaseUtil::getParm('APPOINTMENT').' from your local calendar, do one of the following:';
         *
         * $ret .= '<ul><li> If you use a desktop calendaring program (such as Outlook or iCal or Entourage), click <a href="' . 'https://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=DELAPP&id=' . $this->appointmentid . '">here</a>. This should cause your calendaring application to open, and the '.WaseUtil::getParm('APPOINTMENT').' to be deleted from your local calendar.';
         * ;
         *
         * $ret .= '</li><li>' . 'If you use a browser-based calendaring program (such as gmail or Outlook Web Access), click <a href="' . 'https://' . $_SERVER['SERVER_NAME'] . '/controllers/ical.php?action=MAILDEL&id=' . $this->appointmentid . '&toid=' . $bemail . '">here</a> to have a special email sent to you which, when opened, will delete the '.WaseUtil::getParm('APPOINTMENT').' from your local calendar.</li></ul></p>';
         * }
         */
            
        /* Let the user know about sync options */
        if ($target == 'owner')
            $userid = $block->userid;
        else
            $userid = $this->userid;
        $sync = WasePrefs::getPref($userid, 'localcal');
        
        if ($sync == 'none')
            $ret .= 'Note: if you would like '.$block->APPTHINGS.' synced to your local calendar, login to ' . WaseUtil::getParm('SYSID') . ', go to Preferences, and set your local calendar preference.';
        
        // Include URL to all appointemts in WASE
        $ret .= '<br /><br /><a href="' . WaseUtil::urlheader() . '/views/pages/myappts.php">My ' . WaseUtil::getParm('SYSID') . ' ' . WaseUtil::getParm('APPOINTMENTS') . '</a>';
        
        return wordwrap($ret, 70, "\r\n"); 
    }

    /**
     * Determine if specified user owns this appointment.
     *
     *
     * This function determines if specified user owns the appointment (owns the
     * appointment or owns/manages the block).
     *
     * @param string $usertype
     *            'user' or 'guest'
     * @param string $authid
     *            specified userid
     *            
     * @return bool true if owner, false if not.
     *        
     *        
     */
    function isOwner($usertype, $authid)
    {
        /* Not owner if no credentials */
        if (! $authid)
            return false;
            
            /* If userid/email matches, then owner */
        if ($usertype != 'guest') {
            if (trim(strtoupper($authid)) == trim(strtoupper($this->userid)))
                return true;
        } else {
            if ($authid && ! $this->userid) {
                if (trim(strtoupper($authid)) == trim(strtoupper($this->email)))
                    return true;
            }
            elseif ($authid && $this->userid)
                if (trim(strtoupper($authid)) == trim(strtoupper($this->userid)))
                    return true;
        }
            
       
        
        /* If user owns the block, then they own this appointment. */
        try {
            if (! $block = new WaseBlock('load', array(
                'blockid' => $this->blockid
            )))
                return false;
            ;
        } catch (Exception $error) {
            return false;
        }
        
        return $block->isOwner($usertype, $authid);
    }
    
    /**
     * Determine if specified user can delete this appointment.
     *
     *
     * This function determines if specified user can delete this appointment.
     *
     * @param string $usertype
     *            'user' or 'guest'
     * @param string $authid
     *            specified userid
     *
     * @return bool true if can, false if not.
     *
     *
     */
    function canDeleteAppointment($usertype, $authid)
    {
        return $this->isOwner($usertype, $authid);
    }
    
    /**
     * Determine if specified user can edit this appointment.
     *
     *
     * This function determines if specified user can edit this appointment.
     *
     * @param string $usertype
     *            'user' or 'guest'
     * @param string $authid
     *            specified userid
     *
     * @return bool true if can, false if not.
     *
     *
     */
    function canEditAppointment($usertype, $authid)
    {
        return $this->isOwner($usertype, $authid);
    }

    /**
     * Determine if specified user can view this appointment.
     *
     * This function determines if specified user has the rights to view this appointment.
     *
     * @param string $usertype
     *            'user' or 'guest'
     * @param string $authid
     *            specified userid
     *            
     * @return bool true if viewable, false if not.
     *        
     *        
     */
    function isViewable($usertype, $authid)
    {
        
        /* First, if user is owner, then viewable. */
        if ($this->isOwner($usertype, $authid))
            return true;
            
            /* If owning block is viewable by this user, then so is appointment. */
        try {
            if (! $block = new WaseBlock('load', array(
                'blockid' => $this->blockid
            )))
                return false;
            ;
        } catch (Exception $error) {
            return false;
        }
        
        return $block->isViewable($usertype, $authid);
    }

    /**
     * This function returns an iCal stream for this appointment.
     *
     *
     * @param string $to
     *            the target attendee
     * @param string $from
     *            who made the appointment (default is WASE)
     *            
     * @return string the iCal stream.
     *        
     *        
     *        
     */
    function makeIcal($to, $from, $type = 'add')
    {
        // Make siure we have a sequence.
        if (!$this->sequence)
            $this->sequence = 0;
        
        $ret = "BEGIN:VCALENDAR\r\n"; 
        $ret .= "X-WR-CALNAME:" . WaseUtil::getParm('SYSID') . "\r\n";
        /* METHOD depends on whether adding, changing or deleting an appointment */
        if ($type == 'delete')
            $ret .= "METHOD:CANCEL\r\n";
        else 
            $ret .= "METHOD:REQUEST\r\n";
        $ret .= "PRODID:-//Princeton University/Web Appointment Scheduling Engine//EN\r\n";
        $ret .= "VERSION:2.0\r\n";
        $ret .= "BEGIN:VEVENT\r\n";
        // $ret .= 'ORGANIZER:MAILTO:' . $from . "\r\n";
        $ret .= 'ORGANIZER:MAILTO:' . $to . "\r\n";
        $ret .= 'ATTENDEE;RSVP=FALSE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED:MAILTO:' . $to . "\r\n";
        $ret .= "DTSTART:" . gmdate('Ymd\THis', WaseUtil::makeUnixTime($this->startdatetime)) . "Z\r\n";
        $ret .= "DTEND:" . gmdate('Ymd\THis', WaseUtil::makeUnixTime($this->enddatetime)) . "Z\r\n";
        $ret .= "DTSTAMP:" . gmdate('Ymd\THis') . "Z\r\n";
        $ret .= "UID:" . $this->uid . "\r\n";
        $ret .= "CATEGORIES:APPOINTMENT\r\n";
        
        $block = new WaseBlock('load', array(
            "blockid" => $this->blockid
        ));
        
        $ret .= "SUMMARY:Appointment for " . $this->name . " (" . $this->email . ") with " . $block->name . " (" . $block->email . ")\r\n";

        if ($this->venue)
            $ret .= "LOCATION:" . $block->location . "(" . $this->venue . ")\r\n";
        else
            $ret .= "LOCATION:" . $block->location . "\r\n";
        $ret .= "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . $this->purpose . "\r\n";
        $ret .= "CLASS:PUBLIC\r\n";
        $ret .= "SEQUENCE:" . (int) $this->sequence . "\r\n";
        if ($type == 'delete')
            $ret .= "STATUS:CANCELLED" . "\r\n";
        $ret .= "END:VEVENT\r\n";
        $ret .= "END:VCALENDAR\r\n";
        return $ret;
    }
    
    /**
     * This function returns the cancellation deadline as a string, or "reached" if it has passed.
     *
     *
     *
     * @return string the cancellation deadline as a string, or 'reached'.
     *
     *
     *
     */
    function candeadline() 
    {
        
        /* Read in the block object */
        $block = new WaseBlock('load', array(
            'blockid' => $this->blockid
        ));
        
        // If none, return that.
        if (!$block->candeadline)
            return 'none';
        
        // If reached, return that
        if ($block->candeadline_reached($this->startdatetime))
            return 'reached';
        
        // Compute start date/time minus deadline (in seconds)
        $limit = WaseUtil::makeUnixTime($this->startdatetime) - ($block->candeadline * 60);
        return date('D, n/m/y g:i a',$limit);
              
    }
        

    /**
     * This function returns an xml stream of this appointment's header values.
     *
     * @return string the xml stream.
     *        
     *        
     */
    function xmlApptHeaderInfo()
    {
        return $this->tag('appointmentid') . $this->tag('startdatetime') . $this->tag('enddatetime') . '<apptmaker>' . $this->tag('userid') . $this->tag('name') . 
        $this->tag('phone') . $this->tag('email') . '</apptmaker>' . $this->tag('blockid') . $this->tag('calendarid') . $this->tag('available') .
        '<candeadline>'.$this->candeadline().'</candeadline>';
    }
    
    
    /**
     * This function returns a json stream of this appointment's header values.
     *
     * @return string the xml stream.
     *
     *
     */
    function jsonApptHeaderInfo()
    {
        return $this->jtag('appointmentid') . ","  . $this->jtag('startdatetime') . ","  . $this->jtag('enddatetime') . ","   . '"apptmaker":{' . $this->jtag('userid') . ","   . $this->jtag('name') . ","   .
            $this->jtag('phone') . ","   . $this->jtag('email')  . '},' . $this->jtag('blockid') . ","   . $this->jtag('calendarid') . ","   . $this->jtag('available') . ","   .
        '"candeadline":"'.$this->candeadline().'"';
    }

    /**
     * This function returns an xml header stream with appointment maker masked out.
     *
     * @return string the xml stream.
     *        
     */
    function xmlMaskedApptHeaderInfo()
    {
        return $this->tag('appointmentid') . $this->tag('startdatetime') . $this->tag('enddatetime') . $this->tag('blockid') . $this->tag('calendarid') . $this->tag('available');
    } 
    
    /**
     * This function returns a json header stream with appointment maker masked out.
     *
     * @return string the xml stream.
     *
     */
    function jsonMaskedApptHeaderInfo() 
    {
        return $this->jtag('appointmentid') . ","   . $this->jtag('startdatetime') . ","  . $this->jtag('enddatetime') . ","   . 
            $this->jtag('blockid') . ","   . $this->jtag('calendarid') . ","   . $this->jtag('available');
    } 

    /**
     * This function returns an XML stream of appointment property values.
     *
     * @return string the xml stream.
     *        
     *        
     */
    function xmlApptInfo()
    {
        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        return $this->xmlApptHeaderInfo() . $this->tag('purpose') . $this->tag('venue') . $this->tag('remind') . $this->tag('textemail') . $this->tag('whenmade') . $this->tag('madeby') . '<madebyname>' . $directory->getName($this->madeby) . '</madebyname>' . $this->tag('uid') . $this->tag('sequence');
    }

    /**
     * This function returns an XML stream of appointment property values withg pp maker masked out.
     *
     * @return string the xml stream.
     *        
     */
    function xmlMaskedApptInfo()
    {
        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        return $this->xmlMaskedApptHeaderInfo() . $this->tag('purpose') . $this->tag('venue') . $this->tag('remind') . $this->tag('textemail') . $this->tag('whenmade') . $this->tag('madeby') . '<madebyname>' . $directory->getName($this->madeby) . '</madebyname>' . $this->tag('uid') . $this->tag('sequence');
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
            return '<' . $property . '>' . htmlspecialchars($this->{$property},ENT_NOQUOTES) . '</' . $property . '>';
    }
    
    
    /**
     * Return a json tag with the specified property name and value.
     *
     * @param string $property
     *            the tag property name.
     * @param string $val
     *            the tag value.
     *
     * @return string XML.
     */
    private function jtag($property, $val='') 
    {
        if (func_num_args() == 2)
            return '"' . $property . '":"' .$val . '"';
            else
                return '"' . $property . '":"' . htmlspecialchars($this->{$property},ENT_NOQUOTES) . '"';
    }
}
?>