<?php

class IndexController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;

    public function init()
    {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_editor_messages");
    }

    public function indexAction()
    {
        // action body
    }

    /**
     * logout action
     */
    public function logoutAction()
    {
		// not logged in ?
		if(!$this->auth->hasIdentity())
			return $this->_redirect('/editor');

		// unset the session_id
		$po_users = new Petolio_Model_PoUsers();
		$po_users->find($this->auth->getIdentity()->id);
		$po_users->setSessionId(null);
		$po_users->save(false);

		// clear instance
		Zend_Auth::getInstance()->clearIdentity();

		// msg
		$this->msg->messages[] = $this->translate->_("You have succesfully logged out.");

		// forum logout
		$flux = new Petolio_Service_FluxBB();
		$flux->logout($this->getFrontController()->getBaseUrl().'/editor');

		return $this->_redirect('/editor');
    }
}