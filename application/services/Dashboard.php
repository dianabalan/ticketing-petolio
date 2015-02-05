<?php

// public namespace
namespace {
	/**
	 * Dashboard Service (created to prevent code repetition)
	 * - contains the load function and the attach 2 entry function
	 *
	 * @author Seth
	 * @version 0.1
	 *
	 */
	class Petolio_Service_Dashboard {
		// private vars
		private $translate = null;
		private $auth = null;
		private $request = null;
		private $config = null;
		private $db = null;

		// public definitions
		public $privacy = array();

		/**
		 * Cached youtube object
		 * @var object
		 */
		protected $youtube = null;

		/**
		 * The constructor
		 */
		public function __construct($a, $b) {
			$this->translate = Zend_Registry::get('Zend_Translate');
			$this->auth = Zend_Auth::getInstance();
			$this->config = Zend_Registry::get("config");

			$this->request = $a;
			$this->db = $b;

			// preload privacy
			$this->privacy = array(
				0 => 'p_public',
				1 => 'p_friends',
				2 => 'p_me',
				3 => 'p_custom'
			);
		}

		/**
		 * Load the events
		 * @param int user id (news feed if negative)
		 * @param int page
		 * @param bool/int $n new, false or timestamp
		 * @return array results and bool if last page or int if $n is not false
		 */
		public function load($x, $p = 0, $n = false) {
			// based on user positive or negative build filters (dash / news)
			$filters = array();

			// all dashboard
			if($x == 0) {
				// validate against permissions
				$filters[] = "(a.rights <> '2' OR a.user_id = '{$this->auth->getIdentity()->id}')";
				$filters[] = "(a.rights = '0' OR ((a.rights = '1' OR a.rights = '3') AND r.user_id = '{$this->auth->getIdentity()->id}') OR a.user_id = '{$this->auth->getIdentity()->id}')";

			// private dashboard
			} elseif($x > 0) {
				// since this is only for private dashboard, security check it
				if($this->auth->hasIdentity() && $this->auth->getIdentity()->id == $x) $filters[] = "a.user_id = '{$x}'";

				// for profile dashboard
				else {
					// set user filter
					$filters[] = "a.user_id = '{$x}'";

					// not logged in?
					if (!$this->auth->hasIdentity())
						$filters[] = "a.rights = '0'";

					// logged in?
					else {
						// validate against permissions
						$filters[] = "(a.rights <> '2' OR a.user_id = '{$this->auth->getIdentity()->id}')";
						$filters[] = "(a.rights = '0' OR ((a.rights = '1' OR a.rights = '3') AND r.user_id = '{$this->auth->getIdentity()->id}') OR a.user_id = '{$this->auth->getIdentity()->id}')";
					}
				}

			// friend dashboard
			} elseif($x < 0) {
				// get all friends and partners
				$friends = new Dashboard\Privacy($this->request);

				// validate against permissions
				$x = $x * -1;
				$filters[] = "(a.user_id IN (" . implode(',', $friends->users('array', $x)) . ") OR a.user_id = '{$x}')";
				$filters[] = "(a.rights <> '2' OR a.user_id = '{$x}')";
				$filters[] = "(a.rights = '0' OR ((a.rights = '1' OR a.rights = '3') AND r.user_id = '{$x}') OR a.user_id = '{$x}')";
			}

			// timestamp and exclude self
			if($n != false) {
				$filters[] = "a.date_created >= '". date('Y-m-d H:i:s', $n) ."'";

				// exclude self only if logged in
				if ($this->auth->hasIdentity())
					$filters[] = "a.user_id <> '{$this->auth->getIdentity()->id}'";
			}

			// calculate page (if page is -1 then we need only 5 for sidebar)
			$rows_per_page = $p == -1 ? 5 : $this->config["dashboard"]["pagination"]["itemsperpage"];
			$numrows = count($this->db->dash->getEntries(implode(' AND ', $filters)));
			$lastpage = ceil($numrows/$rows_per_page);

			// just to count new entries?
			if($n != false)
				return $numrows;

			// set page limits
			if ($p < 1) $p = 1;
			elseif ($p > $lastpage) $p = $lastpage;

			// calculate limits
			$limit = false;
			if($numrows != FALSE) {
				$limit[0] = (int)$rows_per_page;
				$limit[1] = (int)($p - 1) * $rows_per_page;
			}

			// load entries
			$results = $this->db->dash->getEntries(implode(' AND ', $filters), "date_created DESC", $limit);

			// check to see if videos are not yet processed
			$this->processingCheck($results);
			$results = array_merge($results); // reindex

			// attach aditional data
			foreach($results as $key => $entry)
				$results[$key]['attached'] = $this->attach2Entry($entry);

			// return the stuff
			return array($results, $lastpage != $p);
		}

