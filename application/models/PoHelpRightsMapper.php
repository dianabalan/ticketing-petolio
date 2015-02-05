<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoHelpRightsMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoHelpRights
     *
     * @var Petolio_Model_DbTable_PoHelpRights
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoHelpRights $cls
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
			->setHelpId($row->help_id)
			->setUserId($row->user_id);

	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoHelpRights $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
        	'help_id' => $cls->getHelpId(),
            'user_id' => $cls->getUserId()
        );

        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoHelpRights $cls
     * @return array
     */
    public function findByField($field, $value, $cls) {
		$table = $this->getDbTable();
		$select = $table->select();
		$result = array();

		$rows = $table->fetchAll($select->where("{$field} = ?", $value));
		foreach ($rows as $row) {
			$cls = new Petolio_Model_PoHelpRights();
			$result[] = $cls;

			$cls->setId($row->id)
				->setHelpId($row->help_id)
				->setUserId($row->user_id);
		}

		return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoHelpRights $dbTable
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
     * @return Petolio_Model_DbTable_PoHelpRights
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable)
            $this->setDbTable('Petolio_Model_DbTable_PoHelpRights');

        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoHelpRights $cls
     *
     */
    public function save(Petolio_Model_PoHelpRights $cls, $ignoreEmptyValuesOnUpdate = true)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key => $value) {
                if (!isset($value) or strlen($value) <= 0)
                    unset($data[$key]);
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
     * @param Petolio_Model_PoHelpRights $cls
     */

    public function find($id, Petolio_Model_PoHelpRights $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();
		$cls->setId($row->id)
			->setHelpId($row->help_id)
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
            $entry = new Petolio_Model_PoHelpRights();
			$entry->setId($row->id)
				->setHelpId($row->help_id)
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
            $entry = new Petolio_Model_PoHelpRights();
			$entry->setId($row->id)
				->setHelpId($row->help_id)
				->setUserId($row->user_id)
				->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Get the users
     * @param int $id - help id
     * @return object with users found or false
     */
    public function getCustomUsers($id) {
    	$res = $this->fetchList("help_id = '{$id}'", 'id');
    	return $res ? $res : false;
    }

    /**
     * Set the users
     * @param int $id - help_id
     * @param array $users - array of user ids
     */
    public function setCustomUsers($id, $users) {
    	if($users) {
			foreach($users as $user) {
				$res = new Petolio_Model_PoHelpRights();
				$res->setHelpId($id)
					->setUserId($user)
					->save();
			}
    	}
    }

    /**
     * Delete all the users associated with the id
     * @param int $id
     */
    public function deleteCustomUser($id) {
    	$this->getDbTable()->delete("help_id = {$id}");
    }
}