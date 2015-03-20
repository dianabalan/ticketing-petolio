<?php

class Petolio_Validator_Phone extends Zend_Validate_Abstract
{

    const INVALID_FORMAT = 'phoneInvalidFormat';

    protected $_messageTemplates = array(
        self::INVALID_FORMAT => "Invalid phone format. Please follow the international format."
    );

    private static $_phonePattern = null;

    private static function _getPhonePattern()
    {
        if ( self::$_phonePattern === null )
        {
            $phone_regex = array(
                '/^',
                '\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|', 
                '2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|', 
                '4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}',
                '$/'
            );
            
            self::$_phonePattern = implode('', $phone_regex);
        }
        
        return self::$_phonePattern;
    }

    public function isValid($value)
    {
        $value = str_replace(' ', '', $value);
        
        if ( $value )
        {
            $pattern = self::_getPhonePattern();
            if ( preg_match($pattern, $value) )
            {
                return true;
            }
        }
        
        $this->_error(self::INVALID_FORMAT);
        return false;
    }

}
