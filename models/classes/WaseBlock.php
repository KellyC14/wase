<?php

/**
 * This class describes a block of available time in the WASE system.
 * 
 * Every block of available meeting time is represented by a WaseBlock.  Each block maps to a single record
 * in the WaseBlock database table.  
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseBlock
{
    
    /* Properties */
    
    /**
     *
     * @var int $blockid Database id of the WaseBlock block.
     */
    public $blockid;

    /**
     *
     * @var int $periodid Database id of the WasePeriod record (if any) .
     *      .. for recurring blocks.
     */
    public $periodid;

    /**
     *
     * @var int $seriesid Database id of the WaseSeries record (if any) .
     *      .. for recurring blocks.
     */
    public $seriesid;

    /**
     *
     * @var int $calendarid Database id of the WaseCalendar record.
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
     * @var string $userid Userid.
     */
    public $userid;

    /**
     *
     * @var string $name Name of user
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
     * @var string $startdatetime Starting date time of block (yyyy-mm-dd hh:mm:ss)
     */
    public $startdatetime;

    /**
     *
     * @var string $enddatetime Ending date time of block (yyyy-mm-dd hh:mm:ss)
     */
    public $enddatetime;

    /**
     *
     * @var string $slotsize Slot size in minutes.
     */
    public $slotsize;

    /**
     *
     * @var int $maxapps Maximum appointments per slot (0 means no maximum set).
     */
    public $maxapps;

    /**
     *
     * @var int $maxper Maximum appointments per person for a block (0 means no maximum set).
     */
    public $maxper;

    /**
     *
     * @var int $deadline Deadline for scheduling appointments, in minutes.
     */
    public $deadline;

    /**
     *
     * @var int $candeadline Deadline for cancelling appointments, in minutes.
     */
    public $candeadline;

    /**
     *
     * @var int $opening When block becomes available for scheduling, in minutes before startdatetime.
     */
    public $opening;

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
     * @var string $uid Ical UID.
     */
    public $uid;

    /**
     *
     * @var string $gid Google UID.
     */
    public $gid;

    /**
     *
     * @var string $eid Exchange UID.
     */
    public $eid;

    /**
     *
     * @var string $eck Exchange ChangeKey.
     */
    public $eck;

    /**
     *
     * @var int $sequence Update counter for generating iCal files.
     */
    public $sequence;

    /**
     *
     * @var bool $purreq This block requires that appointment purposes be specified.
     */
    public $purreq;

    /**
     *
     * @var string $lastchange date/time block was last changed
     */
    public $lastchange;
    
    /**
     *
     * @var string $NAMETHING The label used (singular) to define what this block manages (e.g., "Office Hour").
     */
    public $NAMETHING;
    
    /**
     *
     * @var string $NAMETHINGS The label used (plural) to define what this block manages (e.g., "Office Hours").
     */
    public $NAMETHINGS;
    
    /**
     *
     * @var string $APPTHING The label used (singular) to define what this block schedules ("Appointment").
     */
    public $APPTHING;
    
    /**
     *
     * @var string $APPTHINGS The label used (plural) to define what this block schedules ("Appointments").
     */
    public $APPTHINGS;
    
    
    /* Static (class) methods */
    
    /**
     * Look up a block in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $id
     *            Database id of the WaseBlock record.
     *            
     * @return array|false or false if not found.
     */
    static function find($id)
    {
        /* Get a database handle. */
        if (! WaseSQL::openDB())
            return false;
            
            /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE blockid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Get the entry into an associative array (there can only be 1). */
        $result = WaseSQL::doFetch($entry);
        
        /* Return the result */
        return $result;
    }

    /**
     * Locate all blocks that meet the designated criteria, and return the php resource, or NULL.
     *
     * @static
     *
     * @param array $arr
     *            Associative array containing WaseBlock selection criteria.
     *            
     * @return resource|null
     */
    static function select($arr)
    {
        /* Issue the select call and return the results */
        return WaseSQL::selectDB('WaseBlock', $arr);
    }

    /**
     * Return a list of blocks that meet the specified criteria.
     *
     * @static
     *
     * @param array $arr
     *            Associative array containing WaseBlock selection criteria.
     *            
     * @return WaseList list of matching WaseBlocks.
     */
    static function listMatchingBlocks($criteria)
    {
        return new WaseList(WaseSQL::buildSelect('WaseBlock', $criteria), 'Block');
    }

    /**
     * Return ordered a list of blocks that meet the specified criteria.
     *
     * @static
     *
     * @param array $arr
     *            Associative array containing WaseBlock selection criteria.
     * @param string $orderby
     *            SELECT 'ORDER BY' string.
     *            
     * @return WaseList list of matching WaseBlocks.
     */
    static function listOrderedBlocks($criteria, $orderby) 
    {
        return new WaseList(WaseSQL::buildOrderedSelect('WaseBlock', $criteria, $orderby), 'Block');
    }
    
    /* Obect Methods */
    
    /**
     * Create a WaseBlock.
     *
     * We have two constructors, one for a new block, one for an
     * existing block. In the former case, we fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database block table, and fill in/update the values as
     * per that table entry. In either case, we end up with a filed-in
     * block object.
     *
     * @param string $source
     *            specifies whether to load, update or create a block.
     * @param array $data
     *            associative array with the WaseBlock values.
     *            
     * @return WaseBlock The WaseBlock is built.
     */
    function __construct($source, $data)
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create' a block.
         * $data is an associative array of elements used when we need to create/update a block; for 'load', it contains just the blockid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /*
         * For load and update, find the block and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) { 
            // Grab the object from the cache, if there.
            if (WaseCache::exists('block'.$ndata['blockid']))
                $values = WaseCache::get('block'.$ndata['blockid']);
            // If the object doesn't exist, blow up.
            else {
                if (! $values = WaseBlock::find($ndata['blockid']))
                    throw new Exception('Block id ' . $ndata['blockid'] . ' does not exist', 14);
                // Cache the properties
                WaseCache::add('block'.$ndata['blockid'], $values);
            }

            // Load the data into the object. */
            WaseUtil::loadObject($this, $values);

        }
        
        /* Set defaults for unspecified values */
        if ($source == 'create' || $source == 'createnovalidate') {
            /* Defaults are inherited first from the series (if any) or from the calendar (if no series). */
            if (! $ndata['seriesid'])
				/* Get all of the calendar values */
				$defaults = WaseCalendar::find($ndata['calendarid']);
            else
                /* Get all of the series values */
                $defaults = WaseSeries::find($ndata['seriesid']);
                
                /* Get a list of all of the block properties */
            $properties = get_object_vars($this);
            
            /* Copy any unspecified values from the calendar or series */
            foreach ($defaults as $key => $value) {
                if (array_key_exists($key, $properties))
                    if (! array_key_exists($key, $ndata)) {
                        $ndata[$key] = $value;
                    }
            }
            
            /* Set directory defaults for still unspecified values (@Members get defaults from calendar/series)*/
            // Set @member name to be same as userid.
            if (substr($ndata['userid'],0,1) == '@') {
                if ($ndata['name'] == '' || ($source == 'create' || $source == 'createnovalidate'))
                    //$ndata['name'] = $defaults['name'];
                    $ndata['name'] = $ndata['userid'];
                if ($ndata['phone'] == '')
                    $ndata['phone'] =  $defaults['phone'];
                if ($ndata['email'] == '')
                    $ndata['email'] =  $defaults['email'];
                if ($ndata['location'] == '')
                    $ndata['location'] = $defaults['location'];
                if ($ndata['title'] == '')
                    $ndata['title'] = substr($ndata['userid'], 1);
            }
            else {
                if ($ndata['name'] == '')
                    $ndata['name'] = $directory->getName($ndata['userid']);
                if ($ndata['phone'] == '')
                    $ndata['phone'] = $directory->getPhone($ndata['userid']);
                if ($ndata['email'] == '')
                    $ndata['email'] = $directory->getEmail($ndata['userid']);
                if ($ndata['location'] == '')
                    $ndata['location'] = $directory->getOffice($ndata['userid']);
            }
            
            // Set labels to defaults if unspecified
            if (array_key_exists('APPTHING', $ndata) && $ndata['APPTHING'] == '')
                $ndata['APPTHING'] = $defaults['APPTHING'];
            if (array_key_exists('APPTHINGS', $ndata) && $ndata['APPTHINGS'] == '')
                $ndata['APPTHINGS'] = $defaults['APPTHINGS'];
            if (array_key_exists('NAMETHING', $ndata) &&  $ndata['NAMETHING'] == '')
                $ndata['NAMETHING'] = $defaults['NAMETHING'];
            if (array_key_exists('NAMETHINGS', $ndata) &&  $ndata['NAMETHINGS'] == '')
                $ndata['NAMETHINGS'] = $defaults['NAMETHINGS'];
                    
            /* Now set any remaining unspecified values to the default. */
            if ($ndata['makeaccess'] == '')
                $ndata['makeaccess'] = 'limited'; 
            if ($ndata['viewaccess'] == '')
                $ndata['viewaccess'] = 'limited';
            if (! array_key_exists('maxapps', $ndata))
                $ndata['maxapps'] = 0;
            if (! array_key_exists('maxper', $ndata))
                $ndata['maxper'] = 0;
            if (! array_key_exists('remind', $ndata))
                $ndata['remind'] = 1;
            if (! array_key_exists('remindman', $ndata))
                $ndata['remindman'] = 1;
            if (! array_key_exists('remindmem', $ndata))
                $ndata['remindmem'] = 1;
            if (! array_key_exists('notify', $ndata))
                $ndata['notify'] = 1;
            if (! array_key_exists('notifymem', $ndata))
                $ndata['notifyman'] = 1;
            if (! array_key_exists('notifyman', $ndata))
                $ndata['notifymem'] = 1;
            if (! array_key_exists('showappinfo', $ndata))
                $ndata['showappinfo'] = 0;
            if (! array_key_exists('purreq', $ndata))
                $ndata['purreq'] = 0;
            if (! array_key_exists('available', $ndata))
                $ndata['available'] = 1;
            if (! array_key_exists('deadline', $ndata))
                $ndata['deadline'] = 0;
            if (! array_key_exists('candeadline', $ndata))
                $ndata['candeadline'] = 0;
            if (! array_key_exists('opening', $ndata))
                $ndata['opening'] = 0;
            if (! array_key_exists('slotsize', $ndata))
                $ndata['slotsize'] = 0;
            if (! array_key_exists('sequence', $ndata))
                $ndata['sequence'] = 0;
            if (! array_key_exists('NAMETHING', $ndata) || $ndata['NAMETHING'] == "")
                $ndata['NAMETHING'] = WaseUtil::getParm('NAME');
            if (! array_key_exists('NAMETHINGS', $ndata) || $ndata['NAMETHINGS'] == "")
                $ndata['NAMETHINGS'] = WaseUtil::getParm('NAMES');
            if (! array_key_exists('APPTHING', $ndata) || $ndata['APPTHING'] == "")
                $ndata['APPTHING'] = WaseUtil::getParm('APPOINTMENT');
            if (! array_key_exists('APPTHINGS', $ndata) || $ndata['APPTHINGS'] == "")
                $ndata['APPTHINGS'] = WaseUtil::getParm('APPOINTMENTS');   
            //12/23/19 add defaults for periodid and seriesid
            if (! array_key_exists('periodid', $ndata))
                $ndata['periodid'] = 0;
            if (! array_key_exists('seriesid', $ndata))
                $ndata['seriesid'] = 0;
                    
            //if (! $ndata['uid'])
                // $ndata['uid'] = date('Ymd\TGis') . "-" . rand(1000, 1000000) . 'block@' . $_SERVER['REQUEST_URI'];
                // $ndata['uid'] = date('Ymd\TGis') . "-" . uniqid("",true) . 'block@' . $_SERVER['REQUEST_URI'];
        }
        
        // Test for validity of updates (and modify updates if needed -- updateCheck may modify ndata, for example to reset start/end dates)
        if  ($source == 'update' && $errors = $this->updateCheck($ndata))
            throw new Exception($errors, 17);
        
        // Load ndata into the block
        WaseUtil::loadObject($this, $ndata);

        // If this is an unslotted block, slotsize may be zero, in which case we reset it to the blocksize
        if ($this->slotsize == 0)
            $this->slotsize = $this->blocksize();
 
        // Validate values for create
        if ($source == 'create' &&  $errors = $this->validate())         
            throw new Exception($errors, 17);
      
    }

    /**
     * Save a WaseBlock.
     *
     * This function writes this block data out to the database.
     * The one argument specifies whether this is a new block or not.
     *
     * @param string $type
     *            specifies whether saving the block or updating the block.
     * @param boolean $num
     *            if creating/updating a series, specifies the number of this block in the series.
     *            
     * @return int The WaseBlock database record id.
     */
    function save($type, $num=0)
    {

        /* Read in the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        
        // If creating or updating a block, check for overlaps  
        if ($error = $this->overlap_check($type, $calendar))
            throw new Exception($error, 17);
    
        
        /* Set date/time block was created/updated */
        $this->lastchange = date('Y-m-d H:m:s');
        
        // Set the UID (iCal unique identifier: note:  every instance of a recurring series has the same UID and a unique recurrence-id.
        $uniqueid = date('Ymd\TGis') . "-" . uniqid("",true) . 'block@' . $_SERVER['REQUEST_URI'];
        if ($type == 'create') {
            if ($this->periodid ) {
                if ($num == 1) {
                    $this->uid = $uniqueid;                  
                    $_SESSION['icaluid'] = $this->uid;
                }
                else {
                    if ($_SESSION['icaluid']) {
                        $this->uid = $_SESSION['icaluid'];
                    }
                    else 
                        $this->uid = $uniqueid;
                }
            }
            else
                $this->uid = $uniqueid;
        }
        
      
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            /*
             * Save flag about newly available slots
             * $newslots = true;
             */
            
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify a blockid */
                if ($key != 'blockid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= '`' . $key . '`';
                    //12/23/19 - add strlen check to handle 0
                    if (($vallist) || (strlen($vallist)))
                        $vallist .= ',';
                    $vallist .= WaseSQL::sqlSafe($value);
                    //WaseMsg::dMsg('DBG','Info',"key[$key]value[$value]varlist[$varlist]vallist[$vallist]");
                }
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseBlock (' . $varlist . ') VALUES (' . $vallist . ')';
        }  /* Else create the update list */
        else {
            $sql = '';
            foreach ($this as $key => $value) {
                if ($sql)
                    $sql .= ', ';
                $sql .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
            }
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET ' . $sql . ' WHERE blockid=' . $this->blockid;
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);
            
        /* Get the (new) id and save it */
        if ($type == 'create') {
            $this->blockid = WaseSQL::insert_id();
        }
         
        /* Sync the block if user wants that */
        $syncpref = WasePrefs::getPref($this->userid, 'localcal');
        $syncdone = false;
        $syncfail = '';
        
        if ($syncpref == 'google') {
            if ($type == 'create')
                $syncdone = WaseGoogle::addBlock($this);
            else
                $syncdone = WaseGoogle::changeBlock($this);
            // If sync fails, reset sync preference to none.
            if (!$syncdone) {
                $syncfail = 'google';
                // WasePrefs::savePref($this->userid, 'localcal', 'none');
            }
        } elseif ($syncpref == 'exchange') {
            if ($type == 'create')
                $syncdone = WaseExchange::addBlock($this);
            else
                $syncdone = WaseExchange::changeBlock($this);
            // If sync fails, reset sync preference to none.
            if (!$syncdone) {
                $syncfail = 'exchange';
                // WasePrefs::savePref($this->userid, 'localcal', 'none');
            }
        } 
        if (($syncpref == 'ical' || $syncfail) && $num<=1) {           
            if ($type == 'create')
            {
                $subject = 'New ' . WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block(s)';
                $ical = $this->makeIcal($this->email, WaseUtil::getParm('FROMMAIL'), $type, $num);
                $body = "<html><head></head><body>";
                if ($this->seriesid)
                    $body .= WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Blocks added for ' . $this->userid . ' starting from ';
                else
                    $body .= WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block added for ' . $this->userid . ' from ';
            }
            else {
                $subject = 'Changed ' . WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block';
                $ical = $this->makeIcal($this->email, WaseUtil::getParm('FROMMAIL'), $type, $num);
                if ($this->seriesid)
                    $body = WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Blocks changed for ' . $this->userid . ' starting from ';
                else
                    $body = WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block changed for ' . $this->userid . ' from ';
            }
            $body .= 
                substr($this->startdatetime, 5, 2) . '/' . substr($this->startdatetime, 8, 2) . '/' . substr($this->startdatetime, 0, 4) . ' ' . WaseUtil::AmPm(substr($this->startdatetime, 11, 5)) . ' to ' .
                substr($this->enddatetime, 5, 2) . '/' . substr($this->enddatetime, 8, 2) . '/' . substr($this->enddatetime, 0, 4) . ' ' .  WaseUtil::AmPm(substr($this->enddatetime, 11, 5)) ; 
            /* Let user know if sync failed. */
             
            if ($syncfail == 'google')
                $body .= '<p>Unable to update your Google calendar. Go into ' . WaseUtil::getParm('SYSID') . '  preferences, set your sync preference to none, then back to Google.  This will allow you to re-authorize access to your Google calendar.';
            elseif ($syncfail == 'exchange')
                $body .= '<p>Unable to update your Exchange (Outlook) calendar. ';
            
            
            $body .= '<br /><br />NOTE: This email contains an iCal attachment.  Try opening this attachment (if it has not already happened) if you want to upload this Block to your local calendar. ' .
            'If that does not work, you will have to manually add the block time(s) to your local calendar.  Go to Preferences in ' . WaseUtil::getParm('SYSID') . ' for alternative ' .
            'ways to synchronize ' . WaseUtil::getParm('SYSID') . ' with your local calendar.' .
            '<br /><br />---' . WaseUtil::getParm('SYSNAME');
            
            $body .= '</body></html>';

            WaseUtil::IcalMailer($this->email, 'noreply', $subject, $body, $ical, WaseUtil::getParm('FROMMAIL'), $type);
            
              
        }
        
        /* Now update the database entry */
        if ($syncdone)
            WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET gid=' . WaseSQL::sqlSafe($this->gid) . ', eid=' . WaseSQL::sqlSafe($this->eid) . ', eck=' . WaseSQL::sqlSafe($this->eck) . ' WHERE blockid=' . $this->blockid);
        
        /* Notify users on the wait list that new blocks are available. */
        if (WaseUtil::getParm('WAITLIST')) {
            if ($calendar->waitlist) {
                if ($this->freeSlots()) {
                    WaseWait::notifyForCalendarAndWindow($this->calendarid, $this->blockid, $this->startdatetime, $this->enddatetime);
                }
                /* Purge the wait list of matching entries */
                WaseWait::purgeWaitListWindow($this->calendarid, $this->startdatetime, $this->enddatetime);
            }
        }
        
        // Update the cache
        WaseCache::add('block'.$this->blockid, get_object_vars($this)); 
        
        return $this->blockid;
    }

    /**
     * Validate a WaseBlock.
     *
     * This function validates the data in this WaseBlock.
     *
     * @return string|null error message string.
     */
    function validate()
    {
        /* Assume no errors (we will pass this string back). */
        $errors = '';
        
        /* userid */
        if (! $this->userid)
            $errors .= 'userid required; ';
            
            /* Read in the calendar */
        if (! ($calendar = WaseCalendar::find($this->calendarid)))
            $errors .= 'Invalid calendarid: ' . $this->calendarid . '; ';
            
            /* Read in the series (if there is one) */
        if ($this->seriesid)
            if (! ($series = WaseSeries::find($this->seriesid)))
                $errors .= 'Invalid seriesid: ' . $this->seriesid . '; ';
        
        if (($this->userid != $calendar['userid']) && ! WaseManager::isManager($calendar['calendarid'], $this->userid, '') && ! WaseMember::isMember($calendar['calendarid'], $this->userid))
            $errors .= 'Block userid (' . $this->userid . ') must match calendar userid (' . $calendar['userid'] . ') or a calendar member userid;';
            
            /* Block duration must be positive. */
        if ($this->blocksize() <= 0)
            $errors .= 'End time must be > start time; ';
            
            /* startdatetime and enddatetime */
        if (! $temp = WaseUtil::checkDateTime($this->startdatetime))
            $errors .= 'Invalid start date and time: ' . $this->startdatetime . '; ';
        else
            $this->startdatetime = $temp;
        if (! $temp = WaseUtil::checkDateTime($this->enddatetime))
            $errors .= 'Invalid end date and time: ' . $this->enddatetime . '; ';
        else
            $this->enddatetime = $temp;
            
            /* slotsize */
        if ($this->slotsize) {
            if (! is_numeric($this->slotsize))
                $errors .= 'Invalid value for slot size: ' . $this->slotsize . '; ';
            elseif ($this->slotsize > $this->blocksize())
                $errors .= 'Slot size must be less than or equal to block size. ';
            elseif ($this->blocksize() % $this->slotsize)
                $errors .= 'Slot size value must evenly divide the block duration; ';
        } else
            $this->slotsize = $this->blocksize();
            
            /* maxapps */
        if (! is_numeric($this->maxapps))
            $errors .= 'maxapps must be an integer; ';
            
            /* maxper */
        if (! is_numeric($this->maxper))
            $errors .= 'maxper must be an integer; ';
            /* maxper cannot be greater than max number of possible appopintments */
        if ($this->maxper) {
            if ($this->maxper > $this->slots())
                $errors .= 'maxper (max appointments per user per block) cannot be greater than total number of slots in a block; ';
        }
        
        /* deadline */
        if (! is_numeric($this->deadline))
            $errors .= 'deadline must be an integer; ';
        if (! is_numeric($this->candeadline))
            $errors .= 'candeadline must be an integer; ';
        if (! is_numeric($this->opening))
            $errors .= 'opening must be an integer; ';
            
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
            
            /* remind, remindman, remindmem, showappinfo, purreq */
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

        return $errors;
             
    }

    /**
     * Validate updates to this block.
     *
     * This function checks to see if proposed changes to the block violate any business rules.
     *
     * @param array $ndata
     *            associative array of proposed updates to the block.
     *            NOTE:  this array may be modified, and hence is passed by reference.
     *            
     * @return string|null error message string.
     */
    function updateCheck(&$ndata)
    {
        // Set flag for iCal sequence update required
        $seqinc = false;
        
        if (array_key_exists('userid', $ndata) && ($ndata['userid'] == ''))
            return 'Please notify support staff of an attempt to update a block with a null userid.'; 
        if (!$this->userid)
            return 'Please notify support staff of an attempt to update a block with a blank userid.'; 
        if (array_key_exists('calendarid', $ndata) && ($ndata['calendarid'] != $this->calendarid))
            return 'Cannot assign block to a new calendar';
        if (array_key_exists('seriesid', $ndata) && ($ndata['seriesid'] != $this->seriesid))
            return 'Cannot assign block to a new series';
        if (array_key_exists('periodid', $ndata) && ($ndata['periodid'] != $this->periodid))
            return 'Cannot assign block to a new period';
        if (array_key_exists('slotsize', $ndata) && ($ndata['slotsize'] != $this->slotsize)) {
            /* Cannot change slot size if appointments exist, but allow slotsize=0 (this will be reset to the blocksize) */
            $apps = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE blockid = ' . WaseSQL::sqlSafe($this->blockid), 'Appointment');
            if ($apps->entries() && $ndata['slotsize'] != 0)
                return 'Cannot change slot size when block has appointments scheduled.';
        }
        
        /*
         * If any appointments are scheduled, we have to be careful about resetting slot size or start date/time or end date/time to make sure we don't wipe out any appointments.
         * 
         * We also have to allow changes to start/end times, but not to dates, for recurring blocks. NO!  We now allow this (changes to date or time).
         * 
         */
        
        if (array_key_exists('startdatetime', $ndata)) { 
            $seqinc = true;
            if (!$this->seriesid)
                $newstart = $ndata['startdatetime'];
            else {
                // list($ndate,$ntime) = explode(' ',$ndata['startdatetime']);
                // list($cdate,$ctime) = explode(' ',$this->startdatetime);
                //$newstart = $cdate.' '.$ntime;
                // $ndata['startdatetime'] = $newstart;
                $newstart = $ndata['startdatetime'];
            }
        }
        else
            $newstart = $this->startdatetime;
        
        if (array_key_exists('enddatetime', $ndata)) {
            $seqinc = true;
         if (!$this->seriesid)
                $newend = $ndata['enddatetime'];
            else {
                //list($ndate,$ntime) = explode(' ',$ndata['enddatetime']);
                //list($cdate,$ctime) = explode(' ',$this->enddatetime);
                //$newend = $cdate.' '.$ntime;
                //$ndata['enddatetime'] = $newend;
                $newend = $ndata['enddatetime'];
            }
        }
        else
            $newend = $this->enddatetime;
        
        // Increment sequence number if changing start/end date/time
        if ($seqinc)
            $this->sequence++;
            
        /* If any appointments start before new start date/time or end after new date date/time, reject */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (startdatetime < ' . WaseSQL::sqlSafe($newstart) . ' OR enddatetime > ' . WaseSQL::sqlSafe($newend) . ') AND blockid = ' . WaseSQL::sqlSafe($this->blockid);
        
        $apps = new WaseList($select, 'Appointment');
        if ($apps->entries())
            return 'Change to block start and end times conflict with existing appointments.';
        
        return $this->overlap_check('update');
        
    }
    
    /**
     * Check for block conflicts.
     * 
     * This function determines if the proposed block (new or updated) conflicts with any existing blocks.
     * 
     * @param string $type
     *          'create' or 'update'
     *          
     * @param WaseCalendar $calendar
     *          owning calendar
     *          
     * @returns string | null
     *          If error, the error string, else null
     */
    function overlap_check($type, $calendar = null) {
        


        /* Read in the calendar */
        if (!$calendar)
            $calendar =  new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        
        // Build Qquery string to determine if conflcits exist.
        $query = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE (' . 'startdatetime < ' . WaseSQL::sqlSafe($this->enddatetime) . ' AND ' .
            'enddatetime > ' . WaseSQL::sqlSafe($this->startdatetime) . ' AND ' . 'userid = ' . WaseSQL::sqlSafe($this->userid);
    
        // Exclude this block if updating
        if ($type == 'update')
            $query .= ' AND blockid != ' . $this->blockid;
        
        // Only check this calendar if overlap with other calendars is ok.
        if ($calendar->overlapok)
            $query .= ' AND calendarid=' . $this->calendarid;
    
        $query .= ')';

        $allready = WaseSQL::doQuery($query);
        
        
        $msg = '';
    
        if ($allready) {
            if (WaseSQL::num_rows($allready) > 0) {
                if ($type == 'update')
                    $msg .= 'Proposed block ' . $this->blockid . ' ';
                else
                    $msg .= 'Block ';
                
                $msg .= 'start/end date and times conflict with existing block(s): ';
                
                while ($matchblock = WaseSQL::doFetch($allready)) {                                   
                    if ($matchblock['calendarid'] == $this->calendarid)
                        $msg .= ' on this calendar: id ' . $matchblock['blockid'] . ' at ' . WaseUtil::datetimeToUS($matchblock['startdatetime']) . ' to ' . WaseUtil::datetimeToUS($matchblock['enddatetime']) . '; ';
                    else {
                        $matchcal = WaseCalendar::find($matchblock['calendarid']);
                        $msg .= ' on calendar with title "' . $matchcal['title'] . '": at ' . WaseUtil::datetimeToUS($matchblock['startdatetime']) . ' to ' . WaseUtil::datetimeToUS($matchblock['enddatetime']) . '; ';
                    }
                }
            }
        }
        
        return $msg;
          
    }
    

    /**
     * Delete this block.
     *
     * This function deletes the database record for this block.
     * Any appointments made in this block are also cancelled and deleted.
     *
     * @param string $canceltext
     *            text for appointment cancellation email.
     * @param boolean $num
     *            if a series, the block number being deleted (zero if an instance).
     *            
     * @return void
     */
    function delete($canceltext, $num)
    {
        
        // Deletes require an increment of the iCal sequence number.
        $this->sequence++;
        
        /* First, cancel any appointments */
        $this->cancelApps($canceltext);
        
        /* Save the deleted block data for later delivery as an Ical CANCEL */
        
        /*
         * First, compute the DTSTART
         * $dtstart = gmdate('Ymd\THis\Z',mktime(substr($this->starttime,0,2),substr($this->starttime,3,2),0,substr($this->date,5,2),substr($this->date,8,2),substr($this->date,0,4)));
         */
        /*
         * Now write out the deleted appointment data
         * WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.waseDeleted (calendarid,sequence,uid,dtstart) VALUES (' . WaseSQL::sqlSafe($this->calendarid) . ',' . WaseSQL::sqlSafe($this->sequence + 1) . ',' . WaseSQL::sqlSafe($this->uid) . ',' . WaseSQL::sqlSafe($dtstart) . ');');
         */
        
        /* Delete this block */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE blockid=' . $this->blockid . ' LIMIT 1');
        
        /* Sync the block if user wants that */
        $syncpref = trim(WasePrefs::getPref($this->userid, 'localcal'));
        $syncfail = '';
        $body = '<html><head></head><body>';
        
        if ($syncpref == 'google') {
            if (!$syncdone = WaseGoogle::delObj($this, $this->userid))
                $syncfail = 'google';
        }
        elseif ($syncpref == 'exchange') {
            if (!$syncdone = WaseExchange::delObj($this, $this->userid))
                $synfcail = 'exchange';
        }
        elseif (($syncpref == 'ical'  || $syncfail) && $num<=1) {
            $subject = 'Deleted ' . WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block(s)';
            $ical = $this->makeIcal($this->email, WaseUtil::getParm('FROMMAIL'), 'delete', $num);
            if (!$this->seriesid)
                $body = WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Block deleted for ' . $this->userid . ' from ';
            else
                $body = WaseUtil::getParm('SYSID') . ' ' . $this->APPTHING . ' Blocks deleted for ' . $this->userid . ' starting from ' .
            
            $body .= 
                    substr($this->startdatetime, 5, 2) . '/' . substr($this->startdatetime, 8, 2) . '/' . substr($this->startdatetime, 0, 4) . ' ' . WaseUtil::AmPm(substr($this->startdatetime, 11, 5)) . ' to ' .
                    substr($this->enddatetime, 5, 2) . '/' . substr($this->enddatetime, 8, 2) . '/' . substr($this->enddatetime, 0, 4) . ' ' . WaseUtil::AmPm(substr($this->enddatetime, 11, 5)) ;
           
            /* Let user know if sync failed. */
            if ($syncfail == 'google')
                $body .= '<p>Unable to update your Google calendar.  Please go into ' . WaseUtil::getParm('SYSID') . ' preferences and reset your sync preference to Google if you want your blocks to display in your Google calendar.';
            elseif ($syncfail == 'exchange')
                $body .= '<p>Unable to update your Exchange (Outlook) calendar.  Please go into ' . WaseUtil::getParm('SYSID') . ' preferences and reset your sync preference to Outlook if you want your blocks to display in your Exchange calendar.';
             
            $body .= '<br /><br />NOTE: This email contains an iCal attachment.  Try opening this attachment (if it has not already happened) if you want to remove this Block from your local calendar. ' .
                'If that does not work, you will have to manually remove the block.  Got to Preferences in ' . WaseUtil::getParm('SYSID') . ' for alternative ' .
                'ways to synchronize ' . WaseUtil::getParm('SYSID') . ' with your local calendar';
            $body .= '<br /><br />---' . WaseUtil::getParm('SYSNAME');
            
            $body .= '</body></html>';
            
            // Now send out the email with the iCal; attachment
            WaseUtil::IcalMailer($this->email, 'noreply', $subject, $body, $ical, WaseUtil::getParm('FROMMAIL'), 'delete');
        }
        
        // Update the cache
        WaseCache::remove('block'.$this->blockid);
    }

    /**
     * Cancel appointments for this block.
     *
     * This function cancels any appointments for this block, and sends
     * email (if necessary).
     *
     * @param string $canceltext
     *            text for appointment cancellation email.
     *            
     * @return void
     */
    function cancelApps($canceltext)
    {
        
        /* First, create list of appointments for this block */
        $applist = new WaseList(WaseSQL::buildSelect('WaseAppointment', array(
            'blockid' => $this->blockid
        )), 'Appointment');
        
        /* Now go through the appointments and cancel them. */
        foreach ($applist as $app) {
            $app->delete($canceltext, $this);
            unset($app);
        }
        unset($applist);
    }

    /**
     * Can specified user view this block?
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *            
     * @return bool true if user can view, else false.
     */
    function isViewable($usertype, $authid)
    {
        /* First, if user is owner, then viewable. */
        if ($this->isOwner($usertype, $authid))
            return true;
            
        // If the user is a member of the calendar of which the block owner is a member, then it is viewable by this member
        // (members can see each other's blocks).
        if ($this->isAlsoMember($usertype, $authid))
            return true;
        
        /* If not available, then not viewable */
        if (! $this->available)
            return false;
            
        /* Select on view access type */
        switch ($this->viewaccess) {
            
            /* For open block, anyone can view it */
            case 'open':
                return true;
                break;
            
            /* For private block, must be owner or manager (we already tested for that) */
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
                // Alow guests to be in authroized user list
                // if ($usertype == 'guest')
                //    return false;
                if (in_array($authid, explode(',', $this->viewulist)))
                    return true;
                if (WaseUtil::IsUserInGroup(explode(',',$this->viewglist),$authid))
                    return true;
                if (WaseUtil::doesUserHaveStatus(explode(',',$this->viewslist),$authid))
                    return true; 
                return WaseUtil::IsUserInCourse(explode(',', $this->viewclist), $authid);
               
                break;
        }
    }
    
    
    /**
     * Can specified user make an appointment in this block?
     *
     * This function determines if the specified user can make an appointment. If so,
     * it returns an empty string. If not, it returns the reason why not. The optional curapp
     * field is used if editing an appointment ... it points to the current appointment, which
     * is ignored in testing for appointment conflicts.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     * @param string $startdatetime
     *            optionaly, starting date and time for the appointment.
     * @param string $enddatetime
     *            optionaly, ending date and time for the appointment.
     * @param string $curapp
     *            optionaly, pointer to WaseAppointment if user is changing an existing appointment.
     *            
     * @return string reason why not, or null if allowed.
     */
    function isMakeable($usertype, $authid, $startdatetime = '', $enddatetime = '', $curapp = '')
    {
        $object_value=is_object($curapp);
        //WaseMsg::dMsg('DBG','Info',"isMakeble-authid[$authid]startdatetime[$startdatetime]enddatetime[$enddatetime");
        //WaseMsg::dMsg('DBG','Info',"isMakeable-object_value[$object_value]");
        $jc_authid="";
        $jc_startdatetime="";
        $jc_enddatetime="";
        if($object_value) {
            $jc_authid=$curapp->userid;
            $jc_startdatetime=$curapp->startdatetime;
            $jc_enddatetime=$curapp->enddatetime;
        }
        //WaseMsg::dMsg('DBG','Info',"isMakeble-jc_authid[$jc_authid]jc_startdatetime[$jc_startdatetime]enddatetime[$jc_enddatetime");
        // Owner can always make an appointemnt
        if ($this->isOwner($usertype, $authid))
            return ''; 
                
        // Start with block-level tests.
        
        /* If block unavailable, disallow */
        if (! $this->available)
            return 'Block of time is unavailable';
        
        /* If no appointment slots available, disallow */
        if (! $this->freeSlots())
            if(($authid != "") && ($authid == $jc_authid) && ($startdatetime == $jc_startdatetime) && ($enddatetime == $jc_enddatetime)) {
                //this slot is the cur app slot
                WaseMsg::dMsg('DBG','Info',"isMakeble-no freeslots but slot is cur app");
            } else {
                return 'All '.$this->APPTHING.' slots already taken.';
            }

          
        /* If in the past, disallow */ 
        if (WaseUtil::beforeNow($this->enddatetime)) {
            return 'Cannot make '.$this->APPTHING. '; block is in the past.';
        }
        
        /* If deadline set, check if it has passed. */
        
        if ($this->deadline_reached($this->startdatetime, $this->deadline))
            return 'Scheduling deadline has passed.';
            
        /* If opening set, make sure it has been reached. */
        if (!$this->opening_reached($this->startdatetime)) {
            return 'Scheduling opening has not been reached.';
        }
        
        /* Select on make access type */
        switch ($this->makeaccess) {
            
            /* For open block, anyone can view it */
            case 'open':
                break;
                
                /* For private block, must be owner or manager (we already tested for that) */
            case 'private':
                return 'Access to this time period is restricted.';
                break;
                
                /* For limited, must have a uerid (any userid). */
            case 'limited':
                if ($usertype == 'guest')
                    return 'Access to this time period is restricted.';
                    break;
                    
            /* For restricted access, must be in user list or course list. */
            case 'restricted':
                //if ($usertype == 'guest')
                //    return 'Access to this time period is restricted.';
                $inulist = false; $inclist = false; $inglist = false;
                
                if (in_array($authid, explode(',', $this->makeulist))) 
                    break;
                if (WaseUtil::IsUserInCourse(explode(',', $this->makeclist), $authid))
                    break;
                if (WaseUtil::IsUserInGroup(explode(',', $this->makeglist), $authid))
                    break;
                if (WaseUtil::doesUserHaveStatus(explode(',', $this->makeslist), $authid))
                        break;
                    
                return 'Access to this time period is restricted.';
                    
                break;
        }
         
        
        // Compute total and user apps for the block
       
        $userappsforblock = $this->blockAppsMade($usertype, $authid, $curapp);
        
        $appsforblock = $this->blockAppsMade('', '', $curapp);
        
        // Check for total and  per-user block limit being exceeded. */
        if ($this->maxper) {
            if ($userappsforblock >= $this->maxper)
                return 'User appointment scheduling limit for this block has been reached.';
        }
        
        if ($userappsforblock >= $this->slots())
            return 'Appointment scheduling limit for this block has been reached.';
        
        // See if there is a dummy app for a single-slot block
        if ($this->slotsize == $this->blocksize()) {
            $dummies = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (blockid = ' . $this->blockid . ' AND `available` = 0)','Appointment');
            if ($dummies->entries() != 0)
                return 'Block is unavailable';
        }
            
        // Now check slot limits (if looking at a slot)
        if ($startdatetime)
            return $this->slotIsMakeable($usertype, $authid, $startdatetime, $enddatetime, $curapp);
                        
            
        /* All tests have passed. */
        return '';
    }

 
    
    /**
     * Can specified user make an appointment in this slot?
     *
     * This function determines if the specified user can make an appointment. If so,
     * it returns an empty string. If not, it returns the reason why not. The optional curapp
     * field is used if editing an appointment ... it points to the current appointment, which
     * is ignored in testing for appointment conflicts.
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     * @param string $startdatetime
     *            optionaly, starting date and time for the appointment.
     * @param string $enddatetime
     *            optionaly, ending date and time for the appointment.
     * @param string $curapp
     *            optionaly, pointer to WaseAppointment if user is changing an existing appointment.
     *
     * @return string reason why not, or null if allowed.
     */
    function slotIsMakeable($usertype, $authid, $startdatetime, $enddatetime, $curapp = '')
    {
        // Owner can always make an appointemnt
        if ($this->isOwner($usertype, $authid)) 
            return '';
        
        if (WaseUtil::beforeNow($enddatetime))
            return 'Cannot make '.$this->APPTHING.' in the past.';
        
        $dummyapp = $this->dummyappforslot($startdatetime, $enddatetime);
        if ($dummyapp)
            return 'Slot is not available.';
       
        $userappsforslot = $this->slotAppsMade($usertype, $authid, $startdatetime, $enddatetime, $curapp);
        // User may only make one appointment per slot.
        if ($userappsforslot >= 1)
            return 'User already has an '.$this->APPTHING.' scheduled for this slot.';
                   
        // If there is a per-slot appointment limit, make sure it has not been exceeded
        if ($this->maxapps) {
            $appsforslot = $this->slotAppsMade('', '', $startdatetime, $enddatetime, $curapp);
            //WaseMsg::dMsg('DBG','Info','appsforslot is '.$appsforslot);
            if ($appsforslot >= $this->maxapps)
                return 'All appointments for this slot have been taken.';
        }
        
        if ($userappsforslot >= $this->slots())
           return 'Appointment scheduling limit for this block has been reached.';
        
        return '';
        
        // All tests have passed
    }
    
    
    /**
     * How many appointments can specified user make in this block.
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *            
     * @return int count of makeable appointments.
     */
    function makeableApps($usertype, $authid)
    {
        
        // Owners can make as many appointments as are available.
        if ($this->isOwner($usertype, $authid))
            return $this->freeSlots();
                   
        // First, see if user can make any appointments at all.
        if (($whynot = $this->isMakeable($usertype, $authid, "", "")) != '')
            return 0;
            
        // Non-owner can make as many as are available
        else {
            // Compute how many unlocked available slots we have
            $availslots = count($this->availableSlots($usertype, $authid));
            if ($this->maxper)
               // $avail = min($this->freeSlots(), $this->maxper);
               $avail = min($availslots, $this->maxper); 
            else
                $avail = $availslots;
            return max($avail, 0); 
        }
    }

    /**
     * Return array of available appointment slots.
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *            
     * @return array list of available start date-times.
     */
    function availableSlots($usertype, $authid)
    {
        /* Init return array */
        $available = array();
        
        
        /* Go through all of the slots, see if any appointments can be scheduled by this user for that slot */
        for ($i = WaseUtil::datetimeToMin($this->startdatetime); $i < WaseUtil::datetimeToMin($this->enddatetime); $i += ($this->slotsize)) {
            /* Compute slot start date/time and end date/time */
            $start = WaseUtil::unixToDateTime($i * 60);
            $end = WaseUtil::unixToDateTime(($i * 60) + ($this->slotsize * 60));
            /* Add if available */
            if (! $whynot = $this->slotIsMakeable($usertype, $authid, $start, $end)) 
                $available[] = $start;              
           
        }
        return $available;
    }

    /**
     * Return array of all appointment slots and slot_available_flags. (clone of availableSlots)
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *
     * @return array list of available start date-times.
     */
    function availableSlots_nocheck($usertype, $authid, &$slot_available_flags)
    {
        /* Init return array */
        $available = array();


        /* Go through all of the slots, see if any appointments can be scheduled by this user for that slot */
        for ($i = WaseUtil::datetimeToMin($this->startdatetime); $i < WaseUtil::datetimeToMin($this->enddatetime); $i += ($this->slotsize)) {
            /* Compute slot start date/time and end date/time */
            $start = WaseUtil::unixToDateTime($i * 60);
            $end = WaseUtil::unixToDateTime(($i * 60) + ($this->slotsize * 60));
            /* Add if available */
            // if (! $whynot = $this->slotIsMakeable($usertype, $authid, $start, $end))
            // $available[] = $start;
            $available[] = $start;
            if($this->slotIsMakeable($usertype, $authid, $start, $end)) {
                $flag=0;
                // $flag=$this->slotIsMakeable($usertype, $authid, $start, $end);
            } else {
                $flag=1;
            }
            $slot_available_flags[]=$flag;

        }
        return $available;
    }

    
    /**
     * Return array of available appointment slots, not including the current appoitnment.
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *            
     * @param string $app
     *            the current appointment.
     *
     * @return array list of available start date-times.
     */
    function editableSlots($usertype, $authid, $app)
    {
        /* Init return array */
        $available = array();
    
        
    
        /* Go through all of the slots, see if any appointments can be scheduled by this user for that slot */
        for ($i = WaseUtil::datetimeToMin($this->startdatetime); $i < WaseUtil::datetimeToMin($this->enddatetime); $i += ($this->slotsize)) {
            /* Compute slot start date/time and end date/time */
            $start = WaseUtil::unixToDateTime($i * 60);
            $end = WaseUtil::unixToDateTime(($i * 60) + ($this->slotsize * 60));
            
            /* Add if available */
            if (! $whynot = $this->isMakeable($usertype, $authid, $start, $end, $app))
                $available[] = $start;
                 
        }
        return $available;
    }

    /**
     * Return array of  appointment slots and slot available_flags.
     * (clone of editableSlots)
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *
     * @param string $app
     *            the current appointment.
     *
     * @return array list of available start date-times.
     */
    function editableSlots_nocheck($usertype, $authid, $app, &$slot_available_flags)
    {
        /* Init return array */
        $available = array();
        $object_value=is_object($app);
        WaseMsg::dMsg('DBG','Info',"editableSlots-authid[$authid]object_value[$object_value]");
        /* Go through all of the slots, see if any appointments can be scheduled by this user for that slot */
        for ($i = WaseUtil::datetimeToMin($this->startdatetime); $i < WaseUtil::datetimeToMin($this->enddatetime); $i += ($this->slotsize)) {
            /* Compute slot start date/time and end date/time */
            $start = WaseUtil::unixToDateTime($i * 60);
            $end = WaseUtil::unixToDateTime(($i * 60) + ($this->slotsize * 60));

            /* Add if available */
            // if (! $whynot = $this->isMakeable($usertype, $authid, $start, $end, $app))
            //     $available[] = $start;
            $available[] = $start;
            //WaseMsg::dMsg('DBG','Info',"editbleSlots_nocheck-authid[$authid]start[$start]end[$end]");
            //$jc_whynot = $this->isMakeable($usertype, $authid, $start, $end, $app);
            //WaseMsg::dMsg('DBG','Info',"editbleSlots_nocheck-jcwhynot[$jc_whynot]");
            if (! $whynot = $this->isMakeable($usertype, $authid, $start, $end, $app)) {
                $slot_available_flags[]=1;
                //$slot_available_flags[]=$whynot;
            } else {
                $slot_available_flags[]=0;
                // $slot_available_flags[]=$whynot." apptid".$app->appointmentid;
            }

        }
        return $available;
    }

    /**
     * This function returns a list of the start times of all slots (in datetime format).
     *
     * @return array list of start date-times.
     */
    function allSlots()
    {
        /* Init return array */
        $available = array();
        
        /* Return all slots */
        for ($i = WaseUtil::datetimeToMin($this->startdatetime); $i < WaseUtil::datetimeToMin($this->enddatetime); $i += ($this->slotsize)) {
            /* Compute slot start date/time and end date/time */
            $start = WaseUtil::unixToDateTime($i * 60);
            $end = WaseUtil::unixToDateTime(($i * 60) + ($this->slotsize * 60));
            /* Add slot */
            $available[] = $start;
        }
        return $available;
    }

    /**
     * How many appointments has (specified user) made in this block?
     *
     * This function computes how many appointment have been made. If given an authid argument, it just counts
     * the appointments scheduled for that user.
     *
     * @param string $usertype
     *            optionaly, either 'user' or 'guest'.
     * @param string $authid
     *            optionaly, the specified userid or email (if guest).
     * @param WaseAppointment $curapp
     *            optionaly, appointment to ignore when counting.
     *            
     * @return int count of appointments.
     */
    function blockAppsMade($usertype, $authid, $curapp = '')
    {
        /* Build select list based on arguments 
        if (! $usertype)
            $loadarg = array(
                'blockid' => $this->blockid
            );
        elseif ($usertype == 'guest')
            $loadarg = array(
                'blockid' => $this->blockid,
                'email' => trim($authid)
            );
        else
            $loadarg = array(
                'blockid' => $this->blockid,
                'userid' => trim($authid)
            );
        if ($curapp)
            $loadarg['appointmentid,!='] = $curapp->appointmentid;
            */
        /* Get list of all appointments made for this block, and return the count. */
        // $apps = new WaseList(WaseSQL::buildSelect('WaseAppointment', $loadarg), 'Appointment');
        // return $apps->entries();
        
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (';
        if (! $usertype)
            $select .= 'blockid = ' . $this->blockid;
        elseif ($usertype == 'guest')
            $select .= 'blockid = ' . $this->blockid . ' AND email = ' . WaseSQL::sqlSafe($authid);
        else
            $select .= 'blockid = ' . $this->blockid . ' AND userid = ' . WaseSQL::sqlSafe($authid);
        if ($curapp)
            $select .= ' AND appointmentid !=' . $curapp->appointmentid;
        $select .= ' AND available = 1)';       
        
        $apps = WaseSQL::doQuery($select);
        /* Return their count */
        //return $apps->entries();
        return WaseSQL::num_rows($apps);
    }

    /**
     * How many slots in appointments has (specified user) made in this block?
     *
     * This function computes how many appointment have been made. If given an authid argument, it just counts
     * the appointments scheduled for that user.
     *
     * @param string $usertype
     *            optionaly, either 'user' or 'guest'.
     * @param string $authid
     *            optionaly, the specified userid or email (if guest).
     * @param WaseAppointment $curapp
     *            optionaly, appointment to ignore when counting.
     *
     * @return int count of appointments.
     */
    function blockAppsMade_slots($usertype, $authid, $slot_size,  $curapp = '')
    {
        /* Build select list based on arguments
        if (! $usertype)
            $loadarg = array(
                'blockid' => $this->blockid
            );
        elseif ($usertype == 'guest')
            $loadarg = array(
                'blockid' => $this->blockid,
                'email' => trim($authid)
            );
        else
            $loadarg = array(
                'blockid' => $this->blockid,
                'userid' => trim($authid)
            );
        if ($curapp)
            $loadarg['appointmentid,!='] = $curapp->appointmentid;
            */
        /* Get list of all appointments made for this block, and return the count. */
        // $apps = new WaseList(WaseSQL::buildSelect('WaseAppointment', $loadarg), 'Appointment');
        // return $apps->entries();

        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (';
        if (! $usertype)
            $select .= 'blockid = ' . $this->blockid;
        elseif ($usertype == 'guest')
            $select .= 'blockid = ' . $this->blockid . ' AND email = ' . WaseSQL::sqlSafe($authid);
        else
            $select .= 'blockid = ' . $this->blockid . ' AND userid = ' . WaseSQL::sqlSafe($authid);
        if ($curapp)
            $select .= ' AND appointmentid !=' . $curapp->appointmentid;
        $select .= ' AND available = 1)';

        $apps = WaseSQL::doQuery($select);
        /* Return their count */
        //return $apps->entries();
        $slot_cnt=0;
        $row_cnt=WaseSQL::num_rows($apps);
        //for ($i=0; $i<$row_cnt;$i++) {
            //$startminutes=WaseUtil::datetimeToMin($apps[$i]['startdatetime']);
            //$endminutes=WaseUtil::datetimeToMin($apps[$i]['enddatetime']);
            //$slot_cnt += (($endminutes-$startminutes)/$slot_size);
        //}
        // return WaseSQL::num_rows($apps);
        // return $slot_cnt;
        return $row_cnt;
    }

    /**
     * how many apps have been made for a specified slot (start/end dates/times)?
     *
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     * @param string $startdatetime
     *            optionaly, starting date and time for the appointment.
     * @param string $enddatetime
     *            optionaly, ending date and time for the appointment.
     * @param string $curapp
     *            optionaly, pointer to WaseAppointment, if user is changing an existing appointment.
     *            
     * @return int count of appointments.
     */
    function slotAppsMade($usertype, $authid, $startdatetime, $enddatetime, $curapp = '')
    {
        /* Build appointment select list */
        if ($usertype == 'guest')
            $selextra = ' email = ' . WaseSQL::sqlSafe($authid) . ' AND ';
        elseif ($usertype)
            $selextra = ' userid = ' . WaseSQL::sqlSafe($authid) . ' AND ';
        else
            $selextra = '';
        //WaseMsg::dMsg('DBG','Info','selextra is'.$selextra);
        if ($curapp) {
            //if ($selextra)
                //$selextra .= ' AND ';
            $selextra .= ' appointmentid != ' . WaseSQL::sqlSafe($curapp->appointmentid) . ' AND ';
        }
        
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE ' . $selextra . ' (available = 1 AND blockid = ' . $this->blockid . ' AND enddatetime > ' . WaseSQL::sqlSafe($startdatetime) . ' AND startdatetime < ' . WaseSQL::sqlSafe($enddatetime) . ')';
        //WaseMsg::dMsg('DBG','Info','select sql '.$select);
        /* Get list of appointments that overlap the slot */
        // $apps = new WaseList($select, 'Appointment');
        $apps = WaseSQL::doQuery($select);
        /* Return their count */
        //return $apps->entries();
        $cnt=WaseSQL::num_rows($apps);
        //return WaseSQL::num_rows($apps);
        //WaseMsg::dMsg('DBG','Info','select sql '.$select." cnt".$cnt." userid".$authid." usertype".$usertype);
        return $cnt;
    }

    /**
     * how many slots in apps in this block?
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *
     *
     * @return int count of slots in appts.
     */
    function slotsMade($usertype, $authid)
    {
        $selextra='';
        /* Build appointment select list */
        if ($usertype == 'guest')
            $selextra = ' email = ' . WaseSQL::sqlSafe($authid) . ' AND ';
        elseif ($usertype)
            $selextra = ' userid = ' . WaseSQL::sqlSafe($authid) . ' AND ';
        else
            $selextra = '';
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE '.$selextra.' (blockid = ' . $this->blockid .  ')';
        /* Get list of appointments in block */
        // $apps = new WaseList($select, 'Appointment');
        $apps = WaseSQL::doQuery($select);
        $slot_cnt=0;
        $slotsize=$this->slotsize;
        //$num_entries=WaseSQL::num_rows($apps);
        //WaseMsg::dMsg('DBG','Info','num_entries '.$num_entries.' slotsize is '.$slotsize);
        foreach($apps as $one_entry)  {
            $startdatetime=$one_entry['startdatetime'];
            $enddatetime=$one_entry['enddatetime'];
            //$endminutes=WaseUtil::datetimeToMin($enddatetime);
            //$startminutes=WaseUtil::datetimeToMin($startdatetime);
            //WaseMsg::dMsg('DBG','INFO','starttime ' .$startdatetime.'endtime '.$enddatetime.' end minutes '.$endminutes.' start minutes '.$startminutes);
            $slot_cnt+=(WaseUtil::datetimeToMin($enddatetime)- WaseUtil::datetimeToMin($startdatetime))/$slotsize;
        }
        //WaseMsg::dMsg('DBG','Info', 'slot_cnt is '.$slot_cnt);
        return $slot_cnt;

    }


    /**
     * Does dummy app exist for the specified slot?
     *
     *
     * @param string $startdatetime
     *            slot starting date and time.
     * @param string $enddatetime
     *            slot ending date and time.
     *            
     * @return bool true if it exists, esle false.
     */
    function dummyappforslot($startdatetime, $enddatetime)
    {
        /* See if dummy appointment exists */
        $app = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE blockid = ' . $this->blockid . ' AND available = 0 AND enddatetime > ' . WaseSQL::sqlSafe($startdatetime) . ' AND startdatetime < ' . WaseSQL::sqlSafe($enddatetime), 'Appointment');
        if ($app->entries())
            return true;
        else
            return false;
    }

    /**
     * how many slots (apps) available for this block?
     *
     * @return int count of appointment slots available.
     */
    function freeSlots()
    {
        /* If unlimited number of apps per slot, then all slots are always available. */
        if (!$this->maxapps)
            return $this->slots();
        else {
            // maxappointments has total number of slots times apps per slot.
            // From that, we subtract the number of slotapps taken up by existing appoinments.
            $slotapps = $this->slotsMade('','');
            //WaseMsg::dMsg('DBG','Info', 'called via freeSlots - slotapps '.$slotapps);
            return ($this->maxAppointments() - $slotapps);
        }
    }

    /**
     * This function returns the max number of appointments possible for this block.
     * It returns zero if an unlimited number of appointments can be made.
     *
     * @return int count of possible appointments.
     */
    function maxAppointments()
    {
        return $this->slots() * $this->maxapps;
    }

    /**
     * This function returns the number of slots in this block.
     *
     * @return int count of slots.
     */
    function slots()
    {
        return ($this->blocksize() / $this->slotsize);
    }

    /**
     * This function returns the length of this block, in minutes.
     *
     * @return int block length, in minutes.
     */
    function blocksize()
    {
        return WaseUtil::elapsedDateTime($this->startdatetime, $this->enddatetime);
    }

    /**
     * Has cancellation deadline for this block been reached?
     *
     * @param string $datetime
     *            comparison date and time.
     *            
     * @return bool true if yes, else false.
     */
    function candeadline_reached($datetime)
    {
        return $this->deadline_reached($datetime, $this->candeadline);
    }

    /**
     * Has opening for this block been reached?
     *
     * @param string $datetime
     *            comparison date and time.
     *            
     * @return bool true if yes, else false.
     */
    function opening_reached($datetime)
    {
        if (! $this->opening)
            return true;
        return $this->deadline_reached($datetime, $this->opening);
    }
 
    /**
     * Has appointment cutoff date-time been reached?
     *
     * @param string $datetime
     *            comparison date and time.
     *            
     * @param int $deadline
     *              deadline in minutes (how long before comparison datetiume before deadline reached)
     *            
     * @return bool true if yes, else false.
     */
    function deadline_reached($datetime, $deadline)
    {
        /* If no deadline set, return false */
        if (! $deadline)
            return false;
        /* Now get current date and time in Unix format (in seconds) */
        $curtime = mktime();
        /* Compute start date/time minus deadline (in seconds) */
        $limit = WaseUtil::makeUnixTime($datetime) - ($deadline * 60);
         
        /* If limit reached, return true */
        if ($curtime > $limit) {
             
            return true;
        }
        else {
             
            return false;
        }
    }

    /**
     * Does specified user own this block?
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *            
     * @return bool true if yes, else false.
     */
    function isOwner($usertype, $authid)
    {
        
        /* If userid matches, then this is the owner */
        if (($usertype != 'guest') && (strtoupper(trim($authid)) == strtoupper(trim($this->userid))))
            return true;
            
        /* Load the calendar. */
        try {
            if (! $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            )))
                return false;
            ;
        } catch (Exception $error) {
            return false;
        }
        
        return $calendar->isOwnerOrManager($usertype, $authid);
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
    function canDeleteBlock($usertype, $authid)
    {
        return $this->isOwner($usertype, $authid);
    }
    
    /**
     * Determine if specified user can edit this block.
     *
     *
     * This function determines if specified user can edit this block.
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
    function canEditBlock($usertype, $authid)
    {
        return $this->isOwner($usertype, $authid);
    }
    
    /**
     * Is the user also a member of a calendar owned by this block owner?
     *
     * @param string $usertype
     *            either 'user' or 'guest'.
     * @param string $authid
     *            the specified userid or email (if guest).
     *
     * @return bool true if yes, else false.
     */
    function isAlsoMember($usertype, $authid)
    {   
        /* Load the calendar. */
        try {
            if (! $calendar = new WaseCalendar('load', array(
                'calendarid' => $this->calendarid
            )))
                return false;
                ;
        } catch (Exception $error) {
            return false;  
        }
        
        return $calendar->isMember($authid);
                    
    }
    
    
    
    /**
     * This function returns an iCal stream for this block.
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
    function makeIcal($to, $from, $type = 'create', $num=0)
    {
              
        $ret = "BEGIN:VCALENDAR\r\n"; 
        // $ret .= "X-WR-CALNAME:" . WaseUtil::getParm('SYSID') . "\r\n";
        /* METHOD depends on whether adding, changing or deleting an appointment */
        if ($type == 'delete')
            $ret .= "METHOD:CANCEL\r\n";
        else
            $ret .= "METHOD:PUBLISH\r\n";
   
        $ret .= "PRODID:-//Princeton University/Web Appointment Scheduling Engine//EN\r\n";
        $ret .= "VERSION:2.0\r\n";
        
        if ($this->periodid) {
            if ($type != 'delete')
                $ret .= WaseIcal::addSeries($this);
            else 
                $ret .= WaseIcal::deleteSeries($this);
        }
        else { 
            if ($type != 'delete') 
                $ret .= WaseIcal::addBlock($this);
            else
                $ret .= WaseIcal::deleteBlock($this);
        }
        
     
        $ret .= "END:VCALENDAR\r\n";
        
        return $ret;
    }
    

    /**
     * Return block information as an XML stream.
     *
     * @return string XML.
     */ 
    function xmlBlockInfo()  
    {
        return $this->xmlBlockHeaderInfo() . $this->tag('divideinto', ($this->slots() == 1 ? 'singleslots' : 'multipleslots')) . $this->tag('slotsize') . $this->tag('period', $this->xmlPeriodInfo()) . $this->tag('series', $this->xmlSeriesInfo()) . $this->tag('notifyandremind', $this->xmlNotifyAndRemind()) . $this->tag('accessrestrictions', $this->xmlAccessRestrictions()) . $this->tag("uid") . $this->tag("sequence");
    }

    /**
     * Return block header information as an XML stream.
     *
     * @return string XML.
     */
    function xmlBlockHeaderInfo()
    {
        return $this->tag('blockid') . $this->tag('seriesid') . $this->tag('calendarid') . $this->tag('title') . $this->tag('description') . '<blockowner>' . $this->tag('userid') . $this->tag('name') . $this->tag('phone') . $this->tag('email') . $this->tag('location') . '</blockowner>' . $this->tag('startdatetime') . $this->tag('enddatetime') . $this->tag('maxapps') . $this->tag('maxper') . $this->tag("opening") . $this->tag("deadline") . $this->tag("candeadline") . $this->tag('location') . $this->tag("available") . $this->tag("makeaccess") . $this->tag('purreq') . $this->tag('labels', $this->xmlLabels());
    }
    
    
    /**
     * Return block header information as  JSON stream.
     *
     * @return string JSON.
     */
    function jsonBlockHeaderInfo()
    {
        return $this->jtag('blockid') . "," . $this->jtag('seriesid') . "," . $this->jtag('calendarid') . ","  . $this->jtag('title') . ","  . $this->jtag('description')  . "," . 
        '"blockowner":{' . $this->jtag('userid')  . "," . 
        $this->jtag('name') . ","  . $this->jtag('phone') . ","  . $this->jtag('email') . ","  . $this->jtag('location') . '},' . $this->jtag('startdatetime') . ","  . $this->jtag('enddatetime') . ","  . 
        $this->jtag('maxapps') . ","  . $this->jtag('maxper') . ","  . $this->jtag("opening") . ","  . $this->jtag("deadline") . ","  . $this->jtag("candeadline") . ","  . $this->jtag('location') . ","  . 
        $this->jtag("available") . ","  . $this->jtag("makeaccess") . ","  . $this->jtag('purreq') . ","  . '"labels":{' . $this->jsonLabels() . '}';
    }

    /**
     * Return block notify/remind information as an XML stream.
     *
     * @return string XML.
     */
    function xmlNotifyAndRemind()
    {
        return $this->tag("notify") . $this->tag("notifyman") . $this->tag("notifymem") . $this->tag("remind") . $this->tag("remindman") . $this->tag("remindmem") . $this->tag("apptmsg");
    }

    /**
     * Return block access restrictions information as an XML stream.
     *
     * @return string XML.
     */
    function xmlAccessRestrictions()
    {
        return $this->tag("viewaccess") . $this->tag("viewulist") . $this->tag("viewclist") . $this->tag("viewglist") . $this->tag("viewslist") . $this->tag("makeaccess") . $this->tag("makeulist") . $this->tag("makeclist") . $this->tag("makeglist") . $this->tag("makeslist") . $this->tag("showappinfo");
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
     * This function returns an json stream of calendar labels.
     *
     * @return string the xml stream.
     */
    function jsonLabels()
    {
        return $this->jtag("NAMETHING") . ","   . $this->jtag("NAMETHINGS") . ","   . $this->jtag("APPTHING") . ","   .  $this->jtag("APPTHINGS");
    }
    
    
    
    /**
     * Return block period information as an XML stream.
     *
     * @return string XML.
     */
    function xmlPeriodInfo()
    {
        
        // If no period data, return nulls
        if (! $this->periodid)
            return '';
            // Load the period
        $period = new WasePeriod('load', array(
            'periodid' => $this->periodid
        ));
        // Generate the data stream
        $ret = $this->tag('periodid', $this->periodid) . $this->tag('starttime', $period->starttime) . $this->tag('duration', $period->duration);
        if ($period->dayofweek)
            $ret .= $this->tag('dayofweek', $period->dayofweek);
        elseif ($period->dayofmonth)
            $ret .= $this->tag('dayofmonth', $period->dayofmonth);
        if ($period->weekofmonth)
            $ret .= $this->tag('weekofmonth', $period->weekofmonth);
            // Return the stream
        return $ret;
    }

    /**
     * Return block series information as an XML stream.
     *
     * @return string XML.
     */
    function xmlSeriesInfo()
    {
        
        // If no period data, return nulls
        if (! $this->seriesid)
            return '';
            // Load the series
        $series = new WaseSeries('load', array(
            'seriesid' => $this->seriesid
        ));
        // Generate and return the data stream
        return $this->tag('seriesid', $this->seriesid) . $this->tag('startdate', $series->startdate) . $this->tag('enddate', $series->enddate) . $this->tag('every', $series->every) . $this->tag('daytypes', $series->daytypes);
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
        if (func_num_args() == 2)
            return '<' . $property . '>' .$val . '</' . $property . '>';
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