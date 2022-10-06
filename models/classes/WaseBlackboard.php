<?php
/**
 * This class implements the WASE->LMS interface for Blackboard using Blackboard web services.
 * 
 * This class contains methods to determine course existence and class rosters.
 * 
 * Blackboard uses a number of terms to refer to courses, as follows:
 *    courseId is the user-visible course id typically referenced by users: ANT570_S2015 
 *    batchUid is blackboard's internal course id - usually the same as the courseId:  ANT570_S2015
 *    name is the user-visible course title:  ANT570_S2015 Interdisciplinary Research: Anthropology of Ethics 
 *    id is the internal Blackboard name: _6306_1  (these always start with an underscore)
 *    
 * Various functions require various ones of these, so it is imperative to pass the right identifier to
 * a function.  Some functions will accept more than one type of identifier along with a filter_type which
 * specifies which kind of identifier is being passed.
 * 
 * Blackboard uses a number of terms to refer to users, as follows:
 *    userId is the Blackboard internal user i=dentifier: _1632754_1 (always start with an underscore)
 *    name is the userid the user logs in with
 *    
 * Again, it is important to pass the right user identifier to a given function.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 */
class WaseBlackboard implements WaseLMS
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
     *         course, each array element will have one of the user's roles in the course. Example:
     *        
     *         row 1: STUDENT
     *         row 2: ASSISTANT
     */
    public static function getUserRole($userid, $courseid)
    {
        global $username, $password, $error;
        
        // Initialise a Context SOAP client object
        if (! $context_client = self::init()) {
            return array("ErrorCodes: $error");
        }
        
        // Login
        if ($error = self::login($context_client))
            return array("ErrorCodes: " . $error);
        
        
        // The first step is to convert the userid into an internal Blackboard userid (pk1)
        if (!$userpk1 = self::userid2pk1($userid))
            return array("ErrorCodes: " . $error);
        
        // Next, get the list of courses that the user is in.
        
        $coursepk1s = array();
        $member = new stdClass();
        $member->userid = $userid;
        
        try {
            $result = $context_client->getMemberships($member);
        } catch (Exception $e) {
            return array("ErrorCodes: getMemberships: " . $e->getMessage());
        }
        if (is_array($result->return)) {
            for ($i = 0; $i < count($result->return); $i++) {
                $coursepk1s[] = $result->return[$i]->externalId;       
            }
        }
        else 
            $coursepk1s[] = $result->return->externalId;
        
        if (!$coursepk1 = self::courseId2id($courseid))
            return array("ErrorCodes: $error");
        
        // Now get role of user $userpk1 in course $coursepk1
        
        // Initialise a User SOAP client object
        
        try {
            $membership_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/CourseMembership.WS?wsdl',self::setoptions());
            $membership_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/CourseMembership.WS?wsdl');
        } catch (Exception $e) {
            return array("ErrorCodes: INIT ERROR " . $e->getMessage());
        }
        
        $course = new stdClass();
        $course->courseId = $coursepk1;
        $ids = array($userpk1);

        $member = new stdClass();
        $member->courseId = $coursepk1;
        $member->f = new stdClass();
        $member->f->userIds = array($userpk1);
        $member->f->filterType = 6;
        
        
        try {
            $results = $membership_client->getCourseMembership($member);
        } catch (Exception $e) {
            return array("ErrorCodes: getCourseMembership: " . $e->getMessage());
        }
        if (!$memberships = $results->return)
            return array("Errorcodes: User $userid not found in course $courseid.");
        
        if (!is_array($memberships)) {
            $memberships = array();
            $memberships[] = $results->return;
        }
        
        // Build return array
        $roles = array();
        foreach ($memberships as $member) {
            $roles[] = self::rolename($member->roleId);
        }
        
        return $roles;
        
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
     *         course id and course title, all seperated by the '|' symbol. Example:
     *        
     *        
     *         row 1: Economics 101|ECON101|Macro Economics
     *         row 2: Math 110|MAT110|Introduction to Calculus
     *        
     */
    public static function getEnrollments($userid)
    {
        global $username, $password, $error;
        
        // Initialise a Context SOAP client object
        if (! $context_client = self::init()) {
            return array("ErrorCodes: $error");
        }
        
        // Login
        if ($error = self::login($context_client))
            return array("ErrorCodes: " . $error);
        
        
        // Get the list of courses in which user is enrolled.
        $coursepk1s = array();
        $member = new stdClass();
        $member->userid = $userid;
        
        try {
            $result = $context_client->getMemberships($member);
        } catch (Exception $e) {
            return array("ErrorCodes: getMemberships: " . $e->getMessage());
        }
        if (is_array($result->return)) {
            for ($i = 0; $i < count($result->return); $i++) {
                $coursepk1s[] = $result->return[$i]->externalId;
            }
        }
        else
            $coursepk1s[] = $result->return->externalId;
            
        
        // Now get details about each course
        try {
            $course_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/Course.WS?wsdl',self::setoptions());
            $course_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/Course.WS?wsdl');
            
        } catch (Exception $e) {
            return array("ErrorCodes: INIT ERROR " . $e->getMessage());
        }
        
        // Build course object to query the courses
        $course = new stdClass();
        $course->filter = new stdClass();
        $course->filter->ids = $coursepk1s;
        $course->filter->filterType = 3;
        
        
        try {
            $results = $course_client->getCourse($course);
        } catch (Exception $e) {
            return array("ErrorCodes: getCourse: " . $e->getMessage());
        }
        if (!$courses = $results->return)   
            return array("ErrorCodes: User $userid is not enrolled in any courses.");
        
        if (!is_array($courses)) {
            $courses = array();
            $courses[] = $results->return;
        }
        
        // Now build and return the results array
        $rarray = array();
        for ($i = 0; $i < count($courses); $i++) {
            $result = $courses[$i];
            $rarray[] =   $result->batchUid . "|" . $result->courseId . "|" . $result->name;
        }
        
        return $rarray;
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
     *         *
     *         row 1: SGOLDSTEIN|Student
     *         row 2: RCKELLY|Instructure
     *        
     */
    public static function getCourseMembership($courseid)
    {
        global $username, $password, $error;
       
        // Initialise a Context SOAP client object
        if (! $context_client = self::init()) {
            return array("ErrorCodes: $error");
        }
       
        
        // Login
       if ($error = self::login($context_client))
            return array("ErrorCodes: " . $error);
        
       // Get the pk1 of the target course
      if (!$coursepk1 = self::courseId2id($courseid))
          return array("ErrorCodes: $error");
  
       // Get the course membership using the pk1 of the course.
       if (!$members = self::membership($coursepk1))
           return array("ErrorCodes: $error");
       list($memberships, $pk1s) = $members;
        

       // Get details about all of the course members
       if (! $users = self::userDetails($pk1s))
           return array("ErrorCodes: $error");
        
       
       // Build return array
       $roles = array();
       for ($i = 0; $i < count($memberships); $i++) {
           $roles[] = $users[$i]->name . "|" . self::rolename($memberships[$i]->roleId);      
       }
       
       return $roles;
             
    }

    
    /**
     * Register WASE as a Blackboard tool.
     *
     * @static
     *
     * @return string message indicating result of registration.
     *        
     */
    public static function register()
    {       
       
       global $username, $password, $error;
       
       // Initialise a Context SOAP client object
       if (! $context_client = self::init()) {
            return $error;
        }
       
      
        $register_tool = new stdClass();
        $register_tool->clientVendorId = WaseUtil::getParm('BLACKBOARD_VENDOR_ID');
        $register_tool->clientProgramId = WaseUtil::getParm('BLACKBOARD_PROGRAM_ID');
        $register_tool->registrationPassword = WaseUtil::getParm('BLACKBOARD_REGISTRATION_PASSWORD');
        $register_tool->description = WaseUtil::getParm('BLACKBOARDS_TOOL_DESCRIPTION');
        $register_tool->initialSharedSecret = WaseUtil::getParm('BLACKBOARD_SHARED_SECRET');
        $register_tool->requiredToolMethods = array('Context.WS:loginTool', 'Context.WS:getMemberships', 'User.WS:getUser', 'Course.WS:getCourse',
                                                    'CourseMembership.WS:getCourseMembership','Course.User:VIEW');
        try {
            $result = $context_client->registerTool($register_tool);
            $ok = $result->return->status;
            if ($ok) {
                return "Success (now make the proxy tool available in Learn 9).";
            } else {
                $err = TRUE;
                return "Failed (may already be registered)\n";
            }
        } catch (Exception $e) {
            $err = TRUE;
            return "REGISTER ERROR: {$e->getMessage()}";
        }
    }

    /**
     * Convert a courseId into a pk1 (id).
     *
     * @static @private
     *
     * @param string $courseId
     *            the course identifier (ANT570_S2015)
     * @global string $error
     *            a string variable into which an error message can be stored
     *
     *
     * @return bool|string False if error, else the course id
     *
     *
     */
    private static function courseId2id($courseid) {
        
        global $error;
        
        // Init error to nulls
        $error = '';
        // Get a course context Soap client.
        try {
            $course_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/Course.WS?wsdl',self::setoptions());
            $course_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/Course.WS?wsdl');
        } catch (Exception $e) {
            $error = "INIT ERROR " . $e->getMessage();
            return false;
        }
        // Create a course object using the passed courseId.
        $course = new stdClass();
        $course->filter = new stdClass();
        $course->filter->courseIds = $courseid;
        $course->filter->filterType = 1; // 1 is for courseid, 2 for batchuid, 3 for pk1 (what BB calls "id").
         
        try {
            $results = $course_client->getCourse($course);
        } catch (Exception $e) {
            $error = "getCourse: " . $e->getMessage();
            return false;
        }
        
        if (!$coursedetail = $results->return) {
            $error = "Course $courseid not found.";
            return false;
        }
        return $coursedetail->id;  // $coursedetail->courseId $coursedetail->name
    }

     
     /**
     * Convert a user name (visible userid) into a pk1 (Blackboard id, e.g., _34534_1)\
     *
     * @static @private
     *
     * @param string $userid
     *            the user identifier (serge)
     * @param string $error
     *            a pointer to a string variable into which an error message can be returned=
     *
     *
     * @return bool|string False if error, else the user pk1
     *
     *
     */
    private static function userid2pk1($userid) {
        
        global $error;
        
        // Init error to nulls
        $error = '';
        // Create and set up a user_client object.
        try {
            $user_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/User.WS?wsdl',self::setoptions());
            $user_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/User.WS?wsdl');
        } catch (Exception $e) {
            $error = "INIT ERROR " . $e->getMessage();
            return false;
        }
        $user = new stdClass();
        $user->filter = new stdClass();
        $user->filter->name = array($userid);
        $user->filter->filterType = 6;
        // Get the user details
        try {
            $results = $user_client->getUser($user);
        } catch (Exception $e) {
            $error = "getUser: " . $e->getMessage();
            return false;
        }
        
        // Extract the pk1 (internal userid)
        $user = $results->return;
        
        if (!$user) {
            $error =  "User $userid not found.";
            return false;
        }
        
        return $user->id;
        
    }

   
   /**
     * Get memberships (enrollments) in a course.
     *
     * @static @private
     *
     * @param string $id
     *            the course id as a pk1 (_211456_1)
     * @param string $error
     *            a pointer to a string variable into which an error message can be returned
     *
     *
     * @return array|bool membership and pk1s arrays if successful, else false
     *
     *
     */
    private static function membership($id) {
        
        global $error;
        
        // Init error to nulls
        $error = '';
        
        // Initialise a User SOAP client object
       try {
           $membership_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/CourseMembership.WS?wsdl',self::setoptions());
           $membership_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/CourseMembership.WS?wsdl');
       } catch (Exception $e) {
           $error = "INIT ERROR " . $e->getMessage();
           return false;
       }
      
       // Init a course object with the passed id, and set the user array to nulls (we want all members)
       //$course = new stdClass();
       //$course->courseId = $id;
       $ids = array();
       $ids[] = '';
       
       $member = new stdClass();
       $member->courseId = $id;
       $member->f = new stdClass();
       $member->f->userIds = $ids;
       $member->f->filterType = 6;
       
       try {
           $results = $membership_client->getCourseMembership($member);
       } catch (Exception $e) {
           $error = "getCourseMembership: " . $e->getMessage();
           return false;
       }
       if (!$memberships = $results->return) {
           $error = "Course $id not found.";
           return false;
       }
       
       if (!is_array($memberships)) {
           $memberships = array();
           $memberships[] = $results->return;
       }
       
       // Eliminate unavailable members and save pk1s
       $available = array(); $pk1s = array();
       foreach ($memberships as $member) {
           if ($member->available) {
               $available[] = $member;
               $pk1s[] = $member->userId;
           }
       }
      
       return array($available, $pk1s);
       
    }

    /**
     * Get details about a list of users.
     *
     * @static @private
     *
     * @param array $pk1s the list of user pk1s (internal Blackboard ids, called UserId by Blackboard ;  _13462_1)
     *            the course id as a pk1 (_211456_1)
     * @param string $error
     *            a pointer to a string variable into which an error message can be returned
     *
     *
     * @return array|bool user objects if successful, else false
     *
     *
     */
    private static function userDetails($pk1s) {
        
        global $error;
        
        // Init error to nulls
        $error = '';
        
        // Create and set up a user_client object.
        try {
            $user_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/User.WS?wsdl',self::setoptions());
            $user_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/User.WS?wsdl');
        } catch (Exception $e) {
            $error = "INIT ERROR " . $e->getMessage();
            return false;
        }
         
       // Create user query object
       $user = new stdClass();
       $user->filter = new stdClass();
       $user->filter->id = $pk1s;
       $user->filter->filterType = 2;
       // Get the user details
       try {
           $results = $user_client->getUser($user);
       } catch (Exception $e) {
           $error = "getUser: " . $e->getMessage();
           return false;
       } 
       $users = $results->return;
      
       if (!is_array($users)) {
           $users = array();
           $users[] = $results->return;
       }
       
       return $users; 
         
    }  

    /**
     * Convert a Blackboard role designation to a visible role.
     *
     * @static @private
     *
     * @param string 
     *            a Blackboard role designator
     *
     * @return string
     *            the translated role name 
     *
     *
     */
    private static function rolename($role) {
        // Build the roles array to translate from Blackboard designations to roles
         $rolenames = array(
            "B"=>"COURSE_BUILDER",
            "T"=>"TEACHING_ASSISTANT",
            "S"=>"STUDENT",
            "P"=>"INSTRUCTOR",
            "G"=>"GRADER",
            "I"=>"GUEST",
            "NP"=>"NONPARTICIPANT"
        );
        $rolename = $rolenames[$role];
        if ($rolename)
            return $rolename;
        else 
            return "Unknown";
        
    }  
    /**
     * Initialize the soap context.
     *
     * @static @private
     *        
     * @param string $error
     *            a pointer to a string variable into which an error message can be returned
     *            
     *            
     * @return bool|BbSoapClient FALSE if failure, else a BbSoapClient object
     *        
     *        
     */
    private static function init()
    {  
              
      global $username, $password, $error;
      
      // Global variables for SOAP username and password
      $username = 'session';
      $password = 'nosession';
       
        try {
            $context_client = new BbSoapClient(WaseUtil::getParm('BLACKBOARD_URL') . '/webapps/ws/services/Context.WS?wsdl',self::setoptions());
            $context_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/Context.WS?wsdl');
        } catch (Exception $e) {
            $error = "INIT ERROR: {$e->getMessage()}";
            return FALSE;
        }
        // Set the URL
        $context_client->__setLocation(WaseUtil::getParm('BLACKBOARD_URL') . ':443/webapps/ws/services/Context.WS');
        return $context_client;
    }

    /**
     * Login to Blackboard.
     *
     * @static @private
     *        
     * @param string $context_client
     *            the SOAP object returned from init().
     *            
     * @return string null if successful, else error message.
     *        
     */
    private static function login($context_client)
    {
        global $username, $password;
        
        // Get a session ID
        try {
            $result = $context_client->initialize();
            $password = $result->return;
        } catch (Exception $e) {
            return "INITIALIZE ERROR: {$e->getMessage()}\n";
        }
        
        // Log in as a tool
        $input = new stdClass();
        $input->password = WaseUtil::getParm('BLACKBOARD_SHARED_SECRET');
        $input->clientVendorId = WaseUtil::getParm('BLACKBOARD_VENDOR_ID');
        $input->clientProgramId = WaseUtil::getParm('BLACKBOARD_PROGRAM_ID');
        $input->loginExtraInfo = ''; // not used but must not be NULL
        $input->expectedLifeSeconds = 3600;
        try {
            $result = $context_client->loginTool($input);
        } catch (Exception $e) {
            return "LOGIN ERROR: {$e->getMessage()}\n";
        }
        
        return '';
    }
    
    /**
     * Generate options array to turn off validation of self-signed certificates.
     * 
     * Set this array to nulls if certificate validation should be done.
     * 
     * @static $private
     * 
     * @returns array
     */
    private static function setoptions() {
        $options = array();
        $options['stream_context'] = stream_context_create(
            array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                )
            )
        );
        
        return $options;
    }
    
    
    
}
?>