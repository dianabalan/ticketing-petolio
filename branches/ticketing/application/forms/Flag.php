<?php

class Petolio_Form_Flag extends Petolio_Form_Main
{
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

        $categories = array();
		$cat = new Petolio_Model_PoFlagReasons();
		foreach($cat->getMapper()->fetchAll() as $category)
			$categories[] = array(
				'id' => $category->getId(),
				'parent' => $category->getParentId(),
				'name' => Petolio_Service_Util::Tr($category->getValue())
			);

		$this->addElement('select', 'flag_id', array(
			'label' => '&nbsp;',
			'attribs' => array('style' => 'tree', 'empty' => $translate->_('Select a Reason'), 'html' => "style='width:270px;'"),
			'multiOptions' => $this->generate_tree($categories, 0, 0),
			'registerInArrayValidator' => false,
            'required' => false
        ));

        $this->addElement('submit', 'submit', array(
        	'label' => '&nbsp;',
        	'value' => '&nbsp;',
        ));
    }
}