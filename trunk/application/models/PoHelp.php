<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoHelp extends MainModel
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

	protected $_Flagged;
    protected $_PetId;
	protected $_PetMedicalId;

	/**
	 * mysql var type bigint(20)
	 * @var int
	 */
	protected $_FolderId;
	protected $_Rights;
	protected $_Views;

	/**
	 * mysql var type tinyint(1)
	 *
	 * @var string
	 */
	protected $_Archived;


	/**
	 * @var Petolio_Model_PoUsers
	 */
	protected $_Owner;
	protected $_Pet;
	protected $_Medical;



	function __construct() {
		$this->setColumnsList(array(
	    'id'=>'Id',
	    'user_id'=>'UserId',
	    'attribute_set_id'=>'AttributeSetId',
	    'date_created'=>'DateCreated',
	    'date_modified'=>'DateModified',
		'flagged'=>'Flagged',
	    'pet_id'=>'PetId',
	    'pet_medical_id'=>'PetMedicalId',
	    'rights'=>'Rights',
		'folder_id'=>'FolderId',
	    'archived'=>'Archived'
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoHelp
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
	 * @return Petolio_Model_PoHelp
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
	 * @return Petolio_Model_PoHelp
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
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoHelp
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
	 * @return Petolio_Model_PoHelp
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
	 * sets column Archived type tinyint(1)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoHelp
	 *
	 **/

	public function getFlagged() { return $this->_Flagged; }
	public function setFlagged($data) { $this->_Flagged = $data; return $this; }

    public function setPetId($data) { $this->_PetId = $data; return $this; }
    public function getPetId() { return $this->_PetId; }

    public function setPetMedicalId($data) { $this->_PetMedicalId = $data; return $this; }
    public function getPetMedicalId() { return $this->_PetMedicalId; }

    public function setFolderId($data) { $this->_FolderId = $data; return $this; }
    public function getFolderId() { return $this->_FolderId; }

    public function setRights($data) { $this->_Rights = $data; return $this; }
    public function getRights() { return $this->_Rights; }

    public function setViews($data) { $this->_Views = $data; return $this; }
    public function getViews() { return $this->_Views; }

	public function setArchived($data)
	{
		$this->_Archived=$data;
		return $this;
	}

	/**
	 * gets column Archived type tinyint(1)
	 * @return int
	 */

	public function getArchived()
	{
		return $this->_Archived;
	}

	/**
	 * set the _Owner obj
	 * @param Petolio_Model_PoUsers $owner
	 * @throws Exception
	 */
	public function setOwner($owner = null) {
		if(!isset($owner)) {
			if(!$this->getUserId())
				throw new Exception('User id it\'s not set');

			$owner = new Petolio_Model_PoUsers();
			$owner->find($this->getUserId());
		}
		if(!$owner instanceof Petolio_Model_PoUsers)
			throw new Exception('Invalid instance for $owner object, Petolio_Model_PoUsers expected.');

		$this->_Owner = $owner;
		return $this;
	}

	/**
	 * gets the owner of the pet
	 * @return PoUsers obj
	 */
	public function getOwner() {
		if(!isset($this->_Owner))
			$this->setOwner();

		return $this->_Owner;
	}

	public function setPet($pet = null) {
		if(!isset($pet)) {
			if(!$this->getPetId())
				return false;

			$db = new Petolio_Model_PoPets();
			$pet = reset($db->getPets("array", "a.id = {$this->getPetId()}"));
		}
		if($pet == false)
			return false;

		$this->_Pet = $pet;
		return $this;
	}

	public function getPet() {
		if(!isset($this->_Pet))
			$this->setPet();

		return $this->_Pet;
	}

	public function setMedical($medical = null) {
		if(!isset($medical)) {
			if(!$this->getPetMedicalId())
				return false;

			$db = new Petolio_Model_PoMedicalRecords();
			$medical = reset($db->fetchList("id = {$this->getPetMedicalId()}"));
		}
		if(!$medical instanceof Petolio_Model_PoMedicalRecords)
			throw new Exception('Invalid instance for $medical object, Petolio_Model_PoMedicalRecords expected.');

		$this->_Medical = $medical;
		return $this;
	}

	public function getMedical() {
		if(!isset($this->_Medical))
			$this->setMedical();

		return $this->_Medical;
	}

	/**
	 * returns the mapper class
	 *
	 * @return Petolio_Model_PoHelpMapper
	 *
	 */

	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Petolio_Model_PoHelpMapper());
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
	 * Get question list
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
	public function getQuestions($type = 'array', $where = false, $order = false, $limit = false, $search = false, $answered = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_help'), array(
				'*',
				'species' => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT e2.value ORDER BY e2.id ASC SEPARATOR ',')")
			))

			// get question title
			->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -6) = '_title'", array())
			->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('title' => 'value'))

			// get question species
			->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = a.attribute_set_id AND SUBSTRING(c2.code, -8) = '_species'", array('species_id' => 'id'))
			->joinLeft(array('e2' => 'po_attribute_entity_int'), "e2.attribute_id = c2.id AND a.id = e2.entity_id", array())

			// get pet owner
			->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", array('user_name' => 'name'))

			// get rights
			->joinLeft(array('r' => 'po_help_rights'), "a.id = r.help_id", array())

			// count answers
			->joinLeft(array('z' => 'po_help_answers'), "a.id = z.help_id", array('answers' => 'COUNT(DISTINCT z.id)'))

			// join with flagged as well
			->joinLeft(array('f' => 'po_flags'), "a.id = f.entry_id AND f.scope = 'po_pets'", array('flagged_count' => 'COUNT(DISTINCT f.id)'));

		// if search we have to join with this as all attributes
		if($search)
			$select->joinLeft(array('c4' => 'po_attributes'), "c4.attribute_set_id = a.attribute_set_id", array())
				->joinLeft(array('d4' => 'po_attribute_entity_varchar'), "d4.attribute_id = c4.id AND a.id = d4.entity_id", array());

		// if filtered by checkboxes use this join as well
		if($answered)
			$select->joinLeft(array('i' => 'po_help_answers'), "a.id = i.help_id", array());

		// group by pet
		$select->group("a.id");

		// make sure the user is active and not banned
		$select->where("x.active = 1 AND x.is_banned != 1");

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
	public function formatQuestions($data) {
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

		// load models
		$options = new Petolio_Model_PoAttributeOptions();
		$cached = array(); // cache species

		// go through each question
		$locale = Zend_Registry::get('Zend_Translate')->getLocale();
		foreach($data as &$one) {
			// load species (all)
			if(count($options->fetchList("attribute_id = '{$one['species_id']}'")) == count(explode(',', $one['species']))) {
				$one['species'] = Petolio_Service_Util::Tr("All");
				$one['species_ids'] = null;

			// load species
			} else {
				$species = array();
				$species_ids = array();
				foreach(explode(',', $one['species']) as $spec) {
					// not in cached?
					if(!isset($cached[$spec])) {
						$opt = $options->find($spec);
						$value = isset($translate[strtolower($opt->getValue())]) ? $translate[strtolower($opt->getValue())] : $opt->getValue();
						$value = is_array($value) && isset($value[$locale]) ? $value[$locale] : $opt->getValue();
						$cached[$spec] = $value;
					}

					// compile species
					$species[] = $cached[$spec];
					$species_ids[] = $spec;
				}

				// overwrite species
				$one['species'] = implode($species, ', ');
				$one['species_ids'] = implode($species_ids, ', ');
			}
		}

		// return formatted data
		return $data;
	}
}