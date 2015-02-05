<?php

class Petolio_Model_DbTable_PoAttributeEntityDecimal extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_entity_decimal';
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