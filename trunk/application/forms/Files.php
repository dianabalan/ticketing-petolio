<?php

class Petolio_Form_Files extends Petolio_Form_Main
{

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

        $this->addElement('text', 'name', array(
            'label' => $translate->_('Folder Name'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Add Folder')
        ));
    }


}

