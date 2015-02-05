<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoHelpMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoHelp
     *
     * @var Petolio_Model_DbTable_PoHelp
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoHelp $cls
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
				->setFlagged($row->flagged)
				->setPetId($row->pet_id)
				->setPetMedicalId($row->pet_medical_id)
				->setFolderId($row->folder_id)
				->setRights($row->rights)
				->setViews($row->views)
				->setArchived($row->archived);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoHelp $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
            'user_id' => $cls->getUserId(),
            'attribute_set_id' => $cls->getAttributeSetId(),
            'date_created' => $cls->getDateCreated(),
            'date_modified' => $cls->getDateModified(),
        	'flagged' => $cls->getFlagged(),
            'pet_id' => $cls->getPetId(),
            'pet_medical_id' => $cls->getPetMedicalId(),
        	'folder_id' => $cls->getFolderId(),
        	'rights' => $cls->getRights(),
        	'views' => $cls->getViews(),
            'archived' => $cls->getArchived()
        );
        return $result;
    }

    public function toObject($array) {
    	$cls = new Petolio_Model_PoHelp();

        return $cls->setId($array['id'])
			->setUserId($array['user_id'])
			->setAttributeSetId($array['attribute_set_id'])
			->setDateCreated($array['date_created'])
			->setDateModified($array['date_modified'])
			->setFlagged($array['flagged'])
			->setPetId($array['pet_id'])
			->setPetMedicalId($array['pet_medical_id'])
			->setFolderId($array['folder_id'])
			->setRights($array['rights'])
			->setViews($array['views'])
			->setArchived($array['archived']);
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoHelp $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoHelp();
                    $result[]=$cls;
                    $cls->setId($row->id)
						->setUserId($row->user_id)
						->setAttributeSetId($row->attribute_set_id)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setFlagged($row->flagged)
						->setPetId($row->pet_id)
						->setPetMedicalId($row->pet_medical_id)
						->setFolderId($row->folder_id)
						->setRights($row->rights)
						->setViews($row->views)
						->setArchived($row->archived);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoHelp $dbTable
     * @return Petolio_Model_PoHelpMapper
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
     * @return Petolio_Model_DbTable_PoHelp
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoHelp');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoHelp $cls
     *
     */

    public function save(Petolio_Model_PoHelp $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key=>$value) {
                if (!isset($value) or strlen($value) <= 0) {
                    unset($data[$key]);
                }
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
     * @param Petolio_Model_PoHelp $cls
     */

    public function find($id, Petolio_Model_PoHelp $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setUserId($row->user_id)
			->setAttributeSetId($row->attribute_set_id)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setFlagged($row->flagged)
			->setPetId($row->pet_id)
			->setPetMedicalId($row->pet_medical_id)
			->setFolderId($row->folder_id)
			->setRights($row->rights)
			->setViews($row->views)
			->setArchived($row->archived);
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
            $entry = new Petolio_Model_PoHelp();
            $entry->setId($row->id)
                  ->setUserId($row->user_id)
                  ->setAttributeSetId($row->attribute_set_id)
                  ->setDateCreated($row->date_created)
                  ->setDateModified($row->date_modified)
					->setFlagged($row->flagged)
					->setPetId($row->pet_id)
					->setPetMedicalId($row->pet_medical_id)
					->setFolderId($row->folder_id)
					->setRights($row->rights)
					->setViews($row->views)
                  ->setArchived($row->archived)
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
                    $entry = new Petolio_Model_PoHelp();
                    $entry->setId($row->id)
                          ->setUserId($row->user_id)
                          ->setAttributeSetId($row->attribute_set_id)
                          ->setDateCreated($row->date_created)
                          ->setDateModified($row->date_modified)
							->setFlagged($row->flagged)
							->setPetId($row->pet_id)
							->setPetMedicalId($row->pet_medical_id)
							->setFolderId($row->folder_id)
							->setRights($row->rights)
							->setViews($row->views)
                          ->setArchived($row->archived)
                          ->setMapper($this);

                    $entries[] = $entry;
            }
            return $entries;
    }

	/**
	 * Find the privacy value
	 * @param int $x - entry id
	 * @param int $user - user id
	 * @param bool $return - i forgot what this is for :P
	 */
	public function findPrivacySetting($x, $user, $return = false) {
		$res = reset($this->fetchList("id = '{$x}'"));

		if($return) return $res ? array((int)$res->getId(), (int)$res->getRights()) : array(null, 0);
		else return $res ? (int)$res->getRights() : 0;
	}

	/**
	 * Set privacy setting
	 * @param int $x - entry id
	 * @param int $d - rights value (0 - all | 1 - friends | 2 - service providers)
	 * @param int $user - user id
	 * @param class $rights - the rights object (po_help_rights)
	 */
	public function setPrivacySetting($x, $d, $user, $rights) {
		// find entry
		$res = reset($this->fetchList("id = '{$x}' AND user_id = '{$user}'"));
		if(!$res)
			return false;

		// delete all the users associated with it
		$rights->getMapper()->deleteCustomUser($res->getId());

		// set the rights and save
		$res->setRights($d);
		$res->save();
	}

	/**
	 * Get custom users
	 * @param int $x - entry id
	 * @param int $d - rights value (0 - all | 1 - friends | 2 - service providers)
	 * @param int $user - user id
	 * @param class $rights - the rights object (po_help_rights)
	 */
	public function findCustomUsers($x, $d, $user, $rights) {
		$res = reset($this->fetchList("id = '{$x}' AND user_id = '{$user}' AND rights = '{$d}'"));
		return !$res ? false : $rights->getMapper()->getCustomUsers($res->getId());
	}
}