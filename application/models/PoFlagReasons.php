<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFlagReasons extends MainModel
{
    protected $_Id;
    protected $_Value;
    protected $_ParentId;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'value' => 'value',
		    'parent_id' => 'ParentId',
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setValue($data) { $this->_Value = $data; return $this; }
    public function getValue() { return $this->_Value; }

    public function setParentId($data) { $this->_ParentId = $data; return $this; }
    public function getParentId() { return $this->_ParentId; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFlagReasonsMapper());
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