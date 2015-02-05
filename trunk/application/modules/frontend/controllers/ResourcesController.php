<?php

/**
 * Resources Controller
 *
 * @author Seth
 * @version 0.1
 */
class ResourcesController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $db = null;
	private $id = 0;

	public function init() {
		// load translate and request
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->req = $this->getRequest();

		// define as standard class
		$this->db = new stdClass();

		// load models
		$this->db->file = new Petolio_Model_PoFiles();
		$this->db->folder = new Petolio_Model_PoFolders();
		$this->db->pet = new Petolio_Model_PoPets();
		$this->db->service = new Petolio_Model_PoServices();
		$this->db->microsite = new Petolio_Model_PoMicrosites();
		$this->db->gallery = new Petolio_Model_PoGalleries();
	}

	/**
	 * Index
	 */
	public function indexAction() {
	}

	public function audAction() {
		// disable layout
		$this->_helper->layout->disableLayout();

		// get file
		$file = $this->req->getParam("file", '');
		if(!strlen($file) > 0)
			exit;

		// output decoded file
		$this->view->file = base64_decode($file);
	}

	/**
	 * User Info
	 */
	public function nfoAction() {
		// not logged in ? BYE
		if (!$this->auth->hasIdentity())
			return $this->_redirect('site');
		
		// get all the files by resource level
		$files = $this->db->file->getMapper()->resourceLevel($this->auth->getIdentity()->id);

		// return error if no files found
		if(!(count($files) > 0))
			return Petolio_Service_Util::json(array(
				'success' => false,
				'msg' => $this->translate->_('No resources found.')
			));

		// empty arrays yay
		$pets = array();
		$services = array();
		$microsites = array();
		$galleries = array();

		// count category
		$c_pets = 0;
		$c_services = 0;
		$c_microsites = 0;
		$c_galleries = 0;

		// count subcategory
		$s_pets = array();
		$s_services = array();
		$s_microsites = array();
		$s_galleries = array();

		// count files
		$f_pets = array();
		$f_services = array();
		$f_microsites = array();
		$f_galleries = array();

		// assign each file to its category / subcategory / type
		foreach($files as $file) {
					// used for sorting
			if($file['type'] == 'image'){
				$type = '0_+_' . $this->translate->_('Images');
				$hash = '#img/';
				$micro = '/index-pictures' . $hash;
			}
			if($file['type'] == 'video'){
				$type = '1_+_' . $this->translate->_('Videos');
				$hash = '#vid/';
				$micro = '/index-videos' . $hash;
			}
			if($file['type'] == 'audio'){
				$type = '2_+_' . $this->translate->_('Audios');
				$hash = '#aud/';
				$micro = '/index-audios' . $hash;
			}

			// pets
			if(!is_null($file['pet_name'])) {
				if($file['type'] == 'image') $folder = 'gallery';
				if($file['type'] == 'video') $folder = 'videos';
				if($file['type'] == 'audio') $folder = 'audios';

				$c_pets++;
				@$s_pets[ucfirst($file['pet_name'])]++;
				@$f_pets[ucfirst($file['pet_name'])][$type]++;
				$pets[ucfirst($file['pet_name'])][$type][$file['id'] . "_+_". $file['description']] = array(
					'src' => $this->parse_source($file['type'], "/images/userfiles/pets/{$file['pet_id']}/{$folder}/" . $file['file']),
					'prv' => $this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet'=>$file['pet_id']), 'default', true) . $hash . $file['id']
				);
			}

			// services
			if(!is_null($file['service_name'])) {
				$c_services++;
				@$s_services[ucfirst($file['service_name'])]++;
				@$f_services[ucfirst($file['service_name'])][$type]++;
				$services[ucfirst($file['service_name'])][$type][$file['id'] . "_+_". $file['description']] = array(
					'src' => $this->parse_source($file['type'], "/images/userfiles/services/{$file['service_id']}/" . $file['file']),
					'prv' => $this->view->url(array('controller'=>'services', 'action'=>'view', 'service'=>$file['service_id']), 'default', true) . $hash . $file['id']
				);
			}

			// microsites
			if(!is_null($file['microsite_name'])) {
				$c_microsites++;
				@$s_microsites[ucfirst($file['microsite_name'])]++;
				@$f_microsites[ucfirst($file['microsite_name'])][$type]++;
				$microsites[ucfirst($file['microsite_name'])][$type][$file['id'] . "_+_". $file['description']] = array(
					'src' => $this->parse_source($file['type'], "/images/userfiles/microsites/{$file['microsite_id']}/" . $file['file']),
					'prv' => $this->view->url(array('controller'=>$file['microsite_name']), 'default', true) . $micro . $file['id']
				);
			}

			// galleries
			if(!is_null($file['gallery_name'])) {
				$c_galleries++;
				@$s_galleries[ucfirst($file['gallery_name'])]++;
				@$f_galleries[ucfirst($file['gallery_name'])][$type]++;
				$galleries[ucfirst($file['gallery_name'])][$type][$file['id'] . "_+_". $file['description']] = array(
					'src' => $this->parse_source($file['type'], "/images/userfiles/galleries/{$file['gallery_id']}/" . $file['file']),
					'prv' => $this->view->url(array('controller'=>'galleries', 'action'=>'view', 'gallery'=>$file['gallery_id']), 'default', true) . $hash . $file['id']
				);
			}
		}

		// sort by the key then
		$this->deep_ksort($pets, 2);
		$this->deep_ksort($services, 2);
		$this->deep_ksort($microsites, 2);
		$this->deep_ksort($galleries, 2);

		// put data together
		function __data($t, $p, $s, $m, $g) {
			$o = array();
			if($p) $o[$t->_('Pets')] = $p;
			if($s) $o[$t->_('Services')] = $s;
			if($m) $o[$t->_('Microsites')] = $m;
			if($g) $o[$t->_('Galleries')] = $g;

			return $o;
		}

		// return output
		return Petolio_Service_Util::json(array(
			'success' => true,
			'data' => __data($this->translate, $pets, $services, $microsites, $galleries),
			'c_count' => __data($this->translate, $c_pets, $c_services, $c_microsites, $c_galleries),
			's_count' =>  __data($this->translate, $s_pets, $s_services, $s_microsites, $s_galleries),
			'f_count' => __data($this->translate, $f_pets, $f_services, $f_microsites, $f_galleries),
			'translate' => array(
				// resources dialog
				'view_resources' => $this->translate->_('View Resources'),
				'text' => $this->translate->_('Please select a resource to link. Clicking once will select it while double clicking a resource will open it in a new window for preview.'),
				'resources' => $this->translate->_('Resources'),
				'link_selected' => $this->translate->_('Link Selected'),
				'close' => $this->translate->_('Close'),
				'err' => $this->translate->_('Please select a resource')
			)
		));
	}

	/**
	 * Parse source, if audio we have a base64
	 */
	private function parse_source($t, $s) {
		return $t == 'audio' ? base64_encode($s) : $s;
	}

	/**
	 * deep sorting by key
	 *
	 * @param array $arr the array
	 * @param int $max max depth
	 * @param int $lvl autoincrement level
	 */
	private function deep_ksort(&$arr, $max = 10, $lvl = 0) {
		if($lvl > $max)
			return;

		$lvl++;
		ksort($arr);
		foreach ($arr as &$a)
			if (is_array($a) && !empty($a))
				$this->deep_ksort($a, $max, $lvl);
	}
}