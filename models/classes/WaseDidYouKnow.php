<?php

/**
 * 
 * @copyright 2006, 2008, 2013, 2014 The Trustees of Princeton University.
 *
 * @license See the license.txt file in the docs directory.
 *
 * @author Serge J. Goldstein, serge@princeton.edu., Kelly D. Cole, kellyc@princeton.edu, Jill Moraca, jmoraca@princeton.edu		   
 *
 * @abstract his class describes a "did you know" entry.
 */
class WaseDidYouKnow
{
    
    /* Properties */
    
    /**
     *
     * @var int $didyouknowid Database id of the WaseDidYouKnow entry.
     */
    public $didyouknowid;

    /**
     *
     * @var string $header header text.
     */
    public $header;

    /**
     *
     * @var string $details Entry text.
     */
    public $details;

    /**
     *
     * @var string $dateadded DDate entry added to database.
     */
    public $dateadded;

    /**
     *
     * @var string $release Associated release.
     */
    public $release;

    /**
     *
     * @var string $topics Comma-seperated list of topics.
     */
    public $topics;
    
    /* Static (class) methods */
    
    /**
     * Look up an entry in the database and return its values as an associative array.
     *
     * @static
     *
     * @param int $id
     *            database id of the entry.
     *            
     * @return array an associative array of the entry fields.
     */
    static function find($didyouknowid)
    {
        
        /* Find the entry and return it (or false if none) */
        return WaseSQL::doFetch(WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow WHERE didyouknowid=' . WaseSQL::sqlSafe($didyouknowid)));
    }

    /**
     * Return a set of entries that match the specified criteria.
     *
     * @static
     *
     * @param string $dateadded
     *            starting add date of entries.
     * @param string $release
     *            starting release.
     * @param
     *            string topics
     *            list of matching topics.
     *            
     * @return WaseList of matching entry ids.
     */
    static function getEntries($dateadded, $release, $topics)
    {
        
        /* Our goal is to build a select list that will return the requested entries */
        
        /* Initialize the select string */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseDidYouKnow WHERE 1=1 ';
        
        /* If date specified, limit to entries >= specified date */
        if ($dateadded)
            $select .= ' and `date` >= ' . WaseSQL::sqlSafe(WaseSQL::sqlDate($dateadded));
            
        /* Read in the entries and remember the matching ids */
        $matches = array();
        $entries = WaseSQL::doQuery($select);
        while ($entry = WaseSQL::doFetch($entries)) {
            $match = true;
            if ($release) {
                if (WaseRelease::Compare($release, $entry['release']) == 'GT')
                    $match = false;
            } else {
                if (WaseRelease::CompareToCurrent($entry['release']) == 'GT')
                    $match = false;
            }
            if ($match && $topics) {
                $target_topics = explode(',', $topics);
                $entry_topics = explode(',', $entry['topics']);
                $match = false;
                foreach ($target_topics as $topic)
                    if (in_array($topic, $entry_topics)) {
                        $match = true;
                        break;
                    }
            }
            
            if ($match)
                $matches[] = $entry['didyouknowid'];
        }
        
        /* Now add matching ids to the select statement */
        if ($matches) {
            $select .= ' and (2=3  ';
            foreach ($matches as $match) {
                $select .= ' or didyouknowid = ' . WaseSQL::sqlSafe($match); 
            }
            $select .= ')';
        }
        
        /* Return in random order */
        $select .= ' ORDER BY RAND()';
        
        /* Now return the WaseList */
        return new WaseList($select, 'DidYouKnow');
    }
    
    /* Object Methods */
    
    /**
     * Build an entry from passed data.
     *
     * @param array $data
     *            associative array of entry values.
     *            
     * @return WaseDidYouKnow a didyouknow entry.
     */
    function __construct($data)
    {
        
        /* Load the database data into the object. */
        WaseUtil::loadObject($this, $data);
    }
}
?>