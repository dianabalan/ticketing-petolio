<?php

class Petolio_Model_DbTable_PoHelp extends Zend_Db_Table_Abstract {

	protected $_name = 'po_help';
	protected $_primary = 'id';

	protected $_dependentTables = array(
		'PoFolders',
	);

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
			),
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
			),
        'PoMedicalRecords' => array(
            'columns'           => array('pet_medical_id'),
            'refTableClass'     => 'PoMedicalRecords',
            'refColumns'        => array('id')
			),
        'PoAttributeSets' => array(
            'columns'           => array('attribute_set_id'),
            'refTableClass'     => 'PoAttributeSets',
            'refColumns'        => array('id')
			),
        'PoFolders' => array(
            'columns'           => array('folder_id'),
            'refTableClass'     => 'PoFolders',
            'refColumns'        => array('id')
			)
		);

	public function countAll() {
		$db = $this->getAdapter();
		$select = $db->query("SELECT COUNT(*) AS questions FROM po_help WHERE archived = '0'");
		$found = $select->fetchAll();
        $found = reset($found);

        return $found['questions'];
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
}