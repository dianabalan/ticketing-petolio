<?php

class Petolio_Form_Translations extends Petolio_Form_Main {

    public function init() {
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

		$this->addElement('text', 'label', array(
            'label' => $translate->_('Label'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

    	$options = array(
    		"ln" => $translate->_("Latin"),
    		"en" => $translate->_("English"),
    		"de" => $translate->_("Deutsch")
    	);
		$this->addElement('select', 'language', array(
			'label' => $translate->_('Language'),
			'attribs' => array('empty' => $translate->_('Select language')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));

    	$this->addElement('text', 'value', array(
            'label' => $translate->_('Value'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}