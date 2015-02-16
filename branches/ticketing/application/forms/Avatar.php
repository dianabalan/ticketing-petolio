<?php

class Petolio_Form_Avatar extends Petolio_Form_Main
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

		$file_dec = array(
			'File',
			'Description',
//			'Errors', # don't show errors
			array(array('data'=>'HtmlTag'), array('tag' => 'div', 'class' => 'cls', 'placement' => 'append')),
			array('Label'),
			array(array('row' => 'HtmlTag'), array('tag' => 'div'))
		);

		$this->addElement('file', 'avatar', array(
        	'label' => $translate->_('Image File'),
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $translate->_('Upload »')
        ));
    }
}