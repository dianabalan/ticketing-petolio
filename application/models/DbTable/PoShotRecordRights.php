<?php

class Petolio_Model_DbTable_PoShotRecordRights extends Zend_Db_Table_Abstract
{

    protected $_name = 'po_shot_record_rights';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoShotRecords' => array(
            'columns'           => array('shot_record_id'),
            'refTableClass'     => 'PoShotRecords',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

    /**
     * returns a result set of users who have access to this shot record
     *
     * @param int $shot_record_id
     */
	public function findAllowedUsers($shot_record_id) {
    	$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => $this->_name))
				->joinLeft(
						array('b' => 'po_users'),
						'a.user_id = b.id',
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
	        	->where($this->getAdapter()->quoteInto("a.shot_record_id = ?", $shot_record_id, Zend_Db::BIGINT_TYPE))
				// show only active users
				->where("b.active = 1 AND b.is_banned != 1")
	        	->order("b.name ASC");
		return $this->fetchAll($select);
	}
}