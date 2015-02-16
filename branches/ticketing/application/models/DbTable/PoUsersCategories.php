<?php

class Petolio_Model_DbTable_PoUsersCategories extends Zend_Db_Table_Abstract {

	protected $_name = 'po_users_categories';
	protected $_primary = 'id';

	protected $_dependentTables = array(
		'PoUsers'
	);
}