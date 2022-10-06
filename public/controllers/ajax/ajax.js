function readErrorXML($xml) {
	var er = null;
	$errorcode = $xml.children("errorcode");
	$errortext = $xml.children("errortext");
	var errc = $errorcode.text();
	if (errc !== "" && errc !== "0") {
		err = parseInt(errc);
		er = new Error({errorcode: errc, errortext: $errortext.text()});
	}
	//if(er !== null) alert("error! " + er.getErrCode() + ": " + er.getErrText());
	return er;
}

var g_ajaxURL = "../../controllers/ajax/waseAjax.php"; 
var g_schemaRoot = "SROOT";
var g_AJAXXMLHeader = '<?xml version="1.0"?><wase xmlns="' + g_schemaRoot + '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xsi:schemaLocation="' + g_schemaRoot + 'wase.xsd">';
var g_AJAXXMLFooter = "</wase>";

function callAJAX(str, inCallBack) {
	var dataString = g_AJAXXMLHeader.replace(/\SROOT/g, g_schemaRoot) + str + g_AJAXXMLFooter;
	DoAJAX(dataString, g_ajaxURL , inCallBack, "POST");
}

function DoAJAX(inDatastring, inURL, inCallBack, inPostOrGet) {
	var postORget = isNullOrBlank(inPostOrGet) ? "POST" : inPostOrGet;
	$.ajax({
		/*beforeSend: function() { $.mobile.showPageLoadingMsg(); },
		complete: function() { $.mobile.hidePageLoadingMsg() },*/
		type: postORget,
		url: inURL,
		dataType: "xml",
		data: inDatastring,
		success: inCallBack
	});
}