<?php 
/*
        This page is a pop-up calendar/date selector.
*/
?>

<div data-role="popup" id="popupCal">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupCal").popup("close");' title="Close Window">Close</a></div>
    <h3 class="popuptitle"></h3>
    <div id="divDateTimeSelect" class="popupinner">
        <div id="divCal">
        </div>
        <div id="divTime">
        	<fieldset>
            <div id="divHour" class="ui-field-contain timepartbox">
                <label id="lblHour" for="txtHr" class="ui-hidden-accessible">Hour:</label>
                <input type="text" data-role="spinbox" data-options='{"orientation":"vertical"}' name="txtHr" id="txtHr" min="1" max="12" data-mini="true" />
            </div>
            <div id="divMinutes" class="ui-field-contain timepartbox">
                <label id="lblMinutes" for="txtMins" class="ui-hidden-accessible">Mins:</label>
                <input type="text" data-role="spinbox" data-options='{"orientation":"vertical"}' name="txtMins" id="txtMins" min="0" max="59" data-mini="true" />
            </div>
          	</fieldset>
        </div>
        <div id="divNamedDates">
            <div class="instructions">Or choose one of the following dates:</div>
            <div id="namedDates"></div>
        </div>
        <div class="heightfix"></div>
	</div>
</div>

<script type="text/javascript">
    var gFldID = "";
	var gViewObj = null;
	var cal = null;

    var fieldsWithNamedDates = ['txtStartDate', 'txtEndDateSeries'];

    function drawCalendarSelector(mSelected) {
        //reset DOM
        $("#divCal").empty();

        var mStart = mSelected.clone().startOf("month").startOf("day");
        var mEnd = mSelected.clone().endOf("month").endOf("day");

        var opts = {
            startDate: mStart.toDate(),
            endDate: mEnd.toDate(),
            initialDate: mSelected.toDate(),
            showFooter: false,
            showWeekHandles: false,
            selectorObj: gViewObj,
            parentID: "divCal"
        };

        cal = new Calendar(opts);
        cal.drawCalendar();
        cal.setSelection({range: "day"});
        cal.$cal.find(".monthheader").off("vmousedown");

        //show date
        $("#divCal").show();
    }

    function drawTimeSelector() {
        //hide time
        $("#divTime").hide();
    }

    function drawNamedDates() {
        $("#divNamedDates").hide();
        //if nameddates, show them
        var jc_match=0;
        _.each(fieldsWithNamedDates, function(fieldname) {
            if(gFldID.indexOf(fieldname) > -1) {
                jc_match=1;
            }
        });
        if (jc_match && gViewObj.namedDates.length) {
            $("#namedDates").empty();
            _.each(gViewObj.namedDates, function(ndt) {
                var nm = ndt.name.replace(/_/g," ");
                $("#namedDates").append($("<div>",{class: "nameddate", html: nm}).data("date", ndt.date));
            });
            $(".nameddate").on('click', function(e) {
                var strDt = '';
                if (!isNullOrBlank($(this).data("date")))
                    strDt = moment($(this).data("date"),"YYYY-MM-DD").format(fmtDate);
                $("#" + gFldID).val(strDt);
                $("#" + gFldID).change();
                $("#popupCal").popup("close");
            });
            $("#divNamedDates").show();
        }
    }

    function popupCalInit(inViewObj) {
		gViewObj = inViewObj;
		
		gViewObj.doClickDay = function($td) {
			var strDt = moment($td.attr("id"),cal.dateIDFormat).format(fmtDate);
			$("#" + gFldID).val(strDt);
			$("#" + gFldID).change();
			$("#popupCal").popup("close");
		};
	
		$("#popupCal").bind({
			popupafteropen: function(event, ui) {
				gFldID = $("#fld").val();
				$("h3.popuptitle").text("Select " + $("#fldTitle").val());
				
				var mSelected = moment(); //default to today's date/time

				//Get the date in the input field, if any
				var strdttm = $("#"+gFldID).val();
				if (!isNullOrBlank(strdttm))
					mSelected = moment(strdttm,fmtDate);
				
                drawCalendarSelector(mSelected);

                drawTimeSelector();

                drawNamedDates();
			}
		});

		$("#txtHr").bind("change",function() {
			var val = $(this).val();
		});
		$("#txtMins").bind("change",function() {
			var val = parseInt($(this).val(),10);
			if (val < 10) $(this).val("0"+val);
		});
    }
	
	function computeTime() {
		//get the selected time
		var ampm = $("#txtAmPm").val();
		var hr = $("#txtHr").val(); //1-12			
		var mns = $("#txtMins").val();
		return hr + ":" + mns + " " + ampm;		
	}

</script> 
