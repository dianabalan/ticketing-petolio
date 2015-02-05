<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFoldersMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoFolders
     *
     * @var Petolio_Model_DbTable_PoFolders
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFolders $cls
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
		->setName($row->name)
		->setParentId($row->parent_id)
		->setOwnerId($row->owner_id)
		->setPetId($row->pet_id)
		->setTraceback($row->traceback)
		->setDateCreated($row->date_created);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoFolders $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(

            'id' => $cls->getId(),
            'name' => $cls->getName(),
            'parent_id' => $cls->getParentId(),
            'owner_id' => $cls->getOwnerId(),
            'pet_id' => $cls->getPetId(),
        	'traceback' => $cls->getTraceback(),
        	'date_created' => $cls->getDateCreated()

        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFolders $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoFolders();
                    $result[]=$cls;
                    $cls->setId($row->id)
		->setName($row->name)
		->setParentId($row->parent_id)
		->setOwnerId($row->owner_id)
		->setPetId($row->pet_id)
		->setTraceback($row->traceback)
		->setDateCreated($row->date_created);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoFolders $dbTable
     * @return Petolio_Model_PoFoldersMapper
     *
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * returns the dbTable class
     *
     * @return Petolio_Model_DbTable_PoFolders
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoFolders');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoFolders $cls
     *
     */

    public function save(Petolio_Model_PoFolders $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key=>$value) {
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
     * @param Petolio_Model_PoFolders $cls
     */

    public function find($id, Petolio_Model_PoFolders $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
		->setName($row->name)
		->setParentId($row->parent_id)
		->setOwnerId($row->owner_id)
		->setPetId($row->pet_id)
		->setTraceback($row->traceback)
		->setDateCreated($row->date_created);
    }

    /**
     * fetches all rows
     *
     * @return array
     */
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoFolders();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setParentId($row->parent_id)
                  ->setOwnerId($row->owner_id)
                  ->setPetId($row->pet_id)
				->setTraceback($row->traceback)
				->setDateCreated($row->date_created)
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
            $entries   = array();
            foreach ($resultSet as $row)
            {
                    $entry = new Petolio_Model_PoFolders();
                    $entry->setId($row->id)
                          ->setName($row->name)
                          ->setParentId($row->parent_id)
                          ->setOwnerId($row->owner_id)
                          ->setPetId($row->pet_id)
						->setTraceback($row->traceback)
						->setDateCreated($row->date_created)
                          ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

	/**
	 * Get the folder structure up until and including root
	 * @param csv $traceback (10,12,44)
	 *
	 * @return array
	 */
	public function getBreadcrumbs($traceback = null) {
		$db = $this->getDbTable();

		// null
		if(is_null($traceback))
			$traceback = new Zend_Db_Expr('NULL');

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_folders'), array('a.id', 'a.name'))
			->where("FIND_IN_SET(a.id, ".$db->getAdapter()->quote($traceback).")")
			->order("a.id ASC");

		// return either array or paginator
		return $db->fetchAll($select)->toArray();
	}

	/**
	 * Get all children folders from a folder
	 * @param int $id
	 * @param int $owner
	 *
	 * @return array
	 */
	public function getChildren($id = null, $owner = null) {
		$db = $this->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_folders'), array('a.id', 'a.name', 'a.pet_id', 'a.traceback'))
			->where("FIND_IN_SET(".$db->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).", a.traceback)");

		// if we're intrested to filter by owner
		if($owner)
			$select->where("owner_id = ?", $owner, Zend_Db::BIGINT_TYPE);

		// order by latest first
		$select->order("a.id DESC");

		// return either array or paginator
		return $db->fetchAll($select)->toArray();
	}
}