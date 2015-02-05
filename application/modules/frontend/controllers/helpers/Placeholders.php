<?php

class Petolio_Controller_Helper_Placeholders extends Zend_Controller_Action_Helper_Abstract {

	private $auth = null;
	private $view = null;

    public function init() {
		$this->auth = Zend_Auth::getInstance();
		$this->view = $this->getActionController()->view;
		$this->view->request = $this->getActionController()->getRequest();
    }

	public function direct($topbar = false) {
		$banners = new Petolio_Model_PoAdBanners();
		/*
		 * if the page is pets/view or adoption/view
		 */
		if ((strcasecmp($this->getFrontController()->getRequest()->getControllerName(), "pets") == 0
				|| strcasecmp($this->getFrontController()->getRequest()->getControllerName(), "adoption") == 0)
				&& strcasecmp($this->getFrontController()->getRequest()->getActionName(), "view") == 0) {
			// display pet sponsoring banner
			$pet_id = $this->getActionController()->getRequest()->getParam('pet', null);
			$banners->getNextAd(1, 1, $pet_id, Zend_Registry::get('Zend_Translate')->getLocale());
		} else {
			// display classical advertising
			$banners->getNextAd(2, null, null, Zend_Registry::get('Zend_Translate')->getLocale());
		}

		$this->view->ad = $banners;

		// are we logged in?
		if($this->auth->hasIdentity()) {
			// get the new messages
			$this->view->identity = $this->auth->getIdentity();
			$messages = new Petolio_Model_PoMessages();
			$this->view->new_messages = $messages->getMapper()->getDbTable()->countNew($this->auth->getIdentity()->id);

			// count all of the help questions
			$questions = new Petolio_Model_PoHelp();
			$this->view->all_questions = $questions->getMapper()->getDbTable()->countAll();

			$config = Zend_Registry::get("config");
			
			// count upcoming events
			$start = new DateTime('now');
			$end = clone $start;
			$end->add(new DateInterval('P'. (intval($config['events']['days'])) .'D'));
			$end->setTime(0, 0, 0);
			
			$this->view->new_events = count(Petolio_Service_Event::loadYourEvents($start, $end));
				
			// count new answers
			$notifications = new Petolio_Model_PoNotifications();
			$this->view->new_answers = $notifications->countNewAnswers($this->auth->getIdentity()->id);
				
			// count friend requests
			$friends = new Petolio_Model_PoFriends();
			$requests_friends = $friends->getMapper()->getDbTable()->findFriends(null, $this->auth->getIdentity()->id, 0);
			$this->view->new_friend_requests = count($requests_friends);

			// count chat channels
			$calendar = new Petolio_Model_PoCalendar();
			$this->view->new_meet2chats = $calendar->countActiveChars($this->auth->getIdentity()->id);
			
			// also render the top bar
			if($topbar == true)
				$this->view->render('topbar.phtml');
		}

		// render side bar
		$this->view->render('sidebar.phtml');
    }
}