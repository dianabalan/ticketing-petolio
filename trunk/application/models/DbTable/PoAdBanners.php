<?php

class Petolio_Model_DbTable_PoAdBanners extends Zend_Db_Table_Abstract {

    protected $_name = 'po_ad_banners';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoCustomers' => array(
            'columns'           => array('customer_id'),
            'refTableClass'     => 'PoAdCustomers',
            'refColumns'        => array('id')
        ),
        'PoCampaigns' => array(
            'columns'           => array('campaign_id'),
            'refTableClass'     => 'PoAdCampaigns',
            'refColumns'        => array('id')
        )
	);
	
	public function getNextAd($customer_type = 2, $banner_type = null, $pet_id = null, $language = 'en') {
		$banner_type = $banner_type ? intval($banner_type) : 'null';
		$pet_id = $pet_id ? intval($pet_id) : 'null';
		$language = $language ? $language : 'en';

		$query = "CALL get_next_ad(".$customer_type.", ".$banner_type.", ".$pet_id.", '".$language."');";
		return reset($this->getAdapter()->query($query)->fetchAll());
	}
	
	public function getDefaultAd($banner_type = null, $language = 'en') {
		$banner_type = $banner_type ? intval($banner_type) : 1;
		$language = $language ? $language : 'en';

		$query = "CALL get_default_ad(".$banner_type.", '".$language."');";
		return reset($this->getAdapter()->query($query)->fetchAll());
	}
}