<?php

class Petolio_Form_MedicalRecord extends Petolio_Form_Main
{
	private $options = array();

	public function __construct($options = array())
	{
		$this->options = $options;
		parent::__construct();
	}

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

    	$this->addElement('hidden', 'owner_id', array(
    		'value' => $this->options['owner_id']
    	));
    	$this->addElement('hidden', 'pet_id', array(
    		'value' => $this->options['pet_id']
    	));

    	$this->addElement('text', 'headline1', array(
            'label' => $translate->_('Headline 1'),
            'required' => true,
		 	'validators' => array(
				array('StringLength', false, array('max'=>200))
			),
        ));

    	$this->addElement('text', 'headline2', array(
            'label' => $translate->_('Headline 2'),
            'required' => false,
		 	'validators' => array(
				array('StringLength', false, array('max'=>200))
			),
        ));

        $this->addElement('text', 'start_date', array(
            'label' => $translate->_('Start date'),
			'attribs' => array('style' => 'date'),
            'required' => true,
		 	'validators' => array(
				array('Date', false, array('required' => true))
			),
        ));

        $this->addElement('text', 'end_date', array(
            'label' => $translate->_('End date'),
			'attribs' => array('style' => 'date'),
            'required' => false,
		 	'validators' => array(
				'Date'
			),
        ));

		$this->getView()->tinymce = 'tinymce';
        $this->addElement('textarea', 'description', array(
            'label' => $translate->_('Description'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => false,
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save Medical Record')
        ));
    }
}