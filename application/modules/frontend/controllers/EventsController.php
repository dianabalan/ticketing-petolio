<?php

class EventsController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $auth = null;
	private $request = null;
	private $cfg = null;

   	private $users = null;
	private $cal = null;
	private $att = null;
	private $mess = null;
	private $reci = null;
	
	private $europe = array(48.690832999999998, 9.140554999999949);

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
		$this->cfg = Zend_Registry::get("config");

		$this->users = new Petolio_Model_PoUsers();
		$this->cal = new Petolio_Model_PoCalendar();
		$this->att = new Petolio_Model_PoCalendarAttendees();
		$this->mess = new Petolio_Model_PoMessages();
		$this->reci = new Petolio_Model_PoMessageRecipients();
	}

	/**
	 * Build search filter
	 * @return array
	 */
	private function buildSearchFilter() {
		$filter = array();
		$title = sprintf($this->translate->_("All Current / Upcoming Events %s"), sprintf("(%s)", sprintf($this->translate->_("during the next %s days"), $this->cfg['events']['days'])));

		if (strlen($this->request->getParam('name'))) {
			$name = strtolower($this->request->getParam('name'));
			$filter[] = "(c.subject LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." " .
				"OR c.description LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." " .
				"OR c.street LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." " .
				"OR c.address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." " .
				"OR c.zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." " .
				"OR c.location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%").")";

			$this->view->search_name = $this->request->getParam('name');
			$title = sprintf($this->translate->_("Results, Keywords: %s"), $this->request->getParam('name'));
		}

		$this->view->title = $title;
		return $filter;
	}

	/**
	 * Index action (get user's logged in events + all upcomming special events)
	 */
	public function indexAction()
	{
    	// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species = json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods = json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
    	$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// figure out more
		$more = $this->request->getParam('more') ? intval($this->request->getParam('more')) : 1;
		$this->view->more = $more;
		$more = $more * intval($this->cfg['events']['days']);
		$this->view->events_days = $this->cfg['events']['days'];

		// start and end date
		$start = new DateTime('now');
		$end = clone $start;
		$end->add(new DateInterval('P'. ($more) .'D'));
		$end->setTime(0, 0, 0);

		// get your upcoming events
		if ($this->auth->hasIdentity()) {
			$this->view->yours_json = json_encode(Petolio_Service_Event::loadYourEvents($start, $end));
		}

		// get all upcoming events
		$this->view->all_json = $this->loadAllEvents($start, $end);
		
		// init map
		$this->view->coords = $this->europe;
	}

	/**
	 * Load all upcomming special events
	 * @param DateTime $the_start - start date
	 * @param DateTime $the_end - end date
	 * @return json events
	 */
	private function loadAllEvents($the_start, $the_end, $encode = true, $extra_filter = array()) {
		
		// default sort
		$sort = "c.date_start ASC";
		
		// filter
		$filter = $this->buildSearchFilter();
		$filter = array_merge($filter, $extra_filter);
		
		// get all special events
		$result = $this->cal->getMapper()->browseAllEvents($filter, $this->auth->hasIdentity() ? $this->auth->getIdentity()->id : false, $sort, $the_start, $the_end);

		// format special events
		$out = array();
		$i = 10000;
		foreach($result as $line) {
			$array = Petolio_Service_Calendar::format($line);
			if($this->auth->getIdentity())
				$array['requested'] = true;

			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			$out[$i++] = $array;
		}

		return $encode ? json_encode($out) : $out;
	}

	/**
	 * View action
	 * in case the event no longer exists in the event list
	 */
    public function viewAction()
    {
		// get event id
		$id = $this->request->getParam('id');
		if(!$id)
			die('gtfo noob');

		// get event
		$results = reset($this->cal->getMapper()->getEvents("c.id = {$id}"));
		if(!$results->getId())
			die('gtfo noob');

    	// get the attendant
    	$requested = $invited = $accepted = false;
    	if ($this->auth->getIdentity()) {
			$attendant = reset($this->att->getMapper()->fetchList("calendar_id = '{$id}' AND user_id = '{$this->auth->getIdentity()->id}'"));
			$requested = $this->auth->getIdentity()->id != $results->getUserId() && !$attendant ? true : false;
			$invited = $attendant && $attendant->getType() == 0 && $attendant->getStatus() == 0 ? true : false;
			$accepted = $attendant && $attendant->getType() == 0 && $attendant->getStatus() == 1 ? true : false;
			$access = $attendant && $attendant->getStatus() == 1 ? true : false;
    	}

		// format event
		$event = Petolio_Service_Calendar::format($results);
		$event['requested'] = $requested;
		$event['invited'] = $invited;
		$event['accepted'] = $accepted;
		$event['access'] = $access;

		// return the event
		return Petolio_Service_Util::json(array('success' => true, 'event' => $event));
    }

	/**
	 * Print action
	 * in case the event no longer exists in the event list
	 */
    public function printAction()
    {
    	// vars
    	$save = @$this->request->getParam('save');
    	$load = @$this->request->getParam('load');

    	// save event data in session
    	if($save) {
    		$_SESSION['print_event'] = array(
    			'data' => @$_POST['info'],
    			'timestamp' => time()
    		);

			return Petolio_Service_Util::json(array('success' => true));
    	}

    	// load event data from session
    	if($load) {
	    	// disable layout for print
			$this->_helper->layout->disableLayout();

			// return the event
			$this->view->event = $_SESSION['print_event'];
			return true;
    	}

    	// not in if's? gtfo
    	die('gtfo');
    }

	/**
	 * Join Event Action
	 */
	public function joinAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to join.");
			return $this->_helper->redirector('index', 'site');
		}

		// get event id
		$id = $this->request->getParam('id');
		if(!$id)
			die('gtfo noob');

		// get event
		$this->cal->getMapper()->find($id, $this->cal);
		if(!$this->cal->getId())
			die('gtfo noob');

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		// can't attend your own special event, duh
		if($this->auth->getIdentity()->id == $this->cal->getUserId()) {
			$this->msg->messages[] = sprintf($this->translate->_("You can't join your own %s."), $types[$this->cal->getType()]);
			return $this->_helper->redirector('index', 'site');
		}

		// get the event owner
		$this->users->find($this->cal->getUserId());

		// check if event is private
		if($this->isEventPrivate($this->cal->getAvailability(), $this->users)) {
			// figure out the message
			switch($this->cal->getAvailability()) {
				case 1: $this->msg->messages[] = sprintf($this->translate->_("This %s is available only for the owner's friends."), $types[$this->cal->getType()]); break;
				case 2: $this->msg->messages[] = sprintf($this->translate->_("This %s is available only for the owner's partners."), $types[$this->cal->getType()]); break;
				case 3: $this->msg->messages[] = sprintf($this->translate->_("This %s is available only for the owner's friends and partners."), $types[$this->cal->getType()]); break;
				case 4: $this->msg->messages[] = sprintf($this->translate->_("This %s is available only with invitation."), $types[$this->cal->getType()]); break;
			}

			// and then redirect
			return $this->_helper->redirector('index', 'site');
		}

		// get the attendant
		$continue = false;
		$attendant = reset($this->att->getMapper()->fetchList("calendar_id = '{$id}' AND user_id = '{$this->auth->getIdentity()->id}'"));
		if($attendant) {
			if($attendant->getType() == 0) {
				if($attendant->getStatus() == 0)
					$this->msg->messages[] = sprintf($this->translate->_("The %s owner has already invited you to join."), $types[$this->cal->getType()]);

				if($attendant->getStatus() == 1)
					$this->msg->messages[] = sprintf($this->translate->_("You have already accepted the %s invitation."), $types[$this->cal->getType()]);

				if($attendant->getStatus() == 2)
					$continue = $attendant;
			} else {
				if($attendant->getStatus() == 0)
					$this->msg->messages[] = sprintf($this->translate->_("You have already requested joining this %s, please wait for the event owner to accept or decline you."), $types[$this->cal->getType()]);

				if($attendant->getStatus() == 1)
					$this->msg->messages[] = sprintf($this->translate->_("The %s owner has already accepted your join request."), $types[$this->cal->getType()]);

				if($attendant->getStatus() == 2)
					$this->msg->messages[] = sprintf($this->translate->_("The %s owner has declined your join request."), $types[$this->cal->getType()]);
			}

			if(!$continue)
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'events');
		}

		// update if contiune or insert if new
		$save = $continue ? $continue : $this->att;

		// init form
		$form = new Petolio_Form_ResponseMessage();
		$this->view->form = $form;

		// set title
		$this->view->title = sprintf($this->translate->_("Join %s"), $types[$this->cal->getType()]);

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// no attendant found ? excelent.
		$save->setCalendarId($id);
		$save->setUserId($this->auth->getIdentity()->id);
		$save->setType(1);
		$save->setStatus(0);
		$save->save(true, true);

		$html = sprintf(
			$this->translate->_('%1$s requested to join %2$s (%3$s time)'),
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$this->cal->getId()}'>{$this->cal->getSubject()}</a>",
			Petolio_Service_Util::formatDate($this->cal->getDateStart(), Petolio_Service_Util::MEDIUMDATE, true, $this->users->getTimezone())
		) . '<br /><br />';
		if (isset($data['message']) && strlen($data['message']) > 0) {
   			$html .= sprintf(
    			$this->translate->_("%s has sent you the following message:"),
			    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']))  . "<br/><br/>";
		}
		$html .= sprintf(
			$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
			"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'events', 'action'=>'accept', 'id' => $save->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
			"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'events', 'action'=>'decline', 'id' => $save->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		);

		// send message
		Petolio_Service_Message::send(array(
			'subject' => sprintf($this->translate->_("New Join %s Request"), $types[$this->cal->getType()]),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $this->users->getId(),
			'name' => $this->users->getName(),
			'email' => $this->users->getEmail()
		)), $this->users->isOtherEmailNotification());

		// output the message and redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have successfully joined this %s. You will be notified when the owner accepts or declines your request.", $types[$this->cal->getType()]));
		return $this->_helper->redirector('index', 'events');
	}

	/**
	 * Check if event is private
	 * @param int $ava - Event Availability
	 * @param object $user - Event Owner Object
	 *
	 * @return bool - Private or not
	 */
	private function isEventPrivate($ava = 0, $user) {
		// lets say its not private to begin with
		$private = false;

		// get the user logged in id alias
		$logged = $this->auth->getIdentity()->id;

		// switch between availability
		switch($ava) {
			// everyone
			case 0:
				// do nothing
			break;

			// friends
			case 1:
				// load event owner's friends
				$friends = array();
				foreach($user->getUserFriends() as $row)
					$friends[$row->getId()] = array('name' => $row->getName());

				// match against all friends
				if(!array_key_exists($logged, $friends))
					$private = true;
			break;

			// partners
			case 2:
				// load event owner's partners
				$partners = array();
				foreach($user->getUserPartners() as $row)
					$partners[$row->getId()] = array('name' => $row->getName());

				// match against all partners
				if(!array_key_exists($logged, $partners))
					$private = true;
			break;

			// friends and partners
			case 3:
				$all = array();

				// load event owner's friends
				foreach($user->getUserFriends() as $row)
					$all[$row->getId()] = array('name' => $row->getName());

				// load event owner's partners
				foreach($user->getUserPartners() as $row)
					$all[$row->getId()] = array('name' => $row->getName());

				// match against all friends and partners
				if(!array_key_exists($logged, $all))
					$private = true;
			break;

			// invite only (no possibility of joining... EVER)
			case 4:
				$private = true;
			break;
		}

		// return verdict
		return $private;
	}

	/**
	 * Accept Invitation Action
	 */
	public function acceptAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept an invitation.");
			return $this->_helper->redirector('index', 'site');
		}

		// flag
		$success = false;

		// get event id
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get the attendant
		$this->att->getMapper()->find($id, $this->att);
		if(!$this->att->getId()) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		// init form
		$form = new Petolio_Form_ResponseMessage();
		$this->view->form = $form;

		// get event
		$event = reset($this->cal->getMapper()->fetchList("id = '{$this->att->getCalendarId()}' AND user_id = '{$this->auth->getIdentity()->id}' AND (type = '2' OR type = '3')"));
		if(!$event) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// set title
		$this->view->title = sprintf($this->translate->_("Accept %s join"), $types[$event->getType()]);

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// user was not requested ?
		if($this->att->getType() != 1) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		if ($this->att->getStatus() == 1) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already accepted the %s request."), $types[$event->getType()]);
		} elseif ($this->att->getStatus() == 2) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already declined the %s request."), $types[$event->getType()]);
		} elseif ($this->att->getStatus() == 0) {
			// update to accepted
			$this->att->setStatus(1)->save(true, true);

			// get user
			$this->users->getMapper()->find($this->att->getUserId(), $this->users);

			$html = sprintf(
				$this->translate->_('%1$s has accepted your request to join %2$s (%3$s time)'),
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$event->getId()}'>{$event->getSubject()}</a>",
				Petolio_Service_Util::formatDate($event->getDateStart(), Petolio_Service_Util::MEDIUMDATE, true, $this->users->getTimezone())
			);
			if (isset($data['message']) && strlen($data['message']) > 0) {
	    		$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
			Petolio_Service_Message::send(array(
				'subject' => sprintf($this->translate->_("%s Join Request Accepted"), $types[$event->getType()]),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $this->users->getId(),
				'name' => $this->users->getName(),
				'email' => $this->users->getEmail()
			)), $this->users->isOtherEmailNotification());
		}

		$this->msg->messages[] = sprintf($this->translate->_("You have successfully accepted the %s join request."), $types[$event->getType()]);
		return $this->_helper->redirector('index', 'calendar');
	}

	/**
	 * Decline Invitation Action
	 */
	public function declineAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline an invitation.");
			return $this->_helper->redirector('index', 'site');
		}

		// flag
		$success = false;

   		// get event id
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get the attendant
		$this->att->getMapper()->find($id, $this->att);
		if(!$this->att->getId()) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// get event types
		$types = Petolio_Service_Calendar::getTypes();

		// init form
		$form = new Petolio_Form_ResponseMessage();
		$this->view->form = $form;

		// get event
		$event = reset($this->cal->getMapper()->fetchList("id = '{$this->att->getCalendarId()}' AND user_id = '{$this->auth->getIdentity()->id}' AND (type = '2' OR type = '3')"));
		if(!$event) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		// set title
		$this->view->title = sprintf($this->translate->_("Decline %s join"), $types[$event->getType()]);

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// user was not requested ?
		if($this->att->getType() != 1) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'calendar');
		}

		if($this->att->getStatus() == 1) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already accepted the %s request."), $types[$event->getType()]);
		} elseif($this->att->getStatus() == 2) {
			$this->msg->messages[] = sprintf($this->translate->_("You have already declined the %s request."), $types[$event->getType()]);
		} elseif($this->att->getStatus() == 0) {
			// update to accepted
			$this->att->setStatus(2)->save(true, true);

			// get user
			$this->users->getMapper()->find($this->att->getUserId(), $this->users);

			$html = sprintf(
				$this->translate->_('%1$s has declined your request to join %2$s (%3$s time)'),
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'events'), 'default', true)}#{$event->getId()}'>{$event->getSubject()}</a>",
				Petolio_Service_Util::formatDate($event->getDateStart(), Petolio_Service_Util::MEDIUMDATE, true, $this->users->getTimezone())
			);

			if (isset($data['message']) && strlen($data['message']) > 0) {
    			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
			Petolio_Service_Message::send(array(
				'subject' => sprintf($this->translate->_("%s Join Request Declined"), $types[$event->getType()]),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $this->users->getId(),
				'name' => $this->users->getName(),
				'email' => $this->users->getEmail()
			)), $this->users->isOtherEmailNotification());

			// message
			$this->msg->messages[] = sprintf($this->translate->_("You have successfully declined the %s join request."), $types[$event->getType()]);
		}

		return $this->_helper->redirector('index', 'calendar');
	}
	
	/**
	 * Find events near gps coordinate
	 */
	private function findEventsNearLocation($filter = array(), $lat, $lng, $rad, $the_start, $the_end) {
		// set radius
		$difference = (float)($rad != 0 ? number_format(($rad / 111), 2) : 0.07);
	
		// build filter
		if((isset($lat) && strlen($lat) > 0) && (isset($lng) && strlen($lng) > 0)) {
			// http://en.wikipedia.org/wiki/Pythagorean_theorem
			$filter[] = "POW(c.gps_latitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lat)).", 2) + " .
					"POW(c.gps_longitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lng)).", 2) <= " .
					"POW(".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($difference).", 2)";
		} else {
			if(isset($lat) && strlen($lat) > 0) {
				$latitude_from = floatval($lat) - $difference;
				$latitude_to = floatval($lat) + $difference;
	
				$filter[] = "c.gps_latitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_from);
				$filter[] = "c.gps_latitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_to);
			}
			if(isset($lng) && strlen($lng) > 0) {
				$longitude_from = floatval($lng) - $difference;
				$longitude_to = floatval($lng) + $difference;
	
				$filter[] = "c.gps_longitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_from);
				$filter[] = "c.gps_longitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_to);
			}
		}

		// return events
		return $this->loadAllEvents($the_start, $the_end, false, $filter);
	}
	
	/**
	 * Find events between 2 gps points
	 */
	private function findEventsBetweenPoint($filter = array(), $lat_from, $lat_to, $lng_from, $lng_to, $the_start, $the_end) {
		// build filter
		if(isset($lat_to) && strlen($lat_to) > 0 && isset($lat_from) && strlen($lat_from) > 0) {
			$filter[] = "c.gps_latitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lat_from);
			$filter[] = "c.gps_latitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lat_to);
		}
		if(isset($lng_to) && strlen($lng_to) > 0 && isset($lng_from) && strlen($lng_from) > 0) {
			$filter[] = "c.gps_longitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lng_from);
			$filter[] = "c.gps_longitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lng_to);
		}
	
		// return events
		return $this->loadAllEvents($the_start, $the_end, false, $filter);
	}
	
	/**
	 * Find events randomly
	 */
	private function findUserEventsRandomly($filter = array(), $the_start, $the_end) {
		// build filter
		$filter[] = "c.gps_latitude IS NOT NULL";
		$filter[] = "c.gps_longitude IS NOT NULL";
	
		// return events
		return $this->loadAllEvents($the_start, $the_end, false, $filter);
	}
	
	/*
	 * Google map markers ajax call
	*/
	public function getmarketsAction() {
		// marker params
		$filters = $this->request->getParam("filters");
		$lat = $this->request->getParam("latitude");
		$lng = $this->request->getParam("longitude");
		$lat_to = $this->request->getParam("latitude_to");
		$lat_from = $this->request->getParam("latitude_from");
		$lng_to = $this->request->getParam("longitude_to");
		$lng_from = $this->request->getParam("longitude_from");
		$radius = $this->request->getParam("radius");
	
		// figure out more
		$more = $this->request->getParam('more') ? intval($this->request->getParam('more')) : 1;
		$this->view->more = $more;
		$more = $more * intval($this->cfg['events']['days']);
		$this->view->events_days = $this->cfg['events']['days'];
		
		// start and end date
		$start = new DateTime('now');
		$end = clone $start;
		$end->add(new DateInterval('P'. ($more) .'D'));
		$end->setTime(0, 0, 0);
		
		// decode filters
		$filters = unserialize(base64_decode($filters));
	
		// load based on user action
		if(isset($lat) && (isset($lng)))
			$events = $this->findEventsNearLocation($filters, $lat, $lng, $radius, $start, $end);
		elseif(isset($lat_to) && isset($lat_from) && isset($lng_from) && isset($lng_to))
			$events = $this->findEventsBetweenPoint($filters, $lat_from, $lat_to, $lng_from, $lng_to, $start, $end);
		else
			$events = $this->findUserEventsRandomly($filters, $start, $end);

		// format for google map
		$markets = array();
		foreach($events as $event) {
			$markets[] = array(
					"id"		=> $event['id'],
					"name"		=> $event['title'],
					"latitude"	=> $event['lat'],
					"longitude"	=> $event['long'],
					"username" 	=> $event['user_name'],
					"userid"	=> $event['user_id'],
					"start"		=> $event['formatted_start'],
					"view"		=> $this->translate->_("View event")
			);
		}
	
		// output json
		Petolio_Service_Util::json(array(
			'success' => true,
			'count' => sizeof($markets),
			'items' => $markets
		));
	}
	
}