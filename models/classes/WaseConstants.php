<?php
/**
 * This class contains a set of useful constants.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 */
class WaseConstants {
 

    /** @var string DoNotSendCancellationNotice  */
    const DoNotSendCancellationNotice = 'DO NOT SEND CANCELLATION NOTIFICATION(S)';
    
    /** @var string localcalexchange */
    const LocalCalExchange = 'exchange';
    
    /** @var string localcalgoodle */
    const LocalCalGoogle = 'google';
    
    /** @var string localcalical */
    const LocalCalIcal = 'ical';
    
    /** @var string localcalnone */
    const LocalCalNone = 'none';

    // Constants for SHIB attribute lookups

    const SHIBUIDURN = "urn:oid:0.9.2342.19200300.100.1.1";
    const SHIBEMAILURN = "urn:oid:0.9.2342.19200300.100.1.3";
    const SHIBPHONEURN = "urn:oid:2.5.4.20";
    const SHIBNAMEURN = "urn:oid:2.16.840.1.113730.3.1.241";
    const SHIBOFFICEURN = "urn:oid:1.2.840.113556.1.2.256";
   
}
?>