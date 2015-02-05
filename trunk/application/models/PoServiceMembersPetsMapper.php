<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoServiceMembersPetsMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoServiceMembersPets
     *
     * @var Petolio_Model_DbTable_PoServiceMembersPets     
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoServiceMembersPets $cls
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
		->setServiceId($row->service_id)
		->setPetId($row->pet_id)
		->setStatus($row->status);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoServiceMembersPets $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
        
            'id' => $cls->getId(),
            'service_id' => $cls->getServiceId(),
            'pet_id' => $cls->getPetId(),
            'status' => $cls->getStatus(),
                    
        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoServiceMembersPets $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoServiceMembersPets();
                    $result[]=$cls;
                    $cls->setId($row->id)
		->setServiceId($row->service_id)
		->setPetId($row->pet_id)
		->setStatus($row->status);
            }
            return $result;
    }
    
    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoServiceMembersPets $dbTable
     * @return Petolio_Model_PoServiceMembersPetsMapper
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
     * @return Petolio_Model_DbTable_PoServiceMembersPets     
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoServiceMembersPets');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoServiceMembersPets $cls
     *
     */
     
    public function save(Petolio_Model_PoServiceMembersPets $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoServiceMembersPets $cls
     */

    public function find($id, Petolio_Model_PoServiceMembersPets $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
		->setServiceId($row->service_id)
		->setPetId($row->pet_id)
		->setStatus($row->status);
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
            $entry = new Petolio_Model_PoServiceMembersPets();
            $entry->setId($row->id)
                  ->setServiceId($row->service_id)
                  ->setPetId($row->pet_id)
                  ->setStatus($row->status)
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
                    $entry = new Petolio_Model_PoServiceMembersPets();
                    $entry->setId($row->id)
                          ->setServiceId($row->service_id)
                          ->setPetId($row->pet_id)
                          ->setStatus($row->status)
                          ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

    /**
	 * loads a service provider links and it's partners (pets)
	 * 
	 * @param int $user_id service provider id
	 * @param int $status
	 */
	public function getUserServiceMembersPets($user_id, $status = null) {
		$links = array();

		foreach ($this->getDbTable()->getUserServiceMembersPets($user_id, $status) as $line) {
			$pet = new Petolio_Model_PoPets();
			$pet->setId($line['pet_id'])
				->setUserId($line['pet_user_id'])
				->setAttributeSetId($line['pet_attribute_set_id'])
				->setDateCreated($line['pet_date_created'])
				->setDateModified($line['pet_date_modified'])
				->setDeleted($line['pet_deleted'])
				->setName($line['pet_name']);
			
			$service = new Petolio_Model_PoServices();
			$service->setId($line['service_id'])
					->setName($line['service_name']);

			$member = new Petolio_Model_PoServiceMembersPets();
			$member->setId($line['id'])
				->setPetId($line['pet_id'])
				->setServiceId($line['service_id'])
				->setStatus($line['status'])
				->setMemberPet($pet)
				->setMemberService($service);

			array_push($links, $member);
		}

		return $links;
		
	}
    
}
