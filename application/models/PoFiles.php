<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFiles extends MainModel
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
    protected $_File;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Type;

    /**
     * mysql var type double(15,4)
     *
     * @var
     */
    protected $_Size;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_FolderId;

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
    protected $_OwnerId;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Status;

    /**
     * mysql var type varchar(500)
     *
     * @var string
     */
    protected $_Description;

    /**
     * mysql var type tinyint(4)
     *
     * @var int
     */
    protected $_Rights;

    public static $_KNOWN_EXTENSIONS = array('file', 'dir', 'doc', 'xls', 'jpg', 'gif', 'png', 'txt', 'yt', 'mp3', 'avi', 'mpg', 'mpeg', 'pdf', 'rar', 'zip', 'm4a', 'wav', 'oga');

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'file'=>'File',
    'type'=>'Type',
    'size'=>'Size',
    'folder_id'=>'FolderId',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'owner_id'=>'OwnerId',
    'status'=>'Status',
    'description'=>'Description',
    'rights'=>'Rights'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFiles
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
     * sets column file type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoFiles
     *
     **/

    public function setFile($data)
    {
        $this->_File=$data;
        return $this;
    }

    /**
     * gets column file type varchar(200)
     * @return string
     */

    public function getFile()
    {
        return $this->_File;
    }

    /**
     * sets column type type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoFiles
     *
     **/

    public function setType($data)
    {
        $this->_Type=$data;
        return $this;
    }

    /**
     * gets column type type varchar(100)
     * @return string
     */

    public function getType()
    {
        return $this->_Type;
    }

    /**
     * sets column size type double(15,4)
     *
     * @param  $data
     * @return Petolio_Model_PoFiles
     *
     **/

    public function setSize($data)
    {
        $this->_Size=$data;
        return $this;
    }

    /**
     * gets column size type double(15,4)
     * @return
     */

    public function getSize()
    {
        return $this->_Size;
    }

    /**
     * sets column folder_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFiles
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
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoFiles
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
     * @return Petolio_Model_PoFiles
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
     * sets column owner_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFiles
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
     * sets column status type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoFiles
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
     * sets column description type varchar(500)
     *
     * @param string $data
     * @return Petolio_Model_PoFiles
     *
     **/

    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column description type varchar(500)
     * @return string
     */

    public function getDescription()
    {
        return $this->_Description;
    }

    /**
     * sets column rights type tinyint(4)
     *
     * @param string $data
     * @return Petolio_Model_PoFiles
     *
     **/

    public function setRights($data)
    {
        $this->_Rights=$data;
        return $this;
    }

    /**
     * gets column rights type tinyint(4)
     * @return string
     */

    public function getRights()
    {
        return $this->_Rights;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoFilesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFilesMapper());
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
     * return an icon for the current file
     * @return string
     */
    public function getIcon() {
    	if ( in_array(pathinfo($this->getFile(), PATHINFO_EXTENSION), self::$_KNOWN_EXTENSIONS) ) {
    		return pathinfo($this->getFile(), PATHINFO_EXTENSION) . '.gif';
    	}
    	return 'file.gif';
    }

}

