<?php

/**
 * A template based email system
 *
 * Supports the sending of multipart txt/html emails based on templates
 *
 *
 * @author Jonathan Street
 */
class Petolio_Service_Mail {

	/**
	 * Variable registry for template values
	 */
	protected $templateVariables = array();

	/**
	 * Template name
	 */
	protected $templateName;

	protected $subject;
	protected $text;
	protected $html;

	/**
	 * Zend_Mail instance
	 */
	protected $zendMail;

	/**
	 * Email recipient
	 */
	protected $recipient;

	/**
	 * Reply To
	 */
	protected $replyto = array();

	protected $priority;

	private $db;

	/**
	 * __construct
	 *
	 * Set default options
	 *
	 */
	public function __construct () {
		$this->zendMail = new Zend_Mail();

		// load models
		$this->db = new stdClass();
		$this->db->emails = new Petolio_Model_PoEmails();
	}

	/**
	 * Set variables for use in the templates
	 *
	 * Magic function stores the value put in any variable in this class for
	 * use later when creating the template
	 *
	 * @param string $name  The name of the variable to be stored
	 * @param mixed  $value The value of the variable
	 */
	public function __set($name, $value) {
		$this->templateVariables[$name] = $value;
	}

	/**
	 * Set the template file to use
	 *
	 * @param string $filename Template filename
	 */
	public function setTemplate($filename) {
		$this->templateName = $filename;
	}

	public function setSubject($sub) {
		$this->subject = $sub;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function setHtml($html) {
		$this->html = $html;
	}

	/**
	 * Set the recipient address for the email message
	 *
	 * @param string $email Email address
	 */
	public function setRecipient($email) {
		$this->recipient = $email;
	}

	public function setReplyTo($email, $name) {
		$this->replyto = array($email, $name);
	}

	public function setPriority($prio) {
		$this->priority = $prio;
	}

	/**
	 * Send the constructed email
	 *
	 * @TODO: Add from name
	 */
	public function send() {
		/*
		 * Get data from config
		 * - From address
		 * - Directory for template files
		 */
		$config = Zend_Registry::get('config');
		$translate = Zend_Registry::get('Zend_Translate');
		$from = $config['email']['from'];
		$templateVars = array(); // $config->email->vars->toArray();

		foreach ($templateVars as $key => $value) {
			//If a variable is present in config which has not been set add it to the list
			if (!array_key_exists($key, $this->templateVariables)) {
				$this->{$key} = $value;
			}
		}

		// view config
		$viewConfig = array('basePath' => $config['email']['template']['dir']);

		// build template subject
		$subjectView = new Zend_View($viewConfig);
		$subjectView->translate = $translate;
		foreach ($this->templateVariables as $key => $value) {
			$subjectView->{$key} = $value;
		}
		try {
			$subject = $subjectView->render($this->templateName . '.subj.phtml');
		} catch (Zend_View_Exception $e) {
			$subject = $this->subject ? $this->subject : false;
		}

		// build template text
		$textView = new Zend_View($viewConfig);
		$textView->translate = $translate;
		$textView->base_url = PO_BASE_URL;
		foreach ($this->templateVariables as $key => $value) {
			$textView->{$key} = $value;
		}
		try {
			$text = $textView->render($this->templateName . '.txt.phtml');
		} catch (Zend_View_Exception $e) {
			$text = $this->text ? $this->text : false;
		}

		// build template html
		$htmlView = new Zend_View($viewConfig);
		$htmlView->translate = $translate;
		$htmlView->base_url = PO_BASE_URL;
		foreach ($this->templateVariables as $key => $value) {
			$htmlView->{$key} = $value;
		}
		try {
			$html = $htmlView->render($this->templateName . '.html.phtml');
		} catch (Zend_View_Exception $e) {
			$html = $this->html ? $this->html : false;
		}

		// pass variables to Zend_Mail
		$mail = new Zend_Mail('UTF-8');
		$mail->setFrom($from, "Petolio");
		$mail->addTo($this->recipient);

		// reply to ?
		if(count($this->replyto) > 0)
			$mail->setReplyTo($this->replyto[0], $this->replyto[1]);

		$mail->setSubject($subject);
		if ($text !== false) $mail->setBodyText($text);
		if ($html !== false) $mail->setBodyHtml($html);

		// save email
		$this->db->emails
			->setSerialized(serialize($mail))
			->setPriority($this->priority)
			->save();
	}
}