<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoTranslations extends MainModel
{
    protected $_Id;
    protected $_Language;
    protected $_Label;
    protected $_Value;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'language' => 'Language',
		    'label' => 'Label',
		    'value' => 'Value'
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setLanguage($data) { $this->_Language = $data; return $this; }
    public function getLanguage() { return $this->_Language; }

    public function setLabel($data) { $this->_Label = $data; return $this; }
    public function getLabel() { return $this->_Label; }

    public function setValue($data) { $this->_Value = $data; return $this; }
    public function getValue() { return $this->_Value; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoTranslationsMapper());
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