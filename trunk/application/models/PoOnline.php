<?php

require_once('MainModel.php');

class Petolio_Model_PoOnline extends MainModel {
	protected $_Id;
	protected $_FromId;
	protected $_ToId;
	protected $_Message;
	protected $_DateCreated;
	protected $_Status;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'from_id' => 'FromId',
			'to_id' => 'ToId',
			'message' => 'Message',
			'date_created' => 'DateCreated',
			'status' => 'Status'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setFromId($data) { $this->_FromId = $data; return $this; }
	public function getFromId() { return $this->_FromId; }

	public function setToId($data) { $this->_ToId = $data; return $this; }
	public function getToId() { return $this->_ToId; }

	public function setMessage($data) { $this->_Message = $data; return $this; }
	public function getMessage() { return $this->_Message; }

	public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
	public function getDateCreated() { return $this->_DateCreated; }

	public function setStatus($data) { $this->_Status = $data; return $this; }
	public function getStatus() { return $this->_Status; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoOnlineMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }

	/**
	 * Get online messages joined with the user as well
	 *
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 *
	 * @return array
	 */
	public function getMessages($where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_online'), array('id', 'message', 'date_created', 'from_id'))

			// get from owner
			->joinLeft(array('b' => 'po_users'), "a.from_id = b.id", array('from_name' => 'name', 'from_avatar' => 'avatar'));

		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// return array
		return $db->fetchAll($select)->toArray();
	}

	/**
	 * Get unread messages
	 *
	 * @param string $where
	 * @return array
	 */
	public function getUnread($where = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('po_online'), array('from_id'));

		if($where) $select->where($where);
		$select->order("id DESC");
		$select->group("from_id");

		// return array
		return $db->fetchAll($select)->toArray();
	}
}