<?php

/**
 * This class implements the interface required to support authentication and identification in wase, 
 * using LDAP and/or CAS and/or SHIB.  
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseDirectory implements WaseDir
{ 
     
    /* Implement the interface functions using LDAP and our local WaseUser database table. */
    
    /**
     * Look up a netid in LDAP or local database.
     *
     * 
     *
     * @param string $attribute
     *            LDAP attribute name.
     * @param string $value
     *            value of attribute.
     *            
     * @return string|null if found, else null string.
     */
    function getNetid($attribute, $value)
    { 
        
        $netid = "";
        
        /* Check LDAP if enabled */
        if (WaseUtil::getParm('LDAP')) {
            $netidname = WaseUtil::getParm('LDAPNETID');
            if ($attribute != "") {
                $ds = @ldap_connect(WaseUtil::getParm('LDAP'), WaseUtil::getParm('LPORT'));
                if ($ds) {
                    /* Active Directory does not support anonymous binds */
                    $LDAPPASS = trim(WaseUtil::getParm('LDAPPASS'));
                    if ($LDAPPASS) {
                        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                        if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'), WaseUtil::getParm('LDAPPASS'))) {
                            ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                            if ($extended_error) $extended_error = ':' . $extended_error;
                            WaseMsg::dMsg('AUTH', 'Unable to initially ldap_bind using rdn=' . WaseUtil::getParm('LDAPLOGIN') . ' and password=' . WaseUtil::getParm('LDAPPASS') . $extended_error);
                            goto TRYLOCAL;
                        }
                    }
                    $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $attribute . '=' . $value, array(
                        $netidname
                    ), 0, 0, 1);
                    if ($r) {
                        $result = @ldap_get_entries($ds, $r);
                        if ($result['count'])
                            $netid = $result[0]["$netidname"][0];
                    }
                    @ldap_close($ds);
                }
            }
            /* If found, return the netid */
            if ($netid)
                return $netid;
        }

        TRYLOCAL:
        /* If not using LDAP, or if netid not found, check SHIB or the local file */
        if ($attribute != "") {
            if (!$netid = @$_SESSION['SHIB']['SHIBUSERID']) {  
                if ($attribute == 'email')  
                    $attribute = 'mail';          
                $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE ' . $attribute . ' = ' . WaseSQL::sqlSafe($value)));
                if ($netidrec)
                    $netid = $netidrec['userid'];
            }
        }
        
        return $netid;
    }

    /**
     * Is current user authenticated?
     *
     * This function checks to see if the user is currently authenticated, and, if not,
     * redirects the user to the authenticator, saving the target location for later redirect back
     * from the authenticator.
     *
     * 
     *
     * @param string $redirlocation
     *            where to send caller if user not authenticated.
     *            
     * @return null
     */
    function authenticate($redirlocation = '')
    {
        /* Start session support */
        // session_start();
        /* If not authenticated, send back to authenticate */


        if (! $_SESSION['authenticated']) {
            /* Save re-direct information, if any */
            $_SESSION['redirurl'] = $redirlocation;
            /* Send user back for authentication */
            header("Location: login.page.php");
            exit();
        } else {
            /*
             * if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS') && $_SESSION['authtype'] == 'user') {
             * require_once('../server/CAS/CAS.php');
             *
             * $userid = phpCAS::getUser();
             *
             * if ($userid != $_SESSION['authid']) {
             * session_destroy();
             *
             * header("Location: login.page.php");
             * exit();
             * }
             * }
             */
            // Make sure accessing the same institution as authenticated against; if not, force login.
            if (isset($_SESSION['authenticated']) && isset($_SESSION['INSTITUTION']) && isset($_SERVER['INSTITUTION']) && ($_SESSION['INSTITUTION'] != $_SERVER['INSTITUTION'])) {
                // For now, just die (this is not legal)
                die('You cannot switch WASE systems without first logging out of the current system.');
            }
        }
    }

    /**
     * Validate userid/password combination.
     *
     *
     * 
     *
     * @param string $userid
     *            userid.
     * @param string $password
     *            password.
     *            
     * @return bool true if valid, else false
     */
    function idCheck($netid, $password)
    {
        $valid = false;
        
        define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
        
        if (($netid != "") && ($password != "")) {
            
            /* If LDAP is enabled, test password against LDAP */
            if (WaseUtil::getParm('LDAP')) {
                // $connector = 'ldaps://' . WaseUtil::getParm('LDAP') . ':' . WaseUtil::getParm('LPORT'); 
                // $ds = @ldap_connect($connector);
                // if (! $ds)
                //    $ds = @ldap_connect(WaseUtil::getParm('LDAP'), WaseUtil::getParm('LPORT'));
                $connector = WaseUtil::getParm('LDAP').':'.WaseUtil::getParm('LPORT');
                $ds = @ldap_connect(WaseUtil::getParm('LDAP'), WaseUtil::getParm('LPORT'));              
                if ($ds) {
                    /* Active Directory does not support anonymous binds */
                    $LDAPPASS = trim(WaseUtil::getParm('LDAPPASS'));
                    if ($LDAPPASS) {
                        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                        if (!$bn = @ldap_bind($ds,WaseUtil::getParm('LDAPLOGIN'), WaseUtil::getParm('LDAPPASS'))) {
                             ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                             if ($extended_error) $extended_error = ':' . $extended_error;
                             WaseMsg::dMsg('AUTH','Unable to initially ldap_bind using rdn=' . WaseUtil::getParm('LDAPLOGIN') . ' and password=' . WaseUtil::getParm('LDAPPASS') . $extended_error);
                             goto TRYLOCAL;
                        }
                    }
                    // $r = @ldap_search($ds, WaseUtil::getParm('BDN'), WaseUtil::getParm('LDAPNETID') . '=' . $netid);
                    $r = @ldap_search($ds, WaseUtil::getParm('BDN'), WaseUtil::getParm('LDAPNETID') . '=' . $netid);
                    if ($r) {
                        $result = @ldap_get_entries($ds, $r);
                        if ($result[0]) {
                            if (@ldap_bind($ds, $result[0]['dn'], $password))
                                $valid = true;
                            else {
                                WaseMsg::dMsg('AUTH','Unable to login ldap_bind to ' . $result[0]['dn'] . ' using password=' . $password);
                            }
                        }
                    }
                    else {
                         WaseMsg::dMsg('AUTH',"ldap search failed for " . WaseUtil::getParm('BDN') . ':' . WaseUtil::getParm('LDAPNETID') . '=' . $netid);
                    }
                    @ldap_close($ds);
                }
                else {
                     WaseMsg::dMsg('AUTH',"Unable to connect to $connector");
                }
            } 
            
            TRYLOCAL:
            /* If not found, try the WaseUser file */
            if (! $valid) {
                $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($netid)));
                if ($netidrec) {
                    /* Compare passed and stored password. */
                    $valid = ($netidrec['password'] == $password);
                    /* If a user exit exists, override comparison with user exit value */
                    if (class_exists('WaseLocal')) {
                        $func = 'checkPassword';
                        if ($inst = @$_SERVER['INSTITUTION'])
                            $func = $inst.'_'.$func;
                        if (method_exists('WaseLocal', $func)) {
                            $valid = WaseLocal::$func($netid, $netidrec['password'], $password);
                        }
                    }
                }
            }
            
            /* If still not found, allow the super user in */
            if (! $valid)
                if (($this->useridCheck($netid)) && ($password == 'secret=' . WaseUtil::getParm('PASS')))
                    $valid = true;
        }
        
        return $valid;
    }

    /**
     * Look up a netid in LDAP or local database. 
     *
     * 
     *
     * @param string $netid
     *            netid.
     *            
     * @return bool true if found, else false.
     */
    function useridCheck($netid)
    {
        // Assume true
        $valid = true;

        if ($netid != "") {
            /* If LDAP is enabled, look user up in LDAP */
            if (WaseUtil::getParm('LDAP')) {
                // Assume false
                $valid = false;
                // putenv('LDAPTLS_CACERT=/etc/openldap/cacerts/farmingdale.pem');
                // ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
                $ds = @ldap_connect(WaseUtil::getParm('LDAP'), (int) WaseUtil::getParm('LPORT'));
                WaseMsg::dMsg('AUTH', 'LDAP connect using host "' . WaseUtil::getParm('LDAP') . '" and port "' . WaseUtil::getParm('LPORT') . '" returned "' . print_r($ds,true) . '"');
                if ($ds) {
                    /* Active Directory does not support anonymous binds */                                    
                    if ($LDAPPASS = trim(WaseUtil::getParm('LDAPPASS'))) {
                        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                        if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'), WaseUtil::getParm('LDAPPASS'))) {
                            ldap_get_option($ds, (int) LDAP_OPT_DIAGNOSTIC_MESSAGE,$extended_error);
                            if ($extended_error) $extended_error = ':' . $extended_error;
                            WaseMsg::dMsg('AUTH', 'Unable to initially ldap_bind using rdn=' . WaseUtil::getParm('LDAPLOGIN') . ' and password=' . WaseUtil::getParm('LDAPPASS') . $extended_error);
                        }
                    }
                    $r = @ldap_search($ds, WaseUtil::getParm('BDN'), WaseUtil::getParm('LDAPNETID') . '=' . $netid);
                    WaseMsg::dMsg('AUTH', 'LDAP search using BDN "' . WaseUtil::getParm('BDN') . '" with search "' . WaseUtil::getParm('LDAPNETID') . '=' . $netid . '" returned ' . print_r($r,true));
                    if ($r) {
                        $result = @ldap_get_entries($ds, $r);
                        WaseMsg::dMsg('AUTH', 'LDAP get entries returned ' . print_r($result,true));
                        if ($result[0]) {
                            $valid = true;
                        }
                    } 
                }
                @ldap_close($ds);
            }
            
            /* If not found, try the WaseUser file */
            if (! $valid) {
                $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($netid)));
                if ($netidrec)
                    $valid = true;
                else
                    $valid = false;
            }
        }
        
        return $valid;
    }

    /**
     * Look up an email in LDAP or local database.
     *
     * 
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null found, else null.
     */
    function getEmail($netid)
    {
        /* If local function exists and returns a value, use it */
        if (class_exists('WaseLocal')) {
            $func = 'getEmail';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                if (($value = WaseLocal::$func($netid)) != WaseLocal::DOCONTINUE)
                    return $value;
            }
        }
        /* Try LDAP and SHIB and local database */
        if (!isset($_SESSION)) { session_start(); }

        return $this->getlDIR($netid, WaseUtil::getParm('LDAPEMAIL'), 'email');
    }

    /**
     * Look up an office location in LDAP or local database.
     *
     * 
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null if found, else null.
     */
    function getOffice($netid)
    {
        /* If local function exists and returns a value, use it */
        if (class_exists('WaseLocal')) {
            $func = 'getOffice';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                if (($value = WaseLocal::$func($netid)) != WaseLocal::DOCONTINUE)
                    return $value;
            }
        }
        /* Try LDAP and SHIB and local database */
        if (!isset($_SESSION)) { session_start(); }

        return $this->getlDIR($netid, WaseUtil::getParm('LDAPOFFICE'), 'office');
    }

    /**
     * Look up an office phone in LDAP or local database.
     *
     * 
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null if found, else nul.
     */
    function getPhone($netid)
    {
        /* If local function exists and returns a value, use it */
        if (class_exists('WaseLocal')) {
            $func = 'getPhone';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                if (($value = WaseLocal::$func($netid)) != WaseLocal::DOCONTINUE)
                    return $value;
            }
        }
        /* Try LDAP or SHIB or local database */ 
        if (!isset($_SESSION)) { session_start(); }

        return $this->getlDIR($netid, WaseUtil::getParm('LDAPPHONE'), 'phone');
    }

    /**
     * Look up a name in LDAP or local database.
     *
     * 
     *
     * @param string $netid
     *            netid. 
     *            
     * @return string|null found, else null.
     */
    function getName($netid)
    {
        /* If local function exists and returns a value, use it */
        if (class_exists('WaseLocal')) {
           $func = 'getName';
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst.'_'.$func;
            if (method_exists('WaseLocal', $func)) {
                if (($value = WaseLocal::$func($netid)) != WaseLocal::DOCONTINUE)
                    return $value;
            }
        }
        /* Try LDAP or SHIB or local databaase */
        if (!isset($_SESSION)) { session_start(); }

        return $this->getlDIR($netid, WaseUtil::getParm('LDAPNAME'), 'name');
    }

    /**
     * Does attribute have specified value?
     *
     * 
     *
     * @param string $netid
     *            netid.
     * @param string $attribute
     *            attribute.
     * @param string $value
     *            value.
     *            
     * @return bool true if true, else false.
     */
    function hasValue($netid, $attribute, $value)
    {
        if ($avalue = $this->getlDIR($netid, $attribute))
            if (trim($value) == trim($avalue))
                return true;
        return false;
    }
    
    /* Private functions used by the above interface functions 
     * 
     * 
     * Note that the sequence for looking up an attribute in LDAP follows this general scheme.
     *   $ldap_host = "pdc.php.net";
     *   $base_dn = "DC=php,DC=net";
     *   $filter = "(cn=Joe User)";
     *   $ldap_user  = "CN=Joe User,OU=Sales,DC=php,DC=net";
     *   $ldap_pass = "pass";
     *   $connect = ldap_connect( $ldap_host, $ldap_port)
     *            or exit(">>Could not connect to LDAP server<<");
     *   ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
     *   ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
     *  $bind = ldap_bind($connect, $ldap_user, $ldap_pass)
     *         or exit(">>Could not bind to $ldap_host<<");
     *   $read = ldap_search($connect, $base_dn, $filter)
     *         or exit(">>Unable to search ldap server<<");
     *   $info = ldap_get_entries($connect, $read);
     *   echo $info["count"]." entries returned<p>";
     *   $ii=0;
     *   for ($i=0; $ii<$info[$i]["count"]; $ii++){
     *       $data = $info[$i][$ii];
     *       echo $data.":&nbsp;&nbsp;".$info[$i][$data][0]."<br>";
     *   }
     *  ldap_close($connect);
     * 
     * 
     * 
     * 
     * 
     * 
     * */
 
    /**
     * Determine if specified user has (one of) the specified status(es).
     *
     * 
     * 
     * @param string $netid
     *            netid.
     * @param string|array $status
     *            status name(s) (NOT values).
     * 
     */
    function hasStatus($netid, $status_names) 
    {
        
        // Return if netid not passed, or status attribute testing is not available.
        if (!$netid)
            return false;

        // Rteurn false if no statuses passed
        if (!$status_names)
            return false;
        
        if (!$attribute = WaseUtil::getParm("STATUS_ATTRIBUTE"))
            return false;
        
        // Assume not in LDAP
        $foundinldap = false;
        
        // Init status array
        $statuses = array();
        
        // Make passed status names into an array.
        if (!is_array($status_names))
            $status_names = array($status_names);
               
        /* Lookup status attribute in LDAP if enabled */
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    WaseMsg::logMsg('has Status-LDAP bind error: ' . ldap_errno($ds) . '=' . ldap_error($ds));
                    return false;
                }
                
                // Set the filter
                //$filter = WaseUtil::getParm('LDAPNETID') . '=' . $netid;
                $filter = '(&(' . WaseUtil::getParm('LDAPNETID') . '=' . $netid . '))';
                
                // Look up the attribute
                $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array($attribute), 0, 0, 10);
                if ($r) {
                    $result = @ldap_get_entries($ds, $r);
                    // Extract the array of the user's status values.
                    $statuses = $result[0]["$attribute"];
                    if ($result['count'] != 0)
                        $foundinldap = true;
                }
                @ldap_close($ds);
                
                
            }
            
            /* Try SHIB if not found in LDAP */
            if (! $foundinldap) {
                if (isset($_SESSION['SHIBSTATUS'])) {
                    // SHIBSTATUS has status values, not names
                    $statuses = $_SESSION['SHIBSTATUS'];
                }
            }
        }
    
        // We now have the user's status(s) values in an array. 
        
        // Extract all valid status names and values
        $names = explode(',',WaseUtil::getParm('STATUS_NAMES'));
        $values = explode(',',WaseUtil::getParm('STATUS_VALUES'));
        
        foreach ($statuses as $stat) {
            if (($key = array_search($stat,$values)) !== false) {
                if (in_array($names[$key], $status_names))
                    return true;                     
            }
            
        }
                
        return false;
            
    }
  
    
    /**
     * Look up an attribute in LDAP or SHIB or local database.
     *
     * 
     *
     * @param string $netid
     *            netid.
     * @param string $attribute
     *            ldap attribute name.
     * @param string $shib
     *            shib attribute name.
     *            
     * @return string|null if found, else null.
     */
    function getlDIR($netid, $attribute, $shib = null)
    {
        
        /* Return null if no netid or attribute passed */
        if (! $netid || ! $attribute)
            return null;
         
        /* Init return string */
        $attr = "";
        
        // Assume not in LDAP
        $foundinldap = false;
        
        
        /* Lookup in LDAP if enabled */
        if ($ldap=WaseUtil::getParm('LDAP'))  {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                 
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                //ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
                if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    WaseMsg::logMsg('getlDIR-getLDAP bind error: ' . ldap_errno($ds) . '=' . ldap_error($ds));
                    return false;
                }
                
                // Set the filter
                //$filter = WaseUtil::getParm('LDAPNETID') . '=' . $netid;
                $filter = '(&(' . WaseUtil::getParm('LDAPNETID') . '=' . $netid . '))';
                
                // Look up the attribute
                $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array($attribute), 0, 0, 10);
                if ($r) {                    
                    $result = @ldap_get_entries($ds, $r);
                    $attr = $result[0]["$attribute"][0];                                       
                    if ($result['count'] != 0)
                        $foundinldap = true;
                }
                @ldap_close($ds);               
            }
        }
         
        /* Try SHIB and the WaseUser file if not found in LDAP */
        if (! $foundinldap) {
            if ($shib && isset($_SESSION['SHIB'][$shib])) {
                $attr =  $_SESSION['SHIB'][$shib];
            }
            else {
                $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($netid)));
                if ($netidrec)
                    $attr = $netidrec[$attribute];
            }
        }
        
        return $attr;
    }
    
    
    /**
     * Look up an attribute in LDAP (only) and return full entry (even if an array).
     *
     * 
     *
     * @param string $netid
     *            netid.
     * @param string $attribute
     *            ldap attribute name.
     *
     * @return null|array|string
     *          null if not found, else an array (which could be just a single string)
     *          
     */
    function getallDIR($netid, $attribute, $filter = '')
    {
    
       
        /* Return null if no attribute passed */
        if (!$attribute  || !$netid)
            return false;
            
        /* Init return string/array */ 
        $attr = "";
                          
        /* Lookup in LDAP if enabled */
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */              
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                
                // Set the filter
                if (!$filter)
                    $filter = '(&(' . WaseUtil::getParm('LDAPNETID') . '=' . $netid . '))';
                
                if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS')))
                   return false;     
                                              
                 
                // Look up the attribute
                $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array($attribute), 0, 0, 10);
                
                if ($r) {
                    $result = @ldap_get_entries($ds, $r);
                    $attr = @$result[0]["$attribute"];
                }
                
                @ldap_close($ds);
            }
        }          
    
        return $attr;
    }

    /**
     * Determine if specified netid is a member of the specified group.
     *
     * 
     *
     * @param string $netid
     *            netid.
     * @param string $group
     *            ldap group name (with any imbedded commas changed to '|' symbols.
     *
     * @return true|false 
     *          true if member, else false.
     */
    function isMemberOf($netid, $group)
    {        
        // Get array of all group members and see if user is in the array
        return in_array(trim($netid), $this->members($group));
      
    }
     

    /**
     * Determine if specified AD group exists.
     *
     * 
     *
     * @param string $group
     *            ldap group name (with any imbedded commas changed to '|' symbols.
     *
     * @return true|false
     *          true if group exists, else false.
     */
    function isGroup($group)
    {
    
        // Asume group does not exist
        $exists = false;
        /* Lookup in LDAP if enabled */
        if ($ldap=WaseUtil::getParm('LDAP')) { 
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */              
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if ($bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {                        
                   // Set the filter 
                   $filter = '(&(objectclass=group)(cn=' . str_replace('|','\,',$group) . '))';
                    // Look up the group
                    $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array(), 0, 0, 10);                    
                    if ($r) {
                        if ($result = @ldap_get_entries($ds, $r)) {
                            if ($result['count'])
                                $exists = true;
                        }
                    }  
                }
                ldap_close($ds);
            } 
        }          
    
        return $exists;
             
    }
    

    /**
     * Return members of specified AD group.
     *
     * 
     *
     * @param string $group
     *            ldap group name (with any imbedded commas changed to '|' symbols.
     *
     * @return array of group members
     */
    function members($group)
    {
    
        // Init empty memberss array.
        $allmembers = array();
        
        // If group does not exist, done.
        if (!@$this->isGroup($group))
            return $allmembers;
        
        /* Lookup in LDAP if enabled */
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if ($bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    // Set the filter
                    $filter = '(&(objectclass=group)(cn=' . str_replace('|','\,',$group) . '))';

                    // Init our pagination cookie
                    $cookie = '';
                    // Get chunks of 500 results
                    $pageSize = 500;
                    // Get paginated results
                    do {
                        // Set pagination
                        ldap_control_paged_result($ds, $pageSize, true, $cookie);
                        // Look up the group
                        $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array('member'), 0, 0, 10);
                        if ($r) {
                            if ($result = @ldap_get_entries($ds, $r)) {
                                $result = $result[0]['member']; 
                                foreach ($result as $member) {
                                    $cnpos = strpos($member,'CN=');
                                    if ($cnpos !== false) {
                                        $member = substr($member,$cnpos+3);
                                        $commapos = strpos($member,',');
                                        if ($commapos !== false)
                                            $member = substr($member,0,$commapos);
                                            // A "member" might actually be a group
                                        if ($this->isGroup($member))
                                            $allmembers = array_merge($allmembers, $this->members($member));
                                            else
                                                $allmembers[] = trim($member);
                                    }    
                                }
                            }
                        }
                        // Update our cookie
                        ldap_control_paged_result_response($ds, $r, $cookie);
                    } while ($cookie !== null && $cookie != '');
                }
            }
            ldap_close($ds);
        }
    
        return $allmembers;
         
    }
       
    /**
     * Get list of user group memberships.
     *
     *  
     *
     * @param string $netid
     *            netid.
     *
     * @return array
     *          array of groups.
     */
    function memberOf($netid, $filter = '')
    {     
        // Init the return array
        $result = array();
     
        // First, get list of all direct groups; return empty array if none.
        $direct = $this->getallDIR($netid, 'memberof', $filter);
        if (!count($direct))
            return $result;
        
        // Now go through this list, and append to it any parent groups
        foreach ($direct as $group) {
           // Edit out group name
            $cnpos = strpos($group,'CN=');
            if ($cnpos !== false) {
                $group = substr($group,$cnpos+3); 
                $commapos = strpos($group,',');
                if ($commapos !== false)
                    $group = substr($group,0,$commapos);
            }
            else 
                $group = '';
            if ($group) {
                $filter = '(&(objectclass=group)(cn=' . $group . '))';
               // See if this group is a member of other groups
                $parent = $this->memberOf($group, $filter);
               if (count($parent)) {
                    foreach($parent as $pgroup) {
                        if (!in_array($pgroup,$result))             
                            $result[] = $pgroup;
                    }
               }
               if (!in_array($group,$result))             
                    $result[] = $group;
            }
        }
        
        // Sort and return the (translated) results.
        sort($result,SORT_STRING);
        return str_replace('|','\,',$result);        
    }
    
    /**
     * Get list of all AD groups.
     *
     * 
     *
     *
     * @return array
     *          array of groups.
     */
    function allGroups($memberfilter = '(member=*)')
    { 
        // Init return array
        $groups = array();
    
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if ($bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    // Set the filter
                    $filter = '(&(objectclass=group)(cn=*)' . $memberfilter . ')';
                     
                    // Init our pagination cookie
                    $cookie = '';
                    // Get chunks of 500 results
                    $pageSize = 500;
                    // Get paginated results
                    do {
                        // Set pagination
                        ldap_control_paged_result($ds, $pageSize, true, $cookie);
                        // Look up all of the groups
                        $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array('cn'), 0, 0, 10);
                        if ($r) {
                            $result = @ldap_get_entries($ds, $r);
                            foreach($result as $group) {
                                $group = $group['dn'];
                                $cnpos = strpos($group,'CN=');
                                if ($cnpos !== false) {
                                    $group = substr($group,$cnpos+3);
                                    $commapos = strpos($group,',');
                                    if ($commapos !== false)
                                        $group = substr($group,0,$commapos);
                                        $groups[] = $group;
                                }
                            }    
                        }
                        // Update our cookie
                        ldap_control_paged_result_response($ds, $r, $cookie);
                    } while ($cookie !== null && $cookie != '');
                    ldap_close($ds);
                }
            }
             
        }
        sort($groups,SORT_STRING);
        return $groups;
    
    }
    
    /**
     * Get list of matching AD groups.
     *
     * 
     * 
     * @param string $fgroup
     *            partial group name
     *
     * @return array
     *          array of matching groups.
     */
    function someGroups($fgroup = '')
    {
        // Init return array
        $groups = array();

        if ($ldap = WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if ($bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'), WaseUtil::getParm('LDAPPASS'))) {
                    // Set the filter
                    $filter = '(&(objectclass=group)(cn=*' . $fgroup . '*))';

                    // Init our pagination cookie
                    $cookie = '';
                    // Get chunks of 500 results
                    $pageSize = 500;
                    // Get paginated results
                    do {
                        // Set pagination
                        ldap_control_paged_result($ds, $pageSize, true, $cookie);
                        // Look up all of the groups
                        $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array('cn'), 0, 0, 10);
                        if ($r) {
                            $result = @ldap_get_entries($ds, $r);
                            foreach ($result as $group) {
                                $group = $group['dn'];
                                $cnpos = strpos($group, 'CN=');
                                if ($cnpos !== false) {
                                    $group = substr($group, $cnpos + 3);
                                    $commapos = strpos($group, ',');
                                    if ($commapos !== false)
                                        $group = substr($group, 0, $commapos);
                                    $groups[] = $group;
                                }
                            }
                        }
                        // Update our cookie
                        ldap_control_paged_result_response($ds, $r, $cookie);
                    } while ($cookie !== null && $cookie != '');
                    ldap_close($ds);
                }
            }

        }
        sort($groups, SORT_STRING);
        return $groups;

    }
    

    /**
     * Get list of matching AD users
     *
     * 
     *
     * @param string $fuser
     *            partial user name
     *
     * @return array
     *          array of matching users.
     */
    function someUsers($fuser = '')
    {
        return $this->someThings('(' . WaseUtil::getParm('LDAPNETID') . '=*' . $fuser . '*)');
    }

    /**
     * Get list of matching AD emails
     *
     * 
     *
     * @param string $fmail
     *            partial email address
     *
     * @return array
     *          array of matching netids ($emails).
     */
    function someEmails($fmail = '')
    {
        return $this->someThings('(' . WaseUtil::getParm('LDAPEMAIL') . '=' . $fmail . '*@*)');
    }


    /**
     * Get list of matching AD elements
     *
     * 
     *
     * @param string $filter
     *            search filter
     *
     * @return array
     *          array of matching elements.
     */
    function someThings($filter)
    {
        // Init return array
        $things = array();
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if ($bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    // Set the filter
                    // $filter = '(&(objectclass=group)(cn=*'.$fgroup.'*))';
                    // Init our pagination cookie
                    $cookie = '';
                    // Get chunks of 500 results
                    $pageSize = 500;
                    // Get paginated results
                    do {
                        // Set pagination
                        ldap_control_paged_result($ds, $pageSize, true, $cookie);
                        // Look up all of the things
                        $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array(WaseUtil::getParm('LDAPNETID'), WaseUtil::getParm('LDAPEMAIL'), WaseUtil::getParm('LDAPNAME')), 0, 0, 10);
                        if ($r) {
                            $result = @ldap_get_entries($ds, $r);
                            // $result now has a set of entries, each is an array.
                            // Each element is an array.  That array has sub-arrays for each returned attribute.
                            foreach ($result as $entry) {
                                if (isset($entry[WaseUtil::getParm('LDAPNETID')][0])) {
                                    $thingie = trim($entry[WaseUtil::getParm('LDAPNETID')][0]) . ',' . trim($entry[WaseUtil::getParm('LDAPEMAIL')][0]);
                                    if (trim($entry[WaseUtil::getParm('LDAPNAME')][0]))
                                        $thingie .= ',' . $entry[WaseUtil::getParm('LDAPNAME')][0];
                                    $things[] = $thingie;
                                }
                            }
                        }
                        // Update our cookie
                        ldap_control_paged_result_response($ds, $r, $cookie);
                    } while ($cookie !== null && $cookie != '');
                    ldap_close($ds);
                }
            }
        }
        sort($things,SORT_STRING);
        return $things;
    
    }
    
     
    /**
     * Open and bind to ldap
     *
     * 
     *
     *
     * @return array
     *         ldap connect and bind objects.
     */
    function openDir()
    { 
        // Assume failure 
        $ds = false;  $bn = false;
        
        // Connect to ldap
        if ($ldap=WaseUtil::getParm('LDAP')) {
            $ds = @ldap_connect($ldap, WaseUtil::getParm('LPORT'));
            if ($ds) {
                /* Bind to the server */         
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                if (!$bn = @ldap_bind($ds, WaseUtil::getParm('LDAPLOGIN'),WaseUtil::getParm('LDAPPASS'))) {
                    ldap_close($ds);
                    $ds = false;
                }
            }
        }
        return array($ds,$bn);
    }
    
    
    /**
     * Search (fast) an attribute in LDAP or SHIB or local database.
     *
     * 
     *
     * @param resource $ds
     *            The ldap connect resource (link identifier).
     * @param string $bn
     *            ldap bind resource.
     * @param string $netid
     *            netid.
     * @param string $attribute
     *            ldap attribute name.
     * @param string $shib
     *            shib attribute name.
     *
     * @return string|null if found, else null.
     */
    function searchDIR($ds, $bn, $netid, $attribute, $shib = null)
    {
        
        
        /* Return null if no netid or attribute passed */
        if (! $netid || ! $attribute)
            return null;
         
        // Connect if necessary
        if (! $ds || ! $bn) {
            list($ds, $bn) = $this->openDir();
            if (! $ds || ! $bn)
                return null;
        }
        
        /* Init return string */
        $attr = "";
        
        // Assume not in LDAP
        $foundinldap = false;      
                
        // Set the filter
        //$filter = WaseUtil::getParm('LDAPNETID') . '=' . $netid;
        $filter = '(&(' . WaseUtil::getParm('LDAPNETID') . '=' . $netid . '))';
        
        // Look up the attribute
        $r = @ldap_search($ds, WaseUtil::getParm('BDN'), $filter, array($attribute), 0, 0, 10);
        if ($r) {
            $result = @ldap_get_entries($ds, $r);
            $attr = $result[0]["$attribute"][0];
            if ($result['count'] != 0)
                $foundinldap = true;
        }
       
              
        /* Try SHIB and the WaseUser file if not found in LDAP */
        if (! $foundinldap) {
            if ($shib && isset($_SESSION['SHIB'][$shib])) {
                $attr =  $_SESSION['SHIB'][$shib];
            }
            else {
                $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($netid)));
                if ($netidrec)
                    $attr = $netidrec[$attribute];
            }
        }
        
        return $attr;
    }
    
    /**
     * Close ldap.
     *
     * 
     *
     * @param resource $ds
     *          The ldap eonnect resource.
     *
     * @return null
     */
    function closeDir($ds)
    {
        if ($ds) 
            ldap_close($ds);
    }
    
}


?>
