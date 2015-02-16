<?php

/**
 * Object cache-ing class
 *
 * @author Seth
 * @version 0.1
 *
 */
class Petolio_Service_Cache {
	private $cache_dir = null;
	private $db;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->db = new stdClass();
		$this->cache_dir = APPLICATION_PATH."/../data/cache/";
	}

	/**
	 * Set a cache file
	 *
	 * @param string $name
	 * @param array $data
	 * @throws Petolio_Service_Cache_Exception
	 */
	public function __set($name, $data = array()) {
		// attept to create the cache file
		Zend_Registry::get('Zend_Log')->debug($this->cache_dir);
		$fp = @fopen($this->cache_dir . $name . ".php", "wb");
		if(!$fp)
			throw new Petolio_Service_Cache_Exception("Cache file {$name}.php cannot be written.");

		// write and close the file
		$so = fwrite($fp, '<?php'."\n\n".'$'.$name.' = '.var_export($data, true).';'."\n\n".'?>');
		fclose($fp);
	}

	/**
	 * Get the cache file
	 *
	 * @param string $name
	 * @throws Petolio_Service_Cache_Exception
	 * @return array
	 */
	public function __get($name) {
		// see if cache file exists
		if(!$this->__isset($name))
			throw new Petolio_Service_Cache_Exception("Cache file {$name}.php does not exist.");

		// include and return the contents
		include $this->cache_dir . $name . ".php";
		return $$name;
	}

	/**
	 * Check if cache file exists
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name) {
		// see if file exists
		return is_file($this->cache_dir . $name . ".php");
	}

	/**
	 * Delete cache file
	 *
	 * @param string $name
	 * @throws Petolio_Service_Cache_Exception
	 */
	public function __unset($name) {
		// see if cache file exists
		if(!$this->__isset($name))
			throw new Petolio_Service_Cache_Exception("Cache file {$name}.php does not exist.");

		// delete the cache file
		unlink($this->cache_dir . $name . ".php");
	}

	/**
	 * Call our function or die trying
	 *
	 * @param string $func
	 * @param array $arg
	 * @throws Petolio_Service_Cache_Exception
	 * @return mixed
	 */
	public function __call($func, $arg) {
		if(method_exists($this, $func))
			return call_user_func_array(array($this, $func), $arg);
		else
			throw new Petolio_Service_Cache_Exception(sprintf('The required method "%s" does not exist for %s', $func, get_class($this)));
	}

	/**
	 * po_translations function
	 * @return cached data
	 */
	private function PoTranslations($force = false) {
		// name?
		$name = 'po_translations';

		// already in cache? return
		if(isset($this->$name) && $force == false)
			return $this->$name;

		// generate cache
		else {
			// get data
			$data = array();
			$this->db->trans = new Petolio_Model_PoTranslations();
			foreach($this->db->trans->fetchAll() as $one)
				$data[] = $one->toArray();

			// save data
			$this->$name = $data;

			// return data
			return $data;
		}
	}
}

class Petolio_Service_Cache_Exception extends Exception {
}