<?php

class Petolio_Form_Ticket_TicketStatus extends Zend_Form
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

        $this->addElement('select', 'status',
                array(
                    'label' => $translate->_('Status'),
                    'multiOptions' => array(
                        'billable' => $translate->_('billable'),
                        'instructed' => $translate->_('instructed'),
                        'paid' => $translate->_('paid'),
                        'cancelled' => $translate->_('cancelled')
                    ),
                    'required' => true
                ));


        $this->addElement('submit', 'submit', array(
            'label' => '&nbsp;',
            'value' => $translate->_('Update Ticket Status')
        ));
    }

}

