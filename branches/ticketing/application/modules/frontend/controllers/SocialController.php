<?php

/**
 * Social Controller
 * Ajax CRUD API implementation for social interactions
 * 	- must control all aspects of social interaction
 *  - entities are differentiated by scope
 *
 *  Plugins:
 *  - comments (in the form of user, avatar, comment, date)
 *  - rating (in the form of likes)
 *  - recommend
 *  - subscribe
 *
 * @author Seth
 * @version 0.7
 */
namespace {
	class SocialController extends Zend_Controller_Action {
		// essentially store stuff to send for the plugin
		protected $_params = array();

		// controller init
		public function init() {
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
		}

	    // unused.
	    public function indexAction() {}

	    /**
	     * Validate inputs
	     * Must have at least the plugin, scope and id
	     */
	    private function validate($reqLoggedIn = true) {
	    	// grab parameters
	    	$id = @(int)$_REQUEST["id"];
	    	$scope = @(string)$_REQUEST["scope"];
	    	$plugin = @(string)$_REQUEST["plugin"];

	    	// load translation object
	    	$translate = Zend_Registry::get('Zend_Translate');

	    	// die with error if not set
	    	if(empty($id) || empty($scope) || empty($plugin)) {
	    		Petolio_Service_Util::json(array('success' => false, 'msg' => $translate->_("Cannot validate social plugin, required params missing.")));
	    		return false;
	    	}

	    	$auth = Zend_Auth::getInstance();
	    	if (!$auth->hasIdentity() && $reqLoggedIn == true) {
	    		Petolio_Service_Util::json(array('success' => false, 'msg' => $translate->_("You are not logged in.")));
	    		return false;
	    	}

	    	// set plugin parameters
	    	$this->_params = array($plugin, $this->getRequest()->getActionName(), $auth, $translate, $id, $scope);

	    	// I need this for advertising
	    	$this->view->id = $id;
	    	$this->view->scope = $scope;

	    	// yay trains!
	    	return $this;
	    }

	    /**
	     * Execute the social plugin
	     */
	    private function exec() {
	    	// init social factory
	    	return Social\Core::factory($this->_params);
	    }

		/**
		 * Create action
		 */
	    public function createAction() {
	    	// validate & run & output as json
	    	$validate = $this->validate();
	    	if ($validate) 
		    	Petolio_Service_Util::json($validate->exec());
	    }

		/**
		 * Read action
		 */
	    public function readAction() {
	    	// validate & run
	    	$validate = $this->validate(false);
	    	if ($validate) { 
		    	$return = $validate->exec();
		    	$this->view->plugin = $return['view'];
	
		    	// add render to json
		    	$return['html'] = $this->view->render('social/read.phtml');
	
		    	// output as json
		    	Petolio_Service_Util::json($return);
	    	}
	    }

		/**
		 * Update action
		 */
	    public function updateAction() {
	    	// validate & run & output as json
	    	$validate = $this->validate();
	    	if ($validate) 
	    		Petolio_Service_Util::json($validate->exec());
	    }

		/**
		 * Delete action
		 */
	    public function deleteAction() {
	    	// validate & run & output as json
	    	$validate = $this->validate();
	    	if ($validate) 
	    		Petolio_Service_Util::json($validate->exec());
	    }

	    /**
	     * Additional action
	     */
	    public function miscAction() {
	    	$validate = $this->validate(false);
	    	if ($validate) { 
		    	$return = $validate->exec();
	
		    	// in case the misc action must implement render
		    	if(isset($return['view'])) {
		    		$this->view->plugin = $return['view'];
		    		$return['html'] = $this->view->render("social/misc_{$this->view->plugin->plugin_name}.phtml");
		    	}
	
		    	// validate & run & output as json
		    	Petolio_Service_Util::json($return);
	    	}
	    }
	}
}

/**
 * Petolio Social Wrapper
 *
 * @author Seth^^
 * @version 0.1
 */
namespace Social {
	/**
	 * Core class implements the factory pattern
	 *  - loads social plugins
	 *
	 * @author Seth
	 * @version 0.1
	 */
	final class Core {
		public static function factory($params) {
			$plugin_name = 'Social\\' . ucfirst($params[0]);
			if(class_exists($plugin_name)) {
				$plugin = new $plugin_name($params);
				return $plugin->{$params[1]}();
			} else
				throw new \Exception("Social plugin '{$params[0]}' does not exist!");
		}
	}

	/**
	 * Plugin interface
	 *  - every plugin must support CRUD
	 */
	interface Plugin_Interface {
		public function create();
		public function read();
		public function update();
		public function delete();
	}

	/**
	 * All plugins extend this class
	 *  - all common plugin entities should be loaded here
	 *
	 * @author Seth
	 * @version 0.2
	 */
	abstract class Plugin {
		// main params
		protected $plugin;
		protected $action;
		protected $auth;
		protected $translate;
		protected $id;
		protected $scope;

		// db and view objects
		protected $db;
		protected $view;
		protected $url;

		// handle errors
		protected $error = false;

