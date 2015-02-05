<?php

class Petolio_Model_PoFileRightsMapper {
    protected $_dbTable;

    public function findOneByField($field, $value, $cls)
    {
		$table = $this->getDbTable();
		$select = $table->select();

		$row = $table->fetchRow($select->where("{$field} = ?", $value));
		if (0 == count($row))
			return;

		$cls->setId($row->id)
			->setFileId($row->file_id)
			->setUserId($row->user_id);

		return $cls;
    }

    public function toArray($cls) {
        return array(
            'id' => $cls->getId(),
            'file_id' => $cls->getFileId(),
            'user_id' => $cls->getUserId()
        );;
    }

    public function findByField($field, $value, $cls)
    {
		$table = $this->getDbTable();
		$select = $table->select();
		$result = array();

		$rows = $table->fetchAll($select->where("{$field} = ?", $value));
		foreach ($rows as $row) {
			$cls = new Petolio_Model_PoFileRights();
			$result[] = $cls;

			$cls->setId($row->id)
				->setFileId($row->file_id)
				->setUserId($row->user_id);
		}

		return $result;
    }

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable))
            $dbTable = new $dbTable();

        if (!$dbTable instanceof Zend_Db_Table_Abstract)
            throw new Exception('Invalid table data gateway provided');

        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable)
            $this->setDbTable('Petolio_Model_DbTable_PoFileRights');

        return $this->_dbTable;
    }

    public function save(Petolio_Model_PoFileRights $cls, $ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate)
            foreach ($data as $key=>$value)
                if (!isset($value) or strlen($value) <= 0)
                    unset($data[$key]);

        if ( $escapeValues ) {
	        foreach ($data as $key => $value) {
	        	if ( !($value instanceof Zend_Db_Expr) )
		        	$data[$key] = Petolio_Service_Util::escape($data[$key]);
	        }
        }
                            
        if (null === ($id = $cls->getId())) {
            unset($data['id']);
            $id = $this->getDbTable()->insert($data);
            $cls->setId($id);
        } else
            $this->getDbTable()->update($data, array('id = ?' => $id));
    }

    public function find($id, Petolio_Model_PoFileRights $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result))
            return;

        $row = $result->current();
        $cls->setId($row->id)
			->setFileId($row->file_id)
			->setUserId($row->user_id);
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoFileRights();
            $entry->setId($row->id)
				->setFileId($row->file_id)
				->setUserId($row->user_id)
				->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
    }

    public function fetchList($where=null, $order=null, $count=null, $offset=null)
    {
		$resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset);
		$entries = array();
		foreach ($resultSet as $row) {
			$entry = new Petolio_Model_PoFileRights();
			$entry->setId($row->id)
				->setFileId($row->file_id)
				->setUserId($row->user_id)
				->setMapper($this);

			$entries[] = $entry;
		}

		return $entries;
    }

    public function fetchUsers($id = false)
    {
    	if(!$id)
    		return array();

		$db = $this->getDbTable();
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_file_rights'))
			->joinLeft(array('u' => 'po_users'), 'c.user_id = u.id', array('user_name' => 'name'))
			->where("c.file_id = ?", $id, Zend_Db::BIGINT_TYPE);

		return reset($db->fetchAll($select));
    }
}