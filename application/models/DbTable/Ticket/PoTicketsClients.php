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
			->from($this->_name, self::$_columns)
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
			->join("po_tickets",$this->_name.".ticket_id = po_tickets.id","po_tickets.description","petolio")
			->where("po_clients.client_id = :client_id");

		$rows = $db->fetchAll($query, array(
				':client_id' => $client_id));
	
		
		return $rows;
	}	
	
	public function fetchTicketsClientsById($id)
	{
		$db = $this->getAdapter();
	
		$query = $db->select()
			->from($this->_name, self::$_columns)
			->where($this->_primary.' = :id');
					
		$row = $db->fetchRow($query, array(
				':id' => $id
		));
		
		return $row;
	}	
	
	private function getPrice($ticket_id)
	{

		$db = $this->getAdapter();
		
		$scopeQuery = $db->select()
				->from("po_tickets",array("scope"))
				->where("id = :id");
		
		$scope = $db->fetchOne($scopeQuery, array(
				':id' => $ticket_id
		));
		
		$itemQuery = $db->select()
				->from("po_tickets",array("item_id"))
				->where("id = :id");
		
		$itemId = $db->fetchOne($itemQuery, array(
				':id' => $ticket_id
				)); 
				
		if($scope == "service")
		{
			$price = array( "value" => 1);
		}
		else if($scope == "product")
		{						
			$priceQuery = $db->select()
				->from("po_products",array("id"))
				->join("po_attributes","po_products.attribute_set_id = po_attributes.attribute_set_id",array("label"),"petolio")
				->join("po_attribute_entity_decimal","po_attributes.ID = po_attribute_entity_decimal.attribute_id",array("value"),"petolio")
				->where("po_products.attribute_set_id = 66")
				->where("po_products.ID = :id")
				->where("po_products.ID = po_attribute_entity_decimal.entity_id");
			
			$price = $db->fetchAll($priceQuery, array(
				':id' => $itemId
			));
		}
		
		return $price[0]["value"];
	}
	
	public function addTicketsClient(array $data)
	{
		$db = $this->getAdapter();
		
		date_default_timezone_set("UTC");
		$now = date("Y-m-d H:i:s", time());
		
		$data['date'] = $now;
		$data['amount'] = 1;
		$data['price'] = $this->getPrice($data["ticket_id"]);

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
		
		$data = $this->fetchTicketsClients($tickets_clients_id);
		
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