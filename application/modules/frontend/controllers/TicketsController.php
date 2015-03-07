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
        'tickets-archives' => array(
            'sp'
        ), 
        'my-clients' => array(
            'sp'
        ), 
        'manage-non-petolio-members' => array(
            'sp'
        ), 
        'clients-archives' => array(
            'sp'
        ), 
        'add-non-petolio-member' => array(
            'sp'
        ), 
        'users' => array(
            'sp'
        ),
        'save-users-as-clients' => array(
            'sp'
        )
    );

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
    }

    public function addTicketAction()
    {
        $this->view->title = $this->translate->_("Add Ticket");
    }
    
    public function manageTicketsAction()
    {
    	$this->view->title = $this->translate->_("Manage Tickets");
    }

    public function ticketsArchivesAction()
    {
        $this->view->title = $this->translate->_("Tickets Archives");
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
            $filter->setRadius($this->request->getParam('radius'));
            
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
        if ( !($this->request->isPost() && $this->request->getPost('submit')) )
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

        return $this->_redirect('/tickets/my-tickets');
    }

    public function myClientsAction()
    {
        $this->view->title = $this->translate->_("My Clients");
    }

    public function manageNonPetolioMembersAction()
    {
        $this->view->title = $this->translate->_("Manage Non-Petolio Members");
    }

    public function clientsArchivesAction()
    {
        $this->view->title = $this->translate->_("Clients Archives");
    }

}