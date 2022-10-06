<?php 
// Set often used values
$app = WaseUtil::getParm("APPOINTMENT"); 
$apps = WaseUtil::getParm("APPOINTMENTS"); 
$oh = WaseUtil::getParm("NAME"); 
$ohs = WaseUtil::getParm("NAMES");
$sysname = WaseUtil::getParm("SYSNAME");
$sysid = WaseUtil::getParm("SYSID");
$netid = WaseUtil::getParm("NETID");
$contactemail = WaseUtil::getParm("CONTACTEMAIL");
$contactphone = WaseUtil::getParm("CONTACTPHONE");


?>
<div id="divHelp" class="popupinner" role="main">	
<form id="frmHelp" method="post" action="#" data-ajax="false">
    <div class="helpInstructions">Please note that Help has been opened in a new window or tab. All instructions below
        refer to the main system tab. To get started, select a help topic on the left. For further assistance,
        please contact <?php echo $contactemail; ?> or call <?php echo $contactphone ?>.
    </div>

<!-- must put the helptopic value as a parameter to showTopic (which matches the div name below) -->
<div class="helparea">
<div class="leftHelp">
	<div class="helplist">
		<ul class="helplist">
		    <li style="padding-top: 7px; padding-bottom: 7px;">What do you want to do?</li>
		        <ul class="subhelplist">
					<li><a href="#" onClick="showTopic('quickmake');return false;">Make an <?php echo $app;?></a></li>
					<li><a href="#" onClick="showTopic('quickedit');return false;"> Cancel, edit or view my <?php echo $apps;?></a></li>
					<li><a href="#" onClick="showTopic('quickavail');return false;">Make myself available for <?php echo $apps;?></a></li>
					<li><a href="#" onClick="showTopic('quickchange');return false;"> View or change my availability for <?php echo $apps;?></a></li>
                    <li><a href="#" onClick="showTopic('quicksync');return false;"> Synchronize <?php echo $apps;?></a></li>
					</ul>               
			<li><a href="#" onClick="showTopic('start');return false;">Introduction</a></li>
			<li><a href="#" onClick="showTopic('dologin');return false;">Log in</a></li>
            <li><a href="#" onClick="showTopic('showmyappts');return false;">View My <?php echo ucfirst($apps);?></a></li>
            <li style="padding-top: 7px; padding-bottom: 7px;">Make an <?php echo ucfirst($app);?>
            	<ul class="subhelplist">
                    <li><a href="#" onClick="showTopic('makeappoint');return false;">Search for Available Time</a></li>
                    <li><a href="#" onClick="showTopic('signup');return false;">Sign Up for <?php echo ucfirst($app);?></a></li>
                </ul>
            </li>   
            <li style="padding-top: 7px; padding-bottom: 7px;">Set up Calendars 
            	<ul class="subhelplist">
            		<li><a href="#" onClick="showTopic('calendarinfo');return false;">Calendars Page</a></li>
            		<li><a href="#" onClick="showTopic('createcalendar');return false;">Create New Calendar</a></li>
            		<li><a href="#" onClick="showTopic('calendarsettings');return false;">Edit Calendar Settings</a></li>
                    <?php if (WaseUtil::getParm('WAITLIST')) {?><li><a href="#" onClick="showTopic('waitlist');return false;">Wait List</a></li><?php } ?>
                    <li><a href="#" onClick="showTopic('subscribe');return false;">Subscribe to a Calendar</a></li>
                    <li><a href="#" onClick="showTopic('advertise');return false;">Advertise a Calendar</a></li>
                    <li><a href="#" onClick="showTopic('subscriberss');return false;">Subscribe to a Calendar as RSS feed</a></li>
            	</ul>               
		    </li>
            <li style="padding-top: 7px; padding-bottom: 7px;">Blocks and <?php echo ucfirst($apps);?>
            	<ul class="subhelplist">
					<li><a href="#" onClick="showTopic('doviewcal');return false;">View Calendar</a></li>
					<li><a href="#" onClick="showTopic('addblock');return false;">Add a Block</a></li>
					<li><a href="#" onClick="showTopic('editblock');return false;">Edit a Block</a></li>
					<li><a href="#" onClick="showTopic('advertiseblock');return false;">Advertise a Block</a></li>
					<li><a href="#" onClick="showTopic('addappt');return false;">Add an <?php echo ucfirst($app);?></a></li>
					<li><a href="#" onClick="showTopic('editappt');return false;">Edit an <?php echo ucfirst($app);?></a></li>
                </ul>
			</li>
			<li style="padding-top: 7px; padding-bottom: 7px;">Preferences
            	<ul class="subhelplist">
                <li><a href="#" onClick="showTopic('prefsintro');return false;">Introduction</a></li>              
                <li><a href="#" onClick="showTopic('prefsglobal');return false;">Global Preferences</a></li>
                <li><a href="#" onClick="showTopic('prefsdefault');return false;">Default Preferences</a></li>
                <?php if (WaseUtil::getParm('WAITLIST')) {?><li><a href="#" onClick="showTopic('prefswaitlist');return false;">WaitList Preferences</a></li><?php }?>
                </ul>
            </li>
   		</ul>
	</div>
</div>


<!-- div id is helptopic value, div name must be "divHelp" -->
<div class="helpsection" id="quickmake">
	<h2>Make an <?php echo $app;?></h2>
    <ul>
	<li>Click on the MAKE <?php echo strtoupper($app); ?> tab.</li> 
	<li>Enter a userid, name or calendar title<?php if (WaseUtil::getParm('COURSECALS')) echo ", OR click on <i>find my instructor calendars</i>."; else echo ".";?></li> 
    <li>Select a calendar by clicking on its associated arrow.</li>
    <li>Days with available appointments (if any) will be listed.</li>
    <ul>
    <li>Click the down arrow on an available day to see available appointment times.</li>
    <li>If any times are available, click the + sign to make the <?php echo $app;?>.</li> 
    </ul>
        <li>The appointment may be immediately made (if not, the Create Appointment form will be displayed).</li>
	<li>If the Create Appointment form is displayed, fill in and save the <?php echo $app;?> form.</li>
	<li>If there are no days with available appointments, some calendars will allow you to sign up for the calendar wait list.</li>
	</ul>
</div>
<div class="helpsection" id="quickedit">	 
	<h2>Cancel, edit or view my <?php echo $apps;?></h2>
    <ul>
	<li>Click on the MY <?php echo strtoupper($apps); ?> tab.</li> 
	<li>Click on an <?php echo $app;?> (any field)  to view/edit/delete.</li>
    <li>Make changes (if any).</li>
        <li>Click Save or Delete, or Cancel (if you change your mind).</li>
	</ul>
