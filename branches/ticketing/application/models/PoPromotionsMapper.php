<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoPromotionsMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoPromotions
     *
     * @var Petolio_Model_DbTable_PoPromotions
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoPromotions $cls
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
				->setEventId($row->event_id)
				->setTemplateId($row->template_id)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setActive($row->active)
				->setFlagged($row->flagged);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoPromotions $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(

            'id' => $cls->getId(),
            'user_id' => $cls->getUserId(),
            'event_id' => $cls->getEventId(),
        	'template_id' => $cls->getTemplateId(),
        	'date_created' => $cls->getDateCreated(),
        	'date_modified' => $cls->getDateModified(),
        	'active'=> $cls->getActive(),
            'flagged' => $cls->getFlagged()
        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoPromotions $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoPromotions();
                    $result[]=$cls;
                    $cls->setId($row->id)
						->setUserId($row->user_id)
						->setEventId($row->event_id)
						->setTemplateId($row->template_id)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setActive($row->active)
						->setFlagged($row->flagged);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoPromotions $dbTable
     * @return Petolio_Model_PoPromotionsMapper
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
     * @return Petolio_Model_DbTable_PoPromotions
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoPromotions');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoPromotions $cls
     *
     */

    public function save(Petolio_Model_PoPromotions $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoPromotions $cls
     */

    public function find($id, Petolio_Model_PoPromotions $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setUserId($row->user_id)
			->setEventId($row->event_id)
			->setTemplateId($row->template_id)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setActive($row->active)
			->setFlagged($row->flagged);
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
            $entry = new Petolio_Model_PoPromotions();
            $entry->setId($row->id)
                  ->setUserId($row->user_id)
                  ->setEventId($row->event_id)
				  ->setTemplateId($row->template_id)
				  ->setDateCreated($row->date_created)
				  ->setDateModified($row->date_modified)
				  ->setActive($row->active)
				  ->setFlagged($row->flagged)
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
                    $entry = new Petolio_Model_PoPromotions();
                    $entry->setId($row->id)
                          ->setUserId($row->user_id)
                          ->setEventId($row->event_id)
						  ->setTemplateId($row->template_id)
						  ->setDateCreated($row->date_created)
						  ->setDateModified($row->date_modified)
						  ->setActive($row->active)
						  ->setFlagged($row->flagged)
                          ->setMapper($this);

                    $entries[] = $entry;
            }
            return $entries;
    }
}