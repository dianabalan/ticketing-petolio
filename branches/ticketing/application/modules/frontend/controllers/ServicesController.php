<?php

class ServicesController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $up = null;
    private $yt_name = null;
    private $auth = null;
    private $request = null;
    private $cfg = null;

    private $services = null;
    private $smap = null;

    private $imgs = null;
    private $imap = null;
    private $folders = null;

    private $attr = null;
    private $sets = null;

    private $flag = null;

    private $unlisted = null;

    private $europe = array(48.690832999999998, 9.140554999999949);
    
    public function preDispatch()
    {
		// send auth to template
		$this->view->auth = $this->auth;
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
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		$this->services = new Petolio_Model_PoServices();
		$this->smap = new Petolio_Model_PoServicesMapper();

		$this->imgs = new Petolio_Model_PoFiles();
		$this->imap = new Petolio_Model_PoFilesMapper();
		$this->folders = new Petolio_Model_PoFolders();

		$this->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->sets = new Petolio_Model_DbTable_PoAttributeSets();

		$this->flag = new Petolio_Model_PoFlags();
		$this->view->request = $this->request;

		// set unlisted params
		$this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
				array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
				array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));
    }

    /**
     * Render service options (the left side menu)
     */
    private function serviceOptions($service, $attr, $type = 0)
    {
    	$this->view->service = $service;
    	$this->view->service_attr = $attr;
    	$this->view->service_type = $type;
    	$this->view->render('services/service-options.phtml');
    }

    public function indexAction()
    {
    	// start
    	$this->view->search = true;

		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have a service provider account.");
			return $this->_redirect('site');
		}

		// load user services and load all services
		$this->loadUserServices($this->request->getParam('page'));
    }

    /**
     * Load user services
     * @param string $page
     */
	private function loadUserServices($page)
    {
		// search by name
		$name = $this->request->getParam('name');
		$name = empty($name) ? null : $name;
		$this->view->search_name = $name;

		// search by name ?
		if(isset($name)) $this->view->title = $this->translate->_("Results, Search:")." " . $name;
		else $this->view->title = $this->translate->_("Your Services");

		// get page
		$page = $page ? $page : 0;

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

		// build filter
		$filter = array("a.user_id = {$this->auth->getIdentity()->id}");
		if(!is_null($name))
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($name)."%")." OR d2.value LIKE '%".strtolower($name)."%')";

		// get services
		$paginator = $this->services->getServices('paginator', implode(' AND ', $filter), $sort, false, true);
		$paginator->setItemCountPerPage($this->cfg["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output all services
		$this->view->yours = $this->services->formatServices($paginator);
    }

    public function addAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to create a service.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to create a service.");
			return $this->_redirect('site');
		}

		// step 1 or step 2 ?
    	if(!is_null($this->request->getParam('type')))
			$this->step2();
		else
			$this->step1();
    }

    private function step1()
    {
		// init form
		$form = new Petolio_Form_Service();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// redirect
		$data = $form->getValues();
		return $this->_redirect('services/add/type/'. $data['attribute_set']);
    }

    private function step2()
    {
		// init form
		$attr_sets = new Petolio_Model_PoAttributeSets();
		$attr_sets->find($this->request->getParam('type'));
		if(!$attr_sets->getId()) {
			$this->msg->messages[] = $this->translate->_("Service type not found");
			return $this->_redirect('site');
		}

		$this->view->attribute_set_name = $attr_sets->getName();

		$form = new Petolio_Form_Service($attr_sets->getId());
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost())) {
			return false;
		}

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost())) {
			// reseting the submit button value, I don't know why this is empty
			$form->getElement('submit')->setValue($this->translate->_('Save Service >'));
			// populate back the attribute group values
			$attributes = new Petolio_Model_PoAttributes();
			$data = array();
			foreach ($attributes->getMapper()->getDbTable()->getAttributes($this->request->getParam('type')) as $idx => $field) {
				if(isset($field['group_name']) && strlen($field['group_name']) > 0 && strcasecmp($field['group_name'], 'null') != 0) {
					// resetting the add button values, I don't know why they are empty
					$form->getSubForm($field['group_name'])->getElement('subform_submit_'.$field['group_name'])->setValue('Add '.Petolio_Service_Util::Tr($field['group_name']));
					$code = str_replace('-', '', $field['code']);
					$display_values = $this->request->getParam($code.'_display');
					if($this->request->getParam($code.'_value') && is_array($this->request->getParam($code.'_value'))) {
						foreach ($this->request->getParam($code.'_value') as $key => $value) {
							if(!isset($data[$field['group_name']])) {
								$data[$field['group_name']] = array();
							}
							if(!isset($data[$field['group_name']][$key])) {
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
			$subform_data = array();
			foreach ($data as $group_name => $group_items) {
				foreach ($group_items as $items) {
					array_push($subform_data, array(
						"group_name" => $group_name,
						"items" => json_encode($items)
					));
				}
			}
			$this->view->subform_data = $subform_data;
			return false;
		}

		// get data
		$data = $form->getValues();

		// set options
		$this->services->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'attribute_set_id' => $this->request->getParam('type')
		));

		// handle member limit
		if(isset($data['members_limit']) && strlen($data['members_limit']) > 0) {
			$this->services->setMembersLimit($data['members_limit']);
			unset($data['members_limit']);
		}

		// save service
		$this->services->save(true, true);

		// check if we have a group
		$attributes = new Petolio_Model_PoAttributes();
		$group_data = array();
		$simple_data = array();
		foreach ($attributes->getMapper()->getDbTable()->getAttributes($this->request->getParam('type')) as $idx => $field) {
			if(isset($field['group_id']) && strlen($field['group_id']) > 0 && strcasecmp($field['group_id'], 'null') != 0) {
				if($this->request->getParam($field['code'].'_value') && is_array($this->request->getParam($field['code'].'_value'))) {
					foreach ($this->request->getParam($field['code'].'_value') as $key => $value) {
						if(!isset($group_data[$field['group_id']])) {
							$group_data[$field['group_id']] = array();
						}
						if(!isset($group_data[$field['group_id']][$key])) {
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
		$this->attr->saveAttributeValues($simple_data, $this->services->getId());

		// save group attributes
		foreach ($group_data as $group_id => $group_items) {
			// insert attribute values for each row
			foreach ($group_items as $items) {
				// add new group entity
				$group_entities = new Petolio_Model_PoAttributeGroupEntities();
				$group_entities->setGroupId($group_id);
				$group_entities->setEntityId($this->services->getId());
				$group_entities->save(true, true);

				$this->attr->saveAttributeValues($items, $group_entities->getId());
			}
		}

		// load attributes
		$attributes = reset($this->attr->loadAttributeValues($this->services));
		$this->serviceOptions($this->services, $attributes);

		// do html
		$name = Petolio_Service_Parse::do_limit(ucfirst($this->view->service_attr['name']->getAttributeEntity()->getValue()), 20, false, true);
		$reply = $this->view->url(array('controller'=>'services', 'action'=>'view', 'service'=>$this->services->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has registered a new <u>Service</u>: %2$s');
		$html = array(
			'%1$s has registered a new <u>Service</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$name}</a>"
		);

		// send AMQPC
    	Petolio_Service_AMQPC::sendMessage('service', array($html, $reply, $this->auth->getIdentity()->id));

		// redirect to services index action
		$this->msg->messages[] = $this->translate->_("Your service has been added successfully.");
		return $this->_redirect('services/pictures/service/'. $this->services->getId());
    }

    public function editAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to edit a service.");
			return $this->_redirect('site');
		}

    	// get service
    	$result = $this->smap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('service'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted != 1");
		if(!( is_array($result) && count($result) > 0 )) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_redirect('site');
		} else
			$service = reset($result);

		$this->view->attribute_set_name = $service->getAttributeSetName();

		// init form
		$form = new Petolio_Form_Service($service->getAttributeSetId());

		// load service attributes
		$populate = array();
		$attributes = reset($this->attr->loadAttributeValues($service));
		if(!($this->request->isPost()) || !$form->isValid($this->request->getPost())) {
			$data = array();
			foreach($attributes as $attr) {
				if(!$attr->getGroupId() && !is_array($attr->getAttributeEntity())) {
					$populate[$attr->getCode()] = array (
						"value" => $attr->getAttributeEntity()->getValue(),
						"type" => $attr->getAttributeInputType()->getType()
					);
					if($attr->getHasDescription() && intval($attr->getHasDescription()) == 1 && !isset($_REQUEST[$attr->getCode().'_description'])) {
						$_REQUEST[$attr->getCode().'_description'] = $attr->getAttributeEntity()->getDescription();
					}
				} elseif(is_array($attr->getAttributeEntity())) {
					if(!isset($data[$attr->getGroup()->getName()])) {
						$data[$attr->getGroup()->getName()] = array();
					}
					foreach ($attr->getAttributeEntity() as $entity) {
						if(!isset($data[$attr->getGroup()->getName()][$entity->getEntityId()])) {
							$data[$attr->getGroup()->getName()][$entity->getEntityId()] = array();
						}

						// format value
						$display_value = $entity->getValue();
						$save_value = $entity->getValue();
						$atype = $attr->getAttributeInputType()->getType();
						if($attr->getCurrencyId() && intval($attr->getCurrencyId()) > 0 && ($atype == 'decimal' || $atype == 'int')) {
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
						if(!(isset($display_value) && strlen($display_value) > 0 && strcasecmp($display_value, 'null') != 0)) {
							$display_value = '';
						}
						if(!($entity->getValue() && strlen($entity->getValue()) > 0 && strcasecmp($entity->getValue(), 'null') != 0)) {
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

    	// load service options
		$this->serviceOptions($service, $attributes);

		// populate members limit field
		$populate['members_limit'] = intval($service->getMembersLimit()) == 0 ? '' : $service->getMembersLimit();

		// init form
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost())) {
			return false;
		}

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost())) {
			// reseting the submit button value, I don't know why this is empty
			$form->getElement('submit')->setValue($this->translate->_('Save Service >'));
			// populate back the attribute group values
			$attributes = new Petolio_Model_PoAttributes();
			$data = array();
			foreach ($attributes->getMapper()->getDbTable()->getAttributes($service->getAttributeSetId()) as $idx => $field) {
				if(isset($field['group_name']) && strlen($field['group_name']) > 0 && strcasecmp($field['group_name'], 'null') != 0) {
					// resetting the add button values, I don't know why they are empty
					$form->getSubForm($field['group_name'])->getElement('subform_submit_'.$field['group_name'])->setValue('Add '.Petolio_Service_Util::Tr($field['group_name']));
					$code = str_replace('-', '', $field['code']);
					$display_values = $this->request->getParam($code.'_display');
					if($this->request->getParam($code.'_value') && is_array($this->request->getParam($code.'_value'))) {
						foreach ($this->request->getParam($code.'_value') as $key => $value) {
							if(!isset($data[$field['group_name']])) {
								$data[$field['group_name']] = array();
							}
							if(!isset($data[$field['group_name']][$key])) {
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

		// save service
		$service->setDateModified(date('Y-m-d H:i:s', time()));
		if(isset($data['members_limit'])) {
			if(strlen($data['members_limit']) <= 0) {
				$data['members_limit'] = '0';
			}
			$service->setMembersLimit($data['members_limit']);
			unset($data['members_limit']);
		}
		$service->save(true, true);

		// check if we have a group
		$attributes = new Petolio_Model_PoAttributes();
		$group_data = array();
		$simple_data = array();
		foreach ($attributes->getMapper()->getDbTable()->getAttributes($service->getAttributeSetId()) as $idx => $field) {
			if(isset($field['group_id']) && strlen($field['group_id']) > 0 && strcasecmp($field['group_id'], 'null') != 0) {
				if($this->request->getParam($field['code'].'_value') && is_array($this->request->getParam($field['code'].'_value'))) {
					foreach ($this->request->getParam($field['code'].'_value') as $key => $value) {
						if(!isset($group_data[$field['group_id']])) {
							$group_data[$field['group_id']] = array();
						}
						if(!isset($group_data[$field['group_id']][$key])) {
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
		$this->attr->saveAttributeValues($simple_data, $service->getId());

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

				$this->attr->saveAttributeValues($items, $group_entities->getId());
			}
		}

		// redirect
		$this->msg->messages[] = $this->translate->_("Your service has been edited successfully.");
		return $this->_redirect('services/map/service/'. $service->getId());
    }

    public function viewAction()
    {
		// get service
		$this->services->find($this->request->getParam('service'));
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_helper->redirector('index', 'site');
		}
		if($this->services->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("This service has been removed by the owner.");
			return $this->_helper->redirector('index', 'site');
		}

		// see if the product owner is active and not banned
		if(!($this->services->getOwner()->getActive() == 1 && $this->services->getOwner()->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		$this->buildTypes();
		
    	// if flagged, load reasons
		$this->view->flagged = array();
		if($this->services->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->flag->getMapper()->fetchList("scope = 'po_services' AND entry_id = '{$this->services->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// if service is yours tell me :)
		$admin = false;
		if(isset($this->auth->getIdentity()->id))
			$admin = $this->services->getUserId() == $this->auth->getIdentity()->id ? true : false;

		// get service attributes
		$attribute_set = new Petolio_Model_PoAttributeSets();
		$attribute_set->find($this->services->getAttributeSetId());

		// get service members users
        if($attribute_set->getType() == 1) {
        	$this->view->accepted_members_users = $this->services->getMembersUsers(1);
        	if($admin) {
        		$this->view->requested_members_users = $this->services->getMembersUsers(0);
        		//$this->view->declined_members_users = $this->services->getMembersUsers(2);
        	}

		// get service members pets
        } else {
        	$attributes = new Petolio_Model_PoAttributes();
			$accepted_members_pets = $this->services->getMembersPets(1);
			$this->view->accepted_members_pets = $accepted_members_pets;
			if($admin) {
				$this->view->requested_members_pets = $this->services->getMembersPets(0);
				//$this->view->declined_members_pets = $this->services->getMembersPets(2);
			}
        }
        
        // how many members (pets or users) have space in the service's front page
        $this->view->member_limit = 7;

        // count all user products
		$products = new Petolio_Model_PoProducts();
        $this->view->products_count = $products->countByQuery("user_id = {$this->services->getUserId()} AND archived != 1");
        	
        // count all user services
        $services = new Petolio_Model_PoServices();
        $this->view->services_count = $services->countByQuery("user_id = {$this->services->getUserId()} AND deleted != 1");;
        	
        // count service reviews
        $comments = new Petolio_Model_PoComments();
        $this->view->reviews_count = $comments->countByQuery("scope = 'po_services' AND entity_id = {$this->services->getId()}");
        
        // check if we have to show the partners/members list or not
        $this->view->show_partners_list = $this->isPartnerOrMember($admin, $attribute_set);

		// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// load service appointments
		if($admin) {
        	list($this->view->service_apps, $this->view->service_apps_json) = $this->loadServiceAppointments($this->services, $this->request->getParam('your-page'));
        }

        // get pictures
        if($this->services->getFolderId()) {
			$pictures = array();
			$gallery = $this->folders->getMapper()->getDbTable()->findFolders(array('id' => $this->services->getFolderId()));
			if(isset($gallery)) {
				$files = new Petolio_Model_PoFiles();
				$pictures = $files->getMapper()->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}'", "date_created ASC");
			}
			if(isset($pictures) && count($pictures) > 0) {
				$this->view->listing = array();
				foreach($pictures as $pic)
					$this->view->listing[$pic->getId()] = $pic->getFile();
			}
        }

        // get pet videos
        $videos = array();
        $media = $this->folders->getMapper()->getDbTable()->findFolders(array('id' => $this->services->getFolderId()));
        if(isset($media)) $videos = $this->imap->fetchList("type = 'video' AND folder_id = '{$media->getId()}'", "id ASC", 14);
        if(isset($videos) && count($videos) > 0) {
        	// youtube wrapper
        	$youtube = Petolio_Service_YouTube::factory('Master');
        	$youtube->CFG = array(
        		'username' => $this->cfg["youtube"]["username"],
        		'password' => $this->cfg["youtube"]["password"],
        		'app' => $this->cfg["youtube"]["app"],
        		'key' => $this->cfg["youtube"]["key"]
        	);

        	// needed upfront
        	$ds = DIRECTORY_SEPARATOR;
        	$upload_dir = "..{$ds}data{$ds}userfiles{$ds}services{$ds}{$this->services->getId()}{$ds}";

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

    	// send to template
    	$this->view->attributes = reset($this->attr->loadAttributeValues($this->services, true));
    	$this->view->auth = $this->auth;
    	$this->view->admin = $admin;

    	// load service options
		$this->serviceOptions($this->services, $this->view->attributes, $attribute_set->getType());
    }

    /**
     * loads services upcoming appointments
     *
     * @param Petolio_Model_PoServices $service
     * @param int $page
     */
    private function loadServiceAppointments($service, $page = 0) {
		// format event in calendar template
		$in = array();
		$cal = new Petolio_Model_PoCalendar();
		foreach($cal->getMapper()->browseServiceEvents($service->getId(), $this->auth->getIdentity()->id) as $line) {
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
		$result->setItemCountPerPage($this->cfg["events"]["pagination"]["itemsperpage"]);
		$result->setCurrentPageNumber($page);

		// prep for json encode
		$out = array();
		foreach($result as $line)
			$out[] = $line;

		// return json and object
		return array($result, json_encode($out));
    }

    public function requestPartnershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to request a partnership.");
			return $this->_redirect('site');
		}

    	// get service
    	$this->services->find($this->request->getParam('service'));
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_redirect('site');
		}

		// service is not type 0
		$attribute_set = $this->services->getAttributeSet();
		if($attribute_set->getType() != 0) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_redirect('site');
		}

		$form = new Petolio_Form_RequestPartnership();
		$service_attributes = reset($this->attr->loadAttributeValues($this->services, true));

    	// send to template
    	$this->view->service = $this->services;
    	$this->view->attributes = $service_attributes;
    	$this->view->form = $form;

    	// load service options
		$this->serviceOptions($this->view->service, $this->view->attributes, $attribute_set->getType());

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();
		$service_owner = $this->services->getOwner();

		// check if already has a request
		$link = new Petolio_Model_PoServiceMembersPets();
		$result = $link->fetchList("pet_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($data['pet_id'], Zend_Db::BIGINT_TYPE)." AND service_id = {$this->services->getId()}");
		if(is_array($result) && count($result) > 0 ):
			$this_link = reset($result);
			switch ($this_link->getStatus()) {
				case "0":
					$this->msg->messages[] = $this->translate->_("You already have a partnership request for this pet that is waiting for approval.");
					break;
				case "1":
					$this->msg->messages[] = $this->translate->_("You already have a partnership request for this pet that has been accepted.");
					break;
				case "2":
					$this->msg->messages[] = $this->translate->_("You already have a partnership request for this pet that has been declined.");
					break;
				case "3":
					$this->msg->messages[] = $this->translate->_("You have an invitation to create a partnership link for this pet.");
					$this->msg->messages[] = sprintf(
							$this->translate->_('You can %1$s or %2$s the invitation.'),
							"<a href='{$this->view->url(array('controller'=>'services', 'action'=>'accept-invite', 'link' => $this_link->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
							"<a href='{$this->view->url(array('controller'=>'services', 'action'=>'decline-invite', 'link' => $this_link->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>"
						);
					break;

				default:
					$this->msg->messages[] = $this->translate->_("You already have a partnership request for this pet.");
					break;
			}
			return $this->_helper->redirector('request-partnership', 'services', 'frontend', array('service' => $this->services->getId()));
		endif;

		// save data
		$member_pets = new Petolio_Model_PoServiceMembersPets();
		$member_pets->setPetId($data["pet_id"]);
		$member_pets->setServiceId($this->services->getId());
		$member_pets->save(true, true);

		// get pet name and breed
		$pet = new Petolio_Model_PoPets();
		$pets = $pet->formatPets($pet->getPets('array', "a.id = {$member_pets->getPetId()}"));
		$pet = reset($pets);

		$html = sprintf(
					$this->translate->_('%1$s and his pet %2$s (%3$s) has asked for partnership with your service: %4$s'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $member_pets->getPetId()), 'default', true)}'>{$pet['name']}</a>",
					$pet['breed'],
					$service_attributes['name']->getAttributeEntity()->getValue()
				) . '<br /><br />';
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= sprintf(
					$this->translate->_("%s has sent you the following message:"),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				) . "<br/>" . nl2br($data['message']) . '<br /><br />';
		}
		$html .= sprintf(
					$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'accept-partnership', 'link' => $member_pets->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'decline-partnership', 'link' => $member_pets->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				);

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Request for partnership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect
		$this->msg->messages[] = $this->translate->_("The message was successfully sent.");
		return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $this->services->getId()));
    }

    public function acceptPartnershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a partnership.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to accept a partnership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_pets = new Petolio_Model_PoServiceMembersPets();
        $service_members_pets->find($link_id);
        if(!$service_members_pets->getId()) {
			$this->msg->messages[] = $this->translate->_("Partnership link was not found.");
			return $this->_redirect('site');
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

		// get data
		$data = $form->getValues();

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_pets->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // check if the logged in user is the service owner
        if($service->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner has access to accept or decline a partnership request.");
			return $this->_redirect('site');
        }

        $service_members_pets->setStatus(1);
        $service_members_pets->save(true, true);

        // get data
        $attributes = new Petolio_Model_PoAttributes();
        $pet = $service_members_pets->getMemberPet();
        $pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($pet, true));
        $pet_name = $pet_attributes['name']->getAttributeEntity()->getValue();
        $pet_owner = $pet->getOwner();

        $html = sprintf(
    				$this->translate->_('%1$s accepted the partnership with your pet %2$s for the service %3$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				$pet_name,
    				$service_name
				);
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: partnership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $pet_owner->getId(),
			'name' => $pet_owner->getName(),
			'email' => $pet_owner->getEmail()
		)), $pet_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Partnership accepted with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_pets->getServiceId()));
    }

    public function declinePartnershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a partnership.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to decline a partnership.");
			return $this->_redirect('site');
		}

		$link_id = $this->request->getParam('link');
        $service_members_pets = new Petolio_Model_PoServiceMembersPets();
        $service_members_pets->find($link_id);
        if(!$service_members_pets->getId()) {
			$this->msg->messages[] = $this->translate->_("Partnership link was not found.");
			return $this->_helper->redirector('index', 'site');
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

		// get data
		$data = $form->getValues();

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_pets->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // check if the logged in user is the service owner
        if($service->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner has access to accept or decline a partnership request.");
			return $this->_redirect('site');
        }

        $service_members_pets->setStatus(2);
        $service_members_pets->save(true, true);

        // get data
        $attributes = new Petolio_Model_PoAttributes();
        $pet = $service_members_pets->getMemberPet();
        $pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($pet, true));
        $pet_name = $pet_attributes['name']->getAttributeEntity()->getValue();
        $pet_owner = $pet->getOwner();

        $html = sprintf(
    				$this->translate->_('%1$s declined the partnership with your pet %2$s for the service %3$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				$pet_name,
    				$service_name
				);
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: partnership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $pet_owner->getId(),
			'name' => $pet_owner->getName(),
			'email' => $pet_owner->getEmail()
		)), $pet_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Partnership declined with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_pets->getServiceId()));
    }

    /**
     * deletes a partnership link
     * the link can be deleted by the service provider or by the partner
     */
	public function removePartnershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to cancel a partnership.");
			return $this->_redirect('site');
		}

		$link_id = $this->request->getParam('link');
        $service_members_pets = new Petolio_Model_PoServiceMembersPets();
        $service_members_pets->find($link_id);
        if(!$service_members_pets->getId()) {
			$this->msg->messages[] = $this->translate->_("Partnership link was not found.");
			return $this->_helper->redirector('index', 'site');
        }

        // get service
        $service = $service_members_pets->getMemberService();

        // check if the logged in user is the service owner or the partner
        if($service->getUserId() != $this->auth->getIdentity()->id && $service_members_pets->getMemberPet()->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner or the partner has access to cancel a partnership.");
			return $this->_redirect('site');
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

		// get data
		$data = $form->getValues();

		// get service name
        $attributes = new Petolio_Model_PoAttributes();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // get data
        $attributes = new Petolio_Model_PoAttributes();
        $pet = $service_members_pets->getMemberPet();
        $pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($pet, true));
        $pet_name = $pet_attributes['name']->getAttributeEntity()->getValue();
        $pet_owner = $pet->getOwner();

	    // do the actual job
	    $service_members_pets->deleteRowByPrimaryKey();

        /*
         * removed by the service owner ? notify ex-member user : notify service owner
         */
        if($service->getUserId() == $this->auth->getIdentity()->id) {

	        $html = sprintf(
	    				$this->translate->_('%1$s cancelled the partnership with your pet %2$s for the service %3$s.'),
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $pet->getId()), 'default', true)}'>{$pet_name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
	    				);
			if(isset($data['message']) && strlen($data['message']) > 0) {
				$html .= "<br/><br/>" . sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => $this->translate->_("Partnership cancelled"),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $pet_owner->getId(),
				'name' => $pet_owner->getName(),
				'email' => $pet_owner->getEmail()
			)), $pet_owner->isOtherEmailNotification());
        } else {

        	$user = $service->getOwner();
	        $html = sprintf(
	    				$this->translate->_('%1$s cancelled the partnership between the pet %2$s and your service %3$s.'),
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $pet->getId()), 'default', true)}'>{$pet_name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
	    				);
			if(isset($data['message']) && strlen($data['message']) > 0) {
				$html .= "<br/><br/>" . sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br($data['message']);
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => $this->translate->_("Partnership cancelled"),
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

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Partnership cancelled with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_pets->getServiceId()));
    }

    public function requestMembershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to request a membership.");
			return $this->_redirect('site');
		}

		// get service
    	$this->services->find($this->request->getParam('service'));
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// service is not type 1
		$attribute_set = $this->services->getAttributeSet();
		if($attribute_set->getType() != 1) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_redirect('site');
		}

		// check if already has a request
		$link = new Petolio_Model_PoServiceMembersUsers();
		$result = $link->fetchList("user_id = {$this->auth->getIdentity()->id} AND service_id = {$this->services->getId()}");
		if(is_array($result) && count($result) > 0 ):
			$this_link = reset($result);
			switch ($this_link->getStatus()) {
				case "0":
					$this->msg->messages[] = $this->translate->_("You already have a membership request that is waiting for approval.");
					break;
				case "1":
					$this->msg->messages[] = $this->translate->_("You already have a membership request that has been accepted.");
					break;
				case "2":
					$this->msg->messages[] = $this->translate->_("You already have a membership request that has been declined.");
					break;

				default:
					$this->msg->messages[] = $this->translate->_("You already have a membership request.");
					break;
			}
			return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $this->services->getId()));
		endif;

		$form = new Petolio_Form_RequestMembership();
		$service_attributes = reset($this->attr->loadAttributeValues($this->services, true));

    	// send to template
    	$this->view->service = $this->services;
    	$this->view->attributes = $service_attributes;
    	$this->view->form = $form;

    	// load service options
		$this->serviceOptions($this->view->service, $this->view->attributes, $attribute_set->getType());

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();
		$service_owner = $this->services->getOwner();

		// save data
		$member_users = new Petolio_Model_PoServiceMembersUsers();
		$member_users->setUserId($data["user_id"]);
		$member_users->setServiceId($this->services->getId());
		$member_users->save(true, true);

		$html = sprintf(
					$this->translate->_('%1$s has asked for membership to your service: %2$s'),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					$service_attributes['name']->getAttributeEntity()->getValue()
				) . '<br /><br />';
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= sprintf(
					$this->translate->_("%s has sent you the following message:"),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				) . "<br/>" . nl2br($data['message']) . '<br /><br />';
		}
		$html .= sprintf(
					$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'accept-membership', 'link' => $member_users->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'decline-membership', 'link' => $member_users->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
					"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				);

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Request for membership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect
		$this->msg->messages[] = $this->translate->_("The message was successfully sent.");
		return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $this->services->getId()));
    }

    public function acceptMembershipAction()
    {
        // not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a membership.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to accept a membership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_users = new Petolio_Model_PoServiceMembersUsers();
        $service_members_users->find($link_id);
        if(!$service_members_users->getId()) {
			$this->msg->messages[] = $this->translate->_("Membership link was not found.");
			return $this->_helper->redirector('index', 'site');
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

		// get data
		$data = $form->getValues();

        // get service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_users->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // check if the logged in user is the service owner
        if($service->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner has access to accept or decline a membership request.");
			return $this->_redirect('site');
        }

        $service_members_users->setStatus(1);
        $service_members_users->save(true, true);

        // get data
        $user = $service_members_users->getMemberUser();

        $html = sprintf(
    				$this->translate->_('%1$s accepted the membership request for the service %2$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				$service_name
				);
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: membership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Membership accepted with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_users->getServiceId()));
    }

    public function declineMembershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a membership.");
			return $this->_redirect('site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have service provider account to decline a membership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_users = new Petolio_Model_PoServiceMembersUsers();
        $service_members_users->find($link_id);
        if(!$service_members_users->getId()) {
			$this->msg->messages[] = $this->translate->_("Membership link was not found.");
			return $this->_helper->redirector('index', 'site');
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

		// get data
		$data = $form->getValues();

        // get service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_users->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // check if the logged in user is the service owner
        if($service->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner has access to accept or decline a membership request.");
			return $this->_redirect('site');
        }

        $service_members_users->setStatus(2);
        $service_members_users->save(true, true);

        // get data
        $user = $service_members_users->getMemberUser();

        $html = sprintf(
    				$this->translate->_('%1$s declined the membership request for the service %2$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				$service_name
				);
		if(isset($data['message']) && strlen($data['message']) > 0) {
			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: membership"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Membership declined with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_users->getServiceId()));
    }

    /**
     * deletes a membership link
     * the link can be deleted by the service provider or the member user
     */
    public function removeMembershipAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to cancel a membership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_users = new Petolio_Model_PoServiceMembersUsers();
        $service_members_users->find($link_id);
        if(!$service_members_users->getId()) {
			$this->msg->messages[] = $this->translate->_("Membership link was not found.");
			return $this->_helper->redirector('index', 'site');
        }

        // get service
        $service = $service_members_users->getMemberService();

        // check if the logged in user is the service owner or the member user
        if($service->getUserId() != $this->auth->getIdentity()->id && $service_members_users->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the service owner or the service member has access to cancel a membership.");
			return $this->_redirect('site');
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

		// get data
		$data = $form->getValues();

        // get service name
        $attributes = new Petolio_Model_PoAttributes();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

	    // get member user
	    $member_user = $service_members_users->getMemberUser();

        // do the actual job
        $service_members_users->deleteRowByPrimaryKey();

        /*
         * removed by the service owner ? notify ex-member user : notify service owner
         */
        if($service->getUserId() == $this->auth->getIdentity()->id) {

	        $html = sprintf(
	    				$this->translate->_('%1$s removed you from the membership list of the service %2$s.'),
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
					);
			if(isset($data['message']) && strlen($data['message']) > 0) {
				$html .= "<br/><br/>" . sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => $this->translate->_("Membership cancelled"),
				'message_html' => $html,
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $member_user->getId(),
				'name' => $member_user->getName(),
				'email' => $member_user->getEmail()
			)), $member_user->isOtherEmailNotification());
        } else {

        	$user = $service->getOwner();
        	$html = sprintf(
	    				$this->translate->_('%1$s is no longer member of your service %2$s.'),
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
	    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
					);
			if(isset($data['message']) && strlen($data['message']) > 0) {
				$html .= "<br/><br/>" . sprintf(
	    				$this->translate->_("%s has sent you the following message:"),
					    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				    ) . "<br/>" . nl2br($data['message']);
			}

			// send message
	    	Petolio_Service_Message::send(array(
				'subject' => $this->translate->_("Membership cancelled"),
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

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Membership cancelled with success.");
        return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service_members_users->getServiceId()));
    }

    /**
     * this method invites pets and his owners to create link with the service
     * - send invitation message and email to every user (for every pet)
     */
    public function inviteAction() {
    	$service_id = $this->request->getParam('service');
    	$pet_ids = $this->request->getParam('pets');

		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			die('gtfo noob');
		}

		// get service
    	$this->services->find($service_id);
		if(!$this->services->getId()) {
			die('gtfo noob');
		}

		// service is not type 0
		$attribute_set = $this->services->getAttributeSet();
		if($attribute_set->getType() != 0) {
			die('gtfo noob');
		}

		$service_attributes = reset($this->attr->loadAttributeValues($this->services, true));

		// get all pets
		$pet_obj = new Petolio_Model_PoPets();
		$ids = implode(',', $pet_ids);
		$pets = $pet_obj->formatPets($pet_obj->getPets('array', "a.id IN (".addcslashes($ids, "\000\n\r\\'\"\032").")"));

		$message_count = 0;
		foreach ( $pets as $pet) {
			// check if already has a request
			$link = new Petolio_Model_PoServiceMembersPets();
			$result = $link->fetchList("pet_id = {$pet['id']} AND service_id = {$this->services->getId()}");
			if(!(is_array($result) && count($result) > 0)) {
				// save data
				$member_pets = new Petolio_Model_PoServiceMembersPets();
				$member_pets->setPetId($pet['id']);
				$member_pets->setServiceId($this->services->getId());
				$member_pets->setStatus(3); // invited
				$member_pets->save();

				// get pet owner
				$pet_owner = new Petolio_Model_PoUsers();
				$pet_owner->find($pet['user_id']);

				// send message
				$html = sprintf(
							$this->translate->_('%1$s invited to create a partnership link with his service %2$s and your pet %3$s (%4$s)'),
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $this->services->getId()), 'default', true)}'>{$service_attributes['name']->getAttributeEntity()->getValue()}</a>",
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $member_pets->getPetId()), 'default', true)}'>{$pet['name']}</a>",
							$pet['breed']
						) . '<br /><br />' .
						sprintf(
							$this->translate->_('You can %1$s or %2$s the invitation from %3$s.'),
							"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'accept-invite', 'link' => $member_pets->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
							"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'decline-invite', 'link' => $member_pets->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
							"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
						);
		    	Petolio_Service_Message::send(array(
					'subject' => $this->translate->_("Invite for partnership"),
					'message_html' => $html,
					'from' => $this->auth->getIdentity()->id,
					'status' => 1,
					'template' => 'default'
				), array(array(
					'id' => $pet_owner->getId(),
					'name' => $pet_owner->getName(),
					'email' => $pet_owner->getEmail()
				)), $pet_owner->isOtherEmailNotification());

				$message_count++;
			}

		}

    	$response = array('success' => true, 'message_count' => $message_count);

    	// send json
		return Petolio_Service_Util::json($response);

    }

    /**
     * accept an invitation to create a partnerlink
     */
    public function acceptInviteAction() {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a partnership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_pets = new Petolio_Model_PoServiceMembersPets();
        $service_members_pets->find($link_id);
        if(!$service_members_pets->getId()) {
			$this->msg->messages[] = $this->translate->_("Partnership link was not found.");
			return $this->_redirect('site');
        }

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_pets->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // get member pet
        $member_pet = $service_members_pets->getMemberPet();

        // check if the logged in user is the pet owner
        if($member_pet->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the pet owner has access to accept or decline a partnership invitation.");
			return $this->_redirect('site');
        }

        $service_members_pets->setStatus(1);
        $service_members_pets->save();

        // get data
        $attributes = new Petolio_Model_PoAttributes();
        $pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($member_pet, true));
        $pet_name = $pet_attributes['name']->getAttributeEntity()->getValue();
        $service_owner = $service->getOwner();

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Partnership request accepted"),
			'message_html' =>
    			sprintf(
    				$this->translate->_('%1$s accepted the partnership for his pet %2$s and your service %3$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $service_members_pets->getPetId()), 'default', true)}'>{$pet_name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service_members_pets->getServiceId()), 'default', true)}'>{$service_name}</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Partnership accepted with success.");
        return $this->_helper->redirector('view', 'pets', 'frontend', array('pet' => $service_members_pets->getPetId()));
    }

    /**
     * decline an invitation to create a partnerlink
     */
    public function declineInviteAction() {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a partnership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_pets = new Petolio_Model_PoServiceMembersPets();
        $service_members_pets->find($link_id);
        if(!$service_members_pets->getId()) {
			$this->msg->messages[] = $this->translate->_("Partnership link was not found.");
			return $this->_redirect('site');
        }

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_pets->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // get member pet
        $member_pet = $service_members_pets->getMemberPet();

        // check if the logged in user is the pet owner
        if($member_pet->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the pet owner has access to accept or decline a partnership invitation.");
			return $this->_redirect('site');
        }

        // we decline here with a delete
        $service_members_pets->deleteRowByPrimaryKey();

        // get data
        $attributes = new Petolio_Model_PoAttributes();
        $pet_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($member_pet, true));
        $pet_name = $pet_attributes['name']->getAttributeEntity()->getValue();
        $service_owner = $service->getOwner();

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Partnership request declined"),
			'message_html' =>
    			sprintf(
    				$this->translate->_('%1$s declined the partnership for his pet %2$s and your service %3$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $member_pet->getId()), 'default', true)}'>{$pet_name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Partnership declined with success.");
        return $this->_helper->redirector('view', 'pets', 'frontend', array('pet' => $member_pet->getId()));
    }

    /**
     * this redirects to the compose message form but first try to populate with recipients
     */
    public function sendMessageAction() {
		// user must be logged in
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to send a message.");
			return $this->_redirect('site');
		}

		// get user parameter
		$user = $this->request->getParam('user');
		
		$users_array = array();
		if(is_string($user) && strcasecmp($user, 'all') == 0) {
			$service_id = $this->request->getParam('service');
			$service = new Petolio_Model_PoServices();
			$service->find($service_id);
			if(!$service->getId()) {
				$this->msg->messages[] = $this->translate->_("Service not found.");
				return $this->_helper->redirector('index', 'site');
			}

			$attribute_set = new Petolio_Model_PoAttributeSets();
			$attribute_set->find($service->getAttributeSetId());

			// get service members users
	        if($attribute_set->getType() == 1) {
	        	$accepted_members_users = $service->getMembersUsers(1);
				foreach ($accepted_members_users as $member_user) {
					array_push($users_array, $member_user->getUserId());
				}
			// get service members pets
	        } else {
				$accepted_members_pets = $service->getMembersPets(1);
				foreach ($accepted_members_pets as $member_pet) {
					array_push($users_array, $member_pet->getMemberPet()->getUserId());
				}
	        }

		} elseif ( is_array($user) ) {
			$users_array = $user;
		} else {
			$users_array = array($user);
		}
		
		$populate = array (
			'multi_users' => $users_array
		);

    	$namespace = new Zend_Session_Namespace();
		$namespace->populate = $populate;

		return $this->_helper->redirector('compose', 'messages');
    }

	/**
	 * Upload pictures action
	 */
	public function picturesAction() {
		$service_id = $this->request->getParam('service');
		$this->services->find($service_id);

		// no service ? awww
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service not found.");
			return $this->_helper->redirector('index', 'services');
		}

		// service removed ?
		if($this->services->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("This service has been removed by the owner.");
			return $this->_helper->redirector('index', 'site');
		}

		// get service attributes
		$attribute_set = new Petolio_Model_PoAttributeSets();
		$attribute_set->find($this->services->getAttributeSetId());
		$this->view->attributes = reset($this->attr->loadAttributeValues($this->services, true));

		// load service options
		$this->serviceOptions($this->services, $this->view->attributes, $attribute_set->getType());

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}services{$ds}{$this->services->getId()}{$ds}";

		// create a folder for our service if it does not exist
		$gallery = null;
		if($this->services->getFolderId()) {
			$search_vars = array('id' => $this->services->getFolderId());
			$gallery = $this->folders->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if(!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'service', 'petId' => 0, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->folders->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our microsite too
			$this->services->setFolderId($gallery->getId());
			$this->services->save();
		}

		// load form
		$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
		$this->view->form = $form;

		// get & show all pictures
		$files = new Petolio_Model_PoFiles();
		$result = $files->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
		$this->view->gallery = $result;

    	// make picture primary
    	$primary = $this->request->getParam('primary');
    	if(isset($primary)) {
			// get level
			$result = $files->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_redirect('services/pictures/service/'. $this->services->getId());
			} else
				$pic = reset($result);

			// get all other pictures
			$result = reset($files->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_redirect('services/pictures/service/'. $this->services->getId());
		}

		// get picture remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $files->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_redirect('services/pictures/service/'. $this->services->getId());
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
			$fake = array($this->translate->_("service")); unset($fake);
			Petolio_Service_Autopost::factory('image', $pic->getFolderId(),
				'service',
				$this->services->getId(),
				$this->view->url(array('controller' => 'services', 'action' => 'view', 'service' => $this->services->getId()), 'default', true),
				$this->view->service_attr['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your Picture has been deleted successfully.");
			return $this->_redirect('services/pictures/service/'. $this->services->getId());
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the services directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the services folder on disk.")));
				return $this->_redirect('services/pictures/service/'. $this->services->getId());
			}
		}

		// create the services gallery directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the service folder on disk.")));
				return $this->_redirect('services/pictures/service/'. $this->services->getId());
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
		$size = $this->cfg['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if(!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your picture / pictures exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->cfg['phpSettings']['upload_max_filesize'])));
				return $this->_redirect('services/pictures/service/'. $this->services->getId());
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
			list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["pic"]);
			if($props[0] > $w || $props[1] > $h) {
				Petolio_Service_Image::output($pic, $pic, array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MAX
				));
			}

			// make big thumbnail
			list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["big"]);
			Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN
			));

			// make small thumbnail
			list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["small"]);
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
			$fake = array($this->translate->_("service")); unset($fake);
			Petolio_Service_Autopost::factory('image', $file,
				'service',
				$this->services->getId(),
				$this->view->url(array('controller' => 'services', 'action' => 'view', 'service' => $this->services->getId()), 'default', true),
				$this->view->service_attr['name']->getAttributeEntity()->getValue()
			);
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your service pictures have been uploaded successfully.");
		return $this->_redirect('services/pictures/service/'. $this->services->getId());
	}

	/**
	 * Video upload interface
	 */
	public function videosAction() {
		$service_id = $this->request->getParam('service');
		$this->services->find($service_id);

		// no service ? awww
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service not found.");
			return $this->_helper->redirector('index', 'services');
		}

		// service removed ?
		if($this->services->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("This service has been removed by the owner.");
			return $this->_helper->redirector('index', 'site');
		}

		// get service attributes
		$attribute_set = new Petolio_Model_PoAttributeSets();
		$attribute_set->find($this->services->getAttributeSetId());
		$this->view->attributes = reset($this->attr->loadAttributeValues($this->services, true));

		// load service options
		$this->serviceOptions($this->services, $this->view->attributes, $attribute_set->getType());

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}services{$ds}{$this->services->getId()}{$ds}";

		// create a folder for our microsite if it does not exist
		$gallery = null;
		if($this->services->getFolderId()) {
			$search_vars = array('id' => $this->services->getFolderId());
			$gallery = $this->folders->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if(!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'service', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->folders->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our microsite too
			$this->services->setFolderId($gallery->getId());
			$this->services->save();
		}

		// create service directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the service folder on disk.")));
				return $this->_redirect('services/videos/service/'. $this->services->getId());
			}
		}

		// youtube wrapper
		$youtube = Petolio_Service_YouTube::factory('Master');
		$youtube->CFG = array(
			'username' => $this->cfg["youtube"]["username"],
			'password' => $this->cfg["youtube"]["password"],
			'app' => $this->cfg["youtube"]["app"],
			'key' => $this->cfg["youtube"]["key"]
		);

		// create a new video
		$video = new Zend_Gdata_YouTube_VideoEntry();
		$video->setVideoTitle(md5(mt_rand()));
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($this->view->attributes['description']->getAttributeEntity()->getValue(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr($this->view->attributes['name']->getAttributeEntity()->getValue(), 0, 30) . ', petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'services', 'action'=>'videos', 'service'=>$this->services->getId()), 'default', true);

		// get all videos
		$result = $this->imap->fetchList("type = 'video' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;
		$this->view->service = $this->services;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('services/videos/service/'. $this->services->getId());
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->imap->fetchList("file = '{$filename}' AND folder_id = '{$gallery->getId()}'");
			if(is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('services/videos/service/'. $this->services->getId());
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('services/videos/service/'. $this->services->getId());
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
			$fake = array($this->translate->_("service")); unset($fake);
			Petolio_Service_Autopost::factory('video', $this->imgs,
				'service',
				$this->services->getId(),
				$this->view->url(array('controller' => 'services', 'action' => 'view', 'service' => $this->services->getId()), 'default', true),
				$this->view->attributes['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your service video link has been successfully added.");
			return $this->_redirect('services/videos/service/'. $this->services->getId());
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
			$fake = array($this->translate->_("service")); unset($fake);
			Petolio_Service_Autopost::factory('video', $vid->getFolderId(),
				'service',
				$this->services->getId(),
				$this->view->url(array('controller' => 'services', 'action' => 'view', 'service' => $this->services->getId()), 'default', true),
				$this->view->attributes['name']->getAttributeEntity()->getValue()
			);

			// msg
			$this->msg->messages[] = $this->translate->_("Your service video has been deleted successfully.");
			return $this->_redirect('services/videos/service/'. $this->services->getId());
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
				$fake = array($this->translate->_("service")); unset($fake);
				Petolio_Service_Autopost::factory('video', $this->imgs,
					'service',
					$this->services->getId(),
					$this->view->url(array('controller' => 'services', 'action' => 'view', 'service' => $this->services->getId()), 'default', true),
					$this->view->attributes['name']->getAttributeEntity()->getValue()
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
		$this->msg->messages[] = $this->translate->_("Your service videos has been updated successfully.");
		return $this->_redirect('services/videos/service/'. $this->services->getId());
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

	public function getServiceTypeDataAction() {
		$service_type_id = $this->request->getParam('service_type_id');

		$attr_sets = new Petolio_Model_PoAttributeSets();
		$attr_sets->find($service_type_id);

		$type = $this->translate->_('Partnership service: Service members are pets');
		if($attr_sets->getType() == 1) {
			$type = $this->translate->_('Membership service: Service members are users');
		}
		$description = '';
		if($attr_sets->getDescription() && strcasecmp($attr_sets->getDescription(), 'null') != 0) {
			$description = Petolio_Service_Util::Tr($attr_sets->getDescription());
		}

		$result = array (
			'success' => true,
			'type' => $type,
			'description' => $description
		);
		return Petolio_Service_Util::json($result);
	}

    /**
     * this method invites users to create membership link with the service
     * - send invitation message and email to every user
     */
    public function inviteMembersAction() {
    	$service_id = $this->request->getParam('service');
    	$user_ids = $this->request->getParam('users');
    	$message = $this->request->getParam('message') ? $this->request->getParam('message') : '';

		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			die('gtfo noob');
		}

		// get service
    	$this->services->find($service_id);
		if(!$this->services->getId()) {
			die('gtfo noob');
		}

		// service is not type 1
		$attribute_set = $this->services->getAttributeSet();
		if($attribute_set->getType() != 1) {
			die('gtfo noob');
		}

		$service_attributes = reset($this->attr->loadAttributeValues($this->services, true));

		// get users
		$user_obj = new Petolio_Model_PoUsers();
		$ids = implode(',', $user_ids);
		$users = $user_obj->fetchList("id IN (".addcslashes($ids, "\000\n\r\\'\"\032").")");

		$message_count = 0;
		foreach ( $users as $user) {
			// check if already has a request
			$link = new Petolio_Model_PoServiceMembersUsers();
			$result = $link->fetchList("user_id = {$user->getId()} AND service_id = {$this->services->getId()}");
			if(!(is_array($result) && count($result) > 0)) {
				// save data
				$member_users = new Petolio_Model_PoServiceMembersUsers();
				$member_users->setUserId($user->getId());
				$member_users->setServiceId($this->services->getId());
				$member_users->setStatus(3); // invited
				$member_users->save();

				// send message
				$html = sprintf(
							$this->translate->_('%1$s invited to create a membership link with his service %2$s and you'),
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $this->services->getId()), 'default', true)}'>{$service_attributes['name']->getAttributeEntity()->getValue()}</a>"
						) . '<br /><br />' .
						sprintf(
							$this->translate->_('You can %1$s or %2$s the invitation from %3$s.'),
							"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'accept-invite-members', 'link' => $member_users->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
							"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'decline-invite-members', 'link' => $member_users->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
							"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
						);
				if(isset($message) && strlen($message) > 0) {
					$html .= '<br /><br />' . nl2br($message);
				}
		    	Petolio_Service_Message::send(array(
					'subject' => $this->translate->_("Invite for membership"),
					'message_html' => $html,
					'from' => $this->auth->getIdentity()->id,
					'status' => 1,
					'template' => 'default'
				), array(array(
					'id' => $user->getId(),
					'name' => $user->getName(),
					'email' => $user->getEmail()
				)), $user->isOtherEmailNotification());

				$message_count++;
			}

		}

    	$response = array('success' => true, 'message_count' => $message_count);

    	// send json
		return Petolio_Service_Util::json($response);

    }

    /**
     * accept an invitation to create a memberlink
     */
    public function acceptInviteMembersAction() {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a membership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_users = new Petolio_Model_PoServiceMembersUsers();
        $service_members_users->find($link_id);
        if(!$service_members_users->getId()) {
			$this->msg->messages[] = $this->translate->_("Membership link was not found.");
			return $this->_redirect('site');
        }

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_users->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // get member user
        $member_user = $service_members_users->getMemberUser();

        // check if the logged in user is the pet owner
        if($member_user->getId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the invited person has access to accept or decline a membership invitation.");
			return $this->_redirect('site');
        }

        $service_members_users->setStatus(1);
        $service_members_users->save();

        // get data
        $service_owner = $service->getOwner();

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Membership request accepted"),
			'message_html' =>
    			sprintf(
    				$this->translate->_('%1$s accepted the membership invitation for your service %2$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service_members_users->getServiceId()), 'default', true)}'>{$service_name}</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Membership accepted with success.");
        return $this->_helper->redirector('index', 'index', 'frontend');
    }

    /**
     * decline an invitation to create a memberlink
     */
    public function declineInviteMembersAction() {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a membership.");
			return $this->_redirect('site');
		}

    	$link_id = $this->request->getParam('link');
        $service_members_users = new Petolio_Model_PoServiceMembersUsers();
        $service_members_users->find($link_id);
        if(!$service_members_users->getId()) {
			$this->msg->messages[] = $this->translate->_("Membership link was not found.");
			return $this->_redirect('site');
        }

		// get member service
        $attributes = new Petolio_Model_PoAttributes();
        $service = $service_members_users->getMemberService();
        $service_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($service, true));
        $service_name = $service_attributes['name']->getAttributeEntity()->getValue();

        // get member user
        $member_user = $service_members_users->getMemberUser();

        // check if the logged in user has been invited
        if($member_user->getId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("Only the invited person has access to accept or decline a membership invitation.");
			return $this->_redirect('site');
        }

        // we decline here with a delete
        $service_members_users->deleteRowByPrimaryKey();

        // get data
        $service_owner = $service->getOwner();

		// send message
    	Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Membership request declined"),
			'message_html' =>
    			sprintf(
    				$this->translate->_('%1$s declined the membership invitation for your service %2$s.'),
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
    				"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'services', 'action'=>'view', 'service' => $service->getId()), 'default', true)}'>{$service_name}</a>"
				),
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $service_owner->getId(),
			'name' => $service_owner->getName(),
			'email' => $service_owner->getEmail()
		)), $service_owner->isOtherEmailNotification());

		// redirect with message
        $this->msg->messages[] = $this->translate->_("Membership declined with success.");
        return $this->_redirect('site');
    }

    /**
     * Archive action
     */
    public function archiveAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to archive a service.");
			return $this->_redirect('site');
		}

		// get service
		$service = new Petolio_Model_PoServices();
		$result = $service->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('service'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $service = reset($result);

		// mark as deleted
		$service->setDeleted(1);
		$service->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your service has been archived successfully.");
		return $this->_helper->redirector('index', 'services');
    }

    /**
     * Restore action
     */
    public function restoreAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to restore a service.");
			return $this->_redirect('site');
		}

		// get service
		$service = new Petolio_Model_PoServices();
		$result = $service->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('service'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '1'");
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $service = reset($result);

		// mark as deleted
		$service->setDeleted(0);
		$service->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your service has been restored successfully.");
		return $this->_helper->redirector('index', 'services');
    }

    /**
     * Pets archived
     */
    public function archivesAction()
    {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the archived services.");
			return $this->_redirect('site');
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
		elseif($this->view->order == 'type') $sort = "type {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "a.date_created {$this->view->dir}";
		}

		// get services
		$paginator = $this->services->getServices('paginator', "a.user_id = {$this->auth->getIdentity()->id} AND a.deleted = 1", $sort);
		$paginator->setItemCountPerPage($this->cfg["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output your services
		$this->view->archived = $this->services->formatServices($paginator);
    }

    /**
	 * list the services where the pet owner is member
     */
    public function myServicesAction() {
		// not logged in ? BYE
		if(!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		// load types, colors, countries and is service
    	$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// additional
		$this->view->isService = 'false';

		// do sorting 1
		$this->view->service_order = $this->request->getParam('service_order');
		$this->view->service_dir = $this->request->getParam('service_dir') == 'desc' ? 'desc' : 'asc';
		$this->view->service_rdir = $this->view->service_dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		switch ($this->view->service_order) {
			case "service_name": $sort = "d.value {$this->view->service_dir}"; break;
			case "service_owner": $sort = "u.name {$this->view->service_dir}"; break;
			case "service_type": $sort = "as.name {$this->view->service_dir}"; break;
			case "service_status": $sort = "a.status {$this->view->service_dir}"; break;
			case "service_address":
				if($this->translate->getLocale() == 'en') {
					$sort = "d1.value {$this->view->service_dir}, d3.value {$this->view->service_dir}, e4.value {$this->view->service_dir}";
				} else {
					$sort = "d2.value {$this->view->service_dir}, d3.value {$this->view->service_dir}, e4.value {$this->view->service_dir}";
				}
			default:
				$this->view->service_order = 'service_name';
				$sort = "d.value {$this->view->service_dir}";
			break;
		}

		// get users
		$members_users = new Petolio_Model_PoServiceMembersUsers();
    	$this->view->user_members_services = $members_users->getUserServicesWithReferences($this->auth->getIdentity()->id, null, $sort);
    }

	/**
	 * Service - Contact
	 */
    public function contactAction() {
		// get service
		$this->services->find($this->request->getParam('service'));
		if(!$this->services->getId()) {
			$this->msg->messages[] = $this->translate->_("Service does not exist.");
			return $this->_helper->redirector('index', 'site');
		}
		if($this->services->getDeleted() == 1) {
			$this->msg->messages[] = $this->translate->_("This service has been removed by the owner.");
			return $this->_helper->redirector('index', 'site');
		}

		// get service attributes
		$attribute_set = new Petolio_Model_PoAttributeSets();
		$attribute_set->find($this->services->getAttributeSetId());

    	// send to template
    	$this->view->attributes = reset($this->attr->loadAttributeValues($this->services, true));

    	// load service options
		$this->serviceOptions($this->services, $this->view->attributes, $attribute_set->getType());
		$service = $this->services;

		// authenticated? redirect to send message
    	if($this->auth->hasIdentity())
			return $this->_helper->redirector('send', 'messages', 'frontend', array('id' => $service->getUserId(), 'service' => base64_encode(ucfirst($this->view->attributes['name']->getAttributeEntity()->getValue()))));

		// get user
		$id = $service->getUserId();

		// load user
		$user_obj = new Petolio_Model_PoUsers();
		$user_obj->find($id);
		if(!$user_obj->getId())
			return $this->_helper->redirector('index', 'site');

		// send user to template
		$this->view->user = $user_obj;

		// init form
		$form = new Petolio_Form_Reply($this->translate->_("Send Message >"), false, false, true);
		$form->populate(array(
			'subject' => $this->translate->_('Question for the following service:') . ' ' . ucfirst($this->view->attributes['name']->getAttributeEntity()->getValue()),
			'message' => $this->translate->_('Can you please answer following question:') . '

'
		));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get form data
		$data = $form->getValues();

		// send private message
	    Petolio_Service_Message::send(array(
			'subject' => $data['subject'],
			'message_html' => sprintf($this->translate->_("%s sent you a message:"), $data['from']) . '<br /><br />' . $data['message'],
			'from' => 0,
			'status' => 2,
			'product' => $service->getId(),
			'template' => 'default'
		), array(array(
			'id' => $user_obj->getId(),
			'name' => $user_obj->getName(),
			'email' => $user_obj->getEmail()
		)), $user_obj->isOtherEmailNotification());

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The private message was successfully sent.");
		return $this->_helper->redirector('view', 'services', 'frontend', array('service' => $service->getId()));
    }
    
    /**
     * check if the logged in user is a member/partner
     * only if the user is a partner or member we show the partners/members list
     */
    private function isPartnerOrMember($admin, $attribute_set) {
		if ( $this->auth->hasIdentity() ) {
    		if ( $admin ) {
    			return true;
    		} else {
    			if($attribute_set->getType() == 1) {
    				foreach ($this->view->accepted_members_users as $user) {
    					if ( $user->getUserId() == $this->auth->getIdentity()->id ) {
							return true;
    					}
    				}
    			} else {
    				foreach ($this->view->accepted_members_pets as $user) {
    					if ( $user->getMemberPet()->getUserId() == $this->auth->getIdentity()->id ) {
    						return true;
    					}
    				}
    			}
    		}
    	}
		return false;
    }
    
    public function mapAction() {
    	// not logged in ? BYE
    	if(!$this->auth->hasIdentity()) {
    		Petolio_Service_Util::saveRequest();
    		$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
    		return $this->_redirect('site');
    	}
    	
    	// not service provider ? BYE
    	if($this->auth->getIdentity()->type != 2) {
    		$this->msg->messages[] = $this->translate->_("You must have service provider account to edit a service.");
    		return $this->_redirect('site');
    	}
    	
    	// get service
    	$result = $this->smap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('service'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted != 1");
    	if(!( is_array($result) && count($result) > 0 )) {
    		$this->msg->messages[] = $this->translate->_("Service does not exist.");
    		return $this->_redirect('site');
    	} else {
    		$service = reset($result);
    	}
    	$this->view->service = $service;
    	
    	// load service attributes
    	$attributes = reset($this->attr->loadAttributeValues($service, true));
		
		foreach($attributes as $attr) {
			if(!$attr->getGroupId() && !is_array($attr->getAttributeEntity())) {
				if (Petolio_Service_Util::endsWith($attr->getCode(), "_street")) {
					$this->view->street = $attr->getAttributeEntity()->getValue();
				}
				if (Petolio_Service_Util::endsWith($attr->getCode(), "_zipcode")) {
					$this->view->zipcode = $attr->getAttributeEntity()->getValue();
				}
				if (Petolio_Service_Util::endsWith($attr->getCode(), "_address")) {
					$this->view->address = $attr->getAttributeEntity()->getValue();
				}
				if (Petolio_Service_Util::endsWith($attr->getCode(), "_location")) {
					$this->view->location = $attr->getAttributeEntity()->getValue();
				}
				if (Petolio_Service_Util::endsWith($attr->getCode(), "_country")) {
					$this->view->country = $attr->getAttributeEntity()->getValue();
				}
			}
		}
		
		// send europe as default
		$this->view->coords = $this->europe;
    }
    
    /**
     * Get and set the service types
     */
    private function buildTypes() {
    	// filter by type ?
    	$groups = array();
    	$service_types = array ();
    	$current_group_name = '';
    	foreach($this->sets->getAttributeSets('po_services') as $line) {
    		if(isset($line['group_name']) && strlen($line['group_name']) > 0) {
    			if(strcasecmp($line['group_name'], $current_group_name) != 0) {
    				$groups[$line['group_name']] = array (
    						'id' => $line['group_name'],
    						'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['group_name'])),
    						'indent' => 0
    				);
    			}
    		}
    
    		$current_group_name = $line['group_name'];
    		$service_types[] = array (
    				'id' => $line['id'],
    				'group_name' => $line['group_name'],
    				'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name'])),
    				'indent' => 1
    		);
    	}
    
    	// we want to sort the values if they are translated too so that's why we sort it here
    	$name_arr = array();
    	foreach($groups as $key => $row)
    		$name_arr[$key] = $row['name'];
    	array_multisort($name_arr, SORT_ASC, SORT_STRING, $groups);
    
    	$name_arr = array();
    	foreach($service_types as $key => $row)
    		$name_arr[$key] = $row['name'];
    	array_multisort($name_arr, SORT_ASC, SORT_STRING, $service_types);
    
    	$types = array();
    	foreach($service_types as $value)
    	if(!isset($value['group_name']) || strlen($value['group_name']) <= 0)
    		$types[$value['id']] = $value;
    
    	foreach($groups as $key => $row) {
    		$types[$row['id']] = $row;
    		foreach($service_types as $value)
    		if(strcasecmp($value['group_name'], $row['id']) == 0 )
    			$types[$value['id']] = $value;
    	}
    
    	// output the types
    	$this->view->types = $types;
    }

    public function viewLinksAction() {
    	// get service
    	$this->services->find($this->request->getParam('service'));
    	if(!$this->services->getId()) {
    		$this->msg->messages[] = $this->translate->_("Service does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	}
    	if($this->services->getDeleted() == 1) {
    		$this->msg->messages[] = $this->translate->_("This service has been removed by the owner.");
    		return $this->_helper->redirector('index', 'site');
    	}
    	
    	// see if the product owner is active and not banned
    	if(!($this->services->getOwner()->getActive() == 1 && $this->services->getOwner()->getIsBanned() != 1)) {
    		$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
    		return $this->_helper->redirector('index', 'site');
    	}
    	
    	$this->buildTypes();
    	
    	// if flagged, load reasons
    	$this->view->flagged = array();
    	if($this->services->getFlagged() == 1) {
    		$reasons = new Petolio_Model_PoFlagReasons();
    		$results = $this->flag->getMapper()->fetchList("scope = 'po_services' AND entry_id = '{$this->services->getId()}'");
    		foreach($results as $line) {
    			$reasons->find($line->getReasonId());
    			$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
    		}
    	}
    	
    	// get the flag form
    	$this->view->flag = new Petolio_Form_Flag();
    	
    	// if service is yours tell me :)
    	$admin = false;
    	if(isset($this->auth->getIdentity()->id))
    		$admin = $this->services->getUserId() == $this->auth->getIdentity()->id ? true : false;
    	
    	// get service attributes
    	$attribute_set = new Petolio_Model_PoAttributeSets();
    	$attribute_set->find($this->services->getAttributeSetId());
    	
    	// get service members users
    	if($attribute_set->getType() == 1) {
    		$this->view->accepted_members_users = $this->services->getMembersUsers(1);
    		if($admin) {
    			$this->view->requested_members_users = $this->services->getMembersUsers(0);
    			$this->view->declined_members_users = $this->services->getMembersUsers(2);
    		}
    	
    		// get service members pets
    	} else {
    		$attributes = new Petolio_Model_PoAttributes();
    		$accepted_members_pets = $this->services->getMembersPets(1);
    		$this->view->accepted_members_pets = $accepted_members_pets;
    		if($admin) {
    			$this->view->requested_members_pets = $this->services->getMembersPets(0);
    			$this->view->declined_members_pets = $this->services->getMembersPets(2);
    		}
    	}
		
    	// send to template
    	$this->view->attributes = reset($this->attr->loadAttributeValues($this->services, true));
    	$this->view->auth = $this->auth;
    	$this->view->admin = $admin;

    	// load service options
		$this->serviceOptions($this->services, $this->view->attributes, $attribute_set->getType());
    }
}