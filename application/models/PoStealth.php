<?php

require_once('MainModel.php');

class Petolio_Model_PoStealth extends MainModel {
	protected $_Id;
	protected $_FromId;
	protected $_ToId;

	function __construct() {
		$this->setColumnsList(array(
			'id' => 'Id',
			'from_id' => 'FromId',
			'to_id' => 'ToId'
		));
	}

	public function setId($data) { $this->_Id = $data; return $this; }
	public function getId() { return $this->_Id; }

	public function setFromId($data) { $this->_FromId = $data; return $this; }
	public function getFromId() { return $this->_FromId; }

	public function setToId($data) { $this->_ToId = $data; return $this; }
	public function getToId() { return $this->_ToId; }

	public function getMapper() {
		if(is_null($this->_mapper))
			$this->setMapper(new Petolio_Model_PoStealthMapper());

		return $this->_mapper;
	}

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete("id = {$this->getId()}");
    }
}