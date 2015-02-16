<?php

class Zend_View_Helper_Tr extends Zend_View_Helper_Abstract {

	protected $_view;

	public function setView(Zend_View_Interface $view) {
		$this->_view = $view;
	}

	/**
	 * translates a string to the currently selected language
	 * first tries for db translate, then on failure gettext translate 
	 * 
	 * @param string $label
	 */
	public function Tr($label) {
		return Petolio_Service_Util::Tr($label);
	}
}