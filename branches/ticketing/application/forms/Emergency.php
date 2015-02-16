<?php

class Petolio_Form_Emergency extends Petolio_Form_Main
{
	private $services = null;

	public function __construct($services = null)
	{
		$this->services = $services;
		parent::__construct();
	}

    public function init()
    {
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
    	$this->setName("main_form");

		// copy from partner
		if(count($this->services) > 0) {
		   	$type = array();
    		foreach($this->services as $service) {
    			$v = $service->getMemberService();
				$o = $v->getOwner();

				$idx = array(
					$o->getFirstName(),
					$o->getLastName(),
					$v->getAttributeSetId(),
					$o->getPhone(),
					$o->getEmail()
				);

				$type[base64_encode(json_encode($idx))] = $v->getName();
    		}

	        $this->addElement('select', 'copy_from', array(
	            'label' => $translate->_('Copy Contact Info'),
				'attribs' => array('empty' => $translate->_('Select a partner')),
				'multiOptions' => $type,
	        	'html' => 'onchange="Emergency.copy(this)"'
	        ));
		}

		// first name
        $this->addElement('text', 'first_name', array(
            'label' => $translate->_('First Name')
        ));

		// phone
        $this->addElement('text', 'phone', array(
            'label' => $translate->_('Phone')
        ));

		// last name
        $this->addElement('text', 'last_name', array(
            'label' => $translate->_('Last Name')
        ));

		// email
        $this->addElement('text', 'email', array(
            'label' => $translate->_('E-Mail')
        ));

		// service category
		// -----------------------------------------------------------------------------------------------------------------
        $db = new Petolio_Model_DbTable_PoAttributeSets();
        $select = $db->select()
        	->where("scope = 'po_services'")
        	->where("active = 1")
        	->order("group_name")
        	->order("name");

        $groups = array();
        $service_types = array ();
        $current_group_name = '';
        foreach($db->fetchAll($select) as $line) {
        	if ( isset($line['group_name']) && strlen($line['group_name']) > 0 ) {
        		if ( strcasecmp($line['group_name'], $current_group_name) != 0 ) {
        			$groups[$line['group_name']] = array (
	        			'id' => $line['group_name'],
	        			'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['group_name'])),
	        			'indent' => 0
	        		);
        		}
        	}
        	$current_group_name = $line['group_name'];
    		$service_types[] = array (
    			'id' => $line['id'],
    			'group_name' => $line['group_name'],
    			'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name'])),
    			'indent' => 1
    		);
        }

        // we want to sort the values if they are translated too so that's why we sort it here
        $name = array();
    	foreach ($groups as $key => $row)
		    $name[$key] = $row['name'];
		array_multisort($name, SORT_ASC, SORT_STRING, $groups);

		$name = array();
    	foreach ($service_types as $key => $row)
		    $name[$key] = $row['name'];
		array_multisort($name, SORT_ASC, SORT_STRING, $service_types);

        $type = array();
		foreach ($service_types as $value) {
			if ( !isset($value['group_name']) || strlen($value['group_name']) <= 0 ) {
				$type[] = $value;
			}
		}
        foreach ($groups as $key => $row) {
			$type[] = $row;
			foreach ($service_types as $value) {
				if ( strcasecmp($value['group_name'], $row['id']) == 0 ) {
					$type[] = $value;
				}
			}
		}

        $this->addElement('select', 'category', array(
            'label' => $translate->_('Service Type'),
			'attribs' => array('style' => 'tree', 'empty' => $translate->_('Select a service type')),
			'multiOptions' => $type,
        	'registerInArrayValidator' => false
        ));
		// -----------------------------------------------------------------------------------------------------------------

		// save button
        $this->addElement('button', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Add Contact >'),
			'html' => 'onclick="Emergency.add(this);"'
        ));
    }
}