<?php 
$selclass = ' menuselected';
?>

	<div data-role="panel" id="mobilenav-panel" data-display="overlay" data-position="left">
        <ul data-role="listview">
            <li data-icon="delete" class="subtlerow"><a href="#" data-rel="close" title="Close Menu">Close</a></li>
            <li data-role="list-divider" class="shortdivider"></li>
            <li><a href="myappts.php" data-ajax="false">My <?php echo WaseUtil::getParm('APPOINTMENTS');?></a></li>
            <li><a href="makeappt.php" data-ajax="false">Make <?php echo WaseUtil::getParm('APPOINTMENT');?></a></li>
            <?php if (!isGuest()) { echo '<li><a href="calendars.php" data-ajax="false">Calendars</a></li>'; }?>
            <li data-role="list-divider" class="shortdivider"></li>
            <?php if (!isGuest()) { echo '<li><a href="#" data-ajax="false" onClick="openPrefs(); return false;">Preferences</a></li>'; }?>
            <li><a href="#" data-ajax="false" onClick="openHelp(); return false;">Help</a></li>
            <li><a href="whatsnew.php" data-ajax="false">What's new</a></li>
            <li data-role="list-divider" class="shortdivider"></li>
            <li><a href="logout.php" data-ajax="false">Log out</a></li>
       </ul>
    </div>

 	<!--actions panel is built on relevant pages-->

    <div data-role="header" id="divHeader"> <!-- data-position="fixed">-->
        <div id="usermenu"><?php if ($_SESSION['authtype'] != 'guest') echo $_SESSION['authid']; else echo 'Guest (' . $_SESSION['authid'] . ')'; ?>&nbsp;&nbsp;<a href="logout.php" data-ajax="false">Log out</a></div>
   		<div id="divHeaderLogo"></div>
        <div class="borderdiv_top"></div>
        <div class="borderdiv_bottom"></div>
        <a id="lnkmobilenav" href="#mobilenav-panel"  class="ui-btn ui-corner-all ui-icon-bars ui-btn-icon-notext" title="Menu">Menu</a>
        <!-- nav is for top-horizontal layout -->
        <div id="nav">
        	<div id="menu">
                <ul>
                    <li class="mainnav<?php if ($pagenav == 'myappts') echo $selclass; ?>"><a href="myappts.php" data-ajax="false">My <?php echo WaseUtil::getParm('APPOINTMENTS');?></a></li>
                    <li class="mainnav<?php if ($pagenav == 'makeappt') echo $selclass; ?>"><a href="makeappt.php" data-ajax="false">Make <?php echo WaseUtil::getParm('APPOINTMENT');?></a></li>
                    <?php if (!isGuest()) {
						$str = '<li class="mainnav';
						if ($pagenav == 'calendars') $str .= $selclass;
						$str .= '"><a href="calendars.php" data-ajax="false">Calendars</a></li>';
						
						$str .= '<li class="mainnav';
						if ($pagenav == 'preferences') $str .= $selclass;
						$str .= '"><a href="#" data-ajax="false" onClick="openPrefs(); return false;">Preferences</a></li>';
						
						echo $str;
					}
					?>
                    <li class="mainnav<?php if ($pagenav == 'help') echo $selclass; ?>"><a href="#" data-ajax="false" onClick="openHelp(); return false;">Help</a></li>
                    <li class="mainnav<?php if ($pagenav == 'whatsnew') echo $selclass; ?>"><a href="whatsnew.php" data-ajax="false">What's new</a></li>
               </ul>
           	</div>
            <!--breadcrumbs will go here, built on each page-->
        	<div class="heightfix"></div>
        </div>
    	<div id="divMessage"></div>
    </div><!-- /header -->


<script type="text/javascript">
    gMessage.init();				
	function openHelp() {
		var topic = $("#helpTopic").val() || 'start';
		window.open(getURLPath() + "/help.php?helptopic="+topic, "wasehelp");
	}
	function openPrefs() {
		window.open(getURLPath() + "/preferences.php", "waseprefs");
	}
	</script>
