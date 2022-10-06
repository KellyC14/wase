<?php

/**
 * This script contains the MySQL table definitions for WASE.
 *
 *
 * @copyright 2006, 2008, 2013. 2015 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 *
 */


/* Define the tables */
$tables = array(
    'WaseSeries',
    'WasePrefs',
    'WaseAcCal',
    'WaseCalendar',
    'WaseAppointment',
    'WaseManager',
    'WaseMember',
    'WaseBlock',
    'WasePeriod',
    'WaseNonGrata',
    'WaseDidYouKnow',
    'WaseUser',
    'WaseWait'
    // 'WaseParms'
);

/* Build table creation SQL statements */
$d_WaseAcCal = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseAcCal (
 `accalid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `daytypes` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`accalid`)
) ENGINE=MyISAM AUTO_INCREMENT=11367 DEFAULT CHARSET=utf8;";


$d_WaseSeries = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseSeries (
  `seriesid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `userid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) DEFAULT NULL,
  `startdate` date NOT NULL DEFAULT '0000-00-00',
  `enddate` date NOT NULL DEFAULT '0000-00-00',
  `every` varchar(255) NOT NULL DEFAULT 'weekly',
  `daytypes` varchar(255) NOT NULL DEFAULT 'Teaching Day',
  `slotsize` int(11) NOT NULL DEFAULT '0',
  `maxapps` int(11) NOT NULL DEFAULT '1', 
  `maxper` int(11) NOT NULL DEFAULT '0',
  `deadline` int(11) NOT NULL DEFAULT '0',
  `candeadline` int(11) NOT NULL DEFAULT '0',
  `opening` int(11) NOT NULL DEFAULT '0',
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifyman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifymem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `available` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `makeaccess` varchar(255) NOT NULL DEFAULT '',
  `viewaccess` varchar(255) NOT NULL DEFAULT '',
  `makeulist` text NOT NULL,
  `makeclist` text NOT NULL,
  `viewulist` text NOT NULL,
  `viewclist` text NOT NULL,
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindmem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `apptmsg` text NOT NULL,
  `showappinfo` tinyint(1) unsigned DEFAULT '0',
  `purreq` tinyint(1) unsigned DEFAULT '0',
  `viewglist` text NOT NULL,
  `viewslist` text NOT NULL,
  `makeglist` text NOT NULL,
  `makeslist` text NOT NULL,
  `NAMETHING` varchar(255) NOT NULL DEFAULT '',
  `NAMETHINGS` varchar(255) NOT NULL DEFAULT '',
  `APPTHING` varchar(255) NOT NULL DEFAULT '',
  `APPTHINGS` varchar(255) NOT NULL DEFAULT '',
  `exdate` text NOT NULL,
  PRIMARY KEY (`seriesid`),
  KEY `ownername` (`name`),
  KEY `startdate` (`startdate`)
) ENGINE=MyISAM AUTO_INCREMENT=603 DEFAULT CHARSET=utf8 COMMENT='Describes a recurring block.';";


$d_WaseAppointment = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseAppointment ( 
  `appointmentid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `blockid` int(11) unsigned NOT NULL DEFAULT '0',
  `calendarid` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `textemail` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `userid` varchar(255) DEFAULT NULL,
  `startdatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `purpose` text,
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `reminded` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notes` mediumtext NOT NULL,
  `uid` varchar(512) NOT NULL,
  `gid` varchar(512) NOT NULL,
  `eid` varchar(512) NOT NULL,
  `eck` varchar(512) NOT NULL,
  `whenmade` datetime NOT NULL,
  `lastchange` datetime NOT NULL,
  `madeby` varchar(255) DEFAULT NULL,
  `available` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `sequence` int(11) unsigned NOT NULL DEFAULT '0',
  `venue` text,
  PRIMARY KEY (`appointmentid`),
  KEY `blockid` (`blockid`),
  KEY `userid` (`userid`),
  KEY `startdatetime` (`startdatetime`),
  KEY `calendarid` (`calendarid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$d_WaseBlock = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseBlock (
  `blockid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `periodid` int(11) unsigned NOT NULL DEFAULT '0',
  `seriesid` int(11) unsigned NOT NULL DEFAULT '0',
  `calendarid` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `userid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL DEFAULT '',
  `recurrence` varchar(255) NOT NULL DEFAULT 'multiple',
  `startdatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `slotsize` int(11) NOT NULL DEFAULT '0',
  `maxapps` int(11) NOT NULL DEFAULT '1',
  `maxper` int(11) NOT NULL DEFAULT '0',
  `deadline` int(11) NOT NULL DEFAULT '0',
  `candeadline` int(11) NOT NULL DEFAULT '0',
  `opening` int(11) NOT NULL DEFAULT '0',
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifyman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifymem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `available` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `makeaccess` varchar(255) NOT NULL DEFAULT '',
  `viewaccess` varchar(255) NOT NULL DEFAULT '',
  `makeulist` text NOT NULL,
  `makeclist` text NOT NULL,
  `viewulist` text NOT NULL,
  `viewclist` text NOT NULL,
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindmem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `apptmsg` text NOT NULL,
  `showappinfo` tinyint(1) unsigned DEFAULT '0',
  `uid` varchar(255) NOT NULL DEFAULT '',
  `gid` varchar(255) NOT NULL,
  `eid` varchar(255) NOT NULL,
  `eck` varchar(512) NOT NULL,
  `sequence` int(11) unsigned NOT NULL DEFAULT '0',
  `purreq` tinyint(1) unsigned DEFAULT '0',
  `lastchange` datetime NOT NULL,
  `viewglist` text NOT NULL,
  `viewslist` text NOT NULL,
  `makeglist` text NOT NULL,
  `makeslist` text NOT NULL,
  `NAMETHING` varchar(255) NOT NULL DEFAULT '',
  `NAMETHINGS` varchar(255) NOT NULL DEFAULT '',
  `APPTHING` varchar(255) NOT NULL DEFAULT '',
  `APPTHINGS` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`blockid`),
  KEY `userid` (`userid`),
  KEY `startdatetime` (`startdatetime`),
  KEY `calendarid` (`calendarid`)
) ENGINE=MyISAM AUTO_INCREMENT=5243 DEFAULT CHARSET=utf8 COMMENT='Describes an office hour block.';";

