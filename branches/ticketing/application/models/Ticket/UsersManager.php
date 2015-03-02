<?php

class Petolio_Model_Ticket_UsersManager
{

    private $_dataMapper;

    public function __construct()
    {
        $this->_dataMapper = new Petolio_Model_Ticket_UserMapper();
    }

    /**
     * Gets a list of users which are not registered as clients for the desired service provider.
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

}
