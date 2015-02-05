<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */


class Petolio_Model_PoShotRecordSubentries extends MainModel
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
    protected $_ServiceId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_OwnerId;

	protected $_Immunization;

    /**
     * mysql var type date
     *
     * @var date
     */
    protected $_ReminderDate;

    /**
     * mysql var type date
     *
     * @var string
     */
    protected $_InoculationDate;

    /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Description;

    /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Recommendation;

        /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Drugs;

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
     * the name of the service if it's any set
     *
     * @var string
     */
	protected $_ServiceName;

	/**
	 * the shot record obj if it's any set
	 *
	 * @var Petolio_Model_PoShotRecords
	 */
	protected $_ShotRecord;

	/**
	 * the owner obj if it's any set
	 *
	 * @var Petolio_Model_PoUsers
	 */
	protected $_Owner;

	/**
	 * mysql var type tinyint(4)
	 *
	 * @var int
	 */
	protected $_Deleted;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'shot_record_id'=>'ShotRecordId',
    'service_id'=>'ServiceId',
    'owner_id'=>'OwnerId',
    'immunization' => 'Immunization',
    'reminder_date'=>'ReminderDate',
    'inoculation_date'=>'InoculationDate',
    'description'=>'Description',
    'recommendation'=>'Recommendation',
    'drugs'=>'Drugs',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'deleted'=>'Deleted',
    ));
}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordSubentries
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
     * @return Petolio_Model_PoShotRecordSubentries
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
     * sets column service_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordSubentries
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
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordSubentries
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

    public function setImmunization($data)
    {
        $this->_Immunization=$data;
        return $this;
    }

    public function getImmunization()
    {
        return $this->_Immunization;
    }

    /**
     * sets column reminder_date type date
     *
     * @param string $data
     * @return Petolio_Model_PoShotRecordSubentries
     *
     **/

    public function setReminderDate($data)
    {
        $this->_ReminderDate=$data;
        return $this;
    }

    /**
     * gets column reminder_date type date
     * @return string
     */

    public function getReminderDate()
    {
        return $this->_ReminderDate;
    }

    /**
     * sets column inoculation_date type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoShotRecordSubentries
     *
     **/
    public function setInoculationDate($data)
    {
        $this->_InoculationDate=$data;
        return $this;
    }

    /**
     * gets column inoculation_date type varchar(200)
     * @return string
     */
    public function getInoculationDate()
    {
        return $this->_InoculationDate;
    }

    /**
     * sets column description type text
     *
     * @param text $data
     * @return Petolio_Model_PoShotRecordSubentries
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
     * sets column recommendation type text
     *
     * @param text $data
     * @return Petolio_Model_PoShotRecordSubentries
     *
     **/
    public function setRecommendation($data)
    {
        $this->_Recommendation=$data;
        return $this;
    }

    /**
     * gets column recommendation type text
     * @return text
     */
    public function getRecommendation()
    {
        return $this->_Recommendation;
    }

    /**
     * sets column drugs type text
     *
     * @param text $data
     * @return Petolio_Model_PoShotRecordSubentries
     *
     **/
    public function setDrugs($data)
    {
        $this->_Drugs=$data;
        return $this;
    }

    /**
     * gets column drugs type text
     * @return text
     */
    public function getDrugs()
    {
        return $this->_Drugs;
    }

    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoShotRecordSubentries
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
     * @return Petolio_Model_PoShotRecordSubentries
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
     * sets column deleted type tinyint(4)
     *
     * @param int $data
     * @return Petolio_Model_PoShotRecordSubentries
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
     * sets service name
     * @param string $data
     * @return Petolio_Model_PoShotRecordSubentries
     */
    public function setServiceName($data) {
    	$this->_ServiceName = $data;
    	return $this;
    }

    /**
     * gets service name if it's any set
     * @return string
     */
    public function getServiceName() {
    	return $this->_ServiceName;
    }

    /**
     * sets shot record
     * @param Petolio_Model_PoShotRecords $data
     * @return Petolio_Model_PoShotRecordSubentries
     * @throws Exception
     */
    public function setShotRecord($data = null) {
		if ( !isset($data) ) {
			if ( !$this->getShotRecordId() ) {
				throw new Exception('Shot record id it\'s not set');
			}
			$data = new Petolio_Model_PoShotRecords();
			$data->find($this->getShotRecordId());
		}
		if ( !$data instanceof Petolio_Model_PoShotRecords ) {
			throw new Exception('Invalid instance for $data object, Petolio_Model_PoShotRecords expected.');
		}
		$this->_ShotRecord = $data;
		return $this;
    }

    /**
     * gets shot record
     * @return Petolio_Model_PoShotRecords
     */
    public function getShotRecord() {
		if ( !isset($this->_ShotRecord) ) {
			$this->setShotRecord();
		}
		return $this->_ShotRecord;
    }

    /**
     * sets owner
     * @param Petolio_Model_PoUsers $data
     * @return Petolio_Model_PoShotRecordSubentries
     * @throws Exception
     */
    public function setOwner($data = null) {
		if ( !isset($data) ) {
			if ( !$this->getOwnerId() ) {
				throw new Exception('Owner id it\'s not set');
			}
			$data = new Petolio_Model_PoUsers();
			$data->find($this->getOwnerId());
		}
		if ( !$data instanceof Petolio_Model_PoUsers ) {
			throw new Exception('Invalid instance for $data object, Petolio_Model_PoUsers expected.');
		}
		$this->_Owner = $data;
		return $this;
    }

    /**
     * gets owner
     * @return Petolio_Model_PoUsers
     */
    public function getOwner() {
		if ( !isset($this->_Owner) ) {
			$this->setOwner();
		}
		return $this->_Owner;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoShotRecordSubentriesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoShotRecordSubentriesMapper());
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

    /**
     * finds row by id
     *
     * @param <?=$this->_primaryKey['phptype']?> $id
     * @return Petolio_Model_PoShotRecordSubentries
     */
    public function findWithShotRecord($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
}