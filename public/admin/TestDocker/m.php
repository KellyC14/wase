<?php


include("autoload.include.php");

//    echo "direct not sent: " . $mail->ErrorInfo . "<br />";

// Try WASE
if (WaseUtil::Mailer("serge@princeton.edu", "wase mail", "this is mail"))
    echo "WASE mail sent";
else
    echo "WASE mail not sent";


?>