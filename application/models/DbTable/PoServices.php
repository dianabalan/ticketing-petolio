<?php

class Petolio_Model_DbTable_PoServices extends Zend_Db_Table_Abstract {

	protected $_name = 'po_services';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoServiceMemberPets',
			'PoServiceMemberUsers',
			'PoPayments',
			'PoMedicalRecordSubentries'
		);

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
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

?>