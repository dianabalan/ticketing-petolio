<?php

class Petolio_Form_Service extends Petolio_Form_Main
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

    	// step 1
    	if(is_null($this->attribute_set)) {
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

	        $this->addElement('select', 'attribute_set', array(
	            'label' => $translate->_('Service type'),
				'attribs' => array('style' => 'tree', 'empty' => $translate->_('Select a service type')),
				'multiOptions' => $type,
	        	'registerInArrayValidator' => false,
	            'required' => true,
	        	'html' => 'onchange="Petolio.showServiceTypeData(this)"'
	        ));

	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Next Step >')
	        ));

			return true;
    	}

    	// step 2
    	$groups = array();
    	$db = new Petolio_Model_DbTable_PoAttributes();
    	foreach($db->formAttributes($db->getAttributes($this->attribute_set)) as $one) {
    		if($one['tiny'])
    			$this->getView()->tinymce = 'tinymce.advanced';

    		// manage groups
    		if (isset($one['grup']) && strlen($one['grup']) > 0 && strcasecmp($one['grup'], 'null') != 0 ) {
				if (!$this->getSubForm($one['grup'])) {
					$subform = new Zend_Form();

					$subform->addAttribs(array('class' => 'subform'));
			    	$subform->setDecorators(array('FormElements','Form'));
			    	$subform->setElementDecorators(array('PoStandardElement'));
			    	$subform->removeDecorator('DtDdWrapper');

			    	$subform->addElementPrefixPaths(array(
			            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators', 'path' => '', 'type' => ''),
			    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/', 'path' => '', 'type' => '')
			        ));

			    	$subform->setMethod(Zend_Form::METHOD_POST);
					$this->addSubForm($subform, $one['grup']);
				}

				$one['attr']['decorators'] = array('PoStandardElement');
				if($one['attr']['required']) {
					if(isset($one['attr']['attribs']['html'])) $one['attr']['attribs']['html'] .= ' data-required="true"';
					else $one['attr']['attribs']['html'] = ' data-required="true"';
					unset($one['attr']['required']);
				}

				$groups[$one['grup']][] = $subform->createElement($one['name'], $one['code'], $one['attr']);
			} else
				$this->addElement($one['name'], $one['code'], $one['attr']);
    	}

    	// create elements based on group
        foreach($groups as $group_name => $group) {
        	$group[] = $this->getSubForm($group_name)->createElement('button', 'subform_submit_'.$group_name, array(
	        	'label' => '&nbsp;',
        		'attribs' => array('html' => 'class="submit"'),
        		'decorators' => array ('PoStandardElement'),
	        	'value' => sprintf($translate->_('Add %s'), Petolio_Service_Util::Tr($group_name))
	        ));
        	$this->getSubForm($group_name)->addDisplayGroup($group, 'Group_'.$group_name);
        	$displaygroup = $this->getSubForm($group_name)->getDisplayGroup('Group_'.$group_name);
	    	$displaygroup->removeDecorator('DtDdWrapper');

	    	$displaygroup->addPrefixPaths(array(
	            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
	    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
	        ));
        }

		if(!$this->admin) {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Save Service >')
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