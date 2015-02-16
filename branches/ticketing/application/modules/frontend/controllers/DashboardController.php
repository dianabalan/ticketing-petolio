<?php

/**
 * Dashboard Controller
 *
 * @uses Petolio_Service_Dashboard
 * @uses Petolio_Service_Util
 */
class DashboardController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $request = null;
	private $config = null;

	private $db = null;
	private $dash = null;
	private $upload_dir = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");

		// load models
		$this->db = new stdClass();
		$this->db->dash = new Petolio_Model_PoDashboard();
		$this->db->users = new Petolio_Model_PoUsers();
		$this->db->rights = new Petolio_Model_PoDashboardRights();
		$this->db->files = new Petolio_Model_PoFiles();
		$this->db->comments = new Petolio_Model_PoComments();
		$this->db->ratings = new Petolio_Model_PoRatings();
		$this->db->subscriptions = new Petolio_Model_PoSubscriptions();

		// init dashboard service
		$this->dash = new Petolio_Service_Dashboard($this->request, $this->db);

		// not logged in ? BYE (except for view, more, refresh and new)
		if (!$this->auth->hasIdentity()
			 && $this->request->getActionName() != 'view'
			 && $this->request->getActionName() != 'more'
			 && $this->request->getActionName() != 'refresh'
			 && $this->request->getActionName() != 'new') {
			$this->msg->messages[] = $this->translate->_("You must be logged in.");
			return $this->_redirect('site');
		}

		// preload ratings and privacy
		$this->view->privacy = $this->dash->privacy;

		// set upload dir
		$ds = DIRECTORY_SEPARATOR;
		$this->upload_dir = "..{$ds}data{$ds}userfiles{$ds}dashboard{$ds}";

		// append the dashboard css
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/dashboard.css'));
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
		// redirect to site/mine
		return $this->_helper->redirector('mine', 'site');

