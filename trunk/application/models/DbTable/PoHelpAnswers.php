<?php

class Petolio_Model_DbTable_PoHelpAnswers extends Zend_Db_Table_Abstract {

	protected $_name = 'po_help_answers';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoHelp' => array(
            'columns'           => array('help_id'),
            'refTableClass'     => 'PoHelp',
            'refColumns'        => array('id')
			),
        'PoUsers' => array(
            'columns'           => array('user_id'),
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
}