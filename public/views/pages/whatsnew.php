<?php  
include "_copyright.php";

$pagenav = 'whatsnew'; /*for setting the "selected" page navigation */
$notdoauth = true;
include "_header.php";
include "_includes.php";
?>

<script type="text/javascript" language="javascript" src="whatsnewview.js?v=<?php echo $versionString; ?>"></script>
<script type="text/javascript">
   	$(document).ready(function() {
      	doOnReady();
		whatsnewview.setPageVars();
	}); 
</script>                                                               
</head>

<body onUnload="">

<div data-role="page" id="whatsnew">

	
	<?php 
        if ($_SESSION['authenticated'])
            include('_mainmenu.php'); 
        else 
            echo '<div data-role="header" id="divHeader" class="nonav" data-backbtn="false"><div id="divHeaderLogo"></div><div class="borderdiv_top"></div>
        <div class="borderdiv_bottom"></div></div><div class="btnUnAuthBack"><a href="login.page.php" data-ajax="false">< Back to Login</a></div>';
    ?>

	<div class="ui-content" role="main">	
		<h1 id="pagetitle" class="clear">Title</h1> 

        	<div id="divWhatsNewArea">
                <h2>Release 3.0.6</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Use @member userid for name of appointment slot owner</h4>
                <h4>Fix display slots for contiguous blocks that meet at midnight</h4>
                <h2>Release 3.0.5</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Update to Change Google Calendar scope form calendar to calendar.events in the entire code base</h4>
                <h2>Release 3.0.4</h2>
                <h3>Change Google Calendar scope form calendar to calendar.events. Add Intro language to Login Page</h3>
                <h3>Bug Fixes</h3>
                <h4>Fix Responsive Display for (Help, Privacy, What's New) section</h4>
                <h2>Release 3.0.3</h2>
                <h3>Add Policy Notice to Login Page</h3>
                <h3>Bug Fixes</h3>
                <h4>None</h4>
                <h2>Release 3.0.2</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>User can now lock slot on overlapping slots on multiple calendars which allow block overlaps</h4>
                <h2>Release 3.0.1</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Docker fixes for new Block (recurring) and new Appt and loogut</h4>
                <h2>Release 3.0.0</h2>
                <h3>Docker compatible</h3>
                <h3>Bug Fixes</h3>
                <h4>None</h4>
                <h2>Release 2.8.9</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Now can do any title searches for calendar searches</h4>
                <h2>Release 2.8.8</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Exclude title searches if search string matches "calendar for" for calendar searches</h4>
                <h2>Release 2.8.7</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Fix simplesamlphp security vulnerability</h4>
                <h2>Release 2.8.6</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Fix direct make appointment access</h4>
                <h2>Release 2.8.5</h2>
                <h3>None</h3>
                <h3>Bug Fixes</h3>
                <h4>Fix direct calendar and block access</h4>
                <h2>Release 2.8.4</h2>
                <h3>New Features</h3>
                <h4>Support wintersession</h4>
                <h3>Bug Fixes</h3>
                <h4>Fix direct calendar and block access</h4>
                <h2>Release 2.8.3</h2>
                <h3>New Features</h3>
                <h4>WASE logout preserves your single-signin credentials.</h4>
                <h3>Bug Fixes</h3>
                <h4>Instructor Calendars feature now displays all instructor calendars</h4>
                <h2>Release 2.8.0</h2>
                <h3>New Features</h3>
                <h4>switch to Blackboard REST API</h4>
                <h3>Bug Fixes</h3>
                <h4>None</h4>
                <h2>Release 2.8.1</h2>
                <h3>New Features</h3>
                <h4>Blackboard REST API Class file Updated</h4>
                <h2>Release 2.8.0</h2>
                <h3>New Features</h3>
                <h4>switch to Blackboard REST API</h4>
                <h3>Bug Fixes</h3>
                <h4>None</h4>
                <h2>Release 2.7.4</h2>
                <h3>New Features</h3>
                <h4>None</h4>
                <h3>Bug Fixes</h3>
                <h4>reply-to now set to the correct email in outgoing appointment emails</h4>
                <h2>Release 2.7.3</h2>
                <h3>New Features</h3>
                <h4>New Synchronization documentation</h4>
                <h3>Bug Fixes</h3>
                <h4>missing iCal attachment in outgoing email for iCal synchronization</h4>
                <h2>Release 2.7.2</h2>
                <h3>New Features</h3>
                <h4>None</h4>
                <h3>Bug Fixes</h3>
                <h4>missing iCal attachment in outgoing email after failed synchronization</h4>
                <h2>Release 2.7.1</h2>
                <h3>New Features</h3>
                <h4>Updated Instructions for Exchange synchronization in Preferences</h4>
                <h3>Bug Fixes</h3>
                <h4>Now able to remove GAP accounts as managers and members of a calendar</h4>
                <h4>Display correct Available count in large Calendar view </h4>
                <h2>Release 2.6.0</h2>
                <h3>New Features</h3>
                <h4>A field has been added to the new appointment form for appointment makers to specify venue or other details.</h4>
                <h4>The specification of deadlines on Blocks has been simplified.</h4>
                <h3>Bug Fixes</h3>
                <h4>Active Directory lookups fixed.</h4>
                <h4>The Export to CSV function in My Appointments has been corrected.</h4>
                <h2>Release 2.5.0</h2>
                <h3>New Features</h3>
                <h4>If you create a slotted block and specify that individuals can make more than one
                    appointment in the block (e.g., take up more than one slot), then the individual can make
                    a single appointment that spans the slots, rather than having to make multiple, seperate
                    appointments.</h4>
                <h4>The form for creating and editing blocks has been streamlined and simplified.</h4>
                <h3>Bug Fixes</h3>
                <h4>If you change the message sent out with appointment reminders and notifications on your calendar,
                    you will be prompted about propagating the change to existing blocks.</h4>
                <h4>Waitlist is now defaulted to 'off' for new calendars.</h4>
                <h2>Release 2.4.3</h2>
        		<h3>New Features</h3>
                <h4>When WASE prompts for a netid (to add managers/members, or to restrict appointment making by netid),
                    it will check to see if the user entered the first part
                    of an email address, and, if so, ask the user if they meant to enter the corresponding netid.</h4>
                <h4>The Apply to Manage and Apply to be a Member buttons on the Calendars page have been moved into a
                    more
                    prominent position.</h4>
			</div>


	</div><!-- /content -->

	<?php include('_footer.php'); ?>

</div><!-- /page -->

</body>
</html>
