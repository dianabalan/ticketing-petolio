<?php

class GalleriesController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $auth = null;
    private $request = null;
    private $config = null;
    private $up = null;
    private $yt_name = null;
    private $unlisted = null;

    private $galleries = null;
    private $imap = null;
    private $imgs = null;

    private $db = null;

    public function preDispatch()
    {
		// send auth to template
		$this->view->auth = $this->auth;
		$this->view->request = $this->request;
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
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;
		$this->view->action = $this->request->getParam('action');

		$this->galleries = new Petolio_Model_PoGalleries();
		$this->folders = new Petolio_Model_PoFolders();
		$this->imap = new Petolio_Model_PoFilesMapper();
		$this->imgs = new Petolio_Model_PoFiles();

		$this->db = new stdClass();
		$this->db->service = new Petolio_Model_PoServices();
		$this->db->microsite = new Petolio_Model_PoMicrosites();
		$this->db->gallery = new Petolio_Model_PoGalleries();

		// set unlisted params
		$this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));
    }

    /*
     * Build pet search filter
     */
    private function buildSearchFilter($filter = array()) {
    	$search = array();

    	if (strlen($this->request->getParam('keyword'))) {
    		$filter[] = "(a.title LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." " .
   				"OR a.description LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%").")";
    		$search[] = $this->request->getParam('keyword');
    	}

    	if (strlen($this->request->getParam('owner'))) {
    		$filter[] = "c.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('owner'))."%");
    		$search[] = $this->request->getParam('owner');
    	}

    	if (strlen($this->request->getParam('fromdate'))) {
    		$from = $this->parseDate(base64_decode($this->request->getParam('fromdate')));

    		$filter[] = "UNIX_TIMESTAMP(a.date_created) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($from);
    		$search[] = base64_decode($this->request->getParam('fromdate'));
    	}

    	if (strlen($this->request->getParam('todate'))) {
    		$to = $this->parseDate(base64_decode($this->request->getParam('todate')));

    		$filter[] = "UNIX_TIMESTAMP(a.date_created) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($to);
    		$search[] = base64_decode($this->request->getParam('todate'));
    	}

    	if(count($search) > 0)
    		$this->view->filter = implode(', ', $search);

    	return implode(' AND ', $filter);
    }

    /**
     * Load a gallery by id.
     * @param int $galleryId
     */
    private function loadGallery($galleryId)
    {
    	$result = $this->galleries->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($galleryId, Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Gallery does not exist.");
    		return $this->_helper->redirector('index', 'galleries');
    	} else
    		return reset($result);
    }

    /**
     * Logged in redirector denies access to certain pages when the user is not logged in
     */
    private function verifyUser()
    {
    	// not logged in
    	if(!isset($this->auth->getIdentity()->id)):
    	$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
    	return $this->_helper->redirector('index', 'site');
    	endif;
    }

    /**
     * Render gallery options (the left side menu)
     */
    private function galleryOptions($gallery)
    {
    	$this->view->gallery = $gallery;
    	$this->view->render('galleries/gallery-options.phtml');
    }

    private function parseDate($date) {
    	// parse to date
    	if(preg_match('/^(\d\d?)\/(\d\d?)\/(\d\d\d\d)$/', $date)) {
    		$part = explode('/', $date);
    		return @mktime(0, 0, 0, $part[0], $part[1], $part[2]);
    	} else {
    		$part = explode('.', $date);
    		return @mktime(0, 0, 0, $part[1], $part[0], $part[2]);
    	}
    }

    public function indexAction() {
    	// start
    	$this->view->search = true;

		// build filter
		$filter = array("deleted = 0");
		$filter[] = "(c.active = 1 AND c.is_banned != 1)";
		$filter = $this->buildSearchFilter($filter);

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("All Galleries");

		// get page
		$page = $this->request->getParam('all-page');
		$page = $page ? intval($page) : 0;

		/*
		 * petolio session seed for random sorting
		 * basically the random sort is saved for the pagination to work properly
		 *
		if (!isset($_SESSION["petolio_seed"])) {
			$uniq_id = uniqid();
			$_SESSION["petolio_seed"] = substr($uniq_id, strlen($uniq_id) - 5);
		}*/
		$sort = "RAND(".date("Ymd").")";

		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Galleries_".$filter."_".$sort."_".$page);
		
		if (false === ($paginator = $cache->load($cacheID))) {
			// get galleries
			$paginator = $this->galleries->fetchListToPaginator($filter, $sort);
			$paginator->setItemCountPerPage($this->config["galleries"]["pagination"]["itemsperpage"]);
			$paginator->setCurrentPageNumber($page);
	
			// go through each item to add picture
	    	$files = new Petolio_Model_PoFiles();
			foreach($paginator as &$item) {
				// take the first picture
				$picture = !is_null($item['folder_id']) ? $files->fetchList("folder_id = {$item['folder_id']} AND type = 'image'", "date_created ASC") : array();
				$item['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
				$item['pictures_count'] = count($picture);
			}
			
			$cache->save($paginator, $cacheID);
		}

		// output galleries
		$this->view->galleries = $paginator;
    }

    public function mygalleriesAction()
    {
    	// start
    	$this->view->search = true;
    	$this->verifyUser();

    	// build filter
    	$filter = array(
    		"deleted = 0",
    		"a.owner_id = {$this->auth->getIdentity()->id}"
    	);
    	$filter = $this->buildSearchFilter($filter);

    	// search by ?
    	if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
    	else $this->view->title = $this->translate->_("My Galleries");

    	// get page
    	$page = $this->request->getParam('all-page');
    	$page = $page ? intval($page) : 0;

    	// get galleries
    	$paginator = $this->galleries->fetchListToPaginator($filter, "date_created DESC");
    	$paginator->setItemCountPerPage($this->config["galleries"]["pagination"]["itemsperpage"]);
    	$paginator->setCurrentPageNumber($page);

		// go through each item to add picture
    	$files = new Petolio_Model_PoFiles();
		foreach($paginator as &$item) {
			// take the first picture
			$picture = !is_null($item['folder_id']) ? $files->fetchList("folder_id = {$item['folder_id']} AND type = 'image'", "date_created ASC") : array();
			$item['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
			$item['pictures_count'] = count($picture);
		}

		// output galleries
    	$this->view->yours = $paginator;
    }

    public function viewAction()
    {
    	// get gallery
    	$gallery = $this->galleries->findWithReferences($this->request->getParam('gallery'));
    	if (!$gallery->getId()) {
    		$this->msg->messages[] = $this->translate->_("Gallery not found.");
    		return $this->_helper->redirector('index', 'galleries');
    	}

    	// load menu
    	$this->galleryOptions($gallery);
    	$this->view->admin = ( $this->auth->hasIdentity() && $this->auth->getIdentity()->id == $gallery->getOwnerId()) ? true : false;
    	$this->view->gallery = $gallery;

    	// get pictures
		$page = $this->request->getParam('page') ? intval($this->request->getParam('page')) : 0;
		$pictures = new Petolio_Model_PoFiles();

		if(isset($gallery)) {
			$paginator = $pictures->select2Paginator($pictures->getMapper()->getDbTable()->fetchList("type = 'image' AND folder_id = {$gallery->getFolderId()}", "date_created ASC"));
			$paginator->setItemCountPerPage(14);
			$paginator->setCurrentPageNumber($page);

			$pictures = array();
			foreach($paginator->getItemsByPage($page) as $row)
				$pictures[$row["id"]] = $row["file"];

			$this->view->files = $pictures;
			$this->view->picture_paginator = $paginator;
		}

    	// get videos
    	$videos = $this->imap->fetchList("type = 'video' AND folder_id = {$gallery->getFolderId()}", "id ASC", 14);
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
    		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";

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
    		$this->view->videos = $videos;
    	}
    }

    public function addAction()
    {
    	$this->verifyUser();
		$options = array (
			'owner_id' => $this->auth->getIdentity()->id
		);

    	// get form
		$form = new Petolio_Form_Gallery($options);

		// send form to template
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save gallery
		$this->galleries->setOwnerId($data['owner_id']);
		$this->galleries->setTitle($data['title']);
		$this->galleries->setDescription($data['description']);
		$this->galleries->save(true, true);

		// do html
		$reply = $this->view->url(array('controller'=>'galleries', 'action'=>'view', 'gallery'=>$this->galleries->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has created a new <u>Gallery</u>: %2$s');
		$html = array(
			'%1$s has created a new <u>Gallery</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$data['title']}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('gallery', array($html, $reply, $this->auth->getIdentity()->id, 'new', $this->galleries->getId()));

		// redirect to gallery pictures action
		$this->msg->messages[] = $this->translate->_("Your gallery has been saved successfully.");
		return $this->_redirect('galleries/pictures/gallery/'. $this->galleries->getId());
    }

    public function editAction()
    {
    	$this->verifyUser();

    	// get gallery
		$gallery = $this->loadGallery($this->request->getParam('gallery'));
    	if ($gallery->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You cannot edit this gallery.");
			return $this->_helper->redirector('mygalleries', 'galleries');
    	}

    	// load menu
    	$this->galleryOptions($gallery);

		$options = array (
			'owner_id' => $this->auth->getIdentity()->id
		);

    	// get form
		$form = new Petolio_Form_Gallery($options);
		$form->populate($gallery->toArray());

		// send form to template
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save gallery
		$gallery->setOwnerId($data['owner_id']);
		$gallery->setTitle($data['title']);
		$gallery->setDescription($data['description']);
		$gallery->save(false, true);

		// redirect to gallery files action
		$this->msg->messages[] = $this->translate->_("Your gallery has been saved successfully.");
		return $this->_redirect('galleries/pictures/gallery/' . $gallery->getId());
    }

    /**
     * adding and editing pictures to a gallery
     */
    public function picturesAction()
    {
    	$this->verifyUser();

    	// get and unset uploading messages
    	$this->view->up = $this->up->msg;
    	unset($this->up->msg);

    	// load gallery
    	$gallery = $this->loadGallery($this->request->getParam('gallery'));

    	// load menu
    	$this->galleryOptions($gallery);

    	// needed upfront
    	$ds = DIRECTORY_SEPARATOR;
    	$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";

    	// create a folder for our gallery if not exists
    	$gallery_folder = null;
    	if ($gallery->getFolderId()) {
    		$search_vars = array('id' => $gallery->getFolderId());
    		$gallery_folder = $this->folders->getMapper()->getDbTable()->findFolders($search_vars);
    	}
    	if (!isset($gallery_folder)) {
    		// add the folder
    		$vars = array('name' => $gallery->getId(), 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
    		$gallery_folder = $this->folders->getMapper()->getDbTable()->addFolder($vars);

    		// save the folder in our gallery too
    		$gallery->setFolderId($gallery_folder->getId());
    		$gallery->save();
    	}

    	// load form
    	$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
    	$this->view->form = $form;

    	// get & show all pictures
    	$result = $this->imap->fetchList("type = 'image' AND folder_id = '{$gallery_folder->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
    	$this->view->files = $result;
    	$this->view->gallery = $gallery;

    	// make picture primary
    	$primary = $this->request->getParam('primary');
    	if (isset($primary)) {
    		// get level
    		$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
    		if (!(is_array($result) && count($result) > 0)) {
    			$this->msg->messages[] = $this->translate->_("Picture does not exist.");
    			return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
    		} else
    			$pic = reset($result);

			// get all other pictures
			$result = reset($this->imap->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
		}

    	// get picture remove
    	$remove = $this->request->getParam('remove');
    	if (isset($remove)) {
    		// get level
    		$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
    		if (!(is_array($result) && count($result) > 0)) {
    			$this->msg->messages[] = $this->translate->_("Picture does not exist.");
    			return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
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

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been deleted successfully.");
    		return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
    	}

    	// did we submit form ? if not just return here
    	if(!$this->request->isPost())
    		return false;

    	// create the galleries directory
    	if (!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
    		if (!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
    			$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the galleries folder on disk.")));
    			return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
    		}
    	}

    	// create the gallery's directory
    	if (!file_exists($upload_dir)) {
    		if (!mkdir($upload_dir)) {
    			$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the gallery's folder on disk.")));
    			return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
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
    	$size = $this->config['max_filesize'];
    	$adapter->addValidator('Size', false, $size);

    	// check if files have exceeded the limit
    	if (!$adapter->isValid()) {
    		$msg = $adapter->getMessages();
    		if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
    			$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your picture / pictures exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
    			return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
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
    		list($w, $h) = explode('x', $this->config["thumbnail"]["gallery"]["pic"]);
    		if($props[0] > $w || $props[1] > $h) {
    			Petolio_Service_Image::output($pic, $pic, array(
	    			'type'    => IMAGETYPE_JPEG,
	    			'width'   => $w,
	    			'height'  => $h,
	    			'method'  => THUMBNAIL_METHOD_SCALE_MAX
    			));
    		}

    		// make big thumbnail
    		list($w, $h) = explode('x', $this->config["thumbnail"]["gallery"]["big"]);
    		Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
	    		'type'    => IMAGETYPE_JPEG,
	    		'width'   => $w,
	    		'height'  => $h,
	    		'method'  => THUMBNAIL_METHOD_SCALE_MAX
    		));

    		// make small thumbnail
    		list($w, $h) = explode('x', $this->config["thumbnail"]["gallery"]["small"]);
    		Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'small_' . pathinfo($pic, PATHINFO_BASENAME), array(
	    		'type'    => IMAGETYPE_JPEG,
	    		'width'   => $w,
	    		'height'  => $h,
	    		'method'  => THUMBNAIL_METHOD_SCALE_MIN
    		));

    		// save every file in db
    		$this->imgs = new Petolio_Model_PoFiles();
    		$this->imgs->setOptions(array(
    			'file' => pathinfo($pic, PATHINFO_BASENAME),
    			'type' => 'image',
    			'size' => filesize($pic) / 1024,
    			'folder_id' => $gallery_folder->getId(),
    			'owner_id' => $this->auth->getIdentity()->id,
    			'description' => $original
    		))->save();
    	}

    	// save messages
    	$this->up->msg['errors'] = $errors;
    	$this->up->msg['success'] = $success;

    	// redirect back with message if something was updated
    	if(count($success) > 0) {
			// do html
			$reply = $this->view->url(array('controller'=>'galleries', 'action'=>'view', 'gallery'=>$gallery->getId()), 'default', true);
			$fake = count($success) == 1 ? $this->translate->_('%1$s has added %2$s new picture on his <u>Gallery</u>: %3$s') : $this->translate->_('%1$s has added %2$s new pictures on his <u>Gallery</u>: %3$s');
			$html = array(
				(count($success) == 1 ? '%1$s has added %2$s new picture on his <u>Gallery</u>: %3$s' : '%1$s has added %2$s new pictures on his <u>Gallery</u>: %3$s'),
				"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				count($success),
				"<a href='{$reply}'>{$gallery->getTitle()}</a>"
			);

			// send AMQPC
	    	\Petolio_Service_AMQPC::sendMessage('gallery', array($html, $reply, $this->auth->getIdentity()->id, 'add', $gallery->getId()));

			// save message
    		$this->msg->messages[] = $this->translate->_("Your gallery pictures have been uploaded successfully.");
		}

    	return $this->_helper->redirector('pictures', 'galleries', 'frontend', array('gallery' => $gallery->getId()));
    }

    public function videosAction()
    {
		// verify user
    	$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// load gallery
		$gallery = $this->loadGallery($this->request->getParam('gallery'));

		// load menu
		$this->galleryOptions($gallery);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";

		// create a folder for our gallery if not exists
		$gallery_folder = null;
		if ($gallery->getFolderId()) {
			$search_vars = array('id' => $gallery->getFolderId());
			$gallery_folder = $this->folders->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if (!isset($gallery_folder)) {
			// add the folder
			$vars = array('name' => $gallery->getId(), 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery_folder = $this->folders->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our gallery too
			$gallery->setFolderId($gallery_folder->getId());
			$gallery->save();
		}

		// create the pet videos directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the gallery folder on disk.")));
				return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
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
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($gallery->getDescription(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr($gallery->getTitle(), 0, 30) . ', gallery, petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'galleries', 'action'=>'videos', 'gallery'=>$gallery->getId()), 'default', true);

		// get all videos and refresh cache
		$result = $this->imap->fetchList("type = 'video' AND folder_id = '{$gallery_folder->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;
		$this->view->gallery = $gallery;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->imap->fetchList("file = '{$filename}' AND folder_id = '{$gallery_folder->getId()}'");
			if (is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
			}

			// save video in db
			$this->imgs->setOptions(array(
				'file' => $filename,
				'type' => 'video',
				'size' => 1,
				'folder_id' => $gallery_folder->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original_name
			))->save();

			// msg
			$this->msg->messages[] = $this->translate->_("Your gallery video link has been successfully added.");
			return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
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

			// msg
			$this->msg->messages[] = $this->translate->_("Your gallery video has been deleted successfully.");
			return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
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
					'folder_id' => $gallery_folder->getId(),
					'owner_id' => $this->auth->getIdentity()->id,
					'description' => $original_name
				))->save();

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
		if (!(count($errors) > 0))
			$this->msg->messages[] = $this->translate->_("Your gallery videos have been updated successfully.");

		return $this->_redirect('galleries/videos/gallery/'. $gallery->getId());
    }

    public function youtubeAction()
    {
    	// check if name was set
    	if(!isset($_POST['name']) || empty($_POST['name']))
    		return Petolio_Service_Util::json(array('success' => false));

    	setcookie("petolio_youtube_title", $_POST['name'], time() + 86400, "/");
    	return Petolio_Service_Util::json(array('success' => true));
    }

    /**
     * Archive action
     */
    public function archiveAction()
    {
    	$this->verifyUser();

    	// get gallery
    	$result = $this->galleries->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('gallery'), Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Gallery does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else $gallery = reset($result);

    	// mark as deleted
    	$gallery->setDeleted('1');
    	$gallery->save();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your gallery has been deleted successfully.");
    	return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'galleries');
    }

    /**
     * Get all your videos except for the ones in this gallery
     */
    public function getVideosAction() {
    	$gallery = @$_POST['gallery'];
    	if(!$gallery)
    		die('gtfo noob');

    	// get gallery
    	$gallery = $this->loadGallery($gallery);

    	// get the existing videos
    	$not_in = array();
    	$videos = $this->imap->fetchList("type = 'video' AND folder_id = {$gallery->getFolderId()}");
    	foreach($videos as $video)
    		$not_in[] = "'{$video->getFile()}'";

    	// not in filter
    	$exclude = (count($not_in) > 0 ? "AND file NOT IN (". implode(',', $not_in) .")" : "");

    	// get ideos
    	$output = array();
    	$videos = $this->imap->fetchList("type = 'video' AND owner_id = '{$this->auth->getIdentity()->id}' {$exclude}", "id ASC");
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

    		// iterate over videos for cached entries
    		foreach($videos as $idx => $one) {
    			// get folder
    			$this->folders->find($one->getFolderId());

    			// path is pet
    			if ($this->folders->getPetId() && $this->folders->getPetId() > 0)
    				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$this->folders->getPetId()}{$ds}videos{$ds}";

    			// other (microsite / service)
    			elseif($this->folders->getName() == 'service' || $this->folders->getName() == 'microsite') {
    				// get microsite / service
    				$result = reset($this->db->{$this->folders->getName()}->fetchList("folder_id = '{$this->folders->getId()}'"));
					if(!$result)
						continue;

    				// set upload dir
    				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}{$this->folders->getName()}s{$ds}{$result->getId()}{$ds}";

    				// gallery
    			} else {
    				// get gallery
    				$result = reset($this->db->gallery->fetchList("folder_id = '{$this->folders->getId()}'"));
    				if(!$result)
    					continue;

    				// set upload dir
    				$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$result->getId()}{$ds}";
    			}

    			// get the cached entry
    			$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir);

    			// only when not error
    			if(is_object($entry)) {
	    			// get video thumbnail
	    			$thumbs = $entry->getVideoThumbnails();
	    			$thumbnail = $thumbs[1]['url'];

	    			// get video duration
	    			$duration = date("i:s", $entry->getVideoDuration());

	    			// overwrite videos
	    			$output[] = "<div class='pic'>
						<span class='vid' rel='{$one->getId()}' style=\"background: #000 url('{$thumbnail}') center center no-repeat;\"></span>
						<span class='duration'>{$duration}</span>
						<span class='selection' data-over='1'>" . $this->translate->_("Select") . "</span>
						<span class='selected'>" . $this->translate->_("Selected") . "</span>
					</div>";
    			}
    		}
    	}

    	// output json
    	Petolio_Service_Util::json(array('success' => true, 'videos' => $output));
    }

    /**
     * Save all the linked videos as links
     */
    public function saveVideosAction() {
    	// get gallery
    	$gallery = @$_POST['gallery'];
    	if(!$gallery)
    		die('gtfo noob');

    	// get videos
    	$videos = @$_POST['videos'];
    	if(!$videos)
    		die('gtfo noob');

    	// get gallery
    	$gallery = $this->loadGallery($gallery);

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
    	$upload_dir = "..{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";

    	// go through each video
    	foreach($videos as $video) {
    		// find each video
    		$file = reset($this->imap->fetchList("type = 'video' AND owner_id = '{$this->auth->getIdentity()->id}' AND id = {$video}"));

    		// set the cache, not object? probably an error
    		$entry = $youtube->setVideoEntryCache(pathinfo($file->getFile(), PATHINFO_FILENAME), $upload_dir, false);
    		if(!is_object($entry))
    			continue;

    		// make a copy of each video as linked in our current folder
    		$imgs = new Petolio_Model_PoFiles();
			$imgs->setOptions(array(
				'file' => $file->getFile(),
				'type' => 'video',
				'size' => 1,
				'folder_id' => $gallery->getFolderId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $file->getDescription()
			))->save();
    	}

    	// msg
    	$this->msg->messages[] = $this->translate->_("Your gallery video(s) have been successfully linked.");

    	// terminate with success
    	Petolio_Service_Util::json(array('success' => true));
    }
}