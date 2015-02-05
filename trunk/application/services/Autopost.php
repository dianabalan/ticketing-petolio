<?php

/**
 * Main autopost factory class
 *  - should handle Image and Video right now
 *  - must be extendable to allow other scenarios
 */
namespace {
	final class Petolio_Service_Autopost {
		public static function factory() {
			$module_class_name = 'Autopost\\' . ucfirst(func_get_arg(0));
			return new $module_class_name(func_get_args());
		}
	}
}

/**
 * Petolio Autopost
 *
 * @author Seth^^
 * @version 0.3
 */
namespace Autopost {
	/**
	 * Base class
	 * should contain all of the repetitive functions needed in each module
	 */
	abstract class Base {
		/**
		 * This var will hold our DB models
		 * @var stdClass
		 */
		protected $db = null;

		/**
		 * The identity of the logged user
		 * @var Zend_Auth type object
		 */
		protected $identity = null;

		/**
		 * The arguments sent when autopost was called are stored here
		 * @var array
		 */
		protected $args = array();

		/**
		 * The zend view object, used for renders
		 * @var object
		 */
		protected $view = null;

		/**
		 * Cached youtube object
		 * @var object
		 */
		protected $youtube = null;

		/**
		 * Constructor
		 * Loads all the variables defined in the class and calls the module init function
		 * @return void
		 */
		public function __construct() {
			// handle identity
			$this->identity = \Zend_Auth::getInstance()->getIdentity();

			// handle view
			$this->getView();

			// handle arguments
			$args = func_get_arg(0);
			$this->args = $args;

			// load all models in db
			$this->db = new \stdClass();
			$this->db->dash = new \Petolio_Model_PoDashboard();
			$this->db->rights = new \Petolio_Model_PoDashboardRights();
			$this->db->files = new \Petolio_Model_PoFiles();
			$this->db->comments = new \Petolio_Model_PoComments();
			$this->db->ratings = new \Petolio_Model_PoRatings();
			$this->db->subscriptions = new \Petolio_Model_PoSubscriptions();

			// call the module init
			$this->init();
		}

		/**
		 * Get file values
		 * we need the whole record (mysql defaults), not just what was set in php
		 * @return void
		 */
		public function getFile() {
			// what was sent is not an object? SKIP
			if(!is_object($this->args[1]))
				return true;

			// rewrite args 1 with the full values found while doing a query
			$this->args[1] = $this->db->files->find($this->args[1]->getId());
		}

