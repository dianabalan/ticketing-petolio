<?php

class Petolio_Form_AdCustomer extends Petolio_Form_Main {

	private $type = null;

	public function __construct($type = 1) {
		$this->type = $type;
		parent::__construct();
	}

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

    	$this->addElement('hidden', 'type', array(
    		'value' => $this->type
    	));

    	$this->addElement('text', 'name', array(
            'label' => $translate->_('Name'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

		$this->addElement('text', 'email', array(
            'label' => $translate->_('Email'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200)),
                'EmailAddress'
            )
        ));

        if ($this->type == 1) {
			$this->addElement('text', 'start_date', array(
	            'label' => $translate->_('Start date'),
				'attribs' => array('style' => 'future_date'),
	            'required' => true,
			 	'validators' => array(
					array('FutureDate', false, array('required' => true))
				),
	        ));

	        $this->addElement('text', 'end_date', array(
	            'label' => $translate->_('End date'),
				'attribs' => array('style' => 'future_date'),
	            'required' => true,
			 	'validators' => array(
					array('FutureDate', false, array('required' => true))
				),
	        ));
        }

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}