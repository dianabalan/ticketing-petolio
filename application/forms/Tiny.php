<?php

class Petolio_Form_Tiny extends Petolio_Form_Main
{
	private $label = null;

	public function __construct($label)
	{
		$this->label = $label;
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
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->getView()->tinymce = 'tinymce';
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $this->label
        ));
    }
}