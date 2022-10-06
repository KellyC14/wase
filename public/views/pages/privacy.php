<?php
include "_copyright.php";

$pagenav = 'privacy'; /*for setting the "selected" page navigation */
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

<style type="text/css">
    body {
        background-color: white;
        font-family: Arial, Helvetica, sans-serif;
    }

    h1 {
        color: #CC6633;
        padding-left:15px;
        padding-right:15px;
    }

    p{
        padding-left:15px;
        padding-right:15px;
    }
</style>

<body onUnload="">

<div data-role="page" id="privacy">


    <?php
    if ($_SESSION['authenticated'])
        include('_mainmenu.php');
    else
        echo '<div data-role="header" id="divHeader" class="nonav" data-backbtn="false"><div id="divHeaderLogo"></div><div class="borderdiv_top"></div>
        <div class="borderdiv_bottom"></div></div><div class="btnUnAuthBack"><a href="login.page.php" data-ajax="false">< Back to Login</a></div>';
    ?>

    <div class="ui-content" role="main">
        <!-- <h1 id="pagetitle" class="clear">Title</h1>  -->
        <h1>Privacy Notice</h1>
        <div id="divPrivacyArea">
            <p>
                WASE (Web Appointment Scheduling Engine) allows users to create calendars and allocate blocks of time for appointments. Users who wish to make an appointment with another WASE user can search that user’s calendar and make an appointment by signing up for an open time slot.
            </p>

            <p>
                If a WASE user has a local calendar application such as Outlook or Google Calendar, WASE provides a convenient feature to have WASE appointments added to that local calendar. Users can configure their WASE settings to synchronize their WASE appointments to that local calendar. This synchronization requires the user to authorize their local calendar application to grant WASE access to their calendar.
            </p>

            <p>
                Once the authorization has been granted, WASE will add an appointment event whenever the user makes a WASE appointment. WASE will also remove an appointment event whenever the user cancels a WASE appointment.
            </p>

            <p>
                WASE will never read, display, store, or remove any non-WASE calendar information. The synchronization process is only a one-directional interaction to the local calendar application. WASE does not retrieve any calendar events from the local calendar application.
            </p>

            <p>
                WASE operates in complaince with the guidelines listed in <a href="https://www.princeton.edu/privacy-notice" data-ajax="false" class="ui-link">Princeton University's Privacy Notice</a>.
                WASE does not collect data via third-party analytics providers. WASE will never use or transfer data to serve users advertisements.
            </p>
            <p>
                WASE developers and system administrators may use log data or stored user data to help diagnose problems with our servers, administer our websites, and analyze trends to improve this website.
            </p>

        </div>


    </div><!-- /content -->

    <?php include('_footer.php'); ?>

</div><!-- /page -->

</body>
</html>
