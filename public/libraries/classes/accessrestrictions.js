/*
 * AccessRestrictions
 */
var AccessRestrictions = function(options) {
	//set up 'empty' AR
	var arProto = AccessRestrictions.prototype;
	_.each(Object.keys(arProto.defaults), function(key) {
		arProto.set(key,arProto.defaults[key]);
	});
    WASEObject.call(this,options);
};
AccessRestrictions.prototype = Object.create(WASEObject.prototype);
AccessRestrictions.prototype.constructor = AccessRestrictions;
_.extend(AccessRestrictions.prototype, {
    defaults: {
        viewaccess: 'limited', //values: open,limited,restricted,private
        viewulist: [], //userids
        viewclist: [], //courseids
        viewglist: [], //groupids
        viewslist: [], //statuses
        makeaccess: 'limited', //values: open,limited,restricted,private
        makeulist: [], //userids
        makeclist: [], //courseids
        makeglist: [], //groupids
        makeslist: [], //statuses
        showappinfo: false,
    },
    setFromXML: function($xml) {
        this.set('viewaccess',this.getTagText($xml,'viewaccess'));
        var viewUsers = this.getTagText($xml,'viewulist');
        this.set('viewulist', !isNullOrBlank(viewUsers) ? viewUsers.split(',') : []);
        var viewCourses = this.getTagText($xml,'viewclist');
        this.set('viewclist', !isNullOrBlank(viewCourses) ? viewCourses.split(',') : []);
        var viewGroups = this.getTagText($xml,'viewglist');
        var viewGroupArray = !isNullOrBlank(viewGroups) ? viewGroups.split(',') : [];
        //replace all '|' with ','
        _.each(viewGroupArray, function(v,i) {
        	viewGroupArray[i] = viewGroupArray[i].replace(/[|]/g,',');
        });
        this.set('viewglist', viewGroupArray);
        var viewStatuses = this.getTagText($xml,'viewslist');
        this.set('viewslist', !isNullOrBlank(viewStatuses) ? viewStatuses.split(',') : []);

        this.set('makeaccess',this.getTagText($xml,'makeaccess'));
        var makeUsers = this.getTagText($xml,'makeulist');
        this.set('makeulist', !isNullOrBlank(makeUsers) ? makeUsers.split(',') : []);
        var makeCourses = this.getTagText($xml,'makeclist');
        this.set('makeclist', !isNullOrBlank(makeCourses) ? makeCourses.split(',') : []);
        var makeGroups = this.getTagText($xml,'makeglist');
        var makeGroupArray = !isNullOrBlank(makeGroups) ? makeGroups.split(',') : [];
        //replace all '|' with ','
        _.each(makeGroupArray, function(m,i) {
        	makeGroupArray[i] = makeGroupArray[i].replace(/[|]/g,',');
        });
        this.set('makeglist', makeGroupArray);
        var makeStatuses = this.getTagText($xml,'makeslist');
        this.set('makeslist', !isNullOrBlank(makeStatuses) ? makeStatuses.split(',') : []);

        this.set('showappinfo',isTrue(this.getTagText($xml,'showappinfo')));
    },
    getArrayXML: function(key) {
    	var val = this.get(key);
        if (key === 'viewglist' || key === 'makeglist') {
        	//replace all ',' with '|'
        	_.each(val, function(v,i) {
        		val[i] = val[i].replace(/,/g,'|');
        	});
        }
        val = '<'+key+'>' + this.escapeSpecialChars(val.join(',')) + '</'+key+'>';
        return val;
    },
});



//Drawing Access Restrictions on a page
var gAccessRestrictions = null;
var gARdisplay = null;

