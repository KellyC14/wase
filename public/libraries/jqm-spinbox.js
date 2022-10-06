/*
 * jQuery Mobile Framework : plugin to provide number spinbox.
 * Copyright (c) JTSage
 * CC 3.0 Attribution.  May be relicensed without permission/notification.
 * https://github.com/jtsage/jquery-mobile-spinbox
 */

(function($) {
        $.widget( "custom.spinbox", {
                options: {
                        // All widget options, including some internal runtime details
                        dmin: false,
                        dmax: false,
                        theme: false,
                        initSelector: "input[data-role='spinbox']",
                        clickEvent: 'vclick',
                        orientation: 'horizontal', // or vertical
						type: 'num' // or ampm, or hour
                },
				_create: function() {
                        var w = this, tmp,
                                o = $.extend(this.options, this.element.jqmData('options')),
                                d = {
                                        input: this.element,
                                        wrapper: this.element.parent(),
										outer: null //will be set
                                };
                                
                        w.d = d;
                        
                        /*if ( w.d.input.jqmData('mini') === true ) {
                                w.d.input.addClass('ui-mini');
                        }*/

 						//control group
						w.d.outer = w.d.wrapper.wrap('<div class="cgspinbox ui-controlgroup ui-controlgroup-' + o.orientation + ' ui-corner-all" data-type="' + o.orientation + '" data-role="controlgroup"></div>').wrap('<div class="ui-controlgroup-controls">');			
                        w.d.wrapper.addClass('controlgroup-textinput');
                                
                        /*if ( o.orientation === "horizontal" ) { 
                                w.d.wrapper.css({'display':'inline', 'whiteSpace':'nowrap', 'border':'none'}); 
                                if ( w.d.input.jqmData('mini') === true ) {
                                        w.d.input.css({'width':'30px'});
                                } else {
                                        w.d.input.css({'width':'40px'});
                                }
                        } else {
                                w.d.input.css({'width':'auto'});
                                w.d.wrapper.css({'width':'auto','display':'inline-block'});
                        }*/
                        w.d.input.css({'textAlign':'center'});
 
                        if ( o.dmin === false ) { o.dmin = ( typeof w.d.input.attr('min') !== 'undefined' ) ? parseInt(w.d.input.attr('min'),10) : Number.MAX_VALUE * -1; }
                        if ( o.dmax === false ) { o.dmax = ( typeof w.d.input.attr('max') !== 'undefined' ) ? parseInt(w.d.input.attr('max'),10) : Number.MAX_VALUE; }
                        
                        w.d.up = $('<div>')
                                .addClass('ui-icon-plus ui-btn ui-btn-inline ui-btn-icon-notext');
								
                        w.d.down = $('<div>')
                                .addClass('ui-icon-minus ui-btn ui-btn-inline ui-btn-icon-notext');
                                
						if ( o.orientation === 'horizontal' ) {
								w.d.down.insertBefore(w.d.input.parent()); w.d.up.insertAfter(w.d.input.parent());
								w.d.down.addClass("ui-first-child");
								w.d.up.addClass("ui-last-child");
                        } else {
								w.d.up.insertBefore(w.d.input.parent()); w.d.down.insertAfter(w.d.input.parent());
								w.d.up.addClass("ui-first-child");
								w.d.down.addClass("ui-last-child");
								var wdth = w.d.input.closest("div.ui-input-text").innerWidth();
								w.d.up.css({'width':wdth+'px'});
								w.d.down.css({'width':wdth+'px'});
                        }
                                
                        //$.mobile.behaviors.addFirstLastClasses._addFirstLastClasses(w.d.wrapper.find('.ui-btn'), w.d.wrapper.find('.ui-btn'), true);
                        
                        //w.d.up.on("vclick taphold", function(e) {
                        w.d.up.on("vmousedown", function(e) {
							gIsSpinboxHoldOn = true;
							//e.preventDefault();
							if ( !w.disabled ) {
								if (o.type === 'num') {
									SpinboxIncreaseVal(this,o.dmin,o.dmax);
								} else if (o.type === 'ampm') {
									tmp = w.d.input.val();
									if (tmp === "AM") w.d.input.val("PM");
									else  w.d.input.val("AM");
									w.d.input.trigger('change');
								}
							}
                        });
						w.d.up.on("vmouseup", function(e) {
							gIsSpinboxHoldOn = false;
						});
                        
                        w.d.down.on("vmousedown", function(e) {
							gIsSpinboxHoldOn = true;
							//e.preventDefault();
							if ( !w.disabled ) {
 								if (o.type === 'num') {
									SpinboxDecreaseVal(this,o.dmin,o.dmax);
								} else if (o.type === 'ampm') {
									tmp = w.d.input.val();
									if (tmp === "AM") w.d.input.val("PM");
									else  w.d.input.val("AM");
									w.d.input.trigger('change');
                                }
							}
                        });
						w.d.down.on("vmouseup", function(e) {
							gIsSpinboxHoldOn = false;
						});
                        
                        if ( typeof $.event.special.mousewheel !== 'undefined' ) { // Mousewheel operation, if plugin is loaded
                                w.d.input.on('mousewheel', function(e,d) {
                                        e.preventDefault();
                                        if ( !w.disabled ) {
                                                tmp = parseInt(w.d.input.val(),10) + ((d<0)?-1:1);
                                                if ( tmp >= o.dmin && tmp <= o.dmax ) { 
                                                        w.d.input.val(tmp); 
                                                        w.d.input.trigger('change');
                                                }
                                        }
                                });
                        }
                },
                disable: function(){
                        // Disable the element
                        this.d.input.attr("disabled",true);
                        this.d.input.addClass("ui-disabled").blur();
                        this.disabled = true;
                },
                enable: function(){
                        // Enable the element
                        this.d.input.attr("disabled", false);
                        this.d.input.removeClass("ui-disabled");
                        this.disabled = false;
                }
        });
          
        $( document ).on( "pagecreate create", function( e ){
                $(".spinbox").spinbox();
        });
})( jQuery );

var gIsSpinboxHoldOn = false;
function SpinboxIncreaseVal(el,inMin,inMax) {
	if (!gIsSpinboxHoldOn) return;
	
	//get input
	$inpt = $(el).siblings(".ui-input-text").find("input");
	var tmp = parseInt($inpt.val(),10) + 1;
	if ( tmp <= inMax ) { 
		$inpt.val(tmp);
		$inpt.trigger('change');
	} else { //loop around
		$inpt.val(inMin);
		$inpt.trigger('change');
	}
	if (gIsSpinboxHoldOn) { setTimeout(function() { SpinboxIncreaseVal(el,inMin,inMax) }, 100); }
}
function SpinboxDecreaseVal(el,inMin,inMax) {
	if (!gIsSpinboxHoldOn) return;
	
	//get input
	$inpt = $(el).siblings(".ui-input-text").find("input");
	var tmp = parseInt($inpt.val(),10) - 1;
	if ( tmp >= inMin ) { 
		$inpt.val(tmp);
		$inpt.trigger('change');
	} else if (inMax !== Number.MAX_VALUE) { //loop around, but only if a reasonable number!
		$inpt.val(inMax);
		$inpt.trigger('change');
	}
	if (gIsSpinboxHoldOn) { setTimeout(function() { SpinboxDecreaseVal(el,inMin,inMax) }, 100); }
}