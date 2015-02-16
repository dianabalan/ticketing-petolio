<?php

class ProductsController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $req = null;
	private $cfg = null;

	private $db = null;
	private $op = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->currencies = new Petolio_Model_PoCurrencies();
		$this->db->products = new Petolio_Model_PoProducts();
		$this->db->attr = new Petolio_Model_PoAttributes();
		$this->db->sets = new Petolio_Model_PoAttributeSets();
		$this->db->opts = new Petolio_Model_PoAttributeOptions();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->flag = new Petolio_Model_PoFlags();
		$this->db->fold = new Petolio_Model_PoFolders();
		$this->db->fles = new Petolio_Model_PoFiles();

		// advanced filter operators
		$this->op = array(
			'1' => "> %f",
			'2' => ">= %f",
			'3' => "= %f",
			'4' => "<= %f",
			'5' => "< %f",
			'6' => "LIKE '^%s^'",
		);
    }

    public function indexAction() {
        // action body
    }

    /**
     * Filter function used for list users and export csv
     * @return array of where, having and joins params
     */
    private function _filter() {
    	// based on URL
    	$keyword = $this->req->getParam("keyword", '');
		$species = $this->req->getParam("species", '');
    	$owner = $this->req->getParam("owner", '');
    	$archived = $this->req->getParam("archived", '');
    	$match = $this->req->getParam("match", '');
    	$advanced = $this->req->getParam("advanced", '');

    	// output filters
    	$this->view->keyword = $keyword;
		$this->view->species = $species;
    	$this->view->owner = $owner;
    	$this->view->archived = $archived;
    	$this->view->match = $match;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// species resources
    	$sort = array();
    	$this->view->species_list = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_species'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c->getValue());
    		$sort[$k] = $_t;
    		$this->view->species_list[] = array('value'=> $c->getId(), 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->species_list);

//--------------------
    	// type resources
    	$sort = array();
    	$this->view->product_type = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_type'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c->getValue());
    		$sort[$k] = $_t;
    		$this->view->product_type[] = array('value'=> $c->getId(), 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->product_type);

    	// condition resources
    	$sort = array();
    	$this->view->product_condition = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_condition'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c->getValue());
    		$sort[$k] = $_t;
    		$this->view->product_condition[] = array('value'=> $c->getId(), 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->product_condition);

    	// duration resources
    	$sort = array();
    	$this->view->product_duration = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_duration'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c->getValue());
    		$sort[$k] = $_t;
    		$this->view->product_duration[] = array('value'=> $c->getId(), 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->product_duration);

    	// price type resources
    	$sort = array();
    	$this->view->product_pricetype = array();
		$attr = reset($this->db->attr->fetchList("code = 'product_pricetype'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c->getValue());
    		$sort[$k] = $_t;
    		$this->view->product_pricetype[] = array('value'=> $c->getId(), 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->product_pricetype);

    	// currency resources
    	$sort = array();
    	$this->view->currency_csv = array();
    	$this->view->product_currency = array();
    	foreach($this->db->currencies->fetchAll() as $k => $c) {
    		$sort[$k] = $c->getName();
    		$this->view->currency_csv[$c->getId()] = $c->getName();
    		$this->view->product_currency[] = array('value'=> $c->getId(), 'name' => $c->getName());
    	} array_multisort($sort, SORT_ASC, $this->view->product_currency);
//--------------------

    	// handle filter
    	$where = array();

    	// keywords
    	if(strlen($keyword) > 0)
			$where[] = "(d1.value LIKE '%".strtolower($keyword)."%' OR d4.value LIKE '%".strtolower($keyword)."%')";

		// species
    	if(strlen($species) > 0)
    		$where[] = "e2.value = ".(int)$species;

    	// owner
    	if(strlen($owner) > 0)
    		$where[] = "x.name LIKE '%".strtolower($keyword)."%'";

    	// archived
    	if(strlen($archived) > 0)
    		$where[] = "a.archived = ".(int)$archived;

    	// handle advanced
    	if(strlen($advanced) > 0) {
    		$advanced = json_decode(base64_decode($advanced), true);

    		// go through each filter
    		foreach($advanced as $one) {
    			if($one['filter'] == 'f8.value')
					$one['value'] = str_replace(",", ".", $one['value']);

    			// calc operator and value
    			$end = str_replace('^', '%', sprintf($this->op[$one['operator']], $one['value']));

				// add where filter
    			$where[] = $one['filter'] . ' ' . $end;
    		}
    	}

    	// match all or one
    	$where = count($where) > 0 ? implode($match ? " OR " : " AND ", $where) : 'a.archived = 0 OR a.archived = 1';

    	// return the array
    	return array($where, $advanced ? $advanced : false);
    }

    /**
     * List products
     */
    public function listProductsAction() {
		// filters
		list($where, $advanced) = $this->_filter();

    	// get products
    	$paginator = $this->db->products->getProducts('paginator', $where, "{$this->view->order} {$this->view->dir}", false, true, $advanced);
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output users
    	$this->view->products = $this->db->products->formatProducts($paginator);
    }

    /**
     * Edit product
     */
    public function editProductAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$product = $this->db->products->find($id);
    	if(!$product->getId()) {
    		$this->msg->messages[] = $this->translate->_("Product does not exist.");
    		return $this->_redirect('admin/products/list-products');
    	}

    	// get owner
    	$user = $this->db->user->find($product->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Product Owner does not exist.");
    		return $this->_redirect('admin/products/list-products');
    	}

    	// load product attributes
    	$populate = array('flagged' => $product->getFlagged());
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

		// init form
		$form = new Petolio_Form_Product('po_products', true);
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

		// species displacement
		if($data['product_species']) {
			$displacement = array();
			foreach(explode(",", $data['product_species']) as $group) {
				list($id, $name) = explode("|", $group);
					$displacement[] = $id;
			}

			$data['product_species'] = $displacement;
		}

		// save product
		$product
			->setDateModified(date('Y-m-d H:i:s', time()))
			->setPrimaryCurrencyId($data['primary_currency']);
		unset($data['primary_currency']);

		// save flagged
		$product->setFlagged($data['flagged']);
		unset($data['flagged']);

		// save product
		$product->save(true);

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

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("One of your products has been edited"),
			'message_html' => sprintf($this->translate->_("Petolio Admin Team has edited %s"), "<a href='{$this->view->url(array('controller'=>'products', 'action'=>'view', 'product'=> $product->getId()), 'default', true)}'>{$attributes['title']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// redirect
		$this->msg->messages[] = $this->translate->_("The Product has been saved successfully.");
		return $this->_redirect('admin/products/list-products');
    }

    /**
     * Delete product
     */
    public function deleteProductAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$product = $this->db->products->find($id);
    	if(!$product->getId()) {
    		$this->msg->messages[] = $this->translate->_("Product does not exist.");
    		return $this->_redirect('admin/products/list-products');
    	}

    	// get owner
    	$user = $this->db->user->find($product->getUserId());
    	if(!$user->getId()) {
    		$this->msg->messages[] = $this->translate->_("Product Owner does not exist.");
    		return $this->_redirect('admin/products/list-products');
    	}

    	// get pet attributes
    	$attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product));

    	// set switch
    	$switch = $product->getArchived() == 1 ? 0 : 1;
    	$product->setArchived($switch)->save();

		// notify the user
		Petolio_Service_Message::send(array(
			'subject' => $switch == 1 ? $this->translate->_("One of your products has been archived") : $this->translate->_("One of your products has been restored"),
			'message_html' => $switch == 1 ? sprintf($this->translate->_("Petolio Admin Team has archived %s"), "<a href='{$this->view->url(array('controller'=>'products', 'action'=>'archives'), 'default', true)}'>{$attributes['title']->getAttributeEntity()->getValue()}</a>") : sprintf($this->translate->_("Petolio Admin Team has restored %s"), "<a href='{$this->view->url(array('controller'=>'products', 'action'=>'view', 'product'=> $product->getId()), 'default', true)}'>{$attributes['title']->getAttributeEntity()->getValue()}</a>"),
			'from' => 0,
			'status' => 1
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

    	// msg and redirect
    	$this->msg->messages[] = $switch == 1 ? $this->translate->_("Product was archived.") : $this->translate->_("Product was restored.");
    	return $this->_redirect('admin/products/list-products');
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
    		"scope = 'po_products'",
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
    		return $this->_redirect('admin/products/list-products');
    	}

    	// delete flag
    	$flag->deleteRowByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Flag was deleted.");
    	return $this->_redirect("admin/products/list-flags/id/{$flag->getEntryId()}");
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
    		return $this->_redirect('admin/products/list-products');
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
		return $this->_redirect('admin/products/list-products');
    }

    /**
     * Export to cvs
     */
    public function exportCsvAction() {
		// get filter
		list($where, $advanced) = $this->_filter();

    	// get products
    	$data = $this->db->products->formatProducts($this->db->products->getProducts('paginator', $where, "{$this->view->order} {$this->view->dir}", false, true, $advanced));
    	foreach($data as &$one) {
			// transform this data
			$one['archived'] = $one['archived'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
			$one['primary_currency'] = $this->view->currency_csv[$one['primary_currency_id']]; unset($one['primary_currency_id']);
			$one['flagged'] = $one['flagged'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
		}

		// get the base
		$base = array();
		foreach($data as &$one)
			$base[$one['id']] = $one;

		// get list of products
		$products = array_keys($base);
		$where = array();
		foreach($products as $one)
			$where[] = "id = {$one}";

		// now get attributes
		$product = $this->db->products->fetchList(implode(" OR ", $where));
		foreach ($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($product, true) as $key => $p_attr) {
			// handle key
			list($key, $type) = explode('_', $key);

			foreach($p_attr as $attr) {
				if($attr->getLabel() == 'Description')
					continue;

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

				$base[$key][$attr->getCode()] = $val;
			}
		}

		// output as csv
		$out = $this->_array_to_CSV(array(
			$this->translate->_('Id'),
			$this->translate->_('Date Created'),
			$this->translate->_('Date Modified'),
			$this->translate->_('Title'),
			$this->translate->_('Product Species'),
			$this->translate->_('User Name'),
			$this->translate->_('Type'),
			$this->translate->_('Condition'),
			$this->translate->_('Duration'),
			$this->translate->_('Euro Price'),
			$this->translate->_('Dollar Price'),
			$this->translate->_('Price Type'),
			$this->translate->_('Show Address'),
			$this->translate->_('Show Cellphone'),
			$this->translate->_('Shipping'),
			$this->translate->_('Euro Shipping Cost'),
			$this->translate->_('Dollar Shipping Cost'),
			$this->translate->_('Primary Currency'),
			$this->translate->_('Tags'),
			$this->translate->_('Archived'),
			$this->translate->_('Flagged'),
			$this->translate->_('Picture')
		));

		// output as csv
		foreach($base as $one) {
			$out .= $this->_array_to_CSV(array(
				$one['id'],
				$one['date_created'],
				$one['date_modified'],
				$one['product_title'],
				$one['product_species'],
				$one['user_name'],
				$one['product_type'],
				$one['product_condition'],
				$one['product_duration'],
				$one['product_price1'],
				$one['product_price2'],
				$one['product_pricetype'],
				$one['product_address'],
				$one['product_celular'],
				$one['product_shipping'],
				$one['product_shippingcost1'],
				$one['product_shippingcost2'],
				$one['primary_currency'],
				$one['product_tags'],
				$one['archived'],
				$one['flagged'],
				$one['picture']
			));
		}

		// send headers
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=products-".time().".csv");
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

	 /**
     * Import from .zip
     */
    public function importProductsAction() {
		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}temp{$ds}";

		// create form
		$form = new Petolio_Form_ImportProducts();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost() && $this->req->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// create the users id directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->msg->messages[] = $this->translate->_("There was an critical error regarding the creation of the temp folder on disk.");
				return $this->_redirect('admin/products/import-products');
			}
		}

		// get adapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('IsCompressed', false, 'zip');
		$adapter->addValidator('Size', false, $this->cfg['max_filesize']);

		// check if files have exceeded the limit
		if (!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->msg->messages[] = sprintf($this->translate->_("Your zip file exceed the maximum size limit allowed (%s)"), $this->cfg['phpSettings']['upload_max_filesize']);
				return $this->_redirect('admin/products/import-products');
			}
		}

		// no file ?
		if(!$adapter->getFileName()) {
			$this->msg->messages[] = $this->translate->_("Please select a zip file to upload.");
			return $this->_redirect('admin/products/import-products');
		}

		// error on upload ?
		$file = $adapter->getFileName();
		if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) {
			$this->msg->messages[] = reset($adapter->getMessages());
			return $this->_redirect('admin/products/import-products');
		}

		// unzip the file
		$zip = new ZipArchive;
		$res = $zip->open($file);
		$dir = $file . '_' . $ds;
		if ($res === TRUE) {
			if (!file_exists($dir)) {
				if (!mkdir($dir)) {
					$this->msg->messages[] = $this->translate->_("There was an critical error regarding the creation of the zip file folder on disk.");
					return $this->_redirect('admin/products/import-products');
				}
			}

		  $zip->extractTo($dir);
		  $zip->close();
		} else {
			$this->msg->messages[] = $this->translate->_("Cannot open zip file. Archive is damaged or of wrong format.");
			return $this->_redirect('admin/products/import-products');
		}

		// unlink the zip file
		@unlink($file);

		// get form data
		$data = $form->getValues();
		$user_id = $data['user_id'];

		// find the csv file
		$handler = opendir($dir);
		$import_file = false;
		while ($file = readdir($handler))
		    if ($file != "." && $file != "..")
		        if(preg_match('/\.(csv)$/', $file))
		            $import_file = $file;

		// read csv file
		$data = array();
		if(($handle = fopen($dir . $import_file, "r")) !== FALSE)
			while($row = fgetcsv($handle, 1000, ","))
				$data[] = $row;

		// get set id for products
		$setid = reset(reset($this->db->sets->getMapper()->getDbTable()->getAttributeSets('po_products')));

		// handle species dictionary
    	$species = array();
		$all_species = array();
		$matrix = array(
			'Lizard' => 'Eidechse',
			'Fish' => 'Fisch',
			'Ferret' => 'Frettchen',
			'Gecko' => 'Gecko',
			'Dog' => 'Hund',
			'Rabbit' => 'Kaninchen',
			'Cat' => 'Katze',
			'Crocodile' => 'Krokodil',
			'Rodent' => 'Nagetier',
			'Horse' => 'Pferd',
			'Turtle' => 'Schildkroete',
			'Snake' => 'Schlange',
			'Scorpion' => 'Skorpion',
			'Other' => 'Sonstige',
			'Spider' => 'Spinne',
			'Bird' => 'Vogel'
		);
		$attr = reset($this->db->attr->fetchList("code = 'product_species'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$species[$matrix[$c->getValue()]] = $c->getId();
    		$species[$c->getValue()] = $c->getId();
			$all_species[] = $c->getId();
		}

		$species['All'] = "0";
		$species['Alle'] = "0";

		// handle type dictionary
    	$types = array();
		$matrix = array(
			'Food' => 'Futtermittel',
			'Non-Food' => 'Kein Futtermittel'
		);
		$attr = reset($this->db->attr->fetchList("code = 'product_type'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$types[$matrix[$c->getValue()]] = $c->getId();
    		$types[$c->getValue()] = $c->getId();
		}

		// handle condition dictionary
    	$conditions = array();
		$matrix = array(
			'New' => 'Neu',
			'Used' => 'Gebraucht'
		);
		$attr = reset($this->db->attr->fetchList("code = 'product_condition'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$conditions[$matrix[$c->getValue()]] = $c->getId();
    		$conditions[$c->getValue()] = $c->getId();
		}

		// handle duration dictionary
    	$durations = array();
		$matrix = array(
			'1 Month' => '1 Monat',
			'3 Months' => '3 Monate',
			'6 Months' => '6 Monate',
			'7 Days' => '7 Tage'
		);
		$attr = reset($this->db->attr->fetchList("code = 'product_duration'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$durations[$matrix[$c->getValue()]] = $c->getId();
    		$durations[$c->getValue()] = $c->getId();
		}

		// handle price type dictionary
    	$pricetypes = array();
		$matrix = array(
			'Asking Price' => 'Preis auf Anfrage',
			'Fixed Price' => 'Festpreis'
		);
		$attr = reset($this->db->attr->fetchList("code = 'product_pricetype'"));
    	foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $k => $c) {
    		$pricetypes[$matrix[$c->getValue()]] = $c->getId();
    		$pricetypes[$c->getValue()] = $c->getId();
		}

		// handle yes / no dictionary
    	$yesno = array(
    		'Yes' => 1,
    		'Ja' => 1,
    		'No' => 0,
    		'Nein' => 0
		);

    	// handle currency dictionary
    	$currencies = array();
    	foreach($this->db->currencies->fetchAll() as $k => $c)
    		$currencies[$c->getName()] = $c->getId();

		// interpret product attributes for import
		$real = array();
		foreach($data as $idx => $one) {
			// skip header
			if($idx == 0) continue;

			// add user id & attribute id
			$real[$idx]['main']['user_id'] = $user_id;
			$real[$idx]['main']['attribute_set_id'] = $setid['id'];

			// currency
			$crc = isset($currencies[trim($one[13])]) ? $currencies[trim($one[13])] : "";
			$real[$idx]['main']['primary_currency_id'] = $crc;

			// product title
			$real[$idx]['attributes']['product_title'] = utf8_encode(trim($one[0]));

			// product species
			$spc = array();
			if($one[1])
				foreach(explode(',', $one[1]) as $sp)
					$spc[] = $species[$this->utf8_accents_to_ascii(trim($sp))];
			$real[$idx]['attributes']['product_species'] = $spc;

			// product type
			$tpc = isset($types[$this->utf8_accents_to_ascii(trim($one[2]))]) ? $types[$this->utf8_accents_to_ascii(trim($one[2]))] : "";
			$real[$idx]['attributes']['product_type'] = $tpc;

			// product condition
			$cdc = isset($conditions[$this->utf8_accents_to_ascii(trim($one[3]))]) ? $conditions[$this->utf8_accents_to_ascii(trim($one[3]))] : "";
			$real[$idx]['attributes']['product_condition'] = $cdc;

			// product duration
			$drc = isset($durations[$this->utf8_accents_to_ascii(trim($one[4]))]) ? $durations[$this->utf8_accents_to_ascii(trim($one[4]))] : "";
			$real[$idx]['attributes']['product_duration'] = $drc;

			// product prices
			$real[$idx]['attributes']['product_price1'] = sprintf('%f', $one[5]);
			$real[$idx]['attributes']['product_price2'] = sprintf('%f', $one[6]);

			// product price type
			$ptc = isset($pricetypes[$this->utf8_accents_to_ascii(trim($one[7]))]) ? $pricetypes[$this->utf8_accents_to_ascii(trim($one[7]))] : "";
			$real[$idx]['attributes']['product_pricetype'] = $ptc;

			// product address
			$adc = isset($yesno[trim($one[8])]) ? $yesno[trim($one[8])] : "";
			$real[$idx]['attributes']['product_address'] = $adc;

			// product cellular
			$clc = isset($yesno[trim($one[9])]) ? $yesno[trim($one[9])] : "";
			$real[$idx]['attributes']['product_celular'] = $clc;

			// product shipping
			$spc = isset($yesno[trim($one[10])]) ? $yesno[trim($one[10])] : "";
			$real[$idx]['attributes']['product_shipping'] = $spc;

			// product shipping prices
			$real[$idx]['attributes']['product_shippingcost1'] = sprintf('%f', $one[11]);
			$real[$idx]['attributes']['product_shippingcost2'] = sprintf('%f', $one[12]);

			// product tags & description
			$real[$idx]['attributes']['product_tags'] = utf8_encode(trim($one[14]));
			$real[$idx]['attributes']['product_description'] = utf8_encode(trim($one[15]));

			// product pictures
			$pcp = array();
			if($one[16])
				foreach(explode(',', $one[16]) as $pc)
					$pcp[] = $dir . trim($pc);
			$real[$idx]['pictures'] = $pcp;
		}

		// start import
		foreach($real as $product) {
			// clone the objects
			$db = clone $this->db->products;
			$db2 = clone $this->db->fold;

			// split stuff up
			$main = $product['main'];
			$attributes = $product['attributes'];
			$pictures = $product['pictures'];

			// add gallery folder
			$gallery = $db2->getMapper()->getDbTable()->addFolder(array(
				'name' => 'product',
				'petId' => null,
				'ownerId' => $product['main']['user_id'],
				'parentId' => 0)
			);

			// save product
			$db->setOptions(array(
				'user_id' => $product['main']['user_id'],
				'attribute_set_id' => $product['main']['attribute_set_id'],
				'primary_currency_id' => $product['main']['primary_currency_id'],
				'folder_id' => $gallery->getId()
			))->save(true);

			// if "All" was selected as product_species
			if($attributes["product_species"]["0"] == "0")
				$attributes["product_species"] = $all_species;

			// save attributes
			$this->db->attr->getMapper()->getDbTable()->saveAttributeValues($attributes, $db->getId());

			// needed upfront
			$picture_dir = "..{$ds}data{$ds}userfiles{$ds}products{$ds}{$db->getId()}{$ds}";

			// create directories
			if(!file_exists(pathinfo($picture_dir, PATHINFO_DIRNAME))) {if(!mkdir(pathinfo($picture_dir, PATHINFO_DIRNAME))) {}}
			if(!file_exists($picture_dir)) {if(!mkdir($picture_dir)) {}}

			// prepare upload files
			$i = 0;
			$success = array();

			// copy pictures to their respective directories
			foreach($pictures as $file) {
				$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

				if(copy($file, $picture_dir . $new_filename))
					$success[pathinfo($file, PATHINFO_BASENAME)] = $picture_dir . $new_filename;
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
					'owner_id' => $product['main']['user_id'],
					'description' => $original
				);

				$file = clone $this->db->fles;
				$file->setOptions($opt);
				$file->save();
			}
		}

		// delete the folder from temp
		fclose($handle);
		$this->delTree($dir);

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("%s products have been successfully imported."), count($real));
		return $this->_redirect('admin/products/list-products');
	}

	private function utf8_accents_to_ascii($str) {
		$accents = array(
			'à' => 'a', 'ô' => 'o', 'ď' => 'd', 'ḟ' => 'f', 'ë' => 'e', 'š' => 's', 'ơ' => 'o',
			'ß' => 'ss', 'ă' => 'a', 'ř' => 'r', 'ț' => 't', 'ň' => 'n', 'ā' => 'a', 'ķ' => 'k',
			'ŝ' => 's', 'ỳ' => 'y', 'ņ' => 'n', 'ĺ' => 'l', 'ħ' => 'h', 'ṗ' => 'p', 'ó' => 'o',
			'ú' => 'u', 'ě' => 'e', 'é' => 'e', 'ç' => 'c', 'ẁ' => 'w', 'ċ' => 'c', 'õ' => 'o',
			'ṡ' => 's', 'ø' => 'o', 'ģ' => 'g', 'ŧ' => 't', 'ș' => 's', 'ė' => 'e', 'ĉ' => 'c',
			'ś' => 's', 'î' => 'i', 'ű' => 'u', 'ć' => 'c', 'ę' => 'e', 'ŵ' => 'w', 'ṫ' => 't',
			'ū' => 'u', 'č' => 'c', 'ö' => 'oe', 'è' => 'e', 'ŷ' => 'y', 'ą' => 'a', 'ł' => 'l',
			'ų' => 'u', 'ů' => 'u', 'ş' => 's', 'ğ' => 'g', 'ļ' => 'l', 'ƒ' => 'f', 'ž' => 'z',
			'ẃ' => 'w', 'ḃ' => 'b', 'å' => 'a', 'ì' => 'i', 'ï' => 'i', 'ḋ' => 'd', 'ť' => 't',
			'ŗ' => 'r', 'ä' => 'ae', 'í' => 'i', 'ŕ' => 'r', 'ê' => 'e', 'ü' => 'ue', 'ò' => 'o',
			'ē' => 'e', 'ñ' => 'n', 'ń' => 'n', 'ĥ' => 'h', 'ĝ' => 'g', 'đ' => 'd', 'ĵ' => 'j',
			'ÿ' => 'y', 'ũ' => 'u', 'ŭ' => 'u', 'ư' => 'u', 'ţ' => 't', 'ý' => 'y', 'ő' => 'o',
			'â' => 'a', 'ľ' => 'l', 'ẅ' => 'w', 'ż' => 'z', 'ī' => 'i', 'ã' => 'a', 'ġ' => 'g',
			'ṁ' => 'm', 'ō' => 'o', 'ĩ' => 'i', 'ù' => 'u', 'į' => 'i', 'ź' => 'z', 'á' => 'a',
			'û' => 'u', 'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u', 'ĕ' => 'e',

			'À' => 'A', 'Ô' => 'O', 'Ď' => 'D', 'Ḟ' => 'F', 'Ë' => 'E', 'Š' => 'S', 'Ơ' => 'O',
			'Ă' => 'A', 'Ř' => 'R', 'Ț' => 'T', 'Ň' => 'N', 'Ā' => 'A', 'Ķ' => 'K',
			'Ŝ' => 'S', 'Ỳ' => 'Y', 'Ņ' => 'N', 'Ĺ' => 'L', 'Ħ' => 'H', 'Ṗ' => 'P', 'Ó' => 'O',
			'Ú' => 'U', 'Ě' => 'E', 'É' => 'E', 'Ç' => 'C', 'Ẁ' => 'W', 'Ċ' => 'C', 'Õ' => 'O',
			'Ṡ' => 'S', 'Ø' => 'O', 'Ģ' => 'G', 'Ŧ' => 'T', 'Ș' => 'S', 'Ė' => 'E', 'Ĉ' => 'C',
			'Ś' => 'S', 'Î' => 'I', 'Ű' => 'U', 'Ć' => 'C', 'Ę' => 'E', 'Ŵ' => 'W', 'Ṫ' => 'T',
			'Ū' => 'U', 'Č' => 'C', 'Ö' => 'Oe', 'È' => 'E', 'Ŷ' => 'Y', 'Ą' => 'A', 'Ł' => 'L',
			'Ų' => 'U', 'Ů' => 'U', 'Ş' => 'S', 'Ğ' => 'G', 'Ļ' => 'L', 'Ƒ' => 'F', 'Ž' => 'Z',
			'Ẃ' => 'W', 'Ḃ' => 'B', 'Å' => 'A', 'Ì' => 'I', 'Ï' => 'I', 'Ḋ' => 'D', 'Ť' => 'T',
			'Ŗ' => 'R', 'Ä' => 'Ae', 'Í' => 'I', 'Ŕ' => 'R', 'Ê' => 'E', 'Ü' => 'Ue', 'Ò' => 'O',
			'Ē' => 'E', 'Ñ' => 'N', 'Ń' => 'N', 'Ĥ' => 'H', 'Ĝ' => 'G', 'Đ' => 'D', 'Ĵ' => 'J',
			'Ÿ' => 'Y', 'Ũ' => 'U', 'Ŭ' => 'U', 'Ư' => 'U', 'Ţ' => 'T', 'Ý' => 'Y', 'Ő' => 'O',
			'Â' => 'A', 'Ľ' => 'L', 'Ẅ' => 'W', 'Ż' => 'Z', 'Ī' => 'I', 'Ã' => 'A', 'Ġ' => 'G',
			'Ṁ' => 'M', 'Ō' => 'O', 'Ĩ' => 'I', 'Ù' => 'U', 'Į' => 'I', 'Ź' => 'Z', 'Á' => 'A',
			'Û' => 'U', 'Þ' => 'Th', 'Ð' => 'Dh', 'Æ' => 'Ae', 'Ĕ' => 'E',
		);

		return str_replace(array_keys($accents), array_values($accents),  $str);
	}

	public static function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
		}

		return rmdir($dir);
	}
}