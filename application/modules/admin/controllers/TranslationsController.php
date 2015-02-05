<?php

class TranslationsController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $req = null;

	private $db = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->translations = new Petolio_Model_PoTranslations();
    }

    public function indexAction() {
        // action body
    }

    /**
     * Add translation
     */
    public function addTranslationAction() {
    	// send form
    	$form = new Petolio_Form_Translations();
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// save translation
    	$this->db->translations->setOptions($data)->save(false, true);

    	// regenerate cache
    	$cache = new Petolio_Service_Cache();
    	$cache->PoTranslations(true);

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("The translation has been added successfully.");
    	return $this->_redirect("/admin/translations/list-translations");
    }

    /**
     * Edit translation
     */
    public function editTranslationAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$trans = $this->db->translations->find($id);
    	if(!$trans->getId()) {
    		$this->msg->messages[] = $this->translate->_("Translation does not exist.");
    		return $this->_redirect('admin/translations/list-translations');
    	}

    	// send form
    	$form = new Petolio_Form_Translations();
    	$this->view->form = $form;

    	// set data
    	$form->populate($trans->toArray());

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// save translation
    	$trans->setOptions($data)->save(false, true);

    	// regenerate cache
    	$cache = new Petolio_Service_Cache();
    	$cache->PoTranslations(true);

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("The translation has been saved successfully.");
    	return $this->_redirect('admin/translations/list-translations');
    }

    /**
     * Delete translation
     */
    public function deleteTranslationAction() {
        // based on url
    	$id = $this->req->getParam("id", 0);
    	$trans = $this->db->translations->find($id);
    	if(!$trans->getId()) {
    		$this->msg->messages[] = $this->translate->_("Translation does not exist.");
    		return $this->_redirect('admin/translations/list-translations');
    	}

    	// delete translation
    	$trans->deleteRowByPrimaryKey();

    	// regenerate cache
    	$cache = new Petolio_Service_Cache();
    	$cache->PoTranslations(true);

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("The translation has been deleted successfully.");
    	return $this->_redirect('admin/translations/list-translations');
    }

    /**
     * List translations
     */
    public function listTranslationsAction() {
		// based on URL
		$label = $this->req->getParam("label", '');
		$value = $this->req->getParam("value", '');
		$language = $this->req->getParam("language", '');

		// output filters
		$this->view->label = $label;
		$this->view->value = $value;
		$this->view->language = $language;

		// output sorting
		$this->view->order = $this->req->getParam('order', 'id');
		$this->view->dir = $this->req->getParam('dir', 'desc');

		// handle filter
		$where = array();

		// label
		if(strlen($label) > 0)
			$where[] = "label LIKE '%".strtolower($label)."%'";

		// value
		if(strlen($value) > 0)
			$where[] = "value LIKE '%".strtolower($value)."%'";

		// language
		if(strlen($language) > 0)
			$where[] = "language LIKE '%".strtolower($language)."%'";

		// get translations
    	$paginator = $this->db->translations->fetchListToPaginator(count($where) > 0 ? implode(" AND ", $where) : null, "{$this->view->order} {$this->view->dir}");
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output attrs
		$this->view->trans = $paginator;
    }
}