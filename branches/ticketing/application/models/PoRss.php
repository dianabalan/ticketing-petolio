<?php

require_once('MainModel.php');

class Petolio_Model_PoRss extends MainModel {
	protected $_Id;
	protected $_Author;
	protected $_Title;
	protected $_Description;
	protected $_Link;
	protected $_DateCreated;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'author' => 'Author',
			'title' => 'Title',
			'description' => 'Description',
			'link' => 'Link',
			'date_created' => 'DateCreated'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setAuthor($data) { $this->_Author = $data; return $this; }
	public function getAuthor() { return $this->_Author; }

	public function setTitle($data) { $this->_Title = $data; return $this; }
	public function getTitle() { return $this->_Title; }

	public function setDescription($data) { $this->_Description = $data; return $this; }
	public function getDescription() { return $this->_Description; }

	public function setLink($data) { $this->_Link = $data; return $this; }
	public function getLink() { return $this->_Link; }

	public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
	public function getDateCreated() { return $this->_DateCreated; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoRssMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}