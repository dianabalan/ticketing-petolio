<?php

class ContactController extends Zend_Controller_Action
{
    private $translate = null;
    private $auth = null;
    private $msg = null;
    private $email = null;
    private $request = null;
    private $cfg = null;

    private $test = null;

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
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->email = new Petolio_Service_Mail();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		$this->test = new Petolio_Model_PoTestimonials();
    }

    public function indexAction()
    {
		// init form
		$form = new Petolio_Form_Contact();
		$this->view->form = $form;

		// populate if logged in
		if ($this->auth->hasIdentity())
			$form->populate(array(
				'name' => $this->auth->getIdentity()->name,
				'email' => $this->auth->getIdentity()->email,
			));

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save the message
		$this->test->setUserId($this->auth->hasIdentity() ? $this->auth->getIdentity()->id : 0);
		$this->test->setName($data['name']);
		$this->test->setEmail($data['email']);
		$this->test->setSubject($data['subject']);
		$this->test->setMessage($data['message']);
		$this->test->save(true, true);

		// send the email
		$this->email->setRecipient($this->cfg['email']['to']);
		$this->email->setReplyTo($data['email'], $data['name']);
		$this->email->setTemplate('users/contact');
		$this->email->name = $data['name'];
		$this->email->base_url = PO_BASE_URL;
		$this->email->logged_in = $this->auth->hasIdentity() ? true : false;
		$this->email->email = $data['email'];
		$this->email->subject = $data['subject'];
		$this->email->message = $data['message'];
		$this->email->send();

		// redirect with success message
		$this->msg->messages[] = $this->translate->_("Your message has been successfully sent.");
		return $this->_redirect('contact');
    }

    public function termsAction()
    {
        // action body
    }

    public function dataAction()
    {
        // action body
    }

    public function faqAction()
    {
        // action body
    }

    public function advertisingAction()
    {
    	// action body
    }

    public function cookiesAction()
    {
    	// action body
    }
}