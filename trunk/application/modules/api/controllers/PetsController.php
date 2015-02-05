<?php

class PetsController extends Petolio_Rest_Controller {

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
			$pets = new Petolio_Model_PoPets();
			$data = $pets->find($id)->toArray();
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
				$pets = new Petolio_Model_PoPets();

				// if we have a _sale attribute and it's set to "Yes" then set the to_adopt flag to 1
				foreach($_REQUEST as $code => $value)
				if(substr($code, strrpos($code, '_sale'), 5) == '_sale' && isset($value) && $value == 1)
					$_REQUEST['to_adopt'] = '1';
				
				// save pet
				$pets->setOptions($_REQUEST)->save(true, true);
				
				// save attributes
				$attr = new Petolio_Model_PoAttributes();
				$attr->getMapper()->getDbTable()->saveAttributeValues($_REQUEST, $pets->getId());
				
				$data = $pets->toArray();
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
				$pet = new Petolio_Model_PoPets();
				$pet->find($id);
				$pet->setDateModified(date('Y-m-d H:i:s', time()));
				$pet->setToAdopt(0);
				
				// if we have a _sale attribute and it's set to "Yes" then set the to_adopt flag to 1
				foreach($_REQUEST as $code => $value)
					if(substr($code, strrpos($code, '_sale'), 5) == '_sale' && isset($value) && $value == 1)
						$pet->setToAdopt(1);
				
				// save pet
				$pet->save(true, true);
				
				// save attributes
				$attr = new Petolio_Model_PoAttributes();
				$attr->getMapper()->getDbTable()->saveAttributeValues($_REQUEST, $pet->getId());

				$data = $pet->toArray();
				
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
				$pet = new Petolio_Model_PoPets();
				$pet->find($id);
				$pet->setDeleted(1)->save();
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
	 * Load pet attributes
	 */
	protected function attributes() {
		$pets = new Petolio_Model_PoPets();
		$pets->find($_REQUEST['pet_id']);
		if ($pets) {
			$attributes = new Petolio_Model_PoAttributes();
			$attr = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($pets, true));
			foreach ($attr as $key => $value) {
				if (!is_array($value->getAttributeEntity())) {
					$attr[$key] = $value->getAttributeEntity()->getValue();
				} else {
					$attr_entity = $value->getAttributeEntity();
					$attr[$key] = $attr_entity[0]->getValue();
				}
			}
			return $attr;
		} else {
			return $this->returnError($this->translate->_('Pet not found.'));
		}
	}
	
	/**
	 * Find pets by criteria; can search after attributes too
	 * @return list of pets with some of the attribute values
	 */
	protected function findByCriteria() {
		// filter by species
		$species = $this->getRequest()->getParam('species', null);
		
		// build filter
		$filter = array("a.deleted = 0 AND x.active = 1 AND x.is_banned != 1");
		if (!is_null($species)) {
			$filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
		}
		$filter = $this->buildSearchFilter($filter);
		
		// get page
		$page = $this->getRequest()->getParam('page', 0);
		
		$config = Zend_Registry::get("config");
		$itemsperpage = $config["pets"]["pagination"]["itemsperpage"];
		
		// get pets
		$pets = new Petolio_Model_PoPets();
		$paginator = $pets->getPets('paginator', $filter, false, false, true);
		$paginator->setItemCountPerPage($itemsperpage);
		$paginator->setCurrentPageNumber($page);

		$paginator = $pets->formatPets($paginator);
		
		$results = $paginator->getCurrentItems();
		return $results;
	}
	
	/*
	 * Build pet search filter
	 */
	private function buildSearchFilter($filter = array()) {
		if(strlen($this->getRequest()->getParam('keyword'))) {
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%")." " .
					"OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%")." " .
					"OR d2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%")." " .
					"OR f2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%")." " .
					"OR b.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('keyword'))."%").")";
		}
	
		if(strlen($this->getRequest()->getParam('country'))) {
			$filter[] = "x.country_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->getRequest()->getParam('country'), Zend_Db::BIGINT_TYPE);
		}
	
		if(strlen($this->getRequest()->getParam('zipcode'))) {
			$filter[] = "x.zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->getRequest()->getParam('zipcode')."%");
		}
	
		if(strlen($this->getRequest()->getParam('address'))) {
			$filter[] = "x.address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->getRequest()->getParam('address')."%");
		}
	
		if(strlen($this->getRequest()->getParam('location'))) {
			$filter[] = "x.location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('location'))."%");
		}
	
		if(strlen($this->getRequest()->getParam('owner'))) {
			$filter[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->getRequest()->getParam('owner'))."%");
		}
	
		return implode(' AND ', $filter);
	}
	
}