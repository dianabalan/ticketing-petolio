<?php

class Petolio_Form_Product extends Petolio_Form_Main
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

        	// add multiOptions to product species
        	if($one['code'] == 'product_species') {
        		$one['attr']['multiOptions'] = array();
        		
        		$attributes = new Petolio_Model_PoAttributes();
        		$attr = reset($attributes->getMapper()->fetchList("code = '".$one["code"]."'"));
        		
        		$db = new Petolio_Model_PoAttributeOptionsMapper();
        		foreach($db->fetchList("attribute_id = '".$attr->getId()."'") as $all) {
        			$one['attr']['multiOptions'][$all->getId()] = Petolio_Service_Util::Tr($all->getValue());
        		}
        	}
        	
        	// add currency before tags
        	if($one['code'] == 'product_tags') {
				// currencies
		        $type = array();
		        $db = new Petolio_Model_DbTable_PoCurrencies();
		        foreach($db->fetchAll($db->select(), "name DESC") as $line)
		        	$type[$line['id']] = Petolio_Service_Util::Tr($line['name']);

				$this->addElement('select', 'primary_currency', array(
		            'label' => $translate->_('Primary Currency'),
					'multiOptions' => $type,
		            'required' => true,
		        ));
        	}

			if($one['tiny'])
				$this->getView()->tinymce = 'tinymce.advanced';

        	if($auth->getIdentity()->type == 2 && $one['code'] == 'product_duration') {}
			else $this->addElement($one['name'], $one['code'], $one['attr']);
        }

        if(!$this->admin) {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Save Product & Go to Pictures >')
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