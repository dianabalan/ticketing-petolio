<?php

class Petolio_Form_AttributeSet extends Petolio_Form_Main {

	private $scope = null;
	private $disabled = null;

	public function __construct($scope = null, $disabled = false) {
		$this->scope = $scope;
		$this->disabled = $disabled;

		parent::__construct();
	}

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

    	$this->addElement('text', 'name', array(
            'label' => $translate->_('Name (en)'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

    	$this->addElement('text', 'name_de', array(
            'label' => $translate->_('Name (de)'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

		$this->addElement('text', 'scope', array(
            'label' => $translate->_('Scope'),
            'required' => true,
			'attribs' => $this->disabled ? array('html' => 'readonly="readonly"') : array(),
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
        ));

		$options = array (
			"1" => $translate->_("Yes"),
			"0" => $translate->_("No")
		);
		$this->addElement('select', 'active', array(
			'label' => $translate->_('Active'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

		$this->addElement('text', 'group_name', array(
            'label' => $translate->_('Group Name (en)'),
            'required' => $this->scope == 'po_services' ? true : false,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
        ));

		$this->addElement('text', 'group_name_de', array(
			'label' => $translate->_('Group Name (de)'),
			'required' => $this->scope == 'po_services' ? true : false,
			'validators' => array(
				array('StringLength', false, array('max'=>200))
			)
		));

        if($this->scope == 'po_services') {
        	$options = array(
        		"0" => $translate->_("Partnership"),
        		"1" => $translate->_("Membership")
        	);
        	$this->addElement('select', 'type', array(
        		'label' => $translate->_('Type'),
        		'attribs' => array('empty' => $translate->_('Select a value')),
        		'multiOptions' => $options,
        		'required' => true
        	));
        }

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}