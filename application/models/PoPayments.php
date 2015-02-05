<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoPayments extends MainModel
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
    protected $_UserId;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_ServiceId;
    
    /**
     * mysql var type double(15,4)
     *
     * @var      
     */
    protected $_Value;
    
    /**
     * mysql var type timestamp
     *
     * @var string     
     */
    protected $_DateCreated;
    
    /**
     * mysql var type timestamp
     *
     * @var string     
     */
    protected $_DateModified;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'user_id'=>'UserId',
    'service_id'=>'ServiceId',
    'value'=>'Value',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoPayments     
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
     * sets column user_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoPayments     
     *
     **/

    public function setUserId($data)
    {
        $this->_UserId=$data;
        return $this;
    }

    /**
     * gets column user_id type bigint(20)
     * @return int     
     */
     
    public function getUserId()
    {
        return $this->_UserId;
    }
    
    /**
     * sets column service_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoPayments     
     *
     **/

    public function setServiceId($data)
    {
        $this->_ServiceId=$data;
        return $this;
    }

    /**
     * gets column service_id type bigint(20)
     * @return int     
     */
     
    public function getServiceId()
    {
        return $this->_ServiceId;
    }
    
    /**
     * sets column value type double(15,4)     
     *
     * @param  $data
     * @return Petolio_Model_PoPayments     
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
     * sets column date_created type timestamp     
     *
     * @param string $data
     * @return Petolio_Model_PoPayments     
     *
     **/

    public function setDateCreated($data)
    {
        $this->_DateCreated=$data;
        return $this;
    }

    /**
     * gets column date_created type timestamp
     * @return string     
     */
     
    public function getDateCreated()
    {
        return $this->_DateCreated;
    }
    
    /**
     * sets column date_modified type timestamp     
     *
     * @param string $data
     * @return Petolio_Model_PoPayments     
     *
     **/

    public function setDateModified($data)
    {
        $this->_DateModified=$data;
        return $this;
    }

    /**
     * gets column date_modified type timestamp
     * @return string     
     */
     
    public function getDateModified()
    {
        return $this->_DateModified;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoPaymentsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoPaymentsMapper());
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

