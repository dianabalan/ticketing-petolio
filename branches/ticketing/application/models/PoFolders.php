<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFolders extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Name;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_ParentId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_OwnerId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_PetId;

    protected $_Traceback;
	protected $_DateCreated;


function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'name'=>'Name',
	    'parent_id'=>'ParentId',
	    'owner_id'=>'OwnerId',
	    'pet_id'=>'PetId',
	    'traceback'=>'Traceback',
	    'date_created'=>'DateCreated'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFolders
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
     * sets column name type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoFolders
     *
     **/

    public function setName($data)
    {
        $this->_Name=$data;
        return $this;
    }

    /**
     * gets column name type varchar(100)
     * @return string
     */

    public function getName()
    {
        return $this->_Name;
    }

    /**
     * sets column parent_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFolders
     *
     **/

    public function setParentId($data)
    {
        $this->_ParentId=$data;
        return $this;
    }

    /**
     * gets column parent_id type bigint(20)
     * @return int
     */

    public function getParentId()
    {
        return $this->_ParentId;
    }

    /**
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFolders
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
     * sets column pet_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFolders
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

    public function setTraceback($data) { $this->_Traceback = $data; return $this; }
    public function getTraceback() { return $this->_Traceback; }

    public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
    public function getDateCreated() { return $this->_DateCreated; }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoFoldersMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFoldersMapper());
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

