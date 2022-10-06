<?php

/**
 * This class contains (error) messages, as well as routines to build and save the error (and informatory) messages.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class WaseMsg
{

    /**
     * @var array $msg Set up all of the messages in a static array.
     */
    public static $msg = array(
        0 => '',
        1 => 'The system is down.  Please contact ',
        2 => 'This system must be invoked using HTTPS.  Please contact: %%%%1',
        3 => 'Unable to parse specified XML for simplexml: %%%%1 original: %%%%2',
        4 => 'Specified XML does not validate: %%1',
        5 => 'Specified XML has no request action: %%1',
        6 => 'Invalid password',
        7 => 'Email required if no userid in call to login',
        8 => 'Specified parameter %%1 does not exist',
        9 => 'Insufficient elements for %%1 in: %%2',
        10 => 'Invalid %%1 for %%2 : %%3',
        11 => '%%1 required for %%2',
        12 => 'User %%1 does not own %%2',
        13 => 'Missing %%1 data for %%2 : %%3',
        14 => '%%1',
        15 => 'Errors validating data: %%1',
        16 => 'Error in SQL: %%1',
        17 => 'Errors validating block data: %%1',
        18 => 'Errors validating calendar data: %%1',
        19 => 'Errors validating period data: %%1',
        20 => 'Errors validating series data: %%1',
        21 => 'Session not authenticated',
        22 => '%%1 may not view the specified %%2',
        23 => 'Guests may not create calendars',
        24 => 'User %%1 is not authorized to create/edit a calendar for user %%2',
        25 => 'The following blocks were not updated: %%1',
        26 => 'Specified %%1 does not belong to %%2',
        27 => 'User %%1 not authorized to create %%2 for user %%3',
        28 => 'Specified email %%1 does not match authenticated email %%2',
        29 => 'Userid or email address must be specified when creating an appointment.',
        30 => 'Calendar already exists for user %%1',
        31 => '%%1 argument missing for iCalendar call',
        32 => 'Start and end times must be specified',
        33 => 'Appointment cancellation deadline has been reached; you must contact the %%1 owner to cancel this appointment.',
        34 => 'You must specify a purpose for this appointment.',
        35 => 'User %%1 is allready a %%2 for calendar %%3.',
        36 => 'Invalid value for whichblocks: %%1',
        37 => 'User %%1 already has a wait list entry for calendar %%2',
        38 => 'Errors validating waitlist data: %%1',
        39 => 'Function %%1 does not exist',
        40 => 'Owners and Managers cannot be members of their own calendars',
        41 => 'Members cannot manage a calendar of which they are a member',
        42 => 'An owner cannot be a %%1 for their own calendar',
        43 => '%%1 is not a valid %%6 %%2.  However, it is the first word of the email address %%3 for user %%4 %%5.',
        44 => 'Email text required',
    )
    ;

    /**
     * Return message with substitution.
     *
     * @static
     *
     * @param int $msgnum
     *            the message number
     * @param array $subs
     *            an array specifying substitutions in numeric order.
     * @return string the message.
     */
    static function getMsg($msgnum, $replace)
    {
        
        /* First, grab the message text */
        if (!$msgnum)
               return $replace;
        if ($msgnum >= count(self::$msg))
            return $replace;
        
        $msgtext = self::$msg[$msgnum];
        
        /* If nothing to substitute, just return the message */
        if (! $replace)
            return $msgtext;

        return self::msgsub($msgtext, $replace);
        
    }
    
    
    static function msgsub($msgtext,$replace) {
        /* Do the substitution */
        if (is_array($replace)) {
            $search = array();
            for ($i = 0; $i < count($replace); $i ++) {
                $search[] = '%%' . ($i + 1);
            }
            $msg = str_replace($search, $replace, $msgtext);
        } else {
            $msg = str_replace('%%1', $replace, $msgtext);
        }
         
        return $msg;
    }

    /**
     * Log an error message.
     *
     * @static
     *
     * @param int $msg
     *            the message
     * @return void
     */
    static function logMsg($msg)
    {
        if (! $msg)
            return;
        $fmsg = date('Y-m-d G:i:s') . ': ' . $msg . "\r\n" . print_r(debug_backtrace(), true) . "\r\n" . "\r\n";
        error_log($fmsg);
        // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
        if ($logmail = WaseUtil::getParm('LOGMAIL'))
            WaseUtil::Mailer($logmail, 'WASE Error ', $fmsg);
    }
    
    /**
     * Log a message.
     *
     * @static
     *
     * @param int $msg
     *            the message
     * @return void
     */
    static function log($msg)
    {
        if (! $msg)
            return;
        
        error_log($msg);
        
   
    }
    
    /**
     * Email a debug message.
     * 
     * If debugging is on for the specified $debug category, email the msg to LOGMAIL (parm).
     * The debug categories are set in the string assigned to the DEBUG parm.
     *
     * @static
     *          
     * @param string $debug
     *          the debug category 
     *
     * @param string $subject
     *          the subject of the email
     *          
     * @param string $msg
     *          the message
     *            
     * @return void
     */
    static function dMsg($debug,$subject,$msg="No message")
    {
        if (!$subject  || !$debug || (strpos(WaseUtil::getParm('DEBUG'),trim($debug)) === false))
            return;

        // Log the message
        self::log($subject.':'.$msg);
        // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
        if ($logmail = WaseUtil::getParm('LOGMAIL'))
            WaseUtil::Mailer($logmail, 'WASE DEBUG ' . $debug, $subject, $msg);

    }
}

?>