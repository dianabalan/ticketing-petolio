<?php

/**
 * Controller for Market Place section.
 *
 * @author zsolt
 */
class MarketplaceController extends Zend_Controller_Action
{
	private $europe = array(48.690832999999998, 9.140554999999949);

	private $request = null;
	private $translate = null;
	private $msg = null;
	private $config = null;
	private $auth = null;
	private $db = null;

	private $keyword = false;

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

	public function init() {
		// init
		$this->request = $this->getRequest();
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->config = Zend_Registry::get("config");
		$this->auth = Zend_Auth::getInstance();

		// db
		$this->db = new stdClass();
		$this->db->services = new Petolio_Model_PoServices();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();

		// send request to view
		$this->view->request = $this->request;
	}

	/**
	 * Get and set the service types
	 */
	private function buildTypes() {
		// filter by type ?
	    $groups = array();
	    $service_types = array ();
	    $current_group_name = '';
		foreach($this->db->sets->getAttributeSets('po_services') as $line) {
			if(isset($line['group_name']) && strlen($line['group_name']) > 0) {
        		if(strcasecmp($line['group_name'], $current_group_name) != 0) {
        			$groups[$line['group_name']] = array (
	        			'id' => $line['group_name'],
	        			'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['group_name'])),
	        			'indent' => 0
	        		);
        		}
        	}

        	$current_group_name = $line['group_name'];
       		$service_types[] = array (
       			'id' => $line['id'],
       			'group_name' => $line['group_name'],
       			'name' => Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name'])),
       			'indent' => 1
       		);
		}

        // we want to sort the values if they are translated too so that's why we sort it here
        $name_arr = array();
       	foreach($groups as $key => $row)
		    $name_arr[$key] = $row['name'];
		array_multisort($name_arr, SORT_ASC, SORT_STRING, $groups);

		$name_arr = array();
       	foreach($service_types as $key => $row)
		    $name_arr[$key] = $row['name'];
		array_multisort($name_arr, SORT_ASC, SORT_STRING, $service_types);

        $types = array();
		foreach($service_types as $value)
			if(!isset($value['group_name']) || strlen($value['group_name']) <= 0)
				$types[$value['id']] = $value;

        foreach($groups as $key => $row) {
			$types[$row['id']] = $row;
			foreach($service_types as $value)
				if(strcasecmp($value['group_name'], $row['id']) == 0 )
					$types[$value['id']] = $value;
		}

		// output the types
		$this->view->types = $types;
	}

