<?php

class Petolio_Validator_EmailRegistration extends Zend_Validate_Abstract
{
	const MSG_EMAIL = 'yeah';

	// if you add or modifiy this messages do not forget to add/modify the translation strings too
	protected $_messageTemplates = array(
			self::MSG_EMAIL => "Email already registered!"
	);

	public function isValid($value)
	{		
		$manager = new Petolio_Model_Ticket_UsersManager();
		
		if ( $manager->isAlreadyRegisteredAsPetolio($value) ) 
		{
			$this->_error(self::MSG_EMAIL);
			return false;
		}

		return true;
	}
}