<?php

class ErrorController extends Zend_Controller_Action
{
	public function errorAction()
	{
		// get translate obj
		$translate = Zend_Registry::get('Zend_Translate');

		// get error handler
		$errors = $this->_getParam('error_handler');
		if (!$errors || !$errors instanceof ArrayObject) {
			$this->view->message = $translate->_('You have reached the error page');
			return;
		}

		// switch between error types
		$is_microsite = false;
		switch ($errors->type) {
			// no route
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
				break;

			// no controller
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				// get params and load microsite
				$params = $errors->request->getParams();
				$load404 = $this->loadMicrosite($params);

				$is_microsite = !$load404;

				// proceed with 404 error
				if ($load404) {
					$this->getResponse()->setHttpResponseCode(404);
					$priority = Zend_Log::NOTICE;
					$this->view->message = $translate->_('Page not found');
				}
				break;

			// no action
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				$this->view->message = $translate->_('Page not found');
				break;

			// application error
			default:
				$this->getResponse()->setHttpResponseCode(500);
				$priority = Zend_Log::CRIT;
				$this->view->message = $translate->_('Application error');
				break;
		}

		// Log exception, if logger available
		$log = $this->getLog();
		if ($log != false) {
			$log->log($this->view->message, $priority, $errors->exception);
			$log->log('Request Parameters', $priority, $errors->request->getParams());
		}

		// conditionally display exceptions
		if ($this->getInvokeArg('displayExceptions') == true) {
			$this->view->exception = $errors->exception;
		}

		// write exceptions in log
		if (!$is_microsite) {
			Zend_Registry::get('Zend_Log')->err("Exception -> Message: {$errors->exception->getMessage()}");
			Zend_Registry::get('Zend_Log')->err("Exception -> Trace: {$errors->exception->getTraceAsString()}");
			Zend_Registry::get('Zend_Log')->err("Exception -> Params: " . print_r($errors->request->getParams(), true));
		}

		// bla bla bla
		$this->view->request = $errors->request;
		$this->view->translate = $translate;
	}

