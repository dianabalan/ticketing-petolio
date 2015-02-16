<?php

class PetsController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $req = null;

	private $db = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->pets = new Petolio_Model_PoPets();
		$this->db->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();
		$this->db->flag = new Petolio_Model_PoFlags();
    }

    private function _filter() {
    	// get params
    	$keyword = $this->req->getParam("keyword", '');
    	$owner = $this->req->getParam("owner", '');
    	$archived = $this->req->getParam("archived", '');
    	$species = $this->req->getParam("species", '');

    	// output filters
    	$this->view->keyword = $keyword;
    	$this->view->owner = $owner;
    	$this->view->archived = $archived;
    	$this->view->species = $species;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// filter by species ?
    	$sort = array();
    	$this->view->types = array();
    	foreach($this->db->sets->getAttributeSets('po_pets') as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c['name']);
    		$sort[$k] = $_t;
    		$this->view->types[] = array('value'=> $c['id'], 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->types);

    	// handle filter
    	$where = array();

    	// keyword
    	if(strlen($keyword) > 0)
    		$where[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
	    		"OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
	    		"OR d2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
	    		"OR f2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
	    		"OR b.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%").")";

    	// owner
    	if(strlen($owner) > 0)
    		$where[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($owner)."%");

    	// archived
    	if(strlen($archived) > 0)
    		$where[] = "a.deleted = ".(int)$archived;

    	// species
    	if(strlen($species) > 0)
    		$where[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);

    	// return filters
    	return $where;
    }

	public function indexAction() {
    	// get filter
    	$where = $this->_filter();

    	// get pets
    	$paginator = $this->db->pets->getPets('paginator', count($where) > 0 ? implode(" AND ", $where) : 'a.deleted = 0 OR a.deleted = 1', "{$this->view->order} {$this->view->dir}", false, true);
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output attrs
    	$this->view->pets = $this->db->pets->formatPets($paginator);
    }

    /**
     * Edit pet
     */
    public function editPetAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$pet = $this->db->pets->find($id);
    	if(!$pet->getId()) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// get owner
    	$user = $this->db->user->find($pet->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Pet Owner does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// load pet attributes
    	$populate = array('flagged' => $pet->getFlagged());
    	$attributes = reset($this->db->attr->loadAttributeValues($pet));
    	foreach($attributes as $attr) {
    		$value = is_array($attr->getAttributeEntity()) ? reset($attr->getAttributeEntity()) : $attr->getAttributeEntity();
    		$populate[$attr->getCode()] = array("value" => $value->getValue(), "type" => $attr->getAttributeInputType()->getType());
		}

    	// send form
		$form = new Petolio_Form_Pet($pet->getAttributeSetId(), true);
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save pet
		$pet->setDateModified(date('Y-m-d H:i:s', time()));
		$pet->setToAdopt(0);

		// save flagged
		$pet->setFlagged($data['flagged']);
		unset($data['flagged']);

		// if we have a _sale attribute and it's set to "Yes" then set the to_adopt flag to 1
		foreach($data as $code => $value)
			if(substr($code, strrpos($code, '_sale'), 5) == '_sale' && isset($value) && $value == 1)
				$pet->setToAdopt(1);

		// save pet
		$pet->save(true, true);

		// save attributes
		$this->db->attr->saveAttributeValues($data, $pet->getId());

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("One of your pets has been edited"),
			'message_html' => sprintf($this->translate->_("Petolio Admin Team has edited %s"), "<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet'=> $pet->getId()), 'default', true)}'>{$attributes['name']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// redirect
		$this->msg->messages[] = $this->translate->_("The Pet has been saved successfully.");
		return $this->_redirect('admin/pets/index');
    }

    /**
     * Delete pet
     */
    public function deletePetAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$pet = $this->db->pets->find($id);
    	if(!$pet->getId()) {
    		$this->msg->messages[] = $this->translate->_("Pet does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// get owner
    	$user = $this->db->user->find($pet->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Pet Owner does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// get pet attributes
    	$pet_attributes = reset($this->db->attr->loadAttributeValues($pet, true));

    	// set switch
    	$switch = $pet->getDeleted() == 1 ? 0 : 1;
    	$pet->setDeleted($switch)->save();

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $switch == 1 ? $this->translate->_("One of your pets has been archived") : $this->translate->_("One of your pets has been restored"),
			'message_html' => $switch == 1 ? sprintf($this->translate->_("Petolio Admin Team has archived %s"), "<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'archives'), 'default', true)}'>{$pet_attributes['name']->getAttributeEntity()->getValue()}</a>") : sprintf($this->translate->_("Petolio Admin Team has restored %s"), "<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet'=> $pet->getId()), 'default', true)}'>{$pet_attributes['name']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

    	// msg and redirect
    	$this->msg->messages[] = $switch == 1 ? $this->translate->_("Pet was archived.") : $this->translate->_("Pet was restored.");
    	return $this->_redirect('admin/pets/index');
    }

    /**
     * List flags
     */
    public function listFlagsAction() {
    	// get params
    	$id = (int)$this->req->getParam("id", 0);
    	$user = $this->req->getParam("user", '');
    	$reason = $this->req->getParam("reason", '');

    	// output filters
    	$this->view->id = $id;
    	$this->view->user = $user;
    	$this->view->reason = $reason;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.date_flagged');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// handle filter
    	$where = array(
    		"scope = 'po_pets'",
    		"entry_id = {$id}"
    	);

    	// reason
    	if(strlen($reason) > 0)
    		$where[] = "y.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($reason)."%");

    	// user
    	if(strlen($user) > 0)
    		$where[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($user)."%");

    	// get flags
    	$paginator = $this->db->flag->getFlags('paginator', implode(" AND ", $where), "{$this->view->order} {$this->view->dir}", false, true);
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output flags
    	$this->view->flags = $paginator;
    }

    /**
     * Delete flag
     */
    public function deleteFlagAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$flag = $this->db->flag->find($id);
    	if(!$flag->getId()) {
    		$this->msg->messages[] = $this->translate->_("Flag does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// delete flag
    	$flag->deleteRowByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Flag was deleted.");
    	return $this->_redirect("admin/pets/list-flags/id/{$flag->getEntryId()}");
    }

    /**
     * Send msg
     */
    public function sendMsgAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$user = $this->db->user->find($id);
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("User does not exist.");
    		return $this->_redirect('admin/pets/index');
    	}

    	// output name
    	$this->view->user_name = $user->getName();

    	// init form
    	$form = new Petolio_Form_Reply(null, true, true);
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

		// get form data
		$data = $form->getValues();

		// send message to user
		Petolio_Service_Message::send(array(
			'subject' => $data['subject'],
			'message_html' => $data['message'],
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The message was successfully sent.");
		return $this->_redirect('admin/pets/index');
    }

    /**
     * Export to cvs
     */
    public function exportCsvAction() {
		// get filter
		$where = $this->_filter();

    	// get pets
    	$data = $this->db->pets->formatPets($this->db->pets->getPets('array', count($where) > 0 ? implode(" AND ", $where) : 'a.deleted = 0 OR a.deleted = 1', "{$this->view->order} {$this->view->dir}", false, true));
    	foreach($data as &$one) {
			// skip this data
			unset(
				$one['user_id'],
				$one['attribute_set_id'],
				$one['gender_id'],
				$one['flagged_count'],
				$one['translation_1'],
				$one['translation_2']
			);

			// transform this data
			$one['archived'] = $one['deleted'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No'); unset($one['deleted']);
			$one['to_adopt'] = $one['to_adopt'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
			$one['mobile_emergency'] = $one['mobile_emergency'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
			$one['flagged'] = $one['flagged'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
		}

    	// figure out header
		$header = array_keys(reset($data));
		foreach($header as &$one)
			$one = ucfirst(str_replace(array("_id", "_"), array("", " "), $one));

		// output as csv
		$out = $this->_array_to_CSV($header);
		foreach($data as $one)
			$out .= $this->_array_to_CSV($one);

		// send headers
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=users-".time().".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		// die with data :P
		die($out);
    }

    /**
     * Return csv as string from array
     * @param array $data
     * @return string
     */
    private function _array_to_CSV($data)
    {
    	$outstream = fopen("php://temp", 'r+');
    	fputcsv($outstream, $data, ',', '"');
    	rewind($outstream);
    	$csv = fgets($outstream);
    	fclose($outstream);

    	return $csv;
    }
}