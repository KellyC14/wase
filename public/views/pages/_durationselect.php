<?php 
/*
        This page is a pop-up time selector.
*/
?>

<div data-role="popup" id="popupDeadline">
    <div style="float:right;"><a id="btnCancel" href="#" class="ui-btn ui-btn-inline ui-icon-delete ui-btn-icon-notext ui-mini" data-corners="false" data-shadow="false" onclick='$("#popupDeadline").popup("close");' title="Close Window">Close</a></div>
    <h3 class="popuptitle"></h3>
        <div id="divDeadlineSelect" class="popupinner">
        	<fieldset>

                <label for="flipDeadlineSelect" class="ui-hidden-accessible">Choose Deadline By:</label>
                <select data-role="slider" name="flipDeadlineSelect" id="flipDeadlineSelect">
                    <option value="dur">By Duration</option>
                    <option value="date">By Date and Time</option>
                </select>

                <div id="durationSection">
                    <div id="divDurDays" class="ui-field-contain smallintbox">
                        <label id="lblDurDays" for="txtDurDays">Days:</label>
                        <input type="text" data-role="spinbox" data-options='{"orientation":"horizontal"}' name="txtDurDays" id="txtDurDays" min="0" max="365" data-mini="true" />
                    </div>
                    <div id="divDurHours" class="ui-field-contain smallintbox">
                        <label id="lblDurHours" for="txtDurHours">Hours:</label>
                        <input type="text" data-role="spinbox" data-options='{"orientation":"horizontal"}' name="txtDurHours" id="txtDurHours" min="0" max="23" data-mini="true" />
                    </div>
                    <div id="divDurMinutes" class="ui-field-contain smallintbox">
                        <label id="lblDurMinutes" for="txtDurMinutes">Minutes:</label>
                        <input type="text" data-role="spinbox" data-options='{"orientation":"horizontal"}' name="txtDurMinutes" id="txtDurMinutes" min="0" max="59" data-mini="true" />
                    </div>
                </div>

                <div id="datetimeSection">
                    <div id="divDeadlineCal">
                    </div>
                    <div id="divTime">
                        <label for="txtDeadlineTime">Time:</label>
                        <input type="text" id="txtDeadlineTime" name="txtDeadlineTime" placeholder="hh:mm AM" data-mini="true" />
                    </div>
                    <div id="divNamedDates">
                        <div class="instructions">Or choose one of the following dates:</div>
                        <div id="namedDates"></div>
                    </div>
                </div>

                <div id="deadlineSelection" class="durationText"></div>

                <div id="divDoneDur" class="ui-field-contain popupbutton">
                    <input id="btnDoneDur" type="button" value="Done" data-inline="true" />
                </div>

                <div class="heightfix"></div>
           </fieldset>
        </div>
</div>

