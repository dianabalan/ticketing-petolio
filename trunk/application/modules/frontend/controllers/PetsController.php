<?php

/**
 * Pets page controller ( /pets )
 * All commands related to displaying the pets section and their associated
 * commands
 */
class PetsController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $up = null;
    private $yt_name = null;
    private $auth = null;
    private $config = null;
    private $request = null;

    private $pets = null;
    private $pmap = null;
    private $imgs = null;
    private $imap = null;
    private $favs = null;

    private $interest = null;
    private $interestMap = null;
    private $user = null;
    private $userMap = null;

    private $attr = null;
    private $sets = null;
	private $options = null;
    private $folders = null;
    private $files = null;
    private $flag = null;
    private $unlisted = null;

	private $keyword = false;

    public function init()
    {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();

		$this->pets = new Petolio_Model_PoPets();
		$this->pmap = new Petolio_Model_PoPetsMapper();
		$this->imgs = new Petolio_Model_PoFiles();
		$this->imap = new Petolio_Model_PoFilesMapper();
		$this->favs = new Petolio_Model_PoFavorites();
		$this->interestMap = new Petolio_Model_PoInterestMapper();
		$this->user = new Petolio_Model_PoUsers();
		$this->userMap = new Petolio_Model_PoUsersMapper();

		$this->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->sets = new Petolio_Model_DbTable_PoAttributeSets();
		$this->options = new Petolio_Model_PoAttributeOptions();
		$this->folders = new Petolio_Model_DbTable_PoFolders();
		$this->files = new Petolio_Model_DbTable_PoFiles();
		$this->flag = new Petolio_Model_PoFlags();

		$this->view->auth = $this->auth;
		$this->view->showAdoptionInterest = $this->showAdoptionInterest();
		$this->view->action = $this->request->getParam('action');
		$this->view->request = $this->request;

		// set unlisted params
        $this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));
    }

    public function preDispatch()
    {
		// load countries for searchbox
		$this->view->country_list = array();
		$countriesMap = new Petolio_Model_PoCountriesMapper();
		foreach($countriesMap->fetchAll() as $country)
			$this->view->country_list[$country->getId()] = $country->getName();
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

    /*
	 * Build pet search filter
	 */
	private function buildSearchFilter($filter = array()) {
		$search = array();

		if(strlen($this->request->getParam('keyword'))) {
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." " .
						"OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." " .
						"OR d2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." " .
						"OR f2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." " .
						"OR b.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%").")";
			$search[] = $this->request->getParam('keyword');

			// set keyword search
			$this->keyword = true;
		}

		if(strlen($this->request->getParam('country'))) {
			$filter[] = "x.country_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('country'), Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->country_list[$this->request->getParam('country')];
		}

		if(strlen($this->request->getParam('zipcode'))) {
			$filter[] = "x.zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('zipcode')."%");
			$search[] = $this->request->getParam('zipcode');
		}

		if(strlen($this->request->getParam('address'))) {
			$filter[] = "x.address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('address')."%");
			$search[] = $this->request->getParam('address');
		}

		if(strlen($this->request->getParam('location'))) {
			$filter[] = "x.location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('location'))."%");
			$search[] = $this->request->getParam('location');
		}

		if(strlen($this->request->getParam('owner'))) {
			$filter[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('owner'))."%");
			$search[] = $this->request->getParam('owner');
		}

		if(count($search) > 0)
			$this->view->filter = implode(', ', $search);

		return implode(' AND ', $filter);
	}

    /**
     * Render pet options (the left side menu)
     */
    private function petOptions($pet, $attr)
    {
		$this->view->pet = $pet;
		$this->view->pet_attr = $attr;
		$this->view->render('pets/pet-options.phtml');
    }

    /**
     * Logged in redirector
     * denies access to certain pages when the user is not logged in
     */
    private function verifyUser()
    {
		// not logged in
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}
    }

    /**
     * View the pets main page
     */
    public function indexAction()
    {
    	// start
		$this->view->search = true;

		// see if list or grid
		$this->view->list = $this->request->getParam('list') ? 'list' : 'grid';

		// filter by species
		$species = $this->request->getParam('species');
		$species = empty($species) ? null : $species;

		// filter by species ?
		$this->view->types = array();
		foreach($this->sets->getAttributeSets('po_pets') as $type)
			$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);
		asort($this->view->types);

		// build filter
		$filter = array("a.deleted = 0 AND x.active = 1 AND x.is_banned != 1");
		if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
		$filter = $this->buildSearchFilter($filter);

		// search by ?
		if($this->view->filter) {
			$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
			if(isset($species))
				$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
		} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
		else $this->view->title = $this->translate->_("All Pets");

		// get page
		$page = $this->request->getParam('all-page');
		$page = $page ? intval($page) : 0;

        // do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		//$_SESSION["pets_seed"] = (isset($_SESSION["pets_seed"])/*  && $page > 0 */) ? $_SESSION["pets_seed"] : time();
		
		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'type') $sort = "type {$this->view->dir}";
		elseif($this->view->order == 'breed') $sort = "breed {$this->view->dir}";
		elseif($this->view->order == 'owner') $sort = "x.name {$this->view->dir}";
		elseif($this->view->order == 'country') $sort = "x.country_id {$this->view->dir}";
		elseif($this->view->order == 'adopt') $sort = "a.to_adopt {$this->view->dir}";
		else $sort = "RAND(".date("Ymd").")";

		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Pets_".$filter."_".$sort."_".$this->keyword."_".$page);
		
		if (false === ($pets = $cache->load($cacheID))) {
			// get pets
			$paginator = $this->pets->getPets('paginator', $filter, $sort, false, $this->keyword);
			$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
			$paginator->setCurrentPageNumber($page);

			$pets = $this->pets->formatPets($paginator);
			$cache->save($pets, $cacheID);
		}
		
		// output pets
		$this->view->pets = $pets;


    }

    /**
     * Displays all my pets
     */
    public function mypetsAction()
    {
		// redirect if not logged in
		$this->verifyUser();

    	// start
		$this->view->search = true;

		// see if list or grid
		$this->view->list = $this->request->getParam('list') ? 'list' : 'grid';

		// filter by species
		$species = $this->request->getParam('species');
		$species = empty($species) ? null : $species;

		// filter by species ?
		$this->view->types = array();
		foreach($this->sets->getAttributeSets('po_pets') as $type)
			$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);
		asort($this->view->types);

		// build filter
		$filter = array(
			"a.deleted = 0",
			"a.user_id = {$this->auth->getIdentity()->id}"
		);
		if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
		$filter = $this->buildSearchFilter($filter);

		// search by ?
		if($this->view->filter) {
			$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
			if(isset($species))
				$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
		} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
		else $this->view->title = $this->translate->_("My Pets");

		// get page
		$page = $this->request->getParam('your-page');
		$page = $page ? intval($page) : 0;

        // do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'type') $sort = "type {$this->view->dir}";
		elseif($this->view->order == 'breed') $sort = "breed {$this->view->dir}";
		elseif($this->view->order == 'owner') $sort = "x.name {$this->view->dir}";
		elseif($this->view->order == 'country') $sort = "x.country_id {$this->view->dir}";
		elseif($this->view->order == 'adopt') $sort = "a.to_adopt {$this->view->dir}";
		else $sort = "id {$this->view->dir}";

		// get pets
		$paginator = $this->pets->getPets('paginator', $filter, $sort, false, $this->keyword);
		$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output your pets
		$this->view->yours = $this->pets->formatPets($paginator);
    }

    /**
     * View details about a pet
     */
    public function viewAction($mobile = false)
    {
		// load pet
		$pet = $this->loadPet($this->request->getParam('pet'));

		// if flagged, load reasons
		$this->view->flagged = array();
		if($pet->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->flag->getMapper()->fetchList("scope = 'po_pets' AND entry_id = '{$pet->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// load species
		$this->view->species = array();
		foreach($this->sets->getAttributeSets('po_pets') as $type)
			$this->view->species[$type['id']] = Petolio_Service_Util::Tr($type['name']);

		// send to template
		$this->view->pet = $pet;

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get pet attributes
		$this->view->attributes = reset($this->attr->loadAttributeValues($pet, true));

		// get pet pictures
		$this->populateViewWithPetPictures($pet->getId());

		// get pet audios
		if($mobile == false)
			$this->populateViewWithPetAudios($pet->getId());

		// get pet videos
		$media = $this->folders->findFolders(array('name' => 'videos', 'petId' => $pet->getId()));
		if(isset($media)) $videos = $this->imap->fetchList("folder_id = '{$media->getId()}'", "id ASC", 14);
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
			$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}videos{$ds}";

			// iterate over videos for cached entries
			foreach($videos as $idx => $one) {
				// get the cached entry
				$entry = $youtube->getVideoEntryCache(pathinfo($one->file, PATHINFO_FILENAME), $upload_dir);

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

		// if pet is yours tell me :)
		if($mobile == false && isset($this->auth->getIdentity()->id))
			$this->view->admin = $pet->getUserId() == $this->auth->getIdentity()->id ? true : false;

		if($mobile == false && $this->view->admin) {
			// load types, colors, countries and is service
    		$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
			$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
			$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
			$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
			$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
			$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
			$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
			$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

			// load pet events, appointments
			list($this->view->pet_apps, $this->view->pet_apps_json) = $this->loadPetAppointments($pet, $this->request->getParam('your-page'));
		}

		// load menu
		$this->petOptions($this->view->pet, $this->view->attributes);

    	// find owner
    	$this->user->find($this->view->pet->getUserId());
    	$this->view->owner = $this->user;

		// see if the pet owner is active and not banned
		if(!($this->user->getActive() == 1 && $this->user->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		// load pet's emergency contacts
	    $db = new Petolio_Model_DbTable_PoAttributeSets();
        $select = $db->select()
        	->where("scope = 'po_services'")
        	->where("active = 1");

        $service_types = array();
        foreach($db->fetchAll($select) as $line)
    		$service_types[$line['id']] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name']));
		$this->view->service_types = $service_types;

		$ds = new Petolio_Model_PoEmergency();
		$this->view->pet_emergency_contacts = $ds->fetchList("scope = 'po_pets' AND entity_id = '{$pet->getId()}'", "id ASC");

		// load pet's partner services
		if($mobile == false)
			$this->view->pet_members_services = $this->loadPetServices($pet);
    }

	/**
	 * View details about a pet for Mobile
	 */
	public function viewMobileAction() {
		// disable layout for print
		$this->_helper->layout->disableLayout();

		// get pet id
		$id = $this->request->getParam('pet');

		// check for pet existance
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->view->error_pet = true;
			return;
		}

		// same old function here
		$this->viewAction(true);
	}

    /**
     * loads pet's upcoming appointments
     *
     * @param Petolio_Model_PoPets $pet
     * @param int $page
     */
    private function loadPetAppointments($pet, $page = 0)
    {
		// format event in calendar template
		$in = array();
		$cal = new Petolio_Model_PoCalendar();
		foreach($cal->getMapper()->browsePetEvents($pet->getId(), $this->auth->getIdentity()->id) as $line) {
			$array = Petolio_Service_Calendar::format($line);
			$array['astatus'] = $line['astatus'];
			$array['atype'] = $line['atype'];
			if($this->auth->hasIdentity() && $line['auser_id'] == $this->auth->getIdentity()->id) {
				$array['invited'] = $line['atype'] === '0' && $line['astatus'] === '0' ? true : false;
				$array['accepted'] = $line['atype'] === '0' && $line['astatus'] === '1' ? true : false;
			}

			$in[] = $array;
		}

		// master repeats
		$results = Petolio_Service_Calendar::masterRepeats($in);

		// filter out events that have expired (remember to look out for all day events as well as continuous events)
		$now = new DateTime('now');
		$fivedays = clone $now;
		$fivedays->add(new DateInterval('P7D'));
		foreach($results as $idx => $line) {
			$start = new DateTime(date('Y-m-d H:i:s', $line['start']));
			$end = $line['end'] ? new DateTime(date('Y-m-d H:i:s', $line['end'])) : null;

			if($line['allDay'])
				$now->setTime(0, 0, 0);

			// if start is bigger than 7 days, unset
			if($start > $fivedays)
				unset($results[$idx]);

			// unset if the event passed but check if the event is still running
			if($start < $now) {
				if($end) {
					if($end < $now)
						unset($results[$idx]);
				} else
					unset($results[$idx]);
			}

			// earlier we set the time to 00:00, and we reset it for the next event
			if($line['allDay'])
				$now = new DateTime('now');
		}

	    // do sorting
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'desc' ? 'desc' : 'asc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// add sorting value
		foreach($results as $idx => $line) {
			if($this->view->order == 'name') $sort = $line['title'];
			elseif($this->view->order == 'type') $sort = $line['type'];
			elseif($this->view->order == 'owner') $sort = $line['user_name'];
			else {
				$this->view->order = 'date';
				$sort = $line['start'];
			}

			$results[$idx] = array_merge($line, array('sort' => $sort));
		}

		// perform sort
		Petolio_Service_Util::array_sort($results, array("sort" => $this->view->dir == 'asc' ? true : false));

		// pagination
		$result = Zend_Paginator::factory($results);
		$result->setItemCountPerPage($this->config["events"]["pagination"]["itemsperpage"]);
		$result->setCurrentPageNumber($page);

		// prep for json encode
		$out = array();
		foreach($result as $line)
			$out[] = $line;

		// return json and object
		return array($result, json_encode($out));
    }

    /**
     * loads the pet's partner services
     *
     * @param Petolio_Model_PoPets $pet
     * @return array of Petolio_Model_PoServiceMembersPets with _MemberService obj
     * Petolio_Model_PoServices with _Owner obj and _Name set
     */
    private function loadPetServices($pet)
    {
    	// do sorting 1
		$this->view->service_order = $this->request->getParam('service_order');
		$this->view->service_dir = $this->request->getParam('service_dir') == 'desc' ? 'desc' : 'asc';
		$this->view->service_rdir = $this->view->service_dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		switch ($this->view->service_order) {
			case "service_name":
				$sort = "d.value {$this->view->service_dir}";
				break;
			case "service_owner":
				$sort = "u.name {$this->view->service_dir}";
				break;
			case "service_type":
				$sort = "as.name {$this->view->service_dir}";
				break;
			case "service_status":
				$sort = "a.status {$this->view->service_dir}";
				break;
			case "service_address":
				if($this->translate->getLocale() == 'en')
					$sort = "service_address {$this->view->service_dir}, service_location {$this->view->service_dir}, service_zipcode {$this->view->service_dir}, service_country {$this->view->service_dir}";
				else
					$sort = "service_zipcode {$this->view->service_dir}, service_address {$this->view->service_dir}, service_location {$this->view->service_dir}, service_country {$this->view->service_dir}";
				break;

			default:
				$this->view->service_order = 'service_name';
				$sort = "d.value {$this->view->service_dir}";
				break;
		}

		$members_pets = new Petolio_Model_PoServiceMembersPets();
    	return $members_pets->getPetServicesWithReferences($pet->getId(), null, $sort);
    }

    /**
     * Populate a view with the pet pictures.
     * @param int $petId
     */
    private function populateViewWithPetPictures($petId)
    {
		$gallery = $this->folders->findFolders(array('name' => 'gallery', 'petId' => $petId));
		$pictures = new Petolio_Model_PoFiles();

		if(isset($gallery)) {
			$galleries = $pictures->fetchListToArray("folder_id = '{$gallery->getId()}'", "date_created ASC");

			$gallery = array();
			foreach($galleries as $row)
				$gallery[$row["id"]] = $row["file"];

			$this->view->gallery = $gallery;
		}
    }

    /**
     * Populate a view with the pet audios.
     * @param int $petId
     */
    private function populateViewWithPetAudios($petId)
    {
    	$audios = $this->folders->findFolders(array('name' => 'audios', 'petId' => $petId));
    	$files = new Petolio_Model_PoFiles();
    	
    	if(isset($audios)) {
			$this->view->audios = $files->fetchListToArray("folder_id = '{$audios->getId()}'", "id ASC");
    	}
    }

    /**
     * Load a pet by id.
     * @param int $petId
     */
    private function loadPet($petId)
    {
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($petId, Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else
			return reset($result);
    }

    /**
     * Adds a new pet in the database
     */
    public function addAction()
    {
    	$this->verifyUser();

		if(!is_null($this->request->getParam('species')))
			$this->step2();
		else
			$this->step1();
    }

    /**
     * Edits an existing pet
     */
    public function editAction()
    {
    	$this->verifyUser();

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else
			$pet = reset($result);

		// find most used keyword (cat, bird, horse) _ (breed, genus)
    	$matches = array();
		foreach($_POST as $idx => $one) {
			$split = explode('_', $idx);
			if(!isset($matches[$split[0]]))	$matches[$split[0]] = 1;
			else $matches[$split[0]]++;
		}
		$type = end(array_flip($matches));
		$keyword = $type . '_' . ($type == 'bird' ? 'genus' : 'breed');

    	// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST[$keyword])) {
			$saved = $_POST[$keyword];
			$output = '';
			foreach($_POST[$keyword] as $one) {
				$this->options->find($one);
				$value = Petolio_Service_Util::Tr($this->options->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST[$keyword] = substr($output, 0, -1);
		}

		// load pet attributes
		$populate = array();
		$attributes = reset($this->attr->loadAttributeValues($pet));
		foreach($attributes as $attr) {
			$type = $attr->getAttributeInputType();
			if($type->getName() == 'text' && $type->getType() == 'select') { // ajax
				$val = '';
				foreach($attr->getAttributeEntity() as $one) {
					$this->options->find($one->getValue());
					$val .= $one->getValue() . "|" . Petolio_Service_Util::Tr($this->options->getValue()) . ',';
				}
				$val = substr($val, 0, -1);
			} else
				$val = $attr->getAttributeEntity()->getValue();

			$populate[$attr->getCode()] = array("value" => $val, "type" => $attr->getAttributeInputType()->getType());
		}

		// load menu
		$this->petOptions($pet, $attributes);

		// init form
		$form = new Petolio_Form_Pet($pet->getAttributeSetId());
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
		return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
		return false;

		// get data
		$data = $form->getValues();

		// everything fine, so put back the keyword
		$data[$keyword] = $saved;

		// save pet
		$pet->setDateModified(date('Y-m-d H:i:s', time()));
		$pet->setToAdopt(0);

		// if we have a _sale attribute and it's set to "Yes" then set the to_adopt flag to 1
		foreach($data as $code => $value)
			if(substr($code, strrpos($code, '_sale'), 5) == '_sale' && isset($value) && $value == 1)
				$pet->setToAdopt(1);

		// save pet
		$pet->save(true, true);

		// save attributes
		$this->attr->saveAttributeValues($data, $pet->getId());

		// redirect
		$this->msg->messages[] = $this->translate->_("Your pet has been edited successfully.");
		return $this->_redirect('pets/pictures/pet/'. $pet->getId());
    }

    /**
     * Archive action
     */
    public function archiveAction()
    {
    	$this->verifyUser();

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $pet = reset($result);

		// mark as deleted
		$pet->setDeleted('1')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your pet has been archived successfully.");
		return $this->_helper->redirector('mypets', 'pets');
    }

    /**
     * Restore action
     */
    public function restoreAction()
    {
    	$this->verifyUser();

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '1'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $pet = reset($result);

		// mark as deleted
		$pet->setDeleted('0')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your pet has been restored successfully.");
		return $this->_helper->redirector('mypets', 'pets');
    }

    /**
     * Pet creation step 1 - Select species
     */
    private function step1()
    {
		// init form
		$form = new Petolio_Form_Pet();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
		return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
		return false;

		// redirect
		$data = $form->getValues();
		return $this->_redirect('pets/add/species/'. $data['attribute_set']);
    }

    /**
     * Pet creation step 2 - Add pet information
     */
    private function step2()
    {
		// find most used keyword (cat, bird, horse) _ (breed, genus)
    	$matches = array();
		foreach($_POST as $idx => $one) {
			$split = explode('_', $idx);
			if(!isset($matches[$split[0]]))	$matches[$split[0]] = 1;
			else $matches[$split[0]]++;
		}
		$type = end(array_flip($matches));
		$keyword = $type . '_' . ($type == 'bird' ? 'genus' : 'breed');

    	// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST[$keyword])) {
			$saved = $_POST[$keyword];
			$output = '';
			foreach($_POST[$keyword] as $one) {
				$this->options->find($one);
				$value = Petolio_Service_Util::Tr($this->options->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST[$keyword] = substr($output, 0, -1);
		}

		// init form
		$form = new Petolio_Form_Pet($this->request->getParam('species'));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// everything fine, so put back the keyword
		$data[$keyword] = $saved;

		// save pet
		$pet_options = array(
			'user_id' => $this->auth->getIdentity()->id,
			'attribute_set_id' => $this->request->getParam('species')
		);

		// if we have a _sale attribute and it's set to "Yes" then set the to_adopt flag to 1
		foreach($data as $code => $value)
			if(substr($code, strrpos($code, '_sale'), 5) == '_sale' && isset($value) && $value == 1)
				$pet_options['to_adopt'] = '1';

		// save pet
		$this->pets->setOptions($pet_options)->save(true, true);

		// save attributes
		$this->attr->saveAttributeValues($data, $this->pets->getId());

		// do html
		$pet_name = Petolio_Service_Parse::do_limit(ucfirst($data[$type . '_name']), 20, false, true);
		$reply = $this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet'=>$this->pets->getId()), 'default', true);
		$fake = $this->translate->_('%1$s added a new <u>Pet</u>: %2$s');
		$html = array(
			'%1$s added a new <u>Pet</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$pet_name}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('pet', array($html, $reply, $this->auth->getIdentity()->id));

		// redirect to pictures
		$this->msg->messages[] = $this->translate->_("Your pet has been added successfully.");
		return $this->_redirect('pets/pictures/pet/'. $this->pets->getId());
    }

    /**
     * Pet creation step 3 - Add pictures page (can be accessed independently)
     */
    public function picturesAction()
    {
		// verify user
    	$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $pet = reset($result);

		// load menu
		$this->petOptions($pet, reset($this->attr->loadAttributeValues($pet, true)));

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}gallery{$ds}";

		// find pet's root folder, doesn't exist ? create it
		$vars = array('name' => 'root', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
		$root = $this->folders->findFolders($vars);
		if(is_null($root))
			$root = $this->folders->addFolder($vars);

		// create a gallery folder for our pet
		$vars = array('name' => 'gallery', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $root->getId());
		$gallery = $this->folders->findFolders($vars);
		if(is_null($gallery))
			$gallery = $this->folders->addFolder($vars);

		// load the form
		$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
		$this->view->form = $form;

		// get & show all pictures
		$result = $this->imap->fetchList("folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
		$this->view->gallery = $result;
		$this->view->pet = $pet;

    	// make picture primary
    	$primary = $this->request->getParam('primary');
    	if (isset($primary)) {
			// get level
			$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_helper->redirector('index', 'site');
			} else $pic = reset($result);

			// get all other pictures
			$result = reset($this->imap->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_redirect('pets/pictures/pet/'. $pet->getId());
		}

		// get picture remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_helper->redirector('index', 'site');
			} else $pic = reset($result);

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
			$fake = array($this->translate->_("pet")); unset($fake);
			Petolio_Service_Autopost::factory('image', $pic->getFolderId(),
				'pet',
				$pet->getId(),
				$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
				$this->view->pet_attr['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your pet picture has been deleted successfully.");
			return $this->_redirect('pets/pictures/pet/'. $pet->getId());
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the pet id directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's folder on disk.")));
				return $this->_redirect('pets/pictures/pet/'. $pet->getId());
			}
		}

		// create the pet gallery directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's gallery folder on disk.")));
				return $this->_redirect('pets/pictures/pet/'. $pet->getId());
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
	    if(!$adapter->isValid()) {
	    	$msg = $adapter->getMessages();
	    	if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
	    		$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your picture / pictures exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
	    		return $this->_redirect('pets/pictures/pet/'. $pet->getId());
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
			list($w, $h) = explode('x', $this->config["thumbnail"]["pet"]["pic"]);
			if($props[0] > $w || $props[1] > $h) {
				Petolio_Service_Image::output($pic, $pic, array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MAX
				));
			}

			// make big thumbnail
			list($w, $h) = explode('x', $this->config["thumbnail"]["pet"]["big"]);
			Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN
			));

			// make small thumbnail
			list($w, $h) = explode('x', $this->config["thumbnail"]["pet"]["small"]);
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
			$file->setOptions($opt)->save();

			// post on dashboard
			$fake = array($this->translate->_("pet")); unset($fake);
			Petolio_Service_Autopost::factory('image', $file,
				'pet',
				$pet->getId(),
				$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
				$this->view->pet_attr['name']->getAttributeEntity()->getValue()
			);
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your pet pictures have been updated successfully.");
		return $this->_redirect('pets/pictures/pet/'. $pet->getId());
    }

    /**
     * Pet creation step 3.2 - Add sounds page (can be accessed independently)
     */
    public function audiosAction()
    {
    	// verify user
    	$this->verifyUser();

    	// get and unset uploading messages
    	$this->view->up = $this->up->msg;
    	unset($this->up->msg);

    	// get pet
    	$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
    	if(!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else $pet = reset($result);

    	// load menu
    	$this->petOptions($pet, reset($this->attr->loadAttributeValues($pet, true)));

    	// needed upfront
    	$ds = DIRECTORY_SEPARATOR;
    	$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}audios{$ds}";

    	// find pet's root folder, doesn't exist ? create it
    	$vars = array('name' => 'root', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
    	$root = $this->folders->findFolders($vars);
    	if(is_null($root))
    		$root = $this->folders->addFolder($vars);

    	// create a gallery folder for our pet
    	$vars = array('name' => 'audios', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $root->getId());
    	$gallery = $this->folders->findFolders($vars);
    	if(is_null($gallery))
    		$gallery = $this->folders->addFolder($vars);

    	// load the form
    	$form = new Petolio_Form_Upload($this->translate->_('Audio'), $this->translate->_('Upload Audios'));
    	$this->view->form = $form;

    	// get & show all audios
    	$result = $this->imap->fetchList("type = 'audio' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
    	$this->view->sounds = $result;
    	$this->view->pet = $pet;

    	// get audio remove
    	$remove = $this->request->getParam('remove');
    	if(isset($remove)) {
    		// get level
    		$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
    		if(!(is_array($result) && count($result) > 0)) {
    			$this->msg->messages[] = $this->translate->_("Audio does not exist.");
    			return $this->_helper->redirector('index', 'site');
    		} else $aud = reset($result);

    		// delete from hdd
    		@unlink($upload_dir . $aud->getFile());

			// delete all comments, likes and subscriptions
			$comments = new Petolio_Model_PoComments();
			$ratings = new Petolio_Model_PoRatings();
			$subscriptions = new Petolio_Model_PoSubscriptions();
			$comments->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$aud->getId()}'");
			$ratings->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$aud->getId()}'");
			$subscriptions->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$aud->getId()}'");

    		// delete file from db
    		$aud->deleteRowByPrimaryKey();

    		// update dashboard
    		$fake = array($this->translate->_("pet")); unset($fake);
    		Petolio_Service_Autopost::factory('audio', $aud->getFolderId(),
	    		'pet',
	    		$pet->getId(),
	    		$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
	    		$this->view->pet_attr['name']->getAttributeEntity()->getValue()
    		);

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your pet audio has been deleted successfully.");
    		return $this->_redirect('pets/audios/pet/'. $pet->getId());
    	}

    	// did we submit form ? if not just return here
    	if(!$this->request->isPost())
    		return false;

    	// create the pet id directory
    	if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
    		if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
    			$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's folder on disk.")));
    			return $this->_redirect('pets/audios/pet/'. $pet->getId());
    		}
    	}

    	// create the pet gallery directory
    	if(!file_exists($upload_dir)) {
    		if(!mkdir($upload_dir)) {
    			$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's audios folder on disk.")));
    			return $this->_redirect('pets/audios/pet/'. $pet->getId());
    		}
    	}

    	// prepare upload files
    	$i = 0;
    	$errors = array();
    	$success = array();

    	// get addapter
    	$adapter = new Zend_File_Transfer_Adapter_Http();
    	$adapter->setDestination($upload_dir);
    	$adapter->addValidator('Extension', false, 'mp3,m4a,oga,wav');

    	// getting the max filesize
    	$config = Zend_Registry::get('config');
    	$size = $config['max_filesize'];
    	$adapter->addValidator('Size', false, $size);

    	// check if files have exceeded the limit
    	if(!$adapter->isValid()) {
    		$msg = $adapter->getMessages();
    		if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
    			$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your audio / audios exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
    			return $this->_redirect('pets/audios/pet/'. $pet->getId());
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

    	// go through each audio
    	foreach($success as $original => $aud) {
    		// save every file in db
    		$opt = array(
    			'file' => pathinfo($aud, PATHINFO_BASENAME),
    			'type' => 'audio',
    			'size' => filesize($aud) / 1024,
    			'folder_id' => $gallery->getId(),
    			'owner_id' => $this->auth->getIdentity()->id,
   				'description' => $original
    		);

    		$file = new Petolio_Model_PoFiles();
    		$file->setOptions($opt)->save();

    		// post on dashboard
    		$fake = array($this->translate->_("pet")); unset($fake);
    		Petolio_Service_Autopost::factory('audio', $file,
	    		'pet',
	    		$pet->getId(),
	    		$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
	    		$this->view->pet_attr['name']->getAttributeEntity()->getValue()
    		);
    	}

    	// save messages
    	$this->up->msg['errors'] = $errors;
    	$this->up->msg['success'] = $success;

    	// redirect back with message if something was updated
    	if(count($success) > 0)
    		$this->msg->messages[] = $this->translate->_("Your pet audios have been updated successfully.");
    	return $this->_redirect('pets/audios/pet/'. $pet->getId());
    }

    /**
     * Pet creation step 3.5 - Add videos page (can be accessed independently)
     */
    public function videosAction()
    {
		// verify user
    	$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $pet = reset($result);

		// load menu
		$attrs = reset($this->attr->loadAttributeValues($pet, true));
		$this->petOptions($pet, $attrs);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}videos{$ds}";

		// find pet's root folder, doesn't exist ? create it
		$vars = array('name' => 'root', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
		$root = $this->folders->findFolders($vars);
		if(is_null($root))
			$root = $this->folders->addFolder($vars);

		// create a videos folder for our pet
		$vars = array('name' => 'videos', 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $root->getId());
		$videos = $this->folders->findFolders($vars);
		if(is_null($videos))
			$videos = $this->folders->addFolder($vars);

		// create the pet id directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's folder on disk.")));
				return $this->_redirect('pets/videos/pet/'. $pet->getId());
			}
		}

		// create the pet videos directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the pet's video folder on disk.")));
				return $this->_redirect('pets/videos/pet/'. $pet->getId());
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
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($attrs['description']->getAttributeEntity()->getValue(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr($attrs['name']->getAttributeEntity()->getValue(), 0, 30) . ', pet, petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'pets', 'action'=>'videos', 'pet'=>$pet->getId()), 'default', true);

		// get all videos and refresh cache
		$result = $this->imap->fetchList("type = 'video' AND folder_id = '{$videos->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;
		$this->view->pet = $pet;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('pets/videos/pet/'. $pet->getId());
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->imap->fetchList("file = '{$filename}' AND folder_id = '{$videos->getId()}'");
			if(is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('pets/videos/pet/'. $pet->getId());
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('pets/videos/pet/'. $pet->getId());
			}

			// save video in db
			$this->imgs->setOptions(array(
				'file' => $filename,
				'type' => 'video',
				'size' => 1,
				'folder_id' => $videos->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original_name
			))->save();

			// post on dashboard
			$fake = array($this->translate->_("pet")); unset($fake);
			Petolio_Service_Autopost::factory('video', $this->imgs,
				'pet',
				$pet->getId(),
				$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
				$this->view->pet_attr['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your pet video link has been successfully added.");
			return $this->_redirect('pets/videos/pet/'. $pet->getId());
		}

		// get video remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->imap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
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
					if($entry->getVideoId() == pathinfo($vid->getFile(), PATHINFO_FILENAME)) {
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
			$fake = array($this->translate->_("pet")); unset($fake);
			Petolio_Service_Autopost::factory('video', $vid->getFolderId(),
				'pet',
				$pet->getId(),
				$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
				$this->view->pet_attr['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your pet video has been deleted successfully.");
			return $this->_redirect('pets/videos/pet/'. $pet->getId());
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
					'folder_id' => $videos->getId(),
					'owner_id' => $this->auth->getIdentity()->id,
					'description' => $original_name
				))->save();

				// post on dashboard
				$fake = array($this->translate->_("pet")); unset($fake);
				Petolio_Service_Autopost::factory('video', $this->imgs,
					'pet',
					$pet->getId(),
					$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $pet->getId()), 'default', true),
					$this->view->pet_attr['name']->getAttributeEntity()->getValue()
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
		if(!(count($errors) > 0))
			$this->msg->messages[] = $this->translate->_("Your pet videos have been updated successfully.");

		return $this->_redirect('pets/videos/pet/'. $pet->getId());
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

    /**
     * Pet creation step 4 - Pet Pedigree (can be accessed independently)
     */
    public function pedigreeAction()
    {
		// load file management
		$pedigree = new Pedigree_Management();
		$pedigree->msg = $this->msg;
		$pedigree->view = $this->view;
		$pedigree->auth = $this->auth;
		$pedigree->helper = $this->_helper;
		$pedigree->request = $this->request;
		$pedigree->translate = $this->translate;
		$pedigree->start();

		// load pet options
		if($pedigree->pet)
			$this->petOptions($pedigree->pet, $pedigree->pet_attrs);
    }

    /**
     * Pet creation/editing final step
     */
    public function finishAction()
    {
		$this->msg->messages[] = $this->translate->_("Your pets details have been updated successfully.");
		return $this->_helper->redirector('mypets', 'pets');
    }

    /**
     * Load friends and partners
     * @param int $id - User Id
     * @param string $what - friends / partners
     *
     * @return array of friends or partners
     */
    private function loadFriendsAndPartners($id, $what = 'friends')
    {
		// load user's friends and partners
		$this->user->find($id, $this->user);
		$all = $what == 'friends' ? $this->user->getUserFriends() : $this->user->getUserPartners();

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		// return
		return $result;
    }

    /**
     * Files Action
     */
    public function filesAction()
    {
		// load file management
		$files = new File_Management();
		$files->up = $this->up;
		$files->msg = $this->msg;
		$files->view = $this->view;
		$files->auth = $this->auth;
		$files->config = $this->config;
		$files->helper = $this->_helper;
		$files->request = $this->request;
		$files->translate = $this->translate;
		$files->start();

		// load pet options
		if($files->pet)
			$this->petOptions($files->pet, reset($this->attr->loadAttributeValues($files->pet, true)));
    }

    /**
     * Find user
     * used for file access field "users" (not just)
     *
     * @returns json of users found
     */
    public function findUserAction()
    {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
		return false;

		// no user param ?
		$user = strtolower($this->request->getParam('user'));
		if(!isset($user)) die('bye');

		// search user
		$users = array();
		foreach($this->userMap->fetchList("name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$user."%")." AND id <> '{$this->auth->getIdentity()->id}'") as $all)
			$users[] = array('value' => $all->getId(), 'text' => $all->getName());

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $users));
    }

    /**
     * Find pet
     * used for file access field "users" (not just)
     *
     * @returns json of pets found
     */
    public function findPetAction() {
    	
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
		return false;

		// no users param ?
		Zend_Db_Table_Abstract::getDefaultAdapter()->quote("asd");
		$users = strtolower($this->request->getParam('users'));
		if(!isset($users)) die('bye');

		// search pets
		$results = array();
		$pets = new Petolio_Model_PoPets();
		foreach($pets->getPets("array", "a.user_id IN (".addcslashes($users, "\000\n\r\\'\"\032").")") as $all)
			$results[] = array('value' => $all['id'], 'text' => $all['name']);

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $results));
    }

    /**
     * Find pets by criteria
     * This can be easily extended
     *
     * @returns json of pets found
     */
    public function findByCriteriaAction() {

		// get params
		$name = strtolower($this->request->getParam('name'));

		// search pets
		$results = array();
		$pets = new Petolio_Model_PoPets();
		foreach($pets->getPets("array", "LOWER(d1.value) LIKE ('%".strtolower(addcslashes($name, "\000\n\r\\'\"\032"))."%')") as $all)
			$results[] = array('value' => $all['id'], 'text' => $all['name']);

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $results));
    }

    /**
     * Pets archived
     */
    public function archivesAction()
    {
		// not logged in ? who are you ?
		$this->verifyUser();

    	// start
		$this->view->search = true;
		$this->view->yours = true;

		// filter by species
		$species = $this->request->getParam('species');
		$species = empty($species) ? null : $species;

		// filter by species ?
		$this->view->types = array();
		foreach($this->sets->getAttributeSets('po_pets') as $type)
			$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);
		asort($this->view->types);

		// build filter
		$filter = array(
			"a.deleted = 1",
			"a.user_id = {$this->auth->getIdentity()->id}"
		);
		if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
		$filter = $this->buildSearchFilter($filter);

		// search by ?
		if($this->view->filter) {
			$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
			if(isset($species))
				$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
		} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
		else $this->view->title = $this->translate->_("Pet Archives");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get pets
		$paginator = $this->pets->getPets('paginator', $filter, "id DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output archived pets
		$this->view->archived = $this->pets->formatPets($paginator);
    }

    /**
     * Pet transfer process - Step 1 (C)
     * Adds one of your pets to the adoption list
     */
    public function adoptAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// find pet
    	$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
    	if(!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else
    		$pet = reset($result);

    	// set to adopt
    	$pet->setDateModified(date('Y-m-d H:i:s', time()));
    	$pet->setToAdopt(1)->save();

    	// set the _sale attribute to "Yes"
    	$attributes = new Petolio_Model_PoAttributes();
    	$attr_result = reset($attributes->fetchList("attribute_set_id = '{$pet->getAttributeSetId()}' AND code LIKE '%_sale%'"));
    	if($attr_result)
    		$attributes->getMapper()->getDbTable()->saveAttributeValues(array($attr_result->getCode() => 1), $pet->getId());

		// load attributes
		$attributes = reset($this->attr->loadAttributeValues($pet));
		$this->petOptions($pet, $attributes);

		// do html
		$name = Petolio_Service_Parse::do_limit(ucfirst($this->view->pet_attr['name']->getAttributeEntity()->getValue()), 20, false, true);
		$reply = $this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has put up for adoption one of his <u>Pets</u>: %2$s');
		$html = array(
			'%1$s has put up for adoption one of his <u>Pets</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$name}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('adoption', array($html, $reply, $this->auth->getIdentity()->id));

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your pet was successfully put in the adoption list.");
    	return $this->_helper->redirector("view", "pets", "frontend", array('pet' => $pet->getId()));
    }

    /**
     * Favorite action
     */
    public function favoriteAction()
    {
    	$this->verifyUser();

    	// get pet
    	$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
    	if(!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else $pet = reset($result);

    	// already exists?
    	$result = $this->favs->fetchList("scope = 'po_pets' AND user_id = '{$this->auth->getIdentity()->id}' AND entity_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE));
    	if((is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Pet is already on your favorite list.");
    		return $this->_helper->redirector('index', 'site');
    	}

    	// mark as favorite
    	$this->favs->setOptions(array(
    		'user_id' => $this->auth->getIdentity()->id,
    		'entity_id' => $this->request->getParam('pet'),
    		'scope' => 'po_pets'
    	))->save();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Pet was added to your favorites successfully.");
    	return $this->_helper->redirector('favorites', 'pets');
    }

    /**
     * Clear action
     */
    public function clearAction()
    {
    	$this->verifyUser();

    	// get faved
    	$result = $this->favs->fetchList("scope = 'po_pets' AND user_id = '{$this->auth->getIdentity()->id}' AND entity_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE));
    	if(!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Favorite Pet does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else $pet = reset($result);

    	// delete from fav
    	$pet->deleteRowByPrimaryKey();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your favorite pet has been removed successfully.");
    	return $this->_helper->redirector('favorites', 'pets');
    }

    /**
     * Pets archived
     */
    public function favoritesAction()
    {
    	// not logged in ? who are you ?
    	$this->verifyUser();

    	// start
    	$this->view->search = true;

    	// filter by species
    	$species = $this->request->getParam('species');
    	$species = empty($species) ? null : $species;

    	// filter by species ?
    	$this->view->types = array();
    	foreach($this->sets->getAttributeSets('po_pets') as $type)
    		$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);
    	asort($this->view->types);

    	// get favorites
    	$favs = array();
    	$result = $this->favs->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = 'po_pets'");
    	foreach($result as $one)
    		$favs[] = $one->getEntityId();

    	// build filter
    	$filter = array("a.deleted = 0");
    	if(count($favs) > 0) $filter[] = "a.id IN (" . implode(',', $favs) . ")";
    	else $filter[] = '1 = 2';

    	if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
    	$filter = $this->buildSearchFilter($filter);

    	// search by ?
    	if($this->view->filter) {
    		$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
    		if(isset($species))
    			$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
    	} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
    	else $this->view->title = $this->translate->_("Favorite Pets");

    	// get page
    	$page = $this->request->getParam('page');
    	$page = $page ? intval($page) : 0;

    	// get pets
    	$paginator = $this->pets->getPets('paginator', $filter, "id DESC", false, $this->keyword);
    	$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
    	$paginator->setCurrentPageNumber($page);

    	// output archived pets
    	$this->view->favorites = $this->pets->formatPets($paginator);
    }

    /**
     * Show adoption interest
     */
    private function showAdoptionInterest()
    {
    	if($this->auth->hasIdentity()) {
    		$intrested = $this->interestMap->fetchList("user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->auth->getIdentity()->id, Zend_Db::BIGINT_TYPE)." AND (status = 1 OR status = 0)");
    		if(is_array($intrested) && count($intrested) > 0)
    			return true;
    	}

    	return false;
    }

	public function downloadQrAction() {
		$id = $this->request->getParam('pet');
		if(!$id)
			die("Gtfo noob");

		// decide on qr path
		$url = urlencode($this->view->url(array('controller'=>'pets', 'action'=>'view-mobile', 'pet'=> $id), 'default', true));
		$path = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl={$url}&choe=UTF-8";

		// get file and headers
		$data = file_get_contents($path);
		$headers = get_headers ($path, 1);

		// get modified time and file size
	    $mtime = $headers['Last-Modified'];
	    $size = intval(sprintf("%u", $headers['Content-Length']));
		$description = "qr-code-pet-" . $id . ".png";

	    // set download headers
		header("Content-type: application/force-download");
		header('Content-Type: application/octet-stream');

	    // set attachement headers... ie sux
		if(strstr($_SERVER["HTTP_USER_AGENT"], "MSIE") != false)
			header("Content-Disposition: attachment; filename=" . urlencode($description) . "; modification-date=\"{$mtime}\";");
		else
			header("Content-Disposition: attachment; filename=\"" . $description . "\"; modification-date=\"{$mtime}\";");

		// turn off apache compression
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);

		// set the length so the browser can set the download timers
		header("Content-Length: {$size}");

		// terminate script
		die($data);
	}

	public function printQrAction() {
		$id = $this->request->getParam('pet');
		if(!$id)
			die("Gtfo noob");

    	// disable layout for print
		$this->_helper->layout->disableLayout();

		// decide on qr path
		$url = urlencode($this->view->url(array('controller'=>'pets', 'action'=>'view-mobile', 'pet'=> $id), 'default', true));
		$path = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl={$url}&choe=UTF-8";

		// send to view
		$this->view->qr = $path;
	}

	public function emergencyAction() {
		// verify user
    	$this->verifyUser();

		// get pet
		$result = $this->pmap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $pet = reset($result);

		// load menu
		$this->petOptions($pet, reset($this->attr->loadAttributeValues($pet, true)));

		// data source
		$ds = new Petolio_Model_PoEmergency();

		// on save
		$save = (string)$this->request->getParam('save');
		if($save) {
			$save = json_decode(base64_decode($save));

			// save qr
			$pet->setMobileEmergency($save->qr)->save();

			// delete all of the previous contacts
			$ds->getMapper()->getDbTable()->delete("scope = 'po_pets' AND entity_id = '{$pet->getId()}'");

			// save each contact
			foreach($save->contacts as $one) {
				$clone = clone $ds;
				$clone->setOptions(array(
					'scope' => 'po_pets',
					'entity_id' => $pet->getId(),
					'first_name' => $one[0],
					'last_name' => $one[1],
					'category' => $one[2],
					'phone' => $one[3],
					'email' => $one[4]
				))->save();
			}

			// redirect
			$this->msg->messages[] = $this->translate->_("Your emergency contacts has been saved successfully.");
			return $this->_redirect('pets/view/pet/'. $pet->getId());
		}

		// get pet services
		$services = $this->loadPetServices($pet);

		// load the form
		$form = new Petolio_Form_Emergency($services);
		$this->view->form = $form;

		// get groups to interpret for javascript
        $db = new Petolio_Model_DbTable_PoAttributeSets();
        $select = $db->select()
        	->where("scope = 'po_services'")
        	->where("active = 1");

        $service_types = array();
        foreach($db->fetchAll($select) as $line)
    		$service_types[$line['id']] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name']));
		$this->view->service_types = json_encode($service_types);

		// get existing contacts
		$contact_list = array();
		$contacts = $ds->fetchList("scope = 'po_pets' AND entity_id = '{$pet->getId()}'", "id ASC");
		foreach($contacts as $one)
			$contact_list[] = array(
				$one->getFirstName(),
				$one->getLastName(),
				$one->getCategory(),
				$one->getPhone(),
				$one->getEmail()
			);
		$this->view->contact_list = json_encode($contact_list);
	}
}

/**
 * <3 Septerra Core
 * Allows you to *merge* 2 objects
 * most notably a Model with a Stdandard
 * 	- supports __call and __get atm
 *
 * @author Seth
 */
class Septerra_Core {
	private $a;
	private $b;

	/**
	 * Constructor
	 * @param object $a
	 * @param object $b
	 */
	public function __construct($a, $b) {
		$this->a = $a;
		$this->b = $b;
	}

	/**
	 * Magic __call
	 * @param string $func
	 * @param mixed $arg
	 */
	public function __call($func, $arg) {
		if(method_exists($this->a, $func))
			return call_user_func_array(array($this->a, $func), $arg);
		else
			return call_user_func_array(array($this->b, $func), $arg);
	}

	/**
	 * Magic __get
	 * @param obj property $attr
	 */
	public function __get($attr) {
		try {
			return $this->a->$attr;
		} catch(Exception $e) {
			 return $this->b->$attr;
		}
	}
}

/**
 * File Management Interface
 * @author Seth
 */
interface File_Management_Interface {
	// starting function
	public function start();

	// get actions
	public function browse();
	public function download();
	public function delete();
	public function remove();

	// post actions
	public function upload($what);
	public function access();
	public function permission();
	public function mass($what);
}

/**
 * File Management
 * 	- handles all functions from the files action
 *
 * @author Seth
 */
class File_Management implements File_Management_Interface {
	// inherit from controller
	public $up;
	public $msg;
	public $view;
	public $auth;
	public $config;
	public $helper;
	public $request;
	public $translate;

	// directory separator & paths
	private $ds = DIRECTORY_SEPARATOR;
	private $path = array(
		'..',
		'data',
		'userfiles',
		'pets'
	);
	private $loc = array(
		'images',
		'userfiles'
	);

	// icon setup
	private $ico = '/images/files/';

	// keep all db models here
	private $db;

	// protected vars used by browse + other actions
	public $pet;
	protected $user;
	protected $root;
	protected $admin;

	/**
	 * Constructor
	 */
	public function __construct() {
		// define as standard class
		$this->db = new stdClass();

		// load models
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->folder = new Petolio_Model_PoFolders();
		$this->db->file = new Petolio_Model_PoFiles();
		$this->db->file_right = new Petolio_Model_PoFileRights();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->micro = new Petolio_Model_PoMicrosites();
	}

	/**
	 * Map actions to functions
	 */
	public function start() {
		// icon, download, delete, remove, browse
		if($this->request->getParam('icon')) $this->icon();
		elseif($this->request->getParam('download')) $this->download();
		elseif($this->request->getParam('delete')) $this->delete();
		elseif($this->request->getParam('remove')) $this->remove();
		else $this->browse();

		// create file / folder
		if($this->request->getParam('upload'))
			$this->upload($this->request->getParam('upload'));

		// edit access / permission
		if($this->request->getParam('access')) $this->access();
		if($this->request->getParam('permission')) $this->permission();

		// mass edit access / delete
		if(isset($_POST['mass_action']))
			$this->mass($_POST['mass_action']);
	}

	/**
	 * Deny access with message
	 * @param string $msg
	 */
	private function deny($msg = false) {
		$this->msg->messages[] = $msg ? $msg : $this->translate->_("Invalid Request.");
		$this->helper->redirector('index', 'site');
	}

	/**
	 * Browse (load folder/file structure based on pet id and directory id)
	 */
	public function browse() {
		// assign vars from params
		$pet = (int)$this->request->getParam('pet');
		$browse = (int)$this->request->getParam('browse');

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// no pet ? bye !
		if(!$pet)
			return $this->deny();

		// load pet
		$this->pet = $this->db->pet->find($pet);
		if(!$this->pet->getId())
			return $this->deny();

		// load pet owner
		$user = $this->db->user->find($this->pet->getUserId());

		// load pet owner's friends
		$friends = array();
		foreach($user->getUserFriends() as $row)
			$friends[$row->getId()] = array('name' => $row->getName());

		// load pet owner's partners
		$partners = array();
		foreach($user->getUserPartners() as $row)
			$partners[$row->getId()] = array('name' => $row->getName());

		// combine user with friends and partners
		$this->user = new Septerra_Core($user, (object)array(
			'friends' => $friends,
			'partners' => $partners
		));

		// find root
		$filter = array("name = 'root'", "pet_id = {$this->pet->getId()}", "parent_id = 0");
		if($browse)
			$filter = array("id = {$browse}", "pet_id = {$this->pet->getId()}");

		// load root
		$this->root = reset($this->db->folder->fetchList(implode(' AND ', $filter)));

		// no dir ? bye !
		if(!$this->root)
			return $this->deny();

		// get folders
		$filter = array("parent_id = {$this->root->getId()}", "pet_id = {$this->pet->getId()}");
		$folders = $this->db->folder->fetchListToArray(implode(' AND ', $filter));

		// get files
		$filter = array("folder_id = {$this->root->getId()}");
		$files = $this->db->file->getMapper()->getFiles(implode(' AND ', $filter));

		// filter files that we dont have access to
		foreach($files as $idx => $file)
			if($this->isFilePrivate($file['id'], $file['rights'], $file['owner_id'], $this->user->getId()))
				unset($files[$idx]);

		// do sorting
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// format folders
		$results = array();
		foreach($folders as $folder) {
			$results[] = array(
				'ico' => $this->ico . 'dir.gif',
				'type' => 'dir',
				'real_type' => 'dir',
				'id' => $folder['id'],
				'name' => $folder['name'],
				'owner' => $folder['owner_id'],
				'date' => $folder['date_created'],
				'real_date' => strtotime($folder['date_created']),
				'size' => htmlspecialchars($this->translate->_("<DIR>")),
				'real_size' => 0,
				'rights' => $this->translate->_("Mixed"),
				'sort' => 0
			);
		}

		// format files
		foreach($files as $file) {
			// create format
			$array = array(
				'ico' => $this->ico . (in_array(strtolower(pathinfo($file['file'], PATHINFO_EXTENSION)), Petolio_Model_PoFiles::$_KNOWN_EXTENSIONS) ? strtolower(pathinfo($file['file'], PATHINFO_EXTENSION)) : 'file') . '.gif',
				'type' => pathinfo($file['file'], PATHINFO_EXTENSION),
				'real_type' => 'file',
				'id' => $file['id'],
				'name' => $file['description'],
				'owner' => $file['owner_id'],
				'date' => $file['date_modified'] ? $file['date_modified'] : $file['date_created'],
				'real_date' => strtotime($file['date_modified'] ? $file['date_modified'] : $file['date_created']),
				'size' => $this->formatSize($file['size'] * 1024),
				'real_size' => $file['size'] * 1024,
				'rights' => $this->getAccess($file['rights'], $file['users'])
			);

			// implement sorting
			if($this->view->order == 'name') $array['sort'] = $array['name'];
			elseif($this->view->order == 'size') $array['sort'] = $array['real_size'];
			elseif($this->view->order == 'rights') $array['sort'] = $array['rights'];
			else {
				$this->view->order = 'modified';
				$array['sort'] = $array['real_date'];
			}

			// add the file to our big results array
			$results[] = $array;
		}

		// no sort ? set default
		if(!$this->view->order)
			$this->view->order = 'modified';

		// perform sort (never orders folders)
		Petolio_Service_Util::array_sort($results, array("real_type" => true, "sort" => $this->view->dir == 'asc' ? true : false));

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// pagination
		$paginator = Zend_Paginator::factory($results);
		$paginator->setItemCountPerPage($this->config["files"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// get breadcrumbs and add latest
		$breadcrumbs = $this->db->folder->getMapper()->getBreadcrumbs($this->root->getTraceback());
		$breadcrumbs[] = array(
			'id' => $this->root->getId(),
			'name' => $this->root->getName()
		);

		// figure out if admin
		$this->admin = false;
		if($this->auth->hasIdentity())
			$this->admin = $this->user->getId() == $this->auth->getIdentity()->id ? $this->user->getId() : false;

		// send to template
		$this->view->breadcrumbs = $breadcrumbs;
		$this->view->browse = $paginator;
		$this->view->admin = $this->admin;
		$this->view->root = $this->root;
		$this->view->pet = $this->pet;
	}

	/**
	 * Format file size
	 * @param int $size - size in bytes
	 *
	 * @return string - Formatted size
	 */
	private function formatSize($size) {
		if($size == 0)
			return null;

		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]);
	}

	/**
	 * Get Access right
	 * @param int $right
	 *
	 * @return string - translated
	 */
	private function getAccess($right, $users) {
		$rights = array(
			0 => $this->translate->_("Private"),
			1 => $this->translate->_("Everyone"),
			2 => $this->translate->_("%s Partners"),
			3 => $this->translate->_("%s Friends"),
			4 => $this->translate->_("%s Users")
		);

		return $right != 0 || $right != 1 ? substr(sprintf($rights[$right], $users == 0 ? $this->translate->_("All") : $users), 0, $users == 1 ? -1 : 100) : $rights[$right];
	}

	/**
	 * Retrieve and die with the binary file code
	 */
	public function download() {
		// assign var from param
		$id = (int)$this->request->getParam('download');

		// no file ? bye !
		if(!$id)
			return $this->deny();

		// get file
		$file = $this->db->file->find($id);
		if(!$file->getId())
			return $this->deny();

		// get directory from file
		$dir = $this->db->folder->find($file->getFolderId());

		// is this a pet file ?
		if($dir->getPetId()) {
			// get pet from directory
			$this->pet = $this->db->pet->find($dir->getPetId());

			// load pet owner
			$user = $this->db->user->find($this->pet->getUserId());

		// microsite / gallery pic
		} else {
			// load folder owner
			$user = $this->db->user->find($dir->getOwnerId());
		}

		// load pet owner's friends
		$friends = array();
		foreach($user->getUserFriends() as $row)
			$friends[$row->getId()] = array('name' => $row->getName());

		// load pet owner's partners
		$partners = array();
		foreach($user->getUserPartners() as $row)
			$partners[$row->getId()] = array('name' => $row->getName());

		// combine user with friends and partners
		$this->user = new Septerra_Core($user, (object)array(
			'friends' => $friends,
			'partners' => $partners
		));

		// if file is private deny access
		if($this->isFilePrivate($file->getId(), $file->getRights(), $file->getOwnerId(), $this->user->getId()))
			return $this->deny();

		// load from breadcrumbs (we only need name)
		$breadcrumbs = array();
		foreach($this->db->folder->getMapper()->getBreadcrumbs($dir->getTraceback()) as $crumb)
			$breadcrumbs[] = $crumb['name'];

		// pet file ?
		if($this->pet) {
			$path = array_merge($this->loc, array('pets'), $breadcrumbs, array($dir->getName(), $file->getFile()));
			$path = str_replace('root', $this->pet->getId(), $path);
			$path = implode($this->ds, $path);

		// microsite / gallery pic
		} else {
			// microsite
			if($dir->getName() == 'microsite') {
				$path = array_merge($this->loc, array('microsites'), array($dir->getName(), $file->getFile()));
				$path = implode($this->ds, $path);

				// get microsite for the id
				$results = $this->db->micro->fetchList("user_id = {$user->getId()} AND folder_id = {$dir->getId()}");
				if(!$results)
					return $this->deny();

				// replace folder name with microsite id
				$micro = reset($results);
				$path = strtr($path, array('microsites' => 'microsites', 'microsite' => $micro->getId()));

			// services
			} elseif($dir->getName() == 'service') {
				$path = array_merge($this->loc, array('ss_folder'), array($dir->getName(), $file->getFile()));
				$path = implode($this->ds, $path);

				// get service for the id
				$services = new Petolio_Model_PoServices();
				$results = $services->fetchList("user_id = {$user->getId()} AND folder_id = {$dir->getId()}");
				if(!$results)
					return $this->deny();

				// replace folder name with service id
				$services = reset($results);
				$path = strtr($path, array('service' => $services->getId()));
				$path = strtr($path, array('ss_folder' => 'services'));

			// product
			} elseif($dir->getName() == 'product') {
				$path = array_merge($this->loc, array('pr_folder'), array($dir->getName(), $file->getFile()));
				$path = implode($this->ds, $path);

				// get product for the id
				$products = new Petolio_Model_PoProducts();
				$results = $products->fetchList("user_id = {$user->getId()} AND folder_id = {$dir->getId()}");
				if(!$results)
					return $this->deny();

				// replace folder name with product id
				$products = reset($results);
				$path = strtr($path, array('product' => $products->getId()));
				$path = strtr($path, array('pr_folder' => 'products'));

			// question
			} elseif($dir->getName() == 'question') {
				$path = array_merge($this->loc, array('qs_folder'), array($dir->getName(), $file->getFile()));
				$path = implode($this->ds, $path);

				// get product for the id
				$questions = new Petolio_Model_PoHelp();
				$results = $questions->fetchList("user_id = {$user->getId()} AND folder_id = {$dir->getId()}");
				if(!$results)
					return $this->deny();

				// replace folder name with product id
				$questions = reset($results);
				$path = strtr($path, array('question' => $questions->getId()));
				$path = strtr($path, array('qs_folder' => 'help'));

			// gallery
			} else {
				$path = array_merge($this->loc, array('galleries'), array($dir->getName(), $file->getFile()));
				$path = implode($this->ds, $path);
			}
		}

		// file doens't exist on disk ? bye !
		if(!file_exists($path))
			return $this->deny();

		// is this actually a youtube video ? send to youtube
		if($file->getType() == 'video')
			return $this->helper->redirector->gotoUrl("http://www.youtube.com/watch?v=". pathinfo($file->getFile(), PATHINFO_FILENAME));

		// get modified time and file size
	    $mtime = date('r', filemtime($path) ? filemtime($path) : time());
	    $size = intval(sprintf("%u", filesize($path)));

	    // set download headers
		header("Content-type: application/force-download");
		header('Content-Type: application/octet-stream');

	    // set attachement headers... ie sux
		if(strstr($_SERVER["HTTP_USER_AGENT"], "MSIE") != false)
			header("Content-Disposition: attachment; filename=" . urlencode($file->getDescription()) . "; modification-date=\"{$mtime}\";");
		else
			header("Content-Disposition: attachment; filename=\"" . $file->getDescription() . "\"; modification-date=\"{$mtime}\";");

		// php memory limit (Not higher than 1GB)
		if(intval($size + 1) > $this->returnBytes(ini_get('memory_limit')) && intval($size * 1.5) <= 1073741824)
	        ini_set('memory_limit', intval($size * 1.5));

		// turn off apache compression
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);

		// set the length so the browser can set the download timers
		header("Content-Length: {$size}");

		// set the time limit based on an average D/L speed of 50kb/sec
		set_time_limit(min(7200, // no more than 120 minutes (this is really bad, but...)
			($size > 0) ? intval($size / 51200) + 60 // 1 minute more than what it should take to D/L at 50kb/sec
						: 1 // minimum of 1 second in case size is found to be 0
		));

		// read 1 megabyte at a time
		$chunksize = 1 * (1024 * 1024);
		if($size > $chunksize) { // file is bigger than our 1 mb chunk
	        $buffer = null;
			$handle = fopen($path, 'rb');

	        while (!feof($handle)) {
				$buffer = fread($handle, $chunksize);
				echo $buffer;
				ob_flush();
				flush();
			}

	        fclose($handle);
		} else // streaming whole file for download
			readfile($path);

		// terminate script
		die();
	}

	/**
	 * So is the file private or not ??
	 *
	 * @param int $id - File Id
	 * @param int $rights - File Right
	 * @param int $owner - File Owner
	 * @param int $p_owner - Pet Owner
	 *
	 * @return bool
	 */
	private function isFilePrivate($id, $rights, $owner, $p_owner) {
		// lets say its not private to begin with
		$private = false;

		// not logged in ? if public, not private, everything else is restricted
		if(!$this->auth->hasIdentity())
			$private = $rights == 1 ? false : true;

		// logged in ?
		else {
			// user logged in id alias
			$logged = $this->auth->getIdentity()->id;

			// overwrite if you're the file owner
			if($logged == $owner)
				return $private;

			// overwrite if you're the pet owner
			if($logged == $p_owner)
				return $private;

			// switch between rights
			switch($rights) {
				// nobody
				case 0:
					$private = true;
				break;

				// everyone
				case 1:
					// do nothing
				break;

				// partners
				case 2:
					// loop through allowed users
					$allowed = array();
					foreach($this->db->file_right->getMapper()->findByField('file_id', $id, null) as $user)
						$allowed[] = $user->getUserId();

					// no users ? match against all partners
					if(!$allowed) {
						if(!array_key_exists($logged, $this->user->partners))
							$private = true;

					// specific users found ? match against those
					} else {
						if(!in_array($logged, $allowed))
							$private = true;
					}
				break;

				// friends
				case 3:
					// loop through allowed users
					$allowed = array();
					foreach($this->db->file_right->getMapper()->findByField('file_id', $id, null) as $user)
						$allowed[] = $user->getUserId();

					// no users ? match against all friends
					if(!$allowed) {
						if(!array_key_exists($logged, $this->user->friends))
							$private = true;

					// specific users found ? match against those
					} else {
						if(!in_array($logged, $allowed))
							$private = true;
					}
				break;

				// users
				case 4:
					// loop through allowed users
					$allowed = array();
					foreach($this->db->file_right->getMapper()->findByField('file_id', $id, null) as $user)
						$allowed[] = $user->getUserId();

					// match against users found
					if(!in_array($logged, $allowed))
						$private = true;
				break;
			}
		}

		return $private;
	}

	/**
	 * Return bytes from a string
	 * @param string $val - 100M, 1G, 20K
	 */
	private function returnBytes($val) {
		$val = trim($val);
		switch (strtolower($val[strlen($val) - 1])) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}

		return $val;
	}

	/**
	 * Remove file action
	 */
	public function remove() {
		// assign var from param
		$id = (int)$this->request->getParam('remove');

		// not logged in ? bye !
		if(!$this->auth->hasIdentity())
			return $this->deny();

		// no file ? bye !
		if(!$id)
			return $this->deny();

		// search file
		$results = $this->db->file->fetchList("id = {$id} AND owner_id = {$this->auth->getIdentity()->id}");
		if(!$results)
			return $this->deny();

		// get & delete the file
		$file = reset($results);
		$this->deleteFiles(array($file->getId()));

		// success
		$this->msg->messages[] = sprintf($this->translate->_("%s has been successfully removed."), $file->getDescription());
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
	}

	/**
	 * Remove dir action
	 */
	public function delete() {
		// assign var from param
		$id = (int)$this->request->getParam('delete');

		// not logged in ? bye !
		if(!$this->auth->hasIdentity())
			return $this->deny();

		// no directory ? bye !
		if(!$id)
			return $this->deny();

		// search folder
		$results = $this->db->folder->fetchList("id = {$id} AND owner_id = {$this->auth->getIdentity()->id}");
		if(!$results)
			return $this->deny();

		// get & delete the folder
		$errors = array();
		$folder = reset($results);
		$this->deleteFolders(array($folder->getId()), $errors);

		// error or success
		if($errors) $this->msg->messages[] = sprintf($this->translate->_("%s could not be removed. You might not be the owner of those directories."), implode(', ', $errors));
		else $this->msg->messages[] = sprintf($this->translate->_("%s has been successfully removed."), $folder->getName());
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
	}

	/**
	 * Delete files from db and disk
	 * @param array $files - array of file ids
	 */
	private function deleteFiles($files) {
		// no files ? bye !
		if(!$files)
			return;

		// loop through files
		foreach($files as $file) {
			// get the file
			$file = $this->db->file->find($file);

			// get directory from file
			$dir = $this->db->folder->find($file->getFolderId());

			// load from breadcrumbs (replace root with pet id)
			$breadcrumbs = array();
			foreach($this->db->folder->getMapper()->getBreadcrumbs($dir->getTraceback()) as $crumb)
				$breadcrumbs[] = $crumb['name'];

			// to figure out path, merge path, breadcrumbs, etc
			$path = array_merge($this->path, $breadcrumbs, array($dir->getName(), $file->getFile()));
			$path = str_replace('root', $dir->getPetId(), $path);
			$path = implode($this->ds, $path);

			// delete file from db
			$this->db->file->delete("id = {$file->getId()}");

			// get pet
			$this->pet = $this->db->pet->find($dir->getPetId());

			// get pet name
			$attributes = new \Petolio_Model_PoAttributes();
			$pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($this->pet, true));

			// update dashboard
			if(in_array($file->getType(), array('image', 'audio', 'video'))) {
				$fake = array($this->translate->_("pet")); unset($fake);
				\Petolio_Service_Autopost::factory($file->getType(), $file->getFolderId(),
					'pet',
					$this->pet->getId(),
					$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => $this->pet->getId()), 'default', true),
					$pet_attributes['name']->getAttributeEntity()->getValue()
				);
			}

			// is file a youtube video and is it an upload ?
			if($file->getType() == 'video' && round($file->getSize()) == 0) {
				// youtube wrapper
				$youtube = Petolio_Service_YouTube::factory('Master');
				$youtube->CFG = array(
					'username' => $this->config["youtube"]["username"],
					'password' => $this->config["youtube"]["password"],
					'app' => $this->config["youtube"]["app"],
					'key' => $this->config["youtube"]["key"]
				);

				// find on youtube
				$videoEntryToDelete = null;
				foreach($youtube->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads') as $entry) {
					if($entry->getVideoId() == pathinfo($file->getFile(), PATHINFO_FILENAME)) {
						$videoEntryToDelete = $entry;
						break;
					}
				}

				// delete from youtube (we dont care about errors at this point)
				try {
					$youtube->delete($videoEntryToDelete);
				} catch (Exception $e) {}
			}

			// file doens't exist on disk ? skip
			if(!file_exists($path))
				continue;

			// delete from disk
			@unlink($path);

			// in case of gallery, clear the thumbnails as well
			@unlink(str_replace($file->getFile(), 'thumb_' . $file->getFile(), $path));
			@unlink(str_replace($file->getFile(), 'small_' . $file->getFile(), $path));
		}
	}

	/**
	 * Delete a folder from db and disk
	 * @param array $folder - array of folder ids
	 */
	private function deleteFolders($folders, &$errors = array()) {
		// no folders ? bye !
		if(!$folders)
			return;

		// loop through folders
		foreach($folders as $folder) {
			// get the folder
			$folder = $this->db->folder->find($folder)->toArray();

			// you shouldn't be able to delete the root
			if($folder['name'] == 'root')
				continue;

			// loop through all folders within this folder
			foreach($this->db->folder->getMapper()->getChildren($folder['id'], $folder['owner_id']) as $dir) {
				// call helper for all files within these folders
				if(!$this->_deleteHelper($dir, $folder['owner_id']))
					$errors[] = $dir['name'];
			}

			// call helper for all files within the selected folder
			if(!$this->_deleteHelper($folder, $folder['owner_id']))
				$errors[] = $folder['name'];
		}
	}

	/**
	 * Delete folder helper
	 * @param array $folder
	 * @param int $owner
	 *
	 * @return bool
	 */
	private function _deleteHelper($folder, $owner) {
		// delete all the files from the current dir
		foreach($this->db->file->fetchList("folder_id = {$folder['id']} AND owner_id = {$owner}") as $file)
			$this->deleteFiles(array($file->getId()));

		// load from breadcrumbs (we only need name)
		$breadcrumbs = array();
		foreach($this->db->folder->getMapper()->getBreadcrumbs($folder['traceback']) as $crumb)
			$breadcrumbs[] = $crumb['name'];

		// to figure out path, merge path, breadcrumbs, etc
		$path = array_merge($this->path, $breadcrumbs, array($folder['name']));
		$path = str_replace('root', $folder['pet_id'], $path);
		$path = implode($this->ds, $path);

		// remove the folder (must be empty)
		if(@rmdir($path)) {
			$this->db->folder->delete("id = {$folder['id']}");
			return true;
		} else return false;
	}

	/**
	 * Upload folder or file
	 * @param string $what - folder / file
	 */
	public function upload($what) {
		// send to apropriate action
		if($what == 'folder') $this->createFolder();
		elseif($what == 'file') $this->uploadFile();
		else return $this->deny();
	}

	/**
	 * Create a folder
	 */
	private function createFolder() {
		// no admin ? bye
		if(!$this->admin)
			return $this->deny();

		// show form
		$form = new Petolio_Form_Files();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return;

		// get data
		$data = $form->getValues();

		// add current root to the traceback
		$traceback = $this->root->getTraceback() ? explode(',', $this->root->getTraceback()) : array();
		$traceback[] = $this->root->getId();

		// add folder in db
		$folder = new Petolio_Model_PoFolders();
		$folder->setOptions(array(
			'name' => $data['name'],
			'petId' => $this->pet->getId(),
			'ownerId' => $this->user->getId(),
			'parentId' => $this->root->getId(),
			'traceback' => implode(',', $traceback)
		))->save(true, true);

		// load from breadcrumbs (we only need name)
		$breadcrumbs = array();
		foreach($this->db->folder->getMapper()->getBreadcrumbs($this->root->getTraceback()) as $crumb)
			$breadcrumbs[] = $crumb['name'];

		// to figure out path, merge path, breadcrumbs, etc
		$path = array_merge($this->path, $breadcrumbs, array($this->root->getName(), $folder->getName()));
		$path = str_replace('root', $this->pet->getId(), $path);
		$path = implode($this->ds, $path);

		// try and add the folder on disk
		if(!mkdir($path)) {
			// in case of an error
			$this->db->folder->delete("id = {$folder->getId()}");
			$this->msg->messages[] = $this->translate->_('There was an critical error regarding the creation of your folder on disk.');
		} else
			$this->msg->messages[] = sprintf($this->translate->_("%s has been successfully created."), $folder->getName());

		// redirect
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
	}

	/**
	 * Upload files
	 */
	private function uploadFile() {
		// no admin ? bye
		if(!$this->admin)
			return $this->deny();

		// show form
		$form = new Petolio_Form_Upload($this->translate->_("File"), $this->translate->_("Upload Files"));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// load from breadcrumbs (we only need name)
		$breadcrumbs = array();
		foreach($this->db->folder->getMapper()->getBreadcrumbs($this->root->getTraceback()) as $crumb)
			$breadcrumbs[] = $crumb['name'];

		// to figure out path, merge path, breadcrumbs, etc
		$path = array_merge($this->path, $breadcrumbs, array($this->root->getName()));
		$path = str_replace('root', $this->pet->getId(), $path);
		$path = implode($this->ds, $path) . $this->ds;

		// define some vars
		$i = 0;
		$errors = array();
		$success = array();

		// get addapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($path);
		$adapter->addValidator('Extension', false,
			array('doc', 'xls', 'jpg', 'gif', 'png', 'txt', 'avi', 'mpg', 'mpeg', 'mp3', 'pdf', 'rar', 'zip')
		);

		// set the max filesize
		$adapter->addValidator('Size', false, $this->config['max_filesize']);

		// check if files have exceeded the limit
		if(!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your file / files exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
				return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
			}
		}

		// upload each file
		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

			$adapter->clearFilters();
			$adapter->addFilter('Rename', array('target' => $path . $new_filename, 'overwrite' => true));

			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) $errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
			else $success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $this->ds . $new_filename;
		}

		// go through each successfully uploaded file
		foreach($success as $original => $file) {
			// save every file in db
			$one = new Petolio_Model_PoFiles();
			$one->setOptions(array(
				'file' => pathinfo($file, PATHINFO_BASENAME),
				'type' => 'file',
				'size' => filesize($file) / 1024,
				'folder_id' => $this->root->getId(),
				'owner_id' => $this->user->getId(),
				'description' => $original
			))->save();
		}

		// set upload messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your pet files have been uploaded successfully.");
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
	}

	/*
	 * Access action (for files)
	 */
	public function access() {
		// assign var from param
		$id = (int)$this->request->getParam('access');

		// no id ? bye !
		if(!$id)
			return $this->deny();

		// no admin ? bye
		if(!$this->admin)
			return $this->deny();

		// send friends and partners to dropdowns
		$this->view->friends = $this->user->friends;
		$this->view->partners = $this->user->partners;

		// get file
		$file = $this->db->file->find($id);
		if(!$file)
			return $this->deny();

		// set default variables
		$users_id = array();
		$users_id_name = array();

		// if access right is 2, 3 or 4 then try and fill the users
		if($file->getRights() > 1) {
			foreach($this->db->file_right->getMapper()->fetchUsers($file->getId()) as $user) {
				// fill for friends and partners (2, 3)
				$users_id[] = $user['user_id'];

				// fill for users (4)
				if($file->getRights() == 4)
					$users_id_name[] = $user['user_id'] . '|' . $user['user_name'];
			}
		}

		// fill form
		$this->view->rights = array(
			'access' => $file->getRights(),
			'users_id' => $users_id,
			'users_id_name' => implode(',', $users_id_name)
		);

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return;

		// nothing there? unset
		if(empty($_POST['access_users']))
			unset($_POST['access_users']);

		// validate 4 (at least 1 user must be selected)
		if($_POST['access_value'] == 4 && !isset($_POST['access_users'])) {
			$this->view->rights['access'] = 4;
			$this->view->users_error = true;
			return;
		}

		// update file
		$this->updateFilesAccess(array($file->getId()), $this->rightsSrc($_POST));

		// msg and redirect
		$this->msg->messages[] = sprintf($this->translate->_("The access rights for %s have been successfully updated."), $file->getDescription());
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? preg_replace("/\/access\/(\d+)\//i", "/", $_SERVER['HTTP_REFERER']) : 'pets/mypets');
	}

	/*
	 * Permission action (for folders)
	 */
	public function permission() {
		// assign var from param
		$id = (int)$this->request->getParam('permission');

		// no id ? bye !
		if(!$id)
			return $this->deny();

		// no admin ? bye
		if(!$this->admin)
			return $this->deny();

		// send friends and partners to dropdowns
		$this->view->friends = $this->user->friends;
		$this->view->partners = $this->user->partners;

		// get folder
		$folder = $this->db->folder->find($id);
		if(!$folder)
			return $this->deny();

		// fill form
		$this->view->rights = array(
			'access' => 0,
			'users_id' => array(),
			'users_id_name' => ''
		);

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return;

		// nothing there? unset
		if(empty($_POST['access_users']))
			unset($_POST['access_users']);

		// validate 4 (at least 1 user must be selected)
		if($_POST['access_value'] == 4 && !isset($_POST['access_users'])) {
			$this->view->rights['access'] = 4;
			$this->view->users_error = true;
			return;
		}

		// update folder
		$this->updateFoldersAccess(array($folder->getId()), $this->rightsSrc($_POST));

		// msg and redirect
		$this->msg->messages[] = sprintf($this->translate->_("The access rights for %s have been successfully updated."), $folder->getName());
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? preg_replace("/\/permission\/(\d+)\//i", "/", $_SERVER['HTTP_REFERER']) : 'pets/mypets');
	}

	/**
	 * Get correct right source
	 * @param array $src - usually a $_POST
	 * @return array
	 */
	private function rightsSrc($src = array()) {
		switch($src['access_value']) {
			case 0: return array(0, array());
			case 1: return array(1, array());
			case 2: return array(2, (array)@$src['access_partners']);
			case 3: return array(3, (array)@$src['access_friends']);
			case 4: return array(4, (array)@$src['access_users']);
		}
	}

	/**
	 * Update files access rights
	 * @param array $files - array of file ids
	 * @param array $rights
	 */
	private function updateFilesAccess($files, $rights) {
		// no files ? bye !
		if(!$files)
			return;

		// loop through files
		foreach($files as $file) {
			// get file
			$file = $this->db->file->find($file);

			// get folder
			$folder = $this->db->folder->find($file->getFolderId())->toArray();

			// you shouldn't be able to update the access rights for gallery files
			if($folder['name'] == 'gallery')
				continue;

			// delete old users
			$this->db->file_right->delete("file_id = {$file->getId()}");

			// save users
			foreach($rights[1] as $id) {
				$user = new Petolio_Model_PoFileRights();
				$user->setFileId($file->getId())
					->setUserId($id)
					->save();
			}

			// update file access
			$file->setRights($rights[0])->save(false);
		}
	}

	/**
	 * Update folders access rights
	 * @param array $folders - array of folder ids
	 * @param array $rights
	 */
	private function updateFoldersAccess($folders, $rights) {
		// no folders ? bye !
		if(!$folders)
			return;

		// loop through folders
		foreach($folders as $folder) {
			// get the folder
			$folder = $this->db->folder->find($folder)->toArray();

			// you shouldn't be able to update the access rights for root
			if($folder['name'] == 'root')
				continue;

			// loop through all folders within this folder
			foreach($this->db->folder->getMapper()->getChildren($folder['id']) as $dir) {
				// update all the files from the current dir
				$this->_accessHelper($dir, $rights);
			}

			// update access to all the files in the selected folder
			$this->_accessHelper($folder, $rights);
		}
	}

	/**
	 * Update folder access helper
	 * @param array $folder
	 * @param int $rights
	 */
	private function _accessHelper($folder, $rights) {
		// you shouldn't be able to update the access rights for gallery files
		if($folder['name'] == 'gallery')
			return false;

		// update access to all the files from the current dir
		foreach($this->db->file->fetchList("folder_id = {$folder['id']}") as $file)
			$this->updateFilesAccess(array($file->getId()), $rights);
	}

	/**
	 * Mass edit or delete
	 * @param string $what - sel_edit / sel_delete
	 */
	public function mass($what) {
		// get from post
		$folders = (array)@$_POST['mass_folders'];
		$files = (array)@$_POST['mass_files'];

		// no admin ? bye
		if(!$this->admin)
			return $this->deny();

		// send to apropriate action
		switch($what) {
			// mass delete
			case 'sel_delete':
				// define error
				$errors = array();

				// delete selected folders and files
				$this->deleteFolders($folders, $errors);
				$this->deleteFiles($files);

				// error or success
				if($errors) $this->msg->messages[] = sprintf($this->translate->_("%s could not be removed. You might not be the owner of those directories."), implode(', ', $errors));
				else $this->msg->messages[] = $this->translate->_("Your selected folders and/or files have been successfully removed.");
				return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
			break;

			// mass edit
			case 'sel_edit':
				// fill selection
				$sel = array();
				foreach($folders as $folder)
					$sel[] = "folder|{$folder}";
				foreach($files as $file)
					$sel[] = "file|{$file}";

				// send to template
				$this->view->mass_selection = isset($_POST['mass_selection']) ? $_POST['mass_selection'] : implode(',', $sel);
				$this->view->mass_action = $what;

				// fill form
				$this->view->rights = array(
					'access' => 0,
					'users_id' => array(),
					'users_id_name' => ''
				);

				// send friends and partners to dropdowns
				$this->view->friends = $this->user->friends;
				$this->view->partners = $this->user->partners;

				// did we submit form ? if not just return here
				if(!($this->request->isPost() && $this->request->getPost('submit')))
					return;

				// nothing there? unset
				if(empty($_POST['access_users']))
					unset($_POST['access_users']);

				// validate 4 (at least 1 user must be selected)
				if($_POST['access_value'] == 4 && !isset($_POST['access_users'])) {
					$this->view->rights['access'] = 4;
					$this->view->users_error = true;
					return;
				}

				// mass selection must exist and must not be empty
				if(!isset($_POST['mass_selection']) || empty($_POST['mass_selection']))
					return;

				// "decode" the mass selection
				$folder = $file = array();
				foreach(explode(',', $_POST['mass_selection']) as $item) {
					list($type, $id) = explode('|', $item);
						${$type}[] = $id;
				}

				// update access to selected folders and files
				$this->updateFoldersAccess($folder, $this->rightsSrc($_POST));
				$this->updateFilesAccess($file, $this->rightsSrc($_POST));

				// msg and redirect
				$this->msg->messages[] = $this->translate->_("The access rights for your selected folders and/or files have been successfully updated.");
				return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
			break;

			// action not found ?
			default:
				return $this->deny();
			break;
		}
	}
}

