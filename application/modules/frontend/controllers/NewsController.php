<?php

class NewsController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $request = null;
	private $config = null;

	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->request = $this->getRequest();
		$this->config = Zend_Registry::get("config");

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->news = new Petolio_Model_PoNews();
		$this->db->cache = new Petolio_Model_PoNewsCache();

		// get the sources
		$this->view->sources = array();
		foreach($this->db->news->fetchList() as $source) {
			$this->view->sources[$source->getId()]['title'] = $source->getTitle();
			$this->view->sources[$source->getId()]['date_cached'] = $source->getDateCached();
		}

		// append the menu
		$this->view->request = $this->request;
		$this->view->placeholder('sidebar')->append($this->view->render('news/menu.phtml'));
	}

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

    /*
	 * Build news search filter
	 */
	private function buildSearchFilter($filter = array()) {
		$search = array();

		// handle the vars
		$keyword = strtolower($this->request->getParam('keyword'));
		$source = $this->request->getParam('source');

		// keyword filter
		if(strlen($keyword)) {
			$filter[] = "(title LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%{$keyword}%")." " .
						"OR link LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%{$keyword}%")." " .
						"OR description LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%{$keyword}%")." " .
						"OR author LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%{$keyword}%")." " .
						"OR category LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%{$keyword}%").")";
			$search[] = $this->request->getParam('keyword');

			// set keyword search
			$this->keyword = true;
		}

		// source filter
		if(strlen($source)) {
			$filter[] = "news_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($source, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->sources[$source]['title'];
		}

		if(count($search) > 0)
			$this->view->filter = implode(', ', $search);

		return implode(' AND ', $filter);
	}

	/**
	 * Index
	 */
	public function indexAction() {
		// build filter
		$filter = $this->buildSearchFilter();

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("News");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get cached items
		$paginator = $this->db->cache->fetchListtoPaginator($filter ? $filter : array(), "pubDate DESC");
		$paginator->setItemCountPerPage($this->config["rss"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// prepare output
		$this->view->news = $paginator;
		
		// latest news
		$this->view->latest_news = $this->db->cache->fetchListToArray(null, "pubDate DESC", 3);

		// most viewed
		$this->view->most_viewed = $this->db->cache->fetchListToArray(null, array("viewed DESC", "pubDate DESC"), 3);
		
		// today news
		$this->view->new_entries = $this->db->cache->fetchListToArray("pubDate >= ".strtotime(date("Y-m-d")), "pubDate DESC");
	}
	
	public function incrementViewedAction() {
		$news = $this->db->cache->find($_POST["id"]);
		if ( isset($news)) {
			$news->viewed++;
			$news->save(true, false);
		}
		
		$response = array('success' => true);
		return Petolio_Service_Util::json($response);
		
	}
}