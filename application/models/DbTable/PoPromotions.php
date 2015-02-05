<?php

class Petolio_Model_DbTable_PoPromotions extends Zend_Db_Table_Abstract {

    protected $_name = 'po_promotions';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoCalendar' => array(
            'columns'           => array('event_id'),
            'refTableClass'     => 'PoCalendar',
            'refColumns'        => array('id')
        )
	);
}