/** OBSOLETE
		list($results, $more) = $this->dash->load($this->auth->getIdentity()->id);

		// output results
		$this->view->results = $results;
		$this->view->more = $more;
**/
	}

	/**
	 * News Feed
	 */
	public function newsAction() {
		// redirect to site/news
		return $this->_helper->redirector('news', 'site');

/** OBSOLETE
		list($results, $more) = $this->dash->load($this->auth->getIdentity()->id * -1);

		// output results
		$this->view->results = $results;
		$this->view->more = $more;
**/
	}

	/**
	 * All Feed
	 */
	public function allAction() {
		// redirect to site/index
		return $this->_helper->redirector('index', 'site');

/** OBSOLETE
		list($results, $more) = $this->dash->load(0);

		// output results
		$this->view->results = $results;
		$this->view->more = $more;
**/
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

		// redirect to site/view
		return $this->_redirect('site/view/id/'. $id);

/** OBSOLETE
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
**/
	}

	/**
	 * Return count of new entries
	 */
	public function newAction() {
		$x = @(int)$this->request->getParam('x');
		$time = @(int)$this->request->getParam('time');
		$sw = @(int)$this->request->getParam('sw');
		if(!$x || !$time)
			die('gtfo noob');

		// not logged in?
		if(!$this->auth->hasIdentity())
			die('gtfo noob');

		// 0 - your ($x); 1 - friends ($x negative); 2 - all (0)
		$x = $sw == 1 ? ($x * -1) : ($sw == 0 ? $x : 0);

		// return json
		return Petolio_Service_Util::json(array(
			'success' => true,
			'count' => $this->dash->load($x, 0, $time),
			'time' => time()
		));
	}

	/**
	 * Load more action
	 */
	public function moreAction() {
		$x = @(int)$this->request->getParam('x');
		$more = @(int)$this->request->getParam('more');
		$sw = @(int)$this->request->getParam('sw');
		$profile = $this->request->getParam('p');
		if(!$x || !$more)
			die('gtfo noob');
		
		// not logged in?
		if(!$this->auth->hasIdentity())
			die('gtfo noob');

		// 0 - your ($x); 1 - friends ($x negative); 2 - all (0)
		$x = $sw == 1 ? ($x * -1) : ($sw == 0 ? $x : 0);

		// try to load
		list($results, $more) = $this->dash->load($x, $more);

		$feed_options = array(
			'translate' => $this->translate,
			'privacy' => $this->view->privacy,
			'results' => $results,
			'more' => $more,
			'identity' => $this->auth->getIdentity(),
			'switch' => $sw
		);
		
		if ($profile && is_array($profile)) {
			$feed_options["user"] = $profile;
			$feed_options["hideavatar"] = true;
		}
		
		// set template
		$data = $this->view->partial('dashboard/feed.phtml', $feed_options);

		// output data
		return Petolio_Service_Util::json(array(
			'success' => true,
			'data' => $data,
			'count' => count($results)
		));
	}

	/**
	 * Refresh attached data
	 */
	public function refreshAction() {
		$id = @$this->request->getParam('id');
		$surf = @(string)$this->request->getParam('surface');
		$page = @(int)$this->request->getParam('page');
		if(!$id || !$surf)
			die('gtfo noob');

		// pagination
		if($page !== 0 && $page !== -1)
			$page = $page < 1 ? 1 : $page;

		// multiple entries
		if($surf == 'all') {
			// get entries
			$results = $this->db->dash->getEntries("a.id IN (" . implode(',', $id) . ")");

			// loop through each entry
			$data = array();
			foreach($results as $key => $entry) {
				// attach data
				if($page === 0) $entry['attached'] = $this->dash->attach2Entry($entry);
				else $entry['attached'] = $this->dash->attach2Entry($entry, $this->config["comments"]["pagination"]["itemsperpage"], $page);

				// activity
				$errors = array();
				list($tpl, $rest) = Petolio_Service_Util::get_string_between($entry['data'], "{{", "}}", $errors);

				// construct data
				$data[$entry['id']] = array(
					'links' => $this->view->partial('dashboard/surf_links.phtml', array(
						'entry' => $entry,
						'activity' => isset($tpl[0]) && $tpl[0] == 'txt' ? true : false,
						'privacy' => $this->view->privacy,
						'translate' => $this->translate,
						'identity' => $this->auth->getIdentity(),
					)),
					'ratings' => $this->view->partial("dashboard/surf_ratings.phtml", array(
						'entry' => $entry,
						'translate' => $this->translate
					)),
					'comments' => $this->view->partial('dashboard/surf_comments.phtml', array(
						'entry' => $entry,
						'translate' => $this->translate,
						'identity' => $this->auth->getIdentity()
					))
				);
			}

		// single entry
		} else {
			// get entry
			$result = reset($this->db->dash->getEntries("a.id = '{$id}'"));
			if(!$result)
				return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Entry not found')));

			// attach data
			if($page === 0) $result['attached'] = $this->dash->attach2Entry($result);
			else $result['attached'] = $this->dash->attach2Entry($result, $this->config["comments"]["pagination"]["itemsperpage"], $page);

			// activity
			$errors = array();
			list($tpl, $rest) = Petolio_Service_Util::get_string_between($result['data'], "{{", "}}", $errors);

			// refresh links by default
			$data = array('links' => $this->view->partial('dashboard/surf_links.phtml', array(
				'entry' => $result,
				'activity' => isset($tpl[0]) && $tpl[0] == 'txt' ? true : false,
				'privacy' => $this->view->privacy,
				'translate' => $this->translate,
				'identity' => $this->auth->getIdentity(),
			)));

			// add selected surface
			switch($surf) {
				// links surface
				case 'links':
					// links are already refreshed by default
				break;

				// ratings surface
				case 'ratings':
					$data['ratings'] = $this->view->partial("dashboard/surf_ratings.phtml", array(
						'entry' => $result,
						'translate' => $this->translate
					));
				break;

				// comments surface
				case 'comments':
					$data['comments'] = $this->view->partial('dashboard/surf_comments.phtml', array(
						'entry' => $result,
						'translate' => $this->translate,
						'identity' => $this->auth->getIdentity()
		    		));
				break;
			}
		}

		// output data
		return Petolio_Service_Util::json(array(
			'success' => true,
			'data' => $data
		));
	}

	/**
	 * Control entry privacy
	 */
	public function privacyAction() {
		// required param $a
		$a = @(string)$this->request->getParam('a');
		if(!$a)
			return Petolio_Service_Util::json(array('success' => false));

		// privacy object
		$p = new Dashboard\Privacy($this->request);
		return $p->{$a}();
	}

	/**
	 * Delete entry action
	 */
	public function deleteAction() {
		// required param $x
		$x = @(string)$this->request->getParam('x');
		if(!$x)
			return Petolio_Service_Util::json(array('success' => false));

		// find entry
		$result = reset($this->db->dash->fetchList("id = '{$x}' AND user_id = '{$this->auth->getIdentity()->id}'"));
		if(!$result)
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Entry not found')));

		// delete all comments and likes and subscribers and permissions
		$this->db->comments->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$result->getId()}'");
		$this->db->ratings->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$result->getId()}'");
		$this->db->subscriptions->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$result->getId()}'");
		$this->db->rights->getMapper()->getDbTable()->delete("dashboard_id = '{$result->getId()}'");

		// remove thumbnail if any from serialized
		$params = unserialize($result->getSerialized());
		if(isset($params['l']['thumb']) && !empty($params['l']['thumb']))
			@unlink($this->upload_dir . $params['l']['thumb']);

		// remove big picture as well if uploaded
		if(isset($params['l']) && $params['l']['type'] == 'upload')
			@unlink($this->upload_dir . pathinfo($params['l']['value'], PATHINFO_BASENAME));

		// delete entry
		$result->deleteRowByPrimaryKey();

		// return success
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Check url headers for correct content type
	 */
	public function checkAction() {
		// required params
		$x = @(string)$this->request->getParam('x');
		$v = @(string)$this->request->getParam('v');
		if(!$x || !$v)
			return Petolio_Service_Util::json(array('success' => false));

		// get headers
		$headers = @get_headers($v, 1);
		if(!$headers)
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Get Headers failed')));

		// switch based on content type
		switch($x) {
			case 'picture':
				// check for image content type
				if($headers['Content-Type'] == 'image/gif'
						|| $headers['Content-Type'] == 'image/jpeg'
						|| $headers['Content-Type'] == 'image/png')
					return Petolio_Service_Util::json(array('success' => true));
			break;

			case 'audio':
				// check for image content type
				if($headers['Content-Type'] == 'audio/mpeg'
						|| $headers['Content-Type'] == 'audio/mp4'
						|| $headers['Content-Type'] == 'audio/ogg'
						|| $headers['Content-Type'] == 'audio/wav'
						|| $headers['Content-Type'] == 'audio/vnd.wave')
					return Petolio_Service_Util::json(array('success' => true));
			break;
		}

		// if we got here, this is bad
		return Petolio_Service_Util::json(array('success' => false, 'msg' => sprintf($this->translate->_('URL is not a valid %s'), $x)));
	}

	/**
	 * Share an Entry
	 */
	public function shareAction() {
		// required param $v & $p
		$v = @(int)$this->request->getParam('v');
		$p = @(array)$this->request->getParam('p');
		if(!$v || !$p)
			return Petolio_Service_Util::json(array('success' => false));

		// figure out permissions
		$rights = (int)$p['d'];
		$users = $p['u'];

		// get entry to clone
		$clone = $this->db->dash->find($v);
		$data = $clone->getData();

		// unserialize to test
		$unserialized = unserialize($clone->getSerialized());
		if(isset($unserialized['l']['type'])) {
			$fake = array(
				$this->translate->_("picture"),
				$this->translate->_("video")
			); unset($fake);
			$type = $this->translate->_($unserialized['l']['type']);
		} else $type = $this->translate->_("entry");

		// split text
		$errors = array();
		list($inside, $outside) = Petolio_Service_Util::get_string_between($data, '<!--{us}-->', '<!--{ue}-->', $errors);
		$inside = reset($inside);

		// find original user
		if(isset($unserialized['o'])) {
			$original_id = $unserialized['o']['i'];
			$original_name = $unserialized['o']['n'];
			$o = $unserialized['o'];
		} else {
			$original = $this->db->users->find($clone->user_id);
			if(!$original->getId())
				return Petolio_Service_Util::json(array('success' => false, 'msg' => 'User not found'));

			$original_id = $original->getId();
			$original_name = $original->getName();
			$o = array('i' => $original_id, 'n' => $original_name);
		}

		// replace text
		$data = str_replace($inside, sprintf($this->translate->_('%1$s has shared %2$s\'s %3$s'),
			"<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $original_id), 'default', true)}'>{$original_name}</a>",
			$type
		), $data);

		// insert clone
		$this->db->dash = new \Petolio_Model_PoDashboard();
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $data,
			'serialized' => serialize(array(
				'v' => $v,
				'p' => $p,
				'o' => $o
			)),
			'rights' => $rights,
			'scope' => 'po_dashboard'
		))->save();

		// save user rights
		if($rights == "3" && is_array($users))
			$this->db->rights->getMapper()->setCustomUsers($this->db->dash->getId(), $users);

		// return true :)
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Manual Posting
	 */
	public function postAction() {
		// required param $v & $p
		$v = @(string)$this->request->getParam('v');
		$p = @(array)$this->request->getParam('p');
		if(!$v || !$p)
			return Petolio_Service_Util::json(array('success' => false));

		// figure out permissions
		$rights = (int)$p['d'];
		$users = $p['u'];

		// image or video
		$l = @$this->request->getParam('l');
		if($l != "false") {
			list($type, $value, $status) = $l;

			// go to correct function
			$func = strtolower($status) . ucfirst($type);
			$this->{$func}($value, $rights, array('v' => $v, 'p' => $p, 'l' => array('type' => $type, 'value' => $value, 'status' => $status)));

		// text (/w or w/o link)
		} else {
			// insert entry
			$this->db->dash->setOptions(array(
				'user_id' => $this->auth->getIdentity()->id,
				'data' => $this->view->partial('autopost/txt-.phtml', array(
					'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					'data' => Petolio_Service_Parse::_($v)
				)),
				'serialized' => serialize(array(
					'v' => $v,
					'p' => $p
				)),
				'rights' => $rights,
				'scope' => 'po_dashboard'
			))->save();
		}

		// save user rights
		if($rights == "3" && is_array($users))
			$this->db->rights->getMapper()->setCustomUsers($this->db->dash->getId(), $users);

		// save owner as subscriber
		$this->db->subscriptions->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'scope' => 'po_dashboard',
			'entity_id' => $this->db->dash->getId(),
		))->save();

		// return true :)
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Post picture link
	 * @param string picture url
	 * @param int rights value
	 * @param array params
	 */
	private function linkPicture($x, $r, $z) {
		// start output buffering
		ob_start();

		// get picture using curl
		$ch = curl_init($x);

		// set URL and other appropriate options
		$options = array(
			CURLOPT_HEADER => 0,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT => 60 // 1 minute timeout (should be enough)
		);

		// set options and execute
		curl_setopt_array($ch, $options);
		$connection = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		// get image and clear buffer
		$pic = ob_get_contents();
		ob_end_clean();

		// did you connect remotely?
		if($connection === false)
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Get Headers failed')));

		// check for correct picture content type
		if(!($info['content_type'] == 'image/gif'
				|| $info['content_type'] == 'image/jpeg'
				|| $info['content_type'] == 'image/png'))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('URL is not an image')));

		// constant matrix
		$transform = array(
			'image/gif' => IMAGETYPE_GIF,
			'image/jpeg' => IMAGETYPE_JPEG,
			'image/png' => IMAGETYPE_PNG
		);

		// decide the filename
		$filename = md5(uniqid($this->auth->getIdentity()->id.'_')) . image_type_to_extension($transform[$info['content_type']]);

		// make a thumbnail from the picture
		list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["small"]);
		Petolio_Service_Image::output($pic, $this->upload_dir . $filename, array(
			'type'   => $transform[$info['content_type']],
			'width'   => $w,
			'height'  => $h,
			'method'  => THUMBNAIL_METHOD_SCALE_MIN
		));

		// add to serialized
		$z['l']['thumb'] = $filename;

		// insert entry
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $this->view->partial('autopost/img-.phtml', array(
				'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				'original' => $x,
				'thumb' => $filename,
				'data' => Petolio_Service_Parse::_($z['v'])
			)),
			'serialized' => serialize($z),
			'rights' => $r,
			'scope' => 'po_dashboard'
		))->save();
	}

	/**
	 * Post video link
	 * @param string youtube id
	 * @param int rights value
	 * @param array params
	 */
	private function linkVideo($x, $r, $z) {
		// call our youtube wrapper
		$youtube = Petolio_Service_YouTube::factory('Master');
		$youtube->CFG = array(
			'username' => $this->config["youtube"]["username"],
			'password' => $this->config["youtube"]["password"],
			'app' => $this->config["youtube"]["app"],
			'key' => $this->config["youtube"]["key"]
		);

		// get video entry
		try {
			$entry = $youtube->getVideoEntry($x);
		} catch (Exception $e) {
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Video retrieval failed')));
		}

		// state not null ? something wrong with video, remove it from list
		if(!is_null($entry->getVideoState()))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Video error')));

		// get video thumbnail and duration
		$thumb = $entry->getVideoThumbnails();
		$duration = date("i:s", $entry->getVideoDuration());

		// decide the filename
		$filename = md5(uniqid($this->auth->getIdentity()->id.'_')) . image_type_to_extension(IMAGETYPE_JPEG);

		// download the thumbnail
		$ch = curl_init($thumb[1]['url']);
		$fp = fopen($this->upload_dir . $filename, "wb");

		// set URL and other appropriate options
		$options = array(CURLOPT_FILE => $fp,
			CURLOPT_HEADER => 0,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT => 60 // 1 minute timeout (should be enough)
		);

		// call curl
		curl_setopt_array($ch, $options);
		$connection = curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		// did you connect remotely?
		if($connection === false)
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Get Headers failed')));

		// add to serialized
		$z['l']['thumb'] = $filename;
		$z['l']['duration'] = $duration;

		// insert entry
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $this->view->partial('autopost/vid-.phtml', array(
				'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				'original' => $x,
				'thumb' => $filename,
				'duration' => $duration,
				'data' => Petolio_Service_Parse::_($z['v'])
			)),
			'serialized' => serialize($z),
			'rights' => $r,
			'scope' => 'po_dashboard'
		))->save();
	}

	/**
	 * Post audio link
	 * @param string audio url
	 * @param int rights value
	 * @param array params
	 */
	private function linkAudio($x, $r, $z) {
		// start output buffering
		ob_start();

		// get picture using curl
		$ch = curl_init($x);

		// set URL and other appropriate options
		$options = array(
			CURLOPT_HEADER => 0,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT => 60 // 1 minute timeout (should be enough)
		);

		// set options and execute
		curl_setopt_array($ch, $options);
		$connection = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		// get image and clear buffer
		$pic = ob_get_contents();
		ob_end_clean();

		// did you connect remotely?
		if($connection === false)
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Get Headers failed')));

		// check for correct picture content type
		if(!($info['content_type'] == 'audio/mpeg'
				|| $info['content_type'] == 'audio/mp4'
				|| $info['content_type'] == 'audio/ogg'
				|| $info['content_type'] == 'audio/wav'
				|| $info['content_type'] == 'audio/vnd.wave'))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('URL is not an audio')));

		// decide the filename
		$filename = md5(uniqid($this->auth->getIdentity()->id.'_')) . '.' . pathinfo(strtolower($info['url']), PATHINFO_EXTENSION);

		// insert entry
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $this->view->partial('autopost/aud-.phtml', array(
				'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				'original' => $x,
				'data' => Petolio_Service_Parse::_($z['v'])
			)),
			'serialized' => serialize($z),
			'rights' => $r,
			'scope' => 'po_dashboard'
		))->save();
	}

	/**
	 * Post uploaded picture
	 * @param string picture url
	 * @param int rights value
	 * @param array params
	 */
	private function uploadPicture($x, $r, $z) {
		// get filename and path
		$upload = pathinfo($x, PATHINFO_BASENAME);
		$path = pathinfo($x, PATHINFO_DIRNAME) . '/';

		// file not found?
		if(!@file_exists($this->upload_dir . $upload))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('File not found')));

		// new filename (remove new mark)
		$filename = substr($upload, 1);

		// can't rename the file?
		if(!@rename($this->upload_dir . $upload, $this->upload_dir . $filename))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Cannot rename the file')));

		// get file props
		$props = @getimagesize($this->upload_dir . $filename);

		// constant matrix
		$transform = array(
			'image/gif' => IMAGETYPE_GIF,
			'image/jpeg' => IMAGETYPE_JPEG,
			'image/png' => IMAGETYPE_PNG
		);

		// decide the thumb filename
		$thumb = md5($filename) . image_type_to_extension($transform[$props['mime']]);

		// make a thumbnail from the picture
		list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["small"]);
		Petolio_Service_Image::output($this->upload_dir . $filename, $this->upload_dir . $thumb, array(
			'type'   => $transform[$props['mime']],
			'width'   => $w,
			'height'  => $h,
			'method'  => THUMBNAIL_METHOD_SCALE_MIN
		));

		// add to serialized
		$z['l']['thumb'] = $thumb;
		$z['l']['value'] = $path . $filename;

		// insert entry
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $this->view->partial('autopost/img-.phtml', array(
				'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				'original' => $path . $filename,
				'thumb' => $thumb,
				'data' => Petolio_Service_Parse::_($z['v'])
			)),
			'serialized' => serialize($z),
			'rights' => $r,
			'scope' => 'po_dashboard'
		))->save();
	}

	/**
	 * Post uploaded audio
	 * @param string audio url
	 * @param int rights value
	 * @param array params
	 */
	private function uploadAudio($x, $r, $z) {
		// get filename and path
		$upload = pathinfo($x, PATHINFO_BASENAME);
		$path = pathinfo($x, PATHINFO_DIRNAME) . '/';

		// file not found?
		if(!@file_exists($this->upload_dir . $upload))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('File not found')));

		// new filename (remove new mark)
		$filename = substr($upload, 1);

		// can't rename the file?
		if(!@rename($this->upload_dir . $upload, $this->upload_dir . $filename))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $this->translate->_('Cannot rename the file')));

		// add to serialized
		$z['l']['value'] = $path . $filename;

		// insert entry
		$this->db->dash->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'data' => $this->view->partial('autopost/aud-.phtml', array(
				'user' => "<a style='font-weight: bold;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				'original' => $path . $filename,
				'data' => Petolio_Service_Parse::_($z['v'])
			)),
			'serialized' => serialize($z),
			'rights' => $r,
			'scope' => 'po_dashboard'
		))->save();
	}

	/**
	 * Upload picture or audio action
	 */
	public function uploadAction() {
		// upload type
		$type = @(string)$this->request->getParam('type');
		if(!$type)
			return die(json_encode(array('success' => false, 'msg' => $this->translate->_('Security check violation'))));

		// get the file
		$file = reset($_FILES);

		// check for upload errors
		if($file['error'] == 2) return die(json_encode(array('success' => false, 'msg' => $this->translate->_('File size is too big'))));
		if($file['error'] == 3) return die(json_encode(array('success' => false, 'msg' => $this->translate->_('The uploaded file was only partially uploaded'))));
		if($file['error'] == 4) return die(json_encode(array('success' => false, 'msg' => $this->translate->_('No file was uploaded'))));

		// check for malicious files
		if(!is_uploaded_file($file['tmp_name']))
			return die(json_encode(array('success' => false, 'msg' => $this->translate->_('Possible file upload attack'))));

		// correct mine type
		$finfo = new finfo(FILEINFO_MIME);
		$ftype = $finfo->file($file['tmp_name']);
		$file['type'] = substr($ftype, 0, strpos($ftype, ';'));

		// check for correct content type
		switch($type) {
			case "picture":
				if(!($file['type'] == 'image/gif'
						|| $file['type'] == 'image/jpeg'
						|| $file['type'] == 'image/png'))
					return die(json_encode(array('success' => false, 'msg' => $this->translate->_('The selected file is not a picture'))));

				// constant matrix
				$transform = array(
					'image/gif' => IMAGETYPE_GIF,
					'image/jpeg' => IMAGETYPE_JPEG,
					'image/png' => IMAGETYPE_PNG
				);

				$ex = image_type_to_extension($transform[$file['type']]);
			break;

			case "audio":
				if(!($file['type'] == 'audio/mpeg'
						|| $file['type'] == 'audio/mp4'
						|| $file['type'] == 'audio/ogg'
						|| $file['type'] == 'audio/wav'
						|| $file['type'] == 'image/vnd.wave'))
					return die(json_encode(array('success' => false, 'msg' => $this->translate->_('The selected file is not an audio'))));

				$ex = '.' . pathinfo(strtolower($file['name']), PATHINFO_EXTENSION);
			break;
		}

		// decide the filename
		$filename = 'n' . md5(uniqid($this->auth->getIdentity()->id.'_')) . $ex;

		// move to location
		$temporary = @move_uploaded_file($file['tmp_name'], $this->upload_dir . $filename);
		if(!$temporary)
			die(json_encode(array('success' => false, 'msg' => $this->translate->_("Can't move from temporary folder to upload folder"))));

		// make a thumbnail from the picture
		if($type == "picture") {
			$props = @getimagesize($this->upload_dir . $filename);
			list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["pic"]);
			if($props[0] > $w || $props[1] > $h) {
				Petolio_Service_Image::output($this->upload_dir . $filename, $this->upload_dir . $filename, array(
					'type'   => $transform[$file['type']],
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MAX
				));
			}
		}

		// return true
		return die(json_encode(array('success' => true, 'location' => PO_BASE_URL . 'images/userfiles/dashboard/' . $filename)));
	}

	/**
	 * Load or Save the notification option for the logged in user
	 */
	public function notificationAction() {
		// get user
		$this->db->users->find($this->auth->getIdentity()->id);

		// find status
		$status = @(int)$this->request->getParam('save');

		// not set? load
		if(!isset($status))
			return Petolio_Service_Util::json(array('success' => true, 'status' => $this->db->users->getDashEmailNotification()));

		// set? save
		else {
			$inverted = $status == 1 ? 0 : 1;
			$this->db->users->setDashEmailNotification($inverted)->save();

			// return json
			return Petolio_Service_Util::json(array('success' => true, 'status' => $inverted));
		}
	}
}