AccessRestrictions.getItemHTMLID = function(inValue) {
	return 'id_'+ inValue.replace(/ /g,'_');
};
AccessRestrictions.loadValidRestriction = function(isValid,restrictWhat,restrictBy,restrictVal) {
	if (isValid) {
		//get correct authorized restriction section
		var $parAuthorized = gARdisplay.$display.find(".restrictedValues.restrictWhat_"+restrictWhat);
		var $restrictSection = $parAuthorized.find(".restrictedby_"+restrictBy);
		
		//check to make sure we don't already have this restriction, and if not, add it/show it
		var fieldName = restrictWhat+restrictBy.slice(0,1)+"list";
		var list = gAccessRestrictions[fieldName];
		if (list.indexOf(restrictVal) < 0) {
			list.push(restrictVal);
			$parAuthorized.find(".noneselected").hide();
			$restrictSection.find('.authlist').append(gARdisplay.drawAuthorizedRow(restrictVal, restrictWhat, restrictBy));
			$restrictSection.addClass('visible');
			$parAuthorized.find('.authsection.visible:not(:last) p').show();
			$parAuthorized.find('.authsection.visible:last p').hide();
		}
		
		//in the search results (if any), change to be authorized
		var $parSearch = $parAuthorized.prev();
        var $searchItem = $([]);
        $parSearch.find('.authitem').each(function() {
            if ($(this).data("val") === restrictVal)
                $searchItem = $(this);
		});
		$searchItem.toggleClass('authorized',true);
	} else {
		alert("INVALID!");
	}
};

AccessRestrictions.loadAccessRestrData = function(ar) {
	//set up default access restrictions
	gAccessRestrictions = new AccessRestrictions();

	var va = ar.get('viewaccess') || 'limited';
	$('#cgRestrict_view :radio[value="' + va + '"]').siblings('label').trigger('vclick'); //Trigger click and get jqm formatting
	if (ar.get('viewaccess') === "restricted") {
		_.each(['viewulist','viewclist','viewglist','viewslist'], function(listName,i) {
			_.each(ar.get(listName), function(listVal) {
				AccessRestrictions.loadValidRestriction(true,'view',restrictByVals[i],listVal);
			});
		});
	}
	gAccessRestrictions.set('viewaccess', va);
	
	var ma = ar.get('makeaccess') || 'limited';
	$('#cgRestrict_make :radio[value="' + ma + '"]').siblings('label').trigger('vclick');
	if (ar.get('makeaccess') === "restricted") {
		_.each(['makeulist','makeclist','makeglist','makeslist'], function(listName,i) {
			_.each(ar.get(listName), function(listVal) {
				AccessRestrictions.loadValidRestriction(true,'make',restrictByVals[i],listVal);
			});
		});
	}
	gAccessRestrictions.set('makeaccess', ma);

	$('#cgShowApptInfo :radio[value="' + ar.get('showappinfo') + '"]').siblings('label').trigger('vclick');
	gAccessRestrictions.set('showappinfo', ar.get('showappinfo'));
};



var restrictByVals = ['user','course','group','status'];

