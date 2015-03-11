<?php
class Petolio_Form_TicketAddSelection extends Petolio_Form_Main
{
	private $category = null;
	
	/**
	 * Form for AddTicket.
	 * 
	 * @param string $category Sets combo-box category, default is "empty".
	 */
	public function __construct($category = "empty")
	{
		$this->category = $category;
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
		$errors = $this->admin ? array() : array('errors_class' => 'cluetip_errors', 'msg_errors' => true);
		
		$type = array(
				"prod"=> $translate->_("Products"),
				"serv"=> $translate->_("Services")
		);
		
		$this->addElement('select', 'attribute_set', array(
				'label' => $translate->_('Category'),
				'attribs' => array('empty' => $translate->_('Select a Category')),
				'multiOptions' => $type,
				'value' => $this->category,
				'required' => true,
	        	'html' => 'onchange="showData(this)"'
		));
	}
}