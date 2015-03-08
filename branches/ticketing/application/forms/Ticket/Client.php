<?php

class Petolio_Form_Ticket_Client extends Zend_Form
{

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
        
        $this->addElement('hidden', 'id', array(
                'value' => null
        ));
        
        $this->addElement('select', 'billing_interval', 
                array(
                    'label' => $translate->_('Billing Interval'), 
                    'attribs' => array(
                        'empty' => $translate->_('Select Billing Interval')
                    ), 
                    'multiOptions' => array(
                        '12' => $translate->_('Month'), 
                        '4' => $translate->_('Quarter'), 
                        '1' => $translate->_('Year')
                    ), 
                    'required' => false
                ));
        
        $this->addElement('select', 'payment', 
                array(
                    'label' => $translate->_('Payment'), 
                    'attribs' => array(
                        'empty' => $translate->_('Select Payment')
                    ), 
                    'multiOptions' => array(
                        'cash' => $translate->_('Cash'), 
                        'paypal' => $translate->_('Paypal'), 
                        'bank' => $translate->_('Bank')
                    ), 
                    'required' => false
                ));
        
        $this->addElement('textarea', 'remarks', array(
            'label' => $translate->_('Remarks'), 
            'required' => false, 
            'attribs' => $errors
        ));
        
        $this->addElement('submit', 'submit', array(
            'label' => '&nbsp;', 
            'value' => $translate->_('Save Client')
        ));
    }

}

