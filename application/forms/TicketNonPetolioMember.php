<?php

class Petolio_Form_TicketNonPetolioMember extends Petolio_Form_Main
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
        $this->addElementPrefixPaths(
                array(
                    'decorator' => array(
                        'Petolio_Decorator' => APPLICATION_PATH . '/decorators'
                    ),
                    'validate' => array(
                        'Petolio_Validator_' => APPLICATION_PATH . '/forms/validators/'
                    )
                ));
        
        $this->setMethod(Zend_Form::METHOD_POST);
        
        $this->setAttrib('accept-charset', 'utf-8');
        
        $errors = array(
            'errors_class' => 'cluetip_errors',
            'msg_errors' => true
        );
        
        $this->addElement('text', 'name', 
                array(
                    'label' => $translate->_('Name (shown name)'),
                    'required' => true,
                    'attribs' => $errors,
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'min' => 5,
                                'max' => 75
                            )
                        )
                    )
                ));
        
        $this->addElement('text', 'first_name', 
                array(
                    'label' => $translate->_('First Name'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 100
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('text', 'last_name', 
                array(
                    'label' => $translate->_('Last Name'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 100
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('text', 'email', 
                array(
                    'label' => $translate->_('E-Mail'),
                    'required' => true,
                    'attribs' => $errors,
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 150
                            )
                        ),
                        'EmailAddress',
                        array(
                            'Regex',
                            false,
                            "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/"
                        ),
                        array(
                            'Db_NoRecordExists',
                            false,
                            array(
                                'table' => 'po_users',
                                'field' => 'email'
                            )
                        )
                    )
                ));
        
        $this->addElement('text', 'street', 
                array(
                    'label' => $translate->_('Street'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 100
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('text', 'address', 
                array(
                    'label' => $translate->_('Address'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 200
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('text', 'zipcode', 
                array(
                    'label' => $translate->_('Zip Code'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 10
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $countries = array();
        $countriesMap = new Petolio_Model_PoCountriesMapper();
        foreach ($countriesMap->fetchAll() as $country)
            $countries[$country->getId()] = $country->getName();
        $this->addElement('select', 'country_id', 
                array(
                    'label' => $translate->_('Country'),
                    'attribs' => array(
                        'empty' => $translate->_('Select Country')
                    ),
                    'multiOptions' => $countries,
                    'required' => false
                ));
        
        $this->addElement('text', 'phone', 
                array(
                    'label' => $translate->_('Cell Phone'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 20
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('text', 'private_phone', 
                array(
                    'label' => $translate->_('Private Phone'),
                    'validators' => array(
                        array(
                            'StringLength',
                            false,
                            array(
                                'max' => 20
                            )
                        )
                    ),
                    'required' => false,
                    'attribs' => $errors
                ));
        
        $this->addElement('select', 'gender', 
                array(
                    'label' => $translate->_('Gender'),
                    'attribs' => array(
                        'empty' => $translate->_('Select Gender')
                    ),
                    'multiOptions' => array(
                        '1' => $translate->_('Male'),
                        '2' => $translate->_('Female')
                    ),
                    'required' => false
                ));
        
        $this->addElement('textarea', 'remarks', array(
            'label' => $translate->_('Remarks'),
            'required' => false
        ));
        
        $this->addElement('submit', 'submit', array(
            'label' => '&nbsp;',
            'value' => $translate->_("Save")
        ));
    }
}
