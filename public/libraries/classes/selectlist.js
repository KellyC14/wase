/*
 * SelectList
 */
var SelectList = function(options) {
    options = options || {};
    this.valueFieldID = options.valueFieldID || "";
    this.selectID = options.selectID || "";
    this.vals = options.vals || [];
    this.labels = options.labels || [];
    this.defaultVal = typeof options.defaultVal === 'undefined' ? null : options.defaultVal;
    this.changeHandler = options.changeHandler || $.noop; //parameters: selected value, 'this'
    this.init();
};
SelectList.prototype.constructor = SelectList;
_.extend(SelectList.prototype, {
    init: function() {
        this.$valField = $("#"+this.valueFieldID);
        this.$selList = $("#"+this.selectID);

        //create the list
        this.$selList.addClass("selectListWrapper");
        //horizontal?
        if (this.vals.length > 12) {
            this.$selList.addClass("horizontal");
        }
        var $ul = $("<ul>",{class: "selectList"});
        _.each(this.vals, function(val,ind) {
            $ul.append(
                $("<li>",{class: "selectListItem", "data-val": val, text: this.labels[ind]})
            )
        },this);
        this.$selList.append($ul);

        this.setEnabled(true);

        var obj = this;
        this.$selList.find(".selectListItem").on("click", function() {
            obj.setValue.apply(obj,[$(this).data("val")]);
            obj.doClose();
        });

        //set default value, if it exists
        if (!isNullOrBlank(this.defaultVal)) {
            this.setValue(this.defaultVal);
        }
    },
    setValue: function(inVal) {
        var txt = inVal+"";
        var i = this.vals.indexOf(inVal+"");
        if (i >= 0 && typeof this.labels[i] !== 'undefined') {
            txt = this.labels[i];
        }
        this.$valField.text(txt).data("val",inVal);
        this.changeHandler.apply(this,[inVal]);
    },
    getValue: function() {
        return this.$valField.data("val");
    },
    doClose: function() {
        _.each(this.$selList.find(".selectListItem"), function(item) {
            $(item).removeClass('selected');
        });
        this.$selList.hide();
    },
    setEnabled: function(isEnabled) {
        this.$valField.toggleClass("disabled",!isEnabled);
        if (isEnabled) {
            var obj = this;
            this.$valField.on("click", function() {
                var pos = $(this).position();
                var selLeft = pos.left - 3;
                var selTop = pos.top + 25;
                obj.$selList.find("[data-val="+$(this).data("val")+"]").addClass('selected');
                obj.$selList.css({top: selTop+"px", left: selLeft+"px"}).show();
            });
        } else {
            this.$valField.off("click");
        }
    }
});