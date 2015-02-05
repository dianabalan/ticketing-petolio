<?php

class Petolio_Model_DbTable_PoAdPets extends Zend_Db_Table_Abstract {

    protected $_name = 'po_ad_pets';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoCustomers' => array(
            'columns'           => array('customer_id'),
            'refTableClass'     => 'PoAdCustomers',
            'refColumns'        => array('id')
        ),
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
        )
	);
}