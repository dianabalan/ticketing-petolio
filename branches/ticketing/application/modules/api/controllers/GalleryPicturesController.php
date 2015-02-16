<?php

class GalleryPicturesController extends Petolio_Rest_Controller {

	/**
	 * The index action handles index/list requests; it should respond with a
	 * list of the requested resources.
	 */
	public function indexAction() {
		$data = array();
		
		$gallery_id = $this->getRequest()->getParam('gallery_id', null);
		if ($gallery_id && is_numeric($gallery_id)) {
			$gallery = new Petolio_Model_PoGalleries();
			$gallery->find($gallery_id);
			if($gallery) {
				$files = new Petolio_Model_PoFiles();
				$data = $files->fetchListToArray("type = 'image' AND folder_id = '{$gallery->getFolderId()}'", "date_created ASC");
			} else {
				return $this->returnError($this->translate->_("Gallery does not exist."));
			}
		}
		
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
			$files = new Petolio_Model_PoFiles();
			$data = $files->find($id)->toArray();
		} elseif ($id) {
			$data = call_user_func(array($this, $id));
		} else {
			return $this->returnError($this->translate->_('No action specified.'));
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
				// load gallery
				$gallery = new Petolio_Model_PoGalleries();
				$gallery->find($this->getRequest()->getParam('gallery_id', 0));
				if (!$gallery) {
					return $this->returnError($this->translate->_("Gallery parameter not submitted or gallery not found."));
				}

				// needed upfront
				$ds = DIRECTORY_SEPARATOR;
				$upload_dir = BASE_PATH . "{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";
				
				// create a folder for our gallery if not exists
				$gallery_folder = null;
				$folders = new Petolio_Model_PoFolders();
				if ($gallery->getFolderId()) {
					$search_vars = array('id' => $gallery->getFolderId());
					$gallery_folder = $folders->getMapper()->getDbTable()->findFolders($search_vars);
				}
				if (!isset($gallery_folder)) {
					// add the folder
					$vars = array('name' => $gallery->getId(), 'petId' => null, 'ownerId' => $gallery->getOwnerId(), 'parentId' => 0);
					$gallery_folder = $folders->getMapper()->getDbTable()->addFolder($vars);
				
					// save the folder in our gallery too
					$gallery->setFolderId($gallery_folder->getId());
					$gallery->save();
				}

				// create the galleries directory
				if (!file_exists(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
					if (!mkdir(pathinfo($upload_dir, PATHINFO_DIRNAME))) {
						return $this->returnError($this->translate->_("There was an critical error regarding the creation of the galleries folder on disk."));
					}
				}
				
				// create the gallery's directory
				if (!file_exists($upload_dir)) {
					if (!mkdir($upload_dir)) {
						return $this->returnError($this->translate->_("There was an critical error regarding the creation of the gallery's folder on disk."));
					}
				}
				
				// prepare upload files
				$i = 0;
				$errors = array();
				$success = array();
				
				// get addapter
				$adapter = new Zend_File_Transfer_Adapter_Http();
				$adapter->setDestination($upload_dir);
				$adapter->addValidator('IsImage', false);
				
				// getting the max filesize
				$config = Zend_Registry::get("config");
				$size = $config['max_filesize'];
				$adapter->addValidator('Size', false, $size);
				
				// check if files have exceeded the limit
				if (!$adapter->isValid()) {
					$msg = $adapter->getMessages();
					if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
						return $this->returnError(sprintf($this->translate->_("Your picture / pictures exceed the maximum size limit allowed (%s), nothing was uploaded."), $config['phpSettings']['upload_max_filesize']));
					}
				}
				
				// upload each file
				foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
					$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);
				
					$adapter->clearFilters();
					$adapter->addFilter('Rename',
							array('target' => $upload_dir . $new_filename, 'overwrite' => true));
				
					if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME)))
						$errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
					else
						$success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
				}
				
				// go through each picture
				foreach($success as $original => $pic) {
					// resize original picture if bigger
					$props = @getimagesize($pic);
					list($w, $h) = explode('x', $config["thumbnail"]["gallery"]["pic"]);
					if($props[0] > $w || $props[1] > $h) {
						Petolio_Service_Image::output($pic, $pic, array(
							'type'    => IMAGETYPE_JPEG,
							'width'   => $w,
							'height'  => $h,
							'method'  => THUMBNAIL_METHOD_SCALE_MAX
						));
					}
				
					// make big thumbnail
					list($w, $h) = explode('x', $config["thumbnail"]["gallery"]["big"]);
					Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
						'type'    => IMAGETYPE_JPEG,
						'width'   => $w,
						'height'  => $h,
						'method'  => THUMBNAIL_METHOD_SCALE_MAX
					));
				
					// make small thumbnail
					list($w, $h) = explode('x', $config["thumbnail"]["gallery"]["small"]);
					Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'small_' . pathinfo($pic, PATHINFO_BASENAME), array(
						'type'    => IMAGETYPE_JPEG,
						'width'   => $w,
						'height'  => $h,
						'method'  => THUMBNAIL_METHOD_SCALE_MIN
					));
				
					// save every file in db
					$files = new Petolio_Model_PoFiles();
					$files->setOptions(array(
							'file' => pathinfo($pic, PATHINFO_BASENAME),
							'type' => 'image',
							'size' => filesize($pic) / 1024,
							'folder_id' => $gallery_folder->getId(),
							'owner_id' => $gallery->getOwnerId(),
							'description' => $original
					))->save();
				}
				
				// save messages
				$data['response']['errors'] = $errors;
				$data['response']['success'] = $success;
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
		return $this->returnError("Action not implemented. Use delete and post instead.");
	}
	
	/**
	 * The delete action handles DELETE requests and receives an 'id'
	 * parameter; it should delete the resource identified by the 'id' value.
	 */
	public function deleteAction() {
		parent::deleteAction();

		$id = $this->_getParam('id', false);

		if ( $id && is_numeric($id) ) {
			$data = array('status' => 'success');
			
			try {
				$pic = new Petolio_Model_PoFiles();
				$pic->find($id);
				
				$upload_dir = BASE_PATH . "{$ds}data{$ds}userfiles{$ds}galleries{$ds}{$gallery->getId()}{$ds}";
				
				// delete from hdd
				@unlink($upload_dir . $pic->getFile());
				@unlink($upload_dir . 'thumb_' . $pic->getFile());
				@unlink($upload_dir . 'small_' . $pic->getFile());
				
				// delete all comments, likes and subscriptions
				$comments = new Petolio_Model_PoComments();
				$ratings = new Petolio_Model_PoRatings();
				$subscriptions = new Petolio_Model_PoSubscriptions();
				$comments->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
				$ratings->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
				$subscriptions->getMapper()->getDbTable()->delete("scope = 'po_files' AND entity_id = '{$pic->getId()}'");
				
				$pic->deleteRowByPrimaryKey();
			} catch (Exception $e) {
				$message = "Can't delete the entry!";
				$this->getLog()->err($message . chr(10) .
						$e->getMessage() . chr(10) .
						$e->getTraceAsString());
				return $this->returnError($message);
			}
			$this->sendResponse($data);
		} else {
			return $this->returnError("No identifier found.");
		}
	}
	
}