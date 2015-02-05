<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoContentDistributions extends MainModel {

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    /**
     * mysql var type varchar(50)
     *
     * @var string
     */
    protected $_Url;

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
    protected $_AttributeSetId;

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

	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'url'=>'Url',
	    'user_id'=>'UserId',
	    'attribute_set_id'=>'AttributeSetId',
    	'date_created'=>'DateCreated',
    	'date_modified'=>'DateModified',
	    'deleted'=>'Deleted'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributions
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
     * sets column url type varchar(50)
     *
     * @param string $data
     * @return Petolio_Model_PoContentDistributions
     *
     **/
    public function setUrl($data)
    {
        $this->_Url=$data;
        return $this;
    }

    /**
     * gets column url type varchar(50)
     * @return string
     */
    public function getUrl()
    {
        return $this->_Url;
    }

    /**
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributions
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
     * sets column attribute_set_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoContentDistributions
     *
     **/

    public function setAttributeSetId($data)
    {
        $this->_AttributeSetId=$data;
        return $this;
    }

    /**
     * gets column attribute_set_id type bigint(20)
     * @return int
     */

    public function getAttributeSetId()
    {
        return $this->_AttributeSetId;
    }
    
    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoContentDistributions
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
     * @return Petolio_Model_PoContentDistributions
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
     * @return Petolio_Model_PoContentDistributions
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
     * returns the mapper class
     *
     * @return Petolio_Model_PoContentDistributionsMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoContentDistributionsMapper());
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
	 * Get content distributions list complete with name, target place, with main menu, design and data type
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 *
	 * @return either array or paginator
	 */
	public function getDistributions($type = 'array', $where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();
		$lang = Zend_Registry::get('Zend_Translate')->getLocale();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_content_distributions'))

			// get service type
			->joinLeft(array('b' => 'po_attribute_sets'), "a.attribute_set_id = b.id", array())

			// get service name
			->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -5) = '_name'", array())
			->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('name' => 'value'))

			// get target place
			->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = a.attribute_set_id AND SUBSTRING(c2.code, -12) = '_targetplace'", array())
			->joinLeft(array('d2' => 'po_attribute_entity_int'), "d2.attribute_id = c2.id AND a.id = d2.entity_id", array())
			->joinLeft(array('e2' => 'po_attribute_options'), "e2.id = d2.value", array("targetplace" => "value"));
/*
			// get with main menu
			->joinLeft(array('c3' => 'po_attributes'), "c3.attribute_set_id = a.attribute_set_id AND SUBSTRING(c3.code, -13) = '_withmainmenu'", array())
			->joinLeft(array('d3' => 'po_attribute_entity_int'), "d3.attribute_id = c3.id AND a.id = d3.entity_id", array())
			->joinLeft(array('e3' => 'po_attribute_options'), "e3.id = d3.value", array("withmainmenu" => "value"))

			// get design
			->joinLeft(array('c4' => 'po_attributes'), "c4.attribute_set_id = a.attribute_set_id AND SUBSTRING(c4.code, -7) = '_design'", array())
			->joinLeft(array('d4' => 'po_attribute_entity_int'), "d4.attribute_id = c4.id AND a.id = d4.entity_id", array())
			->joinLeft(array('e4' => 'po_attribute_options'), "e4.id = d4.value", array("design" => "value"))

			// get data type
			->joinLeft(array('c5' => 'po_attributes'), "c5.attribute_set_id = a.attribute_set_id AND SUBSTRING(c5.code, -5) = '_data'", array())
			->joinLeft(array('d5' => 'po_attribute_entity_int'), "d5.attribute_id = c5.id AND a.id = d5.entity_id", array())
			->joinLeft(array('e5' => 'po_attribute_options'), "e5.id = d5.value", array("data" => "value"));
*/
		// group afterwards
		$select->group("a.id");

		// filter and sort and limit ? ok
		if ( strpos($where, "a.deleted") === false ) {
			$select->where("a.deleted != 1");
		}
		if($where) $select->where($where);
		if($order) {
			if (strpos($order, ",") > 0) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token) {
					$select->order($token);
				}
			} else {
				$select->order($order);
			}
		}
		if($limit) $select->limit($limit);

		// return either array or paginator
		return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
	}
}

