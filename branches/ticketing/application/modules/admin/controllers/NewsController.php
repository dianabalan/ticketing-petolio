<?php

class NewsController extends Zend_Controller_Action
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
		$this->db->news = new Petolio_Model_PoNews();
		$this->db->cache = new Petolio_Model_PoNewsCache();
    }

    private function _filter() {
    	// get params
    	$title = $this->req->getParam("title", '');
    	$url = $this->req->getParam("url", '');

    	// output filters
    	$this->view->title = $title;
    	$this->view->url = $url;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// handle filter
    	$where = array();

    	// title
    	if(strlen($title) > 0)
    		$where[] = "title LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($title)."%");

    	// url
    	if(strlen($url) > 0)
    		$where[] = "url LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($url)."%");

    	// return filters
    	return $where;
    }

	public function indexAction() {
    	// get filter
    	$where = $this->_filter();

    	// get news
    	$paginator = $this->db->news->fetchListToPaginator(count($where) > 0 ? implode(" AND ", $where) : null, "{$this->view->order} {$this->view->dir}");
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output news
    	$this->view->news = $paginator;
    }

    /**
     * Add
     */
    public function addAction() {
		// rember thy name, ehm.. or title :)
		$populate = array();
		if(!is_null($this->msg->remember_title)) {
			$populate['title'] = $this->msg->remember_title;
			unset($this->msg->remember_title);
		}

		// send form
		$form = new Petolio_Form_News(true);
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

		// parse url
		if(!Petolio_Service_Rss::parse($data['url'])) {
			$this->msg->messages[] = $this->translate->_("Source url is invalid.");
			$this->msg->remember_title = $data['title'];
			return $this->_redirect('admin/news/add');
		}

		// save new user
		$this->db->news->setOptions($data)->save(true, false);

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The Source has been added successfully.");
		return $this->_redirect('admin/news/index');
    }

    /**
     * Resync
     */
    public function resyncAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$news = $this->db->news->find($id);
    	if(!$news->getId()) {
    		$this->msg->messages[] = $this->translate->_("Source does not exist.");
    		return $this->_redirect('admin/news/index');
    	}

		// sync source
		$out = Petolio_Service_Rss::sync($news->getId());

    	// msg and redirect
    	$this->msg->messages[] = $out ? $this->translate->_("Source was resynced.") : $this->translate->_("Source resync error.");
    	return $this->_redirect('admin/news/index');
	}

    /**
     * Delete
     */
    public function deleteAction() {
    	// based on URL
    	$id = (int)$this->req->getParam("id", 0);
    	$news = $this->db->news->find($id);
    	if(!$news->getId()) {
    		$this->msg->messages[] = $this->translate->_("Source does not exist.");
    		return $this->_redirect('admin/news/index');
    	}

		// delete all of the source's cache
		$this->db->cache->getMapper()->getDbTable()->delete("news_id = '{$news->getId()}'");

    	// delete source
    	$news->deleteRowByPrimaryKey();

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("Source was deleted.");
    	return $this->_redirect('admin/news/index');
    }
}