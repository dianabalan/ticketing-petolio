<?php

class Petolio_Controller_Helper_Login extends Zend_Controller_Action_Helper_Abstract {

	protected $translate = null;
	protected $messages = null;

    public function init() {
        $this->translate = Zend_Registry::get('Zend_Translate');
        $this->msg = new Zend_Session_Namespace("po_admin_messages");
    }

	public function preDispatch() {

		$view = $this->getActionController()->view;
		$auth = Zend_Auth::getInstance();
		$view->auth = $auth;
       	$view->logoutUrl = $view->url(array(
       							'controller'=>'index',
              					'action'=>'logout'), 'admin', true);
		if(!($auth->hasIdentity() && $auth->getIdentity()->is_admin == 1)) {
        	if($auth->hasIdentity()) {
				// unset the session_id
				$po_users = new Petolio_Model_PoUsers();
				$po_users->find($auth->getIdentity()->id);
				$po_users->setSessionId(null);
				$po_users->save(false);

				// clear instance
				$auth->clearIdentity();

				// forum logout
				$flux = new Petolio_Service_FluxBB();
				return $flux->logout($this->getFrontController()->getBaseUrl().'/admin');
        	}
	       	$form = new Petolio_Form_Login();

	        $request = $this->getActionController()->getRequest();
	        if($request->isPost() && $request->getPost('login')) {
	        	// login
	            if($form->isValid($request->getPost())) {
					$values = $form->getValues();

	            	$dbAdapter = Zend_Db_Table::getDefaultAdapter();
	        		$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

	        		$authAdapter->setTableName('po_users')
						->setIdentityColumn('email')
						->setCredentialColumn('password')
						->setCredentialTreatment('SHA1(?)');

					$authAdapter->setIdentity($values['email']);
					$authAdapter->setCredential($values['password']);

					$auth = Zend_Auth::getInstance();
					$result = $auth->authenticate($authAdapter);

					if($result->isValid()) {
						$user = $authAdapter->getResultRowObject();
						if($user->is_admin == 1) {
							$auth->getStorage()->write($user);
							$this->msg->messages[] = $this->translate->_("Successful login.");

							// set or update session_id
							$po_users = new Petolio_Model_PoUsers();
							$po_users->find($user->id);
							$po_users->setSessionId(Zend_Session::getId());
							$po_users->save();

							// forum login
							$flux = new Petolio_Service_FluxBB();
							$flux->login($user->id, $this->getFrontController()->getBaseUrl().'/admin');
						} else {
							$auth->clearIdentity();
							$this->msg->messages[] = $this->translate->_("Your account does not have admin priviledges.");
						}
					} else {
						$this->msg->messages[] = $this->translate->_("Incorrect email and/or password.");
					}

					$redirector = $this->_actionController->getHelper('Redirector');
	    			$redirector->gotoUrl('/admin');
	            }
	        }

	        $view->loginForm = $form;
        }
    }
}