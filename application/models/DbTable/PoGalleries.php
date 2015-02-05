<?php

class Petolio_Model_DbTable_PoGalleries extends Zend_Db_Table_Abstract {

    protected $_name = 'po_galleries';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoFolders' => array(
            'columns'           => array('folder_id'),
            'refTableClass'     => 'PoFolders',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('owner_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

	/**
	 * fetches all rows optionally filtered by where,order,count and offset
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchList($where=null, $order=null, $count=null, $offset=null)
	{

		if (!($where instanceof Zend_Db_Table_Select)) {
			$select = $this->select()->setIntegrityCheck(false)
					->from(array('a' => 'po_galleries'))
					->joinInner(
						array('c' => 'po_users'),
						'a.owner_id = c.id',
						array(
							'owner_name' => 'name'
						));

			if ($where !== null) {
				$this->_where($select, $where);
			}

			if ($order !== null) {
				$this->_order($select, $order);
			}

			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}
			$select->group("a.id");

		} else {
			$select = $where;
		}

		return $select;
	}

	/**
     * fetches one row with the owner reference included
     *
     * @param int $id
     */
    public function findWithReferences($id) {
		$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_galleries'))
				->joinInner(
					array('c' => 'po_users'),
					'a.owner_id = c.id',
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
					))
				->where("c.active = 1 AND c.is_banned != 1") // show only active users
				->where($this->getAdapter()->quoteInto("a.id = ? and deleted = 0", $id, Zend_Db::BIGINT_TYPE));

    	return $this->fetchRow($select);
    }
}