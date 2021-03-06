<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoShotRecords extends MainModel
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
    protected $_PetId;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Sickness;

    /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Description;

    /**
     * mysql var type date
     *
     * @var date
     */
    protected $_Date;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_OwnerId;

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
     * mysql var type tinyint(4)
     *
     * @var int
     */
    protected $_Rights;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Deleted;

    /**
     * number how many users have access to view the shot record
     * this number is relevant only when the rights are:
     * 		2: partners
     * 		3: friends
     * 		4: users
     *
     * @var int
     */
    protected $_Users_Rights_Count;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'pet_id'=>'PetId',
    'sickness'=>'Sickness',
    'description'=>'Description',
    'date'=>'Date',
    'owner_id'=>'OwnerId',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'rights'=>'Rights',
    'deleted'=>'Deleted'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecords
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
     * sets column pet_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecords
     *
     **/

    public function setPetId($data)
    {
        $this->_PetId=$data;
        return $this;
    }

    /**
     * gets column pet_id type bigint(20)
     * @return int
     */

    public function getPetId()
    {
        return $this->_PetId;
    }

    /**
     * sets column sickness type varchar
     *
     * @param string $data
     * @return Petolio_Model_PoShotRecords
     *
     **/
    public function setSickness($data)
    {
        $this->_Sickness=$data;
        return $this;
    }

    /**
     * gets column sickness type varchar
     * @return string
     */
    public function getSickness()
    {
        return $this->_Sickness;
    }

    /**
     * sets column description type text
     *
     * @param text $data
     * @return Petolio_Model_PoShotRecords
     *
     **/
    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column description type text
     * @return text
     */
    public function getDescription()
    {
        return $this->_Description;
    }

    /**
     * sets column date type date
     *
     * @param date $data
     * @return Petolio_Model_PoShotRecords
     *
     **/
    public function setDate($data)
    {
        $this->_Date = $data;
        return $this;
    }

    /**
     * gets column date type date
     * @return date
     */

    public function getDate()
    {
        return $this->_Date;
    }

    /**
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecords
     *
     **/

    public function setOwnerId($data)
    {
        $this->_OwnerId=$data;
        return $this;
    }

    /**
     * gets column owner_id type bigint(20)
     * @return int
     */

    public function getOwnerId()
    {
        return $this->_OwnerId;
    }

    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoShotRecords
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
     * @return Petolio_Model_PoShotRecords
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
     * sets column rights type tinyint(4)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecords
     *
     **/

    public function setRights($data)
    {
        $this->_Rights=$data;
        return $this;
    }

    /**
     * gets column rights type tinyint(4)
     * @return int
     */

    public function getRights()
    {
        return $this->_Rights;
    }

    /**
     * sets column deleted type tinyint(4)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecords
     *
     **/

    public function setDeleted($data)
    {
        $this->_Deleted=$data;
        return $this;
    }

    /**
     * gets column deleted type tinyint(4)
     * @return int
     */

    public function getDeleted()
    {
        return $this->_Deleted;
    }

    /**
     * set the _Users_Rights_Count
     *
     * @param int $data
     */
    public function setUsersRightsCount($data)
    {
    	$this->_Users_Rights_Count = $data;
    	return $this;
    }

    /**
     * gets the _Users_Rights_Count
     * @return int
     */
    public function getUsersRightsCount()
    {
    	return $this->_Users_Rights_Count;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoShotRecordsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoShotRecordsMapper());
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