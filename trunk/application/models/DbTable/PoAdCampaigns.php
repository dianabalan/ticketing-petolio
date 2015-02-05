<?php

class Petolio_Model_DbTable_PoAdCampaigns extends Zend_Db_Table_Abstract {

    protected $_name = 'po_ad_campaigns';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAdBanners' 
	);
	
	protected $_referenceMap    = array(
        'PoCustomers' => array(
            'columns'           => array('customer_id'),
            'refTableClass'     => 'PoAdCustomers',
            'refColumns'        => array('id')
        )
	);
	
	/**
	 * deletes a campaign and all of the campaign's banners
	 * 
	 * @param int $id
	 */
	public function deleteWithReferences($id) {
		$sql = "UPDATE po_ad_campaigns SET deleted = 1 WHERE id = ".$this->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).";";
		$this->getAdapter()->query($sql);
		$sql = "UPDATE po_ad_banners SET deleted = 1 WHERE campaign_id = ".$this->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).";";
		$this->getAdapter()->query($sql);
	}
	
}