</div>
<div class="helpsection" id="quickavail">
	<h2>Make myself available for <?php echo $apps;?></h2>
	<p>People will not be able to make <?php echo $apps;?> with you until you have added one or more BLOCKS to your calendar.  These represent
	blocks of time when you are available for <?php echo $apps;?>
    <ul>
	<li>Click on the CALENDARS tab.</li>  
	<li>If you don't have a calendar, click the +New Calendar button to make one.
	<ul>
	   <li>Fill in the New Calendar form.</li>
	   <li>Click Save to save the new calendar.</li>
	   <li>This will display the View Calendar page.</li>
	</ul>
	<li>If you do have a calendar, click on its title to select it.  This will display the View Calendar page.</li>  
	<li>Click on the +Add Block button to add blocks of available time to the calendar.</li> 
	<ul>
		<li>Fill in the Create Block form, and click Save.</li>
        <li>You should see the block(s) on your calendar</li>
	</ul>
	<li>You can add aditional blocks, or click on the CALENDARS tab to go back to the list of your calendars.</li>
	</ul>
</div>
<div class="helpsection" id="quickchange">	 
	<h2>View or change my availability for <?php echo $apps;?></h2>
    <ul>
	<li>Click on the CALENDARS tab.</li> 
	<li>Select a calendar (click on it's title). This will display the View Calendar page.</li>
	<li>If you don't have a calendar, click the +New Calendar button to make one.
	<ul>
	   <li>Fill in the New Calendar form.</li>
	   <li>Click Save to save the new calendar.</li>
	   <li>This will display the View Calendar page.</li>
	</ul>
	<li>A month is displayed in the upper left ... use this to navigate though your calendar.</li>
	<ul>
	<li>As you navigate, you can select weeks or days to display your availability blocks.</li>
	<ul>
	<li>To display all blocks for a month, click on the month name.</li>
	<li>To display all blocks for a week, click on the week number in the WK column.</li>
	<li>To display blocks for a day, click on the date.</li>
	</ul>
	<li>You can delete, lock/unlock, sync or edit any block by clicking the corresponding icon in the displayed block box.</li>
	</ul>
	<li>If you prefer to see your calendar in a larger format, click the upward-pointing grey arrow next to the month name.</li>
	<ul>
	<li>You can return to the small--calendar display mode by clicking the downward-pointing grey arrow next to the month name in the upper right.</li>
	</ul>
	<li>Click on +Add Block (upper right) to add a new block of available time.</li>
	<ul>
	<li>Fill in the Block form.
	<li>Click Save to save your changes, or Cancel (if you change your mind).</li>
	</ul>
	</ul>
</div>
    <div class="helpsection" id="quicksync">
        <h2>Synchronize <?php echo $apps;?></h2>
        <ul>
            <li>WASE can sync <?php echo $apps;?> and blocks into your calendar.  To have WASE do this:</li>
            <li>Click on the PREFERENCES tab in WASE.</li>
            <li>A PREFERENCES page will open, either in a separate browser tab or window.</li>
            <li>Click on the down-arrow in the "Local Calendar for Sync" box.</li>
            <ul>
                <li>Select <span style="font-weight: bold;">Google Calendar</span> to sync <?php echo $apps;?> and blocks into your Google calendar.</li>
                <li>Select <span style="font-weight: bold;">Exchange/Outlook</span> to sync <?php echo $apps;?> and blocks into your Exchange calendar.</li>
                <li>Select <span style="font-weight: bold;">iCal</span> to sync <?php echo $apps;?> and blocks into your local (iPhone/Android) calendar.</li>
                <li>Select <span style="font-weight: bold;">None</span> if you do not want your <?php echo $apps;?> and blocks synced into your local calendar.</li>
            </ul>
            <li>Further instructions will be displayed on how to complete the snchronization setup.</li
        </ul>
    </div>
<div class="helpsection" id="start">
	<h2>Introduction</h2>
	<p>The <?php echo $sysname;?> allows people to schedule <?php echo $apps;?> over the Web. People with whom <?php echo $apps;?> are made (e.g., people who hold <?php echo $app;?> hours) create a calendar in the system and list their available times on that calendar. People who want to make <?php echo $apps;?> can then access that calendar and make an <?php echo $app;?>.</p>
	<p>Anyone with a <?php echo $netid;?> can create a calendar.  Once created, calendar owners add blocks of time to that calendar 
	to indicate when they are available for <?php echo $apps;?>. These blocks can be divided into individual <?php echo $app;?> slots, or the whole block can be left as one slot and managed on a first-come, first-served basis. The calendar owner can restrict who is allowed to schedule <?php echo $apps;?> during a block of time, and set other access retrictions.</p>
    <p>People who want to make <?php echo $apps;?> with a calendar owner can look up their calendar in the system, find a block of time when the calendar owner, or calendar member (if the calendar has members -- see below), is available for <?php echo $apps;?>, and schedule an <?php echo $app;?>.  People do not need to create a calendar in the system to make <?php echo $apps;?>.  They
     only need to create a calendar if they want people to be able to make <?php echo $apps;?> with them (e.g., they hold <?php echo $ohs;?>).</p>
    <p>A calendar owner can designate one or more people to serve as "managers" for their calendar.  Managers 
    have the same rights as the owner, and they can add and remove blocks of available time for the owner or add and remove <?php echo $apps;?>.  
    Managers act on the calendar owner's behalf -- blocks of time that they add to a calendar apply to the calendar owner, not the manager.</p>
    <p>A calendar can also have one or more "members".  Members are people other than the calendar owner who can advertise their own availability for <?php echo $apps;?> on a calendar.  
    Members add blocks of time to a calendar just as the owner does -- however, the blocks apply to the member, not to the calendar owner.  
    A typical use for a calendar with members is a calendar for a group of people who offer a common service, such as tutoring.  
    Each member (tutor) would list their availability on the calendar, and students could then see all of the available times and make an <?php echo $app;?> with a specific member at a specific time.</p>
    <p>A calendar may also have one or more '@' members, members whose name starts with an @ sign.  These represent resources or equipment or rooms or other entities which are being scheduled though
    <?php echo $sysid;?>.  This makes it possible to use <?php echo $sysid;?> as a general scheduling facility.</p>
    <p>A calendar owner can create more than one calendar.  Each calendar has its own unique URL, and each can be separately advertised to potential <?php echo $app;?> makers.  
    For example, a professor might have one calendar on which she lists <?php echo $ohs;?> for her undergraduate classes, and another on which she lists <?php echo $oh;?>  
    for her graduate classes.  Or someone might want to create a calendar for an organization that they manage, or for a piece of equipment that they run, or for any other entity that is available for limited periods of time</p>
	<p>The system uses e-mail and text msgs to notify and remind people of pending <?php echo $apps;?>.  
	<?php echo ucfirst($apps);?> can be cancelled with or without e-mail notification.  The calendar owner can check the calendar at any time to see if 
	they have pending <?php echo $apps;?>, and users who have made <?php echo $apps;?> can see a list of their pending <?php echo $apps;?>.</p>
    <p>It is also possible to synchronize <?php echo $apps;?> in the system with a local calendaring application, such as Outlook or iCal or Google calendar, using the PREFERENCES system.</p>
</div>
<div class="helpsection" id="dologin">
	<h2>Log in</h2>
	<p>To use the <?php echo $sysid;?> you must first identify yourself, either by 
    <?php if (WaseUtil::getParm('AUTHTYPE') == WaseUtil::getParm('AUTHCAS')) {?>
    clicking the Log in button and entering a <?php echo $netid;?> and <?php echo WaseUtil::getParm('PASSNAME');?>,
    <?php } else {?>
    entering a <?php echo $netid;?> and <?php echo WaseUtil::getParm('PASSNAME');?> and clicking the Log in button,
    <?php }?>
     or, if you are a guest (not a member of the <?php echo WaseUtil::getParm('INSNAME');?> community), by entering your e-mail address and clicking the Guest Log in button.</p>
	<p>Once you have logged in, you will be able to make <?php echo $apps;?> with people who have created calendars in the system, 
	or you can create and manage your own calendars and enable people to make <?php echo $apps;?> with you (e.g., you hold office or advising hours).</p>
	<p>You do not need to create a calendar to make <?php echo $apps;?> with other people; you only need to create a calendar if you want other people to be able to make <?php echo $apps;?> with you.</p>  
	<p>Note that to create and/or manage a calendar, you must login with a  <?php echo $netid;?>. 
	Guests cannot create or manage calendars, but they are able to make <?php echo $apps;?> on calendars set to permit guest access.</p>
</div>


<div class="helpsection" id="makeappoint">
	<h2>Search for Available Time</h2>
	<p>To make an <?php echo $app;?>, you first need to locate the calendar of the person with whom you wish to make the <?php echo $app;?>.  To do that, 
	click the "Make <?php echo ucfirst($app); ?>" tab, then enter the person's name or <?php echo $netid;?> or calendar title in the text box.  
	Matching calendars will appear as you type (or click "Go" when you are done typing). 
    <?php if (WaseUtil::getParm('COURSECALS')) {?> <p>You can also click "find my course instructor calendars" to see a list of any calendars that exist for instructors 
    in your courses.</p> <?php }?>  
    <p>If the search is successful, a list of all matching calendars will be displayed.  
    To see if there are any available <?php echo $app;?> times for a calendar, click on the arrow next to the calendar title.   
    This will display a list of <?php echo $app;?> "blocks", times when the owner 
    is available for <?php echo $apps;?>.  Click the > sign associated with a block 
    to see the available <?php echo $app;?> slots, then click the + associated with an  <?php echo $app;?> slot to make the <?php echo $app;?>.
</p>
</div>
<div class="helpsection" id="signup">
	<h2>Sign Up for <?php echo ucfirst($app);?></h2>
	<p>Once you have selected an <?php echo $app;?> time, you are ready to make the <?php echo $app;?> .
	For many calendars, the <?php echo $app;?> will be made as soon as you click the + icon (and the display will change to show the <?php echo $app;?>).
	This "fast path" <?php echo $app;?> making uses default directory values for your name.  You can inspect and/or change any of these values
	by clicking on the pencil icon (edit) asspciated with the <?php echo $app;?>.  This will open the Edit Appointment form.  
	The <?php echo $app;?> time will be displayed, but you can change it to another available time via the selection down-arrow.  
	Many of the fields on the form have been pre-filled with information from your directory entry, but you can change these if you need to.  
	You can specify the purpose of the <?php echo $app;?> (this may be required), whether or not you want to get a reminder, and enter a text msg address 
	to get a text message notification and reminder (You can use the Preferences tab to set defaults 
	for your text msg address).</p>
	<p>Once you have filled in the information, click Save to make (or edit) the <?php echo $app;?>, or Cancel if you change your mind.
	</p>    
</div>
<div class="helpsection" id="showmyappts">
	<h2>View My <?php echo ucfirst($apps);?></h2>
	<p>Clicking the MyAppointments tab takes you to the My <?php echo ucfirst($apps); ?> page, where you can see and search for any <?php echo $apps;?> 
	you have made (or that someone has made on your behalf), or any <?php echo $apps;?> that people have made with you.</p>  
	<p>The page displays a set of filtering options which allow you to restrict the search by various criteria (click the down arrow next to "Filtering Options" if not displayed).  When the page is initially displayed, the options are filled in on the assumption that you want to search for all of your <?php echo $apps;?> from today on. If you 
	want to limit the search to specific date/time ranges, or to specific calendars (e.g., based on the person with whom you have made the <?php echo $app;?>), fill in or select the appropriate field on the form. If you own or manage a calendar, and want to see <?php echo $apps;?> that have been made with you by a specific person, enter their <?php echo $netid;?> (or name) in the "Made By" field on the form.</p>
	<p>The displayed list of <?php echo $apps;?> will provide details about the <?php echo $app;?>(s), and also allow you to cancel the <?php echo $app;?>(s), if you wish.  You can also print the displayed list, or export it into a CSV (comma-separated) file.  If no <?php echo $apps;?> are found, a message to that effect will be displayed.</p>
    <p>To view or edit the details of an <?php echo $app;?>, click on the <?php echo $app;?> (or the right-arrow button for the <?php echo $app;?>).  This is the Edit <?php echo ucfirst($app);?> page.  You can make changes to the <?php echo $app;?> (by clicking Save after changing the information), cancel the <?php echo $app;?> (by clicking the Cancel <?php echo ucfirst($app);?>, or x button at the top right), or click Cancel to exit out of the screen without making any changes.</p>
    <?php if (WaseUtil::getParm('WAITLIST')) {?> <p>The My <?php echo ucfirst($apps);?> page also shows any wait list entries you have made.  A calendar owner can enable a notification list for their calendar.  Pople who want to make an <?php echo $app;?> but cannot find an available time can add their names to this list -- they will be notified when new <?php echo $app;?> slots become available.  You can edit or delete these entries.</p>  <?php }?>
</div>

<div class="helpsection" id="calendarinfo">
	<h2>Calendars Page</h2>
	<p>The Calendars page lists any calendars you own, manage, or of which you are a member.  To view a calendar, click on its title.  
	To view multiple calendars simultaneously, check the boxes next to the calendar titles, then click the "View Calendar" button (right-arrow button at top right).
    </p>
    <p>To create a new calendar, click the "New Calendar" button (plus-sign button at top right). This will take you to a form where you can fill in the particulars about your calendar.
    You should only create a calendar if you want people to be able to make <?php echo $apps;?> with you.  
    You do not need to create a calendar if you simply wish to make <?php echo $apps;?> with other calendar owners.  Once you have created a calendar, you can use it to schedule your <?php echo $apps;?>; you do not need to delete and recreate your calendar each time you want to use it.  You can also create multiple calendars (e.g., for different kinds of  <?php echo $apps;?>).
    </p>
    <p>You can apply to manage or be a member of a calendar on the Calendars page.  Above the "Managed" and "Member Of" sections, 
    you will see "Apply to Manage" and "Apply for Membership" buttons, respectively.  
    The process for each is the same.   Enter one or multiple (comma-separated) <?php echo $netid;?>s in the "Apply" search box.  Then click the Enter key 
    or tap "Search" from a mobile device.  
    A calendar, or list of calendars, for the given user will appear.  Click the checkbox for each calendar you wish to manage (or be a member of), and 
    enter any text to be included in the request e-mail,  
    then click "Apply".  
    An e-mail will be sent to the owner of the selected calendar(s) with your request.  The calendars 
    you have applied to manage will appear as "pending" on the Calendars page. The calendar owner will have the option to accept or decline the request.</p>
    <p>If you are a calendar owner or manager, you will be alerted to any manage or member requests with a highlighted calendar row 
    on which you can select to either Allow or Deny the request. 
    Click "Allow" to add them as managers or members, or click "Deny" to refuse the request.</p>
    <!-- This feature no longer supported
    <p>Note that youy can apply to manage (or be a member) of a calendar which does not currently exist:  the calendar will be created, and your request to manage/member will be forwarded to the calendar owner.
    This is a useful way for someone to manage the calendar of a user who does not want to use the system themselves.</p>
    -->
	 <p>If you wish to make global changes to a calendar, or delete a calendar, you should check the box next to the calendar's title and click the "Calendar Settings" button (starred-circle button at the top right).  Changes you can make include changing the title of the calendar, 
	 adding or removing calendar managers or members, changing block defaults, and changing settings for e-mail notifications and reminders.  You can also delete the calendar (this will delete all blocks and <?php echo $apps;?>).</p>
	 <p>Changes you make to settings in Calendar Settings, such as a different meeting location, apply to blocks that are added to the calendar subsequent to the change.  However, when you make such a change, 
	 you will be prompted as to whether or not you wish to have these changes propagated to existing blocks as well.</p>
</div>

<!-- Calendar Setup -->
<div class="helpsection" id="createcalendar">
	<h2>Create New Calendar</h2>
    <p>To create a calendar, go to the Calendars page, then click "New Calendar" (plus-sign button at top right).  This will bring up the Create Calendar form. 
	Many of the form fields will have been filled in based on your Directory entry, but you can modify any of these fields.  You must specify a title for your calendar.  You can also specify default 
	values that will be used when you add blocks of available time to your calendar. Take note that most of these defaults can be overridden on each block, but some apply to all blocks.  
	One of the global settings for the calendar is turning the Wait List on or off.  You can set up default access restrictions (who can make <?php echo $apps;?>, and deadlines for making <?php echo $apps;?>), 
	which can be overridden on each block as well.  Access restrictions are described more fully under the help topic "Add a Block".</p>
    <p>You can designate one or more "managers" for your calendar.  Calendar managers can do everything you can do with your calendar (view/schedule/cancel <?php echo $apps;?>).  
    When you add a manager, you can specify whether or not they should receive <?php echo $app;?> notifications and reminders
    (notifications are sent out at the time an <?php echo $app;?> is made or cancelled. Reminders are sent out one day prior to the scheduled <?php echo $app;?>).
    </p>
    <p>You can designate one or more "members" for your calendar.  
    Calendar members are people who can schedule blocks of available time for themselves on the calendar.  
    Normally, all blocks of available time refer to the calendar owner, but a member can schedule blocks of time for themselves. 
    When you add a member, you can specify whether or not they should receive <?php echo $app;?> notifications and reminders.
    </p>
    <p>When you are finished, click Save to save the changes.  You can click Cancel at any time to exit the screen without saving any changes.</p>
</div>

<div class="helpsection" id="calendarsettings">
	<h2>Edit Calendar Settings</h2>
    <p>If you wish to make global changes to a calendar, or delete a calendar, first go to the Calendars page.  Next, check the box next to the calendar's title and click the "Calendar Settings" button (starred-circle button at the top right).  Changes you can make include changing the title of the calendar, adding or removing calendar managers, changing block defaults, and changing settings for e-mail notifications and reminders.</p>
    <p>To delete the calendar, click the "Remove Calendar" button (x-button at top right).  This will delete all blocks and <?php echo $apps;?>.  You will, however, be prompted to confirm the removal and be given the opportunity to edit the e-mail notification message to these cancelled-<?php echo $app;?> holders beforehand.  You should only remove your calendar if you no longer wish to use the system altogether. You do not need to remove your calendar between semesters or years. Instead, you can just continue to add blocks to your calendar that correspond to your availability at future times.</p>
    <p>You can also find a direct URL link for the calendar, a subscription URL for iCal, as well as an RSS URL on the Calendar Settings page.  These allow you to sync your <?php echo WaseUtil::getParm('SYSID');?> calendar
    with a local calendar<?php
    if (WaseUtil::getParm('EXCHANGE_USER') or  WaseUtil::getParm('GOOGLEID')) {
    ?>; however, <?php echo WaseUtil::getParm('SYSID');?> includes a facility for putting blocks and <?php echo $apps;?> directly into your local calendar (e.g., Outlook or Google Calendar) ... you do this through
    the Preferences tab, and this is simpler and more direct than using an Ical or RSS feed.<?php }?></p>
	 <p>Changes you make to settings in Calendar Settings, such as a different meeting location, apply to blocks that are added to the calendar subsequent to the change.  However, when you make such a change, you will be prompted as to whether or not you wish to have these changes propagated to existing blocks as well.</p>
     <p>See the "Create New Calendar" help section for more information about specific form fields.</p>
    <p>When you are finished, click Save to save the changes.  You can click Cancel at any time to exit the screen without saving any changes.</p>
</div>



<div class="helpsection" id="subscribe">
<h2>Subscribe to a Calendar</h2> 
<?php
/* Determine if Google or Exchange is enabled */
$exchange = WaseUtil::getParm('EXCHANGE_USER');
$google = WaseUtil::getParm('GOOGLEID');

if ($exchange && $google)
    $msg = 'Exchange (Outlook) Calendar or Google Calendar';
elseif ($exchange)
    $msg = 'Exchange (Outlook) Calendar';
elseif ($google)
    $msg = 'Google Calendar';
else
    $msg ='';

if ($msg) { ?>
<p>
Note: <?php echo WaseUtil::getParm('SYSID')?> allows you to export <?php echo $apps;?> directly into your <?php echo $msg?>.  You set this up using the Preferences
tab (see the Preferences help section for details).  This is a much more direct way to synchronize your <?php echo $apps;?> than using the subscription mechanism
detailed below.</p>
<?php
}
?>
<p>
If you are using a local calendaring application that supports "internet calendars" (sometimes referred to as "public calendars" or "web calendars"), 
then you can view your <?php echo $apps;?> and blocks directly from your local calendaring application. To set this up you'll use the "Subscription URL" displayed on 
the Calendar Settings page.</p>
<p>
The Subscription URL (which begins with "webcal" instead of the usual "http") currently works well with Microsoft Outlook, the iCal application on a Mac, and Google Calendar. 
It <i>may</i> work with other calendaring applications, but you would need to check the documentation for the application to see if it supports calendar subscriptions.<br><br>
To use this capability,  first go to the Calendar Settings page.
On that page you will see a "Calendar URL (for <?php echo $app?> makers)".    Above this is a button labelled "Publish calendar to iCal/RSS:".  If you want to subscribe your local calendar to your <?php echo $sysid?>
calendar, then you must turn this button ON.  You will be warned that doing this makes it possible for anyone who has the URL to see your <?php echo $apps?> (yet another reason for using the PREFERENCES 
calendar sync facility rather than calendar subscription).</p>
<p> You should now see the Subscription URL It begins with with "webcal" instead of "hhtps").    Copy this entire URL into your browser's cut-and-paste buffer, aka your clipboard, or just jot it down.  
The next steps depend on which local calendaring application you are using.<br />
 <br /> <strong>If you are using Outlook</strong> <ol>   <li>    From the Outlook Tools menu, select <strong>Account Settings</strong>.</li>   <li>      Click the tab for <strong>Internet Calendars</strong>. </li>   <li>      Click <strong>New</strong>.</li>   <li>      Paste the previously copied webcal link into the New Internet Calendars Subscription box.</li>   <li>      Click the <strong>Add</strong> button.</li>   <li> In the Subscription Options popup, give the folder a name such as WebAppts.</li>   <li> <em>optional &nbsp;&nbsp;</em> Provide a brief description of the calendar.</li>   <li> Click <strong>OK</strong>.</li>   <li> Click <strong>Close</strong>.</li> </ol> <p> You should now see Internet Calendars added to the list in Folder View and the name you entered, from step #6 above, listed.</p> <p>    <strong>If you are using Google Calendar </strong></p> <ol>   <li>   Go to Google calendar and locate the calendar list on the left side of the page.</li>   <li> At the bottom of the "Other calendars" list, click <strong>Add</strong> </li>   <li> Select <strong>Add by URL</strong>.</li>   <li> Paste the previously copied webcal link into the Add Other Calendar popup.</li>   <li> Be sure Make the calendar publicly accessible is not checked.</li>   <li> Click the <strong>Add</strong> button.</li>   <li> Locate the webcal link now in the list of Other calendars and click it to display your Web <?php echo ucfirst($apps);?> Scheduling System calendar.<br>   </li> </ol> <p><strong>If you are using iCal on a Mac</strong></p> <ol>     <li>In iCal, under the Calendar menu, click <span class="style1">Subscribe</span>.</li>     
