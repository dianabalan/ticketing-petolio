<?php

class Petolio_Service_Message {
	private static $subject;
	private static $message_html;
	private static $message_text;

	private static function init() {
		// load translate
		$translate = Zend_Registry::get('Zend_Translate');

		// load email template
		self::$subject = $translate->_("Petolio.com:")." %s";
		self::$message_html = $translate->_("Dear %s").",<br /><br />%s<br /><br />".$translate->_("Yours,")."<br />".$translate->_("Petolio team");
		self::$message_text = $translate->_("Dear %s").",\n\n%s\n\n".$translate->_("Yours,")."\n".$translate->_("Petolio team");
	}

	private static function br2nl($string) {
		// replace links and breaks
		$string = preg_replace("/<a.*?href[^=]*=[^'\"]*['\"]([^'\"]+)['\"].*?>([^<]+)<\/a>/", "\\1 (\\2)", $string);
		$string = preg_replace('#<br\s*?/?>#i', "\n", $string);

		return $string;
	}

	private static function setBody($msg = array(), $user = array()) {
		// mirrors
		$mirrors = array(
			'new.petolio.local', // localhost mirror
			'new.petolio.riffcode.ro', // test mirror
			'www.petolio.com', 'www.petolio.de' // official websites
		);

		// compile search
		$search = array();
		foreach($mirrors as $one)
			$search[] = "http://{$one}/";

		// create hash
		$hash = sha1($user['id'] . $user['email']);

		// compile replace
		$replace = array();
		foreach($mirrors as $one)
			$replace[] = "http://{$one}/hash/index/h/{$hash}/";

		// transform all the links into login links
		$msg['message_html'] = str_replace($search, $replace, $msg['message_html']);
		if(isset($msg['message_text']))
			$msg['message_text'] = str_replace($search, $replace, $msg['message_text']);

		// process defaults
		$html = sprintf(self::$message_html, $user['name'], $msg['message_html']);
		$text = isset($msg['message_text']) ? sprintf(self::$message_text, $user['name'], $msg['message_text']) : self::br2nl(sprintf(self::$message_html, $user['name'], $msg['message_html']));

		// return transformed message
		return array(
			'html' => $html,
			'text' => $text
		);
	}

	private static function setHtml($msg, $user) {
		$out = self::setBody($msg, $user);
		return $out['html'];
	}

	private static function setText($msg, $user) {
		$out = self::setBody($msg, $user);
		return $out['text'];
	}

	/**
	 * Send a message along with an email
	 *
	 * Example of usage:
	 * ============================================
    Petolio_Service_Message::send(array(
		'subject' => "text subject",
		'message_html' => "i<br /><a href='http://www.google.com'>like</a><br />to<br />party!",
		'message_text' => "i\nlike\nto\nparty!", // optional
		'from' => 0, // x - user_id, 0 = system
		'status' => 2, // 1 - sent, 2 - when from is 0
		'priority' => 0, // 0 - default prio, higher numbers - higher prio
		'product' => 1 // link message to product
		'template' => 'calendar/appointment' // the message has a template file; in this case the message_* will be used as content
	), array(array( // add recipients, user id, user name and user email
		'id' => 26,
		'name' => "Flavius Cosmin",
		'email' => "flavius@riffcode.ro"
	)));
	 * ============================================
	 */
	public static function send($msg = array(), $to = array(), $email = true)
	{
		// init
		self::init();

		// create message
		$m = new Petolio_Model_PoMessages();
		$m->setSubject($msg['subject']);
		$m->setMessage($msg['message_html']);
		$m->setFromUserId($msg['from']);
		$m->setStatus($msg['status']);
		$m->setDateSent(date('Y-m-d H:i:s'));
		$m->save();

		// send message to each user
		foreach($to as $user) {
			$r = new Petolio_Model_PoMessageRecipients();
			$r->setToUserId($user['id']);
			$r->setMessageId($m->getId());
			$r->save();

			$e = new Petolio_Service_Mail();
			if (isset($msg['template'])) {
				$e->setTemplate($msg['template']);
				$e->name = $user['name'];
				$e->base_url = PO_BASE_URL;
				$e->subject = $msg['subject'];
				$e->content = $msg['message_html'];
			} else {
				$e->setHtml(self::setHtml($msg, $user));
				$e->setText(self::setText($msg, $user));
			}
			$e->setRecipient($user['email']);
			$e->setSubject(sprintf(self::$subject, $msg['subject']));
			$e->setPriority(isset($msg['priority']) ? $msg['priority'] : 0);
				
			// send email as well
			if($email) {
				$e->send();
			}
		}

		// link message to product
		if(isset($msg['product'])) {
			$p = new Petolio_Model_PoMessageProducts();
			$p->setProductId($msg['product']);
			$p->setMessageId($m->getId());
			$p->save();
		}
	}

	/**
	 * Send only an email
	 *
	 * Example of usage:
	 * ============================================
    Petolio_Service_Message::sendEmail(array(
		'subject' => "text subject",
		'message_html' => "i<br /><a href='http://www.google.com'>like</a><br />to<br />party!",
		'message_text' => "i\nlike\nto\nparty!", // optional
		'priority' => 0 // 0 - default prio, higher numbers - higher prio
		'template' => 'calendar/appointment' // the message has a template file; in this case the message_* will be used as content
	), array(array( // add recipients, user id, user name and user email
		'id' => 26,
		'name' => "Flavius Cosmin",
		'email' => "flavius@riffcode.ro"
	)));
	 * ============================================
	 */
	public static function sendEmail($msg = array(), $to = array())
	{
		// init
		self::init();

		// send message to each user
		foreach($to as $user) {
			$e = new Petolio_Service_Mail();
			if (isset($msg['template'])) {
				$e->setTemplate($msg['template']);
				$e->name = $user['name'];
				$e->base_url = PO_BASE_URL;
				$e->subject = $msg['subject'];
				$e->content = $msg['message_html'];
			} else {
				$e->setHtml(self::setHtml($msg, $user));
				$e->setText(self::setText($msg, $user));
			}
			$e->setRecipient($user['email']);
			$e->setSubject(sprintf(self::$subject, $msg['subject']));
			$e->setPriority(isset($msg['priority']) ? $msg['priority'] : 0);
			$e->send();
		}
	}
}