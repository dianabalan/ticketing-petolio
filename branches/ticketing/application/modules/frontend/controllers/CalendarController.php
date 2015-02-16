<?php

class CalendarController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $auth = null;
    private $request = null;

    private $usr = null;
    private $cal = null;
    private $att = null;
    private $mess = null;
    private $reci = null;

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

    public function init()
    {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();

		$this->usr = new Petolio_Model_PoUsers();
		$this->cal = new Petolio_Model_PoCalendar();
		$this->att = new Petolio_Model_PoCalendarAttendees();
		$this->mess = new Petolio_Model_PoMessages();
		$this->reci = new Petolio_Model_PoMessageRecipients();

		// not logged in ? BYE (except for attendees)
    	if (!$this->auth->hasIdentity() && $this->request->getActionName() != 'attendees') {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
    	}
    }

    public function indexAction()
    {
    	// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;
    }

    public function loadAction()
    {
    	// calendar required params
		$start = $this->request->getParam('start');
		$end = $this->request->getParam('end');
		if(!$start && !$end)
			die('gtfo noob');

    	// get your appointments / tasks / events / special events
    	$events = array();
    	$result = $this->cal->getMapper()->getEvents("user_id = '{$this->auth->getIdentity()->id}' AND
    		((`repeat` = '0' AND UNIX_TIMESTAMP(date_end) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($start)." AND UNIX_TIMESTAMP(date_start) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($end).")
    		OR
    		(`repeat` = '0' AND UNIX_TIMESTAMP(date_start) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($start)." AND UNIX_TIMESTAMP(date_start) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($end).")
    		OR
    		(`repeat` = '1' AND UNIX_TIMESTAMP(repeat_until) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($start)." AND UNIX_TIMESTAMP(date_start) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($end).")
    		)");
    	# SMARTFILTER:
		# 1. get events based on date start and date end (might be events that span over a month or more, so those must be loaded within the parameters)
		# 2. get events that start between the filters (might be 1 day events or events without an date_end)
		# 3. get events that are repeating, from when they start till they should end repeating)

    	// format accordingly
    	if (is_array($result) && count($result) > 0)
    		foreach($result as $line)
    			$events[] = Petolio_Service_Calendar::format($line);

    	// master repeats so we dont do it in js
		$events = Petolio_Service_Calendar::masterRepeats($events, true);

    	// get special events where you've been invited
    	$result = $this->cal->getMapper()->getSpecialEvents($this->auth->getIdentity()->id, $start, $end);
        if (is_array($result) && count($result) > 0) {
    		foreach($result as $line) {
    			$array = Petolio_Service_Calendar::format($line);
    			$array['invited'] = $line['atype'] == 0 && $line['astatus'] == 0 ? true : false;
    			$array['accepted'] = $line['atype'] == 0 && $line['astatus'] == 1 ? true : false;
    			$array['access'] = $line['astatus'] == 1 ? true : false;

    			$events[] = $array;
    		}
    	}

		// return the json
		return Petolio_Service_Util::json(array('success' => true, 'events' => $events));
    }

    public function saveAction()
    {
    	// no post ? BYE
    	if(empty($_POST))
    		die('gtfo noob');

    	// todo save? go to todo
    	if($_POST['type'] == 1)
    		return $this->saveTodo();

    	// chat save? go to chat
    	if($_POST['type'] == 3)
    		return $this->saveChat();

    	// perform nulls
    	foreach($_POST as &$line)
    		if($line == 'null')
    			$line = new Zend_Db_Expr('NULL');

       	// reset time if all day is checked
   		if($_POST['allDay'] == 'true') {
   			$_POST['start'] = date('Y-m-d', strtotime($_POST['start']));
   			if(!is_object($_POST['end'])) // not null
   				$_POST['end'] = date('Y-m-d', strtotime($_POST['end']));
   		}
    		
    	// get correct timezone dates
    	$_POST['start'] = Petolio_Service_Util::calculateTimezone($_POST['start'], $_COOKIE['user_timezone'], true);
    	if(!is_object($_POST['end'])) // not null
    		$_POST['end'] = Petolio_Service_Util::calculateTimezone($_POST['end'], $_COOKIE['user_timezone'], true);
    	if(!is_object($_POST['repeat_until'])) // not null
    		$_POST['repeat_until'] = strtotime($_POST['repeat_until']);

        // do we have an id ? that means its an update
        $result = array();
    	if(isset($_POST['id']))
    		$result = $this->cal->getMapper()->fetchList("id = '{$_POST['id']}' AND user_id = '{$this->auth->getIdentity()->id}'");

    	// get now
		$now = new DateTime('now');

    	// no event found ? insert new
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->cal->setUserId($this->auth->getIdentity()->id);
			$this->cal->setSubject($_POST['title']);

			// special event ? set fee and cap
			if($_POST['type'] == 2) {
				$this->cal->setSpecies($_POST['species']);
				$this->cal->setMod($_POST['mod']);
				$this->cal->setFee($_POST['fee']);
				$this->cal->setCap($_POST['cap']);
			}

			$this->cal->setDescription($_POST['description']);
			$this->cal->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$this->cal->setDateEnd(is_object($_POST['end']) /* only if null this = object */ ? $_POST['end'] : date('Y-m-d H:i:s', $_POST['end']));
			$this->cal->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$this->cal->setStreet($_POST['street']);
			$this->cal->setAddress($_POST['address']);
			$this->cal->setZipcode($_POST['zip']);
			$this->cal->setLocation($_POST['location']);
			$this->cal->setCountryId($_POST['countryId']);
			$this->cal->setGpsLatitude($_POST['lat']);
			$this->cal->setGpsLongitude($_POST['long']);
			$this->cal->setType($_POST['type']);
			$this->cal->setReminder($_POST['reminder']);
			$this->cal->setReminderTime($_POST['reminder_time']);
			$this->cal->setRepeat($_POST['repeat']);
			$this->cal->setLinkId($_POST['link_id']);
			$this->cal->setAvailability($_POST['availability']);
			if (isset($_POST['service_type']))
				$this->cal->setLinkType($_POST['service_type']);

			// event is repeating ?
			if($_POST['repeat'] == 1) {
				$n = clone $now;
				$n->add(new DateInterval("PT{$_POST['reminder_time']}M"));

				$crontab = Petolio_Service_Calendar::getCronSyntax($_POST['start'], $_POST['repeat_syntax']);
				$repeat_until = Petolio_Service_Calendar::getRepeatUntil($_POST['repeat_until'])->format('U');
				$next_run_date = Petolio_Service_Calendar::getNextRunDate($crontab, $_POST['start'], $repeat_until, $n)->format('U');

				$this->cal->setRepeatMinutes($crontab[0]);
				$this->cal->setRepeatHours($crontab[1]);
				$this->cal->setRepeatDayOfMonth($crontab[2]);
				$this->cal->setRepeatMonth($crontab[3]);
				$this->cal->setRepeatDayOfWeek($crontab[4]);
				$this->cal->setRepeatUntil(date('Y-m-d H:i:s', $repeat_until));
				$this->cal->setDateNextRun(date('Y-m-d H:i:s', $next_run_date));
			} else
				$this->cal->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));

			// save event
			$this->cal->save(true, true);
			$id = $this->cal->getId();

			// special appointment ? invite the request invite
			if(!is_object($_POST['link_id']) && isset($_POST['users'])) {
				// unset this
				unset($_POST['users']);

				// invite the service owner or the pet owner
				if (isset($_POST['service_type']) && intval($_POST['service_type']) == 1) {
					$link = new Petolio_Model_PoServiceMembersUsers();
					$owners = $link->getLinkOwners($_POST['link_id']);

					// who asked for the appointment ? member
					if ($owners['member']->getId() == $this->auth->getIdentity()->id) {
						// save attendee
						$this->att = new Petolio_Model_PoCalendarAttendees();
						$this->att->setCalendarId($id);
						$this->att->setUserId($owners['service_owner']->getId());
						$this->att->save();

						// notify users
				    	Petolio_Service_Message::send(array(
							'subject' => $this->translate->_("Appointment request"),
							'message_html' =>
								sprintf(
									$this->translate->_('%1$s asked for an appointment'),
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
								) . '<br /><br />' .
				    			sprintf(
				    				$this->translate->_("%s has sent you the following message:"),
								    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
							    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>" .
								sprintf(
									$this->translate->_('You can %1$s or %2$s the request or %3$s the event.'),
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}'>".$this->translate->_('view')."</a>"
								),
							'from' => $this->auth->getIdentity()->id,
							'status' => 1,
							'template' => 'calendar/appointment'
						), array(array(
							'id' => $owners['service_owner']->getId(),
							'name' => $owners['service_owner']->getName(),
							'email' => $owners['service_owner']->getEmail()
						)), $owners['service_owner']->isOtherEmailNotification());

					// who asked for the appointment? service owner
					} else {
						// save attendee
						$this->att = new Petolio_Model_PoCalendarAttendees();
						$this->att->setCalendarId($id);
						$this->att->setUserId($owners['member']->getId());
						$this->att->save();

						// notify users
				    	Petolio_Service_Message::send(array(
							'subject' => $this->translate->_("Appointment request"),
							'message_html' =>
								sprintf(
									$this->translate->_('%1$s asked for an appointment'),
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
								) . '<br /><br />' .
				    			sprintf(
				    				$this->translate->_("%s has sent you the following message:"),
								    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
							    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>" .
								sprintf(
									$this->translate->_('You can %1$s or %2$s the request or %3$s the event.'),
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}'>".$this->translate->_('view')."</a>"
								),
							'from' => $this->auth->getIdentity()->id,
							'status' => 1,
							'template' => 'calendar/appointment'
						), array(array(
							'id' => $owners['member']->getId(),
							'name' => $owners['member']->getName(),
							'email' => $owners['member']->getEmail()
						)), $owners['member']->isOtherEmailNotification());
					}
				} else {
					$link = new Petolio_Model_PoServiceMembersPets();
					$owners = $link->getLinkOwners($_POST['link_id']);

					// get pet
					$popets = new Petolio_Model_PoPets();
					$pet = reset($popets->getPets('array', "a.id = {$owners['pet_id']}"));

					// who asked for the appointment? pet owner
					if ($owners['pet_owner']->getId() == $this->auth->getIdentity()->id) {
						// save attendee
						$this->att = new Petolio_Model_PoCalendarAttendees();
						$this->att->setCalendarId($id);
						$this->att->setUserId($owners['service_owner']->getId());
						$this->att->save();

						// notify users
				    	Petolio_Service_Message::send(array(
							'subject' => $this->translate->_("Appointment request"),
							'message_html' =>
								sprintf(
									$this->translate->_('%1$s asked for an appointment for the pet %2$s'),
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $owners['pet_id']), 'default', true)}'>{$pet['name']}</a>"
								) . '<br /><br />' .
				    			sprintf(
				    				$this->translate->_("%s has sent you the following message:"),
								    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
							    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>" .
								sprintf(
									$this->translate->_('You can %1$s or %2$s the request or %3$s the event.'),
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}'>".$this->translate->_('view')."</a>"
								),
							'from' => $this->auth->getIdentity()->id,
							'status' => 1,
							'template' => 'calendar/appointment'
						), array(array(
							'id' => $owners['service_owner']->getId(),
							'name' => $owners['service_owner']->getName(),
							'email' => $owners['service_owner']->getEmail()
						)), $owners['service_owner']->isOtherEmailNotification());

					// who asked for the appointment? service owner
					} else {
						// save attendee
						$this->att = new Petolio_Model_PoCalendarAttendees();
						$this->att->setCalendarId($id);
						$this->att->setUserId($owners['pet_owner']->getId());
						$this->att->save();

						// notify users
				    	Petolio_Service_Message::send(array(
							'subject' => $this->translate->_("Appointment request"),
							'message_html' =>
								sprintf(
									$this->translate->_('%1$s asked for an appointment for your pet %2$s'),
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $owners['pet_id']), 'default', true)}#{$id}'>{$pet['name']}</a>"
								) . '<br /><br />' .
				    			sprintf(
				    				$this->translate->_("%s has sent you the following message:"),
								    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
							    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>" .
								sprintf(
									$this->translate->_('You can %1$s or %2$s the request or %3$s the event.'),
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
									"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
									"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}'>".$this->translate->_('view')."</a>"
								),
							'from' => $this->auth->getIdentity()->id,
							'status' => 1,
							'template' => 'calendar/appointment'
						), array(array(
							'id' => $owners['pet_owner']->getId(),
							'name' => $owners['pet_owner']->getName(),
							'email' => $owners['pet_owner']->getEmail()
						)), $owners['pet_owner']->isOtherEmailNotification());
					}
				}
			}

		// event found ? update it
    	} else {
    		$result = reset($result);

    		// notify people that the start date has been changed
    		if($result->getDateStart() != date('Y-m-d H:i:s', $_POST['start']))
    			$this->dateNotify($result, 'event', $_POST['start']);

    		// update info
    		$result->setUserId($this->auth->getIdentity()->id);
			$result->setSubject($_POST['title']);

			// special event ? set fee and cap
    		if($_POST['type'] == 2) {
				$result->setSpecies($_POST['species']);
				$result->setMod($_POST['mod']);
				$result->setFee($_POST['fee']);
				$result->setCap($_POST['cap']);
			} else {
				$result->setSpecies(new Zend_Db_Expr('NULL'));
				$result->setMod(new Zend_Db_Expr('NULL'));
				$result->setFee(new Zend_Db_Expr('NULL'));
				$result->setCap(new Zend_Db_Expr('NULL'));
			}

			// update info
    		$result->setDescription($_POST['description']);
			$result->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$result->setDateEnd(is_object($_POST['end']) /* only if null this = object */ ? $_POST['end'] : date('Y-m-d H:i:s', $_POST['end']));
			$result->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$result->setStreet($_POST['street']);
			$result->setAddress($_POST['address']);
			$result->setZipcode($_POST['zip']);
			$result->setLocation($_POST['location']);
			$result->setCountryId($_POST['countryId']);
			$result->setGpsLatitude($_POST['lat']);
			$result->setGpsLongitude($_POST['long']);
			$result->setType($_POST['type']);
			$result->setReminder($_POST['reminder']);
			$result->setReminderTime($_POST['reminder_time']);
			$result->setRepeat($_POST['repeat']);
			$result->setAvailability($_POST['availability']);

			// event is repeating ?
			if($_POST['repeat'] == 1) {
				$n = clone $now;
				$n->add(new DateInterval("PT{$_POST['reminder_time']}M"));

				$crontab = Petolio_Service_Calendar::getCronSyntax($_POST['start'], $_POST['repeat_syntax']);
				$repeat_until = Petolio_Service_Calendar::getRepeatUntil($_POST['repeat_until'])->format('U');
				$next_run_date = Petolio_Service_Calendar::getNextRunDate($crontab, $_POST['start'], $repeat_until, $n)->format('U');

				$result->setRepeatMinutes($crontab[0]);
				$result->setRepeatHours($crontab[1]);
				$result->setRepeatDayOfMonth($crontab[2]);
				$result->setRepeatMonth($crontab[3]);
				$result->setRepeatDayOfWeek($crontab[4]);
				$result->setRepeatUntil(date('Y-m-d H:i:s', $repeat_until));
				$result->setDateNextRun(date('Y-m-d H:i:s', $next_run_date));
			} else {
				$result->setRepeatMinutes(new Zend_Db_Expr('NULL'));
				$result->setRepeatHours(new Zend_Db_Expr('NULL'));
				$result->setRepeatDayOfMonth(new Zend_Db_Expr('NULL'));
				$result->setRepeatMonth(new Zend_Db_Expr('NULL'));
				$result->setRepeatDayOfWeek(new Zend_Db_Expr('NULL'));
				$result->setRepeatUntil(new Zend_Db_Expr('NULL'));
				$result->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));
			}

			// on update change date modified
			$result->setDateModified(date('Y-m-d H:i:s'));

			// save event
			$result->save(false, true);
			$id = $result->getId();
    	}

		// send message
		if($_POST['type'] == 2) {
			// do html
			$reply = "{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}";
			$fake = $this->translate->_('%1$s has created the following <u>Event</u>: %2$s');
			$html = array(
				'%1$s has created the following <u>Event</u>: %2$s',
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				"<a style='color: #73a329;text-decoration: none;' href='{$reply}'>{$_POST['title']}</a>"
			);

			// send AMQPC
	    	\Petolio_Service_AMQPC::sendMessage('event', array($html, $reply, $this->auth->getIdentity()->id));
		}

   		// special event ? invite the selected users
		if($_POST['type'] == 2 && isset($_POST['users'])) {
			$notified = 0;
			foreach($_POST['users'] as $key) {
				// get the user
				$this->usr->find($key, $this->usr);
				if(!$this->usr->getId())
					continue;

				// see if he was already invited
				$this->att = new Petolio_Model_PoCalendarAttendees();
				$invited = $this->att->fetchList("calendar_id = '{$id}' AND user_id = '{$this->usr->getId()}'");
				if(count($invited) > 0)
					continue;

				// increment notified
				$notified++;

				// save attendee
				$this->att = new Petolio_Model_PoCalendarAttendees();
				$this->att->setCalendarId($id);
				$this->att->setUserId($this->usr->getId());
				$this->att->save();

				$showtime = !(isset($_POST['allDay']) && strcasecmp($_POST['allDay'], "true") == 0);
				
				// do html
				$reply = "{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}";
				$fake = $this->translate->_('%1$s has invited you to join the following <u>Event</u>: %2$s (%3$s)');
				$html = array(
					'%1$s has invited you to join the following <u>Event</u>: %2$s (%3$s)',
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$reply}'>{$_POST['title']}</a>",
					Petolio_Service_Util::formatDate($_POST['start'], Petolio_Service_Util::MEDIUMDATE, $showtime, $this->usr->getTimezone())
				);

				// send AMQPC
	    		\Petolio_Service_AMQPC::sendMessage('event', array($html, $reply, $this->auth->getIdentity()->id, $this->usr->getId()));

				// ups
				$html = sprintf($this->translate->_($html[0]), $html[1], $html[2], $html[3]);

				// add the delimiter
				$html .= '<br /><br />';

				// do message if any
				if (isset($_POST["message"]) && strlen($_POST["message"]) > 0) {
			    	$html .= sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>";
				}
// style='font-size: 14px; padding: 4px 10px 4px 10px; background-color: #CBE7CB; color: #004C00; border-radius: 5px;'
				// finish html
				$html .= sprintf(
					$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				);

				// notify users
		    	Petolio_Service_Message::send(array(
					'subject' => $this->translate->_("Event Invitation"),
					'message_html' => $html,
					'from' => $this->auth->getIdentity()->id,
					'status' => 1,
					'template' => 'calendar/appointment'
				), array(array(
					'id' => $this->usr->getId(),
					'name' => $this->usr->getName(),
					'email' => $this->usr->getEmail()
				)), $this->usr->isOtherEmailNotification());
			}
		}

    	// build json response
    	$response = array('success' => true, 'id' => $id);
    	if(isset($_POST['users']))
    		$response['all'] = $notified;

    	// send json
		return Petolio_Service_Util::json($response);
    }

    private function saveTodo()
    {
    	// no post ? BYE
    	if(empty($_POST))
    		die('gtfo noob');

    	// perform nulls
    	foreach($_POST as &$line)
    		if($line == 'null')
    			$line = new Zend_Db_Expr('NULL');

		$related = explode('_', $_POST['related']);
		$_POST['fee'] = $related[0] == 'm' ? $related[1] : new Zend_Db_Expr('NULL');
		$_POST['cap'] = $related[0] == 's' ? $related[1] : new Zend_Db_Expr('NULL');

    	// reset time if all day is checked
    	if($_POST['allDay'] == 'true')
			$_POST['start'] = date('Y-m-d', strtotime($_POST['start']));

		// calculate correct server timezone
    	$_POST['start'] = Petolio_Service_Util::calculateTimezone($_POST['start'], $_COOKIE['user_timezone'], true);

        // do we have an id ? that means its an update
        $result = array();
    	if(isset($_POST['id']))
    		$result = $this->cal->getMapper()->fetchList("id = '{$_POST['id']}' AND user_id = '{$this->auth->getIdentity()->id}'");

    	// get now
		$now = new DateTime('now');

    	// no event found ? insert new
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->cal->setUserId($this->auth->getIdentity()->id);
			$this->cal->setSubject($_POST['title']);

			$this->cal->setSpecies($_POST['species']); // pet id
			$this->cal->setMod(0); // not done
			$this->cal->setFee($_POST['fee']); // medical subentry
			$this->cal->setCap($_POST['cap']); // shot subentry

			$this->cal->setDescription($_POST['description']);
			$this->cal->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$this->cal->setDateEnd(new Zend_Db_Expr('NULL'));
			$this->cal->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$this->cal->setType($_POST['type']);
			$this->cal->setReminder($_POST['reminder']);
			$this->cal->setReminderTime($_POST['reminder_time']);
			$this->cal->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));

			// save event
			$this->cal->save(true, true);
			$id = $this->cal->getId();

		// event found ? update it
    	} else {
    		$result = reset($result);

    		// update info
    		$result->setUserId($this->auth->getIdentity()->id);
			$result->setSubject($_POST['title']);
    		$result->setDescription($_POST['description']);
			$result->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$result->setDateEnd(new Zend_Db_Expr('NULL'));
			$result->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$result->setType($_POST['type']);
			$result->setReminder($_POST['reminder']);
			$result->setReminderTime($_POST['reminder_time']);
			$result->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));

			// on update change date modified
			$result->setDateModified(date('Y-m-d H:i:s'));

			// save event
			$result->save(false, true);
			$id = $result->getId();
    	}

    	// build json response
    	$response = array('success' => true, 'id' => $id);

    	// send json
		return Petolio_Service_Util::json($response);
    }

    private function saveChat()
    {
    	// no post ? BYE
    	if(empty($_POST))
    		die('gtfo noob');

    	// perform nulls
    	foreach($_POST as &$line)
    		if($line == 'null')
    			$line = new Zend_Db_Expr('NULL');

        // reset time if all day is checked
    	if($_POST['allDay'] == 'true') {
			$_POST['start'] = date('Y-m-d', strtotime($_POST['start']));
			if(!is_object($_POST['end'])) // not null
				$_POST['end'] = date('Y-m-d', strtotime($_POST['end']));
		}
    		
    	// calculate correct server timezone
    	$_POST['start'] = Petolio_Service_Util::calculateTimezone($_POST['start'], $_COOKIE['user_timezone'], true);
    	if(!is_object($_POST['end'])) // not null
    		$_POST['end'] = Petolio_Service_Util::calculateTimezone($_POST['end'], $_COOKIE['user_timezone'], true);

        // do we have an id ? that means its an update
        $result = array();
    	if(isset($_POST['id']))
    		$result = $this->cal->getMapper()->fetchList("id = '{$_POST['id']}' AND user_id = '{$this->auth->getIdentity()->id}'");

    	// get now
		$now = new DateTime('now');

    	// no event found ? insert new
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->cal->setUserId($this->auth->getIdentity()->id);
			$this->cal->setSubject($_POST['title']);
			$this->cal->setDescription($_POST['description']);
			$this->cal->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$this->cal->setDateEnd(is_object($_POST['end']) /* only if null this = object */ ? $_POST['end'] : date('Y-m-d H:i:s', $_POST['end']));
			$this->cal->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$this->cal->setType($_POST['type']);
			$this->cal->setReminder(0);
			$this->cal->setReminderTime(new Zend_Db_Expr('NULL'));
			$this->cal->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));
			$this->cal->setAvailability($_POST['availability']);

			// on new set protection from cron, set as featured
			$oneh = new DateTime(date('Y-m-d H:i:s', $_POST['start']));
			$oneh->add(new DateInterval("PT1H"));
			$this->cal->setFee('1');
			$this->cal->setCap($oneh->format('U'));

			// save event
			$this->cal->save(true, true);
			$id = $this->cal->getId();

		// event found ? update it
    	} else {
    		$result = reset($result);

    		// notify people that the start date has been changed
    		if($result->getDateStart() != date('Y-m-d H:i:s', $_POST['start']))
				$this->dateNotify($result, 'chat', $_POST['start']);

    		// update info
    		$result->setUserId($this->auth->getIdentity()->id);
			$result->setSubject($_POST['title']);
    		$result->setDescription($_POST['description']);
			$result->setDateStart(date('Y-m-d H:i:s', $_POST['start']));
			$result->setDateEnd(is_object($_POST['end']) /* only if null this = object */ ? $_POST['end'] : date('Y-m-d H:i:s', $_POST['end']));
			$result->setAllDay($_POST['allDay'] == 'true' ? 1 : 0);
			$result->setType($_POST['type']);
			$result->setReminder(0);
			$result->setReminderTime(new Zend_Db_Expr('NULL'));
			$result->setDateNextRun(date('Y-m-d H:i:s', $_POST['start']));
			$result->setAvailability($_POST['availability']);

			// on update set protection from cron
			$oneh = new DateTime(date('Y-m-d H:i:s', $_POST['start']));
			$oneh->add(new DateInterval("PT1H"));
			$result->setCap($oneh->format('U'));

			// on update change date modified
			$result->setDateModified(date('Y-m-d H:i:s'));

			// save event
			$result->save(false, true);
			$id = $result->getId();
    	}

		// do html
		$reply = "{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}";
		$fake = $this->translate->_('%1$s has created the following <u>Chat Channel</u>: %2$s');
		$html = array(
			'%1$s has created the following <u>Chat Channel</u>: %2$s',
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a style='color: #73a329;text-decoration: none;' href='{$reply}'>{$_POST['title']}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('event', array($html, $reply, $this->auth->getIdentity()->id));

    	// invite the selected users
		if(isset($_POST['users'])) {
			$notified = 0;
			foreach($_POST['users'] as $key) {
				// get the user
				$this->usr->find($key, $this->usr);
				if(!$this->usr->getId())
					continue;

				// see if he was already invited
				$this->att = new Petolio_Model_PoCalendarAttendees();
				$invited = $this->att->fetchList("calendar_id = '{$id}' AND user_id = '{$this->usr->getId()}'");
				if(count($invited) > 0)
					continue;

				// increment notified
				$notified++;

				// save attendee
				$this->att = new Petolio_Model_PoCalendarAttendees();
				$this->att->setCalendarId($id);
				$this->att->setUserId($this->usr->getId());
				$this->att->save();

				$showtime = !(isset($_POST['allDay']) && strcasecmp($_POST['allDay'], "true") == 0);
				
				// do html
				$reply = "{$this->view->url(array('controller'=>'events'), 'default', true)}#{$id}";
				$fake = $this->translate->_('%1$s has invited you to join the following <u>Chat Channel</u>: %2$s (%3$s)');
				$html = array(
					'%1$s has invited you to join the following <u>Chat Channel</u>: %2$s (%3$s)',
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$reply}'>{$_POST['title']}</a>",
					Petolio_Service_Util::formatDate($_POST['start'], Petolio_Service_Util::MEDIUMDATE, $showtime, $this->usr->getTimezone())
				);

				// send AMQPC
	    		\Petolio_Service_AMQPC::sendMessage('event', array($html, $reply, $this->auth->getIdentity()->id, $this->usr->getId()));

				// ups
				$html = sprintf($this->translate->_($html[0]), $html[1], $html[2], $html[3]);

				// add the delimiter
				$html .= '<br /><br />';

				// do message if any
				if(isset($_POST["message"]) && strlen($_POST["message"]) > 0) {
			    	$html .= sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br(addcslashes($_POST['message'], "\000\\'\"\032"))  . "<br/><br/>";
				}

				// finish html
				$html .= sprintf(
					$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'accept', 'id' => $id), 'default', true)}'>".$this->translate->_('Accept')."</a>",
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'calendar', 'action'=>'decline', 'id' => $id), 'default', true)}'>".$this->translate->_('Decline')."</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				);

				// notify users
		    	Petolio_Service_Message::send(array(
					'subject' => $this->translate->_("Chat Channel Invitation"),
					'message_html' => $html,
					'from' => $this->auth->getIdentity()->id,
					'status' => 1,
					'template' => 'calendar/appointment'
				), array(array(
					'id' => $this->usr->getId(),
					'name' => $this->usr->getName(),
					'email' => $this->usr->getEmail()
				)), $this->usr->isOtherEmailNotification());
			}
		}

    	// build json response
    	$response = array('success' => true, 'id' => $id);
    	if(isset($_POST['users']))
    		$response['all'] = $notified;

    	// send json
		return Petolio_Service_Util::json($response);
    }

    /**
     * Notify people the start date was changed
     * @param object $event
     * @param string $what (event|chat)
     * @param int $date (the new date)
     */
    private function dateNotify($event, $what, $date) {
    	// notify only the attendees that are pending or accepted the invite
    	foreach($this->att->fetchList("calendar_id = '{$event->getId()}' AND (status = '0' OR status = '1')") as $attendee) {
    		// get the user
    		$this->usr->find($attendee->getUserId(), $this->usr);
    		if(!$this->usr->getId())
    			continue;

			// do html
			$html = sprintf(
				$this->translate->_('%1$s has changed the start date to the following %4$s: %2$s (%3$s)'),
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$event->getId()}'>{$event->getSubject()}</a>",
				Petolio_Service_Util::formatDate($date, Petolio_Service_Util::MEDIUMDATE, ($event->getAllDay() == 0), $this->usr->getTimezone()),
				$what == 'event' ? $this->translate->_('Event') : $this->translate->_('Chat Channel')
			);

			// notify users
	    	Petolio_Service_Message::send(array(
				'subject' => $what == 'event' ? $this->translate->_("Event Modified") : $this->translate->_("Chat Channel Modified"),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'calendar/appointment'
			), array(array(
				'id' => $this->usr->getId(),
				'name' => $this->usr->getName(),
				'email' => $this->usr->getEmail()
			)), $this->usr->isOtherEmailNotification());
    	}
    }

    public function deleteAction()
    {
    	// get event id
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event
		$event = reset($this->cal->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}'"));
		if(!$event) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		// get event attendees
		if($event->getType() == 2 || $event->getType() == 3) {
			// init form
			$form = new Petolio_Form_ResponseMessage();
			$this->view->form = $form;

			// set title
			$this->view->title = sprintf($this->translate->_("Delete %s"), $types[$event->getType()]);

			// did we submit form ? if not just return here
			if(!($this->request->isPost() && $this->request->getPost('submit')))
				return false;

			// is the form valid ? if not just return here
			if(!$form->isValid($this->request->getPost()))
				return false;

			// get data
			$data = $form->getValues();

			// get attendees
			$attendees = $this->att->getMapper()->fetchList("calendar_id = '{$event->getId()}'");
			foreach($attendees as $one) {
				// notify attendee that is pending or has accepted the invitation about the event deletion
				if($one->getStatus() != '2') {
					$this->usr->getMapper()->find($one->getUserId(), $this->usr);

					// html message
	    			$html = sprintf(
	    				$event->getType() == 3 ? $this->translate->_('%1$s has deleted %2$s') : $this->translate->_('%1$s has canceled %2$s'),
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
	    				$event->getSubject()
	    			);

					// do we have a message here?
					if(isset($data["message"]) && strlen($data["message"]) > 0) {
		    			$html .= "<br/><br/>" . sprintf(
		    				$this->translate->_("%s has sent you the following message:"),
						    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
					    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
					}

					// send message
			    	Petolio_Service_Message::send(array(
						'subject' => sprintf($event->getType() == 3 ? $this->translate->_("%s Deleted") : $this->translate->_("%s Canceled"), $types[$event->getType()]),
						'message_html' => $html,
						'from' => $this->auth->getIdentity()->id,
						'status' => 1,
						'template' => 'calendar/appointment'
					), array(array(
						'id' => $this->usr->getId(),
						'name' => $this->usr->getName(),
						'email' => $this->usr->getEmail()
					)), $this->usr->isOtherEmailNotification());
				}

				// delete atendee
				$one->deleteRowByPrimaryKey();
			}

			// message
			$this->msg->messages[] = sprintf($this->translate->_("Your %s has been successfully deleted."), $types[$event->getType()]);
			if(isset($attendees)) $this->msg->messages[] .= "<br />".$this->translate->_("People notified:")." ".count($attendees);
		} else {
			// message
			$this->msg->messages[] = sprintf($this->translate->_("Your %s has been successfully deleted."), $types[$event->getType()]);
		}

		// delete event
		$event->deleteRowByPrimaryKey();

		// redirect
		return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'calendar/index');
    }

	/**
	 * Mark as Done or Not Done for To-Do
	 */
    public function markAction()
    {
    	// get event id
		$id = $this->request->getParam('id');
		$ajax = $this->request->getParam('ajax');
		if(!$id) {
			if($ajax) die('gtfo noob');
			else {
				$this->msg->messages[] = $this->translate->_("Invalid request.");
				return $this->_helper->redirector('index', 'calendar');
			}
		}

		// get event
		$event = reset($this->cal->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id} AND type = 1'"));
		if(!$event) {
			if($ajax) die('gtfo noob');
			else {
				$this->msg->messages[] = $this->translate->_("Invalid request.");
				return $this->_helper->redirector('index', 'calendar');
			}
		}

    	// set switch
    	$event->setMod($event->getMod() == 1 ? 0 : 1)->save();

		// redirect
		if($ajax) Petolio_Service_Util::json(array('success' => true, 'value' => $event->getMod()));
		else return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'calendar/index');
    }

    public function attendeesAction()
    {
    	// get event id
		$id = $this->request->getParam('id');
		if(!$id)
			die('gtfo noob');

		// get event
		$this->cal->getMapper()->find($id, $this->cal);
		if(!$this->cal->getId())
			die('gtfo noob');

		// get attendees
		$list = array();
		$attendees = $this->att->getMapper()->fetchList("calendar_id = '{$this->cal->getId()}'");
		foreach($attendees as $one) {
			$this->usr->getMapper()->find($one->getUserId(), $this->usr);
			$array = array();

			$array['id'] = $this->usr->getId();
			$array['name'] = $this->usr->getName();
			$array['status'] = $one->getStatus();
			if($this->auth->getIdentity())
				$array['link'] = $one->getStatus() == 0 && $one->getType() == 1 ? $this->cal->getUserId() == $this->auth->getIdentity()->id ? $one->getId() : false : false;

			$list[] = $array;
		}

		return Petolio_Service_Util::json(array('success' => true, 'list' => $list));
    }

    public function acceptAction()
    {
    	// get event id
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event
		$this->cal->getMapper()->find($id, $this->cal);
		if(!$this->cal->getId()) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get the attendant
		$attendant = reset($this->att->getMapper()->fetchList("calendar_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}'"));
		if(!$attendant) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// user was not invited ?
		if($attendant->getType() != 0) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		if($attendant->getStatus() == 1) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already accepted the %s invitation."), $types[$this->cal->getType()]);
		} elseif($attendant->getStatus() == 2) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already declined the %s invitation."), $types[$this->cal->getType()]);
		} elseif($attendant->getStatus() == 0) {
			// init form
			$form = new Petolio_Form_ResponseMessage();
			$this->view->form = $form;

			// set title
			$this->view->title = sprintf($this->translate->_("Accept %s invitation"), $types[$this->cal->getType()]);

			// did we submit form ? if not just return here
			if(!($this->request->isPost() && $this->request->getPost('submit')))
				return false;

			// is the form valid ? if not just return here
			if(!$form->isValid($this->request->getPost()))
				return false;

			// get data
			$data = $form->getValues();

			// update to accepted
			$attendant->setStatus(1)->save(true, true);

			// get user
			$this->usr->getMapper()->find($this->cal->getUserId(), $this->usr);

			$html = sprintf(
    			$this->translate->_('%1$s accepted your invitation to the following %3$s: %2$s (%4$s)'),
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$this->cal->getId()}'>{$this->cal->getSubject()}</a>",
    			$types[$this->cal->getType()],
    			Petolio_Service_Util::formatDate($this->cal->getDateStart(), Petolio_Service_Util::MEDIUMDATE, ($this->cal->getAllDay() == 0), $this->usr->getTimezone())
    		);

			// do we have a message here?
			if(isset($data["message"]) && strlen($data["message"]) > 0) {
	    		$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
    			) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => sprintf($this->translate->_("Answer to your request: %s invitation"), $types[$this->cal->getType()]),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'calendar/appointment'
			), array(array(
				'id' => $this->usr->getId(),
				'name' => $this->usr->getName(),
				'email' => $this->usr->getEmail()
			)), $this->usr->isOtherEmailNotification());

			// message
			$this->msg->messages[] = sprintf($this->translate->_("You have successfully accepted the %s invitation."), $types[$this->cal->getType()]);
		}

		return $this->_helper->redirector('index', 'calendar');
    }

    public function declineAction()
    {
    	// get event id
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event
		$this->cal->getMapper()->find($id, $this->cal);
		if(!$this->cal->getId()) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get the attendant
		$attendant = reset($this->att->getMapper()->fetchList("calendar_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}'"));
		if(!$attendant) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// user was not invited ?
		if($attendant->getType() != 0) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		// if already declined
		if($attendant->getStatus() == 2)
			$this->msg->messages[] = sprintf($this->translate->_("You have already declined the %s invitation."), $types[$this->cal->getType()]);
		else {
			// init form
			$form = new Petolio_Form_ResponseMessage();
			$this->view->form = $form;

			// title
			$this->view->title = sprintf($this->translate->_("Decline %s invitation"), $types[$this->cal->getType()]);

			// did we submit form ? if not just return here
			if(!($this->request->isPost() && $this->request->getPost('submit')))
				return false;

			// is the form valid ? if not just return here
			if(!$form->isValid($this->request->getPost()))
				return false;

			// get data
			$data = $form->getValues();

			// update to declined
			$attendant->setStatus(2)->save(true, true);

			// get user
			$this->usr->getMapper()->find($this->cal->getUserId(), $this->usr);

			$html = sprintf(
    			$this->translate->_('%1$s declined your invitation to the following %3$s: %2$s (%4$s)'),
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$this->cal->getId()}'>{$this->cal->getSubject()}</a>",
    			$types[$this->cal->getType()],
    			Petolio_Service_Util::formatDate($this->cal->getDateStart(), Petolio_Service_Util::MEDIUMDATE, ($this->cal->getAllDay() == 0), $this->usr->getTimezone())
    		);

			// do we have a message here?
			if(isset($data["message"]) && strlen($data["message"]) > 0) {
	    		$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
    			) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => sprintf($this->translate->_("Answer to your request: %s invitation"), $types[$this->cal->getType()]),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'calendar/appointment'
			), array(array(
				'id' => $this->usr->getId(),
				'name' => $this->usr->getName(),
				'email' => $this->usr->getEmail()
			)), $this->usr->isOtherEmailNotification());

			// message
			$this->msg->messages[] = sprintf($this->translate->_("You have successfully declined the %s invitation."), $types[$this->cal->getType()]);
		}

		return $this->_helper->redirector('index', 'calendar');
    }

    /**
     * returns the pet_id and the service_id of a link
     */
    public function getLinkMembersAction()
    {
    	// no id or link ??
    	if(!$this->request->getParam('id') && !$this->request->getParam('link'))
    		return Petolio_Service_Util::json(array('success' => false));

    	$link_type = $this->request->getParam('link_type') ? $this->request->getParam('link_type') : 0;

    	// get link info
    	if ( isset($link_type) && intval($link_type) == 1 ) {
	        $link = new Petolio_Model_PoServiceMembersUsers();
	        $link->find($this->request->getParam('link'));
    	} else {
	        $link = new Petolio_Model_PoServiceMembersPets();
	        $link->find($this->request->getParam('link'));
    	}

        // get attendee status
        $status = Petolio_Service_Calendar::getStatus();
		$attendee = reset($this->att->getMapper()->fetchList("calendar_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('id'), Zend_Db::BIGINT_TYPE)." AND user_id != {$this->auth->getIdentity()->id}"));

        // return json
		if(isset($link_type) && intval($link_type) == 1) {
	        return Petolio_Service_Util::json(array(
	        	'success' => true,
	        	'user_id' => $link->getUserId(),
	        	'service_id' => $link->getServiceId(),
	        	'status' => $attendee ? $status[$attendee->getStatus()][$attendee->getType()][1] : false
	        ));
		} else {
	        return Petolio_Service_Util::json(array(
	        	'success' => true,
	        	'pet_id' => $link->getPetId(),
	        	'service_id' => $link->getServiceId(),
	        	'status' => $attendee ? $status[$attendee->getStatus()][$attendee->getType()][1] : false
	        ));
		}
    }

    /**
     * the adress data should be automatically prefilled, but changeable.
     * If I combine it with a service, the adress data of the service should be prefilled,
     * if they are not available take automatically the data of the service provider, otherwise leave them free.
     */
    public function getPrefilledDataAction() {
    	$data = $_POST;

    	$service_id = 0;
    	if (isset($_POST['link_id']) && strlen($_POST['link_id']) > 0 && strcasecmp($_POST['link_id'], 'null') != 0) {
    		$link = new Petolio_Model_PoServiceMembersPets();
    		$link->find($_POST['link_id']);
    		$service_id = $link->getServiceId();
    	} elseif (isset($_POST['service_id']) && strlen($_POST['service_id']) > 0 && strcasecmp($_POST['service_id'], 'null') != 0)
    		$service_id = $_POST['service_id'];

    	if ($service_id > 0) {
    		// get service address
    		$services = new Petolio_Model_PoServices();
    		$services->find($service_id);

    		$attr = new Petolio_Model_PoAttributes();
    		$attributes = reset($attr->getMapper()->getDbTable()->loadAttributeValues($services, false));

    		$data['street'] = $attributes['street']->getAttributeEntity()->getValue();
    		$data['address'] = $attributes['address']->getAttributeEntity()->getValue();
    		$data['zip'] = $attributes['zipcode']->getAttributeEntity()->getValue();
    		$data['location'] = $attributes['location']->getAttributeEntity()->getValue();
    		$data['countryId'] = $attributes['country']->getAttributeEntity()->getValue();
    		$data['lat'] = $services->getGpsLatitude();
    		$data['long'] = $services->getGpsLongitude();
    	} else {
    		// get logged in user's address
    		$data['street'] = $this->auth->getIdentity()->street;
    		$data['address'] = $this->auth->getIdentity()->address;
    		$data['zip'] = $this->auth->getIdentity()->zipcode;
    		$data['location'] = $this->auth->getIdentity()->location;
    		$data['countryId'] = $this->auth->getIdentity()->country_id;
    		$data['lat'] = $this->auth->getIdentity()->gps_latitude;
    		$data['long'] = $this->auth->getIdentity()->gps_longitude;
    	}

    	$response = array('success' => true, 'results' => $data);

    	// send json
		return Petolio_Service_Util::json($response);
    }
}