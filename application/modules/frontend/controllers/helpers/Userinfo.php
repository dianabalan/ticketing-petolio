<?php

class Petolio_Controller_Helper_Userinfo extends Zend_Controller_Action_Helper_Abstract {

	private $translate = null;
	private $auth = null;
	private $msg = null;

	private $user = null;
	private $pfr = null;
	private $pfru = null;
	private $micro = null;

    public function init() {
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");

		$this->user = new Petolio_Model_PoUsers();
		$this->pfr = new Petolio_Model_PoFieldRights();
		$this->pfru = new Petolio_Model_PoFieldRightUsers();
		$this->micro = new Petolio_Model_PoMicrosites();
    }

	private function loadFriendsAndPartners() {
    	// load user's friends and partners
    	$all = array_merge($this->user->getUserFriends(), $this->user->getUserPartners());
    	ksort($all); // sort friends / partners

    	// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		return $result;
	}

	private function loadUser() {
		// no user ?
		$data = array();
		if (is_null($this->user->getId()))
			return $data;

		// get user info as array
		$data = $this->user->getMapper()->toArray($this->user);
		$private = array();

		// filter based on privacy (we're guests)
		if (!$this->auth->hasIdentity()) {
			foreach($data as $key => $line) {
				// information not public ? hide it
				if($this->pfr->getMapper()->findPrivacySetting($key, $this->user->getId()) != 0)
					$private[] = $key;
			}

		// filter based on privacy (if we're not the owner of the profile)
		} elseif($this->auth->getIdentity()->id != $this->user->getId()) {
			foreach($data as $key => $line) {
				// search for privacy setting
				list($p_key, $p_setting) = $this->pfr->getMapper()->findPrivacySetting($key, $this->user->getId(), true);
				switch($p_setting) {
					// public
					case 0:
						// do nothing
					break;

					// friends
					case 1:
						if(!array_key_exists($this->auth->getIdentity()->id, $this->loadFriendsAndPartners()))
							$private[] = $key;
					break;

					// me
					case 2:
						// make it always private since its never your own profile, nothing to worry about
						$private[] = $key;
					break;

					// custom
					case 3:
						// loop through allowed users
						$allowed = array();
						foreach($this->pfru->getMapper()->getCustomUsers($p_key) as $user)
							$allowed[] = $user->getUserId();

						if(!in_array($this->auth->getIdentity()->id, $allowed))
							$private[] = $key;
					break;
				}
			}
		}

		// prefill info
		$countriesMap = new Petolio_Model_PoCountriesMapper();
		$categoriesMap = new Petolio_Model_PoUsersCategoriesMapper();
		foreach($data as $idx => &$line) {
			if(in_array($idx, $private)) $line = "<small class='private grey'>".$this->translate->_("Information is private.")."</small>";
			elseif(strlen($line) > 0) {
				if($idx == 'category_id') $line = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr(reset($categoriesMap->findByField('id', $line, null))->getName()));
				if($idx == 'country_id') $line = reset($countriesMap->findByField('id', $line, null))->getName();
				if($idx == 'date_of_birth') $line = Petolio_Service_Util::formatDate($line, null, false);
				if($idx == 'gender') $line = $line == "1" ? $this->translate->_("Male") : $this->translate->_("Female");
			} else $line = "<small class='empty grey'>".$this->translate->_("No information available.")."</small>";
		}

		// microsite?
		$results = reset($this->micro->getMapper()->fetchList("user_id = '{$this->user->getId()}' AND active = 1"));
		if($results)
			$data['micro'] = $results->getUrl();

		// send to template
		return $data;
	}

	public function direct($id) {
		$this->user->find($id, $this->user);
		return $this->loadUser();
    }
}