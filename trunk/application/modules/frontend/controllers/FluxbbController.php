<?php

class FluxbbController extends Zend_Controller_Action
{
	private $translate = null;
	private $usr = null;

    public function init(){
    	$this->translate = Zend_Registry::get('Zend_Translate');
    	$this->usr = new Petolio_Model_PoUsers();
    }

    public function indexAction()
    {
    }

	public function newtopicAction() {
		// get the user
		$this->usr->find($_POST['user']);
		if(!$this->usr->getId())
			die("I'm sad :(");

		// do the code
		$reply = PO_BASE_URL . "forum/viewtopic.php?id={$_POST['id']}";
		$fake = $this->translate->_('%1$s has posted a new <u>Topic</u>: %2$s');
		$html = array(
			'%1$s has posted a new <u>Topic</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->usr->getId()), 'default', true)}'>{$this->usr->getName()}</a>",
			"<a href='{$reply}'>{$_POST['subject']}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('forum', array($html, $reply, $this->usr->getId()));
	}

	public function newreplyAction() {
		// get the user
		$this->usr->find($_POST['user']);
		if(!$this->usr->getId())
			die("I'm sad :(");

		// do the code
		$reply = PO_BASE_URL . "forum/viewtopic.php?id={$_POST['id']}";
		$fake = $this->translate->_('%1$s has posted a new reply in <u>Topic</u>: %2$s');
		$html = array(
			'%1$s has posted a new reply in <u>Topic</u>: %2$s',
			"<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->usr->getId()), 'default', true)}'>{$this->usr->getName()}</a>",
			"<a href='{$reply}'>{$_POST['subject']}</a>"
		);

		// send AMQPC
    	\Petolio_Service_AMQPC::sendMessage('forum', array($html, $reply, $this->usr->getId()));
	}
}