<?php

/**
 * This class describes a recurring period of time on a calendar.
 * 
 * Every time period in a set of block recurrences is represented by a WasePeriod.  
 * Each period maps to a single record in the WasePeriod database table.  
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WasePeriod
{
    
    /* Properties */
    
    /**
     *
     * @var int $periodid Database id of the WasePeriod block.
     */
    public $periodid;

    /**
     *
     * @var int $seriesid Database id of the owning WaseSeries.
     */
    public $seriesid;

    /**
     *
     * @var string $startime Start time of the period ('HH:MM')
     */
    public $starttime;

    /**
     *
     * @var int $duration Duration of the period, in minutes
     */
    public $duration;

    /**
     *
     * @var string $dayofweek Day of week of period (for weekly periods)
     */
    public $dayofweek;

    /**
     *
     * @var int $dayofmonth Day of month of period (for monthly periods)
     */
    public $dayofmonth;

    /**
     *
     * @var int $weekofmonth Week of month of period (for monthly periods)
     */
    public $weekofmonth;
    

    /**
     *
     * @var text $rrule The iCal equivalent of the period recurrence.
     */
    public $rrule;
    

    /**
     *
     * @var text $rdate The iCal equivalent of the period recurrence as individual dates.
     */
    public $rdate;

    /**
     *
     * @var text $exdate Excluded block dates in iCal format.
     */
    public $exdate;
    
    
    /* Static (class) methods */
    
    /**
     * Look up a period in the database and return its values as an associative array.
     *
     * @param int $id
     *            Database id of the WasePeriod record.
     *            
     * @return array|false array, or false if not found.
     */
    static function find($id)
    {
        /* Get a database handle. */
        if (! WaseSQL::openDB())
            return false;
            
            /* Find the entry */
        if (! $entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePeriod WHERE periodid=' . WaseSQL::sqlSafe($id)))
            return false;
            
            /* Get the entry into an associative array (there can only be 1). */
        $result = WaseSQL::doFetch($entry);
        
        /* Return the result */
        return $result;
    }

    /**
     * Locate all periods that meet the designated criteria, and return the php resource, or NULL.
     *
     *
     * @param array $arr
     *            Associative array containing WasePeriod selection criteria.
     *            
     * @return resource|null
     */
    static function select($arr)
    {
        /* Issue the select call and return the results */
        return WaseSQL::selectDB('WasePeriod', $arr);
    }

    /**
     * Return a list of periods that meet the specified criteria.
     *
     * @param array $arr
     *            Associative array containing WasePeriod selection criteria.
     *            
     * @return WaseList list of matching WasePeriod records.
     */
    static function listMatchingPeriods($criteria)
    {
        return new WaseList(WaseSQL::buildSelect('WasePeriod', $criteria), 'Period');
    }
    
    /* Obect Methods */
    
    /**
     * Create a WasePeriod.
     *
     * We have two constructors, one for a new period, one for an
     * existing period. In the former case, we create an entry in the
     * database period table, assign an id, and fill in the values as
     * specified in the construction call. In the latter case, we look up the
     * values in the database period table, and fill in the values as
     * per that table entry. In either case, we end up with a filed-in
     * period object.
     *
     * @param string $source
     *            specifies whether to load, update or create a period.
     * @param array $data
     *            associative array with the WasePeriod values.
     * @param string $fromdate
     *            if updating blocks, the start date for the updates.
     *            
     * @return WasePeriod The WasePeriod object is built.
     */
    function __construct($source, $data, $fromdate = '')
    {
        
        /*
         * $source tells us whether to 'load', 'update' or 'create a period.
         * $data is an associative array of elements used when we need to create/update
         * a period; for 'load', it contains just the periodid.
         */
        
        /* Start by trimming all of the passed values */
        foreach ($data as $key => $value) {
            $ndata[$key] = trim($value);
        }
        
        /*
         * For load and update, find the period and load up the values from
         * the database.
         */
        if (($source == 'load') || ($source == 'update')) {
            /* If the object doesn't exist, blow up. */
            if (! $values = WasePeriod::find($ndata['periodid']))
                throw new Exception('Period id ' . $ndata['periodid'] . ' does not exist', 14);
                /* Load the database data into the object. */
            WaseUtil::loadObject($this, $values);
        }
        
        /* Set defaults for unspecified values */
        if ($source == 'create') {
            /* No defaults ... everything must be specified */
        }
        
        /* For update, disallow certain changes */
        if ($source == 'update') {
            $errmsg = $this->updateCheck($ndata, $fromdate);
            if ($errmsg)
                throw new Exception($errmsg, 19);
        }
        
        /* For update and create, load up the values and validate the period. */
        if (($source == 'update') || ($source == 'create')) {
            WaseUtil::loadObject($this, $ndata);
            if ($errors = $this->validate(''))
                throw new Exception($errors, 19);
        }
    }

    /**
     * Save this WasePeriod.
     *
     * This function writes this period data out to the database.
     *
     * @param string $type
     *            specifies whether saving the period or updating the period.
     *            
     * @param string $fromdate
     *            when updating, whether the update applies to all blocks (null) or only blocks from a given date
     *            
     * @return int The WasePeriod database record id.
     */
    function save($type, $fromdate = '')
    {
        
        /* If creating an entry, build the variable and value lists */
        if ($type == 'create') {
            
            // We need to create the iCal 'rrule' that represents this period (including the 'until' from the owning WaseSeries).
            // $this->rrule = WaseIcal::makeRrule($this);         
            
            $varlist = '';
            $vallist = '';
            foreach ($this as $key => $value) {
                /* Don't specify a periodid */
                if ($key != 'periodid') {
                    if ($varlist)
                        $varlist .= ',';
                    $varlist .= $key;
                    if ($vallist)
                        $vallist .= ',';
                    $vallist .= WaseSQL::sqlSafe($value);
                }
            }
            $sql = 'INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WasePeriod (' . $varlist . ') VALUES (' . $vallist . ')';
        }         /* Else create the update list */
else {
            $sql = '';
            $sqlb = '';
            $blockvars = get_class_vars('WaseBlock');
            foreach ($this as $key => $value) {
                if ($sql)
                    $sql .= ', ';
                $sql .= $key . '=' . WaseSQL::sqlSafe($value);
                /* Block list must only contain block field values */
                if (array_key_exists($key, $blockvars)) {
                    if ($sqlb)
                        $sqlb .= ', ';
                    $sqlb .= $key . '=' . WaseSQL::sqlSafe($value);
                }
            }
            $sqlblock = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET ' . $sqlb . ' WHERE periodid=' . $this->periodid;
            if ($fromdate)
                $sqlblock .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($fromdate);
            $sql = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WasePeriod SET ' . $sql . ' WHERE periodid=' . $this->periodid;
        }
        
        /* Now do the update (and blow up if we can't) */
        if (! WaseSQL::doQuery($sql))
            throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);
            
            /* Get the (new) id and return it */
        if ($type == 'create')
            $this->periodid = WaseSQL::insert_id();
            
            /* Now update all of the blocks */
        if ($type == 'update')
            if (! WaseSQL::doQuery($sqlblock))
                throw new Exception('Error in SQL ' . $sql . ':' . WaseSQL::error(), 16);
        
        return $this->periodid;
    }

    /**
     * Validate a WasePeriod.
     *
     * This function validates the data in this WasePeriod.
     *
     * @param null|WaseSeries $series
     *            Database id of owning WaseSeries.
     *            
     * @return string|null an error message string.
     */
    function validate($series)
    {
        
        /* Validate passed data, pass back error string if any errors. */
        $errors = '';
        
        /* Read in the series object, if none is passed */
        if (! $series) {
            try {
                $series = new WaseSeries('load', array(
                    'seriesid' => $this->seriesid
                ));
            } catch (Exception $error) {
                $errors .= 'Invalid seriesid: ' . $error->getMessage() . ';';
            }
        }
        
        /* starttime and duration */
        if (! WaseUtil::checkTime($this->starttime))
            $errors .= 'Invalid start time: ' . $this->starttime;
        if (! is_numeric($this->duration) || ! $this->duration)
            $errors .= 'Invalid duration: ' . $this->duration;
            
            /* Terminate if we couldn't find the series. */
        if (! $series)
            return $errors;
            
            /* dayofweek */
        if (($series->every == 'weekly') || ($series->every == 'otherweekly') || ($series->every == 'monthlyweek')) {
            if (! $this->dayofweek)
                $errors .= 'dayofweek must be specified for ' . $series->every . ';';
            else {
                $dayofweek = strtoupper($this->dayofweek);
                if ($dayofweek != 'SUNDAY' && $dayofweek != 'MONDAY' && $dayofweek != 'TUESDAY' && $dayofweek != 'WEDNESDAY' && $dayofweek != 'THURSDAY' && $dayofweek != 'FRIDAY' && $dayofweek != 'SATURDAY')
                    $errors .= 'Invalid dayofweek value: ' . $this->dayofweek . ';';
            }
        }
        
        /* dayofmonth */
        if ($series->every == 'monthlyday') {
            if (! $this->dayofmonth)
                $errors .= 'dayofmonth must be specified for ' . $series->every . ';';
            else {
                if (! is_numeric($this->dayofmonth))
                    $errors .= 'dayofmonth must be a number;';
                elseif ($this->dayofmonth > 31)
                    $errors .= 'dayofmonth must be less than or equal to 31;';
            }
        }
        
        /* weekofmonth */
        if ($series->every == 'monthlyweek') {
            if (! $this->weekofmonth)
                $errors .= 'weekofmonth must be specified for ' . $series->every . ';';
            else {
                if (! is_numeric($this->weekofmonth))
                    $errors .= 'weekofmonth must be a number;';
                elseif ($this->weekofmonth > 4)
                    $errors .= 'weekofmonth must be less than or equal to 4;';
            }
        }
        return $errors;
    }

    /**
     * Validate updates to this period.
     *
     * This function checks to see if proposed changes to the period (set of blocks) violate any business rules.
     *
     * @param array $ndata
     *            associative array of proposed updates to the block.
     * @param string $fromdate
     *            null, or starting date for block updates.
     *            
     * @return string|null an error message string.
     */
    function updateCheck($ndata, $fromdate = '')
    {
        if (array_key_exists('seriesid', $ndata) && ($ndata['seriesid'] != $this->seriesid))
            return 'Cannot assign period to a new series';
        if (array_key_exists('dayofweek', $ndata) && ($ndata['dayofweek'] != $this->dayofweek))
            return 'Cannot change weekday of a recurring block';
        if (array_key_exists('dayofmonth', $ndata) && ($ndata['dayofmonth'] != $this->dayofmonth))
            return 'Cannot change month day of a recurring block';
        if (array_key_exists('weekofmonth', $ndata) && ($ndata['weekofmonth'] != $this->weekofmonth))
            return 'Cannot change week of a recurring block';
            
            /*
         * Make sure proposed changes do not disrupt any blocks.
         */
            
        /* Init error tracker */
        $errmsg = '';
        /* Build list of blocks */
        if ($fromdate)
            $blist = array(
                'periodid,=,AND' => $this->periodid,
                'date,>=,AND' => $fromdate
            );
        else
            $blist = array(
                'periodid' => $this->periodid
            );
        $blocks = new WaseList(WaseSQL::buildSelect('WaseBlock', $blist), 'Block');
        /* Test applying the updates to the blocks, and catch any errors */
        foreach ($blocks as $block) {
            if ($errmsg = $block->updateCheck($ndata))
                break;
        }
        return $errmsg;
    }

    /**
     * Does specified user own this period (set of blocks)?
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
        
        /* Load the series. */
        try {
            if (! $series = new WaseSeries('load', array(
                'seriesid' => $this->seriesid
            )))
                return false;
            ;
        } catch (Exception $error) {
            return false;
        }
        
        return $series->isOwner($usertype, $authid);
    }
}
?>