<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFieldRightUsersMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoFieldRightUsers
     *
     * @var Petolio_Model_DbTable_PoFieldRightUsers
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFieldRightUsers $cls
     */
    public function findOneByField($field, $value, $cls)
    {
		$table = $this->getDbTable();
		$select = $table->select();

		$row = $table->fetchRow($select->where("{$field} = ?", $value));
		if (0 == count($row)) {
			return;
		}

		$cls->setId($row->id)
			->setFieldRightId($row->field_right_id)
			->setUserId($row->user_id);

	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoFieldRightUsers $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
        	'field_right_id' => $cls->getFieldRightId(),
            'user_id' => $cls->getUserId()
        );

        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFieldRightUsers $cls
     * @return array
     */
    public function findByField($field, $value, $cls) {
		$table = $this->getDbTable();
		$select = $table->select();
		$result = array();

		$rows = $table->fetchAll($select->where("{$field} = ?", $value));
		foreach ($rows as $row) {
			$cls = new Petolio_Model_PoFieldRightUsers();
			$result[] = $cls;

			$cls->setId($row->id)
				->setFieldRightId($row->field_right_id)
				->setUserId($row->user_id);
		}

		return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoFieldRightUsers $dbTable
     * @return Petolio_Model_PoFileRightUsersMapper
     *
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable))
            $dbTable = new $dbTable();

        if (!$dbTable instanceof Zend_Db_Table_Abstract)
            throw new Exception('Invalid table data gateway provided');

        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * returns the dbTable class
     *
     * @return Petolio_Model_DbTable_PoFieldRightUsers
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable)
            $this->setDbTable('Petolio_Model_DbTable_PoFieldRightUsers');

        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoFieldRightUsers $cls
     *
     */
    public function save(Petolio_Model_PoFieldRightUsers $cls, $ignoreEmptyValuesOnUpdate = true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key => $value) {
                if (!isset($value) or strlen($value) <= 0)
                    unset($data[$key]);
            }
        }

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
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * finds row by primary key
     *
     * @param int $id
     * @param Petolio_Model_PoFieldRightUsers $cls
     */

    public function find($id, Petolio_Model_PoFieldRightUsers $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();
		$cls->setId($row->id)
			->setFieldRightId($row->field_right_id)
			->setUserId($row->user_id);
    }

    /**
     * fetches all rows
     *
     * @return array
     */
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoFieldRightUsers();
			$entry->setId($row->id)
				->setFieldRightId($row->field_right_id)
				->setUserId($row->user_id)
				->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * fetches all rows optionally filtered by where,order,count and offset
     *
     * @param string $where
     * @param string $order
     * @param int $count
     * @param int $offset
     *
     */
    public function fetchList($where=null, $order=null, $count=null, $offset=null)
    {
		$resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset);
		$entries = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoFieldRightUsers();
			$entry->setId($row->id)
				->setFieldRightId($row->field_right_id)
				->setUserId($row->user_id)
				->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
    }

    public function getCustomUsers($id) {
    	$res = $this->fetchList("field_right_id = ".$this->getDbTable()->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE), 'id');
    	return $res ? $res : false;
    }

    public function setCustomUsers($id, $users) {
    	if($users) {
			foreach($users as $user) {
				$res = new Petolio_Model_PoFieldRightUsers();
				$res->setFieldRightId($id)
					->setUserId($user)
					->save();
			}
    	}
    }

    public function deleteCustomUser($id) {
    	$this->getDbTable()->delete("field_right_id = ".$this->getDbTable()->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
    }
}