<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoMessages extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;
    
    /**
     * mysql var type varchar(200)
     *
     * @var string     
     */
    protected $_Subject;
    
    /**
     * mysql var type text
     *
     * @var text     
     */
    protected $_Message;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_FromUserId;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Status;
    
    protected $_DraftTo;
    
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
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_ParentMessageId;
    
    /**
     * mysql var type timestamp
     *
     * @var string     
     */
    protected $_DateSent;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'subject'=>'Subject',
    'message'=>'Message',
    'from_user_id'=>'FromUserId',
    'status'=>'Status',
    'draft_to'=>'DraftTo',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'parent_message_id'=>'ParentMessageId',
    'date_sent'=>'DateSent',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessages     
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
     * sets column subject type varchar(200)     
     *
     * @param string $data
     * @return Petolio_Model_PoMessages     
     *
     **/

    public function setSubject($data)
    {
        $this->_Subject=$data;
        return $this;
    }

    /**
     * gets column subject type varchar(200)
     * @return string     
     */
     
    public function getSubject()
    {
        return $this->_Subject;
    }
    
    /**
     * sets column message type text     
     *
     * @param text $data
     * @return Petolio_Model_PoMessages     
     *
     **/

    public function setMessage($data)
    {
        $this->_Message=$data;
        return $this;
    }

    /**
     * gets column message type text
     * @return text     
     */
     
    public function getMessage()
    {
        return $this->_Message;
    }
    
    /**
     * sets column from_user_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessages     
     *
     **/

    public function setFromUserId($data)
    {
        $this->_FromUserId=$data;
        return $this;
    }

    /**
     * gets column from_user_id type bigint(20)
     * @return int     
     */
     
    public function getFromUserId()
    {
        return $this->_FromUserId;
    }
    
    /**
     * sets column status type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessages     
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

    public function setDraftTo($data)
    {
        $this->_DraftTo=$data;
        return $this;
    }

    public function getDraftTo()
    {
        return $this->_DraftTo;
    }
    
    /**
     * sets column date_created type timestamp     
     *
     * @param string $data
     * @return Petolio_Model_PoMessages     
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
     * @return Petolio_Model_PoMessages     
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
     * sets column parent_message_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoMessages     
     *
     **/

    public function setParentMessageId($data)
    {
        $this->_ParentMessageId=$data;
        return $this;
    }

    /**
     * gets column parent_message_id type bigint(20)
     * @return int     
     */
     
    public function getParentMessageId()
    {
        return $this->_ParentMessageId;
    }
    
    /**
     * sets column date_sent type timestamp     
     *
     * @param string $data
     * @return Petolio_Model_PoMessages     
     *
     **/

    public function setDateSent($data)
    {
        $this->_DateSent=$data;
        return $this;
    }

    /**
     * gets column date_sent type timestamp
     * @return string     
     */
     
    public function getDateSent()
    {
        return $this->_DateSent;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoMessagesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoMessagesMapper());
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

