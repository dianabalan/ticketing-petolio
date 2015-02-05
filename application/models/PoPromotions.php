<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoPromotions extends MainModel
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
    protected $_UserId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_EventId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_TemplateId;

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
    protected $_Active;
    protected $_Flagged;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'user_id'=>'UserId',
    'event_id'=>'EventId',
    'template_id'=>'TemplateId',
	'date_created'=>'DateCreated',
	'date_modified'=>'DateModified',
    'active'=>'Active',
    'flagged'=>'Flagged'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPromotions
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
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPromotions
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

    public function getUserId()
    {
        return $this->_UserId;
    }

    /**
     * sets column event_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPromotions
     *
     **/

    public function setEventId($data)
    {
        $this->_EventId=$data;
        return $this;
    }

    /**
     * gets column event_id type bigint(20)
     * @return int
     */

    public function getEventId()
    {
        return $this->_EventId;
    }

    /**
     * sets column template_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPromotions
     *
     **/

    public function setTemplateId($data)
    {
        $this->_TemplateId=$data;
        return $this;
    }

    /**
     * gets column template_id type bigint(20)
     * @return int
     */

    public function getTemplateId()
    {
        return $this->_TemplateId;
    }

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoPromotions
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
	 * @return Petolio_Model_PoPromotions
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
     * sets column active type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoPromotions
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

	public function getFlagged() { return $this->_Flagged; }
	public function setFlagged($data) { $this->_Flagged = $data; return $this; }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoPromotionsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoPromotionsMapper());
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