    /*
	 * Build pet search filter
	 */
	private function buildSearchFilter($filter = array()) {
		$search = array();

		// vars
		$keyword = (string)$this->request->getParam('keyword');
		$owner = (string)$this->request->getParam('owner');
		$owner_id = $this->request->getParam('owner_id');
		$type = (int)$this->request->getParam('type');
		
		$address = $this->request->getParam('address');
		$nearme = $this->request->getParam('nearme', 0);
		$rad = $this->request->getParam('radius');
		$lat = $this->request->getParam('user_latitude', '');
		$lng = $this->request->getParam('user_longitude', '');
		
		$difference = (float)($rad != 0 ? number_format(($rad / 111), 2) : 0.07);
		
		// build filter
		if((isset($lat) && strlen($lat) > 0) && (isset($lng) && strlen($lng) > 0)) {
			// http://en.wikipedia.org/wiki/Pythagorean_theorem
			$filter[] = "POW(a.gps_latitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lat)).", 2) + " .
					"POW(a.gps_longitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lng)).", 2) <= " .
					"POW(".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($difference).", 2)";
		} else {
			if(isset($lat) && strlen($lat) > 0) {
				$latitude_from = floatval($lat) - $difference;
				$latitude_to = floatval($lat) + $difference;
		
				$filter[] = "a.gps_latitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_from);
				$filter[] = "a.gps_latitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_to);
			}
			if(isset($lng) && strlen($lng) > 0) {
				$longitude_from = floatval($lng) - $difference;
				$longitude_to = floatval($lng) + $difference;
		
				$filter[] = "a.gps_longitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_from);
				$filter[] = "a.gps_longitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_to);
			}
		}
		if($nearme == 1) {
			$search[] = $this->translate->_("near me");
		}
		if(strlen($address) > 0) {
			$search[] = $address;
		}
		
		// keyword
		if(strlen($keyword)) {
			$filter[] = "(d1.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%")." " .
						"OR d2.value LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($keyword)."%").")";
			$search[] = $keyword;

			// set keyword search
			$this->keyword = true;
		}

			// owner
		if(strlen($owner) > 0) {
			$filter[] = "u.name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($owner)."%");
			$search[] = $owner;
		}

		// owner id
		if(strlen($owner_id) > 0) {
			$filter[] = "u.id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($owner_id, Zend_Db::BIGINT_TYPE);
			$users = new Petolio_Model_PoUsers();
			$user = $users->find($owner_id);
			if ($user) {
				$search[] = $user->getName();
			} else {
				$search[] = $owner_id;
			}
		}

		// type
		if($type != 0) {
			$filter[] = "a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($type, Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->types[$type]['name'];
		}

		// set filter
		if(count($search) > 0)
			$this->view->filter = implode(', ', $search);

		// return string
		return implode(' AND ', $filter);
	}

	/**
	 * List Services
	 */
	public function indexAction() {
		// see if list or grid
		$this->view->list = $this->request->getParam('list') ? 'list' : 'grid';

		// types
		$this->buildTypes();

		// get filter
		$filter = $this->buildSearchFilter(array("a.deleted = 0"));

		// search by ?
		if($this->view->filter) $this->view->title = $this->translate->_("Results, Search:"). " " . $this->view->filter;
		else $this->view->title = $this->translate->_("All Services");

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		/*
		 * petolio session seed for random sorting
		 * basically the random sort is saved for the pagination to work properly
		 */
		if ( !isset($_SESSION["petolio_seed"]) ) {
			$uniq_id = uniqid();
			$_SESSION["petolio_seed"] = substr($uniq_id, strlen($uniq_id) - 5);
		}

    	// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if ($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif ($this->view->order == 'type') $sort = "type {$this->view->dir}";
		elseif ($this->view->order == 'owner') $sort = "user_name {$this->view->dir}";
		else {
			$this->view->order = 'picture';
			$sort = "has_picture DESC";
		}

		// get products
		$paginator = $this->db->services->getServices('paginator', $filter, $sort . ", petolio_service ASC, RAND(\"".$_SESSION["petolio_seed"]."\")", false, $this->keyword);
		$paginator->setItemCountPerPage($this->config["services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output services
		$this->view->services = $this->db->services->formatServices($paginator);
	}

	/**
	 * Map action
	 */
	public function mapAction() {
		// init map
		$this->view->coords = $this->europe;
	}

	/**
	 * Find services near gps coordinate
	 */
	private function findServicesNearLocation($filter = array(), $lat, $lng, $rad) {
		// set radius
		$difference = (float)($rad != 0 ? number_format(($rad / 111), 2) : 0.07);

		// build filter
		if((isset($lat) && strlen($lat) > 0) && (isset($lng) && strlen($lng) > 0)) {
			// http://en.wikipedia.org/wiki/Pythagorean_theorem
			$filter[] = "POW(a.gps_latitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lat)).", 2) + " .
				"POW(a.gps_longitude - ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(floatval($lng)).", 2) <= " .
				"POW(".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($difference).", 2)";
		} else {
			if(isset($lat) && strlen($lat) > 0) {
				$latitude_from = floatval($lat) - $difference;
				$latitude_to = floatval($lat) + $difference;

				$filter[] = "a.gps_latitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_from);
				$filter[] = "a.gps_latitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($latitude_to);
			}
			if(isset($lng) && strlen($lng) > 0) {
				$longitude_from = floatval($lng) - $difference;
				$longitude_to = floatval($lng) + $difference;

				$filter[] = "a.gps_longitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_from);
				$filter[] = "a.gps_longitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($longitude_to);
			}
		}
		
		// return services
		return $this->db->services->formatServices($this->db->services->getServices('array', implode(' AND ', $filter), 'rand()', 100, false));
	}

	/**
	 * Find services between 2 gps points
	 */
	private function findServicesBetweenPoint($filter = array(), $lat_from, $lat_to, $lng_from, $lng_to) {
		// build filter
		if(isset($lat_to) && strlen($lat_to) > 0 && isset($lat_from) && strlen($lat_from) > 0) {
			$filter[] = "a.gps_latitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lat_from);
			$filter[] = "a.gps_latitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lat_to);
		}
		if(isset($lng_to) && strlen($lng_to) > 0 && isset($lng_from) && strlen($lng_from) > 0) {
			$filter[] = "a.gps_longitude >= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lng_from);
			$filter[] = "a.gps_longitude <= ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($lng_to);
		}

		// return services
		return $this->db->services->formatServices($this->db->services->getServices('array', implode(' AND ', $filter), 'rand()', 100, false));
	}

	/**
	 * Find services randomly
	 */
	private function findUserServicesRandomly($filter = array()) {
		// build filter
		$filter[] = "a.gps_latitude IS NOT NULL";
		$filter[] = "a.gps_longitude IS NOT NULL";
		
		$filters = implode(' AND ', $filter);
		$sort = "RAND(".date("Ymd").")";
		
		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Marketplace_User_Services_".$filters."_".$sort);
		
		if (false === ($services = $cache->load($cacheID))) {
			$services = $this->db->services->formatServices($this->db->services->getServices('array', $filters, $sort, 100, false));
			$cache->save($services, $cacheID);
		}

		// return services
		return $services;
	}

	/*
	 * Google map markers ajax call
	 */
	public function getmarketsAction() {
		// marker params
		$filters = $this->request->getParam("filters");
		$lat = $this->request->getParam("latitude");
		$lng = $this->request->getParam("longitude");
		$lat_to = $this->request->getParam("latitude_to");
		$lat_from = $this->request->getParam("latitude_from");
		$lng_to = $this->request->getParam("longitude_to");
		$lng_from = $this->request->getParam("longitude_from");
		$radius = $this->request->getParam("radius");
		$user_id = $this->request->getParam("user", 0);
		$service_id = $this->request->getParam("service", 0);

		// decode filters
		$filters = unserialize(base64_decode($filters));
		
		if ($user_id > 0) {
			$filters[] = "a.user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($user_id);
		}
		if ($service_id > 0) {
			$filters[] = "a.id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($service_id);
		}
		
		// load based on user action
		if(isset($lat) && (isset($lng)))
			$services = $this->findServicesNearLocation($filters, $lat, $lng, $radius);
		elseif(isset($lat_to) && isset($lat_from) && isset($lng_from) && isset($lng_to))
			$services = $this->findServicesBetweenPoint($filters, $lat_from, $lat_to, $lng_from, $lng_to);
		else
			$services = $this->findUserServicesRandomly($filters);

		// format for google map
		$markets = array();
		foreach($services as $service) {
			$address =  $service["service_zipcode"].' '.$service["service_location"];
			if(strlen($service["service_zipcode"]) > 0 || strlen($service["service_location"]) > 0)
				$address .= ', ';
			$address .= $service["service_country"];

			$markets[] = array(
				"id"		=> $service['id'],
				"name"		=> $service['name'],
				"type"		=> Petolio_Service_Util::Tr($service['type']),
				"latitude"	=> $service['gps_latitude'],
				"longitude"	=> $service['gps_longitude'],
				"username" 	=> $service['user_name'],
				"userid"	=> $service['user_id'],
				"address"	=> $address,
				"view"		=> $this->translate->_("View service")
			);
		}

		// output json
		Petolio_Service_Util::json(array(
			'success' => true,
			'count' => sizeof($markets),
			'items' => $markets
		));
	}

	/**
	 * gets the logged in user's address to search services near by
	 */
	public function getUserAddressAction() {
		$result = array(
			'latitude' => '',
			'longitude' => '',
			'address' => ''
		);

		if($this->auth->hasIdentity()) {
			$user = new Petolio_Model_PoUsers();
			$user->findWithReferences($this->auth->getIdentity()->id);

			$result = array(
				'latitude' => $user->getGpsLatitude(),
				'longitude' => $user->getGpsLongitude(),
				'address' => trim($user->getZipcode().' '.$user->getAddress().' '.$user->getStreet().' '.$user->getLocation().' '.$user->getCountryName())
			);
		}

		return Petolio_Service_Util::json($result);
	}
}