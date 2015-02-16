<?php

class ProductsController extends Zend_Controller_Action
{
	private $auth = null;
	private $request = null;
	private $cfg = null;
	private $translate = null;

	private $msg = null;
	private $up = null;
	private $lk = null;

	private $keyword = false;

    public function init() {
    	// init
    	$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");
		$this->translate = Zend_Registry::get('Zend_Translate');

		// session
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->lk = new Zend_Session_Namespace("po_messages_links");

		// db
		$this->db = new stdClass();
		$this->db->prods =new Petolio_Model_PoProducts();
		$this->db->fold = new Petolio_Model_PoFolders();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->attr = new Petolio_Model_PoAttributes();
		$this->db->sets = new Petolio_Model_PoAttributeSets();
		$this->db->opts = new Petolio_Model_PoAttributeOptions();
		$this->db->cntr = new Petolio_Model_PoCountriesMapper();
		$this->db->fles = new Petolio_Model_PoFiles();
		$this->db->cmnt = new Petolio_Model_PoComments();
		$this->db->rtng = new Petolio_Model_PoRatings();
		$this->db->subs = new Petolio_Model_PoSubscriptions();
		$this->db->favs = new Petolio_Model_PoFavorites();
		$this->db->flag = new Petolio_Model_PoFlags();

		// view
		$this->view->request = $this->request;
    }

