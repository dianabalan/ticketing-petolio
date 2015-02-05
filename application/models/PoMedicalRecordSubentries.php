<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */


class Petolio_Model_PoMedicalRecordSubentries extends MainModel
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
    protected $_MedicalRecordId;

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
    protected $_FolderId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_OwnerId;

    /**
     * mysql var type date
     *
     * @var date
     */
    protected $_VisitDate;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Headline1;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Headline2;

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
	 * the medical record obj if it's any set
	 *
	 * @var Petolio_Model_PoMedicalRecords
	 */
	protected $_MedicalRecord;

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
    'medical_record_id'=>'MedicalRecordId',
    'service_id'=>'ServiceId',
    'owner_id'=>'OwnerId',
    'visit_date'=>'VisitDate',
    'headline1'=>'Headline1',
    'headline2'=>'Headline2',
    'description'=>'Description',
    'recommendation'=>'Recommendation',
    'drugs'=>'Drugs',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'folder_id'=>'FolderId',
    'deleted'=>'Deleted',
    ));
}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * sets column medical_record_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     *
     **/

    public function setMedicalRecordId($data)
    {
        $this->_MedicalRecordId=$data;
        return $this;
    }

    /**
     * gets column medical_record_id type bigint(20)
     * @return int
     */

    public function getMedicalRecordId()
    {
        return $this->_MedicalRecordId;
    }

    /**
     * sets column service_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * sets column folder_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     *
     **/

    public function setFolderId($data)
    {
        $this->_FolderId=$data;
        return $this;
    }

    /**
     * gets column folder_id type bigint(20)
     * @return int
     */

    public function getFolderId()
    {
        return $this->_FolderId;
    }

    /**
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * sets column visit_date type date
     *
     * @param string $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     *
     **/

    public function setVisitDate($data)
    {
        $this->_VisitDate=$data;
        return $this;
    }

    /**
     * gets column visit_date type date
     * @return string
     */

    public function getVisitDate()
    {
        return $this->_VisitDate;
    }

    /**
     * sets column headline1 type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     *
     **/
    public function setHeadline1($data)
    {
        $this->_Headline1=$data;
        return $this;
    }

    /**
     * gets column headline1 type varchar(200)
     * @return string
     */
    public function getHeadline1()
    {
        return $this->_Headline1;
    }

    /**
     * sets column headline2 type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     *
     **/
    public function setHeadline2($data)
    {
        $this->_Headline2=$data;
        return $this;
    }

    /**
     * gets column headline2 type varchar(200)
     * @return string
     */
    public function getHeadline2()
    {
        return $this->_Headline2;
    }

    /**
     * sets column description type text
     *
     * @param text $data
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * sets medical record
     * @param Petolio_Model_PoMedicalRecords $data
     * @return Petolio_Model_PoMedicalRecordSubentries
     * @throws Exception
     */
    public function setMedicalRecord($data = null) {
		if ( !isset($data) ) {
			if ( !$this->getMedicalRecordId() ) {
				throw new Exception('Medical record id it\'s not set');
			}
			$data = new Petolio_Model_PoMedicalRecords();
			$data->find($this->getMedicalRecordId());
		}
		if ( !$data instanceof Petolio_Model_PoMedicalRecords ) {
			throw new Exception('Invalid instance for $data object, Petolio_Model_PoMedicalRecords expected.');
		}
		$this->_MedicalRecord = $data;
		return $this;
    }

    /**
     * gets medical record
     * @return Petolio_Model_PoMedicalRecords
     */
    public function getMedicalRecord() {
		if ( !isset($this->_MedicalRecord) ) {
			$this->setMedicalRecord();
		}
		return $this->_MedicalRecord;
    }

    /**
     * sets owner
     * @param Petolio_Model_PoUsers $data
     * @return Petolio_Model_PoMedicalRecordSubentries
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
     * @return Petolio_Model_PoMedicalRecordSubentriesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoMedicalRecordSubentriesMapper());
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
     * @return Petolio_Model_PoMedicalRecordSubentries
     */
    public function findWithMedicalRecord($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }


}