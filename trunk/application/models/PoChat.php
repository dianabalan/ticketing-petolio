<?php

require_once('MainModel.php');

class Petolio_Model_PoChat extends MainModel {
	protected $_Id;
	protected $_CalendarId;
	protected $_UserId;
	protected $_Message;
	protected $_DateCreated;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'calendar_id' => 'CalendarId',
			'user_id' => 'UserId',
			'message' => 'Message',
			'date_created' => 'DateCreated'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setCalendarId($data) { $this->_CalendarId = $data; return $this; }
	public function getCalendarId() { return $this->_CalendarId; }

	public function setUserId($data) { $this->_UserId = $data; return $this; }
	public function getUserId() { return $this->_UserId; }

	public function setMessage($data) { $this->_Message = $data; return $this; }
	public function getMessage() { return $this->_Message; }

	public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
	public function getDateCreated() { return $this->_DateCreated; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoChatMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }

	/**
	 * Get chat messages joined with the user as well
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
			->from(array('a' => 'po_chat'), array('id', 'message', 'date_created', 'user_id'))

			// get pet owner
			->joinLeft(array('b' => 'po_users'), "a.user_id = b.id", array('user_name' => 'name', 'user_avatar' => 'avatar'));

		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// return array
		return $db->fetchAll($select)->toArray();
	}

	/**
	 * Check for chat activity
	 * (2 different chat messages by 2 different users)
	 *
	 * @param string $where
	 * @return bool
	 */
	public function checkActivity($where) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('po_chat'), array('msg_count' => 'COUNT(distinct message)'));

		// sql props
		$select->where($where);
		$select->group('user_id');
		$select->having('msg_count > 1');
		$select->limit(2);

		// return array
		return count($db->fetchAll($select)->toArray()) == 2 ? true : false;
	}
}