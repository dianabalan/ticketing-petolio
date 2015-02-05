<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoServicesMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoServices
     *
     * @var Petolio_Model_DbTable_PoServices
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoServices $cls
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
				->setUserId($row->user_id)
				->setAttributeSetId($row->attribute_set_id)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setMembersLimit($row->members_limit)
				->setFlagged($row->flagged)
				->setGpsLatitude($row->gps_latitude)
				->setGpsLongitude($row->gps_longitude)
				->setDeleted($row->deleted)
				->setFolderId($row->folder_id);

	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoServices $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
            'user_id' => $cls->getUserId(),
            'attribute_set_id' => $cls->getAttributeSetId(),
            'members_limit' => $cls->getMembersLimit(),
            'date_created' => $cls->getDateCreated(),
        	'date_modified' => $cls->getDateModified(),
        	'flagged' => $cls->getFlagged(),
            'gps_latitude' => $cls->getGpsLatitude(),
            'gps_longitude' => $cls->getGpsLongitude(),
        	'deleted' => $cls->getDeleted(),
        	'folder_id' => $cls->getFolderId()
        );

        return $result;
    }

    public function toObject($array) {
    	$cls = new Petolio_Model_PoServices();

        return $cls->setId($array['id'])
			->setUserId($array['user_id'])
			->setAttributeSetId($array['attribute_set_id'])
			->setDateCreated($array['date_created'])
			->setDateModified($array['date_modified'])
			->setMembersLimit($array['members_limit'])
			->setFlagged($array['flagged'])
			->setGpsLatitude($array['gps_latitude'])
			->setGpsLongitude($array['gps_longitude'])
			->setDeleted($row->deleted)
			->setFolderId($array['folder_id']);
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoServices $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoServices();
                    $result[]=$cls;
                    $cls->setId($row->id)
						->setUserId($row->user_id)
						->setAttributeSetId($row->attribute_set_id)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setMembersLimit($row->members_limit)
						->setFlagged($row->flagged)
						->setGpsLatitude($row->gps_latitude)
						->setGpsLongitude($row->gps_longitude)
						->setDeleted($row->deleted)
						->setFolderId($row->folder_id);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoServices $dbTable
     * @return Petolio_Model_PoServicesMapper
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
     * @return Petolio_Model_DbTable_PoServices
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoServices');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoServices $cls
     *
     */

    public function save(Petolio_Model_PoServices $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoServices $cls
     */

    public function find($id, Petolio_Model_PoServices $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setUserId($row->user_id)
			->setAttributeSetId($row->attribute_set_id)
			->setMembersLimit($row->members_limit)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setFlagged($row->flagged)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setDeleted($row->deleted)
			->setFolderId($row->folder_id);
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
            $entry = new Petolio_Model_PoServices();
            $entry->setId($row->id)
                  ->setUserId($row->user_id)
                  ->setAttributeSetId($row->attribute_set_id)
	              ->setMembersLimit($row->members_limit)
				  ->setDateCreated($row->date_created)
				  ->setDateModified($row->date_modified)
				  ->setFlagged($row->flagged)
				  ->setGpsLatitude($row->gps_latitude)
				  ->setGpsLongitude($row->gps_longitude)
				  ->setFolderId($row->folder_id)
				  ->setDeleted($row->deleted)
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
                    $entry = new Petolio_Model_PoServices();
                    $entry->setId($row->id)
                          ->setUserId($row->user_id)
                          ->setAttributeSetId($row->attribute_set_id)
                          ->setMembersLimit($row->members_limit)
				  		  ->setDateCreated($row->date_created)
				  		  ->setDateModified($row->date_modified)
				  		  ->setFlagged($row->flagged)
						  ->setGpsLatitude($row->gps_latitude)
						  ->setGpsLongitude($row->gps_longitude)
						  ->setDeleted($row->deleted)
				  		  ->setFolderId($row->folder_id)
                          ->setMapper($this);

                    $entries[] = $entry;
            }
            return $entries;
    }
}