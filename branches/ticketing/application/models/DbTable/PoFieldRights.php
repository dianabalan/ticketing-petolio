<?php

class Petolio_Model_DbTable_PoFieldRights extends Zend_Db_Table_Abstract {

	protected $_name = 'po_field_rights';
	protected $_primary = 'id';

	protected $_dependentTables = array(
		'PoFieldRightUsers'
	);
}