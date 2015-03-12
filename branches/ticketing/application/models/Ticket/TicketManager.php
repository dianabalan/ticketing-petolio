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
}