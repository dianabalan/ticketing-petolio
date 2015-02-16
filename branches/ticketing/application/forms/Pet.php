<?php

class Petolio_Form_Pet extends Petolio_Form_Main
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

    	// step 1
    	if(is_null($this->attribute_set)) {
	        $db = new Petolio_Model_DbTable_PoAttributeSets();
	        $select = $db->select()
	        	->where("scope = 'po_pets'")
	        	->where("active = 1");

	        $type = array();
	        foreach($db->fetchAll($select) as $line)
	        	$type[$line['id']] = Petolio_Service_Util::Tr($line['name']);

			// we want to sort the values if they are translated too so that's why we sort it here
	        asort($type);

			$this->addElement('select', 'attribute_set', array(
	            'label' => $translate->_('Pet Species'),
				'attribs' => array('empty' => $translate->_('Select a Pet Species')),
				'multiOptions' => $type,
	            'required' => true,
	        ));

	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Next Step >')
	        ));

			return true;
    	}

    	// step 2
        $db = new Petolio_Model_DbTable_PoAttributes();
        foreach($db->formAttributes($db->getAttributes($this->attribute_set)) as $one) {
			if($one['tiny'])
				$this->getView()->tinymce = 'tinymce.advanced';

			$this->addElement($one['name'], $one['code'], $one['attr']);
        }

        if(!$this->admin) {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Save Pet & Go to Pictures >')
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