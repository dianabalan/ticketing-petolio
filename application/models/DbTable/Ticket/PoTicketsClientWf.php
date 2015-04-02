<?php

class Petolio_Model_DbTable_Ticket_PoTicketsClientWf extends Zend_Db_Table_Abstract
{

	protected $_name = 'po_tickets_client_wf';

	protected $_primary = 'ID';

	public function fetchTicketsClientWf($tickets_clients_id)
	{
		$db = $this->getAdapter();

		$columns = array(
				'ID',
				'flgworkflow',
				'date_created',
				'tickets_clients_id',
		);

		$query = $db->select()
		->from($this->_name, $columns)
		->where('tickets_clients_id = :tickets_clients_id');

		$row = $db->fetchRow($query, array(
				':tickets_clients_id' => $tickets_clients_id
		));

		return $row;
	}

	public function addTicketsClientWf($tickets_clients_id)
	{
		$db = $this->getAdapter();

		date_default_timezone_set("UTC");
		$now = date("Y-m-d H:i:s", time());

		$data['date_created'] = $now;
		$data['tickets_clients_id'] = $tickets_clients_id;
		$data['flgworkflow'] = "billable";

		return $this->insert($data);
	}
	
	public function cancelTicketsClientWf($data)
	{
		return $this->updateFlgworkflow($data,'cancelled');
	}
	
	public function updateFlgworkflowTicketsClientWf($data,$workflow)
	{
		$data['flgworkflow'] = $workflow;
		
		$id = (int) $data[$this->_primary];
		$affected_rows_count = $this->update($data, array(
				$this->_primary. ' = ?' => $id
		));
		
		return $affected_rows_count;		
	}

	public function getTicketWorkflow($tickets_clients_id)
	{
		$db = $this->getAdapter();
		
		$columns = array(
				'flgworkflow',
		);
		
		$query = $db->select()
		->from($this->_name, $columns)
		->where('tickets_clients_id = :tickets_clients_id');
		
		$flg = $db->fetchOne($query, array(
				':tickets_clients_id' => $tickets_clients_id
		));
		
		return $flg;
	}
	
	public function insert(array $data)
	{
		return parent::insert($data);
	}
}