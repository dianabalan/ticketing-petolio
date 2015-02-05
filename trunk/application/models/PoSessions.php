<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoSessions extends MainModel
{
    protected $_Id;
    protected $_Modified;
    protected $_Lifetime;
    protected $_Data;

	function __construct() {
	    $this->setColumnsList(array(
		    'id'=>'Id',
		  	'modified' => 'Modified',
	    	'lifetime' => 'Lifetime',
	    	'data' => 'Data'
	    ));
	}

    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setModified($data) { $this->_Modified = $data; return $this; }
    public function getModified() { return $this->_Modified; }

    public function setLifetime($data) { $this->_Lifetime = $data; return $this; }
    public function getLifetime() { return $this->_Lifetime; }

    public function setData($data) { $this->_Data = $data; return $this; }
    public function getData() { return $this->_Data; }

    public function getMapper()
    {
    	if (null === $this->_mapper)
    		$this->setMapper(new Petolio_Model_PoSessionsMapper());

    	return $this->_mapper;
    }

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete("id = '{$this->getId()}'");
    }
}