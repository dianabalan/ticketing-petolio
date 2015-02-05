<?php

class Petolio_Model_DbTable_PoStealth extends Zend_Db_Table_Abstract {

    protected $_name = 'po_stealth';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('from_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('to_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
    );
}