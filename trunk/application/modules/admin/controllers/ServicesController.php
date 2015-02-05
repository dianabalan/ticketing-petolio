<?php

class ServicesController extends Zend_Controller_Action
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
		$this->db->services = new Petolio_Model_PoServices();
		$this->db->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();
		$this->db->flag = new Petolio_Model_PoFlags();
    }

    private function _filter() {
    	// get params
    	$keyword = $this->req->getParam("keyword", '');
    	$owner = $this->req->getParam("owner", '');
    	$archived = $this->req->getParam("archived", '');
    	$category = $this->req->getParam("category", '');

    	// output filters
    	$this->view->keyword = $keyword;
    	$this->view->owner = $owner;
    	$this->view->archived = $archived;
    	$this->view->category = $category;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// filter by categories ?
    	$sort = array();
    	$this->view->types = array();
    	foreach($this->db->sets->getAttributeSets('po_services') as $k => $c) {
    		if(isset($c['group_name']) && strlen($c['group_name']) > 0) {
    			$_t = array(Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($c['group_name'])), Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($c['name'])));
    			$sort[0][$k] = $_t[0];
    			$sort[1][$k] = $_t[1];
    			$this->view->types[] = array(
    				'value' => $c['id'],
    				'category' => $_t[0],
    				'name' => $_t[1]
    			);
    		}
    	} array_multisort($sort[0], SORT_ASC, $sort[1], SORT_ASC, $this->view->types);

    	// handle filter
    	$where = array();

		// keyword
		if(strlen($keyword) > 0)
			$where[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
				"OR d2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%").")";

		// owner
		if(strlen($owner) > 0)
			$where[] = "u.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($owner)."%");

		// archived
		if(strlen($archived) > 0)
			$where[] = "a.deleted = ".(int)$archived;

		// category
		if(strlen($category) > 0)
			$where[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($category, Zend_Db::BIGINT_TYPE);

    	// return filters
    	return $where;
    }

	public function indexAction() {
		// get filter
		$where = $this->_filter();

    	// get services
    	$paginator = $this->db->services->getServices('paginator', count($where) > 0 ? implode(" AND ", $where) : 'a.deleted = 0 OR a.deleted = 1', "{$this->view->order} {$this->view->dir}", false, true);
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output attrs
    	$this->view->services = $this->db->services->formatServices($paginator);
    }

    /**
     * Edit service
     */
    public function editServiceAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$service = $this->db->services->find($id);
    	if(!$service->getId()) {
    		$this->msg->messages[] = $this->translate->_("Service does not exist.");
    		return $this->_redirect('admin/services/index');
    	}

    	// get owner
    	$user = $this->db->user->find($service->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Service Owner does not exist.");
    		return $this->_redirect('admin/services/index');
    	}

    	// send form
    	$form = new Petolio_Form_Service($service->getAttributeSetId(), true);

    	// load service attributes
    	$populate = array('flagged' => $service->getFlagged());
		$original_attributes = reset($this->db->attr->loadAttributeValues($service));
		if ( !($this->req->isPost()) || !$form->isValid($this->req->getPost()) ) {
			$data = array();
			foreach($original_attributes as $attr) {
				if ( !$attr->getGroupId() && !is_array($attr->getAttributeEntity()) ) {
					$populate[$attr->getCode()] = array (
						"value" => $attr->getAttributeEntity()->getValue(),
						"type" => $attr->getAttributeInputType()->getType()
					);
					if ( $attr->getHasDescription() && intval($attr->getHasDescription()) == 1 && !isset($_REQUEST[$attr->getCode().'_description']) ) {
						$_REQUEST[$attr->getCode().'_description'] = $attr->getAttributeEntity()->getDescription();
					}
				} elseif ( is_array($attr->getAttributeEntity()) ) {
					if ( !isset($data[$attr->getGroup()->getName()]) ) {
						$data[$attr->getGroup()->getName()] = array();
					}
					foreach ($attr->getAttributeEntity() as $entity) {
						if ( !isset($data[$attr->getGroup()->getName()][$entity->getEntityId()]) ) {
							$data[$attr->getGroup()->getName()][$entity->getEntityId()] = array();
						}

						// format value
						$display_value = $entity->getValue();
						$save_value = $entity->getValue();
						$atype = $attr->getAttributeInputType()->getType();
						if ( $attr->getCurrencyId() && intval($attr->getCurrencyId()) > 0 && ($atype == 'decimal' || $atype == 'int') ) {
							$display_value = Petolio_Service_Util::formatCurrency($entity->getValue(), $attr->getCurrency()->getCode());
						}
						if($atype == 'datetime') {
							$display_value = Petolio_Service_Util::formatDate($entity->getValue(), null, false);
						}
						if($atype == 'select') {
							$options = new Petolio_Model_PoAttributeOptions();
							$result = $options->getMapper()->findByField('id', $entity->getValue(), $options);
							$optVal = is_array($result) && count($result) > 0 ? reset($result) : null;
							if(!is_null($optVal)) {
						       	// translate entity value
								$display_value = Petolio_Service_Util::Tr($optVal->getValue());
							}
						}
						if ( !(isset($display_value) && strlen($display_value) > 0 && strcasecmp($display_value, 'null') != 0) ) {
							$display_value = '';
						}
						if ( !($entity->getValue() && strlen($entity->getValue()) > 0 && strcasecmp($entity->getValue(), 'null') != 0) ) {
							$save_value = '';
						}

						array_push($data[$attr->getGroup()->getName()][$entity->getEntityId()], array(
							"id" => $attr->getCode(),
							"label" => Petolio_Service_Util::Tr($attr->getLabel()),
							"display_value" => Petolio_Service_Util::unescape($display_value), // this will be escaped later
							"save_value" => Petolio_Service_Util::unescape($save_value) // this will be escaped later
						));

					}
				}
			}

			$this->view->subform_data = array();
			foreach ($data as $group_name => $group_items) {
				foreach ($group_items as $items) {
					array_push($this->view->subform_data, array(
						"group_name" => $group_name,
						"items" => json_encode($items)
					));
				}
			}
		}

		// populate members limit field
		$populate['members_limit'] = intval($service->getMembersLimit()) == 0 ? '' : $service->getMembersLimit();

		// populate form
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost())) {
			// reseting the submit button value, I don't know why this is empty
			$form->getElement('submit')->setValue($this->translate->_('Save Service >'));
			// populate back the attribute group values
			$attributes = new Petolio_Model_PoAttributes();
			$data = array();
			foreach ($attributes->getMapper()->getDbTable()->getAttributes($service->getAttributeSetId()) as $idx => $field) {
				if ( isset($field['group_name']) && strlen($field['group_name']) > 0 && strcasecmp($field['group_name'], 'null') != 0 ) {
					// resetting the add button values, I don't know why they are empty
					$form->getSubForm($field['group_name'])->getElement('subform_submit_'.$field['group_name'])->setValue('Add '.Petolio_Service_Util::Tr($field['group_name']));
					$code = str_replace('-', '', $field['code']);
					$display_values = $this->req->getParam($code.'_display');
					if ( $this->req->getParam($code.'_value') && is_array($this->req->getParam($code.'_value')) ) {
						foreach ($this->req->getParam($code.'_value') as $key => $value) {
							if ( !isset($data[$field['group_name']]) ) {
								$data[$field['group_name']] = array();
							}
							if ( !isset($data[$field['group_name']][$key]) ) {
								$data[$field['group_name']][$key] = array();
							}
							array_push($data[$field['group_name']][$key], array(
								"id" => $code,
								"label" => Petolio_Service_Util::Tr($field['label']),
								"display_value" => $display_values[$key],
								"save_value" => $value
							));
						}
					}
				}
			}

			$this->view->subform_data = array();
			foreach ($data as $group_name => $group_items) {
				foreach ($group_items as $items) {
					array_push($this->view->subform_data, array(
						"group_name" => $group_name,
						"items" => json_encode($items)
					));
				}
			}

			return false;
		}

		// get data
		$data = $form->getValues();

		// save limit
		if(isset($data['members_limit']) ) {
			if (strlen($data['members_limit']) <= 0)
				$data['members_limit'] = '0';

			$service->setMembersLimit($data['members_limit']);
			unset($data['members_limit']);
		}

		// save flagged
		$service->setFlagged($data['flagged']);
		unset($data['flagged']);

		// save service
		$service->setDateModified(date('Y-m-d H:i:s', time()));
		$service->save(true, true);

    	// check if we have a group
		$attributes = new Petolio_Model_PoAttributes();
		$group_data = array();
		$simple_data = array();
		foreach ($attributes->getMapper()->getDbTable()->getAttributes($service->getAttributeSetId()) as $idx => $field) {
			if ( isset($field['group_id']) && strlen($field['group_id']) > 0 && strcasecmp($field['group_id'], 'null') != 0 ) {
				if ( $this->req->getParam($field['code'].'_value') && is_array($this->req->getParam($field['code'].'_value')) ) {
					foreach ($this->req->getParam($field['code'].'_value') as $key => $value) {
						if ( !isset($group_data[$field['group_id']]) ) {
							$group_data[$field['group_id']] = array();
						}
						if ( !isset($group_data[$field['group_id']][$key]) ) {
							$group_data[$field['group_id']][$key] = array();
						}
						$group_data[$field['group_id']][$key][$field['code']] = $value;
					}
				}
			} else {
				$simple_data[$field['code']] = $data[$field['code']];
			}
		}

		// save simple attributes
		$this->db->attr->saveAttributeValues($simple_data, $service->getId());

		// delete old group entities
		$group_entities = new Petolio_Model_PoAttributeGroupEntities();
		$group_entities->getMapper()->getDbTable()->deleteGroupEntities($service->getId());

		// save group attributes
		foreach ($group_data as $group_id => $group_items) {
			// insert attribute values for each row
			foreach ($group_items as $items) {
				// add new group entity
				$group_entities = new Petolio_Model_PoAttributeGroupEntities();
				$group_entities->setGroupId($group_id);
				$group_entities->setEntityId($service->getId());
				$group_entities->save();

				$this->db->attr->saveAttributeValues($items, $group_entities->getId());
			}
		}

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("One of your services has been edited"),
			'message_html' => sprintf($this->translate->_("Petolio Admin Team has edited %s"), "<a href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service'=> $service->getId()), 'default', true)}'>{$original_attributes['name']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// redirect
		$this->msg->messages[] = $this->translate->_("The Service has been saved successfully.");
		return $this->_redirect('admin/services/index');
    }

    /**
     * Delete service
     */
    public function deleteServiceAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$service = $this->db->services->find($id);
    	if(!$service->getId()) {
    		$this->msg->messages[] = $this->translate->_("Service does not exist.");
    		return $this->_redirect('admin/services/index');
    	}

    	// get owner
    	$user = $this->db->user->find($service->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Service Owner does not exist.");
    		return $this->_redirect('admin/services/index');
    	}

    	// get service attributes
    	$service_attributes = reset($this->db->attr->loadAttributeValues($service, true));

    	// set switch
    	$switch = $service->getDeleted() == 1 ? 0 : 1;
    	$service->setDeleted($switch)->save();

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $switch == 1 ? $this->translate->_("One of your services has been archived") : $this->translate->_("One of your services has been restored"),
			'message_html' => $switch == 1 ? sprintf($this->translate->_("Petolio Admin Team has archived %s"), "<a href='{$this->view->url(array('controller'=>'services', 'action'=>'archives'), 'default', true)}'>{$service_attributes['name']->getAttributeEntity()->getValue()}</a>") : sprintf($this->translate->_("Petolio Admin Team has restored %s"), "<a href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service'=> $service->getId()), 'default', true)}'>{$service_attributes['name']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

    	// msg and redirect
    	$this->msg->messages[] = $switch == 1 ? $this->translate->_("Service was archived.") : $this->translate->_("Service was restored.");
    	return $this->_redirect('admin/services/index');
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
    		"scope = 'po_services'",
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
    		return $this->_redirect('admin/services/index');
    	}

    	// delete flag
    	$flag->deleteRowByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Flag was deleted.");
    	return $this->_redirect("admin/services/list-flags/id/{$flag->getEntryId()}");
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
    		return $this->_redirect('admin/services/index');
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
		return $this->_redirect('admin/services/index');
    }

    /**
     * Export to cvs
     */
    public function exportCsvAction() {
		// get filter
		$where = $this->_filter();

    	// get pets
    	$data = $this->db->services->formatServices($this->db->services->getServices('array', count($where) > 0 ? implode(" AND ", $where) : 'a.deleted = 0 OR a.deleted = 1', "{$this->view->order} {$this->view->dir}", false, true));

    	foreach($data as &$one) {
			// skip this data
			unset(
				$one['user_id'],
				$one['attribute_set_id'],
				$one['folder_id'],
				$one['scope'],
				$one['flagged_count']
			);

			// transform this data
			$one['archived'] = $one['deleted'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No'); unset($one['deleted']);
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