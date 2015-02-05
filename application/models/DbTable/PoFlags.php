<?php

class Petolio_Model_DbTable_PoFlags extends Zend_Db_Table_Abstract {

    protected $_name = 'po_flags';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);
}