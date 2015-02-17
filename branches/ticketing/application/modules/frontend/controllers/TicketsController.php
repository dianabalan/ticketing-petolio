<?php
class TicketsController extends Zend_Controller_Action
{
	private $auth = null;
	private $request = null;
	private $translate = null;
	private $cfg = null;

	private $msg = null;
	
	public function init() {
		// init
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->cfg = Zend_Registry::get("config");
	
		// session
		$this->msg = new Zend_Session_Namespace("po_messages");
	
		// db
	
		// view
		$this->view->request = $this->request;
	}
	
	public function preDispatch() {
		$this->verifyUser();
		$this->verifyRole();		
	}
	
	public function postDispatch() {
	}
	
	/* Logged in redirector
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
	 * @author Stefan Baiu
	 */	
	private function verifyRole()
	{
		$action_name = $this->request->getActionName();
		$usrType = $this->auth->getIdentity()->type;
	
		if ( $action_name != "my-tickets" && $usrType == 1 )
		{
			Petolio_Service_Util::saveRequest();
            $this->msg->messages[] = $this->translate->_("Please log in or sign up as 'Service Provider' to access this page");
            return $this->_helper->redirector('index', 'site');
		}
	}

	public function myTicketsAction() {
		$this->view->title = $this->translate->_("My Tickets");
	}
	
	public function addTicketAction() {
		$this->view->title = $this->translate->_("Add Ticket");
	}
	
	public function ticketsArchivesAction() {
		$this->view->title = $this->translate->_("Tickets Archives");
	}
	
	public function myClientsAction() {
		$this->view->title = $this->translate->_("My Clients");
	}
	
	public function addClientAction() {
		$this->view->title = $this->translate->_("Add Client");
	}
	
	public function manageNonPetolioMembersAction() {
		$this->view->title = $this->translate->_("Manage Non-Petolio Members");
	}
	
	public function clientsArchivesAction() {
		$this->view->title = $this->translate->_("Clients Archives");
	}
}