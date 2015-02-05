<?php

class Petolio_Form_Password extends Petolio_Form_Main
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
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement('password', 'oldpassword', array(
            'label' => $translate->_('Old Password'),
            'required' => true,
            'validators' => array(
				array('StringLength', array('min' => 6, 'max' => 150)),
				'OldPassword'
            )
        ));
        $this->addElement('password', 'password', array(
            'label' => $translate->_('Password'),
            'required' => true,
            'validators' => array(
                array('StringLength', array('min' => 6, 'max' => 150))
            ),
        ));
        $this->addElement('password', 'confirmpassword', array(
            'label' => $translate->_('Password <small>(re-type)</small>'),
            'required' => true,
            'validators' => array(
                array('StringLength', array('min' => 6, 'max' => 150)),
                array('Identical', false, array('token' => 'password'))
            )
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Change Password')
        ));
    }
}