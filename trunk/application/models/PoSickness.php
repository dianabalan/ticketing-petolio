<?php

require_once('MainModel.php');

class Petolio_Model_PoSickness extends MainModel {
	protected $_Id;
	protected $_Name;
	protected $_Scope;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'name' => 'Name',
			'scope' => 'Scope'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setName($data) { $this->_Name = $data; return $this; }
	public function getName() { return $this->_Name; }

	public function setScope($data) { $this->_Scope = $data; return $this; }
	public function getScope() { return $this->_Scope; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoSicknessMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}