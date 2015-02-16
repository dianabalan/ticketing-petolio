<?php

/**
 * Image Switch Controller
 *
 * @author Seth
 * @version 0.2
 */
class ImgswController extends Zend_Controller_Action
{
	private $translate = null;
	private $id = null;
	private $path = null;
	private $url = null;
	private $db = null;
	private $index = array();
	private $total = 0;
	private $pos = 0;
	private $config = null;
	private $ds = null;
	private $dash_dir = null;

	public function init() {
		// load translate and request
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->id = $this->getRequest()->getParam('id');
		$this->path = $this->getRequest()->getParam('path');
		$this->url = $this->getRequest()->getParam('url');
		$this->config = Zend_Registry::get("config");
		$this->ds = DIRECTORY_SEPARATOR;

		// define as standard class
		$this->db = new stdClass();

		// load models
		$this->db->file = new Petolio_Model_PoFiles();
		$this->db->folder = new Petolio_Model_PoFolders();
		$this->db->user = new Petolio_Model_PoUsers();
		$this->db->service = new Petolio_Model_PoServices();
		$this->db->microsite = new Petolio_Model_PoMicrosites();
		$this->db->help = new Petolio_Model_PoHelp();
		$this->db->gallery = new Petolio_Model_PoGalleries();

		// set dash dir
		$this->dash_dir = "..{$this->ds}data{$this->ds}userfiles{$this->ds}dashboard{$this->ds}";
	}

	/**
	 * Build Gallery Index
	 * @param string $type img or aud or vid
	 */
	private function buildIndex($type) {
		// get results
		$index = $this->db->file->getMapper()->fetchList("folder_id = '{$this->db->file->getFolderId()}' AND type = '{$type}'", ($type == 'image' ? "date_created ASC" : "id ASC"));
		
		// if video we need only the working ones
		if($type == 'video') {
			// youtube wrapper
			$youtube = Petolio_Service_YouTube::factory('Master');
			$youtube->CFG = array(
				'username' => $this->config["youtube"]["username"],
				'password' => $this->config["youtube"]["password"],
				'app' => $this->config["youtube"]["app"],
				'key' => $this->config["youtube"]["key"]
			);

			// get folder
			$this->db->folder->find($this->db->file->getFolderId());

			// path is pet
			if ($this->db->folder->getPetId() && $this->db->folder->getPetId() > 0) {
				$dust = array();
				foreach($this->db->folder->getMapper()->getBreadcrumbs($this->db->folder->getTraceback()) as $bread) {
					if($bread['name'] == 'root')
						continue;

					$dust[] = $bread['name'];
				}

				$dirt = count($dust) > 0 ? implode($this->ds, $dust) . $this->ds . $this->db->folder->getName() : "videos";
				$upload_dir = "..{$this->ds}data{$this->ds}userfiles{$this->ds}pets{$this->ds}{$this->db->folder->getPetId()}{$this->ds}{$dirt}{$this->ds}";

			// other (microsite / service)
			} elseif($this->db->folder->getName() == 'service' || $this->db->folder->getName() == 'microsite') {
				// get microsite / service
				$result = reset($this->db->{$this->db->folder->getName()}->fetchList("folder_id = '{$this->db->folder->getId()}'"));

				// set upload dir
				$upload_dir = "..{$this->ds}data{$this->ds}userfiles{$this->ds}{$this->db->folder->getName()}s{$this->ds}{$result->getId()}{$this->ds}";

			// questions
			} elseif($this->db->folder->getName() == 'question') {
				// get question
				$result = reset($this->db->help->fetchList("folder_id = '{$this->db->folder->getId()}'"));

				// set upload dir
				$upload_dir = "..{$this->ds}data{$this->ds}userfiles{$this->ds}help{$this->ds}{$result->getId()}{$this->ds}";

			// gallery
			} else {
				// get gallery
				$result = reset($this->db->gallery->fetchList("folder_id = '{$this->db->folder->getId()}'"));

				// set upload dir
				$upload_dir = "..{$this->ds}data{$this->ds}userfiles{$this->ds}galleries{$this->ds}{$result->getId()}{$this->ds}";
			}

			// iterate over every video
			foreach($index as $idx => $one) {
				// get the cached entry
				$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir);

				// error? skip and remove from list
				if(!is_object($entry)) {
					unset($index[$idx]);
					continue;
				}
			}
		}

		// count total
		$this->total = count($index);

