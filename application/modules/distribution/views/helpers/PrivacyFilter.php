<?php

class Zend_View_Helper_PrivacyFilter extends Zend_View_Helper_Abstract {

	protected $_view;

	public function setView(Zend_View_Interface $view) {
		$this->_view = $view;
	}

	/**
	 * filters out a user array or a part of a user after the user's privacy settings
	 * 
	 * @param array $user_data the filtered user_data
	 */
	public function PrivacyFilter($user_data = array()) {
		// no user ?
		$data = array();
		if (!isset($user_data["id"]))
			return $data;

		// get user info as array
		$data = $user_data;
		$private = array();

		$field_rights = new Petolio_Model_PoFieldRights();
		
		// filter based on privacy (we're guests)
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			foreach($data as $key => $line) {
				// information not public ? hide it
				if ( $field_rights->getMapper()->findPrivacySetting($key, $user_data["id"]) != 0 )
					$private[] = $key;
			}

		// filter based on privacy (if we're not the owner of the profile)
		} elseif(Zend_Auth::getInstance()->getIdentity()->id != $user_data["id"]) {
			foreach($data as $key => $line) {
				// search for privacy setting
				list($p_key, $p_setting) = $field_rights->getMapper()->findPrivacySetting($key, $user_data["id"], true);
				switch($p_setting) {
					// public
					case 0:
						// do nothing
						break;

					// friends
					case 1:
						if(!array_key_exists(Zend_Auth::getInstance()->getIdentity()->id, $this->loadFriendsAndPartners($user_data["id"])))
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
						$pfru = new Petolio_Model_PoFieldRightUsers();
						foreach($pfru->getMapper()->getCustomUsers($p_key) as $user)
							$allowed[] = $user->getUserId();

						if(!in_array(Zend_Auth::getInstance()->getIdentity()->id, $allowed))
							$private[] = $key;
						break;
				}
			}
		}

		// prefill info
		foreach($data as $idx => &$line) {
			if ( in_array($idx, $private) ) 
				$line = ""; // information is not available for the current user
		}

		// send to template
		return $data;
	}
	
	private function loadFriendsAndPartners($user_id) {
    	// load user's friends and partners
    	$user = new Petolio_Model_PoUsers();
    	$user->find($user_id);
    	$all = array_merge($user->getUserFriends(), $user->getUserPartners());
    	ksort($all); // sort friends / partners

    	// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		return $result;
	}
	
}