/**
 * Pedigree Management Interface
 * @author Seth
 */
interface Pedigree_Management_Interface {
	// starting function
	public function start();

	// get actions
	public function view();
	public function delete();
	public function find();

	// ajax actions
	public function insert();
	public function update();
}

/**
 * Pedigree Management
 * 	- handles all functions from the pedigree action
 *
 * @author Seth
 */
class Pedigree_Management implements Pedigree_Management_Interface {
	// inherit from controller
	public $msg;
	public $view;
	public $auth;
	public $helper;
	public $request;
	public $translate;

	// keep all db models here
	private $db;

	// public vars
	public $pet;
	public $pet_attrs;

	// protected vars used by view + other actions
	protected $admin;
    protected $levels = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// define as standard class
		$this->db = new stdClass();

		// load models
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->db->grii = new Petolio_Model_PoPedigree();
	}

	/**
	 * Map actions to functions
	 */
	public function start() {
		// delete, find, view
		if($this->request->getParam('delete')) $this->delete();
		elseif($this->request->getParam('find')) $this->find();
		else $this->view();

		// handle ajax
		if(isset($_POST['act'])) {
			if($_POST['act'] == 'insert') $this->insert();
			elseif($_POST['act'] == 'update') $this->update();
		}
	}

	public function view() {
		// assign vars from params
		$pet = (int)$this->request->getParam('pet');

		// no pet ? bye !
		if(!$pet)
			return $this->deny();

		// load pet
		$this->pet = $this->db->pet->find($pet);
		if(!$this->pet->getId())
			return $this->deny();

		$this->pet_attrs = reset($this->db->attr->loadAttributeValues($this->pet, true));

		// see if admin or not
		$this->view->admin = $this->auth->hasIdentity() && $this->auth->getIdentity()->id == $this->pet->getUserId() ? true : false;

		// set levels
		$this->setLevels();

		// start pedigree tree with pet
		$kids = array();
		$tree = array(
			0 => array('id' => $this->pet->getId(), 'name' => $this->pet_attrs['name']->getAttributeEntity()->getValue())
		);

		// get pedigree
		$res = $this->db->grii->getPedigree("pet_id = '{$this->pet->getId()}'", "level ASC");
		foreach($res as $pedigree) {
			if($pedigree['level'] == 0) $kids[] = $pedigree;
			else $tree[$pedigree['level']] = $pedigree;
		}

		// set template tree and levels
		$this->view->tree = $tree;
		$this->view->kids = $kids;
		$this->view->levels = $this->levels;
	}

	/**
	 * Remove from pedigree
	 */
	public function delete() {
		// assign vars from params
		$pet = (int)$this->request->getParam('pet');
		$id = (int)$this->request->getParam('delete');

		// not logged in ? bye !
		if(!$this->auth->hasIdentity())
			return $this->deny();

		// no level ? bye !
		if(!$id)
			return $this->deny();

		// search for pedigree
		$results = $this->db->grii->fetchList("id = {$id} AND pet_id = {$pet}");
		if(!$results)
			return $this->deny();

		// load pet
		$pet = $this->db->pet->find($pet);
		if(!$pet->getId())
			return $this->deny();

		// not admin ? bye !
		if($this->auth->getIdentity()->id != $pet->getUserId())
			return $this->deny();

		// delete pedigree
		$this->db->grii->delete("id = {$id} AND pet_id = {$pet->getId()}");

		// set levels
		$this->setLevels();

		// who did you just delete
		$grii = reset($results);
		if($grii->getLevel() == 0)
			$who = $this->levels[0];
		if($grii->getLevel() >= 1 && $grii->getLevel() <= 2)
			$who = $this->levels[1];
		else if($grii->getLevel() >= 3 && $grii->getLevel() <= 6)
			$who = $this->levels[2];
		else if($grii->getLevel() >= 7 && $grii->getLevel() <= 14)
			$who = $this->levels[3];

		// msg
		$this->msg->messages[] = sprintf($this->translate->_("%s has been successfully deleted."), $who);
		return $this->helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'pets/mypets');
	}

	/**
	 * Find pet based on species
	 */
	public function find() {
		// assign vars from params
		$pet = (int)$this->request->getParam('pet');
		$name = $this->request->getParam('name');

		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// find pet
		$pet = $this->db->pet->find($pet);
		if(!$pet->getId())
			return false;

		// no name param ?
		$name = strtolower($name);
		if(!isset($name))
			return false;

		// get pedigree
		$grii = array();
		$res = $this->db->grii->getPedigree("pet_id = '{$pet->getId()}'", "level ASC");
		foreach($res as $pedigree)
			$grii[] = $pedigree['pet_id_linked'];

		// search pets from the same species
		$pets = array();
		foreach($this->db->pet->getPets('array', "d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$name."%")." AND a.id <> '{$pet->getId()}' AND a.attribute_set_id = '{$pet->getAttributeSetId()}'") as $all)
			if(!in_array($all['id'], $grii))
				$pets[] = array('value' => $all['id'], 'text' => $all['name']);

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $pets));
	}

	/**
	 * Insert pedigree either by link or fictive name
	 */
	public function insert() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// check for all the values
		if(!isset($_POST['pet']) || !isset($_POST['level']) || !isset($_POST['type']) || !isset($_POST['value']))
			return false;

		// load pet
		$pet = $this->db->pet->find($_POST['pet']);
		if(!$pet->getId())
			return $this->deny();

		// not admin ? bye !
		if($this->auth->getIdentity()->id != $pet->getUserId())
			return $this->deny();

		// save pedigree
		$this->db->grii->setPetId($_POST['pet']);
		if($_POST['type'] == 0) $this->db->grii->setName($_POST['value']);
		else $this->db->grii->setPetIdLinked($_POST['value']);
    	$this->db->grii->setLevel($_POST['level'])->save();

		// return as success
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Update the name of a fictive pedigree
	 */
	public function update() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// check for all the values
		if(!isset($_POST['pet']) || !isset($_POST['id']) || !isset($_POST['value']))
			return false;

		// load pet
		$pet = $this->db->pet->find($_POST['pet']);
		if(!$pet->getId())
			return $this->deny();

		// not admin ? bye !
		if($this->auth->getIdentity()->id != $pet->getUserId())
			return $this->deny();

		// get pedigree
		$results = $this->db->grii->fetchList("id = {$_POST['id']} AND pet_id = {$_POST['pet']}");
		if(!$results)
			return $this->deny();

		// save pedigree
		$grii = reset($results);
		$grii->setName($_POST['value']);
		$grii->save();

		// return as success
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Deny access with message
	 * @param string $msg
	 */
	private function deny($msg = false) {
		Petolio_Service_Util::saveRequest();
		$this->msg->messages[] = $msg ? $msg : $this->translate->_("Please log in or sign up to access this page.");
		$this->helper->redirector('index', 'site');
	}

	/**
	 * Set levels
	 */
	private function setLevels() {
		// set levels
		$this->levels = array(
			'0' => $this->translate->_("Child"),
			'1' => $this->translate->_("Parent"),
			'2' => $this->translate->_("Grandparent"),
			'3' => $this->translate->_("Great-Grandparent")
		);
	}
}