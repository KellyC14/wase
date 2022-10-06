    <div id="divPrefs" class="popupinner" role="main">	

    <form id="prefinfo" method="post" action="#" data-ajax="false">        
            <input type="hidden" id="helpTopic" value="prefsintro" />
        <div class="instructionsprefs"><b>Note</b>: All changes are automatically saved.</div>
        
        <fieldset> 
        	<h3>Global Settings for <?php echo ucfirst(WaseUtil::getParm('APPOINTMENTS'));?></h3>
            <div class="instructions">Preferences set here are used for all <?php echo WaseUtil::getParm('APPOINTMENTS');?> you make.</div>
 
            <div id="divSyncCalType" class="ui-field-contain selectboxlarge">
                <label id="lblSyncCalType" for="selSyncCalType">Local Calendar for Sync:</label>
                <select name="selSyncCalType" id="selSyncCalType" title="Local Calendar Type"></select>
            </div>

			<div id="divGoogleAuthMsg" class="ui-field-contain" style="display:none;">
			    <label></label>
			    <div class="indentedcontent">
			         <input id="btnAuthGoogle" type="button" value="Authorize Access" data-inline="false" /> 
			         Note: You will be redirected to Google to give <?php echo WaseUtil::getParm('SYSID')?> permission to access your calendar(s).
		         </div>
            </div>
            <div id="divGoogleCalList" class="ui-field-contain selectboxlarge">
            	<label></label>
                <select id="selCalLists" title="Choose Calendar">
                	<option value="0">Please select a calendar</option>
                </select>          
            </div>
             
            <div id="divGoogleMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;"><?php echo WaseUtil::getParm('SYSID')?> will attempt to sync with your Google calendar.</div></div>
            <div id="diviCalMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;"><?php echo WaseUtil::getParm('SYSID')?> will attempt to sync with your local calendar by sending you emails with iCal attachments.</div></div>
            <?php $lex = WaseUtil::getParm('EXCHANGE_USER');  $l365 = WaseUtil::getParm('O365_USER');?>

            <?php if ($lex && $l365) {?> <div id="divExchangeMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;">For synchronization to work, you must ensure that your Exchange (Outlook) calendar sharing permissions give users "<?php echo WaseUtil::getParm('EXCHANGE_USER');?>" and "<?php echo WaseUtil::getParm('O365_USER');?>" the "Publishing Editor" permission level. Please contact your Exchange administrator if you are unsure of how to do this.</div></div> <?php } elseif ($l365) {?> <div id="divExchangeMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;">For synchronization to work, you must ensure that your Exchange (Outlook) calendar sharing permissions give user "<?php echo WaseUtil::getParm('EXCHANGE_USER');?>" the "Publishing Editor" permission level. Please contact your Exchange administrator if you are unsure of how to do this.</div></div>
            <?php } else {?> <div id="divExchangeMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;">For synchronization to work, you must ensure that your Exchange (Outlook) calendar sharing permissions give user "<?php echo WaseUtil::getParm('O365_USER');?>" the "Publishing Editor" permission level. Please contact your Exchange administrator if you are unsure of how to do this.</div></div>
            <?php }?>

            <div id="divNoneMessage" class="ui-field-contain" style="display:none;"><label></label><div style="margin-left:32%;">Note: under Calendar Settings, you can turn On "Publish calendar to iCal/RSS", which will generate a URL that you can use to "subscribe" your calendar application to your <?php echo WaseUtil::getParm('SYSID')?> calendar.</div></div>
            <input type="hidden" id="txtGoogleToken" name="txtGoogleToken" title="Google Calendar ID" />
                        
            <div id="divConfirm" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgConfirm">
                    <legend>Confirm when <?php echo WaseUtil::getParm('APPOINTMENTS');?> are made or changed:</legend>
                    <label id="lblConfirm" for="chkConfirm">Send confirmations</label>
                    <input type="checkbox" name="chkConfirm" id="chkConfirm" checked="checked" title="Confirm" />
                </fieldset>
            </div>                

            <hr>
         	<h3>Default Settings for <?php echo ucfirst(WaseUtil::getParm('APPOINTMENTS'));?></h3>
        	<div class="instructions">These preferences are used as default values for <?php echo WaseUtil::getParm('APPOINTMENTS');?>. You can override them when you Add/Edit <?php echo WaseUtil::getParm('APPOINTMENTS');?>.</div>
  
            <div id="divTextMsgEmail" class="ui-field-contain textonlyfield">
                <label id="lblTxtEmail" for="txtTxtEmail" class="rightlabel">Text Msg Address:</label>
                <div><div id="divTextEmail" class="lineuptext"></div>&nbsp;&nbsp;&nbsp;<a class="txtmsgclick" href="#" data-type="button" title="Enter text message email address" onClick="openTextEmailPopup();">click to enter</a></div>
            </div>

            <div id="divRemind" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgRemind">
                    <legend>Receive a reminder of the <?php echo WaseUtil::getParm('APPOINTMENT');?>:</legend>
                    <label id="lblRemind" for="chkRemind">Receive reminders</label>
                    <input type="checkbox" name="chkRemind" id="chkRemind" checked="checked" title="Remind" />
                </fieldset>
            </div>  
            
            <div id="divWaitlistArea">
            <hr>
         	<h3>Settings for Wait Lists</h3>
        	<div class="instructions">These preferences are for wait lists on calendars that you own.</div>
  
            <div id="divNotifyWL" class="ui-field-contain">
                <fieldset data-role="controlgroup" id="cgRemind">
                    <legend>Receive a notification when someone is added to the wait list:</legend>
                    <label id="lblNotifyWL" for="chkNotifyWL">Receive notification</label>
                    <input type="checkbox" name="chkNotifyWL" id="chkNotifyWL" checked="checked" title="Notify" />
                </fieldset>
            </div>                
            </div>
              
            <br>

		</fieldset>
        </form>        

		<?php include('_txtemailselect.php');?>
        
	</div><!-- /content -->

    <script type="text/javascript">
        var gPrefsTxtMsgEl = null;
        function openTextEmailPopup() {
    		var val = $("#prefinfo #divTextEmail").text();
    		var callback = _.bind(prefsViewController.isValidEmail,prefsViewController);
            /*
             $('<div id="#txtMsgDialog">').simpledialog2({
                mode: 'blank',
                headerText: false,
                headerClose: false,
                blankContent: $('#editTextMsgEmail'),
                animate: false,
                width: '300'
            });
            $(".ui-simpledialog-container").drags({handle: 'h2'});*/
            if (!gPrefsTxtMsgEl) {
                gPrefsTxtMsgEl = $('#divTextMsgEmailContents').clone();
                gPrefsTxtMsgEl.insertAfter($("#prefinfo #divTextMsgEmail"));
            }
            $('#prefinfo #divTextMsgEmailContents').show();
    	    initTextPopup(val,callback,'#prefinfo');
	    }
		function doPrefsSubmit(e) {
			preferencesview.savePrefData();
		}		
    </script>                                                               

