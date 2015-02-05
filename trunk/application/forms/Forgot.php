<?php

class Petolio_Form_Forgot extends Petolio_Form_Main
{

    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');
    	
    	/* Form Elements & Other Definitions Here ... */    	
    	$this->setAction('/accounts/forgot');
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement('text', 'email', array(
            'label' => $translate->_('Email'),
            'required' => true,
            'validators' => array(
                'EmailAddress',
        		array('Regex', false, "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/"),
                array('Db_RecordExists', false, array('table' => 'po_users', 'field' => 'email'))
            )
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Recover Password')
        ));
    }
}