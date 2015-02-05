<?php

class Petolio_Model_DbTable_PoPedigree extends Zend_Db_Table_Abstract {

    protected $_name = 'po_pedigree';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
        ),
        'PoAttributeSets' => array(
            'columns'           => array('attribute_set_id'),
            'refTableClass'     => 'PoAttributes',
            'refColumns'        => array('id')
        )
	);
    

}

?>