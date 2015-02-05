<?php

class Petolio_Model_DbTable_PoTranslations extends Zend_Db_Table_Abstract {

    protected $_name = 'po_translations';
	protected $_primary = 'id';

	protected $_referenceMap = array();

	/**
	 * fetches all rows optionally filtered by where, order, count and offset
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchList($where=null, $order=null, $count=null, $offset=null)
	{
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name));

		if(!is_null($where)) $this->_where($select, $where);
		if(!is_null($order)) $this->_order($select, $order);
		if(!is_null($count) || !is_null($offset)) $select->limit($count, $offset);

		return $select;
	}
}