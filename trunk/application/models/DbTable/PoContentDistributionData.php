<?php

class Petolio_Model_DbTable_PoContentDistributionData extends Zend_Db_Table_Abstract {

    protected $_name = 'po_content_distribution_data';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoContentDistributions' => array(
            'columns'           => array('content_distribution_id'),
            'refTableClass'     => 'PoContentDistributions',
            'refColumns'        => array('id')
        )
	);
}