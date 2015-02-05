<?php

class Petolio_Model_DbTable_PoContentDistributionTabs extends Zend_Db_Table_Abstract {

    protected $_name = 'po_content_distribution_tabs';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoContentDistributions' => array(
            'columns'           => array('content_distribution_id'),
            'refTableClass'     => 'PoContentDistributions',
            'refColumns'        => array('id')
        )
	);
}