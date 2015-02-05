<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributeGroupEntities extends MainModel
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
    protected $_EntityId;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_GroupId;
    
    
    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'entity_id'=>'EntityId',
    'group_id'=>'GroupId',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeGroupEntities     
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
     * sets column entity_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeGroupEntities     
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
     * sets column group_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeGroupEntities     
     *
     **/

    public function setGroupId($data)
    {
        $this->_GroupId=$data;
        return $this;
    }

    /**
     * gets column group_id type bigint(20)
     * @return int     
     */
     
    public function getGroupId()
    {
        return $this->_GroupId;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributeGroupEntitiesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributeGroupEntitiesMapper());
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

