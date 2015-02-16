<?php

class Petolio_Form_AttachMedicalRecordFiles extends Petolio_Form_Main
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
            'decorator' => array('Petolio_Decorator' => APPLICATION_PATH.'/decorators'),
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);
    	$this->setEnctype(Zend_Form::ENCTYPE_MULTIPART);

        $file_dec = array(
			'File',
			'Description',
			array(array('data'=>'HtmlTag'), array('tag' => 'div', 'class' => 'cls', 'placement' => 'append')),
			array('Label'),
			array(array('row' => 'HtmlTag'), array('tag' => 'div'))
		);

		$this->addElement('file', 'item_1', array(
        	'label' => $translate->_('File 1'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

		$this->addElement('file', 'item_2', array(
        	'label' => $translate->_('File 2'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

		$this->addElement('file', 'item_3', array(
        	'label' => $translate->_('File 3'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

		$this->addElement('file', 'item_4', array(
        	'label' => $translate->_('File 4'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

		$this->addElement('file', 'item_5', array(
        	'label' => $translate->_('File 5'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Upload Medical Record Files')
        ));
    }


}