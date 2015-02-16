<?php

/**
 * Works hand in hand with ErrorController (that displays microsites to other users)
 */
class MicrositesController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $auth = null;
	private $config = null;
	private $request = null;
	private $up = null;
	private $yt_name = null;

	private $micro = null;
	private $tpl = null;
	private $attr = null;
	private $folders = null;
	private $files = null;
	private $imap = null;
	private $imgs = null;

	private $flag = null;
	private $unlisted = null;
	private $menu = null;

	public function preDispatch()
	{
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("This is available only for registered users. Please register and/or login.");
			return $this->_helper->redirector('index', 'site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2 && $this->request->getActionName() != 'flag') {
			$this->msg->messages[] = $this->translate->_("Microsites only available for service providers.");
			return $this->_helper->redirector('index', 'site');
		}

		// send auth to view and admin, duh
		$this->view->auth = $this->auth;
		$this->view->admin = true;

		// set unlisted params
        $this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));

		// figure out the menu
		$this->menu = array(
			1 => array(
				'link' => $this->view->url(array('controller'=>'microsites', 'action'=>'index'), 'default', true),
				'name' => $this->translate->_("Index")
			),
			2 => array(
				'link' => $this->view->url(array('controller'=>'microsites', 'action'=>'index-pictures'), 'default', true),
				'name' => $this->translate->_("Pictures")
			),
			3 => array(
				'link' => $this->view->url(array('controller'=>'microsites', 'action'=>'index-videos'), 'default', true),
				'name' => $this->translate->_("Videos")
			),
		);
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

	public function init()
	{
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;

		$this->micro = new Petolio_Model_PoMicrosites();
		$this->tpl = new Petolio_Model_PoTemplates();
		$this->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->folders = new Petolio_Model_DbTable_PoFolders();
		$this->files = new Petolio_Model_PoFiles();
		$this->imgs = new Petolio_Model_PoFiles();
		$this->imap = new Petolio_Model_PoFilesMapper();

		$this->flag = new Petolio_Model_PoFlags();
	}

	/*
	* Has microsite ?
	*/
	private function hasMicrosite($id) {
		$results = $this->micro->getMapper()->fetchList("user_id = '{$id}'");
		if($results) {
			$this->micro = reset($results);
			return true;
		} else
			return false;
	}

	/*
	 * Index action, view microsite or redirect to add
	 */
	public function indexAction($location = false, &$body = false)
	{
		// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first.");
			return $this->_helper->redirector('add', 'microsites');
		}

		// if flagged, load reasons
		$this->view->flagged = array();
		if($this->micro->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->flag->getMapper()->fetchList("scope = 'po_microsites' AND entry_id = '{$this->micro->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// send microsite template
		$this->view->microsite = $this->micro;

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get template
		$template = $this->tpl->find($this->micro->getTemplateId());
		$data = file_get_contents('../application/modules/frontend/views/templates/' . $template->getFilename());

		// load microsite attributes
		$files = array();
		$attributes = reset($this->attr->loadAttributeValues($template, false, $this->micro->getId()));
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
		foreach($this->menu as $idx => $link) {
			$value = "<a href='{$link['link']}'>{$link['name']}</a>";
			$data = str_replace("{menu:{$idx}}", $value, $data);
		}

		// help checking for valid stuff
		function check_valid($what) {
			return substr($what, 0, 6) == '<small' ? false : true;
		}

		// get user info
		$info = $this->_helper->userinfo($this->auth->getIdentity()->id);

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
		$data = str_replace("{contact}", $this->translate->_("Service Provider") . ": {$name}<br /><br />".
			$this->translate->_("Contact Info") .": {$contact}<br /><br />".
			$this->translate->_("Address").": {$address}", $data);

		// prepare body for next controllers
		$body = Petolio_Service_Util::get_string_between($data, "{body}", "{/body}", $error = array());
		$body = $body[1][0] . '{body}' . $body[1][1];
		$data = str_replace(array("{body}", "{/body}"), array(), $data);

		// output data
		$this->view->html = $data;
		$this->view->location = $location;

		// render microsite services from owner
		$this->loadUserServices($this->auth->getIdentity()->id);
		echo $this->view->render('microsites/view-services.phtml');
	}

	private function loadUserServices($user) {
    	// get page
		$page = $this->request->getParam('services-page') ? intval($this->request->getParam('services-page')) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
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
		$paginator->setItemCountPerPage($this->config["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output services
		$this->view->yourServices = $serviceModel->formatServices($paginator);
	}

	/**
	 * Page 2 of Microsite (pictures)
	 */
	public function indexPicturesAction() {
		$body = null;
		$this->_helper->viewRenderer('index');

		// overload body
		$this->indexAction('index-pictures', $body);
		$this->view->html = $body;

		// get pictures
		$pictures = array();
		$gallery = $this->folders->findFolders(array('id' => $this->micro->getFolderId(), 'ownerId' => $this->auth->getIdentity()->id));
		if(isset($gallery)) $pictures = $this->imap->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}'", "date_created ASC");
		if(isset($pictures) && count($pictures) > 0) {
			$this->view->listing = array();
			foreach($pictures as $pic)
				$this->view->listing[$pic->getId()] = $pic->getFile();
		}
	}

	/**
	 * Page 3 of Microsite (videos)
	 */
	public function indexVideosAction() {
		$body = null;
		$this->_helper->viewRenderer('index');

		// overload body
		$this->indexAction('index-videos', $body);
		$this->view->html = $body;

		// get videos
		$videos = array();
		$media = $this->folders->findFolders(array('id' => $this->micro->getFolderId(), 'ownerId' => $this->auth->getIdentity()->id));
		if(isset($media)) $videos = $this->imap->fetchList("type = 'video' AND folder_id = '{$media->getId()}'", "id ASC");
		if(isset($videos) && count($videos) > 0) {
	    	// youtube wrapper
			$youtube = Petolio_Service_YouTube::factory('Master');
			$youtube->CFG = array(
				'username' => $this->config["youtube"]["username"],
				'password' => $this->config["youtube"]["password"],
				'app' => $this->config["youtube"]["app"],
				'key' => $this->config["youtube"]["key"]
			);

			// needed upfront
			$ds = DIRECTORY_SEPARATOR;
			$upload_dir = "..{$ds}data{$ds}userfiles{$ds}microsites{$ds}{$this->micro->getId()}{$ds}";

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

	/**
	 * Activate microsite
	 */
	public function activateAction()
	{
		// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first.");
			return $this->_helper->redirector('add', 'microsites');
		}

		$this->micro->setActive(1)->save();
		$this->msg->messages[] = $this->translate->_("You have successfully activated your microsite.");
		return $this->_helper->redirector('index', 'microsites');
	}

	/**
	 * Deactivate microsite
	 */
	public function deactivateAction()
	{
		// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first.");
			return $this->_helper->redirector('add', 'microsites');
		}

		$this->micro->setActive(0)->save();
		$this->msg->messages[] = $this->translate->_("You have successfully deactivated your microsite.");
		return $this->_helper->redirector('index', 'microsites');
	}

	/*
	 * Add action, user can create a microsite for himself
	 */
	public function addAction()
	{
		// already has a microsite ? awwww
		if ($this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You have already created your microsite. You can edit it here.");
			return $this->_helper->redirector('edit', 'microsites');
		}

		if(!is_null($this->request->getParam('template')))
			$this->step2();
		else
			$this->step1();
	}

    /**
     * Microsite creation step 1 - Select template
     */
    private function step1()
    {
		// init form
		$form = new Petolio_Form_Microsite();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// redirect
		$data = $form->getValues();
		return $this->_redirect('microsites/add/template/'. $data['attribute_set']);
    }

    /**
     * Microsite creation step 2 - Add microsite information
     */
    private function step2()
    {
		// init form
		$form = new Petolio_Form_Microsite($this->request->getParam('template'));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get template
		$results = $this->tpl->getMapper()->fetchList("attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('template'), Zend_Db::BIGINT_TYPE)." && scope = 'po_microsites'");
		$template = reset($results);

		// get data
		$data = $form->getValues();

		// save microsite
		$this->micro->setUserId($this->auth->getIdentity()->id);
		$this->micro->setUrl($data['url']);
		$this->micro->setTemplateId($template->getId());
		$this->micro->save(true, true);

		// save attributes
		unset($data['url']);
		$this->attr->saveAttributeValues($data, $this->micro->getId());

		// redirect to microsite gallery action
		$this->msg->messages[] = $this->translate->_("Your microsite has been saved successfully.");
		return $this->_redirect('microsites/pictures');
    }

    /**
     * Edit your Microsite
     */
    public function editAction() {
    	// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first before you can create a gallery.");
			return $this->_helper->redirector('add', 'microsites');
		}

		// get template
		$template = $this->tpl->find($this->micro->getTemplateId());

		// prepare form populate
		$populate = array(
			'url' => $this->micro->getUrl()
		);

		// get template name
        $sets = new Petolio_Model_PoAttributeSets();
        $sets->find($template->getAttributeSetId());
	    $this->view->template = Petolio_Service_Util::Tr($sets->getName());

		// load microsite attributes
		$files = array();
		$attributes = reset($this->attr->loadAttributeValues($template, false, $this->micro->getId()));
		foreach($attributes as $attr) {
			// files present ? tell me
			if($attr->getAttributeInputType()->getName() == 'file' && !is_null($attr->getAttributeEntity()->getValue()))
				$files[$attr->getCode()] = array($attr->getAttributeEntity()->getId(), $attr->getAttributeEntity()->getValue());

			// populate the populate variable :P
			$populate[$attr->getCode()] = array ("value" => $attr->getAttributeEntity()->getValue(),
												 "type" => $attr->getAttributeInputType()->getType());
		}

    	// get form
		$form = new Petolio_Form_Microsite($template->getAttributeSetId(), $files);

		// populate form
		$form->populate($populate);

		// send to template
		$this->view->form = $form;
		$this->view->microsite = $this->micro;

		// if we changed templates
		$change = $this->request->getParam('change');
		if(isset($change)) {
			// delete all attributes and the microsite
			$this->attr->deleteAttributeValues($this->micro->getId(), $template->getAttributeSetId());
			$this->micro->deleteRowByPrimaryKey();

			// redirect to microsite add
			$this->msg->messages[] = $this->translate->_("Your microsite has been successfully reset.");
			return $this->_redirect('microsites/add');
		}

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save microsite
		$this->micro->setUrl($data['url']);
		$this->micro->setDateModified(date('Y-m-d H:i:s'));
		$this->micro->save(false, true);

		// save attributes
		unset($data['url']);
		$this->attr->saveAttributeValues($data, $this->micro->getId());

		// redirect to microsite gallery action
		$this->msg->messages[] = $this->translate->_("Your microsite has been saved successfully.");
		return $this->_redirect('microsites/pictures');
    }

	/*
	 * Upload pictures interface
	 */
	public function picturesAction()
	{
		// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first before you can create a gallery.");
			return $this->_helper->redirector('add', 'microsites');
		}

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}microsites{$ds}{$this->micro->getId()}{$ds}";

		// create a folder for our microsite if it does not exist
		$gallery = null;
		if ($this->micro->getFolderId()) {
			$search_vars = array('id' => $this->micro->getFolderId());
			$gallery = $this->folders->findFolders($search_vars);
		}
		if (!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'microsite', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->folders->addFolder($vars);

			// save the folder in our microsite too
			$this->micro->setFolderId($gallery->getId());
			$this->micro->save();
		}

		// load form
		$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
		$this->view->form = $form;

		// get & show all pictures
		$result = $this->files->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
		$this->view->gallery = $result;
		$this->view->microsite = $this->micro;

    	// make picture primary
    	$primary = $this->request->getParam('primary');
    	if (isset($primary)) {
			// get level
			$result = $this->files->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if (!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_helper->redirector('pictures', 'microsites');
			} else
				$pic = reset($result);

			// get all other pictures
			$result = reset($this->files->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_helper->redirector('pictures', 'microsites');
		}

		// get picture remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->files->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if (!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_helper->redirector('pictures', 'microsites');
			} else
				$pic = reset($result);

			// delete from hdd
			@unlink($upload_dir . $pic->getFile());
			@unlink($upload_dir . 'thumb_' . $pic->getFile());
			@unlink($upload_dir . 'small_' . $pic->getFile());

			// delete all comments, likes and subscriptions
			$comments = new Petolio_Model_PoComments();
			$ratings = new Petolio_Model_PoRatings();
			$subscriptions = new Petolio_Model_PoSubscriptions();
			$comments->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$ratings->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$subscriptions->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");

			// delete file from db
			$pic->deleteRowByPrimaryKey();

			// update dashboard
			$fake = array($this->translate->_("microsite")); unset($fake);
			Petolio_Service_Autopost::factory('image', $pic->getFolderId(),
				'microsite',
				$this->micro->getId(),
				$this->view->url(array('controller' => $this->micro->getUrl(), 'action'=>'index-pictures'), 'default', true),
				$this->micro->getUrl()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your Picture has been deleted successfully.");
			return $this->_helper->redirector('pictures', 'microsites');
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the microsite directory
		if (!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if (!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the microsites folder on disk.")));
				return $this->_helper->redirector('pictures', 'microsites');
			}
		}

		// create the microsite gallery directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the microsite folder on disk.")));
				return $this->_helper->redirector('pictures', 'microsites');
			}
		}

		// prepare upload files
		$i = 0;
		$errors = array();
		$success = array();

		// get addapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('IsImage', false);

		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if (!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your picture / pictures exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
				return $this->_helper->redirector('pictures', 'microsites');
			}
		}

		// upload each file
		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

			$adapter->clearFilters();
			$adapter->addFilter('Rename',
				array('target' => $upload_dir . $new_filename, 'overwrite' => true));

			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME)))
				$errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
			else
				$success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
		}

		// go through each picture
		foreach($success as $original => $pic) {
			// resize original picture if bigger
			$props = @getimagesize($pic);
			list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["pic"]);
			if($props[0] > $w || $props[1] > $h) {
				Petolio_Service_Image::output($pic, $pic, array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MAX
				));
			}

			// make big thumbnail
			list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["big"]);
			Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN
			));

			// make small thumbnail
			list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["small"]);
			Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'small_' . pathinfo($pic, PATHINFO_BASENAME), array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN
			));

			// save every file in db
			$opt = array(
				'file' => pathinfo($pic, PATHINFO_BASENAME),
				'type' => 'image',
				'size' => filesize($pic) / 1024,
				'folder_id' => $gallery->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original
			);

			$file = new Petolio_Model_PoFiles();
			$file->setOptions($opt);
			$file->save();

			// post on dashboard
			$fake = array($this->translate->_("microsite")); unset($fake);
			Petolio_Service_Autopost::factory('image', $file,
				'microsite',
				$this->micro->getId(),
				$this->view->url(array('controller' => $this->micro->getUrl(), 'action'=>'index-pictures'), 'default', true),
				$this->micro->getUrl()
			);
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your microsite pictures have been uploaded successfully.");
		return $this->_helper->redirector('pictures', 'microsites');
	}

	/**
	 * Video upload interface
	 */
	public function videosAction() {
		// no microsite ? awww
		if (!$this->hasMicrosite($this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You must create a microsite first before you can create a gallery.");
			return $this->_helper->redirector('add', 'microsites');
		}

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}microsites{$ds}{$this->micro->getId()}{$ds}";

		// create a folder for our microsite if it does not exist
		$gallery = null;
		if ($this->micro->getFolderId()) {
			$search_vars = array('id' => $this->micro->getFolderId());
			$gallery = $this->folders->findFolders($search_vars);
		}
		if (!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'microsite', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->folders->addFolder($vars);

			// save the folder in our microsite too
			$this->micro->setFolderId($gallery->getId());
			$this->micro->save();
		}

		// create microsite directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the microsite folder on disk.")));
				return $this->_helper->redirector('videos', 'microsites');
			}
		}

    	// youtube wrapper
		$youtube = Petolio_Service_YouTube::factory('Master');
		$youtube->CFG = array(
			'username' => $this->config["youtube"]["username"],
			'password' => $this->config["youtube"]["password"],
			'app' => $this->config["youtube"]["app"],
			'key' => $this->config["youtube"]["key"]
		);

		// create a new video
		$video = new Zend_Gdata_YouTube_VideoEntry();
		$video->setVideoTitle(md5(mt_rand()));
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($this->micro->getUrl(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr($this->micro->getUrl(), 0, 30) . ', microsite, petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'microsites', 'action'=>'videos'), 'default', true);

		// get all videos and refresh cache
		$result = $this->imap->fetchList("type = 'video' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;
		$this->view->microsite = $this->micro;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('microsites/videos');
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->imap->fetchList("file = '{$filename}' AND folder_id = '{$gallery->getId()}'");
			if (is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('microsites/videos');
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('microsites/videos');
			}

			// save video in db
			$this->imgs->setOptions(array(
				'file' => $filename,
				'type' => 'video',
				'size' => 1,
				'folder_id' => $gallery->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original_name
			))->save();

			// post on dashboard
			$fake = array($this->translate->_("microsite")); unset($fake);
			Petolio_Service_Autopost::factory('video', $this->imgs,
				'microsite',
				$this->micro->getId(),
				$this->view->url(array('controller' => $this->micro->getUrl(), 'action'=>'index-videos'), 'default', true),
				$this->micro->getUrl()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your microsite video link has been successfully added.");
			return $this->_redirect('microsites/videos');
		}

		// get video remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if (!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Video does not exist.");
				return $this->_helper->redirector('index', 'site');
			} else $vid = reset($result);

			// delete from hdd
			@unlink($upload_dir . $vid->getFile());

			// delete all comments, likes and subscriptions
			$comments = new Petolio_Model_PoComments();
			$ratings = new Petolio_Model_PoRatings();
			$subscriptions = new Petolio_Model_PoSubscriptions();
			$comments->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");
			$ratings->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");
			$subscriptions->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");

			// delete video
			$table = new Petolio_Model_DbTable_PoFiles();
			$where = $table->getAdapter()->quoteInto('id = ?', $vid->getId());
			$table->delete($where);

			// only delete from youtube if its an upload and not a link
			if(round($vid->getSize()) == 0) {
				// find on youtube
				$videoEntryToDelete = null;
				foreach($youtube->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads') as $entry) {
					if ($entry->getVideoId() == pathinfo($vid->getFile(), PATHINFO_FILENAME)) {
						$videoEntryToDelete = $entry;
						break;
					}
				}

				// delete from youtube (we dont care about errors at this point)
				try {
					$youtube->delete($videoEntryToDelete);
				} catch (Exception $e) {}
			}

			// update dashboard
			$fake = array($this->translate->_("microsite")); unset($fake);
			Petolio_Service_Autopost::factory('video', $vid->getFolderId(),
				'microsite',
				$this->micro->getId(),
				$this->view->url(array('controller' => $this->micro->getUrl(), 'action'=>'index-videos'), 'default', true),
				$this->micro->getUrl()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your microsite video has been deleted successfully.");
			return $this->_redirect('microsites/videos');
		}

		// no status code ? return here
		if(!isset($_GET['status']))
			return;

		// define arrays
		$errors = array();
		$success = array();

		// do stuff based on status
		switch($_GET['status']) {
			// successfully uploaded
			case '200':
				// check if the name is null or not
				if(is_null($this->yt_name)) {
					$errors[] = $this->translate->_("Video title is empty!");
					break;
				}

				// get video entity
				$videoEntry = $youtube->getVideoEntry($_GET['id'], null, true);

				// set our specified title
				$videoEntry->setVideoTitle($this->yt_name);

				// make video unlisted
				$videoEntry->setExtensionElements(array($this->unlisted));

				// update video on youtube
				$new_entry = $youtube->updateEntry($videoEntry, $videoEntry->getEditLink()->getHref());

				// save a filename
				$filename = "{$_GET['id']}.yt";
				$original_name = "{$this->yt_name}.yt";

				// save a file in the directory
				file_put_contents($upload_dir . $filename, serialize($new_entry));

				// save video in db
				$this->imgs->setOptions(array(
					'file' => $filename,
					'type' => 'video',
					'size' => 0,
					'folder_id' => $gallery->getId(),
					'owner_id' => $this->auth->getIdentity()->id,
					'description' => $original_name
				))->save();

				// post on dashboard
				$fake = array($this->translate->_("microsite")); unset($fake);
				Petolio_Service_Autopost::factory('video', $this->imgs,
					'microsite',
					$this->micro->getId(),
					$this->view->url(array('controller' => $this->micro->getUrl(), 'action'=>'index-videos'), 'default', true),
					$this->micro->getUrl()
				);

				// set success
				$success[] = $this->yt_name;
			break;

			// error
			default:
				// set error
				$errors[] = $_GET['code'];
			break;
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back
		$this->msg->messages[] = $this->translate->_("Your pet videos has been updated successfully.");
		return $this->_redirect('microsites/videos');
	}

    /**
     * Set video name when uploading to youtube
     */
    public function youtubeAction() {
    	// check if name was set
    	if(!isset($_POST['name']) || empty($_POST['name']))
    		return Petolio_Service_Util::json(array('success' => false));

    	setcookie("petolio_youtube_title", $_POST['name'], time() + 86400, "/");
    	return Petolio_Service_Util::json(array('success' => true));
    }
}