<?php

class Petolio_Model_PoRssMapper {
	protected $_dbTable;

	public function toArray($cls) {
		return array(
			'id' => $cls->getId(),
			'author' => $cls->getAuthor(),
			'title' => $cls->getTitle(),
			'description' => $cls->getDescription(),
			'link' => $cls->getLink(),
			'date_created' => $cls->getDateCreated()
		);
	}

	public function find($id, Petolio_Model_PoRss $cls) {
		$result = $this->getDbTable()->find($id);
		if(count($result) == 0)
			return;

		$row = $result->current();
		$cls->setId($row->id)
			->setAuthor($row->author)
			->setTitle($row->title)
			->setDescription($row->description)
			->setLink($row->link)
			->setDateCreated($row->date_created);
	}

	public function fetchAll() {
		$entries = array();
		foreach($this->getDbTable()->fetchAll() as $row) {
			$cls = new Petolio_Model_PoRss();
			$cls->setId($row->id)
				->setAuthor($row->author)
				->setTitle($row->title)
				->setDescription($row->description)
				->setLink($row->link)
				->setDateCreated($row->date_created)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function fetchList($where = null, $order = null, $count = null, $offset = null) {
		$entries = array();
		foreach($this->getDbTable()->fetchAll($where, $order, $count, $offset) as $row) {
			$cls = new Petolio_Model_PoRss();
			$cls->setId($row->id)
				->setAuthor($row->author)
				->setTitle($row->title)
				->setDescription($row->description)
				->setLink($row->link)
				->setDateCreated($row->date_created)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function findOneByField($field, $value, Petolio_Model_PoRss $cls) {
		$table = $this->getDbTable();
		$row = $table->fetchRow($table->select()->where("{$field} = ?", $value));
		if(count($row) == 0)
			return;

		$cls->setId($row->id)
			->setAuthor($row->author)
			->setTitle($row->title)
			->setDescription($row->description)
			->setLink($row->link)
			->setDateCreated($row->date_created);

		return $cls;
	}

	public function findByField($field, $value, $cls) {
		$result = array();
		$table = $this->getDbTable();
		foreach($table->fetchAll($table->select()->where("{$field} = ?", $value)) as $row) {
			$cls = new Petolio_Model_PoRss();
			$cls->setId($row->id)
				->setAuthor($row->author)
				->setTitle($row->title)
				->setDescription($row->description)
				->setLink($row->link)
				->setDateCreated($row->date_created);
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
			$this->setDbTable('Petolio_Model_DbTable_PoRss');

		return $this->_dbTable;
	}

	public function save(Petolio_Model_PoRss $cls, $ignoreEmpty = true, $escapeValues = false) {
		$data = $cls->toArray();
		if($ignoreEmpty)
			foreach($data as $key=>$value)
				if(!isset($value) or strlen($value) <= 0)
					unset($data[$key]);

        if ( $escapeValues ) {
	        foreach ($data as $key => $value) {
	        	if ( !($value instanceof Zend_Db_Expr) )
		        	$data[$key] = Petolio_Service_Util::escape($data[$key]);
	        }
        }

		if(($id = $cls->getId()) === null) {
			unset($data['id']);
			$id = $this->getDbTable()->insert($data);
			$cls->setId($id);
		} else
			$this->getDbTable()->update($data, array('id = ?' => $id));
	}
}