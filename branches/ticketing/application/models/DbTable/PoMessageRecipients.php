<?php

class Petolio_Model_DbTable_PoMessageRecipients extends Zend_Db_Table_Abstract {

    protected $_name = 'po_message_recipients';
	protected $_primary = 'id';
    
	protected $_referenceMap    = array(
        'PoToUsers' => array(
            'columns'           => array('to_user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoMessages' => array(
            'columns'           => array('message_id'),
            'refTableClass'     => 'PoMessages',
            'refColumns'        => array('id')
        )
	);
    

}

?>