<?php

class Petolio_Model_DbTable_PoCalendarAttendees extends Zend_Db_Table_Abstract {

    protected $_name = 'po_calendar_attendees';
	protected $_primary = 'id';
    
	protected $_referenceMap    = array(
        'PoCalendar' => array(
            'columns'           => array('calendar_id'),
            'refTableClass'     => 'PoCalendar',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);
    

}

?>