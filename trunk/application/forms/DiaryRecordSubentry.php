<?php

class Petolio_Form_DiaryRecordSubentry extends Petolio_Form_Main
{

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
	private $picture = array();
	private $upload_destination = false;
	private $path = false;

	public function __construct($pic = array(), $upload = false, $path = false)
	{
		$this->picture = $pic;
		$this->upload_destination = $upload;
		$this->path = $path;
		parent::__construct();
	}
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

    public function init()
    {
    	$translate = Zend_Registry::get('Zend_Translate');
		$config = Zend_Registry::get('config');

    	$this->setName("add_diary_record_subentry");
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

    	$this->addElement('hidden', 'send_notification', array(
    		'value' => '1'
    	));

		$this->addElement('text', 'date', array(
            'label' => $translate->_('Date'),
			'attribs' => array('style' => 'date'),
            'required' => true,
		 	'validators' => array(
				array('Date', false, array('required' => true))
			)
        ));

/** ------------------------- KEEP AS PROTOTYPE -----------------------------------
		$picture_array = array(
			'label' => $translate->_("Picture"),
			'required' => false,
			'attribs' => array(
				'size' => '45',
				'style' => 'width: 388px;'
			),
			'destination' => $this->upload_destination,
			'validators' => array(
				array('IsImage', false),
				array('Size', false, $config['max_filesize'])
			),
			'decorators' => array(
				'File',
				array('ViewScript',
					array('viewScript' => 'file.phtml', 'placement' => false)
				)
			)
		);

		if(isset($this->picture["picture"])) {
			$picture_array['description'] = "<a href='{$this->path}{$this->picture["picture"][1]}' target='_blank'>{$this->picture["picture"][1]}</a>" .
				"<a style='margin-left: 5px;' href='{$this->picture["picture"][0]}'>" .
					"<img style='vertical-align: middle;' src='/images/icons/delete.png' /></a>";

			$picture_array['required'] = false;
		}

		$this->addElement("file", "picture", $picture_array);
----------------------------- KEEP AS PROTOTYPE ------------------------------- **/

        $this->getView()->tinymce = 'tinymce';
        $this->addElement('textarea', 'description', array(
            'label' => $translate->_('Description'),
			'attribs' => array('html' => 'class="tinymce"'),
            'required' => true,
        ));

        $this->addElement('button', 'save_dr', array(
        	'label' => '&nbsp;',
			'attribs' => array('html' => 'id="save_dr" class="submit"'),
        	'value' => $translate->_('Save Subentry & Go to Pictures >')
        ));
    }
}