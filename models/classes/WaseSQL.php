<?php

/**
 * This (static) class handles all SQL interactions.  It currently uses mysqli.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseSQL
{

    /**
     * @var resource $DBHANDLE Private variable holds the database handle.
     */
    private static $DBHANDLE = false;
    
    /**
     * @var resource $ALT_DBHANDLE Private variable holds the alternate database handle.
     */
    private static $ALT_DBHANDLE = false;
    

    /**
     * @var string $CHARSET Private variable sets our character set.
     */
    private static $CHARSET = 'utf8';
    
    
    
    /*
     * Get the current database parameters.
     * 
     * @param parm string
     *          The requested parameter
     * @return string
     *          The parameter value.
     */
    static function getParm($parm) {
        switch(strtoupper($parm)) {
            case 'HOST':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? $_SESSION['ALT_HOST'] : WaseUtil::getParm('HOST');
                break;
            case 'USER':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? $_SESSION['ALT_USER'] : WaseUtil::getParm('USER');
                break;
            case 'PASS':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? $_SESSION['ALT_PASS'] : WaseUtil::getParm('PASS');
                break;
            case 'DATABASE':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? $_SESSION['ALT_DATABASE'] : WaseUtil::getParm('DATABASE');
                break;
            case 'CHARSET':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? $_SESSION['ALT_CHARSET'] : self::$CHARSET;
                break;
            case 'DBHANDLE':
                return (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true) ? self::$ALT_DBHANDLE : self::$DBHANDLE;
                break;
            default:
                die("Unknow parameter $parm passed to getParm in WaseSQL.");                           
        }
    }

    /*
     * Clear the DB handle (for a new institution), 
     */
    static function clearHandle() {
        if (self::$DBHANDLE)
            @mysqli_close(self::$DBHANDLE);
        self::$DBHANDLE = false;
    }
    
    /*
     * Get the current database parameters.
     * 
     * @param parm string
     *          The requested parameter
     * @param value database handle
     *          The parameter value.
     *          
     *  
     */
     static function setParm($parm, $value) {
        switch(strtoupper($parm)) {
            case 'DBHANDLE':
                if (isset($_SESSION['ALT_SQL']) && $_SESSION['ALT_SQL'] == true)
                    self::$ALT_DBHANDLE = $value;
                else 
                    self::$DBHANDLE = $value;
                break;
            default:
                die("Unknow parameter $parm passed to setParm in WaseSQL.");
        } 
     }

    /**
     * Open the database and set the handle.
     *
     * @static
     *
     *
     * @return void
     */
    static function SetHandle()
    {
        
        /*
         * We want to be able to specify a remote host.  This is used, for example, by the code that migrates data from wass to wase.
         * 
         * We set our connect parameters as per our config file, or as per session variables (to override the config file);
         */ 
        
        if (! self::getParm('DBHANDLE')) {
            if (! $handle = mysqli_connect(self::getParm('HOST'), self::getParm('USER'), self::getParm('PASS'), self::getParm('DATABASE'))) {
                die('Please report the following MySQL error to your IT support staff: ' . mysqli_connect_error());
            }
            /* If successful, set the handle and character set */
           self::setParm('DBHANDLE',$handle);
           if (function_exists('mysqli_set_charset'))
                $cset = mysqli_set_charset(self::getParm('DBHANDLE'), self::getParm('CHARSET'));
            if (! $cset)
                mysqli_query(self::getParm('DBHANDLE'), 'SET NAMES "' . self::getParm('CHARSET') . '"');
        }
    }

    /**
     * Return database handle.
     *
     * @static
     *
     *
     * @return resource The database handle.
     */
    static function GetHandle()
    {
        // Handle alternate database handle
        /* If not open, try to open the database */
        self::SetHandle();
        return self::getParm('DBHANDLE');
    }

    /**
     * Open the database and set the handle.
     *
     * @static
     *
     *
     * @return resource The database handle.
     */
    static function openDB()
    {
        return self::GetHandle();
    }

    /**
     * Execute a SQL query.
     *
     * @static
     *
     * @param string $query
     *            The SQL query.
     *            
     * @return resource|NULL resource returned by the SQL query.
     */
    static function doQuery($query)
    {
        /* Execute the query. */
        $ret = mysqli_query(self::openDB(), $query);
        /* If error, log the error */
        if ($ret === false) {
            WaseMsg::logMsg('Mysqli error ' . mysqli_errno(self::GetHandle()) . ' = ' . mysqli_error(self::GetHandle()) . '. Generated from query: ' . $query . "\r\n");
        }
        return $ret;
    }

    /**
     * Get next record from a query.
     *
     * @static
     *
     * @param resource $resource
     *            The SQL query resource.
     *            
     * @return array|NULL associative array of the record.
     */
    static function doFetch($resource)
    {
        /* Execute and return the result. */
        return mysqli_fetch_assoc($resource);
    }

    /**
     * Get all records from a query.
     *
     * @static
     *
     * @param resource $resource
     *            The SQL query resource.
     *
     * @return array|NULL associative array of tall of the rows.
     */
    static function doFetchAll($resource)
    {
        /* Execute and return the result. */
        return mysqli_fetch_all($resource);
    }

    /**
     * Get count of rows affected by preceeding query.
     *
     * @static
     *
     * @param resource $resource
     *            The SQL query resource.
     * @return int The row count.
     */
    static function affected_rows()
    {
        /* Execute and return the result. */
        return mysqli_affected_rows(self::openDB());
    }

    /**
     * Get count of rows available.
     *
     * @static
     *
     * @param resource $resource
     *            The SQL query resource.
     * @return int The row count.
     */
    static function num_rows($resource)
    {
        /* Execute and return the result. */
        return mysqli_num_rows($resource);
    }

    /**
     * Release a resource.
     *
     * @static
     *
     * @param resource $resource
     *            The SQL query resource.
     * @return void
     */
    static function freeQuery($resource)
    {
        /* Free the resource. */
        @mysqli_free_result($resource);
        return;
    }

    /**
     * Get id number from latest INSERT.
     *
     * @static
     *
     * @return int The Database record id (from auto_number).
     */
    static function insert_id()
    {
        /* Execute and return the result. */
        return mysqli_insert_id(self::openDB());
    }

    /**
     * Issue a SELECT.
     *
     * This method issues a SELECT against a specified database table, building
     * a WHERE clause as per the arguments (an associative array). It returns the
     * php resource that results from the query, or NULL.
     *
     * @static
     *
     * @param string $table
     *            The database table name.
     * @param array $array
     *            The array containing the SELECT where criteria.
     *            
     * @return resource The resource resulting from the query (record set).
     */
    static function selectDB($table, $arr)
    {
        /* Build the select string and return the results. */
        return self::doQuery(self::buildSelect($table, $arr));
    }

    /**
     * Get error message from latest SQL query.
     *
     * @static
     *
     * @return string|NULL error message, if any.
     */
    static function error()
    {
        /* Execute and return the result. */
        return mysqli_error(self::openDB());
    }

    /**
     * Issue a SELECT.
     *
     * This function returns a MySQL query string from the supplied argument array.
     * The array contains variable names and value; the variable names may include a
     * comma and an operator to be used for the SELECT (default operator is equal).
     * The variables are ANDED together, unless another connector is passed.
     *
     * @static
     *
     * @param string $table
     *            The database table name.
     * @param array $array
     *            The array containing the SELECT where criteria.
     * @param string $orderby
     *            Optionaly, and 'ORDER BY' SQL clause.
     *            
     * @return resource The resource resulting from the query (record set).
     */
    static function buildSelect($table, $arr, $orderby = '')
    {
        /* Build the SELECT string */
        $select = 'SELECT * FROM ' . self::getParm('DATABASE') . '.' . $table . ' WHERE ';
        $first = true;
        $connect = 'AND';
        foreach ($arr as $key => $value) {
            /* Allow caller to specify key AND comparator AND connector */
            @list ($kkey, $operator, $connector, $paren) = explode(',', $key);
            if (! $operator)
                $operator = '=';
                /* Add conector to the select string */
            if (! $first)
                $select .= ' ' . $connect . ' ';
            else
                $select .= '(';
                /* Add sub-paren if requested */
            if ($paren == '(')
                $select .= '(';
                /* Add ckey and operator to the select string */
            if ($operator == 'LIKE')
                $select .= $kkey . ' LIKE ';
            elseif ($operator == 'IN')
                $select .= $kkey . ' IN ';
            else
                $select .= $kkey . $operator;
                /* Add value to the connect string */
            if ($operator == 'IN')
                $select .= $value;
            else
                $select .= self::sqlSafe($value);
                /* Add closing paren if requested */
            if ($paren == ')')
                $select .= ')';
                /* Reset connector if requested */
            if ($connector)
                $connect = $connector;
            $first = false;
        }
        /* Terminate the string and add any orderby specified */
        $select .= ') ' . $orderby;
        /* Return the select string. */
        return $select;
    }

    /**
     * Issue a SELECT.
     *
     * This function returns a MySQL query string from the supplied argument array of arrays.
     * The arrays contains variable names and value; the variable names may include a
     * comma and an operator to be used for the SELECT (default operator is equal).
     * The variables are ANDED together, unless another connector is passed.
     *
     * @static
     *
     * @param string $table
     *            The database table name.
     * @param array $array
     *            The array containing the SELECT where criteria.
     * @param string $orderby
     *            Optionaly, an 'ORDER BY' SQL clause.
     * @param string $fields
     *            Optionaly, a comma-delimited list of fields to return.
     *            
     * @return resource The resource resulting from the query (record set).
     */
    static function buildOrderedSelect($table, $arr, $orderby = '', $fields = '')
    {
        
        /* If not open, try to open the database */
        $dhandle = self::GetHandle();
        /* Build the SELECT string */
        $select = 'SELECT ';
        if ($fields)
            $select .= $fields;
        else
            $select .= '*';
        $select .= ' FROM ' . self::getParm('DATABASE') . '.' . $table . ' WHERE ';
        $first = true;
        $connect = 'AND';
        foreach ($arr as $subarray) {
            /* Allow caller to specify key AND comparator AND connector */
            @list ($kkey, $operator, $connector, $paren) = explode(',', $subarray[0]); 
            if (! $operator)
                $operator = '=';
                /* Add conector to the select string */
            if (! $first)
                $select .= ' ' . $connect . ' ';
            else
                $select .= '(';
                /* Add sub-paren if requested */
            if ($paren == '(')
                $select .= '(';
                /* Add ckey and operator to the select string */
            if ($operator == 'LIKE')
                $select .= $kkey . ' LIKE ';
            elseif ($operator == 'IN')
                $select .= $kkey . ' IN ';
            else
                $select .= $kkey . $operator;
                /* Add value to the connect string */
            if ($operator == 'IN')
                $select .= $subarray[1];
            else
                $select .= self::sqlSafe($subarray[1]); 
                
                /* Add closing paren if requested */
            if ($paren == ')')
                $select .= ')';
                /* Reset connector if requested */
            if ($connector)
                $connect = $connector;
                /* Reset first flag */
            $first = false;
        }
        /* Terminate the string and add any orderby specified */
        $select .= ') ' . $orderby;
        /* Return the select string. */
        return $select;
    }

    /**
     * Sanatize a SQL query string.
     *
     * @static
     *
     * @param string $value
     *            The input string.
     *            
     * @return string The sanatized string.
     */
    static function sqlSafe($value)
    {
        // Stripslashes
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            $ret = stripslashes($value);
        } else
            $ret = $value;
            // Quote if not a number or a numeric string
        if (! (is_numeric($ret))) {
            $link = self::openDB();
            return "'" . mysqli_real_escape_string($link, $ret) . "'";
        } else
            return $ret;
    }

    /**
     * Convert a date to SQL format.
     *
     * @static
     *
     * @param string $date
     *            The input date..
     *            
     * @return string The SQL-formatted date.
     */
    static function sqlDate($date)
    {
        if (! $date)
            return '';
        if (strpos($date, '-') !== false)
            return $date;
        else {
            list ($m, $d, $y) = explode('/', $date);
            return sprintf("%04u-%02u-%02u", $y, $m, $d);
        }
    }
}
?>