<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoCalendar extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_UserId;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Subject;

    protected $_Species;
    protected $_Mod;

    protected $_Fee;
    protected $_Cap;

    /**
     * mysql var type text
     *
     * @var text
     */
    protected $_Description;

    /**
     * mysql var type datetime
     *
     * @var datetime
     */
    protected $_DateStart;

    /**
     * mysql var type datetime
     *
     * @var datetime
     */
    protected $_DateEnd;

    protected $_AllDay;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Street;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Address;

    /**
     * mysql var type varchar(10)
     *
     * @var string
     */
    protected $_Zipcode;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Location;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_CountryId;

    /**
     * mysql var type double(18,15)
     *
     * @var
     */
    protected $_GpsLatitude;

    /**
     * mysql var type double(18,15)
     *
     * @var
     */
    protected $_GpsLongitude;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Type;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Reminder;

    /**
     * mysql var type int(10)
     *
     * @var int
     */
    protected $_ReminderTime;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Repeat;

    /**
     * mysql var type varchar(45)
     *
     * @var string
     */
    protected $_RepeatMinutes;

    /**
     * mysql var type varchar(45)
     *
     * @var string
     */
    protected $_RepeatHours;

    /**
     * mysql var type varchar(45)
     *
     * @var string
     */
    protected $_RepeatDayOfMonth;

    /**
     * mysql var type varchar(45)
     *
     * @var string
     */
    protected $_RepeatMonth;

    /**
     * mysql var type varchar(45)
     *
     * @var string
     */
    protected $_RepeatDayOfWeek;

    protected $_RepeatUntil;

    /**
     * mysql var type timestamp
     *
     * @var string
     */
    protected $_DateCreated;

    /**
     * mysql var type timestamp
     *
     * @var string
     */
    protected $_DateModified;

    /**
     * mysql var type timestamp
     *
     * @var string
     */
    protected $_DateNextRun;

    protected $_Availability;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_LinkId;

    /**
     * mysql var type tinyint(4)
     *
     * @var int
     */
    protected $_LinkType;


    protected $_UserName;
	protected $_UserAvatar;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'user_id'=>'UserId',
    'user_name'=>'UserName',
    'user_avatar'=>'UserAvatar',
    'subject'=>'Subject',
    'species'=>'Species',
    'mod'=>'Mod',
    'fee'=>'Fee',
    'cap'=>'Cap',
    'description'=>'Description',
    'date_start'=>'DateStart',
    'date_end'=>'DateEnd',
    'street'=>'Street',
    'address'=>'Address',
    'zipcode'=>'Zipcode',
    'location'=>'Location',
    'country_id'=>'CountryId',
    'gps_latitude'=>'GpsLatitude',
    'gps_longitude'=>'GpsLongitude',
    'type'=>'Type',
    'reminder'=>'Reminder',
    'reminder_time'=>'ReminderTime',
    'repeat'=>'Repeat',
    'repeat_minutes'=>'RepeatMinutes',
    'repeat_hours'=>'RepeatHours',
    'repeat_day_of_month'=>'RepeatDayOfMonth',
    'repeat_month'=>'RepeatMonth',
    'repeat_day_of_week'=>'RepeatDayOfWeek',
    'repeat_until'=>'RepeatUntil',
    'date_created'=>'DateCreated',
    'date_modified'=>'DateModified',
    'date_next_run'=>'DateNextRun',
    'availability'=>'Availability',
    'link_id'=>'LinkId',
    'link_type'=>'LinkType'
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setId($data)
    {
        $this->_Id=$data;
        return $this;
    }

    /**
     * gets column id type bigint(20)
     * @return int
     */

    public function getId()
    {
        return $this->_Id;
    }

    /**
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setUserId($data)
    {
        $this->_UserId=$data;
        return $this;
    }

    /**
     * gets column user_id type bigint(20)
     * @return int
     */

    public function getUserId()
    {
        return $this->_UserId;
    }

    /**
     * sets column subject type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setSubject($data)
    {
        $this->_Subject=$data;
        return $this;
    }

    /**
     * gets column subject type varchar(200)
     * @return string
     */

    public function getSubject()
    {
        return $this->_Subject;
    }

    public function setSpecies($data)
    {
        $this->_Species=$data;
        return $this;
    }

    public function getSpecies()
    {
        return $this->_Species;
    }

    public function setMod($data)
    {
        $this->_Mod=$data;
        return $this;
    }

    public function getMod()
    {
        return $this->_Mod;
    }

    public function setFee($data)
    {
        $this->_Fee=$data;
        return $this;
    }

    public function getFee()
    {
        return $this->_Fee;
    }

    public function setCap($data)
    {
        $this->_Cap=$data;
        return $this;
    }

    public function getCap()
    {
        return $this->_Cap;
    }

    /**
     * sets column description type text
     *
     * @param text $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column description type text
     * @return text
     */

    public function getDescription()
    {
        return $this->_Description;
    }

    /**
     * sets column date_start type datetime
     *
     * @param datetime $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDateStart($data)
    {
        $this->_DateStart=$data;
        return $this;
    }

    /**
     * gets column date_start type datetime
     * @return datetime
     */

    public function getDateStart()
    {
        return $this->_DateStart;
    }

    /**
     * sets column date_end type datetime
     *
     * @param datetime $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDateEnd($data)
    {
        $this->_DateEnd=$data;
        return $this;
    }

    /**
     * gets column date_end type datetime
     * @return datetime
     */

    public function getDateEnd()
    {
        return $this->_DateEnd;
    }

    public function setAllDay($data)
    {
        $this->_AllDay=$data;
        return $this;
    }

    public function getAllDay()
    {
        return $this->_AllDay;
    }

    /**
     * sets column street type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setStreet($data)
    {
        $this->_Street=$data;
        return $this;
    }

    /**
     * gets column street type varchar(100)
     * @return string
     */

    public function getStreet()
    {
        return $this->_Street;
    }

    /**
     * sets column address type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setAddress($data)
    {
        $this->_Address=$data;
        return $this;
    }

    /**
     * gets column address type varchar(200)
     * @return string
     */

    public function getAddress()
    {
        return $this->_Address;
    }

    /**
     * sets column zipcode type varchar(10)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setZipcode($data)
    {
        $this->_Zipcode=$data;
        return $this;
    }

    /**
     * gets column zipcode type varchar(10)
     * @return string
     */

    public function getZipcode()
    {
        return $this->_Zipcode;
    }

    /**
     * sets column location type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setLocation($data)
    {
        $this->_Location=$data;
        return $this;
    }

    /**
     * gets column location type varchar(100)
     * @return string
     */

    public function getLocation()
    {
        return $this->_Location;
    }

    /**
     * sets column country_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setCountryId($data)
    {
        $this->_CountryId=$data;
        return $this;
    }

    /**
     * gets column country_id type bigint(20)
     * @return int
     */

    public function getCountryId()
    {
        return $this->_CountryId;
    }

    /**
     * sets column gps_latitude type double(18,15)
     *
     * @param  $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setGpsLatitude($data)
    {
        $this->_GpsLatitude=$data;
        return $this;
    }

    /**
     * gets column gps_latitude type double(18,15)
     * @return
     */

    public function getGpsLatitude()
    {
        return $this->_GpsLatitude;
    }

    /**
     * sets column gps_longitude type double(18,15)
     *
     * @param  $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setGpsLongitude($data)
    {
        $this->_GpsLongitude=$data;
        return $this;
    }

    /**
     * gets column gps_longitude type double(18,15)
     * @return
     */

    public function getGpsLongitude()
    {
        return $this->_GpsLongitude;
    }

    /**
     * sets column type type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setType($data)
    {
        $this->_Type=$data;
        return $this;
    }

    /**
     * gets column type type tinyint(1)
     * @return int
     */

    public function getType()
    {
        return $this->_Type;
    }

    /**
     * sets column reminder type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setReminder($data)
    {
        $this->_Reminder=$data;
        return $this;
    }

    /**
     * gets column reminder type tinyint(1)
     * @return int
     */

    public function getReminder()
    {
        return $this->_Reminder;
    }

    /**
     * sets column reminder_time type int(10)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setReminderTime($data)
    {
        $this->_ReminderTime=$data;
        return $this;
    }

    /**
     * gets column reminder_time type int(10)
     * @return int
     */

    public function getReminderTime()
    {
        return $this->_ReminderTime;
    }

    /**
     * sets column repeat type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeat($data)
    {
        $this->_Repeat=$data;
        return $this;
    }

    /**
     * gets column repeat type tinyint(1)
     * @return int
     */

    public function getRepeat()
    {
        return $this->_Repeat;
    }

    /**
     * sets column repeat_minutes type varchar(45)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeatMinutes($data)
    {
        $this->_RepeatMinutes=$data;
        return $this;
    }

    /**
     * gets column repeat_minutes type varchar(45)
     * @return string
     */

    public function getRepeatMinutes()
    {
        return $this->_RepeatMinutes;
    }

    /**
     * sets column repeat_hours type varchar(45)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeatHours($data)
    {
        $this->_RepeatHours=$data;
        return $this;
    }

    /**
     * gets column repeat_hours type varchar(45)
     * @return string
     */

    public function getRepeatHours()
    {
        return $this->_RepeatHours;
    }

    /**
     * sets column repeat_day_of_month type varchar(45)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeatDayOfMonth($data)
    {
        $this->_RepeatDayOfMonth=$data;
        return $this;
    }

    /**
     * gets column repeat_day_of_month type varchar(45)
     * @return string
     */

    public function getRepeatDayOfMonth()
    {
        return $this->_RepeatDayOfMonth;
    }

    /**
     * sets column repeat_month type varchar(45)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeatMonth($data)
    {
        $this->_RepeatMonth=$data;
        return $this;
    }

    /**
     * gets column repeat_month type varchar(45)
     * @return string
     */

    public function getRepeatMonth()
    {
        return $this->_RepeatMonth;
    }

    /**
     * sets column repeat_day_of_week type varchar(45)
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setRepeatDayOfWeek($data)
    {
        $this->_RepeatDayOfWeek=$data;
        return $this;
    }

    /**
     * gets column repeat_day_of_week type varchar(45)
     * @return string
     */

    public function getRepeatDayOfWeek()
    {
        return $this->_RepeatDayOfWeek;
    }

    public function setRepeatUntil($data)
    {
        $this->_RepeatUntil=$data;
        return $this;
    }

    public function getRepeatUntil()
    {
        return $this->_RepeatUntil;
    }

    /**
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDateCreated($data)
    {
        $this->_DateCreated=$data;
        return $this;
    }

    /**
     * gets column date_created type timestamp
     * @return string
     */

    public function getDateCreated()
    {
        return $this->_DateCreated;
    }

    /**
     * sets column date_modified type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDateModified($data)
    {
        $this->_DateModified=$data;
        return $this;
    }

    /**
     * gets column date_modified type timestamp
     * @return string
     */

    public function getDateModified()
    {
        return $this->_DateModified;
    }

    /**
     * sets column date_next_run type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setDateNextRun($data)
    {
        $this->_DateNextRun=$data;
        return $this;
    }

    /**
     * gets column date_next_run type timestamp
     * @return string
     */

    public function getDateNextRun()
    {
        return $this->_DateNextRun;
    }

    public function setAvailability($data) { $this->_Availability = $data; return $this; }
    public function getAvailability() { return $this->_Availability; }

    /**
     * sets column link_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setLinkId($data)
    {
        $this->_LinkId=$data;
        return $this;
    }

    /**
     * gets column link_id type bigint(20)
     * @return int
     */

    public function getLinkId()
    {
        return $this->_LinkId;
    }

    /**
     * sets column link_type type tinyint(4)
     *
     * @param int $data
     * @return Petolio_Model_PoCalendar
     *
     **/

    public function setLinkType($data)
    {
        $this->_LinkType=$data;
        return $this;
    }

    /**
     * gets column link_type type tinyint(20)
     * @return int
     */

    public function getLinkType()
    {
        return $this->_LinkType;
    }

    public function setUserName($data) { $this->_UserName = $data; return $this; }
    public function getUserName() { return $this->_UserName; }

	public function setUserAvatar($data) { $this->_UserAvatar = $data; return $this; }
    public function getUserAvatar() { return $this->_UserAvatar; }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoCalendarMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoCalendarMapper());
        }
        return $this->_mapper;
    }


    /**
     * deletes current row by deleting a row that matches the primary key
     *
     * @return int
     */

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

    /**
     * Count current/live chats and future chats
     */
    public function countActiveChars($user_id) {
    	return count($this->getMapper()->browseLiveChats($user_id)) + count($this->getMapper()->browseFutureChats($user_id));
    }
}

