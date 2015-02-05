<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoMedicalRecordRightsMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoMedicalRecordRights
     *
     * @var Petolio_Model_DbTable_PoMedicalRecordRights
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoMedicalRecordRights $cls
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
				->setMedicalRecordId($row->medical_record_id)
				->setUserId($row->user_id);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoMedicalRecordRights $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
        
            'id' => $cls->getId(),
        	'medical_record_id' => $cls->getMedicalRecordId(),
            'user_id' => $cls->getUserId()
                    
        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoMedicalRecordRights $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls = new Petolio_Model_PoMedicalRecordRights();
                    $result[]=$cls;
		            $cls->setId($row->id)
						->setMedicalRecordId($row->medical_record_id)
						->setUserId($row->user_id);
            }
            return $result;
    }
    
    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoMedicalRecordRights $dbTable
     * @return Petolio_Model_PoMedicalRecordRightsMapper
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
     * @return Petolio_Model_DbTable_PoMedicalRecordRights    
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoMedicalRecordRights');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoMedicalRecordRights $cls
     *
     */
     
    public function save(Petolio_Model_PoMedicalRecordRights $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoMedicalRecordRights $cls
     */

    public function find($id, Petolio_Model_PoMedicalRecordRights $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setMedicalRecordId($row->medical_record_id)
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
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoMedicalRecordRights();
            $entry->setId($row->id)
				  ->setMedicalRecordId($row->medical_record_id)
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
            $entries   = array();
            foreach ($resultSet as $row)
            {
                    $entry = new Petolio_Model_PoMedicalRecordRights();
		            $entry->setId($row->id)
						  ->setMedicalRecordId($row->medical_record_id)
						  ->setUserId($row->user_id)
						  			  ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

    /**
     * returns an array of PoUsers who have access to this medical record
     * 
     * @param int $medical_record_id
     */
    public function findAllowedUsers($medical_record_id = -1) {
        $resultSet = $this->getDbTable()->findAllowedUsers($medical_record_id);
        $entries   = array();
        foreach ($resultSet as $line) {
        	$user = new Petolio_Model_PoUsers();
        	$user->setId($line['user_id'])
				->setName($line['user_name'])
				->setEmail($line['user_email'])
				->setPassword($line['user_password'])
				->setActive($line['user_active'])
				->setStreet($line['user_street'])
				->setAddress($line['user_address'])
				->setZipcode($line['user_zipcode'])
				->setLocation($line['user_location'])
				->setCountryId($line['user_country_id'])
				->setPhone($line['user_phone'])
				->setHomepage($line['user_homepage'])
				->setGender($line['user_gender'])
				->setDateOfBirth($line['user_date_of_birth'])
				->setGpsLatitude($line['user_gps_latitude'])
				->setGpsLongitude($line['user_gps_longitude'])
				->setDateForgot($line['user_date_forgot'])
				->setAvatar($line['user_avatar'])
				->setDateCreated($line['user_date_created'])
				->setDateModified($line['user_date_modified'])
				->setType($line['user_type']);

			$entries[] = $user;
		}
		return $entries;
    }
}
