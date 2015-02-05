<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoTemplates extends MainModel
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
    protected $_AttributeSetId;

    protected $_Scope;

    /**
     * mysql var type text
     *
     * @var string
     */
    protected $_Filename;

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
	 * mysql var type tinyint(1)
	 *
	 * @var int
	 */
    protected $_Deleted;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Active;


function __construct() {
    $this->setColumnsList(array(
		    'id'=>'Id',
		    'attribute_set_id'=>'AttributeSetId',
    		'scope'=>'Scope',
		    'filename'=>'Filename',
			'date_created'=>'DateCreated',
			'date_modified'=>'DateModified',
		    'deleted'=>'Deleted',
		    'active'=>'Active'
		)
	);
}


    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoTemplates
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
     * sets column template_file type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoTemplates
     */
    public function setAttributeSetId($data)
    {
    	$this->_AttributeSetId=$data;
    	return $this;
    }

    /**
     * gets column template_file type varchar(200)
     * @return string
     */
    public function getAttributeSetId()
    {
    	return $this->_AttributeSetId;
    }

	public function setScope($data) { $this->_Scope = $data; return $this; }
	public function getScope() { return $this->_Scope; }

    /**
     * sets column sample_data type text
     *
     * @param string $data
     * @return Petolio_Model_PoTemplates
     */
    public function setFilename($data)
    {
    	$this->_Filename=$data;
    	return $this;
    }

    /**
     * gets column sample_data type text
     * @return string
     */
    public function getFilename()
    {
    	return $this->_Filename;
    }

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoTemplates
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
	 * @return Petolio_Model_PoTemplates
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
     * sets column deleted type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoTemplates
     *
     **/

    public function setDeleted($data)
    {
        $this->_Deleted=$data;
        return $this;
    }

    /**
     * gets column deleted type tinyint(1)
     * @return int
     */

    public function getDeleted()
    {
        return $this->_Deleted;
    }

    /**
     * sets column active type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoTemplates
     *
     **/

    public function setActive($data)
    {
        $this->_Active=$data;
        return $this;
    }

    /**
     * gets column active type tinyint(1)
     * @return int
     */

    public function getActive()
    {
        return $this->_Active;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoTemplatesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoTemplatesMapper());
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

