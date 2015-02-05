<?php

class Petolio_Form_Friend extends Petolio_Form_Main
{

    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setAction('/friends/add');
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
        ));
    	$this->setMethod(Zend_Form::METHOD_GET);

        $this->addElement('text', 'name', array(
            'label' => $translate->_('Name'),
            'required' => true,
			'validators' => array(
				array('StringLength', false, array('min' => 3, 'max' => 20))
			),
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Find Friend')
        ));
    }
}