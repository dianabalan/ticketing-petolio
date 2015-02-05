<?php
require_once('MainModel.php');

class Petolio_Model_PoFileRights extends MainModel
{
    protected $_Id;
    protected $_FileId;
    protected $_UserId;

    function __construct() {
	    $this->setColumnsList(array(
	    	'id'=>'Id',
	    	'file_id'=>'FileId',
	    	'user_id'=>'UserId'
	    ));
	}

    public function setId($data)
    {
        $this->_Id=$data;
        return $this;
    }
    public function getId()
    {
        return $this->_Id;
    }
    
    public function setFileId($data)
    {
        $this->_FileId=$data;
        return $this;
    }
    public function getFileId()
    {
        return $this->_FileId;
    }
    
    public function setUserId($data)
    {
        $this->_UserId=$data;
        return $this;
    }
    public function getUserId()
    {
        return $this->_UserId;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFileRightsMapper());
        }
        return $this->_mapper;
    }
    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }
}