<li>Paste the previously copied webcal link into the Calendar URL text box and click <strong>Subscribe</strong>.</li>    
<li>Enter a name for this calendar (e.g., WebAppts), then click <strong>OK</strong>.</li>    
</ol>  
 <p>This particular Web <?php echo ucfirst($app);?> Scheduling System calendar should now appear in the list of available calendars.</p>
 <p>NOTE:  Google calendar currently can take as long as a day to update a subscribed calendar, so you should NOT use this mechanism to keep up with recently scheduled <?php echo $apps;?>.  Instead, use the 
 PREFERENCES system to sync your <?php echo $sysid?> into your Google calendar.</p>
 <p><strong>If you are using Mozilla Lightning or Sunbird</strong></p>
 <ol>
   <li> From the File menu, select New, Calendar.</li>
   <li> Click the radio button for On the Network.</li>
   <li> Click <strong>Next</strong>.</li>
   <li> Be sure the Format selected is iCalendar (ICS)</li>
   <li>In the Location field, paste the previously copied webcal link into the Location field. </li>
   <li> Click <strong>Next</strong>. </li>
   <li> Provide  a name, such as WebAppts, in the Name field and make any other desired changes (e.g., color, show alarms).</li>
   <li>Click <strong>Next</strong> to display &quot;Your calendar has been created.&quot;</li>
   <li>Click <strong>Finish</strong>.</li>
  </ol>
 <p>You should now see the name of the calendar you entered, from step #7 above,  added to your list of calendars. </p>
 <p>
