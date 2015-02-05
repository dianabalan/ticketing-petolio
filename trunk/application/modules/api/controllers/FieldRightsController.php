<?php

class FieldRightsController extends Petolio_Rest_Controller {

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
			$rights = new Petolio_Model_PoFieldRights();
			$data = $rights->find($id)->toArray();
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

		$data = array('status' => 'success');
		if ( !$id ) {
			// create new entry
			try {
				$right = new Petolio_Model_PoFieldRights();
				$right->setOptions($_REQUEST);
				$right->save(true, true);

				$data = $right->toArray();
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
			$data = array('status' => 'success');
			
			try {
				$rights = new Petolio_Model_PoFieldRights();
				$rights->find($id);
				$rights->setOptions($_REQUEST);
				$rights->save(false, false);

				$data = $rights->toArray();
				
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
				$rights = new Petolio_Model_PoFieldRights();
				$rights->find($id);
				$rights->deleteRowByPrimaryKey();
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
	
}