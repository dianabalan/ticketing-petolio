<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributeInputTypes extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Name;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Type;
    
    /**
     * mysql var type varchar(200)
     *
     * @var string     
     */
    protected $_Description;
    
    

function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'name'=>'Name',
	    'type'=>'Type',
	    'description'=>'Description',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeInputTypes     
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
     * sets column name type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeInputTypes     
     *
     **/

    public function setName($data)
    {
        $this->_Name=$data;
        return $this;
    }

    /**
     * gets column name type varchar(100)
     * @return string     
     */
     
    public function getName()
    {
        return $this->_Name;
    }
    
    /**
     * sets column type type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeInputTypes     
     *
     **/

    public function setType($data)
    {
        $this->_Type=$data;
        return $this;
    }

    /**
     * gets column type type varchar(100)
     * @return string     
     */
     
    public function getType()
    {
        return $this->_Type;
    }
    
    /**
     * sets column description type varchar(200)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeInputTypes     
     *
     **/

    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column sescription type varchar(200)
     * @return string     
     */
     
    public function getDescription()
    {
        return $this->_Description;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributeInputTypesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributeInputTypesMapper());
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

