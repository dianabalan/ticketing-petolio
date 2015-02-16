<?php

class Petolio_Form_Gallery extends Petolio_Form_Main
{
	private $options = array();

	public function __construct($options = array())
	{
		$this->options = $options;
		parent::__construct();
	}

    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
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

    	$this->addElement('text', 'title', array(
            'label' => $translate->_('Title'),
            'required' => true,
		 	'validators' => array(
				array('StringLength', false, array('max'=>200))
			),
        ));

        $this->addElement('textarea', 'description', array(
            'label' => $translate->_('Description'),
			'attribs' => array('html' => 'style="width:380px; height: 180px;"'),
            'required' => false,
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save Gallery & Go to Pictures >')
        ));

    }


}