</div>

<div class="helpsection" id="advertise">
<h2>Advertise a Calendar</h2> 
Once you have created a calendar, and placed blocks of available time on that calendar, you may want to advertise your calendar so that others can easily locate your calendar and make <?php echo $apps;?> with you.  
People can locate your calendar by going to <?php echo WaseUtil::getParm('SYSID');?> and searching for the calendar on the Make <?php echo ucfirst($app);?> page, 
but you can simplify this process for them by advertising the "Calendar URL" displayed on the Calendar Settings page.<br /> <br />
The Calendar URL points directly to your calendar.  When people invoke that URL, they will first be asked to log into <?php echo WaseUtil::getParm('SYSID');?>, and then they will be taken directly to a search result for your calendar, where they can find a block of available time and make an <?php echo $app;?> with you.<br /><br />
To summarize, the "Calendar URL" on the Calendar Settings page is a URL that you should advertise to people who will want to make <?php echo $apps;?> with you.  You might want to put it on a web site, or send it to people in an email.  
</div>
 
<div class="helpsection" id="subscriberss">
<h2>Subscribe to a Calendar as an RSS feed</h2> 
<p>
If a calendar is used to schedule things such as events, seminars, presentations, or other group-oriented events (typically represented by unslotted blocks), 
then the RSS URL will allow people to subscribe to the calendar as an RSS feed (the system will send them the calendar in RSS format).  
This subscription only returns block information; it does not return information about individual <?php echo $apps;?>.  
It is intended for event calendars, and allows someone to see all of the scheduled events (calendar blocks) in an RSS reader.
</p>
<p>To locate this URL, folow the instructions on Subscribing to a calendar ... the RSS URL is displayed along with the Subscription URL.
</div>

