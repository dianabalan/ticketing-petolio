<?php

class TicketsController extends Zend_Controller_Action
{

    private $auth = null;

    private $request = null;

    private $translate = null;

    private $cfg = null;

    private $msg = null;
    
    /**
     * Maps each user type to a role name.
     *
     * @author Stefan Baiu
     */
    private static $roles = array(
        1 => 'po', 
        2 => 'sp'
    );

    /**
     * Maps each action method to a list of roles.
     *
     * @author Stefan Baiu
     */
    private static $action_to_roles_map = array(
        'my-tickets' => array(
            'sp', 
            'po'
        ), 
        'add-ticket' => array(
            'sp'
        ), 
        'manage-tickets' => array(
            'sp'
        ), 
        'tickets-archive' => array(
            'sp'
        ), 
        'my-clients' => array(
            'sp'
        ), 
        'manage-non-petolio-members' => array(
            'sp'
        ), 
        'add-non-petolio-member' => array(
            'sp'
        ), 
        'edit-non-petolio-member' => array(
            'sp'
        ),
        'users' => array(
            'sp'
        ),
        'save-users-as-clients' => array(
            'sp'
        ),
        'edit-client' => array(
            'sp'
        ),
        'clients-archive' => array(
            'sp'
        ),
        'archive-client' => array(
            'sp'
        ),
        'restore-client' => array(
            'sp'
        )
    );
    
    /**
     * Redirects to another URL and displays a message to the user.
     * 
     * @author Stefan Baiu
     * 
     * @param string $message The message to be displayed after users is redirected.
     * 
     * @return void
     */
    private function _redirectWithMessage($url, $message)
    {
        if ( $message )
        {
            $this->msg->messages[] = $this->translate->_($message);
        }
        
        return $this->_redirect($url);
    }

    /**
     * Initializes a new instance of the TicketsController class.
     *
     * This method is called by the {@link Zend_Controller_Action::__construct()}.
     *
     * @author K Arpi
     *        
     * @return void
     */
    public function init()
    {
        // init
        $this->auth = Zend_Auth::getInstance();
        $this->request = $this->getRequest();
        $this->translate = Zend_Registry::get('Zend_Translate');
        $this->cfg = Zend_Registry::get("config");
        
        // session
        $this->msg = new Zend_Session_Namespace("po_messages");
        
        // view
        $this->view->request = $this->request;
    }

    /**
     * Checks whether the user is authenticated or not.
     *
     * If not already logged in, the user is redirected to the login page.
     *
     * @author K Arpi
     *        
     * @return void
     */
    private function verifyIdentity()
    {
        // not logged in
        if ( !isset($this->auth->getIdentity()->id) )
        {
            Petolio_Service_Util::saveRequest();
            $this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
            return $this->_helper->redirector('index', 'site');
        }
    }

    /**
     * Based on user's role, checks whether he is allowed to call the action method or not.
     *
     * If not, the user is redirected and a proper message is displayed.
     *
     * @author Stefan Baiu
     *        
     * @return void
     */
    private function verifyRole()
    {
        $action_name = strtolower($this->request->getActionName());
        
        // action not found
        if ( !isset(self::$action_to_roles_map[$action_name]) )
        {
            return;
        }
        
        $action_roles = self::$action_to_roles_map[$action_name];
        $role = self::$roles[$this->auth->getIdentity()->type];
        
        if ( !in_array($role, $action_roles, true) )
        {
            $this->msg->messages[] = $this->translate->_("Access forbidden");
            return $this->_helper->redirector('index', 'site');
        }
    }

    private function getCountries()
    {
        $countries = new Petolio_Model_PoCountriesMapper();
        $countries_map = array();
        foreach ($countries->fetchAll() as $country)
        {
            $countries_map[$country->getId()] = $country->getName();
        }
        
        return $countries_map;
    }

