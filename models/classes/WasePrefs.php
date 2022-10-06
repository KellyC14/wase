<?php
/**
 * Support the user preferences interface in Wase.
 * 
 * This (static) class implements the preference save/get functions.  
 * Preferences are used to save settings particular to a user.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */

/* Make sure SQL class is included */
require_once ('WaseSQL.php');

class WasePrefs
{

    /**
     * Save a user preference.
     *
     *
     * @static
     *
     * @param string $userid
     *            Userid.
     * @param string $key
     *            The preference key.
     * @param string $value
     *            The preference value.
     * @param string $class
     *            Optionally, the class (user or system).
     *            
     * @return bool true if saved, else false.
     */
    static function savePref($userid, $key, $value, $class = 'user') 
    {
        /* See if preference exists */
        $curval = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE  (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')'));
        $value = trim($value);
        $key = trim($key);
        $curvalue = trim($curval['value']);
      
        /* Update or add, depending on whether preference already exists or not */
        if ($curval) {
            // If we are chaging our localsync license off of Google, we need to clear out the Google fields 
            if ($key == 'localcal'  && $curvalue == 'google' && $value != 'google') {
                // First, trye to revoke the access token, if any.
                $revoked = WaseGoogle::revoke($userid);
                WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs  WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`= "google_calendarid") LIMIT 1');
                WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs  WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`= "google_token") LIMIT 1');
            }
            $sqlret = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WasePrefs SET `value`=' . WaseSQL::sqlSafe($value) . '  WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')');
        } else {
            $sqlret = WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WasePrefs (`userid`,`key`,`value`,`class`) VALUES (' . WaseSQL::sqlSafe($userid) . ',' . WaseSQL::sqlSafe($key) . ',' . WaseSQL::sqlSafe($value) . ',' . WaseSQL::sqlSafe($class) . ')');
            if ($sqlret)
                return 1;
            else
                return 0;
        }
        
        
        
        return '';
    }
    
    /**
     * Save a user preference (no encoding).
     *
     *
     * @static
     *
     * @param string $userid
     *            Userid.
     * @param string $key
     *            The preference key.
     * @param string $value
     *            The preference value.
     * @param string $class
     *            Optionally, the class (user or system).
     *
     * @return bool true if saved, else false.
     */
    static function savePrefRaw($userid, $key, $value, $class = 'user')
    {
        /* See if preference exists */
        $curval = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE  (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')'));
    
        /* Update or add, depending on whether preference already exists or not */
        if ($curval) {
            $sqlret = WaseSQL::doQuery('UPDATE ' . WaseUtil::getParm('DATABASE') . '.WasePrefs SET `value`=' . "'" . $value . "'" .  ' WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')');
        } else {
            $sqlret = WaseSQL::doQuery('INSERT INTO ' . WaseUtil::getParm('DATABASE') . '.WasePrefs (`userid`,`key`,`value`,`class`) VALUES (' . WaseSQL::sqlSafe($userid) . ',' . WaseSQL::sqlSafe($key) . ',' . "'" . $value .  "'," . WaseSQL::sqlSafe($class) . ')');
            if ($sqlret)
                return 1;
            else
                return 0;
        }
    
        return '';
    }

    /**
     * Return a user preference.
     *
     *
     * @static
     *
     * @param string $userid
     *            Userid.
     * @param string $key
     *            The preference key.
     * @param string $class
     *            Optionally, the class (user or system).
     *            
     * @return string The key value, or null if none.
     */
    static function getPref($userid, $key, $class = 'user')
    {
        
        /* Set default for return value */
        $value = '';
        
        /* Get */
        $sqlret = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')');
        if ($sqlret) {
            $valarray = WaseSQL::doFetch($sqlret);
            if ($valarray)
                $value = $valarray['value'];
        }
        
        return $value;
    }
    
    /**
     * Delete a user preference.
     *
     *
     * @static
     *
     * @param string $userid
     *            Userid.
     * @param string $key
     *            The preference key.
     * @param string $class
     *            Optionally, the class (user or system).
     *
     * @return null.
     */
    static function dropPref($userid, $key, $class = 'user')
    {
    
        /* See if preference exists */
        $curval = WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE  (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')'));
        
        /* Drop if it exists */
        if ($curval) {
            $sqlret = WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs  WHERE (`userid`=' . WaseSQL::sqlSafe($userid) . ' AND `key`=' . WaseSQL::sqlSafe($key) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ') LIMIT 1');
        } 
        
        return '';
    }

    /**
     * Return all user preferences.
     *
     *
     * @static
     *
     * @param string $userid
     *            Userid.
     *            
     * @return array Associative array of key/value pairs.
     */
    static function getAllUserPrefs($userid)
    {
        
        /* Set default return value */
        $valarray = '';
        
        /* Set class */
        $class = 'user';
        
        /* Get all preferences */
        $sqlret = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE (`userid` = ' . WaseSQL::sqlSafe($userid) . ' AND `class`=' . WaseSQL::sqlSafe($class) . ')');
        if ($sqlret)
            $valarray = WaseSQL::doFetch($sqlret);
            
            /* Return all of the preferences (if any). */
        return $valarray;
    }

    /**
     * Return an LDAP user entry
     *
     * @static
     *
     * @param string $userid
     *      The userid of the entry
     *
     * @return array Associative array of the user entry values
     */
    static function getEntry($userid)
    {

        // Ignore requests for @members.
        if (substr($userid, 0, 1) == '@')
            return null;

        // Get the entry.
        WaseMsg::dMsg("WSO2", "Called to get entry for $userid");
        $allentries = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE `userid` = ' . WaseSQL::sqlSafe($userid) . ' AND `class` = "LDAP"');

        if (!$allentries)
            return null;

        $entry = array();

        while ($entries = WaseSQL::doFetch($allentries)) {
            foreach ($entries as $key => $value) {
                if ($key == "key")
                    $entrykey = $value;
                if ($key == "value")
                    $entryvalue = $value;
            }
            if ($entrykey)
                $entry[$entrykey] = $entryvalue;
        }
        WaseMsg::dMsg("WSO2", "Returning entry for $userid", print_r($entry, true));
        if (!$entry || (count($entry) == 0))
            return null;

        // Return the entry if still valid
        if ((microtime(true) - $entry['time']) > WaseWSO2::WSO2_TIMEOUT)
            return $entry;
        WaseSQL::doQuery('DELETE FROM ' . WaseUtil::getParm('DATABASE') . '.WasePrefs WHERE `userid` =  ' . WaseSQL::sqlSafe($userid) . ' AND `class` = "LDAP"');
        return null;

    }


    /**
     * Save an LDAP user entry
     *
     * @static
     *
     * @param string $userid
     *      The userid of the entry
     * @param array $entries
     *      The user LDAP data
     *
     * @return array Associative array of the user entry values
     */
    static function saveEntry($userid, $entries)
    {

        // Iterate through the array and save
        foreach ($entries as $key => $value) {
            if (!is_array($value))
                self::savePrefRaw($userid, $key, $value, $class = 'LDAP');
        }

    }

}
?>