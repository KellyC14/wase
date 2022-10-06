<?php
/**
 * This class implements some class (static) methods that are useful for dealing with Exchange calendars.
 * 
 * 
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author Serge J. Goldstein, serge@princeton.edu
 * @author Kelly D. Cole, kellyc@princeton.edu
 * 
 */
class WaseExchange { 

    /**
     * This function inserts an appointment into a Exchange calendar.
     *
     *
     * @static
     *
     * @param WaseAppointment $app 
     *            the target appointment.
     *            
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @param string $userid
     *            the userid of the Exchange calendar owner.
     *            
     * @param string $email
     *            the email address on the Exchange server.
     *            
     * @return bool true if successful, else false.
     */
    static function addApp($app, $block, $userid, $email)
    {
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid 
        ));
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($app);
        
        /* Build our Exchange service(s) */
        list($ews1, $ews2, $order, $savedex) = self::buildService($userid);
        if (!$ews1 && !$ews2)
            return false;
      
        // Start building the request.
        $request = new \jamesiarmes\PhpEws\Request\CreateItemType();
        $request->Items = new \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType();
        
        // Start building the event.
        $event =  new \jamesiarmes\PhpEws\Type\CalendarItemType();
        
        // Set start/end datetimes.
        $event->Start = $dates . 'T' . $tstart . '+00:00';       
        $event->End = $datee . 'T' . $tend . '+00:00';
        
        // Set the location
        $event->Location = $block->location;
        
        // Set the subject.
        $event->Subject = WaseUtil::getParm('SYSID') . ' Appointment for ' . $app->userid . ' (' . $app->name . ') with ' . $block->userid . ' (' . $block->name . ')';
        // Set the start and end times. For Exchange 2007, you need to include the timezone offset.
        // For Exchange 2010, you should set the StartTimeZone and EndTimeZone properties. See below for
        // an example.
        
        // Set no reminders
        $event->ReminderIsSet = false;
        
        // Or use this to specify when reminder is displayed (if this is not set, the default is 15 minutes)
        // $event->ReminderMinutesBeforeStart = 30;
        
        // Build the body.
        $body = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this appointment, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.' . "\r\n";
        if ($app->purpose)
            $body = $app->purpose . "<br />" . $body;
       
        $event->Body = new \jamesiarmes\PhpEws\Type\BodyType();
        $event->Body->BodyType = \jamesiarmes\PhpEws\Enumeration\BodyTypeType::HTML;
        $event->Body->_ = $body;
        
        
        // Set the item class type (not required).
        $event->ItemClass = new \jamesiarmes\PhpEws\Enumeration\ItemClassType();
        $event->ItemClass->_ = \jamesiarmes\PhpEws\Enumeration\ItemClassType::APPOINTMENT;
        
        // Set the sensativity of the event (defaults to normal).
        $event->Sensitivity = new \jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType();
        $event->Sensitivity->_ = \jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType::NORMAL;
        
       // Add some categories to the event. 
        $event->Categories = new \jamesiarmes\PhpEws\ArrayType\ArrayOfStringsType();
        $event->Categories->String = array($block->NAMETHINGS);
        
        
        // Set the importance of the event.
        $event->Importance = new \jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType();
        $event->Importance->_ = \jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType::NORMAL;
        
        /* Determine if exchange integration is direct or by invitation */
        if (WaseUtil::getParm('EXCHANGE_DIRECT')) {
            /* The following code attempts to put the appointment directly into the target user's calendar */

            // Point to the target, shared calendar.
            $savedfolder = new \jamesiarmes\PhpEws\Type\TargetFolderIdType;            
            $savedfolder->AddressListId = new \jamesiarmes\PhpEws\Type\AddressListIdType;
            $savedfolder->AddressListId->Id = 'WASE Address';
            $savedfolder->DistinguishedFolderId = new \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType();
            $savedfolder->DistinguishedFolderId->Id = \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType::CALENDAR;
            $savedfolder->DistinguishedFolderId->Mailbox = new \jamesiarmes\PhpEws\Type\EmailAddressType;
            // Extract the leading email, if more than one specified
            list($firstemail, $rest) = explode(',', $email);
            $savedfolder->DistinguishedFolderId->Mailbox->EmailAddress = $firstemail;
            
            $request->SavedItemFolderId = $savedfolder;
            
            // Don't send meeting invitations.
            $request->SendMeetingInvitations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_TO_NONE;
           
        }
        else {
            /* The following code sends out meeting invitations, rather than putting the appointment directly into the target calendar */
            // Set meeting attendees
            
            $event->RequiredAttendees = new \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType();
            
            /* Invite the student (appointment for) */
            $attendes = new \jamesiarmes\PhpEws\Type\AttendeeType();
            $attendes->Mailbox = new \jamesiarmes\PhpEws\Type\EmailAddressType();
            // Extract the leading email, if more than one specified
            list($firstemail, $rest) = explode(',', $app->email);
            $attendes->Mailbox->EmailAddress = $firstemail;
            $attendes->Name = $app->name;
            $event->RequiredAttendees->Attendee[] = $attendes;

            // Invite the prof (appointment with) */          
            $attendep = new \jamesiarmes\PhpEws\Type\AttendeeType();
            $attendep->Mailbox = new \jamesiarmes\PhpEws\Type\EmailAddressType();
            // Extract the leading email, if more than one specified
            list($firstemail, $rest) = explode(',', $block->email);
            $attendep->Mailbox->EmailAddress = $firstemail;
            $attendep->Name = $block->name;
            $event->RequiredAttendees->Attendee[] = $attendep;
            
            $request->SendMeetingInvitations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;
           
        }
        
        // Add the event to the request.
        $request->Items->CalendarItem[] = $event;
        
        
        // Init our error msg
        $emsg = 'Unable to connect to Exchange server(s).';
         
        
        /* Now try to save the appointment into Exchange  try local then remote Exchange. */
        while($ews1 || $ews2) {
            try {              
                if ($ews1) 
                    $response = $ews1->CreateItem($request);
                else 
                    $response = $ews2->CreateItem($request);
                $respmsg = $response->ResponseMessages->CreateItemResponseMessage;
                if (is_array($respmsg))
                    $respmsg = $respmsg[0];
                if ($respmsg->ResponseClass == 'Success') {
                    // Save the id and the change key
                    $calitem = $respmsg->Items->CalendarItem;
                    if (is_array($calitem))
                        $calitem = $calitem[0];
                    $app->eid = $calitem->ItemId->Id;
                    $app->eck = $calitem->ItemId->ChangeKey;
                    // Save identity of working exchange server
                    if ($ews1) {
                        if ($savedex != $order[0]) WasePrefs::savePref($userid, 'savedex', $order[0]);
                    } else {
                        if ($savedex != $order[1]) WasePrefs::savePref($userid, 'savedex', $order[1]);                         
                    }
                    return true;
                }
                else  {
                    $emsg = $respmsg->MessageText;
                    if ($ews1) 
                        $ews1 = false;
                    else
                        $ews2 = false;
                }
            } catch (Exception $e) {
                $emsg = $e->getMessage();
                if ($ews1)
                    $ews1 = false;
                else 
                    $ews2 = false;
            }
        }
       
        WaseMsg::logMsg("Exchange add error:  unable to add app for calendar $wasecal->calendarid app $app->appointmentid: " . $emsg);
        return false;
        
    }

    /**
     * This function updates an appointment in a Exchange calendar.
     *
     *
     * @static
     *
     * @param WaseAppointment $app
     *            the target appointment.
     *            
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @param string $userid
     *            the userid of the Exchange calendar owner.
     *            
     * @param string $email
     *            the email address on the Exchange server.
     *            
     * @return bool true if successful, else false.
     */
    static function changeApp($app, $block, $userid, $email)
    {
        //  Don't try if we don't have an eid or an eck
        if (!$app->eid || !$app->eck) {
            WaseMsg::logMsg("Exchange sync error:  unable to update app (no eid and/or eck) for $app->calendarid app $app->appointmentid");
            return false;
        }
        
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $app->calendarid
        ));
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($app);
        
        /* Build our Exchange service(s) */
        list($ews1, $ews2, $order, $savedex) = self::buildService($userid);       
        if (!$ews1  && !$ews2) 
            return false;
       
        
        // Start building the request
        $request = new \jamesiarmes\PhpEws\Request\UpdateItemType();
        $request->ConflictResolution = \jamesiarmes\PhpEws\Enumeration\ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->SendMeetingInvitationsOrCancellations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;
        $request->ItemChanges = array();

        
        $change = new \jamesiarmes\PhpEws\Type\ItemChangeType();
        $change->ItemId = new \jamesiarmes\PhpEws\Type\ItemIdType();
        $change->ItemId->Id = $app->eid;
        $change->ItemId->ChangeKey = $app->eck;

        
        // Update Subject Property
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::ITEM_SUBJECT;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Subject = WaseUtil::getParm('SYSID') . ' Appointment for ' . $app->userid . ' (' . $app->name . ') with ' . $block->userid . ' (' . $block->name . ')';
        $change->Updates->SetItemField[] = $field;
        
        // Update Start Property
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_START;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Start = $dates . 'T' . $tstart . '+00:00';
        $change->Updates->SetItemField[] = $field;
        
        // Update End Property
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_END;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->End = $datee . 'T' . $tend . '+00:00';
        $change->Updates->SetItemField[] = $field;
        
        // Update location
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI =  new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI =  \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_LOCATION;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Location = $block->location;
        $change->Updates->SetItemField[] = $field;
        
        // Update the body
        $body = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this appointment, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.' . "\r\n";
        if ($app->purpose)
            $body = $app->purpose . "<br />" . $body;
        
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::ITEM_BODY;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Body = new \jamesiarmes\PhpEws\Type\BodyType();
        $field->CalendarItem->Body->BodyType = \jamesiarmes\PhpEws\Enumeration\BodyTypeType::HTML;
        $field->CalendarItem->Body->_ = $body;
        $change->Updates->SetItemField[] = $field;
        
              
        // Set the requested changes
        $request->ItemChanges[] = $change;
       
        
        // Init our error msg
        $emsg = 'Unable to connect to Exchange server(s).';
                 
        /* Now save the appointment into Exchange:  try local then remote Exchange. */
        while($ews1 || $ews2) {
            try {
                if ($ews1)
                    $response = $ews1->UpdateItem($request);
                else 
                    $response = $ews2->UpdateItem($request);
                $respmsg = $response->ResponseMessages->UpdateItemResponseMessage;
                if (is_array($respmsg))
                    $respmsg = $respmsg[0];
                if ($respmsg->ResponseClass == 'Success') {
                     // Reset the change key
                    $calitem = $respmsg->Items->CalendarItem;
                    if (is_array($calitem))
                        $calitem = $calitem[0];
                    $app->eid = $calitem->ItemId->Id;
                    $app->eck = $calitem->ItemId->ChangeKey;
                    // Save identity of working exchange server
                    if ($ews1) {
                        if ($savedex != $order[0]) WasePrefs::savePref($userid, 'savedex', $order[0]);
                    } else {
                        if ($savedex != $order[1]) WasePrefs::savePref($userid, 'savedex', $order[1]);                         
                    }
                    return true;
                }
                else {
                    $emsg = $respmsg->MessageText;
                    if ($ews1)
                        $ews1 = false;
                    else
                        $ews2 = false;
                }         
            } catch (Exception $e) {
                $emsg = $e->getMessage();
                if ($ews1)
                    $ews1 = false;
                else
                    $ews2 = false;
            }
        }
         
        WaseMsg::logMsg("Exchange update error:  unable to update app for calendar $wasecal->calendarid app $app->appointmentid: " . $emsg);
        return false;
        
        
    }

    /**
     * This function adds a WaseBlock as an event to an Exchange calendar.
     *
     * @static
     *
     * @param WaseBlock $block
     *            the owning WaseBlock.
     * @return string the block as an iCal stream.
     */
    static function addBlock($block)
    {
        WaseMsg::dMsg('exchange','startadd','starting addblock request'); 
        
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $block->calendarid
        ));
        
        
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($block);
        
        // Save userid
        $userid = $block->userid;
        

        /* Build our Exchange service(s) */
        list($ews1, $ews2, $order, $savedex) = self::buildService($userid);
        WaseMsg::dMsg('exchange', 'services built','local service='.print_r($ews1,true).':: remote service='.print_r($ews2,true));
         
        if (!$ews1 && !$ews2)
            return false;
        
        // Start building the request.
        $request = new \jamesiarmes\PhpEws\Request\CreateItemType();
        $request->Items = new \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType();
        
        
        // Start building the event.
        $event = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        // $event->CalendarItemType = \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType::CALENDAR;
        // $request->DateTimeStamp = '';
             
        // Set the start and end times. For Exchange 2007, you need to include the timezone offset.
        // For Exchange 2010, you should set the StartTimeZone and EndTimeZone properties. See below for
        // an example.       
        $event->Start = $dates . 'T' . $tstart . '+00:00';
        $event->End = $datee . 'T' . $tend . '+00:00';
         
        // Set the location
        $event->Location = $block->location;
        
        // Set a title/subject
        if ($block->title)
            $event->Subject = $block->title;
        else
            $event->Subject = WaseUtil::getParm('SYSID') . ' Appointment Block';
        
        
        // Build the body.
        $event->Body = new \jamesiarmes\PhpEws\Type\BodyType();
        $event->Body->BodyType = \jamesiarmes\PhpEws\Enumeration\BodyTypeType::TEXT;
        $event->Body->_ = 'NOTE FROM ' .  WaseUtil::getParm('SYSID') .':  If you wish to delete or change this block, please do it in ' . WaseUtil::getParm('SYSID') . ', not here.' . "\r\n";

        // Set the item class type (not required).
        $event->ItemClass = new \jamesiarmes\PhpEws\Enumeration\ItemClassType();
        $event->ItemClass->_ = \jamesiarmes\PhpEws\Enumeration\ItemClassType::APPOINTMENT;
        
        // Set the sensativity of the event (defaults to normal).
        $event->Sensitivity = new \jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType();
        $event->Sensitivity->_ = \jamesiarmes\PhpEws\Enumeration\SensitivityChoicesType::NORMAL;
        
        
        // Set no reminders
        $event->ReminderIsSet = false;
       
        // Or use this to specify when reminder is displayed (if this is not set, the default is 15 minutes)
        // $event->ReminderMinutesBeforeStart = 30;
        

        // Set the importance of the event.
        $event->Importance = new \jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType();
        $event->Importance->_ = \jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType::NORMAL;
        
        
        // Add some categories to the event. 
        $event->Categories = new \jamesiarmes\PhpEws\ArrayType\ArrayOfStringsType();
        $event->Categories->String = array($block->NAMETHINGS);
        
        WaseMsg::dMsg('exchange','event built',print_r($event,true));
        
        /* Determine if exchange integration is direct or by invitation */
        if (WaseUtil::getParm('EXCHANGE_DIRECT')) {
            /* The following code attempts to put the block directly into the target user's calendar */
            // Point to the target, shared calendar.
            
            $savedfolder = new \jamesiarmes\PhpEws\Type\TargetFolderIdType;
            
            $savedfolder->AddressListId = new \jamesiarmes\PhpEws\Type\AddressListIdType;
            $savedfolder->AddressListId->Id = 'WASE Address';
            $savedfolder->DistinguishedFolderId = new \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType();
            $savedfolder->DistinguishedFolderId->Id = \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType::CALENDAR;
            $savedfolder->DistinguishedFolderId->Mailbox = new \jamesiarmes\PhpEws\Type\EmailAddressType;
            // Extract the leading email, if more than one specified
            list($firstemail, $rest) = explode(',', $block->email);
            $savedfolder->DistinguishedFolderId->Mailbox->EmailAddress = $firstemail;
            
            $request->SavedItemFolderId = $savedfolder;            
         
            // Don't send meeting invitations.
            $request->SendMeetingInvitations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_TO_NONE;
        }
        else {
            /* The following code sends out meeting invitations, rather than putting the block directly into the target calendar */
            // Set meeting attendees
            
            $event->RequiredAttendees = new \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType();
            
            /* Invite the prof (appointment with) */
            if (strtolower($block->email)) {
                $attendep = new \jamesiarmes\PhpEws\Type\AttendeeType();
                $attendep->Mailbox = new \jamesiarmes\PhpEws\Type\EmailAddressType();
                // Extract the leading email, if more than one specified
                list($firstemail, $rest) = explode(',', $block->email);
                $attendep->Mailbox->EmailAddress = $firstemail;
                $attendep->Name = $block->name;
                $event->RequiredAttendees->Attendee[] = $attendep;
            }
            
            $request->SendMeetingInvitations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;
        }
       
        
        // Add the event to the request
        $request->Items->CalendarItem[] = $event;
        
        WaseMsg::dMsg('exchange','request built',print_r($request,true));
      
          
        // Init our error msg
        $emsg = 'Unable to connect to Exchange server(s).';
                 
        /* Now save the appointment into Exchange:  try local then remote Exchange.  */
        while($ews1 || $ews2) {
            try {
                if ($ews1) {
                    $response = $ews1->CreateItem($request);
                    WaseMsg::dMsg('exchange', $order[0] . ' response',print_r($response,true));
                }
                else {
                    $response = $ews2->CreateItem($request);
                    WaseMsg::dMsg('exchange', $order[1] . ' response',print_r($response,true));
                }
                $respmsg = $response->ResponseMessages->CreateItemResponseMessage;
                if (is_array($respmsg))
                    $respmsg = $respmsg[0];
                if ($respmsg->ResponseClass == 'Success') {
                    // Save the id and the change key
                    $calitem = $respmsg->Items->CalendarItem;
                    if (is_array($calitem))
                        $calitem = $calitem[0];
                    $block->eid = $calitem->ItemId->Id;
                    $block->eck = $calitem->ItemId->ChangeKey;
                    // Save identity of working exchange server
                    if ($ews1) {
                        if ($savedex != $order[0]) WasePrefs::savePref($userid, 'savedex', $order[0]);
                    } else {
                        if ($savedex != $order[1]) WasePrefs::savePref($userid, 'savedex', $order[1]);                         
                    }
                    if ($ews1)
                        WaseMsg::dMsg('exchange', 'success with ' . $order[0],print_r($calitem,true));
                    else
                        WaseMsg::dMsg('exchange', 'success with ' . $order[1],print_r($calitem,true));
                    return true;
                }
                else {
                    $emsg = $respmsg->MessageText;
                    if ($ews1) {
                        $ews1 = false;
                        WaseMsg::dMsg('exchange', 'fail with ' . $order[0],$emsg);
                    }
                    else {
                        $ews2 = false;
                        WaseMsg::dMsg('exchange', 'fail with ' . $order[1],$emsg);
                    }
                }                          
            } catch (Exception $e) {
                $emsg = $e->getMessage();
                if ($ews1) {
                    $soap = $ews1->getClient();
                    $ews1 = false;
                }
                else {
                    $soap = $ews2->getClient();
                    $ews2 = false;
                }
                // Try to extract soap information
                $response_code = $soap->getResponseCode();
                $response_headers = $soap->__getLastResponseHeaders();
                $response_body = $soap->__getLastResponse();
                WaseMsg::dMsg('exchange', 'exception',$emsg . " $response_code ; $response_headers ; $response_body");
            }           
                     
       
        }
       
        WaseMsg::logMsg("Exchange insert error:  unable to Create block for calendar $wasecal->calendarid app block $block->blockid: " . $emsg);
        WaseMsg::dMsg('exchange', 'logmsg',"Exchange insert error:  unable to Create block for calendar $wasecal->calendarid app block $block->blockid: " . $emsg);
        return false;
        
        
    }

    /**
     * This function updates a block in a Exchange calendar.
     *
     *
     * @static
     *
     *
     * @param WaseBlock $block
     *            the owning WaseBlock.
     *            
     * @return null.
     */
    static function changeBlock($block)
    {
        /* Get the WASE calendar */
        $wasecal = new WaseCalendar('load', array(
            'calendarid' => $block->calendarid
        ));
        
        //  Don't try if we don't have an eid or an eck
        if (!$block->eid || !$block->eck) {
            WaseMsg::logMsg("Exchange sync error:  unable to update block (no eid and/or eck) for calendar $wasecal->calendarid block $block->blockid");
            return false;
        }
        
        /* Compute start/end date/time */
        list ($dates, $datee, $tstart, $tend) = self::datetimes($block); 

        // Save userid
        $userid = $block->userid;
        

        /* Build our Exchange service(s) */
        list($ews1, $ews2, $order, $savedex) = self::buildService($userid);
        if (!$ews1 && !$ews2)
            return false;
        
        // Start building the request
        $request = new \jamesiarmes\PhpEws\Request\UpdateItemType();
        $request->ConflictResolution = \jamesiarmes\PhpEws\Enumeration\ConflictResolutionType::ALWAYS_OVERWRITE;
        $request->SendMeetingInvitationsOrCancellations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;
        
        $request->ItemChanges = array();      
        $change = new \jamesiarmes\PhpEws\Type\ItemChangeType();
        $change->ItemId = new \jamesiarmes\PhpEws\Type\ItemIdType();
        $change->ItemId->Id = $block->eid;
        $change->ItemId->ChangeKey = $block->eck;
        $request->ItemChanges[] = $change;
        
        // Update Start Property
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_START;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Start = $dates . 'T' . $tstart . '+00:00';
        $change->Updates->SetItemField[] = $field;
        
        // Update End Property
        $field = new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI = new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_END;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->End = $datee . 'T' . $tend . '+00:00';
        $change->Updates->SetItemField[] = $field;
        
        // Update Subject Property
        $field =new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI =  new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI =  \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::ITEM_SUBJECT;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Subject = WaseUtil::getParm("SYSID") . ' Appointment Block';
        $change->Updates->SetItemField[] = $field;
        
        // Update location
        $field =new \jamesiarmes\PhpEws\Type\SetItemFieldType();
        $field->FieldURI =  new \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType();
        $field->FieldURI->FieldURI =  \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType::CALENDAR_LOCATION;
        $field->CalendarItem = new \jamesiarmes\PhpEws\Type\CalendarItemType();
        $field->CalendarItem->Location = $block->location;
        $change->Updates->SetItemField[] = $field;
        
        
         
        
        // Init our error msg
        $emsg = 'Unable to connect to Exchange server(s).';
                 
        /* Now save the appointment into Exchange:  try local then remote Exchange.  */
        while($ews1 || $ews2) {
            try {
                if ($ews1)
                    $response = $ews1->UpdateItem($request);
                else                    
                    $response = $ews2->UpdateItem($request);
            
                $respmsg = $response->ResponseMessages->UpdateItemResponseMessage;
                if (is_array($respmsg))
                    $respmsg = $respmsg[0];
                if ($respmsg->ResponseClass == 'Success') {
                    // Reset the change key
                    $calitem = $respmsg->Items->CalendarItem;
                    if (is_array($calitem))
                        $calitem = $calitem[0];
                    $block->eck = $calitem->ItemId->ChangeKey;
                    // Save identity of working exchange server
                    if ($ews1) {
                        if ($savedex != $order[0]) WasePrefs::savePref($userid, 'savedex', $order[0]);
                    } else {
                        if ($savedex != $order[1]) WasePrefs::savePref($userid, 'savedex', $order[1]);                         
                    }
                    return true;
                }
                else {
                    $emsg = $respmsg->MessageText;
                    if ($ews1)
                        $ews1 = false;
                    else 
                        $ews2 = false;
                }              
                
            } catch (Exception $e) {
                $emsg = $e->getMessage();
                if ($ews1)
                    $ews1 = false;
                else 
                    $ews2 = false;
            }
        }
          
        WaseMsg::logMsg("Exchange change error:  unable to change block for calendar $wasecal->calendarid block $block->blockid: " . $emsg);
        return false;
                 
    }
    

    /**
     * This function deletes an appointment or block from an Exchange calendar.
     *
     *
     * @static
     *
     * @param
     *            WaseAppointment || WaseBlock $obj
     *            the target appointment or block.
     *
     *
     * @return null.
     */
    static function delObj($obj, $userid)
    {
    

        /* Build our Exchange service(s) */
        list($ews1, $ews2, $order, $savedex) = self::buildService($userid);
        if (!$ews1  && !$ews2)
            return false;
        
        // Fail request if we don't have the id and change key. 
        if (!$obj->eid || !$obj->eck) 
            return false; 
        // Define the delete item class
        $request = new \jamesiarmes\PhpEws\Request\DeleteItemType();
        // Send to trash can, or use \jamesiarmes\PhpEws\Enumeration\DisposalType::HARD_DELETE instead to bypass the bin directly
        $request->DeleteType = \jamesiarmes\PhpEws\Enumeration\DisposalType::HARD_DELETE;
        // Inform no one who shares the item that it has been deleted
        $request->SendMeetingCancellations = \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType::SEND_TO_NONE;
    
        // Set the item to be deleted
        $item = new \jamesiarmes\PhpEws\Type\ItemIdType();
        $item->Id = $obj->eid;
        $item->ChangeKey = $obj->eck;
    
        // We can use this to mass delete but in this case it's just one item
        $items = new \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType();
        $items->ItemId[] = $item;
        $request->ItemIds = $items;
    
         
        // Send the delete request:  try local then remote Exchange.
    
        while($ews1 || $ews2) {
            try {
                if ($ews1) 
                    $response = $ews1->DeleteItem($request);
                else 
                    $response = $ews2->DeleteItem($request);
                
                $respmsg = $response->ResponseMessages->DeleteItemResponseMessage;
                if (is_array($respmsg))
                    $respmsg = $respmsg[0];
                if ($respmsg->ResponseClass == 'Success') {
                    // Save identity of working exchange server
                    if ($ews1) {
                        if ($savedex != $order[0]) WasePrefs::savePref($userid, 'savedex', $order[0]);
                    } else {
                        if ($savedex != $order[1]) WasePrefs::savePref($userid, 'savedex', $order[1]);                         
                    }
                    return true;
                }
                $emsg = $respmsg->MessageText;
                
                if ($ews1)
                    $ews1= false;
                else
                    $ews2 = false;               
            } catch (Exception $e) {
                $emsg = $e->getMessage();
                if ($ews1)
                    $ews1= false;
                else
                    $ews2 = false;
            }
        }
    
        WaseMsg::logMsg("Exchange delete error:  unable to delete item for object with eid = $obj->eid : " . $emsg);
        return false;
    
    }
     

    /**
     * This function saves a list of appointments to a Exchange calendar.
     *
     *
     * The passed parameters are used as selection criteria for building the list of appointments.
     *
     * @static
     *
     * @param string $userid
     *            that target userid (AppWith or AppFor).
     * @param string $startdate
     *            the start date.
     * @param string $enddate
     *            the end date.
     * @param string $starttime
     *            the start time (minimum).
     * @param string $endtime
     *            the end time (maximum).
     *            
     * @return null.
     *
     */
    static function listApps($userid, $startdate, $enddate, $starttime, $endtime)
    {
        
        /* Bulld the select criteria */
        $select = 'SELECT * FROM ' . WaseUtil::getParm('DATABASE') . '.WaseAppointment WHERE (userid=' . WaseSQL::sqlSafe($userid) . ' OR blockid in (SELECT blockid FROM ' . WaseUtil::getParm('DATABASE') . '.WaseBlock WHERE userid=' . WaseSQL::sqlSafe($userid) . '))';
        
        if ($startdate)
            $select .= ' AND date(startdatetime) >=' . WaseSQL::sqlSafe($startdate);
        
        if ($enddate)
            $select .= ' AND date(enddatetime) <=' . WaseSQL::sqlSafe($enddate);
        
        if ($starttime)
            $select .= ' AND time(startdatetime) >=' . WaseSQL::sqlSafe($starttime);
        
        if ($endtime)
            $select .= ' AND time(enddatetime) <=' . WaseSQL::sqlSafe($endtime);
        
        $select .= ' ORDER by startdatetime';
        
        /* Now get the waselist of appointments */ 
        $apps = new WaseList($select, 'Appointment');
        
        /* Now add in all of the appointments to the Exchange calendar */
        foreach ($apps as $app) {
            
            /* Read in the block */
            $block = new WaseBlock('load', array(
                "blockid" => $app->blockid
            ));
            
            /* Add the appointment */
            self::addApp($app, $block, $userid, $app->email);
        }
        
        return null;
    }

    /**
     * This function creates local and a remote (o365) Exchange clients (if either or both are configured).
     *
     * @static @private
     *     
     * @param string $userid
     *          the user whose calendar we want to change.
     *            
     * @return array
     *              local and remote connections, if any or either.
     *        
     */
    static private function buildService($userid)
    {
        
        // Assume no connections
        $ewsl = false; 
        $ewso = false;
        
        
        // See if we have o365 parameters
        if ($user = WaseUtil::getParm('O365_USER'))
            $o365parms = array( 
                'host' => WaseUtil::getParm('O365_HOST'),  
                'email' => WaseUtil::getParm('O365_EMAIL'),
                // 'username' => $user,
                'username' => WaseUtil::getParm('O365_EMAIL'),
                'password' => WaseUtil::getParm('O365_PASSWORD'),
                'version' =>  WaseUtil::getParm('O365_VERSION')
            ); 
        else 
            $o365parms = false;
       
        // See if we have local parameters
        if ($user = WaseUtil::getParm('EXCHANGE_USER'))
            $localparms = array(
                'host' => WaseUtil::getParm('EXCHANGE_HOST'),
                'email' => WaseUtil::getParm('EXCHANGE_EMAIL'),
                'username' => WaseUtil::getParm('EXCHANGE_USER'),
                'password' => WaseUtil::getParm('EXCHANGE_PASSWORD'),
                'version' =>  WaseUtil::getParm('EXCHANGE_VERSION')
            );
        else 
            $localparms = false;
        
         
        // Now establish connections
        if ($o365parms !== false)  {
            $ewso = self::connectToServer($o365parms);
            // WaseMsg::logMsg("Logging in with user = " . $o365parms['username'] . ' and password = ' . $o365parms['password']);
        }
        else
            $ewso = false;
        
        if ($localparms !== false) {
            $ewsl = self::connectToServer($localparms);
            // WaseMsg::logMsg("Logging in with user = " . $localparms['username'] . ' and password = ' . $localparms['password']);
        }
        else
            $ewsl = false;
        
       
        // Log failures
        if ($ewsl==false && $ewso==false) {
            WaseMsg::logMsg("Exchange service connect error:  unable to create local or remote Exchange service connections.");
        }
         
        // See if user has a saved exchange server.
       $savedex = WasePrefs::getPref($userid, 'savedex');
                 
        // Return connections in preferred order.
        if ($savedex == 'ewsl')
            return array($ewsl, $ewso, array('ewsl','ewso'), $savedex);
        else 
            return array($ewso, $ewsl, array('ewso','ewsl'), $savedex);
                      
       
    }
       
    /**
     * This internal function connects to an Exchange server.
     * 
     * @param array $ex
     *          Array of connection parameters
     *          
     * @return EwsConnection || false
     *          An exchange connection, or false.
     */
    static private function connectToServer($ex) {
        
        // Try auto-discovery
        try {
            $ews = \jamesiarmes\PhpEws\Autodiscover::getEWS($ex['email'], $ex['password']);
            if (is_object($ews)) {
                $ews->setVersion($ex['version']);
                return $ews;
           }
        } catch (Exception $e) {
           // Just continue trying a manual connect. 
        }
          
        try {
            $ews = new \jamesiarmes\PhpEws\Client($ex['host'], $ex['username'], $ex['password'], $ex['version']);
            if ($ews) 
                return $ews;
        } catch (Exception $e) { 
            WaseMsg::logMsg("Exchange error trying to create ews client: " . $e->getMessage());
            return false;
        }
               
        // In case we drop through.     
        return false;
        
        
    }

    /**
     * This internal function computes start/end dates and times for an appointment.
     *
     * @static @private
     *        
     * @param WaseAppointment $app
     *            the appointment.
     *            
     * @return array start date, end date, start time, end time
     */
    static private function datetimes($obj)
    {
        return array(
            gmdate('Y-m-d', mktime(substr($obj->startdatetime, 11, 2), substr($obj->startdatetime, 14, 2), 0, substr($obj->startdatetime, 5, 2), substr($obj->startdatetime, 8, 2), substr($obj->startdatetime, 0, 4))),
            gmdate('Y-m-d', mktime(substr($obj->enddatetime, 11, 2), substr($obj->enddatetime, 14, 2), 0, substr($obj->enddatetime, 5, 2), substr($obj->enddatetime, 8, 2), substr($obj->enddatetime, 0, 4))),
            gmdate('H:i:s', mktime(substr($obj->startdatetime, 11, 2), substr($obj->startdatetime, 14, 2), 0, substr($obj->startdatetime, 5, 2), substr($obj->startdatetime, 8, 2), substr($obj->startdatetime, 0, 4))),
            gmdate('H:i:s', mktime(substr($obj->enddatetime, 11, 2), substr($obj->enddatetime, 14, 2), 0, substr($obj->enddatetime, 5, 2), substr($obj->enddatetime, 8, 2), substr($obj->enddatetime, 0, 4)))
        );
    }
}
?>