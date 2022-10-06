/********************************************/
/* AJAX/API calls for blocks                */
/********************************************/

var BlockController = function(options) {
    BaseController.call(this,options);
};
BlockController.prototype = Object.create(BaseController.prototype);
BlockController.prototype.constructor = BlockController;

_.extend(BlockController.prototype, {

    init: function() {
        //this.appView = blockview;
    },
    
    showBlock: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) {
            enableButton(".btnSubmit", doSubmit);
            return false;
        }

        var infmsg = '';
        $xml.find("infmsg").each(function() {
            infmsg = $(this).text();
        });
        
        var blk = null;
        $xml.find("block").each(function() {
            blk = new Block($(this));
        });
        blockview.afterAdd(infmsg, blk);
    },
    addBlock: function(inBlock) {
        var str = '<addblock>'
            + this.buildSessionTag()
            + inBlock.toXML()
            + "</addblock>";
        this.callAJAX(str,this.showBlock);
    },
    editBlock: function(inBlock,inWhichBlocks) {
        var str = '<editblock>'
            + this.buildSessionTag()
            + this.buildTag('whichblocks',inWhichBlocks || '')
            + inBlock.toXML()
            + "</editblock>";
        this.callAJAX(str,this.showBlock);
    },
    
    showDeletedBlock: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        if (this.deleteFrom === 'blockview') {
            this.newPageURL = getPageURL('viewcalendar',{calid: this.calID, sdt: getURLDateTime(this.sdt)});
            this.doInfMsgCheck($xml,this.goToNewPage);
        } else {
            viewcalview.showBlockDelete($xml.find("blockid").text());
        }
    },
    deleteBlock: function(inBlockID,inCalID,inCancelText,inWhichBlocks,fromWhere) {
        var str = '<deleteblock>'
            + this.buildSessionTag()
            + this.buildTag('whichblocks',inWhichBlocks || '')
            + this.buildTag('blockid',inBlockID)
            + this.buildTag('canceltext',inCancelText)
            + '</deleteblock>';
        
        this.calID = inCalID;
        if (typeof gBlock !== 'undefined')
            this.sdt = gBlock.get('startdatetime');
        this.deleteFrom = fromWhere || "viewcalview";
        this.callAJAX(str,this.showDeletedBlock);
    },
    
    showSyncedStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;

        //syncblock
        var blkid = $xml.find("syncblock blockid").text();
        var ical = $xml.find("syncblock ical").text();

        viewcalview.showBlockSync(blkid,ical);
    },
    syncBlock: function(inUserID,inWhichBlocks,inBlockID) {
        var which = inWhichBlocks || "instance"; //instance, series, seriesfromtoday
        var str = '<syncblock>'
            + this.buildSessionTag()
            + this.buildTag('userid',inUserID)
            + this.buildTag('whichblocks',which)
            + this.buildTag('blockid',inBlockID)
            + '</syncblock>';
        this.callAJAX(str,this.showSyncedStatus);
    },
    
    showLockedStatus: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //lockblock
        $xml.find("lockblock").each(function() {
            var blkid = $(this).find("blockid").text();
            var avail = isTrue($(this).find("makeavailable").text()) || false;
            viewcalview.setBlockAvailability(blkid, avail);
        });
    },
    lockBlock: function(inBlockID,inIsAvail) {
        var str = '<lockblock>'
            + this.buildSessionTag()
            + this.buildTag('blockid',inBlockID)
            + this.buildTag('makeavailable',getServerBoolean(inIsAvail))
            + '</lockblock>';
        this.callAJAX(str,this.showLockedStatus);
    },
    
    /*
     * Slot
     */
    showLockedSlot: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
            
        //addappointment
        $xml.find('appt').each(function() {
            var blockid = $(this).find("blockid").text();
            var apptid = $(this).find("appointmentid").text();
            var sdt = $(this).find("startdatetime").text();
            var edt = $(this).find("enddatetime").text();
            var avail = isTrue($(this).find("available").text());
            viewcalview.showSlotLocked(blockid,apptid,sdt,edt,avail);
        });
    },
    //locking a slot makes a dummy appointment
    lockSlot: function(inCalID,inBlockID,inStartDateTime,inEndDateTime) {
        var str = '<addappointment>'
            + this.buildSessionTag()
            + '<appt>'
            + this.buildTag('startdatetime',formatDateTimeforServer(inStartDateTime))
            + this.buildTag('enddatetime',formatDateTimeforServer(inEndDateTime))
            + this.buildTag('blockid',inBlockID)
            + this.buildTag('calendarid',inCalID)
            + this.buildTag('available',getServerBoolean(false))
            + this.buildTag('force',getServerBoolean(true))
            + '</appt>'
            + '</addappointment>';
        this.callAJAX(str,this.showLockedSlot);
    },
    
    showUnlockedSlot: function(data) {
        var $xml = $(data);
        if (this.doErrorCheck($xml)) return false;
        
        //deleteappointment
        var apptid = $xml.find("deleteappointment appointmentid").text();
        viewcalview.showSlotUnlocked(apptid);
    },
    unlockSlot: function(inApptID) {
        var str = '<deleteappointment>'
            + this.buildSessionTag()
            + this.buildTag('appointmentid',inApptID)
            + '</deleteappointment>';
        this.callAJAX(str,this.showUnlockedSlot);
    }

    
});

//declare controller instance
var blockController = new BlockController();
