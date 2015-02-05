<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoAdCampaigns extends MainModel {

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
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Name;

    /**
     * mysql var type integer(11)
     *
     * @var int
     */
    protected $_TargetViews;

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

	/**
     * mysql var type tinyint(1)
     *
     * @var int
     */
	protected $_Deleted;

	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'customer_id'=>'CustomerId',
	    'name'=>'Name',
    	'target_views'=>'TargetViews',
    	'date_created'=>'DateCreated',
    	'date_modified'=>'DateModified',
    	'active'=>'Active',
    	'deleted'=>'Deleted'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCampaigns
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
     * @return Petolio_Model_PoAdCampaigns
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
     * sets column name type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdCampaigns
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
     * sets column target_views type integer(11)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCampaigns
     **/
    public function setTargetViews($data)
    {
        $this->_TargetViews=$data;
        return $this;
    }

    /**
     * gets column target_views type integer(11)
     * @return int
     */
    public function getTargetViews()
    {
        return $this->_TargetViews;
    }
    
    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoAdCampaigns
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
     * @return Petolio_Model_PoAdCampaigns
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
     * @return Petolio_Model_PoAdCampaigns
     **/
    public function setActive($data)
    {
        $this->_Active=$data;
        return $this;
    }

    /**
     * gets column activ type tinyint(1)
     * @return int
     */
    public function getActive()
    {
        return $this->_Active;
    }

    /**
     * sets column deleted type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoAdCampaigns
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
     * @return Petolio_Model_PoAdCampaignsMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAdCampaignsMapper());
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
	 * Get advertising campaigns list
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 *
	 * @return either array or paginator
	 */
	public function getCampaigns($type = 'array', $where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_ad_campaigns'))

			// get customer 
			->joinLeft(
				array('cc' => 'po_ad_customers'),
				"c.customer_id = cc.id AND cc.deleted != 1",
				array(
					'customer_name' => 'cc.name'
				))
			
			// get banners count 
			->joinLeft(
				array('b' => 'po_ad_banners'),
				"c.id = b.campaign_id AND b.deleted != 1",
				array(
					'banners' => 'COUNT(DISTINCT(b.id))',
					'total_views' => 'SUM(b.views)',
					'total_clicks' => 'SUM(b.clicks)'
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
	 * deletes the campaign and all of the campaign's banners
	 *
	 * @return int
	 */
	public function deleteWithReferencesByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->deleteWithReferences($this->getId());
	}
}