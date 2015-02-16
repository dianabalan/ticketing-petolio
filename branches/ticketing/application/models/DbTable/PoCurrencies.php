<?php

class Petolio_Model_DbTable_PoCurrencies extends Zend_Db_Table_Abstract {

    protected $_name = 'po_currencies';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAttributes' 
	);
	
}