	// pre
    public function preDispatch() {
		// load countries for searchbox
		$this->view->country_list = array();
		foreach($this->db->cntr->fetchAll() as $country)
			$this->view->country_list[$country->getId()] = $country->getName();

		// filter by species ?
		$this->view->types = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_species'"));
		foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $type)
			$this->view->types[$type->getId()] = Petolio_Service_Util::Tr($type->getValue());
		asort($this->view->types);
    }

	// post
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

    /*
	 * Build pet search filter
	 */
	private function buildSearchFilter($filter = array()) {
		$search = array();

		// vars
		$keyword = (string)$this->request->getParam('keyword');
		$country = (int)$this->request->getParam('country');
		$zipcode = (string)$this->request->getParam('zipcode');
		$address = (string)$this->request->getParam('address');
		$location = (string)$this->request->getParam('location');
		$owner = (string)$this->request->getParam('owner');
		$radius = (int)$this->request->getParam('radius');
		$species = (int)$this->request->getParam('species');

		// keyword
		if(strlen($keyword)) {
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
						"OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%").")";
			$search[] = $keyword;

			// set keyword search
			$this->keyword = true;
		}

		// country
		if($country != 0) {
			$filter[] = "x.country_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($country, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->country_list[$country];
		}

		// zipcode
		if(strlen($zipcode) > 0) {
			$filter[] = "x.zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$zipcode."%");
			$search[] = $zipcode;
		}

		// address
		if(strlen($address) > 0) {
			$filter[] = "x.address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$address."%");
			$search[] = $address;
		}

		// location
		if(strlen($location) > 0) {
			$filter['location'] = "x.location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($location)."%");
			$search[] = $location;
		}

		// owner
		if(strlen($owner) > 0) {
			$filter[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($owner)."%");
			$search[] = $owner;
		}

		// radius
		if(strlen($location) > 0 && $radius != 0) {
			// retrieve location from google
			$geocode = file_get_contents("http://maps.google.com/maps/api/geocode/json?address={$location}&sensor=false");
			$output = json_decode($geocode);

			// only if google gave results
			if($output->status != 'ZERO_RESULTS') {
				// get lat and long
				$lat = $output->results[0]->geometry->location->lat;
				$lng = $output->results[0]->geometry->location->lng;

				// set radius
				$difference = (float)($radius != 0 ? number_format(($radius / 111), 2) : 0.07);

				// unset previous location
				unset($filter['location']);

				// http://en.wikipedia.org/wiki/Pythagorean_theorem
				$filter[] = "POW(a.gps_latitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lat)).", 2) + " .
					"POW(a.gps_longitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lng)).", 2) <= " .
					"POW(".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($difference).", 2)";
				$search[] = sprintf($this->translate->_("Radius %s km"), $radius);
			}
		}

		// species
		if($species != 0) {
			$filter[] = "e2.value = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->types[$species];
		}

		// set filter
		if(count($search) > 0)
			$this->view->filter = implode(', ', $search);

		// return string
		return implode(' AND ', $filter);
	}

	/**
	 * List All Products
	 */
    public function indexAction() {
    	// control search
    	$this->view->search = true;

		// get filter
		$filter = $this->buildSearchFilter(array("a.archived = 0"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("All Products");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		/*
		 * petolio session seed for random sorting
		 * basically the random sort is saved for the pagination to work properly
		 *
		if(!isset($_SESSION["petolio_seed"])) {
			$uniq_id = uniqid();
			$_SESSION["petolio_seed"] = substr($uniq_id, strlen($uniq_id) - 5);
		}*/
		$sort = "RAND(".date("Ymd").")";

		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Products_".$filter."_".$sort."_".$this->keyword."_".$page);
		
		if (false === ($products = $cache->load($cacheID))) {
			// get products
			$paginator = $this->db->prods->getProducts('paginator', $filter, $sort, false, $this->keyword);
			$paginator->setItemCountPerPage($this->cfg["products"]["pagination"]["itemsperpage"]);
			$paginator->setCurrentPageNumber($page);
			
			$products = $this->db->prods->formatProducts($paginator);
			$cache->save($products, $cacheID);
		}

		// output products
		$this->view->products = $products;
	}

	/**
	 * List My Products
	 */
	public function myproductsAction() {
		// verify user
		$this->verifyUser();

		// control search
		$this->view->search = true;
		$this->view->mine = true;

		// get filter
		$filter = $this->buildSearchFilter(array("a.archived = 0", "a.user_id = '{$this->auth->getIdentity()->id}'"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("My Products");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get products
		$paginator = $this->db->prods->getProducts('paginator', $filter, "id DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->cfg["products"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->products = $this->db->prods->formatProducts($paginator);
	}

    /**
     * Render product options (the left side menu)
     */
    private function productOptions($product, $attr) {
		$this->view->product = $product;
		$this->view->product_attr = $attr;
		$this->view->render('products/product-options.phtml');
    }

	/**
	 * Add Product
	 */
	public function addAction() {
		// verify user
		$this->verifyUser();

		// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST["product_species"])) {
			$saved = $_POST["product_species"];
			$output = '';
			foreach($_POST["product_species"] as $one) {
				$this->db->opts->find($one);
				$value = $one == 0 ? Petolio_Service_Util::Tr("All") : Petolio_Service_Util::Tr($this->db->opts->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST["product_species"] = substr($output, 0, -1);
		}

		// create form
		$form = new Petolio_Form_Product('po_products');
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// everything fine, so put back the product_species
		$data["product_species"] = $saved;

		// save product
		$setid = reset(reset($this->db->sets->getMapper()->getDbTable()->getAttributeSets('po_products')));
		$this->db->prods->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'attribute_set_id' => $setid['id'],
			'primary_currency_id' => $data['primary_currency']
		))->save(true, true);

		// unset primary currency here
		unset($data['primary_currency']);

		// if "All" was selected as product_species
		if($data["product_species"]["0"] == "0") {
			$data["product_species"] = array();

			// get all species and put them in the field
			$attr = reset($this->db->attr->getMapper()->fetchList("code = 'product_species'"));
			foreach($this->db->opts->getMapper()->fetchList("attribute_id = '{$attr->getId()}'") as $all)
				$data["product_species"][] = $all->getId();
		}

		// save attributes
		$this->db->attr->getMapper()->getDbTable()->saveAttributeValues($data, $this->db->prods->getId());

		// do html
		$product_name = Petolio_Service_Parse::do_limit(ucfirst($data["product_title"]), 20, false, true);
		$reply = $this->view->url(array('controller'=>'products', 'action'=>'view', 'product'=>$this->db->prods->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has added a new <u>Product</u>: %2$s');
		$html = array(
			'%1$s has added a new <u>Product</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$product_name}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('product', array($html, $reply, $this->auth->getIdentity()->id));

		// redirect to pictures
		$this->msg->messages[] = $this->translate->_("Your product has been added successfully.");
		return $this->_redirect('products/pictures/product/'. $this->db->prods->getId());
	}

	/**
	 * Product - Get
	 */
	private function getProduct($auth = false, $archived = 0) {
		// get jesus
		$jesus = Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('product'), Zend_Db::BIGINT_TYPE);

		// build where
		if($auth) $where = "id = '{$jesus}' AND user_id = '{$this->auth->getIdentity()->id}' AND archived = '{$archived}'";
		else $where = "id = '{$jesus}' AND archived = '{$archived}'";

		// get product
		$result = $this->db->prods->fetchList($where);
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Product does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// the product!
		$product = reset($result);

		// see if the product owner is active and not banned
		if(!($product->getOwner()->getActive() == 1 && $product->getOwner()->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		// return product
		return $product;
	}

	/**
	 * Edit Product
	 */
	public function editAction() {
		// verify user
		$this->verifyUser();

		// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST["product_species"])) {
			$saved = $_POST["product_species"];
			$output = '';
			foreach($_POST["product_species"] as $one) {
				$this->db->opts->find($one);
				$value = $one == 0 ? Petolio_Service_Util::Tr("All") : Petolio_Service_Util::Tr($this->db->opts->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST["product_species"] = substr($output, 0, -1);
		}

		// get product
		$product = $this->getProduct(true);

		// load product attributes
		$populate = array();
		$attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product));
		foreach($attributes as $attr) {
			$type = $attr->getAttributeInputType();
			if($type->getName() == 'text' && $type->getType() == 'select') { // ajax
				$val = '';

				// all
				if(count($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'")) == count($attr->getAttributeEntity()))
					$val = "0|" . Petolio_Service_Util::Tr("All");

				// load species
				else {
					foreach($attr->getAttributeEntity() as $one) {
						$this->db->opts->find($one->getValue());
						$val .= $one->getValue() . "|" . Petolio_Service_Util::Tr($this->db->opts->getValue()) . ',';
					}
					$val = substr($val, 0, -1);
				}

			} else
				$val = $attr->getAttributeEntity()->getValue();

			$populate[$attr->getCode()] = array("value" => $val, "type" => $attr->getAttributeInputType()->getType());
		}

		// load primary currency
		$populate['primary_currency'] = $product->getPrimaryCurrencyId();

		// load menu
		$this->productOptions($product, $attributes);

		// init form
		$form = new Petolio_Form_Product('po_products');
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

		// everything fine, so put back the product_species
		$data["product_species"] = $saved;

		// save product
		$product
			->setDateModified(date('Y-m-d H:i:s', time()))
			->setPrimaryCurrencyId($data['primary_currency'])
			->save(false, true);

		// unset primary currency here
		unset($data['primary_currency']);

		// if "All" was selected as product_species
		if($data["product_species"]["0"] == "0") {
			$data["product_species"] = array();

			// get all species
			$attr = reset($this->db->attr->getMapper()->fetchList("code = 'product_species'"));
			foreach($this->db->opts->getMapper()->fetchList("attribute_id = '{$attr->getId()}'") as $all)
				$data["product_species"][] = $all->getId();
		}

		// save attributes
		$this->db->attr->getMapper()->getDbTable()->saveAttributeValues($data, $product->getId());

		// redirect to pictures
		$this->msg->messages[] = $this->translate->_("Your product has been edited successfully.");
		return $this->_redirect('products/pictures/product/'. $product->getId());
	}

	/**
	 * Product - Pictures
	 */
	public function picturesAction() {
		// verify user
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get product
		$product = $this->getProduct(true);

		// load menu
		$this->productOptions($product, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product, true)));

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}products{$ds}{$product->getId()}{$ds}";

		// create a folder for our product if it does not exist
		$gallery = null;
		if($product->getFolderId()) {
			$search_vars = array('id' => $product->getFolderId());
			$gallery = $this->db->fold->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if(!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'product', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->db->fold->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our product too
			$product->setFolderId($gallery->getId());
			$product->save();
		}

		// load form
		$form = new Petolio_Form_Upload($this->translate->_('Picture'), $this->translate->_('Upload Pictures'));
		$this->view->form = $form;

		// get & show all pictures
		$result = $this->db->fles->fetchList("type = 'image' AND folder_id = '{$gallery->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC");
		$this->view->gallery = $result;

    	// make picture primary
    	$primary = $this->request->getParam('primary');
    	if (isset($primary)) {
			// get level
			$result = $this->db->fles->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($primary, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_redirect('products/pictures/product/'. $product->getId());
			} else
				$pic = reset($result);

			// get all other pictures
			$result = reset($this->db->fles->fetchList("folder_id = '{$pic->getFolderId()}' AND type = 'image' AND owner_id = '{$this->auth->getIdentity()->id}'", "date_created ASC"));
			$first = strtotime($result->getDateCreated());

			// save order
			$pic->setDateCreated(date('Y-m-d H:i:s', strtotime($result->getDateCreated()) - 1))->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("Your Picture has been marked as primary.");
    		return $this->_redirect('products/pictures/product/'. $product->getId());
		}

		// get picture remove
		$remove = $this->request->getParam('remove');
		if( isset($remove) ) {
			// get level
			$result = $this->db->fles->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Picture does not exist.");
				return $this->_redirect('products/pictures/product/'. $product->getId());
			} else
				$pic = reset($result);

			// delete from hdd
			@unlink($upload_dir . $pic->getFile());
			@unlink($upload_dir . 'thumb_' . $pic->getFile());
			@unlink($upload_dir . 'small_' . $pic->getFile());

			// delete all comments, likes and subscriptions
			$this->db->cmnt->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->rtng->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->subs->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");

			// delete file from db
			$pic->deleteRowByPrimaryKey();

			// msg
			$this->msg->messages[] = $this->translate->_("Your Picture has been deleted successfully.");
			return $this->_redirect('products/pictures/product/'. $product->getId());
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the products directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the services folder on disk.")));
				return $this->_redirect('products/pictures/product/'. $product->getId());
			}
		}

		// create the products gallery directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the service folder on disk.")));
				return $this->_redirect('products/pictures/product/'. $product->getId());
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
				return $this->_redirect('products/pictures/product/'. $product->getId());
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

			$file = clone $this->db->fles;
			$file->setOptions($opt);
			$file->save();
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your product pictures have been uploaded successfully.");
		return $this->_redirect('products/pictures/product/'. $product->getId());
	}

	/**
	 * Get HTTP status code
	 */
	private function getCode($status = null) {
		// set codes index
	    $codes = array(
	        100 => array('HTTP/1.1', 'Continue'),
	        101 => array('HTTP/1.1', 'Switching Protocols'),
	        200 => array('HTTP/1.0', 'OK'),
	        201 => array('HTTP/1.0', 'Created'),
	        202 => array('HTTP/1.0', 'Accepted'),
	        203 => array('HTTP/1.0', 'Non-Authoritative Information'),
	        204 => array('HTTP/1.0', 'No Content'),
	        205 => array('HTTP/1.0', 'Reset Content'),
	        206 => array('HTTP/1.0', 'Partial Content'),
	        300 => array('HTTP/1.0', 'Multiple Choices'),
	        301 => array('HTTP/1.0', 'Permanently at another address - consider updating link'),
	        302 => array('HTTP/1.1', 'Found at new location - consider updating link'),
	        303 => array('HTTP/1.1', 'See Other'),
	        304 => array('HTTP/1.0', 'Not Modified'),
	        305 => array('HTTP/1.0', 'Use Proxy'),
	        306 => array('HTTP/1.0', 'Switch Proxy'), // No longer used, but reserved
	        307 => array('HTTP/1.0', 'Temporary Redirect'),
	        400 => array('HTTP/1.0', 'Bad Request'),
	        401 => array('HTTP/1.0', 'Authorization Required'),
	        402 => array('HTTP/1.0', 'Payment Required'),
	        403 => array('HTTP/1.0', 'Forbidden'),
	        404 => array('HTTP/1.0', 'Not Found'),
	        405 => array('HTTP/1.0', 'Method Not Allowed'),
	        406 => array('HTTP/1.0', 'Not Acceptable'),
	        407 => array('HTTP/1.0', 'Proxy Authentication Required'),
	        408 => array('HTTP/1.0', 'Request Timeout'),
	        409 => array('HTTP/1.0', 'Conflict'),
	        410 => array('HTTP/1.0', 'Gone'),
	        411 => array('HTTP/1.0', 'Length Required'),
	        412 => array('HTTP/1.0', 'Precondition Failed'),
	        413 => array('HTTP/1.0', 'Request Entity Too Large'),
	        414 => array('HTTP/1.0', 'Request-URI Too Long'),
	        415 => array('HTTP/1.0', 'Unsupported Media Type'),
	        416 => array('HTTP/1.0', 'Requested Range Not Satisfiable'),
	        417 => array('HTTP/1.0', 'Expectation Failed'),
	        449 => array('HTTP/1.0', 'Retry With'), // Microsoft extension
	        500 => array('HTTP/1.0', 'Internal Server Error'),
	        501 => array('HTTP/1.0', 'Not Implemented'),
	        502 => array('HTTP/1.0', 'Bad Gateway'),
	        503 => array('HTTP/1.0', 'Service Unavailable'),
	        504 => array('HTTP/1.0', 'Gateway Timeout'),
	        505 => array('HTTP/1.0', 'HTTP Version Not Supported'),
	        509 => array('HTTP/1.0', 'Bandwidth Limit Exceeded') // not an official HTTP status code
	    );

		// custom codes?
	    if(!isset($codes[$status]))
	        return $this->translate->_("Unknown Error");

		// return what was found
	    return "{$codes[$status][0]} $status {$codes[$status][1]}";
	}

	/**
	 * Product - Links
	 */
	public function linksAction() {
		// verify user
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->lk = $this->lk->msg;
		unset($this->lk->msg);

		// get product
		$product = $this->getProduct(true);

		// load menu
		$this->productOptions($product, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product, true)));

		// post sent?
		if(!(isset($_POST) && count($_POST) > 0))
			return false;

		// get names & links
		$names = array();
		$links = array();
		foreach($_POST as $idx => $item) {
			if(strpos($idx, 'item_name_') !== false && $item != $this->translate->_("Name"))
				if(strlen(trim($item)) > 0)
					$names[] = $item;

			if(strpos($idx, 'item_link_') !== false && $item != 'http://')
				if(strlen(trim($item)) > 0)
					$links[] = $item;
		}

		// put names and links together
		$all = array();
		foreach($names as $idx => $name)
			if(isset($links[$idx]))
				$all[$name] = $links[$idx];

		// prepare
		$errors = array();
		$success = array();

		// check links
		foreach($all as $name => $link) {
			// perform curl
			$ch = curl_init($link);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// set success or true (200 - OK)
			if($retcode != 200) $errors[$link] = $this->getCode($retcode);
			else $success[$name] = $link;
		}

		// save messages
		$this->lk->msg['errors'] = $errors;
		$this->lk->msg['success'] = $success;

		// some links were good?
		if(count($success) > 0) {
			$product->setLinks(serialize($success));
			$product->save();
		} else {
			$product->setLinks(new Zend_Db_Expr('NULL'));
			$product->save();
		}

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $this->translate->_("Your product links have been updated successfully.");
		return $this->_redirect('products/links/product/'. $product->getId());
	}

    /**
     * Get Product Pictures
     * @param int $id - folder id
     */
    private function productPictures($id) {
    	// got page?
    	$page = $this->request->getParam('page') ? intval($this->request->getParam('page')) : 0;

		// find folder
		$gallery = $this->db->fold->getMapper()->getDbTable()->findFolders(array('name' => 'product', 'id' => $id));
		if(!isset($gallery))
			return false;

		// get pictures
		$paginator = $this->db->fles->select2Paginator($this->db->fles->getMapper()->getDbTable()->fetchList("folder_id = '{$gallery->getId()}'", "date_created ASC"));
		$paginator->setItemCountPerPage(14);
		$paginator->setCurrentPageNumber($page);

		// create array
		$pictures = array();
		foreach ($paginator->getItemsByPage($page) as $row) {
			$pictures[$row["id"]] = $row["file"];
		}

		// output
		$this->view->gallery = $pictures;
		$this->view->picture_paginator = $paginator;
    }

    /**
     * Product - View
     */
    public function viewAction() {
		// get product
		$product = $this->getProduct(false);

		// load menu
		$this->productOptions($product, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product, true)));

		// if flagged, load reasons
		$this->view->flagged = array();
		if($product->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->db->flag->getMapper()->fetchList("scope = 'po_products' AND entry_id = '{$product->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get product pictures
		$this->productPictures($product->getFolderId());

		// if product is yours tell me :)
		if(isset($this->auth->getIdentity()->id))
			$this->view->admin = $product->getUserId() == $this->auth->getIdentity()->id ? true : false;

		// only increment views if question is not yours
		if(!$this->view->admin)
			$product->setViews(($product->getViews() + 1))->save();

		// format attributes
		$attrs = array();
		$cell = false;
		$address = false;
		$increment = 1;
		foreach ($this->view->product_attr as $attr) {
			// default key
			$key = 'details';

			// duration?
			if($attr->getCode() == 'product_duration')
				$key = 'duration';

			// price?
			if(!is_null($attr->getCurrencyId()))
				$key = 'pricing';

			// customer info? skip this
			if($attr->getCode() == 'product_celular' || $attr->getCode() == 'product_address') {
				$val = $attr->getAttributeEntity()->getValue();
				if($val == "Yes" || $val == "Ja") {
					if($attr->getCode() == 'product_celular')
						$cell = true;

					if($attr->getCode() == 'product_address')
						$address = true;
				}

				continue;
			}

			// product description? skip this
			if($attr->getCode() == 'product_description')
				$key = 'description';

			// array?
			if(is_array($attr->getAttributeEntity())) {
				$val = '';
				if($attr->getCode() == 'product_species' && count($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'")) == count($attr->getAttributeEntity()))
					$val = Petolio_Service_Util::Tr('All');
				else {
					foreach($attr->getAttributeEntity() as $one)
						$val .= Petolio_Service_Util::Tr($one->getValue()) . ', ';
					$val = substr($val, 0, -2);
				}

			// string
			} else $val = $attr->getAttributeEntity()->getValue();

			// set attributes
			if(isset($val) && strlen($val) > 0) {
				// multiple prices
				if(!is_null($attr->getCurrencyId())) {
					$attrs[$key][$this->view->Tr($attr->getLabel())][$attr->getCurrencyId() == $product->getPrimaryCurrencyId() ? 0 : $increment] = $val;
					$increment++;
				} else $attrs[$key][$this->view->Tr($attr->getLabel())] = $val;
			}
		}

		// format pricing
		if(isset($attrs['pricing'])) {
			foreach($attrs['pricing'] as &$type) {
				ksort($type);
				$type = array_merge($type);
			}
		}

		// output attrs
		$this->view->attrs = $attrs;
		$this->view->cell = $cell;
		$this->view->address = $address;
    }

	/**
	 * Product - Finish
	 */
    public function finishAction() {
		// verify user
		$this->verifyUser();

    	// redirect with message
		$this->msg->messages[] = $this->translate->_("Your product details have been updated successfully.");
		return $this->_helper->redirector('myproducts', 'products');
    }

	/**
	 * Product - Print
	 */
    public function printAction() {
		// disable layout for print
		$this->_helper->layout->disableLayout();

		// call out the view action
		$this->viewAction();
    }

	/**
	 * Product - Contact
	 */
    public function contactAction() {
		// get product
		$product = $this->getProduct(false);

		// load menu
		$this->productOptions($product, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product, true)));

		// authenticated? redirect to send message
    	if($this->auth->hasIdentity())
			return $this->_helper->redirector('send', 'messages', 'frontend', array('id' => $product->getUserId(), 'product' => base64_encode($product->getId() . '|-+-|' . ucfirst($this->view->product_attr['title']->getAttributeEntity()->getValue()))));

		// get user
		$id = $product->getUserId();

		// load user
		$this->db->user->find($id);
		if(!$this->db->user->getId())
			return $this->_helper->redirector('index', 'site');

		// send user to template
		$this->view->user = $this->db->user;

		// init form
		$form = new Petolio_Form_Reply($this->translate->_("Send Message >"), false, false, true);
		$form->populate(array(
			'subject' => $this->translate->_('Question for the following product:') . ' ' . ucfirst($this->view->product_attr['title']->getAttributeEntity()->getValue()),
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
			'product' => $product->getId(),
			'template' => 'default'
		), array(array(
			'id' => $this->db->user->getId(),
			'name' => $this->db->user->getName(),
			'email' => $this->db->user->getEmail()
		)), $this->db->user->isOtherEmailNotification());

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The private message was successfully sent.");
		return $this->_helper->redirector('view', 'products', 'frontend', array('product' => $product->getId()));
    }

	/**
	 * Product - Add to Favorite
	 */
    public function favoriteAction() {
		// verify user
		$this->verifyUser();

		// get product
		$product = $this->getProduct(false);

    	// already exists?
    	$result = $this->db->favs->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND entity_id = '{$product->getId()}' AND scope = 'po_products'");
    	if((is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Product is already on your favorite list.");
    		return $this->_helper->redirector('index', 'site');
    	}

    	// mark as favorite
    	$this->db->favs->setOptions(array(
    		'user_id' => $this->auth->getIdentity()->id,
    		'entity_id' => $product->getId(),
    		'scope' => 'po_products'
    	))->save();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Product was added to your favorites successfully.");
    	return $this->_helper->redirector('favorites', 'products');
    }

	/**
	 * Product - Clear from Favorite
	 */
    public function clearAction() {
		// verify user
		$this->verifyUser();

		// get product
		$product = $this->getProduct(false);

    	// get faved
    	$result = $this->db->favs->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND entity_id = '{$product->getId()}' AND scope = 'po_products'");
    	if(!(is_array($result) && count($result) > 0)) {
    		$this->msg->messages[] = $this->translate->_("Favorite Product does not exist.");
    		return $this->_helper->redirector('index', 'site');
    	} else $product = reset($result);

    	// delete from fav
    	$product->deleteRowByPrimaryKey();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("Your favorite product has been removed successfully.");
    	return $this->_helper->redirector('favorites', 'products');
    }

	/**
	 * Product - Favorite List
	 */
    public function favoritesAction() {
		// verify user
		$this->verifyUser();

		// control search
		$this->view->search = true;

    	// get favorites
    	$favs = array();
    	$result = $this->db->favs->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = 'po_products'");
    	foreach($result as $one)
    		$favs[] = $one->getEntityId();

		// get filter
		$filter = array("a.archived = 0");
		if(count($favs) > 0) $filter[] = "a.id IN (" . implode(',', $favs) . ")";
		else $filter[] = '1 = 2';
		$filter = $this->buildSearchFilter($filter);

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("Favorite Products");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get products
		$paginator = $this->db->prods->getProducts('paginator', $filter, "id DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->cfg["products"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->products = $this->db->prods->formatProducts($paginator);
    }

	/**
	 * Product - Add to Archive
	 */
    public function archiveAction() {
		// verify user
		$this->verifyUser();

   		// get product
		$product = $this->getProduct(true);

		// mark as deleted
		$product->setArchived('1')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your product has been archived successfully.");
		return $this->_helper->redirector('myproducts', 'products');
    }

	/**
	 * Product - Restore from Archive
	 */
    public function restoreAction() {
		// verify user
		$this->verifyUser();

   		// get product
		$product = $this->getProduct(true, 1);

		// mark as deleted
		$product->setArchived('0')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your product has been restored successfully.");
		return $this->_helper->redirector('myproducts', 'products');
    }

	/**
	 * Product - Archive List
	 */
    public function archivesAction() {
		// verify user
		$this->verifyUser();

		// control search
		$this->view->search = true;
		$this->view->mine = true;

		// get filter
		$filter = $this->buildSearchFilter(array("a.archived = 1"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("Archived Products");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get products
		$paginator = $this->db->prods->getProducts('paginator', $filter, "id DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->cfg["products"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->products = $this->db->prods->formatProducts($paginator);
    }
}