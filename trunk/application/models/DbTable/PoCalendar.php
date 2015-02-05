<?php

class Petolio_Model_DbTable_PoCalendar extends Zend_Db_Table_Abstract {

    protected $_name = 'po_calendar';
	protected $_primary = 'id';
    
	protected $_dependentTables = array(
			'PoCalendarAttendees'
	);

	protected $_referenceMap    = array(
        'PoCalendarUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoCalendarCountries' => array(
            'columns'           => array('country_id'),
            'refTableClass'     => 'PoCountries',
            'refColumns'        => array('id')
        ),
        'PoServiceMembersPets' => array(
            'columns'           => array('link_id'),
            'refTableClass'     => 'PoServiceMembersPets',
            'refColumns'        => array('id')
        )
	);
	
}

?>