<?php

class Petolio_Form_Email extends Petolio_Form_Main
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

		$options = array(
			"1" => $translate->_("Yes"),
			"0" => $translate->_("No"),
		);
		$this->addElement('select', 'dash_email_notification', array(
			'label' => $translate->_('Small Talk Notifications'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

		$this->addElement('select', 'weekly_email_notification', array(
			'label' => $translate->_('Weekly Notifications'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true
		));

		$this->addElement('select', 'other_email_notification', array(
			'label' => $translate->_('Other Notifications'),
			'attribs' => array('empty' => $translate->_('Select a value')),
			'multiOptions' => $options,
			'required' => true,
			'description' => "{{legend}}"
		));

		$this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save')
        ));
    }
}