<?php

class Petolio_Model_DbTable_PoUsers extends Zend_Db_Table_Abstract {

    protected $_name = 'po_users';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoCalendar',
			'PoMicrosites',
			'PoMessages',
			'PoCalendarAttendees',
			'PoMessageRecipients',
			'PoServiceMembersUsers',
			'PoServices',
			'PoFolders',
			'PoFiles',
			'PoPayments',
			'PoPets',
			'PoMedicalRecords',
			'PoMedicalRecordSubentries',
			'PoFriends', // 2 time dependent: user_id, friend_id
			'PoContentDistributions'
	);

	protected $_referenceMap    = array(
        'PoCountries' => array(
            'columns'           => array('country_id'),
            'refTableClass'     => 'PoCountries',
            'refColumns'        => array('id')
        ),
        'PoUsersCategories' => array(
            'columns'           => array('category_id'),
            'refTableClass'     => 'PoUsersCategories',
            'refColumns'        => array('id')
        )
	);

	public function findUsers($vars, $paginate = false)
	{
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_countries'), 'a.country_id = b.id', array('countryName' => 'name'));

		if(isset($vars['id']))
        	$select->where($this->getAdapter()->quoteInto("a.id = ?", $vars['id'], Zend_Db::BIGINT_TYPE));

		if(isset($vars['name'])) {
        	$select->where($this->getAdapter()->quoteInto("a.name LIKE ?", "%{$vars['name']}%"));
		}

		if($paginate) {
			$usr = new Petolio_Model_PoUsers();
			return $usr->select2Paginator($select);
		}

        $found = $this->fetchAll($select);
        if(count(reset($found)) == 0)
        	return null;

		$out = array();
		foreach($found as $line) {
			$out[] = array(
				'id' => $line->id,
				'name' => $line->name,
				'email' => $line->email,
				'gender' => $line->gender,
				'country' => $line->countryName
			);
		}

		return $out;
	}

	/**
	 * Match users, used when trying to find users that match the array of emails
	 * used in friends google atm
	 *
	 * @param array $array - Array of emails
	 */
	public function matchUsers($array)
	{
		$db = $this->getAdapter();
		$db->query("CREATE TEMPORARY TABLE `temp_match_users` (
			`email` varchar(100) COLLATE utf8_bin NOT NULL
			) ENGINE=MEMORY;
		");

		foreach($array as $line)
			$db->query("INSERT INTO `temp_match_users` SET email = ".$this->getAdapter()->quote($line));

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'temp_match_users'), 'a.email = b.email')
			// show only active users
        	->where("a.active = 1 AND a.is_banned != 1")
			->where("b.email IS NOT NULL");

        return $this->fetchAll($select);
	}

	/**
	 * fetches all rows optionally filtered by where,order,count and offset
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchList($where=null, $order=null, $count=null, $offset=null) {
		if (!($where instanceof Zend_Db_Table_Select)) {
			$select = $this->select();
			if ($where !== null) {
				$this->_where($select, $where);
			}
			if ($order !== null) {
				$this->_order($select, $order);
			}
			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}
		} else {
			$select = $where;
		}
		return $select;
	}

	/**
	 * return all the users who are service providers for services whith this user or his pets has links
	 * @param int $user_id
	 * @param int $status
	 */
	public function getUserPartners($user_id, $status = null) {
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "(link1.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").") OR link2.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032")."))";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "(link1.status = ".$this->getAdapter()->quote($status)." OR link2.status = ".$this->getAdapter()->quote($status).")";
	    	}
		}

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'))
			->distinct(true)
			->joinInner(
				array('s' => 'po_services'),
				'u.id = s.user_id',
				array())
			->joinLeft(
				array('link1' => 'po_service_members_users'),
				's.id = link1.service_id',
				array())
			->joinLeft(
				array('link2' => 'po_service_members_pets'),
				's.id = link2.service_id',
				array())
			->joinLeft(
				array('p' => 'po_pets'),
				'link2.pet_id = p.id',
				array())
        	->where("(link1.user_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR p.user_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE).")")
        	->where($status_where)
        	// show only active users
        	->where("u.active = 1 AND u.is_banned != 1")
			->order("u.name ASC");

        $out = array();
		foreach($this->fetchAll($select) as $line)
			$out[$line->name.'-'.$line->id] = $line; // the key is sorting purposes only

		return $out;
	}

	/**
	 * return all the users who has links with this user's services
	 * @param int $user_id
	 * @param int or array $status - link status(es)
	 */
	public function getPartnerUsers($partner_id, $status = null) {
		$link1_status = '';
		$link2_status = '';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$link1_status = " AND link1.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    		$link2_status = " AND link2.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$link1_status = " AND link1.status = ".$this->getAdapter()->quote($status);
	    		$link2_status = " AND link2.status = ".$this->getAdapter()->quote($status);
	    	}
		}

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'))
			->distinct(true)
			->joinLeft(
				array('p' => 'po_pets'),
				'u.id = p.user_id',
				array())
			->joinLeft(
				array('link1' => 'po_service_members_pets'),
				'p.id = link1.pet_id'.$link1_status,
				array())
			->joinLeft(
				array('link2' => 'po_service_members_users'),
				'u.id = link2.user_id'.$link2_status,
				array())
			->joinLeft(
				array('s' => 'po_services'),
				's.id = link1.service_id OR s.id = link2.service_id',
				array())
        	->where($this->getAdapter()->quoteInto("s.user_id = ?", $partner_id, Zend_Db::BIGINT_TYPE))
        	// show only active users
        	->where("u.active = 1 AND u.is_banned != 1")
        	->order("u.name ASC");

        $out = array();
		foreach($this->fetchAll($select) as $line)
			$out[$line->name.'-'.$line->id] = $line; // the key is sorting purposes only

		return $out;
	}

	/**
	 * Check if the user is online or not
	 */
	public function isOnline($user_id) {
		// user online query
		$results = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'))
			->joinInner(
				array('s' => 'po_sessions'),
				'u.session_id = s.id AND LENGTH(s.data) > 0 AND (s.modified + s.lifetime) > '.strtotime('now'),
				array())
			->where("u.id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE));

		$out = null;
		foreach($this->fetchAll($results) as $one)
			$out = $one->id;

		return $out;
	}

	/**
	 * return a list of users:
	 * 		- who are in the current user's friends list
	 * 		- who are the current user's service providers
	 * 		- if the user is a service provider then load the user service's members too
	 *
	 * @param int $user_id = the current user
	 * @param int $user_type - type of the user: 1 - pet owner; 2 - service provider
	 * @param int or array $status - link and friendship status(es):
	 * 		0 - requested; 1 - accepted; 2 -denied;
	 */
	public function getOnlineFriendsAndPartners($user_id, $user_type, $status = null) {
		$online_users = array();
		if ( !isset($user_id) ) {
			return null;
		}
		// get friends
		$status_where = '';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = " AND f.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = " AND f.status = ".$this->getAdapter()->quote($status);
	    	}
		}
		$select_friends = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'))
			->joinInner(
				array('f' => 'po_friends'),
				'f.user_id = u.id OR f.friend_id = u.id',
				array())
			->joinInner(
				array('s' => 'po_sessions'),
				'u.session_id = s.id AND LENGTH(s.data) > 0 AND (s.modified + s.lifetime) > '.strtotime('now'),
				array())
			->where("(f.friend_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR f.user_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE).") AND u.id != ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE).$status_where)
			->order("u.name ASC");

		foreach($this->fetchAll($select_friends) as $line) {
			$key = $line->name.'-'.$line->id;
			if ( !array_key_exists($key, $online_users) ) {
				$online_users[$key] = $line; // the key is sorting purposes only
			}
		}

		// get partners (service providers)
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "(link1.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").") OR link2.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032")."))";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "(link1.status = ".$this->getAdapter()->quote($status)." OR link2.status = ".$this->getAdapter()->quote($status).")";
	    	}
		}

		$select_partners1 = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'))
			->distinct(true)
			->joinInner(
				array('sess' => 'po_sessions'),
				'u.session_id = sess.id AND LENGTH(sess.data) > 0 AND (sess.modified + sess.lifetime) > '.strtotime('now'),
				array())
			->joinInner(
				array('s' => 'po_services'),
				'u.id = s.user_id',
				array())
			->joinLeft(
				array('link1' => 'po_service_members_users'),
				's.id = link1.service_id',
				array())
			->joinLeft(
				array('link2' => 'po_service_members_pets'),
				's.id = link2.service_id',
				array())
			->joinLeft(
				array('p' => 'po_pets'),
				'link2.pet_id = p.id',
				array())
        	->where("(link1.user_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR p.user_id = ".$this->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE).")")
        	->where($status_where)
			->order("u.name ASC");

		foreach($this->fetchAll($select_partners1) as $line) {
			$key = $line->name.'-'.$line->id;
			if ( !array_key_exists($key, $online_users) ) {
				$online_users[$key] = $line; // the key is sorting purposes only
			}
		}

		// get partners (pet owners and member users); only if the user_type = 2, current user is service provider
		if ( intval($user_type) == 2 ) {
			$link1_status = '';
			$link2_status = '';
			if ( isset($status) ) {
		    	if ( is_array($status) ) {
		    		$link1_status = " AND link1.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
		    		$link2_status = " AND link2.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
		    	} elseif ( is_numeric($status) ) {
		    		$link1_status = " AND link1.status = ".$this->getAdapter()->quote($status);
		    		$link2_status = " AND link2.status = ".$this->getAdapter()->quote($status);
		    	}
			}

			$select_partners2 = $this->select()->setIntegrityCheck(false)
				->from(array('u' => 'po_users'))
				->distinct(true)
				->joinInner(
					array('sess' => 'po_sessions'),
					'u.session_id = sess.id AND LENGTH(sess.data) > 0 AND (sess.modified + sess.lifetime) > '.strtotime('now'),
					array())
				->joinLeft(
					array('p' => 'po_pets'),
					'u.id = p.user_id',
					array())
				->joinLeft(
					array('link1' => 'po_service_members_pets'),
					'p.id = link1.pet_id'.$link1_status,
					array())
				->joinLeft(
					array('link2' => 'po_service_members_users'),
					'u.id = link2.user_id'.$link2_status,
					array())
				->joinLeft(
					array('s' => 'po_services'),
					's.id = link1.service_id OR s.id = link2.service_id',
					array())
	        	->where($this->getAdapter()->quoteInto("s.user_id = ?", $user_id, Zend_Db::BIGINT_TYPE))
				->order("u.name ASC");

			foreach($this->fetchAll($select_partners2) as $line) {
				$key = $line->name.'-'.$line->id;
				if ( !array_key_exists($key, $online_users) ) {
					$online_users[$key] = $line; // the key is sorting purposes only
				}
			}
		}

		return $online_users;
	}

    /**
     * finds row with references by primary key
     *
     * @param int $id
     */
	public function findWithReferences($id) {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_countries'), 'a.country_id = b.id', array('country_name' => 'name'));

		if ( isset($id) ) {
        	$select->where($this->getAdapter()->quoteInto("a.id = ?", $id, Zend_Db::BIGINT_TYPE));
		}

		// show only active users
        $select->where("a.active = 1 AND a.is_banned != 1");

        return $this->fetchAll($select);
	}

    /**
     * finds row with session by primary key
     *
     * @param int $id
	 * @param string $type - paginator or array
	 *
	 * @return either array or paginator
     */
	public function findWithSession($id = null, $type = 'array') {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('u' => $this->_name))
			->joinInner(
				array('s' => 'po_sessions'),
				'u.session_id = s.id AND LENGTH(s.data) > 0 AND (s.modified + s.lifetime) > '.strtotime('now'),
				array());

		// has id? add where
		if(isset($id))
        	$select->where($this->getAdapter()->quoteInto("u.id = ?", $id, Zend_Db::BIGINT_TYPE));

        // order by activity
		$select->order('s.modified DESC');

		// return either array or paginator
		$usr = new Petolio_Model_PoUsers();
		return $type == 'paginator' ? $usr->select2Paginator($select) : $this->fetchAll($select);
	}

	public function getRange() {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array($this->_name), array('scope' => "substring(name, 1, 1)"))
			->where('active = 1 AND is_banned != 1')
			->group('substring(name, 1, 1)')
			->order('substring(name, 1, 1)');

		return $this->fetchAll($select);
	}
	
	public function deleteUserAsNonPetolio ($email)
	{
		$db = $this->getAdapter();
		
		$columns = array('id');
		
		$query = $db->select()
		->from(array('u' => $this->_name), $columns)
		->where('u.email = :email')
		->where('u.type != 3');
			
		$id = $db->fetchOne($query, array(
				':email' => $email
		));
		
		$updateClients = "UPDATE po_clients JOIN po_users ON po_users.id = po_clients.client_id SET po_clients.client_id = ".$id." WHERE po_users.email = '".$email."' AND po_users.type = 3;";
		$this->getAdapter()->query($updateClients);	
		
		$deleteNonPetolios = "DELETE FROM po_users WHERE po_users.email = '".$email."' AND po_users.type = 3;";
		$this->getAdapter()->query($deleteNonPetolios);					
		
	}
		
}