<?php

class Petolio_Form_Promotion extends Petolio_Form_Main
{
    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

    	// template
        $db = new Petolio_Model_DbTable_PoAttributeSets();
        $select = $db->select()->setIntegrityCheck(false)
        	->from(array('s' => 'po_attribute_sets'), array('*'))
        	->joinLeft(array('t' => 'po_templates'), "s.id = t.attribute_set_id", array())
        	->where("s.scope = 'po_templates'")
        	->where("t.scope = 'po_promotions'")
        	->where("s.active = 1");

        $type = array();
        foreach($db->fetchAll($select) as $line)
        	$type[$line['id']] = Petolio_Service_Util::Tr($line['name']);

		// we want to sort the values if they are translated too so that's why we sort it here
        asort($type);

		$this->addElement('select', 'attribute_set', array(
            'label' => $translate->_('Promotion Template'),
			'attribs' => array('empty' => $translate->_('Select a Promotion Template')),
			'multiOptions' => $type,
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save Promotion >')
        ));
    }
}