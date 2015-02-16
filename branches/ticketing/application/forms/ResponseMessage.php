<?php

class Petolio_Form_ResponseMessage extends Petolio_Form_Main
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
    	
    	/* Form Elements & Other Definitions Here ... */
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'style="width:380px; height: 180px;"'),
            'required' => false
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Submit')
        ));
    }


}

