<?php

class Petolio_Form_Help extends Petolio_Form_Main
{
	private $attribute_set = null;
	private $admin = false;

	public function __construct($set = null, $admin = false)
	{
		$this->attribute_set = $set;
		$this->admin = $admin;

		parent::__construct();
	}

    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');
		$auth = Zend_Auth::getInstance();

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

    	// generate form based on attributes
        $db = new Petolio_Model_DbTable_PoAttributes();
        foreach($db->formAttributes($db->getAttributes($this->attribute_set)) as $one) {
        	// add multiOptions to question species
        	if($one['code'] == 'help_species') {
        		$one['attr']['multiOptions'] = array();

        		$attributes = new Petolio_Model_PoAttributes();
        		$attr = reset($attributes->getMapper()->fetchList("code = '".$one["code"]."'"));

        		$db = new Petolio_Model_PoAttributeOptionsMapper();
        		foreach($db->fetchList("attribute_id = '".$attr->getId()."'") as $all) {
        			$one['attr']['multiOptions'][$all->getId()] = Petolio_Service_Util::Tr($all->getValue());
        		}
        	}

			// handle tiny
			if($one['tiny'])
				$this->getView()->tinymce = 'tinymce';

        	$this->addElement($one['name'], $one['code'], $one['attr']);

			// after species
        	if($one['code'] == 'help_species') {
        		// add rights
	        	$this->addElement('select', 'rights', array(
		            'label' => $translate->_('Addressed To'),
					'multiOptions' => array(
						'0' => $translate->_('All'),
						'1' => $translate->_('Friends'),
						'2' => $translate->_('Service Providers'),
					),
		            'required' => true,
		        ));

				// get all pets
				$pets = array();
				$petsdb = new Petolio_Model_PoPets();
				$auth = Zend_Auth::getInstance();
				foreach($petsdb->getPets("array", "a.user_id = {$auth->getIdentity()->id}") as $all)
					$pets[$all['id']] = $all['name'];

				// link pet
	        	$this->addElement('select', 'pet_id', array(
		            'label' => $translate->_('Link Pet'),
		            'attribs' => array('empty' => $translate->_('Select a Pet')),
					'multiOptions' => $pets,
					'description' => '{{link_medical_record}}',
		            'required' => false,
		        ));
			}
        }

        if(!$this->admin) {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Save Question & Go to Files >')
	        ));
        } else {
        	$this->addElement('select', 'flagged', array(
        		'label' => $translate->_('Flagged'),
        		'multiOptions' => array('1' => $translate->_('Yes'), '0' => $translate->_('No'))
        	));

        	$this->addElement('submit', 'submit', array(
        		'label' => '&nbsp;',
        		'value' => $translate->_("Submit")
        	));
        }
    }
}