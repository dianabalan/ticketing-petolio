<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoUsersMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoUsers
     *
     * @var Petolio_Model_DbTable_PoUsers
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoUsers $cls
     */
    public function findOneByField($field, $value, $cls)
    {
		$table = $this->getDbTable();
		$select = $table->select();

		$row = $table->fetchRow($select->where("{$field} = ?", $value));
		if (0 == count($row)) {
			return;
		}

 		$cls->setId($row->id)
	        ->setFirstName($row->first_name)
	        ->setLastName($row->last_name)
			->setName($row->name)
			->setEmail($row->email)
			->setPassword($row->password)
			->setActive($row->active)
			->setStreet($row->street)
			->setAddress($row->address)
			->setZipcode($row->zipcode)
			->setLocation($row->location)
			->setCountryId($row->country_id)
			->setPhone($row->phone)
			->setPrivatePhone($row->private_phone)
			->setBusinessPhone($row->business_phone)
			->setPrivateFax($row->private_fax)
			->setBusinessFax($row->business_fax)
			->setHomepage($row->homepage)
			->setGender($row->gender)
			->setDateOfBirth($row->date_of_birth)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDateForgot($row->date_forgot)
			->setType($row->type)
			->setCategoryId($row->category_id)
			->setAvatar($row->avatar)
			->setSessionId($row->session_id)
			->setDashEmailNotification($row->dash_email_notification)
			->setWeeklyEmailNotification($row->weekly_email_notification)
			->setOtherEmailNotification($row->other_email_notification)
 			->setIsAdmin($row->is_admin)
			->setIsEditor($row->is_editor)
			->setIsEditor($row->is_editor)
 			->setIsBanned($row->is_banned)
 			->setTimezone($row->timezone)
			->setLanguage($row->language)
			->setInvisible($row->invisible)
 			->setAboutUs($row->about_us)
 			->setCover($row->cover);

	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoUsers $cls
     * @return array
     *
     */
    public function toArray($cls) {
    	if ( is_array($cls) ) {
    		$result = array();
    		foreach ($cls as $entry) {
    			array_push($result, $this->toArray($entry));
    		}
    		return $result;
    	}
        $result = array(
            'id' => $cls->getId(),
        	'first_name' => $cls->getFirstName(),
        	'last_name' => $cls->getLastName(),
            'name' => $cls->getName(),
            'email' => $cls->getEmail(),
            'password' => $cls->getPassword(),
            'active' => $cls->getActive(),
            'street' => $cls->getStreet(),
            'address' => $cls->getAddress(),
            'zipcode' => $cls->getZipcode(),
            'location' => $cls->getLocation(),
            'country_id' => $cls->getCountryId(),
            'phone' => $cls->getPhone(),
        	'private_phone' => $cls->getPrivatePhone(),
        	'business_phone' => $cls->getBusinessPhone(),
        	'private_fax' => $cls->getPrivateFax(),
        	'business_fax' => $cls->getBusinessFax(),
            'homepage' => $cls->getHomepage(),
            'gender' => $cls->getGender(),
            'date_of_birth' => $cls->getDateOfBirth(),
            'gps_latitude' => $cls->getGpsLatitude(),
            'gps_longitude' => $cls->getGpsLongitude(),
            'date_created' => $cls->getDateCreated(),
            'date_modified' => $cls->getDateModified(),
        	'date_forgot' => $cls->getDateForgot(),
            'type' => $cls->getType(),
        	'category_id' => $cls->getCategoryId(),
            'avatar' => $cls->getAvatar(),
        	'session_id' => $cls->getSessionId(),
        	'dash_email_notification' => $cls->getDashEmailNotification(),
        	'weekly_email_notification' => $cls->getWeeklyEmailNotification(),
        	'other_email_notification' => $cls->getOtherEmailNotification(),
        	'is_admin' => $cls->getIsAdmin(),
        	'is_editor' => $cls->getIsEditor(),
        	'is_banned' => $cls->getIsBanned(),
        	'timezone' => $cls->getTimezone(),
        	'language' => $cls->getLanguage(),
        	'invisible' => $cls->getInvisible(),
        	'about_us' => $cls->getAboutUs(),
        	'cover' => $cls->getCover()
        );

        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoUsers $cls
     * @return array
     */
    public function findByField($field, $value, $cls) {
		$table = $this->getDbTable();
		$select = $table->select();
		$result = array();

		$rows = $table->fetchAll($select->where("{$field} = ?", $value));
		foreach ($rows as $row) {
			$cls = new Petolio_Model_PoUsers();
			$result[] = $cls;
			$cls->setId($row->id)
		        ->setFirstName($row->first_name)
		        ->setLastName($row->last_name)
				->setName($row->name)
				->setEmail($row->email)
				->setPassword($row->password)
				->setActive($row->active)
				->setStreet($row->street)
				->setAddress($row->address)
				->setZipcode($row->zipcode)
				->setLocation($row->location)
				->setCountryId($row->country_id)
				->setPhone($row->phone)
				->setPrivatePhone($row->private_phone)
				->setBusinessPhone($row->business_phone)
				->setPrivateFax($row->private_fax)
				->setBusinessFax($row->business_fax)
				->setHomepage($row->homepage)
				->setGender($row->gender)
				->setDateOfBirth($row->date_of_birth)
				->setGpsLatitude($row->gps_latitude)
				->setGpsLongitude($row->gps_longitude)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setDateForgot($row->date_forgot)
				->setType($row->type)
				->setCategoryId($row->category_id)
				->setAvatar($row->avatar)
				->setSessionId($row->session_id)
				->setDashEmailNotification($row->dash_email_notification)
				->setWeeklyEmailNotification($row->weekly_email_notification)
				->setOtherEmailNotification($row->other_email_notification)
				->setIsAdmin($row->is_admin)
				->setIsEditor($row->is_editor)
				->setIsBanned($row->is_banned)
				->setTimezone($row->timezone)
				->setLanguage($row->language)
				->setInvisible($row->invisible)
 				->setAboutUs($row->about_us)
 				->setCover($row->cover);
		}
		return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoUsers $dbTable
     * @return Petolio_Model_PoUsersMapper
     *
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }

        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }

        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * returns the dbTable class
     *
     * @return Petolio_Model_DbTable_PoUsers
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoUsers');
        }

        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoUsers $cls
     *
     */

    public function save(Petolio_Model_PoUsers $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();
        if ($ignoreEmptyValuesOnUpdate) {
            foreach ($data as $key=>$value) {
                if (!isset($value) or strlen($value) <= 0)
                    unset($data[$key]);
            }
        }

        if ( $escapeValues ) {
	        foreach ($data as $key => $value) {
	        	if ( !($value instanceof Zend_Db_Expr) )
		        	$data[$key] = Petolio_Service_Util::escape($data[$key]);
	        }
        }

        if (null === ($id = $cls->getId())) {
            unset($data['id']);
            $id = $this->getDbTable()->insert($data);
            $cls->setId($id);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * finds row by primary key
     *
     * @param int $id
     * @param Petolio_Model_PoUsers $cls
     */
    public function find($id, Petolio_Model_PoUsers $cls)
    {

        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
	        ->setFirstName($row->first_name)
	        ->setLastName($row->last_name)
			->setName($row->name)
			->setEmail($row->email)
			->setPassword($row->password)
			->setActive($row->active)
			->setStreet($row->street)
			->setAddress($row->address)
			->setZipcode($row->zipcode)
			->setLocation($row->location)
			->setCountryId($row->country_id)
			->setPhone($row->phone)
			->setPrivatePhone($row->private_phone)
			->setBusinessPhone($row->business_phone)
			->setPrivateFax($row->private_fax)
			->setBusinessFax($row->business_fax)
			->setHomepage($row->homepage)
			->setGender($row->gender)
			->setDateOfBirth($row->date_of_birth)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDateForgot($row->date_forgot)
			->setType($row->type)
			->setCategoryId($row->category_id)
			->setAvatar($row->avatar)
			->setSessionId($row->session_id)
			->setDashEmailNotification($row->dash_email_notification)
			->setWeeklyEmailNotification($row->weekly_email_notification)
			->setOtherEmailNotification($row->other_email_notification)
        	->setIsAdmin($row->is_admin)
			->setIsEditor($row->is_editor)
        	->setIsBanned($row->is_banned)
        	->setTimezone($row->timezone)
			->setLanguage($row->language)
			->setInvisible($row->invisible)
 			->setAboutUs($row->about_us)
 			->setCover($row->cover);
    }

    /**
     * finds row with references by primary key
     *
     * @param int $id
     * @param Petolio_Model_PoUsers $cls
     */
    public function findWithReferences($id, Petolio_Model_PoUsers $cls)
    {

        $result = $this->getDbTable()->findWithReferences($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
	        ->setFirstName($row->first_name)
	        ->setLastName($row->last_name)
			->setName($row->name)
			->setEmail($row->email)
			->setPassword($row->password)
			->setActive($row->active)
			->setStreet($row->street)
			->setAddress($row->address)
			->setZipcode($row->zipcode)
			->setLocation($row->location)
			->setCountryId($row->country_id)
			->setPhone($row->phone)
			->setPrivatePhone($row->private_phone)
			->setBusinessPhone($row->business_phone)
			->setPrivateFax($row->private_fax)
			->setBusinessFax($row->business_fax)
			->setHomepage($row->homepage)
			->setGender($row->gender)
			->setDateOfBirth($row->date_of_birth)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDateForgot($row->date_forgot)
			->setType($row->type)
			->setCategoryId($row->category_id)
			->setAvatar($row->avatar)
			->setSessionId($row->session_id)
			->setCountryName($row->country_name)
			->setDashEmailNotification($row->dash_email_notification)
			->setWeeklyEmailNotification($row->weekly_email_notification)
			->setOtherEmailNotification($row->other_email_notification)
        	->setIsAdmin($row->is_admin)
			->setIsEditor($row->is_editor)
        	->setIsBanned($row->is_banned)
        	->setTimezone($row->timezone)
			->setLanguage($row->language)
			->setInvisible($row->invisible)
 			->setAboutUs($row->about_us)
 			->setCover($row->cover);
    }

    /**
     * finds row with active session by primary key
     *
     * @param int $id
     * @param Petolio_Model_PoUsers $cls
     */
    public function findWithSession($id, Petolio_Model_PoUsers $cls)
    {

        $result = $this->getDbTable()->findWithSession($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
	        ->setFirstName($row->first_name)
	        ->setLastName($row->last_name)
			->setName($row->name)
			->setEmail($row->email)
			->setPassword($row->password)
			->setActive($row->active)
			->setStreet($row->street)
			->setAddress($row->address)
			->setZipcode($row->zipcode)
			->setLocation($row->location)
			->setCountryId($row->country_id)
			->setPhone($row->phone)
			->setPrivatePhone($row->private_phone)
			->setBusinessPhone($row->business_phone)
			->setPrivateFax($row->private_fax)
			->setBusinessFax($row->business_fax)
			->setHomepage($row->homepage)
			->setGender($row->gender)
			->setDateOfBirth($row->date_of_birth)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDateForgot($row->date_forgot)
			->setType($row->type)
			->setCategoryId($row->category_id)
			->setAvatar($row->avatar)
			->setSessionId($row->session_id)
        	->setTimezone($row->timezone)
			->setLanguage($row->language)
			->setInvisible($row->invisible)
 			->setAboutUs($row->about_us)
 			->setCover($row->cover);
    }

    /**
     * fetches all rows
     *
     * @return array
     */
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoUsers();
            $entry->setId($row->id)
		          ->setFirstName($row->first_name)
	        	  ->setLastName($row->last_name)
                  ->setName($row->name)
                  ->setEmail($row->email)
                  ->setPassword($row->password)
                  ->setActive($row->active)
                  ->setStreet($row->street)
                  ->setAddress($row->address)
                  ->setZipcode($row->zipcode)
                  ->setLocation($row->location)
                  ->setCountryId($row->country_id)
                  ->setPhone($row->phone)
				  ->setPrivatePhone($row->private_phone)
				  ->setBusinessPhone($row->business_phone)
				  ->setPrivateFax($row->private_fax)
				  ->setBusinessFax($row->business_fax)
                  ->setHomepage($row->homepage)
                  ->setGender($row->gender)
                  ->setDateOfBirth($row->date_of_birth)
                  ->setGpsLatitude($row->gps_latitude)
                  ->setGpsLongitude($row->gps_longitude)
                  ->setDateCreated($row->date_created)
                  ->setDateModified($row->date_modified)
                  ->setDateForgot($row->date_forgot)
                  ->setType($row->type)
                  ->setCategoryId($row->category_id)
                  ->setAvatar($row->avatar)
                  ->setSessionId($row->session_id)
                  ->setDashEmailNotification($row->dash_email_notification)
				  ->setWeeklyEmailNotification($row->weekly_email_notification)
				  ->setOtherEmailNotification($row->other_email_notification)
                  ->setIsAdmin($row->is_admin)
				  ->setIsEditor($row->is_editor)
                  ->setIsBanned($row->is_banned)
                  ->setTimezone($row->timezone)
				  ->setLanguage($row->language)
				  ->setInvisible($row->invisible)
 				  ->setAboutUs($row->about_us)
 				  ->setCover($row->cover)
                  ->setMapper($this);

            $entries[] = $entry;
        }

        return $entries;
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
    public function fetchList($where=null, $order=null, $count=null, $offset=null)
    {
		$resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset);
		$entries = array();
		foreach ($resultSet as $row)
		{
			$entry = new Petolio_Model_PoUsers();
			$entry->setId($row->id)
				->setFirstName($row->first_name)
				->setLastName($row->last_name)
				->setName($row->name)
				->setEmail($row->email)
				->setPassword($row->password)
				->setActive($row->active)
				->setStreet($row->street)
				->setAddress($row->address)
				->setZipcode($row->zipcode)
				->setLocation($row->location)
				->setCountryId($row->country_id)
				->setPhone($row->phone)
				->setPrivatePhone($row->private_phone)
				->setBusinessPhone($row->business_phone)
				->setPrivateFax($row->private_fax)
				->setBusinessFax($row->business_fax)
				->setHomepage($row->homepage)
				->setGender($row->gender)
				->setDateOfBirth($row->date_of_birth)
				->setGpsLatitude($row->gps_latitude)
				->setGpsLongitude($row->gps_longitude)
				->setDateCreated($row->date_created)
				->setDateModified($row->date_modified)
				->setDateForgot($row->date_forgot)
				->setType($row->type)
				->setCategoryId($row->category_id)
				->setAvatar($row->avatar)
				->setSessionId($row->session_id)
				->setDashEmailNotification($row->dash_email_notification)
				->setWeeklyEmailNotification($row->weekly_email_notification)
				->setOtherEmailNotification($row->other_email_notification)
				->setIsAdmin($row->is_admin)
				->setIsEditor($row->is_editor)
				->setIsBanned($row->is_banned)
				->setTimezone($row->timezone)
				->setLanguage($row->language)
				->setInvisible($row->invisible)
 				->setAboutUs($row->about_us)
 				->setCover($row->cover)
				->setMapper($this);

			$entries[] = $entry;
		}

		return $entries;
    }

    public function isServiceProvider($id) {
    	$resultset = $this->fetchList("id = '{$id}' AND type = '2'");
		return count($resultset) > 0 ? true : false;
    }
}