<?php if (WaseUtil::getParm('WAITLIST')) {?>
<div class="helpsection" id="waitlist">
	<h2>Wait List</h2>
    <p>Each calendar can have a wait list associated with it.  Users on the wait list are sent an email whenever <?php echo $apps;?> slots become available on the calendar (and are removed from the wait list). 
    Calendar owners can ask to be notified when someone joins the wait list.   To enable the wait list feature, turn the Wait List field to "On" on the Calendar Settings page.  
    Users can then add entries to the wait list specifying a time range for which they are available and a message for the calendar owner.  
    This is useful if there are no blocks of time on a given calendar for which a person is available. </p>
    <p>The Calendars page shows a count of users (if any) on the wait list foir a calendar.  The actual list of users on the wait list (if any) is displayed in the Calendar Settings page. 
    Users are removed from the wait list once you make additional appointment slots available, but you can also remove users from the wait list from the Calendar Settings page.</p>
    
</div>
<?php } ?>

<div class="helpsection" id="doviewcal">
	<h2>View Calendar</h2>
    <p>To view a calendar that you own, manage, or are a member of, go to the Calendars page, then click the title of the calendar you wish to view.  You may also select multiple calendars by checking the checkboxes next to each calendar and clicking "View Calendar" (right-arrow button at top right).</p>
	<p>The View Calendar page is the central place for users to add, edit, and delete blocks on their calendar.  </p>
    <p><b>Calendar Navigation</b><p>By default, the screen shows you all of the blocks and <?php echo $apps;?> for the current month. 
    To select another month, or a specific week or date, click on the small calendar (forward/backward arrows for month scrolling, "WK" column for week selection, 
     or a date to see only the blocks and <?php echo $apps;?> on that date).  On a large screen, you can also expand the calendar using the up-and-right arrow to the right of the small calendar to 
      view the blocks and <?php echo $apps;?> in calendar format.</p>  
      
