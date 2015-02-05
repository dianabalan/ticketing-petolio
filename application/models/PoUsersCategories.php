<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoUsersCategories extends MainModel
{
    protected $_Id;
    protected $_Name;
    protected $_ParentId;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'name' => 'Name',
		    'parent_id' => 'ParentId',
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setName($data) { $this->_Name = $data; return $this; }
    public function getName() { return $this->_Name; }

    public function setParentId($data) { $this->_ParentId = $data; return $this; }
    public function getParentId() { return $this->_ParentId; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoUsersCategoriesMapper());
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