<?php
/**
 * Controller for Content Distributions section.
 *
 * @author Laszlo Peter
 */
class ContentdistributionsController extends Zend_Controller_Action {

	private $translate = null;
    private $msg = null;
	private $auth = null;
    private $config = null;
    private $request = null;

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

    public function init() {
		$this->request = $this->getRequest();
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->config = Zend_Registry::get("config");
		$this->auth = Zend_Auth::getInstance();
	}

	public function indexAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		/*
		 * load user distributions
		 */
		$page = $this->request->getParam('your-page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'targetplace') $sort = "targetplace {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "a.date_created {$this->view->dir}";
		}

		// get distributions
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$paginator = $content_distributions->getDistributions('paginator', "a.user_id = {$this->auth->getIdentity()->id}", $sort);
		$paginator->setItemCountPerPage($this->config["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output your distributions
		$this->view->yours = $paginator;
		$this->view->fb_app_id = $this->config["facebook"]["app_id"];
	}

	/**
	 * create new content distribution and save ther options
	 */
	public function addAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to create a distribution.");
			return $this->_redirect('site');
		}

		$attribute_sets = new Petolio_Model_PoAttributeSets();
		$attribute_sets->getMapper()->findOneByField('scope', 'po_content_distributions', $attribute_sets);

		// init form
		$form = new Petolio_Form_ContentDistribution($attribute_sets->getId()); // for now this can only be 62, in the future probably this will come as a parameter
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save pet
		$content_distribution_options = array(
			'url' => uniqid(),
			'user_id' => $this->auth->getIdentity()->id,
			'attribute_set_id' => $attribute_sets->getId()
		);
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$content_distributions->setOptions($content_distribution_options);
		$content_distributions->save(true, true);

