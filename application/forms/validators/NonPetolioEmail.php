<?php

class Petolio_Validator_NonPetolioEmail extends Zend_Validate_Abstract
{

    const DUPLICATE_EMAIL = 'duplicateEmail';

    protected $_messageTemplates = array(
        self::DUPLICATE_EMAIL => "Duplicate email for non-Petolio member."
    );

    private $_spId;

    public function setSpId($value)
    {
        $this->_spId = $value;
    }

    private $_currentEmail;

    public function setCurrentEmail($currentEmail)
    {
        $this->_currentEmail = $currentEmail;
    }

    public function isValid($value)
    {
        $value = str_replace(' ', '', $value);
        
        $manager = new Petolio_Model_Ticket_UsersManager();
        
        if ( strcmp($value, $this->_currentEmail) === 0 )
        {
            return true;
        }
        
        if ( $manager->isAlreadyRegisteredAsNonPetolio($value, $this->_spId) )
        {
            $this->_error(self::DUPLICATE_EMAIL);
            return false;
        }
        
        return true;
    }

}
