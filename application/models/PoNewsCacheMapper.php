<?php

class Petolio_Model_PoNewsCacheMapper {
	protected $_dbTable;

	public function toArray($cls) {
		return array(
			'id' => $cls->getId(),
			'news_id' => $cls->getNewsId(),
			'title' => $cls->getTitle(),
			'link' => $cls->getLink(),
			'description' => $cls->getDescription(),
			'pubDate' => $cls->getPubDate(),
			'category' => $cls->getCategory(),
			'author' => $cls->getAuthor(),
			'guid' => $cls->getGuid(),
			'viewed' => $cls->getViewed()
		);
	}

	public function find($id, Petolio_Model_PoNewsCache $cls) {
		$result = $this->getDbTable()->find($id);
		if(count($result) == 0)
			return;

		$row = $result->current();
		$cls->setId($row->id)
			->setNewsId($row->news_id)
			->setTitle($row->title)
			->setLink($row->link)
			->setDescription($row->description)
			->setPubDate($row->pubDate)
			->setCategory($row->category)
			->setAuthor($row->author)
			->setGuid($row->guid)
			->setViewed($row->viewed);
	}

	public function fetchAll() {
		$entries = array();
		foreach($this->getDbTable()->fetchAll() as $row) {
			$cls = new Petolio_Model_PoNewsCache();
			$cls->setId($row->id)
				->setNewsId($row->news_id)
				->setTitle($row->title)
				->setLink($row->link)
				->setDescription($row->description)
				->setPubDate($row->pubDate)
				->setCategory($row->category)
				->setAuthor($row->author)
				->setGuid($row->guid)
				->setViewed($row->viewed)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function fetchList($where = null, $order = null, $count = null, $offset = null) {
		$entries = array();
		foreach($this->getDbTable()->fetchAll($where, $order, $count, $offset) as $row) {
			$cls = new Petolio_Model_PoNewsCache();
			$cls->setId($row->id)
				->setNewsId($row->news_id)
				->setTitle($row->title)
				->setLink($row->link)
				->setDescription($row->description)
				->setPubDate($row->pubDate)
				->setCategory($row->category)
				->setAuthor($row->author)
				->setGuid($row->guid)
				->setViewed($row->viewed)
				->setMapper($this);
			$entries[] = $cls;
		}

		return $entries;
	}

	public function findOneByField($field, $value, Petolio_Model_PoNewsCache $cls) {
		$table = $this->getDbTable();
		$row = $table->fetchRow($table->select()->where("{$field} = ?", $value));
		if(count($row) == 0)
			return;

		$cls->setId($row->id)
			->setNewsId($row->news_id)
			->setTitle($row->title)
			->setLink($row->link)
			->setDescription($row->description)
			->setPubDate($row->pubDate)
			->setCategory($row->category)
			->setAuthor($row->author)
			->setGuid($row->guid)
			->setViewed($row->viewed);

		return $cls;
	}

	public function findByField($field, $value, $cls) {
		$result = array();
		$table = $this->getDbTable();
		foreach($table->fetchAll($table->select()->where("{$field} = ?", $value)) as $row) {
			$cls = new Petolio_Model_PoNewsCache();
			$cls->setId($row->id)
				->setNewsId($row->news_id)
				->setTitle($row->title)
				->setLink($row->link)
				->setDescription($row->description)
				->setPubDate($row->pubDate)
				->setCategory($row->category)
				->setAuthor($row->author)
				->setGuid($row->guid)
				->setViewed($row->viewed);
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
			$this->setDbTable('Petolio_Model_DbTable_PoNewsCache');

		return $this->_dbTable;
	}

	public function save(Petolio_Model_PoNewsCache $cls, $ignoreEmpty = true, $escapeValues = false) {
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