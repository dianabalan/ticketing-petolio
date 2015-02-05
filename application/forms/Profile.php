<?php

class Petolio_Form_Profile extends Petolio_Form_Main
{
	private $type = null;
	private $against = false;
	private $admin = false;

	public function __construct($type = null, $against = false, $admin = false)
	{
		$this->type = $type;
		$this->against = $against;
		$this->admin = $admin;

		parent::__construct();
	}

	private function generate_tree($g_tree, $parent = 0, $indent = 0, &$output_tree = array())
	{
		$tids = array();
		$xid = $loop = 0;

		foreach($g_tree as $cat) {
			if($cat['parent'] == $parent)
				$tids[$xid++] = $loop;

			$loop++;
		}

		if($xid != 0) {
			foreach($tids as $tid) {
				$tmp = array();
				foreach($g_tree[$tid] as $key => $value)
					$tmp[$key] = $value;

				$tmp['indent'] = $indent;
				$output_tree[] = $tmp;

				$this->generate_tree($g_tree, $tmp['id'], $indent + 1, $output_tree);
			}
		}
		else return false;

		return $output_tree;
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
    	$this->setAttrib('accept-charset', 'utf-8');

		$this->addElement('text', 'name', array(
            'label' => $translate->_('Shown Name'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('min' => 5, 'max' => 75))
            ),
        ));

		$this->addElement('text', 'first_name', array(
            'label' => $translate->_('First Name'),
            'validators' => array(
                array('StringLength', false, array('max' => 100))
            ),
			'required' => false
        ));

		$this->addElement('text', 'last_name', array(
            'label' => $translate->_('Last Name'),
            'validators' => array(
                array('StringLength', false, array('max' => 100))
            ),
			'required' => false
        ));

        if($this->type == 2) {
	        $categories = array();
			$cat = new Petolio_Model_PoUsersCategories();
			foreach($cat->getMapper()->fetchAll() as $category)
				$categories[] = array(
					'id' => $category->getId(),
					'parent' => $category->getParentId(),
					'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($category->getName()))
				);

			// sort by name
        	foreach ($categories as $key => $row)
			    $name[$key] = $row['name'];
			array_multisort($name, SORT_ASC, SORT_STRING, $categories);

			$this->addElement('select', 'category_id', array(
	            'label' => $translate->_('Service Category'),
				'attribs' => array('style' => 'tree', 'empty' => $translate->_('Select Category')),
				'multiOptions' => $this->generate_tree($categories, 0, 0),
				'registerInArrayValidator' => false,
	            'required' => false
	        ));
        }

		$this->addElement('text', 'date_of_birth', array(
            'label' => $translate->_('Date of Birth'),
			'attribs' => array('style' => 'date'),
            'required' => false,
		 	'validators' => array(
				'Date'
			),
        ));
		$this->addElement('select', 'gender', array(
            'label' => $translate->_('Gender'),
			'attribs' => array('empty' => $translate->_('Select Gender')),
			'multiOptions' => array('1' => $translate->_('Male'), '2' => $translate->_('Female')),
            'required' => false
        ));
		$users = new Petolio_Model_PoUsers();
		$usersMap = new Petolio_Model_PoUsersMapper();
		$result = $usersMap->findByField('id', $this->against, $users);
        $this->addElement('text', 'email', array(
            'label' => $translate->_('E-Mail'),
            'required' => true,
            'validators' => array(
                array('StringLength', false, array('max'=>150)),
                'EmailAddress',
                array('Regex', false, "/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/"),
                array('Db_NoRecordExists', false, array(
                	'table' => 'po_users',
                	'field' => 'email',
                	'exclude' => array(
                		'field' => 'email',
                		'value' => $result[0]->getEmail()
                	)
                ))
            ),
        ));

