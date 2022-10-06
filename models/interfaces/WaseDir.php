<?php

/**
 * This interface defines the methods required to support authentication and identification in wase.
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
interface WaseDir
{

    /**
     * Look up a netid in LDAP or local database or other directory.
     *
     * @static
     *
     * @param string $attribute
     *            LDAP attribute name.
     * @param string $value
     *            value of attribute.
     *            
     * @return string|null else null string.
     */
    function getNetid($attribute, $value);

    /**
     * Is current user authenticated?
     *
     * This function checks to see if the user is currently authenticated, and, if not,
     * redirects the user to the authenticator, saving the target location for later redirect back
     * from the authenticator.
     *
     * @static
     *
     * @param string $redirlocation
     *            where to send caller if user not authenticated.
     *            
     * @return null
     */
    function authenticate($redirlocation = '');

    /**
     * Validate userid/password combination.
     *
     *
     * @static
     *
     * @param string $userid
     *            userid.
     * @param string $password
     *            password.
     *            
     * @return bool true if valid, else false
     */
    function idCheck($netid, $password);

    /**
     * Look up a netid in LDAP or local database or other directory.
     *
     * @static
     *
     * @param string $netid
     *            netid.
     *            
     * @return bool true if found, else false.
     */
    function useridCheck($netid);

    /**
     * Look up an email in LDAP or local database.
     *
     * @static
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null null.
     */
    function getEmail($netid);

    /**
     * Look up an office location in LDAP or local database or other directory.
     *
     * @static
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null else null.
     */
    function getOffice($netid);

    /**
     * Look up an office phone in LDAP or local database or other directory.
     *
     * @static
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null else nul.
     */
    function getPhone($netid);

    /**
     * Look up a name in LDAP or local database or other directory.
     *
     * @static
     *
     * @param string $netid
     *            netid.
     *            
     * @return string|null null.
     */
    function getName($netid);

    /**
     * Does attribute have specified value?
     *
     * @static
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
    function hasValue($netid, $attribute, $value);


}

?>