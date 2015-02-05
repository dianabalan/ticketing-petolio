<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoUsers extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    protected $_FirstName;
    protected $_LastName;

    /**
     * mysql var type varchar(300)
     *
     * @var string
     */
    protected $_Name;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Email;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Password;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Active;

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
     * mysql var type varchar(20)
     *
     * @var string
     */
    protected $_Phone;

    protected $_PrivatePhone;
    protected $_BusinessPhone;
    protected $_PrivateFax;
    protected $_BusinessFax;

    /**
     * mysql var type varchar(100)
     *
     * @var string
     */
    protected $_Homepage;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Gender;

    /**
     * mysql var type date
     *
     * @var date
     */
    protected $_DateOfBirth;

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
    protected $_DateForgot;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Type;

	protected $_CategoryId;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_Avatar;

    /**
     * mysql var type char(32)
     *
     * @var string
     */
    protected $_SessionId;

    /**
     * mysql var type varchar(200)
     *
     * @var string
     */
    protected $_CountryName;

    protected $_DashEmailNotification;
	protected $_WeeklyEmailNotification;
	protected $_OtherEmailNotification;

    protected $_IsAdmin;
	protected $_IsEditor;

    protected $_IsBanned;

    protected $_Timezone;
	protected $_Language;

	protected $_Invisible;
	
	protected $_About_Us;
	protected $_Cover;

	function __construct() {
	    $this->setColumnsList(array(
		    'id'=>'Id',
		    'name'=>'Name',
		    'email'=>'Email',
		    'password'=>'Password',
		    'active'=>'Active',
		    'street'=>'Street',
		    'address'=>'Address',
		    'zipcode'=>'Zipcode',
		    'location'=>'Location',
		    'country_id'=>'CountryId',
		    'phone'=>'Phone',
		    'homepage'=>'Homepage',
		    'gender'=>'Gender',
		    'date_of_birth'=>'DateOfBirth',
		    'gps_latitude'=>'GpsLatitude',
		    'gps_longitude'=>'GpsLongitude',
		    'date_created'=>'DateCreated',
		    'date_modified'=>'DateModified',
		    'date_forgot'=>'DateForgot',
		    'type'=>'Type',
		    'category_id'=>'CategoryId',
		    'avatar'=>'Avatar',
	    	'session_id' => 'SessionId',
	    	'dash_email_notification' => 'DashEmailNotification',
	    	'weekly_email_notification' => 'WeeklyEmailNotification',
	    	'other_email_notification' => 'OtherEmailNotification',
	    	'is_admin' => 'IsAdmin',
	    	'is_editor' => 'IsEditor',
	    	'is_banned' => 'IsBanned',
	    	'timezone' => 'Timezone',
	    	'language' => 'Language',
	    	'invisible' => 'Invisible',
	    	'about_us' => 'AboutUs',
	    	'cover' => 'Cover'
	    ));
	}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoUsers
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

    public function setFirstName($data) { $this->_FirstName = $data; return $this; }
    public function getFirstName() { return $this->_FirstName; }

    public function setLastName($data) { $this->_LastName = $data; return $this; }
    public function getLastName() { return $this->_LastName; }

    /**
     * sets column name type varchar(300)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setName($data)
    {
        $this->_Name=$data;
        return $this;
    }

    /**
     * gets column name type varchar(300)
     * @return string
     */

    public function getName()
    {
        return ucwords($this->_Name);
    }

    /**
     * sets column email type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setEmail($data)
    {
        $this->_Email=$data;
        return $this;
    }

    /**
     * gets column email type varchar(100)
     * @return string
     */

    public function getEmail()
    {
        return $this->_Email;
    }

    /**
     * sets column password type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setPassword($data)
    {
        $this->_Password=$data;
        return $this;
    }

    /**
     * gets column password type varchar(100)
     * @return string
     */

    public function getPassword()
    {
        return $this->_Password;
    }

    /**
     * sets column active type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setActive($data)
    {
        $this->_Active=$data;
        return $this;
    }

    /**
     * gets column active type tinyint(1)
     * @return int
     */

    public function getActive()
    {
        return $this->_Active;
    }

    /**
     * sets column street type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * sets column phone type varchar(20)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setPhone($data)
    {
        $this->_Phone=$data;
        return $this;
    }

    public function setPrivatePhone($data) { $this->_PrivatePhone = $data; return $this; }
    public function getPrivatePhone() { return $this->_PrivatePhone; }

    public function setBusinessPhone($data) { $this->_BusinessPhone = $data; return $this; }
    public function getBusinessPhone() { return $this->_BusinessPhone; }

    public function setPrivateFax($data) { $this->_PrivateFax = $data; return $this; }
    public function getPrivateFax() { return $this->_PrivateFax; }

    public function setBusinessFax($data) { $this->_BusinessFax = $data; return $this; }
    public function getBusinessFax() { return $this->_BusinessFax; }

    /**
     * gets column phone type varchar(20)
     * @return string
     */

    public function getPhone()
    {
        return $this->_Phone;
    }

    /**
     * sets column homepage type varchar(100)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setHomepage($data)
    {
        $this->_Homepage=$data;
        return $this;
    }

    /**
     * gets column homepage type varchar(100)
     * @return string
     */

    public function getHomepage()
    {
        return $this->_Homepage;
    }

    /**
     * sets column gender type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setGender($data)
    {
        $this->_Gender=$data;
        return $this;
    }

    /**
     * gets column gender type tinyint(1)
     * @return int
     */

    public function getGender()
    {
        return $this->_Gender;
    }

    /**
     * sets column date_of_birth type date
     *
     * @param date $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setDateOfBirth($data)
    {
        $this->_DateOfBirth=$data;
        return $this;
    }

    /**
     * gets column date_of_birth type date
     * @return date
     */

    public function getDateOfBirth()
    {
        return $this->_DateOfBirth;
    }

    /**
     * sets column gps_latitude type double(18,15)
     *
     * @param  $data
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * sets column date_created type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
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
     * @return Petolio_Model_PoUsers
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
     * sets column date_modified type timestamp
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setDateForgot($data)
    {
        $this->_DateForgot=$data;
        return $this;
    }

    /**
     * gets column date_modified type timestamp
     * @return string
     */

    public function getDateForgot()
    {
        return $this->_DateForgot;
    }

    /**
     * sets column type type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoUsers
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
     * sets column avatar type varchar(200)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     *
     **/

    public function setCategoryId($data) { $this->_CategoryId = $data; return $this; }
    public function getCategoryId() { return $this->_CategoryId; }

	public function setAvatar($data)
    {
        $this->_Avatar=$data;
        return $this;
    }

    /**
     * gets column avatar type varchar(200)
     * @return string
     */

    public function getAvatar()
    {
        return $this->_Avatar;
    }

    /**
     * sets column session_id type char(32)
     *
     * @param string $data
     * @return Petolio_Model_PoUsers
     */
    public function setSessionId($data)
    {
        $this->_SessionId=$data;
        return $this;
    }

    /**
     * gets column session_id type char(32)
     * @return string
     */

    public function getSessionId()
    {
        return $this->_SessionId;
    }

	/**
	 * sets the country name of the user
	 *
	 * @param string $data
	 * @return Petolio_Model_PoUsers
	 */
	public function setCountryName($data) {
		$this->_CountryName = $data;
		return $this;
	}

	/**
	 * gets the country name of the user if it's any set
	 *
	 * @return string
	 */
	public function getCountryName() {
		return $this->_CountryName;
	}

	public function setDashEmailNotification($data) {
		$this->_DashEmailNotification = $data; return $this;
	}
	public function getDashEmailNotification() {
		return $this->_DashEmailNotification;
	}

	public function setWeeklyEmailNotification($data) {
		$this->_WeeklyEmailNotification = $data; return $this;
	}
	public function getWeeklyEmailNotification() {
		return $this->_WeeklyEmailNotification;
	}

	public function setOtherEmailNotification($data) {
		$this->_OtherEmailNotification = $data; return $this;
	}
	public function getOtherEmailNotification() {
		return $this->_OtherEmailNotification;
	}
	public function isOtherEmailNotification() {
		return (isset($this->_OtherEmailNotification) && intval($this->_OtherEmailNotification) == 1);
	}

	public function setIsAdmin($data) {
		$this->_IsAdmin = $data; return $this;
	}
	public function getIsAdmin() {
		return $this->_IsAdmin;
	}

	public function setIsEditor($data) {
		$this->_IsEditor = $data; return $this;
	}
	public function getIsEditor() {
		return $this->_IsEditor;
	}

	public function setIsBanned($data) {
		$this->_IsBanned = $data; return $this;
	}
	public function getIsBanned() {
		return $this->_IsBanned;
	}

	public function setTimezone($data) { $this->_Timezone = $data; return $this; }
	public function getTimezone() { return $this->_Timezone; }

	public function setLanguage($data) { $this->_Language = $data; return $this; }
	public function getLanguage() { return $this->_Language; }

	public function setInvisible($data) { $this->_Invisible = $data; return $this; }
	public function getInvisible() { return $this->_Invisible; }

	public function setAboutUs($data) {
		$this->_About_Us = $data;
		return $this;
	}
	
	public function getAboutUs() {
		return $this->_About_Us;
	}

	public function setCover($data) {
		$this->_Cover = $data;
		return $this;
	}
	
	public function getCover() {
		return $this->_Cover;
	}
	
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoUsersMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoUsersMapper());
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

    // filler for friend / partner functions
    public function fill($row) {
		$user = new Petolio_Model_PoUsers();
		$user->setId($row->id)
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

		return $user;
    }

    /**
     * return all the friends of this user
     * @return array of Petolio_Model_PoUsers
     */
    public function getUserFriends() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

    	$friends = new Petolio_Model_DbTable_PoFriends();
    	$all = array_merge($friends->findFriends($this->getId(), null, 1), $friends->findFriends(null, $this->getId(), 1));
    	ksort($all); // sort invited / accepted

		$result = array();
    	foreach ($all as $idx => $row) {
			$user = $this->fill($row);
			$result[$idx] = $user;
    	}

    	return $result;
    }

    /**
     * return all the service providers who has links with this user and his pets
     * @return array of Petolio_Model_PoUsers
     */
    public function getUserPartners($status = null) {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

    	$all = array_merge($this->getMapper()->getDbTable()->getUserPartners($this->getId(), $status), $this->getMapper()->getDbTable()->getPartnerUsers($this->getId(), $status));
    	ksort($all); // sort partners / members

		$result = array();
    	foreach ($all as $idx => $row) {
			$user = $this->fill($row);
			$result[$idx] = $user;
    	}

    	return $result;
    }

    /**
	 * return a list of users:
	 * 		- who are in the current user's friends list
	 * 		- who are the current user's service providers
	 * 		- if the user is a service provider then load the user service's members too
     *
     * @param int or array $status - link and friendship status(es):
	 * 		0 - requested; 1 - accepted; 2 -denied;
	 * @return array of Petolio_Model_PoUsers
     */
    public function getOnlineFriendsAndPartners($status = null) {
        if ( !$this->getId() ) {
            throw new Exception('Primary Key does not contain a value');
        }

        $online_users = $this->getMapper()->getDbTable()->getOnlineFriendsAndPartners($this->getId(), $this->getType(), $status);
        ksort($online_users);

        $result = array();
    	foreach ($online_users as $idx => $row) {
			$user = $this->fill($row);
			$result[$idx] = $user;
    	}

    	return $result;
    }

    /**
	 * finds row by id
	 *
	 * @param int $id
	 * @return Petolio_Model_PoUsers
	 */
	public function findWithReferences($id) {
		$this->getMapper()->findWithReferences($id, $this);
		return $this;
	}

    /**
	 * finds row by id
	 *
	 * @param int $id
	 * @return Petolio_Model_PoUsers
	 */
	public function findWithSession($id) {
		$this->getMapper()->findWithSession($id, $this);
		return $this;
	}

	/**
	 * Get user list
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 * @param string $having
	 * @param bool $pet - join with pets
	 * @param bool $service - join with services
	 *
	 * @return either array or paginator
	 */
	public function getUsers($type = 'array', $where = false, $order = false, $limit = false, $having = false, $pet = false, $service = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_users'), array('*'));

		// join with pets
		if($pet)
			$select->joinLeft(array('x' => 'po_pets'), "a.id = x.user_id", array('join_pet_count' => 'COUNT(x.id)'));

		// join with services
		if($service)
			$select->joinLeft(array('y' => 'po_services'), "a.id = y.user_id", array('join_service_count' => 'COUNT(y.id)'));

		// group up if joining with pet or service
		if($pet || $service)
			$select->group('a.id');

		// handle where
		if($where) $select->where($where);

		// handle order
		if($order) {
			if (strpos($order, ",") > 0)
				foreach (explode(",", $order) as $token)
					$select->order($token);
			else $select->order($order);
		}

		// handle limit
		if($limit) $select->limit($limit);

		// handle having
		if($having) $select->having($having);

		// return either array or paginator
		return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
	}

	/**
	 * Get all service providers joined with the categories (actually everyone...)
	 */
	public function getProviders($exclude) {
		// query master
		$db = $this->getMapper()->getDbTable()->getAdapter();
		$select = $db->query("
			SELECT a.*, c.name as category_name
			FROM po_users AS a
			LEFT JOIN po_users_categories AS c ON a.category_id = c.id
			WHERE a.id != {$exclude}
		");

		// return what was found
		return $select->fetchAll();
	}
}