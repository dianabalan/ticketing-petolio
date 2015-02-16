<?php

class DiaryController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $up = null;
    private $yt_name = null;
    private $auth = null;
    private $config = null;
    private $request = null;
    private $unlisted = null;

	private $db = null;

	/**
	 * Init Controller
	 */
    public function init() {
    	// get basic stuff
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();

		// get db objects
		$this->db = new stdClass();
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->interest = new Petolio_Model_PoInterestMapper();
		$this->db->attr = new Petolio_Model_PoAttributes();
		$this->db->user = new Petolio_Model_PoUsers();

		$this->db->records = new Petolio_Model_PoDiaryRecords();
		$this->db->subentries = new Petolio_Model_PoDiaryRecordSubentries();
		$this->db->rights = new Petolio_Model_PoDiaryRecordRights();

		$this->db->files = new Petolio_Model_PoFiles();
		$this->db->folders = new Petolio_Model_PoFolders();
		$this->db->f_rights = new Petolio_Model_PoFileRights();

		// send to view
		$this->view->auth = $this->auth;
		$this->view->showAdoptionInterest = $this->showAdoptionInterest();

		// set unlisted params
        $this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));
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
     * So is the diary record private or not ??
     *
     * @param int $rights - Diary Record Right
     * @param int $id - Diary Record Id
     * @param int $owner - Diary Record Owner
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
					foreach($this->db->rights->getMapper()->findByField('diary_record_id', $id, null) as $user)
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
					foreach($this->db->rights->getMapper()->findByField('diary_record_id', $id, null) as $user)
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
					foreach($this->db->rights->getMapper()->findByField('diary_record_id', $id, null) as $user)
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
     * Returns an array of PoUsers who have access to the diary record
     * @param Diary Record obj
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
				foreach($this->db->rights->getMapper()->findByField('diary_record_id', $mr->getId(), null) as $user)
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
				foreach($this->db->rights->getMapper()->findByField('diary_record_id', $mr->getId(), null) as $user)
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
				foreach($this->db->rights->getMapper()->findByField('diary_record_id', $mr->getId(), null) as $user)
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
		$msg = $this->translate->_("Diary record not found.");
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
		$subentry = $this->db->subentries->findWithDiaryRecord($id);
		if(!$subentry->getId()) {
			$this->msg->messages[] = $this->translate->_("Diary Record Subentry not found.");
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
		return $this->_helper->redirector($module, 'diary', 'frontend', array('pet' => $data['pet_id']));
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
	 * View All Diary Records for a pet
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
		if($this->view->order == 'rights') $sort = "rights {$this->view->dir}";
		else {
			$this->view->order = 'title';
			$sort = "title {$this->view->dir}";
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
				->setTitle($row["title"])
				->setDescription($row["description"])
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
			$this->msg->messages[] = $this->translate->_("You do not have access to edit or delete diary records that belong to this pet.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $pet->getId()));
		}

		// send to apropriate action
		switch($what) {
			// mass delete
			case 'sel_delete':
				// go through each
				foreach($records as $id) {
					// find diary record
					$record = $this->db->records->find($id);
					if($record->getId()) {
						// see if owner
						if($record->getOwnerId() == $this->auth->getIdentity()->id) {
							// update to deleted
							$record->setDeleted(1);
							$record->save();

							// set message
							$this->msg->messages[] = sprintf($this->translate->_("Diary record \"%s\" deleted successfully."), $record->getTitle());

						// not owner? set message
						} else $this->msg->messages[] = sprintf($this->translate->_("Diary record \"%s\" cannot be deleted. Only the owner of the diary record can delete it."), $record->getTitle());
					}
				}

				// redirect when finished
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? preg_replace("/\/access\/(\d+)/i", "", $_SERVER['HTTP_REFERER']) : "diary/index/pet/{$pet->getId()}");
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
					return $this->_redirect("diary/index/pet/{$pet->getId()}/access/{$records[0]}");

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
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? preg_replace("/\/access\/(\d+)/i", "", $_SERVER['HTTP_REFERER']) : "diary/index/pet/{$pet->getId()}");
			break;

			// action not found ?
			default:
				$this->msg->messages[] = $this->translate->_("Invalid Request.");
				return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $pet->getId()));
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
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $pet->getId()));
		}

		// no admin ? bye
		if(!$admin) {
			$this->msg->messages[] = $this->translate->_("You do not have access to edit or delete diary records that belong to this pet.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $pet->getId()));
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
	 * Add Diary Record
	 */
    public function addAction() {
    	// check if logged in
		$this->verifyUser();

		// get pet
		$pet = $this->db->pet->find($this->request->getParam('pet'));
		if(!$pet->getId())
			return $this->getPet('add');

		// if not owner
		if($pet->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You don't have access to add a diary record to this pet.");
			return $this->_helper->redirector('view', 'diary', 'frontend', array('pet' => $pet->getId()));
		}

		// get form
		$form = new Petolio_Form_DiaryRecord();
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
		foreach($data as $idx => &$line)
			if(!(strlen($line) > 0))
				$line = null;

		// insert diary record
		$record = clone $this->db->records;
		$record->setOwnerId($this->auth->getIdentity()->id);
		$record->setPetId($pet->getId());
		$record->setTitle($data['title']);
		$record->setDescription($data['description']);
		$record->setRights(0);
		$record->save(true, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Diary Record saved successfully.");
		return $this->_helper->redirector('view', 'diary', 'frontend', array('id' => $record->getId()));
    }

	/**
	 * Edit Diary Record
	 */
    public function editAction() {
    	// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->record = $record;

		// restrict access if diary record is private
		if($record->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You do not have access to edit this diary record.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// get form
		$form = new Petolio_Form_DiaryRecord();
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
		foreach($data as $idx => &$line)
			if(!(strlen($line) > 0))
				$line = null;

		// update diary record
		$record->setTitle($data['title']);
		$record->setDescription($data['description']);
		$record->save(false, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Diary Record saved successfully.");
		return $this->_helper->redirector('view', 'diary', 'frontend', array('id' => $record->getId()));
    }

    /**
     * Deletes a Diary Record
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
			$this->msg->messages[] = sprintf($this->translate->_("Diary record \"%s\" deleted successfully."), $record->getTitle());

		// not owner? message
		} else $this->msg->messages[] = sprintf($this->translate->_("Diary record \"%s\" cannot be deleted. Only the owner of the diary record can delete it."), $record->getTitle());

		// redirect
    	return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
    }

	/**
	 * Add a Diary Record Subentry
	 */
    public function addSubentriesAction() {
    	// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// restrict access if diary record is private
		if($record->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You do not have access to add a diary subentry to this pet.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($record->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// admin to view
		$this->view->admin = ($this->auth->getIdentity()->id == $pet->getUserId());

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
		// picture prepare
		$picture = array();
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}diary{$ds}";

		// get form
		$form = new Petolio_Form_DiaryRecordSubentry($picture, $upload_dir);
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

		// get form
		$form = new Petolio_Form_DiaryRecordSubentry();
		$form->populate(array('date' => array(
			"day" => date("j"),
			"month" => date("n"),
			"year" => date("Y")
		)));
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
			// date
			if(is_array($line) && $idx == 'date') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
			// picture
			} elseif(isset($line) && $idx == 'picture') {
				$filename = sha1(md5($subentry->getId()) . 'unimatrix') . '.' . pathinfo($line, PATHINFO_EXTENSION);
				@rename($upload_dir . $line, $upload_dir . $filename);

				// resize original picture if bigger
				$props = @getimagesize($upload_dir . $filename);
				list($w, $h) = explode('x', '95x95');
				if($props[0] > $w || $props[1] > $h) {
					Petolio_Service_Image::output($upload_dir . $filename, $upload_dir . $filename, array(
						'type'   => IMAGETYPE_JPEG,
						'width'   => $w,
						'height'  => $h,
						'method'  => THUMBNAIL_METHOD_SCALE_MIN
					));
				}

				$line = $filename;
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

			// rest
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// insert subentry
		$subentry = clone $this->db->subentries;
		$subentry->setDiaryRecordId($record->getId());
		$subentry->setOwnerId($this->auth->getIdentity()->id);
		$subentry->setDate($data['date']);

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
		if(isset($data['picture'])) $subentry->setPicture($data['picture']);
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

		$subentry->setDescription($data['description']);
		$subentry->save(true, true);

		// notify users
		if($data['send_notification'] == 1) {
			foreach($this->getAllowedUsers($record) as $user) {
				if($user->getId() != $this->auth->getIdentity()->id) {
					$html = sprintf(
						$this->translate->_('%1$s added a new diary record entry for the pet %2$s'),
						"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
						"<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $pet->getId()), 'default', true)}'>{$pet->getName()}</a>"
					) . '<br /><br />' .
					sprintf(
						$this->translate->_('Click %s to view the diary record.'),
						"<a href='{$this->view->url(array('controller'=>'diary', 'action'=>'view', 'id' => $record->getId()), 'default', true)}'>{$this->translate->_('here')}</a>"
					);

					Petolio_Service_Message::send(array(
						'subject' => $this->translate->_("New diary record added"),
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
		$this->msg->messages[] = $this->translate->_("Diary Record Subentry saved successfully.");
		return $this->_helper->redirector('pictures-subentry', 'diary', 'frontend', array('id' => $subentry->getId()));
    }

	/**
	 * Edit a Diary Record Subentry
	 */
    public function editSubentryAction() {
    	// check if logged in
		$this->verifyUser();

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// restrict access if diary record is private
		if($subentry->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You do not have access to edit this diary subentry.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		// get pet
		$pet = $this->db->pet->find($subentry->getDiaryRecord()->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
		// uploaded picture
		$picture = array();
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}diary{$ds}";
		$url_path = "/images/userfiles/diary/";
		if($subentry->getPicture()) {
			$picture = array('picture' => array(
				$this->view->url(array('controller'=>'diary', 'action'=>'delete-picture', 'id' => $subentry->getId()), 'default', true),
				$subentry->getPicture()
			));
		}

		// get form
		$form = new Petolio_Form_DiaryRecordSubentry($picture, $upload_dir, $url_path);
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

		// get form
		$form = new Petolio_Form_DiaryRecordSubentry();
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
			// date
			if(is_array($line) && $idx == 'date') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = null;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
			// picture
			} elseif(isset($line) && $idx == 'picture') {
				$filename = sha1(md5($subentry->getId()) . 'unimatrix') . '.' . pathinfo($line, PATHINFO_EXTENSION);
				@rename($upload_dir . $line, $upload_dir . $filename);

				// resize original picture if bigger
				$props = @getimagesize($upload_dir . $filename);
				list($w, $h) = explode('x', '95x95');
				if($props[0] > $w || $props[1] > $h) {
					Petolio_Service_Image::output($upload_dir . $filename, $upload_dir . $filename, array(
						'type'   => IMAGETYPE_JPEG,
						'width'   => $w,
						'height'  => $h,
						'method'  => THUMBNAIL_METHOD_SCALE_MIN
					));
				}

				$line = $filename;
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

			// rest
			} else
				if(!(strlen($line) > 0))
					$line = null;
		}

		// update subentry
		$subentry->setDate($data['date']);

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
		if(isset($data['picture'])) $subentry->setPicture($data['picture']);
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

		$subentry->setDescription($data['description']);
		$subentry->save(false, true);

		// message + redirect
		$this->msg->messages[] = $this->translate->_("Diary Record Subentry saved successfully.");
		return $this->_helper->redirector('pictures-subentry', 'diary', 'frontend', array('id' => $subentry->getId()));
    }

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
	public function deletePictureAction() {
    	// check if logged in
    	$this->verifyUser();

		// upload path
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}diary{$ds}";

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// if owner
		if($subentry->getOwnerId() == $this->auth->getIdentity()->id) {
			// delete from hdd
			@unlink($upload_dir . $subentry->getPicture());

			// delete from db
			$subentry->setPicture(new Zend_Db_Expr('NULL'));
			$subentry->save();
		}

		// redirector
    	return $this->_helper->redirector('edit-subentry', 'diary', 'frontend', array('id' => $subentry->getId()));
	}
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

    /**
     * Pictures for Diary Record Subentry
     */
    public function picturesSubentryAction() {
       	// check if logged in
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// send to view
		$this->view->subentry = $subentry;

		// get pet
		$pet = $this->db->pet->find($subentry->getDiaryRecord()->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// load the form
		$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
		$this->view->form = $form;

		// get & show all pictures
		$result = $this->db->files->getMapper()->fetchList("type = 'image' AND folder_id = '{$subentry->getFolderId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
		$this->view->gallery = $result;

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}diary records{$ds}{$subentry->getDiaryRecord()->getTitle()}{$ds}{$subentry->getDate()}{$ds}";

   		// make picture primary
    	$primary = $this->request->getParam('primary');
    	if (isset($primary)) {
			// get level
			$result = $this->db->files->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_helper->redirector('index', 'site');
			} else $pic = reset($result);

			// get all other pictures
			$result = reset($this->db->files->getMapper()->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
		}

		// get picture remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->db->files->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
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

			// msg
			$this->msg->messages[] = $this->translate->_("Your subentry picture has been deleted successfully.");
			return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create a diary record folder for our pet if not exists
		$root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'name' => 'diary records'));
		if(is_null($root)) {
			$pet_root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'parentId' => 0));
			$root = $this->db->folders->getMapper()->getDbTable()->addFolder(array('name' => 'diary records', 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => is_null($pet_root) ? 0 : $pet_root->getId()));
		}

		// create the diary record folder if not exists
		$vars = array('name' => $subentry->getDiaryRecord()->getTitle(), 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => $root->getId());
		$folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($folder))
			$folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// create the diary subentry folder if not exists
		$vars = array('name' => $subentry->getDate(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $folder->getId());
		$subentry_folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($subentry_folder))
			$subentry_folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// save folder id
		$subentry->setFolderId($subentry_folder->getId());
		$subentry->save();

		// create the diary records directory
		$dr_folder = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}diary records{$ds}{$subentry->getDiaryRecord()->getTitle()}{$ds}";
		if(!file_exists(pathinfo($dr_folder, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($dr_folder, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary records's folder on disk.")));
				return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
			}
		}

		// create folder for this medical record
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary records's folder on disk.")));
				return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
			}
		}

		// create folder for this medical record subentry
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary record's folder on disk.")));
				return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
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
	    		return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
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

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your subentry pictures have been updated successfully.");
		return $this->_redirect('diary/pictures-subentry/id/'. $subentry->getId());
    }

    /**
     * Videos for Diary Record Subentry
     */
    public function videosSubentryAction() {
       	// check if logged in
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get subentry
		$subentry = $this->getSubentry($this->request->getParam('id'));

		// send to view
		$this->view->subentry = $subentry;

		// get pet
		$pet = $this->db->pet->find($subentry->getDiaryRecord()->getPetId());
		if(!$pet->getId() || $pet->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("Pet not found.");
			return $this->_helper->redirector('index', 'site');
		}

		// load menu
		$pet_attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($pet, true));
		$this->petOptions($pet, $pet_attributes);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}diary records{$ds}{$subentry->getDiaryRecord()->getTitle()}{$ds}{$subentry->getDate()}{$ds}";

		// create a diary record folder for our pet if not exists
		$root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'name' => 'diary records'));
		if(is_null($root)) {
			$pet_root = $this->db->folders->getMapper()->getDbTable()->findFolders(array('petId' => $pet->getId(), 'parentId' => 0));
			$root = $this->db->folders->getMapper()->getDbTable()->addFolder(array('name' => 'diary records', 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => is_null($pet_root) ? 0 : $pet_root->getId()));
		}

		// create the diary record folder if not exists
		$vars = array('name' => $subentry->getDiaryRecord()->getTitle(), 'petId' => $pet->getId(), 'ownerId' => $pet->getUserId(), 'parentId' => $root->getId());
		$folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($folder))
			$folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// create the diary subentry folder if not exists
		$vars = array('name' => $subentry->getDate(), 'petId' => $pet->getId(), 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => $folder->getId());
		$subentry_folder = $this->db->folders->getMapper()->getDbTable()->findFolders($vars);
		if(is_null($subentry_folder))
			$subentry_folder = $this->db->folders->getMapper()->getDbTable()->addFolder($vars);

		// save folder id
		$subentry->setFolderId($subentry_folder->getId());
		$subentry->save();

		// create the diary records directory
		$dr_folder = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}diary records{$ds}{$subentry->getDiaryRecord()->getTitle()}{$ds}";
		if(!file_exists(pathinfo($dr_folder, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($dr_folder, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary records's folder on disk.")));
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
			}
		}

		// create folder for this medical record
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary records's folder on disk.")));
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
			}
		}

		// create folder for this medical record subentry
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the diary record's folder on disk.")));
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
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
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($subentry->getDescription(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr($pet_attributes['name']->getAttributeEntity()->getValue(), 0, 30) . ', diary, petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'diary', 'action'=>'videos-subentry', 'id'=>$subentry->getId()), 'default', true);

		// get all videos and refresh cache
		$result = $this->db->files->getMapper()->fetchList("type = 'video' AND folder_id = '{$subentry->getFolderId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->db->files->getMapper()->fetchList("file = '{$filename}' AND folder_id = '{$subentry->getFolderId()}'");
			if(is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
			}

			// save video in db
			$file = clone $this->db->files;
			$file->setOptions(array(
				'file' => $filename,
				'type' => 'video',
				'size' => 1,
				'folder_id' => $subentry->getFolderId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'description' => $original_name
			))->save();

			// msg
			$this->msg->messages[] = $this->translate->_("Your subentry video link has been successfully added.");
			return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
		}

		// get video remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->db->files->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
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

			// delete video
			$vid->deleteRowByPrimaryKey();

			// msg
			$this->msg->messages[] = $this->translate->_("Your subentry video has been deleted successfully.");
			return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
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
				$file = clone $this->db->files;
				$file->setOptions(array(
					'file' => $filename,
					'type' => 'video',
					'size' => 0,
					'folder_id' => $subentry->getFolderId(),
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
		if(!(count($errors) > 0))
			$this->msg->messages[] = $this->translate->_("Your subentry videos have been updated successfully.");

		return $this->_redirect('diary/videos-subentry/id/'. $subentry->getId());
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
     * Deletes a Diary Record Subentry
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
			$this->msg->messages[] = $this->translate->_("Diary record subentry deleted successfully.");
			
		// not owner? message
		} else $this->msg->messages[] = $this->translate->_("Diary record subentry cannot be deleted. Only the owner of the diary record can delete it.");

		// redirector
    	return $this->_helper->redirector('view', 'diary', 'frontend', array('id' => $subentry->getDiaryRecordId()));
    }

	/**
	 * View Diary Record
	 */
    public function viewAction() {
		// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// send to view
		$this->view->diary_record = $record;

		// check if the currently logged in user has access to view this diary record
		$access = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if diary record is private
		if($access) {
			$this->msg->messages[] = $this->translate->_("You do not have access to view this diary record.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		// get owner
		$users = $this->db->user->find($record->getOwnerId());
		if(!$users->getId()) {
			$this->msg->messages[] = $this->translate->_("Diary record owner not found.");
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
		$this->petOptions($pet, $pet_attributes);

		// is admin
		$this->view->admin = $users->getId() == $this->auth->getIdentity()->id ? true : false;

		// get record subentries
		$record_subentries = $this->db->subentries->getMapper()->fetchWithReferences("a.diary_record_id = '{$record->getId()}'", "date ASC");

		// populate with pictures and videos
		$this->view->pictures = $this->populateWithPictures($record, $record_subentries);
		$this->view->videos = $this->populateWithVideos($record, $record_subentries);

		// pass subentries to template
		$this->view->subentries = $record_subentries;
    }

	/**
	 * Populate view with pictures for each subentry
	 */
	private function populateWithPictures($record, $subentries = array()) {
		if(!count($subentries) > 0)
			return false;

		// go through each subentry
		$out = array();
		foreach($subentries as $subentry) {
			// look for pictures
			$pictures = $this->db->files->getMapper()->fetchList("type = 'image' AND folder_id = '{$subentry->getFolderId()}'", "date_created ASC", 10);
			if(isset($pictures) && count($pictures) > 0)
				$out[$subentry->getId()] = $pictures;
		}

		// return what was found
		return $out;
	}

	/**
	 * Populate view with videos for each subentry
	 */
	private function populateWithVideos($record, $subentries = array()) {
		if(!count($subentries) > 0)
			return false;

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
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$record->getPetId()}{$ds}diary records{$ds}{$record->getTitle()}{$ds}";

		// go through each subentry
		$out = array();
		foreach($subentries as $subentry) {
			// look for videos
			$videos = $this->db->files->getMapper()->fetchList("type = 'video' AND folder_id = '{$subentry->getFolderId()}'", "id ASC", 10);
			if(isset($videos) && count($videos) > 0) {
				// iterate over videos for cached entries
				foreach($videos as $idx => $one) {
					// get the cached entry
					$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir . "{$subentry->getDate()}{$ds}");

					// error? skip and remove from list
					if(!is_object($entry)) {
						unset($videos[$idx]);
						continue;
					}

					// set cached entry
					$one->setMapper($entry);
				}

				// output videos
				$out[$subentry->getId()] = $videos;
			}
		}

		// return what was found
		return $out;
	}

    /**
     * Exports a diary record as a pdf file and sends it to download
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
		$this->view->diary_record = $record;

		// check if the currently logged in user has access to view this diary record
		$access = $this->isPrivate($record->getRights(), $record->getId(), $record->getOwnerId());

		// restrict access if diary record is private
		if($access) {
			$this->msg->messages[] = $this->translate->_("You do not have access to view this diary record.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		// get owner
		$users = $this->db->user->find($record->getOwnerId());
		if(!$users->getId()) {
			$this->msg->messages[] = $this->translate->_("Diary record owner not found.");
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

		// is admin
		$this->view->admin = $users->getId() == $this->auth->getIdentity()->id ? true : false;

		// get record subentries
		$record_subentries = $this->db->subentries->getMapper()->fetchWithReferences("a.diary_record_id = '{$record->getId()}'", "date ASC");

		// pass subentries to template
		$this->view->subentries = $record_subentries;

		// create new PDF document
		$pdf = new Petolio_Service_Pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Petolio');
		$pdf->SetTitle($record->getTitle());
		$pdf->SetSubject('Petolio diary record export');
		$pdf->SetKeywords('PDF, Petolio, diary, record, pet');

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
		$html = $this->view->render("diary/export.phtml");
$txt = <<<EOD
$html
EOD;

		$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $txt, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

		// Close and output PDF document
		$pdf->Output(urlencode($record->getTitle()).'.pdf', 'D');
    }

	/**
	 * Sends a Diary Record as a petolio message
	 */
    public function sendAction() {
		// check if logged in
		$this->verifyUser();

		// get record
		$record = $this->getRecord($this->request->getParam('id'));

		// only the owner of the diary record can send it to other users
		if($record->getOwnerId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the owner of the diary record can send it to other users.");
			return $this->_helper->redirector('index', 'diary', 'frontend', array('pet' => $record->getPetId()));
		}

		if($record->getRights() == 0) {
			// nobody/private
			$this->msg->messages[] = sprintf($this->translate->_("The diary record is private and cannot be visible for anyone.<br />To change this please %s"), "<a href='{$this->view->url(array('controller'=>'diary', 'action'=>'index', 'pet' => $record->getPetId(), 'access' => $record->getId()), 'default', true)}'>".$this->translate->_('click here')."</a>");
		} elseif($record->getRights() == 1) {
			// public, do nothing
		} else { // 2 or 3 or 4
			// loop through allowed users
			$allowed = array();
			foreach($this->getAllowedUsers($record) as $user)
				$allowed[] = "<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $user->getId()), 'default', true)}'>{$user->getName()}</a>";

			$this->msg->messages[] = sprintf($this->translate->_("The diary record is visible only for the following users: %s.<br />To change this please %s"), implode(', ', $allowed), "<a href='{$this->view->url(array('controller'=>'diary', 'action'=>'index', 'pet' => $record->getPetId(), 'access' => $record->getId()), 'default', true)}'>".$this->translate->_('click here')."</a>");
		}

		$populate = array (
			"subject" => $this->translate->_("Emailing: ").$record->getTitle(),
			"message" => "<br/>".$this->translate->_('Your message is ready to be sent with the following diary record attachments:')."<br/><br/>{$record->getTitle()}<br/><a href='{$this->view->url(array('controller'=>'diary', 'action'=>'view', 'id' => $record->getId()), 'default', true)}'>".$this->translate->_('view diary record')."</a>"
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

		$x['html'] = $this->view->partial('diary/rights.phtml', array (
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
	 * Returns a json list of users who have access to view the diary record
	 */
	public function usersAction() {
		// default return
		$return = array('success' => true);

		// get page and action
		$page = $this->request->getParam('page') ? intval($this->request->getParam('page')) : 1;
		$action = $this->request->getParam('modify') ? $this->request->getParam('modify') : null;

		// get record
		$record = $this->getRecord($this->request->getParam('id'), false, $return);

		// nobody/private
		if($record->getRights() == 0) {
			if(isset($action) && $action != null) {
				$return['notify'] = 0;
				$return['text'] = $this->translate->_("The diary record is private. Click on the modify button to change this or click on save to save the diary record subentry without sending any notification message.");
			} else
				$return['text'] = $this->translate->_("The diary record is private and cannot be visible for anyone.");

		// public
		} elseif($record->getRights() == 1) {
			if(isset($action) && $action != null) {
				$return['notify'] = 0;
				$return['text'] = $this->translate->_("The diary record is public. Click on the modify button to restrict to partners, friends or specific users and send them notification or click on save to save the diary record subentry without sending any notification message.");
			} else
				$return['text'] = $this->translate->_("This diary record is public and it's visible for every petolio user.");

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

			if(isset($action) && $action != null) $return['text'] = $this->translate->_("The following users will receive notifications that a new diary record subentry was added:");
			else $return['text'] = $this->translate->_("The following users can view this diary record:");

			// make good use of the social ratings template
			$return['html'] = $this->view->render("social/misc_ratings.phtml");
		}

		// return json
		return Petolio_Service_Util::json($return);
	}

    /**
     * Update diary record access
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
			$this->db->rights->getMapper()->getDbTable()->delete("diary_record_id = {$record->getId()}");

			// set new rights
	    	foreach($rights[1] as $id) {
				$record_rights = clone $this->db->rights;
				$record_rights->setDiaryRecordId($record->getId());
				$record_rights->setUserId($id);
				$record_rights->save();
			}

			// save the file access
			$record->setRights($rights[0]);
			$record->save();

			// get subentries for these records
			foreach($this->db->subentries->fetchList("diary_record_id = {$record->getId()}") as $two) {
				// update files as well
				if($two->getFolderId()) {
					// get the folder
					$folder = $this->db->folders->find($two->getFolderId())->toArray();

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