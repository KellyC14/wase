<?php

/**
 * This class implements some class (static) methods that are useful for reading email.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseEmail
{
    
    public static $email_array = array();
    

    /**
     * Read email messages into our static array ($email_array).
     *
     * @static
     * @private
     *
     * @param resource $resource
     *
     * @return true if messages read in, else false.
     *
     */
    public static function getMessages($resource=false)
    {
        
        // Login if required
        if (!$resource)
            $resource = self::login();
        
        // Init the email array
        self::$email_array = array();
    
        if (!$resource)
            return false;
        
        // Get count of messages
        if (!$count = imap_num_msg($resource))
            return false;
        
        // Loop through and read in the messages
        for ($i = 1;  $i <= $count; $i++) {
            self::$email_array[$i]['header'] = imap_headerinfo($resource,$i);
            self::$email_array[$i]['body'] = imap_body($resource, $i);
            self::$email_array[$i]['structure'] = imap_fetchstructure($resource, $i);
            // Now read in any attachments
            self::$email_array[$i]['attachments'] = array();
            if(isset(self::$email_array[$i]['structure']->parts) && count(self::$email_array[$i]['structure']->parts)) {
    
                for($j = 0; $j < count(self::$email_array[$i]['structure']->parts); $j++) {
    
                    self::$email_array[$i]['attachments'][$j] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );
    
                    if(self::$email_array[$i]['structure']->parts[$j]->ifdparameters) {
                        foreach(self::$email_array[$i]['structure']->parts[$j]->dparameters as $object) {
                            if(strtolower($object->attribute) == 'filename') {
                                self::$email_array[$i]['attachments'][$j]['is_attachment'] = true;
                                self::$email_array[$i]['attachments'][$j]['filename'] = $object->value;
                            }
                        }
                    }
    
                    if(self::$email_array[$i]['structure']->parts[$j]->ifparameters) {
                        foreach(self::$email_array[$i]['structure']->parts[$j]->parameters as $object) {
                            if(strtolower($object->attribute) == 'name') {
                                self::$email_array[$i]['attachments'][$j]['is_attachment'] = true;
                                self::$email_array[$i]['attachments'][$j]['name'] = $object->value;
                            }
                        }
                    }
    
                    if(self::$email_array[$i]['attachments'][$j]['is_attachment']) {
                        self::$email_array[$i]['attachments'][$j]['attachment'] = imap_fetchbody($resource, $i, $j+1);
                        if(self::$email_array[$i]['structure']->parts[$j]->encoding == 3) { // 3 = BASE64
                            self::$email_array[$i]['attachments'][$j]['attachment'] = base64_decode(self::$email_array[$i]['attachments'][$j]['attachment']);
                        }
                        elseif(self::$email_array[$i]['structure']->parts[$j]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            self::$email_array[$i]['attachments'][$j]['attachment'] = quoted_printable_decode(self::$email_array[$i]['attachments'][$j]['attachment']);
                        }
                    }
                }
            }
    
    
        }
        
        return true;
    
    }
    

    /**
     * Read iCal attachments, if any, and return as an array.
     *
     * @static
     * @private
     *
     * @param resource $resource
     *
     * @return array of iCal streams, if any
     *
     */
    public static function getIcals($resource=null)
    {
        // Start by reading in any email
       self::getMessages($resource);
       
       // Loop through the messages array and extract iCal streams.
       $iCals = array();
       
       foreach (self::$email_array as $email) {
           if (($start = strpos($email['body'],'BEGIN:VCALENDAR')) !== false) {
               if (($end = strpos($email['body'],'END:VCALENDAR')) !== false) {
                   $iCals[] = substr($email['body'],$start,($end-$start)+13);
               }
           }
       }
       
       return $iCals;
       
    }
  
    /**
     * Delete all accumulated email.
     *
     * @static
     * @private
     *
     * @param resource $resource
     *
     * @return int count of messages deleted.
     *
     */
    public static function cleanup($resource=null)
    {
        // Login if required
        if (!$resource)
            $resource = self::login();
        
        // Go through all messages and delete them.
        self::$email_array = array();
        
        // Get count of messages
        if (!$count = imap_num_msg($resource))
            return false;
        
        // Loop through and delete the messages
        for ($i = 1;  $i <= $count; $i++) {
            imap_delete ( $resource , $i );
        }
        // Now really delete them
        imap_expunge($resource);
        
        return $count;
        
    } 
    
    /**
     * Login to (Imap) email server.
     *
     * @static @private
     *
     *
     * @return imap_connection resource if successful, else false.
     *
     */
    private static function login()
    {
        
        if (!$server=WaseUtil::getParm('EMAILSERVER'))
            return false;
       
        if (!$user=WaseUtil::getParm('EMAILLOGIN'))
            return false;
        
        if (!$password=WaseUtil::getParm('EMAILPASS'))
            return false;
       
        return imap_open($server, $user, $password, NULL, 1, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));

    }
    

   
}
?>