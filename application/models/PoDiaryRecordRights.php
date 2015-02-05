<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */


class Petolio_Model_PoDiaryRecordRights extends MainModel
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
    protected $_DiaryRecordId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_UserId;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'diary_record_id'=>'DiaryRecordId',
    'user_id'=>'UserId',
    ));
}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoDiaryRecordRights
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
     * sets column diary_record_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoDiaryRecordRights
     *
     **/

    public function setDiaryRecordId($data)
    {
        $this->_DiaryRecordId=$data;
        return $this;
    }

    /**
     * gets column diary_record_id type bigint(20)
     * @return int
     */

    public function getDiaryRecordId()
    {
        return $this->_DiaryRecordId;
    }

    /**
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoDiaryRecordRights
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

    public function getuserId()
    {
        return $this->_UserId;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoDiaryRecordRightsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoDiaryRecordRightsMapper());
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