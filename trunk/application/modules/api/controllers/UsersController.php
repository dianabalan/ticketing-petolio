<?php

class UsersController extends Petolio_Rest_Controller {

	/**
	 * The index action handles index/list requests; it should respond with a
	 * list of the requested resources.
	 */
	public function indexAction() {
		$data = array('status' => 'success');
		$data['response'] = $this->listUsers();
		$this->sendResponse($data);
	}
	
	/**
	 * Retrieve a list of resources, or, if an identifier is present, view a single resource
	 * If the ID parameter is set and it isn't numeric then tries to call the function
	 * @see Petolio_Rest_Controller::getAction()
	 */
	public function getAction() {
		parent::getAction();

		$id = $this->_getParam('id', false);

		$data = array();
		if ( $id && is_numeric($id) ) {
			$users = new Petolio_Model_PoUsers();
			$data = $users->find($id)->toArray();
		} elseif ($id) {
			$data = call_user_func(array($this, $id));
		} else {
			$data = $this->listUsers();
		}
		$this->sendResponse($data);
	}

	/**
	 * Create new entry or call a different action
	 */
	public function postAction() {
		parent::postAction();

		$id = $this->_getParam('id', false);
		
		$data = array('status' => 'success');
		if ( !$id ) {
			// create new entry
			try {
				$data = $this->register();
			} catch (Exception $e) {
				$message = "Can't create new entry!";
				$this->getLog()->err($message . chr(10) .
						$e->getMessage() . chr(10) .
						$e->getTraceAsString());
				return $this->returnError($message);
			}
		} else {
			// call another method
			$data = call_user_func(array($this, $id));
		}
		$this->sendResponse($data);
	}

	/**
	 * Update an existing entry 
	 */
	public function putAction() {
		parent::putAction();

		$id = $this->_getParam('id', false);
		
		if ( $id && is_numeric($id) ) {
			$data = array('status' => 'success');
			
			$user = new Petolio_Model_PoUsers();
			$user->find($id);
			
			// lets check if there is a password change
			if ( isset($_REQUEST["password"]) && strlen($_REQUEST["password"]) > 0 
					&& strcasecmp($_REQUEST["password"], $user->getPassword()) != 0 ) {
				$_REQUEST["password"] = sha1($_REQUEST["password"]);
			}
			
			$user->setOptions($_REQUEST);
			$user->setDateModified(date('Y-m-d H:i:s'));
			$user->save(false, false);
			
			$data = $user->toArray();
			
			$this->sendResponse($data);
		} elseif ( $id ) {
			// call another method
			$data = call_user_func(array($this, $id));
		} else {
			return $this->returnError("No identifier found.");
		}
	}

	/**
	 * tries to authenticate a user after email and password
	 * @return NULL or the authenticated user
	 */
	protected function authenticate() {
		$users = new Petolio_Model_PoUsers();
		$entries = $users->fetchList("email = '{$_REQUEST["email"]}' AND password = SHA1('{$_REQUEST["password"]}')");
		if (count($entries) > 0) {
			$user = reset($entries);
			return $user->toArray();
		}
		return null;
	}
	
	/**
	 * registers a new user
	 * @return error or the registered user
	 */
	protected function register() {
		// do psswd
		$_REQUEST["password"] = sha1($_REQUEST["password"]);

		$users = new Petolio_Model_PoUsers();
		try {
			$users->setOptions($_REQUEST)->save(true, true);
			
			// add user's email field to private
			$rights = new Petolio_Model_PoFieldRights();
			$rights->setOptions(array(
					'field_name' => 'email',
					'entry_id' => $users->getId(),
					'rights' => 2
			))->save();
			
			// save user in forum
			$_REQUEST["po_user_id"] = $users->getId();
			$flux = new Petolio_Service_FluxBB();
			$flux->addUser($_REQUEST);

			// email user
			$email = new Petolio_Service_Mail();
			$email->setRecipient($_REQUEST["email"]);
			$email->setTemplate('users/register');
			$email->petolioLink = PO_BASE_URL;
			$email->activationLink = PO_BASE_URL . 'accounts/activate/hash/' . sha1($_REQUEST["password"] . $users->getId());
			$email->name = $_REQUEST["name"];
			$email->send();
				
		} catch (Exception $e) {
			$message = "Can't register the submitted user!";
			$this->getLog()->err($message . chr(10) .
					$e->getMessage() . chr(10) .
					$e->getTraceAsString());
			return $this->returnError($message);
		}
		return $users->toArray();
	}
	
