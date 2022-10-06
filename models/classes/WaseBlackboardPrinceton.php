<?php
/**
 * This class defines the methods that must be implemented for WASE to communicate with Blackboard at Princeton
 * (using the local Princeton web services).
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 */
class WaseBlackboardPrinceton implements WaseLMS
{

    /**
     * Return the role of a given user in a given course.
     *
     * @static
     *
     * @param string $courseid
     *            The course identifier.
     * @param string $userid
     *            The user identifier.
     *            
     * @return array the roles a user has in a course; if not in course, or course does not exist,
     *         the first element will start with the string 'ErrorCodes'. If the user is in the
     *         course, each array element will have one of the user's roles in the course.  Example:
     *         
     *         row 1: STUDENT
     *         row 2: ASSISTANT
     */
    public static function getUserRole($userid, $course) 
    {
        
        /* Build SOAP client. */
        $client = new SoapClient(WaseUtil::getParm('BBWSDL'));
       
        /* Call the web service and return the data */
        return $client->getUserRole($userid, $course, WaseUtil::getParm('BBCLIENT'), WaseUtil::getParm('BBKEY'));
       
    }

    /**
     * Return list of courses in which a given user is enrolled.
     *
     * @static
     *
     * @param string $userid
     *            the given userid
     *            
     * @return array the courses a user is enrolled in; if not in course, or user does not exist,
     *         the first element will start with the string 'ErrorCodes'. If user is in at
     *         least one course, the array will contain a set of rows, each row having the course name,
     *         course id and course title, all seperated by the '|' symbol.  Example:
     *         
     *                
     *         row 1: Economics 101|ECON101|Macro Economics
     *         row 2: Math 110|MAT110|Introduction to Calculus
     *         
     */
    public static function getEnrollments($userid)
    {
        
        /* Build SOAP client. */
        $client = new SoapClient(WaseUtil::getParm('BBWSDL'));
        
        /* Call the web service and return the data */
        return $client->getEnrollments($userid, WaseUtil::getParm('BBCLIENT'), WaseUtil::getParm('BBKEY'));
       
    }

    /**
     * Return list of users enrolled in a course, along with their roles.
     *
     * @static
     *
     * @return array the users enrolled in a given course; if the course does not exist, the first
     *         element of the array will contain "ErrorCodes", otherwise the array will contain a set of rows,
     *         each row having the userid and primary role, seperated by the '|' symbol. Example:
     *         
     *          *         
     *         row 1: SGOLDSTEIN|Student
     *         row 2: RCKELLY|Instructure
     *        
     */
    public static function getCourseMembership($course_name)
    {

        /* Build SOAP client. */
        $client = new SoapClient(WaseUtil::getParm('BBWSDL'));
    
        /* Call the web service and return the data */
        return $client->getAnyCourseMemberships($course_name, WaseUtil::getParm('BBCLIENT'), WaseUtil::getParm('BBKEY'));
        
    }
    
    /**
     * Register WASE as a tool.
     *
     * The Princeton Blackboard web services use static registration, so this method need not be called.
     * @static
     *
     * @return bool true if registered, else false
     *
     */
    public static function register()
    {
        return TRUE;
    }
    
}
?>