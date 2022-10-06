<?php

/**
 * This class implements an interface to WSO2 APIs.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 */
class WaseWSO2 implements WaseDir
{

    /** @constant define WSO2 timeout value */
    const WSO2_TIMEOUT = 60 * 60;  // 1 hour default timeout

    /** @var string $token the WSO2 access token. */
    private $token;
    /** @var string $key the WSO2 application key. */
    private $key;
    /** @var string $token the WSO2 application secret. */
    private $secret;
    /** $var string the base64_encoded version of the key/secret. */
    private $encoded;
    /** Time in micro-seconds when token was obtained */
    private $tokentime;

    /**
     * The constructor saves the passed consumer key and secret.
     *
     *
     * @param string $key the consumjer key (or null).
     * @param string $secret the consumer secret, or null
     *
     * @return true true if token obatined, else false..
     */
    function __construct($key = null, $secret = null)
    {

        // If secret or key not passed, read them from the parms file.
        $this->key = ($key ? $key : WaseUtil::getParm('WSO2KEY'));
        $this->secret = ($secret ? $secret : WaseUtil::getParm('WSO2SECRET'));

        // Generate base64-encoded version of key:secret.
        $this->encoded = base64_encode("$this->key:$this->secret");

        return true;

    }

    /**
     * Does attribute have specified value?
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
        die('Function hasValue not implemented in WSO2 directory class');
    }

    /**
     * Validate userid/password combination.
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
        // Assume not found
        $valid = false;
        // Try the locval look-aside database.
        $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($netid)));
        if ($netidrec) {
            /* Compare passed and stored password. */
            $valid = ($netidrec['password'] == $password);
            /* If a user exit exists, override comparison with user exit value */
            if (class_exists('WaseLocal')) {
                $func = 'checkPassword';
                if ($inst = @$_SERVER['INSTITUTION'])
                    $func = $inst . '_' . $func;
                if (method_exists('WaseLocal', $func)) {
                    $valid = WaseLocal::$func($netid, $netidrec['password'], $password);
                }
            }
        }

        /* If still not found, allow the super user in */
        if (!$valid)
            if (($this->useridCheck($netid)) && ($password == 'secret=' . WaseUtil::getParm('PASS')))
                $valid = true;

    }

    /**
     * Is current user authenticated?
     *
     * This function checks to see if the user is currently authenticated, and, if not,
     * redirects the user to the authenticator, saving the target location for later redirect back
     * from the authenticator.
     *
     *
     * @param string $redirlocation
     *            where to send caller if user not authenticated.
     *
     * @return null
     */
    function authenticate($redirlocation = '')
    {
        if (!$_SESSION['authenticated']) {
            /* Save re-direct information, if any */
            $_SESSION['redirurl'] = $redirlocation;
            /* Send user back for authentication */
            header("Location: login.page.php");
            exit();
        } else {
            // Make sure accessing the same institution as authenticated against; if not, force login.
            if (isset($_SESSION['authenticated']) && isset($_SESSION['INSTITUTION']) && isset($_SERVER['INSTITUTION']) && ($_SESSION['INSTITUTION'] != $_SERVER['INSTITUTION'])) {
                // For now, just die (this is not legal)
                die('You cannot switch WASE systems without first logging out of the current system.');
            }
        }
    }


    /**
     * Look up identity information about a user given a netid/email/PUID.
     * This code (re-)generates the WSO2 access token (OAUTH2), since it lasts for a limited time.
     *
     * @param string $id Either a netid, a PUID (number) or an email address token (first part of email).
     *
     * @return 0 (if not found) or a PHP array of user identification information.
     */
    function getUser($id)
    {

        // If no userid, return null
        if (!$id = trim($id))
            return "";

        // Let's time this
        $before = microtime(true);
        WaseMsg::dMsg("WSO2", "Called to get user", $id);

        // Because WSO2 is very slow, we are going to buffer user atribute data in our database, and first check to see if we have it
        if ($entry = WasePrefs::getEntry($id))
            return $entry;

        // We'll try twice to get a valid access token
        for ($i = 0; $i < 2; $i++) {
            // Try to get an access token
            if (!$this->getToken()) {
                WaseMsg::log('Unable to get access token from WSO2');
                return "";
            };
            WaseMsg::dMsg("WSO2", "Got WSO2 token for $id", $this->token);
            // Use the access token to look up the user using the passed id  (netid/puid/email) information.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, WaseUtil::getParm('WSO2HOST') . WaseUtil::getParm('WSO2MATCHSEARCHPATH') . urlencode($id));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "Authorization: Bearer " . $this->token
            ));
            curl_setopt($curl, CURLOPT_POST, 0);
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $page = curl_exec($curl);

            // Extract return code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            WaseMsg::dMsg("WSO2", "WSO2  code for $id", $http_code);
            curl_close($curl);

            // If 401 error, we need to regenerate the token
            if ($http_code == 401) {
                $this->dropToken();
            } // Otherwise continue
            else
                break;
        }

        // If good data returned, extract it
        $data = json_decode($page, true);
        if ($http_code == 200) {
            //v103 - returns different json output
            //$result = $data["result"];
            //$entry = $result["entry"];
            $entry = $data;
            // Save the entry in prefs
            $entry["time"] = microtime(true);
            WasePrefs::saveEntry($id, $entry);
        } else {
            //$error = $data->error;
            //$error_description = $data->error_description;
            WaseMsg::log("WSO2 error $http_code looking up user $id");
            // See if the user is in the local database
            $entry = "";
        }

        // Time it
        $elapsed = microtime(true) - $before;
        WaseMsg::dMsg("WSO2", "WSO2 getuser for $id = ", "elapsed time in seconds = " . $elapsed);


        return $entry;
    }

    /**
     * Look up a netid in WSO2 or local database or other directory.
     *
     *
     * @param string $netid
     *            netid.
     *
     * @return bool true if found, else false.
     */
    function useridCheck($netid)
    {
        if (!$entry = $this->getUser($netid))
            return false;
        if ($entry[WaseUtil::getParm('LDAPNETID')])
            return true;
        else
            return false;
    }

    /**
     * Look up a netid in WSO2 which has an attribute with the specified value.
     *
     *
     * @param string $attribute
     *            LDAP attribute name.
     * @param string $value
     *            value of attribute.
     *
     * @return string|null else null string.
     */
    function getNetid($attribute, $value)
    {
        // Init "not found" state
        $found = false;
        $netid = "";

        // Try to get an access token, log an exception if we can't get one.
        if ($this->getToken(true)) {

            // Use the access token to look up the user using the passed id  (netid/puid/email) information.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, WaseUtil::getParm('WSO2HOST') . WaseUtil::getParm('WSO2LDAPSEARCHPATH') . $attribute . '=' . $value);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "Authorization: Bearer " . $this->token
            ));
            curl_setopt($curl, CURLOPT_POST, FALSE);
            curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $page = curl_exec($curl);

            // Extract return code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);


            // If good data returned, extract it
            $data = json_decode($page, true);
            if ($http_code == 200) {
                $result = $data["result"];
                $entry = $result["entry"];
                WaseMsg::dMsg("WSO2", "getNetid for $attribute = $value returned " . print_r($entry, true));
                // If multiple values returned, use the first one
                if (key_exists(0, $entry))
                    $entry = $entry[0];
                if ($netid = $entry[WaseUtil::getParm("LDAPNETID")])
                    $found = true;
            } else {
                //$error = $data->error;
                //$error_description = $data->error_description;
                WaseMsg::dMsg("WSO2", "WSO2 error $http_code getting netid for attribute $attribute with value $value");
            }
        }

        // If we couldn;t find the attribute in WSO2, try the local database.;
        if (!$found) {
            if ($attribute != "") {
                if (!$netid = @$_SESSION['SHIB']['SHIBUSERID']) {
                    if ($attribute == 'email')
                        $attribute = 'mail';
                    if ($attribute == 'uid')
                        $attribute = 'userid';
                    $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE ' . $attribute . ' = ' . WaseSQL::sqlSafe($value)));
                    if ($netidrec)
                        $netid = $netidrec['userid'];
                }
            }
        }

        return $netid;
    }


    /**
     * Look up an email in LDAP or local database.
     *
     *
     * @param string $netid
     *            netid.
     *
     * @return string|null null.
     */
    function getEmail($netid)
    {
        return $this->getAttribute($netid, WaseUtil::getParm('LDAPEMAIL'), 'getEmail', 'email');
    }

    /**
     * Look up an office location in LDAP or local database or other directory.
     *
     *
     * @param string $netid
     *            netid.
     *
     * @return string|null else null.
     */
    function getOffice($netid)
    {
        return $this->getAttribute($netid, WaseUtil::getParm('LDAPOFFICE'), 'getOffice', 'office');
    }

    /**
     * Look up an office phone in LDAP or local database or other directory.
     *
     *
     * @param string $netid
     *            netid.
     *
     * @return string|null else nul.
     */
    function getPhone($netid)
    {
        return $this->getAttribute($netid, WaseUtil::getParm('LDAPPHONE'), 'getPhone', 'phone');
    }

    /**
     * Look up a name in LDAP or local database or other directory.
     *
     *
     * @param string $netid
     *            netid.
     *
     * @return string|null null.
     */
    function getName($netid)
    {
        return $this->getAttribute($netid, WaseUtil::getParm('LDAPNAME'), 'getName', 'name');
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
    function someUsers($fuser)
    {
        return $this->someThings(WaseUtil::getParm('LDAPNETID'), $fuser . '*');

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
    function someEmails($fmail)
    {
        return $this->someThings(WaseUtil::getParm('LDAPEMAIL'), $fmail . '*');

    }

    /**
     * Determine if specified group exists
     *
     *
     *
     * @param string $group
     *            group name
     *
     * @return boolean
     *          true or false.
     *
     */
    function isGroup($group)
    {
        // If we can find the group
        if (count($this->someGroups($group)))
            return true;
        else
            return false;
    }

    /**
     * Get list of matching AD groups
     *
     * 
     *
     * @param string $group
     *            partial group name
     *
     * @return array
     *          array of matching group names.
     */
    function someGroups($group) {

        // Assume not found
        $found = false;
        // Init results array
        $matches = array();

        // Try to get an access token, log an exception if we can't get one.
        if ($this->getToken(true)) {

            // Use the access token to look up the user using the passed id  (netid/puid/email) information.
            $curl = curl_init();
            $curlvalue = WaseUtil::getParm('WSO2HOST') . WaseUtil::getParm('WSO2LDAPGROUPSPATH') . 'name=' . urlencode('*' . $group . '*');
            curl_setopt($curl, CURLOPT_URL, $curlvalue);
            WaseMsg::dMsg("WSO2", "getGroups for groupname = $group using url: $curlvalue");


            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "Authorization: Bearer " . $this->token
            ));
            curl_setopt($curl, CURLOPT_POST, FALSE);
            curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $page = curl_exec($curl);

            // Extract return code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);


            // If good data returned, extract it
            $entry = json_decode($page, true);
            if ($http_code == 200) {
                // $result = $data["result"];
                // $entry = $result["entry"];
                WaseMsg::dMsg("WSO2", "getGroups for groupname = $group returned " . print_r($entry, true));
                $found = true;
            } else {
                //$error = $data->error;
                //$error_description = $data->error_description;
                WaseMsg::dMsg("WSO2", "WSO2 error $http_code getting groups with name=" . $group);
            }
        }

        // If found, build array of results
        if ($found) {
            foreach ($entry as $match) {
                $matches[] = $match['name'];
            }
        }

        sort($matches, SORT_STRING);
        return $matches;
    }

    /**
     * Get list of LDAP attributes
     *
     * 
     *
     * @param string $attribute
     *            search attribute
     * @param string $attribute
     *             search value
     * @param string $objclass
     *             objectclass to restrict search results
     *
     * @return array
     *          array of matching elements.
     */
    function someThings($attribute, $value, $objclass)
    {
        // Init results array
        $matches = array();

        // Try to get an access token, log an exception if we can't get one.
        if ($this->getToken(true)) {

            // Use the access token to look up the user using the passed id  (netid/puid/email) information.
            $curl = curl_init();
            $curlvalue = WaseUtil::getParm('WSO2HOST') . WaseUtil::getParm('WSO2LDAPSEARCHPATH') . $attribute . '=' . urlencode($value);
            if ($objclass)
                $curlvalue .= '&objectclass=' . $objclass;
            curl_setopt($curl, CURLOPT_URL, $curlvalue);


            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "Authorization: Bearer " . $this->token
            ));
            curl_setopt($curl, CURLOPT_POST, FALSE);
            curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $page = curl_exec($curl);

            // Extract return code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);


            // If good data returned, extract it
            $entry = json_decode($page, true);
            if ($http_code == 200) {
                // Entry is an array of matching ldap entries, each entry having numerous attributes
                WaseMsg::dMsg("WSO2", "getNetid for $attribute = $value returned " . print_r($entry, true));
                // Make sure we have an array.
                if (!is_array($entry))
                    $entry = array($entry);
                foreach ($entry as $match) {
                    if (isset($match[WaseUtil::getParm('LDAPNETID')])) {
                        $thingie = trim($match[WaseUtil::getParm('LDAPNETID')]) . ',' . trim($match[WaseUtil::getParm('LDAPEMAIL')]);
                        if (trim($match[WaseUtil::getParm('LDAPNAME')]))
                            $thingie .= ',' . $match[WaseUtil::getParm('LDAPNAME')];
                        $matches[] = $thingie;
                    }
                }
            } else {
                //$error = $data->error;
                //$error_description = $data->error_description;
                WaseMsg::dMsg("WSO2", "WSO2 error $http_code getting netid for attribute $attribute with value $value");
            }
        }

        return $matches;
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
        // Init results array
        $members = array();

        // Try to get an access token, log an exception if we can't get one.
        if ($this->getToken(true)) {

            // Use the access token to look up the user using the passed id  (netid/puid/email) information.
            $curl = curl_init();
            $curlvalue = WaseUtil::getParm('WSO2HOST') . WaseUtil::getParm('WSO2LDAPGROUPSPATH') . 'name=' . urlencode($group);
            curl_setopt($curl, CURLOPT_URL, $curlvalue);
            WaseMsg::dMsg("WSO2", "members for groupname = $group using url: $curlvalue");


            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "Authorization: Bearer " . $this->token
            ));
            curl_setopt($curl, CURLOPT_POST, FALSE);
            curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $page = curl_exec($curl);

            // Extract return code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);


            // If good data returned, extract it
            $entry = json_decode($page, true);
            if ($http_code == 200) {
                // $result = $data["result"];
                // $entry = $result["entry"];
                WaseMsg::dMsg("WSO2", "members for groupname = $group returned " . print_r($entry, true));
                $found = true;
            } else {
                //$error = $data->error;
                //$error_description = $data->error_description;
                WaseMsg::dMsg("WSO2", "WSO2 error $http_code getting groups with name=" . $group);
            }
        }

        // If found, build array of results
        if ($found) {
            if (key_exists(0, $entry))
                $entry = $entry[0];
            $mem = $entry['member'];
            foreach ($mem as $member) {
                list($cn, $rest) = explode(',', $member);
                $members[] = trim(substr($cn, 3));
            }
        }

        return $members;

    }

    /**
     * Use the stored WSO2 key and secret to obtain a (temporary) access token.
     *
     * @parm boolean $force
     *      If set to truem ignore token buffer.
     *
     * @return 0 or http error return code.
     */
    private function getToken($force = false)
    {

        // Grab saved token, if any
        if (!$force) {
            $token = WasePrefs::getPref('system', 'WSO2token', 'system');
            WaseMsg::dMsg("WSO2", "Called to get token", $token);
        } else
            $token = "";

        // If we have a token, see if it has expired
        if ($token != "") {
            $token_time = WasePrefs::getPref('system', 'WSO2token_time', 'system');
            WaseMsg::dMsg("WSO2", "Got token time", $token_time);
            if ($token_time != "") {
                // If token still valid, return it;
                list($usec, $nowsec) = explode(" ", microtime());
                if (($nowsec - $token_time) < self::WSO2_TIMEOUT) {
                    $this->token = $token;
                    WaseMsg::dMsg("WSO2", "Token still valid");
                    return true;
                }
            }
            // If not valide, get rid of old one
            $this->dropToken();
        }


        // If we don;t have a saved token, get one from WSO2.
        $host = WaseUtil::getParm('WSO2HOST');
        $token_path = WaseUtil::getParm('WSO2TOKENPATH');
        WaseMsg::dMsg("WSO2", "Getting token from", $host . ' at ' . $token_path);
        // If token path does not start with an "/", add one.
        if (substr($token_path, 0, 1) != '/' && substr($host, -1, 1) != "/")
            $host .= "/";

        // Set up curl to retrieve the access token.
        $curl = curl_init($host . $token_path);

        // Set required curl options
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic $this->encoded",
        ));
        curl_setopt($curl, CURLOPT_HTTPGET, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // Now get the token and the return code.
        $page = curl_exec($curl);
        $token = json_decode($page);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        WaseMsg::dMsg("WSO2", "Call to get new token returned http code", $http_code);

        // If bad return code, log the error
        if ($http_code != 200) {
            //$error = $token->error;
            //$error_description = $token->error_description;
            WaseMsg::log("WSO2 error $http_code getting access token");
            return false;
        } // If succcessful, save the token.
        else {
            $this->token = $token->access_token;
            // Save the token and its creatime time.
            list($usec, $token_time) = explode(" ", microtime());
            WasePrefs::savePref('system', 'WSO2token', $this->token, 'system');
            WasePrefs::savePref('system', 'WSO2token_time', $token_time, 'system');
            WaseMsg::dMsg("WSO2", "New token is", $this->token);
            return true;
        }
    }

    /**
     * Drop the saved access token.
     *
     * @return 0 or http error return code.
     */
    private function dropToken()
    {
        // Invalidate the saved token
        WasePrefs::dropPref('system', 'WSO2token', 'system');
        WasePrefs::dropPref('system', 'WSO2token_time', 'system');
    }



    /**
     * Return a specific attribute value to the caller.
     *
     * @param string $id Either a netid, a PUID (number) or an email address token (first part of email).
     * @param string $attribute the attribute requested (e.g., email, name, etc.)
     *
     * @return attribute value.
     */
    private function getAttribute($id, $attribute, $caller, $shib)
    {
        // Init return value
        $attr = '';

        /* If local function exists and returns a value, use it */
        if (class_exists('WaseLocal')) {
            $func = $caller;
            if ($inst = @$_SERVER['INSTITUTION'])
                $func = $inst . '_' . $caller;
            if (method_exists('WaseLocal', $func)) {
                if (($value = WaseLocal::$func($id)) != WaseLocal::DOCONTINUE) {
                    return $value;
                }
            }
        }
        // TRY WSO2

        // Because WSO2 is very slow, we buffer it's data in WasePrefs
        if ($entry = WasePrefs::getEntry($id))
            if ($attr = $entry[$attribute])
                return $attr;

        if ($userentry = $this->getUser($id)) {
            if ($attr = $userentry[$attribute]) {
                return $attr;
            }
        }

        /* Try SHIB and the WaseUser file if not found in WSO2 */
        if ($shib && isset($_SESSION['SHIB'][$shib])) {
            $attr = $_SESSION['SHIB'][$shib];
        } else {
            $netidrec = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseUser WHERE userid = ' . WaseSQL::sqlSafe($id)));
            if ($netidrec)
                $attr = $netidrec[$attribute];
        }
        return $attr;
    }

    private function gettime()
    {
        return microtime(true);
    }


}
?>
