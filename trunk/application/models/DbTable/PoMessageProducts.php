<?php

class Petolio_Model_DbTable_PoMessageProducts extends Zend_Db_Table_Abstract {

    protected $_name = 'po_message_products';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoProducts' => array(
            'columns'           => array('product_id'),
            'refTableClass'     => 'PoProducts',
            'refColumns'        => array('id')
        ),
        'PoMessages' => array(
            'columns'           => array('message_id'),
            'refTableClass'     => 'PoMessages',
            'refColumns'        => array('id')
        )
	);


}

?>