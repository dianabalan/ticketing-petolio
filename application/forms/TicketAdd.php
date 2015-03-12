<?php
class Petolio_Form_TicketAdd extends Petolio_Form_Main
{	
	/**
	 * Form for AddTicket.
	 * 
	 */
	public function __construct()
	{
		parent::__construct();
	}

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
		
		// set method
		$this->setMethod(Zend_Form::METHOD_POST);
		 
		// handle errors
		$errors = array('errors_class' => 'cluetip_errors', 'msg_errors' => true);
		
		$reminderType = array(
				"no"=> $translate->_("No one"),
				"only SP"=> $translate->_("Only me"),
				"only client"=> $translate->_("Only clients"),
				"both"=> $translate->_("Both")
		);
		
		$this->addElement('text', 'description', array(
				'label' => $translate->_('Description'),
				'required' => true,
				'validators' => array(
						array('StringLength', false, array('min' => 3, 'max' => 255))
				),
		));
		
		$date = new Zend_Date();
		
		$this->addElement('text', 'ticketDate', array(
				'label' => $translate->_('Ticket date'),
				'value' => $date->get('YYYY-MM-dd'),
				'required' => true,
				'validators' => array(
						new Zend_Validate_Date(array('format' => 'YYYY-MM-dd'))
				),
		));
		
		$this->addElement('select', 'reminder', array(
				'label' => $translate->_('Reminder'),
				'multiOptions' => $reminderType,
				'required' => true,
		));
		
		$this->addElement('text', 'amount', array(
				'label' => $translate->_('Amount'),
				'required' => true,
				'validators' => array(
						new Zend_Validate_Int(),
						new Zend_Validate_GreaterThan(array('min' => 0))
				),
		));
		
		$this->addElement('text', 'price', array(
				'label' => $translate->_('Price'),
				'required' => true,
				'validators' => array(
						new Zend_Validate_Float(),
						new Zend_Validate_GreaterThan(array('min' => 0))
				),
		));
		
		$this->addElement('submit', 'submit', array(
				'label' => '&nbsp;',
				'value' => $translate->_('Finish')
		));
	}
}