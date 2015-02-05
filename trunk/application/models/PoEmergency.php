<?php

require_once('MainModel.php');

class Petolio_Model_PoEmergency extends MainModel {
	protected $_Id;
	protected $_Scope;
	protected $_EntityId;
	protected $_FirstName;
	protected $_LastName;
	protected $_Category;
	protected $_Phone;
	protected $_Email;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'scope' => 'Scope',
			'entity_id' => 'EntityId',
			'first_name' => 'FirstName',
			'last_name' => 'LastName',
			'category' => 'Category',
			'phone' => 'Phone',
			'email' => 'Email'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setScope($data) { $this->_Scope = $data; return $this; }
	public function getScope() { return $this->_Scope; }

	public function setEntityId($data) { $this->_EntityId = $data; return $this; }
	public function getEntityId() { return $this->_EntityId; }

	public function setFirstName($data) { $this->_FirstName = $data; return $this; }
	public function getFirstName() { return $this->_FirstName; }

	public function setLastName($data) { $this->_LastName = $data; return $this; }
	public function getLastName() { return $this->_LastName; }

	public function setCategory($data) { $this->_Category = $data; return $this; }
	public function getCategory() { return $this->_Category; }

	public function setPhone($data) { $this->_Phone = $data; return $this; }
	public function getPhone() { return $this->_Phone; }

	public function setEmail($data) { $this->_Email = $data; return $this; }
	public function getEmail() { return $this->_Email; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoEmergencyMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}