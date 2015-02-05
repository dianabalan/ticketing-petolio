<?php

class PromotionsController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $auth = null;
	private $config = null;
	private $request = null;

	private $promo = null;
	private $tpl = null;
	private $ev = null;
	private $attr = null;
	private $flag = null;

	private $event = null;

	public function preDispatch()
	{
		// no event id ?
		$id = $this->request->getParam('id');
		if(!$id) {
			$this->msg->messages[] = $this->translate->_("No Event selected.");
			return $this->_helper->redirector('index', 'site');
		}

		// get event
		$this->event = reset($this->ev->getMapper()->getEvents("c.id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND c.type = '2'"));
		if(!$this->event){
			$this->msg->messages[] = $this->translate->_("No Event found.");
			return $this->_helper->redirector('index', 'site');
		}

		// send auth to view and admin, duh
		$this->view->auth = $this->auth;
		$this->view->admin = $this->auth->hasIdentity() && $this->event->getUserId() == $this->auth->getIdentity()->id ? true : false;

		// send event to view
		$owner_id = $this->event->getUserId();
		$event = Petolio_Service_Calendar::format($this->event);
		$this->view->event = $event;
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

	public function init()
	{
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");
		$this->request = $this->getRequest();

		$this->promo = new Petolio_Model_PoPromotions();
		$this->tpl = new Petolio_Model_PoTemplates();
		$this->ev = new Petolio_Model_PoCalendar();
		$this->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->flag = new Petolio_Model_PoFlags();
	}

	/*
	 * Has promotion ?
	 */
	private function hasPromotion() {
		if($this->view->admin) $results = $this->promo->getMapper()->fetchList("event_id = '{$this->event->getId()}'");
		else $results = $this->promo->getMapper()->fetchList("event_id = '{$this->event->getId()}' AND active = '1'");

		if($results) {
			$this->promo = reset($results);
			$this->view->promotion = $this->promo;

			return true;
		} else
			return false;
	}

	/*
	 * Is owner ?
	 */
	private function isOwner() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("This is available only for registered users. Please register and/or login.");
			return $this->_helper->redirector('index', 'site');
		}

		// not service provider ? BYE
		if($this->auth->getIdentity()->type != 2) {
			$this->msg->messages[] = $this->translate->_("Promotions only available for service providers.");
			return $this->_helper->redirector('index', 'site');
		}

		// is owner ?
		if($this->event->getUserId() != $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You are not the owner of that promotion.");
			return $this->_helper->redirector('index', 'site');
		}
	}

	/*
	 * Index action, view promotion or redirect to add
	 */
	public function indexAction()
	{
		// no promotion ? redirect
		if (!$this->hasPromotion()) {
			$this->msg->messages[] = $this->translate->_("No promotion found.");
			return $this->_helper->redirector('index', 'site');
		}

		// if flagged, load reasons
		$this->view->flagged = array();
		if($this->promo->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->flag->getMapper()->fetchList("scope = 'po_promotions' AND entry_id = '{$this->promo->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get template
		$template = $this->tpl->find($this->promo->getTemplateId());
		$data = file_get_contents('../application/modules/frontend/views/templates/' . $template->getFilename());

		// output owner and body
		$this->view->owner = $this->_helper->userinfo($this->event->getUserId());
		$this->view->body = $data;
	}

	/*
	 * Activate promotion
	 */
	public function activateAction()
	{
    	// is owner?
    	$this->isOwner();

		// no promotion ? awww
		if (!$this->hasPromotion()) {
			$this->msg->messages[] = $this->translate->_("You must create a promotion first.");
			return $this->_redirect('promotions/add/id/'. $this->event->getId());
		}

		// activate and redirect
		$this->promo->setActive(1)->save();
		$this->msg->messages[] = $this->translate->_("You have successfully activated your promotion.");
		return $this->_redirect('promotions/index/id/'. $this->event->getId());
	}

	/*
	 * Deactivate promotion
	 */
	public function deactivateAction()
	{
    	// is owner?
    	$this->isOwner();

		// no promotion ? awww
		if (!$this->hasPromotion()) {
			$this->msg->messages[] = $this->translate->_("You must create a promotion first.");
			return $this->_redirect('promotions/add/id/'. $this->event->getId());
		}

		// deactivate and redirect
		$this->promo->setActive(0)->save();
		$this->msg->messages[] = $this->translate->_("You have successfully deactivated your promotion.");
		return $this->_redirect('promotions/index/id/'. $this->event->getId());
	}

	/*
	 * Add action, user can create a promotion for himself
	 */
	public function addAction()
	{
    	// is owner?
    	$this->isOwner();

		// already has a promotion ? go to edit
		if ($this->hasPromotion())
			return $this->_redirect('promotions/edit/id/'. $this->event->getId());

		// create add form
		$this->step1();
	}

	/*
	 * Edit action, user can create a promotion for himself
	 */
	public function editAction()
	{
    	// is owner?
    	$this->isOwner();

		// doesn't have a promotion ? go to add
		if (!$this->hasPromotion())
			return $this->_redirect('promotions/add/id/'. $this->event->getId());

		// create edit form
		$this->step1(true);
	}

    /**
     * Promotion add/edit step 1 - Select template
     */
    private function step1($edit = false)
    {
		// init form
		$form = new Petolio_Form_Promotion();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// get template
		$results = $this->tpl->getMapper()->fetchList("attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($data['attribute_set'], Zend_Db::BIGINT_TYPE)." AND scope = 'po_promotions'");
		$template = reset($results);

		// update promotion
		if($edit == true) {
			$this->promo->setTemplateId($template->getId());
			$this->promo->setDateModified(date('Y-m-d H:i:s'));
			$this->promo->save(false, true);

		// add promotion
		} else {
			$this->promo->setEventId($this->event->getId());
			$this->promo->setUserId($this->auth->getIdentity()->id);
			$this->promo->setTemplateId($template->getId());
			$this->promo->save(true, true);
		}

		// redirect
		return $this->_redirect('promotions/index/id/'. $this->event->getId());
    }
}