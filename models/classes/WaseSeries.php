<?php

/**
 * This class describes a recurring series of blocks on a calendar.
 * 
 * Every set of block recurrences is represented by a WaseSeries.  
 * Each series maps to a single record in the WaseSeries database table.  
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseSeries
{
    
    /* Properties */
    
    /**
     *
     * @var int $seriesid Database id of the WaseSeries record.
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
     * @var string $startdate Starting date of series (yyyy-mm-dd)
     */
    public $startdate;

    /**
     *
     * @var string $enddate Ending date of series (yyyy-mm-dd)
     */
    public $enddate;

    /**
     *
     * @var string $every Recurrence frequency
     */
    public $every;

    /**
     *
     * @var string $daytypes Comma seperated list of valid day types for this series.
     */
    public $daytypes;

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
     * @var string $viewclist Block view group list.
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

    /**
     *
     * @var text $exdate Excluded block dates in iCal format.
     */
    public $exdate;



    /* Static (class) methods */
    
    /**
     * Look up a series in the database and return its values as an associative array.
     *
     * @param int $id
     *            Database id of the WaseSeries record.
     *            
     * @return array|false or false if not found.
     */
    static function find($id)
    {
        /* Get a database handle. */
        if (! WaseSQL::openDB())
            return false;
            
            /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseSeries WHERE seriesid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Get the entry into an associative array (there can only be 1). */
        $result = WaseSQL::doFetch($entry);
        /* Free up the query */
        WaseSQL::freeQuery($entry);
        /* Return the result */
        return $result;
    }

    /**
     * Locate all series that meet the designated criteria, and return the php resource, or NULL.
     *
     *
     * @param array $arr
     *            Associative array containing WaseSeries selection criteria.
     *            
     * @return resource|null
     */
    static function select($arr)
    {
        /* Issue the select call and return the results */
        return WaseSQL::selectDB('WaseSeries', $arr);
    }
    
    /* Obect Methods */
    
    /**
     * Create a WaseSeries.
     *
     * We have two constructors, one for a new series, one for an
     * existing series. In the former case, we create an entry in the
     * database series table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database series table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * series object.
     *
     * @param string $source
     *            specifies whether to load, update or create a series.
     * @param array $data
     *            associative array with the WaseSeries values.
     * @param string $fromdate
     *            if updating blocks, the start date for the updates.
     *            
     * @return WaseSeries The WaseSeries object is built.
     */
    function __construct($source, $data, $fromdate = '')
    {
        /*
         * $source tells us whether to 'load', 'update' or 'create a series.
         * $data is an associative array of elements used when we need to create/update
         * a series; for 'load', it contains just the seriesid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        /*
         * For load and update, find the series and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            /* If the object doesn't exist, blow up. */
            if (! $values = WaseSeries::find($ndata['seriesid']))
                throw new Exception('Series id ' . $ndata['seriesid'] . ' does not exist', 14);
                /* Load the database data into the object. */
            WaseUtil::loadObject($this, $values);
        }
        
        /* For create, set defaults for unspecified values */
        if ($source == 'create') {
            /* Defaults are inherited first from the parent calendar */
            
            /* Get all of the calendar values */
            $calendar = WaseCalendar::find($ndata['calendarid']);
            /* Get a list of all of the series properties */
            $properties = get_object_vars($this);
            
            /* Copy certain unspecified values from the calendar */
            foreach ($calendar as $key => $value) {
                if (array_key_exists($key, $properties))
                    if (! array_key_exists($key, $ndata))
                        if (($key != 'title') && ($key != 'description'))
                            $ndata[$key] = $value;
            }
            
            /* Set directory defaults */
            if ($ndata['name'] == '')
                $ndata['name'] = $directory->getName($ndata['userid']);
            if ($ndata['phone'] == '')
                $ndata['phone'] = $directory->getPhone($ndata['userid']);
            if ($ndata['email'] == '')
                $ndata['email'] = $directory->getEmail($ndata['userid']);
            if ($ndata['location'] == '')
                $ndata['location'] = $directory->getOffice($ndata['userid']);
                
                /*
             * Now set any remaining unspecified values to the defaults of the owning
             * calendar values.
             */
            if ($ndata['makeaccess'] == '')
                $ndata['makeaccess'] = 'limited';
            if ($ndata['viewaccess'] == '')
                $ndata['viewaccess'] = 'limited';
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
            if (! array_key_exists('purreq', $ndata))
                $ndata['purreq'] = 0;
            if (! array_key_exists('available', $ndata))
                $ndata['available'] = 1;
            if ($ndata['every'] == '')
                $ndata['every'] = 'weekly';
            if (! array_key_exists('maxapps', $ndata))
                $ndata['maxapps'] = 0;
            if (! array_key_exists('maxper', $ndata))
                $ndata['maxper'] = 0;
            if (! array_key_exists('deadline', $ndata))
                $ndata['deadline'] = 0;
            if (! array_key_exists('candeadline', $ndata))
                $ndata['candeadline'] = 0;
            if (! array_key_exists('opening', $ndata))
                $ndata['opening'] = 0;  
            if (! array_key_exists('slotsize', $ndata))
                $ndata['slotsize'] = 0;
            if ($ndata['daytypes'] == '')
                $ndata['daytypes'] = WaseAcCal::defaultDaytype();
            if (! array_key_exists('NAMETHING', $ndata))
                $ndata['NAMETHING'] = WaseUtil::getParm('NAME');
            if (! array_key_exists('NAMETHINGS', $ndata))
                $ndata['NAMETHINGS'] = WaseUtil::getParm('NAMES');
            if (! array_key_exists('APPTHING', $ndata))
                $ndata['APPTHING'] = WaseUtil::getParm('APPOINTMENT');
            if (! array_key_exists('APPTHINGS', $ndata))
                $ndata['APPTHINGS'] = WaseUtil::getParm('APPOINTMENTS');
            //01/05/2020 - set default for exdate to a blank
            if (! array_key_exists('exdate', $ndata))
                $ndata['exdate'] = ' ';
             
            
        }
        
        /* For update, make sure we are not resetting fields that cannot be reset. */
        if ($source == 'update') {
            $errmsg = $this->updateCheck($ndata, $fromdate);
            if ($errmsg)
                throw new Exception($errmsg, 20);
        }
        
        /* For update and create, load up the passed values and validate the series. */
        if (($source == 'update') || ($source == 'create')) {
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate())
                throw new Exception($errors, 20);
        }
    }

    /**
     * Save this WaseSeries.
     *
     * This function writes this series data out to the database.
     *
     * @param string $type
     *            specifies whether saving the series or updating the series.
     *            
     * @param string $fromdate
     *            when updating, whether the update applies to all blocks (null) or only blocks from a given date
     *            
     * @return int The WaseSeries database record id.
     */
    function save($type, $fromdate = '')
    {
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify a seriesid */
                if ($key != 'seriesid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= '`' . $key . '`';
                    if ($vallist)
                        $vallist .= ',';
                    $vallist .= WaseSQL::sqlSafe($value);
                }
            }
            //01/05/20 - if no exdate add it
            if(preg_match("/exdate/i",$varlist) == 0) {
                $key="exdate";
                $value="";
                $varlist .=',`' . $key . '`';
                $vallist .= ',';
                $vallist .= WaseSQL::sqlSafe($value);
            }
            $sqls = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseSeries (' . $varlist . ') VALUES (' . $vallist . ')';
            WaseMsg::dMsg('DBG','Info',"sqls[$sqls]");
            $sqlb = '';
        }         /* Else create the update list */
