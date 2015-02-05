<?php

class Petolio_Form_AdBanner extends Petolio_Form_Main {

	private $file_path = '/images/userfiles/banners/';
	private $file = null;
	private $customer_type = null;

	public function __construct($customer_type = 1, $file = null) {
		$this->customer_type = $customer_type;
		$this->file = $file;
		parent::__construct();
	}

    public function init() {
    	$translate = Zend_Registry::get('Zend_Translate');

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

    	// system
    	if ($this->customer_type == 0) {
    		// banner type
    		$options = array(
    			"1" => "300px * 250px",
    			"2" => "180px * 150px",
    		);
    		$this->addElement('select', 'type', array(
    			'label' => $translate->_('Type'),
    			'attribs' => array('empty' => $translate->_('Select banner type')),
    			'multiOptions' => $options,
    			'required' => true,
    		));
    	}

    	// pet sponsor
    	if ($this->customer_type == 1) {
	    	$customers = new Petolio_Model_PoAdCustomers();
	    	$options = array();
	    	foreach ( $customers->fetchList("type = 1 AND deleted != 1", "name") as $customer ) {
	    		$options[$customer->getId()] = $customer->getName();
	    	}

			$this->addElement('select', 'customer_id', array(
				'label' => $translate->_('Customer'),
				'attribs' => array('empty' => $translate->_('Select a Customer')),
				'multiOptions' => $options,
		    	'required' => true,
		    ));

		    // banner type
	    	$options = array(
	    		"1" => "300px * 250px",
	    		"2" => "180px * 150px",
	    	);
			$this->addElement('select', 'type', array(
				'label' => $translate->_('Type'),
				'attribs' => array('empty' => $translate->_('Select banner type')),
				'multiOptions' => $options,
		    	'required' => true,
		    ));
    	}

		// classic campaign
		if ($this->customer_type == 2) {
	    	$campaigns = new Petolio_Model_PoAdCampaigns();
	    	$options = array();
	    	foreach ( $campaigns->fetchList("deleted != 1", "name") as $campaign ) {
	    		$options[$campaign->getId()] = $campaign->getName();
	    	}

			$this->addElement('select', 'campaign_id', array(
				'label' => $translate->_('Campaign'),
				'attribs' => array('empty' => $translate->_('Select a Campaign')),
				'multiOptions' => $options,
		    	'required' => true,
		    ));

		    // banner type
		    $this->addElement('hidden', 'type', array(
    			'value' => 1
    		));
    	}

    	$options = array(
    		"en" => $translate->_("English"),
    		"de" => $translate->_("Deutsch"),
    	);
		$this->addElement('select', 'language', array(
			'label' => $translate->_('Language'),
			'attribs' => array('empty' => $translate->_('Select language')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));

	    $ds = DIRECTORY_SEPARATOR;
        $config = Zend_Registry::get('config');

        $array['label'] = $translate->_("File");
        $array['attribs'] = array('size' => '27');
		$array['destination'] = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}banners{$ds}";
		$array['validators'] = array(
			array('IsImage', false),
			array('Size', false, $config['max_filesize'])
		);
		$array['required'] = true;

		if (isset($this->file)) {
			$array['description'] = "<a href='{$this->file_path}{$this->file}' target='_blank'>{$this->file}</a>";
			$array['required'] = false;
		}

       	$array['decorators'] = array(
			'File',
            array('ViewScript',
            	array('viewScript' => 'file.phtml', 'placement' => false)
            )
		);
    	$this->addElement('file', 'file', $array);

    	$this->addElement('text', 'title', array(
            'label' => $translate->_('Title'),
            'required' => false,
            'validators' => array(
                array('StringLength', false, array('max'=>200))
            )
		));

		$this->addElement('text', 'link', array(
            'label' => $translate->_('Link'),
            'required' => true,
            'validators' => array(
                array('Url', false, array('required' => true))
            )
        ));

        $options = array (
        	"0" => $translate->_("Inactive"),
        	"1" => $translate->_("Active"),
        );
		$this->addElement('select', 'active', array(
			'label' => $translate->_('Status'),
			'attribs' => array('empty' => $translate->_('Select a status')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_("Submit")
        ));
    }
}