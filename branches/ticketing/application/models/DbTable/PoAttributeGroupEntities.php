<?php

class Petolio_Model_DbTable_PoAttributeGroupEntities extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attribute_group_entities';
	protected $_primary = 'id';
	
	protected $_referenceMap    = array(
        'PoAttributeGroups' => array(
            'columns'           => array('group_id'),
            'refTableClass'     => 'PoAttributeGroups',
            'refColumns'        => array('id')
        )
    );
	
    /**
     * deletes all group entities entries and entity entries for the specified group_id and entity_id
     * 
     * @param int $group_id
     * @param int $entity_id
     */
	public function deleteGroupEntities($entity_id) {
		if ( !isset($entity_id) ) {
			return false;
		}
		// delete from datetime
		$this->getAdapter()->query("DELETE et.* FROM `po_attribute_entity_datetime` et " . 
			"INNER JOIN `po_attributes` a ON et.attribute_id = a.id INNER JOIN `po_attribute_group_entities` age ON et.entity_id = age.id " .  
			"AND a.group_id = age.group_id WHERE age.entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		// delete from decimal
		$this->getAdapter()->query("DELETE et.* FROM `po_attribute_entity_decimal` et " . 
			"INNER JOIN `po_attributes` a ON et.attribute_id = a.id INNER JOIN `po_attribute_group_entities` age ON et.entity_id = age.id " .  
			"AND a.group_id = age.group_id WHERE age.entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		// delete from int
		$this->getAdapter()->query("DELETE et.* FROM `po_attribute_entity_int` et " . 
			"INNER JOIN `po_attributes` a ON et.attribute_id = a.id INNER JOIN `po_attribute_group_entities` age ON et.entity_id = age.id " .  
			"AND a.group_id = age.group_id WHERE age.entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		// delete from text
		$this->getAdapter()->query("DELETE et.* FROM `po_attribute_entity_text` et " . 
			"INNER JOIN `po_attributes` a ON et.attribute_id = a.id INNER JOIN `po_attribute_group_entities` age ON et.entity_id = age.id " .  
			"AND a.group_id = age.group_id WHERE age.entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		// delete from varchar
		$this->getAdapter()->query("DELETE et.* FROM `po_attribute_entity_varchar` et " . 
			"INNER JOIN `po_attributes` a ON et.attribute_id = a.id INNER JOIN `po_attribute_group_entities` age ON et.entity_id = age.id " .  
			"AND a.group_id = age.group_id WHERE age.entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		
		// delete group entities
		$this->getAdapter()->query("DELETE FROM `po_attribute_group_entities` " . 
			"WHERE entity_id = ".$this->getAdapter()->quote($entity_id, Zend_Db::BIGINT_TYPE).";");
		
	}
    
}