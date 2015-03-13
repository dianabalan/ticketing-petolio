<?php

class Petolio_Controller_Helper_Login extends Zend_Controller_Action_Helper_Abstract {

	protected $translate = null;

    public function init() {
        $this->translate = Zend_Registry::get('Zend_Translate');
    }

	public function preDispatch() {

		$view = $this->getActionController()->view;
		$auth = Zend_Auth::getInstance();
        if ( $auth->hasIdentity() ) {
        	$view->identity = $auth->getIdentity();
        	$view->hasIdentity = $auth->hasIdentity();
        	$view->logoutUrl = $view->url(array(
        							'controller'=>'accounts',
                					'action'=>'logout'), null, true);
        } else {
	       	$form = new Petolio_Form_Login();
	        $view->loginForm = $form;

	       	$messages = new Zend_Session_Namespace("po_messages");
			$errors = new Zend_Session_Namespace("po_errors");
	        $request = $this->getActionController()->getRequest();

			// check for post input
	        if (!($request->isPost() && $request->getPost('login')))
				return false;

        	// check if we have valid info
            if(!$form->isValid($request->getPost()))
				return false;

			// bla bla bla
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
			$redirect_url = '/site/index/';
			$prependbase = true;

			if ($result->isValid()) {
				$user = $authAdapter->getResultRowObject();
				if ($user->active == '0') {
					$auth->clearIdentity();
					$errors->messages[] = $this->translate->_("Your account is inactive.");
				} else if ($user->type == '3') {
					$auth->clearIdentity();
					$errors->messages[] = $this->translate->_("This email is registered only as a non-petolio member");					
				} else if ($user->is_banned == '1') {
					$auth->clearIdentity();
					$errors->messages[] = $this->translate->_("Your account was banned. Please <a href='/contact'>contact us</a> if you think there was a mistake.");
				} else {
					$auth->getStorage()->write($user);
					$messages->messages[] = $this->translate->_("You have succesfully logged in.");

					// set or update session_id
					$po_users = new Petolio_Model_PoUsers();
					$po_users->find($user->id);
					$po_users->setSessionId(Zend_Session::getId());
					$po_users->setLanguage($this->translate->getLocale());
					$po_users->save();
					
					if ( (is_null($po_users->getCover()) || strlen($po_users->getCover()) < 1
							|| strcasecmp($po_users->getCover(), "null") == 0)
							&& $po_users->getType() == 2 ) { // redirect new service providers to the welcome page
						$redirect_url = '/accounts/welcome/';
						$prependbase = true;
					}
					
					// forum login
					$flux = new Petolio_Service_FluxBB();
					$flux->login($user->id, $redirect_url);

					$session = new Zend_Session_Namespace('Petolio_Redirect');
					if ( isset($session->redirect) && strlen($session->redirect) > 0 ) {
						$redirect_url = $session->redirect;
						$prependbase = false;
						unset($session->redirect);
					}
				}
			} else $errors->messages[] = $this->translate->_("Incorrect email and/or password.");

			// redirect bla bla
			$redirector = $this->_actionController->getHelper('Redirector');
			$redirector->gotoUrl($redirect_url, array('prependBase' => $prependbase));
        }
    }
}