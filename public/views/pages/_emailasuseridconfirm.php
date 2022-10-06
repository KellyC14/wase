<?php 
/*
        This page is a pop-up to confirm whether the entered user email address is the intended user.
*/
?>
<div data-role="popup" id="popupEmailAsUserID" data-dismissible="false">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='closeEmailUserIDPopup();' title="Close Window">Close</a></div>
    <div id="divEmailAsUserID" class="popupinner">
        <form id="frmEmailAsUserID" method="post" action="#" data-ajax="false">
            <h2>Matching Email Address?</h2>
            <div id="buildTheText">
                <div class="textdiv">The user ID '<span id="enteredID" class="standoutText"></span>' is not valid.</div>
                <div class="textdiv">However it does match the email address '<span id="userEmail" class="standoutText"></span>',
                which is associated with a user ID of '<span id="userID" class="standoutText"></span>'.</div>
            </div>
            <div id="showTheText" class="textdiv"></div>
            <div class="textdiv">Is this the user you mean?</div>
            <fieldset>
                <div class="submitbuttons buttonspopup">
                    <div id="divButtons" class="ui-field-contain popupbutton">
                        <input type="button" value="No" data-inline="true" onclick="doNo();" />
                        <input type="button" value="Yes" data-inline="true" onclick="doYes();" />
                    </div>
                    <div class="heightfix"></div>
                </div>
            </fieldset>
    
        </form>
    </div>
</div>

<script type="text/javascript">
    var gOnYes = $.noop;
    var gOnNo = $.noop;

    //options are
    //  enteredID, userEmail, userID (buildTheText)
    //  fullMessage (showTheText)
    //  onYes, onNo (yes/no handlers)
    function initEmailAsUserID(options) {
        options = options || {};
        if (options.fullMessage) {
            $("#showTheText").html(options.fullMessage).show();
            $("#buildTheText").hide();
        } else {
            $("#showTheText").hide();
            $("#enteredID").text(options.enteredID);
            $("#userEmail").text(options.userEmail);
            $("#userID").text(options.userID);
            $("#buildTheText").show();
        }
        gOnYes = options.onYes || $.noop;
        gOnNo = options.onNo || $.noop;
    }
    function closeEmailUserIDPopup() {
        $("#popupEmailAsUserID").popup("close");
    }
    function doNo() {
        gOnNo();
        closeEmailUserIDPopup();
    }
    function doYes() {
        gOnYes();
        closeEmailUserIDPopup();
    }
</script> 
