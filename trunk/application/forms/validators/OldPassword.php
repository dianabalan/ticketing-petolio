<?php

class Petolio_Validator_OldPassword extends Zend_Validate_Abstract
{
    const MSG = 'yeah';
 
    protected $_messageTemplates = array(
        self::MSG => "Old Password doesn't match!"
    );
 
    public function isValid($value)
    {
    	$auth = Zend_Auth::getInstance();
        $this->_setValue($value);

        if ($auth->getIdentity()->password != sha1($value)) {
            $this->_error(self::MSG);
            return false;
        }
 
        return true;
    }
}