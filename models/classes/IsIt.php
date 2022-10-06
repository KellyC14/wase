<?php
namespace classes;

/**
 * This class is used to test if a variable has a certain value.
 *
 * Use this as:  if ($var == IsIt::CREATE()) {};
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * @author Jill Moraca, jmoraca@princeton.edu
 */
class IsIt
{

     private static $CREATE = 1;
     private static $UPDATE = 2;
     private static $REMIND = 3;
     private static $LOAD = 4;

     public static function __callStatic($name, $arguments)
     {
         if (!self::${$name})
             throw new Exception('Variable ' . self::${$name} . ' not defined in IsIt class.');
             
         return (count($arguments) === 0) ? self::${$name} : ($arguments[0] === self::${$name});
     }
}
?>