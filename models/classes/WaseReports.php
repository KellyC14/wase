<?php

/**
 * This class contains a set of SQL select statements that are used to generate reports, and procedures to invoke
 * those statements and return the results.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseReports
{
    
    /*
     * We start be defining a set or reports as SQL query strings with variable subsitutions.  
     */
    
    private static function apptsquery() {
    
        // Get database name
        $d = WaseUtil::getParm('DATABASE');
        return 
            'SELECT ' .
            'date('.$d.'WaseAppointment.startdatetime) as "Start Date", ' . 
            'time('.$d.'WaseAppointment.startdatetime) as "Start Time", ' .
            'date('.$d.'WaseAppointment.enddatetime) as "End Date", ' .
            'time('.$d.'WaseAppointment.enddatetime) as "End Time", ' .
            $d.'WaseAppointment.name as "For name", ' .
            $d.'WaseAppointment.email as "For email", '.
            $d.'WaseAppointment.phone as "For phone", '.
            $d.'WaseAppointment.userid as "For userid", '.
            $d.'WaseBlock.name as "With name", ' .
            $d.'WaseBlock.email as "With email", '.
            $d.'WaseBlock.phone as "With phone", '.
            $d.'WaseBlock.userid as "With userid", '.
            $d.'WaseBlock.location as "Location", '.
            $d.'WaseAppointment.purpose as Purpose, ' .
            
            'FROM '.$d.'WaseAppointment, '.$d.'WaseBlock ' .
           
            'WHERE '.$d.'WaseBlock.blockid = '.$d.'WaseAppointment.blockid ' ;
          
     
    }
    /**
     * This report returns a list of appointments that meet the specified search criteria, in the format required by buildorderedselect.
     *
     * @static
     *
     * @param array $criteria  
     *            An associative array of selection criteria.
     *
     * @return array  
     *            The matching appointments as an array of associative arrays. 
     */
    static function appointemts($criteria,$orderby=NULL)
    {
       // Get our select statement, and substitute the selection criteria
       $select = self::msgsub(self::apptsquery());
       // Add in the selection criteria
       $select .= ' AND ' . self::msgsub($criteria);
       // Add in any order
       if ($orderby)
           $self .= 'ORDER BY ' . $orderby;
       // Do the query, get the data, and return the associative array of result
       $results = WaseSQL::doQuery($select);
       $retarray = array();
       while ($row = WaseSQL::doFetch($results)) {
           $retarray[] = $row;
       }
       // Return the results
       return $retarray;   
             
    }
    
    static private function msgsub($criteria) {
        $connect = 'AND';
        $select = '';
        foreach ($criteria as $subarray) {
            /* Allow caller to specify key AND comparator AND connector */
            @list ($kkey, $operator, $connector, $paren) = explode(',', $subarray[0]);
            if (! $operator)
                $operator = '=';
            /* Add conector to the select string */
            if ($select)
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
           
        }
        
        return $select;
    }
    
   
}
?>