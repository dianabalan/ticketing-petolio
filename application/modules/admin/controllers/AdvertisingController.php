<?php

class AdvertisingController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $req = null;

	private $db = null;
	private $path = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->customer = new Petolio_Model_PoAdCustomers();
		$this->db->campaign = new Petolio_Model_PoAdCampaigns();
		$this->db->adpet = new Petolio_Model_PoAdPets();
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->banner = new Petolio_Model_PoAdBanners();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();

		// set banner path
		$ds = DIRECTORY_SEPARATOR;
		$this->path = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}banners{$ds}";
    }

    public function indexAction() {
        // action body
    }

    /**
     * Add customer action
     */
    public function addCustomerAction() {
    	// get type
    	$type = (int)$this->req->getParam("type", 1);

    	// send form
    	$form = new Petolio_Form_AdCustomer($type);
		$this->view->form = $form;
		$this->view->type = $type;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// check if start date is before end date
		if($form->getValue('start_date') && $form->getValue('end_date')) {
			$start_date = $form->getValue('start_date');
			$end_date = $form->getValue('end_date');

			// add error
			if(strtotime("{$start_date['day']}-{$start_date['month']}-{$start_date['year']}") > strtotime("{$end_date['day']}-{$end_date['month']}-{$end_date['year']}")) {
				$value = $form->getElement('end_date')->getValue();
				$form->getElement('end_date')->setValue(''); // if the value is array then zend puts the error message count($value) times
				$form->getElement('end_date')->addError($this->translate->_("End date must be after start date."));
				$form->getElement('end_date')->setValue($value);
				return false;
			}
		}

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// save customer
		$this->db->customer->setOptions($data);
		$this->db->customer->save(true, true);

		// add message
		$this->msg->messages[] = $this->translate->_("The advertising customer has been added successfully.");

		// redirect based on type
		return $type == 1 ? $this->_redirect("admin/advertising/customer-pets/id/{$this->db->customer->getId()}") :
			$this->_redirect("admin/advertising/list-customers/type/{$type}");
    }

    /**
     * Edit customer action
     */
    public function editCustomerAction() {
    	// get vars
    	$id = (int)$this->req->getParam("id", 0);
    	$type = (int)$this->req->getParam("type", 1);

    	// get customer
    	$customer = $this->db->customer->find($id);
    	if(!$customer->getId()) {
			$this->msg->messages[] = $this->translate->_("Customer does not exist.");
    		return $this->_redirect("admin/advertising/list-customers/type/{$type}");
    	}

    	// send form
    	$form = new Petolio_Form_AdCustomer($customer->getType());
    	$form->populate($customer->toArray());
		$this->view->form = $form;
		$this->view->type = $type;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// check if start date is before end date
		if($form->getValue('start_date') && $form->getValue('end_date')) {
			$start_date = $form->getValue('start_date');
			$end_date = $form->getValue('end_date');

			// show error
			if(strtotime("{$start_date['day']}-{$start_date['month']}-{$start_date['year']}") > strtotime("{$end_date['day']}-{$end_date['month']}-{$end_date['year']}")) {
				$value = $form->getElement('end_date')->getValue();
				$form->getElement('end_date')->setValue(''); // if the value is array then zend puts the error message count($value) times
				$form->getElement('end_date')->addError($this->translate->_("End date must be after start date."));
				$form->getElement('end_date')->setValue($value);
				return false;
			}
		}

		// get data
		$data = $form->getValues();

    	// format data
		foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// set options
		$customer->setOptions($data);
		$customer->setDateModified(date('Y-m-d H:i:s'));
		$customer->save(false, true);

		// add message
		$this->msg->messages[] = $this->translate->_("The advertising customer has been updated successfully.");

		// redirect based on type
		return $type == 1 ? $this->_redirect("admin/advertising/customer-pets/id/{$customer->getId()}") :
			$this->_redirect("admin/advertising/list-customers/type/{$customer->getType()}");
    }

    /**
     * List advertising customers
     */
    public function listCustomersAction() {
    	// get variables
    	$type = (int)$this->req->getParam("type", 1);
    	$name = $this->req->getParam("name", '');
    	$email = $this->req->getParam("email", '');

    	// output filters
    	$this->view->type = $type;
    	$this->view->name = $name;
    	$this->view->email = $email;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'c.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// start filter
    	$where = array("c.type = {$type}");

    	// name
    	if(strlen($name) > 0)
    		$where[] = "c.name LIKE '%".strtolower($name)."%'";

    	// email
    	if(strlen($email) > 0 )
    		$where[] = "c.email LIKE '%".strtolower($email)."%'";

		// get customers
		$paginator = $this->db->customer->getCustomers('paginator', implode(" AND ", $where), "{$this->view->order} {$this->view->dir}");
		$paginator->setItemCountPerPage(25);
		$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output customers
		$this->view->customers = $paginator;
    }

    /**
     * Delete a customer and all of the customer's campaigns and banners
     */
    public function deleteCustomerAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$type = (int)$this->req->getParam("type", 1);
    	$customer = $this->db->customer->find($id);
    	if(!$customer->getId()) {
    		$this->msg->messages[] = $this->translate->_("Customer does not exist.");
    		return $this->_redirect("admin/advertising/list-customers/type/{$type}");
    	}

		// delete customer
    	$customer->deleteWithReferencesByPrimaryKey();

    	// msg and redirect
		$this->msg->messages[] = $this->translate->_("Customer deleted successfully.");
		return $this->_redirect("admin/advertising/list-customers/type/{$type}");
    }

    /**
     * Select pets for the pet sponsoring customer
     */
    public function customerPetsAction() {
    	// get params
    	$id = (int)$this->req->getParam("id", 0);
    	$keyword = $this->req->getParam("keyword", '');
    	$owner = $this->req->getParam("owner", '');
    	$species = $this->req->getParam("species", '');

    	// get customer
		$customer = $this->db->customer->find($id);
		if(!$customer->getId()) {
			$this->msg->messages[] = $this->translate->_("Customer does not exist.");
			return $this->_redirect('admin/advertising/list-customers/type/1');
		}

		// output filters
		$this->view->customer = $customer;
		$this->view->keyword = $keyword;
		$this->view->owner = $owner;
		$this->view->species = $species;

		// filter by species ?
		$sort = array();
		$this->view->types = array();
		foreach($this->db->sets->getAttributeSets('po_pets') as $k => $c) {
			$_t = Petolio_Service_Util::Tr($c['name']);
			$sort[$k] = $_t;
			$this->view->types[] = array('value'=> $c['id'], 'name' => $_t);
		} array_multisort($sort, SORT_ASC, $this->view->types);

		// set selected data
		$this->view->selected_data = array();
		foreach($this->db->adpet->fetchList("customer_id = {$customer->getId()}") as $entry)
			$this->view->selected_data[] = $entry->getPetId();

		// start filters
		$where = array("a.deleted = 0");

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

		// species
		if(strlen($species) > 0)
			$where[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);

		// get pets
		$paginator = $this->db->pet->getPets('paginator', implode(" AND ", $where), "d1.value ASC", false, strlen($keyword) > 0, true);
		$paginator->setItemCountPerPage(25);
		$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output pets
		$this->view->pets = $this->db->pet->formatPets($paginator);
	}

	/**
	 * Add or removes an individual selection to/from a distribution
	 */
	public function petsAddRemoveAction() {
		// get params
		$add = $this->req->getParam('add', '');
		$customer = (int)$this->req->getParam('customer', 0);
		$pet = (int)$this->req->getParam('pet', 0);

		// on select
		if($add == 'true')
			$this->db->adpet->setCustomerId($customer)
				->setPetId($pet)
				->save(true, true);

		// on unselect
		else $this->db->adpet->delete("customer_id = {$customer} AND pet_id = {$pet}");

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * Add campaign action
	 */
    public function addCampaignAction() {
    	// send form
    	$form = new Petolio_Form_AdCampaign();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
    	foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// save campaign
		$this->db->campaign->setOptions($data)->save(true, true);

		// redirect to services index action
		$this->msg->messages[] = $this->translate->_("The advertising campaign has been added successfully.");
		return $this->_redirect('admin/advertising/list-campaigns');
    }

	/**
	 * Edit campaign action
	 */
    public function editCampaignAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$campaign = $this->db->campaign->find($id);
    	if(!$campaign->getId()) {
			$this->msg->messages[] = $this->translate->_("Campaign does not exist.");
    		return $this->_redirect('admin/advertising/list-campaigns');
    	}

    	// send form
    	$form = new Petolio_Form_AdCampaign();
    	$form->populate($campaign->toArray());
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
    	foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// set options
		$campaign->setOptions($data);
		$campaign->setDateModified(date('Y-m-d H:i:s'));
		$campaign->save(false, true);

		// redirect to services index action
		$this->msg->messages[] = $this->translate->_("The advertising campaign has been updated successfully.");
		return $this->_redirect('admin/advertising/list-campaigns');
    }

    /**
     * List advertising campaigns
     */
    public function listCampaignsAction() {
    	// get params
    	$customer = $this->req->getParam("customer", '');
    	$name = $this->req->getParam("name", '');
    	$target_views_min = $this->req->getParam("target_views_min", '');
    	$target_views_max = $this->req->getParam("target_views_max", '');
    	$active = $this->req->getParam("active", '');

    	// output filters
    	$this->view->customer = $customer;
    	$this->view->name = $name;
    	$this->view->target_views_min = $target_views_min;
    	$this->view->target_views_max = $target_views_max;
    	$this->view->active = $active;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'c.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// output custoners
    	$this->view->customers = $this->db->customer->fetchList("deleted != 1 AND type = 2", "name");

    	// handle filter
    	$where = array("c.deleted != 1");

    	// name
    	if(strlen($name) > 0)
    		$where[] = "c.name LIKE '%".strtolower($name)."%'";

    	// customer
    	if(strlen($customer) > 0)
    		$where[] = "c.customer_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($customer, Zend_Db::BIGINT_TYPE);

    	// target views min
    	if(strlen($target_views_min) > 0)
    		$where[] = "c.target_views >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($target_views_min, Zend_Db::INT_TYPE);

    	// target views max
    	if(strlen($target_views_max) > 0)
    		$where[] = "c.target_views <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($target_views_max, Zend_Db::INT_TYPE);

    	// active
    	if(strlen($active) > 0)
    		$where[] = "c.active = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($active, Zend_Db::INT_TYPE);

		// get campaigns
		$paginator = $this->db->campaign->getCampaigns('paginator', implode(" AND ", $where), "{$this->view->order} {$this->view->dir}");
		$paginator->setItemCountPerPage(25);
		$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output campaigns
		$this->view->campaigns = $paginator;
    }

    /**
     * Delete a campaign and all of the campaign's banners
     */
    public function deleteCampaignAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$campaign = $this->db->campaign->find($id);
    	if(!$campaign->getId()) {
    		$this->msg->messages[] = $this->translate->_("Campaign does not exist.");
    		return $this->_redirect("admin/advertising/list-campaigns");
    	}

    	// delete campaign
    	$campaign->deleteWithReferencesByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Campaign deleted successfully.");
    	return $this->_redirect("admin/advertising/list-campaigns");
    }

	/**
	 * Add banner action
	 */
    public function addBannerAction() {
    	// get params
    	$customer_type = (int)$this->req->getParam("customer-type", 1);

    	// send form
    	$form = new Petolio_Form_AdBanner($customer_type);
		$this->view->form = $form;
		$this->view->customer_type = $customer_type;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
    	foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// set customer id to 1 if system default
		if($customer_type == 0) {
			$data['customer_id'] = 1;
			$data['system'] = 1;
		}

		// set customer id if only campaign id is selected
		if($customer_type == 2) {
			$campaign = $this->db->campaign->find($data['campaign_id']);
			$data['customer_id'] = $campaign->getCustomerId();
		}

		// this will be updated
		$data['width'] = 0;
		$data['height'] = 0;

		// save banner
		$this->db->banner->setOptions($data)->save(true, true);

		// update banner name
		$new = uniqid() . '.' . pathinfo($this->db->banner->getFile(), PATHINFO_EXTENSION);
		@rename($this->path . $this->db->banner->getFile(), $this->path . $new);

		// get width and height
		$props = @getimagesize($this->path . $new);
		$this->db->banner->setFile($new);
		$this->db->banner->setWidth($props[0]);
		$this->db->banner->setHeight($props[1]);
		$this->db->banner->save(true, true);

		// redirect to services index action
		$this->msg->messages[] = $this->translate->_("The advertising banner has been added successfully.");
		return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
    }

	/**
	 * Edit banner action
	 */
    public function editBannerAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$customer_type = (int)$this->req->getParam("customer-type", 1);
    	$banner = $this->db->banner->find($id);
    	if(!$banner->getId()) {
			$this->msg->messages[] = $this->translate->_("Banner does not exist.");
    		return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
    	}

    	// send form
    	$form = new Petolio_Form_AdBanner($customer_type, $banner->getFile());
    	$form->populate($banner->toArray());
		$this->view->form = $form;
		$this->view->customer_type = $customer_type;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
    	foreach($data as $idx => &$line) {
			if(is_array($line)) $line = (empty($line['year']) && empty($line['month']) && empty($line['day'])) ? NULL : "{$line['year']}-{$line['month']}-{$line['day']}";
			else $line = !(strlen($line) > 0) ? NULL : $line;
		}

		// set customer id to 1 if system default
		if($customer_type == 0) {
			$data['customer_id'] = 1;
			$data['system'] = 1;
		}

		// set customer id if only campaign id is selected
		if($customer_type == 2) {
			$campaign = $this->db->campaign->find($data['campaign_id']);
			$data['customer_id'] = $campaign->getCustomerId();
		}

		// fill out active
		if(!isset($data['active']) || !strlen($data['active']) > 0)
			$data['active'] = 0;

		// fill out file
		$upload = false;
		if(!isset($data['file']) || !strlen($data['file']) > 0)
			$data['file'] = $banner->getFile();

		// replace previous banner
		else {
			$upload = true;
			@unlink($this->path . $banner->getFile());
		}

		// save banner
		$banner->setOptions($data)->save(false, true);

		// update banner name
		if($upload) {
			$new = uniqid() . '.' . pathinfo($banner->getFile(), PATHINFO_EXTENSION);
			@rename($this->path . $banner->getFile(), $this->path . $new);

			// get width and height
			$props = @getimagesize($this->path . $new);
			$banner->setFile($new);
			$banner->setWidth($props[0]);
			$banner->setHeight($props[1]);
			$banner->save(true, true);
		}

		// redirect to services index action
		$this->msg->messages[] = $this->translate->_("The advertising banner has been updated successfully.");
		return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
    }

    /**
     * List banners action
     */
    public function listBannersAction() {
    	// get params
    	$customer_type = (int)$this->req->getParam("customer-type", 1);
    	$customer = $this->req->getParam("customer", '');
    	$campaign = $this->req->getParam("campaign", '');
    	$title = $this->req->getParam("title", '');
    	$type = $this->req->getParam("type", '');
    	$active = $this->req->getParam("active", '');

    	// output filters
    	$this->view->customer_type = $customer_type;
    	$this->view->customer = $customer;
    	$this->view->campaign = $campaign;
    	$this->view->title = $title;
    	$this->view->type = $type;
    	$this->view->active = $active;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'b.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// output customers and campaigns
    	$this->view->customers = $this->db->customer->fetchList("deleted != 1 AND type = {$customer_type}", "name");
    	$this->view->campaigns = $this->db->campaign->fetchList("deleted != 1", "name");

    	// handle filter
    	if($customer_type == 0) {
    		$where = array(
    			"b.deleted != 1",
    			"b.system = 1"
    		);
    	} else {
	    	$where = array(
	    		"b.deleted != 1",
	    		"b.system = 0",
	    		"c.type = {$customer_type}"
	    	);
    	}

    	// title
    	if(strlen($title) > 0)
    		$where[] = "b.title LIKE '%".strtolower($title)."%'";

    	// customer
    	if(strlen($customer) > 0)
    		$where[] = "b.customer_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($customer, Zend_Db::BIGINT_TYPE);

    	// campaign
    	if(strlen($campaign) > 0)
    		$where[] = "b.campaign_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($campaign, Zend_Db::BIGINT_TYPE);

    	// type
    	if(strlen($type) > 0)
    		$where[] = "b.type = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($type, Zend_Db::INT_TYPE);

    	// active
    	if(strlen($active) > 0)
    		$where[] = "b.active = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($active, Zend_Db::INT_TYPE);

		// get campaigns
		$paginator = $this->db->banner->getBanners('paginator', implode(" AND ", $where), "{$this->view->order} {$this->view->dir}");
		$paginator->setItemCountPerPage(25);
		$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output banners
		$this->view->banners = $paginator;
    }

    /**
     * Delete banner action
     */
    public function deleteBannerAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$customer_type = (int)$this->req->getParam("customer-type", 1);
    	$banner = $this->db->banner->find($id);
    	if(!$banner->getId()) {
    		$this->msg->messages[] = $this->translate->_("Banner does not exist.");
    		return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
    	}

    	// if system banner check if last
    	if($customer_type == 0) {
			if(count($this->db->banner->fetchList(array('deleted != 1', 'system = 1', "type = {$banner->getType()}"))) == 1) {
				$this->msg->messages[] = $this->translate->_("You cannot delete the last banner of that type.");
				return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
			}
    	}

    	// delete banner
    	$banner->setDeleted(1)->save(true, true);

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Banner deleted successfully.");
    	return $this->_redirect("admin/advertising/list-banners/customer-type/{$customer_type}");
    }
}