		/**
		 * Absolute entry deletion
		 * @param object $entry The entry object
		 * @return bool true
		 */
		public function deleteEntry($entry) {
			// check if we have the right object
			if(!$entry instanceof \Petolio_Model_PoDashboard)
				return;

			// delete all comments and likes and subscribers and permissions
			$this->db->comments->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$entry->getId()}'");
			$this->db->ratings->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$entry->getId()}'");
			$this->db->subscriptions->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$entry->getId()}'");
			$this->db->rights->getMapper()->getDbTable()->delete("dashboard_id = '{$entry->getId()}'");

			// delete the whole entry then
			$entry->deleteRowByPrimaryKey();

			// done
			return true;
		}

		/**
		 * Get Youtube
		 * Initialize the youtube class
		 * @return Youtube\Master object
		 */
		public function getYoutube() {
			// already loaded?
			if(!is_null($this->youtube))
				return $this->youtube;

			// get config
			$config = \Zend_Registry::get("config");

	    	// call our youtube wrapper
			$this->youtube = \Petolio_Service_YouTube::factory('Master');
			$this->youtube->CFG = array(
				'username' => $config["youtube"]["username"],
				'password' => $config["youtube"]["password"],
				'app' => $config["youtube"]["app"],
				'key' => $config["youtube"]["key"],
			);

			// return the youtube object
			return $this->youtube;
		}

		/**
		 * Get View
		 * We need the zend view for render
		 * @return Zend_View object
		 */
		private function getView() {
			$render = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

			// view not initialized?
			if (null === $render->view)
				$render->initView();

			// set the view
			$this->view = $render->view;
		}
	}

	/**
	 * Module Interface
	 * All modules need to have whatever we have defined here
	 */
	interface Module {
		public function init();
	}

	/**
	 * Main text Class
	 * Must handle everything related to text (status update).
	 */
	final class Text extends Base implements Module {
		/**
		 * Module init
		 * This will get called after the base __constructor
		 * @return void
		 */
		public function init() {
			// set limit to 10 minutes
			$limit = new \DateTime('now');
			$limit->sub(new \DateInterval('PT10M'));

			// does a similar entry already exist?
			$results = reset($this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
				AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
				AND UNIX_TIMESTAMP(date_created) > {$limit->format('U')}
			"));

			// based on arg 1, is it add or del
			if($this->args[1] == 'add') {
				// based on result update the entry or insert a new entry
				if($results) $this->update($results);
				else $this->insert();
			} else $this->remove();
		}

		/**
		 * Insert a new text entry
		 * @return void
		 */
		private function insert() {
			// insert entry
			$this->db->dash->setOptions(array(
				'user_id' => $this->identity->id,
				'data' => $this->view->render('autopost/txt.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
				)),
				'identity' => "{$this->args[0]}|{$this->args[2]}|{$this->args[3]}",
				'scope' => 'po_dashboard',
				'entity_id' => new \Zend_Db_Expr('NULL')
			))->save();

			// save owner as subscriber
			$this->db->subscriptions->setOptions(array(
				'user_id' => $this->identity->id,
				'scope' => 'po_dashboard',
				'entity_id' => $this->db->dash->getId()
			))->save();
		}

		/**
		 * Update the text entry
		 * @return void
		 */
		private function update($entry) {
			// update entry
			$entry->setOptions(array(
				'date_created' => date('Y-m-d H:i:s'),
				'serialized' => serialize(array(
					'args' => $this->args,
				))
			))->save();
		}

		/**
		 * Removes an existing text entry
		 * @return void
		 */
		private function remove() {
			// find the entry first based on user and identity
			$result = reset($this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
				AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
			"));

			// nothing found? return here
			if(!$result)
				return false;

			// absolute delete
			$this->deleteEntry($result);
		}
	}

	/**
	 * Main image Class
	 * Must handle everything related to images from files.
	 */
	final class Image extends Base implements Module {
		/**
		 * Module init
		 * This will get called after the base __constructor
		 * @return void
		 */
		public function init() {
			// set limit to 10 minutes
			$limit = new \DateTime('now');
			$limit->sub(new \DateInterval('PT10M'));

			// add
			if(is_object($this->args[1])) {
				// does a similar entry already exist?
				$results = reset($this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
					AND UNIX_TIMESTAMP(date_created) > {$limit->format('U')}
				", 'id DESC'));

				// based on result update the entry or insert a new entry
				if($results) $this->update($results);
				else $this->insert();

			// remove
			} else {
				// does a similar entry already exist?
				$results = $this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
				", 'id ASC');

				// update each matching entry
				foreach($results as $idx => $result)
					$this->update($result, isset($results[$idx + 1]) ? $results[$idx + 1]->getDateCreated() : date('Y-m-d H:i:s'));
			}
		}

		/**
		 * Insert a new single image entry
		 * @param object $overwrite - We will insert this object when not false
		 * @param object $entry - Entry to update actually (so we don't repeat the single entry code in update as well)
		 * @return void
		 */
		private function insert($overwrite = false, $entry = false) {
			// make sure we have all the values
			$this->getFile();

			// overwrite arg for update
			$file = $overwrite ? $overwrite : $this->args[1];

			// file is not an object? abort.
			if(!is_object($file))
				return true;

			// set the view variables
			$this->view->results = $file;
			$this->view->args = $this->args;

			// insert entry
			$dash = $entry ? $entry : new \Petolio_Model_PoDashboard();
			$dash->setOptions(array(
				'user_id' => $this->identity->id,
				'date_created' => $entry ? $entry->getDateCreated() : $file->getDateCreated(),
				'data' => $this->view->render('autopost/img.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $this->view->results,
					'total' => 1
				)),
				'identity' => "{$this->args[0]}|{$this->args[2]}|{$this->args[3]}",
				'scope' => 'po_files',
				'entity_id' => $file->getId()
			))->save();

			// save owner as subscriber
			if($entry == false) {
				$this->db->subscriptions->setOptions(array(
					'user_id' => $this->identity->id,
					'scope' => 'po_dashboard',
					'entity_id' => $dash->getId()
				))->save();
			}
		}

		/**
		 * Update the entry by either adding multiple images,
		 * updating on delete or simply deleting the entry alltogether
		 * @param object $entry - The entry that needs to be updated
		 * @param string $end - Look until?
		 * @return void
		 */
		private function update($entry, $end = false) {
			// get folder (usually we run this update on delete and there we give it the folder id directly
			$folder = is_object($this->args[1]) ? $this->args[1]->getFolderId() : $this->args[1];

			// find the new files
			$results = $this->db->files->getMapper()->fetchList("folder_id = '{$folder}'
				AND UNIX_TIMESTAMP(date_created) >= UNIX_TIMESTAMP('{$entry->getDateCreated()}')
				" . ($end != false ? "AND UNIX_TIMESTAMP(date_created) < UNIX_TIMESTAMP('{$end}')" : "") . "
			", 'id ASC');

			// did we somehow delete all of the images we uploaded?
			if(count($results) == 0)
				// delete the whole entry then
				return $this->deleteEntry($entry);

			// only 1 picture remaining? we dont need update
			if(count($results) == 1) {
				// redirect to insert (which will do an update actually)
				return $this->insert(reset($results), $entry);
			}

			// limit the results to 5
			$limited = array();
			foreach($results as $idx => $one)
				if($idx < 5)
					$limited[] = $one;

			// set the view variables
			$this->view->results = $limited;
			$this->view->args = $this->args;

			// update entry
			$entry->setOptions(array(
				'data' => $this->view->render('autopost/img+.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $limited,
					'total' => count($results)
				)),
				'scope' => 'po_dashboard',
				'entity_id' => new \Zend_Db_Expr('NULL')
			))->save();
		}
	}

	/**
	 * Main audio Class
	 * Must handle everything related to audios from files.
	 */
	final class Audio extends Base implements Module {
		/**
		 * Module init
		 * This will get called after the base __constructor
		 * @return void
		 */
		public function init() {
			// set limit to 10 minutes
			$limit = new \DateTime('now');
			$limit->sub(new \DateInterval('PT10M'));

			// add
			if(is_object($this->args[1])) {
				// does a similar entry already exist?
				$results = reset($this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
					AND UNIX_TIMESTAMP(date_created) > {$limit->format('U')}
				", 'id DESC'));

				// based on result update the entry or insert a new entry
				if($results) $this->update($results);
				else $this->insert();

			// remove
			} else {
				// does a similar entry already exist?
				$results = $this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
				", 'id ASC');

				// update each matching entry
				foreach($results as $idx => $result)
					$this->update($result, isset($results[$idx + 1]) ? $results[$idx + 1]->getDateCreated() : date('Y-m-d H:i:s'));
			}
		}

		/**
		 * Insert a new single audio entry
		 * @param object $overwrite - We will insert this object when not false
		 * @param object $entry - Entry to update actually (so we don't repeat the single entry code in update as well)
		 * @return void
		 */
		private function insert($overwrite = false, $entry = false) {
			// make sure we have all the values
			$this->getFile();

			// overwrite arg for update
			$file = $overwrite ? $overwrite : $this->args[1];

			// file is not an object? abort.
			if(!is_object($file))
				return true;

			// set the view variables
			$this->view->results = $file;
			$this->view->args = $this->args;

			// insert entry
			$dash = $entry ? $entry : new \Petolio_Model_PoDashboard();
			$dash->setOptions(array(
				'user_id' => $this->identity->id,
				'date_created' => $entry ? $entry->getDateCreated() : $file->getDateCreated(),
				'data' => $this->view->render('autopost/aud.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $this->view->results,
					'total' => 1
				)),
				'identity' => "{$this->args[0]}|{$this->args[2]}|{$this->args[3]}",
				'scope' => 'po_files',
				'entity_id' => $file->getId()
			))->save();

			// save owner as subscriber
			if($entry == false) {
				$this->db->subscriptions->setOptions(array(
					'user_id' => $this->identity->id,
					'scope' => 'po_dashboard',
					'entity_id' => $dash->getId()
				))->save();
			}
		}

		/**
		 * Update the entry by either adding multiple audios,
		 * updating on delete or simply deleting the entry alltogether
		 * @param object $entry - The entry that needs to be updated
		 * @param string $end - Look until?
		 * @return void
		 */
		private function update($entry, $end = false) {
			// get folder (usually we run this update on delete and there we give it the folder id directly
			$folder = is_object($this->args[1]) ? $this->args[1]->getFolderId() : $this->args[1];

			// find the new files
			$results = $this->db->files->getMapper()->fetchList("folder_id = '{$folder}'
				AND UNIX_TIMESTAMP(date_created) >= UNIX_TIMESTAMP('{$entry->getDateCreated()}')
				" . ($end != false ? "AND UNIX_TIMESTAMP(date_created) < UNIX_TIMESTAMP('{$end}')" : "") . "
			", 'id ASC');

			// did we somehow delete all of the images we uploaded?
			if(count($results) == 0)
				// delete the whole entry then
				return $this->deleteEntry($entry);

			// only 1 picture remaining? we dont need update
			if(count($results) == 1) {
				// redirect to insert (which will do an update actually)
				return $this->insert(reset($results), $entry);
			}

			// limit the results to 5
			$limited = array();
			foreach($results as $idx => $one)
				if($idx < 5)
					$limited[] = $one;

			// set the view variables
			$this->view->results = $limited;
			$this->view->args = $this->args;

			// update entry
			$entry->setOptions(array(
				'data' => $this->view->render('autopost/aud+.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $limited,
					'total' => count($results)
				)),
				'scope' => 'po_dashboard',
				'entity_id' => new \Zend_Db_Expr('NULL')
			))->save();
		}
	}

	/**
	 * Main video Class
	 * Must handle everything related to videos from files.
	 */
	final class Video extends Base implements Module {
		/**
		 * Module init
		 * This will get called after the base __constructor
		 * @return void
		 */
		public function init() {
			// set limit to 10 minutes
			$limit = new \DateTime('now');
			$limit->sub(new \DateInterval('PT10M'));

			// add
			if(is_object($this->args[1])) {
				// does a similar entry already exist?
				$results = reset($this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
					AND UNIX_TIMESTAMP(date_created) > {$limit->format('U')}
				", 'id DESC'));

				// based on result update the entry or insert a new entry
				if($results) $this->update($results);
				else $this->insert();

			// remove
			} else {
				// does a similar entry already exist?
				$results = $this->db->dash->getMapper()->fetchList("user_id = '{$this->identity->id}'
					AND identity = '{$this->args[0]}|{$this->args[2]}|{$this->args[3]}'
				", 'id ASC');

				// update each matching entry
				foreach($results as $idx => $result)
					$this->update($result, isset($results[$idx + 1]) ? $results[$idx + 1]->getDateCreated() : date('Y-m-d H:i:s'));
			}
		}

		/**
		 * Insert a new single video entry
		 * @param object $overwrite - We will insert this object when not false
		 * @param object $entry - Entry to update actually (so we don't repeat the single entry code in update as well)
		 * @return void
		 */
		private function insert($overwrite = false, $entry = false) {
			// make sure we have all the values
			$this->getFile();

			// overwrite arg for update
			$file = $overwrite ? $overwrite : $this->args[1];

			// file is not an object? abort.
			if(!is_object($file))
				return true;

			// get video youtube entity for thumbnail and duration
			try {
				$file->setMapper($this->getYoutube()->getVideoEntry(pathinfo($file->getFile(), PATHINFO_FILENAME)));
			} catch (\Exception $e) {
				return false;
			}

			// set the view variables
			$this->view->results = $file;
			$this->view->args = $this->args;

			// insert entry
			$dash = $entry ? $entry : new \Petolio_Model_PoDashboard();
			$dash->setOptions(array(
				'user_id' => $this->identity->id,
				'date_created' => $entry ? $entry->getDateCreated() : $file->getDateCreated(),
				'data' => $this->view->render('autopost/vid.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $this->view->results,
					'total' => 1
				)),
				'identity' => "{$this->args[0]}|{$this->args[2]}|{$this->args[3]}",
				'scope' => 'po_files',
				'entity_id' => $file->getId()
			))->save();

			// save owner as subscriber
			if($entry == false) {
				$this->db->subscriptions->setOptions(array(
					'user_id' => $this->identity->id,
					'scope' => 'po_dashboard',
					'entity_id' => $dash->getId()
				))->save();
			}
		}

		/**
		 * Update the entry by either adding multiple videos,
		 * updating on delete or simply deleting the entry alltogether
		 * @param object $entry - The entry that needs to be updated
		 * @param string $end - Look until?
		 * @return void
		 */
		private function update($entry, $end = false) {
			// get folder (usually we run this update on delete and there we give it the folder id directly
			$folder = is_object($this->args[1]) ? $this->args[1]->getFolderId() : $this->args[1];

			// find the new files
			$results = $this->db->files->getMapper()->fetchList("folder_id = '{$folder}'
				AND UNIX_TIMESTAMP(date_created) >= UNIX_TIMESTAMP('{$entry->getDateCreated()}')
				" . ($end != false ? "AND UNIX_TIMESTAMP(date_created) < UNIX_TIMESTAMP('{$end}')" : "") . "
			", 'id ASC');

			// did we somehow delete all of the videos we uploaded / linked?
			if(count($results) == 0)
				// delete the whole entry then
				return $this->deleteEntry($entry);

			// only 1 video remaining? we dont need update
			if(count($results) == 1) {
				// redirect to insert (which will do an update actually)
				return $this->insert(reset($results), $entry);
			}

			// limit the results to 5
			$limited = array();
			foreach($results as $idx => $one)
				if($idx < 5)
					$limited[] = $one;

			// get video youtube entities for thumbnail and duration
			try {
				foreach($limited as $one)
					// set the entities in mapper for easy access in the view *trollface*
					$one->setMapper($this->getYoutube()->getVideoEntry(pathinfo($one->getFile(), PATHINFO_FILENAME)));
			} catch (\Exception $e) {
				return false;
			}

			// set the view variables
			$this->view->results = $limited;
			$this->view->args = $this->args;

			// update entry
			$entry->setOptions(array(
				'data' => $this->view->render('autopost/vid+.phtml'),
				'serialized' => serialize(array(
					'args' => $this->args,
					'results' => $this->view->results,
					'total' => count($results)
				)),
				'scope' => 'po_dashboard',
				'entity_id' => new \Zend_Db_Expr('NULL')
			))->save();
		}
	}
}