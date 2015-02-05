<?php

class Petolio_Model_DbTable_PoServiceMembersPets extends Zend_Db_Table_Abstract {

    protected $_name = 'po_service_members_pets';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoCalendar'
	);

	protected $_referenceMap    = array(
        'PoServices' => array(
            'columns'           => array('service_id'),
            'refTableClass'     => 'PoServices',
            'refColumns'        => array('id')
        ),
        'PoPets' => array(
            'columns'           => array('pet_id'),
            'refTableClass'     => 'PoPets',
            'refColumns'        => array('id')
        )
	);

	public function getServiceMembersPets($service_id, $status = null, $limit = null) {
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "a.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "a.status = ".$this->getAdapter()->quote($status);
	    	}
		}
    	$select = $this->select()->setIntegrityCheck(false)
				->from(
					array('a' => 'po_service_members_pets'),
					array('*', 'pet_breed' => new Zend_Db_Expr("CASE WHEN d2.value IS NULL THEN f2.value ELSE d2.value END"))
				)
				->joinLeft(
						array('b' => 'po_pets'),
						'a.pet_id = b.id AND b.deleted != 1',
						array(
							'pet_id' => 'id',
							'pet_user_id' => 'user_id',
							'pet_attribute_set_id' => 'attribute_set_id',
							'pet_date_created' => 'date_created',
							'pet_date_modified' => 'date_modified',
							'pet_deleted' => 'deleted'
						)
				)
				// get pet name
				->joinLeft(
						array('c' => 'po_attributes'),
						"c.attribute_set_id = b.attribute_set_id AND SUBSTRING(c.code, -5) = '_name'",
						array()
				)
				->joinLeft(
						array('d' => 'po_attribute_entity_varchar'),
						"d.attribute_id = c.id AND b.id = d.entity_id",
						array('pet_name' => 'value')
				)
				// get pet breed
				->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = b.attribute_set_id AND SUBSTRING(c2.code, -6) = '_breed'", array())
				->joinLeft(array('d2' => 'po_attribute_entity_varchar'), "d2.attribute_id = c2.id AND b.id = d2.entity_id", array())
				->joinLeft(array('e2' => 'po_attribute_entity_int'), "e2.attribute_id = c2.id AND b.id = e2.entity_id", array())
				->joinLeft(array('f2' => 'po_attribute_options'), "f2.attribute_id = c2.id AND f2.id = e2.value", array())
				// get dateofbirth
				->joinLeft(array('c3' => 'po_attributes'), "c3.attribute_set_id = b.attribute_set_id AND SUBSTRING(c3.code, -12) = '_dateofbirth'", array())
				->joinLeft(array('d3' => 'po_attribute_entity_datetime'), "d3.attribute_id = c3.id AND b.id = d3.entity_id", array('pet_dateofbirth' => 'value'))
				// get pet gallery folder
				->joinLeft(
						array('y' => 'po_folders'), 
						"a.pet_id = y.pet_id AND y.name = 'gallery'", 
						array('folder_id' => 'y.id'))
				// get pet owner
				->joinLeft(
						array('u' => 'po_users'),
						'b.user_id = u.id',
						array(
							'user_id' => 'id',
							'user_name' => 'name',
							'user_first_name' => 'first_name',
							'user_last_name' => 'last_name',
							'user_email' => 'email',
							'user_password' => 'password',
							'user_active' => 'active',
							'user_street' => 'street',
							'user_address' => 'address',
							'user_zipcode' => 'zipcode',
							'user_location' => 'location',
							'user_country_id' => 'country_id',
							'user_phone' => 'phone',
							'user_homepage' => 'homepage',
							'user_gender' => 'gender',
							'user_date_of_birth' => 'date_of_birth',
							'user_gps_latitude' => 'gps_latitude',
							'user_gps_longitude' => 'gps_longitude',
							'user_date_forgot' => 'date_forgot',
							'user_avatar' => 'avatar',
							'user_date_created' => 'date_created',
							'user_date_modified' => 'date_modified',
							'user_type' => 'type'
						)
				)
	        	->where($this->getAdapter()->quoteInto("a.service_id = ?", $service_id, Zend_Db::BIGINT_TYPE))
	        	// show only pets from active users
	        	->where("u.active = 1 AND u.is_banned != 1")
	        	->where("b.id IS NOT NULL")
	        	->where($status_where)
				->order("id DESC");
    	if($limit != null) {
    		$select->limit($limit);
    	}
		return $this->fetchAll($select);
	}

	public function getPetServiceProviders($pet_id) {
    	$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_service_members_pets'))
				->joinInner(
					array('b' => 'po_services'),
					'a.service_id = b.id AND b.deleted != 1'
					)
				->joinLeft(
						array('c' => 'po_users'),
						'b.user_id = c.id',
						array(
							'user_id' => 'id',
							'user_name' => 'name',
							'user_email' => 'email',
							'user_password' => 'password',
							'user_active' => 'active',
							'user_street' => 'street',
							'user_address' => 'address',
							'user_zipcode' => 'zipcode',
							'user_location' => 'location',
							'user_country_id' => 'country_id',
							'user_phone' => 'phone',
							'user_homepage' => 'homepage',
							'user_gender' => 'gender',
							'user_date_of_birth' => 'date_of_birth',
							'user_gps_latitude' => 'gps_latitude',
							'user_gps_longitude' => 'gps_longitude',
							'user_date_forgot' => 'date_forgot',
							'user_avatar' => 'avatar',
							'user_date_created' => 'date_created',
							'user_date_modified' => 'date_modified',
							'user_type' => 'type'
						)
				)
	        	->where($this->getAdapter()->quoteInto("a.pet_id = ?", $pet_id, Zend_Db::BIGINT_TYPE))
	        	// show only active users
	        	->where("c.active = 1 AND c.is_banned != 1")
	        	->where("b.id IS NOT NULL")
				->order("a.id DESC");
		return $this->fetchAll($select);
	}

	/**
	 * get pet's services
	 *
	 * @param int $pet_id
	 * @param int or array $status
	 */
	public function getPetServices($pet_id, $status = null) {
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "a.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "a.status = ".$this->getAdapter()->quote($status);
	    	}
		}
		$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_service_members_pets'))
				->joinInner(
					array('b' => 'po_services'),
					'a.service_id = b.id AND b.deleted != 1',
					array(
						'service_id' => 'id',
						'service_user_id' => 'user_id',
						'service_attribute_set_id' => 'attribute_set_id',
						'service_members_limit' => 'members_limit',
						'service_date_created' => 'date_created',
						'service_date_modified' => 'date_modified',
					)
				)
				->joinInner(
					array('c' => 'po_users'),
					'b.user_id = c.id AND c.active = 1 AND c.is_banned != 1',
					array()
				)
	        	->where($this->getAdapter()->quoteInto("a.pet_id = ?", $pet_id, Zend_Db::BIGINT_TYPE))
	        	->where($status_where)
				->order("a.id DESC");

		return $this->fetchAll($select);
	}

	/**
	 * get pet's services with references
	 *
	 * @param int $pet_id
	 * @param int or array $status
	 * @param string $order
	 */
	public function getPetServicesWithReferences($pet_id, $status = null, $order = null) {
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "a.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "a.status = ".$this->getAdapter()->quote($status);
	    	}
		}
		$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_service_members_pets'))

				// get service
				->joinInner(
					array('b' => 'po_services'),
					'a.service_id = b.id AND b.deleted != 1',
					array(
						'service_id' => 'id',
						'service_user_id' => 'user_id',
						'service_attribute_set_id' => 'attribute_set_id',
						'service_members_limit' => 'members_limit',
						'service_date_created' => 'date_created',
						'service_date_modified' => 'date_modified',
					))

				// get service type
				->joinLeft(
					array('as' => 'po_attribute_sets'),
					"b.attribute_set_id = as.id",
					array('service_type' => 'name'))

				// get service name
				->joinLeft(
					array('c' => 'po_attributes'),
					"c.attribute_set_id = b.attribute_set_id AND SUBSTRING(c.code, -5) = '_name'",
					array())
				->joinLeft(
					array('d' => 'po_attribute_entity_varchar'),
					"d.attribute_id = c.id AND b.id = d.entity_id",
					array('service_name' => 'value'))

				// get service address
				->joinLeft(
					array('c1' => 'po_attributes'),
					"c1.attribute_set_id = b.attribute_set_id AND SUBSTRING(c1.code, -8) = '_address'",
					array())
				->joinLeft(
					array('d1' => 'po_attribute_entity_varchar'),
					"d1.attribute_id = c1.id AND b.id = d1.entity_id",
					array('service_address' => 'value'))

				// get service zipcode
				->joinLeft(
					array('c2' => 'po_attributes'),
					"c2.attribute_set_id = b.attribute_set_id AND SUBSTRING(c2.code, -8) = '_zipcode'",
					array())
				->joinLeft(
					array('d2' => 'po_attribute_entity_varchar'),
					"d2.attribute_id = c2.id AND b.id = d2.entity_id",
					array('service_zipcode' => 'value'))

				// get service location
				->joinLeft(
					array('c3' => 'po_attributes'),
					"c3.attribute_set_id = b.attribute_set_id AND SUBSTRING(c3.code, -9) = '_location'",
					array())
				->joinLeft(
					array('d3' => 'po_attribute_entity_varchar'),
					"d3.attribute_id = c3.id AND b.id = d3.entity_id",
					array('service_location' => 'value'))

				// get service country
				->joinLeft(
					array('c4' => 'po_attributes'),
					"c4.attribute_set_id = b.attribute_set_id AND SUBSTRING(c4.code, -8) = '_country'",
					array())
				->joinLeft(
					array('d4' => 'po_attribute_entity_int'),
					"d4.attribute_id = c4.id AND b.id = d4.entity_id",
					array())
				->joinLeft(
					array('e4' => 'po_attribute_options'),
					"e4.id = d4.value",
					array("service_country" => "value"))

				// get service owner
				->joinLeft(
					array('u' => 'po_users'),
					'b.user_id = u.id',
					array(
						'user_id' => 'id',
						'user_name' => 'name',
						'user_first_name' => 'first_name',
						'user_last_name' => 'last_name',
						'user_email' => 'email',
						'user_password' => 'password',
						'user_active' => 'active',
						'user_street' => 'street',
						'user_address' => 'address',
						'user_zipcode' => 'zipcode',
						'user_location' => 'location',
						'user_country_id' => 'country_id',
						'user_phone' => 'phone',
						'user_homepage' => 'homepage',
						'user_gender' => 'gender',
						'user_date_of_birth' => 'date_of_birth',
						'user_gps_latitude' => 'gps_latitude',
						'user_gps_longitude' => 'gps_longitude',
						'user_date_forgot' => 'date_forgot',
						'user_avatar' => 'avatar',
						'user_date_created' => 'date_created',
						'user_date_modified' => 'date_modified',
						'user_type' => 'type'
					))

				// get service owner country
				->joinLeft(
					array('uc' => 'po_countries'),
					"u.country_id = uc.id",
					array('user_country' => 'name'))
				->where($this->getAdapter()->quoteInto("a.pet_id = ?", $pet_id, Zend_Db::BIGINT_TYPE))
				// only active user
				->where("u.active = 1 AND u.is_banned != 1")
	        	->where($status_where);

	    // order the list
		if($order) {
			if ( strpos($order, ",") > 0 ) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token) {
					$select->order($token);
				}
			} else {
				$select->order($order);
			}

		}

		return $this->fetchAll($select);
	}

	/**
	 * loads a service provider links and it's partners (pets)
	 *
	 * @param int $user_id
	 * @param int $status
	 */
	public function getUserServiceMembersPets($user_id, $status = null) {
		$status_where = '1=1';
		if ( isset($status) ) {
	    	if ( is_array($status) ) {
	    		$status_where = "a.status IN (".addcslashes(implode(',', $status), "\000\n\r\\'\"\032").")";
	    	} elseif ( is_numeric($status) ) {
	    		$status_where = "a.status = ".$this->getAdapter()->quote($status);
	    	}
		}
    	$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_service_members_pets'))
				->joinInner(
					array('e' => 'po_services'),
					'a.service_id = e.id AND e.deleted != 1',
					array()
				)
				->joinInner(
						array('b' => 'po_pets'),
						'a.pet_id = b.id AND b.deleted != 1',
						array(
							'pet_id' => 'id',
							'pet_user_id' => 'user_id',
							'pet_attribute_set_id' => 'attribute_set_id',
							'pet_date_created' => 'date_created',
							'pet_date_modified' => 'date_modified',
							'pet_deleted' => 'deleted'
						)
				)
				->joinLeft(
						array('c' => 'po_attributes'),
						"c.attribute_set_id = b.attribute_set_id AND SUBSTRING(c.code, -5) = '_name'",
						array()
				)
				->joinLeft(
						array('d' => 'po_attribute_entity_varchar'),
						"d.attribute_id = c.id AND b.id = d.entity_id",
						array('pet_name' => 'value')
				)
				->joinLeft(
						array('c2' => 'po_attributes'),
						"c2.attribute_set_id = e.attribute_set_id AND SUBSTRING(c2.code, -5) = '_name'",
						array()
				)
				->joinLeft(
						array('d2' => 'po_attribute_entity_varchar'),
						"d2.attribute_id = c2.id AND e.id = d2.entity_id",
						array('service_name' => 'value')
				)
				->where($this->getAdapter()->quoteInto("e.user_id = ?", $user_id, Zend_Db::BIGINT_TYPE))
	        	->where($status_where)
				->order("a.id DESC");

		return $this->fetchAll($select);
	}

	/**
	 * return the service owner and the pet owner of the specified link
	 * @param int $link_id
	 */
	public function getLinkOwners($link_id) {
    	$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_service_members_pets'))
			->joinInner(
				array('b' => 'po_services'),
				'a.service_id = b.id AND b.deleted != 1',
				array()
			)
			->joinInner(
					array('b1' => 'po_users'),
					'b.user_id = b1.id',
				array(
					'service_user_id' => 'id',
					'service_user_name' => 'name',
					'service_user_email' => 'email',
					'service_user_password' => 'password',
					'service_user_active' => 'active',
					'service_user_street' => 'street',
					'service_user_address' => 'address',
					'service_user_zipcode' => 'zipcode',
					'service_user_location' => 'location',
					'service_user_country_id' => 'country_id',
					'service_user_phone' => 'phone',
					'service_user_homepage' => 'homepage',
					'service_user_gender' => 'gender',
					'service_user_date_of_birth' => 'date_of_birth',
					'service_user_gps_latitude' => 'gps_latitude',
					'service_user_gps_longitude' => 'gps_longitude',
					'service_user_date_forgot' => 'date_forgot',
					'service_user_avatar' => 'avatar',
					'service_user_date_created' => 'date_created',
					'service_user_date_modified' => 'date_modified',
					'service_user_type' => 'type'
				)
			)
			->joinInner(
					array('c' => 'po_pets'),
					'a.pet_id = c.id AND c.deleted != 1',
					array()
			)
			->joinInner(
					array('c1' => 'po_users'),
					'c.user_id = c1.id',
				array(
					'pet_user_id' => 'id',
					'pet_user_name' => 'name',
					'pet_user_email' => 'email',
					'pet_user_password' => 'password',
					'pet_user_active' => 'active',
					'pet_user_street' => 'street',
					'pet_user_address' => 'address',
					'pet_user_zipcode' => 'zipcode',
					'pet_user_location' => 'location',
					'pet_user_country_id' => 'country_id',
					'pet_user_phone' => 'phone',
					'pet_user_homepage' => 'homepage',
					'pet_user_gender' => 'gender',
					'pet_user_date_of_birth' => 'date_of_birth',
					'pet_user_gps_latitude' => 'gps_latitude',
					'pet_user_gps_longitude' => 'gps_longitude',
					'pet_user_date_forgot' => 'date_forgot',
					'pet_user_avatar' => 'avatar',
					'pet_user_date_created' => 'date_created',
					'pet_user_date_modified' => 'date_modified',
					'pet_user_type' => 'type'
				)
			)
			->where($this->getAdapter()->quoteInto("a.id = ?", $link_id));

		return $this->fetchAll($select);
	}
}

?>