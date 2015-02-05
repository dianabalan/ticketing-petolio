<?php

class Petolio_Model_DbTable_PoFileRights extends Zend_Db_Table_Abstract {

    protected $_name = 'po_file_rights';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoFiles' => array(
            'columns'           => array('file_id'),
            'refTableClass'     => 'PoFiles',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);
}

?>