<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoFieldRights extends MainModel
{
    protected $_Id;
    protected $_FieldName;
    protected $_EntryId;
    protected $_Rights;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'field_name' => 'FieldName',
		    'entry_name' => 'EntryId',
		    'rights' => 'Rights'
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }
    
    public function setFieldName($data) { $this->_FieldName = $data; return $this; }
    public function getFieldName() { return $this->_FieldName; }
    
    public function setEntryId($data) { $this->_EntryId = $data; return $this; }
    public function getEntryId() { return $this->_EntryId; }
    
    public function setRights($data) { $this->_Rights = $data; return $this; }
    public function getRights() { return $this->_Rights; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFieldRightsMapper());
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