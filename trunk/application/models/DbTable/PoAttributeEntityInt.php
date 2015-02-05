<?php

class Petolio_Model_DbTable_PoAttributeEntityInt extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_entity_int';
	protected $_primary = 'id';
	
	protected $_referenceMap    = array(
        'PoAttributes' => array(
            'columns'           => array('attribute_id'),
            'refTableClass'     => 'PoAttributes',
            'refColumns'        => array('id')
        )
    );
    

}

?>