<?php 
/*
        This page is a pop-up time selector.
*/
?>
<div data-role="popup" id="editTextMsgEmail">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#editTextMsgEmail").popup("close");' title="Close Window">Close</a></div>
        <h2>Generate Text E-mail</h2>
        <div id="divTextMsgEmailContents" class="popupinner">
            <form id="frmTextEmail" method="post" action="#" data-ajax="false">
                <fieldset>

                <div class="instructions">In addition to receiving <span class="appName"><?php echo WaseUtil::getParm('APPOINTMENT');?></span> confirmations and reminders via email, you can also receive them via text message by entering your text message address here.</div>
                <div class="ui-field-contain">
                    <label for="selProviders" class="hidden-accessible">Service Provider:</label>
                    <select name="selProviders" class="selProviders" data-role="none" title="Service Provider"></select>
                </div>
                
                <div class="ui-field-contain">
                    <label for="txtTextNumber" title="Enter your cell phone number" class="hidden-accessible">Cell Number:</label>
                    <input type="text" id="txtTextNumber" name="txtTextNumber" value="" placeholder="Cell Number" />
                </div>
               
                <div class="instructions italic importantnote" style="margin-top:15px;margin-bottom:2px;">If your service provider is not listed, enter your text message e-mail address manually.</div>

                <div class="ui-field-contain">
                    <label for="txtTxtEmailAddr" title="Enter your txt msg e-mail" class="hidden-accessible">Text Msg Address:</label>
                    <input type="text" id="txtTxtEmailAddr" name="txtTxtEmailAddr" value="" placeholder="e.g. 6095551212@att.net" />
                </div>
                </fieldset>
                
                <div class="ui-field-contain popupbutton">
                    <input type="button" value="Generate" data-inline="true" onclick="doGenerate();" />
                </div>
                <div class="heightfix"></div>
            </form>
    </div>
</div>

<script type="text/javascript">
    var gFldID = "";
	var cTxtMsgNoneSelText = "(none selected)";
	var gCallback = $.noop;
	var $par = null;

	var txtMsgGateways = {};
	txtMsgGateways["alltel"] = {display: 'Alltel', value: "message.Alltel.com"};
	txtMsgGateways["att"] = {display: 'AT&T', value: "txt.att.net"};
	txtMsgGateways["cellularone"] = {display: 'Cellular One', value: "mobile.celloneusa.com"};
	txtMsgGateways["cingular"] = {display: 'Cingular', value: "cingular.com"};
	txtMsgGateways["nextel"] = {display: 'Nextel', value: "messaging.nextel.com"};
	txtMsgGateways["sprint"] = {display: 'Sprint', value: "messaging.sprintpcs.com"};
	txtMsgGateways["tmMobile"] = {display: 'T-Mobile', value: "tmomail.net"};
	txtMsgGateways["verizonwireless"] = {display: 'Verizon Wireless', value: "vtext.com"};

	$("#editTextMsgEmail").bind({
		popupafteropen: function(event, ui) {
			var appName = g_ApptText;
			if (typeof makeapptview !== 'undefined') {
				var calView = makeapptview.calendarViews[gShownCalViewID];
				appName = calView.model.get("labels")['APPTHING'];
			} else if (typeof apptview !== 'undefined') {
				appName = apptview.appName;
			}
			if (isNullOrBlank(appName))
				appName = g_ApptText;
			$("#divTextMsgEmailContents .appName").text(appName);
		}
	});

	function initTextPopup(txteml,callback,parSelector) {
		gCallback = callback;
		$par = $(parSelector);
		
		var prvdr = "";
		var num = "";
		if (!isNullOrBlank(txteml) && txteml !== cTxtMsgNoneSelText) {
			var ind = txteml.indexOf("@");
			num = txteml.substr(0,ind);
			prvdr = txteml.substr(ind+1);
		}

		var $selProviders = $par.find("select.selProviders");
		if ($selProviders.find("option").length) {
		    var val = '';
		    _.each(Object.keys(txtMsgGateways), function(key) {
			    if (txtMsgGateways[key].value === prvdr)
				    val = key;
		    });
		    $selProviders.val(val);
		    //jumping through hoops for jquery mobile... 
		    $selProviders.selectmenu();
		    $selProviders.selectmenu("destroy");
		    $selProviders.attr('data-role','none');
		    $selProviders.selectmenu();
		    $selProviders.selectmenu("refresh",true);
	    } else {
			$selProviders.selectmenu();
			_.each(Object.keys(txtMsgGateways), function(key) {
				var str = "<option value='" + key + "'";
				if (prvdr === txtMsgGateways[key].value)
					str += " selected='selected'";
				str += ">" + txtMsgGateways[key].display + "</option>";
				$selProviders.append(str);
			});
		    $selProviders.selectmenu("refresh",true);
		}
		
		$par.find("#txtTextNumber").val(num);
	}
	
	function doGenerate() {
		var txtaddr = "";
		//check to see if entered manually
		var $manualEmail = $par.find("#txtTxtEmailAddr");
		if (!isNullOrBlank($manualEmail.val()))
			txtaddr = $manualEmail.val();
		else { //generate from the provider and cell number
			var nmbr = $par.find("#txtTextNumber").val();
			if (!isNullOrBlank(nmbr)){
				var prvdr = $par.find("select.selProviders").val();
				nmbr = nmbr.replace(/\D/g, "");
				txtaddr = nmbr + "@" + txtMsgGateways[prvdr].value;
				
			}
		}
		gCallback(txtaddr);
		return false;
	}
		
	
</script> 
