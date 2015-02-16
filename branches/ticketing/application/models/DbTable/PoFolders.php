<?php

class Petolio_Model_DbTable_PoFolders extends Zend_Db_Table_Abstract {

    protected $_name = 'po_folders';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoFiles',
			'PoMedicalRecords',
			'PoMicrosites'
	);

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('owner_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
        )
	);

	public function findFolders($params = false, $array = false)
	{
		$select = $this->select()
			->from($this->_name);

		if(array_key_exists('id', $params))
        	$select->where($this->getAdapter()->quoteInto("id = ?", $params['id'], Zend_Db::BIGINT_TYPE));

        if(array_key_exists('name', $params))
        	$select->where($this->getAdapter()->quoteInto("name = ?", $params['name']));

        if(array_key_exists('petId', $params))
        	$select->where($this->getAdapter()->quoteInto("pet_id = ?", $params['petId'], Zend_Db::BIGINT_TYPE));

        if(array_key_exists('ownerId', $params))
        	$select->where($this->getAdapter()->quoteInto("owner_id = ?", $params['ownerId'], Zend_Db::BIGINT_TYPE));

        if(array_key_exists('parentId', $params))
        	$select->where($this->getAdapter()->quoteInto("parent_id = ?", $params['parentId'], Zend_Db::BIGINT_TYPE));

        $found = $this->fetchAll($select);
        if(count(reset($found)) == 0)
        	return null;

		$out = array();
		foreach($found as $line) {
			$entry = new Petolio_Model_PoFolders();
			$entry->setId($line->id)
				->setName($line->name)
				->setParentId($line->parent_id)
				->setOwnerId($line->owner_id)
				->setPetId($line->pet_id)
				->setMapper(new Petolio_Model_PoFoldersMapper());

			$out[] = $entry;
		}

		return count($out) == 1 && $array != true ? reset($out) : $out;
	}

	public function addFolder($params = false)
	{
		if($params == false)
			return null;

		$res = new Petolio_Model_PoFolders();
		$res->setOptions($params);
		if ( !$res->getTraceback() ) {
			if ( $res->getParentId() && intval($res->getParentId()) > 0 ) {
				$parent = new Petolio_Model_PoFolders();
				$parent->find($res->getParentId());

				$traceback = $parent->getTraceback() ? explode(',', $parent->getTraceback()) : array();
				$traceback[] = $parent->getId();
				$res->setTraceback(implode(',', $traceback));
			}
		}
		$res->save();

		return $res;
	}
}