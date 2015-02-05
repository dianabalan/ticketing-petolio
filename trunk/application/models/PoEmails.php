<?php
require_once('MainModel.php');

class Petolio_Model_PoEmails extends MainModel
{
    protected $_Id;
    protected $_Status;
    protected $_Serialized;
    protected $_Priority;
    protected $_DateCreated;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
		    'status' => 'Status',
	    	'serialized' => 'Serialized',
	    	'priority' => 'Priority',
		    'date_created' => 'DateCreated'
	    ));
	}

    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setStatus($data) { $this->_Status = $data; return $this; }
    public function getStatus() { return $this->_Status; }

    public function setSerialized($data) { $this->_Serialized = $data; return $this; }
    public function getSerialized() { return $this->_Serialized; }

    public function setPriority($data) { $this->_Priority = $data; return $this; }
    public function getPriority() { return $this->_Priority; }

    public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
    public function getDateCreated() { return $this->_DateCreated; }

    public function getMapper() {
        if ($this->_mapper === null)
            $this->setMapper(new Petolio_Model_PoEmailsMapper());

        return $this->_mapper;
    }

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }
}