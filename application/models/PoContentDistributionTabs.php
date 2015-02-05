<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoContentDistributionTabs extends MainModel {

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
    protected $_ContentDistributionId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_TabId;

	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'content_distribution_id'=>'ContentDistributionId',
	    'tab_id'=>'TabId'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributionTabs
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
     * sets column content_distribution_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributionTabs
     *
     **/

    public function setContentDistributionId($data)
    {
        $this->_ContentDistributionId=$data;
        return $this;
    }

    /**
     * gets column content_distribution_id type bigint(20)
     * @return int
     */

    public function getContentDistributionId()
    {
        return $this->_ContentDistributionId;
    }

    /**
     * sets column tab_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributionTabs
     *
     **/

    public function setTabId($data)
    {
        $this->_TabId=$data;
        return $this;
    }

    /**
     * gets column tab_id type bigint(20)
     * @return int
     */

    public function getTabId()
    {
        return $this->_TabId;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoContentDistributionTabsMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoContentDistributionTabsMapper());
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