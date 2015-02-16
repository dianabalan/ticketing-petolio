<?php

class RssController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $req = null;

	private $db = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_editor_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->rss = new Petolio_Model_PoRss();
    }

    private function _filter() {
    	// get params
    	$title = $this->req->getParam("title", '');
    	$link = $this->req->getParam("link", '');

    	// output filters
    	$this->view->title = $title;
    	$this->view->link = $link;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// handle filter
    	$where = array();

    	// title
    	if(strlen($title) > 0)
    		$where[] = "title LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($title)."%");

    	// link
    	if(strlen($link) > 0)
    		$where[] = "link LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($link)."%");

    	// return filters
    	return $where;
    }

	public function indexAction() {
    	// get filter
    	$where = $this->_filter();

    	// get news
    	$paginator = $this->db->rss->fetchListToPaginator(count($where) > 0 ? implode(" AND ", $where) : null, "{$this->view->order} {$this->view->dir}");
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output news
    	$this->view->news = $paginator;
    }

    /**
     * Add
     */
    public function addAction() {
    	// populate current date
		$populate['date_created'] = array(
			"day" => date("j"),
			"month" => date("n"),
			"year" => date("Y")
		);

		// send form
		$form = new Petolio_Form_Rss();
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// prepare data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'date_created') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']} " . date('H:i:s', time());
			} else {
				if(!(strlen($line) > 0)) $line = NULL;
			}
		}

		// add author
		$data['author'] = $this->auth->getIdentity()->name;

		// save new news
		$this->db->rss->setOptions($data)->save(true, false);

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The News has been added successfully.");
		return $this->_redirect('editor/rss/index');
    }

    /**
     * Edit
     */
    public function editAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$news = $this->db->rss->find($id);
    	if(!$news->getId()) {
    		$this->msg->messages[] = $this->translate->_("News does not exist.");
    		return $this->_redirect('editor/rss/index');
    	}

		// send form
		$form = new Petolio_Form_Rss();
		$form->populate($this->db->rss->getMapper()->toArray($this->db->rss));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// prepare data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'date_created') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']} " . date('H:i:s', time());
			} else {
				if(!(strlen($line) > 0)) $line = NULL;
			}
		}

		// update news
		$this->db->rss->setOptions($data)->save(true, false);

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The News has been edited successfully.");
		return $this->_redirect('editor/rss/index');
    }

    /**
     * Delete
     */
    public function deleteAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$news = $this->db->rss->find($id);
    	if(!$news->getId()) {
    		$this->msg->messages[] = $this->translate->_("News does not exist.");
    		return $this->_redirect('editor/rss/index');
    	}

    	// delete source
    	$news->deleteRowByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("News was deleted.");
    	return $this->_redirect('editor/rss/index');
    }
}