<script type="text/javascript">
    var gFldID = "";
    var gValField = "";
    var durPopup = {};
    var cal = null;
    var g_mBlockStart = null;
    var g_mSelectedDate = null;

    durPopup.doClickDay = function($td) {
        var mDate = moment($td.attr("id"),cal.dateIDFormat);
        g_mSelectedDate = mDate.clone();
        doDateChange(mDate);
    };

    function drawDeadlineCalSelector(mSelected) {
        //reset DOM
        $("#divDeadlineCal").empty();

        var mStart = mSelected.clone().startOf("month").startOf("day");
        var mEnd = mSelected.clone().endOf("month").endOf("day");

        var opts = {
            startDate: mStart.toDate(),
            endDate: mEnd.toDate(),
            initialDate: mSelected.toDate(),
            showFooter: true,
            showWeekHandles: false,
            selectorObj: durPopup,
            parentID: "divDeadlineCal"
        };

        cal = new Calendar(opts);
        cal.drawCalendar();
        cal.setSelection({range: "day"});
        cal.$cal.find(".monthheader").off("vmousedown");

        //show date
        $("#divDeadlineCal").show();
    }

    function getDurationFromDateTime(mDate,timestring) {
        var mTime = moment(timestring,fmtTime);
        mDate.hours(mTime.hours()).minutes(mTime.minutes());
        var mBlockStart = moment(blockview.periods[0].periodStart).seconds(0).milliseconds(0);
        return moment.duration(mBlockStart.diff(mDate));
    }
    function getDurationFromDHM() {
        var dur = moment.duration({
            days: $("#txtDurDays").val(),
            hours: $("#txtDurHours").val(),
            minutes: $("#txtDurMinutes").val()
        });
        return dur;
    }
    function showSelectedDeadline(dur) {
        $("#deadlineSelection").html(getDeadlineDurationDisplay(dur));
    }

    function setDateTimeValue(dur) {
        var mDeadlineDateTime = g_mBlockStart.clone().subtract(dur);
        var mDeadlineDate = mDeadlineDateTime.clone().hours(0).minutes(0);
        //date
        cal.setToDay(mDeadlineDate);
        g_mSelectedDate = mDeadlineDate.clone();
        //time
        $("#txtDeadlineTime").val(mDeadlineDateTime.format(fmtTime));
    }
    function setDurationValue(dur) {
        $("#txtDurDays").val(dur.days());
        $("#txtDurHours").val(dur.hours());
        $("#txtDurMinutes").val(dur.minutes());
        showSelectedDeadline(dur);
    }

    function doDurationChange() {
        var dur = getDurationFromDHM();
        showSelectedDeadline(dur);
        //set the date
        setDateTimeValue(dur);
    }
    function doDateChange(mDate) {
        var dur = getDurationFromDateTime(mDate,$("#txtDeadlineTime").val());
        setDurationValue(dur);
    }
    function doTimeChange() {
        var dur = getDurationFromDateTime(g_mSelectedDate,$(this).val());
        setDurationValue(dur);
    }

    function setView(byVal) {
        $("#datetimeSection").toggle(byVal === 'date');
        $("#durationSection").toggle(byVal === 'dur');
        if (byVal === 'date') {
            $("#divNamedDates").hide();
            buildTimePicker("#txtDeadlineTime");
        }
    }

    function popupDeadlineInit() {
		$("#btnDoneDur").click(function(e) {
			//set deadline
            var dur = getDurationFromDHM();
            blockview.setDeadlineField(gFldID, dur);
			$("#popupDeadline").popup("close");
		});
		$("#popupDeadline").bind({
			popupafteropen: function(event, ui) {
				gFldID = $("#fld").val();

				//set the title
                var txt = {
                    'txtOpening': 'Opening',
                    'txtDeadline': 'Deadline',
                    'txtCanDeadline': 'Cancelation Deadline'
                };
                var title = "Select " + txt[gFldID];
				$("h3.popuptitle").text(title);

				//set the view
                setView($("#flipDeadlineSelect").val());

				//load the duration value from the opening form
                var valField = {
                    'txtOpening': 'opening',
                    'txtDeadline': 'deadline',
                    'txtCanDeadline': 'candeadline'
                };
                gValField = valField[gFldID];
                var dur = moment.duration(blockview[gValField],'m');
                setDurationValue(dur);

                //Use the start date and time of the FIRST period
                var mStart = moment(blockview.periods[0].periodStart) || moment();
                g_mBlockStart = mStart.seconds(0).milliseconds(0);
                drawDeadlineCalSelector(g_mBlockStart);

                setDateTimeValue(dur);
			}
		});

        $("#txtDurDays").bind("change",doDurationChange);
        $("#txtDurHours").bind("change",doDurationChange);
        $("#txtDurMinutes").bind("change",doDurationChange);

        $("#txtDeadlineTime").bind("change",doTimeChange);

        $("#flipDeadlineSelect").bind("change", function() {
            setView($(this).val())
        });
   }
</script>