<p>The small calendar is color-coded to indicate the status of a day, as follows:
<ul>
<li>No color: There are no blocks of available time on this day.</li>
<li>Grey: There are blocks of available time on this day, but none of the <?php echo $app;?> slots are available (or the Block is locked).</li>
<li>Orange: You can make <?php echo $apps;?> on this day.</li>
<li>Yellow: You have <?php echo $apps;?> scheduled on this day.</li>
</ul>
</p>

<br><p><b>My Calendars</b><br><br>The My Calendars section shows all of the calendars that you own, manage, or are a member of.  
To see the blocks and <?php echo $apps;?> for one or multiple calendars, click the checkbox next to the calendar.  
The calendars are color-coded so that you can tell at a glance which blocks belong to which calendar if multiple calendars are selected.  
The My Calendars section can be collapsed and expanded, which is useful for easier viewing on a smaller screen.</p>

<br><p><b>Blocks and <?php echo ucfirst($apps);?></b><br><br>The main part of the View Calendar screen shows the blocks, <?php echo $apps;?>, and open slots available for the selected time range.  A block corresponds to a period of time during which the calendar owner is available for <?php echo $apps;?>. For a given calendar, all blocks are color-coded to have the same color.  The blocks are listed in chronological order, and key information about the block is displayed here, including the description, location, maximum number of <?php echo $apps;?> allowed per slot, maximum number of <?php echo $apps;?> allowed per person, and the direct URL for viewing the block.
</p>
There is a print-button located in the upper-right corner of the screen.  This will print a list of all blocks and <?php echo $apps;?> for the given time frame.
<p>
There are action buttons for the block in the upper right hand corner of the block.
<ul>
<li>x-button: Delete block.  When you delete a block, all <?php echo $apps;?> are canceled. You can specify the text that should included in the e-mail that will be sent to notify people of the cancellation, or specify that such e-mail notifications should not be sent.</li>
<li>padlock-button: Lock/unlock block. Click to make a block unavailable and prevent anyone from making a new <?php echo $app;?> in that block.  Clicking the padlock again will make the block available.</li>
<li>double-arrow-button: Add the block to your local calendaring application (e.g., Outlook).</li>
<li>pencil-button: Edit block.  You will go to the edit block screen where you can change information about the block.</li>
</ul>
</p>
<p>The slots and <?php echo $apps;?> for the block are listed with their associated times.  Each time slot may have one or more <?php echo $apps;?> associated with it, and the maximum number of <?php echo $apps;?> 
allowed per slot can be specified on the Create or Edit Block form.
</p>
<p>
The action buttons for a slot are located on the right-side of the slot.
<ul>
<li>padlock-button: Lock/unlock a slot. Click to make a slot unavailable and prevent anyone from making a new <?php echo $app;?> for that slot.  Clicking the padlock again will make the slot available.</li>
<li>plus-button: Add <?php echo $app;?>.  Click to go to the add <?php echo $app;?> screen.</li>
</ul>
</p>
<p>
Note:  a block or calendar owner can always add <?php echo $apps;?> to a block, regardless of any restrictions that may be in place.  Note also that people making <?php echo $apps;?> do NOT use the View Calendar
page.  Instead, they click on the Make <?php echo ucfirst($app)?> tab.  The ability of calendar owners/managers to make <?php echo $apps?> on this page is provided as a convenience.</p>
<p>
The actions for an <?php echo $app;?> are located on the right-side of an <?php echo $app;?>, as follows:  
<ul>
<li>pencil-button: Edit <?php echo $app;?>. You will go to the edit <?php echo $app;?> screen where you can change the name/email/phone number associated with the <?php echo $app;?>, as well as the start and end times (as long as you do not violate any block restrictions).</li>
<li>double-arrow-button: Sync the <?php echo $app;?> to your local calendaring application (e.g., Outlook, Gcalendar).</li>
<li>x-button: Cancel <?php echo $app;?>. You will be prompted for text to be part of the e-mail notification sent to <?php echo $app;?> holders.</li>
</ul>
</p>
</div>

