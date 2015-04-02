<?php
class Petolio_Model_Ticket_TicketsClientWfMapper extends Petolio_Model_Ticket_DataMapperAbstract
{
	public function __construct()
	{
		$this->setDbTableClass('Petolio_Model_DbTable_Ticket_PoTicketsClientWf');
	}

	protected function fromClassToDb($object)
	{
		$data = array(
				'ID' => $object->getId(),
				'flgworkflow' => $object->getFlgworkflow(),
				'date_created' => $object->getDateCreated(),
				'tickets_clients_id' => $object->getTicketsClientsId(),
		);

		return $data;
	}

	protected function fromDbToClass($row)
	{
		$ticketsClientWf = new Petolio_Model_Ticket_TicketsClientWf();

		$ticketsClientWf->setId($row['ID']);
		$ticketsClientWf->setFlgworkflow($row['flgworkflow']);
		$ticketsClientWf->setDateCreated($row['date_created']);
		$ticketsClientWf->setTicketsClientsId($row['tickets_clients_id']);

		return $ticketsClientWf;
	}

	public function getTicketWorkflow($client_ticket_id)
	{
		return $this->getDbTable()->getTicketWorkflow($client_ticket_id);		
	}
	
	public function addTicketsClientWf($tickets_clients_id)
	{	
		$this->getDbTable()->addTicketsClientWf($tickets_clients_id);
	}

	public function updateFlgworkflowTicketsClientWf($tickets_clients_id,$flg)
	{			
		$data = $this->getDbTable()->fetchTicketsClientWf($tickets_clients_id);
	
		$this->getDbTable()->updateFlgworkflowTicketsClientWf($data,$flg);
	}

	public function save(Petolio_Model_Ticket_TicketsClientWf $ticketsClientWf, $ignoreNullValues = true, $escapeValues = false)
	{
		parent::save($ticketsClientWf, $ignoreNullValues, $escapeValues);
	}
}