		// save attributes
		$attributes = new Petolio_Model_PoAttributes();
		$attributes->getMapper()->getDbTable()->saveAttributeValues($data, $content_distributions->getId());
		$distribution_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($content_distributions, true, null, false));

		$this->msg->messages[] = $this->translate->_("Your distribution options has been saved successfully.");
		if ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Select categories'
					|| $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Individual selection' ) {
			// redirect to select data
			return $this->_redirect('contentdistributions/data/distribution/'. $content_distributions->getId());
		} else {
			// redirect to controller index
			return $this->_redirect('contentdistributions');
		}

	}

	/**
	 * select data for the content distribution
	 */
	public function dataAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to create a distribution.");
			return $this->_redirect('site');
		}

		$content_distributions = new Petolio_Model_PoContentDistributions();
		$content_distributions->find($this->request->getParam('distribution'));
		if ( !$content_distributions->getId() ) {
			$this->msg->messages[] = $this->translate->_("Content distribution cannot be found!");
			return $this->_redirect('contentdistributions');
		}
		$this->view->distribution = $content_distributions;

		// if the form is submitted
		$this->view->errors = array();
		if ( $this->request->isPost() && $this->request->getPost('submit') ) {
			if ( $this->getRequest()->getParam('data') && count($this->getRequest()->getParam('data')) > 0 ) {

				$distribution_data = new Petolio_Model_PoContentDistributionData();
				$distribution_data->delete("content_distribution_id = ".$content_distributions->getId());

				$data = $this->getRequest()->getParam('data');
				foreach ( $data as $item ) {
					$distribution_data = new Petolio_Model_PoContentDistributionData();
					$distribution_data->setContentDistributionId($content_distributions->getId())
									  ->setDataId($item)
									  ->save(true, true);
				}
				$this->msg->messages[] = $this->translate->_("Your distribution data saved successfully.");
				return $this->_redirect('contentdistributions');

			} else {
				$this->view->errors[] = $this->translate->_("Please select at least one category.");
			}
		}

		$attributes = new Petolio_Model_PoAttributes();
		$distribution_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($content_distributions, true, null, false));
		$this->view->distribution_attributes = $distribution_attributes;

		$distribution_data = new Petolio_Model_PoContentDistributionData();
		$selected_data = array();
		foreach ( $distribution_data->fetchList("content_distribution_id = ".$content_distributions->getId()) as $entry ) {
			array_push($selected_data, $entry->getDataId());
		}
		$this->view->selected_data = $selected_data;

		if ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Select categories' ) {

			$attribute_sets = new Petolio_Model_PoAttributeSets();
			$this->view->categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount("p.user_id = ".$this->auth->getIdentity()->id);

		} elseif ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Individual selection' ) {

			$filter = "a.deleted = 0 AND a.user_id = ".$this->auth->getIdentity()->id;
			$sort = "d1.value ASC"; // sort by name

			// get page
			$page = $this->request->getParam('your-page');
			$page = $page ? intval($page) : 0;

			// get pets
			$pets = new Petolio_Model_PoPets();
			$paginator = $pets->getPets('paginator', $filter, $sort, false, false);
			$paginator->setItemCountPerPage($this->config["pets"]["pagination"]["itemsperpage"]);
			$paginator->setCurrentPageNumber($page);

			// output your pets
			$this->view->yours = $pets->formatPets($paginator);


		} elseif ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'All pets' ) {
			$this->msg->messages[] = $this->translate->_("You selected to distribute all pets. Please change it first to be able to select individually.");
			return $this->_redirect('contentdistributions/edit/distribution/'. $content_distributions->getId());
		} elseif ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Pets for adoption' ) {
			$this->msg->messages[] = $this->translate->_("You selected to distribute all pets put up for adoption. Please change it first to be able to select individually");
			return $this->_redirect('contentdistributions/edit/distribution/'. $content_distributions->getId());
		} elseif ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Complete adoption market' ) {
			$this->msg->messages[] = $this->translate->_("You selected to distribute the complete adoption market. Please change it first to be able to select individually");
			return $this->_redirect('contentdistributions/edit/distribution/'. $content_distributions->getId());
		} elseif ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Complete animal shelter' ) {
			$this->msg->messages[] = $this->translate->_("You selected to distribute the complete animal shelter. Please change it first to be able to select individually");
			return $this->_redirect('contentdistributions/edit/distribution/'. $content_distributions->getId());
		}
	}

	/**
	 * add or removes an individual selection to/from a distribution
	 */
	public function dataAddRemoveAction() {
		$add = $this->request->getParam('add') ? $this->request->getParam('add') : 'false';
		$distribution = $this->request->getParam('distribution') ? intval($this->request->getParam('distribution')) : 0;
		$data = $this->request->getParam('data') ? intval($this->request->getParam('data')) : 0;

		$distribution_data = new Petolio_Model_PoContentDistributionData();
		if ( $add == 'true' ) {
			$distribution_data->setContentDistributionId($distribution)
							  ->setDataId($data)
							  ->save(true, true);
		} else {
			$distribution_data->delete("content_distribution_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($distribution)." AND " .
						"data_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($data));
		}
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
     * Archive action
     */
    public function archiveAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to archive a distribution.");
			return $this->_redirect('site');
		}

		// get distribution
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$result = $content_distributions->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('distribution'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Content distribution does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $content_distributions = reset($result);

		// mark as deleted
		$content_distributions->setDeleted(1);
		$content_distributions->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your content distribution has been archived successfully.");
		return $this->_helper->redirector('index', 'contentdistributions');
    }

    /**
     * Restore action
     */
    public function restoreAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to restore a distribution.");
			return $this->_redirect('site');
		}

		// get service
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$result = $content_distributions->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('distribution'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '1'");
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Content distribution does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else $content_distributions = reset($result);

		// mark as deleted
		$content_distributions->setDeleted(0);
		$content_distributions->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your content distribution has been restored successfully.");
		return $this->_helper->redirector('index', 'contentdistributions');
    }

    /**
     * Content distributions archived
     */
    public function archivesAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the archived content distributions.");
			return $this->_redirect('site');
		}

    	// get page
		$page = $this->request->getParam('your-page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'targetplace') $sort = "targetplace {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "a.date_created {$this->view->dir}";
		}

		// get distributions
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$paginator = $content_distributions->getDistributions('paginator', "a.user_id = {$this->auth->getIdentity()->id} AND a.deleted = 1", $sort);
		$paginator->setItemCountPerPage($this->config["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output your services
		$this->view->archived = $paginator;
    }

    /**
	 * modify a content distribution and save their options
	 */
	public function editAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to edit a distribution.");
			return $this->_redirect('site');
		}

		// get content distribution
		$content_distributions = new Petolio_Model_PoContentDistributions();
		$result = $content_distributions->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('distribution'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Content distribution does not exist.");
			return $this->_helper->redirector('index', 'site');
		} else
			$content_distributions = reset($result);

		// load content distribution attributes
		$populate = array();
		$attribute = new Petolio_Model_PoAttributes();
		$attributes = reset($attribute->getMapper()->getDbTable()->loadAttributeValues($content_distributions));
		$current_data = null;
		$data_code = null;
		foreach($attributes as $key => $attr) {
			$type = $attr->getAttributeInputType();
			$val = $attr->getAttributeEntity()->getValue();

			$populate[$attr->getCode()] = array ("value" => $val,
												 "type" => $attr->getAttributeInputType()->getType());
			if ( $key == 'data' ) {
				$current_data = $val;
				$data_code = $attr->getCode();
			}
		}

		// init form
		$form = new Petolio_Form_ContentDistribution($content_distributions->getAttributeSetId()); // for now this can only be 62, in the future probably this will come as a parameter
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save attributes
		$attributes = new Petolio_Model_PoAttributes();
		$attributes->getMapper()->getDbTable()->saveAttributeValues($data, $content_distributions->getId());
		$distribution_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($content_distributions, true, null, false));

		// if the data field was changed then we delete all the selected data
		if ( $data[$data_code] != $current_data ) {
			$distribution_data = new Petolio_Model_PoContentDistributionData();
			$distribution_data->delete("content_distribution_id = ".$content_distributions->getId());
		}

		$this->msg->messages[] = $this->translate->_("Your distribution options has been saved successfully.");
		if ( $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Select categories'
					|| $distribution_attributes['data']->getAttributeEntity()->getValue() == 'Individual selection' ) {
			// redirect to select data
			return $this->_redirect('contentdistributions/data/distribution/'. $content_distributions->getId());
		} else {
			// redirect to controller index
			return $this->_redirect('contentdistributions');
		}
	}

	/**
	 * share window content
	 */
	public function shareAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			$this->msg->messages[] = $this->translate->_("Your session expired. Please log back in.");
			return Petolio_Service_Util::json(array('success' => false, 'html' => ''));
		}

		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true); // make sure the script is not being rendered

		$content_distributions = new Petolio_Model_PoContentDistributions();
		$result = $content_distributions->getMapper()->fetchList("url = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('url'))." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Content distribution does not exist.");
			return Petolio_Service_Util::json(array('success' => false, 'html' => ''));
		} else
			$content_distributions = reset($result);

		$this->view->content_distributions = $content_distributions;

		// load content distribution attributes
		$attribute = new Petolio_Model_PoAttributes();
		$attributes = reset($attribute->getMapper()->getDbTable()->loadAttributeValues($content_distributions, true, null, false));

		$this->view->distribution_attributes = $attributes;

		$html = $this->view->render('contentdistributions/share.phtml');
		return Petolio_Service_Util::json(array('success' => true, 'html' => $html, 'distribution' => $content_distributions->getId()));
	}

	/**
	 * when somebody add's his content as a facebook tab this is the return action
	 * we save all the page tab ids (it could be more then one) and display a success message
	 */
	public function fbreturnAction() {
		$this->_helper->layout->disableLayout();

		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			$this->view->message = $this->translate->_("Your session expired. Please log back in.");
		} else {
			$content_distributions = new Petolio_Model_PoContentDistributions();
			$result = $content_distributions->getMapper()->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('distribution'), Zend_Db::BIGINT_TYPE)." AND user_id = '{$this->auth->getIdentity()->id}' AND deleted = '0'");
			if (!(is_array($result) && count($result) > 0)) {
				$this->view->message = $this->translate->_("Content distribution does not exist.");
			} else {
				$content_distributions = reset($result);

				$tabs = $this->request->getParam("tabs_added", array());
				if ( count($tabs) > 0 ) {
					foreach ($tabs as $tab_id => $tab_count) {
						$distribution_tabs = new Petolio_Model_PoContentDistributionTabs();
						$result = $distribution_tabs->getMapper()->fetchList("tab_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($tab_id, Zend_Db::BIGINT_TYPE));
						if ( is_array($result) && count($result) > 0 ) {
							// update existent
							$distribution_tabs = reset($result);
							$distribution_tabs->setContentDistributionId($content_distributions->getId())
											  ->save(true, true);
						} else {
							// add new
							$distribution_tabs->setContentDistributionId($content_distributions->getId())
											  ->setTabId($tab_id)
											  ->save(true, true);
						}
					}
					$this->view->message = $this->translate->ngettext("Facebook tab added with success.", "Facebook tabs added with success.", count($tabs));
				} else {
					$this->view->message = $this->translate->_("Your content wasn't added to any page.");
				}
			}
		}
	}
}