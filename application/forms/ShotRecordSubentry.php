<?php

class Petolio_Form_ShotRecordSubentry extends Petolio_Form_Main
{
	private $options = array();

	public function __construct($options = array())
	{
		$this->options = $options;
		parent::__construct();
	}

    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');

    	$this->setName("add_shot_record_subentry");
        /* Form Elements & Other Definitions Here ... */
    	$this->setDecorators(array('FormElements','Form'));
    	$this->setElementDecorators(
    		array('PoStandardElement')
    	);
    	$this->removeDecorator('DtDdWrapper');
    	$this->addElementPrefixPaths(array(
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

    	$this->addElement('hidden', 'owner_id', array(
    		'value' => $this->options['owner_id']
    	));
    	$this->addElement('hidden', 'send_notification', array(
    		'value' => '1'
    	));
    	$this->addElement('hidden', 'shot_record_id', array(
    		'value' => $this->options['shot_record_id']
    	));
    	$service_options = array();
    	if ( isset($this->options['services']) && count($this->options['services']) > 0 ) {
    		$service_options = $this->options['services'];
    	} else {
    		$attributes = new Petolio_Model_PoAttributes();
    		$pet_service_providers = new Petolio_Model_PoServiceMembersPets();
    		$services = $pet_service_providers->getPetServices($this->options['pet_id'], 1); // only accepted links
    		$services_attributes = $attributes->getMapper()->getDbTable()->loadAttributeValues($services);
    		foreach ($services_attributes as $key => $service) {
    			$service_id = substr($key, 0, strpos($key, "_"));
    			$service_options[$service_id] = $service['name']->getAttributeEntity()->getValue();
    		}
    	}
		$this->addElement('select', 'service_id', array(
			'label' => $translate->_('Service'),
			'attribs' => array('empty' => $translate->_('Select a Service')),
			'multiOptions' => $service_options,
			'required' => false,
	    ));

		$this->addElement('text', 'inoculation_date', array(
            'label' => $translate->_('Date of inoculation'),
			'attribs' => array('style' => 'date'),
            'required' => true,
		 	'validators' => array(
				array('Date', false, array('required' => true))
			)
        ));

        $this->addElement('text', 'immunization', array(
            'label' => $translate->_('Immunization'),
            'required' => true,
		 	'validators' => array(
				array('StringLength', false, array('max'=>200))
			),
        ));

		$this->getView()->tinymce = 'tinymce';
        $this->addElement('textarea', 'description', array(
            'label' => $translate->_('Description'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => false,
        ));

		$this->getView()->tinymce = 'tinymce';
        $this->addElement('textarea', 'recommendation', array(
            'label' => $translate->_('Recommendation'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => false,
        ));

		$this->getView()->tinymce = 'tinymce';
        $this->addElement('textarea', 'drugs', array(
            'label' => $translate->_('Drugs'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => false,
        ));

		$this->addElement('text', 'reminder_date', array(
            'label' => $translate->_('Next revaccination'),
			'attribs' => array('style' => 'future_date'),
            'required' => false,
		 	'validators' => array(
				'FutureDate'
			),
        ));

        $this->addElement('button', 'save_sr', array(
        	'label' => '&nbsp;',
			'attribs' => array('html' => 'id="save_sr" class="submit"'),
        	'value' => $translate->_('Save Subentry')
        ));
    }
}