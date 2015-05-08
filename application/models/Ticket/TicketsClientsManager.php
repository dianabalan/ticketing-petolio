<?php

class Petolio_Model_Ticket_TicketsClientsManager
{

    private $_dataMapper;

    public function __construct()
    {
        $this->_dataMapper = new Petolio_Model_Ticket_TicketsClientMapper();
    }

    public function addTicketsClient(Petolio_Model_Ticket_TicketsClient $ticketsClient)
    {
    	$tickets_clients_id = $this->_dataMapper->addTicketsClient($ticketsClient);

    	$wfManager = new Petolio_Model_Ticket_TicketsClientWfManager();
    	$wfManager->addTicketsClientWf($tickets_clients_id);
    }

    public function getClientTickets($user_id, $items_per_page, $page)
    {
    	$paginator = $this->_dataMapper->fetchClientTickets($user_id);
    	$paginator->setItemCountPerPage((int) $items_per_page);
    	$paginator->setCurrentPageNumber((int) $page);

    	return $paginator;
    }

	public function getSpTickets($user_id, $items_per_page, $page)
	{
		$paginator = $this->_dataMapper->fetchSpTickets($user_id);
		$paginator->setItemCountPerPage((int) $items_per_page);
        $paginator->setCurrentPageNumber((int) $page);

        return $paginator;
	}

	public function getSpTicket($user_id, $ticket_id)
	{
		return $this->_dataMapper->fetchSpTicket($user_id, $ticket_id);
	}

    public function getTicketWorkflow($client_ticket_id)
    {
    	$wfManager = new Petolio_Model_Ticket_TicketsClientWfManager();
    	return  $wfManager->getTicketWorkflow($client_ticket_id);
    }

    public function cancelTicketsClient($tickets_clients_id)
    {
    	$this->updateFlgworkflowTicketsClientWf($tickets_clients_id, 'cancelled');
    }

    public function payTicketsClient($tickets_clients_id)
    {
    	$this->updateFlgworkflowTicketsClientWf($tickets_clients_id, 'instructed');
    }

    public function reactivateTicketsClient($tickets_clients_id)
    {
    	$this->updateFlgworkflowTicketsClientWf($tickets_clients_id, 'billable');
    }

    public function modifyAmount($tickets_clients_id, $amount)
    {
    	$this->_dataMapper->modifyAmount($tickets_clients_id, $amount);
    }

    private function updateFlgworkflowTicketsClientWf($tickets_clients_id, $flg)
    {
    	$wfManager = new Petolio_Model_Ticket_TicketsClientWfManager();
    	$wfManager->updateFlgworkflowTicketsClientWf($tickets_clients_id,$flg);
    }

    public function save(Petolio_Model_Ticket_TicketsClient $ticketsClient)
    {
        return $this->_dataMapper->save($ticketsClient);
    }


}