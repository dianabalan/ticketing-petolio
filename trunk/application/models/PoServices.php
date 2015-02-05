<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoServices extends MainModel
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
	 * mysql var type bigint(20)
	 *
	 * @var int
	 */
	protected $_AttributeSetId;

	/**
	 * mysql var type int(10)
	 *
	 * @var int
	 */
	protected $_MembersLimit;

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
	 * @var Petolio_Model_PoUsers
	 */
	protected $_Owner;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Name;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Address;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Zipcode;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Location;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Country;

	/**
	 * mysql var type varchar(200)
	 * this is the service type
	 * @var string
	 */
	protected $_AttributeSetName;

	protected $_Flagged;
    protected $_GpsLatitude;
    protected $_GpsLongitude;

	/**
	 * mysql var type bigint(20)
	 * @var int
	 */
	protected $_FolderId;

	/**
	 * mysql var type tinyint(1)
	 *
	 * @var int
	 */
	protected $_Deleted;

	function __construct() {
		$this->setColumnsList(array(
	    'id'=>'Id',
	    'user_id'=>'UserId',
	    'attribute_set_id'=>'AttributeSetId',
	    'members_limit'=>'MembersLimit',
	    'date_created'=>'DateCreated',
	    'date_modified'=>'DateModified',
		'flagged'=>'Flagged',
	    'gps_latitude'=>'GpsLatitude',
	    'gps_longitude'=>'GpsLongitude',
		'folder_id'=>'FolderId',
		'deleted'=>'Deleted'
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoServices
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
	 * @return Petolio_Model_PoServices
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
	 * sets column folder_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoServices
	 *
	 **/
	public function setFolderId($data)
	{
		$this->_FolderId=$data;
		return $this;
	}

	/**
	 * gets column folder_id type bigint(20)
	 * @return int
	 */
	public function getFolderId()
	{
		return $this->_FolderId;
	}

	/**
	 * set the _Owner obj
	 * @param Petolio_Model_PoUsers $owner
	 * @throws Exception
	 */
	public function setOwner($owner = null) {
		if ( !isset($owner) ) {
			if ( !$this->getUserId() ) {
				throw new Exception('User id it\'s not set');
			}
			$owner = new Petolio_Model_PoUsers();
			$owner->find($this->getUserId());
		}
		if ( !$owner instanceof Petolio_Model_PoUsers ) {
			throw new Exception('Invalid instance for $owner object, Petolio_Model_PoUsers expected.');
		}
		$this->_Owner = $owner;
		return $this;
	}

	/**
	 * gets the owner of the service
	 * @return PoUsers obj
	 */
	public function getOwner() {
		if ( !isset($this->_Owner) ) {
			$this->setOwner();
		}
		return $this->_Owner;
	}

	/**
	 * sets column attribute_set_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoServices
	 *
	 **/

	public function setAttributeSetId($data)
	{
		$this->_AttributeSetId=$data;
		return $this;
	}

	/**
	 * gets column attribute_set_id type bigint(20)
	 * @return int
	 */

	public function getAttributeSetId()
	{
		return $this->_AttributeSetId;
	}

	/**
	 * gets the attribute set obj for the current attribute set id
	 * @return Petolio_Model_PoAttributeSets
	 */
	public function getAttributeSet() {
		if ( !$this->getAttributeSetId() ) {
			throw new Exception('Attribute set id it\'s not set');
		}
		$attr_set = new Petolio_Model_PoAttributeSets();
		$attr_set->find($this->getAttributeSetId());
		return $attr_set;
	}

	/**
	 * sets column members_limit type int(10)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoServices
	 *
	 **/

	public function setMembersLimit($data)
	{
		$this->_MembersLimit=$data;
		return $this;
	}

	/**
	 * gets column members_limit type int(10)
	 * @return int
	 */

	public function getMembersLimit()
	{
		return $this->_MembersLimit;
	}

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
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
	 * @return Petolio_Model_PoServices
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
	 * sets the name of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setName($data) {
		$this->_Name = $data;
		return $this;
	}

	/**
	 * gets the name of the service if it's any set
	 *
	 * @return string
	 */
	public function getName() {
		return $this->_Name;
	}

	/**
	 * sets the address of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setAddress($data) {
		$this->_Address = $data;
		return $this;
	}

	/**
	 * gets the address of the service if it's any set
	 *
	 * @return string
	 */
	public function getAddress() {
		return $this->_Address;
	}

	/**
	 * sets the zipcode of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setZipcode($data) {
		$this->_Zipcode = $data;
		return $this;
	}

	/**
	 * gets the zipcode of the service if it's any set
	 *
	 * @return string
	 */
	public function getZipcode() {
		return $this->_Zipcode;
	}

	/**
	 * sets the location of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setLocation($data) {
		$this->_Location = $data;
		return $this;
	}

	/**
	 * gets the location of the service if it's any set
	 *
	 * @return string
	 */
	public function getLocation() {
		return $this->_Location;
	}

	/**
	 * sets the country of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setCountry($data) {
		$this->_Country = $data;
		return $this;
	}

	/**
	 * gets the country of the service if it's any set
	 *
	 * @return string
	 */
	public function getCountry() {
		return $this->_Country;
	}

	/**
	 * sets the deleted flag of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setDeleted($data) {
		$this->_Deleted = $data;
		return $this;
	}

	/**
	 * gets the deleted flag of the service
	 *
	 * @return int
	 */
	public function getDeleted() {
		return $this->_Deleted;
	}

	/**
	 * sets the attribute set name of the service
	 *
	 * @param string $data
	 * @return Petolio_Model_PoServices
	 */
	public function setAttributeSetName($data = null) {
		if ( !isset($data) ) {
			if ( !$this->getAttributeSetId() ) {
				throw new Exception('AttributeSetId is not set');
			}
			$attr_set = new Petolio_Model_PoAttributeSets();
			$attr_set->getMapper()->find($this->getAttributeSetId(), $attr_set);
			$data = $attr_set->getName();
		}
		$this->_AttributeSetName = $data;
		return $this;
	}

	/**
	 * gets the attribute set name of the service if it's any set
	 *
	 * @return string
	 */
	public function getAttributeSetName() {
		if ( !isset($this->_AttributeSetName) ) {
			$this->setAttributeSetName();
		}
		return $this->_AttributeSetName;
	}

	/**
	 * get the service members pets
	 * @param array of integers or integer $status
	 * @return array of PoServiceMembersPets
	 */
	public function getMembersPets($status = null, $limit = null) {
		if ( !$this->getId() ) {
			throw new Exception('Service id is not set.');
		}

		$members_pets = array();
		$service_members_pets = new Petolio_Model_PoServiceMembersPets();

		$files = new Petolio_Model_PoFiles();
		
		foreach ($service_members_pets->getServiceMembersPets($this->getId(), $status, $limit) as $line) {
			$owner = new Petolio_Model_PoUsers();
			$owner->setId($line['user_id'])
				->setName($line['user_name'])
				->setEmail($line['user_email'])
				->setPassword($line['user_password'])
				->setActive($line['user_active'])
				->setStreet($line['user_street'])
				->setAddress($line['user_address'])
				->setZipcode($line['user_zipcode'])
				->setLocation($line['user_location'])
				->setCountryId($line['user_country_id'])
				->setPhone($line['user_phone'])
				->setHomepage($line['user_homepage'])
				->setGender($line['user_gender'])
				->setDateOfBirth($line['user_date_of_birth'])
				->setGpsLatitude($line['user_gps_latitude'])
				->setGpsLongitude($line['user_gps_longitude'])
				->setDateForgot($line['user_date_forgot'])
				->setAvatar($line['user_avatar'])
				->setDateCreated($line['user_date_created'])
				->setDateModified($line['user_date_modified'])
				->setType($line['user_type']);

			$pet = new Petolio_Model_PoPets();
			$pet->setId($line['pet_id'])
				->setUserId($line['pet_user_id'])
				->setAttributeSetId($line['pet_attribute_set_id'])
				->setDateCreated($line['pet_date_created'])
				->setDateModified($line['pet_date_modified'])
				->setDeleted($line['pet_deleted'])
				->setName($line['pet_name'])
				->setBreed($line['pet_breed'])
				->setDateOfBirth($line['pet_dateofbirth'])
				->setOwner($owner);
			
			// take the first picture
			$picture = !is_null($line['folder_id']) ? $files->fetchList("folder_id = {$line['folder_id']}", "date_created ASC", 1) : array();
			$pet->setPicture(!count($picture) > 0 ? null : reset($picture)->getFile());

			$member = new Petolio_Model_PoServiceMembersPets();
			$member->setId($line['id'])
				->setPetId($line['pet_id'])
				->setServiceId($line['service_id'])
				->setStatus($line['status'])
				->setMemberPet($pet);

			array_push($members_pets, $member);
		}

		return $members_pets;
	}

	/**
	 * get the service members users
	 * @param array of integers or integer $status
	 * @return array of PoServiceMembersUsers
	 */
	public function getMembersUsers($status = null, $limit = null) {
		if ( !$this->getId() ) {
			throw new Exception('Service id is not set.');
		}

		$members_users = array();
		$service_members_users = new Petolio_Model_PoServiceMembersUsers();
		foreach ($service_members_users->getServiceMembersUsers($this->getId(), $status, $limit) as $line) {
			$user = new Petolio_Model_PoUsers();
			$user->setId($line['user_id'])
				->setName($line['user_name'])
				->setEmail($line['user_email'])
				->setPassword($line['user_password'])
				->setActive($line['user_active'])
				->setStreet($line['user_street'])
				->setAddress($line['user_address'])
				->setZipcode($line['user_zipcode'])
				->setLocation($line['user_location'])
				->setCountryId($line['user_country_id'])
				->setPhone($line['user_phone'])
				->setHomepage($line['user_homepage'])
				->setGender($line['user_gender'])
				->setDateOfBirth($line['user_date_of_birth'])
				->setGpsLatitude($line['user_gps_latitude'])
				->setGpsLongitude($line['user_gps_longitude'])
				->setDateForgot($line['user_date_forgot'])
				->setAvatar($line['user_avatar'])
				->setDateCreated($line['user_date_created'])
				->setDateModified($line['user_date_modified'])
				->setType($line['user_type']);

			$member = new Petolio_Model_PoServiceMembersUsers();
			$member->setId($line['id'])
				->setUserId($line['user_id'])
				->setServiceId($line['service_id'])
				->setStatus($line['status'])
				->setMemberUser($user);

			array_push($members_users, $member);
		}

		return $members_users;
	}

	public function getFlagged() { return $this->_Flagged; }
	public function setFlagged($data) { $this->_Flagged = $data; return $this; }

    public function setGpsLatitude($data) { $this->_GpsLatitude = $data; return $this; }
    public function getGpsLatitude() { return $this->_GpsLatitude; }

    public function setGpsLongitude($data) { $this->_GpsLongitude = $data; return $this; }
    public function getGpsLongitude() { return $this->_GpsLongitude; }

	/**
	 * returns the mapper class
	 *
	 * @return Petolio_Model_PoServicesMapper
	 *
	 */
	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Petolio_Model_PoServicesMapper());
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
		if(!$this->getId())
			throw new Exception('Primary Key does not contain a value');

		return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
	}

	/**
	 * Get service list complete with name and type and owner
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 *
	 * @return either array or paginator
	 */
	public function getServices($type = 'array', $where = false, $order = false, $limit = false, $search = false) {
		$db = $this->getMapper()->getDbTable();
	
		// main query
		$select = $db->select()->setIntegrityCheck(false)
		->from(array('a' => 'po_services'),
				array(
						'*',
						'petolio_service' => new Zend_Db_Expr("CASE WHEN a.user_id = 57 THEN 1 ELSE 0 END")
				)
		)
	
		// get service type
		->joinLeft(array('b' => 'po_attribute_sets'), "a.attribute_set_id = b.id AND b.scope='po_services'", array('scope' => 'type', 'type' => 'b.name'))
	
		// get service name
		->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -5) = '_name'", array())
		->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('name' => 'value'))
	
		// get service description
		->joinLeft(array('c20' => 'po_attributes'), "c20.attribute_set_id = a.attribute_set_id AND SUBSTRING(c20.code, -12) = '_description'", array())
		->joinLeft(array('d20' => 'po_attribute_entity_text'), "d20.attribute_id = c20.id AND a.id = d20.entity_id", array('description' => 'value'))
	
		// get service address
		->joinLeft(
		array('c5' => 'po_attributes'),
		"c5.attribute_set_id = a.attribute_set_id AND SUBSTRING(c5.code, -8) = '_address'",
		array())
		->joinLeft(
		array('d5' => 'po_attribute_entity_varchar'),
		"d5.attribute_id = c5.id AND a.id = d5.entity_id",
		array('service_address' => 'value'))
	
		// get service zipcode
		->joinLeft(
		array('c6' => 'po_attributes'),
		"c6.attribute_set_id = a.attribute_set_id AND SUBSTRING(c6.code, -8) = '_zipcode'",
		array())
		->joinLeft(
		array('d6' => 'po_attribute_entity_varchar'),
		"d6.attribute_id = c6.id AND a.id = d6.entity_id",
		array('service_zipcode' => 'value'))
	
		// get service location
		->joinLeft(
		array('c3' => 'po_attributes'),
		"c3.attribute_set_id = a.attribute_set_id AND SUBSTRING(c3.code, -9) = '_location'",
		array())
		->joinLeft(
		array('d3' => 'po_attribute_entity_varchar'),
		"d3.attribute_id = c3.id AND a.id = d3.entity_id",
		array('service_location' => 'value'))
	
		// get service country
		->joinLeft(
		array('c4' => 'po_attributes'),
		"c4.attribute_set_id = a.attribute_set_id AND SUBSTRING(c4.code, -8) = '_country'",
		array())
		->joinLeft(
		array('d4' => 'po_attribute_entity_int'),
		"d4.attribute_id = c4.id AND a.id = d4.entity_id",
		array())
		->joinLeft(
		array('e4' => 'po_attribute_options'),
		"e4.id = d4.value",
		array("service_country" => "value"))
	
		// get service owner
		->joinLeft(
		array('u' => 'po_users'),
		"a.user_id = u.id",
		array(
		'user_name' => 'name',
		'zipcode',
		'location'
				)
		)
	
		// get service owner country
		->joinLeft(
		array('uc' => 'po_countries'),
		"u.country_id = uc.id",
		array(
		'country' => 'name'
				)
		)
	
		// get service picture
		->joinLeft(
		array('files' => 'po_files'),
		"a.folder_id = files.folder_id AND files.type = 'image'",
		array(
		"picture" => "file",
		"has_picture" => new Zend_Db_Expr("CASE WHEN `files`.`file` IS NOT NULL THEN 1 ELSE 0 END")
		)
		)
	
		// join with flagged as well
		->joinLeft(array('f' => 'po_flags'), "a.id = f.entry_id AND f.scope = 'po_services'", array('flagged_count' => 'COUNT(DISTINCT f.id)'));
	
		// if advanced search on
		if ($search)
			$select->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = a.attribute_set_id", array())
			->joinLeft(array('d2' => 'po_attribute_entity_varchar'), "d2.attribute_id = c2.id AND a.id = d2.entity_id", array());
	
		// group afterwards
		$select->group("a.id");
	
		// make sure the user is active and not banned
		$select->where("u.active = 1 AND u.is_banned != 1");
	
		// filter and sort and limit ? ok
		if (strpos($where, "a.deleted") === false)
			$select->where("a.deleted != 1");
	
		// wheres
		if($where) $select->where($where);
	
		// order
		if($order) {
			if (strpos($order, ",") > 0) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token) {
					$select->order($token);
				}
			} else
				$select->order($order);
		}
	
		// limit
		if($limit) $select->limit($limit);
	
		// return either array or paginator
		return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
	}

	/**
	 * Get service list displayed in the footer
	 * 
	 * @return either array
	 */
	public function getFooterServices() {
		$db = $this->getMapper()->getDbTable();
	
		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_services')
		)
	
		// get service name
		->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -5) = '_name'", array())
		->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('name' => 'value'))
			
		// get service owner
		->joinLeft(
				array('u' => 'po_users'), 
				"a.user_id = u.id",
				array('user_name' => 'name', 'zipcode', 'location'));
	
		// group afterwards
		$select->group("a.id");
	
		// make sure the user is active and not banned
		$select->where("u.active = 1 AND u.is_banned != 1 AND a.deleted != 1");
	
		// order
		$select->order("RAND()");
	
		// limit
		$select->limit(6);

		// return array
		return $db->fetchAll($select)->toArray();
	}
	
	/**
	 * Add translation for type
	 *
	 * @param array|object(paginator) $data
	 * @return array|object(paginator)
	 */
	public function formatServices($data) {
		// nothing found? just return here
		if(!count($data) > 0)
			return $data;

		// get the db object
		$db = $this->getMapper()->getDbTable();

		// get translation cache
		$cache = new Petolio_Service_Cache();
		$translations = $cache->PoTranslations();

		// reindex for quick access
		$translate = array();
		foreach($translations as $trans)
			$translate[strtolower($trans['label'])][$trans['language']] = $trans['value'];

		// go through each pet
		$files = new Petolio_Model_PoFiles();
		$locale = Zend_Registry::get('Zend_Translate')->getLocale();
		
		$product_counts = array();
		$products = new Petolio_Model_PoProducts();
		$service_counts = array();
		$services = new Petolio_Model_PoServices();
		$comments = new Petolio_Model_PoComments();
		
		foreach($data as &$one) {
			// get translations
			$translation_1 = !is_null($one['type']) && isset($translate[strtolower($one['type'])][$locale]) ? $translate[strtolower($one['type'])][$locale] : null;
			
			// overwrite breed
			if(!is_null($translation_1))
				$one['type'] = $translation_1;

			// take the first picture
			//$picture = !is_null($one['folder_id']) ? $files->fetchList("folder_id = {$one['folder_id']}", "date_created ASC", 1) : array();
			//$one['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
			
			// count all user products
			if (!isset($product_counts[$one['user_id']])) {
				$product_counts[$one['user_id']] = $products->countByQuery("user_id = {$one['user_id']} AND archived != 1");
			}
			$one['products_count'] = $product_counts[$one['user_id']];
			
			// count all user services
			if (!isset($service_counts[$one['user_id']])) {
				$service_counts[$one['user_id']] = $services->countByQuery("user_id = {$one['user_id']} AND deleted != 1");
			}
			$one['services_count'] = $service_counts[$one['user_id']];
			
			// count service reviews
			$one['reviews_count'] = $comments->countByQuery("scope = 'po_services' AND entity_id = {$one['id']}");
			
			// format description
			$one['description'] = nl2br(substr(strip_tags($one['description']), 0, 500));
		}

		// return formatted data
		return $data;
	}
}