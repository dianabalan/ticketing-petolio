<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoMessageRecipients extends MainModel
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
    protected $_ToUserId;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Status;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_MessageId;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'to_user_id'=>'ToUserId',
    'status'=>'Status',
    'message_id'=>'MessageId',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessageRecipients     
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
     * sets column to_user_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessageRecipients     
     *
     **/

    public function setToUserId($data)
    {
        $this->_ToUserId=$data;
        return $this;
    }

    /**
     * gets column to_user_id type bigint(20)
     * @return int     
     */
     
    public function getToUserId()
    {
        return $this->_ToUserId;
    }
    
    /**
     * sets column status type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessageRecipients     
     *
     **/

    public function setStatus($data)
    {
        $this->_Status=$data;
        return $this;
    }

    /**
     * gets column status type tinyint(1)
     * @return int     
     */
     
    public function getStatus()
    {
        return $this->_Status;
    }
    
    /**
     * sets column message_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessageRecipients     
     *
     **/

    public function setMessageId($data)
    {
        $this->_MessageId=$data;
        return $this;
    }

    /**
     * gets column message_id type bigint(20)
     * @return int     
     */
     
    public function getMessageId()
    {
        return $this->_MessageId;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoMessageRecipientsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoMessageRecipientsMapper());
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