$d_WaseCalendar = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseCalendar (
  `calendarid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `userid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) DEFAULT NULL,
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifyman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notifymem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `available` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `makeaccess` varchar(255) NOT NULL DEFAULT '',
  `viewaccess` varchar(255) NOT NULL DEFAULT '',
  `makeulist` text NOT NULL,
  `makeclist` text NOT NULL,
  `viewulist` text NOT NULL,
  `viewclist` text NOT NULL,
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindman` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `remindmem` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `apptmsg` text NOT NULL,
  `showappinfo` tinyint(1) unsigned DEFAULT '0',
  `purreq` tinyint(1) unsigned DEFAULT '0',
  `overlapok` tinyint(1) unsigned DEFAULT '0',
  `waitlist` tinyint(1) unsigned DEFAULT '0',
  `icalpass` varchar(10) DEFAULT NULL,
  `viewglist` text NOT NULL,
  `viewslist` text NOT NULL,
  `makeglist` text NOT NULL,
  `makeslist` text NOT NULL,
  `NAMETHING` varchar(255) NOT NULL DEFAULT '',
  `NAMETHINGS` varchar(255) NOT NULL DEFAULT '',
  `APPTHING` varchar(255) NOT NULL DEFAULT '',
  `APPTHINGS` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`calendarid`),
  KEY `ownername` (`name`),
  KEY `ownernetid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Describes a user calendar.';";