<div class="helpsection" id="addblock">
<h2>Add a Block</h2>
<p>Before people can make <?php echo $apps;?> with you, you need to add one or more blocks of available time to your calendar.  You do this by going to the Calendars screen, selecting the relevant calendar title to go to the View Calendar screen, and clicking the "Add Block" (plus-icon) button in the upper-right corner.  This will display a form that lets you specify details about the block, including the block title, description, and location for the <?php echo $apps;?> that will be scheduled.
</p>
<b>Date and Times</b><br>You must specify the date and start time of the block.  You must also specify the end time OR duration of the block.  To toggle between end time and duration, click on the drop-down list for the field title.
<p>
<b>Repeating Blocks</b><br>A repeating block can recur daily, weekly, monthly, and so on.  You must specify an end date for the series of blocks.  
You can also specify on what types of days the blocks should be scheduled, wherein the type of day corresponds to the way days are classified on the daytype calendar at <?php echo WaseUtil::getParm('INSNAME');?>.  
</p>
<p><b>Slots and <?php echo ucfirst($apps);?></b><br>Specify whether or not there are <?php echo $app;?> slots. If you choose to divide into slots, you then specify the slot size for the block ... this refers to the length of the <?php echo $app;?> slots within the block.  If you select "No" for dividing into slots, people will not select a specific time within the block (e.g., this is appropriate for the traditional first-come, first-served <?php echo $ohs;?> block, or for a meeting).
  If, instead, you specify a slot size, then the block will be divided into an integral number of these slots, and people will select specific times.  
<br><br>For example, if you create a block that goes from 11:30 am until 3:00 pm, and you use a slot size of 30 minutes, the system will create seven consecutive half-hour slots (11:30-12, 12-12:30, etc.).  People can then select whatever time slot is available.  If you want people to be able to make half-hour or 1 hour <?php echo $apps;?>, you would set a "Max <?php echo $apps;?>" value of 2 (so that people can make <?php echo $apps;?> that span up to 2 half-hour time slots).
<br /><br /> You can limit how many <?php echo $apps;?> can be made for a slot (or block).  For blocks divided into slots, you can also set a limit on the number of (consecutive) <?php echo $app;?> 
slots that an individual may take.  For un-slotted blocks, you can secify a maximum appointment count for the block.
<br><br>You may also specify that the <?php echo $app;?> maker *must* state a purpose for the <?php echo $app;?>.
<br><br> 
<b>Block Availability</b><br>Specify whether the block is available for making <?php echo $apps;?>.  If you make the block unavailable, no <?php echo $apps;?> may be made.
<br><br>
<b>Contact Information</b><br>Name, Phone, E-mail address(es) of contact person for <?php echo $apps;?>.
<br><br>
<b>Notifications and Reminders</b><br>As the block owner, you can choose to be sent an e-mail notification every time a person signs up for an <?php echo $app;?>.  You can also choose to be sent an e-mail reminder of the <?php echo $app;?>, which is sent out the preceeding night.  People who make <?php echo $apps;?> with you may also get notifications and reminders.  Specify here any text that you would like to be included in these e-mail messages.
<br><br>
<b>Deadlines</b><br>You can associate a number of time restrictions for a block, as follows:</p>
<ul>
<li><b><?php echo ucfirst($apps);?> can be made starting on</b>:  Lets you specify when, in days:hours:minutes prior to the start of the block, that it becomes available for scheduling.  For example, a value of 60 would mean that people could only start making <?php echo $apps;?> within 60 minutes of the start of the block.  The default is zero, which means that the block is always available for scheduling.</li>
<li><b><?php echo ucfirst($apps);?> must be made by</b>: Lets you specify when, in days:hours:minutes prior to the start of the block, that it becomes unavailable for scheduling.  For example, if you specify a value of 60, then people could no longer schedule <?php echo $apps;?> once the current time was less than 60 minutes from the start time of the block.  The default is zero, which means that people can make <?php echo $apps;?> right up until the <?php echo $app;?> start time.  The Opening and Deadline values let you specify a "window" within which <?php echo $apps;?> can be scheduled, relative to the start time of the block.  You can specify neither or either or both of these values.</li>
<li><b><?php echo ucfirst($apps);?> must be canceled by</b>: Lets you specify when, in days:hours:minutes prior to the start of the <?php echo $app;?>, that a person can no longer cancel the <?php echo $app;?>.  For example, if you specify a value of 60, then a person who has made an <?php echo $app;?> could not cancel that <?php echo $app;?> any later than 60 minutes prior to its start.  The default is zero, which means that <?php echo $apps;?> can be scheduled right up until their start time.</li>
</ul> 
<br><br>
<b>Access Restrictions</b><br>These fields allow you to restrict who can view this block and/or make <?php echo $apps;?> for this block.
<ul>
<li><strong>Open</strong>: You can set the access level to "Open," which allows access to anyone (including people who do not have a <?php echo $netid;?> -- <?php echo $sysid?> refers to these users as "guests").</li>
<li><strong>Limited</strong>: By default, the access level is set to "Limited," which means that anyone with a <?php echo $netid;?> can view 
    your calendar blocks and/or schedule an <?php echo $app;?>.</li>    
