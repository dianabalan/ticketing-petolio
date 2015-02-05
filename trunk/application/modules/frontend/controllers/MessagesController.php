<?php

class MessagesController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $up = null;
	private $auth = null;
	private $request = null;
	private $cfg = null;

	private $usr = null;
	private $mess = null;
	private $messMap = null;
	private $reci = null;
	private $reciMap = null;

	private $messTable = null;

	public function init() {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		$this->usr = new Petolio_Model_PoUsers();
		$this->mess = new Petolio_Model_PoMessages();
		$this->messMap = new Petolio_Model_PoMessagesMapper();
		$this->reci = new Petolio_Model_PoMessageRecipients();
		$this->reciMap = new Petolio_Model_PoMessageRecipientsMapper();

		$this->messTable = new Petolio_Model_DbTable_PoMessages();

		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		// where are we?
		$this->view->action = $this->request->getActionName();

		// product messages?
		$product = $id = (int)$this->request->getParam('product');
		if($product) {
			// get jesus
			$jesus = Zend_Db_Table_Abstract::getDefaultAdapter()->quote($product, Zend_Db::BIGINT_TYPE);

			// db stuff
			$prods = new Petolio_Model_PoProducts();
			$attr = new Petolio_Model_PoAttributes();

			// get product
			$result = $prods->fetchList("id = '{$jesus}' AND user_id = '{$this->auth->getIdentity()->id}' AND archived = '0'");
			if(!(is_array($result) && count($result) > 0)) {
				$this->msg->messages[] = $this->translate->_("Product does not exist.");
				$product = $this->_helper->redirector('index', 'site');
			} else $product = reset($result);

			// output menu and product
			$this->view->product = $product;
			$this->view->product_attr = reset($attr->getMapper()->getDbTable()->loadAttributeValues($product, true));
			$this->view->render('products/product-options.phtml');
			$this->view->placeholder('sidebar')->append($this->view->render('messages/product_menu.phtml'));
		} else $this->view->placeholder('sidebar')->append($this->view->render('messages/menu.phtml'));
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
	 * Index action
	 */
	public function indexAction() {
		$this->view->new = $this->messTable->countNew($this->auth->getIdentity()->id);
		$this->view->stats = array(
			'inbox' => $this->messTable->getInbox($this->auth->getIdentity()->id)->getTotalItemCount(),
			'outbox' => $this->messTable->getOutbox($this->auth->getIdentity()->id)->getTotalItemCount(),
			'drafts' => $this->messTable->getDrafts($this->auth->getIdentity()->id)->getTotalItemCount()
		);
	}

	/*
	 * Send a private message
	 */
	public function sendAction() {
		// get user
		$id = (int)$this->request->getParam('id');
		if(!$id)
			return $this->_helper->redirector('index', 'site');

		// load user
		$this->usr->find($id);
		if(!$this->usr->getId())
			return $this->_helper->redirector('index', 'site');

		// sending to yourself? wtf
		if($this->usr->getId() == $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You cant send a private message to yourself.");
			return $this->_helper->redirector('index', 'site');
		}

		// send user to template
		$this->view->user = $this->usr;

		// init form
		$form = new Petolio_Form_Reply($this->translate->_("Send Message >"));

		// get product
		$product = (string)$this->request->getParam('product');
		if($product) {
			list($product, $text) = explode('|-+-|', base64_decode($product));
			$form->populate(array(
				'subject' => $this->translate->_('Question for the following product:') . ' ' . $text,
				'message' => $this->translate->_('Can you please answer following question:') . '<br /><br /><br />'
			));
		}

		// get service
		$service = (string)$this->request->getParam('service');
		if($service) {
			$form->populate(array(
				'subject' => $this->translate->_('Question for the following service:') . ' ' . base64_decode($service),
				'message' => $this->translate->_('Can you please answer following question:') . '<br /><br /><br />'
			));
		}

		// send form to view
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get form data
		$data = $form->getValues();

		// save new message and send
		$this->mess->setFromUserId($this->auth->getIdentity()->id);
		$this->mess->setSubject($data['subject']);
		$this->mess->setMessage($data['message']);
		$this->mess->setDraftTo(new Zend_Db_Expr('NULL'));
		$this->mess->setStatus('1');
		$this->mess->setDateSent(date('Y-m-d H:i:s'));
		$this->mess->save(true, true);
		$id = $this->mess->getId();

		// send to recipient
		$this->reci = new Petolio_Model_PoMessageRecipients();
		$this->reci->setToUserId($this->usr->getId());
		$this->reci->setMessageId($id);
		$this->reci->save();

		// link message to product
		if($product) {
			$prod = new Petolio_Model_PoMessageProducts();
			$prod->setProductId($product);
			$prod->setMessageId($id);
			$prod->save();
		}

		if ($this->usr->isOtherEmailNotification()) {
			// send email to notify the user
			Petolio_Service_Message::sendEmail(array(
				'subject' => $this->translate->_("New Private Message"),
				'message_html' =>
					sprintf(
						$this->translate->_("You got a private message from %s"),
						"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
					) . '<br /><br />' .
					sprintf(
						$this->translate->_('You can %s the message.'),
						"<a href='{$this->view->url(array('controller'=>'messages', 'action'=>'view', 'id' => $id), 'default', true)}'>".$this->translate->_('View')."</a>"
					),
				'template' => 'default'
			), array(array(
				'id' => $this->usr->getId(),
				'name' => $this->usr->getName(),
				'email' => $this->usr->getEmail()
			)));
		}

		// do html
		$reply = $this->view->url(array('controller'=>'messages', 'action'=>'view', 'id'=>$id), 'default', true);
		$fake = $this->translate->_('%1$s has sent you a %2$s');
		$html = array(
			'%1$s has sent you a %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>" . $this->translate->_('Message') . "</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('message', array($html, $reply, $this->auth->getIdentity()->id, $this->usr->getId()));

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The private message was successfully sent.");
		return $this->_helper->redirector('index', 'messages');
	}

	/*
	 * Compose action
	 */
	public function composeAction()
	{
		// find existing draft
		$result = $populate = null;
		$draft = $this->request->getParam('draft');
		if($draft) {
			$result = $this->messMap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($draft, Zend_Db::BIGINT_TYPE)." AND from_user_id = '{$this->auth->getIdentity()->id}' AND status = '0'");
			if (is_array($result) && count($result) > 0) {
				$result = reset($result);
				$this->view->draft = $result->getId();
				$populate = array(
					'multi_users' => explode(',', $result->getDraftTo()),
					'subject' => $result->getSubject(),
					'message' => array ("value" => $result->getMessage(),
										"type" => "text")
				);
			}
		}

		// something about session, well what is it?
		$namespace = new Zend_Session_Namespace();
		if(isset($namespace->populate)) {
			$populate = $namespace->populate;
			unset($namespace->populate);
		}

		// init form
		$form = new Petolio_Form_Message($this->translate->_("Send Message >"));
		if(!is_null($populate)) $form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// did we have draft ? set that one
		if(isset($_POST['draft']))
			$this->view->draft = $_POST['draft'];

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get form data and show error if no users selected
		$data = $form->getValues();
		if(!isset($data['multi_users']) && !count($data['multi_users']) > 0) {
			$this->view->users_error = true;
			return false;
		}

		// change from draft to sent
		if(isset($_POST['draft'])) {
			$result = $this->messMap->fetchList("id = '{$_POST['draft']}' AND from_user_id = '{$this->auth->getIdentity()->id}' AND status = '0'");
			$result = reset($result);
			$result->setSubject($data['subject']);
			$result->setMessage($data['message']);
			$result->setStatus('1');
			$result->setDraftTo(new Zend_Db_Expr('NULL'));
			$result->setDateSent(date('Y-m-d H:i:s'));
			$result->save(false, true);
			$id = $result->getId();

		// insert as new message
		} else {
			$this->mess->setSubject($data['subject']);
			$this->mess->setMessage($data['message']);
			$this->mess->setFromUserId($this->auth->getIdentity()->id);
			$this->mess->setStatus('1');
			$this->mess->setDateSent(date('Y-m-d H:i:s'));
			$this->mess->save(true, true);
			$id = $this->mess->getId();
		}

		// send to recipients
		foreach($data['multi_users'] as $user) {
			// get user
			$this->usr->getMapper()->find($user, $this->usr);

			// send to recipient
			$this->reci = new Petolio_Model_PoMessageRecipients();
			$this->reci->setToUserId($this->usr->getId());
			$this->reci->setMessageId($id);
			$this->reci->save();

			if ($this->usr->isOtherEmailNotification()) {
				// send email to notify the user
				Petolio_Service_Message::sendEmail(array(
					'subject' => $this->translate->_("New Message"),
					'message_html' =>
						sprintf(
							$this->translate->_("You got a private message from %s"),
							"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
						) . '<br /><br />' .
						sprintf(
							$this->translate->_('You can %s the message.'),
							"<a href='{$this->view->url(array('controller'=>'messages', 'action'=>'view', 'id' => $id), 'default', true)}'>".$this->translate->_('View')."</a>"
						),
					'template' => 'default'
				), array(array(
					'id' => $this->usr->getId(),
					'name' => $this->usr->getName(),
					'email' => $this->usr->getEmail()
				)));
			}
		}

		// do html
		$reply = $this->view->url(array('controller'=>'messages', 'action'=>'view', 'id'=>$id), 'default', true);
		$fake = $this->translate->_('%1$s has sent you a %2$s');
		$html = array(
			'%1$s has sent you a %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			"<a href='{$reply}'>" . $this->translate->_('Message') . "</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('message', array($html, $reply, $this->auth->getIdentity()->id, $this->usr->getId()));

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The message was successfully sent.");
		return $this->_helper->redirector('index', 'messages');
	}

	/*
	 * Save automatically to drafts
	 */
	public function saveAction()
	{
		// format variables
		$_POST['subj'] = $_POST['subj'] == 'null' ? new Zend_Db_Expr('NULL') : $_POST['subj'];
		$_POST['msg'] = $_POST['msg'] == 'null' ? new Zend_Db_Expr('NULL') : $_POST['msg'];
		$_POST['to'] = $_POST['to'] == 'null' ? new Zend_Db_Expr('NULL') : (is_array($_POST['to']) ? implode(',', $_POST['to']) : $_POST['to']);

		// find existing draft either based on id OR one that was already saved based on to, subj and msg
		$draft = $this->request->getParam('draft');
		$table = $this->messMap->getDbTable();
		$info = $table->info();

		// find the message
		if($draft)
			$result = $table->select()->from($info['name'])
				->where(Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('id = ?', $draft, Zend_Db::BIGINT_TYPE))
				->where('from_user_id = ?', $this->auth->getIdentity()->id)
				->where('status = ?', 0);
		else
			$result = $table->select()->from($info['name'])
				->where('from_user_id = ?', $this->auth->getIdentity()->id)
				->where(Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('MD5(subject) = ?', md5($_POST['subj'])))
				->where(Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('MD5(message) = ?', md5($_POST['msg'])))
				->where(Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('draft_to = ?', $_POST['to']))
				->where('status = ?', 0);

		// fetch all
		$result = reset($table->fetchAll($result));

		// no draft found ? insert new draft
		if (!$result) {
			$this->mess->setFromUserId($this->auth->getIdentity()->id);
			if(isset($_POST['parent']))
				$this->mess->setParentMessageId($_POST['parent']);
		// draft found ? update draft
		} else {
			$result = reset($result);
			$this->messMap->find($result['id'], $this->mess);
		}

		// save
		$this->mess->setSubject($_POST['subj']);
		$this->mess->setMessage($_POST['msg']);
		$this->mess->setDraftTo($_POST['to']);
		$this->mess->setDateModified(date('Y-m-d H:i:s'));
		$this->mess->save(true, true);
		$id = $this->mess->getId();

		// return json :)
		Petolio_Service_Util::json(array(
			'draft' => $this->mess->getId(),
			'success' => true
		));
	}

	/**
	 * Build search filter
	 * @return mixed
	 */
	private function buildSearchFilter() {
		$filter = false;
		if (strlen($this->request->getParam('name'))) {
			$filter = "(m.subject LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('name'))."%")." " .
				"OR m.message LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('name'))."%").")";

			$this->view->search_name = $this->request->getParam('name');
			$this->view->title = "Results, Keywords: {$this->request->getParam('name')}";
		}

		return $filter;
	}

	/*
	 * View inbox
	 */
	public function inboxAction() {
		// mass action ?
		if(isset($_POST['mass_action']))
			return $this->mass($_POST['mass_action']);

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'subject') $sort = "m.subject {$this->view->dir}";
		elseif($this->view->order == 'from') $sort = "u.name {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "m.date_sent {$this->view->dir}";
		}

		// get inbox
		$result = $this->messTable->getInbox($this->auth->getIdentity()->id, $sort, $this->buildSearchFilter(), $this->view->product ? $this->view->product->getId() : false);
		$result->setItemCountPerPage($this->cfg["messages"]["pagination"]["itemsperpage"]);
		$result->setCurrentPageNumber($page);

		// output results
		$this->view->inbox = $result;
	}

	/*
	 * View outbox
	 */
	public function outboxAction() {
		// mass action ?
		if(isset($_POST['mass_action']))
			return $this->mass($_POST['mass_action']);

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'subject') $sort = "m.subject {$this->view->dir}";
		elseif($this->view->order == 'to') $sort = "combinedusers {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "m.date_sent {$this->view->dir}";
		}

		// get outbox
		$result = $this->messTable->getOutbox($this->auth->getIdentity()->id, $sort, $this->buildSearchFilter());
		$result->setItemCountPerPage($this->cfg["messages"]["pagination"]["itemsperpage"]);
		$result->setCurrentPageNumber($page);

		// output results
		$this->view->outbox = $result;
	}

	/*
	 * View drafts
	 */
	public function draftAction() {
		// mass action ?
		if(isset($_POST['mass_action']))
			return $this->mass($_POST['mass_action']);

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'subject') $sort = "m.subject {$this->view->dir}";
		elseif($this->view->order == 'to') $sort = "combinedusers {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = array("m.date_modified {$this->view->dir}", "m.date_created {$this->view->dir}");
		}

		// get draft
		$result = $this->messTable->getDrafts($this->auth->getIdentity()->id, $sort, $this->buildSearchFilter());
		$result->setItemCountPerPage($this->cfg["messages"]["pagination"]["itemsperpage"]);
		$result->setCurrentPageNumber($page);

		// output results
		$this->view->drafts = $result;
	}

	/*
	 * Mass read or delete
	 * @param string preferred action
	 */
	private function mass($what) {
		// send to apropriate action
		switch($what) {
			// mass read
			case 'read':
				// mark as read each message
				foreach($_POST['mass_items'] as $id)
					$this->messTable->markAsRead($this->auth->getIdentity()->id, $id);

				// msg & redirect
				$this->msg->messages[] = $this->translate->_("Your selected messages have been successfully marked as read.");
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'messages/inbox');
			break;

			// mass delete
			case 'delete':
				// delete each message
				foreach($_POST['mass_items'] as $id) {
					// attempt to delete
					$result = $this->deleteMessage($id);
					if($result == false)
						continue;
				}

				// msg & redirect
				$this->msg->messages[] = $this->translate->_("Your selected messages have been successfully deleted.");
				return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'messages/inbox');
			break;
		}
	}

	/*
	 * View message
	 */
	public function viewAction()
	{
		// check id
		$id = $this->request->getParam('id');
		if(!$id ) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'messages');
		}

		// check if we are allowed to view this message
		$message = $this->messTable->getMessage($this->auth->getIdentity()->id, $id);
		if(!$message ) {
			$this->msg->messages[] = $this->translate->_("Message does not exists.");
			return $this->_helper->redirector('index', 'site');
		}

		// mark as read
		$this->messTable->markAsRead($this->auth->getIdentity()->id, $id);
		$this->view->message = $message;
		$this->view->me = $this->auth->getIdentity()->id;
	}

	/*
	 * Reply action
	 */
	public function replyAction()
	{
		// find existing draft
		$result = $populate = null;
		$draft = $this->request->getParam('draft');
		if($draft) {
			$result = $this->messMap->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($draft, Zend_Db::BIGINT_TYPE)." AND from_user_id = '{$this->auth->getIdentity()->id}' AND status = '0'");
			if (is_array($result) && count($result) > 0) {
				$result = reset($result);
				$this->view->draft = $result->getId();
				$populate = array(
					'subject' => $result->getSubject(),
					'message' => "\n{$result->getMessage()}"
				);
			}
		}

   		// check id
		$id = $this->request->getParam('id');
		if(!$id)
			return $this->_helper->redirector('index', 'site');

		// check if we are allowed to reply to this message
		$result = $this->reciMap->fetchList("to_user_id = '{$this->auth->getIdentity()->id}' AND message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
		if (!(is_array($result) && count($result) > 0))
			return $this->_helper->redirector('index', 'site');

		// get message
		$message = $this->messTable->getMessage($this->auth->getIdentity()->id, $id);
		if(!$message)
			return $this->_helper->redirector('index', 'site');

		// send to template and figure out populate
		$this->view->message = $message;
		if(is_null($populate))
			$populate = array(
				'subject' => 'RE: '. $message['subject'],
				'message' => array(
					"value" => "<br/><br/><br/>----- Original Message -----<br/>".
						"From: {$message['name']}<br/>".
						"Date: ". Petolio_Service_Util::formatDate($message['date_sent'], null, true, true) ."<br/>".
						"Subject: {$message['subject']}<br/><br/>".
						"{$message['message']}",
					"type" => "text"
				)
			);

		// init form
		$form = new Petolio_Form_Reply($this->translate->_("Send Reply >"));
		$form->populate($populate);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// did we have draft ? set that one
		if(isset($_POST['draft']))
			$this->view->draft = $_POST['draft'];

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get form data
		$data = $form->getValues();

		// change from draft to sent
		if(isset($_POST['draft'])) {
			$result = $this->messMap->fetchList("id = '{$_POST['draft']}' AND from_user_id = '{$this->auth->getIdentity()->id}' AND status = '0'");
			$result = reset($result);
			$result->setSubject($data['subject']);
			$result->setMessage($data['message']);
			$result->setStatus('1');
			$result->setDraftTo(new Zend_Db_Expr('NULL'));
			$result->setDateSent(date('Y-m-d H:i:s'));
			$result->save(false, true);
			$id = $result->getId();

		// else save new message and send
		} else {
			$this->mess->setFromUserId($this->auth->getIdentity()->id);
			if(isset($message['id']))
				$this->mess->setParentMessageId($message['id']);

			$this->mess->setSubject($data['subject']);
			$this->mess->setMessage($data['message']);
			$this->mess->setDraftTo(new Zend_Db_Expr('NULL'));
			$this->mess->setStatus('1');
			$this->mess->setDateSent(date('Y-m-d H:i:s'));
			$this->mess->save();
			$id = $this->mess->getId();
		}

		// send to recipient
		$this->reci = new Petolio_Model_PoMessageRecipients();
		$this->reci->setToUserId($message['from_user_id']);
		$this->reci->setMessageId($id);
		$this->reci->save();

		// get the user who gets a notification
		$this->usr->find($message['from_user_id']);
		if($this->usr->getId() && $this->usr->isOtherEmailNotification()) {
			// send email to notify the user
			Petolio_Service_Message::sendEmail(array(
				'subject' => $this->translate->_("New Private Message"),
				'message_html' =>
					sprintf(
						$this->translate->_("You got a private message from %s"),
						"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
					) . '<br /><br />' .
					sprintf(
						$this->translate->_('You can %s the message.'),
						"<a href='{$this->view->url(array('controller'=>'messages', 'action'=>'view', 'id' => $id), 'default', true)}'>".$this->translate->_('View')."</a>"
					),
				'template' => 'default'
			), array(array(
				'id' => $this->usr->getId(),
				'name' => $this->usr->getName(),
				'email' => $this->usr->getEmail()
			)));
		}

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The message reply was successfully sent.");
		return $this->_helper->redirector('index', 'messages');
	}

	/*
	 * Delete action
	 */
	public function deleteAction()
	{
   		// check id
		$id = (int)$this->request->getParam('id');
		if(!$id)
			return $this->_helper->redirector('index', 'site');

		// get to
		$to = $this->request->getParam('to');

		// attempt to delete
		$result = $this->deleteMessage($id);
		if($result == false) {
			$this->msg->messages[] = $this->translate->_("Invalid request.");
			return $this->_helper->redirector('index', 'site');
		}

		// product involved?
		$product = (int)$this->request->getParam('product');
		$extra_url = $product ? "/product/{$product}" : "";

		// msg & redirect
		$this->msg->messages[] = $this->translate->_("The message was successfully deleted.");
		return $this->_redirect(isset($to) ? "messages/{$to}{$extra_url}" : ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'messages/inbox'));
	}

	/**
	 * Delete message
	 * @param int $id Message ID
	 */
	private function deleteMessage($id) {
		// find message
		$message = $this->messTable->getMessage($this->auth->getIdentity()->id, $id);
		if(!$message)
			return false;

		// if draft, delete imediatly
		if($message['status'] == 0) {
			// remove from db
			$this->removeMessage($id);
		}

		// decide if outbox
		if($message['from_user_id'] == $this->auth->getIdentity()->id) {
			// update to deleted
			$this->messMap->find($id, $this->mess);
			$this->mess->setStatus('2')->save();

			// remove from db if we have deleted status on both sides
			$this->removeMessage($id, 'outbox');
		}

		// else inbox
	  	else {
		   	// update to deleted
			$result = $this->reciMap->fetchList("to_user_id = '{$this->auth->getIdentity()->id}' AND message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
			$result = reset($result);
			$result->setStatus('2')->save();

			// remove from db if we have deleted status on both sides
			$this->removeMessage($id, 'inbox');
	  	}

	  	return true;
	}

	/**
	 * Delete message helper, will keep the database clean!
	 *
	 * @param int $id Message ID
	 * @param string $mode controller action
	 */
	private function removeMessage($id, $mode = 'draft') {
		switch($mode) {
			case 'draft':
				// delete from db
				$this->messMap->find($id, $this->mess);
				$this->mess->deleteRowByPrimaryKey();
			break;

			case 'outbox':
				// find and compare recipients
				$all = $this->reciMap->fetchList("message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
				$deleted = $this->reciMap->fetchList("message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND status = '2'");
				if($all != $deleted)
					return false;

				// delete recipients
				foreach($deleted as $one)
					$one->deleteRowByPrimaryKey();

				// delete from db
				$this->messMap->find($id, $this->mess);
				$this->mess->deleteRowByPrimaryKey();
			break;

			case 'inbox':
				// check main message is deleted
				$this->messMap->find($id, $this->mess);
				if($this->mess->getStatus() != '2')
					return false;

				// find and compare recipients
				$all = $this->reciMap->fetchList("message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
				$deleted = $this->reciMap->fetchList("message_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE)." AND status = '2'");
				if($all != $deleted)
					return false;

				// delete recipients
				foreach($deleted as $one)
					$one->deleteRowByPrimaryKey();

				// delete from db
				$this->mess->deleteRowByPrimaryKey();
			break;
		}
	}
}