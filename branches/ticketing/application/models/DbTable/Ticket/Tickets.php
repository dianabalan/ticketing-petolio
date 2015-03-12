<?php

class Petolio_Model_DbTable_Ticket_Tickets extends Zend_Db_Table_Abstract
{

	protected $_name = 'po_tickets';

	protected $_primary = 'ID';
	
	public function fetchTicket($id)
	{
		$db = $this->getAdapter();
	
		$columns = array(
				'ID',
				'item_id',
				'user_id',
				'service',
				'amount',
				'price',
				'ticket_date',
				'date_created',
				'date_modified',
				'flgreminder',
				'archive',
				'scope'
		);
	
		$query = $db->select()
		->from($this->_name, $columns)
		->where('ID = :id');
	
		$row = $db->fetchRow($query, array(
				':id' => $id
		));
	
		return $row;
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