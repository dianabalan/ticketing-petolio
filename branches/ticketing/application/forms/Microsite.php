<?php

class Petolio_Form_Microsite extends Petolio_Form_Main
{
	private $attribute_set = null;
	private $files = array();
	private $file_path = '/images/userfiles/attributes/';

	public function __construct($set = null, $files = array())
	{
		$this->attribute_set = $set;
		$this->files = $files;
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

    	// ---------------------------
    	// step 1
    	// ---------------------------

    	// template
    	if(is_null($this->attribute_set)) {
	        $db = new Petolio_Model_DbTable_PoAttributeSets();
	        $select = $db->select()->setIntegrityCheck(false)
	        	->from(array('s' => 'po_attribute_sets'), array('*'))
	        	->joinLeft(array('t' => 'po_templates'), "s.id = t.attribute_set_id", array())
	        	->where("s.scope = 'po_templates'")
	        	->where("t.scope = 'po_microsites'")
	        	->where("s.active = 1");

	        $type = array();
	        foreach($db->fetchAll($select) as $line)
	        	$type[$line['id']] = Petolio_Service_Util::Tr($line['name']);

			// we want to sort the values if they are translated too so that's why we sort it here
	        asort($type);

			$this->addElement('select', 'attribute_set', array(
	            'label' => $translate->_('Microsite Template'),
				'attribs' => array('empty' => $translate->_('Select a Microsite Template')),
				'multiOptions' => $type,
	            'required' => true,
	        ));

	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Next Step >')
	        ));

			return true;
    	}

    	// ---------------------------
    	// step 2
    	// ---------------------------

    	// url
        $auth = Zend_Auth::getInstance();
		$microsites = new Petolio_Model_PoMicrosites();
		if ( $microsites->getMapper()->findOneByField('user_id', $auth->getIdentity()->id, $microsites) ) {
	        $this->addElement('text', 'url', array(
	            'label' => $translate->_('Url'),
	            'required' => true,
	            'validators' => array(
	                array('StringLength', false, array('max'=>200)),
	                //'Alnum',
	                array('Regex', false, "(^[a-zA-Z0-9-]+$)"),
	                array('Db_NoRecordExists', false, array(
	                	'table' => 'po_microsites',
	                	'field' => 'url',
	                	'exclude' => array(
	                		'field' => 'url',
	                		'value' => $microsites->getUrl()
	                	)
	                ))
	            ),
	            'description' => $translate->_('URL is a field that gives users the opprtunity to start your microsite although they are not logged in. You can use it like a homepage. Please do not use any special characters or blanks. <br/>Example: URL = dogservice -> http://petolio.com/dogservice')
	        ));
		} else {
	        $this->addElement('text', 'url', array(
	            'label' => $translate->_('Url'),
	            'required' => true,
	            'validators' => array(
	                array('StringLength', false, array('max'=>200)),
	                //'Alnum',
	                array('Regex', false, "(^[a-zA-Z0-9-]+$)"),
	                array('Db_NoRecordExists', false, array(
	                	'table' => 'po_microsites',
	                	'field' => 'url'
	                ))
	            ),
	            'description' => $translate->_('URL is a field that gives users the opprtunity to start your microsite although they are not logged in. You can use it like a homepage. Please do not use any special characters or blanks. <br/>Example: URL = dogservice -> http://petolio.com/dogservice')
			));
		}

		// attributes
		$db = new Petolio_Model_DbTable_PoAttributes();
		foreach($db->formAttributes($db->getAttributes($this->attribute_set), $this->files) as $one) {
			if($one['tiny'])
				$this->getView()->tinymce = 'tinymce.advanced';

			$this->addElement($one['name'], $one['code'], $one['attr']);
		}

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Save Microsite & Go to Pictures >')
        ));
    }
}