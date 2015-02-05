<?php

class Petolio_Model_DbTable_PoDiaryRecordSubentries extends Zend_Db_Table_Abstract
{

    protected $_name = 'po_diary_record_subentries';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoDiaryRecords' => array(
            'columns'           => array('diary_record_id'),
            'refTableClass'     => 'PoDiaryRecords',
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
					->from(array('a' => 'po_diary_record_subentries'))
					->where("a.deleted != 1");

			if ($where !== null) {
				$this->_where($select, $where);
			}

			if ($order !== null) {
				$this->_order($select, "a.".$order);
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
	 * fetch one subentry with the parent diary record attached to it
	 *
	 * @param int $id
	 */
	function findWithDiaryRecord($id) {
    	$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => 'po_diary_record_subentries'))
				->joinInner(
						array('b' => 'po_diary_records'),
						'a.diary_record_id = b.id',
						array(
							'mr_id' => 'id',
							'mr_pet_id' => 'pet_id',
							'mr_title' => 'title',
							'mr_description' => 'description',
							'mr_owner_id' => 'owner_id',
							'mr_date_created' => 'date_created',
							'mr_date_modified' => 'date_modified',
							'mr_deleted' => 'deleted'
						))
				->where($this->getAdapter()->quoteInto('a.id = ?', $id, Zend_Db::BIGINT_TYPE));
		return $this->fetchRow($select);
	}

	/**
	 * fetches all rows optionally filtered by where,order,count and offset
	 * the select contain service and owner informations
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchWithReferences($where = null, $order = null, $count = null, $offset = null) {
		if (!($where instanceof Zend_Db_Table_Select)) {
	    	$select = $this->select()->setIntegrityCheck(false)
					->from(array('a' => 'po_diary_record_subentries'))
					->joinInner(
							array('f' => 'po_users'),
							'a.owner_id = f.id',
							array (
					            'owner_id' => 'id',
					        	'owner_first_name' => 'first_name',
					        	'owner_last_name' => 'last_name',
					            'owner_name' => 'name',
					            'owner_email' => 'email',
					            'owner_password' => 'password',
					            'owner_active' => 'active',
					            'owner_street' => 'street',
					            'owner_address' => 'address',
					            'owner_zipcode' => 'zipcode',
					            'owner_location' => 'location',
					            'owner_country_id' => 'country_id',
					            'owner_phone' => 'phone',
					        	'owner_private_phone' => 'private_phone',
					        	'owner_business_phone' => 'business_phone',
					        	'owner_private_fax' => 'private_fax',
					        	'owner_business_fax' => 'business_fax',
					            'owner_homepage' => 'homepage',
					            'owner_gender' => 'gender',
					            'owner_date_of_birth' => 'date_of_birth',
					            'owner_gps_latitude' => 'gps_latitude',
					            'owner_gps_longitude' => 'gps_longitude',
					            'owner_date_created' => 'date_created',
					            'owner_date_modified' => 'date_modified',
					        	'owner_date_forgot' => 'date_forgot',
					            'owner_type' => 'type',
					        	'owner_category_id' => 'category_id',
					            'owner_avatar' => 'avatar'
							))
						->where("a.deleted != 1");

			if ($where !== null) {
				$this->_where($select, $where);
			}

			if ($order !== null) {
				$this->_order($select, "a.".$order);
			}
			$select->group("a.id");
			$this->_order($select, "a.id ASC"); // if the order values are null or equal then order by id

			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}

		} else {
			$select = $where;
		}
		return $this->fetchAll($select);
	}
}