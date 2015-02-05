<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoMedicalRecordSubentriesMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoMedicalRecordSubentries
     *
     * @var Petolio_Model_DbTable_PoMedicalRecordSubentries
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoMedicalRecordSubentries $cls
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
				->setServiceId($row->service_id)
				->setOwnerId($row->owner_id)
				->setVisitDate($row->visit_date)
				->setHeadline1($row->headline1)
				->setHeadline2($row->headline2)
				->setDescription($row->description)
				->setRecommendation($row->recommendation)
				->setDrugs($row->drugs)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setFolderId($row->folder_id)
				->setDeleted($row->deleted);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoMedicalRecordSubentries $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(

            'id' => $cls->getId(),
        	'medical_record_id' => $cls->getMedicalRecordId(),
            'service_id' => $cls->getServiceId(),
        	'owner_id' => $cls->getOwnerId(),
        	'visit_date' => $cls->getVisitDate(),
        	'headline1' => $cls->getHeadline1(),
        	'headline2' => $cls->getHeadline2(),
            'description' => $cls->getDescription(),
            'recommendation' => $cls->getRecommendation(),
            'drugs' => $cls->getDrugs(),
        	'date_created' => $cls->getDateCreated(),
        	'date_modified' => $cls->getDateModified(),
            'folder_id' => $cls->getFolderId(),
        	'deleted' => $cls->getDeleted()

        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoMedicalRecordSubentries $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoMedicalRecordSubentries();
                    $result[]=$cls;
		            $cls->setId($row->id)
						->setMedicalRecordId($row->medical_record_id)
						->setServiceId($row->service_id)
						->setOwnerId($row->owner_id)
						->setVisitDate($row->visit_date)
						->setHeadline1($row->headline1)
						->setHeadline2($row->headline2)
						->setDescription($row->description)
						->setRecommendation($row->recommendation)
						->setDrugs($row->drugs)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setFolderId($row->folder_id)
						->setDeleted($row->deleted);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoMedicalRecordSubentries $dbTable
     * @return Petolio_Model_PoMedicalRecordSubentriesMapper
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
     * @return Petolio_Model_DbTable_PoMedicalRecordSubentries
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoMedicalRecordSubentries');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoMedicalRecordSubentries $cls
     *
     */

    public function save(Petolio_Model_PoMedicalRecordSubentries $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoMedicalRecordSubentries $cls
     */

    public function find($id, Petolio_Model_PoMedicalRecordSubentries $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setMedicalRecordId($row->medical_record_id)
			->setServiceId($row->service_id)
			->setOwnerId($row->owner_id)
			->setVisitDate($row->visit_date)
			->setHeadline1($row->headline1)
			->setHeadline2($row->headline2)
			->setDescription($row->description)
			->setRecommendation($row->recommendation)
			->setDrugs($row->drugs)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setFolderId($row->folder_id)
			->setDeleted($row->deleted);
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
            $entry = new Petolio_Model_PoMedicalRecordSubentries();
            $entry->setId($row->id)
				  ->setMedicalRecordId($row->medical_record_id)
				  ->setServiceId($row->service_id)
				  ->setOwnerId($row->owner_id)
				  ->setVisitDate($row->visit_date)
				  ->setHeadline1($row->headline1)
				  ->setHeadline2($row->headline2)
				  ->setDescription($row->description)
				  ->setRecommendation($row->recommendation)
				  ->setDrugs($row->drugs)
				  ->setDateCreated($row->date_created)
				  ->setDateModified($row->date_modified)
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
                    $entry = new Petolio_Model_PoMedicalRecordSubentries();
		            $entry->setId($row->id)
						  ->setMedicalRecordId($row->medical_record_id)
						  ->setServiceId($row->service_id)
						  ->setOwnerId($row->owner_id)
						  ->setVisitDate($row->visit_date)
						  ->setHeadline1($row->headline1)
						  ->setHeadline2($row->headline2)
						  ->setDescription($row->description)
						  ->setRecommendation($row->recommendation)
						  ->setDrugs($row->drugs)
						  ->setDateCreated($row->date_created)
						  ->setDateModified($row->date_modified)
						  ->setFolderId($row->folder_id)
						  ->setDeleted($row->deleted)
						  			  ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

    /**
     * finds row by primary key
     * sets the medical record object too
     * @param int $id
     * @param Petolio_Model_PoMedicalRecordSubentries $cls
     */
    public function findWithMedicalRecord($id, $cls) {
        $result = $this->getDbTable()->findWithMedicalRecord($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $medical_record = new Petolio_Model_PoMedicalRecords();
        $medical_record->setId($row->mr_id)
			->setPetId($row->mr_pet_id)
			->setHeadline1($row->mr_headline1)
			->setHeadline2($row->mr_headline2)
			->setDescription($row->mr_description)
			->setStartDate($row->mr_start_date)
			->setEndDate($row->mr_end_date)
			->setFolderId($row->mr_folder_id)
			->setOwnerId($row->mr_owner_id)
			->setDateCreated($row->mr_date_created)
			->setDateModified($row->mr_date_modified)
			->setDeleted($row->mr_deleted);

        $cls->setId($row->id)
			->setMedicalRecordId($row->medical_record_id)
			->setServiceId($row->service_id)
			->setOwnerId($row->owner_id)
			->setVisitDate($row->visit_date)
			->setHeadline1($row->headline1)
			->setHeadline2($row->headline2)
			->setDescription($row->description)
			->setRecommendation($row->recommendation)
			->setDrugs($row->drugs)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDeleted($row->deleted)
			->setFolderId($row->folder_id)
			->setMedicalRecord($medical_record);
    }

    /**
	 * fetches all rows optionally filtered by where,order,count and offset
	 * the select contain service and owner informations
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchWithReferences($where = null, $order = null, $count = null, $offset = null) {
		$resultSet = $this->getDbTable()->fetchWithReferences($where, $order, $count, $offset);
		$entries   = array();
		foreach ($resultSet as $row) {

			$owner = new Petolio_Model_PoUsers();
			$owner->setId($row->owner_id)
		        ->setFirstName($row->owner_first_name)
		        ->setLastName($row->owner_last_name)
				->setName($row->owner_name)
				->setEmail($row->owner_email)
				->setPassword($row->owner_password)
				->setActive($row->owner_active)
				->setStreet($row->owner_street)
				->setAddress($row->owner_address)
				->setZipcode($row->owner_zipcode)
				->setLocation($row->owner_location)
				->setCountryId($row->owner_country_id)
				->setPhone($row->owner_phone)
				->setPrivatePhone($row->owner_private_phone)
				->setBusinessPhone($row->owner_business_phone)
				->setPrivateFax($row->owner_private_fax)
				->setBusinessFax($row->owner_business_fax)
				->setHomepage($row->owner_homepage)
				->setGender($row->owner_gender)
				->setDateOfBirth($row->owner_date_of_birth)
				->setGpsLatitude($row->owner_gps_latitude)
				->setGpsLongitude($row->owner_gps_longitude)
				->setDateCreated($row->owner_date_created)
				->setDateModified($row->owner_date_modified)
				->setDateForgot($row->owner_date_forgot)
				->setType($row->owner_type)
				->setCategoryId($row->owner_category_id)
				->setAvatar($row->owner_avatar);

			$entry = new Petolio_Model_PoMedicalRecordSubentries();
			$entry->setId($row->id)
				  ->setMedicalRecordId($row->medical_record_id)
				  ->setServiceId($row->service_id)
				  ->setOwnerId($row->owner_id)
				  ->setVisitDate($row->visit_date)
				  ->setHeadline1($row->headline1)
				  ->setHeadline2($row->headline2)
				  ->setDescription($row->description)
				  ->setRecommendation($row->recommendation)
				  ->setDrugs($row->drugs)
				  ->setDateCreated($row->date_created)
				  ->setDateModified($row->date_modified)
				  ->setDeleted($row->deleted)
				  ->setServiceName($row->service_name) // service name
				  ->setOwner($owner) // subentry owner
				  ->setFolderId($row->folder_id)
				  ->setMapper($this);

			$entries[] = $entry;
		}

		return $entries;
	}

}
