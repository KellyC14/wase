<?php
/**
 *
 * This script allows an administrator to convert a listserv list (from a REVIEW) into a Timeline group.
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @authot Kevin Perry, perry@princeton.edu
 */

 
// LDAP parameters
define("LDAP","ldap.princeton.edu");  // LDAP server
define("LDAPUID","uid");              // The "userid" attribute name
define("LDAPGUID","universityid");    // The user unique id attribute name
define("LDAPBDN","o=Princeton University,c=US"); // The base name
// define("LDAPEMAIL","mail"); 
define("LDAPEMAIL","puaka"); // The email attribute name

// Leave these blank to prompt the user for values
define("LDAPUSER","wase");  // Login userid (for authenticated binds)
define("LDAPPASS","");      // Login password (for authenticated binds)

/* Start session support */
session_start();

/* Init error/inform messages */
$errmsg = '';  $infmsg = ''; $json = '';


/* If we are invoking ourselves to process the form. */
if (isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
        // Figure out the LDAP user
        $user = empty($_REQUEST['user']) ? LDAPUSER : trim($_REQUEST['user']);
    
        // Figure out the LDAP passeord, if any
        $pass = empty($_REQUEST['pass']) ? LDAPPASS : trim($_REQUEST['pass']);
        
        // Generate group name
        $name = trim($_REQUEST['name']);
        
        // Generate group id
        $id = trim($_REQUEST['id']);
        
        // Generate the list of owners
        $owners = empty($_REQUEST['owners']) ? array() : explode(',',$_REQUEST['owners']);
        
        // Generate the list of moderators
        $moderators = empty($_REQUEST['moderators']) ? array() : explode(',',$_REQUEST['moderators']);
        
        // Generate list of posters
        $posters = empty($_REQUEST['posters']) ? array() : explode(',',$_REQUEST['posters']);
        
        // Generate list of moderated posters
        $modposters = empty($_REQUEST['modposters']) ? array() : explode(',',$_REQUEST['modposters']);
        
    
		// Generate the list of members
		$members = array();
        $lmembers = preg_split("/[\s,]+/", $_REQUEST['members']);
		foreach ($lmembers as $lmember) {
		  $lmember = trim($lmember);
		  if (strpos($lmember,'@'))
		      $members[] = getUid(LDAPEMAIL, $lmember, $user, $pass);
		}
		
		// Now generate the json group header
		$groupid = $name . '-' . $id;
		$data = array(
			'group' => array(
				'guid' => $groupid,
				'name' => $name,
				'memberType' => 'closed',
			),
			'membership' => array(),
			'privilege' => array(),
		);
		                 
		// Add the members
		foreach($members as $member) {
		    list($uid, $guid, $email) = $member;
		    $data['membership'][] = array(
		    	'guid' => $groupid . '-member-' . $guid,
		        'groupGuid' => $groupid,
		        'personBatchkey' => 'ldap-people',
		        'personGuid' => $guid,
		    );
		}

		// Now add the posters
		foreach($posters as $poster) {
		    list($uid, $guid, $email) = getUid(LDAPUID, $poster, $user, $pass);
		    $data['privilege'][] = array(
		    	'guid' => $groupid . '-poster-' . $guid,
		        'groupGuid' => $groupid,
		        'personBatchkey' => 'ldap-people',
		        'personGuid' => $guid,
		        'permission' => 'post to group',
		    );
		} 

		// Now add the moderated posters
		foreach($modposters as $poster) {
		    list($uid, $guid, $email) = getUid(LDAPUID, $poster, $user, $pass);
		    $data['privilege'][] = array(
		        'guid' => $groupid . '-poster-' . $guid,
		        'groupGuid' => $groupid,
		        'personBatchkey' => 'ldap-people',
		        'personGuid' => $guid,
		        'permission' => 'post to group moderator',
		    );
		}
		
		// Now add the owners
		foreach($owners as $owner) {
		    list($uid, $guid, $email) = getUid(LDAPUID, $owner, $user, $pass);
		    $data['privilege'][] = array(
		    	'guid' => $groupid . '-owner-' . $guid,
		        'groupGuid' => $groupid,
		        'personBatchkey' => 'ldap-people',
		        'personGuid' => $guid,
		        'permission' => 'own group',
		    );
		}
		
		// Now add the moderators
		foreach($moderators as $moderator) {
		    list($uid, $guid, $email) = getUid(LDAPUID, $moderator, $user, $pass);
		    $data['privilege'][] = array(
		        'guid' => $groupid . '-moderator-' . $guid,
		        'groupGuid' => $groupid,
		        'personBatchkey' => 'ldap-people',
		        'personGuid' => $guid,
		        'permission' => 'moderate group posts',
		    );
		}
		
		
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$json = json_encode($data, JSON_PRETTY_PRINT);
		} else {
			$json = json_encode($data);
		}
		 
		
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Exit') {
	echo 'Bye.';
	exit;
}
?>
<html>
<head>
<title>Convert Listserv list into Timeline group</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
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
<h1 align="center">Convert Listserv list into Timeline group</h1>
<?php if ($infmsg) echo '<h3>'.$infmsg.'</h3>'?>
 
  
<form action="list2time.php" method="POST" name="List2Time" id="List2Time">

  <?php if (!LDAPPASS) {?>
  <p><?php echo 'Netid for binding to LDAP: (leave blank for unauthenticated binds)'?>
  <input name="user" type="text" id="user" size="50" value="<?php  echo LDAPUSER;?>"></p>

  <p><?php echo 'Password for binding to LDAP: (leave blank for unauthenticated binds)'?>
  <input name="pass" type="text" id="pass" size="50"></p>
  <?php }?>

  <hr>
    

  <p>Group Name:
    <input name="name" type="text" id="name" size="100">
  </p>
   <p>Group Unique ID: (field will be added to group name to create a unique ID):
    <input name="id" type="text" id="name" size="100">
  </p>
  <p>Group Owners: Enter a list of userids seperated by commas.
    <input name="owners" type="text" id="owners" size="200">
  </p>
  <p>Group Moderators: Enter a list of userids seperated by commas (leave blank if group not moderated).
    <input name="moderators" type="text" id="moderators" size="200">
  </p>
   <p>Group Posters: Enter a list of userids seperated by commas.  These people can post directly.
    <input name="posters" type="text" id="posters" size="200">
  </p>
   <p>Group Moderated Posters: Enter a list of userids seperated by commas.  These people can post to the moderator.
    <input name="modposters" type="text" id="modposters" size="200">
  </p>
  <div>
      <div>
      Group Members: Enter list of members in Listserv REVIEW format (email space name)
      <p>
        <textarea name="members" cols="150" rows="20"></textarea>
      </p>
      </div>
 </div>
  
