<?php

class Petolio_Model_DbTable_PoSessions extends Zend_Db_Table_Abstract {

	protected $_name = 'po_sessions';
	protected $_primary = 'id';

	protected $_dependentTables = array();
	protected $_referenceMap = array();
}