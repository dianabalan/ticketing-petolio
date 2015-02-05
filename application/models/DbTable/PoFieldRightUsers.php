<?php

class Petolio_Model_DbTable_PoFieldRightUsers extends Zend_Db_Table_Abstract {

	protected $_name = 'po_field_right_users';
	protected $_primary = 'id';

	protected $_dependentTables = array(
		'PoFieldRights',
		'PoUsers'
	);

	protected $_referenceMap = array(
        'PoFieldRights' => array(
            'columns'           => array('field_right_id'),
            'refTableClass'     => 'PoFieldRights',
            'refColumns'        => array('id')
		),
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
		)
	);
}