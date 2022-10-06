<?php
/**
 * This class describes the daytype calendar.
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */

class WaseAcCal {

 	/* Properties */
    /** @var int $accalid MySQL id of the date record in the WaseAcCal table. */
	public $accalid;		
	/** @var string $date The target date. */
	public $date;			
	/** @var string $daytypes The daytype for a given date. */
	public $daytypes;      	/* The type of day for this date. */
 
	/* Static (class) methods */
	
	/**
	 * Look up a date and return its day type.
	 *
	 *
	 * @param string $date a date in YYYY-MM-DD format.
	 *
	 * @return string A string that encodes the daytype ('Teaching", 'Summer', etc.).
	 *
	 */
	static function getDaytype($date) {

	    /* Read in our DAYTYPES parameter, return null string if none */
	    if (!($daytypes = self::getAllDaytypes()))
	        return '';
	    
	    /* Extract default daytype */
	    $default = $daytypes[0];
	    
		/* Find the entry, or return default if none */
		if (!$entry = WaseSQL::doQuery('SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAcCal WHERE date=' . WaseSQL::sqlSafe($date)))
			return $default;
	
		/* Get the entry into an associative array (there can only be 1). */
		$result = WaseSQL::doFetch($entry);
		/* Free up the query */
		WaseSQL::freeQuery($entry);	
		/* Return the result */   
		if (in_array($result['daytypes'],$daytypes))
			return $result['daytypes'];
		else
			return $default;
	
	}
		

	/**
	 * Return the deafult day type.
	 *
	 *
	 * @return string A string that encodes the default daytype ('Teaching", 'Summer', etc.).
	 *
	 */
	static function defaultDaytype() {
	
	    /* Read in our DAYTYPES parameter as an array */
	   $daytypes = self::getAllDaytypes();
	   
	   /* Return first entry or null */
	   if (is_array($daytypes)) {
	       if (count($daytypes) < 2)
	           return '';
	       else
	           return $daytypes[0];
	   }
	   else
	       return '';
	     
	
	}
	

	/**
	 * Return all day types as an array.
	 *
	 * We return a null string if there are only one or fewer daytypes.
	 *
	 * @return array An array of all day types in the parameters file.
	 *
	 */
	static function getAllDaytypes() {
	
	    $daytypes = explode(',',WaseUtil::getParm('DAYTYPES'));	
	    if (count($daytypes) < 2)
	        return '';
	    else 
	        return $daytypes;
	
	}
}
?>