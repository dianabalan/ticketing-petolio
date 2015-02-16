<?php

class Petolio_Form_Invite extends Petolio_Form_Main
{
    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setAction('/friends/recommend');
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement('text', 'subject', array(
            'label' => $translate->_('Subject'),
            'required' => true
        ));

        $this->addElement('text', 'email', array(
            'label' => $translate->_('Emails'),
            'required' => true,
        	'attribs' => array('html' => " style='width: 450px;'"),
        	'description' => $translate->_('Use a blank space as email separator')
        ));

        $this->getView()->tinymce = 'tinymce';
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Send Invitation')
        ));
    }
}