<?php

class Petolio_Model_DbTable_PoStarRatings extends Zend_Db_Table_Abstract {

    protected $_name = 'po_star_ratings';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

	public function getEntityRating($scope, $entity_id) {
		$db = $this->getAdapter();
		$select = $db->query("SELECT COUNT(id) AS rating_count, SUM(rating) as rating_sum FROM {$this->_name}
				WHERE entity_id = ".$db->quote($entity_id, Zend_Db::BIGINT_TYPE)." 
				AND scope = ".$db->quote($scope)." 
			");
		
		$found = $select->fetchAll();
		$found = reset($found);
		
		return $found;
	}
	
}