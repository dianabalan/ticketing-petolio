<?php

/**
 * Chat Controller
 */
class ChatController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $request = null;
	private $config = null;

	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");

		// load models
		$this->db = new stdClass();
		$this->db->cal = new Petolio_Model_PoCalendar();
		$this->db->att = new Petolio_Model_PoCalendarAttendees();
		$this->db->chat = new Petolio_Model_PoChat();
		$this->db->users = new Petolio_Model_PoUsers();

		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			$this->msg->messages[] = $this->translate->_("You must be logged in.");
			return $this->_redirect('site');
		}
	}

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}
	
	private function getChatAttendees($calendar_id = 0, $owner_id) {
		$list = array();
		$cal_attendees = new Petolio_Model_PoCalendarAttendees();
		$attendees = $cal_attendees->fetchList("calendar_id = '{$calendar_id}' AND status != 2", "status DESC");
		$user = new Petolio_Model_PoUsers();
		$count = 0;
		foreach($attendees as $one) {
			if ( $count < 4 ) {
				$user->find($one->getUserId());
				$array = array();
					
				$array['id'] = $user->getId();
				$array['avatar'] = $user->getAvatar();
				$array['name'] = $user->getName();
				$array['status'] = $one->getStatus();
				if($this->auth->getIdentity()) {
					$array['link'] = $one->getStatus() == 0 && $one->getType() == 1 ? 
							$owner_id == $this->auth->getIdentity()->id ? $one->getId() : false : false;
				}
					
				$list[] = $array;
			}
			$count++;
		}
		
		return array('count' => count($attendees), 'list' => $list);
	}

	/**
	 * Index
	 */
	public function indexAction() {
		$live_chats = $this->db->cal->getMapper()->browseLiveChats($this->auth->getIdentity()->id);
		$future_chats= $this->db->cal->getMapper()->browseFutureChats($this->auth->getIdentity()->id);

		$this->view->live_chats = array();
		foreach ($live_chats as $key => $chat) {
			$array = Petolio_Service_Calendar::format($chat);
			if($this->auth->getIdentity()) {
				$array['invited'] = $chat['atype'] === '0' && $chat['astatus'] === '0' ? true : false;
				$array['accepted'] = $chat['atype'] === '0' && $chat['astatus'] === '1' ? true : false;
				$array['access'] = $chat['astatus'] == 1 ? true : false;
			}
			
			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			
			// get attendees
			$attendees = $this->getChatAttendees($chat['id'], $chat['user_id']);
			$array['attendees'] = $attendees['list'];
			$array['attendees_count'] = $attendees['count'];
			
			$this->view->live_chats[] = $array;
		}

		$this->view->future_chats = array();
		foreach ($future_chats as $key => $chat) {
			$array = Petolio_Service_Calendar::format($chat);
			if($this->auth->getIdentity()) {
				$array['invited'] = $chat['atype'] === '0' && $chat['astatus'] === '0' ? true : false;
				$array['accepted'] = $chat['atype'] === '0' && $chat['astatus'] === '1' ? true : false;
				$array['access'] = $chat['astatus'] == 1 ? true : false;
			}
			
			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			
			// get attendees
			$attendees = $this->getChatAttendees($chat['id'], $chat['user_id']);
			$array['attendees'] = $attendees['list'];
			$array['attendees_count'] = $attendees['count'];
			
			$this->view->future_chats[] = $array;
		}
		
		$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;
		
	}

	/**
	 * View a chat channel
	 */
	public function viewAction() {
		$this->view->chat = true;
		
		// append the chat css
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/chat.css'));
		
		// get calendar
		$id = @(int)$this->request->getParam('id');
		$cal = $this->db->cal->find($id);
		if(!$cal->getId()) {
			$this->msg->messages[] = $this->translate->_("Entity does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// not chat channel?
		if($cal->getType() != 3) {
			$this->msg->messages[] = $this->translate->_("Entity is not a chat channel.");
			return $this->_helper->redirector('index', 'site');
		}

		// does the user have permission on this chat channel ?
		$permission = $this->db->att->fetchList("calendar_id = '{$cal->getId()}' AND user_id = '{$this->auth->getIdentity()->id}' AND status = '1'");
		if(!($cal->getAvailability() == 0 || count($permission) > 0 || $cal->getUserId() == $this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You don't have permission to access this chat channel.");
			return $this->_helper->redirector('index', 'site');
		}

		// set now with timezone
		$now = new DateTime();
		$now->setTimestamp(Petolio_Service_Util::calculateTimezone(time(), @$_COOKIE['user_timezone']));

		// check the time, and allow users to enter chat on time or 30 minutes before
		$start = new DateTime();
		$start->setTimestamp(Petolio_Service_Util::calculateTimezone($cal->getDateStart(), @$_COOKIE['user_timezone']));
		$start->sub(new DateInterval('PT30M'));
		if($now < $start) {
			$this->msg->messages[] = sprintf($this->translate->_("This Chat Channel is not open yet, you can only join it starting from %s."), Petolio_Service_Util::formatDate($start->format('U'), Petolio_Service_Util::MEDIUMDATE, true, true, true));
			return $this->_helper->redirector('index', 'site');
		}

		// check if the chat channel is closed
		if(!is_null($cal->getDateEnd())) {
			$end = new DateTime();
			$end->setTimestamp(Petolio_Service_Util::calculateTimezone($cal->getDateEnd(), @$_COOKIE['user_timezone']));
			if($now > $end) {
				$this->msg->messages[] = sprintf($this->translate->_("This Chat Channel has been closed since %s."), Petolio_Service_Util::formatDate($end->format('U'), Petolio_Service_Util::MEDIUMDATE, true, true, true));
				return $this->_helper->redirector('index', 'site');
			}
		}

		// okay everything seems to be in order, send chat id and name to view
		$this->view->id = $cal->getId();
		$this->view->name = $cal->getSubject();
		$this->view->owner = $cal->getUserId();

		// featured ?
		$this->view->featured = $cal->getFee() == 1 ? true : false;
		$this->view->featured_bermuda_triangle = time() <= strtotime($cal->getDateStart()) ? Petolio_Service_Util::formatTime(strtotime($cal->getDateStart())) : false;

		// info box
		$user = $this->db->users->find($cal->getUserId());

		// see if the pet owner is active and not banned
		if(!($user->getActive() == 1 && $user->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		$this->view->owner_url = $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $cal->getUserId()), 'default', true);
		$this->view->owner_name = $user->getName();
		$this->view->start = Petolio_Service_Util::formatDate(Petolio_Service_Util::calculateTimezone($cal->getDateStart(), @$_COOKIE['user_timezone']), Petolio_Service_Util::MEDIUMDATE, ($cal->getAllDay() != 1), true, true);

		// get end date
		if(!is_null($cal->getDateEnd()))
			$this->view->end = Petolio_Service_Util::formatDate(Petolio_Service_Util::calculateTimezone($cal->getDateEnd(), @$_COOKIE['user_timezone']), Petolio_Service_Util::MEDIUMDATE, ($cal->getAllDay() != 1), true, true);

		// get description
		if(!is_null($cal->getDescription()))
			$this->view->description = $cal->getDescription();
	}

	/**
	 * Load information about a user
	 */
	public function participantAction() {
		// get user
		$id = @(int)$this->request->getParam('id');
		$user = $this->db->users->find($id);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No user found'
			));

		// object or array
		$avatar = "/images/no-avatar.jpg";
		$user = $user->toArray();

		// avatar control
		if(!is_null($user['avatar'])) {
			$ds = DIRECTORY_SEPARATOR;
			$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$user['id']}{$ds}thumb_{$user['avatar']}";

			// get cache
			if(is_file($image)) {
				$cache = filemtime($image);
				$avatar = "/images/userfiles/avatars/{$user['id']}/thumb_{$user['avatar']}?{$cache}";
			}
		}

		// get type & return json
		$types = array("1" => $this->translate->_("Pet Owner"), "2" => $this->translate->_("Service Provider"));
		return Petolio_Service_Util::json(array(
			'success' => true,
			'name' => $user['name'],
			'avatar' => $avatar,
			'type' => $types[$user['type']],
			'url' => $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user["id"]), 'default', true),
		));
	}

	/**
	 * Log messages sent 4 history
	 */
	public function logAction() {
		// get vars
		$user = @(int)$this->request->getParam('user');
		$msg = @(string)$this->request->getParam('msg');

		// no user or message?
		if(!$user || !$msg)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// get user
		$user = $this->db->users->find($user);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No user found'
			));

		// get channel
		$channel = @(int)$this->request->getParam('channel');
		$cal = $this->db->cal->find($channel);
		if(!$cal->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No channel found'
			));

		// save message log
		$this->db->chat->setOptions(array(
			'calendar_id' => $cal->getId(),
			'user_id' => $user->getId(),
			'message' => $msg
		))->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Get logged messages for history
	 */
	public function getAction() {
		// get vars
		$channel = @(int)$this->request->getParam('channel');
		$history = @(string)$this->request->getParam('history');
		list($undefined, $period) = explode('_', $history);

		// get channel
		$cal = $this->db->cal->find($channel);
		if(!$cal->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No channel found'
			));

		// history time
		$history = new DateTime('now');
		$history->sub(new DateInterval("P{$period}D"));

		// get history
		$messages = $this->db->chat->getMessages("a.calendar_id = {$cal->getId()} AND UNIX_TIMESTAMP(a.date_created) >= '{$history->format('U')}'", "a.date_created ASC");

		// correct timestamp and add user link
		foreach($messages as &$msg) {
			$msg['date_created'] = Petolio_Service_Util::formatDate($msg['date_created'], Petolio_Service_Util::SHORTDATE, true, true);
			$msg['user_url'] = $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $msg['user_id']), 'default', true);

			// object or array
			$avatar = "/images/no-avatar.jpg";

			// avatar control
			if(!is_null($msg['user_avatar'])) {
				$ds = DIRECTORY_SEPARATOR;
				$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$msg['user_id']}{$ds}thumb_{$msg['user_avatar']}";

				// get cache
				if(is_file($image)) {
					$cache = filemtime($image);
					$avatar = "/images/userfiles/avatars/{$msg['user_id']}/thumb_{$msg['user_avatar']}?{$cache}";
				}
			}

			// save avatar
			$msg['user_avatar'] = $avatar;
		}

		// return json
		return Petolio_Service_Util::json(array('success' => true, 'history' => $messages));
	}

	/**
	 * Make a chat channel featured (with 1 hour protection)
	 */
	public function featureAction() {
		// get calendar
		$id = @(int)$this->request->getParam('id');
		$cal = $this->db->cal->find($id);
		if(!$cal->getId()) {
			$this->msg->messages[] = $this->translate->_("Entity does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// not chat channel?
		if($cal->getType() != 3) {
			$this->msg->messages[] = $this->translate->_("Entity is not a chat channel.");
			return $this->_helper->redirector('index', 'site');
		}

		// not owner ?
		if(!($cal->getUserId() == $this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You don't have permission to access this chat channel.");
			return $this->_helper->redirector('index', 'site');
		}

		// in 1 hour
		$in = new DateTime('now');
		$in->add(new DateInterval("PT1H"));

		// set as featured with 1h protection
		$cal->setFee(1)->setCap($in->format('U'))->save();

		// redirect with message
		$this->msg->messages[] = $this->translate->_("You have successfully featured this Chat Channel on Petolio's homepage.");
		return $this->_redirect('chat/view/id/'. $cal->getId());
	}

	/**
	 * Kick user from channel
	 */
	public function kickAction() {
		// get vars
		$user = @(int)$this->request->getParam('user');
		$channel = @(string)$this->request->getParam('channel');

		// no user or message?
		if(!$user || !$channel)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// get user
		$user = $this->db->users->find($user);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No user found'
			));

		// get channel
		$cal = $this->db->cal->find($channel);
		if(!$cal->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No channel found'
			));

		// are we not the owner of this channel?
		if($cal->getUserId() != $this->auth->getIdentity()->id)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'Not channel owner'
			));

		// are we kicking the owner?
		if($cal->getUserId() == $user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'Cannot kick channel owner'
			));

		// send kick command
		$cmd = array(
			array(
				'cmd' => 'inlinepush',
				'params' => array(
					'password' => $this->config["chat"]["password"],
					'raw' => 'kick',
						'channel' => $cal->getId(),
						'data' => array('user' => $user->getId())
					)
			)
		);

		// send to server and return json
		$data = file_get_contents("http://" . $this->config["chat"]["server"] . "/?" . rawurlencode(json_encode($cmd)));
		return Petolio_Service_Util::json(array('success' => true, 'msg' => reset(json_decode($data))));
	}
}