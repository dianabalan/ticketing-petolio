<?php

class Petolio_Controller_Helper_Menu extends Zend_Controller_Action_Helper_Abstract {

	private $translate = null;
	private $auth = null;
	private $view = null;

	private $db = null;

	public function init() {
		// needed objects
		$this->auth = Zend_Auth::getInstance();
		$this->translate = Zend_Registry::get('Zend_Translate');

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();
	}

	public function preDispatch() {
		// set view
		$this->view = $this->getActionController()->view;

		// output menu
		$this->view->menu = $this->view->partial('menu.phtml', array(
			'translate' => $this->translate,
			'sets' => $this->db->sets,
			'editor' => $this->auth->hasIdentity() && $this->auth->getIdentity()->is_editor
		));
	}
}