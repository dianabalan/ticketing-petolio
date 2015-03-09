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
    }
    
    public function manageNonPetolioMembersAction()
    {
        $this->view->title = $this->translate->_("Manage Non-Petolio Members");
    }

    public function addNonPetolioMemberAction()
    {
        $this->view->title = $this->translate->_("Add Non-Petolio Members");
        
        $form = new Petolio_Form_Ticket_NonPetolioMember();
        $this->view->form = $form;
        
        if( !($this->request->isPost()) )
        {
            return false;
        }
        
        if( !$form->isValid($this->request->getPost()) )
        {
            return false;
        }
        
        $manager = new Petolio_Model_Ticket_UsersManager();
        $nonPetolioUser = new Petolio_Model_Ticket_NonPetolioMember();
        $sp_id = (int) $this->auth->getIdentity()->id;
        
        $form_data = $form->getValues();
        $nonPetolioUser->setName($form_data['name']);
        $nonPetolioUser->setFirstname($form_data['first_name']);
        $nonPetolioUser->setLastname($form_data['last_name']);
        $nonPetolioUser->setEmail($form_data['email']);
        $nonPetolioUser->setStreet($form_data['street']);
        $nonPetolioUser->setAddress($form_data['address']);
        $nonPetolioUser->setZipcode($form_data['zipcode']);
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
            return $this->_redirectWithMessage('/tickets/my-clients', 'Something went wrong');
        }
        
        return $this->_redirectWithMessage('/tickets/my-clients', 'The non petolio user was added');
    }
    
    public function editNonPetolioMemberAction()
    {
        $this->view->title = $this->translate->_("Edit Non-Petolio Members");
        
        $form = new Petolio_Form_Ticket_NonPetolioMember();
        $this->view->form = $form;
        
        if( !($this->request->isPost()) )
        {
            return false;
        }
        
        if( !$form->isValid($this->request->getPost()) )
        {
            return false;
        }
    }
}