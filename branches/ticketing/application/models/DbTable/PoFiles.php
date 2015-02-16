<?php

class Petolio_Model_DbTable_PoFiles extends Zend_Db_Table_Abstract {

    protected $_name = 'po_files';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoFolders' => array(
            'columns'           => array('folder_id'),
            'refTableClass'     => 'PoFolders',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('owner_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

	/**
	 * fetches all rows optionally filtered by where,order,count and offset
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchList($where=null, $order=null, $count=null, $offset=null) {
		if (!($where instanceof Zend_Db_Table_Select)) {
			$select = $this->select();
			if ($where !== null) {
				$this->_where($select, $where);
			}
			if ($order !== null) {
				$this->_order($select, $order);
			}
			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}
		} else {
			$select = $where;
		}
		return $select;
	}

	public function findFiles($params = false, $array = false)
	{
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_file_rights'), 'a.id = b.file_id', array('users' => 'COUNT(b.id)'));

		if(isset($params['id']))
        	$select->where($this->getAdapter()->quoteInto("a.id = ?", $params['id'], Zend_Db::BIGINT_TYPE));

		if(isset($params['name']))
        	$select->where($this->getAdapter()->quoteInto("a.file = ?", $params['name']));

		if(isset($params['ownerId']))
        	$select->where($this->getAdapter()->quoteInto("a.owner_id = ?", $params['ownerId'], Zend_Db::BIGINT_TYPE));

		if(isset($params['folderId']))
        	$select->where($this->getAdapter()->quoteInto("a.folder_id = ?", $params['folderId'], Zend_Db::BIGINT_TYPE));

        $select->group("a.id");
        $found = $this->fetchAll($select);
        if(count(reset($found)) == 0)
        	return null;

		$out = array();
		foreach($found as $line) {
			$entry = new Petolio_Model_PoFiles();
            $entry->setId($line->id)
				->setFile($line->file)
				->setType($line->type)
				->setSize($line->size)
				->setFolderId($line->folder_id)
				->setDateCreated($line->date_created)
				->setDateModified($line->date_modified)
				->setOwnerId($line->owner_id)
				->setStatus($line->status)
				->setDescription($line->description)
				->setRights($line->rights . "|" . $line->users)
				->setMapper(new Petolio_Model_PoFilesMapper());

			$out[] = $entry;
		}

		return count($out) == 1 && $array != true ? reset($out) : $out;
	}
}

?>