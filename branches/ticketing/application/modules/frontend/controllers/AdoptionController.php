<?php

/**
 * Pets adoption controller ( /adoption )
 */
class AdoptionController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $auth = null;
    private $config = null;
    private $request = null;

    private $db = null;
	private $keyword = false;

    public function init()
    {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();

		$this->db = new stdClass();
		$this->db->pets = new Petolio_Model_PoPets();
		$this->db->members_pets = new Petolio_Model_PoServiceMembersPets();
		$this->db->interest = new Petolio_Model_PoInterest();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();
		$this->db->folders = new Petolio_Model_DbTable_PoFolders();
		$this->db->files = new Petolio_Model_PoFiles();
		$this->db->flag = new Petolio_Model_PoFlags();

		$this->view->auth = $this->auth;
		$this->view->request = $this->request;
		$this->view->action = $this->request->getParam('action');
 		$this->view->showAdoptionInterest = $this->showinterest();
    }

    public function preDispatch()
    {
		// load countries for searchbox
		$this->view->country_list = array();
		$countriesMap = new Petolio_Model_PoCountriesMapper();
		foreach($countriesMap->fetchAll() as $country)
			$this->view->country_list[$country->getId()] = $country->getName();

		// load category list
		$this->view->category_list = array();
		$cat = new Petolio_Model_PoUsersCategories();
		foreach($cat->getMapper()->fetchAll() as $category)
			if($category->getName() == "animal breeding" || $category->getName() == "animal shelter")
				$this->view->category_list[$category->getId()] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($category->getName()));
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
		if($this->view->search) {
			$search = array();

			if (strlen($this->request->getParam('keyword'))) {
				$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%")." OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%").")";
				$search[] = $this->request->getParam('keyword');

				// set keyword search
				$this->keyword = true;
			}

			if (strlen($this->request->getParam('country'))) {
				$filter[] = "x.country_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('country'), Zend_Db::BIGINT_TYPE);
				$search[] = $this->view->country_list[$this->request->getParam('country')];
			}

			if (strlen($this->request->getParam('zipcode'))) {
				$filter[] = "x.zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('zipcode')."%");
				$search[] = $this->request->getParam('zipcode');
			}

			if (strlen($this->request->getParam('address'))) {
				$filter[] = "x.address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('address')."%");
				$search[] = $this->request->getParam('address');
			}

			if (strlen($this->request->getParam('location'))) {
				$filter[] = "x.location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('location'))."%");
				$search[] = $this->request->getParam('location');
			}

			if (strlen($this->request->getParam('owner'))) {
				$filter[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('owner'))."%");
				$search[] = $this->request->getParam('owner');
			}

			if (strlen($this->request->getParam('category')))
				if($this->request->getParam('category') == 0) $filter[] = "x.category_id IS NULL";
				else $filter[] = "x.category_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('category'), Zend_Db::BIGINT_TYPE);

			if(count($search) > 0)
				$this->view->filter = implode(', ', $search);
		}

		return implode(' AND ', $filter);
	}

    /**
     * Render pet options (the left side menu)
     */
    private function petOptions($pet, $attr)
    {
		$this->view->pet = $pet;
		$this->view->pet_attr = $attr;
		$this->view->render('adoption/pet-options.phtml');
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
     * Load a pet by id.
     * @param int $petId
     * @param string $extra - extra condition
     */
    private function loadPet($petId, $extra = null)
    {
    	$result = $this->db->pets->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($petId, Zend_Db::BIGINT_TYPE)." AND deleted = '0' {$extra}");
    	if (!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else return reset($result);
    }

    /**
     * Reads the list of pets that can have adoption operations on them
     * and assambles the view
     *
     * @param array $where
     */
    private function loadPetsForAdoption($where)
    {
    	// filter by species
    	$species = $this->request->getParam('species');
    	$species = empty($species) ? null : $species;

    	// filter by species ?
    	$this->view->types = array();
    	foreach($this->db->sets->getAttributeSets('po_pets') as $type)
    		$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);

    	// build filter
    	$filter = $where;
	    if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
	    $filter = $this->buildSearchFilter($filter);

    	// search by ?
    	if($this->view->filter) {
    		$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
    		if (isset($species))
    			$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
    	} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
    	else $this->view->title = $this->translate->_("Pets for adoption");
    	
    	$this->view->selected_type = $this->translate->_("All");
    	if (isset($species)) {
    		$this->view->selected_type = $this->view->types[$species];
    	}

    	// get page
    	$page = $this->request->getParam('page');
    	$page = $page ? intval($page) : 0;

    	// do sorting 1
    	$this->view->order = $this->request->getParam('order');
    	$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
    	$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

    	// do sorting 2
    	if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
    	elseif($this->view->order == 'description') $sort = "d5.value {$this->view->dir}";
    	elseif($this->view->order == 'address'){
			if($this->translate->getLocale() == 'en')
				$sort = "user_address {$this->view->dir}, user_location {$this->view->dir}, user_zipcode {$this->view->dir}, user_country_id {$this->view->dir}";
			else
				$sort = "user_zipcode {$this->view->dir}, user_address {$this->view->dir}, user_location {$this->view->dir}, user_country_id {$this->view->dir}";
    	}
    	else $sort = "id {$this->view->dir}";

    	// get pets
    	$paginator = $this->db->pets->getPets('paginator', $filter, $sort, false, $this->keyword, true);
    	$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
    	$paginator->setCurrentPageNumber($page);

    	// output archived pets
    	$this->view->adoption = $this->db->pets->formatPets($paginator);
    }

    /**
     * View the pets main page
     */
    public function indexAction()
    {
    	// start
    	$this->view->search = true;

    	// filter by species ?
    	$types = array('All');
    	foreach($this->db->sets->getAttributeSets('po_pets') as $type) {
    		$types[$type['id']] = Petolio_Service_Util::Tr($type['name']);
    	}
    	
		// load pets for adoption
		$this->loadPetsForAdoption(array("a.deleted = '0'", "a.to_adopt = '1'"));
		
		$types[0] = $this->translate->_("All");
		$this->view->types = $types;
    }
    
    public function listAction() {
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true); // make sure the script is not being rendered
		
		$this->_redirect(PO_BASE_URL . 'adoption');
    	return;
    }

    /**
     * Displays the pets that the user has and has put for adoption
     */
    public function adoptionsAction()
    {
		// is user logged in or not ?
		$this->verifyUser();

    	// start
    	$this->view->search = true;
		$this->view->mine = true;

		// load pets for adoption
		$this->loadPetsForAdoption(array("a.user_id = {$this->auth->getIdentity()->id}", "a.deleted = '0'", "a.to_adopt = '1'"));
    }

    /**
     * View details about a pet
     */
    public function viewAction()
    {
    	// load pet
    	$pet = $this->loadPet($this->request->getParam('pet'));

    	// if flagged, load reasons
    	$this->view->flagged = array();
    	if($pet->getFlagged() == 1) {
    		$reasons = new Petolio_Model_PoFlagReasons();
    		$results = $this->db->flag->getMapper()->fetchList("scope = 'po_pets' AND entry_id = '{$pet->getId()}'");
    		foreach($results as $line) {
    			$reasons->find($line->getReasonId());
    			$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
    		}
    	}

    	// load species
    	$this->view->species = array();
    	foreach($this->db->sets->getAttributeSets('po_pets') as $type)
    		$this->view->species[$type['id']] = Petolio_Service_Util::Tr($type['name']);

    	// send to template
    	$this->view->pet = $pet;

    	// get the flag form
    	$this->view->flag = new Petolio_Form_Flag();

    	// get pet attributes
    	$this->view->attributes = reset($this->db->attr->loadAttributeValues($pet, true));

    	// get pet pictures
    	$pictures = array();
    	$gallery = $this->db->folders->findFolders(array('name' => 'gallery', 'petId' => $pet->getId()));
    	if(isset($gallery)) $pictures = $this->db->files->fetchList("folder_id = '{$gallery->getId()}'", "date_created ASC", 14);
    	if(isset($pictures) && count($pictures) > 0) {
    		$this->view->gallery = array();
    		foreach($pictures as $pic) {
    			$this->view->gallery[$pic->getId()] = $pic->getFile();
    		}
    	}

    	// get pet videos
    	$videos = array();
    	$media = $this->db->folders->findFolders(array('name' => 'videos', 'petId' => $pet->getId()));
    	if(isset($media)) $videos = $this->db->files->fetchList("folder_id = '{$media->getId()}'", "id ASC", 14);
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

    	// if pet is yours tell me :)
    	if(isset($this->auth->getIdentity()->id))
    		$this->view->admin = $pet->getUserId() == $this->auth->getIdentity()->id ? true : false;

    	// load menu
    	$this->petOptions($this->view->pet, $this->view->attributes);

    	// find owner
    	$this->db->user->find($this->view->pet->getUserId());

		// see if the pet owner is active and not banned
		if(!($this->db->user->getActive() == 1 && $this->db->user->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		// out with owner
    	$this->view->owner = $this->db->user->getName();
    }

    /**
     * Display a list of users which are interested in the current pet.
     */
    public function interestsAction()
    {
		// is user logged in ? no ? kick.
		$this->verifyUser();

		// load pet
		$petId = $this->request->getParam("pet");
		$pet = $this->loadPet($petId);

		if($pet->getUserId() != $this->auth->getIdentity()->id)
			return $this->_helper->redirector('index', 'site');

		// send to template
		$this->view->pet = $pet;

		// get pet attributes
		$this->view->attributes = reset($this->db->attr->loadAttributeValues($pet, true));

		// load menu
		$this->petOptions($this->view->pet, $this->view->attributes);

		// get interests
		$interests = $this->db->interest->fetchList("pet_id = ".$petId." "); // AND status = 0
		if (is_array($interests) && count($interests) > 0) {
			$where = "";
			$user_interest_statuses = array();
			foreach ($interests as $interest) {
				if (strlen($where) > 0)
					$where .= " OR ";

				$where .= "id = " . $interest->getUserId();
				$user_interest_statuses[$interest->getUserId()] = $interest->getStatus();
			}

			$users = $this->db->user->fetchList($where);
			if (!empty($users))
				ksort($users);

			$this->view->users = $users;
			$this->view->user_interest_statuses = $user_interest_statuses;
		}
    }

    /**
     * Pet transfer process - Step 2 (D)
     * User interest was removed by the pet owner
     */
    public function declineAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// pet vars
    	$id = $this->request->getParam("pet");
    	$user = $this->request->getParam("user");
    	if(!$id && !$user)
    		return $this->_helper->redirector('index', 'site');

    	// get pet
    	$pet = $this->loadPet($id);

    	// get pet attributes
    	$this->view->attributes = reset($this->db->attr->loadAttributeValues($pet, true));

    	// get user
    	$this->db->user->find($user);

    	// send user to template
    	$this->view->user = $this->db->user;

    	// find interest
    	$interest = reset($this->db->interest->fetchList("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND user_id = {$this->db->user->getId()}"));
    	if(!$interest)
    		return $this->_helper->redirector('index', 'site');

    	// init form
    	$form = new Petolio_Form_ResponseMessage();
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->request->isPost() && $this->request->getPost('submit')))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->request->getPost()))
    		return false;

    	// get form data
    	$data = $form->getValues();

    	// compile message
    	$html = sprintf(
    		$this->translate->_('%1$s removed your intrest in adopting %2$s'),
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true)}'>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</a>"
    	);
    	if (isset($data['message']) && strlen($data['message']) > 0) {
    		$html .= '<br /><br />' . sprintf(
    			$this->translate->_("%s has sent you the following message:"),
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
    		) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
    	}

    	// send message
    	Petolio_Service_Message::send(array(
    		'subject' => $this->translate->_("Your Pet Interested was removed by the pet owner!"),
    		'message_html' => $html,
    		'from' => $this->auth->getIdentity()->id,
    		'status' => 1,
    		'template' => 'default'
    		), array(array(
    			'id' => $this->db->user->getId(),
    			'name' => $this->db->user->getName(),
    			'email' => $this->db->user->getEmail()
    	)), $this->db->user->isOtherEmailNotification());

    	// remove interest
    	$interest->deleteRowByPrimaryKey();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("You have successfully deleted that intrested person");
    	return $this->_helper->redirector("interests", "adoption", "frontend", array('pet' => $pet->getId()));
    }

    /**
     * Pet transfer process - Step 3 (U)
     * Transfers pet to another person and sends a message/e-mail to that person
     */
    public function transferAction()
    {
		// is user logged in ? no ? kick
		$this->verifyUser();

		// check if a transfer request is already sent
		$result = $this->db->interest->fetchList("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND status = '1'");
		if (is_array($result) && count($result) > 0) {
			$this->msg->messages[] = $this->translate->_("You already transfered this pet to somebody else. Please cancel that transfer first.");
			return $this->_redirect('adoption/interests/pet/'. $this->request->getParam('pet'));
		}

		// update interest
		$this->updateInterestStatus($this->request->getParam('pet'), $this->request->getParam('user'), 0, 1);

		// find user
		$this->db->user->find($this->request->getParam('user'));

		// load pet
		$pet = $this->loadPet($this->request->getParam('pet'));

		// get pet attributes
		$pet_attributes = reset($this->db->attr->loadAttributeValues($pet, true));
		$pets_name = $pet_attributes['name']->getAttributeEntity()->getValue();

		// send message
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Pet transfer"),
			'message_html' =>
				sprintf(
					$this->translate->_('%1$s has transferred %2$s to you!'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user'=>$this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$this->request->getParam('pet')), 'default', true)}'>{$pets_name}</a>"
				) .	'<br /><br />' .
				sprintf(
					$this->translate->_('%s to see your pet interests and accept or decline the transfer.'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'interested'), 'default', true)}'>".$this->translate->_('Click here')."</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
    		'template' => 'default'
		), array(array(
			'id' => $this->db->user->getId(),
			'name' => $this->db->user->getName(),
			'email' => $this->db->user->getEmail()
		)), $this->db->user->isOtherEmailNotification());

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("Transfer request has been sent to %s."), $this->db->user->getName());
		return $this->_helper->redirector("interests", "adoption", "frontend", array('pet' => $this->request->getParam('pet')));
    }

    /**
     * Pet transfer process - Step 3 (U)
     * Cancels the transfer to a user to be able to transfer it to someone else
     */
    public function cancelAction()
    {
		// is user logged in ? no ? kick
		$this->verifyUser();

		// update interest
		$this->updateInterestStatus($this->request->getParam('pet'), $this->request->getParam('user'), 1, 0);

		// get user
		$this->db->user->find($this->request->getParam('user'));

		// load pet
		$pet = $this->loadPet($this->request->getParam('pet'));

		// get pet attributes
		$pet_attributes = reset($this->db->attr->loadAttributeValues($pet, true));
		$pets_name = $pet_attributes['name']->getAttributeEntity()->getValue();

		// send message
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Pet transfer cancelled"),
			'message_html' =>
				sprintf(
					$this->translate->_('%1$s has cancelled the transfer of %2$s to you!'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user'=>$this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$this->request->getParam('pet')), 'default', true)}'>{$pets_name}</a>"
				) .	'<br /><br />' .
				sprintf(
					$this->translate->_('%s to see your pet interests.'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'interested'), 'default', true)}'>".$this->translate->_('Click here')."</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $this->db->user->getId(),
			'name' => $this->db->user->getName(),
			'email' => $this->db->user->getEmail()
		)), $this->db->user->isOtherEmailNotification());

		$this->msg->messages[] = sprintf($this->translate->_("Transfer request to %s has been cancelled."), $this->db->user->getName());
		return $this->_helper->redirector("interests", "adoption", "frontend", array('pet' => $this->request->getParam('pet')));
    }

    /**
     * Pet transfer process - Step 1 (D)
     * Removes one of your pets from the adoption list
     */
    public function restoreAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// get pet
    	$pet = $this->loadPet($this->request->getParam('pet'), "AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0' AND to_adopt = '1'");

    	// set to unadopted
    	$pet->setToAdopt(0);
    	$pet->save();

    	// set the _sale attribute to "No"
    	$attributes = new Petolio_Model_PoAttributes();
    	$attr_result = reset($attributes->fetchList("attribute_set_id = '{$pet->getAttributeSetId()}' AND code LIKE '%_sale%'"));
    	if($attr_result)
    		$attributes->getMapper()->getDbTable()->saveAttributeValues(array($attr_result->getCode() => 0), $pet->getId());

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your pet has been restored successfully.");
    	return $this->_helper->redirector('index', 'adoptions', 'adoption');
    }

    /**
     * Display a list of pets for which the logged in user is interested to adopt.
     */
    public function interestedAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// get requested pets
    	$this->view->stats = $this->selectRequestedPetIds(array('0', '1'));
    	if (is_null($this->view->stats)) {
    		$this->msg->messages[] = $this->translate->_("You are not interested in any adoption.");
    		return $this->_helper->redirector('index', 'site');
    	}

    	// start
    	$this->view->search = true;

    	// filter by species
    	$species = $this->request->getParam('species');
    	$species = empty($species) ? null : $species;

    	// filter by species ?
    	$this->view->types = array();
    	foreach($this->db->sets->getAttributeSets('po_pets') as $type)
    		$this->view->types[$type['id']] = Petolio_Service_Util::Tr($type['name']);

    	// build filter
    	$ids = array();
    	$filter = array("a.deleted = 0");
    	foreach ($this->view->stats as $id => $s)
    		$ids[] = "a.id = {$id}";

    	$filter[] = "(" . implode(' OR ', $ids) . ")";
    	if(!is_null($species)) $filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
    	$filter = $this->buildSearchFilter($filter);

    	// search by ?
    	if($this->view->filter) {
    		$this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
    		if (isset($species))
    			$this->view->title .= " " . $this->translate->_("and Type:") . " " . $this->view->types[$species];
    	} elseif(isset($species)) $this->view->title = $this->translate->_("Results, Type:") . " " . $this->view->types[$species];
    	else $this->view->title = $this->translate->_("My adoption interests");

    	// get page
    	$page = $this->request->getParam('page');
    	$page = $page ? intval($page) : 0;

    	// get pets
    	$paginator = $this->db->pets->getPets('paginator', $filter, "id DESC", false, $this->keyword);
    	$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
    	$paginator->setCurrentPageNumber($page);

    	// output intrested pets
    	$this->view->pets = $this->db->pets->formatPets($paginator);
    }

    /**
     * Pet transfer process - Step 2 (D)
     * User is no longer intereted in adopting a pet
     */
    public function removeAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// get pet
    	$pet = $this->loadPet($this->request->getParam('pet'));

    	// send pet to template
    	$this->view->pet = $pet;

    	// get pet attributes
    	$this->view->attributes = reset($this->db->attr->loadAttributeValues($pet, true));

    	// find interest
    	$interest = reset($this->db->interest->fetchList("pet_id = {$pet->getId()} AND user_id = {$this->auth->getIdentity()->id}"));
    	if(!$interest)
    		return $this->_helper->redirector('index', 'site');

    	// init form
    	$form = new Petolio_Form_ResponseMessage();
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->request->isPost() && $this->request->getPost('submit')))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->request->getPost()))
    		return false;

    	// get form data
    	$data = $form->getValues();

    	// find pet owner
    	$this->db->user->find($pet->getUserId());

    	// build message
    	$html = sprintf(
    		$this->translate->_('%1$s is no longer intrested in adopting %2$s'),
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true)}'>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</a>"
    	) . '<br /><br />';
    	if (isset($data['message']) && strlen($data['message']) > 0) {
    		$html .= sprintf(
    			$this->translate->_("%s has sent you the following message:"),
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
    		) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message'])) . "<br/><br/>";
    	}
    	$html .= sprintf(
    		$this->translate->_('You can %1$s all of the interests for %2$s'),
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'interests', 'pet'=>$pet->getId()), 'default', true)}'>".$this->translate->_('View')."</a>",
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true)}'>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</a>"
    	);

    	// send message
    	Petolio_Service_Message::send(array(
	    	'subject' => $this->translate->_("Sorry, I'm no longer interested in adopting your pet!"),
	    	'message_html' => $html,
	    	'from' => $this->auth->getIdentity()->id,
	    	'status' => 1,
			'template' => 'default'
	    	), array(array(
		    	'id' => $this->db->user->getId(),
		    	'name' => $this->db->user->getName(),
		    	'email' => $this->db->user->getEmail()
    	)), $this->db->user->isOtherEmailNotification());

    	// remove interest
    	$interest->deleteRowByPrimaryKey();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your interest for that pet has been removed.");
    	return $this->_helper->redirector('interested', 'adoption');
    }

    /**
     * Pet transfer process - Step 4 (U)
     * User accepts a pet transfer, so the pet is transferred to the new user and a
     * message is being sent to the previous owner
     *
     * @author ?, Modif: Wavetrex 2011.09.30
     */
    public function acceptAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// remove all the interests
    	$this->db->interest->delete("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE));

    	// get pet
    	$pet = $this->loadPet($this->request->getParam('pet'));

    	// find previous owner Id
    	$previousUserId = $pet->getUserId();

    	// transfer the pet by setting the acceping user's id to the pet
    	$pet->setUserId($this->auth->getIdentity()->id);
    	$pet->setToAdopt(0);
    	$pet->setDateModified(date('Y-m-d H:i:s', time()));
    	$pet->save(false);

    	// set the _sale attribute to "No"
    	$attributes = new Petolio_Model_PoAttributes();
    	$attr_result = reset($attributes->fetchList("attribute_set_id = '{$pet->getAttributeSetId()}' AND code LIKE '%_sale%'"));
    	if($attr_result)
    		$attributes->getMapper()->getDbTable()->saveAttributeValues(array($attr_result->getCode() => 0), $pet->getId());

    	// set other owner_id's to the new owner
    	$folders = new Petolio_Model_PoFolders();
    	$folder_results = $folders->fetchList("pet_id = {$pet->getId()}");
    	$files = new Petolio_Model_PoFiles();
    	foreach ($folder_results as $folder) {
    		$files->update(array("owner_id" => $this->auth->getIdentity()->id), "folder_id = {$folder->getId()}");
    	}
    	$folders->update(array("owner_id" => $this->auth->getIdentity()->id), "pet_id = {$pet->getId()}");

    	$medical_records = new Petolio_Model_PoMedicalRecords();
    	$medical_record_results = $medical_records->fetchList("pet_id = {$pet->getId()}");
    	$subentries = new Petolio_Model_PoMedicalRecordSubentries();
    	foreach ($medical_record_results as $medical_record) {
    		$subentries->update(array("owner_id" => $this->auth->getIdentity()->id), "medical_record_id = {$medical_record->getId()} AND owner_id = {$previousUserId}");
    	}
    	$medical_records->update(array("owner_id" => $this->auth->getIdentity()->id), "pet_id = {$pet->getId()}");

    	// get pet attributes->name
    	$pet_attributes = reset($this->db->attr->loadAttributeValues($pet, true));
    	$pets_name = $pet_attributes['name']->getAttributeEntity()->getValue();

    	// get previous owner data
    	$this->db->user->find($previousUserId);

    	// send message to the previous owner
    	Petolio_Service_Message::send(array(
	    	'subject' => $this->translate->_("Pet transfer accepted"),
	    	'message_html' =>
	    	sprintf(
		    	$this->translate->_('%1$s has accepted the transfer of %2$s !'),
		    	"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user'=>$this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
		    	"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet'=> $this->request->getParam('pet')), 'default', true)}'>{$pets_name}</a>"
	    	),
	    	'from' => $this->auth->getIdentity()->id,
	    	'status' => 1,
			'template' => 'default'
    	), array(array(
    		'id' => $this->db->user->getId(),
    		'name' => $this->db->user->getName(),
    		'email' => $this->db->user->getEmail()
    	)), $this->db->user->isOtherEmailNotification());

    	// queue notification message and redirect
    	$this->msg->messages[] = sprintf($this->translate->_("%s has been notified that you accepted %s"), $this->db->user->getName(), $pets_name);
    	return $this->_helper->redirector("view", "pets", "frontend", array('pet' => $pet->getId()));
    }

    /**
     * Pet transfer process - Step 4 (U)
     * User denies a pet transfer
     */
    public function denyAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// delete interest for pet from current user
    	$this->db->interest->delete("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND user_id = {$this->auth->getIdentity()->id}" );

    	// load pet
    	$pet = $this->loadPet($this->request->getParam('pet'));

    	// get pet attributes->name
    	$pet_attributes = reset($this->db->attr->loadAttributeValues($pet, true));
    	$pets_name = $pet_attributes['name']->getAttributeEntity()->getValue();

    	// get current pet owner data
    	$this->db->user->find($pet->getUserId());

    	// send message to the previous owner
    	Petolio_Service_Message::send(array(
    		'subject' => $this->translate->_("Pet transfer declined"),
    		'message_html' =>
	    	sprintf(
		    	$this->translate->_('%1$s has declined the transfer of %2$s !'),
		    	"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user'=>$this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
		    	"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=> $this->request->getParam('pet')), 'default', true)}'>{$pets_name}</a>"
	    	),
	    	'from' => $this->auth->getIdentity()->id,
	    	'status' => 1,
			'template' => 'default'
    	), array(array(
    		'id' => $this->db->user->getId(),
    		'name' => $this->db->user->getName(),
    		'email' => $this->db->user->getEmail()
    	)), $this->db->user->isOtherEmailNotification());

    	// queue notification message and redirect
    	$this->msg->messages[] = sprintf($this->translate->_("Transfer request has been denied, and %s has been notified of your action."), $this->db->user->getName());
    	return $this->_helper->redirector('interested', 'adoption');
    }

    /**
     * Pet transfer process - Step 2 (C)
     * User shows interest for adopting a pet from the list
     */
    public function interestAction()
    {
    	// is user logged in ? no ? kick
    	$this->verifyUser();

    	// load pet
    	$pet = $this->loadPet($this->request->getParam('pet'));

    	// send pet to template
    	$this->view->pet = $pet;

    	// get pet attributes
    	$this->view->attributes = reset($this->db->attr->loadAttributeValues($pet, true));

    	// own pet ? redirect
    	if ($pet->getUserId() == $this->auth->getIdentity()->id) {
    		$this->msg->messages[] = $this->translate->_("You cannot adopt your own pet.");
    		return $this->_helper->redirector('index', 'adoption');
    	}

    	// already interested in adopting this pet ? redirect
    	$result = $this->db->interest->fetchList("user_id = {$this->auth->getIdentity()->id} AND pet_id = {$pet->getId()}");
    	if (count($result) > 0) {
    		$this->msg->messages[] = $this->translate->_("You already have an interest for this pet.");
    		return $this->_helper->redirector('index', 'adoption');
    	}

    	// init form
    	$form = new Petolio_Form_ResponseMessage();
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->request->isPost() && $this->request->getPost('submit')))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->request->getPost()))
    		return false;

    	// get form data
    	$data = $form->getValues();

    	// find pet owner
    	$this->db->user->find($pet->getUserId());

    	// build message
    	$html = sprintf(
    		$this->translate->_('%1$s is intrested in adopting %2$s'),
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true)}'>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</a>"
    		) . '<br /><br />';
    	if (isset($data['message']) && strlen($data['message']) > 0) {
    		$html .= sprintf(
    			$this->translate->_("%s has sent you the following message:"),
    			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
    		) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message'])) . "<br/><br/>";
    	}
    	$html .= sprintf(
    		$this->translate->_('You can %1$s all of the interests for %2$s'),
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'interests', 'pet'=>$pet->getId()), 'default', true)}'>".$this->translate->_('View')."</a>",
    		"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'adoption', 'action'=>'view', 'pet'=>$pet->getId()), 'default', true)}'>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</a>"
    	);

    	// send message
    	Petolio_Service_Message::send(array(
	    	'subject' => $this->translate->_('Hi, I want to adopt your pet!'),
	    	'message_html' => $html,
	    	'from' => $this->auth->getIdentity()->id,
	    	'status' => 1,
			'template' => 'default'
    	), array(array(
	    	'id' => $this->db->user->getId(),
	    	'name' => $this->db->user->getName(),
	    	'email' => $this->db->user->getEmail()
    	)), $this->db->user->isOtherEmailNotification());

    	// save interest
    	$this->db->interest->setPetId($pet->getId());
    	$this->db->interest->setUserId($this->auth->getIdentity()->id);
    	$this->db->interest->save(true, true);

    	// display message
    	$this->msg->messages[] = $this->translate->_("Your adoption interest for the pet have been sent.");
    	return $this->_helper->redirector('index', 'adoption');
    }

    /**
     * Update interest status
     *
     * @param int $petId
     * @param int $userId
     * @param int $currentStatus
     * @param int $nextStatus
     */
    private function updateInterestStatus($petId, $userId, $currentStatus, $nextStatus)
    {
		// find interest
		$result = $this->db->interest->fetchList("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($petId, Zend_Db::BIGINT_TYPE)." AND user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($userId, Zend_Db::BIGINT_TYPE)." AND status = '{$currentStatus}'");
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Pet interest does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else
			$interest = reset($result);

		// save with status
		$interest->setStatus($nextStatus);
		$interest->save(false);
    }

    /**
     * Return a list of pet ids which are selected for adoption and the adoption status
     * is in one of the possibled $statuses.
     *
     * @param array $adoptionStatuses A list of adoption status.
     * 		0 - user is interested
     * 		1 - user requested the transfer
     * 		2 - transfered
     */
    private function selectRequestedPetIds($statuses)
    {
		// find interests
		$interests = $this->db->interest->fetchList("user_id = '{$this->auth->getIdentity()->id}' and status IN ('".implode("','", $statuses)."')");

		// return pet ids
		if (is_array($interests) && count($interests) > 0) {
			$petIds = array();
			foreach ($interests as $interest)
				$petIds[$interest->getPetId()] = array(
					"pet_id" => $interest->getPetId(),
					"status" => $interest->getStatus()
				);

			return $petIds;
		}

		// return null if nothing found
		return null;
    }

    /**
     * Show adoption interest
     */
    private function showinterest()
    {
		if ($this->auth->hasIdentity()) {
			$intrested = $this->db->interest->fetchList("user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->auth->getIdentity()->id, Zend_Db::BIGINT_TYPE)." AND (status = 1 OR status = 0)");
			if (is_array($intrested) && count($intrested) > 0)
				return true;
		}

		return false;
    }
}