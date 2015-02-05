<?php

class FriendsController extends Zend_Controller_Action
{
    private $translate = null;
    private $msg = null;
    private $up = null;
    private $auth = null;
    private $request = null;
	private $cfg = null;

    private $frnd = null;
    private $fmap = null;
    private $usrs = null;
    private $umap = null;

    private $friends = null;
    private $users = null;

    public function init()
    {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		$this->frnd = new Petolio_Model_PoFriends();
		$this->fmap = new Petolio_Model_PoFriendsMapper();
		$this->usrs = new Petolio_Model_PoUsers();
		$this->umap = new Petolio_Model_PoUsersMapper();

		$this->friends = new Petolio_Model_DbTable_PoFriends();
		$this->users = new Petolio_Model_DbTable_PoUsers();
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

    public function indexAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

		// page ?
        $page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// get pending friends
		$pending_friends = $this->friends->findFriends($this->auth->getIdentity()->id, null, 0);
		ksort($pending_friends);

		// get friend requests
		$requests_friends = $this->friends->findFriends(null, $this->auth->getIdentity()->id, 0);
		ksort($requests_friends);

		// set pending and requests to tpl
    	$this->view->pending = $pending_friends;
		$this->view->requests = $requests_friends;

		// get invited and accepted friends
		$this->usrs->find($this->auth->getIdentity()->id);
		$result = $this->usrs->getUserFriends();

		// guess what ? I INVOKE THY USER INFO HELPER
		foreach($result as &$line)
			$line = $this->_helper->userinfo($line->getId());

		// add pagination
		$paginator = Zend_Paginator::factory($result);
		$paginator->setItemCountPerPage($this->cfg["friends"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output
		$this->view->friends = $paginator;
    }

    public function addAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

    	// init form
		$form = new Petolio_Form_Friend();
		$this->view->form = $form;

		// transform to seo
		if(count($_GET) > 0)
			return $this->_helper->redirector('add', 'friends', 'frontend', array('name' => $_GET['name'], 'submit' => $_GET['submit']));

		// search
		$name = $this->request->getParam('name');
		$submit = $this->request->getParam('submit');
		if(is_null($submit))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid(array('name' => $name, 'submit' => $submit)))
			return false;

		// get paginated result
        $page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// search users
		$users = new Petolio_Model_PoUsers();
		$results = $users->fetchListToPaginator("is_banned != 1 AND active = 1 AND name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($name)."%"), "name ASC");
		$results->setItemCountPerPage($this->cfg["users"]["pagination"]["itemsperpage"]);
		$results->setCurrentPageNumber($page);

		// apply user info on users found
		foreach($results as &$data)
			$data = $this->_helper->userinfo($data['id']);

		// send to output
		$this->view->users = $results;
		$this->view->sent = true;
    }

    public function inviteAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

    	// get who, no who, bye
		$who = $this->request->getParam('id');
		if(!$who)
			return $this->_helper->redirector('index', 'site');

		// hmm ? u trying to add yourself ? LOL
		if($who == $this->auth->getIdentity()->id) {
			$this->msg->messages[] = $this->translate->_("You cant add yourself to your own friends list.");
			return $this->_helper->redirector('index', 'friends');
		}

		// check if its an actual user
		$user = new Petolio_Model_PoUsers();
		$user->find($who);
		if(is_null($user)) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		// check if user already on list
		$link1 = $this->friends->findFriends($this->auth->getIdentity()->id, $who);
		$link2 = $this->friends->findFriends($who, $this->auth->getIdentity()->id);
		if(count($link1) > 0 || count($link2) > 0) {
			$this->msg->messages[] = $this->translate->_("That user already is on your friend list or he added you first and you didn't confirm or decline yet.");
			return $this->_helper->redirector('index', 'friends');
		}

		// init form
		$form = new Petolio_Form_ResponseMessage();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// make link
		$friends = new Petolio_Model_PoFriends();
		$friends->setUserId($this->auth->getIdentity()->id);
		$friends->setFriendId($user->getId());
		$friends->save(true, true);

		$html = sprintf(
					$this->translate->_("%s wants to be friends with you."),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				) . "<br/><br/>";
		$html .= sprintf(
					$this->translate->_('You can %1$s or %2$s the request from %3$s.'),
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'friends', 'action'=>'accept-friendship', 'id' => $friends->getId()), 'default', true)}'>".$this->translate->_('Accept')."</a>",
					"<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$this->view->url(array('controller'=>'friends', 'action'=>'decline-friendship', 'id' => $friends->getId()), 'default', true)}'>".$this->translate->_('Decline')."</a>",
					"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				) . "<br /><br />";
		if ( isset($data['message']) && strlen($data['message']) > 0 ) {
    		$html .=sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("New Friend Request"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have added %s to your friend list. He will accept or deny your invitation."), $user->getName());
		return $this->_helper->redirector('index', 'friends');
    }

    /**
     * @deprecated
     * this action must be removed after all the requests with po_friends.id <= 161 changes status ( !=0 )
     */
    public function acceptAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a friendship.");
			return $this->_redirect('site');
		}

    	// get who, no who, bye
		$who = $this->request->getParam('id');
		if(!$who) {
			Zend_Registry::get('Zend_Log')->debug('Accept friendship problem; id parameter is missing!');
			return $this->_helper->redirector('index', 'site');
		}

		// check if it is indeed what we need
		$link = $this->friends->findFriends($who, $this->auth->getIdentity()->id, 0);
		if(!(count($link) > 0)) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// check if its an actual user
		$user = new Petolio_Model_PoUsers();
		$user->find($who);
		if(is_null($user)) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		$html = sprintf(
			$this->translate->_("%s accepted your friend request!"),
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		);
		
		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: friendship"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// accept it
		$this->friends->acceptFriend($this->auth->getIdentity()->id, $who);

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have accepted %s to your friend list."), $user['name']);
		return $this->_helper->redirector('index', 'friends');
    }

    /**
     * @deprecated
     * this action must be removed after all the requests with po_friends.id <= 161 changes status ( !=0 )
     */
    public function declineAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a friendship.");
			return $this->_redirect('site');
		}

    	// get who, no who, bye
		$who = $this->request->getParam('id');
		if(!$who)
			return $this->_helper->redirector('index', 'site');

		// check if it is indeed what we need
		$link = $this->friends->findFriends($who, $this->auth->getIdentity()->id, 0);
		if(!(count($link) > 0)) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// check if its an actual user
		$user = new Petolio_Model_PoUsers();
		$user->find($who);
		if(is_null($user)) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		$html = sprintf(
			$this->translate->_("%s declined your friend request!"),
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		);

		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: friendship"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// deny it
		$this->friends->declineFriend($this->auth->getIdentity()->id, $who);

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have declined %s to your friend list."), $user['name']);
		return $this->_helper->redirector('index', 'friends');
    }

    /**
     * the new accept friendship action
     * gets as parameter the po_friends.id and NOT po_friends.user_id
     */
    public function acceptFriendshipAction() {

    	// get po_friends.id from parameter
		$id = $this->request->getParam('id');
		if(!$id) {
			Zend_Registry::get('Zend_Log')->debug('Accept friendship problem; id parameter is missing!');
			return $this->_helper->redirector('index', 'site');
		}

		// get link
		$friends = new Petolio_Model_PoFriends();
		$friends->find($id);
		if ( !$friends->getId() ) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// not logged in
		if (!$this->auth->hasIdentity()) {
			// check if the user is logged in other domains
			$user = new Petolio_Model_PoUsers();
			$user->findWithSession($friends->getFriendId());
			if ( $user->getId() && $user->getSessionId() && $user->getSessionId() != null && $user->getSessionId() != session_id() ) {
				Zend_Registry::get('Zend_Log')->debug('Automatically logging in the user '.$user->getName().' with session id '.$user->getSessionId());
				$cookie_params = session_get_cookie_params();
    			setcookie(session_name(), $user->getSessionId(), time() + $cookie_params['lifetime'], "/");
    			$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri(); // or $_SERVER['REQUEST_URI'];
				return $this->_redirect($url, array('prependBase' => false));
			}
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to accept a friendship.");
			return $this->_redirect('site');
		}

		// check if the currently logged in user is the invited user
		if ( $friends->getFriendId() != $this->auth->getIdentity()->id ) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// get the user who invited us
		$user = new Petolio_Model_PoUsers();
		$user->find($friends->getUserId());
		if ( !$user->getId() ) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		$html = sprintf(
			$this->translate->_("%s accepted your friend request!"),
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		);

		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: friendship"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// accept it
		$this->friends->acceptFriend($this->auth->getIdentity()->id, $user->getId());

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have accepted %s to your friend list."), $user->getName());
		return $this->_helper->redirector('index', 'friends');
    }

    /**
     * the new accept friendship action
     * gets as parameter the po_friends.id and NOT po_friends.user_id
     */
    public function declineFriendshipAction() {

    	// get po_friends.id from parameter
		$id = $this->request->getParam('id');
		if(!$id) {
			Zend_Registry::get('Zend_Log')->debug('Accept friendship problem; id parameter is missing!');
			return $this->_helper->redirector('index', 'site');
		}

		// get link
		$friends = new Petolio_Model_PoFriends();
		$friends->find($id);
		if ( !$friends->getId() ) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// not logged in
		if (!$this->auth->hasIdentity()) {
			// check if the user is logged in other domains
			$user = new Petolio_Model_PoUsers();
			$user->findWithSession($friends->getFriendId());
			if ( $user->getId() && $user->getSessionId() && $user->getSessionId() != null && $user->getSessionId() != session_id() ) {
				Zend_Registry::get('Zend_Log')->debug('Automatically logging in the user '.$user->getName().' with session id '.$user->getSessionId());
				$cookie_params = session_get_cookie_params();
    			setcookie(session_name(), $user->getSessionId(), time() + $cookie_params['lifetime'], "/");
    			$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri(); // or $_SERVER['REQUEST_URI'];
				return $this->_redirect($url, array('prependBase' => false));
			}
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to decline a friendship.");
			return $this->_redirect('site');
		}

		// check if the currently logged in user is the invited user
		if ( $friends->getFriendId() != $this->auth->getIdentity()->id ) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// get the user who invited us
		$user = new Petolio_Model_PoUsers();
		$user->find($friends->getUserId());
		if ( !$user->getId() ) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		$html = sprintf(
			$this->translate->_("%s declined your friend request!"),
			"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
		);

		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Answer to your request: friendship"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// deny it
		$this->friends->declineFriend($this->auth->getIdentity()->id, $user->getId());

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have declined %s to your friend list."), $user->getName());
		return $this->_helper->redirector('index', 'friends');
    }

    public function removeAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("You must be logged in to remove a friendship.");
			return $this->_redirect('site');
		}

    	// get who, no who, bye
		$who = $this->request->getParam('id');
		if(!$who)
			return $this->_helper->redirector('index', 'site');

		// check if it is indeed what we need
		$link1 = $this->friends->findFriends($this->auth->getIdentity()->id, $who, 1);
		$link2 = $this->friends->findFriends($who, $this->auth->getIdentity()->id, 1);
		if(!(count($link1) > 0) && !(count($link2) > 0)) {
			$this->msg->messages[] = $this->translate->_("Error: Invalid Link.");
			return $this->_helper->redirector('index', 'friends');
		}

		// check if its an actual user
		$user = new Petolio_Model_PoUsers();
		$user->find($who);
		if(is_null($user)) {
			$this->msg->messages[] = $this->translate->_("User not found.");
			return $this->_helper->redirector('index', 'friends');
		}

		// init form
		$form = new Petolio_Form_ResponseMessage();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		$html = sprintf(
					$this->translate->_("%s removed you from his friend list on Petolio!"),
					"<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
				);
		if ( isset($data['message']) && strlen($data['message']) > 0 ) {
			$html .= "<br/><br/>" . sprintf(
    				$this->translate->_("%s has sent you the following message:"),
				    "<a style='color: #73a329;text-decoration: none;' href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>"
			    ) . "<br/>" . nl2br(Petolio_Service_Util::escape($data['message']));
		}

		// send message and email to the user
		Petolio_Service_Message::send(array(
			'subject' => $this->translate->_("Friend Removed You"),
			'message_html' => $html,
			'from' => $this->auth->getIdentity()->id,
			'status' => 1,
			'template' => 'default'
		), array(array(
			'id' => $user->getId(),
			'name' => $user->getName(),
			'email' => $user->getEmail()
		)), $user->isOtherEmailNotification());

		// remove it
		$this->friends->removeFriend($this->auth->getIdentity()->id, $who);

		// msg & redirect
		$this->msg->messages[] = sprintf($this->translate->_("You have removed %s from your friend list."), $user->getName());
		return $this->_helper->redirector('index', 'friends');
    }

    public function facebookAction()
    {
        // ugh.... !?
    }

    public function googleAction()
    {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_redirect('site');
		}

    	// init form
		$form = new Petolio_Form_Google();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// try to retrieve google contacts
    	try {
			$client = Zend_Gdata_ClientLogin::getHttpClient(
				$_POST['username'], $_POST['password'],
				'cp', null, null,
				isset($_POST['token']) ? $_POST['token'] : null, isset($_POST['captcha']) ? $_POST['captcha'] : null,
				'https://www.google.com/accounts/ClientLogin'
			);

			$gdata = new Zend_Gdata($client);
			$gdata->setMajorProtocolVersion(3);

			// perform query and get result feed
			$query = new Zend_Gdata_Query('http://www.google.com/m8/feeds/contacts/default/full');
			$feed = $gdata->getFeed($query);

			// get email addresses
			$results = array();
			foreach($feed as $entry) {
				$xml = simplexml_load_string($entry->getXML());
				foreach ($xml->email as $e)
					$results[] = (string) $e['address'];
			}
		} catch (Zend_Gdata_App_CaptchaRequiredException $captcha) {
			$this->view->error = $captcha;
    		$this->view->captcha = $captcha->getCaptchaUrl();
    		$this->view->token = $captcha->getCaptchaToken();
    		return true;
    	} catch (Exception $error) {
    		$this->view->error = $error;
    		return true;
		}

		// get users
		$out = array();
		$results = $this->users->matchUsers($results);
		foreach($results as $data)
			$out[] = $this->_helper->userinfo($data['id']);

		// return results
		$this->view->users = $out;
		$this->view->sent = true;
    }

    /*
     * Recommend Petolio to users
    */
    public function recommendAction() {
    	// not logged in? can't use this feature
    	if (!$this->auth->hasIdentity()) {
    		Petolio_Service_Util::saveRequest();
    		$this->msg->messages[] = $this->translate->_("You must be logged in to invite friends.");
    		return $this->_redirect('site');
    	}

    	// init form
    	$form = new Petolio_Form_Invite();
    	$this->view->form = $form;

    	// populate form
    	$form->populate(array(
    		'subject' => sprintf($this->translate->_("Petolio.com:")." %s", $this->translate->_("Invitation")),
    		'message' => array(
    			"value" => sprintf($this->translate->_('%1$s wants to invite you to become a member in Petolio, the community portal for pets owner and service provider. Come to %2$s and join us. Looking forward to meet you.'), $this->auth->getIdentity()->name, "<a href=\"http://www.petolio.com\">www.petolio.com</a>")
    				. "<br /><br />".$this->translate->_("Yours,")."<br />".$this->translate->_("Petolio team"),
    			"type" => "text"
    		)
    	));

    	// did we submit form ? if not just return here
    	if(!($this->request->isPost() && $this->request->getPost('submit')))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->request->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// split emails
    	foreach(explode(' ', $data['email']) as $mail) {
    		// send the email
    		$e = new Petolio_Service_Mail();
    		$e->setRecipient($mail);
    		$e->setReplyTo($this->auth->getIdentity()->email, $this->auth->getIdentity()->name);
    		$e->setSubject($data['subject']);
    		$e->setTemplate('friends/recommend');
    		$e->content = $data['message'];
    		$e->subject = $data['subject'];
    		$e->base_url = PO_BASE_URL;
    		$e->send();
    	}

    	// redirect with success message
    	$this->msg->messages[] = $this->translate->_("Your invitation(s) has been successfully sent.");
    	return $this->_redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'index/invite');
    }
}