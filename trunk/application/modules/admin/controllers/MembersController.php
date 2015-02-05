<?php

class MembersController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $req = null;

	private $db = null;
	private $op = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->sess = new Petolio_Model_PoSessions();
		$this->db->user_cat = new Petolio_Model_PoUsersCategories();
		$this->db->user_rgh = new Petolio_Model_PoFieldRights();
		$this->db->countries = new Petolio_Model_PoCountries();
		$this->db->sets = new Petolio_Model_DbTable_PoAttributeSets();

		// advanced filter operators
		$this->op = array(
			'1' => "> %d",
			'2' => ">= %d",
			'3' => "= %d",
			'4' => "<= %d",
			'5' => "< %d",
			'6' => "LIKE '^%s^'",
		);
    }

    public function indexAction() {
        // action body
    }

    /**
     * Filter function used for list users and export csv
     * @return array of where, having and joins params
     */
    private function _filter() {
    	// based on URL
    	$name = $this->req->getParam("name", '');
    	$email = $this->req->getParam("email", '');
    	$type = $this->req->getParam("type", '');
    	$active = $this->req->getParam("active", '');
    	$match = $this->req->getParam("match", '');
    	$from = array($this->req->getParam("from.day", ''), $this->req->getParam("from.month", ''), $this->req->getParam("from.year", ''));
    	$to = array($this->req->getParam("to.day", ''), $this->req->getParam("to.month", ''), $this->req->getParam("to.year", ''));
    	$advanced = $this->req->getParam("advanced", '');

    	// output filters
    	$this->view->name = $name;
    	$this->view->email = $email;
    	$this->view->type = $type;
    	$this->view->active = $active;
    	$this->view->match = $match;
    	$this->view->from = $from;
    	$this->view->to = $to;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.date_created');
    	$this->view->dir = $this->req->getParam('dir', 'desc');

    	// country resources
    	$sort = array();
    	$this->view->country_csv = array();
    	$this->view->country_list = array();
    	foreach($this->db->countries->fetchAll() as $k => $c) {
    		$sort[$k] = $c->getName();
    		$this->view->country_csv[$c->getId()] = $c->getName();
    		$this->view->country_list[] = array('value'=> $c->getId(), 'name' => $c->getName());
    	} array_multisort($sort, SORT_ASC, $this->view->country_list);

    	// gender resource
    	$sort = array();
    	$this->view->gender_csv = array();
    	$this->view->gender_list = array();
    	foreach(array('1' => $this->translate->_("Male"), '2' => $this->translate->_("Female")) as $k => $c) {
    		$sort[$k] = $c;
    		$this->view->gender_csv[$k] = $c;
    		$this->view->gender_list[] = array('value' => $k, 'name' => $c);
    	} array_multisort($sort, SORT_ASC, $this->view->gender_list);

    	// user categories resource
    	$sort = array();
    	$cache = array();
    	$this->view->user_category_csv = array();
    	$this->view->user_category_list = array();
    	foreach($this->db->user_cat->fetchAll() as $k => $c) {
    		$_t = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($c->getName()));
    		$cache[$c->getId()] = $_t;
    		if(isset($cache[$c->getParentId()])) {
    			$sort[0][$k] = $cache[$c->getParentId()];
    			$sort[1][$k] = $_t;
    			$this->view->user_category_csv[$c->getId()] = $_t;
    			$this->view->user_category_list[] = array(
    				'value' => $c->getId(),
    				'category' => $cache[$c->getParentId()],
    				'name' => $_t
    			);
    		}
    	} array_multisort($sort[0], SORT_ASC, $sort[1], SORT_ASC, $this->view->user_category_list);

    	// pet types resource
    	$sort = array();
    	$this->view->pet_category_list = array();
    	foreach($this->db->sets->getAttributeSets('po_pets') as $k => $c) {
    		$_t = Petolio_Service_Util::Tr($c['name']);
    		$sort[$k] = $_t;
    		$this->view->pet_category_list[] = array('value'=> $c['id'], 'name' => $_t);
    	} array_multisort($sort, SORT_ASC, $this->view->pet_category_list);

    	// service types resource
    	$sort = array();
    	$this->view->service_category_list = array();
    	foreach($this->db->sets->getAttributeSets('po_services') as $k => $c) {
    		if(isset($c['group_name']) && strlen($c['group_name']) > 0) {
    			$_t = array(Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($c['group_name'])), Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($c['name'])));
    			$sort[0][$k] = $_t[0];
    			$sort[1][$k] = $_t[1];
    			$this->view->service_category_list[] = array(
    				'value' => $c['id'],
    				'category' => $_t[0],
    				'name' => $_t[1]
    			);
    		}
    	} array_multisort($sort[0], SORT_ASC, $sort[1], SORT_ASC, $this->view->service_category_list);

    	// handle filter
    	$where = array();
    	$having = array();

    	// name
    	if(strlen($name) > 0)
    		$where[] = "a.name LIKE '%".strtolower($name)."%'";

    	// email
    	if(strlen($email) > 0)
    		$where[] = "a.email LIKE '%".strtolower($email)."%'";

    	// type
    	if(strlen($type) > 0)
    		$where[] = "a.type = ".(int)$type;

    	// active
    	if(strlen($active) > 0)
    		$where[] = "a.active = ".(int)$active;

    	// date created from
    	if(strlen($from[0]) > 0 && strlen($from[1]) > 0 && strlen($from[2]) > 0)
    		$where[] = "UNIX_TIMESTAMP(a.date_created) >= ".(int)mktime(0, 0, 0, $from[1], $from[0], $from[2]);

    	// date created to
    	if(strlen($to[0]) > 0 && strlen($to[1]) > 0 && strlen($to[2]) > 0)
    		$where[] = "UNIX_TIMESTAMP(a.date_created) <= ".(int)mktime(23, 59, 59, $to[1], $to[0], $to[2]);

    	// handle advanced
    	$join_pets = false;
    	$join_services = false;
    	if(strlen($advanced) > 0) {
    		$advanced = json_decode(base64_decode($advanced), true);

    		// go through each filter
    		foreach($advanced as $one) {
    			// calc operator and value
    			$end = str_replace('^', '%', sprintf($this->op[$one['operator']], $one['value']));

    			// must we join pets?
    			if(strpos($one['filter'], 'join_pet') !== false)
    				$join_pets = true;

    			// must we join services?
    			if(strpos($one['filter'], 'join_service') !== false)
    				$join_services = true;

    			// age (works with leap years)
    			if($one['filter'] == 'a.date_of_birth')
    				$where[] = "((date_format(now(),'%Y') - date_format(a.date_of_birth,'%Y')) - (date_format(now(),'00-%m-%d') < date_format(a.date_of_birth,'00-%m-%d'))) " . $end;

    			// avatar
    			elseif($one['filter'] == 'a.avatar')
    			$where[] = "{$one['filter']} " . ($one['value'] == 0 ? 'IS NULL' : 'IS NOT NULL');

    			// pet count or service count
    			elseif($one['filter'] == 'join_pet_count' || $one['filter'] == 'join_service_count')
    			$having[] = "{$one['filter']} " . $end;

    			// pet category
    			elseif($one['filter'] == 'join_pet_category')
    			$where[] = "x.attribute_set_id " . $end;

    			// service category
    			elseif($one['filter'] == 'join_service_category')
    			$where[] = "y.attribute_set_id " . $end;

    			// the rest
    			else $where[] = $one['filter'] . ' ' . $end;
    		}
    	}

    	// match all or one
    	$where = count($where) > 0 ? implode($match ? " OR " : " AND ", $where) : $where;
    	$having = count($having) > 0 ? implode($match ? " OR " : " AND ", $having) : $having;

    	// return the array
    	return array($where, $having, $join_pets, $join_services);
    }

    /**
     * List users
     */
    public function listUsersAction() {
		// filters
		list($where, $having, $join_pets, $join_services) = $this->_filter();

    	// get users
    	$paginator = $this->db->user->getUsers('paginator', $where, "{$this->view->order} {$this->view->dir}", false, $having, $join_pets, $join_services);
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output users
    	$this->view->users = $paginator;
    }

    /**
     * Export to cvs
     */
    public function exportCsvAction() {
		// filters
		list($where, $having, $join_pets, $join_services) = $this->_filter();

		// get users
		$data = $this->db->user->getUsers('array', $where, "{$this->view->order} {$this->view->dir}", false, $having, $join_pets, $join_services);
		foreach($data as &$one) {
			// skip this data
			unset(
				$one['password'],
				$one['session_id'],
				$one['join_pet_count'],
				$one['join_pet_category'],
				$one['join_service_count'],
				$one['join_service_category']
			);

			// transform this data
			$one['active'] = $one['active'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
			$one['type'] = $one['type'] == 1 ? $this->translate->_('Pet Owner') : $this->translate->_('Service Provider');
			$one['is_admin'] = $one['is_admin'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');
			$one['is_editor'] = $one['is_editor'] == 1 ? $this->translate->_('Yes') : $this->translate->_('No');

			// add this data
			$one['country_id'] = !is_null($one['country_id']) ? $this->view->country_csv[$one['country_id']] : null;
			$one['gender'] = !is_null($one['gender']) ? $this->view->gender_csv[$one['gender']] : null;
			$one['category_id'] = !is_null($one['category_id']) ? $this->view->user_category_csv[$one['category_id']] : null;
		}

		// figure out header
		$header = array_keys(reset($data));
		foreach($header as &$one)
			$one = ucfirst(str_replace(array("_id", "_"), array("", " "), $one));

		// output as csv
		$out = $this->_array_to_CSV($header);
		foreach($data as $one)
			$out .= $this->_array_to_CSV($one);

		// send headers
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=users-".time().".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		// die with data :P
		die($out);
    }

    /**
     * Return csv as string from array
     * @param array $data
     * @return string
     */
    private function _array_to_CSV($data)
    {
    	$outstream = fopen("php://temp", 'r+');
    	fputcsv($outstream, $data, ',', '"');
    	rewind($outstream);
    	$csv = fgets($outstream);
    	fclose($outstream);

    	return $csv;
    }

    /**
     * Add user
     * @return boolean|void
     */
	public function addUserAction() {
		// send form
		$form = new Petolio_Form_Register(true);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// prepare data
		$data = $form->getValues();
		$data['email'] = $data['remail'];
		$data["password"] = sha1($data["password"]);
		$data['active'] = 1;
		unset($data['remail']);

		// save new user
		$this->db->user->setOptions($data)->save(true, true);

		// add user's email field to private
		$this->db->user_rgh->setOptions(array(
			'field_name' => 'email',
			'entry_id' => $this->db->user->getId(),
			'rights' => 2
		))->save();

		// save user in forum
		$data["po_user_id"] = $this->db->user->getId();
		$flux = new Petolio_Service_FluxBB();
		$flux->addUser($data);

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The User has been added successfully.");
		return $this->_redirect('admin/members/list-users');
	}

	/**
	 * Edit User
	 * @return void|boolean
	 */
	public function editUserAction() {
		// based on URL
		$id = (int)$this->req->getParam("id", 0);
		$user = $this->db->user->find($id);
		if(!$user->getId()) {
			$this->msg->messages[] = $this->translate->_("User does not exist.");
			return $this->_redirect('admin/members/list-users');
		}

		// send form
		$form = new Petolio_Form_Profile($user->getType(), $user->getId(), true);
		$form_data = $user->toArray($user); unset($form_data['password']);
		$form->populate($form_data);
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->req->isPost()))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->req->getPost()))
			return false;

		// get data
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'date_of_birth') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else {
				if(!(strlen($line) > 0)) $line = NULL;
			}
		}

		// password check
		if(is_null($data['password'])) unset($data['password']);
		else $data['password'] = sha1($data["password"]);

		// save user
		$user->setOptions($data);
		$user->setDateModified(date('Y-m-d H:i:s'));
		$user->save(false, true);

		// update forum data
		$flux = new Petolio_Service_FluxBB();
		$flux->updateUser(array('username' => $data['name'], 'email' => $data['email']), $user->getId());

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The User has been saved successfully.");
		return $this->_redirect('admin/members/list-users');
	}

	/**
	 * Login user
	 */
	public function loginUserAction() {
		// get credentials
		$credentials = $this->req->getParam("credentials", '');
		if(!$credentials) {
			$this->msg->messages[] = $this->translate->_("Credentials not found.");
    		return $this->_redirect('admin/members/list-users');
    	}

		// logout admin
		$adm = $this->db->user->find($this->auth->getIdentity()->id);
		$adm->setSessionId(null);
		$adm->save(false);

		// clear instance
		$this->auth->clearIdentity();

		// auth adapter
		$authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
		$authAdapter->setTableName('po_users')
			->setIdentityColumn('email')
			->setCredentialColumn('password')
			->setCredentialTreatment('?');

    	// decode credentials
    	list($email, $password) = explode(':', base64_decode($credentials));

    	// set credentials
		$authAdapter->setIdentity($email);
		$authAdapter->setCredential($password);

		// authenticate
		$result = $this->auth->authenticate($authAdapter);

		// if we could authenticate
		if($result->isValid()) {
			// write user in session
			$user = $authAdapter->getResultRowObject();
			$this->auth->getStorage()->write($user);

			// set or update session_id
			$usr = $this->db->user->find($user->id);
			$usr->setSessionId(Zend_Session::getId());
			$usr->save();

			// set redirect location
			$session = new Zend_Session_Namespace('Petolio_Redirect');
			$session->redirect = $this->getFrontController()->getBaseUrl();

			// forum login
			$flux = new Petolio_Service_FluxBB();
			$flux->login($user->id);

			// in case the user doesnt have a forum account redirect here as well
			unset($session->redirect);
			return $this->_redirect();

		// if we couldnt authenticate
		} else {
			$this->msg->messages[] = $this->translate->_("Could not authenticate.");
			return $this->_redirect('admin');
		}
	}

	/**
	 * Resend Confirmation Mail
	 */
	public function resendMailAction() {
		// based on URL
		$id = (int)$this->req->getParam("id", 0);
		$user = $this->db->user->find($id);
		if(!$user->getId()) {
			$this->msg->messages[] = $this->translate->_("User does not exist.");
			return $this->_redirect('admin/members/list-users');
		}

		// email user
		$email = new Petolio_Service_Mail();
		$email->setRecipient($user->getEmail());
		$email->setTemplate('users/register');
		$email->petolioLink = PO_BASE_URL;
		$email->activationLink = PO_BASE_URL . 'accounts/activate/hash/' . sha1($user->getPassword() . $user->getId());
		$email->name = $user->getName();
		$email->send();

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The confirmation email has been resent to the User.");
		return $this->_redirect('admin/members/list-users');
	}

	/**
	 * Promote user
	 */
	public function promoteUserAction() {
		// based on URL
		$id = (int)$this->req->getParam("id", 0);
		$user = $this->db->user->find($id);
		if(!$user->getId()) {
			$this->msg->messages[] = $this->translate->_("User does not exist.");
			return $this->_redirect('admin/members/list-users');
		}

		// set as service provider
		$user->setType(2)->save();

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("User was promoted to Service Provider.");
		return $this->_redirect('admin/members/list-users');
	}

	/**
	 * Switch user
	 */
	public function switchUserAction() {
		// based on URL
		$id = (int)$this->req->getParam("id", 0);
		$user = $this->db->user->find($id);
		if(!$user->getId()) {
			$this->msg->messages[] = $this->translate->_("User does not exist.");
    		return $this->_redirect('admin/members/list-users');
    	}

    	// set switch
    	$switch = $user->getActive() == 1 ? 0 : 1;
    	$user->setActive($switch)->save();

    	// msg and redirect
    	$this->msg->messages[] = $switch == 1 ? $this->translate->_("User was activated.") : $this->translate->_("User was deactivated.");
    	return $this->_redirect('admin/members/list-users');
	}

	/**
	 * Ban user
	 */
	public function banUserAction() {
		// based on URL
		$id = (int)$this->req->getParam("id", 0);
		$user = $this->db->user->find($id);
		if(!$user->getId()) {
			$this->msg->messages[] = $this->translate->_("User does not exist.");
			return $this->_redirect('admin/members/list-users');
		}

		// decide
		$decide = $user->getIsBanned() == 1 ? 0 : 1;
		$flux = new Petolio_Service_FluxBB();

		// ban action
		if($decide == 1) {
			// force logout
			$session = $this->db->sess->find($user->getSessionId());
			if($session->getId()) {
				$session->deleteRowByPrimaryKey();
				$user->setSessionId(new Zend_Db_Expr('NULL'))->save();
			}

			// ban in forum
			$flux->banUser($user->getId(), $this->auth->getIdentity()->id);

		// unban action
		} else
			$flux->unbanUser($user->getId());

		// set banned
		$user->setIsBanned($decide)->save();

		// msg and redirect
		$this->msg->messages[] = $decide == 1 ? $this->translate->_("User was banned.") : $this->translate->_("User was unbanned.");
		return $this->_redirect('admin/members/list-users');
	}
}