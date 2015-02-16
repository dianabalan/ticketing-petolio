<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoCurrencies extends MainModel
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
     * mysql var type varchar(20)
     *
     * @var string
     */
    protected $_Code;
    
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'code'=>'Code',
    'name'=>'Name',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCurrencies     
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
     * @param int $data
     * @return Petolio_Model_PoCurrencies     
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
     * sets column code type varchar(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCurrencies     
     *
     **/

    public function setCode($data)
    {
        $this->_Code=$data;
        return $this;
    }

    /**
     * gets column code type varchar(20)
     * @return string     
     */
     
    public function getCode()
    {
        return $this->_Code;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoCurrenciesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoCurrenciesMapper());
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

