<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributeOptions extends MainModel
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
     * mysql var type varchar(200)
     *
     * @var string     
     */
    protected $_Value;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'attribute_id'=>'AttributeId',
    'value'=>'Value',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeOptions     
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
     * @return Petolio_Model_PoAttributeOptions     
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
     * sets column value type varchar(200)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeOptions     
     *
     **/

    public function setValue($data)
    {
        $this->_Value=$data;
        return $this;
    }

    /**
     * gets column value type varchar(200)
     * @return string     
     */
     
    public function getValue()
    {
        return $this->_Value;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributeOptionsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributeOptionsMapper());
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

