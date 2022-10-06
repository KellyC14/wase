<?php
/**
 * This class is a factory which creates LMS classes for WASE.
 * 
 * The LMS classes implement LMS-specific code to allow WASE to interact with an LMS.
 * multiple lines.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 */
class WaseLMSFactory {
    
    // Comma-seperated list of valid LMSes.
    const LMSes = "Blackboard,BlackboardPrinceton,BlackboardPrincetonRest";
     
    
    /**
     * This function returns an LMS class that corresponds to the LMS being run by the institution.
     *
     * The LMS parm specifies the type of LMS that is run by the institution.
     *
     *
     * @return object Thee LMS class which implements the waseLMS interface.
     */
    static function getLMS() {
        // Get the local LMS designation and see if it is valid.
        $lms = trim(WaseUtil::getParm('LMS'));
        if (!in_array($lms,explode(',',self::LMSes)))
            throw new Exception("Class $lms does not exist ... the ". WaseUtil::getParm('SYSID') . " configuration file may have a bad LMS parameter.");
        
        // Return the class if it exists
        $lmsclass = 'Wase'.$lms;
        
        if (class_exists($lmsclass)) 
            return new $lmsclass();
        else 
            throw new Exception("Class $lmsclass does not exist ... the ". WaseUtil::getParm('SYSID') . " configuration file may have a bad LMS parameter, or the class does not exist or was not found.");
        
    }
    

}
?>