<?php

class Petolio_Form_Register extends Petolio_Form_Main {

	public $processed = false;
	private $admin = false;

	public function __construct($admin = false)
	{
		$this->admin = $admin;
		parent::__construct();
	}

    public function init() {
    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	if(!$this->admin) {
    		$this->setAction('/site');
	    	$this->setDecorators(array(
	    		array('ViewScript', array('viewScript' => 'register_form.phtml', 'translate' => $translate))
	    	));
    	} else {
	    	$this->setDecorators(array('FormElements','Form'));
	    	$this->setElementDecorators(
	    		array('PoStandardElement')
	    	);
	    	$this->removeDecorator('DtDdWrapper');
		}
		
		$this->addElementPrefixPaths(array(
				'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
				'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
		));
		
		
		// set method
    	$this->setMethod(Zend_Form::METHOD_POST);

    	// handle errors
    	$errors = $this->admin ? array() : array('errors_class' => 'cluetip_errors', 'msg_errors' => true);

    	$this->addElement('text', 'name', array(
            'label' => $translate->_('Name (shown name)'),
            'required' => true,
    		'attribs' => $errors,
            'validators' => array(
                array('StringLength', false, array('min'=>5,'max'=>75))
            ),
        ));
        $this->addElement('text', 'remail', array(
            'label' => $translate->_('E-Mail'),
            'required' => true,
        	'attribs' => $errors,
            'validators' => array(
                array('StringLength', false, array('max'=>150)),
            	'EmailAddress',
				'EmailRegistration',
            	//array('Regex', false, "/^[^\W][a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/"),
                array('Regex', false, "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/"),
//				array('Db_NoRecordExists', false, array('table' => 'po_users', 'field' => 'email'))
            ),
        ));
        
        
        $this->addElement('password', 'password', array(
            'label' => $translate->_('Password'),
            'required' => true,
        	'attribs' => $errors,
            'validators' => array(
                array('StringLength', array('min'=> 6, 'max'=>150))
            ),
        ));
        $this->addElement('password', 'confirmpassword', array(
            'label' => $translate->_('Password <span>(re-type)</span>'),
            'required' => true,
        	'attribs' => $errors,
            'validators' => array(
                array('StringLength', array('min'=> 6, 'max'=>150)),
                array('Identical', false, array('token' => 'password'))
            )
        ));
		$this->addElement('radio', 'type', array(
			'label' => $this->admin ? $translate->_('Type') : $translate->_('Register as'),
			'multiOptions' => array('1' => $this->admin ? $translate->_('Pet Owner') : $translate->_('Pet<br/>Owner'), '2' => $translate->_('Service Provider')),
            'required' => true,
			'attribs' => $errors,
			'decorators' => $this->admin ? array('PoRadio') : array()
		));

		if(!$this->admin) {
	        $this->addElement('checkbox', 'agree', array(
	            'label' => $translate->_('I agree with the<br /> <a href="contact/terms" target="_blank">terms and conditions</a>'),
	            'required' => true,
	        	'uncheckedValue' => null,
	        	'attribs' => $errors
	        ));

	        $this->addElement('submit', 'go', array(
        		'label' => $translate->_('Register'),
        		'class' => 'rgstrbtn',
        		'attribs' => $errors
	        ));
		} else {
	        $this->addElement('submit', 'submit', array(
        		'label' => '&nbsp;',
        		'value' => $translate->_("Submit")
	        ));
		}
    }
}