<li><strong>Restricted</strong>: Setting access level to "Restricted" brings up additional fields which allow you to list the users 
<?php if (WaseUtil::getParm('COURSELIM')) {?> or courses <?php }?>
<?php if (WaseUtil::getParm('ADRESTRICTED')) {?> or groups <?php }?>
who are allowed access to the  
calendar blocks. 
<ul>
<li>To enter a <?php echo $netid;?>, start typing, and select the matching entry from the displayed list.  Then click on Add.</li>
<?php if (WaseUtil::getParm('COURSELIM')) {?>
<li>
To enter a course name you will need use the Blackboard Course ID (ex. SPA101_F2009).
To locate a Blackboard Course ID, log into <a href="https://blackboard.princeton.edu" target="_blank">Blackboard</a> and locate the course in the "My Courses (sortable)" module. 
The Course ID will be prepended to the course name. For example, if your course is listed as SPA101_F2009 Beginner's Spanish I, the Course ID is SPA101_F2009. 
Note that there will be an underscore (_) between the course code (SPA101)and the semester/year code (F2009). It is important to include this underscore, 
and to not have spaces in the Course ID, when you enter it.  Click on Add to add the course.</li><?php }?>
<?php if (WaseUtil::getParm('ADRESTRICTED')) {?>
<li>To enter a group, you must use the Active Directory (AD, also known as LDAP) group name.  Start typing the group name, then select the matching entry from the displayed list. Then Click on Add.</li><?php }?>
</ul> 
<li><strong>Private</strong>: Setting the access level to "Private" limits access to only you and your calendar managers, if any.  This allows you to add blocks to your calendar that only you and your calendar
    managers can use.  People wanting to make an <?php echo $app;?> would have to contact you or one of your calendar managers and request that the <?php echo $app;?> be added.</li>
</ul>
<p>Note that you can have different settings for "View Calendar" and "Make <?php echo ucfirst($apps);?>."  For example, if you want anyone at all to be able to view your calendar blocks, but only you to be the one to schedule <?php echo $apps;?>, you would set "View Calendar" to "Open" and "Make <?php echo ucfirst($apps);?>" to "Private."</p>
<p>When you are finished, click Save to save the changes.  You can click Cancel at any time to exit the screen without saving any changes.</p>
</div>

 
<div class="helpsection" id="editblock">
<h2>Edit a Block</h2>
<p>To edit a block, go the Calendars page and click on the relevant calendar to go to the View Calendar page.  Select the date or date range for the block so that the block will display on the screen.  Then, click on the pencil-icon in the upper-right corner of the block to go the Edit Block page.  
<br><br>This page is very similar to the Add Block page (see Adding a Block for field details).  Not all fields are editable as they affect <?php echo $apps;?> that may already be made.  You will additionally find the Block URL to directly advertise the block to users for making <?php echo $apps;?>.
<br><br>To delete a block, click on the Delete Block (x-button) in the upper right corner of the screen.
</p>
</div>


<div class="helpsection" id="addappt">
<h2>Add an <?php echo ucfirst($app);?></h2>
<p>Users add <?php echo $apps;?> to available blocks through the Make <?php echo ucfirst($app);?> screen.  See the help topic "Make an <?php echo $app;?>".</p>
<p>Calendar owners, managers, and members can also add <?php echo $apps;?> via the View Calendar screen.  Go the Calendars page and click on the relevant calendar title.  Select the date or date range for which you would like to make an <?php echo $app;?>.  Scroll to the appropriate block, then click on the plus-icon on the right side of the desired time slot.  
 You will go to the Create <?php echo ucfirst($apps);?> page where you can enter the appropriate information to create the <?php echo $app;?>.</p>  
</div>


<div class="helpsection" id="editappt">
<h2>Edit an <?php echo ucfirst($app);?></h2>
<p>A user can edit any <?php echo $app;?> that they have made through the My <?php echo ucfirst($apps);?> screen.  See the help topic "View My <?php echo ucfirst($apps);?>".</p>
<p>Calendar owners, managers, and members can also edit <?php echo $apps;?> via the View Calendar screen.  Go the Calendars page and click on the relevant calendar title.  Select the date or date range for the <?php echo $app;?> so that the block on which the <?php echo $app;?> was made will display on the screen.  Then, click on the pencil-icon on the right side of the <?php echo $app;?> to go the Edit <?php echo ucfirst($app);?> page.  
<br><br>This page is very similar to the Sign Up for <?php echo ucfirst($app);?> page (see Sign Up for <?php echo ucfirst($app);?> for field details).
<br><br>To cancel an <?php echo $app;?>, click on the Cancel <?php echo ucfirst($app);?> (x-button) in the upper right corner of the Edit <?php echo $app;?> screen.
</p>
</div>




<div class="helpsection" id="advertiseblock">
<h2>Advertise a Block</h2> 
Once you have created a block, you may want to advertise your block so that others can easily locate the block of time to make <?php echo $apps;?> with you.  <br /> <br />
The Block URL, which is displayed on the Edit Block page as well as on the View Calendar page, points directly to the block.  When people invoke that URL, they will first be asked to log into <?php echo WaseUtil::getParm('SYSID');?>, and then they will be taken directly to the given block on your calendar.<br /><br />
To summarize, the "Block URL" on the View Calendar page and on the Edit Block page is a URL that you should advertise to people who will want to make <?php echo $apps;?> with you during the Block hours.   
</div>


<div class="helpsection" id="prefsintro">
<h2>Preferences</h2>
<p>Clicking the Preferences button brings up the Preferences page, where you can personalize the operation of <?php echo WaseUtil::getParm('SYSID');?>.  
 The available preferences are documented in this section.
</p>
</div> 

<div class="helpsection" id="prefsglobal">
<h2>Global Settings</h2>
<p>
Global Settings For <?php echo ucfirst($apps);?> allows you to specify how you want <?php echo $apps?> to
synchronize with your local calendar (e.g., Google, Outlook/Exchange).
This is the best way to synchronize your <?php echo $sysid; ?> alendar with your local calendar.  
Once you specify your local calendar type, you will be prompted for information that <?php echo $sysid; ?>
 needs to perform the sync.  Once set, the synchronization will occur automatically;  any changes 
 in your <?php echo $sysid; ?> calendar will be synced to your local calendar.
</p>
<p>
You can also specify
whether or not you would like to get confirmations (notifications)
of <?php echo $apps;?> being made or changed. 
</p>
</div>

<div class="helpsection" id="prefsdefault">
<h2>Default Settings</h2>
<p>
In Default Settings for <?php echo ucfirst($apps);?>, you can set a default text message e-mail address, and whether or not you would like to receive <?php echo $app;?> reminders.  
Each of these default fields can be changed on each block or <?php echo $app;?>.  
</p>
</div>

<div class="helpsection" id="prefswaitlist">
<h2>WaitList Settings</h2>
<p>
<?php if (WaseUtil::getParm('WAITLIST')) {?>WaitList settings allows you to decide whether or not you want to be notified when someone is added to the Wait List for a calendar that you own
(you can enable/disable wait lists for your calendars -- see the section on Creating or Editing a calendar).  <?php } ?>
</p>
</div>



<div class="heightfix"></div>

</div>

</form>
</div><!-- /content -->