        if($this->admin) {
	        $this->addElement('password', 'password', array(
	            'label' => $translate->_('Password'),
	            'required' => false,
	            'validators' => array(
	                array('StringLength', array('min'=> 6, 'max'=>150))
	            ),
	        ));
	        $this->addElement('password', 'confirmpassword', array(
	            'label' => $translate->_('Password <span>(re-type)</span>'),
	            'required' => false,
	            'validators' => array(
	                array('StringLength', array('min'=> 6, 'max'=>150)),
	                array('Identical', false, array('token' => 'password'))
	            )
	        ));
        }

		$this->addElement('text', 'homepage', array(
            'label' => $translate->_('Internet'),
            'validators' => array(
                array('StringLength', false, array('max' => 100))
            ),
			'required' => false
        ));

		$this->addElement('text', 'phone', array(
            'label' => $translate->_('Cell Phone'),
            'validators' => array(
                array('StringLength', false, array('max' => 20))
            ),
			'required' => false
        ));
		$this->addElement('text', 'private_phone', array(
            'label' => $translate->_('Private Phone'),
            'validators' => array(
                array('StringLength', false, array('max' => 20))
            ),
			'required' => false
        ));
		$this->addElement('text', 'business_phone', array(
            'label' => $translate->_('Business Phone'),
            'validators' => array(
                array('StringLength', false, array('max' => 20))
            ),
			'required' => false
        ));
		$this->addElement('text', 'private_fax', array(
            'label' => $translate->_('Private Fax'),
            'validators' => array(
                array('StringLength', false, array('max' => 20))
            ),
			'required' => false
        ));
		$this->addElement('text', 'business_fax', array(
            'label' => $translate->_('Business Fax'),
            'validators' => array(
                array('StringLength', false, array('max' => 20))
            ),
			'required' => false
        ));

		$this->addElement('text', 'street', array(
            'label' => $translate->_('Street'),
            'validators' => array(
                array('StringLength', false, array('max' => 100))
            ),
			'required' => false
        ));
		$this->addElement('text', 'address', array(
            'label' => $translate->_('Address'),
            'validators' => array(
                array('StringLength', false, array('max' => 200))
            ),
			'required' => false
        ));
		$this->addElement('text', 'zipcode', array(
            'label' => $translate->_('Zip Code'),
            'validators' => array(
                array('StringLength', false, array('max' => 10))
            ),
            'required' => false
        ));
		$this->addElement('text', 'location', array(
            'label' => $translate->_('Location'),
            'validators' => array(
                array('StringLength', false, array('max' => 100))
            ),
			'required' => false
        ));

        $countries = array();
		$countriesMap = new Petolio_Model_PoCountriesMapper();
		foreach($countriesMap->fetchAll() as $country)
			$countries[$country->getId()] = $country->getName();
		$this->addElement('select', 'country_id', array(
            'label' => $translate->_('Country'),
			'attribs' => array('empty' => $translate->_('Select Country')),
			'multiOptions' => $countries,
            'required' => false
        ));

		if($this->type == 2) {
			// about us
			$this->getView()->tinymce = 'tinymce';
			$this->addElement('textarea', 'about_us', array(
					'label' => '',
					'attribs' => array('html' => 'class="tinymce"'),
					'required' => false
			));
		}
		
		if(!$this->admin) {
	        $this->addElement('submit', 'submit', array(
	        	'label' => '&nbsp;',
	        	'value' => $translate->_('Save Profile Information >')
	        ));
		} else {
			$this->addElement('select', 'is_admin', array(
				'label' => $translate->_('Admin'),
				'multiOptions' => array('1' => $translate->_('Yes'), '0' => $translate->_('No'))
			));

			$this->addElement('select', 'is_editor', array(
				'label' => $translate->_('Editor'),
				'multiOptions' => array('1' => $translate->_('Yes'), '0' => $translate->_('No'))
			));

	        $this->addElement('submit', 'submit', array(
        		'label' => '&nbsp;',
        		'value' => $translate->_("Submit")
	        ));
		}
    }
}