	/**
	 * Load microsite
	 * @param $ctrl - Controller name
	 * @param $act - Action name
	 *
	 * @return bool - true for error, false if found
	 */
	private function loadMicrosite($params = array()) {
		// split params
		$ctrl = @$params['controller'];
		$act = @$params['action'];
		$iframe = @$params['iframe'];

		// if iframe, disable all the bullshit
		if($iframe) {
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$this->view->iframe = true;
		}

		// get the stuff that we need
		$auth = Zend_Auth::getInstance();
		$translate = Zend_Registry::get('Zend_Translate');

		$micro = new Petolio_Model_PoMicrosites();
		$user = new Petolio_Model_PoUsers();
		$flag = new Petolio_Model_PoFlags();
		$tpl = new Petolio_Model_PoTemplates();
		$attrs = new Petolio_Model_DbTable_PoAttributes();

		// send to template
		$this->view->auth = $auth;

		// iframe or not
		$add = array();
		if($iframe) {
			$add = array('iframe' => 'true');
			$file = file_get_contents("http://{$_SERVER['SERVER_NAME']}/fluxbb", false, stream_context_create(array(
				'http'=>array(
					'method'=>"GET",
					'header'=>"Accept-language: en\r\n" .
	            		"Cookie: {$_SERVER['HTTP_COOKIE']}\r\n"
			  )
			)));

			$errors = array();
			$split = Petolio_Service_Util::get_string_between($file, '<!-- site start -->', '<!-- site end -->', $errors);
			list($header, $footer) = $split[1];

			$this->view->header = $header . "<div class='rightbox' style='padding: 0px; border: none; border-radius: 0px; width: 837px; height: 100%; overflow:hidden; overflow-y:scroll;'><div style='padding: 10px;'>";
			$this->view->footer = "</div></div>" . $footer;
			$this->view->frame = true;
		}

		// figure out the menu
		$menu = array(
			1 => array(
				'link' => $this->view->url(array_merge(array('controller'=>$ctrl, 'action'=>'index'), $add), 'default', true),
				'name' => $translate->_("Index")
			),
			2 => array(
				'link' => $this->view->url(array_merge(array('controller'=>$ctrl, 'action'=>'index-pictures'), $add), 'default', true),
				'name' => $translate->_("Pictures")
			),
			3 => array(
				'link' => $this->view->url(array_merge(array('controller'=>$ctrl, 'action'=>'index-videos'), $add), 'default', true),
				'name' => $translate->_("Videos")
			),
		);

		// load microsite
		$results = $micro->getMapper()->fetchList("url = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($ctrl)." AND active = 1");
		if($results)
			$micro = reset($results);
		else
			return true;

		// load microsite's user
		$user->getMapper()->find($micro->getUserId(), $user);
		$this->view->user = $user;

		// if flagged, load reasons
		$this->view->flagged = array();
		if($micro->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $flag->getMapper()->fetchList("scope = 'po_microsites' AND entry_id = '{$micro->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// send microsite template
		$this->view->microsite = $micro;

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get template
		$template = $tpl->find($micro->getTemplateId());
		$data = file_get_contents('../application/modules/frontend/views/templates/' . $template->getFilename());

		// load microsite attributes
		$files = array();
		$attributes = reset($attrs->loadAttributeValues($template, false, $micro->getId()));
		foreach($attributes as $attr) {
			$value = $attr->getAttributeEntity()->getValue();
			// in case of file, you know what to do
			if($attr->getAttributeInputType()->getName() == 'file')
				if(is_null($attr->getAttributeEntity()->getValue()))
					$value = "../../no-pet.jpg";
				else
					$value = sha1(md5($attr->getAttributeEntity()->getId()) . 'unimatrix') . '.' . pathinfo($attr->getAttributeEntity()->getValue(), PATHINFO_EXTENSION);

			// the rest, just fill the data as is
			$data = str_replace("{attr:{$attr->getCode()}}", $value, $data);
		}

		// add menu links
		foreach($menu as $idx => $link) {
			$value = "<a href='{$link['link']}' class='showloading'>{$link['name']}</a>";
			$data = str_replace("{menu:{$idx}}", $value, $data);
		}

		// help checking for valid stuff
		function check_valid($what) {
			return substr($what, 0, 6) == '<small' ? false : true;
		}

		// get user info
		$info = $this->_helper->userinfo($user->getId());

		// get names
		$name = check_valid($info['first_name']) && check_valid($info['last_name']) ? "{$info['first_name']} {$info['last_name']}" : $info['name'];

		// get contact info
		$contact = check_valid($info['email']) ? ', ' . $info['email'] : '';
		$contact .= check_valid($info['business_phone']) ? ', <br/>' . $info['business_phone'] : '';
		$contact .= check_valid($info['homepage']) ? ', <br/>' . $info['homepage'] : '';
		if ( strlen($contact) > 2 ) {
			$contact = substr($contact, 2);
		}

		// get address
		$address = check_valid($info['street']) ? ', ' . $info['street'] : '';
		$address .= check_valid($info['address']) ? ', ' . $info['address'] : '';
		$address .= check_valid($info['zipcode']) ? ', ' . $info['zipcode'] : '';
		$address .= check_valid($info['location']) ? ', ' . $info['location'] : '';
		$address .= check_valid($info['country_id']) ? ', ' . $info['country_id'] : '';
		$address = substr($address, 2);

		// add contact info
		$data = str_replace("{contact}", $translate->_("Service Provider") . ": {$name}<br /><br />".
			$translate->_("Contact Info") .": {$contact}<br /><br />".
			$translate->_("Address").": {$address}", $data);

		// prepare body for next controllers
		$body = Petolio_Service_Util::get_string_between($data, "{body}", "{/body}", $error = array());
		$body = $body[1][0] . '{body}' . $body[1][1];
		$data = str_replace(array("{body}", "{/body}"), array(), $data);

		// output data
		$this->view->html = $data;
		$this->view->location = $act == 'index' ? false : $act;

		// load pictures or videos, depending on location
		if($this->view->location == 'index-pictures') $this->loadMicrositePictures($body, $micro);
		if($this->view->location == 'index-videos') $this->loadMicrositeVideos($body, $micro);

		// if is yours tell me :)
		if (isset($auth->getIdentity()->id)) {
			$this->view->admin = $micro->getUserId() == $auth->getIdentity()->id ? true : false;
		}

		// load sidebar
		if ($auth->hasIdentity()) {
			$this->view->identity = $auth->getIdentity();
			$messages = new Petolio_Model_DbTable_PoMessages();
			$this->view->new_messages = $messages->countNew($auth->getIdentity()->id);
			$this->view->render('sidebar.phtml');
		}

		// render microsite and return false (not an error)
		$this->render('microsites/index', null, 'microsites');

		// render microsite services from owner
		$this->loadUserServices($params, $user->getId());
		$this->render('microsites/view-services', null, true);

		// return false
		return false;
	}

	private function loadUserServices($params, $user) {
		$config = Zend_Registry::get("config");
		$page = @$params['services-page'];

    	// get page
		$page = $page ? $page : 0;

		// do sorting 1
		$this->view->order = @$params['order'];
		$this->view->dir = @$params['dir'] == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'type') $sort = "type {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "a.date_created {$this->view->dir}";
		}

		// get services
		$serviceModel = new Petolio_Model_PoServices();
		$paginator = $serviceModel->getServices('paginator', "a.user_id = {$user}", $sort);
		$paginator->setItemCountPerPage($config["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output services
		$this->view->yourServices = $serviceModel->formatServices($paginator);
	}

	/**
	 * Load Microsite Pictures
	 *
	 * @param string $body
	 * @param object $micro
	 */
	private function loadMicrositePictures($body, $micro) {
		$folders = new Petolio_Model_DbTable_PoFolders();
		$imap = new Petolio_Model_PoFilesMapper();

		// overload body
		$this->view->html = $body;

		// get pictures
		$pictures = array();
		$gallery = $folders->findFolders(array('id' => $micro->getFolderId(), 'ownerId' => $micro->getUserId()));
		if(isset($gallery)) $pictures = $imap->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}'", "date_created ASC");
		if(isset($pictures) && count($pictures) > 0) {
			$this->view->listing = array();
			foreach($pictures as $pic)
				$this->view->listing[$pic->getId()] = $pic->getFile();
		}
	}

	/**
	 * Load Microsite Videos
	 *
	 * @param string $body
	 * @param object $micro
	 */
	private function loadMicrositeVideos($body, $micro) {
		$folders = new Petolio_Model_DbTable_PoFolders();
		$imap = new Petolio_Model_PoFilesMapper();
		$config = Zend_Registry::get("config");

		// overload body
		$this->view->html = $body;

		// get videos
		$videos = array();
		$media = $folders->findFolders(array('id' => $micro->getFolderId(), 'ownerId' => $micro->getUserId()));
		if(isset($media)) $videos = $imap->fetchList("type = 'video' AND folder_id = '{$media->getId()}'", "id ASC");
		if(isset($videos) && count($videos) > 0) {
	    	// youtube wrapper
			$youtube = Petolio_Service_YouTube::factory('Master');
			$youtube->CFG = array(
				'username' => $config["youtube"]["username"],
				'password' => $config["youtube"]["password"],
				'app' => $config["youtube"]["app"],
				'key' => $config["youtube"]["key"],
			);

			// needed upfront
			$ds = DIRECTORY_SEPARATOR;
			$upload_dir = "..{$ds}data{$ds}userfiles{$ds}microsites{$ds}{$micro->getId()}{$ds}";

			// iterate over videos for cached entries
			foreach($videos as $idx => $one) {
				// get the cached entry
				$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir);

				// error? skip and remove from list
				if(!is_object($entry)) {
					unset($videos[$idx]);
					continue;
				}

				// set cached entry
				$one->setMapper($entry);
			}

			// output videos
			$this->view->listing = $videos;
		}
	}

	public function getLog()
	{
		$bootstrap = $this->getInvokeArg('bootstrap');
		if (!$bootstrap->hasResource('Log'))
			return false;

		return $bootstrap->getResource('Log');
	}
}