<?php

class Petolio_Model_DbTable_Ticket_PoTicketsClients extends Zend_Db_Table_Abstract
{

	protected $_name = 'po_tickets_clients';

	protected $_primary = 'ID';
	
	protected static $_columns = array(
				'ID',
				'ticket_id',
				'client_id',
				'amount',
				'price',
				'date',
	);	

	public function fetchTicketsClients($client_id)
	{
		$db = $this->getAdapter();

		$query = $db->select()
			->from($this->_name, self::$columns)
			->where('client_id = :client_id');

		$row = $db->fetchRow($query, array(
				':client_id' => $client_id
		));

		return $row;
	}
	

	public function fetchClientTickets($client_id)
	{
		$db = $this->getAdapter();
	
		foreach(self::$_columns as $column)
		{
			$decriptiveColumns[] = $this->_name.'.'.$column;
		}
				
		$query = $db->select()
			->from($this->_name, $decriptiveColumns)
			->join("po_clients",$this->_name.".client_id = po_clients.id","","petolio")
			->join("po_tickets_client_wf",$this->_name.".ID = po_tickets_client_wf.tickets_clients_id","po_tickets_client_wf.flgworkflow","petolio")
			->where("po_clients.client_id = :client_id");
	
		$rows = $db->fetchAll($query, array(
				':client_id' => $client_id));
	
		return $rows;
	}	
	
	public function fetchTicketsClientsById($id)
	{
		$db = $this->getAdapter();
	
		$query = $db->select()
			->from($this->_name, self::$columns)
			->where('ID = :id');
	
		$row = $db->fetchRow($query, array(
				':id' => $id
		));
	
		return $row;
	}	
	
	public function addTicketsClient(array $data)
	{
		$db = $this->getAdapter();
		
		date_default_timezone_set("UTC");
		$now = date("Y-m-d H:i:s", time());
		
		$data['date'] = $now;
		$data['amount'] = 1;
		$data['price'] = 1;

		$columns = array("po_clients.ID");
		
		$query = $db->select()
			->from("po_clients", $columns)
			->join("po_tickets","po_tickets.user_id = po_clients.sp_id","","petolio")
			->where('po_tickets.ID = :ticket_id')
			->where('po_clients.client_id = :client_id');

		$data["client_id"] = $db->fetchOne($query, array(
				':client_id' => $data["client_id"],
				':ticket_id' => $data['ticket_id']
		));
				 
		return $this->insert($data);		
	}
	
	public function modifyAmount($tickets_clients_id, $amount)
	{
		$db = $this->getAdapter();
		
		$data = $this->fetchTicketsClientsById($tickets_clients_id);
		$data['amount'] = $amount;
		
		$affected_rows_count = $this->update($data, array(
				$this->_primary. ' = ?' => $tickets_clients_id
		));
		
		return $affected_rows_count;	
	}
	
	public function insert(array $data)
	{		
		return parent::insert($data);
	}
}