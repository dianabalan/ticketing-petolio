<?php

class Petolio_Form_Reply extends Petolio_Form_Main
{
	private $button_text = null;
	private $tinymce = true;
	private $admin = false;
	private $guest = false;

	public function __construct($button = null, $tinymce = true, $admin = false, $guest = false)
	{
		$this->button_text = $button;
		$this->tinymce = $tinymce;
		$this->admin = $admin;
		$this->guest = $guest;

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
        ));
    	$this->setMethod(Zend_Form::METHOD_POST);

		if($this->guest) {
			$this->addElement('text', 'from', array(
	            'label' => $translate->_('Email'),
				'attribs' => $this->admin ? array() : array('html' => 'style="width:380px;"'),
	            'required' => true,
	            'validators' => array(
	                'EmailAddress',
	        		array('Regex', false, "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/")
	            )
	        ));
		}

		$this->addElement('text', 'subject', array(
            'label' => $translate->_('Subject'),
			'attribs' => $this->admin ? array() : array('html' => 'style="width:380px;"'),
            'required' => true,
        ));

        if($this->tinymce)
        	$this->getView()->tinymce = 'tinymce';

		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => ($this->tinymce ? array('html' => 'class="tinymce"') : array('html' => 'style="width:380px; height: 180px;"')),
            'required' => true,
        ));

		if($this->admin) {
			$this->addElement('submit', 'submit', array(
				'label' => '&nbsp;',
				'value' => $translate->_('Submit')
			));
		} else {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $this->button_text
	        ));
		}
    }
}