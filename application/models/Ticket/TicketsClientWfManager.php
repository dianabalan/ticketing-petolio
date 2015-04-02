<?php
class Petolio_Model_Ticket_TicketsClientWfManager
{
	private $_dataMapper;

	public function __construct()
	{
		$this->_dataMapper = new Petolio_Model_Ticket_TicketsClientWfMapper();
	}

	public function addTicketsClientWf($tickets_clients_id)
	{
		$this->_dataMapper->addTicketsClientWf($tickets_clients_id);
	}
	
	public function updateFlgworkflowTicketsClientWf($tickets_clients_id,$flg)
	{
		$this->_dataMapper->updateFlgworkflowTicketsClientWf($tickets_clients_id,$flg);
	}			
	
	public function getTicketWorkflow($client_ticket_id)
	{
		return $this->_dataMapper->getTicketWorkflow($client_ticket_id);
	}
	
	public function save(Petolio_Model_Ticket_TicketsClientWf $ticketsClient)
	{
		return $this->_dataMapper->save($ticketsClient);
	}


}