		/**
		 * Constructor
		 * @param array $params
		 */
		public function __construct($params) {
			// unpack param to each protected variable
			list($this->plugin, $this->action, $this->auth, $this->translate, $this->id, $this->scope) = $params;

			// initiate the db and run init on plugin
			$this->db = new \stdClass();
			$this->view = new \stdClass();
			$this->view->plugin_name = $this->plugin;
			$this->url = new \Zend_View_Helper_Url;

			// add plugin init
			$this->init();
		}

		/**
		 * Magical call
		 * @param string $func Function
		 * @param array $arg Arguments
		 */
		public function __call($func, $arg) {
			throw new \Exception("Function: {$func}() does not exist in " . get_class($this));
		}
	}

	/**
	 * Comments plugin
	 *
	 * @author Seth
	 * @version 0.3
	 */
	final class Comments extends Plugin implements Plugin_Interface {
		// Array to hold the data
		private $data = array();
		private $config = null;
		private $page = 1;

		/**
		 * Class init
		 *  - load whatever is specific for this plugin here
		 */
	    public function init() {
	    	// load the models that we need
	    	$this->db->comments = new \Petolio_Model_PoComments();
	    	$this->db->users = new \Petolio_Model_PoUsers();
	    	$this->db->subscriptions = new \Petolio_Model_PoSubscriptions();

	    	// load config
	    	$this->config = \Zend_Registry::get("config");

	    	// fill page
	    	$this->page = @(int)$_REQUEST["page"];

			// autoload comment entity based on x
			if($this->action == 'update' || $this->action == 'delete')
				$this->error = $this->autoload();
	    }

