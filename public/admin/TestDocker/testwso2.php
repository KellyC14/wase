<?php


// Determine how log it takes to get back user attributes from WSO2


// Determine how log it takes to get back user attributes from WSO2

// Write out html header
if ($argv[0]) {
    $nl = "\n";
    $header = $nl;
    $end = $nl;
} else {
    $nl = "<br /><br />";
    $header = "<html><head></head><body>";
    $end = "</body></html>";
}

echo $header;


// Our WSO2 host and token path
$host = "https://api.princeton.edu";
$token_path = "/token";
$search_path = "/active-directory-match/1.0.2/match?search=";

echo "Host is $host, Search = " . $search_path . $nl;

// Our WSO2 key/secret
$key = "OGzIbo8GUBtS4UZqe8q2sFHxJtca";
$secret = "4jOSchf10d_37vbRvo_pqWdoreca";

// Generate base64-encoded version of key:secret.
$encoded = base64_encode("$key:$secret");

// Set up curl to retrieve the access token.
$curl = curl_init($host . $token_path);

// Set required curl options
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Authorization: Basic $encoded"
));
curl_setopt($curl, CURLOPT_HTTPGET, 0);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

// Start timing
$beforea = microtime(true);
// Now get the token and the return code.
$page = curl_exec($curl);
$aftera = microtime(true);

// Convert json to an object
$token = json_decode($page);
// Get the http return code
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// If bad return code, log the error
if ($http_code != 200)
    die("WSO2 error $http_code getting access token</body></html>");

// Extract the token
$token = $token->access_token;


// Let's look up info on serge
$user = "serge";
// Unless we were passed a userid
if ($gaveuser = $_REQUEST['user'])
    $user = $gaveuser;
elseif ($gaveuser = $argv[1])
    $user = $gaveuser;


// Restart curl to get the user info
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $host . $search_path . urlencode($user));
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "accept: application/json",
    "Authorization: Bearer " . $token
));
curl_setopt($curl, CURLOPT_POST, 0);
curl_setopt($curl, CURLOPT_HTTPGET, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

// Start timing
$beforeu = microtime(true);
$page = curl_exec($curl);
$afteru = microtime(true);

// Extract return code
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// If we did not get back the user info, die.
if ($http_code == 200) {
    $data = json_decode($page, true);
    $result = $data["result"];
    $entry = $result["entry"];
    echo "<br />";
    print_r($entry);
} else
    echo "<br />WSO2 lookup http code for $user = $http_code";


// Timing info
echo "<br /><br />Getting access token took: " . ($aftera - $beforea) . " seconds<br />";
echo "<br />Getting user data took: " . ($afteru - $beforeu) . " seconds<br />";

// All done
echo "</body></html>"
?>