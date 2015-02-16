<?php

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoCalendarMapper {

    /**
     * $_dbTable - instance of Petolio_Model_DbTable_PoCalendar
     *
     * @var Petolio_Model_DbTable_PoCalendar
     */
    protected $_dbTable;

    /**
     * finds a row where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoCalendar $cls
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
		->setUserId($row->user_id)
		->setSubject($row->subject)
		->setSpecies($row->species)
		->setMod($row->mod)
		->setFee($row->fee)
		->setCap($row->cap)
		->setDescription($row->description)
		->setDateStart($row->date_start)
		->setDateEnd($row->date_end)
		->setAllDay($row->all_day)
		->setStreet($row->street)
		->setAddress($row->address)
		->setZipcode($row->zipcode)
		->setLocation($row->location)
		->setCountryId($row->country_id)
		->setGpsLatitude($row->gps_latitude)
		->setGpsLongitude($row->gps_longitude)
		->setType($row->type)
		->setReminder($row->reminder)
		->setReminderTime($row->reminder_time)
		->setRepeat($row->repeat)
		->setRepeatMinutes($row->repeat_minutes)
		->setRepeatHours($row->repeat_hours)
		->setRepeatDayOfMonth($row->repeat_day_of_month)
		->setRepeatMonth($row->repeat_month)
		->setRepeatDayOfWeek($row->repeat_day_of_week)
		->setRepeatUntil($row->repeat_until)
		->setDateCreated($row->date_created)
		->setDateModified($row->date_modified)
		->setDateNextRun($row->date_next_run)
		->setLinkId($row->link_id)
		->setLinkType($row->link_type)
		->setAvailability($row->availability);
	    return $cls;
    }


    /**
     * returns an array, keys are the field names.
     *
     * @param new Petolio_Model_PoCalendar $cls
     * @return array
     *
     */
    public function toArray($cls) {
        $result = array(
            'id' => $cls->getId(),
            'user_id' => $cls->getUserId(),
            'user_name' => $cls->getUserName(),
            'user_avatar' => $cls->getUserAvatar(),
            'subject' => $cls->getSubject(),
        	'species' => $cls->getSpecies(),
        	'mod' => $cls->getMod(),
        	'fee' => $cls->getFee(),
        	'cap' => $cls->getCap(),
            'description' => $cls->getDescription(),
            'date_start' => $cls->getDateStart(),
            'date_end' => $cls->getDateEnd(),
        	'all_day' => $cls->getAllDay(),
            'street' => $cls->getStreet(),
            'address' => $cls->getAddress(),
            'zipcode' => $cls->getZipcode(),
            'location' => $cls->getLocation(),
            'country_id' => $cls->getCountryId(),
            'gps_latitude' => $cls->getGpsLatitude(),
            'gps_longitude' => $cls->getGpsLongitude(),
            'type' => $cls->getType(),
            'reminder' => $cls->getReminder(),
            'reminder_time' => $cls->getReminderTime(),
            'repeat' => $cls->getRepeat(),
            'repeat_minutes' => $cls->getRepeatMinutes(),
            'repeat_hours' => $cls->getRepeatHours(),
            'repeat_day_of_month' => $cls->getRepeatDayOfMonth(),
            'repeat_month' => $cls->getRepeatMonth(),
            'repeat_day_of_week' => $cls->getRepeatDayOfWeek(),
        	'repeat_until' => $cls->getRepeatUntil(),
            'date_created' => $cls->getDateCreated(),
            'date_modified' => $cls->getDateModified(),
            'date_next_run' => $cls->getDateNextRun(),
        	'link_id' => $cls->getLinkId(),
        	'link_type' => $cls->getLinkType(),
        	'availability' => $cls->getAvailability()
        );

        return $result;
    }

    /**
     * finds rows where $field equals $value
     *
     * @param string $field
     * @param mixed $value
     * @param Petolio_Model_PoCalendar $cls
     * @return array
     */
    public function findByField($field, $value, $cls)
    {
            $table = $this->getDbTable();
            $select = $table->select();
            $result = array();

            $rows = $table->fetchAll($select->where("{$field} = ?", $value));
            foreach ($rows as $row) {
				$cls=new Petolio_Model_PoCalendar();
				$result[]=$cls;
				$cls->setId($row->id)
					->setUserId($row->user_id)
					->setSubject($row->subject)
					->setSpecies($row->species)
					->setMod($row->mod)
					->setFee($row->fee)
					->setCap($row->cap)
					->setDescription($row->description)
					->setDateStart($row->date_start)
					->setDateEnd($row->date_end)
					->setAllDay($row->all_day)
					->setStreet($row->street)
					->setAddress($row->address)
					->setZipcode($row->zipcode)
					->setLocation($row->location)
					->setCountryId($row->country_id)
					->setGpsLatitude($row->gps_latitude)
					->setGpsLongitude($row->gps_longitude)
					->setType($row->type)
					->setReminder($row->reminder)
					->setReminderTime($row->reminder_time)
					->setRepeat($row->repeat)
					->setRepeatMinutes($row->repeat_minutes)
					->setRepeatHours($row->repeat_hours)
					->setRepeatDayOfMonth($row->repeat_day_of_month)
					->setRepeatMonth($row->repeat_month)
					->setRepeatDayOfWeek($row->repeat_day_of_week)
					->setRepeatUntil($row->repeat_until)
					->setDateCreated($row->date_created)
					->setDateModified($row->date_modified)
					->setDateNextRun($row->date_next_run)
					->setLinkId($row->link_id)
					->setLinkType($row->link_type)
					->setAvailability($row->availability);
            }
            return $result;
    }

    /**
     * sets the dbTable class
     *
     * @param Petolio_Model_DbTable_PoCalendar $dbTable
     * @return Petolio_Model_PoCalendarMapper
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
     * @return Petolio_Model_DbTable_PoCalendar
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Petolio_Model_DbTable_PoCalendar');
        }
        return $this->_dbTable;
    }

    /**
     * saves current row
     *
     * @param Petolio_Model_PoCalendar $cls
     *
     */

    public function save(Petolio_Model_PoCalendar $cls,$ignoreEmptyValuesOnUpdate=true, $escapeValues = false)
    {
       	$data = $cls->toArray();

		unset($data['user_name']);
		unset($data['user_avatar']);

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
     * @param Petolio_Model_PoCalendar $cls
     */

    public function find($id, Petolio_Model_PoCalendar $cls)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }

        $row = $result->current();

        $cls->setId($row->id)
			->setUserId($row->user_id)
			->setSubject($row->subject)
			->setSpecies($row->species)
			->setMod($row->mod)
			->setFee($row->fee)
			->setCap($row->cap)
			->setDescription($row->description)
			->setDateStart($row->date_start)
			->setDateEnd($row->date_end)
			->setAllDay($row->all_day)
			->setStreet($row->street)
			->setAddress($row->address)
			->setZipcode($row->zipcode)
			->setLocation($row->location)
			->setCountryId($row->country_id)
			->setGpsLatitude($row->gps_latitude)
			->setGpsLongitude($row->gps_longitude)
			->setType($row->type)
			->setReminder($row->reminder)
			->setReminderTime($row->reminder_time)
			->setRepeat($row->repeat)
			->setRepeatMinutes($row->repeat_minutes)
			->setRepeatHours($row->repeat_hours)
			->setRepeatDayOfMonth($row->repeat_day_of_month)
			->setRepeatMonth($row->repeat_month)
			->setRepeatDayOfWeek($row->repeat_day_of_week)
			->setRepeatUntil($row->repeat_until)
			->setDateCreated($row->date_created)
			->setDateModified($row->date_modified)
			->setDateNextRun($row->date_next_run)
			->setLinkId($row->link_id)
			->setLinkType($row->link_type)
			->setAvailability($row->availability);
    }

    /**
     * fetches all rows
     *
     * @return array
     */
    public function fetchAll($data = false)
    {
    	if($data == false) $resultSet = $this->getDbTable()->fetchAll();
		else $resultSet = $data;

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Petolio_Model_PoCalendar();
            $entry->setId($row->id)
                  ->setUserId($row->user_id)
				  ->setUserName($row->user_name)
				  ->setUserAvatar($row->user_avatar)
                  ->setSubject($row->subject)
				  ->setSpecies($row->species)
				  ->setMod($row->mod)
				  ->setFee($row->fee)
				  ->setCap($row->cap)
                  ->setDescription($row->description)
                  ->setDateStart($row->date_start)
                  ->setDateEnd($row->date_end)
                  ->setAllDay($row->all_day)
                  ->setStreet($row->street)
                  ->setAddress($row->address)
                  ->setZipcode($row->zipcode)
                  ->setLocation($row->location)
                  ->setCountryId($row->country_id)
                  ->setGpsLatitude($row->gps_latitude)
                  ->setGpsLongitude($row->gps_longitude)
                  ->setType($row->type)
                  ->setReminder($row->reminder)
                  ->setReminderTime($row->reminder_time)
                  ->setRepeat($row->repeat)
                  ->setRepeatMinutes($row->repeat_minutes)
                  ->setRepeatHours($row->repeat_hours)
                  ->setRepeatDayOfMonth($row->repeat_day_of_month)
                  ->setRepeatMonth($row->repeat_month)
                  ->setRepeatDayOfWeek($row->repeat_day_of_week)
                  ->setRepeatUntil($row->repeat_until)
                  ->setDateCreated($row->date_created)
                  ->setDateModified($row->date_modified)
                  ->setDateNextRun($row->date_next_run)
                  ->setLinkId($row->link_id)
                  ->setLinkType($row->link_type)
                  ->setAvailability($row->availability)
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
            $entries   = array();
            foreach ($resultSet as $row)
            {
                    $entry = new Petolio_Model_PoCalendar();
                    $entry->setId($row->id)
                          ->setUserId($row->user_id)
                          ->setSubject($row->subject)
						  ->setSpecies($row->species)
						  ->setMod($row->mod)
						  ->setFee($row->fee)
						  ->setCap($row->cap)
                          ->setDescription($row->description)
                          ->setDateStart($row->date_start)
                          ->setDateEnd($row->date_end)
                          ->setAllDay($row->all_day)
                          ->setStreet($row->street)
                          ->setAddress($row->address)
                          ->setZipcode($row->zipcode)
                          ->setLocation($row->location)
                          ->setCountryId($row->country_id)
                          ->setGpsLatitude($row->gps_latitude)
                          ->setGpsLongitude($row->gps_longitude)
                          ->setType($row->type)
                          ->setReminder($row->reminder)
                          ->setReminderTime($row->reminder_time)
                          ->setRepeat($row->repeat)
                          ->setRepeatMinutes($row->repeat_minutes)
                          ->setRepeatHours($row->repeat_hours)
                          ->setRepeatDayOfMonth($row->repeat_day_of_month)
                          ->setRepeatMonth($row->repeat_month)
                          ->setRepeatDayOfWeek($row->repeat_day_of_week)
                          ->setRepeatUntil($row->repeat_until)
                          ->setDateCreated($row->date_created)
                          ->setDateModified($row->date_modified)
                          ->setDateNextRun($row->date_next_run)
                          ->setAvailability($row->availability)
                          ->setLinkId($row->link_id)
                          ->setLinkType($row->link_type)
                          ->setMapper($this);
                    $entries[] = $entry;
            }
            return $entries;
    }

    /**
	 * Get calendar events complete with owner name and avatar
     *
	 * @param string $type - array or paginator
     * @param string $where
     * @param string $order
     * @param string $limit
     *
     * @return either array or paginator
     */
    public function getEvents($where = false, $order = false, $limit = false) {
    	$db = $this->getDbTable();

    	// main query
    	$select = $db->select()->setIntegrityCheck(false)
    	->from(array('c' => 'po_calendar'), array('*'))

    	// get entry owner
    	->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'));

		// make sure the user is active and not banned
		$select->where("x.active = 1 AND x.is_banned != 1");

    	// filter and sort and limit ? ok
    	if($where) $select->where($where);
    	if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// group by entry id
		$select->group('c.id');

    	// return either array or paginator
    	return $this->fetchAll($db->fetchAll($select));
    }

    // get all events the user has created or events that he was invited to
	public function browseYourEvents($user_id, $the_start, $the_end)
	{
		$db = $this->getDbTable();
		$now = new DateTime('now');

		// the query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'))
			->joinLeft(array('a' => 'po_calendar_attendees'), "a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE), array('atype' => 'type', 'astatus' => 'status'))
			->where("x.active = 1 AND x.is_banned != 1")
			->where('a.status != 2 OR a.status IS NULL')
			->where("c.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE))
			->where("(" .
				"(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_end) >= '{$the_start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$the_end->format('U')}' " .
				"AND (UNIX_TIMESTAMP(c.date_start) >= '{$the_start->format('U')}' " . // event already listed if not
				"OR UNIX_TIMESTAMP(c.date_start) <= '{$now->format('U')}')) " . // but not in the first page
	    		"OR " .
	    		"(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_start) >= '{$the_start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$the_end->format('U')}') " .
	    		"OR " .
	    		"(`repeat` = '1' AND UNIX_TIMESTAMP(c.repeat_until) >= '{$the_start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$the_end->format('U')}') " .
			")")
			->order("c.date_start ASC");

		// return what was found
		return $db->fetchAll($select);
	}

	// get live chats
	public function browseLiveChats($user_id) {
		
		$db = $this->getDbTable();
		$now = new DateTime('now');

		// the query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'))
			->joinLeft(array('a' => 'po_calendar_attendees'), "a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE), array('atype' => 'type', 'astatus' => 'status'))
			->joinLeft(array('m' => 'po_chat'), 'm.calendar_id = c.id', array())
			->where("x.active = 1 AND x.is_banned != 1")
			->where('c.type = 3 AND c.fee = 1')
			->where("UNIX_TIMESTAMP(c.date_start) <= '{$now->format('U')}'")
			->order("m.date_created DESC")
			->group('c.id');

		// return what was found
		return $db->fetchAll($select)->toArray();
	}

	// get next 2 future chats
	public function browseFutureChats($user_id) {
		
		$db = $this->getDbTable();
		$now = new DateTime('now');

		// the query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'))
			->joinLeft(array('a' => 'po_calendar_attendees'), "a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE), array('atype' => 'type', 'astatus' => 'status'))
			->where("x.active = 1 AND x.is_banned != 1")
			->where('c.type = 3')
			->where("UNIX_TIMESTAMP(c.date_start) >= '{$now->format('U')}'")
			->order("c.date_start");

		// return what was found
		return $db->fetchAll($select)->toArray();
	}

    // get all events events (only special events of course)
	public function browseAllEvents($filter = array(), $exclude = false, $order = false, $the_start, $the_end) {
		
		$db = $this->getDbTable();
		$now = new DateTime('now');

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'));

		// are we supposed to exclude something ?
		if($exclude != false)
			$select->joinLeft(array('a' => 'po_calendar_attendees'), "a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($exclude, Zend_Db::BIGINT_TYPE), array('atype' => 'type', 'astatus' => 'status'));

		$select->where("x.active = 1 AND x.is_banned != 1");
		$select->where('c.type = 2 OR c.type = 3'); // only events
		$select->where("(" .
			"(UNIX_TIMESTAMP(c.date_end) >= '{$the_start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$the_end->format('U')}'" .
			"AND (UNIX_TIMESTAMP(c.date_start) >= '{$the_start->format('U')}' " . // event already listed if not
			"OR UNIX_TIMESTAMP(c.date_start) <= '{$now->format('U')}')) " . // but not in the first page
    		"OR " .
    		"(UNIX_TIMESTAMP(c.date_start) >= '{$the_start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$the_end->format('U')}')".
		")");

		// exclude code again
		if($exclude != false) {
			$select->where('a.user_id != ? OR a.user_id IS NULL OR a.status = 2', $exclude); // where user is not in the list, or user is null or user has declined
			$select->where('c.user_id != ?', $exclude); // where owner is not you
		}

		// filter by
		foreach($filter as $one)
			$select->where($one);

		// order
        if($order != false) {
			if (strpos($order, ",") > 0) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token)
					$select->order($token);
			} else $select->order($order);
		}

        // return array
		return $db->fetchAll($select);
	}

    /**
     * get all special events the user has been invited to
     * @author Flavius
     * @modified by Lotzi: gets all the events the user has been invited to, not the special ones
     */
	public function getSpecialEvents($user_id, $start, $end)
	{
		$db = $this->getDbTable()->getAdapter();
		$select = $db->query("SELECT c.*, a.type AS atype, a.status AS astatus, x.name AS user_name, x.avatar AS user_avatar
			FROM po_calendar AS c

			LEFT JOIN po_users AS x ON c.user_id = x.id
			INNER JOIN po_calendar_attendees AS a ON a.calendar_id = c.id
			WHERE
				x.active = 1 AND x.is_banned != 1 AND
				a.user_id = ".$db->quote($user_id, Zend_Db::BIGINT_TYPE)." AND a.status != '2' AND

			((UNIX_TIMESTAMP(c.date_end) >= '{$start}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end}')
    		OR
    		(UNIX_TIMESTAMP(c.date_start) >= '{$start}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end}'))
		");

		return $select->fetchAll();
	}

	/**
	 * returns the pet appointments
	 *
	 * @param int $pet_id
	 */
	public function browsePetEvents($pet_id, $user_id) {
		$db = $this->getDbTable();

		// define start and end from today to 7 days in the future
		$start = new DateTime('now');
		$end = clone $start;
		$end->add(new DateInterval('P7D'));

		// the query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'))
			->joinInner(
				array('link' => 'po_service_members_pets'),
				'c.link_id = link.id',
				array('service_id' => 'service_id'))
			->joinLeft(
				array('a' => 'po_calendar_attendees'),
				"a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE),
				array(
					'atype' => 'type',
					'astatus' => 'status',
					'auser_id' => 'user_id'
				))
			->where("x.active = 1 AND x.is_banned != 1")
			->where('a.status != 2 OR a.status IS NULL')
			->where("link.pet_id = ?", $pet_id, Zend_Db::BIGINT_TYPE)
			->where("c.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE))
			->where("(
				(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_end) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
	    		OR
	    		(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_start) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
	    		OR
	    		(`repeat` = '1' AND UNIX_TIMESTAMP(c.repeat_until) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
			)")
			->order("c.date_start ASC")
			->limit(50);

		// return what was found
		return $db->fetchAll($select);
	}

	/**
	 * returns the service appointments
	 *
	 * @param int $service_id
	 */
	public function browseServiceEvents($service_id, $user_id) {
		$db = $this->getDbTable();

		// define start and end from today 00:00:00 to 7 days in the future
		$start = new DateTime('now');
		$end = clone $start;
		$end->add(new DateInterval('P7D'));

		// the query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('c' => 'po_calendar'))
			->joinLeft(array('x' => 'po_users'), "c.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'))
			->joinInner(
				array('link' => 'po_service_members_pets'),
				'c.link_id = link.id',
				array('service_id' => 'service_id'))
			->joinLeft(
				array('a' => 'po_calendar_attendees'),
				"a.calendar_id = c.id AND a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE),
				array(
					'atype' => 'type',
					'astatus' => 'status',
					'auser_id' => 'user_id'
				))
			->where("x.active = 1 AND x.is_banned != 1")
			->where('a.status != 2 OR a.status IS NULL')
			->where("link.service_id = ?", $service_id, Zend_Db::BIGINT_TYPE)
			->where("c.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE)." OR a.user_id = ".$db->getAdapter()->quote($user_id, Zend_Db::BIGINT_TYPE))
			->where("(
				(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_end) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
	    		OR
	    		(`repeat` = '0' AND UNIX_TIMESTAMP(c.date_start) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
	    		OR
	    		(`repeat` = '1' AND UNIX_TIMESTAMP(c.repeat_until) >= '{$start->format('U')}' AND UNIX_TIMESTAMP(c.date_start) <= '{$end->format('U')}')
			)")
			->order("c.date_start ASC")
			->limit(50);

		// return what was found
		return $db->fetchAll($select);
	}
}