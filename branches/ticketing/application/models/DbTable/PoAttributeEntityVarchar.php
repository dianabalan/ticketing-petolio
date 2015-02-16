<?php

class Petolio_Model_DbTable_PoAttributeEntityVarchar extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_entity_varchar';
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