var AccessRestrictionsDisplay = function(options) {
	this.$display = null;
	this.labels = {};
	_.bindAll(this, 'checkSearch', 'checkSearchEnter', 'clearResults', 'searchButton', 'setSearchList');
};
_.extend(AccessRestrictionsDisplay.prototype, {
	setSearchError: function(errArray) {
		var $par = AccessRestrictions.$searchSection;
		this.doClearResults($par);
		var errString = '';
		_.each(errArray, function(err) {
	        var errc = isNullOrBlank(err.get('errorcode')) ? '' : ' (' + err.get('errorcode') + ') ';
	        errString += err.get('errortext') + errc + '\n';
	    },this);	
		$par.find(".resultsSection").append($("<hr>"), $("<div>",{html: "<i>"+errString+"</i>"})).show();
		$('.loader').hide();
	},
	setSearchList: function(inList, searchString) {
		var $par = AccessRestrictions.$searchSection;
		this.doClearResults($par);
		
		var $results = $("<div>",{class: "resultList"});
		$par.find(".resultsSection").append($("<hr>"), $results);
		
		var $item = $("<div>",{});
		if (!inList.length || inList.length === 1 && isNullOrBlank(inList[0])) {
			var txt = 'no results found';
			if (searchString.length < 3) txt = 'Please enter 3 or more characters to search';
			$item.html("<i>" + txt + "</i>");
			$results.append($item).show();
		} else {
			$results.show();
			_.each(inList, function(val,i) {
				//if the value is already in authorized restrictions, show it with delete
				var dta = $par.data();
				var listName = dta.restrictWhat+dta.restrictBy.slice(0,1)+"list";
				//split apart the results val
				var dataval = val;
				if(dta.restrictBy === 'user') {
                    dataval = val.split(',')[0];
				}
				if (gAccessRestrictions[listName].indexOf(dataval) >= 0) { //already authorized, show as authorized
					$results.append(this.drawAuthorizedRow(val, dta.restrictWhat, dta.restrictBy));
				} else { //show with 'add' button
					$results.append(this.drawRestrictedItem(val, dta.restrictWhat, dta.restrictBy, false));
				}
			},this);
		}
		
		$('.loader').hide();
	},
	//search
	doClearResults: function($parSearch) {
		$parSearch.find('.resultsSection').empty();
	},
	clearResults: function(e) {
		this.doClearResults($(e.target).parents(".restrictedSearch"));
	},
	doSearch: function($el) {
		$('.loader').show();
		var $par = $el.parents(".restrictedSearch");
		var dta = $par.data();
		var restrBy = dta['restrictBy'] || 'user';
		AccessRestrictions.$searchSection = $par;
		var searchVal = restrBy === 'status' ? '' : $el.val();
		arController.searchRestriction(dta['restrictWhat'], restrBy, searchVal);
	},
    checkSearchEnter: function(e) {
        if (e.keyCode === 13) {
            //user clicked 'enter', do the search
            this.doSearch($(e.target));
            return false;
        }
    },
    searchButton: function(e) {
    	var $txt = $(e.target).parents('.restrictedSearch');
    	this.doSearch($(e.target).parents('.restrictedSearch').find('.ui-input-search input'));
    },
    checkSearch: function(e) {
        //auto-search
    	var $el = $(e.target);
        var val = $el.val();
        //if status, just provide all of the results.
		var isStatus = $el.parents('.restrictedSearch').data()['restrictBy'] === 'status';
        if (!isStatus && (isNullOrBlank(val) ||  $.trim(val).length < 4)) {
            this.doClearResults($el.parents(".restrictedSearch"));
        }
        else {
            this.doSearch($el);
        }
	},
	drawRestrictedValues: function(restrictWhat, $parent) {
		//-restricted values
		var $valsContainer = $("<div>",{class: "restrictedValues restrictWhat_"+restrictWhat})
			.data({'restrictWhat': restrictWhat});
		$parent.append($valsContainer);
		$valsContainer.append(
			$("<h2>", {text: "Selected Restrictions"}),
			$("<div>", {class: "noneselected", text: "None Selected. Please search for and select restrictions."})
		);
		
		var restrictions = {
			'user': 'Users',
			'course': 'Courses',
			'group': 'Groups',
			'status': 'Statuses'
		};
		_.each(restrictByVals, function(restrBy) {
			var $valSection = $("<div>", {class: "authsection restrictedby_"+restrBy}).data({"restrictBy": restrBy, "restrictWhat": restrictWhat});
			
			$valSection.append(
				$("<div>", {class: "authlist"}).append($("<h3>", {text: "Authorized " + restrictions[restrBy]})),
				$("<p>",{text: "OR"})
			);
			$valsContainer.append($valSection);
		});
		
	},
	drawRestrictWhatRestricted: function(restrictWhat, $parent) {
		//draw restricted section
		var $restrictedContainer = $("<div>",{class: "restrictedContainer"});
		$parent.append($restrictedContainer);
		
		//-restriction search
		var $searchContainer = $("<div>",{class: "restrictedSearch"})
			.data({'restrictWhat': restrictWhat});
		$restrictedContainer.append($searchContainer);
		var $cgRestrBy = $("<fieldset>", {id: "cgRestrictBy_"+restrictWhat, "data-role": "controlgroup", "data-type": "horizontal"});
		$searchContainer.append(
			$("<h2>", {text: "Restrict access based on..."}),
			$cgRestrBy
		);
		//draw radio options
		_.each(restrictByVals, function(val) {
			var nm = "radRestrBy_"+restrictWhat;
			var id = nm+capitalize(val);
			var $rad = $("<input>", {type: "radio", name: nm, id: id, value: val});
			var $label = $("<label>", {id: "lblRestrict_"+restrictWhat, for: id, text: capitalize(val)});
			$cgRestrBy.append($rad, $label);
			$rad.checkboxradio();
			$rad.checkboxradio('refresh');
			
			//bind event handler
			var vw = this;
			$rad.bind("click",function () {
				var val = $(this).val();
				var instructions = {
					'user': gParms['USERPROMPT'] || 'Enter user',
					'course': gParms['COURSEIDPROMPT'] || 'Enter course',
					'group': gParms['GROUPIDPROMPT'] || 'Enter group',
					'status': gParms['STATUSPROMPT'] || 'Enter status'
				};
				var $par = $(this).parents(".restrictedSearch");
				vw.doClearResults($par);
				$par.data({'restrictBy': val});
				$par.find(".instructions").text(instructions[val] + ", then click the plus to add");
			});
		}, this);
		$cgRestrBy.controlgroup();
		$cgRestrBy.controlgroup("refresh");
		
		//search area
		var searchlabel = "Search for Restriction";
		var id = 'txtRestrict'+restrictWhat;
		var $searchInstructions = $("<div>",{class: 'instructions'});
		var $searchLabel = $("<label>", {for: id, class: "ui-hidden-accessible", text: searchlabel+":"});
		var $searchText = $("<input>", {type: 'text', id: id, class: "highlight", title: searchlabel, "data-type": "search"});
		var $searchButton = $("<a>", {id: id+'GO', href: "#", class: "ui-btn ui-btn-inline goButton", text: "Go"});
        $searchContainer.append(
			$searchInstructions,
			$searchLabel,
			$searchText,
			$searchButton
		);
		$searchText.textinput();
		$searchText.textinput('refresh');
		$searchText.on("keypress", this.checkSearchEnter);
		$searchText.on("input", this.checkSearch);
		$searchButton.on("click", this.searchButton);
		$('.ui-input-clear').on('tap', this.clearResults);
		
		//results area
		$searchContainer.append(
			$("<div>",{class: "resultsSection restrictWhat_"+restrictWhat})
		);
		
		//-currently set restrictions
		this.drawRestrictedValues(restrictWhat, $restrictedContainer);

		//default radio to user - must be after instructions, results area are put in the DOM
		$cgRestrBy.find(':radio[value="user"]').siblings('label').trigger('click');
	},
	drawRestrictWhatSection: function(restrictWhat, legendText, $parent) {
		var $ARsection = $("<div>", {id: "divRestrict_"+restrictWhat, class: "ui-field-contain restrictwhat"});
		$parent.append($ARsection);
		
		//draw radio option group
		var $controlGroup = $("<fieldset>", {id: "cgRestrict_"+restrictWhat, "data-role": "controlgroup"});
		$controlGroup.append($("<legend>", {html: legendText + ":"}));
		$ARsection.append($controlGroup);
		
		//NOTE: gParms must be set
		var showCourseRestr = gParms["COURSELIM"] === '1';
		var showGroupRestr = gParms["ADRESTRICTED"] === '1';
		var showStatusRestr = !isNullOrBlank(gParms["STATUS_ATTRIBUTE"]);
		var restrictText = '(Specify users';
		if (showCourseRestr) restrictText += ' and/or courses';
		if (showGroupRestr) restrictText += ' and/or groups';
		if (showStatusRestr) restrictText += ' and/or status';
		restrictText += ')';
		
		//draw radio options
		var arVals = ['open','limited','private','restricted'];
		var arText = { 
			'open': 'Open (Anyone)',
			'limited': "Limited (Anyone with " + gParms["INSNAME"] + " " + gParms["NETID"] + ")",
			'private': 'Private (Owner and manager(s) ONLY)',
			'restricted': 'Restricted '+restrictText
		};
		_.each(arVals, function(val) {
			var nm = "radRestrict_"+restrictWhat;
			var id = nm+capitalize(val);
			var $rad = $("<input>", {type: "radio", name: nm, id: id, value: val});
			var $label = $("<label>", {id: "lblRestrict_"+restrictWhat, for: id, text: arText[val]});
			$controlGroup.append($label,$rad);
			$rad.checkboxradio();
			$rad.checkboxradio('refresh');
			
			//bind event handler
			$rad.bind("click",function () {
				$(this).parents(".restrictwhat").find(".restrictedContainer").toggle($(this).val() === 'restricted' && $(this).is(":checked"));
			});
		}, this);
		$controlGroup.controlgroup();
		$controlGroup.controlgroup("refresh");
		
		this.drawRestrictWhatRestricted(restrictWhat, $controlGroup);
	},
	draw: function() {
		var $AR = $("<div>",{class: ""});
		$(".arContent").append($AR);

		//draw loader icon
		var $loader = $("<div>",{class: "loader ui-loader", html: "<span class='ui-icon ui-icon-loading'></span>"});
		$AR.append($loader);
		
		//draw View Block section
		this.drawRestrictWhatSection('view','Who can View Blocks?',$AR);
		
		//draw Make Appt section
		var appthings = this.labels['APPTHINGS'] || g_ApptsText;
		this.drawRestrictWhatSection('make','Who can Create '+capitalize(appthings)+'?',$AR);
		
		//Who can see appt details?
		var $ApptDetails = $("<div>", {id: "divShowApptInfo", class: "ui-field-contain"});
		$AR.append($ApptDetails);
		//draw radio option group
		var $controlGroup = $("<fieldset>", {id: "cgShowApptInfo", "data-role": "controlgroup"});
		$controlGroup.append($("<legend>", {html: 'Who can see <span class="appName">Appointment</span> Details?:'}));
		$ApptDetails.append($controlGroup);
		var nm = "radShowApptInfo";
		var $radFalse = $("<input>", {type: "radio", name: nm, id: "radFalse", value: "false"});
		var $labelFalse = $("<label>", {id: "lblFalse", for: "radFalse", html: 'Only the <span class="appName">Appointment</span> Maker, Block Owner, and Managers'});
		$controlGroup.append($radFalse, $labelFalse);
		$radFalse.checkboxradio();
		$radFalse.checkboxradio('refresh');
		var $radTrue = $("<input>", {type: "radio", name: nm, id: "radTrue", value: "true"});
		var $labelTrue = $("<label>", {id: "lblTrue", for: "radTrue", text: 'Anyone who can view the Block'});
		$controlGroup.append($radTrue, $labelTrue);
		$radTrue.checkboxradio();
		$radTrue.checkboxradio('refresh');
		$controlGroup.controlgroup();
		$controlGroup.controlgroup("refresh");
		
		this.$display = $AR;
	},

	getItemDisplay: function(val,arr) {
        var displayVal = arr[0];
        if (!isNullOrBlank(arr[1])) {
            var emailStart = arr[1].split('@')[0];
            if (arr[0] !== arr[1] && emailStart !== val)
                displayVal += " (" + arr[1] + ")";
        }
        return displayVal;
	},
	drawRestrictedItem: function(inVal, inRestrictWhat, inRestrictBy, inIsAuthorized) {
		var itemClass = 'authitem';
		if (inIsAuthorized) itemClass += ' authorized';

		var val = inVal;
		var displayVal = inVal;
		if (inRestrictBy === 'user') { //users have a comma-separated list value: userid, email, user name
			var arr = val.split(',');
			val = arr[0];
			displayVal = this.getItemDisplay(val,arr);
		}
		var $item = $("<div>", {class: itemClass}).data({'val': val});
		
		var $addBtn = $("<a>", {href: "#", "data-ajax": "false", title: "Add "+inRestrictBy, text: "Add "+inRestrictBy, class: "ui-btn ui-corner-all ui-icon-plus ui-btn-icon-notext noborder"});
		$addBtn.on("click", function() {
			arController.isValidRestriction(inRestrictWhat, inRestrictBy, val);
		});
		var $addCol = $("<div>", {class: 'colAdd'}).append($addBtn);
		$item.append($addCol);

		var $deleteBtn = $("<a>", {href: "#", "data-ajax": "false", title: "Delete "+inRestrictBy, text: "Delete "+inRestrictBy, class: "ui-btn ui-corner-all ui-icon-trash ui-btn-icon-notext noborder"});
		$deleteBtn.on("click", function() {
			var $authItem = $(this).parents('div.authitem');
			
			//get the data val to remove from the appropriate accessrestrictions array
			var dataval = $authItem.data("val");
			//remove it from the underlying data list
			var listName = inRestrictWhat+inRestrictBy.slice(0,1)+"list";
			gAccessRestrictions[listName] = jQuery.grep(gAccessRestrictions[listName], function(itemID) {
				return itemID !== dataval;
			});

			var $par = $authItem.parents('.restrictedContainer');
			//remove it from the authorized list
			var $restrSection = $par.find('.authsection.restrictedby_'+inRestrictBy);
			var $listItem = null;
			$restrSection.find('.authitem').each(function() {
				if ($(this).data("val") === dataval)
					$listItem = $(this);
			});
			$listItem.fadeOut("slow", function() {
				$(this).remove();
			});
			
			//hide entire authsection if no items
			if (gAccessRestrictions[listName].length === 0) {
				$restrSection.fadeOut("slow", function() {
					$(this).removeClass('visible');
					$par.find('.restrictedValues .authsection.visible:last p').hide();
				});
			}
			
			//if no sections at all, show 'none selected'
			var totalNum = 0;
			_.each(["u","c","g","s"], function(by) {
				totalNum += gAccessRestrictions[inRestrictWhat+by+"list"].length;
			});
			$par.find(".noneselected").toggle(totalNum === 0);
			
			//if it exists in the search results list, change it back to 'unauthorized'
            var $searchItem = $();
            $par.find('.restrictedSearch .authitem').each(function() {
                if ($(this).data("val") === dataval)
                    $searchItem = $(this);
            });
            $searchItem.removeClass('authorized');
		});
		var $deleteCol = $("<div>", {class: 'colDelete'}).append($deleteBtn);
		$item.append($deleteCol);

        $item.append($("<span>",{text: displayVal}));

        return $item;
	},
	drawAuthorizedRow: function(inVal, inRestrictWhat, inRestrictBy) {
		return this.drawRestrictedItem(inVal, inRestrictWhat, inRestrictBy, true);
	}
});




