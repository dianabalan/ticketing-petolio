<?php

class Petolio_Model_DbTable_PoRss extends Zend_Db_Table_Abstract {

    protected $_name = 'po_rss';
	protected $_primary = 'id';

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
			->from(array($this->_name));

		if(!is_null($where)) $this->_where($select, $where);
		if(!is_null($order)) $this->_order($select, $order);
		if(!is_null($count) || !is_null($offset)) $select->limit($count, $offset);

		return $select;
	}
}