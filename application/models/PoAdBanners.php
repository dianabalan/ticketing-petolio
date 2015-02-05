<?php
require_once('MainModel.php');

/**
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoAdBanners extends MainModel {

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
    protected $_CampaignId;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_File;

    /**
     * mysql var type integer(11)
     *
     * @var int
     */
    protected $_Width;

    /**
     * mysql var type integer(11)
     *
     * @var int
     */
    protected $_Height;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Title;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Link;

    /**
     * mysql var type integer(11)
     *
     * @var int
     */
    protected $_Views;

    /**
     * mysql var type integer(11)
     *
     * @var int
     */
    protected $_Clicks;

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

    /**
     * mysql var type timestamp
     *
     * @var string
     */
	protected $_Viewed;

	/**
     * mysql var type tinyint(1)
     *
     * @var int
     */
	protected $_Type;
	
	/**
     * mysql var type varchar(3)
     *
     * @var string
     */
	protected $_Language;
	
	/**
	 * mysql var type tinyint(1)
	 * 
	 * @var int
	 */
	protected $_System;
	
	
function __construct() {
    $this->setColumnsList(array(
	    'id'=>'Id',
	    'customer_id'=>'CustomerId',
	    'campaign_id'=>'CampaignId',
	    'file'=>'File',
    	'width'=>'Width',
	    'height'=>'height',
	    'title'=>'Title',
	    'link'=>'Link',
	    'views'=>'Views',
	    'clicks'=>'Clicks',
    	'date_created'=>'DateCreated',
    	'date_modified'=>'DateModified',
    	'active'=>'Active',
    	'deleted'=>'Deleted',
	    'viewed'=>'Viewed',
	    'type'=>'Type',
    	'language'=>'Language',
    	'system'=>'System'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
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
     * @return Petolio_Model_PoAdBanners
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
     * sets column campaign_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setCampaignId($data)
    {
        $this->_CampaignId=$data;
        return $this;
    }

    /**
     * gets column campaign_id type bigint(20)
     * @return int
     */
    public function getCampaignId()
    {
        return $this->_CampaignId;
    }

    /**
     * sets column file type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setFile($data)
    {
        $this->_File=$data;
        return $this;
    }

    /**
     * gets column file type varchar(200)
     * @return string
     */
    public function getFile()
    {
        return $this->_File;
    }

    /**
     * sets column width type integer(11)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setWidth($data)
    {
        $this->_Width=$data;
        return $this;
    }

    /**
     * gets column width type integer(11)
     * @return int
     */
    public function getWidth()
    {
        return $this->_Width;
    }
    
    /**
     * sets column height type integer(11)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setHeight($data)
    {
        $this->_Height=$data;
        return $this;
    }

    /**
     * gets column height type integer(11)
     * @return int
     */
    public function getHeight()
    {
        return $this->_Height;
    }
    
    /**
     * sets column title type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setTitle($data)
    {
        $this->_Title=$data;
        return $this;
    }

    /**
     * gets column title type varchar(200)
     * @return string
     */
    public function getTitle()
    {
        return $this->_Title;
    }

    /**
     * sets column link type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setLink($data)
    {
        $this->_Link=$data;
        return $this;
    }

    /**
     * gets column link type varchar(200)
     * @return string
     */
    public function getLink()
    {
        return $this->_Link;
    }

    /**
     * sets column views type integer(11)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setViews($data)
    {
        $this->_Views=$data;
        return $this;
    }

    /**
     * gets column views type integer(11)
     * @return int
     */
    public function getViews()
    {
        return $this->_Views;
    }
    
    /**
     * sets column clicks type integer(11)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setClicks($data)
    {
        $this->_Clicks=$data;
        return $this;
    }

    /**
     * gets column clicks type integer(11)
     * @return int
     */
    public function getClicks()
    {
        return $this->_Clicks;
    }
    
    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
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
     * @return Petolio_Model_PoAdBanners
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
     * @return Petolio_Model_PoAdBanners
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
     * @return Petolio_Model_PoAdBanners
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
     * sets column viewed type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
     *
     **/
    public function setViewed($data)
    {
        $this->_Viewed=$data;
        return $this;
    }

    /**
     * gets column viewed type timestamp
     * @return string
     */

    public function getViewed()
    {
        return $this->_Viewed;
    }
    
    /**
     * sets column type type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
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
     * sets column language type varchar(3)
     *
     * @param string $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setLanguage($data)
    {
        $this->_Language=$data;
        return $this;
    }

    /**
     * gets column language type varchar(3)
     * @return string
     */
    public function getLanguage()
    {
        return $this->_Language;
    }

    /**
     * sets column system type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoAdBanners
     **/
    public function setSystem($data)
    {
        $this->_System=$data;
        return $this;
    }

    /**
     * gets column system type tinyint(1)
     * @return int
     */
    public function getSystem()
    {
        return $this->_System;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAdBannersMapper
     *
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAdBannersMapper());
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
     * gets the next banner
     * 
     * @param int $customer_type
     * @param int $banner_type
     * @param int $pet_id
     */
    public function getNextAd($customer_type = 2, $banner_type = null, $pet_id = null, $language = 'en') {
    	$this->getMapper()->getNextAd($customer_type, $banner_type, $pet_id, $language, $this);
    	return $this;
    }

    /**
	 * Get advertising banners list
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 *
	 * @return either array or paginator
	 */
	public function getBanners($type = 'array', $where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('b' => 'po_ad_banners'))

			// get customer 
			->joinLeft(
				array('c' => 'po_ad_customers'),
				"b.customer_id = c.id AND c.deleted != 1",
				array(
					'customer_name' => 'c.name'
				))
			// get campaign 
			->joinLeft(
				array('cc' => 'po_ad_campaigns'),
				"b.campaign_id = cc.id AND cc.deleted != 1",
				array(
					'campaign_name' => 'cc.name'
				));
				
		// group afterwards
		$select->group("b.id");

		// filter and sort and limit ? ok
		if ( strpos($where, "b.deleted") === false ) {
			$select->where("b.deleted != 1");
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