		// build index
		$i = 0;
		foreach($index as $file) {
			$this->index[$i] = array(
				$file->getId(),
				$this->format($file->getType(), $file),
				$file->getOwnerId()
			);

			if($this->db->file->getId() == $file->getId())
				$this->pos = $i;

			$i++;
		}
	}

	/**
	 * Format file path based on type
	 * @param string $type File Type
	 * @param object $file File Object
	 * @return string
	 */
	private function format($type, $file) {
		$folder = $this->db->folder->find($file->getFolderId());
		return $type == 'video' ?
			str_replace('{video}', pathinfo($file->getFile(), PATHINFO_FILENAME), $this->path) :
			str_replace('{parent}', $folder->getName(),
				str_replace('{'. $type . '}', $file->getFile(), $this->path));
	}

	/**
	 * Send json for the imgsw ajax requests
	 * @param string $type img or vid
	 * @param string $error_1
	 * @param string $error_2
	 */
	private function load($type, $error_1, $error_2) {
		// external file
		if($this->id == 'external') {
			Petolio_Service_Util::json(array(
				'success' => true,
				'data' => array(array('null', $this->path, 'null')),
				'total' => 1,
				'pos' => 0
			));
			return ;
		}

		// get the file
		$this->db->file->find($this->id);

		// throw out error if file does not exist
		if(is_null($this->db->file->getId()))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $error_1));

		// load owner of the file
		$this->db->user->find($this->db->file->getOwnerId());

		// throw out error if owner does not exist
		if(is_null($this->db->user->getId()))
			return Petolio_Service_Util::json(array('success' => false, 'msg' => $error_2));

		// build index
		$this->buildIndex($type);

		// return output
		Petolio_Service_Util::json(array(
			'success' => true,
			'data' => $this->index,
			'total' => $this->total,
			'pos' => $this->pos
		));
	}

	/**
	 * Friendly GD error
	 * @param string $text
	 */
	private function gdError($text) {
		// Set the content-type
		header('Content-Type: image/png');

		// get dimensions
		list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["pic"]);
		$h = 200;

		// Create the image
		$im = imagecreatetruecolor($w, 200);

		// Create some colors
		$bg_color = imagecolorallocate($im, 255, 255, 255);
		$font_color = imagecolorallocate($im, 68, 68, 68);

		// fill rectangle
		imagefilledrectangle($im, 0, 0, $w, $h, $bg_color);

		// font
		$font = "..{$this->ds}library{$this->ds}tcpdf{$this->ds}fonts{$this->ds}arial.ttf";
		$font_size = 11;

		// append text error
		$text = $this->translate->_("Petolio link error:") . " " . $text;

		// box dimensions
		$box = imagettfbbox($font_size, 0, $font, $text);
		$textwidth = abs($box[4] - $box[0]);
		$textheight = abs($box[5] - $box[1]);
		$xcord = ($w/2)-($textwidth/2)-2;
		$ycord = ($h/2)+($textheight/2);

		// Add the text
		imagettftext($im, $font_size, 0, $xcord, $ycord, $font_color, $font, $text);

		// Using imagepng() results in clearer text compared with imagejpeg()
		imagepng($im);
		imagedestroy($im);

		// terminate script here
		exit;
	}

	/**
	 * Index
	 */
	public function indexAction() {
	}

	/**
	 * Img
	 */
	public function imgAction() {
		$this->load('image', $this->translate->_("Image not found."),
			$this->translate->_("Image Owner not found."));
	}

	/**
	 * Aud
	 */
	public function audAction() {
		$this->load('audio', $this->translate->_("Audio not found."),
			$this->translate->_("Audio Owner not found."));
	}

	/**
	 * Vid
	 */
	public function vidAction() {
		$this->load('video', $this->translate->_("Video not found."),
			$this->translate->_("Video Owner not found."));
	}

	/**
	 * External
	 */
	public function extAction() {
		// decode url
		$url = base64_decode($this->url);

		// upload?
		if(strpos($url, 'images/userfiles/dashboard/') !== false) {
			// i trust this
			$file = pathinfo($url, PATHINFO_BASENAME);
			$props = @getimagesize($this->dash_dir . $file);

			// file not found? no properties?
			if(!$props)
				$this->gdError($this->translate->_('Uploaded file cannot be found.'));

			// send headers and read file
			header("Content-type: {$props['mime']}");
			readfile($this->dash_dir . $file);

		// external image
		} else {
			// start output buffering
			ob_start();

			// get picture using curl
			$ch = curl_init($url);

			// set URL and other appropriate options
			$options = array(
				CURLOPT_HEADER => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_TIMEOUT => 60  // 1 minute timeout (should be enough)
			);

			// set options and execute
			curl_setopt_array($ch, $options);
			$connection = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);

			// get image and clear buffer
			$pic = ob_get_contents();
			ob_end_clean();

			// did you connect remotely?
			if($connection === false)
				$this->gdError($this->translate->_('Get Headers failed'));

			// check for correct picture content type
			if(!($info['content_type'] == 'image/gif'
					|| $info['content_type'] == 'image/jpeg'
					|| $info['content_type'] == 'image/png'))
				$this->gdError($this->translate->_('URL is not an image'));

			// constant matrix
			$transform = array(
				'image/gif' => IMAGETYPE_GIF,
				'image/jpeg' => IMAGETYPE_JPEG,
				'image/png' => IMAGETYPE_PNG
			);

			// make a thumbnail and display from the picture
			$props = @getimagesize($url);
			list($w, $h) = explode('x', $this->config["thumbnail"]["general"]["pic"]);
			if($props[0] > $w || $props[1] > $h) {
				Petolio_Service_Image::output($pic, '', array(
					'type'   => $transform[$info['content_type']],
					'width'   => $w,
					'height'  => $h,
					'method'  => THUMBNAIL_METHOD_SCALE_MAX
				));

			// else display directly
			} else {
				header("Content-type: {$props['mime']}");
				echo $pic;
			}
		}

		// terminate the script here
		exit;
	}
}