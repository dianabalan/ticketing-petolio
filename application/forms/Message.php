<?php

class Petolio_Form_Message extends Petolio_Form_Main
{
	private $button_text = null;

	public function __construct($button = null)
	{
		$this->button_text = $button;
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

    	// load user's friends and partners
		$user = new Petolio_Model_PoUsers();
		$user->find(Zend_Auth::getInstance()->getIdentity()->id);
    	$all = array_merge($user->getUserFriends(), $user->getUserPartners());
    	ksort($all); // sort friends / partners

    	// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = $row->getName();

        $this->addElement('multiselect', 'multi_users', array(
            'label' => $translate->_('To'),
			'attribs' => array('html' => "title='".$translate->_('Choose Users')."' style='width:390px;'"),
			'multiOptions' => $result,
            'required' => false
        ));

		$this->addElement('text', 'subject', array(
            'label' => $translate->_('Subject'),
			'attribs' => array('html' => 'style="width:380px;"'),
            'required' => true
        ));

        $this->getView()->tinymce = 'tinymce';
		$this->addElement('textarea', 'message', array(
            'label' => $translate->_('Message'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => true
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => $this->button_text
        ));
    }
}