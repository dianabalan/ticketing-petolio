<?php

class Petolio_Model_DbTable_PoTemplates extends Zend_Db_Table_Abstract {

    protected $_name = 'po_templates';
	protected $_primary = 'id';

	protected $_dependentTables = array(
		'PoMicrosites'
	);
}