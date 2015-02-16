<?php

class Petolio_Form_Attribute extends Petolio_Form_Main {

	private $disabled = null;

	public function __construct($disabled = false) {
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

    	$this->addElement('text', 'label', array(
            'label' => $translate->_('Label (en)'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

    	$this->addElement('text', 'label_de', array(
            'label' => $translate->_('Label (de)'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

    	$options = array();
    	$attrTypes = new Petolio_Model_PoAttributeInputTypes();
    	foreach($attrTypes->fetchAll() as $one)
    		$options[$one->getId()] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($one->getDescription()));
    	$this->addElement('select', 'attribute_input_type_id', array(
    		'label' => $translate->_('Type'),
    		'attribs' => array('empty' => $translate->_('Select a value'), 'html' => $this->disabled ? 'disabled="disabled"' : ''),
    		'multiOptions' => $options,
    		'required' => $this->disabled ? false : true
    	));

		$options = array(
			"1" => $translate->_("Yes"),
			"0" => $translate->_("No"),
		);
		$this->addElement('select', 'active', array(
			'label' => $translate->_('Active'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

		$options = array(
			"1" => $translate->_("Yes"),
			"0" => $translate->_("No"),
		);
		$this->addElement('select', 'is_unique', array(
			'label' => $translate->_('Unique'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

		$options = array(
			"1" => $translate->_("Yes"),
			"0" => $translate->_("No"),
		);
		$this->addElement('select', 'is_required', array(
			'label' => $translate->_('Required'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

    	$this->addElement('text', 'print_order', array(
            'label' => $translate->_('Print Order'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

    	$options = array();
    	$attrTypes = new Petolio_Model_PoCurrencies();
    	foreach($attrTypes->fetchAll() as $one)
    		$options[$one->getId()] = $one->getName();
    	$this->addElement('select', 'currency_id', array(
    		'label' => $translate->_('Currency'),
    		'attribs' => array('empty' => $translate->_('Select a value')),
    		'multiOptions' => $options,
    		'required' => false
    	));

    	$options = array();
    	$attrTypes = new Petolio_Model_PoAttributeGroups();
    	foreach($attrTypes->fetchAll() as $one)
    		$options[$one->getId()] = $one->getName();
    	$this->addElement('select', 'group_id', array(
    		'label' => $translate->_('Group'),
    		'attribs' => array('empty' => $translate->_('Select a value')),
    		'multiOptions' => $options,
    		'required' => false
    	));

    	$this->addElement('text', 'description', array(
            'label' => $translate->_('Description'),
            'required' => false,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}