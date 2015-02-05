<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFieldRightUsers extends MainModel
{
    protected $_Id;
    protected $_FieldRightId;
    protected $_UserId;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'field_rights_id' => 'FieldRightId',
		    'user_id' => 'UserId'
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setFieldRightId($data) { $this->_FieldRightId = $data; return $this; }
    public function getFieldRightId() { return $this->_FieldRightId; }

    public function setUserId($data) { $this->_UserId = $data; return $this; }
    public function getUserId() { return $this->_UserId; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFieldRightUsersMapper());
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