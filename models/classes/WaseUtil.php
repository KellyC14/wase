<?php
/**
 * This class contains a number of static utility functions.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseUtil
{  
    
    /* XML header parameters */
    const XMLHEADER = '<?xml version="1.0" encoding="UTF-8"?>';
    
    /* Icalendar parameters */
    const ICALHEADER = "BEGIN:VCALENDAR\r\nPRODID:-//Princeton University/Web Appointment Scheduling Engine//EN\r\nVERSION:2.0\r\n";

    const ICALTRAILER = "END:VCALENDAR\r\n";

    /**
     * @var array $config stores the Symphony-parsed YAML configuration file.
     */
    static $config;

    /**
     * @var string $entitytrans stores an HTML entity translate table.
     */
    static $entitytrans = '';

    /**
     * This function returns a parameter from the WaseParms file if it exists, else it returns NULL.
     *
     * @static
     *
     * @param string $parm
     *            The wase parameter.
     *            
     * @return string|null parameter value, if it exists in the WaseParms file.
     */
    static function getParm($parm)
    {
        if (is_array(self::$config) && key_exists($parm, self::$config))
            return self::$config[$parm];
        else 
            return self::lookup($parm);
        
            
        // return self::lookup($parm); 
        
    }

    /**
     * This function returns a parameter from the INI yaml file if it exists, else it returns NULL.
     *
     * @static
     *
     * @param string $parm
     *            The wase parameter.
     *
     * @return mixed parameter value, if it exists in the config file, else null.
     */
    static function lookup($parm)
    {

        // Make sure config is an array.
        if (!is_array(self::$config))
            self::$config = array();

        // Make sure that the INSTITUTION is in the S_SERVER array.
        if (!$serverinst = @$_SERVER['INSTITUTION']) {
            if ($serverinst = @getenv('WASE_INSTITUTION')) {
                $_SERVER['INSTITUTION'] = $serverinst;
                // Put it in the session array as well
                if (session_status() == PHP_SESSION_NONE)
                    session_start();
                $_SESSION['INSTITUTION'] = $_SERVER['INSTITUTION'];
            }
        }

        // If we are passed a hostname via the environment, use that.
        if ($sname = @getenv("WASE_SERVER_NAME"))
            $_SERVER["SERVER_NAME"] = $sname;


        // Initialize config with our environment and server variables.
        if (count(self::$config) == 0) {
            // Build array of environment variables
            $envplusserver = @getenv();
            // Build our parm prefix value
            $globalpre = 'WASE_';
            $globaloff = strlen($globalpre);
            $inspre = $globalpre . $serverinst . '_';
            $insoff = strlen($inspre);
            // Iterate through envplusserver array and add in variables to $config, prefering institution version over global.
            foreach ($envplusserver as $key => $value) {
                // If an institution parm, use it
                if (strpos($key, $inspre) === 0) {
                    $usekey = substr($key, $insoff);
                    self::$config[$usekey] = $value;
                } // If a global parm, and value has not ben set, use the global parm
                elseif (strpos($key, $globalpre) === 0) {
                    $usekey = substr($key, $globaloff);
                    if (!key_exists($usekey, self::$config))
                        self::$config[$usekey] = $value;
                }
                // Else just use the raw key/value -- NO:  only pic up WASE values
                //else {
                //    if (!key_exists(self::$config[$key]))
                //        self::$config[$key] = $value;
                //}
            }
        }

        // Create institution prefix string
        if ($institution = $_SERVER['INSTITUTION'])
            $instdir = '/' . $institution;
        else
            $instdir = '';

        // Special case:  INSTITUTION returns the current institution name
        if ($parm == 'INSTITUTION')
            return $institution;

        $dieheader = 'Please relay the following WASE error message to your IT support staff: ';

        // We are here because a parm was not found in our local static buffer (config).
        //
        // We will do the following, in sequence, to try to locate the parm and save it's value in our
        // local static buffer (config).
        // 1.  If we have not already loaded it, load up any parameters files into config.  Set flag indicating that this is done.
        // 2.  If in the environment, set config (overriding anything in the parameters files).
        // 3.  Read in from database, if it exists.
        // 4.  Repeat step 2 (environment overrides evrything, but we can't read from database until we have database parms from ebvironment).
        // 5.  Return no found status.


        // 1.  Load up any parms file
        $fileparms = false;

        if (!key_exists("parms_file_loaded", self::$config)) {

            // Set up parms file names.
            $confFile = __DIR__ . '/../../config' . $instdir . '/waseParms.yml';
            $confFileSystem = __DIR__ . '/../../config' . $instdir . '/waseParmsSystem.yml';
            $confFileCustom = __DIR__ . '/../../config' . $instdir . '/waseParmsCustom.yml';
            $confFileGlobal = __DIR__ . '/../../config/global/waseParmsSystem.yml';

            $fileparms = array();

            // Get global parms, if any
            if (file_exists($confFileGlobal))
                $global = file_get_contents($confFileGlobal);
            else
                $global = '';

            $fileparms = "";

            if (file_exists($confFile)) {
                $parser = new \Symfony\Component\Yaml\Parser();
                $fileparms = $parser->parse($global . file_get_contents($confFile));
            } elseif (file_exists($confFileSystem)) {
                $parser = new \Symfony\Component\Yaml\Parser();
                $fileparms = $parser->parse($global . file_get_contents($confFileSystem) . @file_get_contents($confFileCustom));
            }

            // Merge into our parms buffer
            if ($fileparms) {
                foreach ($fileparms as $key => $value) {
                    // Don't replace an existing paqrameter
                    if (!key_exists($key, self::$config)) {
                        self::$config[$key] = $value;
                    }
                }
            }

            @self::$config["parms_file_loaded"] == 1;

        }


        // 3. If still not found, load from database.

        if (!key_exists($parm, self::$config) && !key_exists('parms_database_loaded', self::$config)) {
            // Make sure we have the database parms already
            $diemsg = '';

            if (!key_exists('HOST', self::$config))
                $diemsg .= 'WASE_HOST parameter not set. ';
            if (!key_exists('DATABASE', self::$config))
                $diemsg .= 'WASE_DATABASE parameter not set. ';
            if (!key_exists('USER', self::$config))
                $diemsg .= 'WASE_USER parameter not set. ';
            if (!key_exists('PASS', self::$config))
                $diemsg .= 'WASE_PASS parameter not set. ';

            if ($diemsg)
                die($dieheader . $diemsg);


            // Now load up the cache from the database
            if (!($parm == 'HOST' || $parm == 'USER' || $parm == 'DATABASE' || $parm == 'PASS')) {
                if ($res = WaseSQL::doQuery('SELECT * FROM ' . self::$config['DATABASE'] . '.WasePrefs WHERE class="parameters"')) {
                    while ($parameter = WaseSQL::doFetch($res)) {
                        foreach ($parameter as $key => $value) {
                            self::$config[$key] = $value;
                        }
                    }
                    WaseSQL::freeQuery($res);
                }
                @self::$config['parms_database_loaded'] = 1;
            }
        }

        // If still not found, and we could not load from database or from parameters files, die
        if (!key_exists($parm, self::$config) && !$fileparms && !key_exists('parms_database_loaded', self::$config))
            die($dieheader . "Could not find parameter in config files $confFile or $confFileSystem or in database " . self::$config['DATABASE']);

        // Special case code for alertmsg:  return global alertmsg if set
        if ($parm == 'ALERTMSG') {

            if (isset(self::$config['G_ALERTMSG']) && self::$config['G_ALERTMSG'] != "") {

                self::$config['ALERTMSG'] == self::$config['G_ALERTMSG'];

            }
        }


        // Now return the parameter

        $ret = isset(self::$config[$parm]) ? self::$config[$parm] : '';

        // Special case code for DAYTYPES; if only 1 coded, treat as though none coded.
        if ($parm == 'DAYTYPES') {
            if ($ret = '' || strpos($ret,',') !== false)
                return $ret;
            self::$config['DAYTYPES'] = '';
            return '';
        } else
            return $ret;

    }

    /**
     * This function determines if a parameter exists.
     *
     * @static
     *
     * @param string $parm
     *            The wase parameter.
     *
     * @return true|false true if it exists in the WaseParms file, else false.
     */
    static function isParm($parm)
    {
        // Make sure parm cache is loaded.
        $junk = self::lookup($parm);
        // Let caller know if oarm excists.
        return isset(self::$config[$parm]);
    }


    /**
     * This function returns a set of labels used to describe the objects that WASE manipulates.
     *
     * @static
     *
     * @param array|object $id
     *            The associative array with a key value of 'calid' or 'blockid', and the cal/block id number
     *            -- OR --
     *            The calendar or block
     *
     * @return array of labels, drawn from the block/calendar or the parameters file.
     */
    static function getLabels($idarray)
    {
        // Look up calendar or block, unless passed
        if (is_object($idarray))
            $obj = (array) $idarray;
        else {
            if (key_exists('calid',$idarray))
                $obj = WaseCalendar::find($idarray['calid']);
            elseif (key_exists('calendarid',$idarray))
                $obj = WaseCalendar::find($idarray['calendarid']);
            elseif (key_exists('blockid',$idarray))
                $obj = WaseBlock::find($idarray['blockid']);
            elseif (key_exists('appointmentid',$idarray)) {
                // Get the appointment
                $app = WaseAppointment::find($idarray['appointmentid']);
                // Now get the block
                $obj = WaseBlock::find($app['blockid']);
            }
            else
                $obj = array();
        }
        
         // Now go through and fill in the labels
         if (!$labels['NAMETHING'] = (@$obj['NAMETHING']) ? $obj['NAMETHING'] : self::getParm('NAME'))
             $labels['NAMETHING'] = 'office hours';
         // If no plural form, use the singular 
         if (!$labels['NAMETHINGS'] = (@$obj['NAMETHINGS']) ? $obj['NAMETHINGS'] : self::getParm('NAMES'))
             $labels['NAMETHINGS'] = $labels['NAMETHING'];
         
         if (!$labels['APPTHING'] = (@$obj['APPTHING']) ? $obj['APPTHING'] : self::getParm('APPOINTMENT'))
             $labels['APPTHING'] = 'appointment';
         // If no plural form, use the singular if available
         if (!$labels['APPTHINGS'] = (@$obj['APPTHINGS']) ? $obj['APPTHINGS'] : self::getParm('APPOINTMENTS'))
             $labels['APPTHINGS'] = $labels['APPTHING'];
         
         return $labels;
               
    }
    
    
    /**
     * This function initializes wase functions.
     *
     * @static This method performs startup tasks for all WASE modules:
     *         1) Determines if the WASE system is up and running and, if not, terminates with an error message.
     *         2) Sets timezone.
     *         3) Sets error_reporting level.
     *        
     * @return void
     */
    static function Init()
    {

        /* If we are down */
        if (! self::getParm('RUNNING')) {
            if (self::getParm('DOWNMSG'))
				/* Issue specified error message if set */
				die(self::getParm('DOWNMSG'));
            else
                die('The system is down.  Please contact ' . self::getParm('CONTACTNAME'));
        }
        
        /* If we are down */
        if (! self::getParm('G_RUNNING')) {
            if (self::getParm('G_DOWNMSG'))
                /* Issue specified error message if set */
                    die(self::getParm('G_DOWNMSG'));
                else
                    die('The system is down.  Please contact ' . self::getParm('G_CONTACTNAME'));
        }
        
        /* Set timezone */
        if ($timezone = self::getParm('TIMEZONE'))
            date_default_timezone_set($timezone);
            
        /* Set error_reporting */
        ($level = self::getParm('ERROR_LEVEL')) ? error_reporting($level) : error_reporting(E_ERROR | E_WARNING | E_PARSE);
        // To support setting an error level of zero:
        // (($level = self::getParm('ERROR_LEVEL')) !== '') ? error_reporting($level) : error_reporting(E_ERROR | E_WARNING | E_PARSE);
        // error_reporting((E_STRICT | E_ALL) ^ E_NOTICE);  
        // error_reporting(E_ERROR | E_WARNING | E_PARSE);
        ini_set('display_errors', '0');  // Do NOT display errors
        
        // Set chaching and other HTTP controls.
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
        // session_cache_limiter(�nocache�);
        ini_set( 'session.cookie_httponly', 1 );
        ini_set( 'session.cookie_secure', 1 );
        
        
    }

    /**
     * Terminate wase if not invoked via HTTPS and REQSSL set in the parms file.
     *
     * @static
     *
     * @return void
     */
    static function CheckSSL()
    {
        if (self::getParm('REQSSL')) {
            if (! $_SERVER['HTTPS']) {
                die('This system must be invoked using HTTPS, not HTTP');
            }
        }
    }
    
    /**
     * Determine if userid is allowed access to an administrative script.
     *
     * The script first checks to see if the userid provided the super-password.
     * If not, a check is made to see if the user has login access.
     *
     * @static
     *
     * @param string $userid The userid to check.
     *
     * @param string $adminusers Optional set of comma-seperated valid userid.
     *
     * @return boolean true if allowed, else die.
     */
    static function CheckAdminAuth($adminusers=null)
    {
        /* Make sure password was specified, or user is authorized */
        if ($_REQUEST['secret'] != WaseUtil::getParm('PASS')) {
            // See if logged in, if not, send to login, then return here
            if (!$_SESSION['authenticated']) {
                /* Save re-direct information, if any */
                $_SESSION['redirurl'] = $_SERVER['REQUEST_URI'];
                /* Send user back for authentication */
                header("Location: ../views/pages/login.page.php");
                exit();
            }
            if (!WaseUtil::CheckAdminUser($_SESSION['authid'],$adminusers)) {
                $msg = 'Attempt to access admin application ' . $_SERVER['SCRIPT_NAME']  . ' by ' . $_SESSION['authid'] . ' without authorization from: ' . $_SERVER['REMOTE_ADDR'];
                WaseMsg::logMsg($msg);
                die('Unauthorized access.');
            }
        }
        return true;
    }
 
    /**
     * Determine if useri is an admin for an admin script.
     *
     * The script first checks to see if the userid provided the super-password.
     * If not, a check is made to see if the user has login access.  Once logged in, a check
     * is made to see if user is an admin or not.
     *
     * @static
     *
     * @param string $userid The userid to check.
     *
     * @param string $adminusers Optional set of comma-seperated valid userid.
     *
     * @return boolean true if allowed, else false.
     */
    static function IsAdminAuth($adminusers=null)
    {
        /* Make sure password was specified, or user is authorized */
        if ($_REQUEST['secret'] != WaseUtil::getParm('PASS')) {
            // See if logged in, if not, send to login, then return here
            if (!$_SESSION['authenticated']) {
                /* Save re-direct information, if any */
                $_SESSION['redirurl'] = $_SERVER['REQUEST_URI'];
                /* Send user back for authentication */
                header("Location: ../views/pages/login.page.php");
                exit();
            }
            if (WaseUtil::CheckAdminUser($_SESSION['authid'],$adminusers))
                return true;
            else 
                return false;
        }
        return true;
    }
    
    /**
     * Determine if userid is allowed login (userid) access to an administrative script.
     *
     * The script first checks to see if the userid is in the optionally-provided list
     * of valid userids.  If not, it checks to see if the userid is in the super-user lost.
     * 
     * @static
     *
     * @param string $userid The userid to check.
     *             
     * @param string $adminusers Optional set of comma-seperated valid userid.
     * 
     * @return boolean True if allowed, else false.
     */
    static function CheckAdminUser($userid,$adminusers=null)
    {       
        // If in script-specific or super-user array, allow, else deny.
        if (in_array($userid,array_map('trim',explode(',',$adminusers))) || in_array($userid,array_map('trim',explode(',',self::getParm('SUPER_USERS')))))
            return true;
        else
            return false;
    }


    /**
     * Send an email through PHPMailer
     *
     * @static
     *
     * @param string $to
     *            The email address.
     * @param string $subject
     *            The email subject.
     * @param string $message
     *            The email text.
     * @param string $headers
     *            Optionally, additional email headers.
     * @param string $flags
     *            Optionally, command line flags.
     *
     * @return bool true if sent, else false.
     */
    static function Mailer($to, $subject, $message, $headers = '', $flags = '')
    {

        // Use the built-in mail if PHPMAILER arg is not true.
        if (!WaseUtil::getParm('PHPMAILER')) {
            // We want the mail to appear to come from our SMTP use
            if (!$flags) {
                if ($from = WaseUtil::getParm('SMTPUSER'))
                    $flags = "-f" . $from;
            }
            return mail($to, $subject, $message, $headers, $flags);
        }


        //Import the PHPMailer class into the global namespace
        // use PHPMailer\PHPMailer\PHPMailer;


        //Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer;

        //Tell PHPMailer to use SMTP
        $mail->isSMTP();

        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        if (!$smtpdebug = WaseUtil::getParm("SMTPDEBUG"))
            $smtpdebug = 0;
        $mail->SMTPDebug = $smtpdebug;
        $mail->Debugoutput = "error_log";

        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Set the hostname of the mail server (the smtp HUB server)
        $mail->Host = self::getParm("SMTPHUB");
        //Set the SMTP port number - likely to be 25, 465 or 587
        $mail->Port = self::getParm("SMTPPORT");
        if (self::getParm("SMTPPORT") == 587)
            $mail->SMTPSecure = 'tls';
        // Character set
        $mail->CharSet = 'UTF-8';

        //Username to use for SMTP authentication
        $mail->Username = self::getParm("SMTPUSER");
        //Password to use for SMTP authentication
        $mail->Password = self::getParm("SMTPPASS");

        // Set defaults for content-type and from
        $content = "html";
        $from = self::getParm("FROMMAIL");
        $replyto = '';

        // Parse additional headers
        $h = explode("\r\n", trim($headers));
        if ($h) {
            foreach ($h as $header) {
                list($head, $value) = explode(':', $header);
                $lhead = strtolower(trim($head));
                if ($lhead == "content-type") {
                    $lvalue = strtolower(trim($value));
                    if ($lvalue == "text/html")
                        $content = "html";
                    elseif ($lvalue == "text/plain")
                        $content = "plain";
                    else
                        $mail->ContentType = trim($value);

                }
                if ($lhead == "from")
                    $from = trim($value);
                if ($lhead == "reply-to")
                    $replyto = trim($value);
            }
        }

        //Set who the message is to be sent from
        if ($f = trim($flags)) {
            if (substr($f, 0, 2) == "-f")
                if ($rto = trim(substr($f, 3)))
                    $replyto = $rto;
        }

        // Set the FROM header
        $mail->setFrom($from);

        //Set an alternative reply-to address
        if ($replyto)
            $mail->addReplyTo($replyto);

        //Set who the message is to be sent to
        $mail->addAddress($to);

        //Set the subject line
        $mail->Subject = $subject;

        // Set the message
        if ($content == "html") {
            $mail->isHTML(true);
            $mail->AltBody = $mail->html2text($message);
        } else
            $mail->isHTML(false);
        $mail->Body = $message;

        //send the message, check for errors
        try {
            $mail->send();
            return true;
        } catch (phpmailerException $e) {
            WaseMsg::log($e->errorMessage());
            return false;
        }

    }

    /**
     * Send an email with an optional iCal attachment through PHPMailer.
     *
     * @static
     *
     * @param string $to
     *            The email address.
     * @param string $replyto
     *            Optionally, to whom replies should be directed.
     * @param string $subject
     *            The email subject.
     * @param string $text
     *            The email text.
     * @param string $ical
     *            Optionally, the iCal attachment string.
     * @param string $from
     *            Optionally, from whom the email appears to come.
     *
     * @return bool true if sent, else false.
     */
    static function IcalMailer($to, $replyto, $subject, $text, $ical, $from, $type)
    {
        // Use the build-in mail if PHPMAILER arg is not true.
        if (!WaseUtil::getParm('PHPMAILER')) {
            return self::sendEmailWithIcal($to, $replyto, $subject, $text, $ical, $from, $type);
        }

        // Set method as per type
        if ($type == 'delete')
            $method = 'CANCEL';
        else
            $method = 'PUBLISH';

        // If no $from, generate one.
        if (!$from)
            $from = WaseUtil::getParm('FROMMAIL');

        // If no replyto, use from
        if (!$replyto)
            $replyto = $from;

        // Build content-type with method
        $content = 'text/calendar; method=' . $method;


        // We need to "fold" the ical
        $ical = WaseIcal::foldICAL($ical);

        //Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer;


        //Tell PHPMailer to use SMTP
        $mail->isSMTP();

        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        if (!$smtpdebug = WaseUtil::getParm("SMTPDEBUG"))
            $smtpdebug = 0;
        $mail->SMTPDebug = $smtpdebug;
        $mail->Debugoutput = "error_log";

        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Set the hostname of the mail server
        $mail->Host = self::getParm("SMTPHOST");
        //Set the SMTP port number - likely to be 25, 465 or 587
        $mail->Port = self::getParm("SMTPPORT");
        if (self::getParm("SMTPPORT") == 587)
            $mail->SMTPSecure = 'tls';
        // Character set
        $mail->CharSet = 'UTF-8';

        // Username to use for SMTP authentication
        $mail->Username = self::getParm("SMTPUSER");
        //Password to use for SMTP authentication
        $mail->Password = self::getParm("SMTPPASS");

        // Set the FROM header
        $mail->setFrom($from);

        // Set an alternative reply-to address
        if ($replyto)
            $mail->addReplyTo($replyto);

        // Set who the message is to be sent to
        $mail->addAddress($to);

        // Set the subject line
        $mail->Subject = $subject;

        // Set the body
        $mail->isHTML(true);
        $mail->Body = $text;

        // Add in the iCal attachment
        $mail->addStringAttachment(base64_encode($ical), "appointment.ics", "base64", $content);

        //send the message, check for errors
        try {
            $mail->send();
            return true;
        } catch (phpmailerException $e) {
            WaseMsg::log($e->errorMessage());
            return false;
        }

    }


    /**
     * Send an email with an optional iCal attachment.
     *
     * @static
     *
     * @param string $to
     *            The email address.
     * @param string $replyto
     *            Optionally, to whom replies should be directed.
     * @param string $subject
     *            The email subject.
     * @param string $text
     *            The email text.
     * @param string $ical
     *            Optionally, the iCal attachment string.
     * @param string $from
     *            Optionally, from whom the email appears to come.
     *            
     * @return bool true if sent, else false.
     */
    static function sendEmailWithIcal($to, $replyto, $subject, $text, $ical, $from, $type)
    {

        // Set method as per type
        if ($type == 'delete')
            $method = 'CANCEL';
        else
            $method = 'PUBLISH';
        
        // If no $from, generate one.
        if (! $from)
            $from = WaseUtil::getParm('FROMMAIL');
        
        // If no replyto, use from
        if (! $replyto)
            $replyto = $from;


        // We need to "fold" the ical
        $ical = WaseIcal::foldICAL($ical);
            
        // Create Mime Boundry
        $mime_boundary = "WASE-" . md5(time());
        $my_sysmail = WaseUtil::getParm('SYSMAIL');
        // Build the email headers and body
        if ($ical) {
            // Use multipart/mixed instead of multipart/alternative
            $headers = "MIME-Version: 1.0\r\n" . 'Content-Type: multipart/mixed; boundary=' . '"' . $mime_boundary . '"' . "\r\n" . "Reply-To: " . $replyto . "\r\n" . "Errors-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
            
            // Start message with the mime boundary and the text headers
            $message = "--" . $mime_boundary . "\r\n" . "Content-Type: text/html; charset=UTF-8\r\n" . "Content-Transfer-Encoding: 7bit\r\n\r\n" . $text . "\r\n\r\n";
            
            // Add in mime boundary and the iCal version
            $message .= "--" . $mime_boundary . "\r\n" . 'Content-Type: text/calendar; name="appointment.ics"; method=' . $method .'; charset=UTF-8' . "\r\n" . "Content-Transfer-Encoding: 8bit\r\n\r\n" . $ical . "\r\n" . "--" . $mime_boundary . "--";
        } else {
            $headers = "Content-Type: text/html; charset=UTF-8\r\n" . "Reply-To: " . $replyto . "\r\n" . "Errors-To: $my_sysmail\r\nReturn-Path: $my_sysmail\r\n";
            $message = $text;
        }
        // Now send the email
        // $fheaders = "-f ".WaseUtil::getParm('SYSMAIL');
        // returnWaseUtil::Mailer($to, $subject, $message, $headers);
        return mail($to, $subject, $message, $headers);
        
        
    }

    /**
     * This function validates a date.
     *
     * @static
     *
     * @param string $date
     *            The date.
     *            
     * @return bool true if valid, else false.
     */
    static function isDateValid($date)
    {
        if (strpos($date, '/') !== false)
            list ($m, $d, $y) = explode('/', $date);
        elseif (strpos($date, '-') !== false)
            list ($y, $m, $d) = explode('-', $date);
        else
            return false;
        
        if (! checkdate((int) $m, (int) $d, (int) $y))
            return false;
        else
            return true;
    }

    /**
     * This function compares two dates.
     *
     * @static
     *
     * @param string $date1
     *            The first date.
     * @param string $date2
     *            The second date.
     *            
     * @return string >, < or =
     */
    static function compareDates($date1, $date2)
    {
        list ($y1, $m1, $d1) = explode('[/.-]', $date1);
        list ($y2, $m2, $d2) = explode('[/.-]', $date2);
        
        if ($y2 > $y1)
            return '>';
        elseif ($y2 < $y1)
            return '<';
        else {
            if ($m2 > $m1)
                return '>';
            elseif ($m2 < $m1)
                return '<';
            else {
                if ($d2 > $d1)
                    return '>';
                elseif ($d2 < $d1)
                    return '<';
                else
                    return '=';
            }
        }
    }
    
    /**
     * This function compares two datetimes.
     *
     * @static
     *
     * @param string $datetime1
     *            The first datetime (yyyy-mm-dd hh:mm:ss).
     * @param string $datetime2
     *            The second datetime.
     *
     * @return string >, < or =
     */
    static function compareDateTimes($datetime1, $datetime2)
    {
        list($date1,$time1) = explode(' ',$datetime1);
        list($date2,$time2) = explode(' ',$datetime2);
        $datecomp = self::compareDates($date1, $date2);
        
        if ($datecomp == '=') {
            list ($h1, $m1, $s1) = explode('[/.-]', $time1);
            list ($h2, $m2, $s2) = explode('[/.-]', $time2);
            
            if ($h2 > $h1)
                return '>';
            elseif ($h2 < $h1)
                return '<';
            else {
                if ($m2 > $m1)
                    return '>';
                elseif ($m2 < $m1)
                    return '<';
                else {
                    if ($s2 > $s1)
                        return '>';
                    elseif ($s2 < $s1)
                        return '<';
                    else
                        return '=';
                }
            }             
        }
        else
            return $datecomp;
    }
    
    
    /**
     * This function converts a date to US format.
     *
     * @static
     *
     * @param string $date
     *            The input date.
     *            
     * @return string The output date.
     */
    static function usDate($date)
    {
        if (strpos($date, '/') !== false)
            return $date;
        else {
            list ($y, $m, $d) = explode('-', $date);
            return sprintf("%u/%u/%u", $m, $d, $y);
        }
    }

    /**
     * This function converts a date to long format.
     *
     * @static
     *
     * @param string $date
     *            The input date.
     *            
     * @return string The output date.
     */
    static function longDate($date)
    {
        if (strpos($date, '/') !== false)
            list ($m, $d, $y) = explode('/', $date);
        elseif (strpos($date, '-') !== false)
            list ($y, $m, $d) = explode('-', $date);
        else
            return "";
        
        $months = array(
            '',
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        );
        
        return $months[(integer) $m] . ' ' . sprintf('%2u', $d) . ', ' . $y;
    }

    /**
     * This function converts minutes to HH:MM string format.
     *
     * @static
     *
     * @param int $minutes.
     *            The input minutes.
     *            
     * @return string The output hour:minutes.
     */
    static function minToTime($minutes)
    {
        $mins = $minutes % 60;
        $hours = ($minutes - $mins) / 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * This function converts minutes into Unix datetime format.
     *
     * @static
     *
     * @param int $minutes.
     *            The input minutes.
     *            
     * @return string The output unix time since the epoch, in seconds.
     */
    static function minToDateTime($minutes)
    {
        return self::unixToDateTime($minutes * 60);
    }

    /**
     * This function converts a Unix time (in seconds) to datetime format.
     *
     * @static
     *
     * @param int $unixtime.
     *            The input time in seconds.
     *            
     * @return string The output date/time.
     */
    static function unixToDateTime($unixtime)
    {
        return date('Y-m-d H:i:s', $unixtime);
    }

    /**
     * This function converts HH:MM to minutes.
     *
     * @static
     *
     * @param string $time.
     *            The input time in 'HH:MM'.
     *            
     * @return int The output in minutes.
     */
    static function timeToMin($time)
    {
        list ($h, $m, $s) = explode(':', $time);
        return (($h * 60) + $m);
    } 

    /**
     * This function converts a datetime into total minutes.
     *
     * @static
     *
     * @param string $datetime.
     *            The date and time as a datetime string.
     *            
     * @return int The output in minutes.
     */
    static function datetimeToMin($datetime)
    {
        // return self::makeUnixTime($datetime) / 60;
       return strtotime($datetime)/60; 
    } 

    /**
     * This function converts a datetime into US format.
     *
     * @static
     *
     * @param string $datetime.
     *            The date and time as a datetime string.
     *            
     * @return int The output in US format.
     */
    static function datetimeToUS($datetime)
    {
        return self::usDate(substr($datetime, 0, 10)) . ' ' . self::AmPm(substr($datetime, 11, 5));
    }

    /**
     * This function takes a datetime, adds minutes to it, and computes the resulting datetime
     *
     * @static
     *
     * @param string $datetime.
     *            The starting date/time.
     * @param int $mins
     *            How many minutes to add.
     *            
     * @return string The resulting date/time.
     */
    static function addDateTime($datetime, $mins)
    {
        return self::minToDateTime((self::datetimeToMin($datetime) + $mins));
    }

    /**
     * This function returns an XML stream to a web browser.
     *
     * @static
     *
     * @param string $xml.
     *            The XML string.
     *            
     * @return string xml
     */
    static function returnXML($xml)
    {
        header('Content-type: text/xml');
        echo $xml;
    }
    
    /**
     * This function returns JSON stream to a web browser.
     *
     * @static
     *
     * @param string $json
     *            The json string to be returned.
     *
     * @return string $json
     */
    static function returnJSON($json)
    {   
        header('Content-type: application/json');       
        echo $json;
    }
    

    /**
     * Encode characters as numeric enities in an XML stream.
     *
     * @static This function encodes the input string as numeric entity references,
     *         as that �s a safe way to use high characters and special characters in an xml document.
     *        
     * @param string $string
     *            The text to be encoded.
     *            
     * @return string The resulting string.
     */
    static function xmlCharEncode($string)
    {
        /* First, load up the enitity translate table */
        if (! self::$entitytrans) {
            self::$entitytrans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
            foreach (self::$entitytrans as $k => $v)
                self::$entitytrans[$k] = "&#" . ord($k) . ";";
        }
        return strtr($string, self::$entitytrans);
    }

    /**
     * Encode characters as numeric enities in an XML stream.
     *
     * @static This function takes text that will be imbedded in an XML stream and esacpes any characters that might
     *         causes problems.
     *        
     * @param string $text
     *            The text to be encoded.
     *            
     * @return string The resulting string.
     */
    static function safeXML($text)
    {
        /* KDC, 11-11-2009. Testing no entity conversion. */
        /* return htmlentities(iconv('ISO-8859-1', 'ISO-8859-1//IGNORE', $text),ENT_QUOTES,'ISO-8859-1'); */
        return self::xmlCharEncode($text);
    }

    /**
     * Encode characters as numeric enities in an HTML stream.
     *
     * @static This function takes text that will be imbedded in an HTML stream and esacpes any characters that might
     *         causes problems.
     *        
     * @param string $text
     *            The text to be encoded.
     *            
     * @return string The resulting string.
     */
    static function safeHTML($text)
    {
        $okescaped = array(
            '&lt;b&gt;',
            '&lt;/b&gt;',
            '&lt;i&gt;',
            '&lt;/i&gt;',
            '&lt;em&gt;',
            '&lt;/em&gt;',
            '&lt;strong&gt;',
            '&lt;/strong&gt;',
            '&lt;p&gt;',
            '&lt;/p&gt;',
            '&lt;br&gt;',
            '&lt;br/&gt;',
            '&lt;br /&gt;'
        );
        $okhtml = array(
            '<b>',
            '</b>',
            '<i>',
            '</i>',
            '<em>',
            '</em>',
            '<strong>',
            '</strong>',
            '<p>',
            '</p>',
            '<br>',
            '<br/>',
            '<br />'
        );
        return str_replace($okescaped, $okhtml, htmlentities($text, ENT_QUOTES, 'ISO-8859-1'));
    }

    /**
     * Route error message.
     *
     * @static This method takes an error message and routes it to the specified desitinations,
     *         including returning it to the web server as a stream (echo). The supported destinations are detailed in the code.
     *        
     * @param int $code
     *            The error numeric code.
     * @param array $subarray
     *            Array of message substitutions.
     * @param list $destinations
     *            List of target destinations for the message.
     *            
     * @return string The resulting string.
     */
    static function Error($code, $subarray, $destinations,$sub=true)
    {
        
        /* Extract the destinations into an array */
        $dests = explode(',', trim(strtoupper($destinations)));
        /* Get the message text (with substitutions performed) */
        if ($sub)
            $msg = WaseMsg::getMsg($code, $subarray);
        else 
            $msg = implode(' ',$subarray);
        /* Log the message (regardless of destination) */
        WaseMsg::logMsg($msg);
        /* Now hande each destination */
        foreach ($dests as $dest) {
            switch ($dest) {
                case 'HTMLFORMAT':
                    $msg = '<html><head></head><body>' . $msg . '</body></html>';
                case 'HTTP':
                case 'HTML':
                    echo $msg;
                    break;
                case 'LOG': /* Done in all cases */
					break;
                case 'SYSEMAIL':
                    $subject = 'Error report from ' . self::getParm('SYSID');
                    $message = "Error reported on " . date('m/d/Y H:i') . ':' . "\r\n" . $msg;
                    // $fheaders = "-f ".self::getParm('SYSMAIL');
                    WaseUtil::Mailer(self::getParm('SYSMAIL'), $subject, $message, null);
                    break;
                case 'AJAXRET':
                    return XMLHEADER . '<wase><error><errorcode>' . $code . '</errorcode><errortext>' . $msg . '</errortext></error></wase>';
                    break;
                case 'AJAXHEAD':
                    return XMLHEADER . '<wase><error><errorcode>' . $code . '</errorcode><errortext>' . $msg . '</errortext></error>';
                    break;
                case 'AJAXERR':
                    return '<error><errorcode>' . $code . '</errorcode><errortext>' . $msg . '</errortext></error>';
                    break;
                case 'AJAX':
                    $ret = XMLHEADER . '<wase><error><errorcode>' . $code . '</errorcode><errortext>' . $msg . '</errortext></error></wase>';
                    self::returnXML($ret);
                    break;
                case 'AJASRET':
                    return array("error"=>array("errorcode"=>$code,"errortext"=>$msg));
                case 'AJASERR':
                    return '"error":{"errorcode":"' . $code . '","errortext":"' . $msg .'"}';
                    break;
                case 'ICAL': 
                    WaseIcal::returnICAL(self::ICALHEADER . "REQUEST-STATUS:3." . $code . ";" . $msg . "\r\n" . self::ICALTRAILER, "PUBLISH", '');
                    break;
                case 'FORMAT':
                    return $msg;
                    break;
                case 'DIE':
                    die();
            }
        }
    }

    /**
     * Load an object with values from an associative array.
     *
     * @static
     *
     * @param object $ob
     *            The object.
     * @param array $arr
     *            Array of properties and their values.
     *            
     * @return object The resulting loaded object.
     */
    static function loadObject($ob, $arr)
    {
        /*
         * For each property in the obejct, load the corresponding value from the array.
         */
        $obvars = array_keys(get_object_vars($ob));
        foreach ($arr as $key => $value) {
            if (in_array($key, $obvars))
                $ob->$key = $value;
        }
    }

    /**
     * Convert object properties to an XML stream.
     *
     * @static
     *
     * @param object $ob
     *            The object.
     *            
     * @return string The resulting XML.
     */
    static function objectToXML($ob)
    {
        
        /* Extract all properties into an associative array. */
        $arr = get_object_vars($ob);
        /* Rumble through the array and generate the xml. */
        $xml = '';
        foreach ($arr as $key => $value) {
            $xml .= '<' . self::safeXML($key) . '>';
            if (is_object($value))
                $xml .= self::objectToXML($value);
            else
                $xml .= self::safeXML($value);
            $xml .= '</' . self::safeXML($key) . '>';
        }
        /* Return the xml string. */
        return $xml;
    }

    /**
     * Convert array to an XML stream.
     *
     * @static
     *
     * @param array $arr
     *            The array.
     * @param string $only
     *            Optionally, the only key to extract.
     *            
     * @return string The resulting XML.
     */
    static function arrayToXML($arr, $only)
    {
        $xml = '';
        foreach ($arr as $key => $value) {
            if ((! $only) || ($key == $only))
                $xml .= '<' . self::safeXML($key) . '>' . self::safeXML($value) . '</' . self::safeXML($key) . '>';
        }
        return $xml;
    }

    /**
     * Remove elements from an XML stream.
     *
     * @static
     *
     * @param array $arr
     *            The array of element names to remove.
     * @param string $xml
     *            The input XML.
     *            
     * @return string The resulting XML.
     */
    static function stripFromXML($arr, $xml)
    {
        $outxml = '';
        foreach ($arr as $key) {
            list ($beforexml, $rest) = explode('<' . $key . '>', $xml);
            list ($before, $afterxml) = explode('</' . $key . '>', $rest);
            $outxml .= $beforexml . $afterxml;
        }
        return $outxml;
    }

    /**
     * Remove slashes and Microsoft Word weird characters.
     *
     * @static
     *
     * @param string $arg
     *            The input string.
     *            
     * @return string The output string.
     */
    static function slashstrip($arg)
    {
        /* Convert Microsoft Word chars to ASCII chars */
        $arg = preg_replace(array(
            '/[\x60\x82\x91\x92\xb4\xb8]/i',
            '/[\x84\x93\x94]/i',
            '/[\x85]/i',
            '/[\x00-\x0d\x0b\x0c\x0e-\x1f\x7f-\x9f]/i'
        ), array(
            '\'',
            '"',
            '...',
            ''
        ), $arg);
        
        /* Now strip the slashes */
        if (get_magic_quotes_gpc())
            return stripslashes($arg);
        else
            return $arg;
    }

    

    /**
     * Is user in an AD/LDAP group?
     *
     * This method requires an integration with an LDAP server,
     *
     * @static
     *
     * @param array $groups
     *            Ann array of  group identifiers.
     * @param string $userid
     *            The user identifier.
     *
     * @return bool true if yes, else false.
     */
    static function IsUserInGroup($groups, $userid)
    {
      // If LDAP group restrictions not on, or LDAP not on, return false.
      if (!WaseUtil::getParm('ADRESTRICTED') || !WaseUtil::getParm('LDAP'))
          return false;
      
      // If no netid or groups passed, return false.
      if (! $groups || ! $userid)
          return false;

        $directory = WaseDirectoryFactory::getDirectory();

      // Check all groups passed
      foreach($groups as $group) {
          if ($directory->isMemberOf($userid, $group))
              return true;
      }
      
      // Nope, not in an authroized group.
      return false;
        
    }
    
        
    /**
     * Does user have one of the specified status(es)?
     *
     * This method requires an integration with an LDAP server, or CAS/SHOB authentication,
     *
     * @static
     *
     * @param array $statuses
     *            The statuses to be tested.
     *            
     * @param string $userid
     *            The user.
     *
     * @return bool true if yes, else false.
     */
    static function doesUserHaveStatus($statuses, $userid)
    {
        return WaseDirectory::hasStatus($userid, $statuses);
        
    }
       
    
    
    /**
     * Is user in a course?
     *
     * This method requires an integration with an LMS or other system which
     * can lookup users in courses.
     *
     * @static
     *
     * @param array $courseid
     *            Ann array of  course identifiers.
     * @param string $userid
     *            The user identifier.
     *            
     * @return bool true if yes, else false.
     */
    static function IsUserInCourse($courseid, $userid)
    {
        /*
         * Call user exit. It will return one of three values:
         * USERNOTINCOURSE, USERINCOURSE, NULL
         *
         * If USERNOTINCOURSE, it will return false.
         * If USERINCOURSE, it will return true.
         * Else it will drop down to the standard code.
         *
         */
        
        // Return false if no courses or user passed
        if (! $courseid || ! $userid)
            return false; 
        
        if (class_exists('WaseLocal')) {           
            $func = 'IsUserInCourse';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                switch (WaseLocal::$func($courseid, $userid)) {
                    case WaseLocal::USERNOTINCOURSE:
                        return false;
                        break;
                    case WaseLocal::USERINCOURSE:
                        return true;
                        break; 
                }
            }
        }
        
        /* Make sure data is in array format */
        if (is_array($courseid))
            $courses = $courseid;
        else
            $courses = array(
                $courseid
            );
        
             
        /* Build SOAP client. */
        // $client = new SoapClient(self::getParm('BBWSDL'));
        // Build the LMS class
        $lms = WaseLMSFactory::getLMS();
         
        /* Check the courses */
        foreach ($courses as $course) {
            /* Call the web service */
            // $result = $client->getUserRole($userid, $course, self::getParm('BBCLIENT'), self::getParm('BBKEY'));
            $result = $lms->getUserRole($userid,$course);
            /* Returned value may be a string or an array */
            if (is_array($result))
                $testvalue = trim((string) $result[0]);
            else
                $testvalue = trim((string) $result);
               
             /*
             * If any kind of error, including user not in course, or invalid
             * course, continue looking.
             */
            $testvalue = strtoupper(substr($testvalue,0,10));    
            if ($testvalue != 'ERRORCODES') { 
                return true;
            }
        }
        
        /* Return the bad news */
        return false;
    }


    /**
     * Does AS group exist?
     *
     * This method requires an integration with an LDAP/AD server.
     *
     * @static
     *
     * @param string $groupid
     *            The group identifier.
     *
     * @return bool true if yes, else false.
     */
    static function IsGroupValid($groupid)
    {
         
        /*
         * Call user exit. It will return one three values:
         * GROUPISVALID, CGROUPSNOTVALID, NULL
         *
         * If GROUPISVALID, it will return true.
         * If GROUPSNOTVALID, it will return false.
         * Else it will drop down to the standard code.
         *
         */
        if (class_exists('WaseLocal')) {
            $func = 'IsGroupValid';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                switch (WaseLocal::$func($groupid)) {
                    case WaseLocal::GROUPISVALID:
                        return true;
                        break;
                    case WaseLocal::GROUPISNOTVALID:
                        return false;
                        break;
                }
            }
       }
    
       // Return the WaseDirectory check
        $directory = WaseDirectoryFactory::getDirectory();
        return $directory->isGroup($groupid);
       
    }
    
    
    /**
     * Does course exist?
     *
     * This method requires an integration with an LMS or other system which
     * can lookup courses.
     *
     * @static
     *
     * @param string $courseid
     *            The course identifier.
     *            
     * @return bool true if yes, else false.
     */
    static function IsCourseValid($courseid)
    {
       
        /*
         * Call user exit. It will return one three values:
         * COURSEISVALID, COURSEISNOTVALID, NULL
         *
         * If COURSEISVALID, it will return true.
         * If COURSEISNOTVALID, it will return false.
         * Else it will drop down to the standard code.
         *
         */
        if (class_exists('WaseLocal')) {
            $func = 'IsCourseValid';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                switch (WaseLocal::$func($courseid)) {
                    case WaseLocal::COURSEISVALID:
                        return true;
                        break;
                    case WaseLocal::COURSEISNOTVALID:
                        return false;
                        break;
                }
            }
        }
        
        /* Build SOAP client. */
        // $client = new SoapClient(self::getParm('BBWSDL'));
        // Build the LMS class
        $lms = WaseLMSFactory::getLMS();
        
        /* Call the web service */
        //$result = $client->getUserRole('xxxxxxxx', $courseid, self::getParm('BBCLIENT'), self::getParm('BBKEY'));
        $result = $lms->getUserRole('xxxxxxxx', $courseid);
        
        /* Returned value may be a string or an array */
        if (is_array($result))
            $testvalue = trim($result[0]);
        else
            $testvalue = trim($result);
            
            /* If error says course invalid, return false */
        if ($testvalue == 'ErrorCodes.INVALID_COURSE')
            return false;
        else
            return true;
    }
 
    /**
     * In what courses is a user enrolled?
     *
     * This method requires an integration with an LMS or other system which
     * can lookup users in courses.
     *
     * @static
     *
     * @param string $userid
     *            The user identifier.
     *            
     * @return array An associative array of course identifiers.
     */
    static function getCourses($userid)
    {
        
        /* Call user exits, if they exist */
        if (class_exists('WaseLocal')) {
            $func = 'getCourses';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func))
                return WaseLocal::$func($userid);
        }
        
        /* Standard code */
        
          
        /* Build SOAP client. */
        // $client = new SoapClient(self::getParm('BBWSDL'));
        // Build the LMS class
        $lms = WaseLMSFactory::getLMS();
       
        /* Call the web service */
        //$result = $client->getEnrollments($userid, self::getParm('BBCLIENT'), self::getParm('BBKEY'));
        $result = $lms->getEnrollments($userid);
        
        /* Init the result array */
        $retarray = array();
        $i = 0;
        
        /* Returned value may be a string or an array */
        if (is_array($result))
            $testvalue = trim($result[0]);
        else
            $testvalue = trim($result);
            
        /* If error says no course, return null */
        if (strpos('AA' . $testvalue, 'ErrorCodes.') == 2)
            return '';
        
        foreach ($result as $res) {
            list ($batchUid, $internal, $title) = explode('|', $res);
            $retarray[$i]['course_name'] = $batchUid;
            $retarray[$i]['course_id'] = $internal;
            $retarray[$i]['course_title'] = $title;
            $i ++;
        }
         
        return $retarray;
    }

    /**
     * What users are in a course?
     *
     * This method requires an integration with an LMS or other system which
     * can lookup users in courses.
     *
     * @static
     *
     * @param string $course_name
     *            The course name.
     *            
     * @return array An associative array with userid and roles of enrolled students.
     */
    static function getEnrollment($course_name)
    {
        
        /* Call user exits, if they exist */
        if (class_exists('WaseLocal')) {
            $func = 'getEnrollment';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func))
                return WaseLocal::$func($course_name);
        }
        
        /* Standard code */
        
       /* Build SOAP client. */
        // $client = new SoapClient(self::getParm('BBWSDL'));
        // Build the LMS class
        $lms = WaseLMSFactory::getLMS();
        
        /* Call the web service */
        // $result = $client->getCourseMembership($course_name, self::getParm('BBCLIENT'), self::getParm('BBKEY'));
        $result = $lms->getCourseMembership($course_name);
        
        /* Init the result array */
        $retarray = array();
        $i = 0;
        
        /* Returned value may be a string or an array */
        if (is_array($result))
            $testvalue = trim($result[0]);
        else
            $testvalue = trim($result);
            
            /* If error says no course, return null */
        if (strpos('AA' . $testvalue, 'ErrorCodes.') == 2)
            return '';
        
        foreach ($result as $res) {
            list ($userid, $role) = explode('|', $res);
            $retarray[$i]['userid'] = $userid;
            $retarray[$i]['role'] = $role;
            $i ++;
        }
         
        return $retarray;
    }

    /**
     * What courses (and their instructors) is a student enrolled in?
     *
     * This method requires an integration with an LMS or other system which
     * can lookup users in courses.
     *
     * @static
     *
     * @param
     *            string userid The student userid.
     *            
     * @return array An associative array with course and instructor information.
     */
    static function getCoursesAndInstructors($userid)
    {
        
        /* Call user exits, if they exist */
        if (class_exists('WaseLocal')) {
            $func = 'getCoursesAndInstructors';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func))
                return WaseLocal::$func($userid);
        }
        
        /* Standard code */
        
        /* Get list of courses */
        $courses = self::getCourses($userid);
        if (! $courses)
            return '';

        /* */
        $blackboard_instructor_mode=$_SESSION['blackboard_instructor_mode'];
        /* For each course, get list of instructors */
        $imax = count($courses);
        $directory = WaseDirectoryFactory::getDirectory();
        for ($i = 0; $i < $imax; ++ $i) {
            $members = self::getEnrollment($courses[$i]['course_name']);
            
            if (! $members)
                continue;
            $jmax = count($members); 
            $k = 0;
            //note in blackboard roles are instructor and teachingassistant so use preg_match instead
            for ($j = 0; $j < $jmax; ++ $j) {
                //if ($members[$j]['role'] == 'INSTRUCTOR' or $members[$j]['role'] == 'TEACHING_ASSISTANT') {
                if((preg_match("/instructor/i",$members[$j]['role'])) || (preg_match("/teaching/i",$members[$j]['role']))) {
                    $courses[$i]['instructors'][$k]['userid'] = $members[$j]['userid'];
                    $courses[$i]['instructors'][$k]['name'] = $directory->getName($members[$j]['userid']);
                    $k ++;
                }
            }
        }
        return $courses;
    }

    /**
     * Get list of courses, instructors and wase calendars for a given user.
     *
     * This method requires an integration with an LMS or other system which
     * can lookup users in courses.
     *
     * @static
     *
     * @param
     *            string userid The student userid.
     *            
     * @return array An associative array with course and instructor and calendar information.
     */
    static function getCourseCalendars($userid)
    {
        
        /* First, get courses and instructors */
        if (!$courses = self::getCoursesAndInstructors($userid))
            return $courses;
        
        /* Go through all courses */
        $imax = count($courses);
        if ($imax) for ($i = 0; $i < $imax; ++ $i) {
            /* Go through all instructors */
            $jmax = count($courses[$i]['instructors']);
            if ($jmax) for ($j = 0; $j < $jmax; ++ $j) {
                if (key_exists('userid', $courses[$i]['instructors'][$j]))
                    $courses[$i]['instructors'][$j]['calendars'] = WaseCalendar::arrayOwnedorMemberedCalendarids($courses[$i]['instructors'][$j]['userid']);
            }
            /* Sort the instructor array based on calendar counts */
            usort($courses[$i]['instructors'], array(
                'self',
                'sort_by_calendar_count'
            ));
        }
        
        return $courses;
    }

    /**
     * Usort routine for comparing counts of calendars.
     *
     *
     * @static
     *
     * @param array $a
     *            The first calendar array.
     * @param string $b
     *            The second calendar array.
     *            
     * @return int -1, 0, 1 as per usort rules.
     */
    static function sort_by_calendar_count($a, $b)
    {
        if (count($a['calendars']) == count($b['calendars']))
            return 0;
        return (count($a['calendars']) < count($b['calendars'])) ? 1 : - 1;
    }

    /**
     * Is passed date before today?
     *
     *
     * @static
     *
     * @param string $date
     *            The target date.
     *            
     * @return bool true if yes, else false.
     */
    static function beforeToday($date)
    {
        $thisday = getdate();
        $toyear = (int) $thisday['year'];
        $tomonth = (int) $thisday['mon'];
        $today = (int) $thisday['mday'];
        
        if (strpos($date, '/') !== false)
            list ($pmonth, $pday, $pyear) = explode('/', $date);
        elseif (strpos($date, '-') !== false)
            list ($pyear, $pmonth, $pday) = explode('-', $date);
        else
            return false;
        
        if ((int) $pyear > $toyear)
            return false;
        elseif ((int) $pyear < $toyear)
            return true;
        
        if ((int) $pmonth > $tomonth)
            return false;
        elseif ((int) $pmonth < $tomonth)
            return true;
        
        if ((int) $pday < $today)
            return true;
        else
            return false;
    }

    /**
     * Is passed date today?
     *
     *
     * @static
     *
     * @param string $date
     *            The target date.
     *            
     * @return bool true if yes, else false.
     */
    static function isToday($date)
    {
        $thisday = getdate();
        $toyear = (int) $thisday['year'];
        $tomonth = (int) $thisday['mon'];
        $today = (int) $thisday['mday'];
        
        if (strpos($date, '/') !== false)
            list ($pmonth, $pday, $pyear) = explode('/', $date);
        elseif (strpos($date, '-') !== false)
            list ($pyear, $pmonth, $pday) = explode('-', $date);
        else
            return false;
        
        if (((int) $pyear == $toyear) && ((int) $pmonth == $tomonth) && ((int) $pday == $today))
            return true;
        else
            return false;
    }

    /**
     * Is passed date/time before current date/time?
     *
     *
     * @static
     *
     * @param string $datetime
     *            The target date/time.
     *            
     * @return bool true if yes, else false.
     */
    static function beforeNow($datetime)
    {
        return (strtotime($datetime) < time());   
        
    }

    /**
     * Is email address valid (syntax and semantics).
     *
     * The function will always return true if the TESTEMAIL parameter
     * in WaseParms is set to false.
     *
     * @static
     *
     * @param string $Email
     *            The target email address.
     *            
     * @return bool true if yes, else false.
     */
    static function validateEmail($Email)
    {
        
        /* Return valid if testing is disabled */
        if (! self::getParm('TESTEMAIL'))
            return '';
            
            /* Courtesy John Coggeshall, http://www.coggeshall.org/. */
            
        /* First, validate the format of the email */
        if (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $Email))
            return 'Email address ' . $Email . ' does not appear to be properly formatted.';
            
            /* Now get the name of the target email server (use MX lookup) */
        list ($Username, $Domain) = explode("@", $Email);
        if (getmxrr($Domain, $MXHost)) {
            $ConnectAddress = $MXHost;
        } else {
            $ConnectAddress = array(
                $Domain
            );
        }
        
        $me = $_SERVER['SERVER_ADDR'];
        
        /* Now connect to the server and check the address */
        foreach ($ConnectAddress as $server) {
            $Connect = fsockopen($server, 25, $errno, $errtext, 3);
            if ($Connect)
                break;
        }
        
        if ($Connect) {
            if (preg_match("/^220/", $Out = fread($Connect, 1024))) {
                fputs($Connect, "HELO $me\r\n", strlen($me) + 7);
                
                $fout = fread($Connect, 1024);
                
                $Out = substr(trim($fout), 0, 3);
                if ($Out < 200 || $Out > 399) {
                    fputs($Connect, "QUIT\r\n");
                    
                    fclose($Connect);
                    
                    return 'Email server ' . $ConnectAddress . ' replies: ' . $fout;
                }
                fputs($Connect, "MAIL FROM:<" . self::getParm('SYSMAIL') . ">\r\n");
                
                $Ffrom = fread($Connect, 1024);
                
                $From = substr(trim($Ffrom), 0, 3);
                if ($From < 250 || $From > 252) {
                    fputs($Connect, "QUIT\r\n");
                    
                    fclose($Connect);
                    
                    return 'Email server ' . $ConnectAddress . ' will not accept email: ' . $Ffrom;
                }
                fputs($Connect, "RCPT TO:<" . $Email . ">\r\n");
                
                $Fto = fread($Connect, 1024);
                
                $To = substr(trim($Fto), 0, 3);
                if ($To < 250 || $To > 252) {
                    fputs($Connect, "QUIT\r\n");
                    
                    fclose($Connect);
                    
                    return 'Email server ' . $ConnectAddress . ' rejected specified email address: ' . $Fto;
                }
                fputs($Connect, "QUIT\r\n");
                
                fclose($Connect);
            } else
                return "No response from server";
        } else
            return "Can not connect to E-Mail server " . $ConnectAddress;
            
            /* Success */
        return '';
    }

    /**
     * Validate time and convert to standard form (HH:MM:SS).
     *
     * This function checks a 5-character time entry (hh:mm). It also converts a "am/pm"
     * type time to 24-hour notation, and rechecks ambigous times (e.g., 6:30), and converts times like 2:30 to 14:30.
     *
     * @static
     *
     * @param string $time
     *            The target time.
     *            
     * @return string|null converted time if valid, else null.
     */
    static function checkTime($time)
    {
        $ampm = "";
        $t = strtoupper($time);
        if (strpos($t, "PM") !== false) {
            $ampm = "pm";
            $t = trim(str_replace("PM", "", $t));
        } elseif (strpos($t, "AM") !== false) {
            $ampm = "am";
            $t = trim(str_replace("AM", "", $t));
        } elseif (strpos($t, "MIDNIGHT") !== false) {
            $ampm = "am";
            $t = trim(str_replace("MIDNIGHT", "", $t));
        } elseif (strpos($t, "NOON") !== false) {
            $ampm = "pm";
            $t = trim(str_replace("NOON", "", $t));
        } else
            $t = trim($t);
        
        if (($t == "") || (strlen($t) > 8))
            return "";
        
        if (strpos($t, ':') === false) {
            if (strlen($t) > 2)
                return "";
            $t = $t . ":00";
        }
        
        list ($hh, $mm, $ss) = explode(":", $t);
        if (! $ss)
            $ss = "00";
        
        if ($hh == "")
            return "";
        
        if ($mm == "")
            $mm = "00";
            
            /* Convert times like 5:30 to pm, and reject times like 6:30. */
        if (strlen($hh) == 1)
            if ($ampm == "")
                if ($hh < 7)
                    $hh += 12;
                elseif ($hh < 9)
                    return "";
        
        $hh = intval($hh);
        $mm = intval($mm);
        
        if ($ampm == "pm")
            if ($hh < 12)
                $hh += 12;
        
        if ($ampm == "am")
            if ($hh == 12)
                $hh = "00";
        
        if (($hh > 23) || ($hh < 0))
            return "";
        
        if (($mm > 60) || ($mm < 0))
            return "";
        
        if (strlen($hh) == 1)
            $hh = "0" . $hh;
        if (strlen($mm) == 1)
            $mm = "0" . $mm;
        
        return $hh . ":" . $mm . ":" . $ss;
    }

    /**
     * Validate a datetime and convert to standard form (YYYY-mm-dd HH:MM:SS).
     *
     * @static
     *
     * @param string $datetime
     *            The target datetime.
     *            
     * @return string|null converted datetime if valid, else null.
     */
    static function checkDateTime($datetime)
    {
        list ($date, $time) = explode(' ', $datetime);
        if (! $time) {
            if (strpos($date, ':') !== FALSE) {
                $time = $date;
                $date = date('Y-m-d');
            } else
                $time = date('H:i:s');
        }
        if (! $time = self::checkTime($time))
            return '';
        
        if (strpos($date, '/') !== FALSE)
            list ($month, $day, $year) = explode(':', $date);
        elseif (strpos($date, '-') !== FALSE)
            list ($year, $month, $day) = explode('-', $date);
        else
            return '';
        
        if (! checkdate($month, $day, $year))
            return '';
        
        return $year . '-' . $month . '-' . $day . ' ' . $time;
    }

    /**
     * Convert a datetime to a Unix timestamp.
     *
     * @static
     *
     * @param string $datetime
     *            The target datetime.
     *            
     * @return int The converted datetime (seonds since the epoch) if valid, else null.
     */
    static function makeUnixTime($datetime)
    {
        /* Make sure we have a valid datetime */
        if (! $datetime = self::checkDateTime($datetime))
            return '';
        list ($date, $time) = explode(' ', $datetime);
        list ($year, $month, $day) = explode('-', $date);
        list ($hour, $minute, $second) = explode(':', $time);
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Compute elapsed time.
     *
     * This function computes elpased time (duration) in minutes between a (valid) start time
     * and a (valid) end time on the same day, using 24-hout time notation.
     *
     * @static
     *
     * @param string $starttime
     *            The start time.
     * @param string $endtime
     *            The end time.
     *            
     * @return int Elapsed time, in minutes.
     */
    static function elapsedTime($starttime, $endtime)
    {
        list ($sh, $sm, $ss) = explode(":", $starttime);
        list ($eh, $em, $es) = explode(":", $endtime);
        $elapsed = (($eh * 60) + $em) - (($sh * 60) + $sm);
        return $elapsed;
    }

    /**
     * Compute elapsed time.
     *
     * This function computes elpased time (duration) in minutes between a (valid) start datetime
     * and a (valid) end datetime.
     *
     * @static
     *
     * @param string $starttime
     *            The start datetime.
     * @param string $endtime
     *            The end datetime.
     *            
     * @return int Elapsed time, in minutes.
     */
    static function elapsedDateTime($startdatetime, $enddatetime)

    {
        //return (self::makeUnixTime($enddatetime) - self::makeUnixTime($startdatetime)) / 60;
        return ((strtotime($enddatetime) - strtotime($startdatetime)) / 60);
        
    }

    /**
     * Is passed value a boolean?
     *
     * @static
     *
     * @param string $value
     *            The target value.
     *            
     * @return bool true if yes, else false.
     */
    static function checkBool($value)
    {
        if ($value == "1" || (is_int($value) && ($value == 1)) || strtolower(trim($value == 'true')) || (is_bool($value) && ($value == true)) || $value == "0" || (is_int($value) && ($value == 0)) || strtolower(trim($value == 'false')) || (is_bool($value) && ($value == false)) || $value == '' || $value == 0)
            return true;
        else
            return false;
    }

    /**
     * Convert a boolean value to 1 or 0.
     *
     * @static
     *
     * @param string $value
     *            The target value.
     *            
     * @return int 1 or 0.
     */
    static function makeBool($value)
    {
        if (is_object($value))
            $value = (string) $value;
        if (strtoupper(trim($value)) == 'FALSE' || strtoupper(trim($value)) == '0') {
            return 0;
        }
        if (($value) || trim($value) == "1" || $value == 1 || (strtoupper(trim($value)) == 'TRUE') || $value == true) {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * Build a WASE http url header.
     *
     * @return string $url
     *      The url header text.
     */
    static function urlheader() {
        
        if ($_SERVER['HTTPS'])
            $url = 'https://';
        else
            $url = 'http://';
            
        $url .=  $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
        
        // Add in institution if needed
        if ($_SERVER['INSTITUTION'])
            $url .=  '/' . $_SERVER['INSTITUTION'];
            
        return $url;
         
    }

    /**
     * Case insensitive search for string in an array.
     *
     * @static
     *
     * @param string $item
     *            The target string.
     * @param array $array
     *            The target array.
     *            
     * @return bool true if yes, else false.
     */
    static function in_array_ci($item, $array)
    {
        foreach ($array as $arrayvalue) {
            if (strtoupper($item) == strtoupper($arrayvalue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert time to US (AM/PM) format.
     *
     * @static
     *
     * @param string $time
     *            The target time.
     *            
     * @return string The converted time string.
     */
    static function AmPM($time)
    {
        if ((self::posistr($time, "pm") !== false) || (self::posistr($time, 'am') !== false) || (self::posistr($time, 'noon') !== false) || (self::posistr($time, 'midnight') !== false))
            return trim($time);
        
        if (trim($time) == "")
            return trim($time);
        
        list ($hh, $mm, $jj) = explode(":", $time);
        if ($hh >= 12) {
            if ($hh > 12) {
                $hh = $hh - 12;
                $ampm = " pm";
            } else {
                if ($mm == 0)
                    $ampm = " noon";
                else
                    $ampm = " pm";
            }
        } else {
            $ampm = " am";
        }
        
        return trim($hh . ":" . $mm . $ampm);
    }

    /**
     * Case insensitive search for string in another string.
     *
     * @static
     *
     * @param array $haystack
     *            What you are looking in.
     * @param string $needle
     *            What you are looking for.
     * @param int $offset
     *            Optionally, a starting offset in the string to search.
     *            
     * @return bool true if yes, else false.
     */
    static function posistr($haystack, $needle, $offset = 0)
    {
        return strpos(strtoupper($haystack), strtoupper($needle), $offset);
    }

    /**
     * Generate a random string of alphanumeric characters of the specified length.
     *
     * @static
     *
     * @param int $l
     *            The length of the string you want.
     * @param bool $u
     *            If true, forbids same characters next to each other.
     *            
     * @return string The random character string.
     */
    static function rand_chars($l, $u = FALSE)
    {
        $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        if (! $u)
            for ($s = '', $i = 0, $z = strlen($c) - 1; $i < $l; $x = rand(0, $z), $s .= $c{$x}, $i ++);
        else
            for ($i = 0, $z = strlen($c) - 1, $s = $c{rand(0, $z)}, $i = 1; $i != $l; $x = rand(0, $z), $s .= $c{$x}, $s = ($s{$i} == $s{$i - 1} ? substr($s, 0, - 1) : $s), $i = strlen($s));
        return $s;
    }
    

    /**
     * Generate a unique password string of the specified length.
     *
     * @static
     *
     * @param int $l
     *             The length of the string.
     *
     * @return string The password
     *
     */
    static function genpass($l) {
        return self::rand_chars($l);
    }
    
    /**
     * Strip opening/closing quotes from a string.
     * 
     * @static
     * 
     * @param string The string to be unquoted.
     * 
     * @return string The converted string.
     * 
     */
    static function unquote($string) {
        if (
            (
                (substr($string,0,1) == '"') && (substr($string,-1 ) == '"')
            )
            ||
            (
                (substr($string,0,1) == "'") && (substr($string,-1)=="'")
            )
           )
           return substr($string,1,strlen($string)-2);
        else
           return $string;
    }

    /**
     * Strip opening/closing quotes from a string.
     *
     * @static
     *
     * @param string The string to be unquoted.
     *
     * @return array The quote character and the unquoted string.
     *
     */
    static function quoteunquote($string) {
        if (
            (
                (substr($string,0,1) == '"') && (substr($string,-1 ) == '"')
            )
            ||
            (
                (substr($string,0,1) == "'") && (substr($string,-1)=="'")
            )
        )
            return array (substr($string,0,1), substr($string,1,strlen($string)-2));
        else
            return array('',$string);
    }
    
    /**
     * Echo the input array as csv data maintaining consistency with most CSV implementations.
     *
     * @static
     *
     * @param array $fields
     *            Array of values to output as CSV string.
     *            
     * @return void
     */
    static function echocsv($fields)
    {
        $separator = '';
        foreach ($fields as $field) {
            if (preg_match('/\\r|\\n|,|"/', $field)) {
                $field = '"' . str_replace('"', '""', $field) . '"';
            }
            echo $separator . $field;
            $separator = ',';
        }
        echo "\r\n";
    }

    /**
     * Echo a csv file given an array of results (Array of associative arrays).
     *
     *
     * @static
     *
     * @param array $arrayofresults
     *            Array of arrays of values to output as CSV string.
     *            
     * @return void
     */
    static function writecsvfile($arrayofresults)
    {
        // send response headers to the browser
        // following headers instruct the browser to treat the data as a csv file called export.csv
        // header( 'Location: blank');
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="appointments.csv"');
        header("Content-Transfer-Encoding: binary");
        
        // output header row (if atleast one row exists)
        if ($arrayofresults[0]) {
            self::echocsv(array_keys($arrayofresults[0]));
        }
        
        // output data rows (if atleast one row exists)
        foreach ($arrayofresults as $row) {
            self::echocsv($row);
        }
    }

    /**
     * Send a POST requst using cURL.
     *
     * @static
     *
     * @param string $url
     *            to request.
     * @param array $post
     *            values to send.
     * @param array $options
     *            for cURL.
     *            
     * @return string Th HTML returned from the POST.
     */
    static function curl_post($url, array $post = NULL, array $options = array())
    {
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_POSTFIELDS => http_build_query($post)
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (! $result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * Send a GET requst using cURL.
     *
     * @static
     *
     * @param string $url
     *            to request.
     * @param array $get
     *            values to send.
     * @param array $options
     *            for cURL.
     *            
     * @return string The HTML returned from the GET.
     */
    static function curl_get($url, array $get = NULL, array $options = array())
    {
        $defaults = array(
            CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 4
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (! $result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}
?>
