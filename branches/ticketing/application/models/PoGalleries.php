<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoGalleries extends MainModel
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
    protected $_Title;

    /**
     * mysql var type text
     *
     * @var string
     */
    protected $_Description;

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
	 * @var Petolio_Model_PoUsers
	 */
	protected $_Owner;

	protected $_Deleted;

function __construct() {
    $this->setColumnsList(array(
		    'id'=>'Id',
		    'title'=>'Title',
		    'description'=>'Description',
		    'folder_id'=>'FolderId',
    		'owner_id'=>'OwnerId',
			'date_created'=>'DateCreated',
			'date_modified'=>'DateModified',
    		'deleted'=>'Deleted'
		)
	);
}


    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoGalleries
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
     * sets column title type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoGalleries
     */
    public function setTitle($data)
    {
    	$this->_Title=$data;
    	return $this;
    }

    /**
     * gets column title type varchar(200)
     * @return string
     */
    public function getTitle()
    {
    	return $this->_Title;
    }

    /**
     * sets column description type text
     *
     * @param string $data
     * @return Petolio_Model_PoGalleries
     */
    public function setDescription($data)
    {
    	$this->_Description=$data;
    	return $this;
    }

    /**
     * gets column description type text
     * @return string
     */
    public function getDescription()
    {
    	return $this->_Description;
    }

    /**
     * sets column folder_id type bigint(20)
     *
     * @param string $data
     * @return Petolio_Model_PoGalleries
     */
    public function setFolderId($data)
    {
    	$this->_FolderId=$data;
    	return $this;
    }

    /**
     * gets column folder_id type bigint(20)
     * @return string
     */
    public function getFolderId()
    {
    	return $this->_FolderId;
    }


    /**
     * sets column owner_id type bigint(20)
     *
     * @param string $data
     * @return Petolio_Model_PoGalleries
     */
    public function setOwnerId($data)
    {
    	$this->_OwnerId=$data;
    	return $this;
    }

    /**
     * gets column owner_id type bigint(20)
     * @return string
     */
    public function getOwnerId()
    {
    	return $this->_OwnerId;
    }

    /**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoGalleries
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
	 * @return Petolio_Model_PoGalleries
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

	public function setDeleted($data) { $this->_Deleted = $data; return $this; }
	public function getDeleted() { return $this->_Deleted; }

	/**
	 * set the _Owner obj
	 * @param Petolio_Model_PoUsers $owner
	 * @throws Exception
	 */
	public function setOwner($owner = null) {
		if ( !isset($owner) ) {
			if ( !$this->getOwnerId() ) {
				throw new Exception('Owner id it\'s not set');
			}
			$owner = new Petolio_Model_PoUsers();
			$owner->find($this->getOwnerId());
		}
		if ( !$owner instanceof Petolio_Model_PoUsers ) {
			throw new Exception('Invalid instance for $owner object, Petolio_Model_PoUsers expected.');
		}
		$this->_Owner = $owner;
		return $this;
	}

	/**
	 * gets the owner of the service
	 * @return Petolio_Model_PoUsers obj
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
     * @return Petolio_Model_PoGalleriesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoGalleriesMapper());
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
	 * also sets the _Owner obj
	 *
	 * @param <?=$this->_primaryKey['phptype']?> $id
	 * @return MainModel
	 *
	 */
	public function findWithReferences($id) {
		$this->getMapper()->findWithReferences($id, $this);
		return $this;
	}


}

