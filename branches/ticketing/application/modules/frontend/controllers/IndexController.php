<?php

class IndexController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $request = null;
	private $config = null;

	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");

		// load models
		$this->db = new stdClass();

		// redirect to site
		return $this->_redirect('site');
	}

	/**
	 * Index
	 */
	public function indexAction() {
	}
}