	/**
	 * creates an sql where statement from the submitted array
	 * @param array $data
	 * @return string
	 */
	private function filter($data) {
		$sql = "1=1";
		foreach ($data as $key => $value) {
			$sql .= " AND {$key} = '{$value}'";
		}
		return $sql;
	}
	
	protected function listUsers() {
		$users = new Petolio_Model_PoUsers();
		return $users->fetchListToArray($this->filter($_REQUEST));
	}

	/**
	 * Send reset password link to the user.
	 */
	protected function resetPassword() {
		$users = new Petolio_Model_PoUsers();
		$result = $users->getMapper()->findByField('email', $_REQUEST['email'], $users);
		if (is_array($result) && count($result) > 0) {
			// save timestamp (link active for 7 days)
			$user = reset($result);
			$user->setDateForgot(time())->save();
		
			// send the email
			$email = new Petolio_Service_Mail();
			$email->setRecipient($user->getEmail());
			$email->setTemplate('users/recover');
			$email->activationLink = PO_BASE_URL . 'accounts/recover/hash/' . sha1($user->getPassword() . $user->getId());
			$email->name = $user->getName();
			$email->base_url = PO_BASE_URL;
			$email->send();
		
			// msg
			$data = array('status' => 'success');
			$data['message'] = $this->translate->_("We have sent you an e-mail with instructions on how to reset your password.");
			$this->sendResponse($data);
		} else {
			$data = array('status' => 'failed');
			$data['message'] = $this->translate->_("User does not exists.");
			$this->sendResponse($data);
		}
	}
	
	/**
	 * upload new avatar image
	 */
	protected function avatar() {
		$user = new Petolio_Model_PoUsers();
		$user->find($this->getRequest()->getParam('user_id', 0));
		if ( !$user ) {
			return $this->returnError($this->translate->_("User not found."));
		}
		
		$data = array('status' => 'success');
		
		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = BASE_PATH . "{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$user->getId()}{$ds}";
		
		// create the users id directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				return $this->returnError($this->translate->_("There was an critical error regarding the creation of the user's folder on disk."));
			}
		}
		
		// get adapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('IsImage', false);
		
		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);
		
		// check if files have exceeded the limit
		if (!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				return $this->returnError(sprintf($this->translate->_("Your picture exceed the maximum size limit allowed (%s)"), $config['phpSettings']['upload_max_filesize']));
			}
		}
		
		// no file ?
		if(!$adapter->getFileName()) {
			return $this->returnError($this->translate->_("Please select a picture file to upload."));
		}
		
		// pre-process file
		$file = $adapter->getFileName();
		$this->getLog()->debug("Filename: " . $file);
		$new_filename = md5(time()) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);
		$this->getLog()->debug("NEW Filename: " . $new_filename);
		$adapter->clearFilters();
		$adapter->addFilter('Rename', array('target' => $upload_dir . $new_filename, 'overwrite' => true));
		
		// error on upload ?
		if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) {
			return $this->returnError(reset($adapter->getMessages()));
		}
		
		// process uploaded picture
		$pic = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
		
		// delete previous picture
		if(!is_null($user->getAvatar())) {
			@unlink($upload_dir . $user->getAvatar());
			@unlink($upload_dir . 'thumb_' . $user->getAvatar());
		}
		
		$props = @getimagesize($pic);
		
		// make thumbnail
		list($w, $h) = explode('x', $config["thumbnail"]["account"]["small"]);
		Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN,
				'valign'  => THUMBNAIL_ALIGN_TOP,
				'halign'  => THUMBNAIL_ALIGN_CENTER
			));
		
		// make big
		list($w, $h) = explode('x', $config["thumbnail"]["account"]["big"]);
		if($props[0] > $w || $props[1] > $h) {
			Petolio_Service_Image::output($pic, $pic, array(
					'type'   => IMAGETYPE_JPEG,
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MIN,
					'valign'  => THUMBNAIL_ALIGN_TOP,
					'halign'  => THUMBNAIL_ALIGN_CENTER
				));
		}
		
		// save avatar
		$user->setAvatar(pathinfo($pic, PATHINFO_BASENAME))->save();
		
		// return message
		$data['message'] = $this->translate->_("Your profile picture has been uploaded successfully.");
		return $data;
	}
}