//Set javascript variables based on media queries so that only certain javascript code runs at certain sizes.
var c_iSmallSize = 480; //up to this value
var c_iMediumSize = 768; //up to this value
var c_iLargeSize = 1900; //? or just not use?
var g_iScreenSize = c_iSmallSize; //default to small size, set actual size below.

function isSmallScreen() {
	return g_iScreenSize < c_iMediumSize;
}
function isMediumScreen() {
	return g_iScreenSize > c_iSmallSize && g_iScreenSize <= c_iMediumSize;
}
function isLargeScreen() {
	return g_iScreenSize > c_iMediumSize;
}

var mediaCheck = function(options) {
  var mq, mqChange, createListener, convertEmToPx, getPXValue,
      matchMedia = window.matchMedia !== undefined;
      
  if (matchMedia) {
    mqChange = function(mq, options) {
      if (mq.matches) {
        if ( typeof options.entry === "function" ) {
          options.entry();
        }
      } else if ( typeof options.exit === "function" ) {
        options.exit();
      }
	};
    
	//has matchMedia support
    createListener = function() {
      mq = window.matchMedia(options.media);
      mq.addListener(function() {
        mqChange(mq, options);
      });
	  window.addEventListener( "orientationchange", function() {
        mq = window.matchMedia( options.media );
        mqChange( mq, options );
      }, false );
      mqChange(mq, options);
    };
    
    createListener();
  } else {
// pageWidth is initialized during initial match
    var pageWidth,
        breakpoints = {};

    mqChange = function( mq, options ) {
      if ( mq.matches ) {
        if ( typeof options.entry === "function" && ( breakpoints[options.media] === false || breakpoints[options.media] === null )) {
          options.entry();
        }
      } else if ( typeof options.exit === "function" && ( breakpoints[options.media] === true || breakpoints[options.media] === null )) {
        options.exit();
      }

      breakpoints[options.media] = mq.matches;
    };

    convertEmToPx = function( value ) {
      var emElement;

      emElement = document.createElement( "div" );
      emElement.style.width = "1em";
      document.body.appendChild( emElement );

      return value * emElement.offsetWidth;
    };

    getPXValue = function( width, unit ) {
      var value;

      switch ( unit ) {
      case "em":
        value = convertEmToPx( width );
        break;
      default:
        value = width;
      }

      return value;
    };

    // Create list of breakpoints
    for ( var i in options ) {
      breakpoints[options.media] = null;
    }

    // No matchMedia support
    var mmListener = function() {
      var parts = options.media.match( /\((.*)-.*:\s*([\d\.]*)(.*)\)/ ),
          constraint = parts[ 1 ],
          value = getPXValue( parseInt( parts[ 2 ], 10 ), parts[3] ),
          fakeMatchMedia = {},
          clientWidth = document.documentElement.clientWidth;


      // scope this to width changes to prevent small-screen scrolling (browser chrome off-screen)
      //   from triggering a change
      if (pageWidth !== clientWidth) {
        fakeMatchMedia.matches = constraint === "max" && value > clientWidth ||
                                 constraint === "min" && value < clientWidth;
        mqChange( fakeMatchMedia, options );

        // reset pageWidth
        pageWidth = clientWidth;
      }
    };

    if (window.addEventListener) {
      window.addEventListener("resize", mmListener);
    } else if (window.attachEvent) {
      window.attachEvent("onresize", mmListener);
    }
    mmListener();
  }
};


$(function() {
  
	mediaCheck({
		media: '(max-width: 480px)',
		entry: function() {
			g_iScreenSize = 480;
		},
		exit: function() {
		}
	});
	mediaCheck({
		media: '(min-width: 480px)',
		entry: function() {
			g_iScreenSize = 480;
		},
		exit: function() {
		}
	});
	mediaCheck({
		media: '(min-width: 768px)',
		entry: function() {
			g_iScreenSize = 768;
			
			if (typeof blockview !== 'undefined') {
				$(".localnav a").text("Delete Block");
			}
			if (typeof calendarconfigview !== 'undefined') {
				$(".localnav #cancelbutton").text("Remove Calendar");
				$(".localnav #syncbutton").text("Sync Calendar");
			}
			if (typeof apptview !== 'undefined' || typeof blockview !== 'undefined' || typeof calendarconfigview !== 'undefined') {
				$(".localnav").controlgroup({ mini: true });
			} else
				$(".localnav").controlgroup({ mini: false });
				
			//Make Appointment - block display	
			if (typeof makeapptview !== 'undefined') {	
				if (gShownCalViewID) {
					makeapptview.calendarViews[gShownCalViewID].placeBlockDisplay(false);
				}
			}
			
			//My Appts: expand filter section
			if (typeof myapptsview !== 'undefined') {			
				$("#divApptFilter").collapsible("expand");
			}
			
		},
		exit: function() {
			
			//change top action buttons from text to icons
			if (typeof blockview !== 'undefined') {
				$(".localnav a").text("Delete");
			}
			if (typeof calendarconfigview !== 'undefined') {
				$(".localnav #cancelbutton").text("Remove Cal");
				$(".localnav #syncbutton").text("Sync Cal");
			}
			if (typeof apptview !== 'undefined' || typeof blockview !== 'undefined' || typeof calendarconfigview !== 'undefined') {
			} else {
			}
			$(".localnav").controlgroup({ mini: true });


			//View Calendar: remove calendar expand button
			if (typeof viewcalview !== 'undefined') {
				$("#divCalSelect").collapsible("collapse");
			}
			
			//Make Appointment - block display	
			if (typeof makeapptview !== 'undefined') {	
				if (gShownCalViewID) {
					makeapptview.calendarViews[gShownCalViewID].placeBlockDisplay(true);
				}
			}
			
			//My Appts: collapse filter section
			if (typeof myapptsview !== 'undefined') {
				$("#divApptFilter").collapsible("collapse");
			}
			
		}
	});
	mediaCheck({
		media: '(min-width: 1024px)',
		entry: function() {
			g_iScreenSize = 769;
		},
		exit: function() {
		}
	});
});