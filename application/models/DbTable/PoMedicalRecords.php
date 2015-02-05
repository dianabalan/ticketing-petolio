<?php

class Petolio_Model_DbTable_PoMedicalRecords extends Zend_Db_Table_Abstract {

    protected $_name = 'po_medical_records';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoMedicalRecordSubentries'
		);

	protected $_referenceMap    = array(
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
        ),
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
		$select->where("deleted != 1");
		return $select;
	}

    /**
     * fetches all rows optionally filtered by where,order,count and offset
     * counts the users who are access to view this medical records
     *
     * @param string $where
     * @param string $order
     * @param int $count
     * @param int $offset
     */
	public function fetchListWithRightsCount($where=null, $order=null, $count=null, $offset=null) {

		if (!($where instanceof Zend_Db_Table_Select)) {

			$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => $this->_name))
				->joinLeft(array('b' => 'po_medical_record_rights'), 'a.id = b.medical_record_id', array('users' => 'COUNT(b.id)'));

			if ($where !== null) {
				$this->_where($select, $where);
			}
			if ($order !== null) {
				$this->_order($select, "a.".$order);
			}
			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}
			$select->group("a.id");
		} else {
			$select = $where;
		}
		$select->where("deleted != 1");
		return $select;
	}
}

?>