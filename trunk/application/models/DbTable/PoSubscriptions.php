<?php

class Petolio_Model_DbTable_PoSubscriptions extends Zend_Db_Table_Abstract {

    protected $_name = 'po_subscriptions';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);
}