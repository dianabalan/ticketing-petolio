<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoGalleryComments extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;

    /**
     * mysql var type text
     * 
     * @var string
     */
    protected $_Comment;
    
    /**
     * mysql var type bigint(20)
     * 
     * @var int
     */
    protected $_GalleryId;
    
    /**
     * mysql var type bigint(20)
     * 
     * @var int
     */
    protected $_UserId;
    
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
    
	
function __construct() {
    $this->setColumnsList(array(
		    'id'=>'Id',
		    'comment'=>'Comment',
		    'gallery_id'=>'GalleryId',
    		'user_id'=>'UserId',
			'date_created'=>'DateCreated',
			'date_modified'=>'DateModified',
		)
	);
}
    
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoGalleryComments     
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
     * sets column comment type text
     * 
     * @param string $data
     * @return Petolio_Model_PoGalleryComments
     */
    public function setComment($data)
    {
    	$this->_Comment=$data;
    	return $this;
    }
    
    /**
     * gets column comment type text
     * @return string
     */
    public function getComment() 
    {
    	return $this->_Comment;
    }
    
    /**
     * sets column gallery_id type bigint(20)
     * 
     * @param string $data
     * @return Petolio_Model_PoGalleryComments
     */
    public function setGalleryId($data)
    {
    	$this->_GalleryId=$data;
    	return $this;
    }
    
    /**
     * gets column gallery_id type bigint(20)
     * @return string
     */
    public function getGalleryId() 
    {
    	return $this->_GalleryId;
    }
    
    
    /**
     * sets column user_id type bigint(20)
     * 
     * @param string $data
     * @return Petolio_Model_PoGalleryComments
     */
    public function setUserId($data)
    {
    	$this->_UserId=$data;
    	return $this;
    }
    
    /**
     * gets column user_id type bigint(20)
     * @return string
     */
    public function getUserId() 
    {
    	return $this->_UserId;
    }
    
    /**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoGalleryComments
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
	 * @return Petolio_Model_PoGalleryComments
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
     * returns the mapper class
     *
     * @return Petolio_Model_PoGalleryCommentsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoGalleryCommentsMapper());
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

