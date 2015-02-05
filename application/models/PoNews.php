<?php

require_once('MainModel.php');

class Petolio_Model_PoNews extends MainModel {
	protected $_Id;
	protected $_Title;
	protected $_Url;
	protected $_DateCreated;
	protected $_DateCached;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'title' => 'Title',
			'url' => 'Url',
			'date_created' => 'DateCreated',
			'date_cached' => 'DateCached'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setTitle($data) { $this->_Title = $data; return $this; }
	public function getTitle() { return $this->_Title; }

	public function setUrl($data) { $this->_Url = $data; return $this; }
	public function getUrl() { return $this->_Url; }

	public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
	public function getDateCreated() { return $this->_DateCreated; }

	public function setDateCached($data) { $this->_DateCached = $data; return $this; }
	public function getDateCached() { return $this->_DateCached; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoNewsMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}