<DIV align="left"> 
  
    <p>
      <input type="submit" class="submit" name="submit" value="Submit">
      &nbsp;&nbsp;&nbsp;
        <input type=submit name="submit" value="Exit">
    </p>
  
</DIV>
<div>
      JSON: This will be filled in when you click on Submit
      <p>
        <textarea name="json" cols="150" rows="20"><?php echo $json;?></textarea>
      </p>
      </div>
</form>
</body>
</html> 
<?php
function getUid($attr, $value, $user, $pass) {
     
    // Assume not found
    $uid = ''; $guid = ''; $email='';

    // Lookup attribute value in ldap
    $ds = @ldap_connect(LDAP);
    if ($ds) {
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        if ($user && $pass) {
            $bn = @ldap_bind($ds, LDAPUID.'='.$user.','.LDAPBDN, $pass);
        } else {
            $bn = @ldap_bind($ds);
        }
        $search = "$attr=$value";
        $r = @ldap_search($ds, LDAPBDN, $search, array(
            LDAPUID, LDAPGUID, LDAPEMAIL
        ), 0, 0, 10);
        if ($r) {
            $result = @ldap_get_entries($ds, $r);          
            $uid = $result[0][LDAPUID][0];
            $guid = $result[0][LDAPGUID][0];
            $email = $result[0][LDAPEMAIL][0];
        }
        @ldap_close($ds);
    }
    // Return a set of attributes as an array
    return array($uid, $guid, $email);
        
}?>