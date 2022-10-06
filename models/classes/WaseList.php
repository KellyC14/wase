<?php

/**
 * WASE object collection generator.
 * 
 * This class implements a list (collection) of objects (results).  
 * It generates the objects on the fly from a SQL query result. 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseList implements Iterator
{ 
    
    /* Properties */
    /**
     * @var string $query The SQL query.
     */
    protected $query;

    /**
     * @var string $type Type of list (WaseBlock, WaseCalendar, etc.).
     */
    protected $type;

    /**
     * @var resource $resource The PHP resource returned by the query.
     */
    protected $resource;

    /**
     * @var object $result The current row data (as an object).
     */
    protected $result;

    /**
     * @var int $row The current row number.
     */
    protected $row;

    /**
     * @var bool $valid Whether we have any more data to give back.
     */
    protected $valid;

    /**
     * @var int $rows Total number of rows in the results (resource).
     */
    protected $rows;

    /**
     * @var string $temp Temporary variable to hold results.
     */
    protected $temp;
    
    /* The object methods */
    
    /**
     * Construct the list by saving the passed parameter values.
     *
     * @param string $query
     *            the SQL query.
     * @param string $type
     *            the Wase oibject type.
     *            
     * @return WaseList the WaseList object is built.
     */
    public function __construct($query, $type)
    {
        /* Build the query as per the passed array of key/value pairs. */
        $this->query = $query;
        $this->type = $type;
        $this->resource = false;
        $this->result = false;
        $this->row = 0;
        $this->valid = false;
        $this->rows = 0;
        $this->temp = '';
        
        // Throw exception if type not specified
        if (!$this->type)
            throw new Exception('No type specified when creating WaseList for: ' . $this->query, 1);
    }

    /**
     * Release the PHP resource for the query.
     *
     *
     * @return void
     */
    public function __destruct()
    {
        /* Release the resource (if any). */
        WaseSQL::freeQuery($this->resource);
        $this->query = '';
        $this->type = '';
        $this->resource = false;
        $this->result = false;
        $this->row = 0;
        $this->valid = false;
        $this->rows = 0;
        $this->temp = '';
    }

    /**
     * Rewind resource to first element.
     *
     *
     * @return void
     */
    public function rewind()
    {
        /* Reset everything */
        $this->result = false;
        $this->row = 0;
        $this->valid = false;
        $this->rows = 0;
        $this->temp = '';
        
        /* Release result (if we already have one) */
        WaseSQL::freeQuery($this->resource);
        
        $this->resource = false;
        
        /* Open the database */
        if (WaseSQL::openDB()) { 
            /* Perform the query */
            if ($this->resource = WaseSQL::doQuery($this->query)) {
                if ($this->temp = WaseSQL::doFetch($this->resource)) {
                    try {
                        switch ($this->type) {
                            case 'Appointment':
                                $this->result = new WaseAppointment('load', $this->temp);
                                break;
                            case 'Block':
                                $this->result = new WaseBlock('load', $this->temp);
                                break;
                            case 'Calendar':
                                $this->result = new WaseCalendar('load', $this->temp);
                                break;
                            case 'Series':
                                $this->result = new WaseSeries('load', $this->temp);
                                break;
                            case 'Period':
                                $this->result = new WasePeriod('load', $this->temp);
                                break;
                            case 'Manager':
                                $this->result = new WaseManager('load', $this->temp);
                                break;
                            case 'Member':
                                $this->result = new WaseMember('load', $this->temp);
                                break;
                            case 'Waiter':
                                $this->result = new WaseWait('load', $this->temp);
                                break;
                            case 'DidYouKnow':
                                $this->result = new WaseDidYouKnow($this->temp);
                                break;
                        }
                    } catch (Exception $error) {
                        /* If an error, set result to false */
                        $this->result = false;
                    }
                }
                if ($this->result) {
                    $this->valid = true;
                    $this->rows = WaseSQL::num_rows($this->resource);
                    $this->row = 0;
                }
            } else {
                $this->result = false;
                $this->valid = false;
                $this->rows = 0;
                $this->row = 0;
            }
        }         /* Return unable status */
        else {
            $this->resource = false;
            $this->valid = false;
            $this->row = 0;
            $this->rows = 0;
        }
    }

    /**
     * Move to the next element.
     *
     *
     * @return void
     */
    public function next()
    {
        /* Increment row pointer */
        $this->row ++;
        /* If more, get next result */
        if ($this->valid()) {
            if ($this->temp = WaseSQL::doFetch($this->resource)) {
                try {
                    switch ($this->type) {
                        case 'Block':
                            $this->result = new WaseBlock('load', $this->temp);
                            break;
                        case 'Calendar':
                            $this->result = new WaseCalendar('load', $this->temp);
                            break;
                        case 'Series':
                            $this->result = new WaseSeries('load', $this->temp);
                            break;
                        case 'Period':
                            $this->result = new WasePeriod('load', $this->temp);
                            break;
                        case 'Appointment':
                            $this->result = new WaseAppointment('load', $this->temp);
                            break;
                        case 'Manager':
                            $this->result = new WaseManager('load', $this->temp);
                            break;
                        case 'Member':
                            $this->result = new WaseMember('load', $this->temp);
                            break;
                        case 'Waiter':
                            $this->result = new WaseWait('load', $this->temp);
                            break;
                        case 'DidYouKnow':
                            $this->result = new WaseDidYouKnow($this->temp);
                            break;
                    }
                } catch (Exception $error) {
                    /* If an error, set result to false */
                    $this->result = false;
                }
            }
        }
    }

    /**
     * Determine if any more elements in the resource.
     *
     *
     * @return bool true if yes, else false.
     */
    public function valid()
    {
        /* Are there any rows at all? */
        if (! $this->rows)
            return false;
            /* Are there additional items left in the the results resource */
        return (($this->row < $this->rows) ? true : false);
    }

    /**
     * Return current row number.
     *
     *
     * @return int current row number.
     */
    public function key()
    {
        return $this->row;
    }

    /**
     * Return current row value.
     *
     *
     * @return object current row value (Wase object).
     */
    public function current()
    {
        return $this->result;
    }

    /**
     * Return total count of rowsin result set.
     *
     *
     * @return int count of rows.
     */
    public function entries()
    {
        $this->rewind();
        return $this->rows;
    }
    

    /**
     * Return the MySQL query string.
     *
     *
     * @return string the MySQL query string.
     */
    public function querystring()
    {
        return $this->query;
    }
}
?>