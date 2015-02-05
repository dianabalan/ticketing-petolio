<?php

class Petolio_Service_FluxBB extends Zend_Db_Table_Abstract {

	private $db = null;
	private $util = null;
	private $translate = null;

	function __construct() {
		parent::__construct();

		$this->db = $this->getAdapter();
		$this->util = new Petolio_Service_Util();
		$this->translate = Zend_Registry::get('Zend_Translate');
	}

    public function getForumConfig($wat = false) {
    	if($wat) {
	    	$what = null;
	    	$pun_config = array();
			foreach($wat as $line)
				$what .= "OR conf_name = '{$line}'";
			$what = substr($what, 3);
			$str = "WHERE {$what}";
		} else $str = null;

		$qry = $this->db->query("SELECT * FROM forum_config {$str}");
		foreach($qry->fetchAll() as $cfg)
			$pun_config[$cfg['conf_name']] = $cfg['conf_value'];

		return $pun_config;
    }

    public function callApi($act, $opt) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, PO_BASE_URL . 'forum/api.php');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('action' => $act, 'option' => $opt));

		curl_exec($ch);
		curl_close($ch);
    }

    public function directApi($act, $opt) {
    	$opt = base64_encode(serialize($opt));
    	header("Location: " . PO_BASE_URL . "forum/api.php?action={$act}&option={$opt}");
    	exit;
    }

	public function banUser($user_id, $admin_id) {
		$user = $this->findUser($user_id);
		$admin = $this->findUser($admin_id);
		if(!$user && !$admin)
			return false;

		$this->db->insert("forum_bans", array(
			'username' => $user['username'],
			'email' => $user['email'],
			'message' => $this->translate->_("Banned from Petolio Admin"),
			'ban_creator' => $admin['id']
		));

		$this->callApi('cache', 'generate_bans_cache');
	}

	public function unbanUser($user_id) {
		$user = $this->findUser($user_id);
		if(!$user)
			return false;

		$this->db->delete("forum_bans", array(
			'username = ?' => $user['username'],
			'email = ?' => $user['email']
		));

		$this->callApi('cache', 'generate_bans_cache');
	}

	public function addUser($data) {
		$pun_config = $this->getForumConfig(array(
			'o_default_email_setting', 'o_default_timezone',
			'o_default_dst', 'o_default_lang', 'o_default_style'
		));

		$this->db->insert("forum_users", array(
			'username' => $data['name'],
			'group_id' => $data['type'] == 2 ? 5 : 4,
			'po_user_id' => $data['po_user_id'],
			'password' => sha1('Blank'),
			'email' => $data["email"],
			'email_setting' => 2, // $pun_config['o_default_email_setting'], -- ignore this value, email must be always hidden and disallow any form emailing
			'timezone' => $pun_config['o_default_timezone'],
			'dst' => $pun_config['o_default_dst'],
			'language' => $pun_config['o_default_lang'],
			'style' => $pun_config['o_default_style'],
			'registered' => time(),
			'registration_ip' => $this->util->get_remote_address(),
			'last_visit' => time()
		));

		$this->callApi('cache', 'generate_users_info_cache');
	}

	private function findUser($id) {
		$qry = $this->db->query("SELECT id, username, password, email FROM forum_users WHERE po_user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($id, Zend_Db::BIGINT_TYPE));
		$result = $qry->fetchAll();

		return empty($result) ? false : reset($result);
	}

	public function updateUser($data, $id) {
		$user = $this->findUser($id);
		if(!$user)
			return false;

		$this->db->update("forum_online", array('ident' => $data['username']), "user_id = {$user['id']}");
		$this->db->update("forum_posts", array('poster' => $data['username']), "poster_id = {$user['id']}");
		$this->db->update("forum_users", $data, "id = {$user['id']}");
		$this->callApi('cache', 'generate_users_info_cache');
	}

	public function login($id, $redirect = PO_BASE_SITE) {
		$session = new Zend_Session_Namespace('Petolio_Redirect');
		if (isset($session->redirect) && strlen($session->redirect) > 0) {
			$redirect = $session->redirect;
			$prependbase = false;
			unset($session->redirect);
		}

		$user = $this->findUser($id);
		if(!$user)
			return $this->logout($redirect);

		$opt = array(
			'id' => $user['id'],
			'password' => $user['password'],
			'timeout' => 31536000
		);

		if($redirect)
			$opt['redirect'] = $redirect;

		$this->directApi('login', $opt);
	}

	public function logout($redirect = PO_BASE_SITE) {
		$this->directApi('logout', array(
			'redirect' => $redirect,
			'timeout' => 31536000
		));
	}
}