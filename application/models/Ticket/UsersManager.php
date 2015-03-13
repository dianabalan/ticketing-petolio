<?php

class Petolio_Model_Ticket_UsersManager
{

    private $_dataMapper;

    public function __construct()
    {
        $this->_dataMapper = new Petolio_Model_Ticket_UserMapper();
    }

    /**
     * Gets a list of users which are not registered as clients for the specified service provider.
     *
     * @author Stefan Baiu
     *        
     * @param int $sp_id The service provider id.
     * @param int $page The current page.
     * @param int $items_per_page The number of items to get per page.
     * @param Petolio_Model_Ticket_SearchUserFilter $filter (optional) The search filter.
     *       
     * @return Zend_Paginator
     */
    public function getNonClients($sp_id, $page, $items_per_page, Petolio_Model_Ticket_SearchUserFilter $filter = null)
    {
        return $this->_dataMapper->fetchNonClients($sp_id, $page, $items_per_page, $filter);
    }
    
    /**
     * Gets a list of users which are registered as clients for the specified service provider.
     *
     * @author Stefan Baiu
     *
     * @param int $sp_id The service provider id.
     *
     * @return Petolio_Model_Ticket_User[]
     */
    public function getClients($sp_id)
    {
        return $this->_dataMapper->fetchClients($sp_id);
    }
    
    public function registerNonPetolioMember(Petolio_Model_Ticket_NonPetolioMember $user, $sp_id)
    {
        return $this->_dataMapper->registerNonPetolioMember($user, $sp_id);
    }
    
    public function updateNonPetolioMember(Petolio_Model_Ticket_NonPetolioMember $user, $sp_id)
    {
        return $this->_dataMapper->updateNonPetolioMember($user, $sp_id);
    }
    
    /**
     * Gets a non-Petolio member created by the specified service provider.
     *
     * @author Stefan Baiu
     *
     * @param int $user_id The user id.
     * @param int $sp_id The service provider id.
     *
     * @return Petolio_Model_Ticket_NonPetolioMember
     */
    public function getNonPetolioMember($user_id, $sp_id)
    {
        return $this->_dataMapper->fetchNonPetolioMember($user_id, $sp_id);
    }
    
    public function getNonPetolioMembers($sp_id)
    {
        return $this->_dataMapper->fetchNonPetolioMembers($sp_id);
    }    
    
    public function isAlreadyRegisteredAsPetolio($email)
    {
    	return $this->_dataMapper->isAlreadyRegisteredAsPetolio($email);
    }
}
