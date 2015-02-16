<?php

class MapController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $auth = null;
	private $request = null;

	private $db = null;
	private $europe = array(48.690832999999998, 9.140554999999949);

	public function init() {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();

		$this->db = new stdClass();
		$this->db->services = new Petolio_Model_PoServices();
		$this->db->products = new Petolio_Model_PoProducts();

		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}
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

	/**
	 * Index action (just a simple map)
	 */
	public function indexAction() {
		// send europe as default
		$this->view->coords = $this->europe;
	}

	/**
	 * Choose action (redirect where needed)
	 */
	public function chooseAction() {
		// servuce provicer?
		if($this->auth->getIdentity()->type == 2)
			return $this->_helper->redirector('services', 'map');
		else
			return $this->_helper->redirector('products', 'map');
	}

	/**
	 * Add - Services
	 */
	public function servicesAction() {
		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("You must have a service provider account.");
			return $this->_redirect('site');
		}

		// do we have any services ?
		$services = $this->db->services->getServices('array', "a.user_id = {$this->auth->getIdentity()->id}");
		if(!$services) {
			$this->msg->messages[] = $this->translate->_("You have must create a service before you add it on our map. You can add your service here.");
			return $this->_helper->redirector('add', 'services');
		}

		// send services to template
		$this->view->services = array();
		foreach($services as $service)
			$this->view->services[$service['id']] = $service['name'];

		// send europe as default
		$this->view->coords = $this->europe;
	}

	/**
	 * Add - Products
	 */
	public function productsAction() {
		// do we have any products ?
		$products = $this->db->products->getProducts('array', "a.user_id = {$this->auth->getIdentity()->id}");
		if(!$products) {
			$this->msg->messages[] = $this->translate->_("You have must create a product before you add it on our map. You can add your product here.");
			return $this->_helper->redirector('add', 'products');
		}

		// send products to template
		$this->view->products = array();
		foreach($products as $product)
			$this->view->products[$product['id']] = $product['title'];

		// send europe as default
		$this->view->coords = $this->europe;
	}

	/**
	 * Return gps location of a service
	 */
	public function selectAction() {
		// vars
		$id = (int)$this->getRequest()->getParam('id');
		$type = (string)$this->getRequest()->getParam('type');

		// select type
		switch($type) {
			case 'product':
				// find product by id
				$product = $this->db->products->find($id);
				if(!$product)
					return Petolio_Service_Util::json(array('success' => false));

				Petolio_Service_Util::json(array(
					'success' => true,
					'product' => array(
						$product->getGpsLatitude(),
						$product->getGpsLongitude()
					)
				));
			break;

			case 'service':
				// find service by id
				$service = $this->db->services->find($id);
				if(!$service)
					return Petolio_Service_Util::json(array('success' => false));

				Petolio_Service_Util::json(array(
					'success' => true,
					'service' => array(
						$service->getGpsLatitude(),
						$service->getGpsLongitude()
					)
				));
			break;

			default:
				return Petolio_Service_Util::json(array('success' => false));
			break;
		}
	}

	/**
	 * Save gps location for (services / products)
	 */
	public function saveAction() {
		// vars
		$id = (int)$this->getRequest()->getParam('id');
		$type = (string)$this->getRequest()->getParam('type');
		$lat = (float)$this->getRequest()->getParam('lat');
		$long = (float)$this->getRequest()->getParam('long');

		// select type
		switch($type) {
			case 'product':
				// find product by id
				$product = $this->db->products->find($id);
				if(!$product)
					return Petolio_Service_Util::json(array('success' => false));

				// save location
				$product->setGpsLatitude($lat == 0 ? new Zend_Db_Expr('NULL') : $lat);
				$product->setGpsLongitude($long == 0 ? new Zend_Db_Expr('NULL') : $long);
				$product->save();

				Petolio_Service_Util::json(array('success' => true));
			break;

			case 'service':
				// find service by id
				$service = $this->db->services->find($id);
				if(!$service)
					return Petolio_Service_Util::json(array('success' => false));

				// save location
				$service->setGpsLatitude($lat == 0 ? new Zend_Db_Expr('NULL') : $lat);
				$service->setGpsLongitude($long == 0 ? new Zend_Db_Expr('NULL') : $long);
				$service->save();

				Petolio_Service_Util::json(array('success' => true));
			break;

			default:
				return Petolio_Service_Util::json(array('success' => false));
			break;
		}
	}
}