<?php

?>
<html><head></head><body>
<?php 

// Read in XML, if any

if ($xml = trim($_REQUEST['xml'])) { 
    echo 'XML is:<br /><br />';
    // Replace < and > and & with literals
    echo htmlentities($xml) . '<br /><br />';    
    echo 'JSON is:<br /><br />';
    // Add xml header
    $xml = '<xml>'.$xml.'</xml>';
    $obj = simplexml_load_string($xml);
    if ($obj) {
        $json = json_encode($obj);
        // Convert empty arguments to empty strings
        $json = str_ireplace(':{}', ':""', $json);
        echo $json . '<br /><br />OBJECT is:<br /><br />'; 
        print_r($obj);
        echo '<br /><br />Array is:<br /><br />';
        print_r(json_decode($json, true));
        echo '<br /><br />';
    }
    else 
        echo 'Invalid xml<br /><br />';
}
?>
<form method="post" action="xml2json.php">
Enter your XML:<br />
<textarea name="xml" cols="60" rows="10"></textarea><br />
<input type="submit" name="submit" value="submit" />
</form>
</body>
</html> 