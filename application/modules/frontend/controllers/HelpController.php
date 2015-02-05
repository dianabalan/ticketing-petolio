<?php

class HelpController extends Zend_Controller_Action
{
	private $auth = null;
	private $request = null;
	private $cfg = null;
	private $translate = null;

	private $msg = null;
	private $up = null;
    private $yt_name = null;
    private $unlisted = null;

	private $cache = array();

	private $keyword = false;
	private $answtarg = false;

    public function init() {
    	// init
    	$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");
		$this->translate = Zend_Registry::get('Zend_Translate');

		// session
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->yt_name = isset($_COOKIE["petolio_youtube_title"]) ? $_COOKIE["petolio_youtube_title"] : null;

		// db
		$this->db = new stdClass();
		$this->db->help = new Petolio_Model_PoHelp();
		$this->db->righ = new Petolio_Model_PoHelpRights();
		$this->db->answ = new Petolio_Model_PoHelpAnswers();
		$this->db->fold = new Petolio_Model_PoFolders();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->attr = new Petolio_Model_PoAttributes();
		$this->db->sets = new Petolio_Model_PoAttributeSets();
		$this->db->opts = new Petolio_Model_PoAttributeOptions();
		$this->db->fles = new Petolio_Model_PoFiles();
		$this->db->cmnt = new Petolio_Model_PoComments();
		$this->db->rtng = new Petolio_Model_PoRatings();
		$this->db->subs = new Petolio_Model_PoSubscriptions();
		$this->db->medi = new Petolio_Model_PoMedicalRecords();

		// view
		$this->view->request = $this->request;

		// set unlisted params
		$this->unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
		$this->unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));

		// append the dashboard and help css files
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/dashboard.css'));
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/help.css'));
    }

	// pre
    public function preDispatch() {
		// filter by species ?
		$this->view->types = array();
		$attr = reset($this->db->attr->fetchList("code = 'help_species'"));
		foreach($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'") as $type)
			$this->view->types[$type->getId()] = Petolio_Service_Util::Tr($type->getValue());
		asort($this->view->types);

		// filter by status
		$this->view->status = array(
			'' => $this->translate->_("All"),
			'1' => $this->translate->_("Open"),
			'2' => $this->translate->_("Resolved")
		);
    }

	// post
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

    /**
     * Logged in redirector
     * denies access to certain pages when the user is not logged in
     */
    private function verifyUser() {
		// not logged in
		if(!isset($this->auth->getIdentity()->id)) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to view the requested page.");
			return $this->_helper->redirector('index', 'site');
		}
    }

    /*
	 * Build help search filter
	 */
	private function buildSearchFilter($filter = array()) {
		$search = array();

		// vars
		$keyword = (string)$this->request->getParam('keyword');
		$from = (string)$this->request->getParam('fromdate');
		$to = (string)$this->request->getParam('todate');
		$owner = (string)$this->request->getParam('owner');
		$species = (int)$this->request->getParam('species');
		$status = (int)$this->request->getParam('status');
		$answered = (string)$this->request->getParam('answered');
		$targeted = (string)$this->request->getParam('targeted');

		// keyword
		if(strlen($keyword)) {
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
						"OR d4.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%").")";
			$search[] = $keyword;

			// set keyword search
			$this->keyword = true;
		}

		// from date
    	if(strlen($from)) {
    		$filter[] = "UNIX_TIMESTAMP(a.date_created) >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->parseDate(base64_decode($from)));
    		$search[] = base64_decode($from);
    	}

		// to date
    	if(strlen($to)) {
    		$filter[] = "UNIX_TIMESTAMP(a.date_created) <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->parseDate(base64_decode($to)));
    		$search[] = base64_decode($to);
    	}

		// owner
		if(strlen($owner) > 0) {
			$filter[] = "x.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($owner)."%");
			$search[] = $owner;
		}

		// species
		if($species != 0) {
			$filter[] = "e2.value = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($species, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->types[$species];
		}

		// status
		if($status != 0) {
			$real_value = $status == 1 ? 0 : 1;
			$filter[] = "a.archived = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($real_value, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->status[$status];
		}

		// answered
		if(strlen($answered)) {
			$filter[] = "i.user_id = {$this->auth->getIdentity()->id}";
			$search[] = $this->translate->_('Answered');

			// mark as answered or targeted
			$this->answtarg = true;
		}

		// targeted
		if(strlen($targeted)) {
			if($this->auth->getIdentity()->type == 2)
				$filter[] = "(a.rights = 1 AND i.user_id = {$this->auth->getIdentity()->id}) OR a.rights = 2";
			else
				$filter[] = "(a.rights = 1 AND i.user_id = {$this->auth->getIdentity()->id})";

			$search[] = $this->translate->_('Targeted');

			// mark as answered or targeted
			$this->answtarg = true;
		}

		// set filter
		if(count($search) > 0)
			$this->view->filter = implode(', ', $search);

		// return string
		return implode(' AND ', $filter);
	}

    private function parseDate($date) {
    	// parse to date
    	if(preg_match('/^(\d\d?)\/(\d\d?)\/(\d\d\d\d)$/', $date)) {
    		$part = explode('/', $date);
    		return @mktime(0, 0, 0, $part[0], $part[1], $part[2]);
    	} else {
    		$part = explode('.', $date);
    		return @mktime(0, 0, 0, $part[1], $part[0], $part[2]);
    	}
    }

	/**
	 * List All Questions
	 */
    public function indexAction() {
    	// control search
    	$this->view->search = true;

		// get filter
		$filter = $this->buildSearchFilter();

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("All Questions");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get questions
		$paginator = $this->db->help->getQuestions('paginator', $filter, "date_created DESC", false, $this->keyword, $this->answtarg);
		$paginator->setItemCountPerPage($this->cfg["questions"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output questions
		$this->view->questions = $this->db->help->formatQuestions($paginator);

		// add user info
		foreach($this->view->questions as &$one) {
			if(!isset($this->cache[$one['user_id']]))
				$this->cache[$one['user_id']] = $this->_helper->userinfo($one['user_id']);

			$one['user'] = $this->cache[$one['user_id']];
		}
	}

	/**
	 * List My Open Questions
	 */
	public function myquestionsAction() {
		// verify user
		$this->verifyUser();

		// control search
		$this->view->search = true;
		$this->view->mine = true;

		// get filter
		$filter = $this->buildSearchFilter(array("a.archived = 0", "a.user_id = '{$this->auth->getIdentity()->id}'"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("My Open Questions");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get questions
		$paginator = $this->db->help->getQuestions('paginator', $filter, "date_created DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->cfg["questions"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output questions
		$this->view->questions = $this->db->help->formatQuestions($paginator);
	}

    /**
     * Render question options (the left side menu)
     */
    private function questionOptions($question, $attr) {
		// can answer
		$this->view->can = $this->canAnswer($question);

		// question and attributes
		$this->view->question = $question;
		$this->view->question_attr = $attr;

		// render this
		$this->view->render('help/question-options.phtml');
    }

	/**
	 * Get the friends and partners
	 * @return array
	 */
	private function users($id = 0) {
		// default
		if($id == 0)
			$id = $this->auth->getIdentity()->id;

		// load user's friends and partners
		$this->db->user->find($id);
		$all = array_merge($this->db->user->getUserFriends(), $this->db->user->getUserPartners());

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		// return array
		return array_keys($result);
	}

	/**
	 * Add Question
	 */
	public function addAction() {
		// verify user
		$this->verifyUser();

		// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST["help_species"])) {
			$saved = $_POST["help_species"];
			$output = '';
			foreach($_POST["help_species"] as $one) {
				$this->db->opts->find($one);
				$value = $one == 0 ? Petolio_Service_Util::Tr("All") : Petolio_Service_Util::Tr($this->db->opts->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST["help_species"] = substr($output, 0, -1);
		}

		// load medical id
		$medical_id = (int)@$_POST['pet_medical_id'];
		if($medical_id > 0) {
			$med = $this->db->medi->find($medical_id);
			if($med->getId())
				$this->view->medical = array(
					$med->getId(),
					$med->getHeadline1()
				);
		}

		// create form
		$form = new Petolio_Form_Help('po_help');
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// everything fine, so put back the help_species
		$data["help_species"] = $saved;

		// set medical id
		$medical_id = (int)@$_POST['pet_medical_id'];

		// save question
		$setid = reset(reset($this->db->sets->getMapper()->getDbTable()->getAttributeSets('po_help')));
		$this->db->help->setOptions(array(
			'user_id' => $this->auth->getIdentity()->id,
			'attribute_set_id' => $setid['id'],
			'pet_id' => strlen($data['pet_id']) ? $data['pet_id'] : new Zend_Db_Expr('NULL'),
			'pet_medical_id' => $medical_id > 0 ? $medical_id : new Zend_Db_Expr('NULL')
		))->save(true, true);

		// set medical to public
		if($medical_id > 0)
			$this->db->medi->find($data['pet_medical_id'])->setRights(1)->save();

		// save rights
		$this->db->help->getMapper()->setPrivacySetting($this->db->help->getId(), $data['rights'], $this->auth->getIdentity()->id, $this->db->righ);

		// save friends and partners
		if($data['rights'] == 1)
			$this->db->righ->getMapper()->setCustomUsers($this->db->help->getId(), $this->users());

		// unset rights here
		unset($data['rights']);

		// unset pet id
		unset($data['pet_id']);

		// if "All" was selected as help_species
		if($data["help_species"]["0"] == "0") {
			$data["help_species"] = array();

			// get all species and put them in the field
			$attr = reset($this->db->attr->getMapper()->fetchList("code = 'help_species'"));
			foreach($this->db->opts->getMapper()->fetchList("attribute_id = '{$attr->getId()}'") as $all)
				$data["help_species"][] = $all->getId();
		}

		// save attributes
		$this->db->attr->getMapper()->getDbTable()->saveAttributeValues($data, $this->db->help->getId());

		// do html
		$name = Petolio_Service_Parse::do_limit(ucfirst($data["help_title"]), 20, false, true);
		$reply = $this->view->url(array('controller'=>'help', 'action'=>'view', 'question'=>$this->db->help->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has asked a new <u>Question</u>: %2$s');
		$html = array(
			'%1$s has asked a new <u>Question</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$name}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('question', array($html, $reply, $this->auth->getIdentity()->id, $this->db->help->getId()));

		// redirect to files
		$this->msg->messages[] = $this->translate->_("Your question has been added successfully.");
		return $this->_redirect('help/files/question/'. $this->db->help->getId());
	}

	/**
	 * Question - Answer
	 */
	public function answerAction() {
		// verify user
		$this->verifyUser();

		// ignored cache
		$ignored = array();

		// get question
		$question = $this->getQuestion(false, null);

		// load menu
		$this->questionOptions($question, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question, true)));

		// cannot answer question?
		if(!$this->view->can) {
			$this->msg->messages[] = $this->translate->_("You cannot post an answer to this question.");
			return $this->_redirect('help/view/question/'. $question->getId());
		}

		// create form
		$form = new Petolio_Form_Tiny($this->translate->_("Save Answer & Go to Files >"));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save answer
		$this->db->answ->setOptions(array(
			'help_id' => $question->getId(),
			'user_id' => $this->auth->getIdentity()->id,
			'answer' => $data['message']
		))->save(true, false);

		// find the question owner
		$this->db->user->find($question->getUserId());

		// send notification of answer to the owner of the question
		if($this->auth->getIdentity()->id != $question->getUserId())
			Petolio_Service_Message::send(array(
				'subject' => $this->translate->_("Somebody has answered your Opinion question!"),
				'message_html' =>
					sprintf(
						$this->translate->_('%1$s has answered your question: %2$s'),
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'help', 'action'=>'view', 'question' => $question->getId()), 'default', true)}'>".ucfirst($this->view->question_attr['title']->getAttributeEntity()->getValue())."</a>"
					) . '<br /><br />' .
					sprintf(
						$this->translate->_('You can %s the answer.'),
						"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'help', 'action'=>'view', 'question' => $question->getId()), 'default', true)}'>".$this->translate->_('Read')."</a>"
					),
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $this->db->user->getId(),
				'name' => $this->db->user->getName(),
				'email' => $this->db->user->getEmail()
			)), $this->db->user->isOtherEmailNotification());

		// get all existing answers
		$answers = $this->db->answ->getAnswers('array', "a.help_id = {$question->getId()}", "a.date_created ASC", false);

		// send a message to all answers
		foreach($answers as $answer) {
			// ignore question owner and self entries
			if($answer['question_user_id'] != $answer['user_id'] && $answer['user_id'] != $this->auth->getIdentity()->id) {
				if(!in_array($answer['user_id'], $ignored)) {
					// send message
					Petolio_Service_Message::send(array(
						'subject' => $this->translate->_("New activity to a Opinion question you answered"),
						'message_html' =>
							sprintf(
								$this->translate->_('%1$s has also answered a question you follow: %2$s'),
								"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
								"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'help', 'action'=>'view', 'question' => $question->getId()), 'default', true)}'>".ucfirst($this->view->question_attr['title']->getAttributeEntity()->getValue())."</a>"
							) . '<br /><br />' .
							sprintf(
								$this->translate->_('You can %s the answer.'),
								"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'help', 'action'=>'view', 'question' => $question->getId()), 'default', true)}'>".$this->translate->_('Read')."</a>"
							),
						'from' => $this->auth->getIdentity()->id,
						'status' => 1,
						'template' => 'default'
					), array(array(
						'id' => $answer['user_id'],
						'name' => $answer['user_name'],
						'email' => $answer['user_email']
					)), (isset($answer['user_other_email_notification']) && intval($answer['user_other_email_notification']) == 1));

					// add to ignored
					$ignored[] = $answer['user_id'];
				}
			}
		}

		// do html
		$question_name = Petolio_Service_Parse::do_limit(ucfirst($this->view->question_attr['title']->getAttributeEntity()->getValue()), 20, false, true);
		$reply = $this->view->url(array('controller'=>'help', 'action'=>'view', 'question' => $question->getId()), 'default', true);
		$fake = $this->translate->_('%1$s has answered the <u>Question</u>: %2$s');
		$html = array(
			'%1$s has answered the <u>Question</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>{$question_name}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('question', array($html, $reply, $this->auth->getIdentity()->id));

		// redirect to files
		$this->msg->messages[] = $this->translate->_("Your answer has been added successfully.");
		return $this->_redirect('help/files/answer/'. $this->db->answ->getId());
	}

	/**
	 * Question - Get
	 */
	private function getQuestion($auth = false, $archived = 0, $id = false) {
		// get jesus
		if($id) $jesus = $id;
		else $jesus = Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('question'), Zend_Db::BIGINT_TYPE);

		// build where
		if($auth) $where = "id = '{$jesus}' AND user_id = '{$this->auth->getIdentity()->id}'";
		else $where = "id = '{$jesus}'";

		// build where
		if(!is_null($archived))
			$where .= " AND archived = '{$archived}'";

		// get question
		$result = $this->db->help->fetchList($where);
		if(!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("Question does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// the product!
		$question = reset($result);

		// see if the product owner is active and not banned
		if(!($question->getOwner()->getActive() == 1 && $question->getOwner()->getIsBanned() != 1)) {
			$this->msg->messages[] = $this->translate->_("Owner is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		// return product
		return $question;
	}

	/**
	 * Answer - Get
	 */
	private function getAnswer() {
		// get jesus
		$jesus = Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('answer'), Zend_Db::BIGINT_TYPE);

		// get answer
		$result = reset($this->db->answ->getAnswers('array', "a.id = {$jesus}"));
		if(!$result) {
			$this->msg->messages[] = $this->translate->_("Answer does not exist.");
			return $this->_helper->redirector('index', 'site');
		}

		// only for question owner or answer owner
		if(!($result['question_user_id'] == $this->auth->getIdentity()->id ||
			$result['user_id'] == $this->auth->getIdentity()->id)) {
			$this->msg->messages[] = $this->translate->_("You are not allowed to do that.");
			return $this->_helper->redirector('index', 'site');
		}

		// did we get here? cool
		return $result;
	}

	/**
	 * Edit Question
	 */
	public function editAction() {
		// verify user
		$this->verifyUser();

		// if answer send to that
		if(!is_null($this->request->getParam('answer')))
			return $this->editAnswer();

		// if the form doesn't pass the validation then the multi chosen values has to be formatted
		if($this->request->isPost() && $this->request->getPost('submit') && is_array($_POST["help_species"])) {
			$saved = $_POST["help_species"];
			$output = '';
			foreach($_POST["help_species"] as $one) {
				$this->db->opts->find($one);
				$value = $one == 0 ? Petolio_Service_Util::Tr("All") : Petolio_Service_Util::Tr($this->db->opts->getValue());
				$output .= "{$one}|{$value},";
			}

			$_POST["help_species"] = substr($output, 0, -1);
		}

		// get question
		$question = $this->getQuestion(true, null);

		// load question attributes
		$populate = array();
		$attributes = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question));
		foreach($attributes as $attr) {
			$type = $attr->getAttributeInputType();
			if($type->getName() == 'text' && $type->getType() == 'select') { // ajax
				$val = '';

				// all
				if(count($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'")) == count($attr->getAttributeEntity()))
					$val = "0|" . Petolio_Service_Util::Tr("All");

				// load species
				else {
					foreach($attr->getAttributeEntity() as $one) {
						$this->db->opts->find($one->getValue());
						$val .= $one->getValue() . "|" . Petolio_Service_Util::Tr($this->db->opts->getValue()) . ',';
					}
					$val = substr($val, 0, -1);
				}

			} else
				$val = $attr->getAttributeEntity()->getValue();

			$populate[$attr->getCode()] = array("value" => $val, "type" => $attr->getAttributeInputType()->getType());
		}

		// populate rights
		$populate['rights'] = $question->getRights();

		// populate pet id
		$populate['pet_id'] = $question->getPetId();

		// load menu
		$this->questionOptions($question, $attributes);

		// load medical id
		$medical_id = (int)@$_POST['pet_medical_id'];
		if($medical_id == 0) $medical_id = $question->getPetMedicalId();
		if($medical_id > 0) {
			$med = $this->db->medi->find($medical_id);
			if($med->getId())
				$this->view->medical = array(
					$med->getId(),
					$med->getHeadline1()
				);
		}

		// init form
		$form = new Petolio_Form_Help('po_help');
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

		// everything fine, so put back the help_species
		$data["help_species"] = $saved;

		// set medical id
		$medical_id = (int)@$_POST['pet_medical_id'];

		// save question
		$question
			->setDateModified(date('Y-m-d H:i:s', time()))
			->setPetId(strlen($data['pet_id']) ? $data['pet_id'] : new Zend_Db_Expr('NULL'))
			->setPetMedicalId($medical_id > 0 ? $medical_id : new Zend_Db_Expr('NULL'))
			->save(false, true);

		// set medical to public
		if($medical_id > 0)
			$this->db->medi->find($data['pet_medical_id'])->setRights(1)->save();

		// save rights
		$this->db->help->getMapper()->setPrivacySetting($question->getId(), $data['rights'], $this->auth->getIdentity()->id, $this->db->righ);

		// save friends and partners
		if($data['rights'] == 1)
			$this->db->righ->getMapper()->setCustomUsers($question->getId(), $this->users());

		// unset rights here
		unset($data['rights']);

		// unset pet id
		unset($data['pet_id']);

		// if "All" was selected as help_species
		if($data["help_species"]["0"] == "0") {
			$data["help_species"] = array();

			// get all species
			$attr = reset($this->db->attr->getMapper()->fetchList("code = 'help_species'"));
			foreach($this->db->opts->getMapper()->fetchList("attribute_id = '{$attr->getId()}'") as $all)
				$data["help_species"][] = $all->getId();
		}

		// save attributes
		$this->db->attr->getMapper()->getDbTable()->saveAttributeValues($data, $question->getId());

		// redirect to files
		$this->msg->messages[] = $this->translate->_("Your question has been edited successfully.");
		return $this->_redirect('help/files/question/'. $question->getId());
	}

	/**
	 * Answer - Edit
	 */
	private function editAnswer() {
		// verify user
		$this->verifyUser();

		// get answer
		$answer = $this->getAnswer();

		// get question
		$question = $this->getQuestion(false, null, $answer['help_id']);

		// load menu
		$this->questionOptions($question, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question, true)));

		// populate form
		$populate = array();
		$populate['message'] = $answer['answer'];

		// create form
		$form = new Petolio_Form_Tiny($this->translate->_("Save Answer & Go to Files >"));
		$form->populate($populate);
		$this->view->form = $form;
		$this->view->answer = true;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// save answer
		$answer = $this->db->answ->find($answer['id']);
		$answer->setDateModified(date('Y-m-d H:i:s', time()))
			->setAnswer($data['message'])
			->save(false, false);

		// redirect to files
		$this->msg->messages[] = $this->translate->_("Your answer has been edited successfully.");
		return $this->_redirect('help/files/answer/'. $answer->getId());
	}

	/**
	 * Question - Files
	 */
	public function filesAction() {
		// verify user
		$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// if answer
		if(!is_null($this->request->getParam('answer'))) {
			// get answer
			$answer = $this->getAnswer();
			$this->view->answer = $answer;

			// get question
			$question = $this->getQuestion(false, null, $answer['help_id']);

			// get correct url
			$url = 'answer/'. $answer['id'];

		// not answer, still question
		} else {
			// no answer here
			$answer = false;

			// get question
			$question = $this->getQuestion(true, null);

			// get correct url
			$url = 'question/'. $question->getId();
		}

		// load menu
		$this->questionOptions($question, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question, true)));

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}help{$ds}{$question->getId()}{$ds}";

		// create a folder for our question if it does not exist
		$gallery = null;
		if($question->getFolderId()) {
			$search_vars = array('id' => $question->getFolderId());
			$gallery = $this->db->fold->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if(!isset($gallery)) {
			// add the folder
			$vars = array('name' => 'question', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$gallery = $this->db->fold->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our question too
			$question->setFolderId($gallery->getId());
			$question->save();
		}

		// load form
		$form = new Petolio_Form_Upload($this->translate->_('File'), $this->translate->_('Upload Files'));
		$this->view->form = $form;

		// get & show all files
		$extra = $answer ? " AND status = {$answer['id']}" : null;
		$result = $this->db->fles->fetchList("(type = 'image' OR type = 'pdf') AND folder_id = '{$gallery->getId()}'". $extra, "date_created ASC");
		$this->view->gallery = $result;

		// get file remove
		$remove = $this->request->getParam('remove');
		if( isset($remove) ) {
			// get level
			$result = $this->db->fles->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE));
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("File does not exist.");
				return $this->_redirect('help/files/'. $url);
			} else
				$pic = reset($result);

			// delete from hdd
			@unlink($upload_dir . $pic->getFile());
			@unlink($upload_dir . 'thumb_' . $pic->getFile());
			@unlink($upload_dir . 'small_' . $pic->getFile());

			// delete all comments, likes and subscriptions
			$this->db->cmnt->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->rtng->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->subs->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");

			// delete file from db
			$pic->deleteRowByPrimaryKey();

			// msg
			$this->msg->messages[] = $this->translate->_("Your File has been deleted successfully.");
			return $this->_redirect('help/files/'. $url);
		}

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the help directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the services folder on disk.")));
				return $this->_redirect('help/files/'. $url);
			}
		}

		// create the question directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the service folder on disk.")));
				return $this->_redirect('help/files/'. $url);
			}
		}

		// prepare upload files
		$i = 0;
		$errors = array();
		$success = array();

		// get addapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
    	$adapter->addPrefixPaths(array(
    		'validate' => array('Petolio_Validator_' => APPLICATION_PATH.'/forms/validators/')
        ));
		$adapter->addValidator('IsImageorPdf', false);

		// getting the max filesize
		$size = $this->cfg['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if(!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->up->msg['errors'] = array('Critical Error' => array(sprintf($this->translate->_("Your file / files exceed the maximum size limit allowed (%s), nothing was uploaded."), $this->cfg['phpSettings']['upload_max_filesize'])));
				return $this->_redirect('help/files/'. $url);
			}
		}

		// upload each file
		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);

			$adapter->clearFilters();
			$adapter->addFilter('Rename',
				array('target' => $upload_dir . $new_filename, 'overwrite' => true));

			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME)))
				$errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
			else
				$success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
		}

		// go through each file
		foreach($success as $original => $pic) {
			$ext = pathinfo($pic, PATHINFO_EXTENSION);

			// images only
			if($ext != 'pdf') {
				// resize original file if bigger
				$props = @getimagesize($pic);
				list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["pic"]);
				if($props[0] > $w || $props[1] > $h) {
					Petolio_Service_Image::output($pic, $pic, array(
						'type'   => IMAGETYPE_JPEG,
						'width'   => $w,
						'height'  => $h,
						'method'  => THUMBNAIL_METHOD_SCALE_MAX
					));
				}

				// make big thumbnail
				list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["big"]);
				Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MIN
				));

				// make small thumbnail
				list($w, $h) = explode('x', $this->cfg["thumbnail"]["general"]["small"]);
				Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'small_' . pathinfo($pic, PATHINFO_BASENAME), array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MIN
				));
			}

			// save every file in db
			$opt = array(
				'file' => pathinfo($pic, PATHINFO_BASENAME),
				'type' => $ext == 'pdf' ? 'pdf' : 'image',
				'size' => filesize($pic) / 1024,
				'folder_id' => $gallery->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'status' => $answer ? $answer['id'] : 0,
				'description' => $original
			);

			$file = clone $this->db->fles;
			$file->setOptions($opt);
			$file->save();
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back with message if something was updated
		if(count($success) > 0)
			$this->msg->messages[] = $answer ? $this->translate->_("Your answer files have been uploaded successfully.") :
			$this->translate->_("Your question files have been uploaded successfully.");
		return $this->_redirect('help/files/'. $url);
	}

    /**
     * Question Videos
     */
    public function videosAction() {
		// verify user
    	$this->verifyUser();

		// get and unset uploading messages
		$this->view->up = $this->up->msg;
		unset($this->up->msg);

		// get question
		$question = $this->getQuestion(true, null);

		// load menu
		$attrs = reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question, true));
		$this->questionOptions($question, $attrs);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}help{$ds}{$question->getId()}{$ds}";

		// create a folder for our question if it does not exist
		$videos = null;
		if($question->getFolderId()) {
			$search_vars = array('id' => $question->getFolderId());
			$videos = $this->db->fold->getMapper()->getDbTable()->findFolders($search_vars);
		}
		if(!isset($videos)) {
			// add the folder
			$vars = array('name' => 'question', 'petId' => null, 'ownerId' => $this->auth->getIdentity()->id, 'parentId' => 0);
			$videos = $this->db->fold->getMapper()->getDbTable()->addFolder($vars);

			// save the folder in our question too
			$question->setFolderId($videos->getId());
			$question->save();
		}

		// create the help directory
		if(!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
			if(!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the services folder on disk.")));
				return $this->_redirect('help/videos/question/'. $question->getId());
			}
		}

		// create the question directory
		if(!file_exists($upload_dir)) {
			if(!mkdir($upload_dir)) {
				$this->up->msg['errors'] = array('Critical Error' => array($this->translate->_("There was an critical error regarding the creation of the service folder on disk.")));
				return $this->_redirect('help/videos/question/'. $question->getId());
			}
		}

    	// youtube wrapper
		$youtube = Petolio_Service_YouTube::factory('Master');
		$youtube->CFG = array(
			'username' => $this->cfg["youtube"]["username"],
			'password' => $this->cfg["youtube"]["password"],
			'app' => $this->cfg["youtube"]["app"],
			'key' => $this->cfg["youtube"]["key"]
		);

		// create a new video
		$video = new Zend_Gdata_YouTube_VideoEntry();
		$video->setVideoTitle(md5(mt_rand()));
		$video->setVideoDescription(stripslashes(strip_tags(html_entity_decode($attrs['description']->getAttributeEntity()->getValue(), ENT_QUOTES, 'UTF-8'))));
		$video->setVideoCategory('Animals');
		$video->setVideoTags(substr("Question #{$question->getId()}", 0, 30) . ', help, petolio');

		// make video unlisted
		$video->setExtensionElements(array($this->unlisted));

		// get upload form
		$this->view->form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
		$this->view->form['url'] = $this->view->form['url'] . '?nexturl=' . $this->view->url(array('controller'=>'help', 'action'=>'videos', 'question'=>$question->getId()), 'default', true);

		// get all videos and refresh cache
		$result = $this->db->fles->fetchList("type = 'video' AND folder_id = '{$videos->getId()}' AND owner_id = '{$this->auth->getIdentity()->id}'", "id ASC");
		foreach($result as $one)
			$one->setMapper($youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir));

		// output to template
		$this->view->videos = $result;
		$this->view->question = $question;

		// link youtube video ?
		if(isset($_POST['link'])) {
			// see if link is the right format
			$id = Petolio_Service_Util::ExtractYoutubeVideoID($_POST['link']);
			if($id == false) {
				$this->msg->messages[] = $this->translate->_("Your youtube link is invalid.");
				return $this->_redirect('help/videos/question/'. $question->getId());
			}

			// save a filename
			$filename = "{$id}.yt";
			$original_name = "{$_POST['name2']}.yt";

			// see if this id already exists
			$result = $this->db->fles->fetchList("file = '{$filename}' AND folder_id = '{$videos->getId()}'");
			if(is_array($result) && count($result) > 0) {
				$this->msg->messages[] = $this->translate->_("The selected video is already linked in this folder.");
				return $this->_redirect('help/videos/question/'. $question->getId());
			}

			// set the cache, not object? probably an error
			$entry = $youtube->setVideoEntryCache($id, $upload_dir, false);
			if(!is_object($entry)) {
				$this->msg->messages[] = $entry;
				return $this->_redirect('help/videos/question/'. $question->getId());
			}

			// save video in db
			$this->db->fles->setOptions(array(
				'file' => $filename,
				'type' => 'video',
				'size' => 1,
				'folder_id' => $videos->getId(),
				'owner_id' => $this->auth->getIdentity()->id,
				'status' => 0,
				'description' => $original_name
			))->save();

			// msg
			$this->msg->messages[] = $this->translate->_("Your question video link has been successfully added.");
			return $this->_redirect('help/videos/question/'. $question->getId());
		}

		// get video remove
		$remove = $this->request->getParam('remove');
		if(isset($remove)) {
			// get level
			$result = $this->db->fles->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($remove, Zend_Db::BIGINT_TYPE)." AND owner_id = '{$this->auth->getIdentity()->id}'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Video does not exist.");
				return $this->_helper->redirector('index', 'site');
			} else $vid = reset($result);

			// delete from hdd
			@unlink($upload_dir . $vid->getFile());

			// delete all comments, likes and subscriptions
			$this->db->cmnt->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");
			$this->db->rtng->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");
			$this->db->subs->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$vid->getId()}'");

			// delete file from db
			$vid->deleteRowByPrimaryKey();

			// only delete from youtube if its an upload and not a link
			if(round($vid->getSize()) == 0) {
				// find on youtube
				$videoEntryToDelete = null;
				foreach($youtube->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads') as $entry) {
					if($entry->getVideoId() == pathinfo($vid->getFile(), PATHINFO_FILENAME)) {
						$videoEntryToDelete = $entry;
						break;
					}
				}

				// delete from youtube (we dont care about errors at this point)
				try {
					$youtube->delete($videoEntryToDelete);
				} catch (Exception $e) {}
			}

			// msg
			$this->msg->messages[] = $this->translate->_("Your question video has been deleted successfully.");
			return $this->_redirect('help/videos/question/'. $question->getId());
		}

		// no status code ? return here
		if(!isset($_GET['status']))
			return;

		// define arrays
		$errors = array();
		$success = array();

		// do stuff based on status
		switch($_GET['status']) {
			// successfully uploaded
			case '200':
				// check if the name is null or not
				if(is_null($this->yt_name)) {
					$errors[] = $this->translate->_("Video title is empty!");
					break;
				}

				// get video entity
				$videoEntry = $youtube->getVideoEntry($_GET['id'], null, true);

				// set our specified title
				$videoEntry->setVideoTitle($this->yt_name);

				// make video unlisted
				$videoEntry->setExtensionElements(array($this->unlisted));

				// update video on youtube
				$new_entry = $youtube->updateEntry($videoEntry, $videoEntry->getEditLink()->getHref());

				// save a filename
				$filename = "{$_GET['id']}.yt";
				$original_name = "{$this->yt_name}.yt";

				// save a file in the directory
				file_put_contents($upload_dir . $filename, serialize($new_entry));

				// save video in db
				$this->db->fles->setOptions(array(
					'file' => $filename,
					'type' => 'video',
					'size' => 0,
					'folder_id' => $videos->getId(),
					'owner_id' => $this->auth->getIdentity()->id,
					'status' => 0,
					'description' => $original_name
				))->save();

				// set success
				$success[] = $this->yt_name;
			break;

			// error
			default:
				// set error
				$errors[] = $_GET['code'];
			break;
		}

		// save messages
		$this->up->msg['errors'] = $errors;
		$this->up->msg['success'] = $success;

		// redirect back
		if(!(count($errors) > 0))
			$this->msg->messages[] = $this->translate->_("Your question videos have been updated successfully.");

		return $this->_redirect('help/videos/question/'. $question->getId());
    }

    /**
     * Set video name when uploading to youtube
     */
    public function youtubeAction() {
    	// check if name was set
    	if(!isset($_POST['name']) || empty($_POST['name']))
    		return Petolio_Service_Util::json(array('success' => false));

    	setcookie("petolio_youtube_title", $_POST['name'], time() + 86400, "/");
    	return Petolio_Service_Util::json(array('success' => true));
    }

    /**
     * Get Question Files
     * @param obj $question - question object
     */
    private function questionFiles($question) {
    	// got page?
    	$page = $this->request->getParam('page') ? intval($this->request->getParam('page')) : 0;

		// find folder
		$folder = $this->db->fold->getMapper()->getDbTable()->findFolders(array('name' => 'question', 'id' => $question->getFolderId()));
		if(!isset($folder))
			return false;

		// get pictures
		$paginator = $this->db->fles->select2Paginator($this->db->fles->getMapper()->getDbTable()->fetchList("type = 'image' AND folder_id = {$folder->getId()} AND status = 0", "date_created ASC"));
		$paginator->setItemCountPerPage(14);
		$paginator->setCurrentPageNumber($page);

		// create picture array
		$pictures = array();
		foreach ($paginator->getItemsByPage($page) as $row)
			$pictures[$row["id"]] = $row["file"];

		// output pictures
		if(count($pictures) > 0) {
			$this->view->pictures = $pictures;
			$this->view->picture_paginator = $paginator;
		}

   		// get videos
    	$videos = $this->db->fles->fetchList("type = 'video' AND folder_id = {$folder->getId()} AND status = 0", "id ASC", 14);
    	if(isset($videos) && count($videos) > 0) {
    		// youtube wrapper
    		$youtube = Petolio_Service_YouTube::factory('Master');
    		$youtube->CFG = array(
    			'username' => $this->cfg["youtube"]["username"],
    			'password' => $this->cfg["youtube"]["password"],
    			'app' => $this->cfg["youtube"]["app"],
    			'key' => $this->cfg["youtube"]["key"]
    		);

    		// needed upfront
    		$ds = DIRECTORY_SEPARATOR;
    		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}help{$ds}{$question->getId()}{$ds}";

    		// iterate over videos for cached entries
    		foreach($videos as $idx => $one) {
    			// get the cached entry
    			$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir);

    			// error? skip and remove from list
    			if(!is_object($entry)) {
    				unset($videos[$idx]);
    				continue;
    			}

    			// set cached entry
    			$one->setMapper($entry);
    		}

    		// output videos
    		$this->view->videos = $videos;
    	}

		// get pdfs
		$pdfs = $this->db->fles->fetchList("type = 'pdf' AND folder_id = {$folder->getId()} AND status = 0", "id ASC", 14);
		if(isset($pdfs) && count($pdfs) > 0) {
			$pdflist = array();
			foreach ($pdfs as $one)
				$pdflist[$one->getId()] = array(
					'name' => $one->getDescription(),
					'size' => $this->formatSize($one->getSize() * 1024)
				);

    		// output pdfs
    		$this->view->pdfs = $pdflist;
		}
    }

	/**
	 * Format file size
	 * @param int $size - size in bytes
	 *
	 * @return string - Formatted size
	 */
	private function formatSize($size) {
		if($size == 0)
			return null;

		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]);
	}

    /**
     * Question - View
     */
    public function viewAction() {
    	Petolio_Service_Util::saveRequest();
    	
		// get question
		$question = $this->getQuestion(false, null);

		// load menu
		$this->questionOptions($question, reset($this->db->attr->getMapper()->getDbTable()->loadAttributeValues($question, true)));

		// if flagged, load reasons
		$this->view->flagged = array();
		if($question->getFlagged() == 1) {
			$reasons = new Petolio_Model_PoFlagReasons();
			$results = $this->flag->getMapper()->fetchList("scope = 'po_help' AND entry_id = '{$question->getId()}'");
			foreach($results as $line) {
				$reasons->find($line->getReasonId());
				$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
			}
		}

		// get the flag form
		$this->view->flag = new Petolio_Form_Flag();

		// get question pictures
		$this->questionFiles($question);

		// if question is yours tell me :)
		$this->view->admin = $this->auth->hasIdentity() ? $question->getUserId() == $this->auth->getIdentity()->id : false;

		// only increment views if question is not yours
		if(!$this->view->admin)
			$question->setViews(($question->getViews() + 1))->save();

		// format attributes
		$attrs = array();
		$increment = 1;
		foreach ($this->view->question_attr as $attr) {
			// default key
			$key = 'details';

			// title? skip
			if($attr->getCode() == 'help_title')
				continue;

			// question description? skip this
			if($attr->getCode() == 'help_description')
				$key = 'description';

			// array?
			if(is_array($attr->getAttributeEntity())) {
				$val = '';
				if($attr->getCode() == 'help_species' && count($this->db->opts->fetchList("attribute_id = '{$attr->getId()}'")) == count($attr->getAttributeEntity()))
					$val = Petolio_Service_Util::Tr('All');
				else {
					foreach($attr->getAttributeEntity() as $one)
						$val .= Petolio_Service_Util::Tr($one->getValue()) . ', ';
					$val = substr($val, 0, -2);
				}

			// string
			} else $val = $attr->getAttributeEntity()->getValue();

			// set attributes
			if(isset($val) && strlen($val) > 0) {
				// multiple prices
				if(!is_null($attr->getCurrencyId())) {
					$attrs[$key][$this->view->Tr($attr->getLabel())][$attr->getCurrencyId() == $question->getPrimaryCurrencyId() ? 0 : $increment] = $val;
					$increment++;
				} else $attrs[$key][$this->view->Tr($attr->getLabel())] = $val;
			}
		}

		// output attrs
		$this->view->attrs = $attrs;

		// get all answers
		$answers = $this->db->answ->getAnswers('array', "a.help_id = {$question->getId()}", "a.date_created ASC", false);
		foreach($answers as &$answer) {
			// check if owner of that answer
			$answer['owner'] = $this->auth->hasIdentity() ? $answer['user_id'] == $this->auth->getIdentity()->id : false;

			// get pictures
			$answer['pictures'] = array();
			if($question->getFolderId())
				foreach($this->db->fles->fetchList("type = 'image' AND folder_id = {$question->getFolderId()} AND status = {$answer['id']}", "id ASC", 14) as $one)
					$answer['pictures'][$one->getId()] = $one->getFile();

			// get pdfs
			$answer['pdfs'] = array();
			if($question->getFolderId())
				foreach($this->db->fles->fetchList("type = 'pdf' AND folder_id = {$question->getFolderId()} AND status = {$answer['id']}", "id ASC", 14) as $one)
					$answer['pdfs'][$one->getId()] = array(
						'name' => $one->getDescription(),
						'size' => $this->formatSize($one->getSize() * 1024)
					);
		}

		// output answers
		$this->view->answers = $answers;
    }

	/**
	 * Can answer the question or not
	 */
	private function canAnswer($question) {
		// not logged in?
		if(!$this->auth->hasIdentity())
			return false;

		// question is resolved?
		if($question->getArchived() == 1)
			return false;

		// admin
		if($question->getUserId() == $this->auth->getIdentity()->id)
			return true;

		// all
		if($question->getRights() == 0)
			return true;

		// friends
		if($question->getRights() == 1 && in_array($this->auth->getIdentity()->id, $this->users($question->getUserId())))
			return true;

		// service providers
		if($question->getRights() == 2 && $this->auth->getIdentity()->type == 2)
			return true;

		// nope
		return false;
	}

	/**
	 * Question - Finish
	 */
    public function finishAction() {
    	// verify user
		$this->verifyUser();

		// if answer
		if(!is_null($this->request->getParam('answer'))) {
			// get answer
			$answer = $this->getAnswer();

	    	// redirect with message
			$this->msg->messages[] = $this->translate->_("Your answer has been updated successfully.");
			return $this->_redirect('help/view/question/'. $answer['help_id']);

		// if question
		} else {
	    	// redirect with message
			$this->msg->messages[] = $this->translate->_("Your question details have been updated successfully.");
			return $this->_helper->redirector('myquestions', 'help');
		}
    }

	/**
	 * Question - Add to Archive
	 */
    public function archiveAction() {
    	// verify user
		$this->verifyUser();

   		// get question
		$question = $this->getQuestion(true);

		// mark as deleted
		$question->setArchived('1')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your question has been marked as resolved successfully.");
		return $this->_helper->redirector('myquestions', 'help');
    }

	/**
	 * Question - Restore from Archive
	 */
    public function restoreAction() {
		// verify user
		$this->verifyUser();

   		// get question
		$question = $this->getQuestion(true, 1);

		// mark as deleted
		$question->setArchived('0')->save();

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("Your question has been marked as open successfully.");
		return $this->_helper->redirector('myquestions', 'help');
    }

	/**
	 * Question - Archive List
	 */
    public function archivesAction() {
		// verify user
		$this->verifyUser();

		// control search
		$this->view->search = true;
		$this->view->mine = true;

		// get filter
		$filter = $this->buildSearchFilter(array("a.archived = 1"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("My Resolved Questions");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get questions
		$paginator = $this->db->help->getQuestions('paginator', $filter, "date_created DESC", false, $this->keyword);
		$paginator->setItemCountPerPage($this->cfg["questions"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output questions
		$this->view->questions = $this->db->help->formatQuestions($paginator);
    }

	/**
	 * Answer - Delete Answer
	 */
	public function deleteAction() {
		// verify user
		$this->verifyUser();

   		// get answer
		$answer = $this->getAnswer();

		// get question
		$question = $this->getQuestion(false, null, $answer['help_id']);

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}help{$ds}{$question->getId()}{$ds}";

		// delete all files
		$result = $this->db->fles->fetchList("folder_id = {$question->getFolderId()} AND status = {$answer['id']}");
		foreach($result as $pic) {
			// delete from hdd
			@unlink($upload_dir . $pic->getFile());
			@unlink($upload_dir . 'thumb_' . $pic->getFile());
			@unlink($upload_dir . 'small_' . $pic->getFile());

			// delete all comments, likes and subscriptions
			$this->db->cmnt->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->rtng->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
			$this->db->subs->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");

			// delete file from db
			$pic->deleteRowByPrimaryKey();
		}

		// delete answer
		$this->db->answ->getMapper()->getDbTable()->delete("id = {$answer['id']}");

		// message and redirect
		$this->msg->messages[] = $this->translate->_("Your answer was deleted successfully.");
		return $this->_redirect('help/view/question/'. $answer['help_id']);
	}
}