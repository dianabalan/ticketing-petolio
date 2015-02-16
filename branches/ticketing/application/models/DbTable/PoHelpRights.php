<?php

class Petolio_Model_DbTable_PoHelpRights extends Zend_Db_Table_Abstract {

    protected $_name = 'po_help_rights';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoHelp'
	);

}