<?php

class Petolio_Model_DbTable_PoCostDefinitions extends Zend_Db_Table_Abstract {

    protected $_name = 'po_cost_definitions';
	protected $_primary = 'id';
    
	protected $_referenceMap    = array(
        'PoAttributeSets' => array(
            'columns'           => array('attribute_set_id'),
            'refTableClass'     => 'PoAttributeSets',
            'refColumns'        => array('id')
        )
	);
    

}

?>