<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoCostDefinitions extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_AttributeSetId;
    
    /**
     * mysql var type tinyint(4)
     *
     * @var int     
     */
    protected $_CostType;
    
    /**
     * mysql var type double(15,4)
     *
     * @var      
     */
    protected $_Value;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'attribute_set_id'=>'AttributeSetId',
    'cost_type'=>'CostType',
    'value'=>'Value',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCostDefinitions     
     *
     **/

    public function setId($data)
    {
        $this->_Id=$data;
        return $this;
    }

    /**
     * gets column id type bigint(20)
     * @return int     
     */
     
    public function getId()
    {
        return $this->_Id;
    }
    
    /**
     * sets column attribute_set_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCostDefinitions     
     *
     **/

    public function setAttributeSetId($data)
    {
        $this->_AttributeSetId=$data;
        return $this;
    }

    /**
     * gets column attribute_set_id type bigint(20)
     * @return int     
     */
     
    public function getAttributeSetId()
    {
        return $this->_AttributeSetId;
    }
    
    /**
     * gets the attribute set name for the current attribute set id
     * @return string
     */
    public function getAttributeSetName() {
    	if ( !$this->getAttributeSetId() ) {
            throw new Exception('Attribute set id it\'s not set');
    	}
    	$attr_set = new Petolio_Model_PoAttributeSets();
    	$attr_set->getMapper()->find($this->getAttributeSetId(), $attr_set);
    	return $attr_set->getName();
    }

    /**
     * sets column cost_type type tinyint(4)     
     *
     * @param int $data
     * @return Petolio_Model_PoCostDefinitions     
     *
     **/

    public function setCostType($data)
    {
        $this->_CostType=$data;
        return $this;
    }

    /**
     * gets column cost_type type tinyint(4)
     * @return int     
     */
     
    public function getCostType()
    {
        return $this->_CostType;
    }
    
    /**
     * sets column value type double(15,4)     
     *
     * @param  $data
     * @return Petolio_Model_PoCostDefinitions     
     *
     **/

    public function setValue($data)
    {
        $this->_Value=$data;
        return $this;
    }

    /**
     * gets column value type double(15,4)
     * @return      
     */
     
    public function getValue()
    {
        return $this->_Value;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoCostDefinitionsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoCostDefinitionsMapper());
        }
        return $this->_mapper;
    }


    /**
     * deletes current row by deleting a row that matches the primary key
     * 
     * @return int
     */

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

}

