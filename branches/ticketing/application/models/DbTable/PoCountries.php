<?php

class Petolio_Model_DbTable_PoCountries extends Zend_Db_Table_Abstract {

    protected $_name = 'po_countries';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoUsers', 
			'PoCalendar'
	);
	
	
}

?>