    /**
     * This method is called before the action method.
     *
     * @author K Arpi
     *        
     * @return void
     */
    public function preDispatch()
    {
        $this->verifyIdentity();
        $this->verifyRole();
    }

    /**
     * This method is called after the action method.
     *
     * @author K Arpi
     *        
     * @return void
     */
    public function postDispatch()
    {
    }

    public function myTicketsAction()
    {
        $this->view->title = $this->translate->_("My Tickets");
        
        //get user id
        $user_id = $this->auth->getIdentity()->id;
        
        // get page
        $page = $this->request->getParam("page");
        $page = $page ? $page : 0;
        //TODO: add to cfg: tickets.pagination.itemsperpage = 10;
        $items_per_page = $this->cfg["products"]["pagination"]["itemsperpage"];
        
        $manager = new Petolio_Model_Ticket_TicketManager();
        
        $this->view->list = $manager->getTickets($user_id, $items_per_page, $page);
    }

    public function addTicketAction()
    {
        $this->view->title = $this->translate->_("Add Ticket");

        //step3, process data
        if($this->request->isPost())
        {
        	$this->addTicketStep3();
        	return ;      
        }
        
        //step2, render add ticket form
        if($this->request->getParam('product') || $this->request->getParam('service'))
        {
        	$this->view->form = new Petolio_Form_TicketAdd();
        	return;
        }
        
        //step1, select product or service to make ticket with
        $this->addTicketStep1();
    }

    /**
     * Sets previously selected combo-box value, and shows a table according to selection.
     * @author K Arpi
     *        
     * @return void
     */
    private function addTicketStep1()
    {
    	// get page
    	$page = $this->request->getParam("page");
    	$page = $page ? $page : 0;
    	
    	//get selection, and add to view
    	$this->view->category = $this->request->getParam("sel");
    	//add form
    	$this->view->form = new Petolio_Form_TicketAddSelection($this->view->category);
    	//add list of items to view, based on selection
    	switch ($this->view->category)
    	{
    		case "prod":
    			$products = new Petolio_Model_PoProducts();
    			// set filter
    			$filter = "a.archived = 0 AND a.user_id = {$this->auth->getIdentity()->id}";
    			// get products
    			$paginator = $products->getProducts('paginator', $filter, "id DESC", false, true);
    			$paginator->setItemCountPerPage($this->cfg["products"]["pagination"]["itemsperpage"]);
    			$paginator->setCurrentPageNumber($page);
    	
    			// output products
    			$this->view->list = $products->formatProducts($paginator);
    			break;
    		case "serv":
    			$services = new Petolio_Model_PoServices();
    			// get services
    			$paginator = $services->getServices('paginator', "a.user_id = {$this->auth->getIdentity()->id}", null, false, true);
    			$paginator->setItemCountPerPage($this->cfg["services"]["pagination"]["itemsperpage"]);
    			$paginator->setCurrentPageNumber($page);
    	
    			// output all services
    			$this->view->list = $services->formatServices($paginator);
    			break;
    	}
    }
    
    /**
     * Process data from form submition.
     * @author K Arpi
     *
     * @return void
     */
    private function addTicketStep3()
    {
    	//validate form
    	$form = new Petolio_Form_TicketAdd();
    	if(!$form->isValid($_POST))
    	{
    		$this->view->form = $form;
    		return ;
    	}
    	//add data to db
    	else 
    	{    		
    		$ticket = new Petolio_Model_Ticket_Ticket();
    		if($id=$this->request->getParam('product'))
	    		$ticket->setScope('product');    	
    		else if($id=$this->request->getParam('service'))
    			$ticket->setScope('service');
	    	$ticket->setItemId($id);
	    	
	    	$post = $this->request->getPost();
	    	$ticket->setTicketDate($post['ticketDate']);
	    	$ticket->setDescription($post['description']);
	    	$ticket->setFlagReminder($post['reminder']);
	    	$ticket->setUserId($this->auth->getIdentity()->id);
	    	
	    	$manager = new Petolio_Model_Ticket_TicketManager();
	    	$manager->save($ticket);
    		
	    	return $this->_redirectWithMessage('/tickets/my-clients', 'You succesfully added ticket.');
    	}
    }
    
