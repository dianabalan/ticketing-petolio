<?php
class Petolio_Model_Ticket_TicketsClientMapper extends Petolio_Model_Ticket_DataMapperAbstract
{
	public function __construct()
	{
		$this->setDbTableClass('Petolio_Model_DbTable_Ticket_PoTicketsClients');
	}

	protected function fromClassToDb($object)
	{
		$data = array(
				'ID' => $object->getId(),
				'ticket_id' => $object->getTicketId(),
				'client_id' => $object->getClientId(),
				'amount' => $object->getAmount(),
				'price' => $object->getPrice(),				
				'date' => $object->getDate(),				
		);

		return $data;
	}

	protected function fromDbToClass($row)
	{
		$ticketsClient = new Petolio_Model_Ticket_TicketsClient();

		$ticketsClient->setId($row['ID']);
		$ticketsClient->setTicketId($row['ticket_id']);
		$ticketsClient->setClientId($row['client_id']);
		$ticketsClient->setAmount($row['amount']);
		$ticketsClient->setPrice($row['price']);
		$ticketsClient->setDate($row['date']);
		
		return $ticketsClient;
	}
	
	public function addTicketsClient(Petolio_Model_Ticket_TicketsClient $ticketsClient)
	{
		$data = $this->fromClassToDb($ticketsClient);
		$this->_replaceEmptyStringsWithNullValues($data);
		
		return $this->getDbTable()->addTicketsClient($data);
	}	
	
	public function fetchClientTickets($user_id)
	{
		$rows = $this->getDbTable()->fetchClientTickets($user_id);
		$adapter = new Zend_Paginator_Adapter_Array($rows);
		return new Zend_Paginator($adapter);
	}

	public function getTicketsClientsWf($tickets_clients_id)
	{
		return $this->getDbTable()->getTicketsClientsWf($tickets_clients_id);
	}
	
	public function modifyAmount($tickets_clients_id, $amount)
	{
		return $this->getDbTable()->modifyAmount($tickets_clients_id, $amount);
	}

	public function save(Petolio_Model_Ticket_TicketsClient $ticketsClient, $ignoreNullValues = true, $escapeValues = false)
	{
		parent::save($ticketsClient, $ignoreNullValues, $escapeValues);
	}
}