$d_WaseDidYouKnow = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseDidYouKnow (
`didyouknowid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `header` varchar(255) DEFAULT NULL,
  `details` text,
  `dateadded` date NOT NULL,
  `release` varchar(255) DEFAULT '0',
  `topics` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`didyouknowid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;";


$d_WaseManager = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseManager (
 `calendarid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '',
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$d_WaseMember = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseMember (
  `calendarid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '',
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `remind` tinyint(1) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$d_WaseNonGrata = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseNonGrata (
  `calendarid` int(11) unsigned NOT NULL,
  `authid` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$d_WasePeriod = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WasePeriod (
  `periodid` int(11) NOT NULL AUTO_INCREMENT,
  `seriesid` int(11) NOT NULL DEFAULT '0',
  `starttime` time NOT NULL DEFAULT '00:00:00',
  `duration` int(11) unsigned NOT NULL,
  `dayofweek` varchar(255) NOT NULL DEFAULT '',
  `dayofmonth` int(11) NOT NULL DEFAULT '0',
  `weekofmonth` int(11) NOT NULL DEFAULT '0',
  `rrule` varchar(255) NOT NULL,
  `exdate` text NOT NULL,
  `rdate` text NOT NULL,
  PRIMARY KEY (`periodid`)
) ENGINE=MyISAM AUTO_INCREMENT=594 DEFAULT CHARSET=utf8;";

$d_WasePrefs = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WasePrefs (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(4096) NOT NULL,
  `class` varchar(255) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  KEY `netid` (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=5237 DEFAULT CHARSET=utf8;";

$d_WaseUser = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseUser (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `displayname` varchar(255) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `telephonenumber` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;"; 

$d_WaseWait = "CREATE TABLE  " . WaseUtil::getParm('DATABASE') . ".WaseWait (
  `waitid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `calendarid` int(11) NOT NULL DEFAULT '0',
  `userid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `textemail` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `startdatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `whenadded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`waitid`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;";


/* Now define table updates that may need to be applied to upgrade older versions */
$needtoalter = true;

$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WasePeriod ADD `exdate` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `exdate` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WasePeriod ADD `rdate` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WasePeriod ADD `rrule` VARCHAR(255) NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `lastchange` datetime NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseAppointment ADD `lastchange` datetime NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `viewglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `viewglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `viewglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `viewslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `viewslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `viewslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `makeglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `makeglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `makeglist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `makeslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `makeslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `makeslist` text NOT NULL;";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `NAMETHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `NAMETHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `APPTHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseSeries ADD `APPTHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `NAMETHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `NAMETHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `APPTHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseCalendar ADD `APPTHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `NAMETHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `NAMETHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `APPTHING` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseBlock ADD `APPTHINGS` varchar(255) NOT NULL DEFAULT '';";
$al[] = "ALTER TABLE " . WaseUtil::getParm('DATABASE') . ".WaseAppointment ADD `venue` text;";

$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET APPTHING = "appointment" WHERE APPTHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET APPTHINGS = "appointments" WHERE APPTHINGS = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET NAMETHING = "office hour" WHERE NAMETHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseCalendar SET NAMETHINGS = "office hours" WHERE NAMETHINGS = ""';


$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET APPTHING = "appointment" WHERE APPTHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET APPTHINGS = "appointments" WHERE APPTHINGS = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET NAMETHING = "office hour" WHERE NAMETHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseBlock SET NAMETHINGS = "office hours" WHERE NAMETHINGS = ""';


$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseSeries SET APPTHING = "appointment" WHERE APPTHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseSeries SET APPTHINGS = "appointments" WHERE APPTHINGS = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseSeries SET NAMETHING = "office hour" WHERE NAMETHING = ""';
$al[] = 'UPDATE ' . WaseUtil::getParm('DATABASE') . '.WaseSeries SET NAMETHINGS = "office hours" WHERE NAMETHINGS = ""';

$al[] = 'ALTER TABLE ' .  WaseUtil::getParm('DATABASE') . '.WaseBlock CHANGE maxapps maxapps INT( 11 ) NOT NULL DEFAULT  "1"';
$al[] = 'ALTER TABLE ' .  WaseUtil::getParm('DATABASE') . '.WaseSeries CHANGE maxapps maxapps INT( 11 ) NOT NULL DEFAULT  "1"';
 
?>
