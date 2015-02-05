<?php

class FlagController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $auth = null;
	private $config = null;

	private $flag = null;
	private $user = null;

	public function preDispatch()
	{
    	// not logged in ?
    	if(!isset($this->auth->getIdentity()->id))
    		return Petolio_Service_Util::json(array('success' => false));
	}

	public function init()
	{
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->auth = Zend_Auth::getInstance();
		$this->config = Zend_Registry::get("config");

		$this->flag = new Petolio_Model_PoFlags();
		$this->user = new Petolio_Model_PoUsers();
	}

	/*
	 * Index action
	 */
	public function indexAction()
	{
	}

    /**
     * Add Flag
     */
    public function addAction() {
    	// no post? piss off
    	if(!$_POST)
    		return Petolio_Service_Util::json(array('success' => false));

    	// split id from owner
    	list($id, $owner) = explode('_', $_POST['a']);

    	// get owner
    	$this->user->find($owner);
    	if(!$this->user->getId())
    		return Petolio_Service_Util::json(array('success' => false));

    	// do class
    	$class = "Petolio_Model_{$_POST['c']}";
    	$db = new $class();

    	// get scope
    	$scope = str_replace("po", "po_", strtolower($_POST['c']));

    	// check if the microsite really exists
    	$db->find($id);
    	if(!$db->getId())
    		return Petolio_Service_Util::json(array('success' => false));

    	// check if we already flagged this microsite
    	$result = $this->flag->getMapper()->fetchList("scope = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($scope)." AND user_id = '{$this->auth->getIdentity()->id}' AND entry_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
    	if(!empty($result))
    		return Petolio_Service_Util::json(array('success' => true, 'msg' => $this->translate->_("You have already flagged this microsite before.")));

    	// add the flag
    	$this->flag->setUserId($this->auth->getIdentity()->id)
    		->setScope($scope)
    		->setEntryId($id)
    		->setReasonId($_POST['b'])
    		->save();

    	// count the flags for this entity and if bigger or equal to the number we set in config, take it down
    	$result = $this->flag->getMapper()->fetchList("scope = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($scope)." AND entry_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
    	if(count($result) >= $this->config["flags"]["count"]) {
			$db->setFlagged(1)->save();

			if ($this->user->isOtherEmailNotification()) {
				// email owner
				Petolio_Service_Message::sendEmail(array(
					'subject' => $this->translate->_("Your content has been flagged by the community"),
					'message_html' => $this->translate->_("The following content has been flagged as inappropriate by the community:") . "<br /><a href='{$_POST['d']}'>{$_POST['d']}</a><br /><br />" . $this->translate->_("The Petolio Administrator will contact you immediately."),
					'message_text' => $this->translate->_("The following content has been flagged as inappropriate by the community:") . "\n{$_POST['d']}\n\n" . $this->translate->_("The Petolio Administrator will contact you immediately."),
					'template' => 'default'
				), array(array( // add recipients: user name and user email
					'id' => $this->user->getId(),
					'name' => $this->user->getName(),
					'email' => $this->user->getEmail()
				)));
			}

			// email administrator
			$e = new Petolio_Service_Mail();
			$e->setRecipient($this->config['email']['to']);
			$e->setSubject($this->translate->_("Petolio.com:")." ". $this->translate->_("Content has been flagged by the community"));
			$e->setHtml($this->translate->_("Hi Admin,") . "<br /><br />" .
				$this->translate->_("The community flagged the following content:") .
				"<br /><a href='{$_POST['d']}'>{$_POST['d']}</a><br /><br />" .
				$this->translate->_("Please review it immediately."));
			$e->send();
    	}

    	// return with success
		return Petolio_Service_Util::json(array('success' => true, 'msg' => $this->translate->_("You have successfuly flagged this entity.")));
    }
}