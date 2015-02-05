<?php

class Petolio_Model_DbTable_PoRatings extends Zend_Db_Table_Abstract {

    protected $_name = 'po_ratings';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);
}