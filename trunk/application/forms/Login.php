<?php

class Petolio_Form_Login extends Petolio_Form_Main {

    public function init() {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setAction('/site');
    	$this->setAttrib('class', 'cluetip_form');
    	$this->setDecorators(array(
    		array('ViewScript', array('viewScript' => 'login_form.phtml', 'translate' => $translate))
    	));
    	$this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement('text', 'email', array(
            'label' => $translate->_('E-Mail'),
        	'attribs' => array('errors_class' => 'cluetip_errors', 'msg_errors' => true),
            'required' => true
        ));
        $this->addElement('password', 'password', array(
            'label' => $translate->_('Password'),
        	'attribs' => array('errors_class' => 'cluetip_errors', 'msg_errors' => true),
            'required' => true,
            'validators' => array(
                array('StringLength', array('min'=> 6, 'max'=>150))
            ),
        ));
        $this->addElement('submit', 'login', array(
            'label' => $translate->_('login')
        ));
    }
}