    public function editTicketAction()
    {
    	$this->view->title = $this->translate->_("Edit Ticket");
    	$form = new Petolio_Form_TicketAdd();    	

    	//prevent page from loading if ticket param is not set
    	if(!$ticket_id = $this->request->getParam("ticket"))
    		return $this->_redirect('/tickets/my-tickets');
    	
    	//fetch data and redirect if ticket with ticket_id does not exist
    	$manager = new Petolio_Model_Ticket_TicketManager();
    	if(!$ticket = $manager->getTicket($this->auth->getIdentity()->id, $ticket_id))
    		return $this->_redirectWithMessage('/tickets/my-tickets', 'No such ticket');
    	
    	if(!$this->request->isPost())
    	{    	
	    	//format date
	    	$date = new Zend_Date($ticket->getTicketDate());
	    	
	    	//show on form
	    	$form->setDefaults(array(
	    			'description' => $ticket->getDescription(),
	    			'ticketDate' => $date->get("YYYY-MM-dd"),
	    			'reminder' => $ticket->getFlagReminder()
	    	));
    	}
    	else if($form->isValid($_POST))
    	{
    		$post = $this->request->getPost();
    		$ticket->setDescription($post['description']);
    		$ticket->setTicketDate($post['ticketDate']);
    		$ticket->setFlagReminder($post['reminder']);
    		
	    	$manager->save($ticket);
    			
    		return $this->_redirectWithMessage('/tickets/my-tickets', 'Ticket edited succesfully.');
    	}
    	$this->view->form = $form;
    }
    
    public function archiveTicketAction()
    {
    	//prevent page from loading if ticket param is not set
    	if(!$ticket_id = $this->request->getParam("ticket"))
    		return $this->_redirect('/tickets/my-tickets');
    	
    	//fetch data and redirect if ticket with ticket_id does not exist
    	$manager = new Petolio_Model_Ticket_TicketManager();
    	if(!$ticket = $manager->getTicket($this->auth->getIdentity()->id, $ticket_id))
    		return $this->_redirectWithMessage('/tickets/my-tickets', 'No such ticket');
    	
    	//archive ticket
    	$ticket->setArchive(0);
    	$manager->save($ticket);
    	
    	return $this->_redirectWithMessage('/tickets/my-tickets', 'Ticket archived succesfully.');
    } 
    
    public function manageTicketsAction()
    {
    	$this->view->title = $this->translate->_("Manage Tickets");
    }

    public function ticketsArchiveAction()
    {
        $this->view->title = $this->translate->_("Tickets Archives");
        
        //get user id
        $user_id = $this->auth->getIdentity()->id;
        
        // get page
        $page = $this->request->getParam("page");
        $page = $page ? $page : 0;
        //TODO: add to cfg: tickets.pagination.itemsperpage = 10;
        $items_per_page = $this->cfg["products"]["pagination"]["itemsperpage"];
        
        $manager = new Petolio_Model_Ticket_TicketManager();
        
        $this->view->list = $manager->getArchivedTickets($user_id, $items_per_page, $page);
    }

 	public function restoreTicketAction()
    {
    	//prevent page from loading if ticket param is not set
    	if(!$ticket_id = $this->request->getParam("ticket"))
    		return $this->_redirect('/tickets/my-tickets');
    	
    	//fetch data and redirect if ticket with ticket_id does not exist
    	$manager = new Petolio_Model_Ticket_TicketManager();
    	if(!$ticket = $manager->getTicket($this->auth->getIdentity()->id, $ticket_id))
    		return $this->_redirectWithMessage('/tickets/my-tickets', 'No such ticket');
    	
    	//restore ticket
    	$ticket->setArchive(1);
    	$manager->save($ticket);
    	
    	return $this->_redirectWithMessage('/tickets/my-tickets', 'Ticket restored succesfully.');
    } 
    
