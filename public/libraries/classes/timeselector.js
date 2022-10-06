gTimeSelectors = [];
gfmtFormTime = "h:mm a";

function TimeSelector(inTextID) {
	this.increment = 30;
	this.mDefault = moment().startOf("day").hour(9);
	this.mOptionsStart = moment().startOf("day");

	this.valholder = inTextID;
	this.id = inTextID + "_sel";
	this.init();
}
TimeSelector.add = function(ts) {
	gTimeSelectors.push(ts);
};
TimeSelector.closeAll = function() {
	for (var i=0; i < gTimeSelectors.length; i++) {
		gTimeSelectors[i].doClose();
	}
};

TimeSelector.prototype = {
	init: function() {
		var ts = this;
		TimeSelector.add(ts);
		
		//clicking anywhere else closes the time list
		$(document).bind("click", function(e) {
			if (!$(e.target).closest('.timelist').length && $(e.target).attr("id") !== ts.valholder) {
				ts.doClose();
			}
		});
		$("#"+this.valholder).bind("change", function() {
			TimeSelector.closeAll();
		});
	},
	openSelector: function(inEl) {
		var ts = this;
		this.drawSelector();
		this.setPosition($(inEl).offset().top + $(inEl).parents(".ui-input-text").height(), $(inEl).offset().left + 3);
		$(".timeitem").bind("click", function() {
			ts.doSelect($(this));
		});
	},
	drawSelector: function() {
		if (!$("#" + this.id).length) {
			var str = '';
			str += '<div id="' + this.id + '" class="timelist">';
			str += '</div>';
			$("form").parents(".ui-content").first().append(str);
		}
		$("#" + this.id).empty().append(this.drawOptions());
		$("#" + this.id).show();
		
		var strShownVal = $("#"+this.valholder).val();
		var mShown = this.mDefault.clone();
		if (!isNullOrBlank(strShownVal)) {
			mShown = moment(strShownVal,gfmtFormTime);
		}
		var id = this.valholder+'_'+mShown.hours()+'_'+mShown.minutes();
		if ($("#" + id).length)
			$("#" + this.id).scrollTop($("#" + id).position().top);
	},
	drawOptions: function() {
		var str = '';
		var mEnd = this.mOptionsStart.clone().endOf("day");
		for (var mTmp = this.mOptionsStart.clone(); mTmp.isBefore(mEnd); mTmp.add(this.increment,"minutes")) {
			var id = this.valholder+'_'+mTmp.hours()+'_'+mTmp.minutes();
			str += '<div class="timeitem" id="'+id+'">' + mTmp.format(gfmtFormTime) + '</div>';
		}
		return str;
	},
	setPosition: function(inTop,inLeft) {
		$("#" + this.id).offset({ top: inTop, left: inLeft });
	},
	doClose: function() {
		$("#"+this.id).hide();
	},
	doSelect: function(inTimeItem) {
		$("#"+this.valholder).val($(inTimeItem).text());
		$("#"+this.valholder).trigger("change");
		this.doClose();
	}
};