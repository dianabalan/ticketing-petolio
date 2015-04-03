<?php

class Petolio_Model_DbTable_Ticket_Tickets extends Zend_Db_Table_Abstract
{

	protected $_name = 'po_tickets';

	protected $_primary = 'ID';
	
	protected static $_columns = array(
			'ID',
			'item_id',
			'user_id',
			'description',
			'ticket_date',
			'date_created',
			'date_modified',
			'flgreminder',
			'archive',
			'scope'
	);
	
	public function fetchTicket($user_id, $ticket_id)
	{
		$db = $this->getAdapter();
	
		$query = $db->select()
		->from($this->_name, self::$_columns)
		->where('ID = :id')
		->where('user_id = :user_id');
	
		$row = $db->fetchRow($query, array(
				':id' => $ticket_id,
				':user_id' =>$user_id
		));
	
		return $row;
	}

	public function fetchTickets($user_id, $archive)
	{
		$db = $this->getAdapter();
	
		$query = $db->select()
			->from($this->_name, self::$_columns)
			->where('user_id = :user_id')
			->where('archive = :archive');
	
		$rows = $db->fetchAll($query, array(
				':user_id' => $user_id,
				':archive' => $archive
		));
	
		return $rows;
	}
	
	public function fetchClientTickets($client_id)
	{
		$db = $this->getAdapter();
		
		foreach(self::$_columns as $column)
		{
			$descriptiveColumns[] = $this->_name.'.'.$column;
		}
		
		$subQuery = $db->select()
					->from('po_tickets_clients', array('COUNT(*)'))
					->where('po_tickets_clients.ticket_id = po_tickets.id');		
		
		$descriptiveColumns["status"] = "IF((".$subQuery.") = 0,'available','taken')";
		
		$query = $db->select()
			->from($this->_name, $descriptiveColumns)
			->join('po_clients','user_id = sp_id','','petolio')
			->where('client_id = :client_id');
		
		$rows = $db->fetchAll($query, array(
				':client_id' => $client_id));
		
		return $rows;
	}
	
	public function insert(array $data)
	{
		date_default_timezone_set("UTC");
		$now = date("Y-m-d H:i:s", time());
	
		$data['date_created'] = $now;
		$data['date_modified'] = $now;
	
		return parent::insert($data);
	}
	
	public function update(array $data, $where)
	{
		date_default_timezone_set("UTC");
		$now = date("Y-m-d H:i:s", time());
	
		$data['date_modified'] = $now;
	
		return parent::update($data, $where);
	}
}