<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoProducts extends MainModel
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
    protected $_GpsLatitude;
    protected $_GpsLongitude;

	/**
	 * mysql var type bigint(20)
	 * @var int
	 */
	protected $_FolderId;
	protected $_PrimaryCurrencyId;
	protected $_Links;
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

	/**
	 * mysql var type varchar(200)
	 *
	 * @var string
	 */
	protected $_Title;



	function __construct() {
		$this->setColumnsList(array(
	    'id'=>'Id',
	    'user_id'=>'UserId',
	    'attribute_set_id'=>'AttributeSetId',
	    'date_created'=>'DateCreated',
	    'date_modified'=>'DateModified',
		'flagged'=>'Flagged',
	    'gps_latitude'=>'GpsLatitude',
	    'gps_longitude'=>'GpsLongitude',
		'folder_id'=>'FolderId',
		'primary_currency_id'=>'PrimaryCurrencyId',
	    'links'=>'Links',
	    'archived'=>'Archived',
	    'views'=>'Views'
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoProducts
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
	 * @return Petolio_Model_PoProducts
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
	 * @return Petolio_Model_PoProducts
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
	 * @return Petolio_Model_PoProducts
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
	 * @return Petolio_Model_PoProducts
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
	 * @return Petolio_Model_PoProducts
	 *
	 **/

	public function getFlagged() { return $this->_Flagged; }
	public function setFlagged($data) { $this->_Flagged = $data; return $this; }

    public function setGpsLatitude($data) { $this->_GpsLatitude = $data; return $this; }
    public function getGpsLatitude() { return $this->_GpsLatitude; }

    public function setGpsLongitude($data) { $this->_GpsLongitude = $data; return $this; }
    public function getGpsLongitude() { return $this->_GpsLongitude; }

    public function setFolderId($data) { $this->_FolderId = $data; return $this; }
    public function getFolderId() { return $this->_FolderId; }

    public function setPrimaryCurrencyId($data) { $this->_PrimaryCurrencyId = $data; return $this; }
    public function getPrimaryCurrencyId() { return $this->_PrimaryCurrencyId; }

    public function setLinks($data) { $this->_Links = $data; return $this; }
    public function getLinks() { return $this->_Links; }

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
	 * sets the Title of the pet
	 *
	 * @param string $data
	 * @return Petolio_Model_PoProducts
	 */
	public function setTitle($data) {
		$this->_Title = $data;
		return $this;
	}

	/**
	 * gets the Title of the pet if it's any set
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->_Title;
	}

	/**
	 * returns the mapper class
	 *
	 * @return Petolio_Model_PoProductsMapper
	 *
	 */

	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Petolio_Model_PoProductsMapper());
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
	 * Get product list
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 * @param bool $search - if search join some more
	 * @param mixed $advanced
	 *
	 * @return either array or paginator
	 */
	public function getProducts($type = 'array', $where = false, $order = false, $limit = false, $search = false, $advanced = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_products'), array(
				'*',
				'species' => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT e2.value ORDER BY e2.id ASC SEPARATOR ',')"),
				'pricing' => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT(c3.id, '|', c3.currency_id) ORDER BY c3.id ASC SEPARATOR ',')")
			))

			// get product title
			->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -6) = '_title'", array())
			->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array('title' => 'value'))

			// get product species
			->joinLeft(array('c2' => 'po_attributes'), "c2.attribute_set_id = a.attribute_set_id AND SUBSTRING(c2.code, -8) = '_species'", array('species_id' => 'id'))
			->joinLeft(array('e2' => 'po_attribute_entity_int'), "e2.attribute_id = c2.id AND a.id = e2.entity_id", array())

			// get product condition
			->joinLeft(array('f3' => 'po_attributes'), "f3.attribute_set_id = a.attribute_set_id AND SUBSTRING(f3.code, -10) = '_condition'", array())
			->joinLeft(array('f4' => 'po_attribute_entity_int'), "f4.attribute_id = f3.id AND a.id = f4.entity_id", array())
			->joinLeft(array('e4' => 'po_attribute_options'), "e4.id = f4.value", array("condition" => "value"))
						

			// get product price
			->joinLeft(array('c3' => 'po_attributes'), "c3.attribute_set_id = a.attribute_set_id AND SUBSTRING(c3.code, 8, 6) = '_price' AND c3.currency_id IS NOT NULL", array())

			// get pet owner
			->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", array('user_name' => 'name', 'user_address' => 'address', 'user_zipcode' => 'zipcode', 'user_location' => 'location', 'user_country_id' => 'country_id', 'user_category_id' => 'category_id'))

			// join with flagged as well
			->joinLeft(array('f' => 'po_flags'), "a.id = f.entry_id AND f.scope = 'po_products'", array('flagged_count' => 'COUNT(DISTINCT f.id)'));

		// if search we have to join with this as all attributes
		if($search)
			$select->joinLeft(array('c4' => 'po_attributes'), "c4.attribute_set_id = a.attribute_set_id", array())
				->joinLeft(array('d4' => 'po_attribute_entity_varchar'), "d4.attribute_id = c4.id AND a.id = d4.entity_id", array());

		// if we have advanced filters
		if($advanced) {
			foreach($advanced as $one) {
				if($one['filter'] == 'f2.value')
					// type
					$select->joinLeft(array('f1' => 'po_attributes'), "f1.attribute_set_id = a.attribute_set_id AND SUBSTRING(f1.code, -5) = '_type'", array())
						->joinLeft(array('f2' => 'po_attribute_entity_int'), "f2.attribute_id = f1.id AND a.id = f2.entity_id", array());

				if($one['filter'] == 'f6.value')
					// duration
					$select->joinLeft(array('f5' => 'po_attributes'), "f5.attribute_set_id = a.attribute_set_id AND SUBSTRING(f5.code, -9) = '_duration'", array())
						->joinLeft(array('f6' => 'po_attribute_entity_int'), "f6.attribute_id = f5.id AND a.id = f6.entity_id", array());

				if($one['filter'] == 'f8.value')
					// euro
					$select->joinLeft(array('f7' => 'po_attributes'), "f7.attribute_set_id = a.attribute_set_id AND SUBSTRING(f7.code, -7) = '_price1'", array())
						->joinLeft(array('f8' => 'po_attribute_entity_decimal'), "f8.attribute_id = f7.id AND a.id = f8.entity_id", array());

				if($one['filter'] == 'f10.value')
					// dollar
					$select->joinLeft(array('f9' => 'po_attributes'), "f9.attribute_set_id = a.attribute_set_id AND SUBSTRING(f9.code, -7) = '_price2'", array())
						->joinLeft(array('f10' => 'po_attribute_entity_decimal'), "f10.attribute_id = f9.id AND a.id = f10.entity_id", array());

				if($one['filter'] == 'f12.value')
					// price type
					$select->joinLeft(array('f11' => 'po_attributes'), "f11.attribute_set_id = a.attribute_set_id AND SUBSTRING(f11.code, -10) = '_pricetype'", array())
						->joinLeft(array('f12' => 'po_attribute_entity_int'), "f12.attribute_id = f11.id AND a.id = f12.entity_id", array());

				if($one['filter'] == 'f14.value')
					// address
					$select->joinLeft(array('f13' => 'po_attributes'), "f13.attribute_set_id = a.attribute_set_id AND SUBSTRING(f13.code, -8) = '_address'", array())
						->joinLeft(array('f14' => 'po_attribute_entity_int'), "f14.attribute_id = f13.id AND a.id = f14.entity_id", array());

				if($one['filter'] == 'f16.value')
					// cell phone
					$select->joinLeft(array('f15' => 'po_attributes'), "f15.attribute_set_id = a.attribute_set_id AND SUBSTRING(f15.code, -8) = '_celular'", array())
						->joinLeft(array('f16' => 'po_attribute_entity_int'), "f16.attribute_id = f15.id AND a.id = f16.entity_id", array());

				if($one['filter'] == 'f18.value')
					// shipping
					$select->joinLeft(array('f17' => 'po_attributes'), "f17.attribute_set_id = a.attribute_set_id AND SUBSTRING(f17.code, -9) = '_shipping'", array())
						->joinLeft(array('f18' => 'po_attribute_entity_int'), "f18.attribute_id = f17.id AND a.id = f18.entity_id", array());

				if($one['filter'] == 'f20.value')
					// shipping euro
					$select->joinLeft(array('f19' => 'po_attributes'), "f19.attribute_set_id = a.attribute_set_id AND SUBSTRING(f19.code, -14) = '_shippingcost1'", array())
						->joinLeft(array('f20' => 'po_attribute_entity_decimal'), "f20.attribute_id = f19.id AND a.id = f20.entity_id", array());

				if($one['filter'] == 'f22.value')
					// shipping dollar
					$select->joinLeft(array('f21' => 'po_attributes'), "f21.attribute_set_id = a.attribute_set_id AND SUBSTRING(f21.code, -14) = '_shippingcost2'", array())
						->joinLeft(array('f22' => 'po_attribute_entity_decimal'), "f22.attribute_id = f21.id AND a.id = f22.entity_id", array());
			}
		}

		// group by product
		$select->group("a.id");

		// make sure the user is active and not banned
		$select->where("x.active = 1 AND x.is_banned != 1");

		// filter and sort and limit ? ok
		if(strpos($where, "a.archived") === false)
			$select->where("a.archived != 1");

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
	 * Add species, pricing and add the picture
	 *
	 * @param array|object(paginator) $data
	 * @return array|object(paginator)
	 */
	public function formatProducts($data) {
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
    	$files = new Petolio_Model_PoFiles();
		$options = new Petolio_Model_PoAttributeOptions();
		$decimals = new Petolio_Model_PoAttributeEntityDecimal();
		$currencies = new Petolio_Model_PoCurrencies();
		$cached = array(); // cache species
		$cached2 = array(); // cache currency

		// go through each product
		$locale = Zend_Registry::get('Zend_Translate')->getLocale();
		foreach($data as &$one) {
			// load species (all)
			if(count($options->fetchList("attribute_id = '{$one['species_id']}'")) == count(explode(',', $one['species'])))
				$one['species'] = Petolio_Service_Util::Tr("All");

			// load species
			else {
				$species = array();
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
				}

				// overwrite species
				$one['species'] = implode($species, ', ');
			}
			
			// load condition
			$one['condition'] = isset($translate[strtolower($one['condition'])][$locale]) ? $translate[strtolower($one['condition'])][$locale] : $one['condition'];

			// load pricing
			$pricing = array();
			foreach(explode(',', $one['pricing']) as $idx => $price) {
				list($value, $currency) = explode('|', $price);

				// load currency
				if(!isset($cached2[$currency])) {
					$curr = $currencies->find($currency);
					$cached2[$currency] = $curr->getCode();
				}

				// load from db
				$opt = reset($decimals->fetchList("attribute_id = '{$value}' AND entity_id = '{$one['id']}'"));
				if(!is_null($opt->getValue())) {
					$value = Petolio_Service_Util::formatCurrency($opt->getValue(), $cached2[$currency]);

					// compile prices
					if($opt->getId())
						$pricing[$currency == $one['primary_currency_id'] ? 'primary' : 'secondary_' . $idx] = $value;
				}
			}

			// overwrite pricing
			if(count($pricing) > 0)
				$one['pricing'] = $pricing;
			else unset($one['pricing']);

			// take the first picture
			$picture = !is_null($one['folder_id']) ? $files->fetchList("folder_id = {$one['folder_id']}", "date_created ASC", 1) : array();
			$one['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
		}

		// return formatted data
		return $data;
	}
}