		/**
		 * Attach additional data to an entry
		 * @param array $entry
		 * @param int $limit comment limit
		 * @param int $page pagination (0 default - no pagination, -1 last page)
		 */
		public function attach2Entry($entry, $limit = 5, $page = 0) {
			// if scope is self
			if($entry['scope'] == 'po_dashboard')
				$entry['entity_id'] = $entry['id'];

			// attach comments latest 5 comments
			$comments = $this->db->comments->getComments("scope = '{$entry['scope']}' AND entity_id = {$entry['entity_id']}", "date_created ASC");
			$total_c = count($comments);

			// limit comments (if default)
			if($page === 0)
				$comments = array_slice($comments, $limit * -1);

			// figure out the page otherwise
			else {
				$lastpage = (int)ceil($total_c/$limit);
				if($page === -1) $page = 1;
				elseif ($page > $lastpage) $page = $lastpage;

				// calculate limits
				$_limit = false;
				if($comments != FALSE) {
					$_limit[0] = (int)$limit * ($page);
					$_limit[1] = 0;
				}

				// get new comments with limit and order them correctly
				$comments = $this->db->comments->getComments("scope = '{$entry['scope']}' AND entity_id = {$entry['entity_id']}", "date_created DESC", $_limit);
				Petolio_Service_Util::array_sort($comments, 'date_created');

				// show pagination if we have hidden comments
				if(!($_limit[0] >= $total_c))
					$pagination = array(
						'offset' => $_limit[0],
						'total' => $total_c,
						'next' => $page + 1
					);
			}

			// attach likes
			if($this->auth->hasIdentity())
				$total_r = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$entry['entity_id']}' AND scope = '{$entry['scope']}' AND user_id <> '{$this->auth->getIdentity()->id}'"));
			else
				$total_r = count($this->db->ratings->getMapper()->fetchList("entity_id = '{$entry['entity_id']}' AND scope = '{$entry['scope']}'"));

			// see if we liked this
			$self = false;
			if($this->auth->hasIdentity())
				$self = reset($this->db->ratings->getMapper()->fetchList("entity_id = '{$entry['entity_id']}' AND scope = '{$entry['scope']}' AND user_id = '{$this->auth->getIdentity()->id}'"));

			// what's the pattern?
			if($self && $total_r > 0) $pattern = 'all_ratings';
			elseif($self) $pattern = 'slf_ratings';
			else $pattern = 'non_ratings';

			// attach privacy value
			$privacy = new Dashboard\Privacy($this->request);

			// see if we're subscribed
			$subscription = false;
			if($this->auth->hasIdentity())
				$subscription = reset($this->db->subscriptions->getMapper()->fetchList("entity_id = '{$entry['entity_id']}' AND scope = '{$entry['scope']}' AND user_id = '{$this->auth->getIdentity()->id}'"));

			// return attached data
			return array(
				'all_comments' => $page === 0 ? ($total_c > $limit ? $total_c : false) : false,
				'pagination' => isset($pagination) ? $pagination : false,
				'comments' => $comments,
				'pattern' => $pattern,
				'self' => $self ? $self->getId() : false,
				'all_ratings' => $total_r,
				'privacy' => $privacy->load($entry['id']),
				'subscription' => $subscription ? $subscription->getId() : false
			);
		}

		/**
		 * Get Youtube
		 * Initialize the youtube class
		 * @return Youtube\Master object
		 */
		private function getYoutube() {
			// already loaded?
			if(!is_null($this->youtube))
				return $this->youtube;

			// get config
			$config = Zend_Registry::get("config");

			// call our youtube wrapper
			$this->youtube = Petolio_Service_YouTube::factory('Master');
			$this->youtube->CFG = array(
				'username' => $config["youtube"]["username"],
				'password' => $config["youtube"]["password"],
				'app' => $config["youtube"]["app"],
				'key' => $config["youtube"]["key"]
			);

			// return the youtube object
			return $this->youtube;
		}