else {
            $updates = '';
            $updateb = '';
            /* We need a list of the variables in a block */
            $blockvars = array_keys(get_class_vars('WaseBlock'));
            
            foreach ($this as $key => $value) {
                /* Add change to series update string */
                if ($updates)
                    $updates .= ', ';
                $updates .= '`' . $key . '`' . '=' . WaseSQL::sqlSafe($value);
                /* If this is a block var, change it as well */
                if (in_array($key, $blockvars)) {
                    if ($updateb)
                        $updateb .= ', ';
                    $updateb .= $key . '=' . WaseSQL::sqlSafe($value);
                }
            }
            $sqls = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseSeries SET ' . $updates . ' WHERE seriesid=' . $this->seriesid;
            $sqlb = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET ' . $updateb . ' WHERE seriesid=' . $this->seriesid;
            if ($fromdate)
                $sqlb .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($fromdate);
        }
        
        /* Now do the update (and blow up if we can't) */
        if ($sqls) {
            if (! WaseSQL::doQuery($sqls))
                throw new Exception('Error in SQL ' . $sqls . ':' . WaseSQL::error(), 16);
        }
        if ($sqlb) {
            if (! WaseSQL::doQuery($sqlb))
                throw new Exception('Error in SQL ' . $sqlb . ':' . WaseSQL::error(), 16);
        }
        
        /* Save the (new) series id */
        if ($type == 'create')
            $this->seriesid = WaseSQL::insert_id();
        
        return $this->seriesid;
    }

    /**
     * Validate a WasePeriod.
     *
     * This function validates the data in this WasePeriod.
     *
     * @return string|null error message string.
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
            $errors .= 'Invalid calendarid: ' . $this->calendarid . ';';
        
        if (($this->userid != $calendar['userid']) && ! WaseManager::isManager($calendar['calendarid'], $this->userid, '') && ! WaseMember::isMember($calendar['calendarid'], $this->userid))
            $errors .= 'Series userid (' . $this->userid . ') must match calendar userid (' . $calendar['userid'] . ' or a calendar member userid);';
            
            /* startdate and enddate */
        
        if (! WaseUtil::isDateValid($this->startdate))
            $errors .= 'Invalid start date: ' . $this->startdate;
        if (! WaseUtil::isDateValid($this->enddate))
            $errors .= 'Invalid end date: ' . $this->enddate;
        $compare = WaseUtil::compareDates($this->enddate, $this->startdate);
        if ($compare != '<' && $compare != '=')
            $errors .= 'End date (' . $this->enddate . ') must be >= start date (' . $this->startdate . ';';
            
        /* every */
        if (($this->every != 'daily') && ($this->every != 'dailyweekdays') && ($this->every != 'weekly') && ($this->every != 'otherweekly') && ($this->every != 'monthlyday') && ($this->every != 'monthlyweekday'))
            $errors .= 'Invalid every value: ' . $this->every . ';';
            
        /* daytypes */
        $daytypes = explode(',', $this->daytypes);
        if (! $daytypes)
            $daytypes = WaseAcCal::defaultDaytype();
        $alltypes = WaseAcCal::getAllDaytypes();
        
      
        foreach ($daytypes as $day) {
            if (!$alltypes)
                $errors .= 'Invalid daytype value: ' . $day . ';';
            elseif (! WaseUtil::in_array_ci($day, $alltypes))
                $errors .= 'Invalid daytype value: ' . $day . ';';
        }
        
        /* maxapps */
        if (! is_numeric($this->maxapps))
            $errors .= 'maxapps must be an integer;';
            
            /* maxper */
        if (! is_numeric($this->maxper))
            $errors .= 'maxper must be an integer;';
            
            /* deadline */
        if (! is_numeric($this->deadline))
            $errors .= 'deadline must be an integer;';
        if (! is_numeric($this->candeadline))
            $errors .= 'candeadline must be an integer;';
        if (! is_numeric($this->opening))
            $errors .= 'opening must be an integer;';
            
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
            
            /* makeaccess, viewaccess */
        if (($this->makeaccess != 'open') && ($this->makeaccess != 'limited') && ($this->makeaccess != 'restricted') && ($this->makeaccess != 'private'))
            $errors .= 'Invalid makeaccess value: ' . $this->makeaccess . ';';
        
        if (($this->viewaccess != 'open') && ($this->viewaccess != 'limited') && ($this->viewaccess != 'restricted') && ($this->viewaccess != 'private'))
            $errors .= 'Invalid viewaccess value: ' . $this->viewaccess . ';';
            
        /* makeulist, makeclist, makeglist, viewulist, viewclist, viewglist, viewslist */
            
        /* remind */
        if (! WaseUtil::checkBool($this->remind))
            $errors .= 'remind must be a boolean;';
        
        return $errors;
    }

    /**
     * Validate updates to this series.
     *
     * This function checks to see if proposed changes to the series (set of blocks) violate any business rules.
     *
     * @param array $ndata
     *            associative array of proposed updates to the block.
     * @param string $fromdate
     *            null, or starting date for block updates.
     *            
     * @return string|null error message string.
     */
    function updateCheck($ndata, $fromdate = '')
    {
        if (array_key_exists('calendarid', $ndata) && ($ndata['calendarid'] != $this->calendarid))
            return 'Cannot assign series to a new calendar';
        if (array_key_exists('userid', $ndata) && ($ndata['userid'] != $this->userid))
            return 'Cannot assign series to a new user';
        if (array_key_exists('every', $ndata) && ($ndata['every'] != $this->every))
            return 'Cannot change recurrence type of a series';
        if (array_key_exists('startdate', $ndata) && ($ndata['startdate'] != $this->startdate))
            return 'Cannot change starting date of a series';
        if (array_key_exists('enddate', $ndata) && ($ndata['enddate'] != $this->enddate))
            return 'Cannot change ending date of a series';
        if (array_key_exists('daytypes', $ndata) && ($ndata['daytypes'] != $this->daytypes))
            return 'Cannot change day types of a series';
            
            /*
         * Make sure proposed changes do not disrupt any periods.
         */
            
        /* Init error tracker */
        $errmsg = '';
        /* Build list of periods */
        if ($fromdate)
            $plist = array(
                'seriesid,=,AND' => $this->seriesid,
                'date,>=,AND' => $fromdate
            );
        else
            $plist = array(
                'seriesid' => $this->seriesid
            );
            /* Build list of periods */
        $periods = new WaseList(WaseSQL::buildSelect('WasePeriod', $plist), 'Period');
        /* Test applying the updates to the blocks, and catch any errors */
        foreach ($periods as $period) {
            if ($errmsg = $period->updateCheck($ndata, $fromdate))
                break;
        }
        return $errmsg;
    }

    /**
     * Delete this set of blocks.
     *
     * This function deletes the database record for the blocks belonging to this series.
     * Any appointments made in these blocks are also cancelled and deleted.
     *
     * @param string $canceltext
     *            text for appointment cancellation email.
     *            
     * @return void
     */
    function delete($canceltext)
    {
        
        /* First, build a list of all of the blocks */
        $blocks = new WaseList(WaseSQL::buildSelect('WaseBlock', array(
            'seriesid' => $this->seriesid
        )), 'Block');
        
        /* Now delete each block (this will cancel the appointments) */
        $elements = $blocks->entries(); $i = 0;
        foreach ($blocks as $block) {
            $i++;
            if ($i == 1)
                $first = true;
            else
                $first = false;
            if (is_object($block))
                $block->delete($canceltext, $first);
        }
        
        /* Now delete all of the period blocks */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WasePeriod WHERE seriesid=' . $this->seriesid);
        
        /* Delete this series */
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WaseSeries WHERE seriesid=' . $this->seriesid . ' LIMIT 1');
    }
    
    /**
     * Build the blocks associated with a period.
     *
     * This function builds a set of blocks for this series and a given period.
     *
     * @param WasePeriod $period
     *            the WasePeriod block that specifies the recurrence pattern
     *            
     * @return int|string return count of blocks built as an int, else return an error message string
     */
    function buildBlocks($period)
    {
        
        /*
         * The series block contains the start and end dates and the
         * recurrence frequency. The period block contains the start and end times,
         * and the types of days on which the blocks occur.
         */
     
        /* Init block counter */
        $blockcount = 0;
        
        /*
         * The first step is to build a comprehensive list of dates on which the
         * blocks will occur, from startdate to enddate.
         */
        
        $startunix = mktime(3, 0, 0, substr($this->startdate, 5, 2), substr($this->startdate, 8, 2), substr($this->startdate, 0, 4)); /* Make it 3am to avoid leap-second problems */
        $endunix = mktime(3, 0, 0, substr($this->enddate, 5, 2), substr($this->enddate, 8, 2), substr($this->enddate, 0, 4)); /* Make it 3am to avoid leap-second problems */
        $startweekday = date('l', $startunix);
        $startmonthday = date('d', $startunix);
        
        /* Get list of acceptable daytypes */
        $daytypes = explode(',', $this->daytypes);
        /* if none, use defaults */
        if (! $daytypes)
            $daytypes = WaseAcCal::defaultDaytype();
            
        /* Init array of good dates */
        $usedates = array();
        
        /* Init error tracker */
        $errmsg = '';
        
        // Get list of available dates/
        list ($usedates, $exdates, $rdates)  = $this->buildEligibleDates($period);  

        // Save the included and excluded dates in the period.
        
        if ($exdates) 
            $period->exdate = 'EXDATE;VALUE=DATE:' . implode(',',$exdates) . "\r\n";
        if ($rdates) {
            $period->rdate = 'RDATE;VALUE=DATE:' . implode(',',$rdates) . "\r\n";
            $period->rrule = WaseIcal::makeRrule($period);
        }
        // Save the exdate/rdate values
        $saved = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WasePeriod SET `exdate` = ' . WaseSQL::sqlSafe($period->exdate) . ', `rdate` = ' . WaseSQL::sqlSafe($period->rdate) . 
            ', `rrule` = ' . WaseSQL::sqlSafe($period->rrule) . ' WHERE periodid = ' . $period->periodid);
        
        /*
         * The next step is to make sure that all of the blocks can be built ... in particular, we must make sure that the created blocks will
         * not overlap any existing blocks.
         */ 
         
        
        // First, get the calendar to see if overlapok is on.
        /* Read in the calendar */
        $calendar = new WaseCalendar('load', array(
            'calendarid' => $this->calendarid
        ));
        
        // Init the select string
        $query = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE ((';
        $times = '';
        
        // Now add in all of the possible start/end datetimes.
        foreach ($usedates as $currdate) {
            $startdatetime = $currdate . ' ' . $period->starttime;
            $enddatetime = WaseUtil::addDateTime($currdate . ' ' . $period->starttime, $period->duration);
            if ($times)
                $times .= ' OR ';
            $times .= ' (' . 'startdatetime < ' . WaseSQL::sqlSafe($enddatetime) . ' AND enddatetime > ' . WaseSQL::sqlSafe($startdatetime) . ')';
        }
        // Finish the query string
        $query .=  $times . ') AND ' . 'userid = ' . WaseSQL::sqlSafe($this->userid);
        // If overlap with other calendars ok, only search the current calendar.    
        if ($calendar->overlapok)
            $query .= ' AND calendarid=' . $this->calendarid;
            
        $query .= ')';
        
        $allready = WaseSQL::doQuery($query);
        
        if ($allready) {
            if (WaseSQL::num_rows($allready) > 0) {
                $errmsg = 'No blocks created: proposed block start/end times conflict with: ';
                while ($matchblock = WaseSQL::doFetch($allready)) {
                    if ($matchblock['calendarid'] == $this->calendarid)
                        $errmsg .= 'block on calendar "' . $matchblock['title'] . '" at ' . WaseUtil::datetimeToUS($matchblock['startdatetime']) . ' to ' . WaseUtil::datetimeToUS($matchblock['enddatetime']) . '; ';
                    else {
                        $matchcal = WaseCalendar::find($matchblock['calendarid']);
                        $errmsg .= 'block on calendar with title "' . $matchcal['title'] . '": at ' . WaseUtil::datetimeToUS($matchblock['startdatetime']) . ' to ' . WaseUtil::datetimeToUS($matchblock['enddatetime']) . '; ';
                    }
                }              
            }
        }
        
        if ($errmsg)
            return $errmsg;
        
        /*
         * Now that we have the dates, and they are ok, we iterate through the dates
         * and build the blocks (kep track of when we reach the last element).
         */
        $errmsg = ''; $elements = count($usedates); $i = 0;
        foreach ($usedates as $currdate) {
            $i++;
            // If the series has a zero slotize, reset the slotize to the period duration (unslotted blocks).
            if ($this->slotsize)
                $slotsize = $this->slotsize;
            else 
                $slotsize = $period->duration;
            
            try {
                $block = new WaseBlock('createnovalidate', array(
                    'periodid' => $period->periodid,
                    'seriesid' => $this->seriesid,
                    'calendarid' => $this->calendarid,
                    'title' => $this->title,
                    'description' => $this->description,
                    'userid' => $this->userid,
                    'name' => $this->name,
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'location' => $this->location,
                    'startdatetime' => $currdate . ' ' . $period->starttime,
                    'enddatetime' => WaseUtil::addDateTime($currdate . ' ' . $period->starttime, $period->duration),
                    'slotsize' => $slotsize,
                    'maxapps' => $this->maxapps,
                    'maxper' => $this->maxper,
                    'deadline' => $this->deadline,
                    'candeadline' => $this->candeadline,
                    'opening' => $this->opening,
                    'notify' => $this->notify,
                    'notifyman' => $this->notifyman,
                    'available' => $this->available,
                    'makeaccess' => $this->makeaccess,
                    'viewaccess' => $this->viewaccess,
                    'makeulist' => $this->makeulist,
                    'makeclist' => $this->makeclist,
                    'makeglist' => $this->makeglist,
                    'makeslist' => $this->makeslist,
                    'viewulist' => $this->viewulist,
                    'viewclist' => $this->viewclist,
                    'viewglist' => $this->viewglist,
                    'viewslist' => $this->viewslist,
                    'remind' => $this->remind,
                    'apptmsg' => $this->apptmsg,
                    'showappinfo' => $this->showappinfo,
                    'purreq' => $this->purreq,
                    'NAMETHING' => $this->NAMETHING,
                    'NAMETHINGS' => $this->NAMETHINGS,
                    'APPTHING' => $this->APPTHING,
                    'APPTHINGS' => $this->APPTHINGS
                    ) 
                );
            } catch (Exception $error) {
                /* Save error message. */
                $errmsg = 'Error encountered after ' . $blockcount . ' block(s) created: ' .  WaseUtil::Error($error->getCode(), array($error->getMessage()), 'FORMAT');
            }
            /* Now save the block */
            if (!$errmsg) {
                try {
                    $blockid = $block->save('create',$i);
                    $blockcount++;
                    unset($block);
                } catch (Exception $errors) {
                    /* Save error message. */
                    $errmsg = 'Error encountered after ' . $blockcount . ' block(s) created: ' . WaseUtil::Error($errors->getCode(), array($errors->getMessage()), 'FORMAT');
                }
            }
            /* Quit if we encountered an error */
            if ($errmsg) {
                break;
            }
        }
        
        /*
         * All done.  Save the excluded dates in the period.
         */
        if (! $errmsg) {
            $errmsg = $blockcount;
             
            if ($exdates) {
                $period->exdate = 'EXDATE;VALUE=DATE:' . implode(',',$exdates) . "\r\n";
                // Save the exdate value
                $saved = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WasePeriod SET `exdate` = ' . WaseSQL::sqlSafe($period->exdate) . ' WHERE periodid = ' . $period->periodid);
            }
        }
        
        return $errmsg;
    }

    /**
     * Determine if recurring block edit or build is valid.
     *
     * This function determines if a proposed edit or creation of a recurring set of blocks will cause
     * conflicts with existing blocks and/or appointments.
     *
     * @param WasePeriod period
     *          The WasePeriod that specifies the recurrence pattern.
     *
     * @param null | WaseBlock 
     *          If editing existing block(s), the proposed (changed) block.
     *          The date of the statdatetime and enddatetime of this block MUST correspond to the desired change range.
     *
     *
     * @return null | string 
     *          If create/edit ok, a null string;  else the reason why the create/edit would not work.
     *
     */
    function canBuildorEdit($period,$curblock=null) {
        

        // If editing an existing series/period, get the blocks in the series/period and check for conflicts.
        if ($curblock) {
        
            // Get the existing blocks
            $savedBlocks = new WaseList('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE (seriesid = ' . $curblock->seriesid . ' AND periodid = ' . $curblock->periodid . 
                ' AND startdatetime >= ' . $curblock->startdatetime . ' AND enddatetime <= ' . $curblock->enddatetime .')', 'Block');
            
            // For each block, see if proposed change is viable.
            foreach ($savedBlocks as $saveBlock) {
                
            }
        
        
        }
        else {
            
        }
        
        
        // Determine date range of the creation or proposed change.
        if ($curblock) {
            $startdate = substr($curblock->startdatetime,0,10);
            $enddate = substr($curblock->enddatetime,0,10);
        }
        else {
            $startdate = $this->startdate;
            $enddate = $this->enddate;
        }
       
    
        // First step, build list of proposed dates for build or edit
        list($usedates, $exdates, $rdates) = $this->buildEligibleDates($period,$startdate,$enddate);
        if (!$usedates) {
            if ($curblock)
                return 'No blocks changed:  no blocks found to change.';
            else
                return 'No blocks built:  no eligibale dates found (check your block specification, including daytypes).' ;
        }
    
        
    }
    
    
    /**
     * Determine elligible dates for a proposed recurring block.
     * 
     * This function builds an array of eligibale dates for a recurring block series.
     * 
     * @param WasePeriod period
     * 
     * @return array an array of eligibale dates.
     */
    function buildEligibleDates($period,$startdate=null,$enddate=null) {
        
        // Determine start and end dates.
        if (!$startdate)
            $startdate = $this->startdate;
        if (!$enddate)
            $enddate = $this->enddate;
        
        // Build unix start and end dates/days
        $startunix = mktime(3, 0, 0, substr($startdate, 5, 2), substr($startdate, 8, 2), substr($startdate, 0, 4)); /* Make it 3am to avoid leap-second problems */
        $endunix = mktime(3, 0, 0, substr($enddate, 5, 2), substr($enddate, 8, 2), substr($enddate, 0, 4)); /* Make it 3am to avoid leap-second problems */
        $startweekday = date('l', $startunix);
        $startmonthday = date('d', $startunix);
        
        /* Get list of acceptable daytypes */
        $daytypes = explode(',', $this->daytypes);
        /* if none, use defaults */
        if (! $daytypes)
            $daytypes = WaseAcCal::defaultDaytype();
        
        /* Init array of good dates */
        $usedates = array();
        /* Init array of skipped dates (for iCal 'exdate' element */
        $exdates = array();
        /* Init array of ical-formatted use dates. */
        $rdates = array();
        
         
        /* Loop from start date to end date */
        for ($currunix = $startunix; $currunix <= $endunix; $currunix += (60 * 60 * 24)) {
            /* Get date and dayofweek info. for current date */
            $currdate = date('Y-m-d', $currunix);
            $currweekday = date('l', $currunix);
            $currmonthday = date('d', $currunix);
            $icaldate = substr($currdate,0,4).substr($currdate,5,2).substr($currdate,8,2);
             
            /* Select on recurrence type */
            switch ($this->every) {
                /* For 'daily', no more checks needed */
                case 'daily':
                    if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                        $usedates[] = $currdate;
                        $rdates[] = $icaldate;
                    }
                    else 
                        $exdates[] = $icaldate;
                    break;
    
                /* for daylyweekdays, check not saturday/sunday */
                case 'dailyweekdays':
                    if (strtoupper($currweekday) != 'SUNDAY' && strtoupper($currweekday) != 'SATURDAY') {
                         if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                            $usedates[] = $currdate;
                            $rdates[] = $icaldate;
                        }
                        else 
                            $exdates[] = $icaldate;
                    }
                    break;
    
                        /* If weekly, make sure day of week same as starting day of week */
                case 'weekly':
                    if (strtoupper($currweekday) == strtoupper($period->dayofweek)) {
                        if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                            $usedates[] = $currdate;
                            $rdates[] = $icaldate;
                        }
                        else 
                            $exdates[] = $icaldate;
                        /* Skip most of the rest of the week */
                        $currunix += (60 * 60 * 24 * 5);
                    }
                    break;
    
                    /* If every other week, day of week must match starting day of week. */
                case 'otherweekly':
                    if (strtoupper($currweekday) == strtoupper($period->dayofweek)) {
                         if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                            $usedates[] = $currdate;
                            $rdates[] = $icaldate;
                        }
                        else 
                            $exdates[] = $icaldate;
                        /* Skip two weeks */
                        $currunix += (60 * 60 * 24 * 13);
                    } 
                    break;
    
                    /* If a specific day of the month, check the day of the month */
                case 'monthlyday':
                    if ($currmonthday == $period->dayofmonth) {
                        if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                            $usedates[] = $currdate;
                            $rdates[] = $icaldate;
                        }
                        else 
                            $exdates[] = $icaldate;
                        /* Skip most of the rest of the month */
                        $currunix += (60 * 60 * 24 * 26);
                    }
                    break;
    
                    /* If a weekday on a specific week of the month */
                case 'monthlyweekday':
                    if (strtoupper($currweekday) == strtoupper($period->dayofweek)) {
                        /* We need to compute the cardinality of this day. */
                        $currmonthweek = (($currmonthday - ($currmonthday % 7)) / 7) + 1;
                        if ($currmonthweek == $period->weekofmonth) {
                           if (!$daytypes || WaseUtil::in_array_ci(WaseAcCal::getDaytype($currdate), $daytypes)) {
                                $usedates[] = $currdate;
                                $rdates[] = $icaldate;
                            }
                            else 
                                $exdates[] = $icaldate;
                            /* Skip most of the rest of the month */
                            $currunix += (60 * 60 * 24 * 19);
                        }
                    }
                    break;
            }
        } 
        
        return array($usedates, $exdates, $rdates);
        
    }


    /**
     * Does specified user own this series (set of blocks)?
     *
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
    
}
?>