	    /**
	     * Autoload comment entity
	     */
	    private function autoload() {
	    	// find entity owner
	    	$owner = $this->isOwner();
	    	if(is_null($owner))
	    		return array('success' => false, 'msg' => $this->translate->_("Owner of entity not found."));

	    	// get x
	    	$x = @(int)$_REQUEST["x"];
	    	if(empty($x))
	    		return array('success' => false, 'msg' => $this->translate->_("No entity id provided."));

	    	// delete comment without checking comment owner because we are entity owners
	    	if($this->auth->hasIdentity() && $this->auth->getIdentity()->id == $owner)
	    		$results = reset($this->db->comments->getMapper()->fetchList("id = '{$x}' AND scope = '{$this->scope}'"));
	    	else
	    		$results = reset($this->db->comments->getMapper()->fetchList("id = '{$x}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));
	    	if(!$results)
	    		return array('success' => false, 'msg' => $this->translate->_("No entity matched."));

	    	// fill comments with result
			$this->db->comments = $results;
	    }

	    /**
	     * Validate user input
	     */
	    private function validate() {
	    	$comment = @(string)$_REQUEST["comment"];
	    	if(empty($comment))
	    		return array('success' => false, 'msg' => $this->translate->_("Please type your comment."));

	    	// set data
	    	$this->data['comment'] = \Petolio_Service_Parse::_($comment, 1500);

	    	// set optional data
	    	$this->data['label'] = @(string)$_REQUEST["label"];
	    	$this->data['url'] = @(string)$_REQUEST["url"];
	    	$this->data['owner'] = @(int)$_REQUEST["owner"];

	    	// fix for microsite
	    	$this->data['url'] = str_replace('/iframe/true', '', $this->data['url']);
	    }

	    /**
	     * Attempt to see if the logged user is indeed the owner of the entity
	     * @return int or null - the owner
	     */
	    private function isOwner() {
	    	// scope assignments (model class and owner function)
	    	$scopes = array(
	    		'po_files' => array(
	    			'Petolio_Model_PoFiles',
	    			'getOwnerId'
	    		), 'po_pets' => array(
    				'Petolio_Model_PoPets',
    				'getUserId'
	    		), 'po_dashboard' => array(
    				'Petolio_Model_PoDashboard',
    				'getUserId'
	    		), 'po_services' => array(
    				'Petolio_Model_PoServices',
    				'getUserId'
	    		)
	    	);

	    	// load model and field
	    	list($model, $field) = $scopes[$this->scope];
	    	$db = new $model;
			$db->find($this->id);

			// return owner
			return $db->{$field}();
	    }

	    /**
	     * Create
	     */
		public function create() {
			// validate fields
			$validate = $this->validate();
			if(!is_null($validate))
				return $validate;

			// notify people start
			$notify = new Notify($this->id, $this->scope, $this->data, $this->auth, $this->url, $this->translate);
			if($notify->getError())
				return $notify->getError();

			// notify and autopost notification
			$notify->onComment();

			// add the comment
			$this->db->comments
				->setUserId($this->auth->getIdentity()->id)
				->setScope($this->scope)
				->setEntityId($this->id)
				->setText($this->data['comment'])
				->save();

			// subscribe to entity if not already
			$result = reset($this->db->subscriptions->getMapper()->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = '{$this->scope}' AND entity_id = '{$this->id}'"));
			if(!$result) {
				$this->db->subscriptions
					->setUserId($this->auth->getIdentity()->id)
					->setScope($this->scope)
					->setEntityId($this->id)
					->save();
			}

			// return success
			return array('success' => true);
		}

		/**
		 * Read
		 */
		public function read() {
			// find entity owner
			$owner = $this->isOwner();
			if(is_null($owner))
				return array('success' => false, 'msg' => $this->translate->_("Owner of entity not found."));

			// calculate page
			$rows_per_page = $this->config["comments"]["pagination"]["itemsperpage"];
			$numrows = count($this->db->comments->getMapper()->fetchList(
								"entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
								"scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope)));
			$lastpage = ceil($numrows/$rows_per_page);

			// set page limits
			if ($this->page < 1) $this->page = 1;
			elseif ($this->page > $lastpage) $this->page = $lastpage;

			// calculate limits
			$limit = false;
			if($numrows != FALSE) {
				$limit[0] = $rows_per_page;
				$limit[1] = ($this->page - 1) * $rows_per_page;
			}

			// load comments
	    	$this->view->data = $this->db->comments->getComments(
				"a.entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
				"a.scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope), "a.id DESC", $limit);
	    	$this->view->pagination = \Petolio_Service_Util::paginate($lastpage, $this->page);
	    	$this->view->admin = $this->auth->hasIdentity() && $this->auth->getIdentity()->id == $owner;

			// return success and view
			return array('success' => true, 'view' => $this->view);
		}

		/**
		 * Update
		 */
		public function update() {
			// if error, return it
			if($this->error)
				return $this->error;

			// return success
			return array('success' => true);
		}

		/**
		 * Delete
		 */
		public function delete() {
			// if error, return it
			if($this->error)
				return $this->error;

			// delete & return success
 			$this->db->comments->deleteRowByPrimaryKey();

			// return success
			return array('success' => true);
		}
	}

	/**
	 * Ratings plugin
	 *
	 * @author Seth
	 * @version 0.5
	 */
	final class Ratings extends Plugin implements Plugin_Interface {
		// Page reference
		private $page = 1;

		/**
		 * Class init
		 *  - load whatever is specific for this plugin here
		 */
	    public function init() {
	    	// load the models that we need
	    	$this->db->ratings = new \Petolio_Model_PoRatings();
	    	$this->db->users = new \Petolio_Model_PoUsers();

	    	// fill page
	    	$this->page = @(int)$_REQUEST["page"];

			// autoload comment entity based on x
			if($this->action == 'update' || $this->action == 'delete')
				$this->error = $this->autoload();
	    }

	    /**
	     * Autoload comment entity
	     */
	    private function autoload() {
	    	// get x
	    	$x = @(int)$_REQUEST["x"];
	    	if(empty($x))
	    		return array('success' => false, 'msg' => $this->translate->_("No entity id provided."));

	    	// get result
	    	$results = reset($this->db->ratings->getMapper()->fetchList("id = '{$x}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));
	    	if(!$results)
	    		return array('success' => false, 'msg' => $this->translate->_("No entity matched."));

	    	// load ratings with result
			$this->db->ratings = $results;
	    }

	    /**
	     * Validate user input
	     */
	    private function validate() {
	    	// set optional data
	    	$this->data['label'] = @(string)$_REQUEST["label"];
	    	$this->data['url'] = @(string)$_REQUEST["url"];
	    	$this->data['owner'] = @(int)$_REQUEST["owner"];

	    	// fix for microsite
	    	$this->data['url'] = str_replace('/iframe/true', '', $this->data['url']);
	    }

	    /**
	     * Create
	     */
		public function create() {
			// validate (add optional data)
			$this->validate();

			// notify people start
			$notify = new Notify($this->id, $this->scope, $this->data, $this->auth, $this->url, $this->translate);
			if($notify->getError())
				return $notify->getError();

			// notify and autopost notification
			$notify->onRating();

			// add the rating if it doesn't exist
			$result = reset($this->db->ratings->getMapper()->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = '{$this->scope}' AND entity_id = '{$this->id}'"));
			if(!$result) {
				$this->db->ratings
					->setUserId($this->auth->getIdentity()->id)
					->setScope($this->scope)
					->setEntityId($this->id)
					->save();
			}

			// return success
			return array('success' => true);
		}

		/**
		 * Read
		 */
		public function read() {
			// load ratings count
	    	$this->view->data = count($this->db->ratings->getMapper()->fetchList(
				"entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
				"scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope)));

	    	// see if we liked this
	    	if($this->auth->hasIdentity())
	    		$this->view->self = reset($this->db->ratings->getMapper()->fetchList(
					"entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
					"scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope)." AND " .
					"user_id = '{$this->auth->getIdentity()->id}'"));

			// return success and view
			return array('success' => true, 'view' => $this->view);
		}

		/**
		 * Update
		 */
		public function update() {
			// if error, return it
			if($this->error)
				return $this->error;

			// return success
			return array('success' => true);
		}

		/**
		 * Delete
		 */
		public function delete() {
			// if error, return it
			if($this->error)
				return $this->error;

			// remove from dashboard
			\Petolio_Service_Autopost::factory('text', 'del', 'rating|' . $this->scope, $this->id, null, null);

			// delete & return success
			$this->db->ratings->deleteRowByPrimaryKey();

			// return success
			return array('success' => true);
		}

		/**
		 * Additional view request
		 */
		public function misc() {
			// calculate page
			$rows_per_page = 10;
			if($this->auth->hasIdentity())
				$numrows = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}' AND user_id <> '{$this->auth->getIdentity()->id}'"));
			else
				$numrows = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}'"));
			$lastpage = ceil($numrows/$rows_per_page);

			// set page limits
			if ($this->page < 1) $this->page = 1;
			elseif ($this->page > $lastpage) $this->page = $lastpage;

			// calculate limits
			$limit = false;
			if($numrows != FALSE) {
				$limit[0] = $rows_per_page;
				$limit[1] = ($this->page - 1) * $rows_per_page;
			}

			// load likes
			if($this->auth->hasIdentity())
				$this->view->data = $this->db->ratings->getRatings("a.entity_id = '{$this->id}' AND a.scope = '{$this->scope}' AND user_id <> '{$this->auth->getIdentity()->id}'", "a.id DESC", $limit);
			else
				$this->view->data = $this->db->ratings->getRatings("a.entity_id = '{$this->id}' AND a.scope = '{$this->scope}'", "a.id DESC", $limit);
			$this->view->pagination = \Petolio_Service_Util::paginate($lastpage, $this->page);

			// see if we liked this
			$self = false;
			if($this->auth->hasIdentity())
				$self = reset($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));

			// what's the pattern?
			if($self && $numrows > 0) $pattern = 'all_ratings';
			elseif($self) $pattern = 'slf_ratings';
			else $pattern = 'non_ratings';

			// return array
			return array(
				'success' => true,
				'view' => $this->view,
				'total' => $numrows,
				'pattern' => $pattern
			);
		}
	}

	/**
	 * Ratings with stars plugin
	 *
	 * @author Lotzi
	 * @version 0.1
	 */
	final class StarRatings extends Plugin implements Plugin_Interface {
		// Page reference
		private $page = 1;

		/**
		 * Class init
		 *  - load whatever is specific for this plugin here
		 */
	    public function init() {
	    	// load the models that we need
	    	$this->db->ratings = new \Petolio_Model_PoStarRatings();
	    	$this->db->users = new \Petolio_Model_PoUsers();

	    	// fill page
	    	$this->page = @(int)$_REQUEST["page"];

			// autoload rating entity based on entity_id
			if($this->action == 'update' || $this->action == 'delete')
				$this->error = $this->autoload();
	    }

	    /**
	     * Autoload star rating entity
	     */
	    private function autoload() {
	    	// get x
	    	$id = @(int)$_REQUEST["id"];
	    	if(empty($id))
	    		return array('success' => false, 'msg' => $this->translate->_("No entity id provided."));

	    	// get result
	    	$results = reset($this->db->ratings->getMapper()->fetchList("entity_id = '{$id}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));
	    	if(!$results)
	    		return array('success' => false, 'msg' => $this->translate->_("No entity matched."));

	    	// load ratings with result
			$this->db->ratings = $results;
	    }

	    /**
	     * Validate user input
	     */
	    private function validate() {
	    	$rating = @(string)$_REQUEST["rating"];
	    	if(empty($rating))
	    		return array('success' => false, 'msg' => $this->translate->_("Please click on one of the stars."));

	    	// set data
	    	$this->data['rating'] = $rating;
	    	
	    	// set optional data
	    	$this->data['url'] = @(string)$_REQUEST["url"];

	    	// fix for microsite
	    	$this->data['url'] = str_replace('/iframe/true', '', $this->data['url']);
	    }

	    /**
	     * Create
	     */
		public function create() {
			// validate (add optional data)
			$this->validate();

			// add the rating if it doesn't exist
			$result = reset($this->db->ratings->getMapper()->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = '{$this->scope}' AND entity_id = '{$this->id}'"));
			if(!$result) {
				$this->db->ratings
					->setUserId($this->auth->getIdentity()->id)
					->setRating($this->data['rating'])
					->setScope($this->scope)
					->setEntityId($this->id)
					->save();
			}

			// return success
			return array('success' => true);
		}

		/**
		 * Read
		 */
		public function read() {
			// get entity rating
			$data = $this->db->ratings->getEntityRating($this->scope, $this->id);
	    	$this->view->rating = (isset($data) && $data['rating_count'] > 0) ? round(($data['rating_sum'] / $data['rating_count'])) : 0;
	    	$this->view->rating_count = isset($data['rating_count']) ? $data['rating_count'] : 0;

	    	// see if we liked this
	    	if($this->auth->hasIdentity())
	    		$this->view->self = reset($this->db->ratings->getMapper()->fetchList(
					"entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
					"scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope)." AND " .
					"user_id = '{$this->auth->getIdentity()->id}'"));

			// return success and view
			return array('success' => true, 'view' => $this->view);
		}

		/**
		 * Update
		 */
		public function update() {
			// if error, return it
			if($this->error)
				return $this->error;

			// return success
			return array('success' => true);
		}

		/**
		 * Delete
		 */
		public function delete() {
			// if error, return it
			if($this->error)
				return $this->error;

			// TODO - this is really necessary?
			// remove from dashboard
			// \Petolio_Service_Autopost::factory('text', 'del', 'rating|' . $this->scope, $this->id, null, null);

			// delete & return success
			$this->db->ratings->deleteRowByPrimaryKey();

			// return success
			return array('success' => true);
		}

		/**
		 * Additional view request
		 */
		public function misc() {
			// calculate page
			$rows_per_page = 10;
			if($this->auth->hasIdentity())
				$numrows = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}' AND user_id <> '{$this->auth->getIdentity()->id}'"));
			else
				$numrows = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}'"));
			$lastpage = ceil($numrows/$rows_per_page);

			// set page limits
			if ($this->page < 1) $this->page = 1;
			elseif ($this->page > $lastpage) $this->page = $lastpage;

			// calculate limits
			$limit = false;
			if($numrows != FALSE) {
				$limit[0] = $rows_per_page;
				$limit[1] = ($this->page - 1) * $rows_per_page;
			}

			// load likes
			if($this->auth->hasIdentity())
				$this->view->data = $this->db->ratings->getRatings("a.entity_id = '{$this->id}' AND a.scope = '{$this->scope}' AND user_id <> '{$this->auth->getIdentity()->id}'", "a.id DESC", $limit);
			else
				$this->view->data = $this->db->ratings->getRatings("a.entity_id = '{$this->id}' AND a.scope = '{$this->scope}'", "a.id DESC", $limit);
			$this->view->pagination = \Petolio_Service_Util::paginate($lastpage, $this->page);

			// see if we liked this
			$self = false;
			if($this->auth->hasIdentity())
				$self = reset($this->db->ratings->getMapper()->fetchList("entity_id = '{$this->id}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));

			// what's the pattern?
			if($self && $numrows > 0) $pattern = 'all_ratings';
			elseif($self) $pattern = 'slf_ratings';
			else $pattern = 'non_ratings';

			// return array
			return array(
				'success' => true,
				'view' => $this->view,
				'total' => $numrows,
				'pattern' => $pattern
			);
		}
	}

	/**
	 * Recommend plugin
	 *
	 * @author Seth
	 * @version 0.1
	 */
	final class Recommend extends Plugin implements Plugin_Interface {
		// Array to hold the data
		private $data = array();

		/**
		 * Class init
		 *  - load whatever is specific for this plugin here
		 */
	    public function init() {
	    }

		/**
	     * Validate user input
	     */
	    private function validate() {
	    	$comment = @(string)$_REQUEST["emails"];
	    	if(empty($comment))
	    		return array('success' => false, 'msg' => $this->translate->_("Please type your friend email addresses."));

	    	// set data
	    	$this->data['emails'] = $comment;
	    }

	    /**
	     * Create
	     */
		public function create() {
			// validate fields
			$validate = $this->validate();
			if(!is_null($validate))
				return $validate;

			foreach(explode(' ', $this->data['emails']) as $one) {
				// email user
				$email = new \Petolio_Service_Mail();
				$email->setRecipient($one);
				$email->setTemplate('users/recommend');
				$email->base_url = PO_BASE_URL;
				$email->who = $this->auth->getIdentity()->name;

				// scope
				if($this->scope == 'po_promotions') {
					$email->what = $this->translate->_("event");
					$email->eventLink = PO_BASE_URL . 'promotions/index/id/' . $this->id;
				} elseif($this->scope == 'po_products') {
					$email->what = $this->translate->_("product");
					$email->eventLink = PO_BASE_URL . 'products/view/product/' . $this->id;
				} elseif($this->scope == 'po_services') {
					$email->what = $this->translate->_("service");
					$email->eventLink = PO_BASE_URL . 'services/view/service/' . $this->id;
				}

				$email->send();
			}

			// return success
			return array('success' => true, 'count' => count(explode(' ', $this->data['emails'])));
		}

		/**
		 * Read
		 */
		public function read() {
			// return success and view
			return array('success' => true, 'view' => $this->view);
		}

		/**
		 * Update
		 */
		public function update() {
			// return success
			return array('success' => true);
		}

		/**
		 * Delete
		 */
		public function delete() {
			// return success
			return array('success' => true);
		}
	}

	/**
	 * Subscriptions plugin
	 *
	 * @author Seth
	 * @version 0.1
	 */
	final class Subscriptions extends Plugin implements Plugin_Interface {
		/**
		 * Class init
		 *  - load whatever is specific for this plugin here
		 */
	    public function init() {
	    	// load the models that we need
	    	$this->db->subscriptions = new \Petolio_Model_PoSubscriptions();

			// autoload comment entity based on x
			if($this->action == 'update' || $this->action == 'delete')
				$this->error = $this->autoload();
	    }

	    /**
	     * Autoload comment entity
	     */
	    private function autoload() {
	    	// get x
	    	$x = @(int)$_REQUEST["x"];
	    	if(empty($x))
	    		return array('success' => false, 'msg' => $this->translate->_("No entity id provided."));

	    	// get result
	    	$results = reset($this->db->subscriptions->getMapper()->fetchList("id = '{$x}' AND scope = '{$this->scope}' AND user_id = '{$this->auth->getIdentity()->id}'"));
	    	if(!$results)
	    		return array('success' => false, 'msg' => $this->translate->_("No entity matched."));

	    	// load subscriptions with result
			$this->db->subscriptions = $results;
	    }

	    /**
	     * Create
	     */
		public function create() {
			// add the subscription if it doesn't exist
			$result = reset($this->db->subscriptions->getMapper()->fetchList("user_id = '{$this->auth->getIdentity()->id}' AND scope = '{$this->scope}' AND entity_id = '{$this->id}'"));
			if(!$result) {
				$this->db->subscriptions
					->setUserId($this->auth->getIdentity()->id)
					->setScope($this->scope)
					->setEntityId($this->id)
					->save();
			}

			// return success
			return array('success' => true);
		}

		/**
		 * Read
		 */
		public function read() {
	    	// see if we are subscribed
	    	if($this->auth->hasIdentity())
	    		$this->view->subscription = reset($this->db->subscriptions->getMapper()->fetchList(
					"entity_id = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->id, \Zend_Db::BIGINT_TYPE)." AND " .
					"scope = ".\Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->scope)." AND " .
					"user_id = '{$this->auth->getIdentity()->id}'"));

			// return success and view
			return array('success' => true, 'view' => $this->view);
		}

		/**
		 * Update
		 */
		public function update() {
			// if error, return it
			if($this->error)
				return $this->error;

			// return success
			return array('success' => true);
		}

		/**
		 * Delete
		 */
		public function delete() {
			// if error, return it
			if($this->error)
				return $this->error;

			// delete & return success
			$this->db->subscriptions->deleteRowByPrimaryKey();

			// return success
			return array('success' => true);
		}
	}

	/**
	 * The notify class
	 *  - sending the mail to subscribed people
	 *
	 * @author Seth
	 * @version 0.1
	 */
	final class Notify {
		// data and auth objects
		protected $id;
		protected $scope;
		protected $data;
		protected $auth;
		protected $url;
		protected $translate;

		// db
		protected $db;

		// handle errors
		protected $error = false;

		/**
		 * Constructor
		 * @param array $params
		 */
		public function __construct($id, $scope, $data, $auth, $url, $translate) {
			// set objects
			$this->id = $id;
			$this->scope = $scope;
			$this->data = $data;
			$this->auth = $auth;
			$this->url = $url;
			$this->translate = $translate;

			// initiate the db and run init on plugin
			$this->db = new \stdClass();
			$this->db->users = new \Petolio_Model_PoUsers();
			$this->db->subscriptions = new \Petolio_Model_PoSubscriptions();

			// load the user based on the owner
			$this->db->users->find($this->data['owner']);
			if(!$this->db->users->getId())
				return $this->setError(array('success' => false, 'msg' => $this->translate->_("Owner of entity not found.")));
		}

		/**
		 * Set error
		 * @param array $error
		 */
		private function setError($error) {
			$this->error = $error;
		}

		/**
		 * Get error
		 * @return array
		 */
		public function getError() {
			return $this->error;
		}

		/**
		 * Handle notify for comments
		 * @return void
		 */
		public function onComment() {
			// get reply url
			$errors = array();
			$reply = reset(reset(\Petolio_Service_Util::get_string_between($this->data['url'], 'href="', '">', $errors)));

			// handle ignore posting
			$ignore = false;
			if($this->data['label'] == "{ignore}") {
				$this->data['label'] = "{direct}";
				$ignore = true;
			}

			// get subscribers
			$subscribers = $this->db->subscriptions->getSubscribers("scope = '{$this->scope}' AND entity_id = '{$this->id}' AND user_id <> '{$this->auth->getIdentity()->id}' AND user_id <> '{$this->db->users->getId()}'");

			// compile email for subscribers
			if(count($subscribers) > 0) {
				// email translation
				if($this->data['label'] == "{direct}") {
					// decode url
					$errors = array();
					$the_url = \Petolio_Service_Util::get_string_between($this->data['url'], '{', '}', $errors);

					// do translation
					if($this->db->users->getId() == $this->auth->getIdentity()->id) {
						$email = array(
							'entries' => $this->translate->_('%1$s has also commented on one of his own {link}Small Talk entries{/link}'),
							'pictures' => $this->translate->_('%1$s has also commented on one of his own {link}Pictures{/link}'),
							'audios' => $this->translate->_('%1$s has also commented on one of his own {link}Audios{/link}'),
							'videos' => $this->translate->_('%1$s has also commented on one of his own {link}Videos{/link}')
						);
					} else {
						$email = array(
							'entries' => $this->translate->_('%1$s has also commented on one of %3$s\'s {link}Small Talk entries{/link}'),
							'pictures' => $this->translate->_('%1$s has also commented on one of %3$s\'s {link}Pictures{/link}'),
							'audios' => $this->translate->_('%1$s has also commented on one of %3$s\'s {link}Audios{/link}'),
							'videos' => $this->translate->_('%1$s has also commented on one of %3$s\'s {link}Videos{/link}')
						);
					}

					$email_string = str_replace(array("{link}", "{/link}"), array($the_url[1][0], $the_url[1][1]), $email[$the_url[0][0]]);
				} else {
					if($this->db->users->getId() == $this->auth->getIdentity()->id)
						$email_string = $this->translate->_('%1$s has also commented on his own pet %2$s');
					else
						$email_string = $this->translate->_('%1$s has also commented on %3$s\'s pet %2$s');
				}

				// translate string
				$translated = sprintf(
					$email_string,
					"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
					$this->data['url'],
					"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->db->users->getId()), 'default', true)}'>{$this->db->users->getName()}</a>"
				);

				// get all subscribed users except for you, yes i'm talking to YOU.
				foreach($subscribers as $subscriber) {
					// send email and message
					\Petolio_Service_Message::send(array(
						'subject' => strip_tags($translated),
						'message_html' => $translated . "<br/>" . $this->data['comment'] . "<br /><br />" . "<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$reply}'>".$this->translate->_('Reply to comment')."</a>",
						'from' => $this->auth->getIdentity()->id,
						'status' => 1,
						'template' => 'default'
					), array(array(
						'id' => $subscriber['user_id'],
						'name' => $subscriber['user_name'],
						'email' => $subscriber['user_email']
					)), $subscriber['user_dash_email_notification']);

					// send AMQPC
			    	// \Petolio_Service_AMQPC::sendMessage('dashboard', array($translated, $reply, $this->auth->getIdentity()->id, $subscriber['user_id']));
				}
			}

			// if you comment your own entry... you dont need to be notified of that :)
			if($this->db->users->getId() == $this->auth->getIdentity()->id)
				return false;

			// direct (imgsw / dashboard)
			if($this->data['label'] == "{direct}") {
				// decode url
				$errors = array();
				$the_url = \Petolio_Service_Util::get_string_between($this->data['url'], '{', '}', $errors);

				// define dashboard
				$dashboard = array(
					array(
						'pictures' => '{translate}%1$s has commented on one of %2$s\'s {link}Pictures{/link}{/translate}',
						'audios' => '{translate}%1$s has commented on one of %2$s\'s {link}Audios{/link}{/translate}',
						'videos' => '{translate}%1$s has commented on one of %2$s\'s {link}Videos{/link}{/translate}'
					),
					"<div class='truncate'>{$this->data['comment']}</div>",
					array(
						'<a href="{user_link}" style="font-weight: bold;">{user_name}</a>',
						"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->db->users->getId()), 'default', true)}'>{$this->db->users->getName()}</a>",
						$this->data['url']
					)
				);

			// pet (from pet view)
			} else {
				$dashboard = array(
					'{translate}%1$s has commented on %2$s\'s pet %3$s{/translate}' . "<div class='truncate'>{$this->data['comment']}</div>",
					array(
						'<a href="{user_link}" style="font-weight: bold;">{user_name}</a>',
						"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->db->users->getId()), 'default', true)}'>{$this->db->users->getName()}</a>",
						$this->data['url']
					)
				);
			}

			// post on dashboard
			if(!$ignore) {
				$fake = array(
					$this->translate->_('%1$s has commented on one of %2$s\'s {link}Pictures{/link}'),
					$this->translate->_('%1$s has commented on one of %2$s\'s {link}Audios{/link}'),
					$this->translate->_('%1$s has commented on one of %2$s\'s {link}Videos{/link}'),
					$this->translate->_('%1$s has commented on %2$s\'s pet %3$s'),
					$this->translate->_('%1$s has commented on one of %2$s\'s %3$s'),
					$this->translate->_('%1$s has commented on %2$s\'s %3$s %4$s'),
					$this->translate->_('%1$s has commented on one of your %2$s'),
					$this->translate->_('%1$s has commented on your %2$s %3$s'),
					$this->translate->_('%1$s has commented on your %2$s'),
				); unset($fake);

				// get string between template
				if($this->data['label'] == "{direct}") {
					$var1 = $dashboard[0][$the_url[0][0]] . $dashboard[1];
					$var2 = $dashboard[2];
				} else {
					$var1 = $dashboard[0];
					$var2 = $dashboard[1];
				}

				\Petolio_Service_Autopost::factory('text', 'add', 'comment|' . $this->scope, $this->id, $var1, $var2);
			}

			// email translation
			if($this->data['label'] == "{direct}") {
				$email = array(
					'entries' => $this->translate->_('%1$s has commented on one of your {link}Small Talk entries{/link}'),
					'pictures' => $this->translate->_('%1$s has commented on one of your {link}Pictures{/link}'),
					'audios' => $this->translate->_('%1$s has commented on one of your {link}Audios{/link}'),
					'videos' => $this->translate->_('%1$s has commented on one of your {link}Videos{/link}')
				);

				$email_string = str_replace(array("{link}", "{/link}"), array($the_url[1][0], $the_url[1][1]), $email[$the_url[0][0]]);
			} else $email_string = $this->translate->_('%1$s has commented on your pet %2$s');

			// translate string
			$translated = sprintf(
				$email_string,
				"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				$this->data['url']
			);

			// send email and message
			\Petolio_Service_Message::send(array(
				'subject' => strip_tags($translated),
				'message_html' => $translated . "<br/>" . $this->data['comment'] . "<br /><br />" . "<a style='background: none repeat scroll 0 0 #74A428;border: 1px solid #74A428;border-radius: 0px;box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);color: #FFFFFF;cursor: pointer;font-style: normal;padding: 4px 20px;width: auto;text-decoration: none;' href='{$reply}'>".$this->translate->_('Reply to comment')."</a>",
				'from' => $this->auth->getIdentity()->id,
				'status' => 1,
				'template' => 'default'
			), array(array(
				'id' => $this->db->users->getId(),
				'name' => $this->db->users->getName(),
				'email' => $this->db->users->getEmail()
			)), $this->db->users->getDashEmailNotification());

			// send AMQPC
	    	// \Petolio_Service_AMQPC::sendMessage('dashboard', array($translated, $reply, $this->auth->getIdentity()->id, $this->db->users->getId()));
		}

		/**
		 * Handle notify for ratings
		 * @return void
		 */
		public function onRating() {
			// get reply url
			$errors = array();
			$reply = reset(reset(\Petolio_Service_Util::get_string_between($this->data['url'], 'href="', '">', $errors)));

			// if you like your own entry... you dont need to be notified of that :)
			if($this->db->users->getId() == $this->auth->getIdentity()->id)
				return false;

			// handle ignore posting
			$ignore = false;
			if($this->data['label'] == "{ignore}") {
				$this->data['label'] = "{direct}";
				$ignore = true;
			}

			// direct (imgsw / dashboard)
			if($this->data['label'] == "{direct}") {
				// decode url
				$errors = array();
				$the_url = \Petolio_Service_Util::get_string_between($this->data['url'], '{', '}', $errors);

				// define dashboard
				$dashboard = array(
					array(
						'pictures' => '{translate}%1$s likes one of %2$s\'s {link}Pictures{/link}{/translate}',
						'audios' => '{translate}%1$s likes one of %2$s\'s {link}Audios{/link}{/translate}',
						'videos' => '{translate}%1$s likes one of %2$s\'s {link}Videos{/link}{/translate}'
					),
					array(
						'<a href="{user_link}" style="font-weight: bold;">{user_name}</a>',
						"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->db->users->getId()), 'default', true)}'>{$this->db->users->getName()}</a>",
						$this->data['url']
					)
				);

			// pet (from pet view)
			} else {
				$dashboard = array(
					'{translate}' . '%1$s likes %2$s\'s pet %3$s' . '{/translate}',
					array(
						'<a href="{user_link}" style="font-weight: bold;">{user_name}</a>',
						"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->db->users->getId()), 'default', true)}'>{$this->db->users->getName()}</a>",
						$this->data['url']
					)
				);
			}

			// post on dashboard
			if(!$ignore) {
				$fake = array(
					$this->translate->_('%1$s likes one of %2$s\'s {link}Pictures{/link}'),
					$this->translate->_('%1$s likes one of %2$s\'s {link}Audios{/link}'),
					$this->translate->_('%1$s likes one of %2$s\'s {link}Videos{/link}'),
					$this->translate->_('%1$s likes %2$s\'s pet %3$s'),
					$this->translate->_('%1$s likes one of %2$s\'s %3$s'),
					$this->translate->_('%1$s likes %2$s\'s %3$s %4$s'),
				); unset($fake);

				// get string between template
				if($this->data['label'] == "{direct}") {
					$var1 = $dashboard[0][$the_url[0][0]];
					$var2 = $dashboard[1];
				} else {
					$var1 = $dashboard[0];
					$var2 = $dashboard[1];
				}

				\Petolio_Service_Autopost::factory('text', 'add', 'rating|' . $this->scope, $this->id, $var1, $var2);
			}

			// email translation
			if($this->data['label'] == "{direct}") {
				$email = array(
					'entries' => $this->translate->_('%1$s likes one of your {link}Small Talk entries{/link}'),
					'pictures' => $this->translate->_('%1$s likes one of your {link}Pictures{/link}'),
					'audios' => $this->translate->_('%1$s likes one of your {link}Audios{/link}'),
					'videos' => $this->translate->_('%1$s likes one of your {link}Videos{/link}')
				);

				$email_string = str_replace(array("{link}", "{/link}"), array($the_url[1][0], $the_url[1][1]), $email[$the_url[0][0]]);
			} else $email_string = $this->translate->_('%1$s likes your pet %2$s');

			// translate string
			$translated = sprintf(
				$email_string,
				"<a href='{$this->url->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
				$this->data['url']
			);

			// send AMQPC
	    	// \Petolio_Service_AMQPC::sendMessage('dashboard', array($translated, $reply, $this->auth->getIdentity()->id, $this->db->users->getId()));
		}
	}
}