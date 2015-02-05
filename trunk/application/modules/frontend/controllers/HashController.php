<?php

class HashController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $redirect = null;
	private $request = null;

	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->redirect = new Zend_Session_Namespace('Petolio_Redirect');
		$this->request = $this->getRequest();

		// load models
		$this->db = new stdClass();
		$this->db->users = new Petolio_Model_PoUsers();
	}

	/**
	 * Index
	 */
	public function indexAction() {
		// vars
		$h = @(string)$this->request->getParam('h');
		if(empty($h))
			return die('bye');

		// create the redirect location
		$this->redirect->redirect = str_replace("/hash/index/h/{$h}", "", $this->request->getRequestUri());

		// already logged in? redirect here
		if($this->auth->hasIdentity())
			return $this->_helper->redirector->gotoUrl($this->redirect->redirect);

		// find based on hash
		$result = reset($this->db->users->getMapper()->fetchList("sha1(concat(id, email)) = '{$h}'"));
		if(!$result)
			return die('bye');

		// set user object
		$user = (object)$result->toArray();

		// write storage
		$this->auth->getStorage()->write($user);
		$this->msg->messages[] = $this->translate->_("You have succesfully logged in.");

		// set or update session_id
		$result->setSessionId(Zend_Session::getId())->save();

		// forum login
		$flux = new Petolio_Service_FluxBB();
		$flux->login($user->id);
	}
}