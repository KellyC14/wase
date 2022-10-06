<?php

/**
 * This class describes a WaseAlert and provides a set of methods to create, delete and send alerts.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * 
 */
class WaseAlert
{
    
    /* Properties */
    
    /**
     *
     * @var int $alertid Sequential identifier of alert in WaseAlert database table.
     */
    public $alertid;

    /**
     *
     * @var int $calendarid Calendar to which alert applies.
     */
    public $calendarid;

    /**
     *
     * @var string $datestart Starting date for alert date range.
     */
    public $datestart;

    /**
     *
     * @var string $timestart Starting time for alert date range.
     */
    public $timestart;

    /**
     *
     * @var string $dateend Ending date for alert date range .
     */
    public $dateend;

    /**
     *
     * @var string $timeend Ending time for alert date range.
     */
    public $timeend;

    /**
     *
     * @var string $timeend serid/netid of owning user.
     */
    public $userid;

    /**
     *
     * @var string $email Target email for alert.
     */
    public $email;

    /**
     *
     * @var string $message Text of alert.
     */
    public $message;

    /**
     *
     * @var string $type Type of alert (text string, default=APPSLOT).
     */
    public $type;

    /**
     *
     * @var int $sentcount How often sent
     */
    public $sentcount;
    
    /* Static (class) methods */
    
    /**
     * This method creates an alert by adding an entry to the WaseAlert table.
     *
     * @param
     *            int calendarid Database table id of owning calendar.
     * @param string $calendarid,$datestart,$timestart,$dateend,$timeend,$userid,$email,$message,$type
     *            All of the alert property values.
     *            
     * @return int Database id of the inserted WaseAlert record.
     */
    static function create($calendarid, $datestart, $timestart, $dateend, $timeend, $userid, $email, $message, $type)
    {
        
        /* Set defaults */
        if ($timestart == "")
            $timestart == "00:00:00";
        if ($dateend == "")
            $dateend == $datestart;
        if ($timeend == "")
            $timeend == "23:59:59";

        // Get our directory class
        $directory = WaseDirectoryFactory::getDirectory();

        if ($email == "")
            $email = $directory->getEmail($userid);
        
        if ($type == "")
            $type = 'APPSLOT';
            
            /* Now write out the record */
        $insert = WaseSQL::doQUERY('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WaseAlert (calendarid,datestart,timestart,dateend,timeend,userid,email,message,type) VALUES (' . WaseSQL::sqlSafe($calendarid) . ',' . WaseSQL::sqlSafe($datestart) . ',' . WaseSQL::sqlSafe($timestart) . ',' . WaseSQL::sqlSafe($dateend) . ',' . WaseSQL::sqlSafe($timeend) . WaseSQL::sqlSafe($userid) . ',' . WaseSQL::sqlSafe($email) . ',' . WaseSQL::sqlSafe($message) . ',' . WaseSQL::sqlSafe($type) . ');');
        if (! $insert)
            return $insert;
        else
            return WaseSQL::insert_id();
    }

    /**
     * This method sends an alert.
     *
     * @param
     *            int Database id of the alert record.
     *            
     * @return null
     */
    static function issue($alertid)
    {
        
        /* Read in the alert */
        $alert = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAlert WHERE alertid=' . WaseSQL::sqlSafe($alertid) . ';'));
        /* If found, send it out */
        if ($alert) {
            /* Allow the user to specify multiple emails, separated by a comma */
            $emails = explode(',', $alert['email']);
            // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
            foreach ($emails as $email) {
                WaseUtil::Mailer($email, $alert['message'], $alert['message']);
            }
            /* And update the sent count */
            WaseSQL::doQUERY('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAlert SET sentcount=sentcount+1 WHERE alertid=' . $alertid . ' LIMIT 1;');
        }
    }

    /**
     * This function sends out all alerts for a given date range.
     *
     * @param string $datestart
     *            Starting date.
     * @param string $timestart
     *            Starting time.
     * @param string $dateend
     *            Ending date.
     * @param string $timeend
     *            Ending time.
     * @param string $message
     *            Alert to send (overriding the text in the alert record).
     *            
     * @return null
     */
    static function issueall($datestart, $timestart, $dateend, $timeend, $message = '')
    {
        
        /* Set defaults */
        if ($timestart == "")
            $timestart == "00:00:00";
        if ($dateend == "")
            $dateend == $datestart;
        if ($timeend == "")
            $timeend == "23:59:59";
            
            /* Locate all of the matching entries */
        $alerts = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAlert WHERE ((datestart>=' . WaseSQL::sqlSafe($datestart) . ' AND timestart>=' . WaseSQL::sqlSafe($timestart) . ') AND (dateend<=' . WaseSQL::sqlSafe($dateend) . ' AND timeend<=' . WaseSQL::sqlSafe($timeend) . '));');
        
        /* Now go through and send out the alerts */
        while ($alert = WaseSQL::doFetch($alerts)) {
            /* Issue the alert */
            /* Allow the user to specify multiple emails, separated by a comma */
            $emails = explode(',', $alert['email']);
            // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
            foreach ($emails as $email) {
                /* If caller did not provided a message, then use it instead of the saved one */
                if ($message)
                    $alert['message'] = $message;
                WaseUtil::Mailer($email, $alert['message'], $alert['message']);
            }
            /* And update the sent count */
            WaseSQL::doQUERY('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseAlert SET sentcount=sentcount+1 WHERE alertid=' . $alert['alertid'] . ' LIMIT 1;');
        }
    }
    
    /* Dynamic (object) methods */
}
?>