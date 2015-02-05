<?php

class Petolio_Form_ContentDistribution extends Petolio_Form_Main
{
	private $attribute_set = null;

	public function __construct($set = null)
	{
		$this->attribute_set = $set;
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

        $db = new Petolio_Model_DbTable_PoAttributes();
        foreach($db->formAttributes($db->getAttributes($this->attribute_set)) as $one) {
        	if($one['tiny'])
        		$this->getView()->tinymce = 'tinymce.advanced';

        	$this->addElement($one['name'], $one['code'], $one['attr']);
        }

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save Distribution Options')
        ));
    }
}