		/**
		 * Check video entries are not yet processed
		 * Recheck them until processed and delete the entry automatically if error
		 *
		 * @param array $results
		 */
		private function processingCheck(&$results) {
			// each entry
			foreach($results as $idx => $result) {
				$unserialized = unserialize($result['serialized']);

				// each video entry
				if(isset($unserialized['args']) && $unserialized['args'][0] == 'video') {
					// get results from serialised
					$videos = !is_array($unserialized['results']) ? array($unserialized['results']) : $unserialized['results'];

					// each video
					$rez = array();
					foreach($videos as $vdx => $vid) {
						// each video that is processing
						if($vid->getMapper() instanceof Zend_Gdata_YouTube_VideoEntry
							&& !is_null($vid->getMapper()->getVideoState())
							&& $vid->getMapper()->getVideoState()->getName() == 'processing') {
							// run update
							$status = $this->reqUpdate($vid, $result);

							// if status is false, video was removed
							if($status === false)
								unset($videos[$vdx]);

							// if status is not processing, update the entry
							if($status !== 'processing')
								$rez[$result['id']] = true;
						}
					}

					// get view when we need to update
					if(count($rez) > 0) {
						$render = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

						// view not initialized?
						if (null === $render->view)
							$render->initView();

						// entries to update
						foreach($rez as $key => $one) {
							// none, delete entry
							if(count($videos) == 0) {
								// delete all comments and likes and permissions
								$this->db->comments->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$key}'");
								$this->db->ratings->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$key}'");
								$this->db->subscriptions->getMapper()->getDbTable()->delete("scope = 'po_dashboard' AND entity_id = '{$key}'");
								$this->db->rights->getMapper()->getDbTable()->delete("dashboard_id = '{$key}'");

								// delete the whole entry then
								$this->db->dash->find($key)->deleteRowByPrimaryKey();
								unset($results[$idx]);
							}

							// solo, vid
							if(count($videos) == 1) {
								// set the view variables
								$render->view->results = reset($videos);
								$render->view->args = $unserialized['args'];
								$data = $render->view->render('autopost/vid.phtml');

								// update entry
								$this->db->dash->find($key)->setOptions(array(
									'data' => $data,
									'serialized' => serialize(array(
										'args' => $unserialized['args'],
										'results' => reset($videos),
										'total' => 1
									))
								))->save();

								// update item in list as well
								$results[$idx]['data'] = $data;
							}

							// multiple, vid+
							if(count($videos) > 1) {
								// set the view variables
								$render->view->results = $videos;
								$render->view->args = $unserialized['args'];
								$data = $render->view->render('autopost/vid+.phtml');

								// update entry
								$this->db->dash->find($key)->setOptions(array(
									'data' => $data,
									'serialized' => serialize(array(
										'args' => $unserialized['args'],
										'results' => $videos,
										'total' => count($videos)
									))
								))->save();

								// update item in list as well
								$results[$idx]['data'] = $data;
							}
						}
					}
				}
			}
		}

		/**
		 * Proceed with the update
		 * @param object $vid
		 * @param array $entity
		 * @return mixed false for deleted, true for updated or processing for same shit
		 */
		private function reqUpdate($vid, $entity) {
			// request from youtube
			try {
				$req = $this->getYoutube()->getVideoEntry($vid->getMapper()->getVideoId());
			} catch (\Exception $e) {
				return false;
			}

			// error? return false or processing
			if(!is_null($req->getVideoState())) {
				// still processing?
				if($req->getVideoState()->getName() == 'processing')
					return $req->getVideoState()->getName();

				// return here
				return false;
			}

			// no error, assuming update
			$vid->setMapper($req);
			return true;
		}
	}
}

// dashboard namespace
namespace Dashboard {
	/**
	 * Privacy Interface
	 * @author Seth
	 */
	interface Privacy_Interface {
		// needed actions
		public function save();
		public function load($id);
		public function saveCustom();
		public function loadCustom();
		public function users();
	}

