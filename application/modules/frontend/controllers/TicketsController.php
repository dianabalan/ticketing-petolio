<?php
class TicketsController extends Zend_Controller_Action
{
	private $auth = null;
	private $request = null;
	private $translate = null;
	private $cfg = null;

	private $msg = null;
	
	private static $roles = array(
			1 => 'po',
			2 => 'sp'
	);
	
	private static $action_to_roles_map = array(
			'my-tickets' => array(
					'sp',
					'po'
			),
			'add-ticket' => array(
					'sp'
			),
			'tickets-archives' => array(
					'sp'
			),
			'my-clients' => array(
					'sp'
			),
			'add-client' => array(
					'sp'
			),
			'manage-non-petolio-members' => array(
					'sp'
			),
			'clients-archives' => array(
					'sp'
			),
	);
	
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
        $action_roles = self::$action_to_roles_map[$action_name];
        $role = self::$roles[$this->auth->getIdentity()->type];
        
        if ( !in_array($role, $action_roles, true) )
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