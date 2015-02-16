<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFavorites extends MainModel
{
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
    protected $_EntityId;

	protected $_Scope;


	function __construct() {
	    $this->setColumnsList(array(
	    	'id'=>'Id',
		    'user_id'=>'UserId',
		    'entity_id'=>'EntityId',
		    'scope' => 'Scope'
	    ));
	}

	public function setId($data)
	{
		$this->_Id=$data;
		return $this;
	}

	public function getId()
	{
		return $this->_Id;
	}

    /**
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFavorites
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
     * sets column friend_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoFavorites
     *
     **/

    public function setEntityId($data)
    {
        $this->_EntityId=$data;
        return $this;
    }

    /**
     * gets column friend_id type bigint(20)
     * @return int
     */

    public function getEntityId()
    {
        return $this->_EntityId;
    }

    public function setScope($data)
    {
        $this->_Scope=$data;
        return $this;
    }

    public function getScope()
    {
        return $this->_Scope;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoFavoritesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFavoritesMapper());
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