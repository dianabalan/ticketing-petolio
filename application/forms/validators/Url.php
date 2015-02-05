<?php

class Petolio_Validator_Url extends Zend_Validate_Abstract
{
    const MSG_INVALID = 'msgInvalid';
    const MSG_REQUIRED = 'msgRequired';

    // if you add or modifiy this messages do not forget to add/modify the translation strings too
    protected $_messageTemplates = array(
        self::MSG_INVALID => "'%value%' is not a valid URL.",
        self::MSG_REQUIRED => "Value is required and can't be empty"
    );

    /**
     * Required value
     *
     * @var boolean
     */
    protected $_required;

    /**
     * Sets validator options
     * Accepts the following option keys:
     *   'required' => boolean
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options = false)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp['required'] = array_shift($options);
            $options = $temp;
        }

        $this->setRequired($options['required']);
    }

    /**
     * Returns the required option
     *
     * @return boolean
     */
    public function getRequired()
    {
        return $this->_required;
    }

    /**
     * Sets the required option
     *
     * @param  boolean $required
     * @return Zend_Validate_FutureDate Provides a fluent interface
     */
    public function setRequired($required)
    {
        $this->_required = $required;
        return $this;
    }


    public function isValid($value)
    {
    	$this->_setValue($value);

    	if ( !(isset($value) && strlen($value) > 0) ) {
    		if ( $this->getRequired() ) {
    			$this->_error(self::MSG_REQUIRED);
    			return false;
    		}
    	}

		if (!Zend_Uri::check($value)) {
			$this->_error(self::MSG_INVALID);
			return false;
		}

		return true;
    }
}