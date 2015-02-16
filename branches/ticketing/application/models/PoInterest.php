<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoInterest extends MainModel {


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
	protected $_PetId;

	/**
	 * mysql var type tinyint(1)
	 *
	 * @var string
	 */
	protected $_Status;

	function __construct() {
		$this->setColumnsList(array(
	    'id'=>'Id',
	    'user_id'=>'UserId',
    	'pet_id'=>'PetId',
		'status'=>'Status',
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoPets
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
	 * @return Petolio_Model_PoInterest
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
	 * sets column pet_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoInterest
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
	/**
	 * sets column sttus type tinyint(4)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoInterest
	 *
	 **/

	public function setStatus($data)
	{
		$this->_Status=$data;
		return $this;
	}

	/**
	 * gets column status type tinyint(4)
	 * @return int
	 */

	public function getStatus()
	{
		return $this->_Status;
	}

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoFriendsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoInterestMapper());
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