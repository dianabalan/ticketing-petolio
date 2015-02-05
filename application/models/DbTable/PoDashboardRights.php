<?php

class Petolio_Model_DbTable_PoDashboardRights extends Zend_Db_Table_Abstract {

    protected $_name = 'po_dashboard_rights';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoDashboard'
	);

}