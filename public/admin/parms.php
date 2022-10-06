<?php

/**
 * This script allows the institutional administrator to manage their institutiuonal parms. 
 * These parms controls localication of the WASE system.
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the admin directory.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 *
 */

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';
$infmsg = '';


/* Check for SSL connection. */
if (!$_SERVER['HTTPS'] && !$_REQUEST['nossl']) 
	die('The install.php script must be invoked using https.  If you really want to run this without SSL, append "&nossl=1" to the URL you used to invoke this script.'); 


/* Make sure password was specified */
WaseUtil::CheckAdminAuth();

// Read in the parms file.
if ($institution = $_SERVER['INSTITUTION'])
    $instdir = '/'.$institution;
else
    $instdir = '';
$confFile = __DIR__ . '/../../config'. $instdir .'/waseParmsCustom.yml';
if (file_exists($confFile)) {
    $lines = file($confFile);
} else
    die('Config file "' . htmlspecialchars($confFile) . '" for "'. htmlspecialchars($institution) . '" not found.');


// Check for a submit
if ($_REQUEST['submit'] == 'Exit')
    die('Bye');


// Parse the parms file into an arry of keys, values and comments.
$comments = '';
$parms = array();  // Parsed lines
$out = array();    // Raw lines
foreach ($lines as $line) {
    $rawline = $line;
    // Skip blank lines
    if (!trim($line)) {
        $out[] = $rawline;
        continue;
    }
    // If comment, save unless part of a block comment.
    if (substr($line,0,1) == '#') {
        $out[] = $rawline;
        if (trim(substr($line,1)) == "") {
            if ($comments) {
                $comments = '';
            }
            continue;
        }
        else {
            // Skip comment lines that do not have a blank after the #
            if (substr($line, 1, 1) != ' ')
                continue;
            if ($comments) 
                $comments .= ' ';
            $comments .= trim(substr($line,1));
            continue;
        }
    }
    // This is a parameter, not a comment.  Parse out the key and value.
    list($key,$value) = explode(':',$line,2);
    $key = trim($key);  $value = trim($value);
    if (substr($value,0,1) == '"' || substr($value,0,1) == "'") {
        $quote = substr($value,0,1);
        $value = substr($value,1,strlen($value) - 2);
    }
    else 
        $quote = '';
    // Save the parm
    $parms[$key]['quote'] = $quote;
    $parms[$key]['comments'] = $comments;
    
    // If modified, save changed value
    if ($_REQUEST['submit'] == 'Change' && key_exists($key,$_REQUEST))  {
        $out[] = $key . ':' . ' ' . $quote . $_REQUEST[$key] . $quote . PHP_EOL;
        $value = $_REQUEST[$key];
    }
    else 
        $out[] = $rawline; 
    $parms[$key]['value'] = $value;
    $comments = '';
}

// Make requested changes
if ($_REQUEST['submit'] == 'Change') {
    // First step ... try to save current parms file as backup
    $i = 1;
    while(file_exists(__DIR__ . '/../../config' . $instdir . '/waseParmsCustom.' . date('Ymd') . '.' . $i . '.yml')) {
        $i++;
    }
    $oldfile = __DIR__ . '/../../config' . $instdir . '/waseParmsCustom.' . date('Ymd') . '.' . $i . '.yml';
    if (!copy($confFile, $oldfile))
        die('Could not copy ' . $confFile . ' into ' . $oldfile);
    // Write out the new lines to the new file
    if (!file_put_contents($confFile, $out))
        die('Could not write updated data to file ' . $confFile);
    // Let the user know
    $infmsg = 'File updated';
}


// Set up the html form
?>
<!DOCTYPE html>
<html>
<head>
<?php $title = 'WASE Parameters'; if ($institution = $_SERVER['INSTITUTION']) $title .= ' for "' . $institution . '"';?>
<title><?php echo $title;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">
body {
	background-color: #E6DDD0;
	font-family: Arial, Helvetica, sans-serif;
}

td {
	background-color: #CCCCCC;
}

#divHeaderLogo {
	height: 110px;
	background-position: top left;
 	background-image: url(../views/images/waselogo.gif);
	background-repeat: no-repeat;
	repeat: no-repeat;
	margin: 0;
}

.submit {
	width:100px; 
	height:100px;
	font-size:24px;
}
</style>
</head>
<body>
<div id="divHeaderLogo"></div> 
<h1 id="title"><?php echo $title;?></h1>
<p>
Use this script to examine and possibly modify the localization parameters for your institution.  Not all parameters can be set with this script 
(e.g., parameters that, if changed, would compromise the operation of your site).  
If you need to change parameters not listed below,
contact the WASE support staff.
</p>
<p>
To change any of the parameters below, edit the "value" box (you can change one or more parameters at a time) then click any of the "Change" buttons.  Click "Exit" when done.
<p>
<?php if ($infmsg) echo '<h2>'.$infmsg.'</h2>'; if ($errmsg) echo '<h2>'.$errmsg.'</h2>'; ?></p>
<hr />
<p></p>
<form action=parms.php method="post" name="Parms" id="Parms">
<table border=1 >
<tr>
<th align="left" width="50">Key</th>
<th align="left" width="160">Value</th>
<th align="left">Description</th>
</tr>

<?php 
$i = 0;
foreach ($parms as $key => $parm) {
    $i++;
    if ($i > 20) {?>
        </table>
        <hr>
        <input type="submit" class="submit" name="submit" id="submit" value="Change">
        <input type="submit" class="submit" name="submit" id="submit" value="Exit">
        <hr><br />
        <table border=1>
        <tr>
        <th align="left" width="50">Key</th>
        <th align="left" width="160">Value</th>
        <th align="left">Description</th>
        </tr><?php 
        $i = 0;      
    }
?>
    <tr>
    <th align="left"><?php echo $key;?>
    <td><input name="<?php echo $key;?>" type="text" size="100" maxlength="150" value="<?php echo WaseUtil::safeHTML($parm['value']);?>" /></td>
    <td><?php echo $parm['comments']?></td>
    </tr><?php    
}?>
</table>
<hr>
<input type="submit" class="submit" name="submit" id="submit" value="Change">
<input type="submit" class="submit" name="submit" id="submit" value="Exit">
</form>
</body>
</html>