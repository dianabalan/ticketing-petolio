<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoAdBannersMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoAdBanners
     *
     * @var Petolio_Model_DbTable_PoAdBanners     
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoAdBanners $cls
     */     
    public function findOneByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();

            $row = $table->fetchRow($select->where("{$field} = ?", $value));
            if (0 == count($row)) {
                    return;
            }

            $cls->setId($row->id)
				->setCustomerId($row->customer_id)
				->setCampaignId($row->campaign_id)
				->setFile($row->file)
				->setWidth($row->width)
				->setHeight($row->height)
				->setTitle($row->title)
				->setLink($row->link)
				->setViews($row->views)
				->setClicks($row->clicks)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setActive($row->active)
				->setDeleted($row->deleted)
				->setViewed($row->viewed)
				->setType($row->type)
				->setLanguage($row->language)
				->setSystem($row->system);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoAdBanners $cls
     * @return array
     *
     */
    public function toArray($cls) {
    	
        $result = array(
        
            'id' => $cls->getId(),
            'customer_id' => $cls->getCustomerId(),
            'campaign_id' => $cls->getCampaignId(),
            'file' => $cls->getFile(),
        	'width' => $cls->getWidth(),
	        'height' => $cls->getHeight(),
	        'title' => $cls->getTitle(),
	        'link' => $cls->getLink(),
	        'views' => $cls->getViews(),
	        'clicks' => $cls->getClicks(),
	        'date_created' => $cls->getDateCreated(),
	        'date_modified' => $cls->getDateModified(),
	        'active' => $cls->getActive(),
	        'deleted' => $cls->getDeleted(),
	        'viewed' => $cls->getViewed(),
        	'type' => $cls->getType(),
        	'language' => $cls->getLanguage(),
        	'system' => $cls->getSystem()
                    
        );
        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoAdBanners $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
                    $cls=new Petolio_Model_PoAdBanners();
                    $result[]=$cls;
                    $cls->setId($row->id)
						->setCustomerId($row->customer_id)
						->setCampaignId($row->campaign_id)
						->setFile($row->file)
						->setWidth($row->width)
						->setHeight($row->height)
						->setTitle($row->title)
						->setLink($row->link)
						->setViews($row->views)
						->setClicks($row->clicks)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setActive($row->active)
						->setDeleted($row->deleted)
						->setViewed($row->viewed)
						->setType($row->type)
						->setLanguage($row->language)
						->setSystem($row->system);
            }
            return $result;
    }
    
    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoAdBanners $dbTable
     * @return Petolio_Model_PoAdBannersMapper
     * 
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * returns the dbTable class
     * 
     * @return Petolio_Model_DbTable_PoAdBanners     
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoAdBanners');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoAdBanners $cls
     *
     */
     
    public function save(Petolio_Model_PoAdBanners $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key=>$value) {
                if (!isset($value) or strlen($value) <= 0)
                    unset($data[$key]);
            }
        }

        if ( $escapeValues ) {
	        foreach ($data as $key => $value) {
	        	if ( !($value instanceof Zend_Db_Expr) )
		        	$data[$key] = Petolio_Service_Util::escape($data[$key]);
	        }
        }
                
        if (null === ($id = $cls->getId())) {
            unset($data['id']);
            $id = $this->getDbTable()->insert($data);
            $cls->setId($id);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * finds row by primary key
     *
     * @param int $id
     * @param Petolio_Model_PoAdBanners $cls
     */

    public function find($id, Petolio_Model_PoAdBanners $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setCustomerId($row->customer_id)
			->setCampaignId($row->campaign_id)
			->setFile($row->file)
			->setWidth($row->width)
			->setHeight($row->height)
			->setTitle($row->title)
			->setLink($row->link)
			->setViews($row->views)
			->setClicks($row->clicks)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setActive($row->active)
			->setDeleted($row->deleted)
			->setViewed($row->viewed)
			->setType($row->type)
			->setLanguage($row->language)
			->setSystem($row->system);
    }

    /**
     * fetches all rows 
     *
     * @return array
     */
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoAdBanners();
            $entry->setId($row->id)
				->setCustomerId($row->customer_id)
				->setCampaignId($row->campaign_id)
				->setFile($row->file)
				->setWidth($row->width)
				->setHeight($row->height)
				->setTitle($row->title)
				->setLink($row->link)
				->setViews($row->views)
				->setClicks($row->clicks)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setActive($row->active)
				->setDeleted($row->deleted)
				->setViewed($row->viewed)
				->setType($row->type)
				->setLanguage($row->language)
				->setSystem($row->system)
                              ->setMapper($this);
            $entries[] = $entry;
        }
        return $entries;
    }

    /**
     * fetches all rows optionally filtered by where,order,count and offset
     * 
     * @param string $where
     * @param string $order
     * @param int $count
     * @param int $offset 
     *
     */
    public function fetchList($where=null, $order=null, $count=null, $offset=null)
    {
            $resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset);
            $entries   = array();
            foreach ($resultSet as $row)
            {
                    $entry = new Petolio_Model_PoAdBanners();
                    $entry->setId($row->id)
						->setCustomerId($row->customer_id)
						->setCampaignId($row->campaign_id)
						->setFile($row->file)
						->setWidth($row->width)
						->setHeight($row->height)
						->setTitle($row->title)
						->setLink($row->link)
						->setViews($row->views)
						->setClicks($row->clicks)
						->setDateCreated($row->date_created)
						->setDateModified($row->date_modified)
						->setActive($row->active)
						->setDeleted($row->deleted)
						->setViewed($row->viewed)
						->setType($row->type)
						->setLanguage($row->language)
						->setSystem($row->system)
                          ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

    /**
     * gets the next banner
     * 
     * @param int $customer_type
     * @param int $banner_type
     * @param int $pet_id
     * @param string $language
     * @param Petolio_Model_PoAdBanners $cls
     */
    public function getNextAd($customer_type = 2, $banner_type = null, $pet_id = null, $language = 'en', Petolio_Model_PoAdBanners $cls) {
        $row = $this->getDbTable()->getNextAd($customer_type, $banner_type, $pet_id, $language);

        if (0 == count($row) || !isset($row["id"])) {
        	$this->getDefaultAd($banner_type, $language, $cls);
        	return;
        }

        $cls->setId($row["id"])
			->setCustomerId($row["customer_id"])
			->setCampaignId($row["campaign_id"])
			->setFile($row["file"])
			->setWidth($row["width"])
			->setHeight($row["height"])
			->setTitle($row["title"])
			->setLink($row["link"])
			->setViews($row["views"])
			->setClicks($row["clicks"])
			->setDateCreated($row["date_created"])
			->setDateModified($row["date_modified"])
			->setActive($row["active"])
			->setDeleted($row["deleted"])
			->setViewed($row["viewed"])
			->setType($row["type"])
			->setLanguage($row["language"])
			->setSystem($row["system"]);
    	
    }
    
    /**
     * gets a default/system banner
     * 
     * @param int $customer_type
     * @param int $banner_type
     * @param string $language
     * @param Petolio_Model_PoAdBanners $cls
     */
    public function getDefaultAd($banner_type = null, $language = 'en', Petolio_Model_PoAdBanners $cls) {
        $row = $this->getDbTable()->getDefaultAd($banner_type, $language);

        if (0 == count($row)) {
            return;
        }

        $cls->setId($row["id"])
			->setCustomerId($row["customer_id"])
			->setCampaignId($row["campaign_id"])
			->setFile($row["file"])
			->setWidth($row["width"])
			->setHeight($row["height"])
			->setTitle($row["title"])
			->setLink($row["link"])
			->setViews($row["views"])
			->setClicks($row["clicks"])
			->setDateCreated($row["date_created"])
			->setDateModified($row["date_modified"])
			->setActive($row["active"])
			->setDeleted($row["deleted"])
			->setViewed($row["viewed"])
			->setType($row["type"])
			->setLanguage($row["language"])
			->setSystem($row["system"]);
    }
}
