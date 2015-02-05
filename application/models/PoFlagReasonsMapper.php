<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFlagReasonsMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoFlagReasons
     *
     * @var Petolio_Model_DbTable_PoFlagReasons
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFlagReasons $cls
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
			->setValue($row->value)
			->setParentId($row->parent_id);

	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoFlagReasons $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
        	'value' => $cls->getValue(),
            'parent_id' => $cls->getParentId()
        );

        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFlagReasons $cls
     * @return array
     */
    public function findByField($field, $value, $cls) {
		$table = $this->getDbTable();
		$select = $table->select();
		$result = array();

		$rows = $table->fetchAll($select->where("{$field} = ?", $value));
		foreach ($rows as $row) {
			$cls = new Petolio_Model_PoFlagReasons();
			$result[] = $cls;

			$cls->setId($row->id)
				->setValue($row->value)
				->setParentId($row->parent_id);
		}

		return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoFlagReasons $dbTable
     * @return Petolio_Model_PoFlagReasonsMapper
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
     * @return Petolio_Model_DbTable_PoFlagReasons
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable)
            $this->setDbTable('Petolio_Model_DbTable_PoFlagReasons');

        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoFlagReasons $cls
     *
     */
    public function save(Petolio_Model_PoFlagReasons $cls, $ignoreEmptyValuesOnUpdate = true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key => $value) {
                if (is_null($value) or $value == '')
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
     * @param Petolio_Model_PoFlagReasons $cls
     */

    public function find($id, Petolio_Model_PoFlagReasons $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();
		$cls->setId($row->id)
			->setValue($row->value)
			->setParentId($row->parent_id);
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
            $entry = new Petolio_Model_PoFlagReasons();
			$entry->setId($row->id)
				->setValue($row->value)
				->setParentId($row->parent_id)
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
            $entry = new Petolio_Model_PoFlagReasons();
			$entry->setId($row->id)
				->setValue($row->value)
				->setParentId($row->parent_id)
				->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
    }
}