	/**
	 * Petolio Dashboard Privacy
	 *
	 * @author Seth^^
	 * @version 0.1
	 */
	final class Privacy implements Privacy_Interface {
		// common variables
		private $auth;
		private $params;
		private $request;

		// the db object
		private $db;

		/**
		 * Constructor, loads all the common stuff here
		 */
		public function __construct($req) {
			// get auth, request and params
			$this->auth = \Zend_Auth::getInstance();
			$this->request = $req;
			$this->loadParams();

			// set the db object
			$this->db = new \stdClass();
			$this->db->user = new \Petolio_Model_PoUsers();
			$this->db->dash = new \Petolio_Model_PoDashboard();
			$this->db->rights = new \Petolio_Model_PoDashboardRights();
		}

		/**
		 * Load all the parameters we might need
		 */
		private function loadParams() {
			// init empty std class
			$this->params = new \stdClass();

			// dashboard_id, rights value and users
			$this->params->x = @(int)$this->request->getParam('x');
			$this->params->d = @(int)$this->request->getParam('d');
			$this->params->u = @(array)$this->request->getParam('u');
		}

		/**
		 * Save privacy setting
		 * @see Dashboard.Privacy_Interface::save()
		 */
		public function save() {
			// save rights
			$this->db->dash->getMapper()->setPrivacySetting($this->params->x, $this->params->d, $this->auth->getIdentity()->id, $this->db->rights);

			// save friends and partners
			if($this->params->d == 1)
				$this->db->rights->getMapper()->setCustomUsers($this->params->x, $this->users('array'));

			// return json
			return \Petolio_Service_Util::json(array('success' => true));
		}

		/**
		 * Load privacy setting
		 * @param int - dashboard id
		 * @return int - rights value
		 * @see Dashboard.Privacy_Interface::load()
		 */
		public function load($id) {
			// find privacy settings for all the fields in request
			return $this->db->dash->getMapper()->findPrivacySetting($id, $this->auth->hasIdentity() ? $this->auth->getIdentity()->id : 0);
		}

		/**
		 * Save custom users
		 * @see Dashboard.Privacy_Interface::saveCustom()
		 */
		public function saveCustom() {
			// save setting & save users
			$this->db->dash->getMapper()->setPrivacySetting($this->params->x, $this->params->d, $this->auth->getIdentity()->id, $this->db->rights);
			$this->db->rights->getMapper()->setCustomUsers($this->params->x, $this->params->u);

			// return json
			return \Petolio_Service_Util::json(array('success' => true));
		}

		/**
		 * Load custom users
		 * @see Dashboard.Privacy_Interface::loadCustom()
		 */
		public function loadCustom() {
			// user list
			$out = array();
			$result = $this->db->dash->getMapper()->findCustomUsers($this->params->x, $this->params->d, $this->auth->getIdentity()->id, $this->db->rights);
			if($result)
				foreach($result as $one)
				$out[] = $one->getUserId();

			// return user list
			return \Petolio_Service_Util::json(array('success' => true, 'users' => $out));
		}

		/**
		 * Get the friends and partners
		 * @param bool $return - how to return, json or array
		 * @param int $fail - in case of error, add this fail to not break the sql
		 * @see Dashboard.Privacy_Interface::users()
		 */
		public function users($return = 'json', $fail = null) {
			// user not logged in? just show himself
			if(!$this->auth->hasIdentity())
				return array($fail);

			// load user's friends and partners
			$this->db->user->find($this->auth->getIdentity()->id);
			$all = array_merge($this->db->user->getUserFriends(), $this->db->user->getUserPartners());

			if($return == 'json')
				ksort($all); // sort friends / partners

			// filter out what we dont need
			$result = array();
			foreach($all as $row)
				$result[$row->getId()] = array('name' => $row->getName());

			// return array
			if($return == 'array')
				return array_keys($result);

			// return friends + partners
			return \Petolio_Service_Util::json(array('success' => true, 'users' => $result));
		}

		/**
		 * Magical call
		 * @param string $func Function
		 * @param array $arg Arguments
		 */
		public function __call($func, $arg) {
			return \Petolio_Service_Util::json(array('success' => false, 'msg' => "Function: {$func}() does not exist in " . get_class($this)));
		}
	}
}