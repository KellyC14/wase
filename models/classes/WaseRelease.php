<?php

/**
 * This class contains the current release of WASE, and a utility function to compare releases.
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
 
/* 
 *
 *
 */
class WaseRelease
{

    const RELEASE = "3.0.7";
    
    /* Static (class) methods */
    
    /**
     * Determine if a release is LT, GT or EQ to another release.
     *
     *
     * @static
     *
     * @param string $first
     *            first release.
     * @param string $second
     *            second release.
     *            
     * @return string LT, GT or EQ.
     */
    static function Compare($first, $second)
    {
        if (! $first)
            $first = array(
                0
            );
        else
            $first = explode('.', $first);
        if (! $second)
            $second = array(
                0
            );
        else
            $second = explode('.', $second);
            
            /* Zero-fill the two arrays so that they have the same length */
        while (count($first) < count($second))
            $first[] = 0;
        while (count($second) < count($first))
            $second[] = 0;
            
            /* Now compare and return the appropriate boolean */
        for ($i = 0; $i < count($first); $i ++) {
            if ($first[$i] < $second[$i])
                return 'LT';
            elseif ($first[$i] > $second[$i])
                return 'GT';
        }
        
        return 'EQ';
    }

    /**
     * Determine if a release is LT, GT or EQ to currnt release.
     *
     *
     * @static
     *
     * @param string $first
     *            first release.
     *            
     * @return string LT, GT or EQ.
     */
    static function CompareToCurrent($release)
    {
        return self::Compare($release, self::RELEASE);
    }
}
?>