<?php 
/* Handle loading of classes. */
function __oldautoload($class) {
	/* Set path to WaseParms or other classes */
	if ($class == 'WaseParms')
		$parmspath = '../../config/';
    else
    	$parmspath = '../../models/classes/';
		
	/* Now load the class */ 
	if ($class != 'WaseLocal')
		require_once($parmspath.$class.'.php');
	else
		@include_once($parmspath.$class.'.php');
	
}


/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

?>
