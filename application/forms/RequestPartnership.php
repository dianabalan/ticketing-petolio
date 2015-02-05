<?php

class Petolio_Form_RequestPartnership extends Petolio_Form_Main
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
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

    	$pets = new Petolio_Model_PoPets();
    	$your_pets = $pets->getMapper()->fetchList("user_id = ".Zend_Auth::getInstance()->getIdentity()->id." AND deleted != 1");
    	
    	$attributes = new Petolio_Model_PoAttributes();
    	$all_attributes = $attributes->getMapper()->getDbTable()->loadAttributeValues($your_pets);
    	
    	$pets_array = array();
    	foreach ( $all_attributes as $key => $value ) {
    		$pets_array[$value['name']->getAttributeEntity()->getEntityId()] = $value['name']->getAttributeEntity()->getValue();
    	}
		
        $this->addElement('select', 'pet_id', array(
            'label' => $translate->_('Pet'),
				'attribs' => array('empty' => $translate->_('Select a pet')),
				'multiOptions' => $pets_array,
	            'required' => true,
		));
        
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'style="width:380px; height: 180px;"'),
            'required' => false,
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Send Message >')
        ));
    }

}