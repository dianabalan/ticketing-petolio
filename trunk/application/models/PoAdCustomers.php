<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoAdCustomers extends MainModel {

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
    protected $_Name;
    
    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Email;
    
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
	protected $_Type;

    /**
     * mysql var type date
     *
     * @var string
     */
	protected $_StartDate;
	
    /**
     * mysql var type date
     *
     * @var string
     */
	protected $_EndDate;

	/**
     * mysql var type tinyint(1)
     *
     * @var int
     */
	protected $_Deleted;

	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'name'=>'Name',
    	'email'=>'Email',
    	'date_created'=>'DateCreated',
    	'date_modified'=>'DateModified',
    	'type'=>'Type',
    	'start_date'=>'StartDate',
    	'end_date'=>'EndDate',
    	'deleted'=>'Deleted'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCustomers
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
     * sets column name type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdCustomers
     **/
    public function setName($data)
    {
        $this->_Name=$data;
        return $this;
    }

    /**
     * gets column name type varchar(200)
     * @return string
     */
    public function getName()
    {
        return $this->_Name;
    }

    /**
     * sets column email type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdCustomers
     **/
    public function setEmail($data)
    {
        $this->_Email=$data;
        return $this;
    }

    /**
     * gets column email type varchar(200)
     * @return string
     */
    public function getEmail()
    {
        return $this->_Email;
    }

    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoAdCustomers
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
     * @return Petolio_Model_PoAdCustomers
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
     * sets column type type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCustomers
     **/
    public function setType($data)
    {
        $this->_Type=$data;
        return $this;
    }

    /**
     * gets column type type tinyint(1)
     * @return int
     */
    public function getType()
    {
        return $this->_Type;
    }

    /**
     * gets column start_date type date
     * @return string
     */

    public function getStartDate()
    {
        return $this->_StartDate;
    }

    /**
     * sets column start_date type date
     *
     * @param string $data
     * @return Petolio_Model_PoAdCustomers
     *
     **/
    public function setStartDate($data)
    {
        $this->_StartDate=$data;
        return $this;
    }

    /**
     * gets column end_date type date
     * @return string
     */

    public function getEndDate()
    {
        return $this->_EndDate;
    }

    /**
     * sets column end_date type date
     *
     * @param string $data
     * @return Petolio_Model_PoAdCustomers
     *
     **/
    public function setEndDate($data)
    {
        $this->_EndDate=$data;
        return $this;
    }

    /**
     * sets column deleted type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCustomers
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
     * @return Petolio_Model_PoAdCustomersMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAdCustomersMapper());
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
	 * Get advertising customers list
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 *
	 * @return either array or paginator
	 */
	public function getCustomers($type = 'array', $where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();
		$lang = Zend_Registry::get('Zend_Translate')->getLocale();

		$subquery1 = $db->select()->setIntegrityCheck(false)
			->from(
				array('cc' => 'po_ad_campaigns'),
				array('campaigns' => 'COUNT(DISTINCT(cc.id))'))
			->where('cc.customer_id = c.id AND cc.deleted != 1');
		$subquery2 = $db->select()->setIntegrityCheck(false)
			->from(
				array('b' => 'po_ad_banners'),
				array('banner_totals' => "CONCAT(COUNT(DISTINCT(b.id)), '#', SUM(b.views), '#', SUM(b.clicks))"))
			->where('c.id = b.customer_id AND b.deleted != 1');
		$subquery3 = $db->select()->setIntegrityCheck(false)
			->from(
				array('p' => 'po_ad_pets'),
				array('pets' => 'COUNT(DISTINCT(p.id))'))
			->where('p.customer_id = c.id');
			
		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(
				array('c' => 'po_ad_customers'),
				array(
					'*',
					'campaigns' => new Zend_Db_Expr("({$subquery1})"),
					'banner_totals' => new Zend_Db_Expr("({$subquery2})"),
					'pets' => new Zend_Db_Expr("({$subquery3})")
				));

		// group afterwards
		$select->group("c.id");

		// filter and sort and limit ? ok
		if ( strpos($where, "c.deleted") === false ) {
			$select->where("c.deleted != 1");
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

	/**
	 * deletes the customer and all of the customer's campaigns and banners
	 *
	 * @return int
	 */
	public function deleteWithReferencesByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->deleteWithReferences($this->getId());
	}
}