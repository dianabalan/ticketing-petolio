<?php

class Petolio_Form_Upload extends Petolio_Form_Main
{
	private $text = null;
	private $button = null;

	public function __construct($text = null, $button = null)
	{
		$this->text = $text;
		$this->button = $button;
		parent::__construct();
	}

    public function init()
    {
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
			array('Label'),
			array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'cls', 'placement' => 'append')),
			array(array('row'  => 'HtmlTag'), array('tag' => 'div'))
		);

		$this->addElement('file', 'item_1', array(
        	'label' => $this->text . ' 1',
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

		$this->addElement('file', 'item_2', array(
        	'label' => $this->text . ' 2',
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('file', 'item_3', array(
        	'label' => $this->text . ' 3',
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('file', 'item_4', array(
        	'label' => $this->text . ' 4',
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('file', 'item_5', array(
        	'label' => $this->text . ' 5',
			'attribs' => array('size' => '50', 'style' => 'width: 400px;'),
			'decorators' => $file_dec
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $this->button
        ));
    }
}