    public function usersAction()
    {
        $this->view->title = $this->translate->_("Users");
        $this->view->country_list = $this->getCountries();
        
        $page = $this->request->getParam('page');
        $page = isset($page) ? intval($page) : 0;
        $items_per_page = (int) $this->cfg["users"]["pagination"]["itemsperpage"];
        
        $manager = new Petolio_Model_Ticket_UsersManager();
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $cache = new Zend_Session_Namespace("cache");
        
        if ( $this->_request->isXmlHttpRequest() )
        {
            $filter = $cache->filter;
            $paginator = $manager->getNonClients($sp_id, $page, $items_per_page, $filter);
            
            $this->view->paginator = $paginator;
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer('partials/list-with-pagination');
        }
        else
        {
            unset($cache->filter);
            
            $filter = new Petolio_Model_Ticket_SearchUserFilter();
            $filter->setKeyword($this->request->getParam('keyword'));
            $filter->setCountry($this->request->getParam('country'));
            $filter->setZipcode($this->request->getParam('zipcode'));
            $filter->setAddress($this->request->getParam('address'));
            $filter->setLocation($this->request->getParam('location'));
            
            if ( !$filter->hasValues() )
            {
                $filter = null;
            }
            
            $paginator = $manager->getNonClients($sp_id, $page, $items_per_page, $filter);
            
            $cache->filter = $filter;
            
            $this->view->paginator = $paginator;
        }
    }
    
    public function saveUsersAsClientsAction()
    {
        if ( !($this->request->isPost()) )
        {
            return $this->_helper->redirector('index', 'site');
        }
        
        $post_data = $this->request->getPost();
        $user_ids = $post_data['client_id'];
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $manager = new Petolio_Model_Ticket_ClientsManager();
        $count = $manager->addClients($user_ids, $sp_id);
        
        $messages = new Zend_Session_Namespace("po_messages");
        
        $text = $this->translate->_('clients were added successfully');
        $messages->messages[] = $count . '/' . count($user_ids) . ' ' . $text;

        return $this->_redirect('/tickets/my-clients');
    }

    public function myClientsAction()
    {
        $this->view->title = $this->translate->_("My Clients");
        $this->view->country_list = $this->getCountries();
        
        $sp_id = (int) $this->auth->getIdentity()->id;
        $manager = new Petolio_Model_Ticket_UsersManager();
        $users = $manager->getClients($sp_id);
        
        $view_users = array();
        foreach ($users as $user)
        {
            $view_users[] = array(
                'id' => $user->getId(),
                'name' => $user->getName(),
                'avatar' => $user->getAvatar(),
                'email' => $user->getEmail(),
                'address' => $user->getAddress(),
                'location' => $user->getLocation(),
                'country_id' => $user->getCountryId(),
                'zipcode' => $user->getZipcode(),
                'type' => $user->getType()
            );
        }
        
        $this->view->users = $view_users;
    }
    
