<?php

class Petolio_Form_ImportProducts extends Petolio_Form_Main
{
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
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);
    	$this->setEnctype(Zend_Form::ENCTYPE_MULTIPART);

// ---------------------------------------------------------
    	$options = array();
    	$users = new Petolio_Model_PoUsers();
    	foreach ($users->fetchList("active = 1 AND is_banned = 0", "name") as $user)
    		$options[$user->getId()] = $user->getName();

		$this->addElement('select', 'user_id', array(
			'label' => $translate->_('Products will be imported for'),
			'attribs' => array('empty' => $translate->_('Select a User')),
			'multiOptions' => $options,
	    	'required' => true,
	    ));
// ---------------------------------------------------------

		$this->addElement('file', 'zip_file', array(
        	'label' => $translate->_('ZIP File'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => array(
				'File',
				'Description',
//				'Errors', # don't show errors
				array(array('data'=>'HtmlTag'), array('tag' => 'div', 'class' => 'cls', 'placement' => 'append')),
				array('Label'),
				array(array('row' => 'HtmlTag'), array('tag' => 'div'))
			)
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Start Import')
        ));
    }
}