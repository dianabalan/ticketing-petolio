<?php

require_once('MainModel.php');

class Petolio_Model_PoNewsCache extends MainModel {
	protected $_Id;
	protected $_NewsId;
	protected $_Title;
	protected $_Link;
	protected $_Description;
	protected $_PubDate;
	protected $_Category;
	protected $_Author;
	protected $_Guid;
	protected $_Viewed;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'news_id' => 'NewsId',
			'title' => 'Title',
			'link' => 'Link',
			'description' => 'Description',
			'pubDate' => 'PubDate',
			'category' => 'Category',
			'author' => 'Author',
			'guid' => 'Guid',
			'viewed' => 'Viewed'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setNewsId($data) { $this->_NewsId = $data; return $this; }
	public function getNewsId() { return $this->_NewsId; }

	public function setTitle($data) { $this->_Title = $data; return $this; }
	public function getTitle() { return $this->_Title; }

	public function setLink($data) { $this->_Link = $data; return $this; }
	public function getLink() { return $this->_Link; }

	public function setDescription($data) { $this->_Description = $data; return $this; }
	public function getDescription() { return $this->_Description; }

	public function setPubDate($data) { $this->_PubDate = $data; return $this; }
	public function getPubDate() { return $this->_PubDate; }

	public function setCategory($data) { $this->_Category = $data; return $this; }
	public function getCategory() { return $this->_Category; }

	public function setAuthor($data) { $this->_Author = $data; return $this; }
	public function getAuthor() { return $this->_Author; }

	public function setGuid($data) { $this->_Guid = $data; return $this; }
	public function getGuid() { return $this->_Guid; }

	public function setViewed($data) { $this->_Viewed = $data; return $this; }
	public function getViewed() { return $this->_Viewed; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoNewsCacheMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}