    public function editClientAction()
    {
        $this->view->title = $this->translate->_("Edit Client");
        
        $form = new Petolio_Form_Ticket_Client();
        $this->view->form = $form;
        
        $user_id = $this->request->getParam('id', 0);
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $manager = new Petolio_Model_Ticket_ClientsManager();
        $client = $manager->getClient($user_id, $sp_id);
        
        if ( null === $client )
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'No such client');
        }
        
        if ( $this->request->isGet() )
        {
            $form->setDefaults(array(
                'id' => $client->getClientId(),
                'billing_interval' => $client->getBillingInterval(),
                'payment' => $client->getPayment(),
                'remarks' => $client->getRemarks()
            ));
            
            return false;
        }
        
        if( !($this->request->isPost()) )
        {
            return false;
        }
        
        if( !$form->isValid($this->request->getPost()) )
        {
            return false;
        }
        
        $data = $form->getValues();
        
        $client->setBillingInterval($data['billing_interval']);
        $client->setPayment($data['payment']);
        $client->setRemarks($data['remarks']);
        
        try 
        {
            $manager->save($client);
        }
        catch (Exception $e)
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/my-clients', 'Changes were applied');
    }
    
    public function archiveClientAction()
    {
        if( !($this->request->isPost()) )
        {
            return $this->_helper->redirector('index', 'site');
        }
        
        $user_id = $this->request->getParam('user_id', 0);
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $manager = new Petolio_Model_Ticket_ClientsManager();
        $client = $manager->getClient($user_id, $sp_id);
        
        if ( null === $client )
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'No such client');
        }
        
        $client->setIsActive(false);
        
        try 
        {
            $manager->save($client);
        }
        catch (Exception $e)
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/my-clients', 'The client was archived');
    }
    
    public function clientsArchiveAction()
    {
        $this->view->title = $this->translate->_("Clients Archive");
        
        $sp_id = (int) $this->auth->getIdentity()->id;
        $manager = new Petolio_Model_Ticket_UsersManager();
        $users = $manager->getInactiveClients($sp_id);
        
        $view_users = array();
        foreach ($users as $user)
        {
            $view_users[] = array(
                'id' => $user->getId(),
                'name' => $user->getName(),
                'avatar' => $user->getAvatar(),
                'email' => $user->getEmail(),
                'date_modified' => $user->getDateModified()
            );
        }
        
        $this->view->users = $view_users;
    }
    
    public function restoreClientAction()
    {
        if( !($this->request->isPost()) )
        {
            return $this->_helper->redirector('index', 'site');
        }
        
        $user_id = $this->request->getParam('user_id', 0);
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $manager = new Petolio_Model_Ticket_ClientsManager();
        $client = $manager->getClient($user_id, $sp_id);
        
        if ( null === $client )
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'No such client');
        }
        
        $client->setIsActive(true);
        
        try
        {
            $manager->save($client);
        }
        catch (Exception $e)
        {
            return $this->_redirectWithMessage('/tickets/my-clients', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/my-clients', 'The client was restored');
    }
    
    public function manageNonPetolioMembersAction()
    {
        $this->view->title = $this->translate->_("Manage Non-Petolio Members");
        
        $sp_id = (int) $this->auth->getIdentity()->id;
        $manager = new Petolio_Model_Ticket_UsersManager();
        
        $nonPetolioUsers = $manager->getNonPetolioMembers($sp_id);
        
        $view_users = array();
        foreach ($nonPetolioUsers as $user)
        {
            $view_users[] = array(
                'id' => $user->getId(),
                'name' => $user->getName(),
                'avatar' => $user->getAvatar(),
                'email' => $user->getEmail(),
                'address' => $user->getAddress(),
                'location' => $user->getLocation(),
                'zipcode' => $user->getZipcode()
            );
        }
        
        $this->view->users = $view_users;
    }

    public function addNonPetolioMemberAction()
    {
        $this->view->title = $this->translate->_("Add Non-Petolio Member");
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $form = new Petolio_Form_Ticket_NonPetolioMember($sp_id);
        
        $this->view->form = $form;
        
        if ( !($this->request->isPost()) )
        {
            return false;
        }
        
        if( !$form->isValid($this->request->getPost()) )
        {
            return false;
        }
        
        $manager = new Petolio_Model_Ticket_UsersManager();
        $nonPetolioUser = new Petolio_Model_Ticket_NonPetolioMember();
        
        $form_data = $form->getValues();
        $nonPetolioUser->setName($form_data['name']);
        $nonPetolioUser->setFirstname($form_data['first_name']);
        $nonPetolioUser->setLastname($form_data['last_name']);
        $nonPetolioUser->setEmail($form_data['email']);
        $nonPetolioUser->setStreet($form_data['street']);
        $nonPetolioUser->setAddress($form_data['address']);
        $nonPetolioUser->setZipcode($form_data['zipcode']);
        $nonPetolioUser->setLocation($form_data['location']);
        $nonPetolioUser->setCountryId($form_data['country_id']);
        $nonPetolioUser->setPhone($form_data['phone']);
        $nonPetolioUser->setPrivatePhone($form_data['private_phone']);
        $nonPetolioUser->setGender($form_data['gender']);
        $nonPetolioUser->setRemarks($form_data['remarks']);
        
        try
        {
            $manager->registerNonPetolioMember($nonPetolioUser, $sp_id);
        }
        catch (Exception $e)
        {
            return $this->_redirectWithMessage('/tickets/manage-non-petolio-members', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/manage-non-petolio-members', 'The non petolio user was added');
    }
    
    public function editNonPetolioMemberAction()
    {
        $this->view->title = $this->translate->_("Edit Non-Petolio Member");
        
        $sp_id = (int) $this->auth->getIdentity()->id;
        $user_id = (int) $this->request->getParam('id', 0);
        
        $manager = new Petolio_Model_Ticket_UsersManager();
        $nonPetolioUser = $manager->getNonPetolioMember($user_id, $sp_id);
        
        if ( null === $nonPetolioUser )
        {
            return $this->_redirectWithMessage('/tickets/manage-non-petolio-members', 'No such user');
        }
        
        $form = new Petolio_Form_Ticket_NonPetolioMember($sp_id, $nonPetolioUser);
        $this->view->form = $form;
        
        if ( $this->request->isGet() )
        {
            $form->setDefaults(array(
                'id' => $user_id,
                'name' => $nonPetolioUser->getName(),
                'first_name' => $nonPetolioUser->getFirstname(),
                'last_name' => $nonPetolioUser->getLastname(),
                'email' => $nonPetolioUser->getEmail(),
                'street' => $nonPetolioUser->getStreet(),
                'address' => $nonPetolioUser->getAddress(),
                'zipcode' => $nonPetolioUser->getZipcode(),
                'location' => $nonPetolioUser->getLocation(),
                'country_id' => $nonPetolioUser->getCountryId(),
                'phone' => $nonPetolioUser->getPhone(),
                'private_phone' => $nonPetolioUser->getPrivatePhone(),
                'gender' => $nonPetolioUser->getGender(),
                'remarks' => $nonPetolioUser->getRemarks()
            ));
        }
        
        if( !($this->request->isPost()) )
        {
            return false;
        }
        
        if( !$form->isValid($this->request->getPost()) )
        {
            return false;
        }
        
        $form_data = $form->getValues();
        $nonPetolioUser->setName($form_data['name']);
        $nonPetolioUser->setFirstname($form_data['first_name']);
        $nonPetolioUser->setLastname($form_data['last_name']);
        $nonPetolioUser->setEmail($form_data['email']);
        $nonPetolioUser->setStreet($form_data['street']);
        $nonPetolioUser->setAddress($form_data['address']);
        $nonPetolioUser->setZipcode($form_data['zipcode']);
        $nonPetolioUser->setLocation($form_data['location']);
        $nonPetolioUser->setCountryId($form_data['country_id']);
        $nonPetolioUser->setPhone($form_data['phone']);
        $nonPetolioUser->setPrivatePhone($form_data['private_phone']);
        $nonPetolioUser->setGender($form_data['gender']);
        $nonPetolioUser->setRemarks($form_data['remarks']);
        
        try
        {
            $manager->updateNonPetolioMember($nonPetolioUser, $sp_id);
        }
        catch (Exception $e)
        {
            return $this->_redirectWithMessage('/tickets/manage-non-petolio-members', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/manage-non-petolio-members', 'Changes were applied');
    }
}