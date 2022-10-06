<?php

/**
 * This class implements an in-memory object properties cache.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * 
 */
class WaseCache
{

    /** @var array $cache  This array contains the cached object properties. */
    private static $cache = array();

    /** @var boolean $caching  This flag indicates if caching is ON or OFF. */
    public static $caching = false;

    /** @var boolean $loging  This flag indicates if loging is ON or OFF. */
    public static $loging = false; 
    
    /** @var boolean $getloging  This flag indicates if loging of gets is ON or OFF. */
    public static $getloging = false;
    
    /** @var integer $hts  This int counts up cache hits. */
    public static $hits = 0;

    /**
     * Clear the cache.
     *
     * @static
     *
     *
     * @return void
     */
    static function clear()
    {
        if (self::$caching) {
            if (self::$loging)
                WaseMsg::log('Clearing the cache');
            self::$cache = array();
            // self::$hits = 0;
        }
    }

    /**
     * Add an element (array of properties) to the cache.
     *
     * @static
     *
     * @param string $name
     *            The name of the obejct.
     * @param array $properties
     *            The properties of the object.
     *            
     * @return void
     */
    static function add($name, $properties)
    {
        if (self::$caching && $name && $properties) {
            if (self::$loging)
                WaseMsg::log('Adding ' . $name);
            self::$cache[$name] = $properties;
        }
    }

    /**
     * Remove an element (array of properties) from the cache.
     *
     * @static
     *
     * @param string $name
     *            The name of the object.
     *            
     * @return void
     */
    static function remove($name)
    {
        if (self::$caching && $name) {
            if (self::$loging)
                WaseMsg::log('Removing ' . $name);
            unset(self::$cache[$name]);
        }
    }

    /**
     * Is the object cached?
     *
     * @param string $name
     *            The name of the object.
     *            
     * @return boolean True if yes, else false.
     */
    static function exists($name)
    {
        if (! self::$caching) {
            if (self::$loging)
                WaseMsg::log('No caching ' . $name);
            return false;
        }
        
        if (! $name) {
            if (self::$loging)
                WaseMsg::log('No value ' . $name);
            return false;
        } else {
            if (self::$loging)
                WaseMsg::log('Existing ' . $name . ' is ' . (key_exists($name, self::$cache) ? 'true' : 'false'));
            return (key_exists($name, self::$cache));
        }
    }

    /**
     * Return the properties of an object.
     *
     * @param string $name
     *            The name of the object.
     *            
     * @return array The properties, or null if not found.
     */
    static function get($name)   
    {
        if (self::$caching && $name) {
            if (self::$loging || self::$getloging)
                WaseMsg::log('Getting ' . $name);
            self::$hits += 1;
            return @self::$cache[$name];
        } else {
            return null;
        }
    }
}
?>