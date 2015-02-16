<?php

class Petolio_Form_Contact extends Petolio_Form_Main
{
    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setAction('/contact');
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement('text', 'name', array(
            'label' => $translate->_('Sender'),
            'required' => true
        ));

        $this->addElement('text', 'email', array(
            'label' => $translate->_('Email'),
            'required' => true,
            'validators' => array(
                'EmailAddress',
        		array('Regex', false, "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/")
            )
        ));

        $this->addElement('text', 'subject', array(
            'label' => $translate->_('Subject'),
            'required' => true
        ));

        $this->getView()->tinymce = 'tinymce';
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Send Message')
        ));
    }
}