<?php

class Petolio_Form_Ticket_EditClientTicket extends Zend_Form
{
	public $ticket_id;
	public $data;
	
	public function __construct($ticket_id)
	{
		$this->ticket_id = $ticket_id;
		parent::__construct();
	}
		
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        
        $this->setDecorators(array(
            'FormElements', 
            'Form'
        ));
        
        $this->setElementDecorators(array(
            'PoStandardElement'
        ));
        
        $this->removeDecorator('DtDdWrapper');
        
        $this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH . '/decorators'), 
            'validate' => array('Petolio_Validator_' => APPLICATION_PATH . '/forms/validators/')
        ));
        
        $this->setMethod(Zend_Form::METHOD_POST);
        
        $errors = array(
            'errors_class' => 'cluetip_errors', 
            'msg_errors' => true
        );
        
        $this->addElement('hidden', 'ticket', array(
        		'value' => $this->ticket_id
        ));        
        
        $this->addElement('text', 'amount', array(
            'required' => true, 
            'attribs' => $errors
        ));
        
        $this->addElement('submit', 'save'.$this->ticket_id, array(
            'label' => '&nbsp;', 
            'value' => $translate->_('Save')
        ));
    }

}

