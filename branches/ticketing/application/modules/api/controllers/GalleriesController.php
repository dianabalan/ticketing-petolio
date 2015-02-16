<?php

class GalleriesController extends Petolio_Rest_Controller {

	/**
	 * The index action handles index/list requests; it should respond with a
	 * list of the requested resources.
	 */
	public function indexAction() {
		$data = array();
		$data = $this->findByCriteria();
		$this->sendResponse($data);
	}
	
	/**
	 * Retrieve a list of resources, or, if an identifier is present, view a single resource
	 * If the ID parameter is set and it isn't numeric then tries to call the function
	 * @see Petolio_Rest_Controller::getAction()
	 */
	public function getAction() {
		parent::getAction();

		$id = $this->_getParam('id', false);

		$data = array();
		if ( $id && is_numeric($id) ) {
			$galleries = new Petolio_Model_PoGalleries();
			$data = $galleries->find($id)->toArray();
		} elseif ($id) {
			$data = call_user_func(array($this, $id));
		} else {
			return $this->returnError($this->translate->_('No action specified.'));
		}
		$this->sendResponse($data);
	}

	/**
	 * Create new entry or call a different action
	 */
	public function postAction() {
		parent::postAction();

		$id = $this->_getParam('id', false);

		$data = array();
		if ( !$id ) {
			// create new entry
			try {
				$galleries = new Petolio_Model_PoGalleries();
				$galleries->setOptions($_REQUEST);
				$galleries->save(true, true);

				$data = $galleries->toArray();
			} catch (Exception $e) {
				$message = "Can't create new entry!";
				$this->getLog()->err($message . chr(10) .
						$e->getMessage() . chr(10) .
						$e->getTraceAsString());
				return $this->returnError($message);
			}
		} else {
			// call another method
			$data = call_user_func(array($this, $id));
		}
		$this->sendResponse($data);
	}

	/**
	 * Update an existing entry
	 */
	public function putAction() {
		parent::putAction();

		$id = $this->_getParam('id', false);

		if ( $id && is_numeric($id) ) {
			$data = array();
			
			try {
				$galleries = new Petolio_Model_PoGalleries();
				$galleries->find($id);
				$galleries->setOptions($_REQUEST);
				$galleries->save(false, false);

				$data = $galleries->toArray();
				
				$this->sendResponse($data);
			} catch (Exception $e) {
				$message = "Can't update the entry!";
				$this->getLog()->err($message . chr(10) .
						$e->getMessage() . chr(10) .
						$e->getTraceAsString());
				return $this->returnError($message);
			}
		} else {
			return $this->returnError("No identifier found.");
		}
	}
	
	/**
	 * The delete action handles DELETE requests and receives an 'id'
	 * parameter; it should delete the resource identified by the 'id' value.
	 */
	public function deleteAction() {
		parent::deleteAction();

		$id = $this->_getParam('id', false);

		if ( $id && is_numeric($id) ) {
			$data = array('status' => 'success');
			
			try {
				$galleries = new Petolio_Model_PoGalleries();
				$galleries->find($id);
				$galleries->setDeleted(1)->save();
			} catch (Exception $e) {
				$message = "Can't delete the entry!";
				$this->getLog()->err($message . chr(10) .
						$e->getMessage() . chr(10) .
						$e->getTraceAsString());
				return $this->returnError($message);
			}
			$this->sendResponse($data);
		} else {
			return $this->returnError("No identifier found.");
		}
	}

	/**
	 * Find pets by criteria; can search after attributes too
	 * @return list of pets with some of the attribute values
	 */
	protected function findByCriteria() {
		// build filter
		$filter = array("deleted = 0");
		$filter[] = "(c.active = 1 AND c.is_banned != 1)";
		$filter = $this->buildSearchFilter($filter);
	
		// get page
		$page = $this->getRequest()->getParam('page', 0);
	
		$config = Zend_Registry::get("config");
		$itemsperpage = $config["galleries"]["pagination"]["itemsperpage"];
	
		// get galleries
		$galleries = new Petolio_Model_PoGalleries();
		$paginator = $galleries->fetchListToPaginator($filter, false);
		$paginator->setItemCountPerPage($itemsperpage);
		$paginator->setCurrentPageNumber($page);

		// go through each item to add picture
		$files = new Petolio_Model_PoFiles();
		foreach($paginator as &$item) {
			// take the first picture
			$picture = !is_null($item['folder_id']) ? $files->fetchList("folder_id = {$item['folder_id']} AND type = 'image'", "date_created ASC") : array();
			$item['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
			$item['pictures_count'] = count($picture);
		}
	
		$results = $paginator->getCurrentItems();
	
		return $results;
	}

	/*
	 * Build pet search filter
	*/
	private function buildSearchFilter($filter = array()) {
		if (strlen($this->getRequest()->getParam('keyword'))) {
			$filter[] = "(a.title LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%")." " .
					"OR a.description LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%").")";
		}
	
		if (strlen($this->getRequest()->getParam('owner'))) {
			$filter[] = "c.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('owner'))."%");
		}
	
		if (strlen($this->getRequest()->getParam('fromdate'))) {
			$from = $this->parseDate(base64_decode($this->getRequest()->getParam('fromdate')));
	
			$filter[] = "UNIX_TIMESTAMP(a.date_created) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($from);
		}
	
		if (strlen($this->getRequest()->getParam('todate'))) {
			$to = $this->parseDate(base64_decode($this->getRequest()->getParam('todate')));
	
			$filter[] = "UNIX_TIMESTAMP(a.date_created) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($to);
		}
	
		return implode(' AND ', $filter);
	}
	
}