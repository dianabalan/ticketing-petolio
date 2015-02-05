<?php

class Petolio_Form_Rss extends Petolio_Form_Main
{
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

        $this->addElement('text', 'title', array(
            'label' => $translate->_('Title'),
            'required' => true
        ));

        $this->addElement('text', 'link', array(
            'label' => $translate->_('Link'),
            'required' => true,
            'validators' => array(
                array('Url', false, array('required' => true))
            )
        ));

		$this->addElement('text', 'date_created', array(
            'label' => $translate->_('Published on'),
			'attribs' => array('style' => 'date'),
            'required' => true,
		 	'validators' => array(
				array('Date', false, array('required' => true))
			)
        ));

		$this->addElement('textarea', 'description', array(
            'label' => $translate->_('Description'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save News')
        ));
    }
}