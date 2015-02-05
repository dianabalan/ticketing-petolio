<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoMessageProducts extends MainModel
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
    protected $_ProductId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_MessageId;




function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'product_id'=>'ProductId',
    'message_id'=>'MessageId',
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMessageProducts
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
     * sets column product_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMessageProducts
     *
     **/

    public function setProductId($data)
    {
        $this->_ProductId=$data;
        return $this;
    }

    /**
     * gets column product_id type bigint(20)
     * @return int
     */

    public function getProductId()
    {
        return $this->_ProductId;
    }

    /**
     * sets column message_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoMessageProducts
     *
     **/

    public function setMessageId($data)
    {
        $this->_MessageId=$data;
        return $this;
    }

    /**
     * gets column message_id type bigint(20)
     * @return int
     */

    public function getMessageId()
    {
        return $this->_MessageId;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoMessageProductsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoMessageProductsMapper());
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

