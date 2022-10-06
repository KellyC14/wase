<?php


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
$ldap_path = "/active-directory/1.0.0/users/full?";

// Our WSO2 key/secret
$key = "OGzIbo8GUBtS4UZqe8q2sFHxJtca";
$secret = "4jOSchf10d_37vbRvo_pqWdoreca";

echo "Using $host";

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
$search = $_REQUEST['search'] ?? $argv[1] ?? "uid=serge";

echo "Searching for: " . $search . $nl;

// Restart curl to get the user info
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $host . $ldap_path . $search);
echo "URL is: " . $host . $ldap_path . urlencode($search) . $nl;
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
    echo $nl;
    print_r($entry);
} else
    echo "$nl WSO2 lookup http code for $search = $http_code";

echo $nl;

// Timing info
echo "$nl Getting access token took: " . ($aftera - $beforea) . " seconds $nl";
echo "$nl Getting user data took: " . ($afteru - $beforeu) . " seconds $nl";

// All done
echo $end;
?>