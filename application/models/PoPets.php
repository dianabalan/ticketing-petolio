<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoPets extends MainModel
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
	 * mysql var type tinyint(1)
	 *
	 * @var string
	 */
	protected $_Deleted;

	/**
	 * mysql var type tinyint(1)
	 *
	 * @var string
	 */
	protected $_ToAdopt;

	protected $_MobileEmergency;

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
	 * mysql var type tinyint(1)
	 *
	 * @var int
	 */
	protected $_Flagged;

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Breed;

	/**
	 * mysql var type datetime
	 *
	 * @var string
	 */
	protected $_DateOfBirth;

	/**
	 * the main picture of the pet
	 * not an actual db field
	 * used only rarely, and populated manually
	 * 
	 * @var string
	 */
	protected $_Picture;

	function __construct() {
		$this->setColumnsList(array(
		    'id'=>'Id',
		    'user_id'=>'UserId',
		    'attribute_set_id'=>'AttributeSetId',
		    'date_created'=>'DateCreated',
		    'date_modified'=>'DateModified',
		    'deleted'=>'Deleted',
	    	'to_adopt'=>'ToAdopt',
	    	'mobile_emergency'=>'MobileEmergency',
			'flagged'=>'Flagged'
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoPets
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
	 * @return Petolio_Model_PoPets
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
	 * sets column attribute_set_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoPets
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
	 * gets the attribute set name for the current attribute set id
	 * @return string
	 */
	public function getAttributeSetName() {
		if ( !$this->getAttributeSetId() ) {
			throw new Exception('Attribute set id it\'s not set');
		}
		$attr_set = new Petolio_Model_PoAttributeSets();
		$attr_set->getMapper()->find($this->getAttributeSetId(), $attr_set);
		return $attr_set->getName();
	}

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoPets
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
	 * @return Petolio_Model_PoPets
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
	 * sets column deleted type tinyint(1)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoPets
	 *
	 **/

	public function setDeleted($data)
	{
		$this->_Deleted=$data;
		return $this;
	}

	/**
	 * gets column to_adopt type tinyint(1)
	 * @return int
	 */

	public function getToAdopt()
	{
		return $this->_ToAdopt;
	}

	/**
	 * sets column to_adopt type tinyint(1)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoPets
	 *
	 **/

	public function setToAdopt($data)
	{
		$this->_ToAdopt=$data;
		return $this;
	}

	public function getMobileEmergency() { return $this->_MobileEmergency; }
	public function setMobileEmergency($data) { $this->_MobileEmergency=$data; return $this; }

	/**
	 * gets column deleted type tinyint(1)
	 * @return int
	 */

	public function getDeleted()
	{
		return $this->_Deleted;
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
	 * gets the owner of the pet
	 * @return PoUsers obj
	 */
	public function getOwner() {
		if ( !isset($this->_Owner) ) {
			$this->setOwner();
		}
		return $this->_Owner;
	}

	/**
	 * sets the name of the pet
	 *
	 * @param string $data
	 * @return Petolio_Model_PoPets
	 */
	public function setName($data) {
		$this->_Name = $data;
		return $this;
	}

	/**
	 * gets the name of the pet if it's any set
	 *
	 * @return string
	 */
	public function getName() {
		return $this->_Name;
	}

	/**
	 * sets the breed of the pet
	 *
	 * @param string $data
	 * @return Petolio_Model_PoPets
	 */
	public function setBreed($data) {
		$this->_Breed = $data;
		return $this;
	}

	/**
	 * gets the breed of the pet if it's any set
	 *
	 * @return string
	 */
	public function getBreed() {
		return $this->_Breed;
	}

	/**
	 * sets the dateofbirth of the pet
	 *
	 * @param string $data
	 * @return Petolio_Model_PoPets
	 */
	public function setDateOfBirth($data) {
		$this->_DateOfBirth = $data;
		return $this;
	}

	/**
	 * gets the dateofbirth of the pet if it's any set
	 *
	 * @return string
	 */
	public function getDateOfBirth() {
		return $this->_DateOfBirth;
	}

	/**
	 * gets if this record is flagged or not: 0 or 1
	 * @return int
	 */
	public function getFlagged() {
		return $this->_Flagged;
	}

	/**
	 * set if this record is flagged or not: 0 or 1
	 *
	 * @param int $data
	 */
	public function setFlagged($data) {
		$this->_Flagged = $data;
		return $this;
	}

	public function getPicture() { 
		return $this->_Picture;
	}
	public function setPicture($data) {
		$this->_Picture=$data; 
		return $this;
	}
	
	/**
	 * returns the mapper class
	 *
	 * @return Petolio_Model_PoPetsMapper
	 *
	 */
	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Petolio_Model_PoPetsMapper());
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
	 * Get pet list complete with name and gender and breed and picture
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 * @param bool $search - if search join some more
	 * @param bool $extra - even more information about the pet
	 *
	 * @return either array or paginator
	 */
	public function getPets($type = 'array', $where = false, $order = false, $limit = false, $search = false, $extra = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_pets'),
					array(
						'*',
						'breed' => new Zend_Db_Expr("CASE WHEN d2.value IS NULL THEN f2.value ELSE d2.value END"),
						'type' => "b.name"
					)
				)

			// get pet type
			->joinLeft(array('b' => 'po_attribute_sets'), "a.attribute_set_id = b.id AND b.scope='po_pets'", array('translation_1' => 'b.name'))

			// get pet name
			->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -5) = '_name'", array())
			->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('name' => 'value'))

			// get pet breed
			->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = a.attribute_set_id AND SUBSTRING(c2.code, -6) = '_breed'", array())
			->joinLeft(array('d2' => 'po_attribute_entity_varchar'), "d2.attribute_id = c2.id AND a.id = d2.entity_id", array())
			->joinLeft(array('e2' => 'po_attribute_entity_int'), "e2.attribute_id = c2.id AND a.id = e2.entity_id", array())
			->joinLeft(array('f2' => 'po_attribute_options'), "f2.attribute_id = c2.id AND f2.id = e2.value", array('translation_2' => 'f2.value'))

			// get pet gender
			->joinLeft(array('c3' => 'po_attributes'), "c3.attribute_set_id = a.attribute_set_id AND SUBSTRING(c3.code, -7) = '_gender'", array())
			->joinLeft(array('d3' => 'po_attribute_entity_int'), "d3.attribute_id = c3.id AND a.id = d3.entity_id", array('gender_id' => 'value'))
			->joinLeft(array('e3' => 'po_attribute_options'), "e3.attribute_id = c3.id AND e3.id = d3.value", array('gender' => 'value'))

			// get pet birth date
			->joinLeft(array('c6' => 'po_attributes'), "c6.attribute_set_id = a.attribute_set_id AND SUBSTRING(c6.code, -12) = '_dateofbirth'", array())
			->joinLeft(array('d6' => 'po_attribute_entity_datetime'), "d6.attribute_id = c6.id AND a.id = d6.entity_id", array('dateofbirth' => 'value'))

			// get pet owner
			->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", array('user_name' => 'name', 'user_address' => 'address', 'user_zipcode' => 'zipcode', 'user_location' => 'location', 'user_country_id' => 'country_id', 'user_category_id' => 'category_id'))

			// get gallery picture
			->joinLeft(array('y' => 'po_folders'), "a.id = y.pet_id AND y.name = 'gallery'", array('folder_id' => 'y.id'))

			// join with flagged as well
			->joinLeft(array('f' => 'po_flags'), "a.id = f.entry_id AND f.scope = 'po_pets'", array('flagged_count' => 'COUNT(DISTINCT f.id)'));

		// get pet description if extra
		if ($extra)
			$select->joinLeft(array('c5' => 'po_attributes'), "c5.attribute_set_id = a.attribute_set_id AND SUBSTRING(c5.code, -12) = '_description'", array())
				->joinLeft(array('d5' => 'po_attribute_entity_text'), "d5.attribute_id = c5.id AND a.id = d5.entity_id", array('description' => 'value'));

		// if search we have to join with this as all attributes
		if ($search)
			$select->joinLeft(array('c4' => 'po_attributes'), "c4.attribute_set_id = a.attribute_set_id", array())
				->joinLeft(array('d4' => 'po_attribute_entity_varchar'), "d4.attribute_id = c4.id AND a.id = d4.entity_id", array());

		// group by pet
		$select->group("a.id");

		// make sure the user is active and not banned
		$select->where("x.active = 1 AND x.is_banned != 1");

		// filter and sort and limit ? ok
		if (strpos($where, "a.deleted") === false)
			$select->where("a.deleted != 1");

		if($where) $select->where($where);
		if($order) {
			if (strpos($order, ",") > 0) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token) {
					$select->order($token);
				}
			} else {
				$select->order($order);
			}
		}
		if($limit) $select->limit($limit);

		// return either array or paginator
		return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
	}

	/**
	 * Add translation for type and breed and add the picture
	 *
	 * @param array|object(paginator) $data
	 * @return array|object(paginator)
	 */
	public function formatPets($data) {
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
		foreach($data as &$one) {
			// get translations
			$translation_1 = !is_null($one['translation_1']) && isset($translate[strtolower($one['translation_1'])][$locale]) ? $translate[strtolower($one['translation_1'])][$locale] : null;
			$translation_2 = !is_null($one['translation_2']) && isset($translate[strtolower($one['translation_2'])][$locale]) ? $translate[strtolower($one['translation_2'])][$locale] : null;

			// overwrite breed
			if(!is_null($translation_1))
				$one['type'] = $translation_1;

			// overwrite type
			if(!is_null($translation_2))
				$one['breed'] = $translation_2;

			// take the first picture
			$picture = !is_null($one['folder_id']) ? $files->fetchList("folder_id = {$one['folder_id']}", "date_created ASC", 1) : array();
			$one['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
		}

		// return formatted data
		return $data;
	}
}