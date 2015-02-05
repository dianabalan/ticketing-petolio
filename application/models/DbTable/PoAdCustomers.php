<?php

class Petolio_Model_DbTable_PoAdCustomers extends Zend_Db_Table_Abstract {

    protected $_name = 'po_ad_customers';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAdBanners', 'PoAdCampaigns', 'PoAdPets'
	);
	
	/**
	 * deletes a customer and all of the customer's campaigns and banners
	 * 
	 * @param int $id
	 */
	public function deleteWithReferences($id) {
		$sql = "UPDATE po_ad_customers SET deleted = 1 WHERE id = ".$this->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).";";
		$this->getAdapter()->query($sql);
		$sql = "UPDATE po_ad_campaigns SET deleted = 1 WHERE customer_id = ".$this->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).";";
		$this->getAdapter()->query($sql);
		$sql = "UPDATE po_ad_banners SET deleted = 1 WHERE customer_id = ".$this->getAdapter()->quote($id, Zend_Db::BIGINT_TYPE).";";
		$this->getAdapter()->query($sql);
	}
}