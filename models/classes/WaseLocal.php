<?php
/**
 * This class contains local (institution) user exits.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseLocal {
          
    
    /*
     Define constants for function return values (where more than true/false is needed)
     */
    
    const DOCONTINUE = '';  /* The default (null) response; means code should continue normally. */
    
    const USERINCOURSE = 1;
    const USERNOTINCOURSE = 2;
    
    const COURSEISVALID = 1;
    const COURSEISNOTVALID = 2; 
    
    const GROUPISVALID = 1;
    const GROUPISNOTVALID = 2; 
    
     
    
    /**
     * PRINCETON:
     * 
     * Return a user full name from the directory, with class year appended.
     *
     *
     * @static
     *
     * @param string $netid
     *            netid. 
     *            
     * @return string|null found, else null.
     */

    static function princeton_getName($netid)
    {

        // Get our directory class
        // $directory = WaseDirectoryFactory::getDirectory();
        // For now, use LDAP
        $directory = new WaseDirectory();

        if (!$name = $directory->getlDIR($netid, WaseUtil::getParm('LDAPNAME')))
            return self::DOCONTINUE;
        
        // See if user is a student
        $year = '';
        $pustatus = trim($directory->getlDIR($netid, 'pustatus'));
        if (trim($pustatus) == 'undergraduate')
            $year = (int)$directory->getlDIR($netid, 'puclassyear');
        elseif (substr($pustatus,0,1) == 'u')
            $year = (int) substr($pustatus,1);
        
        // If not a student, just return the name
        if (!$year)
            return $name;
            
        if ($year < 100)
            $year = '20' . $year;
                
        return $name . ", " . $year; 
    
    }

    /**
     * PRINCETON:
     *
     * Add additional fields to the appointment report generated in My Appointments.
     *
     *
     * @static
     *
     * @param WaseAppointment $appts
     *              The appointment.
     * @param WaseBlock $block
     *              The Block
     * @param array $searchresult
     *              The array of fields/values to be returned.
     *
     * @return array
     *              The modified searchresult
     */

    static function princeton_exportappts($appt, $block, &$searchresult)
    {
        // Get our directory class
        // $directory = WaseDirectoryFactory::getDirectory();
        // For now, use LDAP
        $directory = new WaseDirectory();

        // Return the status of the appointment holder
        $searchresult['ApptForDepartment'] = $directory->getlDIR($appt->userid, 'puresidentdepartment');
        // Return the status of the appointment holder
        $searchresult['ApptWithDepartment'] = $directory->getlDIR($block->userid, 'puresidentdepartment');
        // All done
        return;

    }
   
}
?>