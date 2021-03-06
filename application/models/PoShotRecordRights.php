<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */


class Petolio_Model_PoShotRecordRights extends MainModel
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
    protected $_ShotRecordId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_UserId;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'shot_record_id'=>'ShotRecordId',
    'user_id'=>'UserId',
    ));
}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordRights
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
     * sets column shot_record_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordRights
     *
     **/

    public function setShotRecordId($data)
    {
        $this->_ShotRecordId=$data;
        return $this;
    }

    /**
     * gets column shot_record_id type bigint(20)
     * @return int
     */

    public function getShotRecordId()
    {
        return $this->_ShotRecordId;
    }

    /**
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordRights
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
     * @return Petolio_Model_PoShotRecordRightsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoShotRecordRightsMapper());
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