<?php

class Petolio_Model_DbTable_PoContentDistributions extends Zend_Db_Table_Abstract {

    protected $_name = 'po_content_distributions';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoContentDistributionData' 
	);
	
	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoAttributeSets' => array(
            'columns'           => array('attribute_set_id'),
            'refTableClass'     => 'PoAttributeSets',
            'refColumns'        => array('id')
        )
	);
}