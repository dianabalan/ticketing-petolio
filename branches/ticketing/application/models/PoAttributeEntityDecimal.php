<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributeEntityDecimal extends MainModel
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
    protected $_AttributeId;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_EntityId;
    
    /**
     * mysql var type double(15,6)
     *
     * @var      
     */
    protected $_Value;
    
    /**
     * mysql var type varchar(200)
     * 
     * @var string
     */
    protected $_Description;
    
    protected $_Latin;
    
    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'attribute_id'=>'AttributeId',
    'entity_id'=>'EntityId',
    'value'=>'Value',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeEntityDecimal     
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
     * sets column attribute_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeEntityDecimal     
     *
     **/

    public function setAttributeId($data)
    {
        $this->_AttributeId=$data;
        return $this;
    }

    /**
     * gets column attribute_id type bigint(20)
     * @return int     
     */
     
    public function getAttributeId()
    {
        return $this->_AttributeId;
    }
    
    /**
     * sets column entity_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeEntityDecimal     
     *
     **/

    public function setEntityId($data)
    {
        $this->_EntityId=$data;
        return $this;
    }

    /**
     * gets column entity_id type bigint(20)
     * @return int     
     */
     
    public function getEntityId()
    {
        return $this->_EntityId;
    }
    
    /**
     * sets column value type double(15,6)     
     *
     * @param  $data
     * @return Petolio_Model_PoAttributeEntityDecimal     
     *
     **/

    public function setValue($data)
    {
        $this->_Value=$data;
        return $this;
    }

    /**
     * gets column value type double(15,6)
     * @return      
     */
     
    public function getValue()
    {
        return $this->_Value;
    }
    
    /**
     * sets column description type varchar(200)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeEntityDecimal     
     *
     **/
    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column description type varchar(200)
     * @return datetime     
     */
    public function getDescription()
    {
        return $this->_Description;
    }
    
    /**
     * sets the latin translation
     * @param string $value
     */
    public function setLatin($value = null) {
    	if ( isset($value) ) {
    		$this->_Latin = $value;
    	} else {
    		$this->_Latin = Petolio_Service_Util::Latin($this->getValue());
    	}
    	return $this;
    }
    
    /**
     * gets the latin translation of the value or null
     * @return string
     */
    public function getLatin() {
    	return $this->_Latin;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributeEntityDecimalMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributeEntityDecimalMapper());
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

