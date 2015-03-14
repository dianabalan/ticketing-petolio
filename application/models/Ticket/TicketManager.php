<?php

class Petolio_Model_Ticket_TicketManager
{

	private $_dataMapper;

	public function __construct()
	{
		$this->_dataMapper = new Petolio_Model_Ticket_TicketMapper();
	}

	/**
	 * Saves the changes performed on the specified client to the data source.
	 *
	 * @param Petolio_Model_Ticket_Ticket $ticket The ticket object to be saved.
	 *
	 * @return void
	 */
	public function save(Petolio_Model_Ticket_Ticket $ticket)
	{
		return $this->_dataMapper->save($ticket);
	}
	
	/**
	 * 
	 * @param integer $user_id
	 * @param integer $ticket_id
	 * @return Petolio_Model_Ticket_Ticket|NULL
	 */
	public function getTicket($user_id, $ticket_id)
	{
		return $this->_dataMapper->fetchTicket($user_id, $ticket_id);
	}
	
	/**
	 * 
	 * @param integer $user_id
	 * @param integer $items_per_page
	 * @param integer $page
	 * @return Zend_Paginator
	 */
	public function getTickets($user_id, $items_per_page, $page)
	{
		$paginator = $this->_dataMapper->fetchTickets($user_id);
		$paginator->setItemCountPerPage((int) $items_per_page);
        $paginator->setCurrentPageNumber((int) $page);
        
        return $paginator;
	}
	
	public function getArchivedTickets($user_id, $items_per_page, $page)
	{
		$paginator = $this->_dataMapper->fetchTickets($user_id, 0);
		$paginator->setItemCountPerPage((int) $items_per_page);
		$paginator->setCurrentPageNumber((int) $page);
	
		return $paginator;
	}
}