<?php

class Petolio_Form_AdCampaign extends Petolio_Form_Main {

    public function init() {

    	$translate = Zend_Registry::get('Zend_Translate');

    	/* Form Elements & Other Definitions Here ... */
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setName('addcampaign');
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

    	$customers = new Petolio_Model_PoAdCustomers();
    	$options = array();
    	foreach ( $customers->fetchList("type = 2 AND deleted != 1", "name") as $customer ) {
    		$options[$customer->getId()] = $customer->getName();
    	}

		$this->addElement('select', 'customer_id', array(
			'label' => $translate->_('Customer'),
			'attribs' => array('empty' => $translate->_('Select a Customer')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));

    	$this->addElement('text', 'name', array(
            'label' => $translate->_('Name'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

		$this->addElement('text', 'target_views', array(
            'label' => $translate->_('Target Views'),
            'required' => true,
            'validators' => array(
                'Int'
            )
        ));

        $options = array (
        	"0" => $translate->_("Inactive"),
        	"1" => $translate->_("Active"),
        );
		$this->addElement('select', 'active', array(
			'label' => $translate->_('Status'),
			'attribs' => array('empty' => $translate->_('Select a status')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}