<?php

class Petolio_Model_PoDashboardMapper {
	protected $_dbTable;

	public function toArray($cls) {
		return array(
			'id' => $cls->getId(),
			'user_id' => $cls->getUserId(),
			'date_created' => $cls->getDateCreated(),
			'data' => $cls->getData(),
			'serialized' => $cls->getSerialized(),
			'identity' => $cls->getIdentity(),
			'rights' => $cls->getRights(),
			'scope' => $cls->getScope(),
			'entity_id' => $cls->getEntityId(),
			'deleted' => $cls->getDeleted()
		);
	}

	public function find($id, Petolio_Model_PoDashboard $cls) {
		$result = $this->getDbTable()->find($id);
		if(count($result) == 0)
			return;

		$row = $result->current();
		$cls->setId($row->id)
			->setUserId($row->user_id)
			->setDateCreated($row->date_created)
			->setData($row->data)
			->setSerialized($row->serialized)
			->setIdentity($row->identity)
			->setRights($row->rights)
			->setScope($row->scope)
			->setEntityId($row->entity_id)
			->setDeleted($row->deleted);
	}

	public function fetchAll() {
		$entries = array();
		foreach($this->getDbTable()->fetchAll() as $row) {
			$cls = new Petolio_Model_PoDashboard();
			$cls->setId($row->id)
				->setUserId($row->user_id)
				->setDateCreated($row->date_created)
				->setData($row->data)
				->setSerialized($row->serialized)
				->setIdentity($row->identity)
				->setRights($row->rights)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setDeleted($row->deleted)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function fetchList($where = null, $order = null, $count = null, $offset = null) {
		$entries = array();
		foreach($this->getDbTable()->fetchAll($where, $order, $count, $offset) as $row) {
			$cls = new Petolio_Model_PoDashboard();
			$cls->setId($row->id)
				->setUserId($row->user_id)
				->setDateCreated($row->date_created)
				->setData($row->data)
				->setSerialized($row->serialized)
				->setIdentity($row->identity)
				->setRights($row->rights)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setDeleted($row->deleted)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function findOneByField($field, $value, Petolio_Model_PoDashboard $cls) {
		$table = $this->getDbTable();
		$row = $table->fetchRow($table->select()->where("{$field} = ?", $value));
		if(count($row) == 0)
			return;

		$cls->setId($row->id)
			->setUserId($row->user_id)
			->setDateCreated($row->date_created)
			->setData($row->data)
			->setSerialized($row->serialized)
			->setIdentity($row->identity)
			->setRights($row->rights)
			->setScope($row->scope)
			->setEntityId($row->entity_id)
			->setDeleted($row->deleted);

		return $cls;
	}

	public function findByField($field, $value, $cls) {
		$result = array();
		$table = $this->getDbTable();
		foreach($table->fetchAll($table->select()->where("{$field} = ?", $value)) as $row) {
			$cls = new Petolio_Model_PoDashboard();
			$cls->setId($row->id)
				->setUserId($row->user_id)
				->setDateCreated($row->date_created)
				->setData($row->data)
				->setSerialized($row->serialized)
				->setIdentity($row->identity)
				->setRights($row->rights)
				->setScope($row->scope)
				->setEntityId($row->entity_id)
				->setDeleted($row->deleted);
			$result[] = $cls;
		}

		return $result;
	}

	public function setDbTable($dbTable) {
		if(is_string($dbTable))
			$dbTable = new $dbTable();

		if(!$dbTable instanceof Zend_Db_Table_Abstract)
			throw new Exception('Invalid table data gateway provided');

		$this->_dbTable = $dbTable;
		return $this;
	}

	public function getDbTable() {
		if(is_null($this->_dbTable))
			$this->setDbTable('Petolio_Model_DbTable_PoDashboard');

		return $this->_dbTable;
	}

	public function save(Petolio_Model_PoDashboard $cls, $ignoreEmpty = true) {
		$data = $cls->toArray();
		if($ignoreEmpty)
			foreach($data as $key=>$value)
				if(!isset($value) or strlen($value) <= 0)
					unset($data[$key]);

		if(($id = $cls->getId()) === null) {
			unset($data['id']);
			$id = $this->getDbTable()->insert($data);
			$cls->setId($id);
		} else
			$this->getDbTable()->update($data, array('id = ?' => $id));
	}

	/**
	 * Find the privacy value
	 * @param int $x - entry id
	 * @param int $user - user id
	 * @param bool $return - i forgot what this is for :P
	 */
	public function findPrivacySetting($x, $user, $return = false) {
		$res = reset($this->fetchList("id = '{$x}'"));

		if($return) return $res ? array((int)$res->getId(), (int)$res->getRights()) : array(null, 0);
		else return $res ? (int)$res->getRights() : 0;
	}

	/**
	 * Set privacy setting
	 * @param int $x - entry id
	 * @param int $d - rights value (3 for custom)
	 * @param int $user - user id
	 * @param class $rights - the rights object (po_dashboard_rights)
	 */
	public function setPrivacySetting($x, $d, $user, $rights) {
		// find entry
		$res = reset($this->fetchList("id = '{$x}' AND user_id = '{$user}'"));
		if(!$res)
			return false;

		// delete all the users associated with it
		$rights->getMapper()->deleteCustomUser($res->getId());

		// set the rights and save
		$res->setRights($d);
		$res->save();
	}

	/**
	 * Get custom users
	 * @param int $x - entry id
	 * @param int $d - rights value (3 for custom)
	 * @param int $user - user id
	 * @param class $rights - the rights object (po_dashboard_rights)
	 */
	public function findCustomUsers($x, $d, $user, $rights) {
		$res = reset($this->fetchList("id = '{$x}' AND user_id = '{$user}' AND rights = '{$d}'"));
		return !$res ? false : $rights->getMapper()->getCustomUsers($res->getId());
	}
}