<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoAdPets extends MainModel {

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    /**
     * mysql var type bigint(50)
     *
     * @var int
     */
    protected $_CustomerId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_PetId;


	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'customer_id'=>'CustomerId',
	    'pet_id'=>'PetId'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdPets
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
     * sets column customer_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdPets
     **/
    public function setCustomerId($data)
    {
        $this->_CustomerId=$data;
        return $this;
    }

    /**
     * gets column customer_id type bigint(20)
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_CustomerId;
    }

    /**
     * sets column pet_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdPets
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
     * returns the mapper class
     *
     * @return Petolio_Model_PoAdPetsMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAdPetsMapper());
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

