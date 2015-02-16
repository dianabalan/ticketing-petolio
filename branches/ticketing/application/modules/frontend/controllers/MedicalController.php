<?php

class MedicalController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $up = null;
    private $auth = null;
    private $config = null;
    private $request = null;

	private $db = null;

	/**
	 * Init Controller
	 */
    public function init() {
    	// get basic stuff
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();

		// get db objects
		$this->db = new stdClass();
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->interest = new Petolio_Model_PoInterestMapper();
		$this->db->attr = new Petolio_Model_PoAttributes();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->cale = new Petolio_Model_PoCalendar();

		$this->db->records = new Petolio_Model_PoMedicalRecords();
		$this->db->subentries = new Petolio_Model_PoMedicalRecordSubentries();
		$this->db->rights = new Petolio_Model_PoMedicalRecordRights();

		$this->db->files = new Petolio_Model_PoFiles();
		$this->db->folders = new Petolio_Model_PoFolders();
		$this->db->f_rights = new Petolio_Model_PoFileRights();

		$this->db->s_m_pets = new Petolio_Model_PoServiceMembersPets();

		// send to view
		$this->view->auth = $this->auth;
		$this->view->showAdoptionInterest = $this->showAdoptionInterest();
    }

	/**
	 * Runs after action method
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

    /**
     * Logged in redirector
     * denies access to certain pages when the user is not logged in
     */
    private function verifyUser() {
		// not logged in
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}
    }

    /**
     * Returns true if interested in adopt pets
     */
    private function showAdoptionInterest() {
    	if($this->auth->hasIdentity()) {
    		$intrested = $this->db->interest->fetchList("user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->auth->getIdentity()->id, Zend_Db::BIGINT_TYPE)." AND (status = 1 OR status = 0)");
    		if(is_array($intrested) && count($intrested) > 0)
    			return true;
    	}

    	return false;
    }

    /**
     * Load friends and partners
     * @param int $id - User Id
     * @param string $what - friends / partners
     *
     * @return array of friends or partners
     */
    private function loadFriendsAndPartners($id, $what = 'friends') {
		// load user's friends and partners
		$this->db->user->find($id);
		$all = $what == 'friends' ? $this->db->user->getUserFriends() : $this->db->user->getUserPartners();

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		// return
		return $result;
    }

    /**
     * So is the medical record private or not ??
     *
     * @param int $rights - Medical Record Right
     * @param int $id - Medical Record Id
     * @param int $owner - Medical Record Owner
     *
     * @return bool
     */
    private function isPrivate($rights, $id, $owner) {
		// lets say its not private at first
		$private = false;

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
					foreach($this->db->rights->getMapper()->findByField('medical_record_id', $id, null) as $user)
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
					foreach($this->db->rights->getMapper()->findByField('medical_record_id', $id, null) as $user)
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
					foreach($this->db->rights->getMapper()->findByField('medical_record_id', $id, null) as $user)
						$allowed[] = $user->getUserId();

					if(!in_array($this->auth->getIdentity()->id, $allowed))
						$private = true;
				break;
			}
		}

		return $private;
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
     * Returns an array of PoUsers who have access to the medical record
     * @param Medical Record obj
     *
     * @return array of PoUsers
     */
    private function getAllowedUsers($mr, $load_everyone = false) {
		$allowed_users = array();
		$owner = $this->db->user->find($mr->getOwnerId());

		// switch between rights
		switch($mr->getRights()) {
			// nobody/private
			case 0:
			break;

			// everyone/public access
			case 1:
				// load all friends and partners
				if($load_everyone)
					$allowed_users = array_merge($owner->getUserFriends(), $owner->getUserPartners());
			break;

			// partners
			case 2:
				// loop through allowed users
				$allowed = array();
				foreach($this->db->rights->getMapper()->findByField('medical_record_id', $mr->getId(), null) as $user)
					$allowed[] = $user->getUserId();

				if(!$allowed) $allowed_users = $owner->getUserPartners();
				else {
					foreach($owner->getUserPartners() as $partner)
						if(in_array($partner->getId(), $allowed))
							$allowed_users[] = $partner;
				}
			break;

			// friends
			case 3:
				// loop through allowed users
				$allowed = array();
				foreach($this->db->rights->getMapper()->findByField('medical_record_id', $mr->getId(), null) as $user)
					$allowed[] = $user->getUserId();

				if(!$allowed) $allowed_users = $owner->getUserFriends();
				else {
					foreach($owner->getUserFriends() as $friend)
						if(in_array($friend->getId(), $allowed))
							$allowed_users[] = $friend;
				}
			break;

			// users
			case 4:
				// loop through allowed users
				$allowed = array();
				foreach($this->db->rights->getMapper()->findByField('medical_record_id', $mr->getId(), null) as $user)
					$allowed[] = $user->getUserId();

				if(!$allowed) $allower_users = array();
				else $allowed_users = $owner->getMapper()->fetchList("id IN (".implode(',', $allowed).")");
			break;
		}

		return $allowed_users;
    }

	/**
	 * Get record or throw message and redirect
	 */
	private function getRecord($id, $x = false, $z = false) {
		$msg = $this->translate->_("Medical record not found.");
		$record = $this->db->records->find($id);
		if(!$record->getId()) {
			// json powered
			if($x) {
				$x['html'] = $msg;
				return Petolio_Service_Util::json($x);

			// 2nd json apparently
			} elseif($z) {
				$return['success'] = false;
				return Petolio_Service_Util::json($return);

			// normal blabber
			} else {
				$this->msg->messages[] = $msg;
				return $this->_helper->redirector('index', 'site');
			}
		}

		return $record;
	}

	/**
	 * Get subentry or throw message and redirect
	 */
	private function getSubentry($id) {
		$subentry = $this->db->subentries->findWithMedicalRecord($id);
		if(!$subentry->getId()) {
			$this->msg->messages[] = $this->translate->_("Medical Record Subentry not found.");
			return $this->_helper->redirector('index', 'site');
		}

		return $subentry;
	}

	/**
	 * Get the Pet
	 */
	private function getPet($module = 'index') {
		// change render
		$this->_helper->viewRenderer->setRender('add');

		// get form
		$form = new Petolio_Form_PetFind();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// redirect back to add
		return $this->_helper->redirector($module, 'medical', 'frontend', array('pet' => $data['pet_id']));
	}

    /**
     * Render pet options (the left side menu)
     */
    private function petOptions($pet, $attr) {
		$this->view->pet = $pet;
		$this->view->pet_attr = $attr;
		$this->view->render('pets/pet-options.phtml');
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
	 * View All Medical Records for a pet
	 */
    public function indexAction() {
    	// check if logged in
    	$this->verifyUser();

		// get pet
		$pet = $this->db->pet->find($this->request->getParam('pet'));
		if(!$pet->getId())
			return $this->getPet();

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// load page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

    	// do sorting 2
		if($this->view->order == 'start') $sort = "start_date {$this->view->dir}";
		elseif($this->view->order == 'end') $sort = "end_date {$this->view->dir}";
		elseif($this->view->order == 'rights') $sort = "rights {$this->view->dir}";
		else {
			$this->view->order = 'headline';
			$sort = "headline1 {$this->view->dir}";
		}

		// load filter
		$filter = "pet_id = {$pet->getId()}";

		// get records
		$paginator = $this->db->records->select2Paginator($this->db->records->getMapper()->getDbTable()->fetchListWithRightsCount($filter, $sort));
		$paginator->setItemCountPerPage($this->config["messages"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// compute records
		$this->view->records = array();
		foreach($paginator->getItemsByPage($page) as $row) {
			$entry = clone $this->db->records;
			$entry->setId($row["id"])
				->setOwnerId($row["owner_id"])
				->setPetId($row["pet_id"])
				->setHeadline1($row["headline1"])
				->setHeadline2($row["headline2"])
				->setDescription($row["description"])
				->setStartDate($row["start_date"])
				->setEndDate($row["end_date"])
				->setFolderId($row["folder_id"])
				->setDateCreated($row["date_created"])
				->setDateModified($row["date_modified"])
				->setRights($this->getAccess($row["rights"], $row['users']));

			$this->view->records[] = $entry;
		}

		// send to template
		$this->view->paginator = $paginator;
		$this->view->admin = ($this->auth->hasIdentity() && $this->auth->getIdentity()->id == $pet->getUserId()) ? true : false;

		// mass edit access / delete
		if(isset($_POST['mass_action']))
			$this->mass($_POST['mass_action'], $this->view->admin, $pet);

		// access single record only
		if($this->request->getParam('access'))
			$this->access($this->view->admin, $pet);
    }

	/**
	 * Mass edit or delete
	 * @param string $what - sel_edit / sel_delete
	 * @param bool $admin
	 * @param obj $pet
	 */
	private function mass($what, $admin = false, $pet) {
		// get from post
		$records = (array)@$_POST['mass_records'];

		// no admin ? bye
		if(!$admin) {
			$this->msg->messages[] = $this->translate->_("You do not have access to edit or delete medical records that belong to this pet.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $pet->getId()));
		}

		// send to apropriate action
		switch($what) {
			// mass delete
			case 'sel_delete':
				// go through each
				foreach($records as $id) {
					// find medical record
					$record = $this->db->records->find($id);
					if($record->getId()) {
						// see if owner
						if($record->getOwnerId() == $this->auth->getIdentity()->id) {
							// update to deleted
							$record->setDeleted(1);
							$record->save();

							// set message
							$this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" deleted successfully."), $record->getHeadline1());

						// not owner? set message
						} else $this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" cannot be deleted. Only the owner of the medical record can delete it."), $record->getHeadline1());
					}
				}

				// redirect when finished
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? preg_replace("/\/access\/(\d+)/i", "", $_SERVER['HTTP_REFERER']) : "medical/index/pet/{$pet->getId()}");
			break;

			// mass edit
			case 'sel_edit':
				// fill selection
				$sel = array();
				foreach($records as $record)
					$sel[] = "record|{$record}";

				// send to template
				$this->view->mass_selection = isset($_POST['mass_selection']) ? $_POST['mass_selection'] : implode(',', $sel);
				$this->view->mass_action = $what;

				// send friends and partners to dropdowns
				$this->view->friends = $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'friends');
				$this->view->partners = $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'partners');

				// just one selected, switch to access
				if(count($sel) == 1)
					return $this->_redirect("medical/index/pet/{$pet->getId()}/access/{$records[0]}");

				// multiple selected
				else {
					// fill form
					$this->view->rights = array(
						'access' => 0,
						'users_id' => array(),
						'users_id_name' => ''
					);
				}

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
				$record = array();
				foreach(explode(',', $_POST['mass_selection']) as $item) {
					list($type, $id) = explode('|', $item);
						${$type}[] = $id;
				}

				// update access to selected records
				$this->updateRecordAccess($record, $this->rightsSrc($_POST));

				// msg and redirect
				$this->msg->messages[] = $this->translate->_("The access rights for your selected records have been successfully updated.");
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? preg_replace("/\/access\/(\d+)/i", "", $_SERVER['HTTP_REFERER']) : "medical/index/pet/{$pet->getId()}");
			break;

			// action not found ?
			default:
				$this->msg->messages[] = $this->translate->_("Invalid Request.");
				return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $pet->getId()));
			break;
		}
	}

	/*
	 * Access action (for files)
	 */
	private function access($admin = false, $pet) {
		// assign var from param
		$id = (int)$this->request->getParam('access');

		// must not be accessible if this exists
		if(isset($_POST['mass_action']))
			return false;

		// no id ? bye !
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("Invalid Request.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $pet->getId()));
		}

		// no admin ? bye
		if(!$admin) {
			$this->msg->messages[] = $this->translate->_("You do not have access to edit or delete medical records that belong to this pet.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $pet->getId()));
		}

		// fill selection
		$sel = array();
		$sel[] = "record|{$id}";

		// send to template
		$this->view->mass_selection = implode(',', $sel);
		$this->view->mass_action = 'sel_edit';

		// send friends and partners to dropdowns
		$this->view->friends = $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'friends');
		$this->view->partners = $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'partners');

		// get record
		$record = $this->getRecord($id);

		// set default variables
		$users_id = array();
		$users_id_name = array();

		// if access right is 2, 3 or 4 then try and fill the users
		if($record->getRights() > 1) {
			foreach($this->db->rights->getMapper()->findAllowedUsers($record->getId()) as $user) {
				// fill for friends and partners (2, 3)
				$users_id[] = $user->getId();

				// fill for users (4)
				if($record->getRights() == 4)
					$users_id_name[] = $user->getId() . '|' . $user->getName();
			}
		}

		// fill form
		$this->view->rights = array(
			'access' => $record->getRights(),
			'users_id' => $users_id,
			'users_id_name' => implode(',', $users_id_name)
		);

		// this will end up back at mass
	}

	/**
	 * Add Medical Record
	 */
    public function addAction() {
    	// check if logged in
		$this->verifyUser();

		// get pet
		$pet = $this->db->pet->find($this->request->getParam('pet'));
		if(!$pet->getId())
			return $this->getPet('add');

		// check if the logged in user is the owner of the pet or a service provider
		$options = array();
		$options['owner_id'] = $this->auth->getIdentity()->id;
		$options['pet_id'] = $pet->getId();

		// if not owner
		if($pet->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You don't have access to add a medical record to this pet.");
			return $this->_helper->redirector('view', 'medical', 'frontend', array('pet' => $pet->getId()));
		}

		// get form
		$form = new Petolio_Form_MedicalRecord($options);
		$this->view->form = $form;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// did we submit form ? if not just return here
		if(!($this->request->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && ($idx == 'start_date' || $idx == 'end_date')) {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// insert medical record
		$record = clone $this->db->records;
		$record->setOwnerId($data['owner_id']);
		$record->setPetId($data['pet_id']);
		$record->setHeadline1($data['headline1']);
		$record->setHeadline2($data['headline2']);
		$record->setStartDate($data['start_date']);
		$record->setEndDate($data['end_date']);
		$record->setDescription($data['description']);
		$record->setRights(0);
		$record->save(true, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Medical Record saved successfully.");
		return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
    }

	/**
	 * Edit Medical Record
	 */
    public function editAction() {
    	// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->record = $record;

		// check if the currently logged in user has access to view this medical record
		$private = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if medical record is private
		if($private) {
			$this->msg->messages[] = $this->translate->_("You do not have access to this medical record.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// check if the logged in user is the owner of the medical record
		$options = array();
		$options['owner_id'] = $record->getOwnerId();
		$options['pet_id'] = $record->getPetId();
		if(!($record->getOwnerId() == $this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You don't have access to edit this medical record.");
			return $this->_helper->redirector('view', 'medical', 'frontend', array('id' => $record->getId()));
		}

		// get form
		$form = new Petolio_Form_MedicalRecord($options);
		$form->populate($record->toArray());
		$this->view->form = $form;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// did we submit form ? if not just return here
		if(!($this->request->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && ($idx == 'start_date' || $idx == 'end_date')) {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// check if we need to update folder names
		if($record->getHeadline1() != $data['headline1']) {
			// upload location
			$ds = DIRECTORY_SEPARATOR;
			$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$record->getPetId()}{$ds}medical records";

			// find record folder
			$folder = $this->db->folders->getMapper()->getDbTable()->findFolders(array('id' => $record->getFolderId(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id));
			if(!is_null($folder)) {
				// rename on disk and in db
				rename("{$upload_dir}{$ds}{$folder->getName()}", "{$upload_dir}{$ds}{$data['headline1']}");
				$folder->setName($data['headline1'])->save();
			}
		}

		// update medical record
		$record->setOwnerId($data['owner_id']);
		$record->setPetId($data['pet_id']);
		$record->setHeadline1($data['headline1']);
		$record->setHeadline2($data['headline2']);
		$record->setStartDate($data['start_date']);
		$record->setEndDate($data['end_date']);
		$record->setDescription($data['description']);
		$record->save(false, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Medical Record saved successfully.");
		return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
    }

	/**
	 * Attach a file to a Medical Record
	 */
    public function attachAction() {
       	// check if logged in
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->record = $record;

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// check if the logged in user is the owner of the pet or a service provider
		if(!($record->getOwnerId() == $this->auth->getIdentity()->id)) {
			// only accepted links
			$is_service_provider = false;
			foreach($this->db->s_m_pets->getPetServices($record->getPetId(), 1) as $service)
				if($service->getUserId() == $this->auth->getIdentity()->id)
					$is_service_provider = true;

			// not service provider? message and redirect
			if(!$is_service_provider) {
				$this->msg->messages[] = $this->translate->_("You don't have access to attach files to this medical record.");
				return $this->_helper->redirector('view', 'pets', 'frontend', array('pet' => $record->getPetId()));
			}
		}

		// get all files
		if($record->getFolderId())
			$this->view->files = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $record->getOwnerId(), 'folderId' => $record->getFolderId()), true);

		// get form
		$form = new Petolio_Form_AttachMedicalRecordFiles();
		$this->view->form = $form;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$record->getPetId()}{$ds}medical records{$ds}{$record->getHeadline1()}{$ds}";

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create a medical record folder for our pet if not exists
		$root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'name' => 'medical records'));
		if(is_null($root)) {
			$pet_folder = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'parentId' => 0));
			$root = $this->db->folders->getMapper()->getDbTable()->addFolder(array('name' => 'medical records', 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => is_null($pet_folder) ? 0 : $pet_folder->getId()));
		}

		// create this medical record folder if not exists
		$vars = array('name' => $record->getHeadline1(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $root->getId());
		$folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($folder))
			$folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// save folder id
		$record->setFolderId($folder->getId());
		$record->save();

		// create the medical_records directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the medical records's folder on disk.")));
				return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
			}
		}

		// create this medical_record directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the medical record's folder on disk.")));
				return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
			}
		}

		// prepare upload files
		$i = 0;
		$errors = array();
		$success = array();

		// get addapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('Extension', false, array('doc', 'xls', 'jpg', 'gif', 'png', 'txt', 'avi', 'mpg', 'mpeg', 'mp3', 'pdf', 'rar', 'zip'));

		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if(!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your file / files exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
				return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
			}
		}

		// upload each file
		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

			$adapter->clearFilters();
			$adapter->addFilter('Rename', array('target' => $upload_dir . $new_filename, 'overwrite' => true));

			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) $errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
			else $success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
		}

		// go through each file
		foreach($success as $original => $file) {
			// save every file in db
			$opt = array(
				'file' => pathinfo($file, PATHINFO_BASENAME),
				'type' => 'medical',
				'size' => filesize($file) / 1024,
				'folder_id' => $folder->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original
			);

			$file = clone $this->db->files;
			$file->setOptions($opt)->save();
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect
		return $this->_helper->redirector('attach', 'medical', 'frontend', array('id' => $record->getId()));
    }

    /**
     * Deletes a Medical Record
     */
    public function deleteAction() {
    	// check if logged in
    	$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// if owner
		if($record->getOwnerId() == $this->auth->getIdentity()->id) {
			// set as deleted
			$record->setDeleted(1);
			$record->save();

			// message
			$this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" deleted successfully."), $record->getHeadline1());

		// not owner? message
		} else $this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" cannot be deleted. Only the owner of the medical record can delete it."), $record->getHeadline1());

		// redirect
    	return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
    }

	/**
	 * Add a Medical Record Subentry
	 */
    public function addSubentriesAction() {
    	// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// check if the currently logged in user has access to view this medical record
		$private = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if medical record is private
		if($private == true) {
			$this->msg->messages[] = $this->translate->_("You do not have access to this medical record.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// admin to view
		$this->view->admin = ($this->auth->getIdentity()->id == $pet->getUserId());

		// check if the logged in user is the owner of the pet or a service provider
		$options = array();
		$options['owner_id'] = $this->auth->getIdentity()->id;
		$options['medical_record_id'] = $record->getId();
		$options['pet_id'] = $record->getPetId();
		$options['write'] = 'pet_owner';
		if(!($pet->getUserId() == $this->auth->getIdentity()->id)) {
			// only accepted links
			$is_service_provider = false;
			$logged_in_user_pet_services = array();
			foreach($this->db->s_m_pets->getPetServices($pet->getId(), 1) as $service) {
				// if service owner
				if($service->getUserId() == $this->auth->getIdentity()->id) {
					$is_service_provider = true;
					$options['write'] = 'service_provider';
					$logged_in_user_pet_services[] = $service;
				}
			}

			// not service provider? message and redirect
			if(!$is_service_provider) {
				$this->msg->messages[] = $this->translate->_("You don't have access to add a medical subentry to this pet.");
				return $this->_helper->redirector('view', 'pets', 'frontend', array('pet' => $pet->getId()));
			}

			$service_options = array();
			foreach($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($logged_in_user_pet_services) as $key => $service) {
				$service_id = substr($key, 0, strpos($key, "_"));
				$service_options[$service_id] = $service['name']->getAttributeEntity()->getValue();
			}
			$options['services'] = $service_options;
		}

		// get form
		$form = new Petolio_Form_MedicalRecordSubentry($options);
		$this->view->form = $form;

		// send to template
		$this->view->record = $record;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// did we submit form ? if not just return here
		if(!($this->request->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'visit_date') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// insert subentry
		$subentry = clone $this->db->subentries;
		$subentry->setMedicalRecordId($data['medical_record_id']);
		$subentry->setServiceId($data['service_id']);
		$subentry->setOwnerId($data['owner_id']);
		$subentry->setVisitDate($data['visit_date']);
		$subentry->setHeadline1($data['headline1']);
		$subentry->setHeadline2($data['headline2']);
		$subentry->setDescription($data['description']);
		$subentry->setRecommendation($data['recommendation']);
		$subentry->setDrugs($data['drugs']);
		$subentry->save(true, true);

		// notify users
		if($data['send_notification'] == 1) {
			foreach($this->getAllowedUsers($record) as $user) {
				if($user->getId() != $this->auth->getIdentity()->id) {
					$html = sprintf(
						$this->translate->_('%1$s added a new medical record entry for the pet %2$s'),
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $pet->getId()), 'default', true)}'>{$pet->getName()}</a>"
					) . '<br /><br />' .
					sprintf(
						$this->translate->_('Click %s to view the medical record.'),
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'medical', 'action'=>'view', 'id' => $record->getId()), 'default', true)}'>{$this->translate->_('here')}</a>"
					);

					Petolio_Service_Message::send(array(
						'subject' => $this->translate->_("New medical record added"),
						'message_html' => $html,
						'from' => $this->auth->getIdentity()->id,
						'status' => 1,
						'template' => 'default'
					), array(array(
						'id' => $user->getId(),
						'name' => $user->getName(),
						'email' => $user->getEmail()
					)), $user->isOtherEmailNotification());
				}
			}
		}

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Medical Record Subentry saved successfully.");
		return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
    }

	/**
	 * Edit a Medical Record Subentry
	 */
    public function editSubentryAction() {
    	// check if logged in
		$this->verifyUser();

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// check if the currently logged in user has access to view this medical record
		$private = $this->isPrivate($subentry->getMedicalRecord()->getRights(), $subentry->getMedicalRecord()->getId(), $subentry->getMedicalRecord()->getOwnerId());

		// restrict access if medical record is private
		if($private) {
			$this->msg->messages[] = $this->translate->_("You do not have access to this medical record.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $subentry->getMedicalRecord()->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($subentry->getMedicalRecord()->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// check if the logged in user is the owner of the medical record subentry
		$options = array();
		$options['owner_id'] = $this->auth->getIdentity()->id;
		$options['medical_record_id'] = $subentry->getMedicalRecordId();
		$options['pet_id'] = $pet->getId();

		// only the owner can edit an entry
		if(!($subentry->getOwnerId() == $this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You don't have access to edit this medical record subentry.");
			return $this->_helper->redirector('view', 'medical', 'frontend', array('id' => $subentry->getMedicalRecordId()));
		}

		// if the owner is a service provider then get the liinked services
		if(!($pet->getUserId() == $this->auth->getIdentity()->id)) {
			// only accepted links
			$is_service_provider = false;
			$logged_in_user_pet_services = array();
			foreach($this->db->s_m_pets->getPetServices($pet->getId(), 1) as $service) {
				// if service owner
				if($service->getUserId() == $this->auth->getIdentity()->id) {
					$is_service_provider = true;
					$logged_in_user_pet_services[] = $service;
				}
			}

			// not service provider? message and redirect
			if(!$is_service_provider) {
				$this->msg->messages[] = $this->translate->_("You don't have access to edit this medical record subentry.");
				return $this->_helper->redirector('view', 'medical', 'frontend', array('id' => $subentry->getMedicalRecordId()));
			}

			$service_options = array();
			foreach($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($logged_in_user_pet_services) as $key => $service) {
				$service_id = substr($key, 0, strpos($key, "_"));
				$service_options[$service_id] = $service['name']->getAttributeEntity()->getValue();
			}
			$options['services'] = $service_options;
		}

		// get form
		$form = new Petolio_Form_MedicalRecordSubentry($options);
		$form->populate($subentry->toArray());
		$this->view->form = $form;

		// send to template
		$this->view->subentry = $subentry;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// did we submit form ? if not just return here
		if(!($this->request->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'visit_date') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// check if we need to update folder names
		if($subentry->getHeadline1() != $data['headline1']) {
			// upload location
			$ds = DIRECTORY_SEPARATOR;
			$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}medical records{$ds}{$subentry->getMedicalRecord()->getHeadline1()}";

			// find subentry folder
			$folder = $this->db->folders->getMapper()->getDbTable()->findFolders(array('id' => $subentry->getFolderId(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id));
			if(!is_null($folder)) {
				// rename on disk and in db
				rename("{$upload_dir}{$ds}{$folder->getName()}", "{$upload_dir}{$ds}{$data['headline1']}");
				$folder->setName($data['headline1'])->save();
			}
		}

		// update subentry
		$subentry->setMedicalRecordId($data['medical_record_id']);
		$subentry->setServiceId($data['service_id']);
		$subentry->setOwnerId($data['owner_id']);
		$subentry->setVisitDate($data['visit_date']);
		$subentry->setHeadline1($data['headline1']);
		$subentry->setHeadline2($data['headline2']);
		$subentry->setDescription($data['description']);
		$subentry->setRecommendation($data['recommendation']);
		$subentry->setDrugs($data['drugs']);
		$subentry->save(false, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Medical Record Subentry saved successfully.");
		return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
    }

	/**
	 * Attach a file to a Medical Record Subentry
	 */
    public function attachSubentryAction() {
    	// check if logged in
		$this->verifyUser();

		// change render
		$this->_helper->viewRenderer->setRender('attach');

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// send to view
		$this->view->subentry = $subentry;

		// get pet
		$pet = $this->db->pet->find($subentry->getMedicalRecord()->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// check if the logged in user is the owner of the pet or a service provider
		if(!($subentry->getOwnerId() == $this->auth->getIdentity()->id)) {
			// only accepted links
			$is_service_provider = false;
			foreach($this->db->s_m_pets->getPetServices($subentry->getMedicalRecord()->getPetId(), 1) as $service)
				if($service->getUserId() == $this->auth->getIdentity()->id)
					$is_service_provider = true;

			// not service provider? message and redirect
			if(!$is_service_provider) {
				$this->msg->messages[] = $this->translate->_("You don't have access to attach files to this medical record subentry.");
				return $this->_helper->redirector('view', 'pets', 'frontend', array('pet' => $subentry->getMedicalRecord()->getPetId()));
			}
		}

		// get all files
		if($subentry->getFolderId())
			$this->view->files = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $subentry->getOwnerId(), 'folderId' => $subentry->getFolderId()), true);

		// get form
		$form = new Petolio_Form_AttachMedicalRecordFiles();
		$this->view->form = $form;

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$subentry->getMedicalRecord()->getPetId()}{$ds}medical records{$ds}{$subentry->getMedicalRecord()->getHeadline1()}{$ds}{$subentry->getHeadline1()}{$ds}";

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create a medical record folder for our pet if not exists
		$root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'name' => 'medical records'));
		if(is_null($root)) {
			$pet_folder = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'parentId' => 0));
			$root = $this->db->folders->getMapper()->getDbTable()->addFolder(array('name' => 'medical records', 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => is_null($pet_folder) ? 0 : $pet_folder->getId()));
		}

		// create this medical record folder if not exists
		$vars = array('name' => $subentry->getMedicalRecord()->getHeadline1(), 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => $root->getId());
		$folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($folder))
			$folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// save folder id
		$record = $subentry->getMedicalRecord();
		$record->setFolderId($folder->getId());
		$record->save();

		// create this medical record subentry folder if not exists
		$vars = array('name' => $subentry->getHeadline1(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $record->getFolderId());
		$subentry_folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($subentry_folder))
			$subentry_folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// save folder id
		$subentry->setFolderId($subentry_folder->getId());
		$subentry->save();

		// create the medical_records directory
		$mr_folder = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$subentry->getMedicalRecord()->getPetId()}{$ds}medical records{$ds}{$subentry->getMedicalRecord()->getHeadline1()}{$ds}";
		if(!file_exists(pathinfo($mr_folder, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($mr_folder, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the medical records's folder on disk.")));
				return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
			}
		}

		// create folder for this medical record
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the medical records's folder on disk.")));
				return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
			}
		}

		// create folder for this medical record subentry
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the medical record's folder on disk.")));
				return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
			}
		}

		// prepare upload files
		$i = 0;
		$errors = array();
		$success = array();

		// get addapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('Extension', false, array('doc', 'xls', 'jpg', 'gif', 'png', 'txt', 'avi', 'mpg', 'mpeg', 'mp3', 'pdf', 'rar', 'zip'));

		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if(!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your file / files exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->config['phpSettings']['upload_max_filesize'])));
				return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
			}
		}

		// upload each file
		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

			$adapter->clearFilters();
			$adapter->addFilter('Rename', array('target' => $upload_dir . $new_filename, 'overwrite' => true));

			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) $errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
			else $success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
		}

		// go through each file
		foreach($success as $original => $file) {
			// save every file in db
			$opt = array(
				'file' => pathinfo($file, PATHINFO_BASENAME),
				'type' => 'medical',
				'size' => filesize($file) / 1024,
				'folder_id' => $subentry_folder->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original
			);

			$file = clone $this->db->files;
			$file->setOptions($opt)->save();
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect
		return $this->_helper->redirector('attach-subentry', 'medical', 'frontend', array('id' => $subentry->getId()));
    }

    /**
     * Deletes a Medical Record Subentry
     */
    public function deleteSubentryAction() {
    	// check if logged in
    	$this->verifyUser();

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// if owner
		if($subentry->getOwnerId() == $this->auth->getIdentity()->id) {
			// set as deleted
			$subentry->setDeleted(1);
			$subentry->save();

			// message
			$this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" deleted successfully."), $subentry->getHeadline1());

		// not owner? message
		} else $this->msg->messages[] = sprintf($this->translate->_("Medical record \"%s\" cannot be deleted. Only the owner of the medical record can delete it."), $subentry->getHeadline1());

		// redirector
    	return $this->_helper->redirector('view', 'medical', 'frontend', array('id' => $subentry->getMedicalRecordId()));
    }

	/**
	 * View Medical Record
	 */
    public function viewAction() {
		// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->medical_record = $record;

		// check if the currently logged in user has access to view this medical record
		$access = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if medical record is private
		if($access) {
			$this->msg->messages[] = $this->translate->_("You do not have access to view this medical record.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
		}

		// get owner
		$users = $this->db->user->find($record->getOwnerId());
		if(!$users->getId()) {
			$this->msg->messages[] = $this->translate->_("Medical record owner not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// send to view
		$this->view->owner = $users;

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// if iframe, disable all the bullshit
		if($this->request->getParam('iframe')) {
			$this->_helper->layout->disableLayout();
			$file = file_get_contents("http://{$_SERVER['SERVER_NAME']}/fluxbb/index/iframe/true", false, stream_context_create(array(
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

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// get record files
		if($record->getFolderId())
			$this->view->files = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $record->getOwnerId(), 'folderId' => $record->getFolderId()), true);

		// is admin
		$this->view->admin = $users->getId() == $this->auth->getIdentity()->id ? true : false;

		// get record subentries
		$record_subentries = $this->db->subentries->getMapper()->fetchWithReferences("a.medical_record_id = '{$record->getId()}'", "visit_date ASC");

		// get subentries files
		$subentries_files = array();
		foreach($record_subentries as $one)
			if($one->getFolderId())
				$subentries_files[$one->getId()] = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $one->getOwnerId(), 'folderId' => $one->getFolderId()), true);

		// get subentries notes (if not iframe)
		$subentries_notes = array();
		if(!$this->view->frame) {
			foreach($record_subentries as $one) {
				$result = reset($this->db->cale->fetchList("user_id = {$this->auth->getIdentity()->id} AND species = {$pet->getId()} AND fee = {$one->getId()} AND type = 1", "date_start ASC"));
				if($result) {
					$array = Petolio_Service_Calendar::format($result);
					$array['deadline'] = Petolio_Service_Util::formatDate($array['start'], Petolio_Service_Util::MEDIUMDATE, ($array['allDay'] != 1), true, true);
					$subentries_notes[$one->getId()] = $array;
				}
			}
		}

		// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species = json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods = json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
    	$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// pass subentries and files and notes to template
		$this->view->subentries = $record_subentries;
		$this->view->subentries_files = $subentries_files;
		$this->view->subentries_notes = $subentries_notes;
    }

    /**
     * Exports a medical record as a pdf file and sends it to download
     */
    public function exportAction() {
    	// check if logged in
		$this->verifyUser();

    	// disable all form of layout
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->medical_record = $record;

		// check if the currently logged in user has access to view this medical record
		$access = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if medical record is private
		if($access) {
			$this->msg->messages[] = $this->translate->_("You do not have access to view this medical record.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
		}

		// get owner
		$users = $this->db->user->find($record->getOwnerId());
		if(!$users->getId()) {
			$this->msg->messages[] = $this->translate->_("Medical record owner not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// send to view
		$this->view->owner = $users;

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->view->pet = $pet;
		$this->view->pet_attr = $pet_attributes;

		// get record files
		if($record->getFolderId())
			$this->view->files = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $record->getOwnerId(), 'folderId' => $record->getFolderId()), true);

		// is admin
		$this->view->admin = $users->getId() == $this->auth->getIdentity()->id ? true : false;

		// get record subentries
		$record_subentries = $this->db->subentries->getMapper()->fetchWithReferences("a.medical_record_id = '{$record->getId()}'", "visit_date ASC");

		// get subentries files
		$subentries_files = array();
		foreach($record_subentries as $one)
			if($one->getFolderId())
				$subentries_files[$one->getId()] = $this->db->files->getMapper()->getDbTable()->findFiles(array('ownerId' => $one->getOwnerId(), 'folderId' => $one->getFolderId()), true);

		// pass subentries and it's files to template
		$this->view->subentries = $record_subentries;
		$this->view->subentries_files = $subentries_files;

		// create new PDF document
		$pdf = new Petolio_Service_Pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Petolio');
		$pdf->SetTitle($record->getHeadline1());
		$pdf->SetSubject('Petolio medical record export');
		$pdf->SetKeywords('PDF, Petolio, medical, record, pet');

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, 36, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set font
		$pdf->SetFont('times', 'BI', 13);

		// add a page
		$pdf->AddPage();

		// set some text to print
		$html = $this->view->render("medical/export.phtml");
$txt = <<<EOD
$html
EOD;

		$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $txt, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

		// Close and output PDF document
		$pdf->Output(urlencode($record->getHeadline1()).'.pdf', 'D');
    }

	/**
	 * Sends a Medical Record as a petolio message
	 */
    public function sendAction() {
		// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// only the owner of the medical record can send it to other users
		if($record->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the owner of the medical record can send it to other users.");
			return $this->_helper->redirector('index', 'medical', 'frontend', array('pet' => $record->getPetId()));
		}

		if($record->getRights() == 0) {
			// nobody/private
			$this->msg->messages[] = sprintf($this->translate->_("The medical record is private and cannot be visible for anyone.<br />To change this please %s"), "<a href='{$this->view->url(array('controller'=>'medical', 'action'=>'index', 'pet' => $record->getPetId(), 'access' => $record->getId()), 'default', true)}'>".$this->translate->_('click here')."</a>");
		} elseif($record->getRights() == 1) {
			// public, do nothing
		} else { // 2 or 3 or 4
			// loop through allowed users
			$allowed = array();
			foreach($this->getAllowedUsers($record) as $user)
				$allowed[] = "<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user->getId()), 'default', true)}'>{$user->getName()}</a>";

			$this->msg->messages[] = sprintf($this->translate->_("The medical record is visible only for the following users: %s.<br />To change this please %s"), implode(', ', $allowed), "<a href='{$this->view->url(array('controller'=>'medical', 'action'=>'index', 'pet' => $record->getPetId(), 'access' => $record->getId()), 'default', true)}'>".$this->translate->_('click here')."</a>");
		}

		$populate = array (
			"subject" => $this->translate->_("Emailing: ").$record->getHeadline1(),
			"message" => "<br/>".$this->translate->_('Your message is ready to be sent with the following medical record attachments:')."<br/><br/>{$record->getHeadline1()}<br/><a href='{$this->view->url(array('controller'=>'medical', 'action'=>'view', 'id' => $record->getId()), 'default', true)}'>".$this->translate->_('view medical record')."</a>"
		);

		// namespace
    	$namespace = new Zend_Session_Namespace();
		$namespace->populate = $populate;

		// redirect to compose new petolio message
		return $this->_helper->redirector('compose', 'messages');
    }

    /**
	 * Return a json list of people who have rights idk
	 */
	public function rightsAction() {
		// default
		$x = array('success' => true, 'html' => '');

		// check if logged in
		if(!isset($this->auth->getIdentity()->id)) {
			$x['html'] = $this->translate->_("You must be logged in to view the requested page.");
			return Petolio_Service_Util::json($x);
		}

		// get record
		$record = $this->getRecord($this->request->getParam('id'), $x);

		// set default variables
		$users_id = array();
		$users_id_name = array();

		// if access right is 2, 3 or 4 then try and fill the users
		if($record->getRights() > 1) {
			foreach($this->db->rights->getMapper()->findAllowedUsers($record->getId()) as $user) {
				// fill for friends and partners (2, 3)
				$users_id[] = $user->getId();

				// fill for users (4)
				if($record->getRights() == 4)
					$users_id_name[] = $user->getId() . '|' . $user->getName();
			}
		}

		$x['html'] = $this->view->partial('medical/rights.phtml', array (
			'dialog' => true,
			'friends' => $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'friends'),
			'partners' => $this->loadFriendsAndPartners($this->auth->getIdentity()->id, 'partners'),
			'rights' => array(
				'access' => $record->getRights(),
				'users_id' => $users_id,
				'users_id_name' => implode(',', $users_id_name)
			),
			'translate' => $this->view->translate
		));

		// did we submit form ? if not just return here
		if(!isset($_POST['access_value']))
			return Petolio_Service_Util::json($x);

		// update access to selected records
		$this->updateRecordAccess(array($record->getId()), $this->rightsSrc($_POST));

		// return x
		return Petolio_Service_Util::json($x);
	}

	/**
	 * Returns a json list of users who have access to view the medical record
	 */
	public function usersAction() {
		// default return
		$return = array('success' => true);

		// get page and action
		$page = $this->request->getParam('page', 1);
		$action = $this->request->getParam('modify', null);

		// get record
		$record = $this->getRecord($this->request->getParam('id'), false, $return);

		// nobody/private
		if($record->getRights() == 0) {
			if(isset($action) && $action != null) {
				$return['notify'] = 0;
				$return['text'] = $this->translate->_("The medical record is private. Click on the modify button to change this or click on save to save the medical record subentry without sending any notification message.");
			} else {
				$return['text'] = $this->translate->_("The medical record is private and cannot be visible for anyone.");
			}

		// public
		} elseif($record->getRights() == 1) {
			if(isset($action) && $action != null) {
				$return['notify'] = 0;
				$return['text'] = $this->translate->_("The medical record is public. Click on the modify button to restrict to partners, friends or specific users and send them notification or click on save to save the medical record subentry without sending any notification message.");
			} else
				$return['text'] = $this->translate->_("This medical record is public and it's visible for every petolio user.");

 		// 2 or 3 or 4
		} else {
			// loop through allowed users
			$allowed = array();
			foreach($this->getAllowedUsers($record) as $user) {
				$allowed[$user->getId()] = $user->toArray();
				$allowed[$user->getId()]['user_id'] = $allowed[$user->getId()]['id'];
				$allowed[$user->getId()]['user_name'] = $allowed[$user->getId()]['name'];
				$allowed[$user->getId()]['user_avatar'] = $allowed[$user->getId()]['avatar'];
			}

			// calculate page
			$rows_per_page = 10;
			$numrows = count($allowed);
			$lastpage = ceil($numrows/$rows_per_page);

			// set page limits
			if($page < 1) $page = 1;
			elseif($page > $lastpage) $page = $lastpage;

			// calculate limits
			$limit = false;
			if($numrows != FALSE) {
				$limit[0] = $rows_per_page;
				$limit[1] = ($page - 1) * $rows_per_page;
			}

			// load likes
			$this->view->data = array_slice($allowed, $limit[1], $limit[0], true);
			$this->view->pagination = Petolio_Service_Util::paginate($lastpage, $page);

			$this->view->plugin = $this->view;
			$return['notify'] = 1;

			if(isset($action) && $action != null) $return['text'] = $this->translate->_("The following users will receive notifications that a new medical record subentry was added:");
			else $return['text'] = $this->translate->_("The following users can view this medical record:");

			// make good use of the social ratings template
			$return['html'] = $this->view->render("social/misc_ratings.phtml");
		}

		// return json
		return Petolio_Service_Util::json($return);
	}

    /**
     * Find medical record
     * used for questions right now
     *
     * @returns json of medical records found
     */
    public function findAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// no pet id param ?
		$id = (int)$this->request->getParam('x');
		if(!isset($id))
			die('bye');

		// search medical records
		$results = array();
		foreach($this->db->records->fetchList("pet_id = {$id} AND owner_id = {$this->auth->getIdentity()->id} AND deleted = 0") as $all)
			$results[$all->getId()] = $all->getHeadline1();

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $results));
    }

    /**
     * Find medical subentries
     * used for to-do calendar
     *
     * @returns json of medical subentries found
     */
    public function listAction() {
		// not logged in ? who are you ?
		if(!isset($this->auth->getIdentity()->id))
			return false;

		// search medical records
		$results = array();
		foreach($this->db->records->fetchList("owner_id = {$this->auth->getIdentity()->id} AND deleted = 0") as $all)
			foreach($this->db->subentries->fetchList("medical_record_id = {$all->getId()} AND owner_id = {$this->auth->getIdentity()->id} AND deleted = 0") as $one)
				$results[$all->getPetId()][$all->getId()][] = array($one->getId(), $one->getHeadline1(), $all->getHeadline1());

		// return results
		return Petolio_Service_Util::json(array('success' => true, 'results' => $results));
    }

    /**
     * Update medical record access
     *
     * @param array $record - array of record ids
     * @param array $rights
     */
    private function updateRecordAccess($records, $rights) {
		// no records ? bye !
		if(!$records)
			return;

		// loop through records
		foreach($records as $one) {
	    	// get record
	    	$record = $this->getRecord($one);

			// remove old rights
			$this->db->rights->getMapper()->getDbTable()->delete("medical_record_id = {$record->getId()}");

			// set new rights
	    	foreach($rights[1] as $id) {
				$record_rights = clone $this->db->rights;
				$record_rights->setMedicalRecordId($record->getId());
				$record_rights->setUserId($id);
				$record_rights->save();
			}

			// save the file access
			$record->setRights($rights[0]);
			$record->save();

			// update files as well
			if($record->getFolderId()) {
				// get the folder
				$folder = $this->db->folders->find($record->getFolderId())->toArray();

				// you shouldn't be able to update the access rights for root
				if($folder['name'] == 'root')
					return;

				// loop through all folders within this folder
				foreach($this->db->folders->getMapper()->getChildren($folder['id']) as $dir) {
					// update all the files from the current dir
					$this->_accessHelper($dir, $rights);
				}

				// update access to all the files in the selected folder
				$this->_accessHelper($folder, $rights);
			}
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
			$file = $this->db->files->find($file);

			// get folder
			$folder = $this->db->folders->find($file->getFolderId())->toArray();

			// you shouldn't be able to update the access rights for gallery files
			if($folder['name'] == 'gallery')
				continue;

			// delete old users
			$this->db->f_rights->delete("file_id = {$file->getId()}");

			// save users
			foreach($rights[1] as $id) {
				$user = clone $this->db->f_rights;
				$user->setFileId($file->getId())
					->setUserId($id)
					->save();
			}

			// update file access
			$file->setRights($rights[0])->setDateModified(date('Y-m-d H:i:s'))->save(false);
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
		foreach($this->db->files->fetchList("folder_id = {$folder['id']}") as $file)
			$this->updateFilesAccess(array($file->getId()), $rights);
	}
}