/********************************************/
/* AJAX/API calls for cal config view       */
/********************************************/

var ARController = function(options) {
    BaseController.call(this,options);
};
ARController.prototype = Object.create(BaseController.prototype);
ARController.prototype.constructor = ARController;

_.extend(ARController.prototype, {
    showValidRestriction: function(data, callback) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //check restriction
        var $restr = $xml.find(this.restrictBy);
        var isvalid = isTrue($restr.children("isvalid").text());
        var restrVal = $restr.children(this.restrictBy+'id').text();
        AccessRestrictions.loadValidRestriction(isvalid,this.restrictWhat,this.restrictBy,restrVal);
    },
    isValidRestriction: function(restrictWhat, restrictBy, restrictVal) {
    	//restrictWhat: view, make
    	//restrictBy: user, course, group, status
    	this.restrictWhat = restrictWhat;
    	this.restrictBy = restrictBy;
    	var str = "";
    	         
        switch(restrictBy) {
    	case 'user':
    		str = "<validateusers><user>"
                + this.buildEscapeTag('userid',restrictVal)
                + "</user></validateusers>";
    		break;
    	case 'course':
            str = "<validatecourses><course>"
                + this.buildEscapeTag('courseid',restrictVal)
                + "</course></validatecourses>";
    		break;
    	case 'group':
            str = "<validategroups><group>" 
                + this.buildEscapeTag('groupid',restrictVal)
                + "</group></validategroups>";
    		break;
    	case 'status':
            str = "<validatestatuses><status>" 
                + this.buildEscapeTag('statusid',restrictVal)
                + "</status></validatestatuses>";
    		break;
    	}
        this.callAJAX(str, this.showValidRestriction);
    },

    showSearchResults: function(data,callback) {
        var $xml = $(data);
        //check for errors
        var errs = [];
        $xml.find("error").each(function() {
            var errc = $(this).children("errorcode").text();
            if (errc !== "" && errc !== "0") {
                errs.push(new Error({errorcode: parseInt(errc,10), errortext: $(this).children("errortext").text()}));
            }
        });
        if (errs.length > 0) {
        	gARdisplay.setSearchError(errs);
        	return false;
        }
        
        //getmatching- restrictBy
        var listName = {
        	'user': 'userlist',
        	'course': 'courselist',
        	'group': 'grouplist',
        	'status': 'namelist'
        };
        var $resultlist = $xml.find(listName[this.restrictBy]);
        var arrResultList = JSON.parse($resultlist.text());
        gARdisplay.setSearchList(arrResultList, $xml.find('searchstring').text());
    },
    searchRestriction: function(restrictWhat, restrictBy, inString) {
    	//restrictWhat: view, make
    	//restrictBy: user, course, group, status
    	this.restrictWhat = restrictWhat;
    	this.restrictBy = restrictBy;
    	var tagName = {
    		'user': 'getmatchingusers',
    		'course': 'getmatchingcourses',
    		'group': 'getmatchinggroups',
    		'status': 'getmatchingstatuses'
    	};
    	var str = "<"+tagName[restrictBy]+">"
        	+ this.buildSessionTag()
            + this.buildEscapeTag('searchstring',inString)
            + "</"+tagName[restrictBy]+">";
        this.callAJAX(str, this.showSearchResults);
    }
});


//declare controller instance
var arController = new ARController();
