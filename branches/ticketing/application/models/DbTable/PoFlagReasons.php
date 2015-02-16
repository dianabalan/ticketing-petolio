<?php

class Petolio_Model_DbTable_PoFlagReasons extends Zend_Db_Table_Abstract {

    protected $_name = 'po_flag_reasons';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoFlags' => array(
            'columns'           => array('parent_id'),
            'refTableClass'     => 'PoFlags',
            'refColumns'        => array('id')
        )
	);
}