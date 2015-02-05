<?php

require_once('MainModel.php');

class Petolio_Model_PoComments extends MainModel {
	protected $_Id;
	protected $_UserId;
	protected $_Scope;
	protected $_EntityId;
	protected $_Text;
	protected $_DateCreated;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'user_id' => 'UserId',
			'scope' => 'Scope',
			'entity_id' => 'EntityId',
			'text' => 'Text',
			'date_created' => 'DateCreated'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setUserId($data) { $this->_UserId = $data; return $this; }
	public function getUserId() { return $this->_UserId; }

	public function setScope($data) { $this->_Scope = $data; return $this; }
	public function getScope() { return $this->_Scope; }

	public function setEntityId($data) { $this->_EntityId = $data; return $this; }
	public function getEntityId() { return $this->_EntityId; }

	public function setText($data) { $this->_Text = $data; return $this; }
	public function getText() { return $this->_Text; }

	public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
	public function getDateCreated() { return $this->_DateCreated; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoCommentsMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }

	/**
	 * Get comments joined with user as well
	 *
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 *
	 * @return array
	 */
	public function getComments($where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_comments'), array('*'))

			// get pet owner
			->joinLeft(array('b' => 'po_users'), "a.user_id = b.id", array('name', 'avatar'));

		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// return array
		return $db->fetchAll($select)->toArray();
	}
}