<?php

class Petolio_Model_DbTable_PoAttributeGroups extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_groups';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAttributes',
			'PoAttributeGroupEntities'
	);

}