<?php

/**
 * Main youtube factory class
 *  - should handle Master right now
 *  - must be extendable to allow other scenarios
 */
namespace {
	final class Petolio_Service_YouTube {
		public static function factory($type) {
			$module_class_name = 'YouTube\\' . ucfirst($type);
			return new $module_class_name();
		}
	}
}

/**
 * Petolio YouTube Wrapper
 *
 * @author Seth^^
 * @version 0.1
 *
 * @uses Zend_Gdata client login
 * @uses Zend_Gdata_YouTube
 */
namespace YouTube {
	/**
	 * Base class
	 * should handle login and other basic api functions
	 */
	abstract class Base {
		/**
		 * The http client object saved from login
		 * @var object
		 */
		private $httpClient;

		/**
		 * Login using zend google YouTube client module
		 * @param string $username
		 * @param string $password
		 */
		protected function _login($username, $password) {
			$this->httpClient = \Zend_Gdata_ClientLogin::getHttpClient(
				$username, $password,
				'youtube', null, 'Petolio',
				null, null, 'https://www.google.com/accounts/ClientLogin'
			);
		}

		/**
		 * Load zend google youtube modules
		 * @param string $app - Application id
		 * @param string $key - Developer key
		 * @param string $subroutine - Application subroutine
		 */
		protected function _youtube($app, $key, $subroutine) {
			return new \Zend_Gdata_YouTube($this->httpClient, $app, $subroutine, $key);
		}
	}

	/**
	 * Master class
	 * class that will handle petolio's main youtube account
	 * 	- used for pet videos right now, we're hosting them on our own account
	 *  - implements a cache
	 */
	class Master extends Base {
		/**
		 * Config - it should contain petolio's own youtube account + application and developer key
		 * @var array
		 */
		public $CFG;

		/**
		 * Youtube cache location
		 * @var string
		 */
		private $cache = '../data/cache/youtube.auth';

		/**
		 * Youtube - keeps the Zend_Gdata_YouTube object
		 * @var object
		 */
		protected $youtube;

		/**
		 * Load Zend_Gdata_YouTube from cache
		 *  - if the file doesn't exist, create it
		 */
		private function getCache() {
			// cache doesnt exist ?
			if(!is_file($this->cache))
				return $this->setCache();

			// get the file
			$this->youtube = unserialize(file_get_contents($this->cache));
		}

		/**
		 * Save Zend_Gdata_YouTube to file
		 *  - login, load zend google youtube modules and save them in the cache file
		 */
		private function setCache() {
			// attempt to authenticate
			$this->_login($this->CFG['username'], $this->CFG['password']);

			// set youtube
			$this->youtube = $this->_youtube($this->CFG['app'], $this->CFG['key'], get_class($this));

			// save Zend_Gdata_YouTube
			file_put_contents($this->cache, serialize($this->youtube));
		}

		/**
		 * Get Video Entry Cache
		 *  - assuming all of our videos are saved as files somewhere on the hdd
		 *    we can cache the working entry to decrease load times
		 *
		 * @param string $video - the video id
		 * @param string $dir - the cache directory
		 * @return object (Zend_Gdata_YouTube_VideoEntry) or error string
		 */
		public function getVideoEntryCache($video, $dir) {
			// read cache
			$cache = @file_get_contents($dir . $video . '.yt');
			if(!$cache)
				return 'Petolio Error: Cached file not found';

			// unserialize cached object
			$entry = @unserialize(file_get_contents($dir . $video . '.yt'));
			if(!$entry)
				return $this->setVideoEntryCache($video, $dir);

			// object is an exception? return null
			if($entry instanceof \Exception)
				return strtr(
					str_replace('Expected response code 200, got', 'Error', $entry->getMessage()),
					array("\r\n" => ': ', "\n\r" => ': ', "\r" => ': ', "\n" => ': ')
				);

			// state is not null? something is wrong with it
			if(!is_null($entry->getVideoState()))
				return $this->setVideoEntryCache($video, $dir);

			// everything is fine and dandy, return our entry
			return $entry;
		}

		/**
		 * Set Video Entry Cache
		 * @param string $video - the video id
		 * @param string $dir - the cache directory
		 * @param bool $save - to save cache with error or not
		 * @return object (Zend_Gdata_YouTube_VideoEntry) or error string
		 */
		public function setVideoEntryCache($video, $dir, $save = true) {
			// get the video entry from youtube
			try {
				$entry = $this->getVideoEntry($video);
			} catch (\Exception $e) {
				// write exception
				if($save === true)
					file_put_contents($dir . $video . '.yt', serialize($e));

				// return error
				return strtr(
					str_replace('Expected response code 200, got', 'Error', $e->getMessage()),
					array("\r\n" => ': ', "\n\r" => ': ', "\r" => ': ', "\n" => ': ')
				);
			}

			// return state
			if(!is_null($entry->getVideoState())) {
				$state = $entry->getVideoState();
				return ucfirst($state->getName()) .": {$state->getText()}";
			}

			// write entry and return it
			file_put_contents($dir . $video . '.yt', serialize($entry));
			return $entry;
		}

		/**
		 * Magical __call
		 *  - everything gets passed through to Zend_Gdata_YouTube
		 *
		 * @param string $func
		 * @param array $arg
		 */
		public function __call($func, $arg) {
			// not set ? load from cache
			if(is_null($this->youtube))
				$this->getCache();

			// try and call with the cached object
			try {
				// send call to Zend_Gdata_YouTube
				return call_user_func_array(array($this->youtube, $func), $arg);

			// catch http exception
			} catch (\Zend_Gdata_App_HttpException $httpException) {
				// refresh token
				$this->setCache();

				// log error
				\Zend_Registry::get('Zend_Log')->err("Zend_Gdata_App_HttpException -> Message: {$httpException->getRawResponseBody()}");

				// send call to Zend_Gdata_YouTube
				return call_user_func_array(array($this->youtube, $func), $arg);

			// catch app exception
			} catch (\Zend_Gdata_App_Exception $e) {
				// refresh token
				$this->setCache();

				// log error
				\Zend_Registry::get('Zend_Log')->err("Zend_Gdata_App_Exception -> Message: {$e->getMessage()}");

				// send call to Zend_Gdata_YouTube
				return call_user_func_array(array($this->youtube, $func), $arg);
			}
		}
	}
}