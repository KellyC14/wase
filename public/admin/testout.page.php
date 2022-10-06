<?php

define('XMLFHEADER','<?xml version="1.0" encoding="UTF-8"?> 
								<wase>
								'); 

/* We no longer use this function. */
function __oldautoload($class) {
	/* Set path to WaseParms and WaseLocal */
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
require_once('../../vendor/autoload.php');

/* Turn on output buffering */
ob_start();

$input = XMLFHEADER . trim($_POST['wase']) . '</wase>';
 
//$host = 'waseqa.princeton.edu'; 
$host = $_SERVER['SERVER_NAME'];

 
echo '<html><head><title>waseAjax</title></head><body>Input is:<br />' . htmlentities($input) . '<br /><br />Output is:<br /><br />';

$reply = sendToHost('ssl://' . $host,
			443,
			'post',
			'/princeton/controllers/ajax/waseAjax.php',
			$input,
			$useragent=0);
		
echo htmlentities($reply);
			 
echo '</body></html>';
 
/* Flush the output buffer */
ob_flush();

exit();


/* Issue a GET or POST to a web host */
/* sendToHost
 * ~~~~~~~~~~
 * Params:
 *   $host      - host name, possibly preceded with ssl:// 
 *   $port      - 80 or 443 (ssl)
 *   $method    - get or post, case-insensitive
 *   $path      - The /path/to/file.html part
 *   $data      - The query string, without initial question mark (or post data)
 *   $useragent - If true, 'MSIE' will be sent as 
                  the User-Agent (optional)
 *
 * Examples:
 *   sendToHost('www.google.com',80,'get','/search','q=php_imlib');
 *   sendToHost('www.example.com','post','/some_script.cgi',
 *              'param=First+Param&second=Second+param');
 *   For GET, the data consists of urlencoded field=value strings, seperated by & signs:
 *             name=Serge+Goldstein&id=serge&password=secret
 *
 */

function sendToHost($host,$port,$method,$path,$data,$useragent=0)
{
    // Supply a default method of GET if the one passed was empty
    if (empty($method)) {
        $method = 'GET';
    }
	list($prefix,$justhost) = explode('://',$host);
	if ($justhost == '') 
		$justhost = $host;
    $method = strtoupper($method);
    $fp = fsockopen($host, $port, $errno, $errstr);
	if (!$fp) {
		return 'Connect error for host=' . $host . ', port=' . $port . ' errortext="' . $errstr . '", errornumber=' . $errno;
	}
    if ($method == 'GET') {
        $path .= '?' . $data;
    }
    fputs($fp, "$method $path HTTP/1.1\r\n");
    fputs($fp, "Host: $justhost\r\n");
    fputs($fp,"Content-type: text/xml\r\n");
    fputs($fp, "Content-length: " . strlen($data) . "\r\n");
    if ($useragent) {
        fputs($fp, "User-Agent: MSIE\r\n");
    }
    fputs($fp, "Connection: close\r\n\r\n");
    if ($method == 'POST') {
        fputs($fp, $data);
    }
	$buf = '';
    while (!feof($fp)) {
        $buf .= fgets($fp,128);
    }
    fclose($fp);
    return $buf;
}