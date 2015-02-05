<?php

class Petolio_Model_DbTable_PoPayments extends Zend_Db_Table_Abstract {

    protected $_name = 'po_payments';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoServices' => array(
            'columns'           => array('service_id'),
            'refTableClass'     => 'PoServices',
            'refColumns'        => array('id')
        )
	);
    

}

?>