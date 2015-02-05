<?php

class SiteController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $request = null;
	private $config = null;

	private $db = null;
	private $dash = null;

	public function init() {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");

		// init db models
		$this->db = new stdClass();
		$this->db->services = new Petolio_Model_PoServices();
		$this->db->pets = new Petolio_Model_PoPets();
		$this->db->calendar = new Petolio_Model_PoCalendar();

		// init notifications models
		$this->db->notify = new Petolio_Model_PoNotifications();

		// init online models
		$this->db->online = new Petolio_Model_PoOnline();
		$this->db->users = new Petolio_Model_PoUsers();
		$this->db->stealth = new Petolio_Model_PoStealth();

		// load dashboard models
		$this->db->dash = new Petolio_Model_PoDashboard();
		$this->db->comments = new Petolio_Model_PoComments();
		$this->db->ratings = new Petolio_Model_PoRatings();
		$this->db->rights = new Petolio_Model_PoDashboardRights();
		$this->db->subscriptions = new Petolio_Model_PoSubscriptions();

		// init dashboard service
		$this->dash = new Petolio_Service_Dashboard($this->request, $this->db);

		// preload ratings and privacy
		$this->view->privacy = $this->dash->privacy;

		// append the dashboard css
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/dashboard.css'));
	}

	/**
	 * runs before action method
	 * @see Zend_Controller_Action::preDispatch()
	 */
	public function preDispatch() {
		// dont forget location
		$this->view->current_menu = "site";
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

	/**
	 * Index
	 */
	public function indexAction() {
		if($this->auth->hasIdentity()) {
			list($results, $more) = $this->dash->load(0);
			
			// output results
			$this->view->results = $results;
			$this->view->more = $more;

			// get chats
			$this->loadChats();
		} else
			$this->notLoggedIn();
	}

	/**
	 * Index
	 */
	public function mineAction() {
		// not logged in
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}

		// get list
		list($results, $more) = $this->dash->load($this->auth->getIdentity()->id);

		// output results
		$this->view->results = $results;
		$this->view->more = $more;

		// get chats
		$this->loadChats();
	}

	/**
	 * News Feed
	 */
	public function newsAction() {
		// not logged in
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}

		// get list
		list($results, $more) = $this->dash->load($this->auth->getIdentity()->id * -1);

		// output results
		$this->view->results = $results;
		$this->view->more = $more;

		// get chats
		$this->loadChats();
	}

	/**
	 * View one entry on page (useful to notifications for entries with scope po_dashboard)
	 */
	public function viewAction() {
		// get req params
		$id = @(int)$this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Entry does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// create filters
		$filters = array();
		$filters[] = "a.id = '{$id}'";

		// for people who are not logged in only public is available
		if (!$this->auth->hasIdentity()) {
			$filters[] = "a.rights = '0'";
		// for people logged in or self
		} else {
			$filters[] = "(a.rights <> '2' OR a.user_id = '{$this->auth->getIdentity()->id}')";
			$filters[] = "(a.rights = '0' OR ((a.rights = '1' OR a.rights = '3') AND r.user_id = '{$this->auth->getIdentity()->id}') OR a.user_id = '{$this->auth->getIdentity()->id}')";
		}

		// load entry
    	$result = reset($this->db->dash->getEntries(implode(' AND ', $filters)));
    	if(!$result) {
    		$this->msg->messages[] = $this->translate->_("Entry does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	}

		// attach aditional data
		$result['attached'] = $this->dash->attach2Entry($result, $this->config["comments"]["pagination"]["itemsperpage"], -1);

		// output results
		$this->view->results = array($result);
		$this->view->more = 0;

		// get chats
		$this->loadChats();
	}

	/*
	 * Show on index when not logged in
	 * (Register user action)
	 */
	private function notLoggedIn() {
		// register form
		$form = new Petolio_Form_Register();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('go')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// prepare data
		$data = $form->getValues();

		// little display error hack
		$data['email'] = $data['remail'];
		unset($data['remail']);

		// do psswd
		$data["password"] = sha1($data["password"]);

		// save new user
		$users = new Petolio_Model_PoUsers();
		$users->setOptions($data)->save(true, true);

		// add user's email field to private
		$rights = new Petolio_Model_PoFieldRights();
		$rights->setOptions(array(
			'field_name' => 'email',
			'entry_id' => $users->getId(),
			'rights' => 2
		))->save();

		// save user in forum
		$data["po_user_id"] = $users->getId();
		$flux = new Petolio_Service_FluxBB();
		$flux->addUser($data);

		// email user
		$email = new Petolio_Service_Mail();
		$email->setRecipient($data["email"]);
		$email->setTemplate('users/register');
		$email->petolioLink = PO_BASE_URL;
		$email->activationLink = PO_BASE_URL . 'accounts/activate/hash/' . sha1($data["password"] . $users->getId());
		$email->name = $data["name"];
		$email->base_url = PO_BASE_URL;
		$email->send();

		// message
		$messages = new Zend_Session_Namespace("po_messages");
		$messages->messages[] = $this->translate->_("Thank you for signing up.");
		$messages->messages[] = $this->translate->_("An email was sent to your email address. Please click on the attached link to activate your account.");

		// do html
		$reply = $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user'=>$users->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has signed up on Petolio!');
		$html = array(
			'%1$s has signed up on Petolio!',
			"<a href='{$reply}'>{$data["name"]}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('member', array($html, $reply, $users->getId()));

		// redirect when done
		return $this->_redirect('site');
	}

	/**
	 * loads all the live chats and future chats
	 */
	private function loadChats() {
		// ---- calendar init start

		$cal = new Petolio_Model_PoCalendar();
		$users = new Petolio_Model_PoUsers();

		// load types, colors, countries and is service
		$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// ---- calendar init end

		// get live chat channels
		$live = $this->db->calendar->getMapper()->browseLiveChats($this->auth->getIdentity()->id);

		// output live
		$this->view->live = array();
		foreach($live as $line) {
			$array = Petolio_Service_Calendar::format($line);
			if($this->auth->getIdentity()) {
				$array['invited'] = $line['atype'] === '0' && $line['astatus'] === '0' ? true : false;
				$array['accepted'] = $line['atype'] === '0' && $line['astatus'] === '1' ? true : false;
				$array['access'] = $line['astatus'] == 1 ? true : false;
			}

			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			$this->view->live[] = $array;
		}

		// get future chat channels
		$channels = $this->db->calendar->getMapper()->browseFutureChats($this->auth->getIdentity()->id);
		$this->view->future_count = count($channels);

		// output future
		$this->view->future = array();
		foreach(array_slice($channels, 0, 2) as $line) {
			$array = Petolio_Service_Calendar::format($line);
			if($this->auth->getIdentity()) {
				$array['invited'] = $line['atype'] === '0' && $line['astatus'] === '0' ? true : false;
				$array['accepted'] = $line['atype'] === '0' && $line['astatus'] === '1' ? true : false;
				$array['access'] = $line['astatus'] == 1 ? true : false;
			}

			$array['formatted_start'] = Petolio_Service_Util::formatDate($array["start"], Petolio_Service_Util::MEDIUMDATE, ($array["allDay"] != 1), true, true);
			$this->view->future[] = $array;
		}
	}

	/**
	 * This function is used to delete file attribute values from 'po_attribute_entity_varchar' on demand
	 */
	public function deleteAction() {
		// get value from param
		$id = $this->getRequest()->getParam('attribute');

		// db stuff
		$attrs = new Petolio_Model_DbTable_PoAttributes();
		$varchar = new Petolio_Model_PoAttributeEntityVarchar();

		// search for attribute value
		$results = $varchar->fetchList("SHA1(CONCAT(MD5(CONCAT(id, value)), 'unimatrix')) = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id));
		if(!$results)
			return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'site');

		// select and delete result
		$result = reset($results);
		$attrs->deleteAttributeValue(array(
			'attribute_name' => 'file',
			'attribute_type' => 'varchar',
			'id' => $result->getAttributeId()
		), $result->getEntityId());

		// redirect to last page
		return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'site');
	}

    /**
     * Find attribute
     * @returns json of attribute values found
     */
    public function findAction()
    {
    	// get options model
    	$db = new Petolio_Model_PoAttributeOptionsMapper();

		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// no search param ?
		$search = strtolower($this->request->getParam('search'));
		if(!isset($search))
			die('bye');

		$found = array();

		// add "all" to the elements
		$addall = $this->request->getParam('addall') == 'true' ? true : false;
		if($addall && stripos(Petolio_Service_Util::Tr("All"), $search) >= 0)
			$found[0] = array('value' => '0', 'text' => Petolio_Service_Util::Tr("All"));

		// search attribute
		foreach($db->fetchList("attribute_id = '{$this->request->getParam('attribute')}' AND value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$search."%")) as $all)
			$found[$all->getId()] = array('value' => $all->getId(), 'text' => Petolio_Service_Util::Tr($all->getValue()));

		// include secondary search if german
		if($this->translate->getLocale() == 'de') {
			$translation = new Petolio_Model_PoTranslations();
			foreach($translation->fetchList("language = 'de' AND value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$search."%")) as $tr)
				foreach($db->fetchList("attribute_id = '{$this->request->getParam('attribute')}' AND value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$tr->getLabel()."%")) as $all)
					$found[$all->getId()] = array('value' => $all->getId(), 'text' => Petolio_Service_Util::Tr($all->getValue()));
		}

		// reindex keys
		$found = array_merge($found);

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $found));
    }

	/**
	 * returns the current date
	 * if there is a date parameter then returns the date for the next day
	 */
	public function getDateAction() {
		// return json
		return Petolio_Service_Util::json(array('success' => true, 'date' => Petolio_Service_Util::formatDate(strtotime('now'), null, false, true)));
	}

	/**
	 * increment the banner clicks counter
	 */
	public function bannerClickAction() {
		$id = $this->request->getParam('id') ? intval($this->request->getParam('id')) : 0;

		$banners = new Petolio_Model_PoAdBanners();
		$banners->find($id);
		$banners->setClicks($banners->getClicks() + 1);
		$banners->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Get Services for Plus
	 */
	public function getServicesAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// not service provider?
		if($this->auth->getIdentity()->type != 2)
			return Petolio_Service_Util::json(array('success' => false));

		// get services
		$out = array();
		$services = new Petolio_Model_PoServices();
		foreach($services->getServices('array', "a.user_id = {$this->auth->getIdentity()->id}") as $service)
			$out[] = array($service['id'], $service['scope'], $service['name']);

		// return services
		return Petolio_Service_Util::json(array('success' => true, 'services' => $out));
	}

	/**
	 * Set locale action
	 */
	public function setlocaleAction() {
		// save selected locale
		$locale_param = $this->getRequest()->getParam('locale');

		// trying to set invalid locale!?
		if(strlen($locale_param) > 2)
			die('bye');

		// set the cookie
		setcookie('petolio_language', $locale_param, time() + 365 * 24 * 60 * 60, '/');

		// update the user's language
		if($this->auth->hasIdentity()) {
			$usr = $this->db->users->find($this->auth->getIdentity()->id);
			$usr->setLanguage($locale_param)->save();
		}

		// redirect to last page
		return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'site');
	}




















	/**
	 * Get ourselves + status
	 */
	private function getMe($types, &$out) {
		// build me
		$avatar = "/images/no-avatar.jpg";
		$user = $this->db->users->toArray();

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

		// output me
		$out['me'] = array(
			'id' => $user["id"],
			'name' => $user['name'],
			'avatar' => $avatar,
			'type' => $types[$user['type']],
			'url' => $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user["id"]), 'default', true),
		);

		// output status
		$out['status'] = $user['invisible'] == 1 ? 'offline' : 'online';
	}

	/**
	 * Get buddy list
	 */
	private function getBuddies($types, &$out) {
		// build buddy list
		$data = array();
		foreach($this->db->users->getUserFriends() as $user) {
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

			// build data
			$data[] = array(
				'id' => $user["id"],
				'name' => $user['name'],
				'avatar' => $avatar,
				'status' => 'offline',
				'type' => $types[$user['type']],
				'url' => $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user["id"]), 'default', true),
			);
		}

		// output buddy list
		$out['buddies'] = $data;
	}

	/**
	 * Get provider list (actually everyone...)
	 */
	private function getProviders($types, &$out) {
		// build buddy list
		$data = array();
		foreach($this->db->users->getProviders($this->auth->getIdentity()->id) as $user) {
			// object or array
			$avatar = "/images/no-avatar.jpg";

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

			// build data
			$data[] = array(
				'id' => $user["id"],
				'name' => $user['name'],
				'avatar' => $avatar,
				'status' => 'offline',
				'type' => $user['type'],
				'category' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($user['category_name'])),
				'url' => $this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user["id"]), 'default', true),
			);
		}

		// output buddy list
		$out['providers'] = $data;
	}

	/**
	 * Get unread messages
	 */
	private function getUnread(&$out) {
		// get unread messages
		$offline = array();
		foreach($this->db->online->getUnread("to_id = {$this->auth->getIdentity()->id} AND status = '0'") as $one)
			$offline[] = $one['from_id'];

		// output offline list
		$out['offline'] = $offline;
	}

	/**
	 * Get shadow settings
	 */
	private function getShadow(&$out) {
		// output shadow list
		$shadow = array();
		foreach($this->db->stealth->fetchList("from_id = {$this->auth->getIdentity()->id}") as $one)
			$shadow[] = $one->getToId();

		// output offline list
		$out['shadow'] = json_encode($shadow);
	}

	/**
	 * Get the ape online data
	 */
	public function apeAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// start with this output
		$out = array('success' => true);

		// get the logged in user and set type
		$this->db->users->find($this->auth->getIdentity()->id);
		$types = array("1" => $this->translate->_("Pet Owner"), "2" => $this->translate->_("Service Provider"));

		// get me and my status
		$this->getMe($types, $out);

		// get buddies
		$this->getBuddies($types, $out);

		// get providers
		$this->getProviders($types, $out);

		// get unread messages
		$this->getUnread($out);

		// get shadow
		$this->getShadow($out);

		// return json
		return Petolio_Service_Util::json(json_encode($out));
	}

	/**
	 * Log messages sent 4 history
	 */
	public function logAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$from = @(int)$this->request->getParam('from');
		$to = @(int)$this->request->getParam('to');
		$msg = @(string)$this->request->getParam('msg');

		// no user or message?
		if(!$from || !$to || !$msg)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// security check
		if($this->auth->getIdentity()->id != $from)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'Security check failed'
			));

		// get from
		$user = $this->db->users->find($from);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No from user found'
			));

		// get to
		$user = $this->db->users->find($to);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No to user found'
			));

		// save message log
		$this->db->online->setOptions(array(
			'from_id' => $from,
			'to_id' => $to,
			'message' => $msg
		))->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Mark message as read
	 */
	public function readAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$from = @(int)$this->request->getParam('from');
		$to = @(int)$this->request->getParam('to');
		$msg = @(string)$this->request->getParam('msg');

		// no user or message?
		if(!$from || !$to || !$msg)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// security check
		if($this->auth->getIdentity()->id != $to)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'Security check failed'
			));

		// get from
		$user = $this->db->users->find($from);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No from user found'
			));

		// get to
		$user = $this->db->users->find($to);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No to user found'
			));

		// mark as read
		$message = reset($this->db->online->fetchList("from_id = {$from} AND to_id = {$to} AND message = '{$msg}' AND status = '0'", "id DESC"));
		if($message)
			$message->setStatus(1)->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Get logged messages for history
	 */
	public function getAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$from = @(int)$this->request->getParam('from');
		$to = @(int)$this->request->getParam('to');
		$history = @(string)$this->request->getParam('history');
		list($undefined, $period) = explode('_', $history);

		// no user or message?
		if(!$from || !$to || !$history)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// security check
		if($this->auth->getIdentity()->id != $from)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'Security check failed'
			));

		// get from
		$user = $this->db->users->find($from);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No from user found'
			));

		// get to
		$user = $this->db->users->find($to);
		if(!$user->getId())
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'No to user found'
			));

		// history time
		$history = new DateTime('now');
		$history->sub(new DateInterval("P{$period}D"));

		// get history
		$messages = $this->db->online->getMessages("((a.from_id = {$from} and a.to_id = {$to}) OR (a.from_id = {$to} and a.to_id = {$from})) AND UNIX_TIMESTAMP(a.date_created) >= '{$history->format('U')}'", "a.date_created ASC");

		// correct timestamp and avatar
		foreach($messages as &$msg) {
			$date_created = Petolio_Service_Util::formatDate($msg['date_created'], Petolio_Service_Util::SHORTDATE, true, true);
			list($the_date, $the_time, $the_ante) = explode(' ', $date_created);

			$the_time = $the_time{0} == '0' ? substr($the_time, 1, strlen($the_time)) : $the_time;
			$msg['date_created'] = $the_date .' '. $the_time .' '. $the_ante;
			$msg['time_created'] = $the_time .' '. $the_ante;

			// from avatar
			$avatar = "/images/no-avatar.jpg";
			if(!is_null($msg['from_avatar'])) {
				$ds = DIRECTORY_SEPARATOR;
				$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$msg['from_id']}{$ds}thumb_{$msg['from_avatar']}";

				// get cache
				if(is_file($image)) {
					$cache = filemtime($image);
					$avatar = "/images/userfiles/avatars/{$msg['from_id']}/thumb_{$msg['from_avatar']}?{$cache}";
				}
			}

			// update avatar
			$msg['from_avatar'] = $avatar;
		}

		// return json
		return Petolio_Service_Util::json(array('success' => true, 'history' => $messages));
	}

	/*
	 * Mark as read
	 */
	public function markAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$from = @(int)$this->request->getParam('from');
		if(!$from)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// mark as read
		$unread = $this->db->online->fetchList("from_id = {$from} AND to_id = {$this->auth->getIdentity()->id} AND status = '0'");
		foreach($unread as $one)
			$one->setStatus(1)->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/*
	 * Change status
	 */
	public function statusAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$name = @(string)$this->request->getParam('name');
		if(!$name)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// update the status
		$user = $this->db->users->find($this->auth->getIdentity()->id);
		$user->setInvisible($name == 'offline' ? 1 : 0)->save();

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/*
	 * Change shadow
	 */
	public function shadowAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get vars
		$id = @(int)$this->request->getParam('id');
		if(!$id)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// get shadows
    	$result = $this->db->stealth->fetchList("from_id = '{$this->auth->getIdentity()->id}' AND to_id = '{$id}'");

    	// not found ? insert new
    	if (!count($result) > 0) {
			$this->db->stealth->setOptions(array(
				'from_id' => $this->auth->getIdentity()->id,
				'to_id' => $id
			))->save();

		// found ? delete
		} else {
			$result = reset($result);
			$result->deleteRowByPrimaryKey();
		}

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/*
	 * See if the user is actually online
	 */
	public function isOnlineAction() {
		// get vars
		$id = @(int)$this->request->getParam('id');
		if(!$id)
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => 'params missing'
			));

		// return json
		return Petolio_Service_Util::json(array('success' => is_null($this->db->users->getMapper()->getDbTable()->isOnline($id)) ? false : true));
	}





	private function formatNotificationMessage($data, $id) {
		$c = count($data[0]);
		if($c == 2) $r = sprintf($this->translate->_($data[0][0]), $data[0][1]);
		elseif($c == 3) $r = sprintf($this->translate->_($data[0][0]), $data[0][1], $data[0][2]);
		elseif($c == 4) $r = sprintf($this->translate->_($data[0][0]), $data[0][1], $data[0][2], $data[0][3]);
		else $r = $this->translate->_('Invalid data');

		$encoded = str_replace(array('+', '/'), array('_', '-'), base64_encode($id . '_' . $data[1]));
		return str_replace($data[1], PO_BASE_URL . 'site/link-notification/go/' . $encoded, $r);
	}

	public function viewNotificationsAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get notifications
		$paginator = $this->db->notify->getNotifications("paginator", "user_id = '{$this->auth->getIdentity()->id}'", "date_created DESC");
		$paginator->setItemCountPerPage($this->config["notifications"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// format notifications
		foreach($paginator as &$one) {
			// decode data
			$data = unserialize($one['data']);

			// avatar control
			$avatar = "/images/no-avatar.jpg";
			if(!is_null($one['user_avatar'])) {
				$ds = DIRECTORY_SEPARATOR;
				$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$one['author_id']}{$ds}thumb_{$one['user_avatar']}";

				// get cache
				if(is_file($image)) {
					$cache = filemtime($image);
					$avatar = "/images/userfiles/avatars/{$one['author_id']}/thumb_{$one['user_avatar']}?{$cache}";
				}
			}

			// send to out
			$one['avatar'] = $avatar;
			$one['link'] = $data[1];
			$one['msg'] = $this->formatNotificationMessage($data, $one['id']);
			$one['date'] = array(
				'long' => Petolio_Service_Util::formatDate($one['date_created'], Petolio_Service_Util::MEDIUMDATE, true, true),
				'short' => Petolio_Service_Util::formatTime($one['date_created'])
			);
		}

		// output pets
		$this->view->notifications = $paginator;

		// get chats
		$this->loadChats();
	}

	public function getNotificationsAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get notifications
		$results = $this->db->notify->getNotifications("array", "user_id = '{$this->auth->getIdentity()->id}'", "date_created DESC", array(8, 0));
		$count = $this->db->notify->getMapper()->getDbTable()->getAdapter()->fetchOne("SELECT COUNT(*) FROM po_notifications AS a LEFT JOIN po_users AS x ON a.author_id = x.id WHERE x.active = 1 AND x.is_banned != 1 AND a.user_id = '{$this->auth->getIdentity()->id}' AND a.status = '0'");

		// format results a little bit
		$notifications = array();
		foreach($results as $one) {
			// decode data
			$data = unserialize($one['data']);

			// avatar control
			$avatar = "/images/no-avatar.jpg";
			if(!is_null($one['user_avatar'])) {
				$ds = DIRECTORY_SEPARATOR;
				$image = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$one['author_id']}{$ds}thumb_{$one['user_avatar']}";

				// get cache
				if(is_file($image)) {
					$cache = filemtime($image);
					$avatar = "/images/userfiles/avatars/{$one['author_id']}/thumb_{$one['user_avatar']}?{$cache}";
				}
			}

			// send to out
			$notifications[] = array(
				'avatar' => $avatar,
				'msg' => $this->formatNotificationMessage($data, $one['id']),
				'date' => array(
					'long' => Petolio_Service_Util::formatDate($one['date_created'], Petolio_Service_Util::MEDIUMDATE, true, true),
					'short' => Petolio_Service_Util::formatTime($one['date_created'])
				),
				'status' => $one['status']
			);
		}

		// output notifications
		return Petolio_Service_Util::json(array(
			'success' => true,
			'notifications' => $notifications,
			'count' => $count
		));
	}

	public function markNotificationsAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return Petolio_Service_Util::json(array('success' => false));

		// get notifications
		$results = $this->db->notify->fetchList("user_id = '{$this->auth->getIdentity()->id}'");

		// mark as read every one of them
		foreach($results as $one)
			$one->setStatus(1)->save();

		// send json
		return Petolio_Service_Util::json(array('success' => true));
	}

	public function linkNotificationAction() {
		// get vars
		$go = @(string)$this->request->getParam('go');
		if(!$go)
			die('gtfo noob');

		// decode hash
		$decoded = base64_decode(str_replace(array('_', '-'), array('+', '/'), $go));

		// get url
		list($id, $url) = explode('_', $decoded);
		
		// transform any petolio url to the current domain
		$url = str_replace(array(
			'new.petolio.local', // localhost mirror
			'new.petolio.riffcode.ro', // test mirror
			'petolio.com', 'petolio.de' // official mirror
		), str_replace('www.', '', $_SERVER['HTTP_HOST']), $url);

		// mark notification as read
		$notification = $this->db->notify->find($id);
		$notification->setStatus(1)->save();

		// go to url
		header("Location: {$url}");
		exit;
	}

	public function viewNotesAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}

		// get vars
		$this->view->filter_pet = false;
		$pet = @(int)$this->request->getParam('pet');
		if($pet > 0)
			$this->view->filter_pet = $pet;

		// helper for layout
		$this->view->current_menu = 'notes';
		$this->view->method = 'mine';

		// get all of the needed database models
		$medical = new Petolio_Model_PoMedicalRecords();
		$diary = new Petolio_Model_PoDiaryRecords();
		$shot = new Petolio_Model_PoShotRecords();
		$todo = new Petolio_Model_PoCalendar();

		$s_medical = new Petolio_Model_PoMedicalRecordSubentries();
		$s_shot = new Petolio_Model_PoShotRecordSubentries();

		// get all medical subentries
		$sub_m = array();
		foreach($s_medical->fetchList("owner_id = {$this->auth->getIdentity()->id} AND deleted = 0") as $one)
			$sub_m[$one->getId()] = array($one->getMedicalRecordId(), $one->getHeadline1());

		// get all shot subentries
		$sub_s = array();
		foreach($s_shot->fetchList("owner_id = {$this->auth->getIdentity()->id} AND deleted = 0") as $one)
			$sub_s[$one->getId()] = array($one->getShotRecordId(), Petolio_Service_Util::Tr($one->getImmunization()));

		// get all pets
		$pets = array();
		foreach($this->db->pets->formatPets($this->db->pets->getPets('array', "a.user_id = {$this->auth->getIdentity()->id} AND deleted = 0")) as $one)
			$pets[$one['id']] = $one;
		$this->view->pets = $pets;

		// ready, set, go
		$records = array();

		// medical
		$filters = array();
		$filters[] = "owner_id = {$this->auth->getIdentity()->id}";
		$filters[] = "deleted = 0";
		if($this->view->filter_pet)
			$filters[] = "pet_id = {$this->view->filter_pet}";
		foreach($medical->fetchList(implode(' AND ', $filters)) as $one) {
			if(!isset($pets[$one->getPetId()]))
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => true,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $pets[$one->getPetId()],
				'type' => 'medical',
				'title' => $one->getHeadline1(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// diary
		$filters = array();
		$filters[] = "owner_id = {$this->auth->getIdentity()->id}";
		$filters[] = "deleted = 0";
		if($this->view->filter_pet)
			$filters[] = "pet_id = {$this->view->filter_pet}";
		foreach($diary->fetchList(implode(' AND ', $filters)) as $one) {
			if(!isset($pets[$one->getPetId()]))
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => true,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $pets[$one->getPetId()],
				'type' => 'diary',
				'title' => $one->getTitle(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// shot
		$filters = array();
		$filters[] = "owner_id = {$this->auth->getIdentity()->id}";
		$filters[] = "deleted = 0";
		if($this->view->filter_pet)
			$filters[] = "pet_id = {$this->view->filter_pet}";
		foreach($shot->fetchList(implode(' AND ', $filters)) as $one) {
			if(!isset($pets[$one->getPetId()]))
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => true,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $pets[$one->getPetId()],
				'type' => 'shot',
				'title' => $one->getSickness(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// todo
		$filters = array();
		$filters[] = "user_id = {$this->auth->getIdentity()->id}";
		$filters[] = "type = 1";
		if($this->view->filter_pet)
			$filters[] = "species = {$this->view->filter_pet}";
		foreach($todo->fetchList(implode(' AND ', $filters)) as $one) {
//			if(!isset($pets[$one->getSpecies()]))
//				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => true,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => @$pets[$one->getSpecies()],
				'type' => 'todo',
				'title' => $one->getSubject(),
				'description' => $one->getDescription(),
				'done' => $one->getMod(),
				'related' => $one->getFee() ? @array('medical', $sub_m[$one->getFee()][0], $sub_m[$one->getFee()][1]) : @array('shot', $sub_s[$one->getCap()][0], $sub_s[$one->getCap()][1]),
				'deadline' => Petolio_Service_Util::formatDate(Petolio_Service_Util::calculateTimezone($one->getDateStart(), @$_COOKIE['user_timezone']), Petolio_Service_Util::MEDIUMDATE, ($one->getAllDay() != 1), true, true)
			);
		}

		// order by date (newest first)
		if(!$this->view->filter_pet) {
			Petolio_Service_Util::array_sort($records, array("sort" => true, "sort" => false));
		}

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// pagination
		$paginator = Zend_Paginator::factory($records);
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->records = $paginator;

		// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species = json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods = json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
    	$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// get chats
		$this->loadChats();
	}

    private function loadFriendsAndPartners($id, $what = 'friends') {
		// load user's friends and partners
		$this->db->users->find($id);
		$all = $what == 'friends' ? $this->db->users->getUserFriends() : $this->db->users->getUserPartners();

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		// return
		return $result;
    }

    /**
     * So is the shot record private or not ??
     *
     * @param int $rights - Record Right
     * @param int $id - Record Id
     * @param int $owner - Record Owner
	 * @param str $db_id - The id (medical / shot / record)
     *
     * @return bool
     */
    private function isPrivate($rights, $id, $owner, $db_id) {
		// lets say its not private at first
		$private = false;

		// records
		list($db) = explode('_', $db_id);
		$dbs = array(
			'medical' => new Petolio_Model_PoMedicalRecordRights(),
			'diary' => new Petolio_Model_PoDiaryRecordRights(),
			'shot' => new Petolio_Model_PoShotRecordRights()
		);

		// not logged in ? simple
		if(!$this->auth->hasIdentity())
			$private = $rights == 1 ? false : true;

		// logged in ? ugh
		else {
			// overwrite if you're the owner, duh
			if($this->auth->getIdentity()->id == $owner)
				return $private;

			// switch between rights
			switch($rights) {
				// nobody/private
				case 0:
					$private = true;
				break;

				// everyone/public access
				case 1:
					// do nothing
				break;

				// partners
				case 2:
					// loop through allowed users
					$allowed = array();
					foreach($dbs[$db]->getMapper()->findByField($db_id, $id, null) as $user)
						$allowed[] = $user->getUserId();

					if(!$allowed) {
						if(!array_key_exists($this->auth->getIdentity()->id, $this->loadFriendsAndPartners($owner, 'partners')))
							$private = true;
					} else {
						if(!in_array($this->auth->getIdentity()->id, $allowed))
							$private = true;
					}
				break;

				// friends
				case 3:
					// loop through allowed users
					$allowed = array();
					foreach($dbs[$db]->getMapper()->findByField($db_id, $id, null) as $user)
						$allowed[] = $user->getUserId();

					if(!$allowed) {
						if(!array_key_exists($this->auth->getIdentity()->id, $this->loadFriendsAndPartners($owner, 'friends')))
							$private = true;
					} else {
						if(!in_array($this->auth->getIdentity()->id, $allowed))
							$private = true;
					}

				break;

				// users
				case 4:
					// loop through allowed users
					$allowed = array();
					foreach($dbs[$db]->getMapper()->findByField($db_id, $id, null) as $user)
						$allowed[] = $user->getUserId();

					if(!in_array($this->auth->getIdentity()->id, $allowed))
						$private = true;
				break;
			}
		}

		return $private;
    }

	public function viewAllNotesAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}

		// helper for layout
		$this->view->current_menu = 'notes';
		$this->view->method = 'all';

		// change render
		$this->_helper->viewRenderer->setRender('view-notes');

		// get all of the needed database models
		$medical = new Petolio_Model_PoMedicalRecords();
		$diary = new Petolio_Model_PoDiaryRecords();
		$shot = new Petolio_Model_PoShotRecords();

		// ready, set, go
		$records = array();
		
		$pet_id = $this->request->getParam("pet", null);

		// start where
		$where = "deleted = 0";

		if (!is_null($pet_id)) {
			$pets = new Petolio_Model_PoPets();
			$pets = $pets->getPets("array",  "a.id = ".addcslashes($pet_id, "\000\n\r\\'\"\032"));
			if (count($pets) > 0) {
				$pet = $pets[0];
				$this->view->filter_pet = $pet;
				// show only pet's entries
				$where .= " AND pet_id = ".$pet["id"];
			} else {
				// exclude current user's entries
				$where .= " AND owner_id != " . $this->auth->getIdentity()->id;
			}
		}
		
		// medical
		foreach($medical->fetchList($where) as $one) {
			$access = $this->isPrivate($one->getRights(), $one->getId(), $one->getOwnerId(), 'medical_record_id');
			if($access == true)
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => $one->getOwnerId() == $this->auth->getIdentity()->id ? true : false,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $one->getPetId(),
				'type' => 'medical',
				'title' => $one->getHeadline1(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// diary
		foreach($diary->fetchList($where) as $one) {
			$access = $this->isPrivate($one->getRights(), $one->getId(), $one->getOwnerId(), 'diary_record_id');
			if($access == true)
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => $one->getOwnerId() == $this->auth->getIdentity()->id ? true : false,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $one->getPetId(),
				'type' => 'diary',
				'title' => $one->getTitle(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// shot
		foreach($shot->fetchList($where) as $one) {
			$access = $this->isPrivate($one->getRights(), $one->getId(), $one->getOwnerId(), 'shot_record_id');
			if($access == true)
				continue;

			$records[] = array(
				'id' => $one->getId(),
				'owner' => $one->getOwnerId() == $this->auth->getIdentity()->id ? true : false,
				'date' => $one->getDateCreated(),
				'sort' => strtotime($one->getDateCreated()),
				'pet' => $one->getPetId(),
				'type' => 'shot',
				'title' => $one->getSickness(),
				'description' => html_entity_decode($one->getDescription())
			);
		}

		// order by date (newest first)
		if (is_null($pet_id)) {
			Petolio_Service_Util::array_sort($records, array("sort" => true, "sort" => false));
		}

		// get all pet ids
		$pet_ids = array();
		foreach($records as $one)
			$pet_ids[] = $one['pet'];

		// get Unique
		$pet_ids = array_unique($pet_ids);

		// get all pets
		if(count($pet_ids) > 0) {
			$pets = array();
			foreach($this->db->pets->formatPets($this->db->pets->getPets('array', "a.id IN (" . implode(',', array_map('intval', $pet_ids)) . ") AND deleted = 0")) as $one)
				$pets[$one['id']] = $one;

			// format by reference
			foreach($records as $idx => &$two) {
				if(isset($pets[$two['pet']]))
					$two['pet'] = $pets[$two['pet']];
				else unset($records[$idx]);
			}
		}

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// pagination
		$paginator = Zend_Paginator::factory($records);
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->records = $paginator;

		// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species = json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods = json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
    	$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// get chats
		$this->loadChats();
	}
}