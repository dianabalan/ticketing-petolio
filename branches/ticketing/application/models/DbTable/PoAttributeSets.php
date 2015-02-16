<?php

class Petolio_Model_DbTable_PoAttributeSets extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_sets';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAttributes',
			'PoPets',
			'PoPedigree',
			'PoServices',
			'PoCostDefinitions',
			'PoContentDistributions'
	);

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
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_attributes'), 'a.id = b.attribute_set_id', array('nr' => 'COUNT(b.id)'));

		if(!is_null($where)) $this->_where($select, $where);
		if(!is_null($order)) $this->_order($select, $order);
		if(!is_null($count) || !is_null($offset)) $select->limit($count, $offset);

		$select->group("a.id");
		return $select;
	}

	public function listAttributeSets() {
		$select = $this->select()
			->from($this->_name)
			->where("active = 1")
			->order("scope")
			->group('scope');

		return $this->fetchAll($select);
	}

	public function getAttributeSets($scope) {
		if(!$scope)
			return null;

		$select = $this->select()
			->from($this->_name)
        	->where($this->getAdapter()->quoteInto("scope = ?", $scope))
        	->where("active = 1")
        	->order("name");

		return $this->fetchAll($select);
	}

	public function getAttributeSetsWithPetCount($where) {
		$select = $this->select()->setIntegrityCheck(false)
			->from(
				array('a' => $this->_name),
				array('*', 'pet_count' => new Zend_Db_Expr('COUNT(p.id)')))
			->joinInner(
				array('p' => 'po_pets'),
				'a.id = p.attribute_set_id',
				array())
			->joinInner(
				array('u' => 'po_users'),
				'p.user_id = u.id',
				array())
			->group('a.id')
        	->where("p.deleted != 1")
        	// show only active users
        	->where("u.active = 1 AND u.is_banned != 1")
        	->order("a.name");

        if ( $where ) {
        	$select->where($where);
        }
		return $this->fetchAll($select);
	}
}