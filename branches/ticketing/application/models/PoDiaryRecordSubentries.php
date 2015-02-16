<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */


class Petolio_Model_PoDiaryRecordSubentries extends MainModel
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
    protected $_OwnerId;

    /**
     * mysql var type date
     *
     * @var date
     */
    protected $_Date;

	protected $_FolderId;

    /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Description;

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
	 * the diary record obj if it's any set
	 *
	 * @var Petolio_Model_PoDiaryRecords
	 */
	protected $_DiaryRecord;

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
    'diary_record_id'=>'DiaryRecordId',
    'owner_id'=>'OwnerId',
    'date'=>'Date',
    'folder_id'=>'FolderId',
    'description'=>'Description',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'deleted'=>'Deleted',
    ));
}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * sets column date type date
     *
     * @param string $data
     * @return Petolio_Model_PoDiaryRecordSubentries
     *
     **/

    public function setDate($data)
    {
        $this->_Date=$data;
        return $this;
    }

    /**
     * gets column date type date
     * @return string
     */

    public function getDate()
    {
        return $this->_Date;
    }

    public function setFolderId($data) { $this->_FolderId=$data; return $this; }
    public function getFolderId() { return $this->_FolderId; }

    /**
     * sets column description type text
     *
     * @param text $data
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * sets diary record
     * @param Petolio_Model_PoDiaryRecords $data
     * @return Petolio_Model_PoDiaryRecordSubentries
     * @throws Exception
     */
    public function setDiaryRecord($data = null) {
		if ( !isset($data) ) {
			if ( !$this->getDiaryRecordId() ) {
				throw new Exception('Diary record id it\'s not set');
			}
			$data = new Petolio_Model_PoDiaryRecords();
			$data->find($this->getDiaryRecordId());
		}
		if ( !$data instanceof Petolio_Model_PoDiaryRecords ) {
			throw new Exception('Invalid instance for $data object, Petolio_Model_PoDiaryRecords expected.');
		}
		$this->_DiaryRecord = $data;
		return $this;
    }

    /**
     * gets diary record
     * @return Petolio_Model_PoDiaryRecords
     */
    public function getDiaryRecord() {
		if ( !isset($this->_DiaryRecord) ) {
			$this->setDiaryRecord();
		}
		return $this->_DiaryRecord;
    }

    /**
     * sets owner
     * @param Petolio_Model_PoUsers $data
     * @return Petolio_Model_PoDiaryRecordSubentries
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
     * @return Petolio_Model_PoDiaryRecordSubentriesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoDiaryRecordSubentriesMapper());
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
     * @return Petolio_Model_PoDiaryRecordSubentries
     */
    public function findWithDiaryRecord($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
}