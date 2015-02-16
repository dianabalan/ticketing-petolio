<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoCalendarAttendees extends MainModel
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
    protected $_CalendarId;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_UserId;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Type;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Status;
    

    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'calendar_id'=>'CalendarId',
    'user_id'=>'UserId',
    'type'=>'Type',
    'status'=>'Status',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCalendarAttendees     
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
     * sets column calendar_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCalendarAttendees     
     *
     **/

    public function setCalendarId($data)
    {
        $this->_CalendarId=$data;
        return $this;
    }

    /**
     * gets column calendar_id type bigint(20)
     * @return int     
     */
     
    public function getCalendarId()
    {
        return $this->_CalendarId;
    }
    
    /**
     * sets column user_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoCalendarAttendees     
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
     * sets column type type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoCalendarAttendees     
     *
     **/

    public function setType($data)
    {
        $this->_Type=$data;
        return $this;
    }

    /**
     * gets column type type tinyint(1)
     * @return int     
     */
     
    public function getType()
    {
        return $this->_Type;
    }
    
    /**
     * sets column status type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoCalendarAttendees     
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
     * returns the mapper class
     *
     * @return Petolio_Model_PoCalendarAttendeesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoCalendarAttendeesMapper());
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

