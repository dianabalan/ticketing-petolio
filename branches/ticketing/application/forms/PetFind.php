<?php

class Petolio_Form_PetFind extends Petolio_Form_Main
{
    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');
		$auth = Zend_Auth::getInstance();

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

		// get all pets
		$pets = array();
		$petsdb = new Petolio_Model_PoPets();
		$auth = Zend_Auth::getInstance();
		foreach($petsdb->getPets("array", "a.user_id = {$auth->getIdentity()->id}") as $all)
			$pets[$all['id']] = $all['name'];

		// link pet
    	$this->addElement('select', 'pet_id', array(
            'label' => $translate->_('Pet'),
            'attribs' => array('empty' => $translate->_('Select a Pet')),
			'multiOptions' => $pets,
            'required' => true
        ));

    	$this->addElement('submit', 'submit', array(
    		'label' => '&nbsp;',
    		'value' => $translate->_("Submit")
    	));
    }
}