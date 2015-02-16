<?php

class Petolio_Model_PoEmergencyMapper {
	protected $_dbTable;

	public function toArray($cls) {
		return array(
			'id' => $cls->getId(),
			'scope' => $cls->getScope(),
			'entity_id' => $cls->getEntityId(),
			'first_name' => $cls->getFirstName(),
			'last_name' => $cls->getLastName(),
			'category' => $cls->getCategory(),
			'phone' => $cls->getPhone(),
			'email' => $cls->getEmail()
		);
	}

	public function find($id, Petolio_Model_PoEmergency $cls) {
		$result = $this->getDbTable()->find($id);
		if(count($result) == 0)
			return;

		$row = $result->current();
		$cls->setId($row->id)
			->setScope($row->scope)
			->setEntityId($row->entity_id)
			->setFirstName($row->first_name)
			->setLastName($row->last_name)
			->setCategory($row->category)
			->setPhone($row->phone)
			->setEmail($row->email);
	}

	public function fetchAll() {
		$entries = array();
		foreach($this->getDbTable()->fetchAll() as $row) {
			$cls = new Petolio_Model_PoEmergency();
			$cls->setId($row->id)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setFirstName($row->first_name)
				->setLastName($row->last_name)
				->setCategory($row->category)
				->setPhone($row->phone)
				->setEmail($row->email)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function fetchList($where = null, $order = null, $count = null, $offset = null) {
		$entries = array();
		foreach($this->getDbTable()->fetchAll($where, $order, $count, $offset) as $row) {
			$cls = new Petolio_Model_PoEmergency();
			$cls->setId($row->id)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setFirstName($row->first_name)
				->setLastName($row->last_name)
				->setCategory($row->category)
				->setPhone($row->phone)
				->setEmail($row->email)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function findOneByField($field, $value, Petolio_Model_PoEmergency $cls) {
		$table = $this->getDbTable();
		$row = $table->fetchRow($table->select()->where("{$field} = ?", $value));
		if(count($row) == 0)
			return;

		$cls->setId($row->id)
			->setScope($row->scope)
			->setEntityId($row->entity_id)
			->setFirstName($row->first_name)
			->setLastName($row->last_name)
			->setCategory($row->category)
			->setPhone($row->phone)
			->setEmail($row->email);

		return $cls;
	}

	public function findByField($field, $value, $cls) {
		$result = array();
		$table = $this->getDbTable();
		foreach($table->fetchAll($table->select()->where("{$field} = ?", $value)) as $row) {
			$cls = new Petolio_Model_PoEmergency();
			$cls->setId($row->id)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setFirstName($row->first_name)
				->setLastName($row->last_name)
				->setCategory($row->category)
				->setPhone($row->phone)
				->setEmail($row->email);
			$result[] = $cls;
		}

		return $result;
	}

	public function setDbTable($dbTable) {
		if(is_string($dbTable))
			$dbTable = new $dbTable();

		if(!$dbTable instanceof Zend_Db_Table_Abstract)
			throw new Exception('Invalid table data gateway provided');

		$this->_dbTable = $dbTable;
		return $this;
	}

	public function getDbTable() {
		if(is_null($this->_dbTable))
			$this->setDbTable('Petolio_Model_DbTable_PoEmergency');

		return $this->_dbTable;
	}

	public function save(Petolio_Model_PoEmergency $cls, $ignoreEmpty = true, $escapeValues = false) {
		$data = $cls->toArray();
		if($ignoreEmpty)
			foreach($data as $key=>$value)
				if(!isset($value) or strlen($value) <= 0)
					unset($data[$key]);

        if ( $escapeValues ) {
	        foreach ($data as $key => $value) {
	        	if ( !($value instanceof Zend_Db_Expr) )
		        	$data[$key] = Petolio_Service_Util::escape($data[$key]);
	        }
        }

		if(($id = $cls->getId()) === null) {
			unset($data['id']);
			$id = $this->getDbTable()->insert($data);
			$cls->setId($id);
		} else
			$this->getDbTable()->update($data, array('id = ?' => $id));
	}
}