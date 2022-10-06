<?php

/**
 * This class is a factory which creates Directory classes for WASE.
 *
 * The Directory classes implement Directory-specific code to allow WASE to interact with a Directory.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 */
class WaseDirectoryFactory
{

    /**
     * This function returns a Direcrory class that corresponds to the Directory being run by the institution.
     *
     * The DIRECTORY paraneter specifies the type of Direcory interface that is run by the institution.
     *
     *
     * @return object The Directory class which implements the wase Directory interface.
     */
    static function getDirectory()
    {
        // Get the local Directory designation and see if it is valid.
        $directoryclass = 'Wase' . trim(WaseUtil::getParm('DIRECTORY'));
        if (class_exists($directoryclass))
            return new $directoryclass();
        else {  // Try "directory" as the default.
            $directoryclass = 'WaseDirectory';
            if (class_exists($directoryclass))
                return new $directoryclass();
            else
                throw new Exception("Class $directoryclass does not exist ... the " . WaseUtil::getParm('SYSID') . " configuration file may have a bad DIRECTORY parameter, or the class does not exist or was not found.");
        }
    }

}

?>