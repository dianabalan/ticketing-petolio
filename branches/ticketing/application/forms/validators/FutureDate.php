<?php

class Petolio_Validator_FutureDate extends Zend_Validate_Abstract
{
    const MSG_DAY = 'msgDay';
    const MSG_MONTH = 'msgMonth';
    const MSG_YEAR = 'msgYear';
    const MSG_INVALID = 'msgInvalid';
    const MSG_PAST = 'msgPast';
	const MSG_REQUIRED = 'msgRequired';

    // if you add or modifiy this messages do not forget to add/modify the translation strings too
    protected $_messageTemplates = array(
        self::MSG_DAY => "You forgot to fill in the Day!",
        self::MSG_MONTH => "You forgot to fill in the Month!",
        self::MSG_YEAR => "You forgot to fill in the Year!",
        self::MSG_INVALID => "That date is invalid!",
        self::MSG_PAST => "That date is in the past!",
        self::MSG_REQUIRED => "Date is required and can't be empty"
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
    public function __construct($options = false) {
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

    	if(empty($value['day']) && empty($value['month']) && empty($value['year'])) {
    		if($this->getRequired()) {
    			$this->_error(self::MSG_REQUIRED);
    			return false;
    		} else return true;
		}

    	if(empty($value['day'])) {
            $this->_error(self::MSG_DAY);
            return false;
    	}
    	if(empty($value['month'])) {
            $this->_error(self::MSG_MONTH);
            return false;
    	}
    	if(empty($value['year'])) {
            $this->_error(self::MSG_YEAR);
            return false;
    	}

    	if(checkdate($value['month'], $value['day'], $value['year']) === false) {
			$this->_error(self::MSG_INVALID);
			return false;
		}

		if(strtotime("{$value['day']}-{$value['month']}-{$value['year']}") < strtotime(date('j') . '-' . date('n') . '-' . date('Y'))) {
			$this->_error(self::MSG_PAST);
			return false;
		}

        return true;
    }
}