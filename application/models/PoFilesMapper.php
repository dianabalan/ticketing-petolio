<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFilesMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoFiles
     *
     * @var Petolio_Model_DbTable_PoFiles
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFiles $cls
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
		->setFile($row->file)
		->setType($row->type)
		->setSize($row->size)
		->setFolderId($row->folder_id)
		->setDateCreated($row->date_created)
		->setDateModified($row->date_modified)
		->setOwnerId($row->owner_id)
		->setStatus($row->status)
		->setDescription($row->description)
		->setRights($row->rights);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoFiles $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(

            'id' => $cls->getId(),
            'file' => $cls->getFile(),
            'type' => $cls->getType(),
            'size' => $cls->getSize(),
            'folder_id' => $cls->getFolderId(),
            'date_created' => $cls->getDateCreated(),
            'date_modified' => $cls->getDateModified(),
            'owner_id' => $cls->getOwnerId(),
            'status' => $cls->getStatus(),
            'description' => $cls->getDescription(),
        	'rights' => $cls->getRights(),

        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoFiles $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoFiles();
                    $result[]=$cls;
                    $cls->setId($row->id)
		->setFile($row->file)
		->setType($row->type)
		->setSize($row->size)
		->setFolderId($row->folder_id)
		->setDateCreated($row->date_created)
		->setDateModified($row->date_modified)
		->setOwnerId($row->owner_id)
		->setStatus($row->status)
		->setDescription($row->description)
		->setRights($row->rights);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoFiles $dbTable
     * @return Petolio_Model_PoFilesMapper
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
     * @return Petolio_Model_DbTable_PoFiles
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoFiles');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoFiles $cls
     *
     */

    public function save(Petolio_Model_PoFiles $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
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
     * @param Petolio_Model_PoFiles $cls
     */

    public function find($id, Petolio_Model_PoFiles $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
		->setFile($row->file)
		->setType($row->type)
		->setSize($row->size)
		->setFolderId($row->folder_id)
		->setDateCreated($row->date_created)
		->setDateModified($row->date_modified)
		->setOwnerId($row->owner_id)
		->setStatus($row->status)
		->setDescription($row->description)
		->setRights($row->rights);
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
            $entry = new Petolio_Model_PoFiles();
            $entry->setId($row->id)
                  ->setFile($row->file)
                  ->setType($row->type)
                  ->setSize($row->size)
                  ->setFolderId($row->folder_id)
                  ->setDateCreated($row->date_created)
                  ->setDateModified($row->date_modified)
                  ->setOwnerId($row->owner_id)
                  ->setStatus($row->status)
                  ->setDescription($row->description)
                  ->setRights($row->rights)
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
                    $entry = new Petolio_Model_PoFiles();
                    $entry->setId($row->id)
                          ->setFile($row->file)
                          ->setType($row->type)
                          ->setSize($row->size)
                          ->setFolderId($row->folder_id)
                          ->setDateCreated($row->date_created)
                          ->setDateModified($row->date_modified)
                          ->setOwnerId($row->owner_id)
                          ->setStatus($row->status)
                          ->setDescription($row->description)
                          ->setRights($row->rights)
                          ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

	/**
	 * Get file list complete with the number of users selected
	 *  - including filter and sorting options
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getFiles($where = false, $order = false, $limit = false) {
		$db = $this->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_files'))
			->joinLeft(array('b' => 'po_file_rights'), 'a.id = b.file_id', array('users' => 'COUNT(b.id)'))
			->group("a.id");

		// filter and sort and limit ? ok
		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit);

		// return either array or paginator
		return $db->fetchAll($select)->toArray();
	}

	/**
	 * Get file list complete the folder
	 *  - including filter and sorting options
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getFilesWFolders($where = false, $order = false, $limit = false) {
		$db = $this->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_files'))
			->joinLeft(array('b' => 'po_folders'), 'a.folder_id = b.id', array('folder_name' => 'b.name', 'folder_pet_id' => 'b.pet_id'));

		// filter and sort and limit ? ok
		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit);

		// return either array or paginator
		return $db->fetchAll($select)->toArray();
	}

	/**
	 * Get all the pictures videos audios from the file going up the tree
	 * - used on resources
	 *
	 * @param int $id User Id
	 */
	public function resourceLevel($id) {
		$db = $this->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			// origin
			->from(array('a' => 'po_files'), array('id', 'type', 'description', 'folder_id', 'file'))
			->joinLeft(array('b' => 'po_folders'), 'a.folder_id = b.id', array('folder_name' => 'b.name'))

			// pets
			->joinLeft(array('c' => 'po_pets'), 'b.pet_id = c.id', array('pet_id' => 'id'))
			->joinLeft(array('x1' => 'po_attributes'), "x1.attribute_set_id = c.attribute_set_id AND SUBSTRING(x1.code, -5) = '_name'", array())
			->joinLeft(array('x2' => 'po_attribute_entity_varchar'), "x2.attribute_id = x1.id AND c.id = x2.entity_id", array('pet_name' => 'value'))

			// services
			->joinLeft(array('d' => 'po_services'), 'b.id = d.folder_id', array('service_id' => 'id'))
			->joinLeft(array('y1' => 'po_attributes'), "y1.attribute_set_id = d.attribute_set_id AND SUBSTRING(y1.code, -5) = '_name'", array())
			->joinLeft(array('y2' => 'po_attribute_entity_varchar'), "y2.attribute_id = y1.id AND d.id = y2.entity_id", array('service_name' => 'value'))

			// microsites
			->joinLeft(array('e' => 'po_microsites'), 'b.id = e.folder_id', array('microsite_id' => 'id', 'microsite_name' => 'url'))

			// galleries
			->joinLeft(array('f' => 'po_galleries'), 'b.id = f.folder_id', array('gallery_id' => 'id', 'gallery_name' => 'title'))

			// condotion
			->where("a.owner_id = {$id} AND (a.type = 'image' OR a.type = 'video' OR a.type = 'audio')");

		// return either array or paginator
		return $db->fetchAll($select)->toArray();
	}
}